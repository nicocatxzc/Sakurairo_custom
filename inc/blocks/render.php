<?php
//解析短代码  
function register_shortcodes()
{
    // 提示块
    function iro_render_notice($type, $content = '')
    {

        $map = [
            'task'    => ['fa-solid fa-clipboard-list', 'task'],
            'warning' => ['fa-solid fa-triangle-exclamation', 'warning'],
            'noway'   => ['fa-solid fa-square-xmark', 'noway'],
            'buy'     => ['fa-solid fa-square-check', 'buy'],
        ];

        if (!isset($map[$type])) {
            return '';
        }

        [$icon, $class] = $map[$type];

        return sprintf(
            '<div class="shortcodestyle %s">
                <i class="%s"></i>
                <span>%s</span>
            </div>',
            esc_attr($class),
            esc_attr($icon),
            wp_kses_post($content)
        );
    }

    add_shortcode('task', fn($a, $c = '') => iro_render_notice('task', $c));
    add_shortcode('warning', fn($a, $c = '') => iro_render_notice('warning', $c));
    add_shortcode('noway', fn($a, $c = '') => iro_render_notice('noway', $c));
    add_shortcode('buy', fn($a, $c = '') => iro_render_notice('buy', $c));

    register_block_type('sakurairo/notice', [
        'render_callback' => 'iro_render_notice_block',
    ]);
    function iro_render_notice_block($attributes, $content)
    {

        $type = $attributes['type'] ?? 'task';
        $text = $attributes['content'] ?? '';

        return iro_render_notice($type, $text);
    }

    // gh卡片
    function iro_render_ghcard($path)
    {
        if (strpos($path, 'https://github.com/') === 0) {
            $path = str_replace('https://github.com/', '', $path);
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+\/[a-zA-Z0-9_.-]+$/', $path)) {
            return '<p>' . __('Invalid GitHub repository path:', 'sakurairo') . ' ' . esc_html($path) . '</p>';
        }

        list($username, $repo) = explode('/', $path, 2);

        $cache_key = 'ghcard_repo_' . md5(strtolower($path));
        $cache_store_key = 'ghcard_repo_store_' . md5(strtolower($path));
        $repo_data = get_transient($cache_key);
        $data_state = $repo_data === false ? 'missing' : 'fresh';

        if ($repo_data === false) {
            $api_url = sprintf('https://api.github.com/repos/%s/%s', rawurlencode($username), rawurlencode($repo));
            $response = wp_remote_get($api_url, array(
                'timeout' => 10,
                'headers' => array(
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0',
                    'Accept'     => 'application/vnd.github+json'
                )
            ));

            if (is_wp_error($response)) {
                error_log(sprintf('[ghcard] Request error for %s: %s', $path, $response->get_error_message()));
            } else {
                error_log(sprintf('[ghcard] API status %s for %s', wp_remote_retrieve_response_code($response), $path));
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (is_array($data) && empty($data['message'])) {
                    $repo_data = array(
                        'full_name'   => $data['full_name'] ?? $path,
                        'description' => $data['description'] ?? '',
                        'language'    => $data['language'] ?? '',
                        'stars'       => intval($data['stargazers_count'] ?? 0),
                        'forks'       => intval($data['forks_count'] ?? 0),
                        'issues'      => intval($data['open_issues_count'] ?? 0),
                        'url'         => $data['html_url'] ?? 'https://github.com/' . $path,
                        'updated_at'  => $data['pushed_at'] ?? '',
                    );

                    set_transient($cache_key, $repo_data, $cache_ttl);
                    update_option($cache_store_key, array(
                        'data' => $repo_data,
                        'timestamp' => time(),
                    ), false);
                    $data_state = 'fresh';
                    error_log(sprintf('[ghcard] Cached data for %s', $path));
                } else {
                    error_log(sprintf('[ghcard] Unexpected payload for %s: %s', $path, substr($body, 0, 200)));
                }
            }

            if (!$repo_data) {
                $stored = get_option($cache_store_key, false);
                if ($stored && isset($stored['data'])) {
                    $repo_data = $stored['data'];
                    $data_state = 'stale';
                    error_log(sprintf('[ghcard] Using stale cache for %s', $path));
                }
            }
        }

        if (!$repo_data) {
            return sprintf(
                '<div class="ghcard shortcodestyle ghcard--error"><p>%s</p></div>',
                sprintf(__('Unable to fetch GitHub data for %s at the moment.', 'sakurairo'), esc_html($path))
            );
        }

        $language_raw = $repo_data['language'];
        $language = $language_raw !== '' ? esc_html($language_raw) : __('Unknown', 'sakurairo');
        $description = $repo_data['description'] !== '' ? esc_html($repo_data['description']) : __('No description provided.', 'sakurairo');

        $updated_markup = '';
        if (!empty($repo_data['updated_at'])) {
            $updated_timestamp = strtotime($repo_data['updated_at']);
            if ($updated_timestamp) {
                $updated_markup = sprintf(
                    '<span class="ghcard-updated">%s</span>',
                    sprintf(
                        __('Updated %s ago', 'sakurairo'),
                        human_time_diff($updated_timestamp, current_time('timestamp'))
                    )
                );
            }
        }

        $language_colors = array(
            'JavaScript' => '#f7df1e',
            'TypeScript' => '#3178c6',
            'PHP'        => '#8993be',
            'Python'     => '#3776ab',
            'Ruby'       => '#cc342d',
            'Go'         => '#00ADD8',
            'Java'       => '#f89820',
            'C++'        => '#00599C',
            'C#'         => '#178600',
            'C'          => '#555555',
            'Shell'      => '#89e051',
            'Swift'      => '#f05138',
            'Kotlin'     => '#A97BFF'
        );

        $lang_color = ($language_raw !== '' && isset($language_colors[$language_raw]))
            ? $language_colors[$language_raw]
            : 'var(--shortcode-color-accent)';

        $language_markup = sprintf(
            '<span class="ghcard-language"><span class="ghcard-language-dot" style="background:%s;"></span>%s</span>',
            esc_attr($lang_color),
            $language
        );

        $meta_markup = $updated_markup !== ''
            ? '<div class="ghcard-meta">' . $updated_markup . '</div>'
            : '';

        $card_classes = 'ghcard shortcodestyle';
        if ($data_state === 'stale') {
            $card_classes .= ' ghcard--stale';
        }

        $stale_notice = '';
        if ($data_state === 'stale') {
            $stored = get_option($cache_store_key, false);
            $age = '';
            if ($stored && isset($stored['timestamp'])) {
                $age = human_time_diff($stored['timestamp'], current_time('timestamp'));
            }
            $stale_notice = sprintf(
                '<div class="ghcard-stale-tip">%s</div>',
                $age ? sprintf(__('Showing cached data (%s old).', 'sakurairo'), esc_html($age)) : __('Showing cached data.', 'sakurairo')
            );
        }

        return sprintf(
            '<div class="%s">
                <div class="ghcard-header">
                    <div class="ghcard-title">
                        <i class="fa-brands fa-github" aria-hidden="true"></i>
                        <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>
                    </div>
                    %s
                </div>
                <p class="ghcard-description">%s</p>
                %s
                <div class="ghcard-stats">
                    <span><i class="fa-solid fa-star" aria-hidden="true"></i>%s</span>
                    <span><i class="fa-solid fa-code-branch" aria-hidden="true"></i>%s</span>
                    <span><i class="fa-regular fa-circle-dot" aria-hidden="true"></i>%s</span>
                </div>
                %s
            </div>',
            esc_attr($card_classes),
            esc_url($repo_data['url']),
            esc_html($repo_data['full_name']),
            $language_markup,
            $description,
            $meta_markup,
            number_format_i18n($repo_data['stars']),
            number_format_i18n($repo_data['forks']),
            number_format_i18n($repo_data['issues']),
            $stale_notice
        );
    }

    add_shortcode('ghcard', function ($attr, $content = '') {
        //获取内容
        $atts = shortcode_atts(array("path" => "mirai-mamori/Sakurairo"), $attr);

        $path = trim($atts['path']);

        return iro_render_ghcard($path);
    });

    register_block_type('sakurairo/ghcard', [
        'render_callback' => 'iro_render_ghcard_block',
    ]);
    function iro_render_ghcard_block($attributes, $content)
    {

        $path = $attributes['path'] ?? '';

        return iro_render_ghcard($path);
    }

    // 展示卡片
    function iro_render_showcard($atts, $content)
    {

        return sprintf(
            '<div class="showcard">
                <div class="img" style="background:url(%s);background-size:cover;background-position:center;">
                    <a href="%s" target="_blank" rel="noopener noreferrer">
                        <button class="showcard-button" style="color:%s;">
                            <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </a>
                </div>
                <div class="icon-title">
                    <i class="%s" style="color:%s;"></i>
                    <span class="title">%s</span>
                </div>
            </div>',
            esc_url($atts['img'] ?? ''),
            esc_url($atts['link'] ?? $content ?? ''),
            esc_attr($atts['color'] ?? ''),
            esc_attr($atts['icon'] ?? ''),
            esc_attr($atts['color'] ?? ''),
            wp_kses_post($atts['title'] ?? '')
        );
    }


    add_shortcode('showcard', function ($attr, $content = '') {
        $atts = shortcode_atts(array("icon" => "", "title" => "", "img" => "", "color" => ""), $attr);
        return iro_render_showcard($atts, $content);
    });

    register_block_type('sakurairo/showcard', [
        'render_callback' => 'iro_render_showcard_block',
    ]);
    function iro_render_showcard_block($attributes, $content)
    {

        $img = $attributes['img'] ?? '';
        $link = $attributes['link'] ?? '';
        $color = $attributes['color'] ?? '';
        $icon = $attributes['icon'] ?? '';
        $title = $attributes['title'] ?? '';

        return iro_render_showcard([
            'img' => $img,
            'link' => $link,
            'color' => $color,
            'icon' => $icon,
            'title' => $title,
        ], '');
    }

    // 对话
    function iro_render_conversations($atts, $content)
    {
        // 基本信息
        $username = isset($atts['username']) ? $atts['username'] : '';
        $avatar = isset($atts['avatar']) ? $atts['avatar'] : '';
        $direction = isset($atts['direction']) && in_array($atts['direction'], ['row', 'row-reverse'])
            ? $atts['direction']
            : 'row';

        if (empty($avatar) && !empty($username)) {
            $user = get_user_by('login', $username);
            if ($user) {
                $avatar = get_avatar_url($user->ID, 40);
            }
        }

        $speaker_alt = '';
        if (!empty($username)) {
            $speaker_alt = '<span class="screen-reader-text">' .
                sprintf(__("%s says: ", "sakurairo"), esc_html($username)) .
                '</span>';
        }

        return sprintf(
            '<div class="conversations-code" style="flex-direction: %s;">
                <img src="%s">
                <div class="conversations-code-text">%s%s</div>
            </div>',
            $direction,
            esc_url($avatar),
            $speaker_alt,
            $content
        );
    }

    add_shortcode('conversations', function ($attr, $content = '') {
        $atts = shortcode_atts(array("avatar" => "", "direction" => "", "username" => ""), $attr);
        return iro_render_conversations($atts, $content);
    });

    register_block_type('sakurairo/conversation', [
        'render_callback' => 'iro_render_conversations_block',
    ]);
    function iro_render_conversations_block($attributes, $content)
    {

        $avatar = $attributes['avatar'] ?? '';
        $direction = $attributes['direction'] ?? '';
        $content = $attributes['content'] ?? '';

        return iro_render_conversations([
            'avatar' => $avatar,
            'direction' => $direction,
        ], $content);
    }

    // 折叠
    function iro_render_collapse($atts, $content = null)
    {
        $atts = shortcode_atts(array("title" => ""), $atts);
        ob_start();
?>
        <a href="javascript:void(0)" class="collapseButton">
            <div class="collapse shortcodestyle">
                <i class="fa-solid fa-angle-down"></i>
                <span class="xTitle"><?= $atts['title'] ?></span>
                <span class="ecbutton"><?php _e('Expand / Collapse', 'sakurairo'); ?></span>
            </div>
        </a>
        <div class="xContent" style="display: none;"><?= do_shortcode($content) ?></div>
<?php
        return ob_get_clean();
    }

    add_shortcode('collapse', function ($atts, $content = null) {
        return iro_render_collapse($atts, $content);
    });

    register_block_type('sakurairo/collapse', [
        'render_callback' => 'iro_render_collapse_block',
    ]);
    function iro_render_collapse_block($attributes, $content)
    {
        return iro_render_collapse([
            'title' => $attributes['title'] ?? '',
        ], $attributes['content'] ?? $content);
    }

    // bilibili
    function iro_render_bilibili($content)
    {
        preg_match_all('/av([0-9]+)/', $content, $av_matches);
        preg_match_all('/BV([a-zA-Z0-9]+)/', $content, $bv_matches);
        $iframes = '';

        // av号
        if (!empty($av_matches[1])) {
            foreach ($av_matches[1] as $av) {
                $av = intval($av);

                $iframe_url = 'https://player.bilibili.com/player.html?avid=' . $av . '&page=1&autoplay=0&danmaku=0';
                $iframe = '<div style="position: relative; padding: 30% 45%;"><iframe src="' . $iframe_url . '" frameborder="no" scrolling="no" sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts" allowfullscreen="allowfullscreen" style="position: absolute; width: 100%; height: 100%; left: 0; top: 0;"> </iframe></div><br>';
                $iframes .= $iframe;
            }
        }
        // bv号
        if (!empty($bv_matches[1])) {
            foreach ($bv_matches[1] as $bv) {

                $iframe_url = 'https://player.bilibili.com/player.html?bvid=' . $bv . '&page=1&autoplay=0&danmaku=0';
                $iframe = '<div style="position: relative; padding: 30% 45%;"><iframe src="' . $iframe_url . '" frameborder="no" scrolling="no" sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts" allowfullscreen="allowfullscreen" style="position: absolute; width: 100%; height: 100%; left: 0; top: 0;"> </iframe></div><br>';
                $iframes .= $iframe;
            }
        }
        return $iframes;
    }

    add_shortcode('vbilibili', function ($atts, $content = null) {
        return iro_render_bilibili($content);
    });

    register_block_type('sakurairo/vbilibili', [
        'render_callback' => 'iro_render_bilibili_block',
    ]);
    function iro_render_bilibili_block($attributes, $content)
    {
        $bid = $attributes['videoId'] ?? '';

        return iro_render_bilibili($bid);
    }

    function iro_render_steamuser($atts = array(), $content = null)
    {
        $key = iro_opt('steam_key');
        if (empty($key)) {
            // 多语言支持
            $lang = get_user_locale();
            if ($lang == 'zh_TW') {
                return '<div class="steam-error">需在Steam模板設置填寫Steam API KEY</div>';
            } elseif ($lang == 'ja') {
                return '<div class="steam-error">SteamテンプレートでSteam API KEYを設定してください</div>';
            } elseif ($lang == 'en_US') {
                return '<div class="steam-error">Please fill in Steam API KEY in Steam template settings</div>';
            } else {
                return '<div class="steam-error">需在Steam模板设置填写Steam API KEY</div>';
            }
        }
        preg_match_all('/\b7656\d{13}\b/', $content, $matches);
        $output = '<div class="steam-user-card">';
        foreach ($matches[0] as $steamid) {
            $url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $key . '&steamids=' . $steamid;
            $response = get_transient('steam_stat_' . $steamid);
            if (!$response) {
                $response = wp_remote_get($url);
                set_transient('steam_stat_' . $steamid, $response, 180);
            }

            // 添加错误检查，防止WP_Error被当作数组使用
            if (is_wp_error($response)) {
                $output .= '<div class="steam-error">API错误: ' . $response->get_error_message() . '</div>';
                continue;
            }

            $data = json_decode($response["body"], true);
            $player = $data['response']['players'][0] ?? [];

            // 多语言支持
            $lang = get_user_locale();
            $status_text = [
                'offline' => [
                    'zh_CN' => '离线',
                    'zh_TW' => '離線',
                    'ja' => 'オフライン',
                    'en_US' => 'Offline'
                ],
                'online' => [
                    'zh_CN' => '在线',
                    'zh_TW' => '在線',
                    'ja' => 'オンライン',
                    'en_US' => 'Online'
                ],
                'away' => [
                    'zh_CN' => '离开',
                    'zh_TW' => '離開',
                    'ja' => '退席中',
                    'en_US' => 'Away'
                ],
                'unknown' => [
                    'zh_CN' => '未知状态，请提交issue',
                    'zh_TW' => '未知狀態，請提交issue',
                    'ja' => '不明なステータス、issueを提出してください',
                    'en_US' => 'Unknown status, please submit an issue'
                ],
                'playing' => [
                    'zh_CN' => '正在玩',
                    'zh_TW' => '正在玩',
                    'ja' => 'プレイ中',
                    'en_US' => 'Playing'
                ],
                'last_online' => [
                    'zh_CN' => '上次在线',
                    'zh_TW' => '上次在線',
                    'ja' => '最終オンライン',
                    'en_US' => 'Last online'
                ],
                'error' => [
                    'zh_CN' => 'ID填写错误，请检验',
                    'zh_TW' => 'ID填寫錯誤，請檢驗',
                    'ja' => 'IDが間違っています、確認してください',
                    'en_US' => 'ID error, please check'
                ]
            ];

            $status = match ($player['personastate'] ?? 0) {
                0 => $status_text['offline'][$lang] ?? $status_text['offline']['zh_CN'],
                1 => $status_text['online'][$lang] ?? $status_text['online']['zh_CN'],
                3 => $status_text['away'][$lang] ?? $status_text['away']['zh_CN'],
                default => $status_text['unknown'][$lang] ?? $status_text['unknown']['zh_CN']
            };

            if (empty($data['response']['players'][0])) {
                $output .= '<div class="steam-error">' . ($status_text['error'][$lang] ?? $status_text['error']['zh_CN']) . '</div>';
            } else {
                $avatar = esc_attr(substr($player['avatar'], 0, strrpos($player['avatar'], '.')) . '_full' . substr($player['avatar'], strrpos($player['avatar'], '.')));
                $output .= '<div class="steam-profile">';
                $output .= '<div class="steam-profile-header">';
                $output .= '<img class="steam-avatar" src="' . $avatar . '" alt="Steam Avatar">';
                $output .= '<div class="steam-profile-info">';
                $output .= '<a href="' . esc_attr($player['profileurl']) . '" target="_blank" class="steam-username"><i class="fa-brands fa-steam"></i> ' . esc_attr($player['personaname']) . '</a>';
                $output .= '<div class="steam-status status-' . strtolower(str_replace(' ', '-', $status)) . '">' . $status . '</div>';
                $output .= '</div>'; // .steam-profile-info
                $output .= '</div>'; // .steam-profile-header

                if (!empty($player['gameextrainfo'])) {
                    $output .= '<div class="steam-game-info">';
                    $output .= '<a href="https://store.steampowered.com/app/' . esc_attr($player['gameid']) . '/" target="_blank" class="steam-game-name"><i class="fa-solid fa-gamepad"></i> ' .
                        ($status_text['playing'][$lang] ?? $status_text['playing']['zh_CN']) . ': ' . esc_attr($player['gameextrainfo']) . '</a>';
                    $output .= '<img class="steam-game-banner" src="https://shared.cdn.steamchina.queniuam.com/store_item_assets/steam/apps/' . esc_attr($player['gameid']) . '/header.jpg" alt="Game Banner">';
                    $output .= '</div>'; // .steam-game-info
                }

                if (($player['personastate'] ?? 0) === 0 && isset($player['lastlogoff'])) {
                    $last_online = wp_date('Y-m-d H:i', $player['lastlogoff']);
                    $output .= '<div class="steam-last-online"><i class="fa-regular fa-clock"></i> ' .
                        ($status_text['last_online'][$lang] ?? $status_text['last_online']['zh_CN']) . '：' . esc_attr($last_online) . '</div>';
                }

                $output .= '</div>'; // .steam-profile
            }
        }
        $output .= '</div>'; // .steam-user-card
        return $output;
    }

    add_shortcode('steamuser', function ($atts, $content = null) {
        return iro_render_steamuser($atts, $content);
    });

    register_block_type('sakurairo/steamuser', [
        'render_callback' => 'iro_render_steamuser_block',
    ]);
    function iro_render_steamuser_block($attributes, $content)
    {
        return iro_render_steamuser(array(), $attributes['content'] ?? $content);
    }

    function iro_render_checkbox($attr, $content = null)
    {
        $atts = shortcode_atts(array(
            'checked' => 'false',
            'inline' => 'false',
            'name'   => '',
            'value'  => '',
            'id'     => '',
        ), $attr);

        $is_true = function ($value) {
            return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), true);
        };

        $id = $atts['id'] !== '' ? sanitize_html_class($atts['id']) : wp_unique_id('iro-checkbox-');
        $classes = array('checkbox-code', 'shortcodestyle');
        if ($is_true($atts['inline'])) {
            $classes[] = 'inline';
        }

        $attributes = array(
            'type' => 'checkbox',
            'id'   => $id,
        );

        if ($atts['name'] !== '') {
            $attributes['name'] = sanitize_text_field($atts['name']);
        }
        if ($atts['value'] !== '') {
            $attributes['value'] = sanitize_text_field($atts['value']);
        }
        if ($is_true($atts['checked'])) {
            $attributes['checked'] = 'checked';
        }

        $attribute_html = '';
        foreach ($attributes as $key => $value) {
            $attribute_html .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }

        $label_content = trim(do_shortcode((string) $content));
        if ($label_content === '') {
            $label_content = __('Checkbox', 'sakurairo');
        }

        return sprintf(
            '<div class="%1$s"><input%2$s><span>%3$s</span></div>',
            esc_attr(implode(' ', array_filter($classes))),
            $attribute_html,
            wp_kses_post($label_content)
        );
    }

    add_shortcode('checkbox', function ($attr, $content = null) {
        return iro_render_checkbox($attr, $content);
    });

    register_block_type('sakurairo/checkbox', [
        'render_callback' => 'iro_render_checkbox_block',
    ]);
    function iro_render_checkbox_block($attributes, $content)
    {
        return iro_render_checkbox([
            'checked' => !empty($attributes['checked']) ? 'true' : 'false',
            'inline' => !empty($attributes['inline']) ? 'true' : 'false',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? '',
            'id' => $attributes['id'] ?? '',
        ], $attributes['content'] ?? $content);
    }

    function iro_render_label($attr, $content = null)
    {
        $atts = shortcode_atts(array("color" => "info", "shape" => ""), $attr);
        $color_map = array(
            'warning' => 'badge-warning',
            'severe'  => 'badge-severe',
            'info'    => 'badge-info',
        );

        $color = $color_map[strtolower($atts['color'])] ?? 'badge-info';
        $shape = strtolower($atts['shape']) === 'round' ? 'badge-rounded' : '';

        $classes = array('badge', $color);
        if ($shape !== '') {
            $classes[] = $shape;
        }

        return sprintf(
            '<span class="%1$s">%2$s</span>',
            esc_attr(implode(' ', $classes)),
            wp_kses_post(do_shortcode((string) $content))
        );
    }

    add_shortcode('label', function ($attr, $content = null) {
        return iro_render_label($attr, $content);
    });

    register_block_type('sakurairo/label', [
        'render_callback' => 'iro_render_label_block',
    ]);
    function iro_render_label_block($attributes, $content)
    {
        return iro_render_label([
            'color' => $attributes['color'] ?? 'info',
            'shape' => $attributes['shape'] ?? '',
        ], $attributes['content'] ?? $content);
    }

    function iro_render_progressbar($attr, $content = null)
    {
        $atts = shortcode_atts(array("color" => "default", "progress" => "100", "label" => ""), $attr);

        $progress = is_numeric($atts['progress']) ? floatval($atts['progress']) : 100;
        $progress = max(0, min(100, $progress));
        $progress_display = number_format_i18n($progress);

        $color_key = strtolower(trim($atts['color']));
        $color_map = array(
            'default' => 'bg-default',
            'primary' => 'bg-default',
            'accent'  => 'bg-default',
            'match'   => 'bg-match',
            'info'    => 'bg-info',
            'blue'    => 'bg-info',
            'teal'    => 'bg-info',
            'success' => 'bg-success',
            'green'   => 'bg-success',
            'warning' => 'bg-warning',
            'orange'  => 'bg-warning',
            'amber'   => 'bg-warning',
            'danger'  => 'bg-danger',
            'red'     => 'bg-danger',
            'error'   => 'bg-danger',
            'rose'    => 'bg-rose',
            'pink'    => 'bg-rose',
        );
        $color_class = $color_map[$color_key] ?? 'bg-default';

        $label = trim($atts['label']) !== '' ? $atts['label'] : trim(do_shortcode((string) $content));
        $label_markup = $label !== '' ? sprintf("<div class='progress-label'><span>%s</span></div>", esc_html($label)) : '';

        $width = round($progress, 2);

        return sprintf(
            "<div class='progress-wrapper'>
                <div class='progress-info'>%s
                    <div class='progress-percentage'><span>%s%%</span></div>
                </div>
                <div class='progress'>
                    <div class='progress-bar %s' style='width: %s%%;' role='progressbar' aria-valuenow='%s' aria-valuemin='0' aria-valuemax='100'></div>
                </div>
            </div>",
            $label_markup,
            $progress_display,
            esc_attr($color_class),
            esc_attr($width),
            esc_attr($width)
        );
    }

    add_shortcode('progressbar', function ($attr, $content = null) {
        return iro_render_progressbar($attr, $content);
    });

    register_block_type('sakurairo/progressbar', [
        'render_callback' => 'iro_render_progressbar_block',
    ]);
    function iro_render_progressbar_block($attributes, $content)
    {
        return iro_render_progressbar([
            'color' => $attributes['color'] ?? 'default',
            'progress' => $attributes['progress'] ?? '100',
            'label' => $attributes['label'] ?? '',
        ], $attributes['content'] ?? $content);
    }

    function iro_render_timeline($attr, $content = null)
    {
        $raw_content = trim((string) $content);
        if ($raw_content === '') {
            return '';
        }

        $atts = shortcode_atts(array(
            'layout' => 'vertical',
            'icon' => '',
            'accent' => 'default',
        ), $attr);

        $layout = strtolower($atts['layout']);
        $layout = in_array($layout, array('vertical', 'horizontal'), true) ? $layout : 'vertical';

        $accent_key = strtolower(trim($atts['accent']));
        $accent_whitelist = array('default', 'match', 'info', 'success', 'warning', 'danger', 'rose');
        if (!in_array($accent_key, $accent_whitelist, true)) {
            $accent_key = 'default';
        }

        $icon_class = trim($atts['icon']);

        $lines = preg_split('/\r\n|\r|\n/', $raw_content);
        $entries = array();
        foreach ($lines as $line_raw) {
            $line = trim($line_raw);
            if ($line === '') {
                continue;
            }

            $line_plain = trim(wp_strip_all_tags(preg_replace('/<br\s*\/?>(\s|&nbsp;)?/i', '', $line)));
            if ($line_plain === '') {
                continue;
            }

            $decoded_line = html_entity_decode($line, ENT_QUOTES, get_bloginfo('charset'));
            $parts = array_map('trim', explode('|', $decoded_line));
            if (count($parts) === 0) {
                continue;
            }

            $time = array_shift($parts);
            $title = count($parts) ? array_shift($parts) : '';
            $body_text = count($parts) ? implode("\n", $parts) : '';

            if ($time === '' && $title === '' && trim($body_text) === '') {
                continue;
            }

            $entries[] = array(
                'time' => $time,
                'title' => $title,
                'body' => $body_text,
            );
        }

        if (empty($entries)) {
            return '';
        }

        $wrapper_classes = array(
            'timeline-code',
            'timeline-layout-' . $layout,
            'timeline-accent-' . $accent_key,
        );

        $icon_markup = '';
        if ($icon_class !== '') {
            $icon_markup = sprintf('<i class="%s" aria-hidden="true"></i>', esc_attr($icon_class));
        }

        $output = sprintf('<div class="%s" role="list">', esc_attr(implode(' ', $wrapper_classes)));

        foreach ($entries as $entry) {
            $time_html = $entry['time'] !== '' ? str_replace('/', '<br>', esc_html($entry['time'])) : '';
            $title_html = $entry['title'] !== '' ? esc_html($entry['title']) : '';
            $body_html = '';
            if (trim($entry['body']) !== '') {
                $body_processed = wpautop(do_shortcode($entry['body']));
                $body_html = wp_kses_post($body_processed);
            }

            $node = '<div class="timeline-node" role="listitem">';
            $node .= '<div class="timeline-dot" aria-hidden="true">';
            $node .= ($icon_markup !== '') ? $icon_markup : '<span class="timeline-dot-symbol"></span>';
            $node .= '</div>';

            if ($time_html !== '') {
                $node .= sprintf('<div class="timeline-time">%s</div>', $time_html);
            }

            $node .= '<div class="timeline-card">';
            if ($title_html !== '') {
                $node .= sprintf('<div class="timeline-title">%s</div>', $title_html);
            }
            if ($body_html !== '') {
                $node .= sprintf('<div class="timeline-content">%s</div>', $body_html);
            }
            $node .= '</div>'; // .timeline-card
            $node .= '</div>'; // .timeline-node

            $output .= $node;
        }

        $output .= '</div>';

        return $output;
    }

    add_shortcode('timeline', function ($attr, $content = null) {
        return iro_render_timeline($attr, $content);
    });

    register_block_type('sakurairo/timeline', [
        'render_callback' => 'iro_render_timeline_block',
    ]);
    function iro_render_timeline_block($attributes, $content)
    {
        return iro_render_timeline([
            'layout' => $attributes['layout'] ?? 'vertical',
            'icon' => $attributes['icon'] ?? '',
            'accent' => $attributes['accent'] ?? 'default',
        ], $attributes['content'] ?? $content);
    }

    function iro_render_hidden($attr, $content = null)
    {
        $atts = shortcode_atts(array("tip" => "", "type" => "blur"), $attr);
        $type = strtolower($atts['type']) === 'background' ? 'background' : 'blur';
        $class = $type === 'background' ? 'hidden-text-background' : 'hidden-text-blur';
        $tip_attr = $atts['tip'] !== '' ? sprintf(' title="%s"', esc_attr($atts['tip'])) : '';

        return sprintf(
            '<span class="hidden-text %1$s"%2$s>%3$s</span>',
            esc_attr($class),
            $tip_attr,
            wp_kses_post(do_shortcode((string) $content))
        );
    }

    add_shortcode('hidden', function ($attr, $content = null) {
        return iro_render_hidden($attr, $content);
    });

    register_block_type('sakurairo/hidden', [
        'render_callback' => 'iro_render_hidden_block',
    ]);
    function iro_render_hidden_block($attributes, $content)
    {
        return iro_render_hidden([
            'tip' => $attributes['tip'] ?? '',
            'type' => $attributes['type'] ?? 'blur',
        ], $attributes['content'] ?? $content);
    }

    function iro_render_post_time($attr)
    {
        $atts = shortcode_atts(array("format" => 'Y-n-d G:i:s'), $attr);
        $format = $atts['format'] !== '' ? wp_strip_all_tags($atts['format']) : 'Y-n-d G:i:s';
        return esc_html(get_the_time($format));
    }

    add_shortcode('post_time', function ($attr) {
        return iro_render_post_time($attr);
    });

    register_block_type('sakurairo/post-time', [
        'render_callback' => 'iro_render_post_time_block',
    ]);
    function iro_render_post_time_block($attributes)
    {
        return iro_render_post_time([
            'format' => $attributes['format'] ?? 'Y-n-d G:i:s',
        ]);
    }

    function iro_render_post_modified_time($attr)
    {
        $atts = shortcode_atts(array("format" => 'Y-n-d G:i:s'), $attr);
        $format = $atts['format'] !== '' ? wp_strip_all_tags($atts['format']) : 'Y-n-d G:i:s';
        return esc_html(get_the_modified_time($format));
    }

    add_shortcode('post_modified_time', function ($attr) {
        return iro_render_post_modified_time($attr);
    });

    register_block_type('sakurairo/post-modified-time', [
        'render_callback' => 'iro_render_post_modified_time_block',
    ]);
    function iro_render_post_modified_time_block($attributes)
    {
        return iro_render_post_modified_time([
            'format' => $attributes['format'] ?? 'Y-n-d G:i:s',
        ]);
    }

    function iro_render_noshortcode($attr, $content = null)
    {
        return (string) $content;
    }

    add_shortcode('noshortcode', function ($attr, $content = null) {
        return iro_render_noshortcode($attr, $content);
    });

    register_block_type('sakurairo/noshortcode', [
        'render_callback' => 'iro_render_noshortcode_block',
    ]);
    function iro_render_noshortcode_block($attributes, $content)
    {
        return iro_render_noshortcode(array(), $attributes['content'] ?? $content);
    }
}
add_action('init', 'register_shortcodes');
//code end