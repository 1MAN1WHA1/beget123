<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = (int)($_GET['id'] ?? 0);
$user_id  = (int)$_SESSION['user_id'];

if ($order_id <= 0) {
    die("Заказ не найден.");
}

// Anti-IDOR: ищем заказ по id И по владельцу
$sql = "
    SELECT 
        orders.id as order_id,
        orders.created_at,
        orders.status,
        products.title,
        products.description,
        products.price,
        products.image_url
    FROM orders
    JOIN products ON orders.product_id = products.id
    WHERE orders.id = ? AND orders.user_id = ?
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Заказ не найден или у вас нет прав на его просмотр.");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ #<?= (int)$order['order_id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width: 900px;">
    <a href="profile.php" class="btn btn-outline-secondary btn-sm mb-3">← Назад в профиль</a>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h4 class="mb-0">Заказ #<?= (int)$order['order_id'] ?></h4>
            <small class="text-muted"><?= htmlspecialchars($order['created_at']) ?></small>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <?php $img = $order['image_url'] ?: 'https://via.placeholder.com/300'; ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="img-fluid rounded" alt="Фото">
                </div>
                <div class="col-md-8">
                    <h5><?= htmlspecialchars($order['title']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($order['description'] ?? '') ?></p>
                    <p class="fw-bold">Цена: <?= number_format((float)$order['price'], 0, '', ' ') ?> ₽</p>
                    <p>Статус: <span class="badge bg-secondary"><?= htmlspecialchars($order['status']) ?></span></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>