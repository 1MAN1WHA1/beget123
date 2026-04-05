<?php
require_once 'check_admin.php';

$message = '';
$title = trim((string)($_POST['title'] ?? ''));
$price = trim((string)($_POST['price'] ?? ''));
$desc  = trim((string)($_POST['description'] ?? ''));
$img   = trim((string)($_POST['image_url'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $message = '<div class="alert alert-danger">Ошибка безопасности: неверный CSRF-токен.</div>';
    } elseif ($title === '' || $price === '') {
        $message = '<div class="alert alert-danger">Заполните название и цену.</div>';
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $message = '<div class="alert alert-danger">Цена должна быть неотрицательным числом.</div>';
    } else {
        $sql = "INSERT INTO products (title, description, price, image_url) VALUES (:t, :d, :p, :i)";
        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([
                ':t' => $title,
                ':d' => $desc,
                ':p' => $price,
                ':i' => $img,
            ]);
            $message = '<div class="alert alert-success">Товар успешно добавлен.</div>';
            $title = $price = $desc = $img = '';
        } catch (Throwable $e) {
            $message = '<div class="alert alert-danger">Не удалось сохранить запись. Проверьте данные и попробуйте ещё раз.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить товар</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1>Добавление нового товара</h1>
        <a href="index.php" class="btn btn-secondary mb-3">← На главную</a>

        <?= $message ?>

        <form method="POST" class="card p-4 shadow-sm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="mb-3">
                <label>Название товара:</label>
                <input type="text" name="title" class="form-control" required value="<?= e($title) ?>">
            </div>

            <div class="mb-3">
                <label>Цена (руб):</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" required value="<?= e($price) ?>">
            </div>

            <div class="mb-3">
                <label>Ссылка на картинку (URL):</label>
                <input type="text" name="image_url" class="form-control" placeholder="https://..." value="<?= e($img) ?>">
                <small class="text-muted">Пока просто вставьте ссылку на картинку из интернета</small>
            </div>

            <div class="mb-3">
                <label>Описание:</label>
                <textarea name="description" class="form-control" rows="3"><?= e($desc) ?></textarea>
            </div>

            <button type="submit" class="btn btn-success">Сохранить в БД</button>
        </form>
    </div>
</body>
</html>
