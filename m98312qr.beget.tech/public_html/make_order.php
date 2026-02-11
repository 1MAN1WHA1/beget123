<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id'])) {
    die("Войдите в аккаунт");
}

if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("CSRF ошибка");
}

$user_id = $_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);

if (!$stmt->fetch()) {
    die("Товар не найден");
}

$stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id) VALUES (?, ?)");
$stmt->execute([$user_id, $product_id]);

header("Location: profile.php");
exit;
