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
 * 安全解析 WordPress 评论中的 Markdown 内容。
 * 此函数应挂载到 `preprocess_comment` 过滤器。
 *
 * @param array $incoming_comment 评论数据数组。
 * @return array 修改后的评论数据数组。
 */
function markdown_parser($incoming_comment)
{
    global $wpdb, $comment_markdown_content;
    global $allowedtags;

    /** 
     * 检查是否启用了 Markdown（假设前端评论表单中有 enable_markdown 字段）
     * Check if Markdown is enabled (assuming there is an enable_markdown field in the frontend comment form)
     */
    $enable_markdown = isset($_POST['enable_markdown']) ? (bool) $_POST['enable_markdown'] : false;

    /**
     * 初步安全检查
     * Initial security checks
     */
    $may_script = array(
        '/<script\b[^>]*>(.*?)<\/script>/is', // 阻止 <script> 标签
        '/<[^>]+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/is', // 阻止 HTML 属性中的 onxxx 事件
        '/\s(?:href|src)\s*=\s*(?:"(?:javascript|data):[^"]*"|\'(?:javascript|data):[^\']*\'|(?:javascript|data):[^\s>]+)/is', // 阻止带引号的 javascript: 或 data: 协议的 href/src 属性
    );

    foreach ($may_script as $pattern) {
        if (preg_match($pattern, $incoming_comment['comment_content'])) {
            siren_ajax_comment_err(__("For security reasons, JavaScript is not allowed in comments.")); //恶意内容警告
            return ($incoming_comment);
        }
    }

    /**
     * 启用 Markdown（如果启用）
     * Enable Markdown (if enabled)
     * 这里使用 wp_kses 来过滤 HTML 标签，允许的标签在 $allowedtags 中定义。
     * Here, we use wp_kses to filter HTML tags, and the allowed tags are defined in $allowedtags.
     */
    if ($enable_markdown) {
        include 'inc/Parsedown.php';
        $Parsedown = new Parsedown();
        // 核心安全
        // Set safe mode to true to prevent unsafe HTML tags
        $Parsedown->setSafeMode(true);

        // 禁用自动链接
        // Disable automatic linking of URLs
        $Parsedown->setUrlsLinked(false);

        $incoming_comment['comment_content'] = $Parsedown->text($incoming_comment['comment_content']);
        /**
         * 使用 wp_kses 过滤 HTML 标签
         * Use wp_kses to filter HTML tags
         * 使用全局的 $allowedtags 变量来定义允许的 HTML 标签。
         * Use the global $allowedtags variable to define allowed HTML tags.
         */
        // kses 过滤
        // Use wp_kses to filter the comment content
        $incoming_comment['comment_content'] = wp_kses($incoming_comment['comment_content'], $allowedtags); // 自行调用kses
    } else {
        $incoming_comment['comment_content'] = htmlspecialchars($incoming_comment['comment_content'], ENT_QUOTES, 'UTF-8'); //未启用markdown直接转义
    }

    // $column_names = $wpdb->get_row("SELECT * FROM information_schema.columns where 
    // table_name='$wpdb->comments' and column_name = 'comment_markdown' LIMIT 1");
    // //Add column if not present.
    // if (!isset($column_names)) {
    //     $wpdb->query("ALTER TABLE $wpdb->comments ADD comment_markdown text");
    // }
    $comment_markdown_content = $incoming_comment['comment_content'];

    return $incoming_comment;
}
add_filter('preprocess_comment', 'markdown_parser');
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
    $allowedtags['pre'] = array('class' => array());
    $allowedtags['code'] = array('class' => array());
    $allowedtags['h1'] = array('class' => array());
    $allowedtags['h2'] = array('class' => array());
    $allowedtags['h3'] = array('class' => array());
    $allowedtags['h4'] = array('class' => array());
    $allowedtags['h5'] = array('class' => array());
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
