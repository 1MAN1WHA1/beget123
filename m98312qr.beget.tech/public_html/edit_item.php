<?php
require_once 'check_admin.php';
require_once 'dp.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin_panel.php?err=not_found");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: admin_panel.php?err=not_found");
    exit;
}

$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF токен неверный.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $is_course = isset($_POST['is_course']) ? 1 : 0;

        if ($title === '') $errors[] = "Название не может быть пустым.";
        if ($price === '' || !is_numeric($price)) $errors[] = "Цена должна быть числом.";

        if (!$errors) {
            $upd = $pdo->prepare("
                UPDATE products
                SET title = ?, description = ?, price = ?, image_url = ?, is_course = ?
                WHERE id = ?
            ");
            $upd->execute([$title, $description, $price, $image_url, $is_course, $id]);

            header("Location: admin_panel.php?msg=updated");
            exit;
        }
    }
}

// Для отображения (если были ошибки)
$img = !empty($product['image_url']) ? $product['image_url'] : 'https://via.placeholder.com/300';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Редактирование товара/курса #<?= (int)$product['id'] ?></h1>
        <a href="admin_panel.php" class="btn btn-outline-secondary">← Назад</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <img src="<?= htmlspecialchars($img) ?>" class="img-fluid rounded" alt="preview">
                </div>

                <div class="col-md-8">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" name="title" class="form-control"
                                   value="<?= htmlspecialchars($product['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Цена</label>
                            <input type="number" step="0.01" name="price" class="form-control"
                                   value="<?= htmlspecialchars($product['price']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ссылка на картинку (image_url)</label>
                            <input type="text" name="image_url" class="form-control"
                                   value="<?= htmlspecialchars($product['image_url'] ?? '') ?>">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_course" id="is_course"
                                <?= ((int)($product['is_course'] ?? 0) === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_course">
                                Это курс (is_course=1)
                            </label>
                        </div>

                        <button class="btn btn-primary w-100">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>
