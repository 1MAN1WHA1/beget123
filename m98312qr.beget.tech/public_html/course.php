<?php
require 'dp.php';

$course_id = (int)($_GET['id'] ?? 0);
if ($course_id <= 0) die("Курс не найден");

// курс
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_course = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) die("Курс не найден (проверь is_course=1)");

$user_id = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$has_access = false;

if ($user_id) {
    $access_check = $pdo->prepare("
        SELECT id
        FROM orders
        WHERE user_id = ? AND product_id = ? AND status = 'paid'
        LIMIT 1
    ");
    $access_check->execute([$user_id, $course_id]);
    $has_access = (bool)$access_check->fetchColumn();
}

// уроки
$lessons = [];
$totalLessons = 0;
$doneLessons = 0;
$progressPercent = 0;
$completed = []; // lesson_id => true

if ($has_access) {
    $ls = $pdo->prepare("SELECT id, title FROM lessons WHERE course_id = ? ORDER BY id ASC");
    $ls->execute([$course_id]);
    $lessons = $ls->fetchAll(PDO::FETCH_ASSOC);
    $totalLessons = count($lessons);

    // какие уроки закрыты (есть хотя бы 1 дз)
    $doneList = $pdo->prepare("
        SELECT DISTINCT hs.lesson_id
        FROM homework_submissions hs
        JOIN lessons l ON l.id = hs.lesson_id
        WHERE hs.user_id = ? AND l.course_id = ?
    ");
    $doneList->execute([$user_id, $course_id]);
    $rows = $doneList->fetchAll(PDO::FETCH_COLUMN);

    foreach ($rows as $lid) {
        $completed[(int)$lid] = true;
    }
    $doneLessons = count($completed);

    if ($totalLessons > 0) {
        $progressPercent = (int)floor(($doneLessons / $totalLessons) * 100);
        if ($progressPercent > 100) $progressPercent = 100;
    }

    // сохранить % в БД (по заданию обязательно показывать где хранится)
    $up = $pdo->prepare("
        INSERT INTO course_progress (user_id, course_id, progress_percent)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            progress_percent = VALUES(progress_percent),
            updated_at = CURRENT_TIMESTAMP
    ");
    $up->execute([$user_id, $course_id, $progressPercent]);
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($course['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">

  <div class="mb-3">
    <a href="index.php" class="btn btn-secondary">&larr; Назад на главную</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h3"><?= htmlspecialchars($course['title']) ?></h1>
      <p><?= nl2br(htmlspecialchars($course['description'] ?? '')) ?></p>
      <p><b>Цена:</b> <?= htmlspecialchars($course['price']) ?> ₽</p>

      <?php if (!$user_id): ?>
        <div class="alert alert-warning">Войдите, чтобы купить курс.</div>
        <a href="login.php" class="btn btn-primary">Войти</a>

      <?php elseif (!$has_access): ?>
        <div class="alert alert-danger">Доступ закрыт. Купите курс, чтобы смотреть уроки.</div>
        <a href="buy_course.php?id=<?= (int)$course_id ?>" class="btn btn-success">Купить курс</a>

      <?php else: ?>
        <div class="alert alert-success">Доступ открыт ✅</div>

        <!-- ПРОГРЕСС -->
        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <div class="fw-semibold">Прогресс курса</div>
            <div class="text-muted"><?= (int)$doneLessons ?>/<?= (int)$totalLessons ?> (<?= (int)$progressPercent ?>%)</div>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar"
                 role="progressbar"
                 style="width: <?= (int)$progressPercent ?>%;"
                 aria-valuenow="<?= (int)$progressPercent ?>"
                 aria-valuemin="0"
                 aria-valuemax="100">
              <?= (int)$progressPercent ?>%
            </div>
          </div>
        </div>

        <h2 class="h5 mt-3">Уроки</h2>

        <?php if (!$lessons): ?>
          <p>Уроков пока нет.</p>
        <?php else: ?>
          <ul class="list-group">
            <?php foreach ($lessons as $lesson): ?>
              <?php $lid = (int)$lesson['id']; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="view_lesson.php?id=<?= $lid ?>">
                  <?= htmlspecialchars($lesson['title']) ?>
                </a>

                <?php if (!empty($completed[$lid])): ?>
                  <span class="badge bg-success">ДЗ сдано ✅</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Не сдано</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>

</div>
</body>
</html>
