<?php
/*GET参数操作*/
function iro_action_operator()
{
    if (!isset($_GET['iro_act']) || empty($_GET['iro_act'])) {
        return;
    }

    if (!is_admin() || !current_user_can('manage_options')) {
        echo __("拒绝访问。", "sakurairo");
        return;
    }

    $direct_info = sanitize_key($_GET['iro_act']);

    switch ($direct_info) {
        case 'bangumi':
            $direct_url = 'https://api.bgm.tv/v0/users/' . (iro_opt('bangumi_id') ?: '944883') . '/collections';
            header("Location: $direct_url", true, 302);
            break;

        case 'mal':
            switch (iro_opt('my_anime_list_sort')) {
                case 1: // Status and Last Updated
                    $sort = 'order=16&order2=5&status=7';
                    break;
                case 2: // Last Updated
                    $sort = 'order=5&status=7';
                    break;
                case 3: // Status
                    $sort = 'order=16&status=7';
                    break;
            }
            $direct_url = 'https://myanimelist.net/animelist/' . (iro_opt('my_anime_list_username') ?: 'username') . '/load.json?' . $sort;
            header("Location: $direct_url", true, 302);
            break;

        case 'steam_library':
            $direct_url = 'https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=' . iro_opt('steam_key') . '&steamid=' . iro_opt('steam_id') . '&include_appinfo=1&include_played_free_games=1&include_free_games=1';
            header("Location: $direct_url", true, 302);
            break;

        case 'playlist':
            $direct_url = rest_url('sakura/v1/meting/aplayer') . '?_wpnonce=' . wp_create_nonce('wp_rest') . '&server=' . (iro_opt('aplayer_server') ?: 'netease') . '&type=playlist&id=' . (iro_opt('aplayer_playlistid') ?: '5380675133');
            header("Location: $direct_url", true, 302);
            break;
        case 'del_exist_theme':
            $current_theme_folder = basename(get_template_directory());
            if ($current_theme_folder != 'Sakurairo') {
                if (!function_exists('WP_Filesystem')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }
                WP_Filesystem();
                global $wp_filesystem;
                $wp_filesystem->delete(get_theme_root() . '/Sakurairo', true);
                wp_redirect(admin_url(), 302); //重载theme_folder_check_on_admin_init流程
            } else {
                wp_redirect(admin_url(), 302);
                return;
            }
    }
}
iro_action_operator();
