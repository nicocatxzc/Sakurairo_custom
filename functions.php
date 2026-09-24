<?php

/**
 * iro functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package iro
 */

define('IRO_VERSION', wp_get_theme()->get('Version'));
define('BUILD_VERSION', '3');
define('INT_VERSION', '20.1.0');

// 设置框架
require_once get_template_directory() . '/inc/theme_init/iro_opt.php';

$iro_options=get_option('iro_options');
global $iro_options;

// 屏蔽php日志信息
if (iro_opt('php_notice_filter') != 'inner') {

    if (iro_opt('php_notice_filter', 'normal') == 'normal') { //仅显示严重错误
        error_reporting(E_ALL & ~E_DEPRECATED);
        ini_set('display_errors', '1');
    }
    if (iro_opt('php_notice_filter') == 'all') { //屏蔽大部分错误
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        ini_set('display_errors', '0');
    }
}

// Update-Checker
// 在主文件中载入，否则会报错
require_once get_template_directory() . '/update-checker/update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

function UpdateCheck($url, $flag = 'Sakurairo')
{
    return PucFactory::buildUpdateChecker(
        $url,
        __FILE__,
        $flag
    );
}
switch (iro_opt('iro_update_source')) {
    case 'github':
        $iroThemeUpdateChecker = UpdateCheck('https://github.com/mirai-mamori/Sakurairo', 'Sakurairo');
        break;
    case 'upyun':
        $iroThemeUpdateChecker = UpdateCheck('https://api.fuukei.org/update/jsdelivr.json');
        break;
    case 'official_building':
        $iroThemeUpdateChecker = UpdateCheck('https://api.fuukei.org/update/' . iro_opt('iro_update_channel') . '/check.json');
}

// 载入主题支持
require_once get_template_directory() . '/inc/theme_init/support.php';
// 载入翻译
require_once get_template_directory() . '/inc/theme_init/translation.php';
// 载入shuoshuo文章类型
require_once get_template_directory() . '/inc/theme_init/shuoshuo.php';
// 工具函数
require_once get_template_directory() . '/inc/functions/tools.php';
require_once get_template_directory() . '/inc/functions/ip.php';
// api
require_once get_template_directory() . '/inc/api.php';
// 全站优化
require_once get_template_directory() . '/inc/functions/optimize/index.php';
// 载入wp精简定制
require_once get_template_directory() . '/inc/functions/cust_wp.php';
// wordpress兼容性修复
require_once get_template_directory() . '/inc/theme_init/wp_fix.php';
require_once get_template_directory() . '/inc/functions/custom/register.php';
// 中国本地化
require_once get_template_directory() . '/inc/functions/wp_cn.php';
// 载入区块渲染
require_once get_template_directory() . '/inc/blocks/render.php';
// 载入函数组件
require_once get_template_directory() . '/frontend/components/component_register.php';
// 载入内容相关资源
require_once get_template_directory() . '/inc/functions/content/index.php';
// 载入区块编辑器修改
require_once get_template_directory() . '/inc/blocks/iro_blocks.php';
// seo相关
require_once get_template_directory() . '/inc/functions/seo.php';
// 站点地图
require_once get_template_directory() . '/inc/functions/sitemap.php';
// 载入前台头部资源
require_once get_template_directory() . '/inc/functions/enqueue_assets.php';
// 前端主题配置
require_once get_template_directory() . '/frontend/theme_config.php';
// 加载customizer编辑器
require_once get_template_directory() . '/opt/customizer/index.php';
// 定制后台以及登录页
require_once get_template_directory() . '/inc/functions/custom/index.php';
// 评论区相关
require_once get_template_directory() . '/inc/functions/comment/index.php';
// 导航栏相关
require_once get_template_directory() . '/inc/functions/nav_bar.php';
// 主题安装后检查及后台通知
require_once get_template_directory() . '/inc/theme_init/check.php';
// 操作触发
require_once get_template_directory() . '/inc/functions/operator.php';