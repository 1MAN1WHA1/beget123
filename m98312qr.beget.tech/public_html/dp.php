<?php
// dp.php — мост к ядру (чтобы старые require 'dp.php' не ломались)

$bootstrap = __DIR__ . '/site-core/bootstrap.php';        // site-core внутри public_html
if (!file_exists($bootstrap)) {
    $bootstrap = __DIR__ . '/../site-core/bootstrap.php'; // site-core рядом с public_html
}

require_once $bootstrap;
// $pdo создаётся внутри bootstrap.php
