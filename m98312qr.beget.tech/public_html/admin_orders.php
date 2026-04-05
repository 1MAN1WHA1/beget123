<?php
require 'check_admin.php';

$sql = "
    SELECT
        orders.id as order_id,
        orders.created_at,
        users.email,
        products.title,
        products.price
    FROM orders
    JOIN users ON orders.user_id = users.id
    JOIN products ON orders.product_id = products.id
    ORDER BY orders.id DESC
";
$stmt = $pdo->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h1>Все заказы</h1>
    <a href="index.php">← На главную</a>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>ID</th><th>Дата</th><th>Пользователь</th><th>Товар</th><th>Цена</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= (int)$order['order_id'] ?></td>
                <td><?= e((string)$order['created_at']) ?></td>
                <td><?= e((string)$order['email']) ?></td>
                <td><?= e((string)$order['title']) ?></td>
                <td><?= e((string)$order['price']) ?> ₽</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
