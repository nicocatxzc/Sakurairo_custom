<?php
// /**
//  * 字数、词数统计
//  */
// function count_post_words($post_ID)
// {
//     $post = get_post($post_ID);
//     if (!in_array($post->post_type, ['post', 'shuoshuo'])) {
//         return;
//     }
//     $content = $post->post_content;
//     $content = strip_tags($content);
//     $count = word_stat($content);
//     update_post_meta($post_ID, 'post_words_count', $count);
//     return $count;
// }

// add_action('save_post', 'count_post_words');