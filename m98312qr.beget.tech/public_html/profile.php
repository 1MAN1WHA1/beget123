<?php
session_start();
require 'dp.php';

// CSRF (нужно для delete_order.php)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Данные пользователя (чтобы показать аватар и т.п.)
$u = $pdo->prepare("SELECT id, username, email, avatar_url FROM users WHERE id = ?");
$u->execute([$user_id]);
$user = $u->fetch(PDO::FETCH_ASSOC);

// Заказы пользователя (с is_course)
$sql = "
SELECT 
    orders.id,
    orders.product_id,
    orders.created_at,
    orders.status,
    products.title,
    products.price,
    products.is_course
FROM orders
JOIN products ON products.id = orders.product_id
WHERE orders.user_id = ?
ORDER BY orders.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderStatus(string $status): string {
    return match ($status) {
        'paid' => 'Оплачен',
        'new'  => 'Новый',
        default => $status,
    };
}

function statusBadgeClass(string $status): string {
    return match ($status) {
        'paid' => 'bg-success',
        'new'  => 'bg-secondary',
        default => 'bg-dark',
    };
}

$avatarOk = (!empty($_GET['avatar']) && $_GET['avatar'] === 'ok');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

    <!-- Навигация -->
    <div class="d-flex justify-content-between mb-3">
        <a href="index.php" class="btn btn-secondary">&larr; Назад на главную</a>
        <a href="change_password.php" class="btn btn-warning">Сменить пароль</a>
    </div>

    <!-- Профиль -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <?php
                $avatarSrc = !empty($user['avatar_url'])
                    ? $user['avatar_url']
                    : 'https://via.placeholder.com/100';
            ?>
            <img src="<?= htmlspecialchars($avatarSrc) ?>"
                 alt="avatar"
                 style="width:100px;height:100px;object-fit:cover;border-radius:50%;">

            <div class="flex-grow-1">
                <h3 class="h5 mb-1">Профиль</h3>
                <?php if (!empty($user['username'])): ?>
                    <div><b>Логин:</b> <?= htmlspecialchars($user['username']) ?></div>
                <?php endif; ?>
                <?php if (!empty($user['email'])): ?>
                    <div><b>Email:</b> <?= htmlspecialchars($user['email']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body pt-0">
            <?php if ($avatarOk): ?>
                <div class="alert alert-success">Аватар обновлён ✅</div>
            <?php endif; ?>

            <h5 class="mb-2">Загрузка аватара</h5>
            <form action="upload_avatar.php" method="POST" enctype="multipart/form-data" class="row g-2">
                <div class="col-md-8">
                    <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png" required>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">Загрузить</button>
                </div>
            </form>
            <small class="text-muted">Разрешены JPG/PNG, до 5MB. Файлы сохраняются в папку <code>/avatars</code>.</small>
        </div>
    </div>

    <!-- Заказы -->
    <h2 class="mb-3">Мои заказы</h2>

    <?php if (empty($orders)): ?>
        <p>Вы пока не сделали ни одного заказа.</p>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <?php
            $isCourse = (int)($order['is_course'] ?? 0) === 1;

            // Подробнее:
            // - курс: course.php (если не paid — сразу buy_course.php)
            // - товар: order_details.php
            if ($isCourse) {
                $detailsUrl = ($order['status'] === 'paid')
                    ? "course.php?id=" . (int)$order['product_id']
                    : "buy_course.php?id=" . (int)$order['product_id'];
            } else {
                $detailsUrl = "order_details.php?id=" . (int)$order['id'];
            }
        ?>

        <div class="card mb-3 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">
                        <?= htmlspecialchars($order['title']) ?>
                        <?php if ($isCourse): ?>
                            <span class="badge bg-warning text-dark ms-2">Курс</span>
                        <?php endif; ?>
                    </h5>

                    <small class="text-muted">
                        <?= htmlspecialchars($order['created_at']) ?>
                    </small>

                    <div class="mt-2">
                        <span class="badge <?= statusBadgeClass((string)$order['status']) ?>">
                            <?= htmlspecialchars(renderStatus((string)$order['status'])) ?>
                        </span>
                    </div>
                </div>

                <div class="text-end">
                    <strong><?= htmlspecialchars($order['price']) ?> ₽</strong><br>

                    <a href="<?= htmlspecialchars($detailsUrl) ?>" class="btn btn-outline-primary btn-sm mt-2">
                        Подробнее
                    </a>

                    <form action="delete_order.php" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                        <button type="submit"
                                class="btn btn-danger btn-sm mt-2"
                                onclick="return confirm('Вы уверены, что хотите удалить этот заказ?');">
                            Удалить
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

</body>
</html>
