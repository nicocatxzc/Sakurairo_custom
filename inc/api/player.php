<?php
/**
 * 页脚播放器 REST 接口
 */

/**
 * 歌单接口
 */
function footer_player_rest_playlist(WP_REST_Request $request)
{
    return rest_ensure_response(footer_player_playlist());
}

/**
 * 网易云单曲资源接口，url/pic 302 跳转，lyric 直接输出文本
 */
function footer_player_rest_meting(WP_REST_Request $request)
{
    $type = (string) $request->get_param('type');
    $id = (string) $request->get_param('id');
    $sig = (string) $request->get_param('sig');

    if (!in_array($type, ['url', 'pic', 'lyric'], true) || $id === '' || !hash_equals(wp_hash($type . '#:' . $id, 'iro_player_meting'), $sig)) {
        return new WP_Error('iro_player_meting_invalid', __('无效的播放器请求。', 'sakurairo'), ['status' => 403]);
    }

    $meting_file = get_template_directory() . '/inc/libs/Meting.php';

    try {
        $meting = new \Sakura\API\Meting('netease');
        $cookie = trim((string) iro_opt('footer_player_netease_cookie'));
        if ($cookie !== '') {
            $meting->cookie($cookie);
        }

        if ($type === 'lyric') {
            $data = json_decode((string) $meting->format(true)->lyric($id), true);
            $lyric = footer_player_merge_lrc(
                isset($data['lyric']) ? (string) $data['lyric'] : '',
                isset($data['tlyric']) ? (string) $data['tlyric'] : ''
            );
            if ($lyric === '') {
                $lyric = '[00:00.000]此歌曲暂无歌词，请您欣赏';
            }
            header('Content-Type: text/plain; charset=utf-8');
            echo $lyric;
            exit;
        }

        $method = $type === 'pic' ? 'pic' : 'url';
        $data = json_decode((string) $meting->format(true)->$method($id), true);
        $url = isset($data['url']) ? (string) $data['url'] : '';
        if ($url === '') {
            return new WP_Error('iro_player_meting_empty', __('解析失败', 'sakurairo'), ['status' => 404]);
        }
        if ($type === 'url') {
            $url = footer_player_clean_audio_url($url);
        }
        wp_redirect($url, 302);
        exit;
    } catch (\Throwable $e) {
        return new WP_Error('iro_player_meting_error', __('解析失败', 'sakurairo'), ['status' => 502]);
    }
}
