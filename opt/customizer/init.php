<?php

/**
 * Sakurairo Theme Customizer.
 * Use Kirki
 * https://github.com/themeum/kirki
 * @package Sakurairo
 */

if (! defined('ABSPATH')) {
    exit;
}

// 载入Kirki
if (! class_exists('Kirki')) {
    require_once __DIR__ . '/kirki/kirki.php';

    define('KIRKI_NO_OUTPUT', true);
    define('KIRKI_NO_GUTENBERG_OUTPUT', true);
}

// 面板部分
// 面板：每个面板至少包含 id、title，可选description（可选priority 将自动分配，描述不填自动为空）
$panels = [
    [
        'id'          => 'iro_global',
        'title'       => esc_html__('全局设置', 'sakurairo'),
        'priority'    => 10,
    ],
    [
        'id'          => 'iro_cover',
        'title'       => esc_html__('主页封面', 'sakurairo'),
        'priority'    => 10,
    ],
    [
        'id'          => 'iro_homepage',
        'title'       => esc_html__('主页设置', 'sakurairo'),
        'priority'    => 10,
    ],
    [
        'id'          => 'iro_pages',
        'title'       => esc_html__('页面设置', 'sakurairo'),
        'priority'    => 10,
    ],
];

// 所有可以传递的参数列表（按 Themeum/Kirki 官方文档）
$allowed_params = [
    'tab',              // 所属section中的选项卡，
    'active_callback',  // 回调函数，决定该字段是否显示
    'button_label',     // 用于 repeater 等控件，定义新增行的按钮文案
    'capability',       // 所需权限
    'choices',          // 可选项，适用于下拉、单选、复选等类型
    'default',          // 默认值
    'description',      // 描述信息
    'fields',           // 用于 repeater 等控件，定义子字段
    'js_vars',          // 用于 postMessage 实时预览的 JS 配置
    'label',            // 字段标签（必填，未设置则默认空字符串）
    'multiple',         // 允许多选时使用
    'option_name',      // 当保存到 option 时指定 option 名称
    'option_type',      // 保存类型，'theme_mod' 或 'option'
    'output',           // 自动输出前端 CSS 的配置数组
    'partial_refresh',  // 部分刷新设置
    'preset',           // 预设值（如预设色板）
    'priority',         // 排序权重（必填，未填写将自动赋值）
    'row_label',        // 用于 repeater 等控件，定义每行的标题
    'sanitize_callback', // 数据过滤函数
    'section',          // 所属区块 ID（必填）
    'settings',         // 设置项 ID（必填）
    'tooltip',          // 字段提示信息
    'transport',        // 数据传输方式，如 'refresh' 或 'postMessage'，未设置的请设置iro_key，将请求php端渲染
    'iro_key',          // Sakurairo options键，
    // 使用的选项将实时上报更改信息，以进行复杂更改的渲染，
    // 同时也不用设置默认值，直接从iro_options中获取当前值
    // 也可以不设置回调，默认更新至 iro_options[iro_key]
    'iro_subkey'        // key的子键
];

$vision_resource_basepath = iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/');

// 分组和设置项部分
// 分组：每个分组至少包含 id、title、description、所属面板 panel
// 设置项（Field）数组：每个设置项至少包含 type、settings、label、所属区块 section
$sections = [
    // ====================外观设置====================
    [
        'id'          => 'iro_appearance',
        'title'       => esc_html__('外观设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_global',

        'fields'      => [
            [
                'type'     => 'custom',
                'settings' => 'iro_appearance_notice',
                'default'  => __('<p>主题配色</p>', 'sakurairo'),
            ],
            [
                'type'        => 'color',
                'settings'    => 'word_color_first',
                'iro_key'     => 'word_color_first',
                'label'       => esc_html__('主要文字颜色', 'sakurairo'),
                'description' => esc_html__('文章标题和正文内容等文字的颜色', 'sakurairo'),
                'default'     => '#505050',
                'transport'   => 'auto',
                'output'      => [['element' => ':root', 'property' => '--word-color-first']],
            ],
            [
                'type'        => 'color',
                'settings'    => 'word_color_second',
                'iro_key'     => 'word_color_second',
                'label'       => esc_html__('次要文字颜色', 'sakurairo'),
                'description' => esc_html__('帮助和页脚等文字的颜色', 'sakurairo'),
                'default'     => '#00000080',
                'transport'   => 'auto',
                'output'      => [['element' => ':root', 'property' => '--word-color-second']],
            ],
            [
                'type'        => 'color',
                'settings'    => 'active_color',
                'iro_key'     => 'active_color',
                'label'       => esc_html__('激活组件颜色', 'sakurairo'),
                'description' => esc_html__('鼠标悬浮链接以及按钮和高亮标签等部分的颜色', 'sakurairo'),
                'default'     => '#00b0f0',
                'transport'   => 'auto',
                'output'      => [['element' => ':root', 'property' => '--active-color']],
            ],
            [
                'type'      => 'color',
                'settings'  => 'code_block_background_color',
                'iro_key'   => 'code_block_background_color',
                'label'     => esc_html__('代码块背景色', 'sakurairo'),
                'default'   => '#e1e4e8',
                'transport' => 'auto',
                'output'    => [['element' => ':root', 'property' => '--code-background']],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'widget_transparency',
                'iro_key'   => 'widget_transparency',
                'label'     => esc_html__('组件透明度', 'sakurairo'),
                'default'   => 0.8,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 1, 'step' => 0.01],
                'output'    => [['element' => ':root', 'property' => '--widget-transparency']],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'background_transparency',
                'iro_key'   => 'background_transparency',
                'label'     => esc_html__('背景透明度', 'sakurairo'),
                'default'   => 0.8,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 1, 'step' => 0.01],
                'output'    => [['element' => ':root', 'property' => '--background-transparency']],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'background_blur',
                'iro_key'   => 'background_blur',
                'label'     => esc_html__('背景模糊度', 'sakurairo'),
                'default'   => 0.7,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 1, 'step' => 0.01],
                'output'    => [['element' => ':root', 'property' => '--background-blur']],
            ],

            [
                'type'     => 'custom',
                'settings' => 'iro_dark_mode_notice',
                'default'  => __('<p>深色模式</p>', 'sakurairo'),
            ],
            [
                'type'        => 'color',
                'settings'    => 'word_color_first_dark',
                'iro_key'     => 'word_color_first_dark',
                'label'       => esc_html__('主要文字颜色', 'sakurairo'),
                'description' => esc_html__('文章标题和正文内容等文字的颜色', 'sakurairo'),
                'default'     => '#CCCCCC',
                'transport'   => 'auto',
                'output'      => [['element' => ':root.dark', 'property' => '--word-color-first']],
            ],
            [
                'type'      => 'color',
                'settings'  => 'word_color_second_dark',
                'iro_key'   => 'word_color_second_dark',
                'label'     => esc_html__('次要文字颜色', 'sakurairo'),
                'default'   => '#7d7d7d',
                'transport' => 'auto',
                'output'    => [['element' => ':root.dark', 'property' => '--word-color-second']],
            ],
            [
                'type'      => 'color',
                'settings'  => 'active_color_dark',
                'iro_key'   => 'active_color_dark',
                'label'     => esc_html__('激活组件颜色', 'sakurairo'),
                'default'   => '#FCCD00',
                'transport' => 'auto',
                'output'    => [['element' => ':root.dark', 'property' => '--active-color']],
            ],
            [
                'type'      => 'color',
                'settings'  => 'code_block_background_color_dark',
                'iro_key'   => 'code_block_background_color_dark',
                'label'     => esc_html__('代码块背景色', 'sakurairo'),
                'default'   => '#24292e',
                'transport' => 'auto',
                'output'    => [['element' => ':root.dark', 'property' => '--code-background']],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'widget_transparency_dark',
                'iro_key'   => 'widget_transparency_dark',
                'label'     => esc_html__('组件透明度', 'sakurairo'),
                'default'   => 0.8,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 1, 'step' => 0.01],
                'output'    => [['element' => ':root.dark', 'property' => '--widget-transparency']],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'background_transparency_dark',
                'iro_key'   => 'background_transparency_dark',
                'label'     => esc_html__('背景透明度', 'sakurairo'),
                'default'   => 0.7,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 1, 'step' => 0.01],
                'output'    => [['element' => ':root.dark', 'property' => '--background-transparency']],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'image_bright_dark',
                'iro_key'   => 'image_bright_dark',
                'label'     => esc_html__('深色模式图像亮度', 'sakurairo'),
                'default'   => 0.7,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 1, 'step' => 0.01],
                'output'    => [['element' => ':root.dark', 'property' => '--image-bright']],
            ],
            [
                'type'        => 'textarea',
                'settings'    => 'theme_commemorate_mode_date',
                'iro_key'     => 'theme_commemorate_mode_date',
                'label'       => esc_html__('纪念模式日期', 'sakurairo'),
                'description' => esc_html__('一行一个，例如7-21，主题会在这些日期加上黑白滤镜', 'sakurairo'),
            ],
        ],
    ],

    // ====================字体设置====================
    [
        'id'          => 'iro_fonts',
        'title'       => esc_html__('字体设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_global',

        'fields'      => [
            [
                'type'        => 'slider',
                'settings'    => 'global_font_size',
                'iro_key'     => 'global_font_size',
                'label'       => esc_html__('字体大小', 'sakurairo'),
                'description' => esc_html__('此处以像素为单位，主题大部分组件会以此为基础调整自身字体大小，以实现等比缩放的效果', 'sakurairo'),
                'default'     => 16,
                'transport'   => 'auto',
                'choices'     => ['min' => 1, 'max' => 64, 'step' => 0.1],
                'output'      => [
                    [
                        'element'       => ':root',
                        'property'      => '--global-font-size',
                        'value_pattern' => '$px',
                    ],
                ],
            ],
            [
                'type'        => 'slider',
                'settings'    => 'global_font_weight',
                'iro_key'     => 'global_font_weight',
                'label'       => esc_html__('非强调文本字重', 'sakurairo'),
                'description' => esc_html__('建议的取值范围为 300-500', 'sakurairo'),
                'default'     => 300,
                'transport'   => 'auto',
                'choices'     => ['min' => 100, 'max' => 1000, 'step' => 10],
                'output'      => [['element' => ':root', 'property' => '--global-font-weight']],
            ],
            [
                'type'        => 'text',
                'settings'    => 'global_default_font',
                'iro_key'     => 'global_default_font',
                'label'       => esc_html__('默认字体', 'sakurairo'),
                'description' => esc_html__('填写字体名称，需在页面头部嵌入对应的字体样式', 'sakurairo'),
                'transport'   => 'auto',
                'output'      => [['element' => ':root', 'property' => '--global-font-family']],
            ],
            [
                'type'         => 'repeater',
                'settings'     => 'extra_fonts',
                'iro_key'      => 'extra_fonts',
                'label'        => esc_html__('额外字体', 'sakurairo'),
                'description'  => esc_html__('在此处添加外部字体，添加后即可在字体设置中使用对应的字体名称', 'sakurairo'),
                'row_label'    => [
                    'type'  => 'field',
                    'field' => 'font_name',
                    'value' => esc_html__('字体', 'sakurairo'),
                ],
                'button_label' => esc_html__('添加字体', 'sakurairo'),
                'fields'       => [
                    'font_name' => [
                        'type'  => 'text',
                        'label' => esc_html__('字体名称', 'sakurairo'),
                    ],
                    'link'      => [
                        'type'  => 'text',
                        'label' => esc_html__('字体链接', 'sakurairo'),
                    ],
                ],
            ],
        ],
    ],

    // ====================导航栏====================
    [
        'id'          => 'iro_nav',
        'title'       => esc_html__('导航栏', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_global',

        'fields'      => [
            [
                'type'      => 'image',
                'settings'  => 'nav_logo',
                'iro_key'   => 'nav_logo',
                'label'     => esc_html__('导航栏logo', 'sakurairo'),
                'transport' => 'postMessage',
                'js_vars'   => [
                    [
                        'element'  => '.site-branding img.logo',
                        'function' => 'html',
                        'attr'     => 'src',
                    ],
                ],
            ],
            [
                'type'      => 'text',
                'settings'  => 'nav_title',
                'iro_key'   => 'nav_title',
                'label'     => esc_html__('导航栏标题', 'sakurairo'),
                'transport' => 'postMessage',
                'js_vars'   => [
                    [
                        'element'  => '.site-title',
                        'function' => 'html',
                    ],
                ],
            ],
            [
                'type'      => 'text',
                'settings'  => 'nav_title_font',
                'iro_key'   => 'nav_title_font',
                'label'     => esc_html__('导航栏标题字体', 'sakurairo'),
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => '.site-title',
                        'property'      => 'font-family',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
            [
                'type'      => 'text',
                'settings'  => 'nav_option_font',
                'iro_key'   => 'nav_option_font',
                'label'     => esc_html__('导航栏选项字体', 'sakurairo'),
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => '.menu-wrapper .menu',
                        'property'      => 'font-family',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
            [
                'type'      => 'select',
                'settings'  => 'navbar_distribution',
                'iro_key'   => 'navbar_distribution',
                'label'     => esc_html__('导航栏选项分布位置', 'sakurairo'),
                'default'   => 'right',
                'choices'   => [
                    'left'         => esc_html__('左', 'sakurairo'),
                    'center'       => esc_html__('中', 'sakurairo'),
                    'right'        => esc_html__('右', 'sakurairo'),
                    'space-evenly' => esc_html__('均匀', 'sakurairo'),
                ],
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => '.menu-wrapper .menu',
                        'property'      => 'justify-content',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'navbar_option_margin',
                'iro_key'   => 'navbar_option_margin',
                'label'     => esc_html__('导航栏选项间距', 'sakurairo'),
                'default'   => 0.3,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 2, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.menu-wrapper .menu > li',
                        'property'      => 'margin',
                        'value_pattern' => '0 $rem !important',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'nav_menu_cover_radius',
                'iro_key'   => 'nav_menu_cover_radius',
                'label'     => esc_html__('导航栏菜单圆角', 'sakurairo'),
                'default'   => 0.6,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 2, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.menu-wrapper .sub-menu',
                        'property'      => 'border-radius',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'    => 'switch',
                'settings' => 'nav_user_menu',
                'iro_key' => 'nav_user_menu',
                'label'   => esc_html__('导航栏用户栏', 'sakurairo'),
                'description' => esc_html__('默认开启，将显示用户头像与菜单', 'sakurairo'),
                'default' => true,
            ],
        ],
    ],

    // ====================前台设置====================
    [
        'id'          => 'iro_frontend',
        'title'       => esc_html__('前台设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_global',

        'fields'      => [
            [
                'type'      => 'slider',
                'settings'  => 'widget_button_radius',
                'iro_key'   => 'widget_button_radius',
                'label'     => esc_html__('工具栏按钮圆角', 'sakurairo'),
                'default'   => 0.6,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 3, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.site-widget',
                        'property'      => '--widget_button_radius',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'widget_panel_radius',
                'iro_key'   => 'widget_panel_radius',
                'label'     => esc_html__('工具栏面板圆角', 'sakurairo'),
                'default'   => 0.6,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 2, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.site-widget',
                        'property'      => '--widget_panel_radius',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'      => 'text',
                'settings'  => 'widget_font',
                'iro_key'   => 'widget_font',
                'label'     => esc_html__('工具栏字体', 'sakurairo'),
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => '.site-widget',
                        'property'      => 'font-family',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
            [
                'type'        => 'switch',
                'settings'    => 'widget_wordpress_widget',
                'iro_key'     => 'widget_wordpress_widget',
                'label'       => esc_html__('工具栏wordpress组件', 'sakurairo'),
                'description' => esc_html__('启用后将会显示wordpress可编辑工具栏，你可以前往「外观-小工具」编辑', 'sakurairo'),
                'default'     => false,
            ],
            [
                'type'    => 'switch',
                'settings' => 'widget_font_switch',
                'iro_key' => 'widget_font_switch',
                'label'   => esc_html__('工具栏字体切换按钮', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'         => 'repeater',
                'settings'     => 'widget_font_choice',
                'iro_key'      => 'widget_font_choice',
                'label'        => esc_html__('工具栏可选字体', 'sakurairo'),
                'description'  => esc_html__('请使用有效的字体名称，需要在全局字体设置中添加对应名称的额外字体才能生效', 'sakurairo'),
                'row_label'    => [
                    'type'  => 'field',
                    'field' => 'name',
                    'value' => esc_html__('字体', 'sakurairo'),
                ],
                'button_label' => esc_html__('添加字体', 'sakurairo'),
                'fields'       => [
                    'name' => [
                        'type'  => 'text',
                        'label' => esc_html__('字体名称', 'sakurairo'),
                    ],
                ],
                'active_callback' => [
                    [
                        'setting'  => 'widget_font_switch',
                        'operator' => '==',
                        'value'    => true,
                    ],
                ],
            ],
            [
                'type'        => 'image',
                'settings'    => 'frontend_default_background',
                'iro_key'     => 'frontend_default_background',
                'label'       => esc_html__('前台默认背景', 'sakurairo'),
                'transport'   => 'auto',
                'output'      => [
                    [
                        'element'       => 'body',
                        'property'      => 'background-image',
                        'value_pattern' => 'url($)',
                    ],
                ],
            ],
            [
                'type'    => 'select',
                'settings' => 'frontend_background_fill_mode',
                'iro_key' => 'frontend_background_fill_mode',
                'label'   => esc_html__('前台背景填充模式', 'sakurairo'),
                'description' => esc_html__('根据你选择的图片类型选择合适的填充方案，插画为缩放至填充满，纹理为复制并铺满', 'sakurairo'),
                'choices' => [
                    'pattern' => esc_html__('插画', 'sakurairo'),
                    'texture' => esc_html__('纹理', 'sakurairo'),
                ],
            ],
            [
                'type'    => 'select',
                'settings' => 'frontend_particle',
                'iro_key' => 'frontend_particle',
                'label'   => esc_html__('前台背景粒子特效', 'sakurairo'),
                'choices' => [
                    'off'    => esc_html__('关闭', 'sakurairo'),
                    'sakura' => esc_html__('樱花', 'sakurairo'),
                    'snow'   => esc_html__('雪', 'sakurairo'),
                    'custom' => esc_html__('自定义', 'sakurairo'),
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'frontend_particle_builtin_amount',
                'iro_key'   => 'frontend_particle_builtin',
                'iro_subkey' => 'amount',
                'label'     => esc_html__('粒子数量', 'sakurairo'),
                'default'   => 30,
                'choices'   => ['min' => 10, 'max' => 100, 'step' => 1],
                'active_callback' => [
                    [
                        'setting'  => 'frontend_particle',
                        'operator' => 'contains',
                        'value'    => 'sakura,snow',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'frontend_particle_builtin_minsize',
                'iro_key'   => 'frontend_particle_builtin',
                'iro_subkey' => 'minsize',
                'label'     => esc_html__('粒子最小大小', 'sakurairo'),
                'default'   => 10,
                'choices'   => ['min' => 1, 'max' => 100, 'step' => 1],
                'active_callback' => [
                    [
                        'setting'  => 'frontend_particle',
                        'operator' => 'contains',
                        'value'    => 'sakura,snow',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'frontend_particle_builtin_maxsize',
                'iro_key'   => 'frontend_particle_builtin',
                'iro_subkey' => 'maxsize',
                'label'     => esc_html__('粒子最大大小', 'sakurairo'),
                'default'   => 30,
                'choices'   => ['min' => 30, 'max' => 100, 'step' => 1],
                'active_callback' => [
                    [
                        'setting'  => 'frontend_particle',
                        'operator' => 'contains',
                        'value'    => 'sakura,snow',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'frontend_particle_builtin_speed',
                'iro_key'   => 'frontend_particle_builtin',
                'iro_subkey' => 'speed',
                'label'     => esc_html__('粒子速度', 'sakurairo'),
                'default'   => 10,
                'choices'   => ['min' => 1, 'max' => 100, 'step' => 1],
                'active_callback' => [
                    [
                        'setting'  => 'frontend_particle',
                        'operator' => 'contains',
                        'value'    => 'sakura,snow',
                    ],
                ],
            ],
            [
                'type'        => 'code',
                'settings'    => 'particle_config',
                'iro_key'     => 'particle_config',
                'label'       => esc_html__('自定义粒子特效实现', 'sakurairo'),
                'description' => esc_html__('参考 tsParticle 的配置格式填写 JSON', 'sakurairo'),
                'choices'     => ['language' => 'json'],
                'active_callback' => [
                    [
                        'setting'  => 'frontend_particle',
                        'operator' => '==',
                        'value'    => 'custom',
                    ],
                ],
            ],
        ],
    ],

    // ====================页尾设置====================
    [
        'id'          => 'iro_footer',
        'title'       => esc_html__('页尾设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_global',

        'fields'      => [
            [
                'type'    => 'switch',
                'settings' => 'footer_sakura',
                'iro_key' => 'footer_sakura',
                'label'   => esc_html__('页尾樱花', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'      => 'text',
                'settings'  => 'footer_font',
                'iro_key'   => 'footer_font',
                'label'     => esc_html__('页尾字体', 'sakurairo'),
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => '.site-footer',
                        'property'      => 'font-family',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
            [
                'type'        => 'code',
                'settings'    => 'footer_html',
                'iro_key'     => 'footer_html',
                'label'       => esc_html__('页尾html代码', 'sakurairo'),
                'description' => esc_html__('可以在此处编写页脚内容，也可以加入能接受延迟加载的统计代码，请确保它们安全', 'sakurairo'),
                'choices'     => ['language' => 'html'],
                'transport'   => 'postMessage',
                'js_vars'     => [
                    [
                        'element'  => '.site-footer .site-info',
                        'function' => 'html',
                    ],
                ],
            ],
            [
                'type'    => 'select',
                'settings' => 'footer_hitokoto_select',
                'iro_key' => 'footer_hitokoto_select',
                'label'   => esc_html__('页脚一言', 'sakurairo'),
                'choices' => [
                    'off'    => esc_html__('关闭', 'sakurairo'),
                    'api'    => esc_html__('总是使用API', 'sakurairo'),
                    'custom' => esc_html__('总是自定义', 'sakurairo'),
                    'both'   => esc_html__('各一半', 'sakurairo'),
                ],
            ],
            [
                'type'        => 'textarea',
                'settings'    => 'footer_hitokoto_api',
                'iro_key'     => 'footer_hitokoto_api',
                'label'       => esc_html__('一言API地址', 'sakurairo'),
                'description' => esc_html__('填写地址，格式为 JavaScript 数组', 'sakurairo'),
                'default'     => '["https://v1.hitokoto.cn/","https://v1.hitokoto.cn/"]',
                'active_callback' => [
                    [
                        'setting'  => 'footer_hitokoto_select',
                        'operator' => '!=',
                        'value'    => 'off',
                    ],
                ],
            ],
            [
                'type'        => 'textarea',
                'settings'    => 'footer_hitokoto_custom',
                'iro_key'     => 'footer_hitokoto_custom',
                'label'       => esc_html__('一言自定义内容', 'sakurairo'),
                'description' => esc_html__('一行一句，尽量不要出现特殊字符。', 'sakurairo'),
                'active_callback' => [
                    [
                        'setting'  => 'footer_hitokoto_select',
                        'operator' => '!=',
                        'value'    => 'off',
                    ],
                ],
            ],
        ],
    ],

    // ====================搜索设置====================
    [
        'id'          => 'iro_search',
        'title'       => esc_html__('搜索设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_global',

        'fields'      => [
            [
                'type'    => 'switch',
                'settings' => 'nav_menu_search_switch',
                'iro_key' => 'nav_menu_search_switch',
                'label'   => esc_html__('导航栏搜索按钮', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'    => 'switch',
                'settings' => 'search_filter',
                'iro_key' => 'search_filter',
                'label'   => esc_html__('搜索页过滤栏', 'sakurairo'),
                'default' => false,
            ],
            [
                'type'    => 'switch',
                'settings' => 'search_for_shuoshuo',
                'iro_key' => 'search_for_shuoshuo',
                'label'   => esc_html__('在搜索结果中显示说说', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'    => 'switch',
                'settings' => 'search_for_pages',
                'iro_key' => 'search_for_pages',
                'label'   => esc_html__('在搜索结果中显示页面', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'    => 'switch',
                'settings' => 'search_pages_can_only_admins',
                'iro_key' => 'search_pages_can_only_admins',
                'label'   => esc_html__('只有管理员可以搜索页面', 'sakurairo'),
                'default' => false,
                'active_callback' => [
                    [
                        'setting'  => 'search_for_pages',
                        'operator' => '==',
                        'value'    => true,
                    ],
                ],
            ],
            [
                'type'    => 'switch',
                'settings' => 'search_for_pinned_posts',
                'iro_key' => 'search_for_pinned_posts',
                'label'   => esc_html__('在搜索结果中置顶置顶文章', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'        => 'text',
                'settings'    => 'search_results_custom_exclude',
                'iro_key'     => 'search_results_custom_exclude',
                'label'       => esc_html__('搜索结果排除', 'sakurairo'),
                'description' => esc_html__('从搜索结果中排除自定义ID内容，多个ID请使用英文逗号分隔', 'sakurairo'),
            ],
        ],
    ],

    // ====================其他设置====================
    [
        'id'          => 'iro_global_others',
        'title'       => esc_html__('其他设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_global',

        'fields'      => [
            [
                'type'        => 'switch',
                'settings'    => 'pjax',
                'iro_key'     => 'pjax',
                'label'       => esc_html__('PJAX', 'sakurairo'),
                'description' => esc_html__('启用后前台站内跳转将不会刷新页面，体验更好，但与第三方内容可能存在兼容性问题，请按需使用', 'sakurairo'),
                'default'     => true,
            ],
            [
                'type'        => 'textarea',
                'settings'    => 'pjax_keep_loading',
                'iro_key'     => 'pjax_keep_loading',
                'label'       => esc_html__('PJAX启用后仍需在页脚刷新的资源', 'sakurairo'),
                'description' => esc_html__('启用PJAX后页脚的自定义内容不会在页面跳转时刷新，可在此处填入需要刷新的JavaScript与样式表路径，一行一个', 'sakurairo'),
                'active_callback' => [
                    [
                        'setting'  => 'pjax',
                        'operator' => '==',
                        'value'    => true,
                    ],
                ],
            ],
            [
                'type'        => 'switch',
                'settings'    => 'top_scroll_progress',
                'iro_key'     => 'top_scroll_progress',
                'label'       => esc_html__('顶部阅读进度条', 'sakurairo'),
                'description' => esc_html__('开启后会在页面顶部显示进度条，进度取决于当前页面的滚动进度', 'sakurairo'),
                'default'     => true,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'top_loading_progress',
                'iro_key'     => 'top_loading_progress',
                'label'       => esc_html__('顶部加载进度条', 'sakurairo'),
                'description' => esc_html__('开启后会在页面顶部显示进度条，进度取决于下一页的加载进度', 'sakurairo'),
                'default'     => true,
            ],
            [
                'type'    => 'radio',
                'settings' => 'pagination_mode',
                'iro_key' => 'pagination_mode',
                'label'   => esc_html__('文章列表分页导航方式', 'sakurairo'),
                'default' => 'pagination',
                'choices' => [
                    'ajax'       => esc_html__('滚动加载', 'sakurairo'),
                    'pagination' => esc_html__('传统分页', 'sakurairo'),
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'pagination_ajax_wait',
                'iro_key'   => 'pagination_ajax_wait',
                'label'     => esc_html__('ajax自动加载等待时间', 'sakurairo'),
                'default'   => 3,
                'choices'   => ['min' => 0, 'max' => 10, 'step' => 1],
                'active_callback' => [
                    [
                        'setting'  => 'pagination_mode',
                        'operator' => '==',
                        'value'    => 'ajax',
                    ],
                ],
            ],
            [
                'type'    => 'image',
                'settings' => 'missing_avatars_placeholder',
                'iro_key' => 'missing_avatars_placeholder',
                'label'   => esc_html__('站内头像占位', 'sakurairo'),
            ],
            [
                'type'    => 'image',
                'settings' => 'missing_images_placeholder',
                'iro_key' => 'missing_images_placeholder',
                'label'   => esc_html__('站内图片占位', 'sakurairo'),
            ],
            [
                'type'    => 'select',
                'settings' => 'lightbox',
                'iro_key' => 'lightbox',
                'label'   => esc_html__('lightbox', 'sakurairo'),
                'description' => esc_html__('请选择你需要使用的灯箱效果，wordpress在6.4后已正式支持灯箱效果，此处仅提供另一种可选的效果', 'sakurairo'),
                'default' => 'medium_zoom',
                'choices' => [
                    'off'         => esc_html__('off', 'sakurairo'),
                    'medium_zoom' => 'Medium Zoom',
                ],
            ],
            [
                'type'    => 'select',
                'settings' => 'code_highlight_method',
                'iro_key' => 'code_highlight_method',
                'label'   => esc_html__('代码高亮方式', 'sakurairo'),
                'default' => 'hljs',
                'choices' => [
                    'off'  => esc_html__('关闭', 'sakurairo'),
                    'hljs' => 'highlight.js',
                ],
            ],
            [
                'type'        => 'switch',
                'settings'    => 'code_katex',
                'iro_key'     => 'code_katex',
                'label'       => esc_html__('启用公式支持', 'sakurairo'),
                'description' => esc_html__('启用主题公式支持，使用Katex，需要写入markdown区块才能渲染', 'sakurairo'),
                'default'     => true,
            ],
        ],
    ],

    // ====================基本设置====================
    [
        'id'          => 'iro_basic',
        'title'       => esc_html__('基本设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_global',

        'fields'      => [
            [
                'type'        => 'image',
                'settings'    => 'favicon_link',
                'iro_key'     => 'favicon_link',
                'label'       => esc_html__('站点图标', 'sakurairo'),
                'description' => esc_html__('填写链接，它将会出现在浏览器标签页的标题旁边', 'sakurairo'),
                'default'     => $vision_resource_basepath . 'basic/favicon.ico',
                'transport'   => 'postMessage',
                'js_vars'     => [
                    [
                        'element'  => 'link[rel="shortcut icon"]',
                        'function' => 'html',
                        'attr'     => 'href',
                    ],
                ],
            ],
            [
                'type'        => 'text',
                'settings'    => 'fontawesome_source',
                'iro_key'     => 'fontawesome_source',
                'label'       => esc_html__('Fontawesome源', 'sakurairo'),
                'description' => esc_html__('Fontawesome图标的加载地址，同时用于后台主题设置框架图标与前台图标', 'sakurairo'),
                'default'     => 'https://s4.zstatic.net/ajax/libs/font-awesome/6.7.2/css/all.min.css',
            ],
            [
                'type'        => 'switch',
                'settings'    => 'fontawesome_source_add_to_frontend',
                'iro_key'     => 'fontawesome_source_add_to_frontend',
                'label'       => esc_html__('将fontawesome源载入到前台', 'sakurairo'),
                'description' => esc_html__('如果你需要使用自定义fontawesome图标，可以开启这个选项', 'sakurairo'),
                'default'     => false,
            ],
        ],
    ],

    // ====================封面设置====================
    [
        'id'          => 'iro_cover_display',
        'title'       => esc_html__('封面设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_cover',

        'fields'      => [
            [
                'type'    => 'switch',
                'settings' => 'cover_switch',
                'iro_key' => 'cover_switch',
                'label'   => esc_html__('封面开关', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'        => 'slider',
                'settings'    => 'cover_height',
                'iro_key'     => 'cover_height',
                'label'       => esc_html__('封面高度', 'sakurairo'),
                'description' => esc_html__('封面占可视窗口的百分比', 'sakurairo'),
                'default'     => 100,
                'transport'   => 'auto',
                'choices'     => ['min' => 1, 'max' => 100, 'step' => 1],
                'active_callback' => [
                    [
                        'setting'  => 'cover_switch',
                        'operator' => '==',
                        'value'    => true,
                    ],
                ],
                'output'      => [
                    [
                        'element'       => '.homepage-cover',
                        'property'      => '--cover-height',
                        'value_pattern' => '$dvh !important',
                    ],
                ],
            ],
            [
                'type'    => 'select',
                'settings' => 'cover_focus_style',
                'iro_key' => 'cover_focus_style',
                'label'   => esc_html__('首页聚焦显示内容', 'sakurairo'),
                'choices' => [
                    'off'          => esc_html__('无', 'sakurairo'),
                    'avatar'       => esc_html__('头像', 'sakurairo'),
                    'text'         => esc_html__('文字', 'sakurairo'),
                    'mashiro_text' => esc_html__('Mashiro特效文字', 'sakurairo'),
                ],
                'active_callback' => [
                    [
                        'setting'  => 'cover_switch',
                        'operator' => '==',
                        'value'    => true,
                    ],
                ],
            ],
            [
                'type'        => 'image',
                'settings'    => 'cover_avatar',
                'iro_key'     => 'cover_avatar',
                'label'       => esc_html__('个人头像', 'sakurairo'),
                'description' => esc_html__('最佳宽高比为1:1', 'sakurairo'),
            ],
            [
                'type'        => 'text',
                'settings'    => 'cover_title_text',
                'iro_key'     => 'cover_title',
                'iro_subkey'  => 'text',
                'label'       => esc_html__('封面文字内容', 'sakurairo'),
                'transport'   => 'postMessage',
                'js_vars'     => [
                    [
                        'element'  => '.cover-title',
                        'function' => 'html',
                    ],
                ],
            ],
            [
                'type'      => 'text',
                'settings'  => 'cover_title_font',
                'iro_key'   => 'cover_title',
                'iro_subkey' => 'font',
                'label'     => esc_html__('封面文字字体', 'sakurairo'),
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => '.cover-title',
                        'property'      => 'font-family',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'cover_title_size',
                'iro_key'   => 'cover_title',
                'iro_subkey' => 'size',
                'label'     => esc_html__('封面文字大小', 'sakurairo'),
                'default'   => 5,
                'transport' => 'auto',
                'choices'   => ['min' => 1, 'max' => 9, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.cover-title',
                        'property'      => 'font-size',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'      => 'color',
                'settings'  => 'cover_title_color',
                'iro_key'   => 'cover_title',
                'iro_subkey' => 'color',
                'label'     => esc_html__('封面文字颜色', 'sakurairo'),
                'default'   => '#FFF',
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => '.cover-title',
                        'property'      => 'color',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
            [
                'type'    => 'switch',
                'settings' => 'cover_infor_bar_switch',
                'iro_key' => 'cover_infor_bar_switch',
                'label'   => esc_html__('封面信息栏开关', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'      => 'slider',
                'settings'  => 'cover_infor_bar_radius',
                'iro_key'   => 'cover_infor_bar_radius',
                'label'     => esc_html__('封面信息栏圆角', 'sakurairo'),
                'default'   => 1,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 5, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.homepage-cover .socials',
                        'property'      => 'border-radius',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'      => 'text',
                'settings'  => 'cover_signature_text',
                'iro_key'   => 'cover_signature',
                'iro_subkey' => 'text',
                'label'     => esc_html__('封面签名内容', 'sakurairo'),
                'transport' => 'postMessage',
                'js_vars'   => [
                    [
                        'element'  => '.homepage-cover .signature p',
                        'function' => 'html',
                    ],
                ],
            ],
            [
                'type'      => 'text',
                'settings'  => 'cover_signature_font',
                'iro_key'   => 'cover_signature',
                'iro_subkey' => 'font',
                'label'     => esc_html__('封面签名字体', 'sakurairo'),
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => '.homepage-cover .signature p',
                        'property'      => 'font-family',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'cover_signature_size',
                'iro_key'   => 'cover_signature',
                'iro_subkey' => 'size',
                'label'     => esc_html__('封面签名大小', 'sakurairo'),
                'default'   => 1,
                'transport' => 'auto',
                'choices'   => ['min' => 0.1, 'max' => 2, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.homepage-cover .signature p',
                        'property'      => 'font-size',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'    => 'switch',
                'settings' => 'cover_typedjs',
                'iro_key' => 'cover_typedjs',
                'label'   => esc_html__('封面打字机效果', 'sakurairo'),
                'default' => true,
            ],
            [
                'type'    => 'switch',
                'settings' => 'cover_typedjs_mark',
                'iro_key' => 'cover_typedjs_mark',
                'label'   => esc_html__('封面打字机引号', 'sakurairo'),
                'default' => false,
            ],
            [
                'type'        => 'text',
                'settings'    => 'cover_typedjs_placeholder',
                'iro_key'     => 'cover_typedjs_placeholder',
                'label'       => esc_html__('封面打字机占位符', 'sakurairo'),
                'default'     => '疯狂造句中......',
                'transport'   => 'postMessage',
                'js_vars'     => [
                    [
                        'element'  => '#typed',
                        'function' => 'html',
                    ],
                ],
            ],
            [
                'type'        => 'code',
                'settings'    => 'cover_typedjs_config',
                'iro_key'     => 'cover_typedjs_config',
                'label'       => esc_html__('封面打字机配置', 'sakurairo'),
                'description' => esc_html__('参考 typed.js 的配置格式填写 JSON', 'sakurairo'),
                'choices'     => ['language' => 'json'],
            ],
        ],
    ],

    // ====================社交区域====================
    [
        'id'          => 'iro_cover_social',
        'title'       => esc_html__('社交区域', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_cover',

        'fields'      => [
            [
                'type'    => 'radio_image',
                'settings' => 'cover_social_icon',
                'iro_key' => 'cover_social_icon',
                'label'   => esc_html__('社交栏图标包', 'sakurairo'),
                'description' => esc_html__('选择你喜欢的图标包。图标包引用信息详见关于主题', 'sakurairo'),
                'choices' => [
                    'fluent_design' => $vision_resource_basepath . 'options/display_icon_fd.gif',
                    'muh2'          => $vision_resource_basepath . 'options/display_icon_h2.gif',
                    'flat_colorful' => $vision_resource_basepath . 'options/display_icon_fc.gif',
                ],
            ],
            [
                'type'         => 'repeater',
                'settings'     => 'cover_social_displays',
                'iro_key'      => 'cover_social_displays',
                'label'        => esc_html__('社交区域展示内容', 'sakurairo'),
                'row_label'    => [
                    'type'  => 'field',
                    'field' => 'title',
                    'value' => esc_html__('社交条目', 'sakurairo'),
                ],
                'button_label' => esc_html__('添加社交条目', 'sakurairo'),
                'fields'       => [
                    'select' => [
                        'type'    => 'select',
                        'label'   => esc_html__('显示的图标', 'sakurairo'),
                        'choices' => [
                            'qq'            => esc_html__('QQ', 'sakurairo'),
                            'wechat'        => esc_html__('微信', 'sakurairo'),
                            'bilibili'      => esc_html__('bilibili', 'sakurairo'),
                            'netease_music' => esc_html__('网易云音乐', 'sakurairo'),
                            'sina'          => esc_html__('新浪', 'sakurairo'),
                            'github'        => esc_html__('Github', 'sakurairo'),
                            'telegram'      => esc_html__('Telegram', 'sakurairo'),
                            'steam'         => esc_html__('Steam', 'sakurairo'),
                            'youtube'       => esc_html__('Youtube', 'sakurairo'),
                            'instgram'      => esc_html__('instgram', 'sakurairo'),
                            'tiktok'        => esc_html__('抖音', 'sakurairo'),
                            'xiaohongshu'   => esc_html__('小红书', 'sakurairo'),
                            'discord'       => esc_html__('Discord', 'sakurairo'),
                            'zhihu'         => esc_html__('知乎', 'sakurairo'),
                            'linkedin'      => esc_html__('领英', 'sakurairo'),
                            'twitter'       => esc_html__('推特/X', 'sakurairo'),
                            'facebook'      => esc_html__('facebook', 'sakurairo'),
                            'email'         => esc_html__('邮箱', 'sakurairo'),
                            'custom'        => esc_html__('自定义', 'sakurairo'),
                        ],
                    ],
                    'icon'   => [
                        'type'  => 'upload',
                        'label' => esc_html__('自定义图标', 'sakurairo'),
                    ],
                    'qrcode' => [
                        'type'  => 'upload',
                        'label' => esc_html__('二维码', 'sakurairo'),
                    ],
                    'title'  => [
                        'type'  => 'text',
                        'label' => esc_html__('标题', 'sakurairo'),
                    ],
                    'link'   => [
                        'type'  => 'text',
                        'label' => esc_html__('链接', 'sakurairo'),
                    ],
                ],
            ],
        ],
    ],

    // ====================封面其他====================
    [
        'id'          => 'iro_cover_other',
        'title'       => esc_html__('封面背景与视频', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_cover',

        'fields'      => [
            [
                'type'        => 'text',
                'settings'    => 'cover_random_pic_url_pc',
                'iro_key'     => 'cover_random_pic_url_pc',
                'label'       => esc_html__('PC封面图片地址', 'sakurairo'),
                'description' => esc_html__('填写图片地址或者随机图API', 'sakurairo'),
                'transport'   => 'auto',
                'output'      => [
                    [
                        'element'       => ':root',
                        'property'      => '--cover-background-img-pc',
                        'value_pattern' => 'url($)',
                    ],
                ],
            ],
            [
                'type'      => 'text',
                'settings'  => 'cover_random_pic_url_mb',
                'iro_key'   => 'cover_random_pic_url_mb',
                'label'     => esc_html__('移动端封面图片地址', 'sakurairo'),
                'transport' => 'auto',
                'output'    => [
                    [
                        'element'       => ':root',
                        'property'      => '--cover-background-img-mb',
                        'value_pattern' => 'url($)',
                    ],
                ],
            ],
            [
                'type'        => 'switch',
                'settings'    => 'cover_as_background',
                'iro_key'     => 'cover_as_background',
                'label'       => esc_html__('前台背景一体化', 'sakurairo'),
                'description' => esc_html__('开启后封面背景将会变透明，以实现前台背景与封面的一体化效果', 'sakurairo'),
                'default'     => false,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'post_cover_as_background',
                'iro_key'     => 'post_cover_as_background',
                'label'       => esc_html__('使用特色图片作为背景', 'sakurairo'),
                'description' => esc_html__('在文章页将会使用特色图片作为背景', 'sakurairo'),
                'default'     => false,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'cover_video',
                'iro_key'     => 'cover_video',
                'label'       => esc_html__('封面视频', 'sakurairo'),
                'description' => esc_html__('用视频代替封面图片', 'sakurairo'),
                'default'     => false,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'cover_video_loop',
                'iro_key'     => 'cover_video_loop',
                'label'       => esc_html__('封面视频循环', 'sakurairo'),
                'description' => esc_html__('开启后视频将会循环播放', 'sakurairo'),
                'default'     => false,
            ],
            [
                'type'        => 'upload',
                'settings'    => 'cover_video_source',
                'iro_key'     => 'cover_video_source',
                'label'       => esc_html__('视频URL地址', 'sakurairo'),
                'description' => esc_html__('视频的文件地址', 'sakurairo'),
                'choices'     => ['button_label' => esc_html__('选择视频', 'sakurairo')],
            ],
        ],
    ],

    // ====================首页布局====================
    [
        'id'          => 'iro_homepages',
        'title'       => esc_html__('首页布局', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_homepage',

        'fields'      => [
            [
                'type'    => 'sortable',
                'settings' => 'homepage_components',
                'iro_key' => 'homepage_components',
                'label'   => esc_html__('首页布局', 'sakurairo'),
                'description' => esc_html__('选择你想在首页展示的组件，它们将按照此处的顺序显示', 'sakurairo'),
                'default' => ['show', 'post_list'],
                'choices' => [
                    'show'        => esc_html__('展示区域', 'sakurairo'),
                    'post_list'   => esc_html__('最新文章', 'sakurairo'),
                    'static_page' => esc_html__('自定义页面', 'sakurairo'),
                ],
            ],
            [
                'type'    => 'dropdown_pages',
                'settings' => 'homepage_static_page_id',
                'iro_key' => 'homepage_static_page_id',
                'label'   => esc_html__('自定义页面', 'sakurairo'),
                'active_callback' => [
                    [
                        'setting'  => 'homepage_components',
                        'operator' => 'contains',
                        'value'    => 'static_page',
                    ],
                ],
            ],
            [
                'type'     => 'text',
                'settings' => 'homepage_show_title_icon',
                'iro_key'  => 'homepage_show_title',
                'iro_subkey' => 'icon',
                'label'    => esc_html__('展示区域标题图标', 'sakurairo'),
                'description' => esc_html__('图标元素的类名，自定义前需在页面头部嵌入自定义的图标集样式，例如fontawesome，否则会不显示', 'sakurairo'),
                'default'  => 'fa-icon-solid fa-laptop',
            ],
            [
                'type'     => 'text',
                'settings' => 'homepage_show_title_text',
                'iro_key'  => 'homepage_show_title',
                'iro_subkey' => 'text',
                'label'    => esc_html__('展示区域标题内容', 'sakurairo'),
                'default'  => 'Display',
            ],
            [
                'type'     => 'text',
                'settings' => 'homepage_post_list_title_icon',
                'iro_key'  => 'homepage_post_list_title',
                'iro_subkey' => 'icon',
                'label'    => esc_html__('文章区域标题图标', 'sakurairo'),
                'default'  => 'fa-icon-regular fa-bookmark',
            ],
            [
                'type'     => 'text',
                'settings' => 'homepage_post_list_title_text',
                'iro_key'  => 'homepage_post_list_title',
                'iro_subkey' => 'text',
                'label'    => esc_html__('文章区域标题内容', 'sakurairo'),
                'default'  => 'Article',
            ],
            [
                'type'    => 'radio_image',
                'settings' => 'homepage_component_title_align',
                'iro_key' => 'homepage_component_title_align',
                'label'   => esc_html__('首页区域标题位置', 'sakurairo'),
                'default' => 'left',
                'transport' => 'auto',
                'choices' => [
                    'left'   => $vision_resource_basepath . 'options/area_title_text_left.webp',
                    'right'  => $vision_resource_basepath . 'options/area_title_text_right.webp',
                    'center' => $vision_resource_basepath . 'options/area_title_text_center.webp',
                ],
                'output'  => [
                    [
                        'element'       => '.page-home',
                        'property'      => '--block-title-position',
                        'value_pattern' => '$ !important',
                    ],
                ],
            ],
        ],
    ],

    // ====================展示区域====================
    [
        'id'          => 'iro_display_aera',
        'title'       => esc_html__('展示区域', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_homepage',

        'fields'      => [
            [
                'type'         => 'repeater',
                'settings'     => 'show_area_content',
                'iro_key'      => 'show_area_content',
                'label'        => esc_html__('展示区域内容', 'sakurairo'),
                'row_label'    => [
                    'type'  => 'field',
                    'field' => 'title',
                    'value' => esc_html__('展示卡片', 'sakurairo'),
                ],
                'button_label' => esc_html__('添加展示卡片', 'sakurairo'),
                'fields'       => [
                    'img'         => [
                        'type'  => 'upload',
                        'label' => esc_html__('图片链接', 'sakurairo'),
                    ],
                    'title'       => [
                        'type'  => 'text',
                        'label' => esc_html__('标题', 'sakurairo'),
                    ],
                    'description' => [
                        'type'  => 'text',
                        'label' => esc_html__('描述', 'sakurairo'),
                    ],
                    'link'        => [
                        'type'  => 'text',
                        'label' => esc_html__('跳转链接', 'sakurairo'),
                    ],
                ],
                'default'      => [
                    [
                        'img'         => $vision_resource_basepath . 'series/exhibition2.webp',
                        'title'       => '夏霞',
                        'description' => 'あの儚く散る花火の下で、馬鹿みたいに永遠を誓った',
                        'link'        => '',
                    ],
                    [
                        'img'         => $vision_resource_basepath . 'series/exhibition3.webp',
                        'title'       => '雪冴ゆる',
                        'description' => '独りぽっちの冴えない僕を暗闇から連れ出してくれた',
                        'link'        => '',
                    ],
                ],
            ],
        ],
    ],

    // ====================文章区域====================
    [
        'id'          => 'iro_article_aera',
        'title'       => esc_html__('文章区域', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_homepage',

        'fields'      => [
            [
                'type'    => 'radio_image',
                'settings' => 'post_card_with_image_design',
                'iro_key' => 'post_card_with_image_design',
                'label'   => esc_html__('文章区域卡片设计', 'sakurairo'),
                'description' => esc_html__('你可以选择信件设计或者票券设计', 'sakurairo'),
                'choices' => [
                    'letter' => $vision_resource_basepath . 'options/post_list_design_letter.webp',
                    'ticket' => $vision_resource_basepath . 'options/post_list_design_ticket.webp',
                ],
            ],
            [
                'type'    => 'sortable',
                'settings' => 'post_card_metas',
                'iro_key' => 'post_card_metas',
                'label'   => esc_html__('文章卡片显示信息', 'sakurairo'),
                'default' => ['category', 'comment_count', 'views'],
                'choices' => [
                    'author'        => esc_html__('作者', 'sakurairo'),
                    'category'      => esc_html__('分类', 'sakurairo'),
                    'comment_count' => esc_html__('评论数量', 'sakurairo'),
                    'views'         => esc_html__('浏览量', 'sakurairo'),
                ],
            ],
            [
                'type'    => 'radio',
                'settings' => 'post_card_image',
                'iro_key' => 'post_card_image',
                'label'   => esc_html__('文章区域装饰特色图片选项', 'sakurairo'),
                'choices' => [
                    'always_with_cover'  => esc_html__('始终且使用封面API', 'sakurairo'),
                    'always_alone'       => esc_html__('始终且使用独立API', 'sakurairo'),
                    'only_feather_image' => esc_html__('仅特色图片', 'sakurairo'),
                ],
            ],
            [
                'type'    => 'text',
                'settings' => 'post_card_image_url',
                'iro_key' => 'post_card_image_url',
                'label'   => esc_html__('文章封面随机图API', 'sakurairo'),
            ],
            [
                'type'      => 'slider',
                'settings'  => 'post_card_design_card_radius',
                'iro_key'   => 'post_card_design',
                'iro_subkey' => 'card_radius',
                'label'     => esc_html__('文章卡片圆角', 'sakurairo'),
                'default'   => 0.7,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 2, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.post-list',
                        'property'      => '--post-card-border-radius',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'post_card_design_meta_radius',
                'iro_key'   => 'post_card_design',
                'iro_subkey' => 'meta_radius',
                'label'     => esc_html__('文章卡片元信息圆角', 'sakurairo'),
                'default'   => 0.3,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 2, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.post-list',
                        'property'      => '--post-card-meta-border-radius',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'post_card_design_title_radius',
                'iro_key'   => 'post_card_design',
                'iro_subkey' => 'title_radius',
                'label'     => esc_html__('文章卡片标题圆角', 'sakurairo'),
                'default'   => 0.3,
                'transport' => 'auto',
                'choices'   => ['min' => 0, 'max' => 2, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.post-list',
                        'property'      => '--post-card-title-border-radius',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
            [
                'type'      => 'slider',
                'settings'  => 'post_card_design_title_font_size',
                'iro_key'   => 'post_card_design',
                'iro_subkey' => 'title_font_size',
                'label'     => esc_html__('文章卡片标题大小', 'sakurairo'),
                'default'   => 1.2,
                'transport' => 'auto',
                'choices'   => ['min' => 0.5, 'max' => 3, 'step' => 0.01],
                'output'    => [
                    [
                        'element'       => '.post-list',
                        'property'      => '--post-card-title-font-size',
                        'value_pattern' => '$rem !important',
                    ],
                ],
            ],
        ],
    ],

    // ====================通用====================
    [
        'id'          => 'iro_pages_common',
        'title'       => esc_html__('通用', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_pages',

        'fields'      => [
            [
                'type'        => 'switch',
                'settings'    => 'page_post_toc',
                'iro_key'     => 'page_post_toc',
                'label'       => esc_html__('文章目录', 'sakurairo'),
                'description' => esc_html__('在文章页显示目录(检测到内容有标题会自动生成大纲并显示)', 'sakurairo'),
                'default'     => true,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'page_page_toc',
                'iro_key'     => 'page_page_toc',
                'label'       => esc_html__('页面目录', 'sakurairo'),
                'description' => esc_html__('在页面显示目录', 'sakurairo'),
                'default'     => false,
            ],
        ],
    ],

    // ====================文章页面====================
    [
        'id'          => 'iro_pages_extra',
        'title'       => esc_html__('文章页面', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_pages',

        'fields'      => [
            [
                'type'        => 'switch',
                'settings'    => 'article_function',
                'iro_key'     => 'article_function',
                'label'       => esc_html__('文章功能栏', 'sakurairo'),
                'description' => esc_html__('默认开启，将在文章页面显示下方启用的功能', 'sakurairo'),
                'default'     => true,
            ],
            [
                'type'        => 'select',
                'settings'    => 'article_licenses',
                'iro_key'     => 'article_licenses',
                'label'       => esc_html__('文章版权协议', 'sakurairo'),
                'description' => esc_html__('版权协议将显示在功能栏中。也可通过文章自定义字段「license」单独指定。', 'sakurairo'),
                'default'     => 'cc-by-nc-sa',
                'choices'     => [
                    ''              => esc_html__('不显示', 'sakurairo'),
                    'cc0'           => 'CC0 1.0',
                    'cc-by'         => 'CC BY 4.0',
                    'cc-by-nc'      => 'CC BY-NC 4.0',
                    'cc-by-nc-nd'   => 'CC BY-NC-ND 4.0',
                    'cc-by-nc-sa'   => 'CC BY-NC-SA 4.0',
                    'cc-by-nd'      => 'CC BY-ND 4.0',
                    'cc-by-sa'      => 'CC BY-SA 4.0',
                ],
                'active_callback' => [
                    [
                        'setting'  => 'article_function',
                        'operator' => '==',
                        'value'    => true,
                    ],
                ],
            ],
            [
                'type'        => 'text',
                'settings'    => 'article_author_reward_link',
                'iro_key'     => 'article_author_reward',
                'iro_subkey'  => 'link',
                'label'       => esc_html__('打赏按钮链接', 'sakurairo'),
                'description' => esc_html__('点击打赏按钮后跳转的链接', 'sakurairo'),
                'active_callback' => [
                    [
                        'setting'  => 'article_function',
                        'operator' => '==',
                        'value'    => true,
                    ],
                ],
            ],
            [
                'type'        => 'image',
                'settings'    => 'article_author_reward_image1',
                'iro_key'     => 'article_author_reward',
                'iro_subkey'  => 'image1',
                'label'       => esc_html__('打赏图片一', 'sakurairo'),
                'active_callback' => [
                    [
                        'setting'  => 'article_function',
                        'operator' => '==',
                        'value'    => true,
                    ],
                ],
            ],
            [
                'type'        => 'text',
                'settings'    => 'article_author_reward_link1',
                'iro_key'     => 'article_author_reward',
                'iro_subkey'  => 'link1',
                'label'       => esc_html__('打赏图片一跳转链接', 'sakurairo'),
                'description' => esc_html__('点击图片后跳转的链接', 'sakurairo'),
            ],
            [
                'type'        => 'image',
                'settings'    => 'article_author_reward_image2',
                'iro_key'     => 'article_author_reward',
                'iro_subkey'  => 'image2',
                'label'       => esc_html__('打赏图片二', 'sakurairo'),
            ],
            [
                'type'        => 'text',
                'settings'    => 'article_author_reward_link2',
                'iro_key'     => 'article_author_reward',
                'iro_subkey'  => 'link2',
                'label'       => esc_html__('打赏图片二跳转链接', 'sakurairo'),
                'description' => esc_html__('点击图片后跳转的链接', 'sakurairo'),
            ],
            [
                'type'        => 'switch',
                'settings'    => 'article_author_avatar',
                'iro_key'     => 'article_author_avatar',
                'label'       => esc_html__('文章作者头像', 'sakurairo'),
                'default'     => true,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'article_author_name',
                'iro_key'     => 'article_author_name',
                'label'       => esc_html__('文章作者名称', 'sakurairo'),
                'default'     => false,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'article_author_quote',
                'iro_key'     => 'article_author_quote',
                'label'       => esc_html__('文章作者签名', 'sakurairo'),
                'default'     => true,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'article_modified_time',
                'iro_key'     => 'article_modified_time',
                'label'       => esc_html__('文章最后更新时间', 'sakurairo'),
                'default'     => false,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'article_tag',
                'iro_key'     => 'article_tag',
                'label'       => esc_html__('文章标签', 'sakurairo'),
                'default'     => true,
            ],
            [
                'type'        => 'switch',
                'settings'    => 'article_nextpre',
                'iro_key'     => 'article_nextpre',
                'label'       => esc_html__('文章上一篇/下一篇导航', 'sakurairo'),
                'description' => esc_html__('默认开启，文章页面将显示上一篇/下一篇切换', 'sakurairo'),
                'default'     => true,
            ],
        ],
    ],

    // ====================评论区设置====================
    [
        'id'          => 'iro_pages_comment',
        'title'       => esc_html__('评论区设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_pages',

        'fields'      => [
            [
                'type'        => 'text',
                'settings'    => 'comment_input_place_holder',
                'iro_key'     => 'comment_input_place_holder',
                'label'       => esc_html__('评论区输入框占位符', 'sakurairo'),
                'default'     => '要来喵一句吗？',
                'transport'   => 'postMessage',
                'js_vars'     => [
                    [
                        'element'  => '.comment-form-comment .placeholder',
                        'function' => 'html',
                    ],
                ],
            ],
            [
                'type'        => 'text',
                'settings'    => 'comment_submit_button_text',
                'iro_key'     => 'comment_submit_button_text',
                'label'       => esc_html__('评论区提交按钮文本', 'sakurairo'),
                'default'     => '提交',
                'transport'   => 'postMessage',
                'js_vars'     => [
                    [
                        'element'  => '#submit',
                        'function' => 'html',
                    ],
                ],
            ],
            [
                'type'        => 'multicheck',
                'settings'    => 'comment_smilies_list',
                'iro_key'     => 'comment_smilies_list',
                'label'       => esc_html__('评论区表情', 'sakurairo'),
                'description' => esc_html__('选择要在评论区域输入框中显示的表情。全部取消选中可关闭评论区域输入框表情功能。', 'sakurairo'),
                'default'     => ['bilibili', 'tieba', 'yanwenzi'],
                'choices'     => [
                    'bilibili' => esc_html__('bilibili', 'sakurairo'),
                    'tieba'    => esc_html__('贴吧', 'sakurairo'),
                    'yanwenzi' => esc_html__('颜文字', 'sakurairo'),
                    'custom'   => esc_html__('自定义', 'sakurairo'),
                ],
            ],
            [
                'type'        => 'text',
                'settings'    => 'comment_smilies_list_custom_name',
                'iro_key'     => 'comment_smilies_list_custom_name',
                'label'       => esc_html__('自定义表情包名称', 'sakurairo'),
                'description' => esc_html__('建议输入少于4个汉字的内容，以免造成移动端的兼容性问题。', 'sakurairo'),
                'default'     => 'custom',
                'active_callback' => [
                    [
                        'setting'  => 'comment_smilies_list',
                        'operator' => 'contains',
                        'value'    => 'custom',
                    ],
                ],
            ],
            [
                'type'        => 'select',
                'settings'    => 'comment_captcha',
                'iro_key'     => 'comment_captcha',
                'label'       => esc_html__('评论区验证码', 'sakurairo'),
                'description' => esc_html__('开启后游客评论需要通过验证码验证', 'sakurairo'),
                'default'     => 'off',
                'choices'     => [
                    'off'       => esc_html__('Off', 'sakurairo'),
                    'builtin'   => esc_html__('主题内建验证码', 'sakurairo'),
                    'turnstile' => 'Cloudflare Turnstile',
                ],
            ],
        ],
    ],

    // ====================模板页面设置====================
    [
        'id'          => 'iro_pages_template',
        'title'       => esc_html__('模板页面设置', 'sakurairo'),
        'description' => '',
        'panel'       => 'iro_pages',

        'fields'      => [
            [
                'type'        => 'select',
                'settings'    => 'friend_link_sorting_mode',
                'iro_key'     => 'friend_link_sorting_mode',
                'label'       => esc_html__('友情链接列表排序模式', 'sakurairo'),
                'description' => esc_html__('选择友情链接列表排序模式，默认使用“名称”排序。', 'sakurairo'),
                'choices'     => [
                    'name'    => esc_html__('名称', 'sakurairo'),
                    'rating'  => esc_html__('评级', 'sakurairo'),
                    'updated' => esc_html__('更新时间', 'sakurairo'),
                    'rand'    => esc_html__('随机', 'sakurairo'),
                ],
            ],
            [
                'type'        => 'select',
                'settings'    => 'friend_link_order',
                'iro_key'     => 'friend_link_order',
                'label'       => esc_html__('升序或降序', 'sakurairo'),
                'description' => esc_html__('按升序或降序排序友情链接列表', 'sakurairo'),
                'choices'     => [
                    'ASC'  => esc_html__('升序', 'sakurairo'),
                    'DESC' => esc_html__('降序', 'sakurairo'),
                ],
                'active_callback' => [
                    [
                        'setting'  => 'friend_link_sorting_mode',
                        'operator' => '!=',
                        'value'    => 'rand',
                    ],
                ],
            ],
        ],
    ],
];

// ====================Panel注册====================
$panelAutoPriority = 10;
foreach ($panels as &$panel) {
    if (empty($panel['priority'])) {
        $panel['priority'] = $panelAutoPriority;
        $panelAutoPriority += 10;
    }
}
unset($panel);

$sectionAutoPriority = 10;
foreach ($sections as &$section) {
    if (empty($section['priority'])) {
        $section['priority'] = $sectionAutoPriority;
        $sectionAutoPriority += 10;
    }
}
unset($section);

foreach ($panels as $panel) {
    // 必须字段：id、title
    // 可选字段：description、priority
    switch ('panel') {
        case 'panel':
            new \Kirki\Panel(
                $panel['id'],
                [
                    'title'       => $panel['title'],
                    'description' => isset($panel['description']) ? $panel['description'] : '',
                    'priority'    => $panel['priority'],
                ]
            );
            break;
    }
}

// ====================分组和设置项注册====================
// 定义各字段类型的默认值映射（必填项 default 若未设置时使用）  
$type_defaults = [
    'background'       => [],
    'checkbox'         => false,
    'code'             => '',
    'color'            => '#000000',
    'color_palette'    => '#000000',
    'dashicons'        => '',
    'date'             => '',
    'dimension'        => '',
    'dimensions'       => [],
    'dropdown_pages'   => '',
    'editor'           => '',
    'generic'          => '',
    'image'            => '',
    'url'              => '',
    'multicheck'       => [],
    'multicolor'       => [],
    'number'           => 0,
    'palette'          => '',
    'radio'            => '',
    'radio_buttonset'  => '',
    'radio_image'      => '',
    'repeater'         => [],
    'select'           => '',
    'slider'           => 0,
    'sortable'         => [],
    'switch'           => false,
    'text'             => '',
    'textarea'         => '',
    'toggle'           => false,
    'typography'       => [],
    'upload'           => '',
    'input_slider'     => 0,
];
foreach ($sections as $section) {
    // 必须字段：id、title、panel
    // 可选字段：description、priority
    $section_id = $section['id'];
    new \Kirki\Section(
        $section['id'],
        [
            'title'       => $section['title'],
            'description' => isset($panel['description']) ? $panel['description'] : '',
            'panel'       => $section['panel'],
            'priority'    => $section['priority'],
        ]
    );

    // 自动设置字段排序
    $priority = 10;
    if (isset($section['fields']) && is_array($section['fields'])) {
        foreach ($section['fields'] as &$field) {
            if (empty($field['priority'])) {
                $field['priority'] = $priority;
                $priority += 10;
            }
        }
        unset($field);
    }

    // 含有fields设置项
    if (isset($section['fields']) && is_array($section['fields'])) {
        foreach ($section['fields'] as $field) {
            // 自动将当前 section 的 id 分配给字段
            $field['section'] = $section_id;

            // 构造设置项，仅提取允许的参数
            $args = [];
            foreach ($allowed_params as $param) {
                if (isset($field[$param])) {
                    $args[$param] = $field[$param];
                }
            }

            // 对必填项做检查与默认处理
            // 必须字段：label、settings、section、priority，
            if (! isset($args['label'])) {
                $args['label'] = '';
            }
            if (! isset($args['settings'])) {
                // 如果没有设置 settings，则跳过此字段（或记录错误）
                error_log('Customize filed setting name missed.');
                continue;
            }
            if (! isset($args['capability'])) { // Kirki 4.0
                $args['capability'] = 'edit_theme_options';
            }
            if (! isset($args['option_type'])) {
                $args['option_type'] = 'theme_mod';
            }
            // if ( ! isset( $args['option_name'] ) ) { // 仅限option类型，theme_mod无效
            // 	$args['option_name'] = 'iro_options';
            // }

            // 自动根据类型补充默认值
            if (! isset($args['default'])) {
                // 将 type 转为小写并用下划线替换空格
                $type_key = strtolower($field['type'] ?? '');
                if (isset($type_defaults[$type_key])) {
                    $args['default'] = $type_defaults[$type_key];
                } else {
                    $args['default'] = '';
                }
            }

            if (isset($args['iro_key'])) {
                $setting_id    = $args['settings'];
                $iro_key       = $args['iro_key'];
                $type_default  = $args['default'];
                $iro_options_all = is_array($GLOBALS['iro_options'] ?? null) ? $GLOBALS['iro_options'] : [];
                $iro_default   = $iro_options_all[$iro_key] ?? null;
                $iro_subkey    = isset($args['iro_subkey']) ? $args['iro_subkey'] : '';

                if (! isset($args['transport'])) { // 没设置预览方式的默认请求php渲染
                    $args['transport'] = 'refresh';
                }

                $iro_options_map = get_theme_mod('iro_options_map', []);
                // 构建映射结构
                $iro_options_map[$setting_id] = [
                    'iro_key'    => $iro_key,
                    'iro_subkey' => $iro_subkey,
                    'default'    => $iro_default,
                ];

                // 存储映射表
                set_theme_mod('iro_options_map', $iro_options_map);

                // 自动default
                $args['default'] = isset($args['iro_subkey'])
                    ? (is_array($iro_default) && isset($iro_default[$args['iro_subkey']]) ? $iro_default[$args['iro_subkey']] : $type_default)
                    : ($iro_default !== null ? $iro_default : $type_default); //从iro_opt中获取默认值，或使用种类默认值

                // set_theme_mod($setting_id, $args['default']);
            }

            // 根据字段类型选择对应的 Kirki 类注册组件
            // 将类型字符串统一转换为小写，下划线格式
            // 分类按需实例化
            $field_type_key = str_replace(' ', '_', strtolower($field['type']));
            switch ($field_type_key) {
                case 'checkbox':
                    new \Kirki\Field\Checkbox($args);
                    break;
                case 'code':
                    new \Kirki\Field\Code($args);
                    break;
                case 'color':
                    new \Kirki\Field\Color($args);
                    break;
                case 'custom':
                    new \Kirki\Field\Custom($args);
                    break;
                case 'dashicons':
                    new \Kirki\Field\Dashicons($args);
                    break;
                case 'dropdown_pages':
                    new \Kirki\Field\Dropdown_Pages($args);
                    break;
                case 'generic':
                    new \Kirki\Field\Generic($args);
                    break;
                case 'image':
                    new \Kirki\Field\Image($args);
                    break;
                case 'url':
                    new \Kirki\Field\URL($args);
                    break;
                case 'multicheck':
                    new \Kirki\Field\Multicheck($args);
                    break;
                case 'number':
                    new \Kirki\Field\Number($args);
                    break;
                case 'radio':
                    new \Kirki\Field\Radio($args);
                    break;
                case 'radio_buttonset':
                    new \Kirki\Field\Radio_Buttonset($args);
                    break;
                case 'radio_image':
                    new \Kirki\Field\Radio_Image($args);
                    break;
                case 'repeater':
                    new \Kirki\Field\Repeater($args);
                    break;
                case 'select':
                    new \Kirki\Field\Select($args);
                    break;
                case 'slider':
                    new \Kirki\Field\Slider($args);
                    break;
                case 'sortable':
                    new \Kirki\Field\Sortable($args);
                    break;
                case 'switch':
                    new \Kirki\Field\Checkbox_Switch($args);
                    break;
                case 'text':
                    new \Kirki\Field\Text($args);
                    break;
                case 'textarea':
                    new \Kirki\Field\Textarea($args);
                    break;
                case 'toggle':
                    new \Kirki\Field\Checkbox_Toggle($args);
                    break;
                case 'upload':
                    new \Kirki\Field\Upload($args);
                    break;
                case 'input_slider':
                    new \Kirki\Field\InputSlider($args);
                    break;
                case 'divider':
                    new \Kirki\Field\Divider($args);
                    break;
                default:
                    error_log('Unknown Kirki field type: ' . $field['type']);
                    break;
            }
        }
    }
}
