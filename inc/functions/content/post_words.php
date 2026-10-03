<?php
function iro_get_post_words($id)
{
    $post = get_post($id);
    if (!$post) {
        return 0;
    }

    $words = (int) get_post_meta($id, 'post_words_count', true);
    if (!$words) {
        return iro_count_post_words($id);
    } else {
        return $words;
    }
}

function iro_count_post_words($id)
{
    $text = iro_ai_post_content($id, 999999999);
    if (is_wp_error($text) || $text === '') {
        return 0;
    }
    $sum = 0;

    // 英文单词：按单词个数计算 排除Lm Lo; 一组连续数字视为一个单词计算
    $res = preg_match_all('/[\d\p{Lu}\p{Ll}\p{Lt}]+/u', $text);
    if ($res !== false) {
        $sum += $res;
    }
    // 按字符个数计算的：汉字、假名、谚文
    $res = preg_match_all('/[\p{Han}\p{Katakana}\p{Hiragana}\p{Hangul}]/u', $text);
    if ($res !== false) {
        $sum += $res;
    }
    add_post_meta($id, 'post_words_count', $sum, true);
    update_post_meta($id, 'post_words_count', $sum);
    return $sum;
}
add_action('save_post', function ($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    iro_count_post_words($post_id);
}, 10, 3);

function iro_get_reading_time($post_id)
{
    $words_count = get_post_meta($post_id, 'post_words_count', true);
    if ($words_count) {
        $ert = round($words_count / 220);
        if ($ert  < 1) {
            return __("小于一分钟", "sakurairo");
        } else if ($ert > 60) {
            $hour = round($ert / 60);
            return sprintf(_n('%s 小时', '%s 小时', $hour, "sakurairo"), number_format_i18n($hour));
        } else {
            return sprintf(_n('%s 分钟', '%s 分钟', $ert, "sakurairo"), number_format_i18n($ert));
        }
    } else {
        return "";
    }
}
