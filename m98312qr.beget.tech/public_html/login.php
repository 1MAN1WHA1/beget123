<?php
require 'dp.php';

$errorMsg = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Ошибка безопасности: неверный CSRF-токен.';
    } else {
        $emailVal = trim((string)($_POST['email'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');

        if ($emailVal === '' || $pass === '') {
            $errorMsg = 'Заполните все поля.';
        } elseif (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Некорректный формат Email.';
        } else {
            $sql = 'SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $emailVal]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($pass, (string)$user['password_hash'])) {
                $errorMsg = 'Неверный email или пароль.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['email']   = (string)$user['email'];
                $_SESSION['role']    = (string)$user['role'];

                ensure_csrf();
                redirect('index.php');
            }
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
                    <?php if ($errorMsg !== ''): ?>
                        <div class="alert alert-danger"><?= e($errorMsg) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" autocomplete="on">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   required
                                   value="<?= e($emailVal) ?>">
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
