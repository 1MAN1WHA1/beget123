<?php
require_once 'check_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_panel.php');
}

if (!csrf_check($_POST['csrf_token'] ?? null)) {
    redirect('admin_panel.php?err=csrf');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('admin_panel.php?err=not_found');
}

$stmt = $pdo->prepare('SELECT id, is_course FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    redirect('admin_panel.php?err=not_found');
}

$isCourse = (int)($product['is_course'] ?? 0) === 1;

$ord = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE product_id = ?');
$ord->execute([$id]);
if ((int)$ord->fetchColumn() > 0) {
    redirect('admin_panel.php?err=has_orders');
}

if ($isCourse) {
    $ls = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?');
    $ls->execute([$id]);
    if ((int)$ls->fetchColumn() > 0) {
        redirect('admin_panel.php?err=has_lessons');
    }
}

try {
    $del = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $del->execute([$id]);
    redirect('admin_panel.php?msg=deleted');
} catch (Throwable $e) {
    redirect('admin_panel.php?err=delete_failed');
}
