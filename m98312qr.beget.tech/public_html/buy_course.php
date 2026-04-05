<?php
require 'dp.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$course_id = (int)($_GET['id'] ?? 0);
$error = ($_GET['err'] ?? '') === 'payment' ? 'Не удалось провести оплату. Попробуйте ещё раз.' : '';

if ($course_id <= 0) {
    http_response_code(400);
    die('Некорректный курс');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_course = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    http_response_code(404);
    die('Курс не найден');
}

$paid = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND product_id = ? AND status = 'paid' LIMIT 1");
$paid->execute([$user_id, $course_id]);
if ($paid->fetchColumn()) {
    redirect('course.php?id=' . $course_id);
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
            <h1 class="h4">Оплата: <?= e((string)$course['title']) ?></h1>
            <p class="mb-1"><b>Сумма:</b> <?= e((string)$course['price']) ?> ₽</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger mt-3"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="pay_course.php" class="mt-3">
                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

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
