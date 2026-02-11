<?php
require_once 'check_admin.php';

$pageTitle = 'Админка';

// Логика админки
require __DIR__ . '/site-core/actions/admin_products.php';

// Шаблоны
require __DIR__ . '/site-core/templates/layout/header.php';
require __DIR__ . '/site-core/templates/pages/admin_panel.php';
require __DIR__ . '/site-core/templates/layout/footer.php';
