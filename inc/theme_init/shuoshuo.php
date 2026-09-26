<?php
function register_shuoshuo_post_type()
{
    $labels = array(
        'name'               => _x('说说', 'post type general name', 'sakurairo'),
        'singular_name'      => _x('说说', 'post type singular name', 'sakurairo'),
        'menu_name'          => _x('说说', 'admin menu', 'sakurairo'),
        'name_admin_bar'     => _x('说说', 'add new on admin bar', 'sakurairo'),
        'add_new'            => _x('新建', 'shuoshuo', 'sakurairo'),
        'add_new_item'       => __('新建说说', 'sakurairo'),
        'new_item'           => __('新说说', 'sakurairo'),
        'edit_item'          => __('编辑说说', 'sakurairo'),
        'view_item'          => __('查看说说', 'sakurairo'),
        'all_items'          => __('所有说说', 'sakurairo'),
        'search_items'       => __('搜索说说', 'sakurairo'),
        'parent_item_colon'  => __('父级说说：', 'sakurairo'),
        'not_found'          => __('未找到说说。', 'sakurairo'),
        'not_found_in_trash' => __('回收站中未找到说说。', 'sakurairo')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'shuoshuo'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'custom-fields', 'comments'),
        'taxonomies'         => array('category')
    );

    register_post_type('shuoshuo', $args);
}
add_action('init', 'register_shuoshuo_post_type');

function register_emotion_meta_boxes()
{
    register_meta('post', 'emotion', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));
    register_meta('post', 'emotion_color', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));
}
add_action('init', 'register_emotion_meta_boxes');

function add_emotion_meta_box()
{
    add_meta_box(
        'emotion_meta_box_id',
        __('情绪选项', 'sakurairo'),
        'render_emotion_meta_box',
        'shuoshuo', // 仅在shuoshuo内容类型中显示
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_emotion_meta_box');

function render_emotion_meta_box($post)
{
    $emotion_value = get_post_meta($post->ID, 'emotion', true);
    $emotion_color_value = get_post_meta($post->ID, 'emotion_color', true);
    wp_nonce_field('emotion_meta_box_nonce', 'emotion_meta_box_nonce_field');
    echo '<label for="emotion">' . __('情绪图标', 'sakurairo') . '</label>';
    echo '<input type="text" id="emotion" name="emotion" value="' . esc_attr($emotion_value) . '" />';
    echo '<br><br>';
    echo '<label for="emotion_color">' . __('情绪颜色', 'sakurairo') . '</label>';
    echo '<input type="text" id="emotion_color" name="emotion_color" value="' . esc_attr($emotion_color_value) . '" />';
    echo '<br><br>';
    echo '<p>' . __('情绪图标请填写 FontAwesome 图标的 Unicode 值，情绪颜色请填写 RGBA 或十六进制颜色值。', 'sakurairo') . '</p>';
}

function save_emotion_meta_box($post_id)
{
    if (!isset($_POST['emotion_meta_box_nonce_field']) || !wp_verify_nonce($_POST['emotion_meta_box_nonce_field'], 'emotion_meta_box_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['emotion'])) {
        update_post_meta($post_id, 'emotion', sanitize_text_field($_POST['emotion']));
    }
    if (isset($_POST['emotion_color'])) {
        update_post_meta($post_id, 'emotion_color', sanitize_text_field($_POST['emotion_color']));
    }
}
add_action('save_post', 'save_emotion_meta_box');

function register_custom_meta_boxes()
{
    register_meta('post', 'title_style', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));
    register_meta('post', 'license', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));
}
add_action('init', 'register_custom_meta_boxes');

function add_custom_meta_box()
{
    add_meta_box(
        'custom_meta_box_id',
        __('自定义选项', 'sakurairo'),
        'render_custom_meta_box',
        'post', // 仅在post内容类型中显示
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_meta_box');

function render_custom_meta_box($post)
{
    $title_style_value = get_post_meta($post->ID, 'title_style', true);
    $license_value = get_post_meta($post->ID, 'license', true);
    wp_nonce_field('custom_meta_box_nonce', 'custom_meta_box_nonce_field');
    echo '<label for="title_style">' . __('标题样式', 'sakurairo') . '</label>';
    echo '<input type="text" id="title_style" name="title_style" value="' . esc_attr($title_style_value) . '" />';
    echo '<br><br>';
    echo '<label for="license">' . __('许可协议', 'sakurairo') . '</label>';
    echo '<input type="text" id="license" name="license" value="' . esc_attr($license_value) . '" />';
    echo '<br><br>';
    echo '<p>' . __('标题样式请填写 CSS 样式，部分样式需要加 !important 才能生效；许可协议请前往主题设置查看设置方法。', 'sakurairo') . '</p>';
}

function save_custom_meta_box($post_id)
{
    if (!isset($_POST['custom_meta_box_nonce_field']) || !wp_verify_nonce($_POST['custom_meta_box_nonce_field'], 'custom_meta_box_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['title_style'])) {
        update_post_meta($post_id, 'title_style', sanitize_text_field($_POST['title_style']));
    }
    if (isset($_POST['license'])) {
        update_post_meta($post_id, 'license', sanitize_text_field($_POST['license']));
    }
}
add_action('save_post', 'save_custom_meta_box');
