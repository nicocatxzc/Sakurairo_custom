<?php
// 自动为页面添加description标签
if (iro_opt('iro_seo', 'on') != 'off') {

    if (iro_opt('iro_seo', 'on') == 'auto') {
        add_action('wp_head', function () {
            ob_start();
        }, 0);

        add_action('wp_head', function () {
            $head_content = ob_get_clean();

            // 检查seo部分
            $has_description = preg_match('/<meta\s+name=["\']description["\']/i', $head_content);
            $has_keywords    = preg_match('/<meta\s+name=["\']keywords["\']/i', $head_content);

            echo $head_content;
            // 选择性补充
            if (!$has_description) {
                echo iro_get_description();
            }
            if (!$has_keywords) {
                echo iro_get_keywords();
            }
        }, 99);
    } else {
        // 始终添加
        add_action('wp_head', function () {
            echo iro_get_description();
            echo iro_get_keywords();
        }, 99);
    }
}

function iro_get_keywords()
{
    global $post;
    $keywords = '';

    if (is_singular()) {
        $tags = get_the_tags();
        if ($tags) {
            $keywords = implode(',', array_column($tags, 'name'));
        }
    } elseif (is_category()) {
        $cats = get_the_category();
        if ($cats) {
            $keywords = implode(',', array_column($cats, 'name'));
        }
    }

    if (empty($keywords)) {
        $keywords = iro_opt('iro_meta_keywords');
    }

    if (empty($keywords)) {
        $keywords = get_bloginfo('name');
    }

    if (! empty($keywords)) {
        return '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
    }
    return '';
}

function iro_get_description()
{
    global $post;
    $description = '';

    if (is_singular() && !empty($post->post_content)) {
        $description = trim(mb_strimwidth(preg_replace('/\s+/', ' ', strip_tags($post->post_content)), 0, 240, '…'));
    }

    if (empty($description) && is_category()) {
        $description = trim(category_description());
    }

    if (empty($description)) {
        $description = iro_opt('iro_meta_description');
    }

    if (empty($description)) {
        $description = get_bloginfo('description');
    }

    if (!empty($description)) {
        return '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    return '';
}

/*
 * SEO优化
 */
// 外部链接自动加nofollow
add_filter('the_content', 'siren_auto_link_nofollow');
function siren_auto_link_nofollow($content)
{
    $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>";
    if (preg_match_all("/$regexp/siU", $content, $matches, PREG_SET_ORDER)) {
        if (!empty($matches)) {
            $srcUrl = get_option('siteurl');
            foreach ($matches as $result) {
                $tag = $result[0];
                $tag2 = $result[0];
                $url = $result[0];
                $noFollow = '';
                $pattern = '/target\s*=\s*"\s*_blank\s*"/';
                preg_match($pattern, $tag2, $match, PREG_OFFSET_CAPTURE);
                if (count($match) < 1)
                    $noFollow .= ' target="_blank" ';
                $pattern = '/rel\s*=\s*"\s*[n|d]ofollow\s*"/';
                preg_match($pattern, $tag2, $match, PREG_OFFSET_CAPTURE);
                if (count($match) < 1)
                    $noFollow .= ' rel="nofollow" ';
                $pos = strpos($url, $srcUrl);
                if ($pos === false) {
                    $tag = rtrim($tag, '>');
                    $tag .= $noFollow . '>';
                    $content = str_replace($tag2, $tag, $content);
                }
            }
        }
    }

    $content = str_replace(']]>', ']]>', $content);
    return $content;
}

// 图片自动加标题
add_filter('the_content', 'siren_auto_images_alt');
function siren_auto_images_alt($content)
{
    global $post;
    $post_title = $post ? $post->post_title : '默认标题'; // 检查 $post 是否为空

    // 优化正则表达式
    $pattern = '/<a([^>]*?)href=(["\'])([^"\']*?\.(?:bmp|gif|jpeg|jpg|png))\2([^>]*?)>/i';
    $replacement = '<a$1href=$2$3$2 alt="' . esc_attr($post_title) . '" title="' . esc_attr($post_title) . '"$4>';

    // 使用 preg_replace_callback 以提高性能
    $content = preg_replace_callback($pattern, function ($matches) use ($post_title) {
        return '<a' . $matches[1] . 'href=' . $matches[2] . $matches[3] . $matches[2] . ' alt="' . esc_attr($post_title) . '" title="' . esc_attr($post_title) . '"' . $matches[4] . '>';
    }, $content);

    return $content;
}

// 分类页面全部添加斜杠，利于SEO
function siren_nice_trailingslashit($string, $type_of_url)
{
    if ($type_of_url != 'single')
        $string = trailingslashit($string);
    return $string;
}
add_filter('user_trailingslashit', 'siren_nice_trailingslashit', 10, 2);
