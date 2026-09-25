<?php
function register_shuoshuo_post_type()
{
    $labels = array(
        'name'               => _x('Shuoshuo', 'post type general name', 'sakurairo'),
        'singular_name'      => _x('Shuoshuo', 'post type singular name', 'sakurairo'),
        'menu_name'          => _x('Shuoshuo', 'admin menu', 'sakurairo'),
        'name_admin_bar'     => _x('Shuoshuo', 'add new on admin bar', 'sakurairo'),
        'add_new'            => _x('Add New', 'shuoshuo', 'sakurairo'),
        'add_new_item'       => __('Add New Shuoshuo', 'sakurairo'),
        'new_item'           => __('New Shuoshuo', 'sakurairo'),
        'edit_item'          => __('Edit Shuoshuo', 'sakurairo'),
        'view_item'          => __('View Shuoshuo', 'sakurairo'),
        'all_items'          => __('All Shuoshuo', 'sakurairo'),
        'search_items'       => __('Search Shuoshuo', 'sakurairo'),
        'parent_item_colon'  => __('Parent Shuoshuo:', 'sakurairo'),
        'not_found'          => __('No shuoshuo found.', 'sakurairo'),
        'not_found_in_trash' => __('No shuoshuo found in Trash.', 'sakurairo')
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
        __('Emotion Meta Box', 'sakurairo'),
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
    echo '<label for="emotion">' . __('Emotion', 'sakurairo') . '</label>';
    echo '<input type="text" id="emotion" name="emotion" value="' . esc_attr($emotion_value) . '" />';
    echo '<br><br>';
    echo '<label for="emotion_color">' . __('Emotion Color', 'sakurairo') . '</label>';
    echo '<input type="text" id="emotion_color" name="emotion_color" value="' . esc_attr($emotion_color_value) . '" />';
    echo '<br><br>';
    echo '<p>' . __('For the Emotion, please fill in the Unicode value of the Fontawesome icon, and for the Emotion Color, please fill in the RGBA or hexadecimal color.', 'sakurairo') . '</p>';
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
        __('Custom Meta Box', 'sakurairo'),
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
    echo '<label for="title_style">' . __('Title Style', 'sakurairo') . '</label>';
    echo '<input type="text" id="title_style" name="title_style" value="' . esc_attr($title_style_value) . '" />';
    echo '<br><br>';
    echo '<label for="license">' . __('License', 'sakurairo') . '</label>';
    echo '<input type="text" id="license" name="license" value="' . esc_attr($license_value) . '" />';
    echo '<br><br>';
    echo '<p>' . __('For the Title Style, Please fill in the css style, part of the style need to add !important effective, and for the License, please go to Theme Options to learn how to set it up.', 'sakurairo') . '</p>';
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
