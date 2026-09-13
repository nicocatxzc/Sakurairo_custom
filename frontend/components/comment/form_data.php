<?php
function iro_comment_form_data($args = array(), $post = null)
{
    $post = get_post($post);

    if (! $post || ! comments_open($post)) {
        do_action('comment_form_comments_closed');

        return null;
    }

    $post_id   = $post->ID;
    $commenter = wp_get_current_commenter();
    $user      = wp_get_current_user();

    $user_identity = $user->exists()
        ? $user->display_name
        : '';

    $args = wp_parse_args($args);

    $required = get_option('require_name_email');

    $required_attribute = ' required';
    $checked_attribute  = ' checked';

    $required_indicator = ' ' . wp_required_field_indicator();
    $required_text      = ' ' . wp_required_field_message();

    // 默认字段
    $fields = array(
        'author' => iro_comment_form_field_author(
            $commenter,
            $required,
            $required_attribute
        ),

        'email' => iro_comment_form_field_email(
            $commenter,
            $required,
            $required_attribute
        ),

        'url' => iro_comment_form_field_url(
            $commenter
        ),
    );

    // 保存信息
    $cookies_enabled =
        has_action('set_comment_cookies', 'wp_set_comment_cookies')
        && get_option('show_comments_cookies_opt_in');

    if ($cookies_enabled) {
        $fields['cookies'] = iro_comment_form_field_cookies(
            $commenter,
            $checked_attribute
        );
    }

    //保存原始字段。
    $original_fields = $fields;

    // WordPress 插件入口
    $fields = apply_filters(
        'comment_form_default_fields',
        $fields
    );

    // 保留 WordPress 的参数/filter contract。
    $defaults = array(
        'fields' => $fields,

        'comment_field' => iro_comment_form_field_comment(
            $required_indicator,
            $required_attribute
        ),

        'must_log_in' => iro_comment_form_must_log_in($post_id),

        'logged_in_as' => iro_comment_form_logged_in_as(
            $user_identity,
            $post_id,
            $required_text
        ),

        'comment_notes_before' => iro_comment_form_notes_before(
            $required_text
        ),

        'comment_notes_after' => '',

        'action' => site_url('/wp-comments-post.php'),

        'novalidate' => false,

        'id_form' => 'commentform',
        'id_submit' => 'submit',

        'class_container' => 'comment-respond',
        'class_form' => 'comment-form',
        'class_submit' => 'submit',

        'name_submit' => 'submit',

        'title_reply' => __('Leave a Reply'),
        'title_reply_to' => __('Leave a Reply to %s'),

        'title_reply_before' =>
        '<h3 id="reply-title" class="comment-reply-title">',

        'title_reply_after' => '</h3>',

        'cancel_reply_before' => ' <small>',
        'cancel_reply_after' => '</small>',

        'cancel_reply_link' => __('Cancel reply'),

        'label_submit' => __('Post Comment'),

        'submit_button' =>
        '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',

        'submit_field' =>
        '<p class="form-submit functions">%1$s %2$s</p>',
    );

    // 保留 WordPress comment_form_defaults。
    $args = wp_parse_args(
        $args,
        apply_filters(
            'comment_form_defaults',
            $defaults
        )
    );

    /*
     * WordPress 原版行为：
     * filtered args 不能把默认值完全删掉。
     */
    $args = array_merge($defaults, $args);

    /*
     * email 的 aria-describedby 兼容逻辑。
     */
    $email_has_notes =
        str_contains(
            $args['comment_notes_before'],
            'id="email-notes"'
        );

    if (
        isset($args['fields']['email'])
        && ! $email_has_notes
    ) {
        $args['fields']['email'] = str_replace(
            ' aria-describedby="email-notes"',
            '',
            $args['fields']['email']
        );
    }

    // comment field contract
    $comment_fields = array(
        'comment' => apply_filters(
            'comment_form_field_comment',
            $args['comment_field']
        ),
    );

    foreach ((array) $args['fields'] as $name => $field) {
        $comment_fields[$name] = apply_filters(
            "comment_form_field_{$name}",
            $field
        );
    }

    // comment_form_fields
    $comment_fields = apply_filters(
        'comment_form_fields',
        $comment_fields
    );


    // 字段顺序信息
    $field_names = array_keys($comment_fields);

    $non_comment_fields = array_diff(
        $field_names,
        array('comment')
    );

    $first_field = reset($non_comment_fields);
    $last_field  = end($non_comment_fields);

    // 提交
    $submit_button = sprintf(
        $args['submit_button'],
        esc_attr($args['name_submit']),
        esc_attr($args['id_submit']),
        esc_attr($args['class_submit']),
        esc_attr($args['label_submit'])
    );

    $submit_button = apply_filters(
        'comment_form_submit_button',
        $submit_button,
        $args
    );

    $submit_field = sprintf(
        $args['submit_field'],
        $submit_button,
        get_comment_id_fields($post_id)
    );

    $submit_field = apply_filters(
        'comment_form_submit_field',
        $submit_field,
        $args
    );

    return array(
        'post' => $post,
        'post_id' => $post_id,

        'commenter' => $commenter,

        'user' => array(
            'logged_in' => $user->exists(),
            'identity' => $user_identity,
        ),

        'args' => $args,

        'fields' => $comment_fields,

        'original_fields' => $original_fields,

        'field_names' => $field_names,

        'first_field' => $first_field,
        'last_field' => $last_field,

        'required' => $required,

        'cookies_enabled' => $cookies_enabled,

        'thread_comments' => (bool) get_option('thread_comments'),

        'comment_registration' =>
        (bool) get_option('comment_registration'),

        'submit' => array(
            'button' => $submit_button,
            'field' => $submit_field,
            'hidden' => get_comment_id_fields($post_id),
        ),

        'reply' => array(
            'title' => $args['title_reply'],
            'title_to' => $args['title_reply_to'],
            'cancel' => $args['cancel_reply_link'],
        ),
    );
}

function iro_comment_form_field_author(
    array $commenter,
    bool $required,
    string $required_attribute
) {
    ob_start();
?>
    <p class="comment-form-author flex-center">
        <label for="author" class="hide">
            <?= esc_html__('Name') ?><?= $required ? ' ' . wp_required_field_indicator() : '' ?>
        </label>
        <input
            id="author"
            name="author"
            type="text"
            placeholder="<?= esc_html__('Name') ?>"
            value="<?= esc_attr($commenter['comment_author']) ?>"
            size="30"
            maxlength="245"
            autocomplete="name"
            <?= $required ? $required_attribute : '' ?> />
    </p>
<?php
    return ob_get_clean();
}

function iro_comment_form_field_email(
    array $commenter,
    bool $required,
    string $required_attribute
) {
    ob_start();
?>
    <p class="comment-form-email flex-center">
        <label for="email" class="hide">
            <?= esc_html__('Email') ?><?= $required ? ' ' . wp_required_field_indicator() : '' ?>
        </label>
        <input
            id="email"
            name="email"
            type="email"
            placeholder="<?= esc_html__('Email') ?>"
            value="<?= esc_attr($commenter['comment_author_email']) ?>"
            size="30"
            maxlength="100"
            aria-describedby="email-notes"
            autocomplete="email"
            <?= $required ? $required_attribute : '' ?> />
    </p>
<?php
    return ob_get_clean();
}

function iro_comment_form_field_url(
    array $commenter
) {
    ob_start();
?>
    <p class="comment-form-url flex-center">
        <label for="url" class="hide"><?= esc_html__('Website') ?></label>
        <input
            id="url"
            name="url"
            type="url"
            placeholder="<?= esc_html__('Website') ?>"
            value="<?= esc_attr($commenter['comment_author_url']) ?>"
            size="30"
            maxlength="200"
            autocomplete="url" />
    </p>
<?php
    return ob_get_clean();
}

function iro_comment_form_field_cookies(
    array $commenter,
    string $checked_attribute
) {
    $checked = empty($commenter['comment_author_email'])
        ? ''
        : $checked_attribute;
    ob_start();
?>
    <p class="comment-form-cookies-consent">
        <input
            id="wp-comment-cookies-consent"
            name="wp-comment-cookies-consent"
            type="checkbox"
            value="yes"
            <?= $checked ?> />
        <label
            for="wp-comment-cookies-consent"
            title="<?= esc_html__('Save my name, email, and website in this browser for the next time I comment.') ?>">
            <?= __('保留个人信息', 'sakurairo') ?>
        </label>
    </p>
<?php
    return ob_get_clean();
}

function iro_comment_form_field_comment(
    string $required_indicator,
    string $required_attribute
) {
    ob_start();
?>
    <p class="comment-form-comment">
        <label for="comment" class="hide">
            <?= esc_html_x('Comment', 'noun') ?><?= $required_indicator ?>
        </label>
        <textarea
            id="comment"
            name="comment"
            cols="45"
            rows="8"
            maxlength="65525"
            placeholder=" "
            <?= $required_attribute
            //此处标签不换行，不然评论区默认会多空格
            ?>></textarea>

        <span class="placeholder">
            <?= iro_opt("comment_input_place_holder", "") ?>
        </span>
    </p>

<?php
    return ob_get_clean();
}

function iro_comment_form_must_log_in(int $post_id)
{
    $login_url = wp_login_url(
        apply_filters(
            'the_permalink',
            get_permalink($post_id),
            $post_id
        )
    );
    ob_start();
?>
    <p class="must-log-in">
        <?= sprintf(
            esc_html__('You must be <a href="%s">logged in</a> to post a comment.'),
            esc_url($login_url)
        ) ?>
    </p>
<?php
    return ob_get_clean();
}

function iro_comment_form_logged_in_as(
    string $identity,
    int $post_id,
    string $required_text
) {
    /*
    $logout_url = wp_logout_url(
        apply_filters(
            'the_permalink',
            get_permalink($post_id),
            $post_id
        )
    );
    ob_start();
?>
    <p class="logged-in-as">
        <?= sprintf(
            esc_html__('Logged in as %1$s. <a href="%2$s">Edit your profile</a>. <a href="%3$s">Log out?</a>'),
            esc_html($identity),
            esc_url(get_edit_user_link()),
            esc_url($logout_url)
        ) ?>
        <?= $required_text ?>
    </p>
<?php
    return ob_get_clean();
    */
}

function iro_comment_form_notes_before(string $required_text)
{
    /*
    ob_start();
?>
    <p class="comment-notes">
        <span id="email-notes">
            <?= esc_html__('Your email address will not be published.') ?>
        </span>
        <?= $required_text ?>
    </p>
<?php
    return ob_get_clean();
    */
}
