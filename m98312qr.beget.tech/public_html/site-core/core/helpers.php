<?php

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): void
{
    header("Location: {$to}");
    exit;
}

function ensure_csrf(): void
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrf_token(): string
{
    ensure_csrf();
    return $_SESSION['csrf_token'];
}

function csrf_check(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('403 Forbidden');
    }
}
