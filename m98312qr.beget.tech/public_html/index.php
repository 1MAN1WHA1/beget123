<?php
require 'dp.php';

$pageTitle = 'Главная';

// Логика каталога (SQL/фильтры)
require __DIR__ . '/site-core/actions/catalog.php';

// Шаблоны
require __DIR__ . '/site-core/templates/layout/header.php';
require __DIR__ . '/site-core/templates/pages/catalog.php';
require __DIR__ . '/site-core/templates/layout/footer.php';
