<?php
/*
 * 修改评论表情调用路径
 */

// 简单遍历系统表情库，今后应考虑标识表情包名——使用增加的扩展名，同时保留原有拓展名
// 还有一个思路是根据表情调用路径来判定<-- 此法最好！
// 贴吧

function make_onclick_grin($name, $type, $before = '', $after = '')
{
    $extra_params = "";
    if ($before || $after) {
        $extra_params = ",'$before','$after'";
    }
    return "onclick=\"grin('$name','$type'$extra_params)\"";
}
/**
 * 通过文件夹获取自定义表情列表，使用Transients来存储获得的列表，除非手动清除，数据永不过期。
 * 数据格式如下：
 * Array
 * (
 *     [0] => Array
 *         (
 *             [path] => C:\xampp\htdocs\wordpress/wp-content/uploads/sakurairo_vision/@2.4/smilies\bilipng\emoji_2233_chijing.png
 *             [little_path] => /sakurairo_vision/@2.4/smilies\bilipng\emoji_2233_chijing.png
 *             [file_url] => http://192.168.233.174/wordpress/wp-content/uploads/sakurairo_vision/@2.4/smilies\bilipng\emoji_2233_chijing.png
 *             [name] => emoji_2233_chijing.png
 *             [base_name] => emoji_2233_chijing
 *             [extension] => png
 *         )
 *     ...
 * ）    
 *
 * @return array
 */
function get_custom_smilies_list()
{

    $custom_smilies_list = get_transient("custom_smilies_list");

    if ($custom_smilies_list !== false) {
        return $custom_smilies_list;
    }

    $custom_smilies_list = array();
    $custom_smilies_dir = iro_opt('smilies_dir');

    if (!$custom_smilies_dir) {
        return $custom_smilies_list;
    }

    $custom_smilies_extension = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'avif', 'webp'];
    $custom_smilies_path = wp_get_upload_dir()['basedir'] . $custom_smilies_dir;

    if (!is_dir($custom_smilies_path)) {
        return $custom_smilies_list;
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($custom_smilies_path), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($files as $file) {
        if ($file->isFile()) {
            $file_name = $file->getFilename();
            $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $file_path = $file->getPathname();
            $file_little_path = str_replace(wp_get_upload_dir()['basedir'], '', $file_path);
            $file_url = wp_get_upload_dir()['baseurl'] . $file_little_path;
            if (in_array($file_extension, $custom_smilies_extension)) {
                $custom_smilies_list[] = array(
                    'path' => $file_path,
                    'little_path' => $file_little_path,
                    'file_url' => $file_url,
                    'name' => $file_name,
                    'base_name' => $file_base_name,
                    'extension' => $file_extension
                );
            }
        }
    }
    set_transient("custom_smilies_list", $custom_smilies_list);

    return $custom_smilies_list;
}

/**
 * 通过 GET 方法更新自定义表情包列表
 */
function update_custom_smilies_list()
{

    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['update_custom_smilies'])) {
        return;
    }

    $transient_name = sanitize_key($_GET['update_custom_smilies']);

    if ($transient_name === 'true') {
        delete_transient("custom_smilies_list");
        $custom_smilies_list = get_custom_smilies_list();
        $much = count($custom_smilies_list);
        $custom_smilies_dir = iro_opt('smilies_dir');
        $custom_smilies_path = wp_get_upload_dir()['basedir'] . $custom_smilies_dir;
        echo '自定义表情列表更新完成！总共有' . $much . '个表情。<br>';
        echo 'Custom smilies updated!Total' . $much . '.';
        echo "<pre>调试信息：
        - 表情目录设置为: $custom_smilies_dir
        - 实际读取的路径为: $custom_smilies_path
        Debug info:
        - Smilies path set is: $custom_smilies_dir
        - The directory actually read is: $custom_smilies_path
        </pre>
        <p>以下图片已被收录至自定义表情中（The following images have been included in the custom emoticons）：</p>";
    }
    if (!empty($custom_smilies_list)) {
        echo '<ul style="list-style: none; padding: 0; max-width: 600px;">';
        foreach ($custom_smilies_list as $smiley) {
            echo '<li style="margin-bottom: 10px; display: flex; align-items: center;">';
            echo '<img src="' . esc_url($smiley['file_url']) . '" alt="' . esc_attr($smiley['base_name']) . '" style="height: 60px; margin-right: 10px;">';
            echo '<span>' . esc_html($smiley['base_name']) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>没有任何图片被加入表情包中（No emoticons found）。</p>';
    }
}
update_custom_smilies_list();


$custom_smiliestrans = array();
function push_custom_smilies()
{

    global $custom_smiliestrans;
    $custom_smilies_panel = '';
    $custom_smilies_list = get_custom_smilies_list();

    if (!$custom_smilies_list) {
        $custom_smilies_panel = '<div style="font-size: 20px;text-align: center;width: 300px;height: 100px;line-height: 100px;">File does not exist!</div>';
        return $custom_smilies_panel;
    }

    $custom_smilies_cdn = iro_opt('smilies_proxy');
    foreach ($custom_smilies_list as $smiley) {

        if ($custom_smilies_cdn) {
            $smiley_url = $custom_smilies_cdn . $smiley['little_path'];
        } else {
            $smiley_url = $smiley['file_url'];
        }
        $custom_smilies_panel = $custom_smilies_panel . '<span title="' . $smiley['base_name'] . '" ' . make_onclick_grin($smiley['base_name'], 'Math') . '><img alt="custom_smilies" loading="lazy" style="height: 60px;" src="' . $smiley_url . '" /></span>';
        $custom_smiliestrans['{{' . $smiley['base_name'] . '}}'] = '<span title="' . $smiley['base_name'] . '" ><img alt="custom_smilies" loading="lazy" style="height: 60px;" src="' . $smiley_url . '" /></span>';
    }

    return $custom_smilies_panel;
}

/**
 * 替换评论、文章中的表情符号
 *
 */
function custom_smilies_filter($content)
{
    push_custom_smilies();
    global $custom_smiliestrans;
    $content = str_replace(array_keys($custom_smiliestrans), $custom_smiliestrans, $content);
    return $content;
}
add_filter('the_content', 'custom_smilies_filter');
add_filter('comment_text', 'custom_smilies_filter');

function is_webp(): bool
{
    return (isset($_COOKIE['su_webp']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp')));
}

$wpsmiliestrans = array();
function push_tieba_smilies()
{
    global $wpsmiliestrans;
    // don't bother setting up smilies if they are disabled
    if (!get_option('use_smilies'))
        return;
    $tiebaname = array('good', 'han', 'spray', 'Grievance', 'shui', 'reluctantly', 'anger', 'tongue', 'se', 'haha', 'rmb', 'doubt', 'tear', 'surprised2', 'Happy', 'ku', 'surprised', 'theblackline', 'smilingeyes', 'spit', 'huaji', 'bbd', 'hu', 'shame', 'naive', 'rbq', 'britan', 'aa', 'niconiconi', 'niconiconi_t', 'niconiconit', 'awesome');
    $return_smiles = '';
    $type = is_webp() ? 'webp' : 'png';
    $tiebaimgdir = 'tieba' . $type . '/';
    $smiliesgs = '.' . $type;
    foreach ($tiebaname as $tieba_Name) {
        $grin = make_onclick_grin($tieba_Name, 'tieba');
        // 选择面版
        $return_smiles = $return_smiles . '<span title="' . $tieba_Name . '" ' . $grin . '><img alt="tieba_smilie" loading="lazy" src="' . iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/') . 'smilies/' . $tiebaimgdir . 'icon_' . $tieba_Name . $smiliesgs . '" /></span>';
        // 正文转换
        $wpsmiliestrans['::' . $tieba_Name . '::'] = '<span title="' . $tieba_Name . '" ' . $grin . '><img alt="tieba_smilie" loading="lazy" src="' . iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/') . 'smilies/' . $tiebaimgdir . 'icon_' . $tieba_Name . $smiliesgs . '" /></span>';
    }
    return $return_smiles;
}
push_tieba_smilies();

function tieba_smile_filter($content)
{
    global $wpsmiliestrans;
    $content = str_replace(array_keys($wpsmiliestrans), $wpsmiliestrans, $content);
    return $content;
}
add_filter('the_content', 'tieba_smile_filter'); //替换文章关键词
add_filter('comment_text', 'tieba_smile_filter'); //替换评论关键词

function push_emoji_panel()
{
    $emojis = ['(⌒▽⌒)', '（￣▽￣）', '(=・ω・=)', '(｀・ω・´)', '(〜￣△￣)〜', '(･∀･)', '(°∀°)ﾉ', '(￣3￣)', '╮(￣▽￣)╭', '(´_ゝ｀)', '←_←', '→_→', '(&lt;_&lt;)', '(&gt;_&gt;)', '(;¬_¬)', '("▔□▔)/', '(ﾟДﾟ≡ﾟдﾟ)!?', 'Σ(ﾟдﾟ;)', 'Σ(￣□￣||)', '(’；ω；‘)', '（/TДT)/', '(^・ω・^ )', '(｡･ω･｡)', '(●￣(ｴ)￣●)', 'ε=ε=(ノ≧∇≦)ノ', '(’･_･‘)', '(-_-#)', '（￣へ￣）', '(￣ε(#￣)Σ', 'ヽ(‘Д’)ﾉ', '（#-_-)┯━┯', '(╯°口°)╯(┴—┴', '←◡←', '( ♥д♥)', '_(:3」∠)_', 'Σ&gt;―(〃°ω°〃)♡→', '⁄(⁄ ⁄•⁄ω⁄•⁄ ⁄)⁄', '(╬ﾟдﾟ)▄︻┻┳═一', '･*･:≡(　ε:)', '(笑)', '(汗)', '(泣)', '(苦笑)'];
    return join('', array_map(function ($emoji) {
        return '<span class="emoji-item">' . $emoji . '</span>';
    }, $emojis));
}

// bilibili smiles
$bilismiliestrans = array();
function push_bili_smilies()
{
    global $bilismiliestrans;
    $name = array('baiyan', 'bishi', 'bizui', 'chan', 'dai', 'daku', 'dalao', 'dalian', 'dianzan', 'doge', 'facai', 'fanu', 'ganga', 'guilian', 'guzhang', 'haixiu', 'heirenwenhao', 'huaixiao', 'jingxia', 'keai', 'koubizi', 'kun', 'lengmo', 'liubixue', 'liuhan', 'liulei', 'miantian', 'mudengkoudai', 'nanguo', 'outu', 'qinqin', 'se', 'shengbing', 'shengqi', 'shuizhao', 'sikao', 'tiaokan', 'tiaopi', 'touxiao', 'tuxue', 'weiqu', 'weixiao', 'wunai', 'xiaoku', 'xieyanxiao', 'yiwen', 'yun', 'zaijian', 'zhoumei', 'zhuakuang');
    $return_smiles = '';
    $type = is_webp() ? 'webp' : 'png';
    $biliimgdir = 'bili' . $type . '/';
    $smiliesgs = '.' . $type;
    foreach ($name as $smilies_Name) {
        $grin = make_onclick_grin($smilies_Name, 'Math');
        // 选择面版
        $return_smiles = $return_smiles . '<span title="' . $smilies_Name . '" ' . $grin . '><img alt="bili_smilies" loading="lazy" src="' . iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/') . 'smilies/' . $biliimgdir . 'emoji_' . $smilies_Name . $smiliesgs . '" /></span>';
        // 正文转换
        $bilismiliestrans['{{' . $smilies_Name . '}}'] = '<span title="' . $smilies_Name . '" ' . $grin . '><img alt="bili_smilies" loading="lazy" src="' . iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/') . 'smilies/' . $biliimgdir . 'emoji_' . $smilies_Name . $smiliesgs . '" /></span>';
    }
    return $return_smiles;
}
push_bili_smilies();

function bili_smile_filter($content)
{
    global $bilismiliestrans;
    $content = str_replace(array_keys($bilismiliestrans), $bilismiliestrans, $content);
    return $content;
}
add_filter('the_content', 'bili_smile_filter'); //替换文章关键词
add_filter('comment_text', 'bili_smile_filter'); //替换评论关键词

function featuredtoRSS($content)
{
    global $post;
    if (has_post_thumbnail($post->ID)) {
        $content = '<div>' . get_the_post_thumbnail($post->ID, 'medium', array('style' => 'margin-bottom: 15px;')) . '</div>' . $content;
    }
    return $content;
}
add_filter('the_excerpt_rss', 'featuredtoRSS');
add_filter('the_content_feed', 'featuredtoRSS');

//
function bili_smile_filter_rss($content)
{
    $type = is_webp() ? 'webp' : 'png';
    $biliimgdir = 'bili' . $type . '/';
    $smiliesgs = '.' . $type;
    $content = str_replace('{{', '<img src="' . iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/') . 'smilies/' . $biliimgdir, $content);
    $content = str_replace('}}', $smiliesgs . '" alt="emoji" style="height: 2em; max-height: 2em;">', $content);
    $content = str_replace('[img]', '<img src="', $content);
    $content = str_replace('[/img]', '" style="display: block;margin-left: auto;margin-right: auto;">', $content);
    return $content;
}
add_filter('comment_text_rss', 'bili_smile_filter_rss'); //替换评论rss关键词

/*
 * 评论表情修复
 */

function admin_ini()
{
    wp_enqueue_style('cus-styles-fit', get_template_directory_uri() . '/css/dashboard-emoji-fix.css');
}
add_action('admin_enqueue_scripts', 'admin_ini');