<?php
// 载入翻译文件
load_theme_textdomain('sakurairo', get_template_directory() . '/languages');

add_action('init', 'set_user_locale');
function set_user_locale()
{
    if (is_user_logged_in()) {
        $user_locale = get_user_locale();
        switch_to_locale($user_locale);
    }
}

function i18n_templates_name($translated_name, $original_name)
{
    $lang = get_user_locale();

    $template_names = array(
        'Friendly Links Template' => array(
            'zh_CN' => '友情链接模板',
            'zh_TW' => '友情連結模板',
            'ja'    => 'フレンドリーリンクテンプレート',
        ),
        'Bangumi Template' => array(
            'zh_CN' => '追番模板',
            'zh_TW' => '追番模板',
            'ja'    => 'バンガミテンプレート',
        ),
        'Bilibili FavList Template' => array(
            'zh_CN' => 'Bilibili 收藏模板',
            'zh_TW' => 'Bilibili 收藏模板',
            'ja'    => 'Bilibili お気に入りテンプレート',
        ),
        'Bilibili FollowVideos Template' => array(
            'zh_CN' => 'Bilibili 追剧模板',
            'zh_TW' => 'Bilibili 追劇模板',
            'ja'    => 'Bilibili フォロービデオテンプレート',
        ),
        'Steam Library Template' => array(
            'zh_CN' => 'Steam 库模板',
            'zh_TW' => 'Steam 庫模板',
            'ja'    => 'Steamライブラリテンプレート',
        ),
        'Archive Template' => array(
            'zh_CN' => '归档模板',
            'zh_TW' => '歸檔模板',
            'ja'    => 'アーカイブページテンプレート',
        ),
    );

    if (isset($template_names[$original_name]) && isset($template_names[$original_name][$lang])) {
        return $template_names[$original_name][$lang];
    }
    // 英语/无翻译，返回gettext处理后的文本，防止原生翻译丢失
    return $translated_name;
}

add_filter('gettext', 'i18n_templates_name', 10, 2);
