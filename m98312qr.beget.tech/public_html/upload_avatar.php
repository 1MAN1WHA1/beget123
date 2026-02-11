<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['avatar'])) {
    die("Файл не передан");
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    die("Ошибка загрузки: " . $file['error']);
}

// Макс размер 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    die("Слишком большой файл (макс 5MB)");
}

// Разрешённые типы
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!isset($allowedTypes[$mime])) {
    die("Можно загружать только JPG или PNG");
}

$ext = $allowedTypes[$mime];

// Папка у тебя такая:
$uploadDir = __DIR__ . '/avatars/';   // физический путь
$publicPathPrefix = 'avatars/';       // путь, который будет храниться в БД

if (!is_dir($uploadDir)) {
    die("Папка avatars не найдена. Создай её в public_html.");
}

// Уникальное имя
$newName = 'avatar_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destination = $uploadDir . $newName;

// Сохраняем файл
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    die("Не удалось сохранить файл");
}

// То, что пишем в БД (относительный путь)
$dbPath = $publicPathPrefix . $newName;

$upd = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$upd->execute([$dbPath, $user_id]);

header("Location: profile.php?avatar=ok");
exit;
