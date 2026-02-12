<?php
require 'dp.php';

// bootstrap уже стартует сессию, поэтому session_start НЕ нужен
require_login();

$user_id  = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Неверный метод');
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!csrf_check($token)) {
    die('CSRF токен неверный');
}

$lesson_id = (int)($_POST['lesson_id'] ?? 0);
if ($lesson_id <= 0) {
    die('Некорректный урок');
}

if (empty($_FILES['homework'])) {
    die('Файл не передан');
}

// 1) Урок -> course_id
$stmt = $pdo->prepare("SELECT id, course_id FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lesson) {
    die('Урок не найден');
}

$course_id = (int)$lesson['course_id'];

// 2) Проверка paid-доступа
$access = $pdo->prepare("
    SELECT id
    FROM orders
    WHERE user_id = ? AND product_id = ? AND status = 'paid'
    LIMIT 1
");
$access->execute([$user_id, $course_id]);
if (!$access->fetchColumn()) {
    die('Доступ закрыт: сначала купите курс');
}

// 3) Разрешённые типы: .zip и .docx
$allowedExt = ['zip', 'docx'];

// MIME может отличаться (особенно на хостингах), поэтому проверяем и MIME и расширение
$allowedMime = [
    'application/zip',
    'application/x-zip-compressed',
    'application/octet-stream', // иногда zip определяется так
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$file = $_FILES['homework'];

// 4) Ошибка загрузки
if ($file['error'] !== UPLOAD_ERR_OK) {
    die("Ошибка загрузки: " . (int)$file['error']);
}

// 5) Размер (20MB)
if ($file['size'] > 20 * 1024 * 1024) {
    die("Файл слишком большой (макс 20MB)");
}

// 6) MIME через finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    die("Можно загружать только .zip или .docx");
}

// если MIME не из списка — не стопаем жёстко, но лучше оставить проверку:
if (!in_array($mime, $allowedMime, true)) {
    die("Недопустимый тип файла (MIME): " . htmlspecialchars($mime));
}

// 7) Папка homeworks в public_html
$uploadDirFs     = __DIR__ . '/homeworks/';
$uploadDirPublic = 'homeworks/';

if (!is_dir($uploadDirFs)) {
    die("Папка homeworks не найдена. Создай её в public_html.");
}

// по пользователям
$userDirFs     = $uploadDirFs . 'user_' . $user_id . '/';
$userDirPublic = $uploadDirPublic . 'user_' . $user_id . '/';

if (!is_dir($userDirFs)) {
    mkdir($userDirFs, 0755, true);
}

$newName = 'hw_' . $lesson_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

$destinationFs     = $userDirFs . $newName;
$destinationPublic = $userDirPublic . $newName;

if (!move_uploaded_file($file['tmp_name'], $destinationFs)) {
    die("Не удалось сохранить файл");
}

// 8) Запись в БД
$ins = $pdo->prepare("
    INSERT INTO homework_submissions (user_id, lesson_id, file_path, original_name, mime_type)
    VALUES (?, ?, ?, ?, ?)
");
$ins->execute([$user_id, $lesson_id, $destinationPublic, $file['name'], $mime]);

/**
 * 9) Обновляем прогресс в БД (course_progress)
 * done = кол-во уроков курса, по которым у пользователя есть хотя бы 1 submission
 * total = всего уроков в курсе
 */
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = ?");
$totalStmt->execute([$course_id]);
$totalLessons = (int)$totalStmt->fetchColumn();

$doneStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT hs.lesson_id)
    FROM homework_submissions hs
    JOIN lessons l ON l.id = hs.lesson_id
    WHERE hs.user_id = ? AND l.course_id = ?
");
$doneStmt->execute([$user_id, $course_id]);
$doneLessons = (int)$doneStmt->fetchColumn();

$progress = 0;
if ($totalLessons > 0) {
    // для 2 уроков будет 50 и 100 ровно
    $progress = (int)floor(($doneLessons / $totalLessons) * 100);
    if ($progress > 100) $progress = 100;
}

$up = $pdo->prepare("
    INSERT INTO course_progress (user_id, course_id, progress_percent)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        progress_percent = VALUES(progress_percent),
        updated_at = CURRENT_TIMESTAMP
");
$up->execute([$user_id, $course_id, $progress]);

// назад на урок (как у тебя было)
header("Location: view_lesson.php?id=" . $lesson_id . "&hw=ok");
exit;
