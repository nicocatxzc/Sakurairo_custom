<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * 评论表情：token → 图片的替换管线（评论、正文、RSS 共用）
 * 表情定义与替换表统一来自 smiles_data.php，这里只挂 filter
 */
require_once get_template_directory() . '/inc/functions/comment/smiles_data.php';

function iro_smiley_filter($content)
{
    return iro_replace_smilies($content);
}
add_filter('the_content', 'iro_smiley_filter'); //替换文章关键词
add_filter('comment_text', 'iro_smiley_filter'); //替换评论关键词
add_filter('comment_text_rss', 'iro_smiley_filter'); //替换评论rss关键词

/**
 * 兼容旧调用，新代码用 iro_is_webp()
 */
function is_webp(): bool
{
    return iro_is_webp();
}

function featuredtoRSS($content)
{
    global $post;
    if (has_post_thumbnail($post->ID)) {
        $content = '<div>' . get_the_post_thumbnail($post->ID, 'medium', array('style' => 'margin-bottom: 15px;')) . '</div>' . $content;
    }
    return $content;
}
add_filter('the_excerpt_rss', 'featuredtoRSS');
add_filter('the_content_feed', 'featuredtoRSS');
