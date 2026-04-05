<?php
require_once 'check_admin.php';

$message = '';

function exportProductsToCsv(PDO $pdo): string {
    $exportDir = __DIR__ . '/exports/';
    if (!is_dir($exportDir) && !mkdir($exportDir, 0755, true)) {
        return 'Не удалось создать папку exports.';
    }

    $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';
    $fullPath = $exportDir . $filename;

    $stmt = $pdo->query('SELECT * FROM products ORDER BY id ASC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fp = fopen($fullPath, 'w');
    if (!$fp) {
        return 'Не удалось создать CSV файл.';
    }

    if (empty($rows)) {
        fputcsv($fp, ['empty']);
        fclose($fp);
        return "Таблица products пустая. Бэкап создан (пустой): exports/{$filename}";
    }

    fputcsv($fp, array_keys($rows[0]));
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);

    return "Бэкап сохранён: exports/{$filename}";
}

function seedProducts(PDO $pdo, int $count): string {
    if ($count < 1) {
        return 'Количество должно быть > 0.';
    }

    $tplStmt = $pdo->query('SELECT * FROM products ORDER BY RAND() LIMIT 1');
    $tpl = $tplStmt->fetch(PDO::FETCH_ASSOC);
    if (!$tpl) {
        return 'В products нет ни одной записи — сначала добавьте товар вручную.';
    }

    $ins = $pdo->prepare('
        INSERT INTO products (title, description, price, image_url, is_course)
        VALUES (?, ?, ?, ?, ?)
    ');

    $inserted = 0;
    for ($i = 0; $i < $count; $i++) {
        $suffix = ' #' . date('His') . '_' . bin2hex(random_bytes(2));
        $title = (string)$tpl['title'] . $suffix;

        $price = (float)$tpl['price'];
        $delta = random_int(-15, 15) / 100;
        $newPrice = round($price * (1 + $delta), 2);

        try {
            $ins->execute([
                $title,
                (string)($tpl['description'] ?? ''),
                $newPrice,
                (string)($tpl['image_url'] ?? ''),
                (int)($tpl['is_course'] ?? 0),
            ]);
            $inserted++;
        } catch (Throwable $e) {
            continue;
        }
    }

    return "Сгенерировано записей: {$inserted} из {$count}.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $message = 'Ошибка безопасности: неверный CSRF-токен.';
    } else {
        $count = (int)($_POST['count'] ?? 0);
        $m1 = exportProductsToCsv($pdo);
        $m2 = seedProducts($pdo, $count);
        $message = $m1 . '<br>' . $m2;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Seeder + CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="h5 mb-0">⚙️ Seeder + CSV backup (products)</h3>
        </div>
        <div class="card-body">

            <?php if ($message !== ''): ?>
                <div class="alert alert-info"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" class="mb-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-2">
                    <label class="form-label">Сколько товаров/курсов добавить?</label>
                    <input type="number" name="count" class="form-control" value="50" min="1" max="1000" required>
                </div>

                <div class="alert alert-warning small mb-3">
                    Скрипт сначала создаст CSV-бэкап таблицы <b>products</b> в <code>/exports</code>, затем скопирует случайную запись нужное количество раз, меняя цену на ±15%.
                </div>

                <button class="btn btn-success w-100">Сделать бэкап и наполнить</button>
            </form>

            <a href="admin_panel.php" class="btn btn-secondary w-100">← Вернуться в админку</a>
        </div>
    </div>
</div>

</body>
</html>
