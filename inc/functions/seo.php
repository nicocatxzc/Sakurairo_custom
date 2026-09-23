<?php
if (iro_opt('iro_seo', 'on') != 'off') {

    if (iro_opt('iro_seo', 'on') == 'auto') {

        add_action('wp_head', function () {
            ob_start();
        }, 0);

        add_action('wp_head', function () {
            $head_content = ob_get_clean();

            $has_description = preg_match('/<meta\s+name=["\']description["\']/i', $head_content);
            $has_keywords    = preg_match('/<meta\s+name=["\']keywords["\']/i', $head_content);
            $has_og          = preg_match('/<meta\s+(?:property|name)=["\'](?:og:|twitter:)/i', $head_content);
            $has_schema      = preg_match('/application\/ld\+json/i', $head_content);

            echo $head_content;

            if (!$has_description) {
                echo iro_get_description();
            }

            // keywords 默认不输出，防止被搜索引擎判定为关键词滥用。
            // if (!$has_keywords) {
            //     echo iro_get_keywords();
            // }

            if (!$has_og) {
                echo iro_get_opengraph();
            }

            if (!$has_schema) {
                echo iro_get_json_ld();
            }
        }, 99);
    } else {
        // 始终输出
        add_action('wp_head', function () {
            echo iro_get_description();
            echo iro_get_opengraph();
            echo iro_get_json_ld();
        }, 99);
    }
}

/**
 * 清洗任意文本为适合 meta 输出的纯文本
 */
function iro_clean_text(string $text): string
{
    if ($text === '') {
        return '';
    }
    $text = strip_shortcodes($text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text)
        ?? preg_replace('/\s+/', ' ', $text);
    return trim((string) $text);
}

/**
 * 当前页面的 canonical 风格 URL（不输出，仅供 og:url / JSON-LD 使用）
 */
function iro_get_canonical_url(): string
{
    if (is_singular()) {
        $url = get_permalink();
        return $url ? (string) $url : home_url('/');
    }
    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $link = get_term_link($term);
            if (!is_wp_error($link)) {
                return (string) $link;
            }
        }
    }
    if (is_author()) {
        return (string) get_author_posts_url(get_queried_object_id());
    }
    if (is_search()) {
        return (string) get_search_link();
    }
    if (is_front_page() || is_home()) {
        return home_url('/');
    }
    global $wp;
    return home_url('/' . ltrim((string) ($wp->request ?? ''), '/'));
}

// function iro_get_keywords()
// {
//     global $post;
//     $keywords = '';

//     if (is_singular()) {
//         $tags = get_the_tags();
//         if ($tags) {
//             $keywords = implode(',', array_column($tags, 'name'));
//         }
//     } elseif (is_category()) {
//         $cats = get_the_category();
//         if ($cats) {
//             $keywords = implode(',', array_column($cats, 'name'));
//         }
//     }

//     if (empty($keywords)) {
//         $keywords = iro_opt('iro_meta_keywords');
//     }

//     if (empty($keywords)) {
//         $keywords = get_bloginfo('name');
//     }

//     if (! empty($keywords)) {
//         return '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
//     }
//     return '';
// }

function iro_get_description_text(int $max = 240): string
{
    static $cache = [];

    if (isset($cache[$max])) {
        return $cache[$max];
    }

    $desc = '';

    if (is_singular()) {
        $post = get_post();
        if ($post && !post_password_required($post)) {
            $raw  = !empty($post->post_excerpt) ? $post->post_excerpt : $post->post_content;
            $desc = iro_clean_text($raw);
        }
    } elseif (is_category() || is_tag() || is_tax()) {
        $desc = iro_clean_text((string) term_description());
    } elseif (is_author()) {
        $author = get_queried_object();
        if ($author && !empty($author->description)) {
            $desc = iro_clean_text((string) $author->description);
        }
    } elseif (is_search()) {
        $desc = iro_clean_text(sprintf('关于 “%s” 的搜索结果', get_search_query()));
    }

    if ($desc === '') {
        $desc = iro_clean_text((string) iro_opt('iro_meta_description'));
    }
    if ($desc === '') {
        $desc = iro_clean_text((string) get_bloginfo('description'));
    }
    if ($desc === '') {
        $desc = iro_clean_text((string) get_bloginfo('name'));
    }

    if ($desc !== '' && $max > 0) {
        $desc = mb_strimwidth($desc, 0, $max, '…', 'UTF-8');
    }

    $cache[$max] = $desc;
    return $desc;
}

function iro_get_description(): string
{
    $desc = iro_get_description_text(240);
    if ($desc === '') {
        return '';
    }
    return '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
}

function iro_get_og_title(): string
{
    if (is_singular()) {
        return (string) get_the_title();
    }
    if (is_category() || is_tag() || is_tax()) {
        return (string) single_term_title('', false);
    }
    if (is_author()) {
        return (string) get_the_author_meta('display_name', get_queried_object_id());
    }
    if (is_search()) {
        return sprintf('搜索：%s', get_search_query());
    }
    if (is_front_page() || is_home()) {
        return (string) get_bloginfo('name');
    }
    return (string) wp_get_document_title();
}

function iro_extract_first_image(string $content): string
{
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m)) {
        return (string) $m[1];
    }
    return '';
}

function iro_get_og_image(): string
{
    $image = '';

    if (is_singular()) {
        $post = get_post();
        if ($post) {
            if (has_post_thumbnail($post)) {
                $thumb = get_the_post_thumbnail_url($post, 'full');
                if ($thumb) {
                    $image = (string) $thumb;
                }
            }
            if ($image === '') {
                $image = iro_extract_first_image((string) $post->post_content);
            }
        }
    }

    if ($image === '') {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo) {
                $image = (string) $logo;
            }
        }
    }

    return iro_media_optimize_image_url($image, [
        'format'  => 'jpg',
        'quality' => 82,
        'width'   => 1200,
        'height'  => 630,
    ]);
}

function iro_get_opengraph(): string
{
    $url   = iro_get_canonical_url();
    $title = iro_get_og_title();
    $desc  = iro_get_description_text(200);
    $site  = (string) get_bloginfo('name');
    $image = iro_get_og_image();

    $type = (is_singular('post')) ? 'article' : 'website';

    $tags = [];

    $tags[] = '<meta property="og:type" content="' . esc_attr($type) . '">';
    $tags[] = '<meta property="og:site_name" content="' . esc_attr($site) . '">';
    $tags[] = '<meta property="og:title" content="' . esc_attr($title) . '">';
    if ($desc !== '') {
        $tags[] = '<meta property="og:description" content="' . esc_attr($desc) . '">';
    }
    $tags[] = '<meta property="og:url" content="' . esc_url($url) . '">';
    if ($image !== '') {
        $tags[] = '<meta property="og:image" content="' . esc_attr($image) . '">';
    }
    $tags[] = '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">';

    if ($type === 'article') {
        $post = get_post();
        if ($post) {
            $tags[] = '<meta property="article:published_time" content="' . esc_attr(get_the_date('c', $post)) . '">';
            $tags[] = '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $post)) . '">';
            $cats = get_the_category($post->ID);
            if ($cats) {
                foreach ($cats as $c) {
                    $tags[] = '<meta property="article:section" content="' . esc_attr($c->name) . '">';
                }
            }
        }
    }

    // Twitter Card
    $tags[] = '<meta name="twitter:card" content="' . ($image !== '' ? 'summary_large_image' : 'summary') . '">';
    $tags[] = '<meta name="twitter:title" content="' . esc_attr($title) . '">';
    if ($desc !== '') {
        $tags[] = '<meta name="twitter:description" content="' . esc_attr($desc) . '">';
    }
    if ($image !== '') {
        $tags[] = '<meta name="twitter:image" content="' . esc_url($image) . '">';
    }

    return implode("\n", $tags) . "\n";
}

function iro_build_breadcrumb_jsonld($post): ?array
{
    if (!$post) {
        return null;
    }

    $items    = [];
    $position = 1;

    $items[] = [
        '@type'    => 'ListItem',
        'position' => $position++,
        'name'     => '首页',
        'item'     => home_url('/'),
    ];

    if (is_singular('post')) {
        $cats = get_the_category($post->ID);
        if ($cats) {
            $cat = $cats[0];
            $ancestors = array_reverse(get_ancestors($cat->term_id, 'category'));
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, 'category');
                if ($ancestor && !is_wp_error($ancestor)) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => $ancestor->name,
                        'item'     => (string) get_term_link($ancestor),
                    ];
                }
            }
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $cat->name,
                'item'     => (string) get_term_link($cat),
            ];
        }
    }

    $items[] = [
        '@type'    => 'ListItem',
        'position' => $position,
        'name'     => get_the_title($post),
        'item'     => (string) get_permalink($post),
    ];

    return [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}

function iro_get_json_ld(): string
{
    $site_url  = home_url('/');
    $site_name = (string) get_bloginfo('name');
    $lang      = (string) get_bloginfo('language');
    $graph     = [];

    // ---- WebSite ----
    $website = [
        '@type'      => 'WebSite',
        '@id'        => $site_url . '#website',
        'url'        => $site_url,
        'name'       => $site_name,
        'inLanguage' => $lang,
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url('/?s={search_term_string}'),
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
    $graph[] = $website;

    // ---- Organization ----
    $org = [
        '@type' => 'Organization',
        '@id'   => $site_url . '#organization',
        'name'  => $site_name,
        'url'   => $site_url,
    ];
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        if ($logo_url) {
            $org['logo'] = [
                '@type' => 'ImageObject',
                'url'   => (string) $logo_url,
            ];
        }
    }
    $graph[] = $org;

    // ---- 文章 / 页面 ----
    if (is_singular()) {
        $post = get_post();
        if ($post) {
            $permalink = (string) get_permalink($post);

            $entity = [
                '@type'            => is_page() ? 'WebPage' : 'Article',
                '@id'              => $permalink . '#article',
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id'   => $permalink,
                ],
                'headline'      => (string) get_the_title($post),
                'datePublished' => get_the_date('c', $post),
                'dateModified'  => get_the_modified_date('c', $post),
                'author'        => [
                    '@type' => 'Person',
                    'name'  => (string) get_the_author_meta('display_name', $post->post_author),
                    'url'   => (string) get_author_posts_url($post->post_author),
                ],
                'publisher' => [
                    '@id' => $site_url . '#organization',
                ],
                'description' => iro_get_description_text(200),
                'inLanguage'  => $lang,
                'url'         => $permalink,
            ];

            $image = iro_get_og_image();
            if ($image !== '') {
                $entity['image'] = $image;
            }

            if (is_singular('post')) {
                $cats = get_the_category($post->ID);
                if ($cats) {
                    $entity['articleSection'] = wp_list_pluck($cats, 'name');
                }
                $tags = get_the_tags($post->ID);
                if ($tags && !is_wp_error($tags)) {
                    $entity['keywords'] = implode(',', wp_list_pluck($tags, 'name'));
                }
            }

            $graph[] = $entity;

            $breadcrumb = iro_build_breadcrumb_jsonld($post);
            if ($breadcrumb) {
                $graph[] = $breadcrumb;
            }
        }
    } elseif (is_category() || is_tag() || is_tax()) {
        // ---- 归档页 ----
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $link = get_term_link($term);
            if (!is_wp_error($link)) {
                $graph[] = [
                    '@type'       => 'CollectionPage',
                    '@id'         => $link . '#webpage',
                    'url'         => (string) $link,
                    'name'        => (string) single_term_title('', false),
                    'description' => iro_get_description_text(200),
                    'inLanguage'  => $lang,
                    'isPartOf'    => [
                        '@id' => $site_url . '#website',
                    ],
                ];
            }
        }
    }

    $data = [
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ];

    // 不要加 JSON_UNESCAPED_SLASHES：让 “/” 被转义为 “\/”，
    // 防止内容里出现 </script> 提前闭合标签。
    $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return '';
    }

    return '<script type="application/ld+json">' . $json . '</script>' . "\n";
}

// 外部链接自动加nofollow
add_filter('the_content', 'siren_auto_link_nofollow', 20);
function siren_auto_link_nofollow(string $content): string
{
    if (is_admin() || is_feed() || $content === '') {
        return $content;
    }

    return preg_replace_callback(
        '/<a\s([^>]*?)href=(["\'])([^"\']+)\2([^>]*?)>/i',
        function ($m) {
            $before = $m[1];
            $quote  = $m[2];
            $href   = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $after  = $m[4];

            $is_same_origin = iro_media_is_same_origin($href);

            $attrs      = $before . $after;
            $has_rel    = preg_match('/\brel\s*=/i', $attrs);
            $has_target = preg_match('/\btarget\s*=/i', $attrs);

            $add = '';

            if (!$is_same_origin) {
                if (!$has_target) {
                    $add .= ' target="_blank"';
                }
                if (!$has_rel) {
                    $add .= ' rel="nofollow noopener noreferrer"';
                }
            }

            // 没有需要新增的属性就原样返回，避免无意义的重写
            if ($add === '') {
                return $m[0];
            }

            return '<a ' . $before . 'href=' . $quote . $m[3] . $quote . $after . $add . '>';
        },
        $content
    );
}

// 图片自动加标题
add_filter('the_content', 'siren_auto_images_alt', 20);
function siren_auto_images_alt(string $content): string
{
    if (is_admin() || is_feed() || $content === '' || strpos($content, '<img') === false) {
        return $content;
    }

    $post = get_post();
    $post_title = $post ? (string) $post->post_title : '';

    return preg_replace_callback(
        '/<img\b([^>]*?)>/i',
        function ($m) use ($post_title) {
            $attrs = $m[1];

            $has_alt   = preg_match('/\balt\s*=/i', $attrs);
            $has_title = preg_match('/\btitle\s*=/i', $attrs);

            $add = '';
            if (!$has_alt) {
                $add .= ' alt="' . esc_attr($post_title) . '"';
            }
            if (!$has_title && $post_title !== '') {
                $add .= ' title="' . esc_attr($post_title) . '"';
            }

            return '<img' . $attrs . $add . '>';
        },
        $content
    );
}
