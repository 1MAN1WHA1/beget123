<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id'])) {
    die('Доступ запрещен');
}

$user_id = (int)$_SESSION['user_id'];
$lesson_id = (int)($_POST['lesson_id'] ?? 0);

if ($lesson_id <= 0) {
    die('Некорректный урок');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['homework'])) {
    die('Файл не передан');
}

// 1) Берём урок -> узнаём course_id
$stmt = $pdo->prepare("SELECT id, course_id FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lesson) {
    die('Урок не найден');
}

// 2) Проверяем paid-доступ к курсу
$access = $pdo->prepare("
    SELECT id FROM orders
    WHERE user_id = ? AND product_id = ? AND status = 'paid'
    LIMIT 1
");
$access->execute([$user_id, (int)$lesson['course_id']]);
if (!$access->fetchColumn()) {
    die('Доступ закрыт: сначала купите курс');
}

// 3) Разрешённые типы по заданию: .zip и .docx
$allowedTypes = [
    'application/zip',
    'application/x-zip-compressed',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
$allowedExt = ['zip', 'docx'];

$file = $_FILES['homework'];

// 4) Ошибка загрузки
if ($file['error'] !== UPLOAD_ERR_OK) {
    die("Ошибка загрузки: " . $file['error']);
}

// 5) Размер (например 20MB)
if ($file['size'] > 20 * 1024 * 1024) {
    die("Файл слишком большой (макс 20MB)");
}

// 6) MIME через finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($mime, $allowedTypes, true) || !in_array($ext, $allowedExt, true)) {
    die("Можно загружать только .zip или .docx");
}

// 7) Папка homeworks в public_html
$uploadDirFs = __DIR__ . '/homeworks/';
$uploadDirPublic = 'homeworks/';

if (!is_dir($uploadDirFs)) {
    die("Папка homeworks не найдена. Создай её в public_html.");
}

// Можно хранить по пользователям
$userDirFs = $uploadDirFs . 'user_' . $user_id . '/';
$userDirPublic = $uploadDirPublic . 'user_' . $user_id . '/';

if (!is_dir($userDirFs)) {
    mkdir($userDirFs, 0755, true);
}

$newName = 'hw_' . $lesson_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

$destinationFs = $userDirFs . $newName;
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

header("Location: view_lesson.php?id=" . $lesson_id . "&hw=ok");
exit;
