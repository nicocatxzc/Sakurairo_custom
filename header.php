<?php

/**
 * The header for our theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Sakurairo
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function use_customize_data()
{ // 模版中加载，解决其他位置获取不到临时值的问题
    $persistent_options = get_theme_mod('iro_options', []);
    $mapping = get_theme_mod('iro_options_map', []);

    foreach ($mapping as $setting_id => $map) {
        $preview_value = get_theme_mod($setting_id, null);
        if (null !== $preview_value) {
            $iro_key = isset($map['iro_key']) ? $map['iro_key'] : $setting_id;
            $iro_subkey = isset($map['iro_subkey']) ? $map['iro_subkey'] : '';
            if ($iro_subkey) {
                if (! isset($persistent_options[$iro_key]) || ! is_array($persistent_options[$iro_key])) {
                    $persistent_options[$iro_key] = [];
                }
                $persistent_options[$iro_key][$iro_subkey] = $preview_value;
            } else {
                $persistent_options[$iro_key] = $preview_value;
            }
        }
    }
    set_theme_mod('iro_options', $persistent_options);
}
if (is_customize_preview()) {
    use_customize_data();
} //预览模式将临时值写入theme_mod中的iro_options
?>

<head
    <?php if (isset($_COOKIE["darkmode"]) && $_COOKIE["darkmode"] == "true"): ?>
    class="dark"
    <?php endif; ?>>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    
    <meta http-equiv="x-dns-prefetch-control" content="on">

    <link rel="shortcut icon" href="<?= esc_url(iro_opt('favicon_link', '')); ?>" />

    <?php wp_head(); ?>
    <link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?>｜<?php bloginfo('description'); ?>" href="<?php bloginfo('rss2_url'); ?>" />

    <?php
    // 前端主题样式
    require get_template_directory() . '/frontend/theme_style_vars.php';
    ?>
    <?= iro_opt("custom_site_header"); ?>

    <?php if (iro_opt('pjax')) {
        $script_leep_loading_list = iro_opt("pjax_keep_loading");
        if (strlen($script_leep_loading_list) > 0) :
    ?>
            <script>
                (function() {
                    const srcs = `<?php echo iro_opt("pjax_keep_loading"); ?>`;
                    const MARK = 'data-pjax-keep-loading';

                    function clearOld() {
                        document
                            .querySelectorAll(`script[${MARK}], link[${MARK}]`)
                            .forEach(el => el.remove());
                    }

                    function loadResource(path) {
                        path = path.trim();
                        if (!path) return;

                        if (path.endsWith('.js')) {
                            const script = document.createElement('script');
                            script.src = path;
                            script.async = true;
                            script.setAttribute(MARK, '');
                            document.body.appendChild(script);
                        } else if (path.endsWith('.css')) {
                            const link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = path;
                            link.setAttribute(MARK, '');
                            document.head.appendChild(link);
                        }
                    }

                    document.addEventListener('pjax:complete', () => {
                        clearOld();
                        srcs.split(/[\n,]+/).forEach(loadResource);
                    });
                })();
            </script>
    <?php endif;
    } ?>
</head>