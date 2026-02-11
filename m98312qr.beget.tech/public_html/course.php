<?php
session_start();
require 'dp.php';

$course_id = (int)($_GET['id'] ?? 0);
if ($course_id <= 0) die("Курс не найден");

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_course = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) die("Курс не найден (проверь is_course=1)");

$user_id = $_SESSION['user_id'] ?? null;
$has_access = false;

if ($user_id) {
    $access_check = $pdo->prepare("
        SELECT id FROM orders
        WHERE user_id = ? AND product_id = ? AND status = 'paid'
        LIMIT 1
    ");
    $access_check->execute([(int)$user_id, $course_id]);
    $has_access = (bool)$access_check->fetchColumn();
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

  <!-- ✅ Кнопка назад -->
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

        <?php
          $ls = $pdo->prepare("SELECT id, title FROM lessons WHERE course_id = ? ORDER BY id ASC");
          $ls->execute([$course_id]);
          $lessons = $ls->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <h2 class="h5 mt-3">Уроки</h2>
        <?php if (!$lessons): ?>
          <p>Уроков пока нет.</p>
        <?php else: ?>
          <ul class="list-group">
            <?php foreach ($lessons as $lesson): ?>
              <li class="list-group-item">
                <a href="view_lesson.php?id=<?= (int)$lesson['id'] ?>">
                  <?= htmlspecialchars($lesson['title']) ?>
                </a>
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
