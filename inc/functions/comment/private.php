<?php
/*私密评论*/
add_action('wp_ajax_siren_private', 'siren_private');
function siren_private()
{
    check_ajax_referer('siren_private_comment', 'nonce');

    $comment_id = isset($_POST['p_id']) ? absint(wp_unslash($_POST['p_id'])) : 0;
    $action = isset($_POST['p_action']) ? sanitize_key(wp_unslash($_POST['p_action'])) : '';

    if (!$comment_id || $action !== 'set_private') {
        wp_die('', '', array('response' => 400));
    }

    if (!current_user_can('manage_options')) {
        wp_die('', '', array('response' => 403));
    }

    if (!get_comment($comment_id)) {
        wp_die('', '', array('response' => 404));
    }

    update_comment_meta($comment_id, '_private', 'true');
    echo esc_html__('Yes', 'sakurairo') . ' <i class="fa-solid fa-lock"></i>';
    wp_die();
}

/*
 * 私密评论
 * @bigfa
 */
function siren_private_message_hook($comment_content, $comment)
{
  $comment_ID = $comment->comment_ID;
  $parent_ID = $comment->comment_parent;
  $parent_email = get_comment_author_email($parent_ID);
  $is_private = get_comment_meta($comment_ID, '_private', true);
  $email = $comment->comment_author_email;
  $current_commenter = wp_get_current_commenter();
  if ($is_private) $comment_content = '#私密# ' . $comment_content;
  if ($current_commenter['comment_author_email'] == $email || $parent_email == $current_commenter['comment_author_email'] || current_user_can('delete_user')) return $comment_content;
  if ($is_private) return '<i class="fa-solid fa-lock"></i> ' . __("The comment is private", "sakurairo")/*该评论为私密评论*/;
  return $comment_content;
}
add_filter('get_comment_text', 'siren_private_message_hook', 10, 2);

function siren_mark_private_message($comment_id)
{
  if (isset($_POST['is-private'])) {
    update_comment_meta($comment_id, '_private', 'true');
  }
}
add_action('comment_post', 'siren_mark_private_message');