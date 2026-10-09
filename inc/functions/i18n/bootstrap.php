<?php
// 内容国际化支持
if (!defined('ABSPATH')) {
    exit;
}

$iro_i18n_dir = __DIR__ . '/';

require_once $iro_i18n_dir . 'registry.php';
// 代号迁移要早于分类法注册，否则会先按新代号建出重复术语
require_once $iro_i18n_dir . 'migrate.php';
require_once $iro_i18n_dir . 'taxonomy.php';
require_once $iro_i18n_dir . 'path.php';
require_once $iro_i18n_dir . 'lang.php';
require_once $iro_i18n_dir . 'user_content.php';
require_once $iro_i18n_dir . 'sync.php';
require_once $iro_i18n_dir . 'fuzzy.php';
require_once $iro_i18n_dir . 'admin.php';

unset($iro_i18n_dir);
