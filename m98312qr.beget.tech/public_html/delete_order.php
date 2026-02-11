<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Проверка CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Ошибка безопасности: неверный CSRF-токен!");
    }

    $user_id = $_SESSION['user_id'];
    $order_id = $_POST['order_id'] ?? '';

    // Проверка, что order_id передан
    if (!$order_id) {
        die("Не указан заказ для удаления!");
    }

    // Удаляем только если этот заказ принадлежит текущему пользователю
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $order_id,
        ':user_id' => $user_id
    ]);

    // После удаления возвращаемся на profile.php
    header("Location: profile.php");
    exit;
}
