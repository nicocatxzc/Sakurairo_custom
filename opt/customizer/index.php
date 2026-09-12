<?php
/**
 * Customizer功能
 * 仅在Customizer预览框架中和Customizer编辑器载入时加载
 */
if (!defined('ABSPATH')) {
	exit;
}
add_action( 'customize_register', function () {
    require_once get_template_directory() . '/opt/customizer/init.php';
} );
if ( is_customize_preview() ) {
    require_once get_template_directory() . '/opt/customizer/init.php';
}
function update_customize_to_iro_options() { //从key映射表中重组并保存设置项至iro_options中
    $theme_mod_options = get_theme_mod( 'iro_options', [] );
    $mapping = get_theme_mod( 'iro_options_map', [] );
	$iro_options = get_option('iro_options');
    
    foreach ( $mapping as $setting_id => $map ) {
        $preview_value = get_theme_mod( $setting_id, null );
        if ( null !== $preview_value ) {
            $iro_key = isset( $map['iro_key'] ) ? $map['iro_key'] : $setting_id;
            $iro_subkey = isset( $map['iro_subkey'] ) ? $map['iro_subkey'] : '';
            if ( $iro_subkey ) {
                if ( ! isset( $theme_mod_options[ $iro_key ] ) || ! is_array( $theme_mod_options[ $iro_key ] ) ) {
                    $theme_mod_options[ $iro_key ] = [];
                }
                $theme_mod_options[ $iro_key ][ $iro_subkey ] = $preview_value;
            } else {
                $theme_mod_options[ $iro_key ] = $preview_value;
            }
            // 移除已保存的值，确保下次还能同步
            remove_theme_mod( $setting_id );
        }
    }
	$theme_mod_options = array_merge($iro_options,$theme_mod_options);
    update_option( 'iro_options', $theme_mod_options );
}
add_action( 'customize_save_after', 'update_customize_to_iro_options' );