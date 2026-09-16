<?php
class IroAnimeList
{

    private static function buildPagination(int $page, int $totalItems, int $perPage): array
    {
        $totalPages = $perPage > 0 ? (int) ceil($totalItems / $perPage) : 0;
        return [
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'total_items'  => $totalItems,
            'per_page'     => $perPage,
            'has_next'     => $page < $totalPages,
        ];
    }

    private static function emptyResult(int $page, int $perPage, string $message = '没有数据'): array
    {
        return [
            'success'    => false,
            'message'    => $message,
            'data'       => [],
            'pagination' => [
                'current_page' => $page,
                'total_pages'  => 0,
                'total_items'  => 0,
                'per_page'     => $perPage,
            ],
        ];
    }

    private static function errorResult(string $message, int $perPage = 12): array
    {
        return [
            'success'    => false,
            'message'    => $message,
            'data'       => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages'  => 0,
                'total_items'  => 0,
                'per_page'     => $perPage,
            ],
        ];
    }

    // bilibili
    public static function getBilibiliList(int $page = 1, int $perPage = 15, string $type = 'bangumi'): array
    {
        $userID = iro_opt('bilibili_id');
        if (empty($userID)) {
            return [];
        }

        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $typeInt = ($type === 'movie' || $type === '2') ? 2 : 1;

        $cacheKey = "bilibili_{$userID}_{$page}_{$perPage}_{$typeInt}";
        $list     = null;

        // 读缓存
        $cached = get_transient($cacheKey);
        if ($cached !== false && is_string($cached)) {
            $parsed = json_decode($cached, true);
            if (is_array($parsed) && isset($parsed['data']['data']['list'])) {
                $list = $parsed['data'];
            } else {
                delete_transient($cacheKey);
            }
        }

        // 请求远端
        if ($list === null) {
            $url = add_query_arg([
                'vmid' => $userID,
                'pn'   => $page,
                'ps'   => $perPage,
                'type' => $typeInt,
            ], 'https://api.bilibili.com/x/space/bangumi/follow/list');

            $response = wp_remote_get($url, [
                'headers' => [
                    'Cookie'     => iro_opt('bilibili_cookie') ?: '',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36',
                    'Origin'     => 'https://space.bilibili.com',
                    'Referer'    => 'https://space.bilibili.com/',
                ],
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) {
                return self::errorResult($response->get_error_message(), $perPage);
            }

            $body = wp_remote_retrieve_body($response);
            $list = json_decode($body, true);

            set_transient($cacheKey, wp_json_encode([
                'data'      => $list,
                'timestamp' => time() * 1000,
            ]), 1800);
        }

        if (!isset($list['data']['list']) || !is_array($list['data']['list'])) {
            return self::emptyResult($page, $perPage);
        }

        // 数据结构统一化
        $formatted = [];
        foreach ($list['data']['list'] as $item) {
            $formatted[] = [
                'name'      => $item['title'] ?? '',
                'name_cn'   => $item['title'] ?? '',
                'date'      => $item['publish']['pub_time'] ?? '',
                'summary'   => $item['evaluate'] ?? '',
                'url'       => $item['url'] ?? '',
                'images'    => $item['cover'] ?? '',
                'eps'       => $item['total_count'] ?? 1,
                'ep_status' => $item['ep_status'] ?? 0,
                'progress'  => $item['progress'] ?? 0,
                'tags'      => $item['styles'] ?? [],
            ];
        }

        // 分页（保持一致：API 已分页，仍按 offset 切片）
        $totalItems    = (int) ($list['data']['total'] ?? 0);
        $offset        = ($page - 1) * $perPage;
        $paginatedData = array_slice($formatted, $offset, $perPage);

        return [
            'success'    => true,
            'data'       => array_values($paginatedData),
            'pagination' => self::buildPagination($page, $totalItems, $perPage),
        ];
    }

    // bangumi
    public static function getBangumiList(int $page = 1, int $perPage = 12): array
    {
        $userID = iro_opt('bangumi_id');
        if (empty($userID)) {
            return [];
        }

        $page    = max(1, $page);
        $perPage = max(1, $perPage);

        $cacheKey    = "bangumi_{$userID}";
        $collections = null;

        // 读缓存
        $cached = get_transient($cacheKey);
        if ($cached !== false && is_string($cached)) {
            $parsed      = json_decode($cached, true);
            $collections = $parsed['data'] ?? null;
            if (!is_array($collections)) {
                delete_transient($cacheKey);
                $collections = null;
            }
        }

        // 请求远端
        if ($collections === null) {
            $url = "https://api.bgm.tv/v0/users/{$userID}/collections";

            $response = wp_remote_get($url, [
                'headers' => [
                    'User-Agent' => 'nicocatxzc/hachimi(https://github.com/nicocatxzc/hachimi):WordPressTheme',
                ],
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) {
                return self::errorResult($response->get_error_message(), $perPage);
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            $collections = [];
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $item) {
                    // type: 2=在看, 3=看过 ; subject_type: 2=动画
                    if (
                        in_array((int) ($item['type'] ?? 0), [2, 3], true) &&
                        (int) ($item['subject_type'] ?? 0) === 2
                    ) {
                        $collections[] = $item;
                    }
                }
            }

            set_transient($cacheKey, wp_json_encode([
                'data'      => $collections,
                'timestamp' => time() * 1000,
            ]), 1800);
        }

        if (empty($collections)) {
            return self::emptyResult($page, $perPage);
        }

        // 数据结构统一化
        $formatted = [];
        foreach ($collections as $item) {
            $subject = $item['subject'] ?? [];

            $tags = [];
            if (!empty($subject['tags']) && is_array($subject['tags'])) {
                foreach ($subject['tags'] as $tag) {
                    $tags[] = $tag['name'] ?? '';
                }
            }

            $eps      = (int) ($subject['eps'] ?? 0);
            $epStatus = (int) ($item['ep_status'] ?? 0);
            $progress = ($eps > 0 && $epStatus) ? ($epStatus / $eps) * 100 : 0;

            $formatted[] = [
                'name'      => $subject['name'] ?? '',
                'name_cn'   => $subject['name_cn'] ?? '',
                'date'      => $subject['date'] ?? '',
                'summary'   => $subject['short_summary'] ?? '',
                'url'       => 'https://bgm.tv/subject/' . ($subject['id'] ?? ''),
                'images'    => $subject['images']['large'] ?? '',
                'eps'       => $eps ?: 1,
                'ep_status' => $epStatus,
                'tags'      => $tags,
                'progress'  => $progress,
            ];
        }

        $totalItems    = count($formatted);
        $offset        = ($page - 1) * $perPage;
        $paginatedData = array_slice($formatted, $offset, $perPage);

        return [
            'success'    => true,
            'data'       => array_values($paginatedData),
            'pagination' => self::buildPagination($page, $totalItems, $perPage),
        ];
    }

    // mal
    public static function getMyAnimeList(int $page = 1, int $perPage = 12): array
    {
        $username = iro_opt('my_anime_list_username');
        if (empty($username)) {
            return [];
        }

        $page    = max(1, $page);
        $perPage = max(1, $perPage);

        // 排序参数
        switch ((int) iro_opt('my_anime_list_sort')) {
            case 1:
                $sortQuery = 'order=16&order2=5&status=7';
                break; // 状态 + 最近更新
            case 2:
                $sortQuery = 'order=5&status=7';
                break; // 最近更新
            case 3:
                $sortQuery = 'order=16&status=7';
                break; // 状态
            default:
                $sortQuery = 'order=5&status=7';
                break;
        }

        $cacheKey = 'mal_' . md5($username . '|' . $sortQuery);
        $data     = null;

        // 读缓存
        $cached = get_transient($cacheKey);
        if ($cached !== false && is_string($cached)) {
            $parsed = json_decode($cached, true);
            if (is_array($parsed)) {
                $data = $parsed;
            } else {
                delete_transient($cacheKey);
            }
        }

        // 请求远端
        if ($data === null) {
            $url = "https://myanimelist.net/animelist/{$username}/load.json?{$sortQuery}";

            $response = wp_remote_get($url, [
                'headers' => [
                    'Host'       => 'myanimelist.net',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.97 Safari/537.36',
                ],
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) {
                return self::errorResult($response->get_error_message(), $perPage);
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (!is_array($data)) {
                $data = [];
            }

            set_transient($cacheKey, wp_json_encode($data), 1800);
        }

        if (empty($data)) {
            return self::emptyResult($page, $perPage);
        }

        // 数据结构统一化
        $formatted = [];
        foreach ($data as $item) {
            $eps      = (int) ($item['anime_num_episodes'] ?? 0);
            $watched  = (int) ($item['num_watched_episodes'] ?? 0);
            $progress = $eps > 0 ? ($watched / $eps) * 100 : 0;

            // 图片地址转换
            $image = '';
            if (!empty($item['anime_image_path']) && preg_match('/\/anime(.*?)\./', $item['anime_image_path'], $m)) {
                $image = "https://cdn.myanimelist.net/images/anime/{$m[1]}.jpg";
            }

            $formatted[] = [
                'name'      => $item['anime_title'] ?? '',
                'name_cn'   => $item['anime_title_eng'] ?? '',
                'date'      => $item['start_date'] ?? '',
                'summary'   => '',
                'url'       => 'https://myanimelist.net' . ($item['anime_url'] ?? ''),
                'images'    => $image,
                'eps'       => $eps ?: 1,
                'ep_status' => $watched,
                'progress'  => $progress,
                'tags'      => [],
            ];
        }

        $totalItems    = count($formatted);
        $offset        = ($page - 1) * $perPage;
        $paginatedData = array_slice($formatted, $offset, $perPage);

        return [
            'success'    => true,
            'data'       => array_values($paginatedData),
            'pagination' => self::buildPagination($page, $totalItems, $perPage),
        ];
    }
}
