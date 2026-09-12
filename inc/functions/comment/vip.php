<?php

/**
 * 获取访客VIP样式
 */
function get_author_class($comment_author_email, $user_id)
{
    global $wpdb;
    $author_count = count(
        $wpdb->get_results(
            "SELECT comment_ID as author_count FROM $wpdb->comments WHERE comment_author_email = '$comment_author_email' "
        )
    );
    # 等级梯度
    $lv_array = [0, 5, 10, 20, 40, 80, 160];
    $Lv = 0;
    foreach ($lv_array as $key => $value) {
        if ($value >= $author_count)
            break;
        $Lv = $key;
        if (user_can($user_id, 'administrator')) {
            $Lv = 6;
        }
    }

    // $Lv = $author_count < 5 ? 0 : ($author_count < 10 ? 1 : ($author_count < 20 ? 2 : ($author_count < 40 ? 3 : ($author_count < 80 ? 4 : ($author_count < 160 ? 5 : 6)))));
    echo "<span class=\"showGrade{$Lv}\" title=\"Lv{$Lv}\"><img alt=\"level_img\" src=\"" . iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/') . "comment_level/level_{$Lv}.svg\" style=\"height: 1.5em; max-height: 1.5em; display: inline-block;\"></span>";
}
