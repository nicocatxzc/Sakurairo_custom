<?php

/**
 * 当前播放器模式，非法值回落到关闭
 */
function footer_player_mode(): string
{
    $mode = (string) iro_opt('footer_player_mode', 'off');
    if (!in_array($mode, ['off', 'netease', 'custom', 'static'], true)) {
        return 'off';
    }
    return $mode;
}

/**
 * 构造播放器 REST 地址
 */
function footer_player_rest_url(string $route): string
{
    return rest_url('sakura/v1/player/' . ltrim($route, '/'));
}

/**
 * 注入前端的播放器配置，只暴露必要字段，歌单本体走接口
 */
function footer_player_frontend_config(): array
{
    $config = [
        'enabled' => footer_player_mode() !== 'off',
        'playlist' => '',
        'order' => 'list',
        'preload' => 'metadata',
        'volume' => 0.5,
        'theme' => '#2980b9',
    ];

    if (!$config['enabled']) {
        return $config;
    }

    $order = (string) iro_opt('footer_player_order', 'list');
    $preload = (string) iro_opt('footer_player_preload', 'metadata');
    $volume = iro_opt('footer_player_volume', 0.5);

    $config['playlist'] = esc_url_raw(footer_player_rest_url('playlist'));
    $config['order'] = $order === 'random' ? 'random' : 'list';
    $config['preload'] = in_array($preload, ['none', 'metadata', 'auto'], true) ? $preload : 'metadata';
    $config['volume'] = is_numeric($volume) ? max(0, min(1, (float) $volume)) : 0.5;

    return $config;
}

/**
 * 按当前模式构建歌单
 */
function footer_player_playlist(): array
{
    switch (footer_player_mode()) {
        case 'netease':
            $playlist_id = trim((string) iro_opt('footer_player_netease_playlist'));
            if ($playlist_id === '') {
                return [];
            }
            $cache_key = 'iro_player_netease_' . md5($playlist_id . '|' . (string) iro_opt('footer_player_netease_cookie'));
            return footer_player_cache_playlist($cache_key, function () use ($playlist_id) {
                return footer_player_build_netease($playlist_id);
            });

        case 'custom':
            $api = trim((string) iro_opt('footer_player_custom_api'));
            if ($api === '') {
                return [];
            }
            $cache_key = 'iro_player_custom_' . md5($api);
            return footer_player_cache_playlist($cache_key, function () use ($api) {
                return footer_player_build_custom($api);
            });

        case 'static':
            return footer_player_build_static();
    }

    return [];
}

/**
 * 带 SWR 缓存的歌单构建，构建失败返回 null 而不写缓存
 */
function footer_player_cache_playlist(string $key, callable $callback): array
{
    $playlist = iro_swr_cache($key, function () use ($callback) {
        $data = $callback();
        return is_array($data) ? $data : null;
    });

    return is_array($playlist) ? $playlist : [];
}

/**
 * 网易云歌单，使用内置 Meting 解析
 */
function footer_player_build_netease(string $playlist_id): ?array
{
    $meting_file = get_template_directory() . '/inc/libs/Meting.php';
    if (!file_exists($meting_file)) {
        return null;
    }
    require_once $meting_file;
    if (!class_exists('\Sakura\API\Meting')) {
        return null;
    }

    try {
        $meting = new \Sakura\API\Meting('netease');
        $cookie = trim((string) iro_opt('footer_player_netease_cookie'));
        if ($cookie !== '') {
            $meting->cookie($cookie);
        }
        $data = $meting->format(true)->playlist($playlist_id);
    } catch (\Throwable $e) {
        return null;
    }

    $data = json_decode((string) $data, true);
    if (!is_array($data)) {
        return null;
    }

    $playlist = [];
    foreach ($data as $value) {
        if (!is_array($value)) {
            continue;
        }
        $artists = isset($value['artist']) ? (array) $value['artist'] : [];
        $playlist[] = [
            'name' => isset($value['name']) ? (string) $value['name'] : '',
            'artist' => implode(' / ', array_filter(array_map('strval', $artists))),
            'url' => footer_player_meting_url('url', isset($value['url_id']) ? (string) $value['url_id'] : ''),
            'cover' => footer_player_meting_url('pic', isset($value['pic_id']) ? (string) $value['pic_id'] : ''),
            'lrc' => footer_player_meting_url('lyric', isset($value['lyric_id']) ? (string) $value['lyric_id'] : ''),
            'type' => 'auto',
        ];
    }

    return $playlist;
}

/**
 * 网易云资源地址
 */
function footer_player_meting_url(string $type, string $id): string
{
    if ($id === '') {
        return '';
    }

    return add_query_arg(
        [
            'type' => $type,
            'id' => $id,
            'sig' => wp_hash($type . '#:' . $id, 'iro_player_meting'),
        ],
        footer_player_rest_url('meting')
    );
}

/**
 * 自定义 API 歌单，服务端抓取跨域歌单并本地缓存
 */
function footer_player_build_custom(string $api): ?array
{
    if (!wp_http_validate_url($api)) {
        return null;
    }

    $response = wp_remote_get($api, ['timeout' => 15, 'redirection' => 3]);
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }

    $data = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($data)) {
        return null;
    }

    if (isset($data['data']) && is_array($data['data'])) {
        $data = $data['data'];
    } elseif (isset($data['songs']) && is_array($data['songs'])) {
        $data = $data['songs'];
    } elseif (isset($data['result']['songs']) && is_array($data['result']['songs'])) {
        $data = $data['result']['songs'];
    } elseif (isset($data['result']) && is_array($data['result'])) {
        $data = $data['result'];
    }

    $playlist = [];
    foreach ($data as $entry) {
        $track = footer_player_format_custom_item($entry);
        if ($track !== null) {
            $playlist[] = $track;
        }
    }

    return $playlist;
}

/**
 * 归一化自定义 API 的单条歌曲
 */
function footer_player_format_custom_item($entry): ?array
{
    if (!is_array($entry)) {
        return null;
    }

    $url = footer_player_pick_string($entry, ['url', 'src', 'song', 'audio']);
    if ($url === '') {
        return null;
    }

    $cover = footer_player_pick_string($entry, ['cover', 'pic', 'picture', 'image', 'img']);
    $lrc = footer_player_pick_string($entry, ['lrc', 'lyric', 'lyrics', 'lrcurl']);
    $type = footer_player_pick_string($entry, ['type', 'ext']);
    if ($type === '') {
        $type = strtolower(pathinfo((string) wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }

    return [
        'name' => footer_player_pick_string($entry, ['name', 'title']) ?: __('未知歌曲', 'sakurairo'),
        'artist' => footer_player_pick_artist($entry),
        'url' => $url,
        'cover' => $cover,
        'lrc' => $lrc,
        'type' => $type,
    ];
}

/**
 * 从数组里取第一个非空标量字段
 */
function footer_player_pick_string(array $entry, array $keys): string
{
    foreach ($keys as $key) {
        if (!empty($entry[$key]) && is_scalar($entry[$key])) {
            return trim((string) $entry[$key]);
        }
    }
    return '';
}

/**
 * 兼容字符串与数组形式的艺术家
 */
function footer_player_pick_artist(array $entry): string
{
    $artist = footer_player_pick_string($entry, ['artist', 'author', 'singer']);
    if ($artist !== '') {
        return $artist;
    }

    if (!empty($entry['artists']) && is_array($entry['artists'])) {
        $names = [];
        foreach ($entry['artists'] as $item) {
            if (is_scalar($item)) {
                $names[] = (string) $item;
            } elseif (is_array($item) && isset($item['name'])) {
                $names[] = (string) $item['name'];
            }
        }
        return implode(' / ', array_filter($names));
    }

    return '';
}

/**
 * 静态歌单（设置项 + 自动扫描结果）
 */
function footer_player_build_static(): array
{
    $items = iro_opt('footer_player_static');
    if (!is_array($items)) {
        $items = [];
    }

    if (iro_opt('footer_player_auto_scan', false)) {
        $items = array_merge($items, footer_player_scan_uploads_cached());
    }

    $playlist = [];
    foreach ($items as $item) {
        $track = footer_player_format_static_item($item);
        if ($track !== null) {
            $playlist[] = $track;
        }
    }

    return $playlist;
}

/**
 * 归一化静态歌单的单条歌曲
 */
function footer_player_format_static_item($item): ?array
{
    if (!is_array($item)) {
        return null;
    }

    $url = isset($item['url']) ? trim((string) $item['url']) : '';
    if ($url === '') {
        return null;
    }

    $name = isset($item['name']) ? trim((string) $item['name']) : '';
    if ($name === '') {
        $name = basename((string) wp_parse_url($url, PHP_URL_PATH));
    }
    if ($name === '') {
        $name = __('未知歌曲', 'sakurairo');
    }

    $cover = isset($item['cover']) ? trim((string) $item['cover']) : '';
    $lrc = isset($item['lrc']) ? trim((string) $item['lrc']) : '';
    $type = isset($item['type']) ? trim((string) $item['type']) : '';
    if ($type === '') {
        $type = strtolower(pathinfo((string) wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }

    return [
        'name' => $name,
        'artist' => isset($item['artist']) ? trim((string) $item['artist']) : '',
        'url' => $url,
        'cover' => $cover,
        'lrc' => $lrc,
        'type' => $type,
    ];
}

/**
 * 扫描上传目录，只收录带封面的音乐
 */
function footer_player_scan_uploads(): array
{
    $uploads = wp_get_upload_dir();
    $basedir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';
    $baseurl = isset($uploads['baseurl']) ? (string) $uploads['baseurl'] : '';
    if ($basedir === '' || !is_dir($basedir)) {
        return [];
    }

    $audio_extensions = ['mp3', 'flac', 'm4a', 'aac', 'ogg', 'oga', 'wav', 'webm'];
    $image_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    try {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basedir, \FilesystemIterator::SKIP_DOTS)
        );
        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
    } catch (\Throwable $e) {
        return [];
    }

    sort($files);

    $tracks = [];
    foreach ($files as $absolute) {
        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if (!in_array($extension, $audio_extensions, true)) {
            continue;
        }

        $cover = footer_player_find_cover($absolute, $extension, $image_extensions);
        if ($cover === null) {
            continue;
        }

        $meta = footer_player_read_id3($absolute);
        $tracks[] = [
            'name' => $meta['name'] !== '' ? $meta['name'] : pathinfo($absolute, PATHINFO_FILENAME),
            'artist' => $meta['artist'],
            'url' => footer_player_absolute_to_url($absolute, $basedir, $baseurl),
            'cover' => footer_player_absolute_to_url($cover, $basedir, $baseurl),
            'lrc' => '',
            'type' => $extension,
        ];
    }

    return $tracks;
}

/**
 * 带缓存的扫描
 */
function footer_player_scan_uploads_cached(): array
{
    $scanned = iro_swr_cache('iro_player_uploads_scan', function () {
        return footer_player_scan_uploads();
    });

    return is_array($scanned) ? $scanned : [];
}

/**
 * 绝对路径转上传目录 URL
 */
function footer_player_absolute_to_url(string $absolute, string $basedir, string $baseurl): string
{
    $relative = ltrim(str_replace($basedir, '', $absolute), '/\\');

    // 历史数据里可能混入 JSON 转义的中文（\uXXXX），先还原，避免反斜杠被误当成目录分隔符
    if (strpos($relative, '\\u') !== false) {
        $relative = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
            $decoded = json_decode('"' . $matches[0] . '"');
            return is_string($decoded) ? $decoded : $matches[0];
        }, $relative);
    }

    // 仅 Windows 下把路径分隔符换成 URL 分隔符；Linux 下保持原样，不误伤文件名
    return rtrim($baseurl, '/') . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
}

/**
 * 查找同名封面，优先 WordPress 分离出的 <name>-<ext>-image.*
 */
function footer_player_find_cover(string $absolute, string $audio_extension, array $image_extensions): ?string
{
    $directory = dirname($absolute);
    $base = pathinfo($absolute, PATHINFO_FILENAME);

    foreach ($image_extensions as $image_extension) {
        $candidate = $directory . '/' . $base . '-' . $audio_extension . '-image.' . $image_extension;
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    foreach ($image_extensions as $image_extension) {
        $candidate = $directory . '/' . $base . '.' . $image_extension;
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    $matches = glob($directory . '/' . $base . '-*');
    if (!$matches) {
        return null;
    }

    $covers = [];
    foreach ($matches as $match) {
        if (is_file($match) && in_array(strtolower(pathinfo($match, PATHINFO_EXTENSION)), $image_extensions, true)) {
            $covers[] = $match;
        }
    }
    sort($covers);

    return $covers[0] ?? null;
}

/**
 * 用内核 getID3 读取标题与艺术家
 */
function footer_player_read_id3(string $absolute): array
{
    $result = ['name' => '', 'artist' => ''];
    $getid3_file = ABSPATH . 'wp-includes/ID3/getid3.php';
    if (!file_exists($getid3_file)) {
        return $result;
    }

    require_once $getid3_file;
    if (!class_exists('getID3')) {
        return $result;
    }

    try {
        $analyzer = new \getID3();
        $info = $analyzer->analyze($absolute);
    } catch (\Throwable $e) {
        return $result;
    }

    $tags = isset($info['tags']) && is_array($info['tags']) ? $info['tags'] : [];
    $result['name'] = footer_player_tag_value($tags, ['title']);
    $result['artist'] = footer_player_tag_value($tags, ['artist', 'band', 'albumartist']);

    return $result;
}

/**
 * 从 ID3 标签组里取字段，兼容数组值
 */
function footer_player_tag_value(array $tags, array $names): string
{
    foreach ($tags as $tag) {
        if (!is_array($tag)) {
            continue;
        }
        foreach ($names as $name) {
            if (empty($tag[$name])) {
                continue;
            }
            $value = $tag[$name];
            if (is_array($value)) {
                $value = reset($value);
            }
            $value = is_scalar($value) ? trim((string) $value) : '';
            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
}

/**
 * 网易云音频地址清洗，避免 HTTPS 站出现混合内容
 */
function footer_player_clean_audio_url(string $url): string
{
    $url = str_replace('://m7c.', '://m7.', $url);
    $url = str_replace('://m8c.', '://m8.', $url);
    $url = str_replace('http://m8.', 'https://m9.', $url);
    $url = str_replace('http://m7.', 'https://m9.', $url);
    $url = str_replace('http://m10.', 'https://m10.', $url);

    return preg_replace('#^http://#i', 'https://', $url);
}

/**
 * 合并原文与翻译歌词
 */
function footer_player_merge_lrc(string $lyric, string $tlyric): string
{
    $lyric = footer_player_lrc_trim($lyric);
    $tlyric = footer_player_lrc_trim($tlyric);
    $len1 = count($lyric);
    $len2 = count($tlyric);
    $result = '';

    for ($i = 0, $j = 0; $i < $len1 && $j < $len2; $i++) {
        while ($lyric[$i][0] > $tlyric[$j][0] && $j + 1 < $len2) {
            $j++;
        }
        if ($lyric[$i][0] == $tlyric[$j][0]) {
            $tlyric[$j][2] = str_replace('/', '', $tlyric[$j][2]);
            if (!empty($tlyric[$j][2])) {
                $lyric[$i][2] .= " ({$tlyric[$j][2]})";
            }
            $j++;
        }
    }

    for ($i = 0; $i < $len1; $i++) {
        $time = $lyric[$i][0];
        $result .= sprintf("[%02d:%02d.%03d]%s\n", $time / 60000, $time % 60000 / 1000, $time % 1000, $lyric[$i][2]);
    }

    return $result;
}

/**
 * 解析 LRC 时间轴
 */
function footer_player_lrc_trim(string $lyrics): array
{
    $lines = explode("\n", $lyrics);
    $data = [];
    foreach ($lines as $key => $line) {
        preg_match('/\[(\d{2}):(\d{2}[\.:]?\d*)]/', $line, $matches);
        if (empty($matches)) {
            continue;
        }
        $text = preg_replace('/\[(\d{2}):(\d{2}[\.:]?\d*)]/', '', $line);
        $time = intval($matches[1]) * 60000 + intval(floatval($matches[2]) * 1000);
        $text = preg_replace('/\s\s+/', ' ', $text);
        $data[] = [$time, $key, trim($text)];
    }
    sort($data);

    return $data;
}
