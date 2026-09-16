<?php
//rest api支持
function permalink_tip()
{
    if (!get_option('permalink_structure')) {
        $msg = __('<b>为了更好的使用体验，请不要将<a href="/wp-admin/options-permalink.php">固定链接</a>设置为朴素。为此，您可能需要配置<a href="https://www.wpdaxue.com/wordpress-rewriterule.html" target="_blank">伪静态</a>。</b>', 'sakurairo'); /**/
        echo '<div class="notice notice-success is-dismissible" id="scheme-tip"><p><b>' . $msg . '</b></p></div>';
    }
}
add_action('admin_notices', 'permalink_tip');

/**
 * Router
 */
add_action('rest_api_init', function () {
    require_once get_template_directory() . '/inc/api/comments.php';
    register_rest_route(
        'sakura/v1',
        '/comments',
        array(
            'methods' => 'GET',
            'callback' => 'iro_get_comments',
            'permission_callback' => '__return_true'
        )
    );

    require_once get_template_directory() . '/inc/api/captcha.php';
    register_rest_route(
        'sakura/v1',
        '/captcha',
        array(
            'methods' => 'GET',
            'callback' => 'iro_create_captcha',
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'sakura/v1',
        '/captcha',
        array(
            'methods' => 'POST',
            'callback' => 'iro_verify_captcha',
            'permission_callback' => '__return_true'
        )
    );

    require_once get_template_directory() . '/inc/api/search_index.php';
    register_rest_route(
        'sakura/v1',
        '/search_index',
        array(
            'methods' => 'GET',
            'callback' => 'iro_get_search_index',
            'permission_callback' => '__return_true'
        )
    );

    require_once get_template_directory() . '/inc/api/bangumi.php';
    register_rest_route(
        'sakura/v1',
        '/bangumi/bangumi',
        array(
            'methods' => 'GET',
            'callback' => function (\WP_REST_Request $req) {
                return IroAnimeList::getBangumiList(
                    (int) $req->get_param('page') ?: 1,
                    (int) $req->get_param('per_page') ?: 12
                );
            },
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'sakura/v1',
        '/bangumi/bilibili',
        array(
            'methods' => 'GET',
            'callback' => function (\WP_REST_Request $req) {
                return IroAnimeList::getBilibiliList(
                    (int) $req->get_param('page') ?: 1,
                    (int) $req->get_param('per_page') ?: 15,
                    (string) ($req->get_param('type') ?? 'bangumi')
                );
            },
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'sakura/v1',
        '/bangumi/mal',
        array(
            'methods' => 'GET',
            'callback' => function (\WP_REST_Request $req) {
                return IroAnimeList::getMyAnimeList(
                    (int) $req->get_param('page') ?: 1,
                    (int) $req->get_param('per_page') ?: 12
                );
            },
            'permission_callback' => '__return_true'
        )
    );
});
