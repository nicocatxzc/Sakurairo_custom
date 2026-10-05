<?php

/**
 * 按名字模式去构建产物里反查 chunk 文件名。
 *
 * @param string $prefix chunk 名，如 iro-core
 * @return string|null 命中的文件名（不含目录），找不到返回 null
 */
function iro_dist_chunk_file(string $prefix): ?string
{
    $dist = get_template_directory() . '/frontend/dist';

    if (!is_dir($dist)) {
        return null;
    }

    $matched = preg_grep(
        '/^' . preg_quote($prefix, '/') . '\.[0-9A-Za-z_-]+\.js$/',
        scandir($dist) ?: [],
    );

    return $matched ? (string) reset($matched) : null;
}

/**
 * Enqueue scripts and styles.
 */
function iro_enqueue_scripts()
{
    $dev_mode = iro_opt("dev_mode", false) &&
        (
            !iro_opt("dev_mode_admin_only", true) ||
            current_user_can('manage_options')
        );
?>
    <?php if ($dev_mode): ?>
        <script type="module" src="<?= iro_opt("dev_mode_hmr_client") ?>"></script>
        <script type="module" src="<?= iro_opt("dev_mode_main_js") ?>"></script>
    <?php else: ?>
        <?php
        // 首屏必然要下的共享 chunk 必须预载，否则浏览器要先下完并解析
        $iro_preloads = array_filter([
            iro_dist_chunk_file('iro-core'),
            iro_dist_chunk_file('rolldown-runtime'),
        ]);
        ?>
        <link rel="stylesheet" href="<?= get_template_directory_uri() . '/frontend/dist/style.css?ver=' . INT_VERSION ?>">
        <?php foreach ($iro_preloads as $iro_preload): ?>
            <link rel="modulepreload" href="<?= esc_url(get_template_directory_uri() . '/frontend/dist/' . $iro_preload) ?>">
        <?php endforeach; ?>
        <script type="module" src="<?= get_template_directory_uri() . '/frontend/dist/app.js?ver=' . INT_VERSION ?>"></script>
    <?php endif; ?>
<?php
}
add_action('wp_enqueue_scripts', 'iro_enqueue_scripts', 999);
