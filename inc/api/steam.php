<?php
if (!defined('ABSPATH')) exit;

class IroSteam
{
    // 封面 CDN 基址
    private const COVER_CDN = [
        'steamchina'      => 'https://shared.cdn.steamchina.queniuam.com',
        'steamakamai'     => 'https://shared.akamai.steamstatic.com',
        'steamfastly'     => 'https://shared.fastly.steamstatic.com',
        'steamcloudflare' => 'https://shared.cloudflare.steamstatic.com',
    ];

    // 商店链接（%d 为 appid）
    private const STORE_URL = [
        'steam'     => 'https://store.steampowered.com/app/%d',
        'xiaoheihe' => 'https://www.xiaoheihe.cn/app/topic/game/pc/%d',
        'steamdb'   => 'https://steamdb.info/app/%d',
    ];

    // 封面资源路径按此顺序换区查询：锁区游戏在本地可见区拿不到 assets，需要宽松区兜底
    private const REGION_PRIORITY = ['US', 'HK', 'CN'];

    // 单次 IStoreBrowseService 请求的 appid 上限
    private const STORE_ITEMS_LIMIT = 100;

    private static function useCache(): bool
    {
        return (bool) iro_opt('page_template_data_cache', true);
    }

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

    private static function errorResult(string $message, int $perPage = 20): array
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

    // 拉取整个游戏库（已按最后游玩时间排序），失败返回 null
    private static function fetchGames(string $steamID, string $steamKey): ?array
    {
        $refresh = function () use ($steamID, $steamKey) {
            $response = wp_remote_get(
                add_query_arg([
                    'key'                       => $steamKey,
                    'steamid'                   => $steamID,
                    'include_appinfo'           => 1,
                    'include_played_free_games' => 1,
                    'include_free_games'        => 1,
                ], 'https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/'),
                ['timeout' => 15]
            );

            if (is_wp_error($response)) {
                return null;
            }

            $games = json_decode(wp_remote_retrieve_body($response), true)['response']['games'] ?? null;

            // 密钥无效或资料未公开时没有 games 字段
            if (!is_array($games)) {
                return null;
            }

            usort($games, function ($a, $b) {
                return ((int) ($b['rtime_last_played'] ?? 0)) - ((int) ($a['rtime_last_played'] ?? 0));
            });

            return $games;
        };

        if (!self::useCache()) {
            return $refresh();
        }

        return iro_swr_cache("steam-{$steamID}", $refresh);
    }

    // 批量取封面资源路径（相对 /store_item_assets/），服务不可用返回 null
    private static function fetchStoreItems(string $region, array $appids): ?array
    {
        $refresh = function () use ($region, $appids) {
            $response = wp_remote_get(
                add_query_arg(
                    'input_json',
                    wp_json_encode([
                        'ids'          => array_map(function ($appid) {
                            return ['appid' => $appid];
                        }, $appids),
                        'context'      => ['country_code' => $region, 'language' => 'english'],
                        'data_request' => ['include_assets' => true],
                    ]),
                    'https://api.steampowered.com/IStoreBrowseService/GetItems/v1/'
                ),
                ['timeout' => 15]
            );

            if (is_wp_error($response)) {
                return null;
            }

            return json_decode(wp_remote_retrieve_body($response), true)['response']['store_items'] ?? null;
        };

        $items = self::useCache()
            ? iro_swr_cache('steam-items-' . $region . '-' . md5(implode(',', $appids)), $refresh)
            : $refresh();

        if (!is_array($items)) {
            return null;
        }

        $covers = [];
        foreach ($items as $item) {
            // 该区不可见时 success 为 15 且没有 assets
            $header = $item['assets']['header'] ?? '';
            $format = $item['assets']['asset_url_format'] ?? '';

            if ($header === '' || $format === '') {
                continue;
            }

            $covers[(int) ($item['appid'] ?? 0)] = str_replace('${FILENAME}', $header, $format);
        }

        return $covers;
    }

    // appdetails 兜底：尚未收录进 IStoreBrowseService 的应用只能从这里取
    private static function fetchAppDetailsCover(int $appid): ?string
    {
        $refresh = function () use ($appid) {
            $response = wp_remote_get(
                add_query_arg([
                    'appids' => $appid,
                    'cc'     => self::REGION_PRIORITY[0],
                ], 'https://store.steampowered.com/api/appdetails'),
                ['timeout' => 15]
            );

            if (is_wp_error($response)) {
                return null;
            }

            return json_decode(wp_remote_retrieve_body($response), true)[$appid]['data']['header_image'] ?? null;
        };

        $header = self::useCache()
            ? iro_swr_cache('steam-appdetails-' . $appid, $refresh)
            : $refresh();

        if (!is_string($header) || $header === '') {
            return null;
        }

        // 转成相对 /store_item_assets/ 的路径，交由设置的 CDN 重新拼装
        $position = strpos($header, '/store_item_assets/');
        return $position === false ? null : substr($header, $position + strlen('/store_item_assets/'));
    }

    // 解析本页游戏的封面资源路径，锁区游戏靠换区拿到
    private static function resolveCovers(array $appids): array
    {
        $covers    = [];
        $reachable = false;

        foreach (self::REGION_PRIORITY as $region) {
            $missing = array_values(array_diff($appids, array_keys($covers)));
            if (empty($missing)) {
                break;
            }

            foreach (array_chunk($missing, self::STORE_ITEMS_LIMIT) as $chunk) {
                $assets = self::fetchStoreItems($region, $chunk);

                // 服务不可用，剩余游戏退回固定封面路径
                if ($assets === null) {
                    break 2;
                }

                $reachable = true;
                $covers += $assets;
            }
        }

        if (!$reachable) {
            return $covers;
        }

        foreach (array_diff($appids, array_keys($covers)) as $appid) {
            $header = self::fetchAppDetailsCover((int) $appid);
            if ($header !== null) {
                $covers[(int) $appid] = $header;
            }
        }

        return $covers;
    }

    public static function getSteamList(int $page = 1, int $perPage = 20): array
    {
        $steamID  = (string) iro_opt('steam_id');
        $steamKey = (string) iro_opt('steam_key');

        $page    = max(1, $page);
        $perPage = max(1, $perPage);

        if ($steamID === '' || $steamKey === '') {
            return self::errorResult(__('请先在主题设置中填写 Steam 账号 ID 与 API 密钥', 'sakurairo'), $perPage);
        }

        $games = self::fetchGames($steamID, $steamKey);

        if ($games === null) {
            return self::errorResult(__('请求 Steam 失败，请检查 API 密钥或账号资料是否公开', 'sakurairo'), $perPage);
        }

        if (empty($games)) {
            return self::emptyResult($page, $perPage, __('游戏库为空', 'sakurairo'));
        }

        $coverCDN = self::COVER_CDN[iro_opt('steam_covercdn', 'steamakamai')] ?? self::COVER_CDN['steamakamai'];
        $storeURL = self::STORE_URL[iro_opt('steam_store', 'steam')] ?? self::STORE_URL['steam'];

        $pageGames = array_slice($games, ($page - 1) * $perPage, $perPage);
        $covers    = self::resolveCovers(array_column($pageGames, 'appid'));

        $formatted = [];
        foreach ($pageGames as $game) {
            $appid    = (int) ($game['appid'] ?? 0);
            $playtime = (int) ($game['playtime_forever'] ?? 0);

            $formatted[] = [
                'appid'                 => $appid,
                'name'                  => (string) ($game['name'] ?? ''),
                'images'                => $coverCDN . '/store_item_assets/' . ($covers[$appid] ?? "steam/apps/{$appid}/header.jpg"),
                'url'                   => sprintf($storeURL, $appid),
                'playtime'              => self::formatPlaytime($playtime),
                'playtime_minutes'      => $playtime,
                'last_played'           => $playtime > 0 ? self::formatLastPlayed((int) ($game['rtime_last_played'] ?? 0)) : '',
                'last_played_timestamp' => (int) ($game['rtime_last_played'] ?? 0),
            ];
        }

        return [
            'success'    => true,
            'data'       => $formatted,
            'pagination' => self::buildPagination($page, count($games), $perPage),
        ];
    }

    private static function formatPlaytime(int $minutes): string
    {
        if ($minutes <= 0) {
            return __('尚未游玩', 'sakurairo');
        }

        if ($minutes < 60) {
            return sprintf(__('%s 分钟', 'sakurairo'), $minutes);
        }

        return sprintf(__('%s 小时', 'sakurairo'), rtrim(rtrim(number_format($minutes / 60, 1), '0'), '.'));
    }

    private static function formatLastPlayed(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return '';
        }

        return wp_date('Y-m-d H:i', $timestamp);
    }
}

