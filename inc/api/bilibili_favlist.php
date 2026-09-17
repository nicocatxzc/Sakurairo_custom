<?php
if (!defined('ABSPATH')) exit;

// 获取收藏夹列表
function iro_get_bilibili_favlist()
{
    $user_id = (int) iro_opt('bilibili_id');
    if (!$user_id) return null;

    $raw_data = iro_swr_cache(
        "bilibili-{$user_id}-favlist-all",
        function () use ($user_id) {
            $res = wp_remote_get(
                'https://api.bilibili.com/x/v3/fav/folder/created/list-all?' . http_build_query([
                    'up_mid' => $user_id,
                ]),
                [
                    'timeout' => 10,
                    'headers' => [
                        'Cookie'     => (string) iro_opt('bilibili_cookie', ''),
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                        'Origin'     => 'https://space.bilibili.com',
                        'Referer'    => 'https://space.bilibili.com/',
                    ],
                ]
            );

            if (is_wp_error($res)) return null;

            $body = json_decode(wp_remote_retrieve_body($res), true);
            return $body['data'] ?? null;
        }
    );

    if (!$raw_data) return null;

    $show_private = (bool) iro_opt('bilibili_show_private_favlist', false);

    $list = null;
    if (isset($raw_data['list']) && is_array($raw_data['list'])) {
        $list = $show_private
            ? $raw_data['list']
            : array_values(array_filter($raw_data['list'], function ($item) {
                $attr = (int) ($item['attr'] ?? 0);
                return ($attr & 1) === 0; // 非私密
            }));
    }

    return array_merge($raw_data, ['list' => $list]);
}

// 获取收藏夹详情
function iro_get_bilibili_fav_detail(int $fav_id, int $page = 1)
{
    $per_page = 20;
    $user_id  = (int) iro_opt('bilibili_id');

    if (!$user_id) return null;
    if (!$fav_id || $page < 1) return null;

    // 检查收藏夹是否存在
    $fav_list = iro_get_bilibili_favlist();
    $exists   = false;
    if (!empty($fav_list['list'])) {
        foreach ($fav_list['list'] as $item) {
            if ((int) ($item['id'] ?? 0) === $fav_id) {
                $exists = true;
                break;
            }
        }
    }
    if (!$exists) return null;

    $raw_data = iro_swr_cache(
        "bilibili-{$user_id}-fav-{$fav_id}-page-{$page}-ps-{$per_page}",
        function () use ($fav_id, $page, $per_page) {
            $res = wp_remote_get(
                'https://api.bilibili.com/x/v3/fav/resource/list?' . http_build_query([
                    'media_id' => $fav_id,
                    'pn'       => $page,
                    'ps'       => $per_page,
                    'platform' => 'web',
                ]),
                [
                    'timeout' => 10,
                    'headers' => [
                        'Cookie'     => (string) iro_opt('bilibili_cookie', ''),
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                        'Origin'     => 'https://space.bilibili.com',
                        'Referer'    => 'https://space.bilibili.com/',
                    ],
                ]
            );

            if (is_wp_error($res)) return null;

            $body = json_decode(wp_remote_retrieve_body($res), true);
            return $body['data'] ?? null;
        }
    );

    if (!$raw_data) {
        return [
            'medias'     => [],
            'pagination' => [
                'current_page' => $page,
                'total_pages'  => 0,
                'total_items'  => 0,
                'per_page'     => $per_page,
            ],
        ];
    }

    $total_items = (int) ($raw_data['info']['media_count'] ?? 0);
    $total_pages = (int) ceil($total_items / $per_page);

    return [
        'medias'     => $raw_data['medias'] ?? [],
        'pagination' => [
            'current_page' => $page,
            'total_pages'  => $total_pages,
            'total_items'  => $total_items,
            'per_page'     => $per_page,
        ],
    ];
}
