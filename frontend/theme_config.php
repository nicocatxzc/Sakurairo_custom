<?php
function iro_front_theme_config()
{
    $iro_theme_config = [
        'language' => esc_js(str_replace('-', '_', get_locale())),
        'api' => esc_url_raw(rest_url()),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'iro_api' => esc_url_raw(rest_url('sakura/v1')),

        'typed_config' => iro_opt("cover_typedjs_config"),
        'particle' => [
            'select' => iro_opt("frontend_particle"),
            'builtin' => iro_opt("frontend_particle_builtin"),
            'config' => iro_opt("frontend_particle") == 'custom' ? iro_opt("particle_config") : [] // 省流
        ]
    ];
?>

    <?php
    $iro_page_config = [
        "post_id" => get_the_ID(),
        "is_home" => is_home(),
    ];
    ?>

    <?php
    $user = wp_get_current_user();
    $iro_user_config = [
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'roles' => $user->roles,
        'description' => get_the_author_meta('description', $user->ID),
        'slug' => $user->user_nicename,
        'avatar' => [
            'url_96' => get_avatar_url($user->ID, ['size' => 96]),
            'url_150' => get_avatar_url($user->ID, ['size' => 150]),
            'url_300' => get_avatar_url($user->ID, ['size' => 300]),
        ]
    ];
    ?>
    <script id="iro_theme_config" type="application/json">
        <?= json_encode($iro_theme_config, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script id="iro_page_config" type="application/json">
        <?= json_encode($iro_page_config, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script id="iro_user_config" type="application/json">
        <?= json_encode($iro_user_config, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>
    </script>
<?php
}
add_action('wp_enqueue_scripts', 'iro_front_theme_config',1);
