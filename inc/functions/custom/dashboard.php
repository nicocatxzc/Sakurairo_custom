<?php
//dashboard scheme
function dash_scheme($key, $name, $col1, $col2, $col3, $base, $focus, $current, $rules = "")
{
    $hash = 'rules=' . urlencode($rules);
    if ($col1) {
        $hash .= '&color_1=' . str_replace("#", "", $col1);
    }
    if ($col2) {
        $hash .= '&color_2=' . str_replace("#", "", $col2);
    }
    if ($col3) {
        $hash .= '&color_3=' . str_replace("#", "", $col3);
    }

    wp_admin_css_color(
        $key,
        $name,
        get_template_directory_uri() . "/inc/dash-scheme.php?" . $hash,
        array($col1, $col2, $col3),
        array('base' => $base, 'focus' => $focus, 'current' => $current)
    );
}

//Sakurairo
dash_scheme(
    $key = "sakurairo",
    $name = "Sakurairo🌸",
    $col1 = iro_opt('admin_second_class_color'),
    $col2 = iro_opt('admin_first_class_color'),
    $col3 = iro_opt('admin_emphasize_color'),
    $base = "#FFF",
    $focus = "#FFF",
    $current = "#FFF",
    $rules = 'body{background-image:url(' . iro_opt('admin_background') . ');background-attachment:fixed;background-size:cover;}'
);

// WordPress Custom style @ Admin
function custom_admin_open_sans_style()
{
    require get_template_directory() . '/inc/option-scheme.php';
}
add_action('admin_head', 'custom_admin_open_sans_style');

// WordPress Custom Font @ Admin
function custom_admin_open_sans_font()
{
    echo '<link href="https://' . iro_opt('gfonts_api', 'fonts.googleapis.com') . '/css?family=Noto+Serif+SC&display=swap" rel="stylesheet">' . PHP_EOL;
    echo '<style>body, #wpadminbar *:not([class="ab-icon"]), .wp-core-ui, .media-menu, .media-frame *, .media-modal *{font-family: "Noto Serif SC", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;}</style>' . PHP_EOL;
}
add_action('admin_head', 'custom_admin_open_sans_font');

// WordPress Custom Font @ Admin Frontend Toolbar
function custom_admin_open_sans_font_frontend_toolbar()
{
    if (current_user_can('manage_options') && is_admin_bar_showing()) {
        echo '<link href="https://' . iro_opt('gfonts_api', 'fonts.googleapis.com') . '/css?family=Noto+Serif+SC&display=swap" rel="stylesheet">' . PHP_EOL;
        echo '<style>#wpadminbar *:not([class="ab-icon"]){font-family: "Noto Serif SC", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;}</style>' . PHP_EOL;
    }
}
add_action('wp_head', 'custom_admin_open_sans_font_frontend_toolbar');

// WordPress Custom Font @ Admin Login
function custom_admin_open_sans_font_login_page()
{
    if (stripos($_SERVER["SCRIPT_NAME"], strrchr(wp_login_url(), '/')) !== false) {
        echo '<link href="https://' . iro_opt('gfonts_api', 'fonts.googleapis.com') . '/css?family=Noto+Serif+SC&display=swap" rel="stylesheet">' . PHP_EOL;
        echo '<style>body{font-family: "Noto Serif SC", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;}</style>' . PHP_EOL;
    }
}
add_action('login_head', 'custom_admin_open_sans_font_login_page');