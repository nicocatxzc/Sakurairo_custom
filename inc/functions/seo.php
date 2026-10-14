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
            $has_canonical   = preg_match('/<link\s[^>]*rel=["\']canonical["\']/i', $head_content);
            $has_pagination  = preg_match('/<link\s[^>]*rel=["\'](?:prev|next)["\']/i', $head_content);

            echo $head_content;

            if (!$has_description) {
                echo iro_get_description();
            }

            // keywords 默认不输出，防止被搜索引擎判定为关键词滥用。
            // if (!$has_keywords) {
            //     echo iro_get_keywords();
            // }

            if (!$has_canonical) {
                echo iro_get_canonical();
            }

            if (!$has_pagination) {
                echo iro_get_rel_pagination();
            }

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
            echo iro_get_canonical();
            echo iro_get_rel_pagination();
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
 * 当前页面的 canonical 风格 URL（供 canonical / og:url / JSON-LD 使用）
 */
function iro_get_canonical_url(): string
{
    $paged = (int) get_query_var('paged');

    // 分页页必须自指：指回第一页会让第 2 页起的内容被判定为与第一页重复
    if ($paged > 1 && !is_singular()) {
        return (string) get_pagenum_link($paged);
    }

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
    if (is_home()) {
        // 首页设为静态页面时，文章列表页是另一个地址，指回站点根会被判成与首页重复
        $posts_page = (int) get_option('page_for_posts');
        return $posts_page ? (string) get_permalink($posts_page) : home_url('/');
    }
    if (is_front_page()) {
        return home_url('/');
    }
    global $wp;
    return home_url('/' . ltrim((string) ($wp->request ?? ''), '/'));
}

/**
 * canonical 链接
 *
 * 内核的 rel_canonical() 只认 is_singular()，列表页与归档页一直没人输出，
 * 这里把这些页面补上；分页页靠 iro_get_canonical_url() 自指。
 */
function iro_get_canonical(): string
{
    if (is_singular()) {
        return '';
    }

    $url = iro_get_canonical_url();

    return $url === '' ? '' : '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
}

/**
 * 分页的关联链接
 *
 * 内核 4.4 起不再输出；Google 已不使用，但 Bing 与多数 SEO 检查器仍会读它。
 * 只在主查询真的分页时输出，避免给单页文章留下自相矛盾的关系。
 */
function iro_get_rel_pagination(): string
{
    $paged = max(1, (int) get_query_var('paged'));
    $total = (int) ($GLOBALS['wp_query']->max_num_pages ?? 0);

    if ($total < 2) {
        return '';
    }

    $links = '';

    if ($paged > 1) {
        $links .= '<link rel="prev" href="' . esc_url(get_pagenum_link($paged - 1)) . '">' . "\n";
    }

    if ($paged < $total) {
        $links .= '<link rel="next" href="' . esc_url(get_pagenum_link($paged + 1)) . '">' . "\n";
    }

    return $links;
}

/**
 * 站点默认图像：站长自定义 logo 优先，其次 WordPress 站点图标
 *
 * 分享图与结构化数据都要一张「站点自己的图」，只认 custom_logo 的话，
 * 只设了站点图标的站会在 og:image 与 Organization.logo 上一起缺图。
 */
function iro_get_site_image_url(): string
{
    $logo_id = get_theme_mod('custom_logo');

    if ($logo_id) {
        $logo = wp_get_attachment_image_url($logo_id, 'full');
        if ($logo) {
            return (string) $logo;
        }
    }

    return (string) get_site_icon_url();
}

/**
 * 站点图标链接
 *
 * 站长清空「站点图标」后不能留空 href：部分浏览器会把空 href 解析成当前地址再请求一次。
 * 兜底用站点根下约定俗成的 /favicon.ico。
 */
function iro_get_favicon_url(): string
{
    return (string) (iro_opt('favicon_link') ?: home_url('/favicon.ico'));
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
        $desc = iro_clean_text(sprintf(__('关于 “%s” 的搜索结果', 'sakurairo'), get_search_query()));
    }

    if ($desc === '') {
        $desc = iro_clean_text(iro__((string) iro_opt('iro_meta_description')));
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
        return sprintf(__('搜索：%s', 'sakurairo'), get_search_query());
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
        $image = iro_get_site_image_url();
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
        'name'     => iro__('首页'),
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

/**
 * 当前列表页要输出的文章清单
 *
 * 只用主查询已经取到的文章，不额外查库；列表页的 ItemList 是给搜索引擎与 AI
 * 抓取看的「这一页列出了什么」，缺了它归档页只剩一个没有内容的 WebPage。
 */
function iro_build_item_list_jsonld(string $url): ?array
{
    $items    = [];
    $position = 1;

    foreach ((array) ($GLOBALS['wp_query']->posts ?? []) as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }

        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => (string) get_the_title($post),
            'url'      => (string) get_permalink($post),
        ];
    }

    if ($items === []) {
        return null;
    }

    return [
        '@type'           => 'ItemList',
        '@id'             => $url . '#itemlist',
        'numberOfItems'   => count($items),
        'itemListOrder'   => 'https://schema.org/ItemListOrderDescending',
        'itemListElement' => $items,
    ];
}

/**
 * 分类/标签页的面包屑：首页 > 祖先分类… > 当前分类
 *
 * @return array<string,mixed>
 */
function iro_build_term_breadcrumb_jsonld(WP_Term $term, string $url): array
{
    $items = [[
        '@type'    => 'ListItem',
        'position' => 1,
        'name'     => iro__('首页'),
        'item'     => home_url('/'),
    ]];

    $position = 2;

    foreach (array_reverse(get_ancestors($term->term_id, $term->taxonomy)) as $ancestor_id) {
        $ancestor = get_term($ancestor_id, $term->taxonomy);
        if ($ancestor && !is_wp_error($ancestor)) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => (string) $ancestor->name,
                'item'     => (string) get_term_link($ancestor),
            ];
        }
    }

    $items[] = [
        '@type'    => 'ListItem',
        'position' => $position,
        'name'     => (string) $term->name,
        'item'     => $url,
    ];

    return [
        '@type'           => 'BreadcrumbList',
        '@id'             => $url . '#breadcrumb',
        'itemListElement' => $items,
    ];
}

/**
 * 列表页（首页、分类、标签、自定义分类）的页面实体
 *
 * @param array<string,mixed>|null $breadcrumb 已经建好的面包屑节点
 * @return array<int,array<string,mixed>> 直接并入 @graph 的节点列表
 */
function iro_build_list_jsonld(string $url, string $name, string $description, string $type, ?array $breadcrumb = null): array
{
    $site_url = home_url('/');
    $list     = iro_build_item_list_jsonld($url);

    $page = [
        '@type'       => $type,
        '@id'         => $url . '#webpage',
        'url'         => $url,
        'name'        => $name,
        'description' => $description,
        'inLanguage'  => (string) get_bloginfo('language'),
        'isPartOf'    => ['@id' => $site_url . '#website'],
        'publisher'   => ['@id' => $site_url . '#organization'],
    ];

    if ($list !== null) {
        $page['mainEntity'] = ['@id' => $list['@id']];
    }

    if ($breadcrumb !== null) {
        $page['breadcrumb'] = ['@id' => $breadcrumb['@id']];
    }

    $nodes = [$page];

    if ($list !== null) {
        $nodes[] = $list;
    }

    if ($breadcrumb !== null) {
        $nodes[] = $breadcrumb;
    }

    return $nodes;
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
    $logo_url = iro_get_site_image_url();
    if ($logo_url !== '') {
        $org['logo'] = [
            '@type' => 'ImageObject',
            'url'   => $logo_url,
        ];
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
    } elseif (is_home()) {
        // ---- 博客列表页 ----
        $blog_url = iro_get_canonical_url();
        $graph = array_merge($graph, iro_build_list_jsonld(
            $blog_url,
            $site_name,
            iro_get_description_text(200),
            'Blog'
        ));
    } elseif (is_category() || is_tag() || is_tax()) {
        // ---- 归档页 ----
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $link = get_term_link($term);
            if (!is_wp_error($link)) {
                $link = (string) $link;
                // 页面实体用当前页地址（分页页自指），面包屑末项仍指向分类本身
                $graph = array_merge($graph, iro_build_list_jsonld(
                    iro_get_canonical_url(),
                    (string) single_term_title('', false),
                    iro_get_description_text(200),
                    'CollectionPage',
                    iro_build_term_breadcrumb_jsonld($term, $link)
                ));
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
