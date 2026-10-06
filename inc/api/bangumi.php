<?php
class IroAnimeList
{
    // 追番多时逐页拉取会拖慢首屏：限制页数与总耗时（bgm.tv 每页最多 50 条）
    private const BGM_PAGE_LIMIT = 50;
    private const BGM_MAX_PAGES = 10;
    private const BGM_TIME_BUDGET = 10;

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

    /**
     * 逐页拉取 bgm.tv 动画收藏
     *
     * 首页就拉不到（网络错误 / 非 200 / 响应不合规）返回 null，调用方据此不写缓存并报错；
     * 中途某页失败或超出时间预算时返回已拿到的部分，避免整页空白。
     *
     * @return array<int, array<string, mixed>>|null
     */
    private static function fetchBangumiCollections(string $userID): ?array
    {
        $items = [];
        $deadline = microtime(true) + self::BGM_TIME_BUDGET;

        for ($page = 0; $page < self::BGM_MAX_PAGES; $page++) {
            if ($page > 0 && microtime(true) > $deadline) {
                break;
            }

            $response = wp_remote_get(
                add_query_arg([
                    'subject_type' => 2,
                    'limit'        => self::BGM_PAGE_LIMIT,
                    'offset'       => $page * self::BGM_PAGE_LIMIT,
                ], "https://api.bgm.tv/v0/users/{$userID}/collections"),
                [
                    'headers' => [
                        'User-Agent' => 'nicocatxzc/hachimi(https://github.com/nicocatxzc/hachimi):WordPressTheme',
                    ],
                    'timeout' => 15,
                ]
            );

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                break;
            }

            $pageData = json_decode(wp_remote_retrieve_body($response), true);

            if (!isset($pageData['data']) || !is_array($pageData['data'])) {
                break;
            }

            $items = array_merge($items, $pageData['data']);

            $total = (int) ($pageData['total'] ?? count($items));
            if (count($items) >= $total || count($pageData['data']) < self::BGM_PAGE_LIMIT) {
                return $items;
            }
        }

        return $items === [] ? null : $items;
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

        $list = iro_swr_cache(
            "bilibili_{$userID}_{$page}_{$perPage}_{$typeInt}",
            function () use ($userID, $page, $perPage, $typeInt) {
                $url = add_query_arg([
                    'vmid' => $userID,
                    'pn'   => $page,
                    'ps'   => $perPage,
                    'type' => $typeInt,
                ], 'https://api.bilibili.com/x/space/bangumi/follow/list');

                $response = wp_remote_get($url, [
                    'headers' => [
                        'Cookie'     => iro_opt('bilibili_cookie') ?: '',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                        'Origin'     => 'https://space.bilibili.com',
                        'Referer'    => 'https://space.bilibili.com/',
                    ],
                    'timeout' => 15,
                ]);

                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                    return null;
                }

                $list = json_decode(wp_remote_retrieve_body($response), true);

                // 接口级报错（如用户不可见、被风控）不能当成有效列表缓存
                if (
                    !is_array($list) ||
                    (int) ($list['code'] ?? 0) !== 0 ||
                    !isset($list['data']['list']) ||
                    !is_array($list['data']['list'])
                ) {
                    return null;
                }

                return $list;
            }
        );

        if ($list === null) {
            return self::errorResult('请求 Bilibili 失败', $perPage);
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

        // 列表已由接口按 pn/ps 分页，这里不能再按全局偏移切一次
        return [
            'success'    => true,
            'data'       => $formatted,
            'pagination' => self::buildPagination($page, (int) ($list['data']['total'] ?? 0), $perPage),
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

        // 键加 _all：旧实现只缓存接口首屏（≤30 条）且没有完整标记，换键让残缺缓存立即失效
        $collections = iro_swr_cache(
            "bangumi_{$userID}_all",
            function () use ($userID) {
                $items = self::fetchBangumiCollections($userID);

                if ($items === null) {
                    return null;
                }

                $result = [];
                foreach ($items as $item) {
                    // bgm.tv 收藏类型 type：2=看过、3=在看；subject_type：2=动画
                    if (
                        in_array((int) ($item['type'] ?? 0), [2, 3], true) &&
                        (int) ($item['subject_type'] ?? 2) === 2
                    ) {
                        $result[] = $item;
                    }
                }

                return $result;
            }
        );

        if ($collections === null) {
            return self::errorResult('请求 Bangumi 失败', $perPage);
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

        $data = iro_swr_cache(
            'mal_' . md5($username . '|' . $sortQuery),
            function () use ($username, $sortQuery) {
                $url = "https://myanimelist.net/animelist/{$username}/load.json?{$sortQuery}";

                $response = wp_remote_get($url, [
                    'headers' => [
                        'Host'       => 'myanimelist.net',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.97 Safari/537.36',
                    ],
                    'timeout' => 15,
                ]);

                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                    return null;
                }

                $data = json_decode(wp_remote_retrieve_body($response), true);

                // 非 JSON（错误页、私有列表重定向）视为失败，不能缓存成「没有追番」
                return is_array($data) ? $data : null;
            }
        );

        if ($data === null) {
            return self::errorResult('请求 MyAnimeList 失败', $perPage);
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
