<?php

/**
 * 内建随机图 API
 *
 * 目录由设置项 iro_gallery_path 指定（相对站点根），图片按长宽比移动分拣进 pc/ 与 mb/。
 * 索引文件 <gallery>/imglist.json 同时记录图片清单与 gallery、pc、mb 三个目录的 mtime 快照：
 * gallery 的 mtime 变了说明有新图进来，重新分拣；pc/ 或 mb/ 的 mtime 变了说明有人直接动过
 * 分拣目录，只重建索引。
 */

/**
 * 目录（或文件）的改动时间
 *
 * 目录的 mtime 只在增删其下条目时变化，正是判定「是否需要重新分拣/重建索引」的依据。
 * 每次读取前清一遍 stat 缓存，移动文件之后拿到的才是新值。
 */
function iro_gallery_mtime(string $path): int
{
    clearstatcache(true, $path);

    return (int) @filemtime($path);
}

/**
 * 递归列出目录下的文件
 *
 * @param string   $dir     目标目录
 * @param string[] $exclude 仅在首层忽略的子项名，用于把分拣出来的 pc/ 与 mb/ 排除在源图之外
 * @return string[] 文件绝对路径
 */
function iro_gallery_scan(string $dir, array $exclude = []): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $files = [];

    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..' || in_array($entry, $exclude, true)) {
            continue;
        }

        $path = $dir . '/' . $entry;

        if (is_dir($path)) {
            $files = array_merge($files, iro_gallery_scan($path));
        } else {
            $files[] = $path;
        }
    }

    return $files;
}

/**
 * 只重建索引：按 pc/ 与 mb/ 的现状重新生成清单，不移动任何文件
 *
 * mtime 必须在所有移动动作之后采样，且写入的是已存在的文件——覆盖内容不会改动目录 mtime，
 * 否则记录下来的时间戳会被写索引这一步自己顶上。
 *
 * @return array 图片清单与 mtime 快照
 */
function iro_gallery_index(string $dir): array
{
    $index = ['pc' => [], 'mb' => []];

    foreach (['pc', 'mb'] as $sub) {
        $root = $dir . '/' . $sub;

        foreach (iro_gallery_scan($root) as $file) {
            $index[$sub][] = $sub . '/' . substr($file, strlen($root) + 1);
        }
    }

    $index['gallery_mtime'] = iro_gallery_mtime($dir);
    $index['pc_mtime']      = iro_gallery_mtime($dir . '/pc');
    $index['mb_mtime']      = iro_gallery_mtime($dir . '/mb');

    file_put_contents($dir . '/imglist.json', (string) wp_json_encode($index));

    return $index;
}

/**
 * 重新分拣：把 gallery 下的源图按长宽比移动进 pc/ 或 mb/，随后重建索引
 *
 * 目标路径沿用源文件的相对路径，避免不同子目录下的同名文件在分拣时互相覆盖。
 *
 * @return array 图片清单与 mtime 快照
 */
function iro_gallery_sort(string $dir): array
{
    $allowed = ['jpg', 'jpeg', 'bmp', 'png', 'webp', 'gif'];

    foreach (['pc', 'mb'] as $sub) {
        if (!is_dir($dir . '/' . $sub)) {
            mkdir($dir . '/' . $sub, 0755, true);
        }
    }

    foreach (iro_gallery_scan($dir, ['pc', 'mb']) as $file) {
        if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed, true)) {
            continue;
        }

        $image_size = @getimagesize($file);

        if ($image_size === false || !$image_size[1]) {
            continue;
        }

        // 竖图（含方图）给移动端，其余给 PC，判定沿用原有的 9/10 长宽比
        $sub  = $image_size[0] / $image_size[1] < 9 / 10 ? 'mb' : 'pc';
        $dest = $dir . '/' . $sub . '/' . substr($file, strlen($dir) + 1);

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        @rename($file, $dest);
    }

    return iro_gallery_index($dir);
}

/**
 * 随机取图并跳转
 *
 * 跳转用 WP_REST_Response 而不是 wp_safe_redirect()：除浏览器直接访问外，模板侧
 * （DEFAULT_FEATURE_IMAGE）还会用 rest_do_request() 内部调它取 Location 头，
 * 直接输出跳转头再 exit 会把整页变成一次跳转。
 *
 * @return WP_Error|WP_REST_Response
 */
function iro_gallery_get_image(WP_REST_Request $request)
{
    $rel = trim(str_replace('\\', '/', (string) iro_opt('iro_gallery_path', 'wp-content/iro-gallery')), '/');
    $rel = $rel === '' ? 'wp-content/iro-gallery' : $rel;
    $dir = rtrim(ABSPATH, '/\\') . '/' . $rel;

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return new WP_Error(
            'iro_gallery_no_dir',
            __('图片目录不存在且无法创建，请检查 iro_gallery_path 设置与目录权限。', 'sakurairo'),
            ['status' => 500]
        );
    }

    $file  = $dir . '/imglist.json';
    $index = json_decode((string) @file_get_contents($file), true);
    $index = is_array($index) ? $index : [];

    // 索引文件就建在 gallery 目录里，首次创建它会改动该目录的 mtime；
    // 先 touch 掉，紧接着的采样才不会被「建索引文件」这一步顶掉。
    if (!file_exists($file)) {
        touch($file);
    }

    if (empty($index) || ($index['gallery_mtime'] ?? null) !== iro_gallery_mtime($dir)) {
        $index = iro_gallery_sort($dir);
    } elseif (
        ($index['pc_mtime'] ?? null) !== iro_gallery_mtime($dir . '/pc')
        || ($index['mb_mtime'] ?? null) !== iro_gallery_mtime($dir . '/mb')
    ) {
        $index = iro_gallery_index($dir);
    }

    // 指定分类为空时退回全部，避免单一分类没图就让接口整体不可用
    $images = $index[sanitize_key((string) $request->get_param('size'))] ?? [];

    if (empty($images)) {
        $images = array_merge($index['pc'] ?? [], $index['mb'] ?? []);
    }

    if (empty($images)) {
        return new WP_Error(
            'iro_gallery_empty',
            __('图片目录中没有可用的图片。', 'sakurairo'),
            ['status' => 500]
        );
    }

    $image = $images[array_rand($images)];

    return new WP_REST_Response(
        null,
        302,
        ['Location' => home_url('/' . $rel . '/' . implode('/', array_map('rawurlencode', explode('/', $image))))]
    );
}
