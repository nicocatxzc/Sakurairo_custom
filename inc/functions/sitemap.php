<?php

/**
 * 站点地图
 */

if (!defined('ABSPATH')) {
    exit;
}

const IRO_SITEMAP_ROUTE_VAR     = 'iro_sitemap';
const IRO_SITEMAP_BATCH_SIZE    = 500;

if (iro_opt("iro_sitemap", false)) {
    add_action('init', 'iro_sitemap_register_routes', 10);
    add_filter('query_vars', function (array $vars): array {
        $vars[] = IRO_SITEMAP_ROUTE_VAR;

        return $vars;
    });
    // 先接管核心的 /wp-sitemap*，再由本站路由分发
    add_action('template_redirect', 'iro_sitemap_redirect_core', 0);
    add_action('template_redirect', 'iro_sitemap_dispatch', 1);
    add_action('update_option_iro_options', function () {
        flush_rewrite_rules();
    });
    add_filter('wp_sitemaps_enabled', '__return_false');
    add_filter('robots_txt', 'iro_sitemap_robots_txt', 20, 2);
}

/**
 * 匹配路由表
 *
 * @return array<string,string>
 */
function iro_sitemap_routes(): array
{
    return [
        'sitemap\.xml'          => 'index',
        'sitemap-post\.xml'     => 'post',
        'sitemap-page\.xml'     => 'page',
        'sitemap-category\.xml' => 'category',
        'sitemap-tag\.xml'      => 'tag',
        'sitemap\.xsl'          => 'stylesheet',
        'sitemap-urls\.xsl'     => 'urls-stylesheet',
    ];
}

function iro_sitemap_register_routes(): void
{
    foreach (iro_sitemap_routes() as $pattern => $route) {
        add_rewrite_rule(
            '^' . $pattern . '$',
            'index.php?' . IRO_SITEMAP_ROUTE_VAR . '=' . $route,
            'top'
        );
    }
}

function iro_sitemap_dispatch(): void
{
    $route = get_query_var(IRO_SITEMAP_ROUTE_VAR);

    if (!is_string($route) || $route === '') {
        return;
    }

    switch ($route) {
        case 'index':
            iro_sitemap_render_index();
            break;
        case 'post':
            iro_sitemap_render_post_type('post');
            break;
        case 'page':
            iro_sitemap_render_post_type('page');
            break;
        case 'category':
            iro_sitemap_render_taxonomy('category');
            break;
        case 'tag':
            iro_sitemap_render_taxonomy('post_tag');
            break;
        case 'stylesheet':
            iro_sitemap_render_stylesheet('sitemap.xsl');
            break;
        case 'urls-stylesheet':
            iro_sitemap_render_stylesheet('sitemap-urls.xsl');
            break;
        default:
            status_header(404);
            exit;
    }

    exit;
}

// 核心停用地图后仍会为 /wp-sitemap* 注册伪静态（只是 404），
// 这里在核心的 template_redirect（优先级 10）之前把它们 301 到本站对应的地图
function iro_sitemap_redirect_core(): void
{
    if (get_query_var('sitemap') !== '') {
        wp_safe_redirect(home_url('/sitemap.xml'), 301);
        exit;
    }

    $stylesheet = get_query_var('sitemap-stylesheet');

    if ($stylesheet === '') {
        return;
    }

    wp_safe_redirect(home_url($stylesheet === 'index' ? '/sitemap.xsl' : '/sitemap-urls.xsl'), 301);
    exit;
}

// robots.txt 里原本由核心追加的 Sitemap 行随地图一起停用了，这里补回本站地址
function iro_sitemap_robots_txt(string $output, bool $is_public): string
{
    if (!$is_public) {
        return $output;
    }

    return $output . "\nSitemap: " . esc_url(home_url('/sitemap.xml')) . "\n";
}

function iro_sitemap_render_index(): void
{
    header('Content-Type: ' . 'application/xml; charset=UTF-8');
    iro_sitemap_echo_declaration('sitemap.xsl');

    ob_start();
?>
    <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        <?php foreach (['sitemap-post.xml', 'sitemap-page.xml', 'sitemap-category.xml', 'sitemap-tag.xml'] as $file) : ?>
            <sitemap>
                <loc><?= esc_url(home_url('/' . $file)) ?></loc>
            </sitemap>
        <?php endforeach; ?>
    </sitemapindex>
<?php
    echo ob_get_clean();
}

/**
 * 按批产出已发布文章，避免整站文章一次性进内存
 *
 * @return Generator<int,WP_Post>
 */
function iro_sitemap_post_batches(string $post_type): Generator
{
    $paged = 1;

    do {
        $query = new WP_Query([
            'post_type'              => $post_type,
            'post_status'            => 'publish',
            'posts_per_page'         => IRO_SITEMAP_BATCH_SIZE,
            'paged'                  => $paged,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'fields'                 => 'ids',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        if ($query->posts === []) {
            break;
        }

        // 按批取回 ID 后一次性预热文章缓存，避免逐条查库
        _prime_post_caches($query->posts, false, false);

        foreach ($query->posts as $post_id) {
            $post = get_post($post_id);

            if ($post) {
                yield $post;
            }
        }

        $paged++;
    } while (count($query->posts) === IRO_SITEMAP_BATCH_SIZE);
}

function iro_sitemap_render_post_type(string $post_type): void
{
    header('Content-Type: ' . 'application/xml; charset=UTF-8');
    iro_sitemap_echo_declaration('sitemap-urls.xsl');

    ob_start();
?>
    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        <?php foreach (iro_sitemap_post_batches($post_type) as $post) : ?>
            <url>
                <loc><?= esc_url(get_permalink($post)) ?></loc>
                <lastmod><?= esc_html(str_replace(' ', 'T', $post->post_modified_gmt) . '+00:00') ?></lastmod>
            </url>
        <?php endforeach; ?>
    </urlset>
<?php
    echo ob_get_clean();
}

function iro_sitemap_render_taxonomy(string $taxonomy): void
{
    header('Content-Type: ' . 'application/xml; charset=UTF-8');
    iro_sitemap_echo_declaration('sitemap-urls.xsl');

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        // 空归档没有索引价值
        'hide_empty' => true,
        'orderby'    => 'term_id',
        'order'      => 'ASC',
    ]);

    if (is_wp_error($terms)) {
        $terms = [];
    }

    ob_start();
?>
    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        <?php foreach ($terms as $term) : ?>
            <?php
            $link = get_term_link($term);

            if (is_wp_error($link)) {
                continue;
            }
            ?>
            <url>
                <loc><?= esc_url($link) ?></loc>
            </url>
        <?php endforeach; ?>
    </urlset>
<?php
    echo ob_get_clean();
}

// <?xml 声明会被 short_open_tag 当成 PHP 起始标签，只能由 PHP 输出
function iro_sitemap_echo_declaration(string $stylesheet): void
{
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

    echo '<?xml-stylesheet type="text/xsl" href="' . esc_url(home_url('/' . $stylesheet)) . '"?>' . "\n";
}

function iro_sitemap_render_stylesheet(string $file): void
{
    $path = get_template_directory() . '/inc/' . $file;

    if (!is_readable($path)) {
        status_header(404);
        exit;
    }

    header('Content-Type: ' . 'text/xsl; charset=UTF-8');
    readfile($path);

    exit;
}
