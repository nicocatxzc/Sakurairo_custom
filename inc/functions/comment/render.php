<?php
if (!function_exists('akina_comment_format')) {
    function akina_comment_format($comment, $args, $depth)
    {
        $GLOBALS['comment'] = $comment;
?>
        <li <?php comment_class(); ?> id="comment-<?php echo esc_attr(comment_ID()); ?>">
            <div class="contents">
                <div class="comment-arrow">
                    <div class="main shadow">
                        <div class="profile">
                            <a href="<?php comment_author_url(); ?>" target="_blank" rel="nofollow"><?php echo str_replace('src=', 'src="' . iro_opt('load_in_svg') . '" onerror="imgError(this,1)" data-src=', get_avatar($comment->comment_author_email, 80, '', get_comment_author(), array('class' => array('lazyload')))); ?></a>
                        </div>
                        <div class="commentinfo">
                            <section class="commeta">
                                <div class="left">
                                    <h4 class="author">
                                        <a href="<?php comment_author_url(); ?>" target="_blank" rel="nofollow"><?php echo get_avatar($comment->comment_author_email, 24, '', get_comment_author()); ?>
                                            <span class="bb-comment isauthor" title="<?php _e('Author', 'sakurairo'); ?>"><?php _e('Blogger', 'sakurairo'); /*博主*/ ?></span>
                                            <?php comment_author(); ?><?php echo get_author_class($comment->comment_author_email, $comment->user_id); ?>
                                        </a>
                                    </h4>
                                </div>
                                <?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))); ?>
                                <div class="right">
                                    <div class="info"><time datetime="<?php comment_date('Y-m-d'); ?>"><?php echo poi_time_since(strtotime($comment->comment_date), true); //comment_date(get_option('date_format'));  
                                                                                                        ?></time><?= siren_get_useragent($comment->comment_agent); ?><?php echo mobile_get_useragent_icon($comment->comment_agent); ?>&nbsp;<?php if (iro_opt('comment_location')) {
                                                                                                                                                                                                                                                _e('Location', 'sakurairo'); /*来自*/ ?>: <?php echo \Sakura\API\IpLocationParse::getIpLocationByCommentId($comment->comment_ID);
                                                                                                                                                                                                                                                                                    } ?>
                                    <?php if (current_user_can('manage_options') and (wp_is_mobile() == false)) {
                                        $comment_ID = $comment->comment_ID;
                                        $i_private = get_comment_meta($comment_ID, '_private', true);
                                        $flag = null;
                                        $flag .= ' <i class="fa-regular fa-snowflake"></i> <a href="javascript:;" data-actionp="' . esc_attr('set_private&nonce=' . wp_create_nonce('siren_private_comment')) . '" data-idp="' . absint(get_comment_id()) . '" id="sp" class="sm">' . __("Private", "sakurairo") . ': <span class="has_set_private">';
                                        if (!empty($i_private)) {
                                            $flag .= __("Yes", "sakurairo") . ' <i class="fa-solid fa-lock"></i>';
                                        } else {
                                            $flag .= __("No", "sakurairo") . ' <i class="fa-solid fa-lock-open"></i>';
                                        }
                                        $flag .= '</span></a>';
                                        $flag .= edit_comment_link('<i class="fa-solid fa-pen-to-square"></i> ' . __("Edit", "mashiro"), ' <span style="color:rgba(0,0,0,.35)">', '</span>');
                                        echo $flag;
                                    } ?></div>
                                </div>
                            </section>
                        </div>
                        <div class="body">
                            <?php comment_text(); ?>
                        </div>
                    </div>
                    <div class="arrow-left"></div>
                </div>
            </div>
            <hr>
    <?php
    }
}

/*
 * 评论添加@
 */
function comment_add_at($comment_text, $comment = '')
{
  if (isset($comment->comment_parent) && $comment->comment_parent > 0) {
    if (substr($comment_text, 0, 3) === "<p>")
      $comment_text = str_replace(substr($comment_text, 0, 3), '<p><a href="#comment-' . $comment->comment_parent . '" class="comment-at">@' . get_comment_author($comment->comment_parent) . '</a>&nbsp;', $comment_text);
    else
      $comment_text = '<a href="#comment-' . $comment->comment_parent . '" class="comment-at">@' . get_comment_author($comment->comment_parent) . '</a>&nbsp;' . $comment_text;
  }
  return $comment_text;
}
add_filter('comment_text', 'comment_add_at', 20, 2);