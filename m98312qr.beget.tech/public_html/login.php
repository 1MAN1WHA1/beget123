<?php
// ⚠️ В проде лучше выключить, но оставлю как у тебя для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// dp.php уже подключает site-core/bootstrap.php и запускает сессию (если её нет)
// + создаёт $pdo и csrf_token
require 'dp.php';

$errorMsg = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $emailVal = trim($_POST['email'] ?? '');
    $pass     = (string)($_POST['password'] ?? '');

    if ($emailVal === '' || $pass === '') {
        $errorMsg = "Заполните все поля!";
    } elseif (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Некорректный формат Email!";
    } else {
        $sql = "SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $emailVal]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errorMsg = "Пользователь не найден.";
        } elseif (!password_verify($pass, $user['password_hash'])) {
            $errorMsg = "Неверный пароль.";
        } else {
            // ❗ НЕ делаем session_start() — сессия уже запущена в bootstrap.php

            // Защита от фиксации сессии
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['email']   = (string)$user['email'];
            $_SESSION['role']    = (string)$user['role'];

            // CSRF уже создаётся в bootstrap.php, но на всякий случай:
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Вход</h4>
                </div>
                <div class="card-body">
                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" autocomplete="on">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   required
                                   value="<?= htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="register.php">Нет аккаунта? Регистрация</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
