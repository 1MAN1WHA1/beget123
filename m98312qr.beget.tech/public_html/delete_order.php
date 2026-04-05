<?php
require 'dp.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profile.php');
}

if (!csrf_check($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    die('Ошибка безопасности: неверный CSRF-токен');
}

$user_id = (int)$_SESSION['user_id'];
$order_id = (int)($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    die('Не указан заказ для удаления');
}

$stmt = $pdo->prepare('DELETE FROM orders WHERE id = :id AND user_id = :user_id');
$stmt->execute([
    ':id' => $order_id,
    ':user_id' => $user_id,
]);

redirect('profile.php');
