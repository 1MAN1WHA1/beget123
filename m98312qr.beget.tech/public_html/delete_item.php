<?php
require_once 'check_admin.php';
require_once 'dp.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_panel.php");
    exit;
}

// CSRF check
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    header("Location: admin_panel.php?err=csrf");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin_panel.php?err=not_found");
    exit;
}

// Проверяем, есть ли такой товар
$stmt = $pdo->prepare("SELECT id, is_course FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: admin_panel.php?err=not_found");
    exit;
}

$isCourse = (int)($product['is_course'] ?? 0) === 1;

// 1) Если есть заказы — нельзя удалять
$ord = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE product_id = ?");
$ord->execute([$id]);
if ((int)$ord->fetchColumn() > 0) {
    header("Location: admin_panel.php?err=has_orders");
    exit;
}

// 2) Если это курс и есть уроки — нельзя удалять
if ($isCourse) {
    $ls = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = ?");
    $ls->execute([$id]);
    if ((int)$ls->fetchColumn() > 0) {
        header("Location: admin_panel.php?err=has_lessons");
        exit;
    }
}

// 3) Удаляем
try {
    $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $del->execute([$id]);
    header("Location: admin_panel.php?msg=deleted");
    exit;
} catch (Throwable $e) {
    header("Location: admin_panel.php?err=delete_failed");
    exit;
}
