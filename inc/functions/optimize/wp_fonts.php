<?php
defined('ABSPATH') || exit;

/*
 * 内核 6.4 起在 wp_head(50) 直接打印 theme.json 合并出的 @font-face
 * （来源 WP_Font_Face_Resolver::get_fonts_from_theme_json()，站长选用的字体库字体
 * 会先写进用户全局样式，再从这里出来），字体在首屏就会被请求。
 */
add_action('wp', function () {
    remove_action('wp_head', 'wp_print_font_faces', 50);
    remove_action('wp_head', 'wp_print_font_faces_from_style_variations', 50);
});

/**
 * 获取 WordPress Font Library 保存的字体，按 font-family 分组，
 * 字段转成内核 WP_Font_Face 的入参格式（kebab-case，生成 CSS 时再走内核自己的 src 排序与 format 提示）。

 * @return array<string, array<int, array<string, mixed>>>
 */
function iro_get_font_library(): array
{
    $posts = get_posts(
        array(
            'post_type'              => 'wp_font_face',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'suppress_filters'       => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );

    $faces = array();

    foreach ($posts as $post) {
        $settings = json_decode($post->post_content, true);

        if (! is_array($settings) || empty($settings['fontFamily']) || empty($settings['src'])) {
            continue;
        }

        $srcs = array_filter(
            (array) $settings['src'],
            function ($src) {
                return is_string($src) && '' !== trim($src);
            }
        );

        if (empty($srcs)) {
            continue;
        }

        $face = array();

        foreach ($settings as $key => $value) {
            if ('src' !== $key) {
                $face[_wp_to_kebab_case($key)] = $value;
            }
        }

        $face['src'] = array_values($srcs);
        $face['font-display'] = $settings['fontDisplay'] ?? 'swap';

        $faces[$settings['fontFamily']][] = $face;
    }

    return $faces;
}

function iro_get_wp_fonts_style()
{
    $iro_font_faces_css = '';
    if (function_exists('wp_print_font_faces')) {
        ob_start();
        wp_print_font_faces();

        if (function_exists('wp_print_font_faces_from_style_variations')) {
            wp_print_font_faces_from_style_variations();
        }

        // 内核把 CSS 包在它自己的 <style class="wp-fonts-local"> 里，直接嵌进来会提前闭合外层 style，只取内容
        preg_match_all('#<style[^>]*>(.*?)</style>#s', ob_get_clean(), $iro_printed_font_faces);
        $iro_font_faces_css = trim(implode("\n", $iro_printed_font_faces[1] ?? []));

        if ('' === $iro_font_faces_css) {
            // 内核一条都没生成时才去读字体库
            // 注意不能把空数组交给 wp_print_font_faces()，那会被当成没传参又回退去读 theme.json
            $iro_library_faces = iro_get_font_library();

            if ($iro_library_faces !== []) {
                ob_start();
                wp_print_font_faces($iro_library_faces);
                preg_match('#<style[^>]*>(.*?)</style>#s', ob_get_clean(), $iro_library_font_faces);
                $iro_font_faces_css = trim($iro_library_font_faces[1] ?? '');
            }
        }
    }
    return $iro_font_faces_css;
}
