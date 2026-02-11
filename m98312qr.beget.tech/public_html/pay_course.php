<?php
session_start();
require 'dp.php';

// ВАЖНО: чтобы видеть реальную причину ошибки (на время отладки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Чтобы PDO бросал исключения (если в dp.php не настроено)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$course_id = (int)($_POST['course_id'] ?? 0);
$payment_method = trim($_POST['payment_method'] ?? 'card');

if ($course_id <= 0) {
    die("Некорректный курс");
}

// Проверяем что это курс
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_course = 1");
$stmt->execute([$course_id]);
if (!$stmt->fetchColumn()) {
    die("Курс не найден (проверь is_course=1)");
}

try {
    // Если уже paid — просто на курс
    $paid = $pdo->prepare("SELECT id FROM orders WHERE user_id=? AND product_id=? AND status='paid' LIMIT 1");
    $paid->execute([$user_id, $course_id]);
    if ($paid->fetchColumn()) {
        header("Location: course.php?id=" . $course_id);
        exit;
    }

    // Ищем последний new заказ по этому курсу
    $checkNew = $pdo->prepare("
        SELECT id FROM orders 
        WHERE user_id=? AND product_id=? AND status='new'
        ORDER BY id DESC
        LIMIT 1
    ");
    $checkNew->execute([$user_id, $course_id]);
    $newOrderId = $checkNew->fetchColumn();

    if ($newOrderId) {
        // Обновляем new -> paid
        $upd = $pdo->prepare("UPDATE orders SET status='paid', payment_method=? WHERE id=?");
        $upd->execute([$payment_method, $newOrderId]);
    } else {
        // Создаём paid заказ
        $ins = $pdo->prepare("INSERT INTO orders (user_id, product_id, status, payment_method) VALUES (?, ?, 'paid', ?)");
        $ins->execute([$user_id, $course_id, $payment_method]);
    }

    header("Location: course.php?id=" . $course_id);
    exit;

} catch (Throwable $e) {
    // Самая частая причина: status не позволяет 'paid' (ENUM без paid)
    echo "<h1>Ошибка оплаты</h1>";
    echo "<p><b>Причина:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Проверь тип поля <code>orders.status</code>. Если это ENUM и там нет <code>paid</code> — добавь его:</p>";
    echo "<pre>ALTER TABLE orders MODIFY status ENUM('new','paid') NOT NULL DEFAULT 'new';</pre>";
}
