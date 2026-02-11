<?php
// dp.php уже подключен, значит helpers.php уже есть
$pageTitle = $pageTitle ?? 'Главная';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-light bg-light px-4 mb-4 shadow-sm">
    <span class="navbar-brand">Мой Магазин</span>

    <div>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <span class="me-3">Привет!</span>
            <a href="profile.php" class="btn btn-outline-primary btn-sm">Личный кабинет</a>

            <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_panel.php" class="btn btn-outline-danger btn-sm">Админка</a>
                <a href="add_item.php" class="btn btn-success btn-sm">+ Добавить товар</a>
            <?php endif; ?>

            <a href="logout.php" class="btn btn-dark btn-sm">Выйти</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm">Войти</a>
            <a href="register.php" class="btn btn-outline-primary btn-sm">Регистрация</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
