<?php
/*
 * 链接新窗口打开
 */
function rt_add_link_target($content)
{
    $content = str_replace('<a', '<a rel="nofollow"', $content);
    // use the <a> tag to split into segments
    $bits = explode('<a ', $content);
    // loop though the segments
    foreach ($bits as $key => $bit) {
        // fix the target="_blank" bug after the link
        if (strpos($bit, 'href') === false) {
            continue;
        }

        // fix the target="_blank" bug in the codeblock
        if (strpos(preg_replace('/code([\s\S]*?)\/code[\s]*/m', 'temp', $content), $bit) === false) {
            continue;
        }

        // find the end of each link
        $pos = strpos($bit, '>');
        // check if there is an end (only fails with malformed markup)
        if ($pos !== false) {
            // get a string with just the link's attibutes
            $part = substr($bit, 0, $pos);
            // for comparison, get the current site/network url
            $siteurl = network_site_url();
            // if the site url is in the attributes, assume it's in the href and skip, also if a target is present
            if (strpos($part, $siteurl) === false && strpos($part, 'target=') === false) {
                // add the target attribute
                $bits[$key] = 'target="_blank" ' . $bits[$key];
            }
        }
    }
    // re-assemble the content, and return it
    return implode('<a ', $bits);
}
add_filter('comment_text', 'rt_add_link_target');

// 评论通过BBCode插入图片
function comment_picture_support($content)
{
    $content = str_replace('http://', 'https://', $content); // 干掉任何可能的 http
    $content = str_replace('{UPLOAD}', 'https://i.loli.net/', $content);
    $content = str_replace('[/img][img]', '[/img^img]', $content);
    $content = str_replace('[img]', '<br><img src="' . iro_opt('load_in_svg') . '" data-src="', $content);
    $content = str_replace('[/img]', '" class="lazyload comment_inline_img" onerror="imgError(this)"><br>', $content);
    $content = str_replace('[/img^img]', '" class="lazyload comment_inline_img" onerror="imgError(this)"><img src="' . iro_opt('load_in_svg') . '" data-src="', $content);
    return $content;
}
add_filter('comment_text', 'comment_picture_support');

/**
 * 评论内容安全过滤：未启用 Markdown 时转义 HTML，启用时经 Parsedown 渲染后再用 wp_kses 过滤。
 *
 * @param string $content         评论正文。
 * @param bool   $enable_markdown 是否启用 Markdown。
 * @return string|WP_Error 处理后的正文；命中恶意特征时返回 WP_Error。
 */
function iro_comment_filter_content(string $content, bool $enable_markdown)
{
    global $allowedtags;

    /**
     * 初步安全检查：阻止 <script>、HTML 属性中的 onxxx 事件、javascript: / data: 协议
     */
    $may_script = array(
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/<[^>]+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/is',
        '/\s(?:href|src)\s*=\s*(?:"(?:javascript|data):[^"]*"|\'(?:javascript|data):[^\']*\'|(?:javascript|data):[^\s>]+)/is',
    );

    foreach ($may_script as $pattern) {
        if (preg_match($pattern, $content)) {
            return new WP_Error(
                'comment_content_invalid',
                __("For security reasons, JavaScript is not allowed in comments.", 'sakurairo'),
                array('status' => 403)
            );
        }
    }

    if (! $enable_markdown) {
        return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }

    require_once get_template_directory() . '/inc/libs/Parsedown.php';

    $Parsedown = new Parsedown();
    // 安全模式，阻止不安全 HTML 标签
    $Parsedown->setSafeMode(true);
    // 禁用自动链接
    $Parsedown->setUrlsLinked(false);

    return wp_kses($Parsedown->text($content), $allowedtags);
}

/**
 * 传统评论表单（wp-comments-post.php）的 Markdown 处理。
 * 只有该流程会触发 preprocess_comment，REST 流程见 iro_rest_comment_markdown()。
 *
 * @param array $incoming_comment 评论数据数组。
 * @return array 修改后的评论数据数组。
 */
function markdown_parser($incoming_comment)
{
    global $wpdb, $comment_markdown_content;

    $filtered = iro_comment_filter_content(
        (string) $incoming_comment['comment_content'],
        isset($_POST['enable_markdown']) ? (bool) $_POST['enable_markdown'] : false
    );

    if (is_wp_error($filtered)) {
        wp_die($filtered->get_error_message(), '', array('response' => 403));
    }

    $incoming_comment['comment_content'] = $filtered;
    $comment_markdown_content = $filtered;

    return $incoming_comment;
}
add_filter('preprocess_comment', 'markdown_parser');

/**
 * REST 评论流程的 Markdown 处理。
 * create_item() 只经过 rest_pre_insert_comment，不触发 preprocess_comment，
 * 且 JSON 请求体不会填充 $_POST，所以启用标记只能从 $request 读取。
 *
 * @param array|WP_Error  $prepared_comment 待入库的评论数据。
 * @param WP_REST_Request $request          当前请求。
 * @return array|WP_Error
 */
function iro_rest_comment_markdown($prepared_comment, WP_REST_Request $request)
{
    if (! is_array($prepared_comment) || ! isset($prepared_comment['comment_content'])) {
        return $prepared_comment;
    }

    $filtered = iro_comment_filter_content(
        (string) $prepared_comment['comment_content'],
        (bool) $request->get_param('enable_markdown')
    );

    if (is_wp_error($filtered)) {
        return $filtered;
    }

    $prepared_comment['comment_content'] = $filtered;

    return $prepared_comment;
}
add_filter('rest_pre_insert_comment', 'iro_rest_comment_markdown', 10, 2);

remove_filter('comment_text', 'make_clickable', 9);

//打开评论HTML标签限制
function allow_more_tag_in_comment()
{
    global $allowedtags;
    $allowedtags['img'] = [
        'src' => [],
        'alt' => [],
        'width' => [],
        'height' => [],
        'title' => [],
    ];
    $allowedtags['a'] = [
        'href' => [],
        'title' => [],
        'target' => [],
        'rel' => [],
    ];
    $allowedtags['b'] = array('class' => array());
    $allowedtags['br'] = array('class' => array());
    $allowedtags['blockquote'] = array('class' => array());
    $allowedtags['p'] = array('class' => array());
    $allowedtags['hr'] = array('class' => array());
    $allowedtags['pre'] = array('class' => array());
    $allowedtags['code'] = array('class' => array());
    $allowedtags['h1'] = array('class' => array());
    $allowedtags['h2'] = array('class' => array());
    $allowedtags['h3'] = array('class' => array());
    $allowedtags['h4'] = array('class' => array());
    $allowedtags['h5'] = array('class' => array());
    $allowedtags['h6'] = array('class' => array());
    $allowedtags['ul'] = array('class' => array());
    $allowedtags['ol'] = array('class' => array());
    $allowedtags['li'] = array('class' => array());
    $allowedtags['td'] = array('class' => array());
    $allowedtags['th'] = array('class' => array());
    $allowedtags['tr'] = array('class' => array());
    $allowedtags['table'] = array('class' => array());
    $allowedtags['thead'] = array('class' => array());
    $allowedtags['tbody'] = array('class' => array());
    $allowedtags['span'] = array('class' => array());
}
add_action('init', 'allow_more_tag_in_comment');
// 移除wp核心内置的两阶段评论过滤
remove_filter('pre_comment_content', 'wp_filter_kses');
remove_filter('comment_save_pre', 'wp_filter_kses');
