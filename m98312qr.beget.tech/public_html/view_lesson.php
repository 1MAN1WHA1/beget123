<?php
require 'dp.php'; // сессия + $pdo + csrf_token + helpers

require_login();

$user_id  = (int)$_SESSION['user_id'];
$lesson_id = (int)($_GET['id'] ?? 0);

if ($lesson_id <= 0) {
    die("Урок не найден");
}

/** 1) Урок */
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ? LIMIT 1");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lesson) {
    die("Урок не найден");
}

/** 2) Курс */
$course_id = (int)$lesson['course_id'];

$cstmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_course = 1 LIMIT 1");
$cstmt->execute([$course_id]);
$course = $cstmt->fetch(PDO::FETCH_ASSOC);

/** 3) Доступ paid */
$access_check = $pdo->prepare("
    SELECT 1
    FROM orders
    WHERE user_id = ? AND product_id = ? AND status = 'paid'
    LIMIT 1
");
$access_check->execute([$user_id, $course_id]);
$has_access = (bool)$access_check->fetchColumn();

/** 4) Последнее ДЗ */
$lastHw = null;
if ($has_access) {
    $hw = $pdo->prepare("
        SELECT id, file_path, original_name, mime_type, created_at
        FROM homework_submissions
        WHERE user_id = ? AND lesson_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $hw->execute([$user_id, $lesson_id]);
    $lastHw = $hw->fetch(PDO::FETCH_ASSOC);
}

/** 5) Сообщения */
$hwStatus  = (string)($_GET['hw'] ?? '');
$hwOk      = ($hwStatus === 'ok');
$hwDeleted = ($hwStatus === 'deleted');
$hwNone    = ($hwStatus === 'none');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= e($lesson['title'] ?? 'Урок') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">

    <!-- Навигация -->
    <div class="d-flex justify-content-between mb-3">
        <a href="<?= $course ? 'course.php?id=' . (int)$course_id : 'index.php' ?>" class="btn btn-secondary">
            &larr; Назад
        </a>
        <div class="d-flex gap-2">
            <a href="course.php?id=<?= (int)$course_id ?>" class="btn btn-outline-secondary">К курсу</a>
            <a href="profile.php" class="btn btn-outline-primary">Личный кабинет</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <?php if ($course): ?>
                <div class="mb-2 text-muted">
                    Курс: <b><?= e($course['title'] ?? '') ?></b>
                </div>
            <?php endif; ?>

            <h1 class="h3 mb-3"><?= e($lesson['title'] ?? '') ?></h1>

            <?php if (!$has_access): ?>
                <div class="alert alert-danger">
                    Доступ закрыт. Чтобы посмотреть этот урок, купите курс.
                </div>

                <a href="buy_course.php?id=<?= (int)$course_id ?>" class="btn btn-success">
                    Купить курс
                </a>

            <?php else: ?>
                <!-- Видео -->
                <div class="ratio ratio-16x9 mb-3">
                    <video src="<?= e($lesson['video_url'] ?? '') ?>" controls></video>
                </div>

                <hr>
                <h2 class="h5">Домашнее задание</h2>

                <?php if ($hwOk): ?>
                    <div class="alert alert-success">ДЗ загружено ✅</div>
                <?php elseif ($hwDeleted): ?>
                    <div class="alert alert-warning">ДЗ удалено 🗑 Прогресс обновлён.</div>
                <?php elseif ($hwNone): ?>
                    <div class="alert alert-info">ДЗ по этому уроку не найдено.</div>
                <?php endif; ?>

                <!-- Загрузка ДЗ -->
                <form action="upload_homework.php" method="POST" enctype="multipart/form-data" class="mt-3">
                    <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

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
                            <b>Последняя загрузка:</b> <?= e($lastHw['original_name'] ?? '') ?><br>
                            <small class="text-muted"><?= e($lastHw['created_at'] ?? '') ?></small>
                        </div>

                        <?php
                        // безопасный путь: только из homeworks/
                        $path = (string)($lastHw['file_path'] ?? '');
                        $safeDownload = (str_starts_with($path, 'homeworks/')) ? $path : '';
                        ?>

                        <div class="d-flex gap-2">
                            <?php if ($safeDownload !== ''): ?>
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="<?= e($safeDownload) ?>"
                                   target="_blank" rel="noopener">
                                    Скачать последнюю работу
                                </a>
                            <?php endif; ?>

                            <!-- Удаление ДЗ по этому уроку -->
                            <form action="delete_homework.php" method="POST" class="d-inline"
                                  onsubmit="return confirm('Удалить ДЗ по этому уроку? Прогресс пересчитается.');">
                                <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    Удалить ДЗ
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

</div>
</body>
</html>
