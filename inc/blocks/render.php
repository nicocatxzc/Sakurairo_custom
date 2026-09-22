<?php
add_action('init', function () {

    // 页面类
    add_shortcode('friend_link', function () {
        ob_start();
        require get_template_directory() . '/frontend/components/page/template/friend_link.php';
        return ob_get_clean();
    });
    register_block_type('sakurairo/friend-link', [
        'render_callback' => function ($attributes) {
            ob_start();
            require get_template_directory() . '/frontend/components/page/template/friend_link.php';
            return ob_get_clean();
        },
    ]);

    add_shortcode('bangumi', function () {
        ob_start();
        require get_template_directory() . '/frontend/components/page/template/bangumi.php';
        return ob_get_clean();
    });
    register_block_type('sakurairo/bangumi', [
        'render_callback' => function ($attributes) {
            ob_start();
            require get_template_directory() . '/frontend/components/page/template/bangumi.php';
            return ob_get_clean();
        },
    ]);

    add_shortcode('favlist', function () {
        ob_start();
        require get_template_directory() . '/frontend/components/page/template/bilibili_favlist.php';
        return ob_get_clean();
    });
    register_block_type('sakurairo/favlist', [
        'render_callback' => function ($attributes) {
            ob_start();
            require get_template_directory() . '/frontend/components/page/template/bilibili_favlist.php';
            return ob_get_clean();
        },
    ]);

    add_shortcode('steam', function () {
        ob_start();
        require get_template_directory() . '/frontend/components/page/template/steam.php';
        return ob_get_clean();
    });
    register_block_type('sakurairo/steam', [
        'render_callback' => function ($attributes) {
            ob_start();
            require get_template_directory() . '/frontend/components/page/template/steam.php';
            return ob_get_clean();
        },
    ]);

    add_shortcode('archive', function () {
        ob_start();
        require get_template_directory() . '/frontend/components/page/template/archive.php';
        return ob_get_clean();
    });
    register_block_type('sakurairo/timeline', [
        'render_callback' => function ($attributes) {
            ob_start();
            require get_template_directory() . '/frontend/components/page/template/archive.php';
            return ob_get_clean();
        },
    ]);


    /*
     * GitHub Card
     */
    add_shortcode('ghcard', function ($atts) {

        $atts = shortcode_atts([
            'path' => '',
        ], $atts, 'ghcard');

        return hachimi_render_ghcard($atts);
    });

    register_block_type('sakurairo/ghcard', [
        'render_callback' => function ($attributes) {
            return hachimi_render_ghcard($attributes);
        },
    ]);


    /*
     * Notice
     */
    add_shortcode('task', function ($atts, $content = '') {
        return hachimi_render_notice([
            'type'    => 'task',
            'content' => $content,
        ]);
    });

    add_shortcode('warning', function ($atts, $content = '') {
        return hachimi_render_notice([
            'type'    => 'warning',
            'content' => $content,
        ]);
    });

    add_shortcode('noway', function ($atts, $content = '') {
        return hachimi_render_notice([
            'type'    => 'noway',
            'content' => $content,
        ]);
    });

    add_shortcode('buy', function ($atts, $content = '') {
        return hachimi_render_notice([
            'type'    => 'buy',
            'content' => $content,
        ]);
    });

    register_block_type('sakurairo/notice', [
        'render_callback' => function ($attributes, $content = '') {
            return hachimi_render_notice([
                'type'    => $attributes['type'] ?? 'task',
                'content' => $attributes['content'] ?? $content,
            ]);
        },
    ]);


    /*
     * Show Card
     */
    add_shortcode('showcard', function ($atts, $content = '') {

        $atts = shortcode_atts([
            'icon'  => '',
            'title' => '',
            'img'   => '',
            'link'  => '',
            'color' => '',
        ], $atts, 'showcard');

        return hachimi_render_showcard([
            'img'   => $atts['img'],
            'link'  => $atts['link'] ?: $content,
            'color' => $atts['color'],
            'icon'  => $atts['icon'],
            'title' => $atts['title'],
        ]);
    });

    register_block_type('sakurairo/showcard', [
        'render_callback' => function ($attributes, $content = '') {
            return hachimi_render_showcard([
                'img'   => $attributes['img'] ?? '',
                'link'  => $attributes['link'] ?? $content,
                'color' => $attributes['color'] ?? '',
                'icon'  => $attributes['icon'] ?? '',
                'title' => $attributes['title'] ?? '',
            ]);
        },
    ]);


    /*
     * Conversation
     */
    add_shortcode('conversations', function ($atts, $content = '') {

        $atts = shortcode_atts([
            'avatar'    => '',
            'direction' => 'row',
            'username'  => '',
        ], $atts, 'conversations');

        return hachimi_render_conversations([
            'username'  => $atts['username'],
            'avatar'    => $atts['avatar'],
            'direction' => $atts['direction'],
            'content'   => $content,
        ]);
    });

    register_block_type('sakurairo/conversation', [
        'render_callback' => function ($attributes, $content = '') {

            return hachimi_render_conversations([
                'username'  => $attributes['username'] ?? '',
                'avatar'    => $attributes['avatar'] ?? '',
                'direction' => $attributes['direction'] ?? 'row',
                'content'   => $content ?: ($attributes['content'] ?? ''),
            ]);
        },
    ]);


    /*
     * Bilibili
     */
    add_shortcode('vbilibili', function ($atts, $content = '') {
        return hachimi_render_bilibili([
            'content' => $content,
        ]);
    });

    register_block_type('sakurairo/vbilibili', [
        'render_callback' => function ($attributes, $content = '') {
            return hachimi_render_bilibili([
                'content' => $attributes['videoId'] ?? $content,
            ]);
        },
    ]);
}, 999);

// ghcard
// github解析
function hachimi_parse_github_path($path)
{

    //完整 URL
    if (preg_match(
        '/github\.com\/([^\/]+)\/([^\/\?#]+)/',
        $path,
        $matches
    )) {
        return [
            'owner' => $matches[1],
            'repo'  => $matches[2],
        ];
    }

    //owner/repo
    if (preg_match(
        '/^([a-zA-Z0-9\-]+)\/([a-zA-Z0-9\-\._]+)$/',
        $path,
        $matches
    )) {
        return [
            'owner' => $matches[1],
            'repo'  => $matches[2],
        ];
    }

    return false;
}
function hachimi_get_ghcard_data($args)
{
    $path = trim($args['path'] ?? '');

    if ($path === '') {
        return [];
    }

    $repo_info = hachimi_parse_github_path($path);

    if (!$repo_info) {
        return [
            'error' => true,
            'message' => '无法被解析的github仓库:' . $path,
        ];
    }

    $owner = $repo_info['owner'];
    $repo  = $repo_info['repo'];

    $cache_key = 'ghcard_' . md5($owner . '/' . $repo);
    $data      = get_transient($cache_key);

    if (!$data) {

        $api_url = "https://api.github.com/repos/{$owner}/{$repo}";

        $response = wp_remote_get($api_url, [
            'headers' => [
                'User-Agent' => 'WordPress Theme',
                'Accept'     => 'application/vnd.github.v3+json',
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {

            $data = [
                'url'         => "https://github.com/{$owner}/{$repo}",
                'name'        => $repo,
                'owner'       => $owner,
                'description' => "未知的仓库{$path}",
                'stars'       => 0,
                'language'    => '',
                'forks'       => 0,
                'license'     => '',
            ];
        } else {

            $body     = wp_remote_retrieve_body($response);
            $api_data = json_decode($body, true);

            $data = [
                'url'         => $api_data['html_url'] ?? '',
                'name'        => $api_data['name'] ?? '',
                'owner'       => $api_data['owner']['login'] ?? '',
                'description' => $api_data['description'] ?? '',
                'stars'       => $api_data['stargazers_count'] ?? 0,
                'language'    => $api_data['language'] ?? '',
                'forks'       => $api_data['forks_count'] ?? 0,
                'license'     => $api_data['license']['key'] ?? '',
            ];
        }

        set_transient($cache_key, $data, DAY_IN_SECONDS);
    }

    return $data;
}


// 渲染ghcard
function hachimi_render_ghcard($args)
{
    $data = hachimi_get_ghcard_data($args);

    if (!empty($data['error'])) {
        return '<div>' . esc_html($data['message']) . '</div>';
    }

    $data_info = hachimi_encode_data($data);

    ob_start();
    require get_template_directory() . '/frontend/components/block/ghcard.php';
    return ob_get_clean();
}

// notice
function hachimi_get_notice_data($args)
{
    return [
        'type'    => $args['type'] ?? 'task',
        'content' => $args['content'] ?? '',
    ];
}

function hachimi_render_notice($args)
{
    $data = hachimi_get_notice_data($args);

    $data_info = hachimi_encode_data($data);

    ob_start();
    require get_template_directory() . '/frontend/components/block/notice.php';
    return ob_get_clean();
}

// showcard
function hachimi_get_showcard_data($args)
{
    return [
        'img'   => esc_url($args['img'] ?? ''),
        'link'  => esc_url($args['link'] ?? ''),
        'color' => esc_attr($args['color'] ?? ''),
        'icon'  => esc_attr($args['icon'] ?? ''),
        'title' => wp_kses_post($args['title'] ?? ''),
    ];
}

function hachimi_render_showcard($args)
{
    $data = hachimi_get_showcard_data($args);

    $data_info = hachimi_encode_data($data);

    ob_start();
    require get_template_directory() . '/frontend/components/block/showcard.php';
    return ob_get_clean();
}

// conversation
function hachimi_get_conversations_data($args)
{
    $direction = $args['direction'] ?? 'row';

    if (!in_array($direction, ['row', 'row-reverse'], true)) {
        $direction = 'row';
    }

    return [
        'username'  => $args['username'] ?? '',
        'avatar'    => $args['avatar'] ?? '',
        'direction' => $direction,
        'content'   => $args['content'] ?? '',
    ];
}

function hachimi_render_conversations($args)
{
    $data = hachimi_get_conversations_data($args);

    $data_info = hachimi_encode_data($data);

    ob_start();
    require get_template_directory() . '/frontend/components/block/conversation.php';
    return ob_get_clean();
}

// bvideo
function hachimi_get_bilibili_data($args)
{
    $content = $args['content'] ?? '';

    preg_match_all('/av([0-9]+)/i', $content, $av_matches);
    preg_match_all('/BV([a-zA-Z0-9]+)/', $content, $bv_matches);

    return [
        'av' => $av_matches[1] ?? [],
        'bv' => $bv_matches[1] ?? [],
    ];
}

function hachimi_render_bilibili($args)
{
    $data = hachimi_get_bilibili_data($args);

    $data_info = hachimi_encode_data($data);

    ob_start();
    require get_template_directory() . '/frontend/components/block/bvideo.php';
    return ob_get_clean();
}
