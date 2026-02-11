<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Ошибка безопасности: неверный CSRF-токен!");
    }

    $user_id = $_SESSION['user_id'];
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        // Если пароли не совпадают — редирект обратно с ошибкой
        header('Location: change_password.php?error=1');
        exit;
    }

    // Проверка текущего пароля
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        header('Location: change_password.php?error=2'); // неверный текущий пароль
        exit;
    }

    // Обновление пароля
    $new_hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
    $stmt->execute([
        ':hash' => $new_hash,
        ':id' => $user_id
    ]);

    // Редирект обратно на change_password.php с параметром updated=1
    header('Location: change_password.php?updated=1');
    exit;
}
