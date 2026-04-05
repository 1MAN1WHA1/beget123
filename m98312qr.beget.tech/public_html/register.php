<?php
require 'dp.php';

$errorMsg = '';
$successMsg = '';
$email = trim((string)($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Ошибка безопасности: неверный CSRF-токен.';
    } else {
        $pass = (string)($_POST['password'] ?? '');
        $passConfirm = (string)($_POST['password_confirm'] ?? '');

        if ($email === '' || $pass === '') {
            $errorMsg = 'Заполните все поля.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Некорректный формат Email.';
        } elseif ($pass !== $passConfirm) {
            $errorMsg = 'Пароли не совпадают.';
        } elseif (mb_strlen($pass) < 6) {
            $errorMsg = 'Пароль должен содержать минимум 6 символов.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, 'client')";
            $stmt = $pdo->prepare($sql);

            try {
                $stmt->execute([
                    ':email' => $email,
                    ':hash' => $hash,
                ]);
                $successMsg = "Регистрация успешна! <a href='login.php'>Войти</a>";
                $email = '';
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '23000') {
                    $errorMsg = 'Такой email уже зарегистрирован.';
                } else {
                    $errorMsg = 'Не удалось выполнить регистрацию. Попробуйте позже.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Регистрация</h4>
                </div>
                <div class="card-body">

                    <?php if ($errorMsg !== ''): ?>
                        <div class="alert alert-danger"><?= e($errorMsg) ?></div>
                    <?php endif; ?>

                    <?php if ($successMsg !== ''): ?>
                        <div class="alert alert-success"><?= $successMsg ?></div>
                    <?php else: ?>

                    <form method="POST" action="register.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="mb-3">
                            <label class="form-label">Email адрес</label>
                            <input type="email" name="email" class="form-control" required value="<?= e($email) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Подтверждение пароля</label>
                            <input type="password" name="password_confirm" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="login.php">Уже есть аккаунт? Войти</a>
                    </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
