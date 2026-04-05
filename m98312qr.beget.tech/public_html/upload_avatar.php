<?php
require 'dp.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Неверный метод');
}

if (!csrf_check($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    die('CSRF токен неверный');
}

if (!isset($_FILES['avatar'])) {
    die('Файл не передан');
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    die('Ошибка загрузки файла');
}

if ($file['size'] > 5 * 1024 * 1024) {
    die('Слишком большой файл (макс 5MB)');
}

if (!is_uploaded_file($file['tmp_name'])) {
    die('Некорректная загрузка файла');
}

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']) ?: '';

if (!isset($allowedTypes[$mime])) {
    die('Можно загружать только JPG или PNG');
}

$ext = $allowedTypes[$mime];
$uploadDir = __DIR__ . '/avatars/';
$publicPathPrefix = 'avatars/';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    die('Не удалось создать папку avatars');
}

$newName = 'avatar_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destination = $uploadDir . $newName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    die('Не удалось сохранить файл');
}

$dbPath = $publicPathPrefix . $newName;
$upd = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$upd->execute([$dbPath, $user_id]);

redirect('profile.php?avatar=ok');
