<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$course_id = (int)($_GET['id'] ?? 0);

if ($course_id <= 0) {
    die("Некорректный курс");
}

// Проверяем, что это курс
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_course = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Курс не найден (проверь is_course=1 у товара)");
}

// Если уже paid — на курс
$paid = $pdo->prepare("SELECT id FROM orders WHERE user_id=? AND product_id=? AND status='paid' LIMIT 1");
$paid->execute([$user_id, $course_id]);
if ($paid->fetchColumn()) {
    header("Location: course.php?id=" . $course_id);
    exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Оплата курса</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">

    <a href="course.php?id=<?= (int)$course_id ?>" class="btn btn-secondary mb-3">&larr; Назад</a>

    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4">Оплата: <?= htmlspecialchars($course['title']) ?></h1>
            <p class="mb-1"><b>Сумма:</b> <?= htmlspecialchars($course['price']) ?> ₽</p>

            <form method="post" action="pay_course.php" class="mt-3">
                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">

                <div class="mb-2">Способ оплаты:</div>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="pm1" value="card" checked>
                    <label class="form-check-label" for="pm1">Банковская карта</label>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="pm2" value="sbp">
                    <label class="form-check-label" for="pm2">СБП</label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="pm3" value="wallet">
                    <label class="form-check-label" for="pm3">Кошелёк</label>
                </div>

                <button class="btn btn-success w-100">Оплатить</button>
            </form>

        </div>
    </div>

</div>
</body>
</html>
