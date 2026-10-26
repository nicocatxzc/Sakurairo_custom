<?php
/*
 * 图片CDN
 */
add_filter('upload_dir', 'iro_custom_upload_dir');
function iro_custom_upload_dir($uploads)
{
    /*     $upload_path = '';
     */
    $upload_url_path = iro_opt('image_cdn');

    $uploads['path'] = $uploads['basedir'] . $uploads['subdir'];

    if ($upload_url_path) {
        $uploads['baseurl'] = $upload_url_path;
        $uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];
    }
    return $uploads;
}

function toc_support($content)
{
    $content = str_replace('[toc]', '<div class="has-toc have-toc"></div>', $content); // TOC 支持
    $content = str_replace('[begin]', '<span class="begin">', $content); // 首字格式支持
    $content = str_replace('[/begin]', '</span>', $content); // 首字格式支持
    return $content;
}
add_filter('the_content', 'toc_support');
add_filter('the_excerpt_rss', 'toc_support');
add_filter('the_content_feed', 'toc_support');

/**
 * 正文图片 404 兜底：渲染阶段就给 `<img>` 挂上 onerror。
 *
 * 前台 `_iro.utils.missImg()` 才是真正干活的那个（先带 `_retry` 重试两次、再换占位图），
 * 但它是运行时里动态加载的，早期加载失败时可能还没就位；`iroImgError()` 由 header 内联定义，
 * 一定能接住，内部再转给 missImg。
 */
function iro_content_image_fallback(string $content): string
{
    if (stripos($content, '<img') === false) {
        return $content;
    }

    if (class_exists('WP_HTML_Tag_Processor')) {
        $tags = new WP_HTML_Tag_Processor($content);

        while ($tags->next_tag('IMG')) {
            if ($tags->get_attribute('onerror') !== null) {
                continue;
            }
            $tags->set_attribute('onerror', 'iroImgError(this)');
        }

        return $tags->get_updated_html();
    }

    return preg_replace('/<img(?![^>]*\sonerror=)/i', '<img onerror="iroImgError(this)"', $content) ?? $content;
}
add_filter('the_content', 'iro_content_image_fallback', 20);

// 阅读数量
require_once get_template_directory() . '/inc/functions/content/post_views.php';
// wp_query干预
require_once get_template_directory() . '/inc/functions/content/query.php';
// 多语言查询隔离
require_once get_template_directory() . '/inc/functions/content/i18n_query.php';
// 统计信息
require_once get_template_directory() . '/inc/functions/content/statistical.php';
// 文章字数
require_once get_template_directory() . '/inc/functions/content/post_words.php';
