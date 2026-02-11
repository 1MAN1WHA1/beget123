<h2 class="mb-3">Каталог товаров</h2>

<div class="card mb-4 p-3 bg-light">
    <form action="index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-7">
            <input type="text"
                   name="q"
                   class="form-control"
                   placeholder="Поиск по названию (например: php)"
                   value="<?= e($q) ?>">
        </div>

        <div class="col-md-3">
            <select name="type" class="form-select">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Все</option>
                <option value="course" <?= $type === 'course' ? 'selected' : '' ?>>Только курсы</option>
                <option value="product" <?= $type === 'product' ? 'selected' : '' ?>>Только товары</option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Найти</button>
        </div>

        <?php if ($q !== '' || $type !== 'all'): ?>
            <div class="col-12 text-end">
                <a href="index.php" class="text-muted text-decoration-none small">Сбросить фильтры</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($q !== '' || $type !== 'all'): ?>
    <div class="mb-3 text-muted">
        Найдено: <?= (int)$foundCount ?>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (empty($products)): ?>
        <p class="text-muted">Ничего не найдено.</p>
    <?php endif; ?>

    <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <?php $img = !empty($product['image_url']) ? $product['image_url'] : 'https://via.placeholder.com/300'; ?>
                <img src="<?= e($img) ?>" class="card-img-top" style="height:200px; object-fit:cover;">

                <div class="card-body">
                    <h5><?= e($product['title']) ?></h5>
                    <p><?= e($product['description']) ?></p>
                    <strong><?= e((string)$product['price']) ?> ₽</strong>

                    <?php if ((int)($product['is_course'] ?? 0) === 1): ?>
                        <div class="mt-2">
                            <span class="badge bg-warning text-dark">Курс</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-white">
                    <?php if ((int)($product['is_course'] ?? 0) === 1): ?>
                        <a href="course.php?id=<?= (int)$product['id'] ?>" class="btn btn-outline-primary w-100">
                            Подробнее
                        </a>
                    <?php else: ?>
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <form method="POST" action="make_order.php">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button class="btn btn-primary w-100">Купить</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-primary w-100">Войти для покупки</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php endforeach; ?>
</div>
