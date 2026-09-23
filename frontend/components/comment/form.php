<?php
require_once get_template_directory() . "/frontend/components/comment/form_data.php";
$post_comment_args = iro_comment_form_data();
?>

<?php if (! $post_comment_args) : ?>
    <!-- Comment Not Availiable -->
<?php else : ?>

    <?php do_action('comment_form_before'); ?>

    <div
        id="respond"
        class="<?= esc_attr($post_comment_args['args']['class_container']) ?>">

        <?php if (
            $post_comment_args['comment_registration']
            && ! $post_comment_args['user']['logged_in']
        ) : ?>

            <?= $post_comment_args['args']['must_log_in'] ?>

            <?php do_action('comment_form_must_log_in_after'); ?>

        <?php else : ?>

            <form
                disaction="<?= esc_url($post_comment_args['args']['action']) ?>"
                action="<?php echo esc_url(rest_url('wp/v2/comments')); ?>"
                method="post"
                id="<?= esc_attr($post_comment_args['args']['id_form']) ?>"
                class="<?= esc_attr($post_comment_args['args']['class_form']) ?>"
                <?= $post_comment_args['args']['novalidate'] ? 'novalidate' : '' ?>>

                <?php do_action('comment_form_top'); ?>


                <?php if ($post_comment_args['user']['logged_in']) : ?>

                    <?= apply_filters(
                        'comment_form_logged_in',
                        $post_comment_args['args']['logged_in_as'],
                        $post_comment_args['commenter'],
                        $post_comment_args['user']['identity']
                    ) ?>

                    <?php do_action(
                        'comment_form_logged_in_after',
                        $post_comment_args['commenter'],
                        $post_comment_args['user']['identity']
                    ); ?>

                <?php else : ?>

                    <?= $post_comment_args['args']['comment_notes_before'] ?>

                <?php endif; ?>


                <?php
                // 字段分组
                $comment_fields = [];
                $info_fields    = [];
                $check_fields   = [];
                $extra_fields   = [];

                foreach ($post_comment_args['fields'] as $name => $field) :

                    switch ($name):

                        case 'comment':
                            $comment_fields[$name] = $field;
                            break;

                        case 'author':
                        case 'email':
                        case 'url':
                            $info_fields[$name] = $field;
                            break;

                        case 'cookies':
                            $check_fields[$name] = $field;
                            break;

                        default:
                            $extra_fields[$name] = $field;
                            break;

                    endswitch;

                endforeach;
                ?>


                <!-- 评论区域 -->
                <span id="reply-context" class="reply-context" hidden></span>
                <?php foreach ($comment_fields as $name => $field) : ?>
                    <?= $field ?>
                <?php endforeach; ?>

                <?= $post_comment_args['args']['comment_notes_after'] ?>

                <!-- 名称 / 邮箱 / 网站 -->
                <?php if (!is_user_logged_in()): ?>
                    <div class="infos">
                        <img
                            alt="avatar"
                            src="<?= iro_media_optimize_image_url(iro_opt("missing_avatars_placeholder")) ?>"
                            class="nuxtpic avatar" />
                        <?php foreach ($info_fields as $name => $field) : ?>
                            <?= $field ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- 其他字段 -->
                <?php if (!is_user_logged_in()): ?>
                    <div class="checks flex-center">
                        <?php if (iro_opt("comment_captcha", "builtin") != "off"): ?>
                            <div class="captcha <?= iro_opt("comment_captcha", "builtin") ?>"></div>
                        <?php endif; ?>
                        <?php foreach ($check_fields as $name => $field) : ?>
                            <?= $field ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>


                <!-- 其他附加字段 -->
                <?php if ($extra_fields) : ?>
                    <div class="extras">
                        <?php foreach ($extra_fields as $name => $field) : ?>
                            <?= $field ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>


                <?= $post_comment_args['submit']['field'] ?>

                <?php do_action('comment_form', $post_comment_args['post_id']); ?>

            </form>

        <?php endif; ?>

    </div>

    <?php do_action('comment_form_after'); ?>

<?php endif; ?>