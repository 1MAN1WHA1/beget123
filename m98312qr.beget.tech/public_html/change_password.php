<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Обработка формы на той же странице
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Проверка CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Ошибка безопасности: неверный CSRF-токен!";
    } else {
        $user_id = $_SESSION['user_id'];
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Проверка совпадения нового пароля и подтверждения
        if ($new !== $confirm) {
            $error = "Новый пароль и подтверждение не совпадают!";
        } else {
            // Проверка текущего пароля
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current, $user['password_hash'])) {
                $error = "Текущий пароль неверен!";
            } else {
                // Обновление пароля
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
                $stmt->execute([
                    ':hash' => $new_hash,
                    ':id' => $user_id
                ]);

                $message = "Пароль успешно изменён!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сменить пароль</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">

    <a href="profile.php" class="btn btn-secondary mb-3">&larr; Назад в личный кабинет</a>

    <h2>Сменить пароль</h2>

    <?php if($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form action="" method="POST" class="mt-3">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="mb-3">
            <label class="form-label">Текущий пароль</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Новый пароль</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Подтвердите новый пароль</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Сменить пароль</button>
    </form>

</div>
</body>
</html>
