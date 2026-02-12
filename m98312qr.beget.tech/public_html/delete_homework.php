<?php
require 'dp.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

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

// 1) Узнаём course_id у урока (чтобы пересчитать прогресс)
$stmt = $pdo->prepare("SELECT course_id FROM lessons WHERE id = ? LIMIT 1");
$stmt->execute([$lesson_id]);
$course_id = (int)$stmt->fetchColumn();
if ($course_id <= 0) {
    die('Урок не найден');
}

// 2) Берём все сдачи по этому уроку у этого пользователя (чтобы удалить файлы)
$sel = $pdo->prepare("
    SELECT id, file_path
    FROM homework_submissions
    WHERE user_id = ? AND lesson_id = ?
");
$sel->execute([$user_id, $lesson_id]);
$subs = $sel->fetchAll(PDO::FETCH_ASSOC);

if (!$subs) {
    header("Location: view_lesson.php?id={$lesson_id}&hw=none");
    exit;
}

// 3) Удаляем записи из БД
$del = $pdo->prepare("DELETE FROM homework_submissions WHERE user_id = ? AND lesson_id = ?");
$del->execute([$user_id, $lesson_id]);

// 4) Удаляем физические файлы (только из папки homeworks/)
$baseDir = realpath(__DIR__ . '/homeworks');
if ($baseDir) {
    foreach ($subs as $s) {
        $relPath = (string)($s['file_path'] ?? '');

        // Разрешаем удалять только пути, которые начинаются с homeworks/
        if (!str_starts_with($relPath, 'homeworks/')) {
            continue;
        }

        $abs = realpath(__DIR__ . '/' . $relPath);

        // abs может быть false, если файла уже нет
        if ($abs && str_starts_with($abs, $baseDir) && is_file($abs)) {
            @unlink($abs);
        }
    }
}

// 5) Пересчёт прогресса и обновление course_progress
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
    $progress = (int)floor(($doneLessons / $totalLessons) * 100);
    if ($progress > 100) $progress = 100;
}

// Если таблица course_progress у тебя уже создана:
$up = $pdo->prepare("
    INSERT INTO course_progress (user_id, course_id, progress_percent)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        progress_percent = VALUES(progress_percent),
        updated_at = CURRENT_TIMESTAMP
");
$up->execute([$user_id, $course_id, $progress]);

header("Location: view_lesson.php?id={$lesson_id}&hw=deleted");
exit;
