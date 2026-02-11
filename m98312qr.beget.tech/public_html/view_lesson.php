<?php
session_start();
require 'dp.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}
$user_id = (int)$user_id;

$lesson_id = (int)($_GET['id'] ?? 0);
if ($lesson_id <= 0) {
    die("Урок не найден");
}

// Урок
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lesson) {
    die("Урок не найден");
}

// Курс (чтобы показать название и цену, и чтобы сделать кнопку “назад” на курс)
$course_id = (int)$lesson['course_id'];
$cstmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_course = 1");
$cstmt->execute([$course_id]);
$course = $cstmt->fetch(PDO::FETCH_ASSOC); // может быть null, если курс удалён/не помечен

// Проверка доступа (paid)
$access_check = $pdo->prepare("
    SELECT id FROM orders
    WHERE user_id = ? AND product_id = ? AND status = 'paid'
    LIMIT 1
");
$access_check->execute([$user_id, $course_id]);
$has_access = (bool)$access_check->fetchColumn();

// Последнее ДЗ (если доступ есть)
$lastHw = null;
if ($has_access) {
    $hw = $pdo->prepare("
        SELECT * FROM homework_submissions
        WHERE user_id = ? AND lesson_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $hw->execute([$user_id, $lesson_id]);
    $lastHw = $hw->fetch(PDO::FETCH_ASSOC);
}

$hwOk = (!empty($_GET['hw']) && $_GET['hw'] === 'ok');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($lesson['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">

    <!-- Навигация -->
    <div class="d-flex justify-content-between mb-3">
        <a href="<?= $course ? 'course.php?id=' . (int)$course_id : 'index.php' ?>" class="btn btn-secondary">
            &larr; Назад
        </a>
        <a href="profile.php" class="btn btn-outline-primary">Личный кабинет</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <?php if ($course): ?>
                <div class="mb-2 text-muted">
                    Курс: <b><?= htmlspecialchars($course['title']) ?></b>
                </div>
            <?php endif; ?>

            <h1 class="h3 mb-3"><?= htmlspecialchars($lesson['title']) ?></h1>

            <?php if (!$has_access): ?>
                <div class="alert alert-danger">
                    Доступ закрыт. Чтобы посмотреть этот урок, купите курс.
                </div>

                <a href="buy_course.php?id=<?= (int)$course_id ?>" class="btn btn-success">
                    Купить курс
                </a>

            <?php else: ?>
                <!-- Видео урока -->
                <div class="ratio ratio-16x9 mb-3">
                    <video src="<?= htmlspecialchars($lesson['video_url']) ?>" controls></video>
                </div>

                <!-- Загрузка ДЗ -->
                <hr>
                <h2 class="h5">Сдать домашнее задание</h2>

                <?php if ($hwOk): ?>
                    <div class="alert alert-success">ДЗ загружено ✅</div>
                <?php endif; ?>

                <form action="upload_homework.php" method="POST" enctype="multipart/form-data" class="mt-3">
                    <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id ?>">

                    <div class="mb-3">
                        <label class="form-label">Файл ДЗ (.zip или .docx):</label>
                        <input type="file" name="homework" class="form-control" accept=".zip,.docx" required>
                        <div class="form-text">Разрешены только .zip и .docx</div>
                    </div>

                    <button type="submit" class="btn btn-success">Загрузить ДЗ</button>
                </form>

                <?php if ($lastHw): ?>
                    <div class="mt-4">
                        <div class="alert alert-secondary mb-2">
                            <b>Последняя загрузка:</b>
                            <?= htmlspecialchars($lastHw['original_name']) ?>
                            <br>
                            <small class="text-muted"><?= htmlspecialchars($lastHw['created_at']) ?></small>
                        </div>

                        <?php if (!empty($lastHw['file_path'])): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($lastHw['file_path']) ?>" target="_blank">
                                Скачать последнюю работу
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

</div>
</body>
</html>
