<?php
require get_template_directory() . '/opt/option-framework.php';

if (!function_exists('iro_opt')) {
    $GLOBALS['iro_options'] = get_option('iro_options');
    function iro_opt($option = '', $default = null)
    {
        if (is_customize_preview()) {
            $theme_mod = get_theme_mod('iro_options', []);
            if (isset($theme_mod[$option])) {
                return $theme_mod[$option]; //预览模式优先使用预览值
            } else {
                return $GLOBALS['iro_options'][$option] ?? $default;
            }
        } else {
            return $GLOBALS['iro_options'][$option] ?? $default;
        }
    }
}
if (!function_exists('iro_opt_update')) {
    function iro_opt_update($option = '', $value = null)
    {
        $options = get_option('iro_options'); // 当数据库没有指定项时，WordPress会返回false
        if ($options) {
            $options[$option] = $value;
        } else {
            $options = array($option => $value);
        }
        update_option('iro_options', $options);
    }
}
