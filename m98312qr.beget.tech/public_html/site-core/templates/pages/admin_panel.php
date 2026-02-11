<div class="py-2">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Панель администратора</h1>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">← На сайт</a>
            <a href="add_item.php" class="btn btn-success btn-sm">+ Добавить товар/курс</a>
            <a href="admin_seeder.php" class="btn btn-outline-primary btn-sm">Seeder + CSV</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Выйти</a>
        </div>
    </div>

    <?php if ($msg === 'updated'): ?>
        <div class="alert alert-success">Запись обновлена ✅</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success">Запись удалена ✅</div>
    <?php endif; ?>

    <?php if ($err === 'csrf'): ?>
        <div class="alert alert-danger">CSRF токен неверный. Удаление заблокировано.</div>
    <?php elseif ($err === 'not_found'): ?>
        <div class="alert alert-danger">Запись не найдена.</div>
    <?php elseif ($err === 'has_orders'): ?>
        <div class="alert alert-warning">Нельзя удалить: по этому товару есть заказы (orders).</div>
    <?php elseif ($err === 'has_lessons'): ?>
        <div class="alert alert-warning">Нельзя удалить: у курса есть уроки (lessons).</div>
    <?php elseif ($err === 'delete_failed'): ?>
        <div class="alert alert-danger">Удаление не выполнено (ограничения БД / внешние ключи).</div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-7">
                    <input type="text" name="q" class="form-control"
                           placeholder="Поиск по названию..."
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
                    <button class="btn btn-primary w-100">Найти</button>
                </div>

                <?php if ($q !== '' || $type !== 'all'): ?>
                    <div class="col-12 text-end">
                        <a href="admin_panel.php" class="text-muted text-decoration-none small">Сбросить</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="mb-2 text-muted">
        Всего: <?= (int)$totalRows ?> | Страница: <?= (int)$page ?> / <?= (int)$totalPages ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Название</th>
                        <th style="width:110px;">Тип</th>
                        <th style="width:120px;">Цена</th>
                        <th style="width:180px;">Создан</th>
                        <th style="width:190px;" class="text-end">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="6" class="p-4 text-muted">Ничего не найдено.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= (int)$it['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($it['title']) ?></div>
                                <div class="text-muted small"><?= e(mb_strimwidth((string)($it['description'] ?? ''), 0, 80, '…')) ?></div>
                            </td>
                            <td>
                                <?php if ((int)($it['is_course'] ?? 0) === 1): ?>
                                    <span class="badge bg-warning text-dark">Курс</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Товар</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string)$it['price']) ?> ₽</td>
                            <td class="text-muted small"><?= e((string)($it['created_at'] ?? '')) ?></td>
                            <td class="text-end">
                                <a href="edit_item.php?id=<?= (int)$it['id'] ?>" class="btn btn-warning btn-sm">✏️</a>

                                <form action="delete_item.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('Вы уверены, что хотите удалить?');">
                                    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php
            $prev = max(1, $page - 1);
            $next = min($totalPages, $page + 1);
            ?>

            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="admin_panel.php?<?= e($buildQuery(['page' => $prev])) ?>">«</a>
            </li>

            <?php
            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="admin_panel.php?<?= e($buildQuery(['page' => $i])) ?>"><?= (int)$i ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="admin_panel.php?<?= e($buildQuery(['page' => $next])) ?>">»</a>
            </li>
        </ul>
    </nav>

</div>
