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