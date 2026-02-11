<?php
// 1. Подключаем БД и проверку на админа
require 'dp.php';
require 'check_admin.php'; // Эту страницу видит только админ!

$message = '';

// 2. Если нажата кнопка "Сохранить"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $price = $_POST['price'];
    $desc  = trim($_POST['description']);
    $img   = trim($_POST['image_url']);

    if (empty($title) || empty($price)) {
        $message = '<div class="alert alert-danger">Заполните название и цену!</div>';
    } else {
        // 3. Сохраняем в Базу Данных
        $sql = "INSERT INTO products (title, description, price, image_url) VALUES (:t, :d, :p, :i)";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([
                ':t' => $title,
                ':d' => $desc,
                ':p' => $price,
                ':i' => $img
            ]);
            $message = '<div class="alert alert-success">Товар успешно добавлен!</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Ошибка БД: ' . $e->getMessage() . '</div>';
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
            <div class="mb-3">
                <label>Название товара:</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label>Цена (руб):</label>
                <input type="number" name="price" class="form-control" step="0.01" required>
            </div>

            <div class="mb-3">
                <label>Ссылка на картинку (URL):</label>
                <input type="text" name="image_url" class="form-control" placeholder="https://...">
                <small class="text-muted">Пока просто вставьте ссылку на картинку из интернета</small>
            </div>

            <div class="mb-3">
                <label>Описание:</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-success">Сохранить в БД</button>
        </form>
    </div>
</body>
</html>