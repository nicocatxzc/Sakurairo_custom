<?php
if (class_exists('Sakurairo_CSF')) {

    $prefix = 'iro_options';

    $vision_resource_basepath = get_option('iro_options')['vision_resource_basepath'] ?? 'https://s.nmxc.ltd/sakurairo_vision/@3.0/';

    Sakurairo_CSF::createOptions($prefix, [
        'menu_title' => __('iro主题设置', 'sakurairo_csf'),
        'menu_slug'  => 'iro_options',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'title' => __('欢迎！', 'sakurairo_csf'),
        'icon'        => 'fa fa-podcast',
        'fields'      => [

            [
                'type'    => 'heading',
                'content' => __('感谢每一位支持我们的人！', 'sakurairo_csf'),
            ],

            [
                'type'    => 'content',
                'content' => __('<a href="https://afdian.com/a/mamori"><img alt="afdian" height="50" src="https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/afdian.webp"></a><a href="https://liberapay.com/furina/donate"><img alt="liberapay" height="50" src="https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/liberapay.webp"></a><a href="https://app.unifans.io/c/somekawahitomi"><img alt="unifans" height="50" src="https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/unifans.webp"></a>', 'sakurairo_csf'),
            ],

            [
                'type'    => 'content',
                'content' => __('<img src="https://fuukei-api.nyat.icu/api/sponsors"  alt="Sponsor" width="100%" height="100%" />', 'sakurairo_csf'),
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id'    => 'preliminary',
        'title' => __('基本设置', 'sakurairo_csf'),
        'icon'      => 'fa fa-sliders',
        'fields' => [
            [
                'id'    => 'favicon_link',
                'type'  => 'text',
                'title' => __('站点图标', 'sakurairo_csf'),
                'desc'   => __('填写链接，它将会出现在浏览器标签页的标题旁边', 'sakurairo_csf'),
                'default' => $vision_resource_basepath . 'basic/favicon.ico'
            ],

            [
                'id'    => 'iro_seo',
                'type'  => 'select',
                'title' => __('自动SEO', 'sakurairo_csf'),
                'options'     => [
                    'off'  => __('不使用主题SEO', 'sakurairo_csf'),
                    'auto'  => __('自动完善SEO', 'sakurairo_csf'),
                    'on'  => __('总是加上所有SEO', 'sakurairo_csf'),
                ],
                'desc'   => __('如果启用，主题将根据情况决定是否加上SEO相关页面meta属性', 'sakurairo_csf'),
                "default" => "on",
            ],

            [
                'id'     => 'iro_meta_keywords',
                'type'   => 'text',
                'title'  => __('站点关键词', 'sakurairo_csf'),
                'dependency' => ['iro_seo', '!=', 'off', '', 'true'],
                'desc'   => __('使用英文逗号分隔，并尽量控制在五个词以内', 'sakurairo_csf'),
            ],

            [
                'id'     => 'iro_meta_description',
                'type'   => 'text',
                'title'  => __('站点描述', 'sakurairo_csf'),
                'dependency' => ['iro_seo', '!=', 'off', '', 'true'],
                'desc'   => __('提供一些关于网站内容的描述，控制在120字以内，它将出现在搜索引擎搜索结果条目的下方', 'sakurairo_csf'),
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id'    => 'global',
        'title' => __('全局设置', 'sakurairo_csf'),
        'icon'      => 'fa fa-globe',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title'  => __('外观设置', 'sakurairo_csf'),
        'icon'      => 'fa fa-tree',
        'fields' => [
            [
                'type'    => 'subheading',
                'content' => __('主题配色', 'sakurairo_csf'),
            ],

            // [
            //     'id' => 'extract_theme_skin_from_cover',
            //     'type' => 'switcher',
            //     'title' => __('Extract Theme Color from Cover Image', 'sakurairo_csf'),
            //     'label' => __('Default on, Following options will be used as fallback (while cover image cannot be read by scripts)', 'sakurairo_csf'),
            //     'default' => true
            // ],

            // [
            //     'id' => 'extract_article_highlight_from_feature',
            //     'type' => 'switcher',
            //     'title' => __('Extract Article Highlight from Featured Image', 'sakurairo_csf'),
            //     'label' => __('Default on, The colors displayed on the article page will be taken from the article featured image', 'sakurairo_csf'),
            //     'default' => true
            // ],

            [
                'id'      => 'word_color_first',
                'type'    => 'color',
                'title'   => __('主要文字颜色', 'sakurairo_csf'),
                'desc'    => __('文章标题和正文内容等文字的颜色', 'sakurairo_csf'),
                'default' => '#505050'
            ],

            [
                'id'      => 'word_color_second',
                'type'    => 'color',
                'title'   => __('次要文字颜色', 'sakurairo_csf'),
                'desc'    => __('帮助和页脚等文字的颜色', 'sakurairo_csf'),
                'default' => '#00000080'
            ],

            [
                'id'      => 'active_color',
                'type'    => 'color',
                'title'   => __('激活组件颜色', 'sakurairo_csf'),
                'desc'    => __('鼠标悬浮链接以及按钮和高亮标签等部分的颜色', 'sakurairo_csf'),
                'default' => '#00b0f0'
            ],

            [
                'id'      => 'code_block_background_color',
                'type'    => 'color',
                'title'   => __('代码块背景色', 'sakurairo_csf'),
                'default' => '#e1e4e8'
            ],

            [
                'id'     => 'widget_transparency',
                'type'   => 'slider',
                'title'  => __('组件透明度', 'sakurairo_csf'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.8'
            ],

            [
                'id'     => 'background_transparency',
                'type'   => 'slider',
                'title'  => __('背景透明度', 'sakurairo_csf'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.8'
            ],

            [
                'id'     => 'background_blur',
                'type'   => 'slider',
                'title'  => __('背景模糊度', 'sakurairo_csf'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.7'
            ],

            [
                'type'    => 'subheading',
                'content' => __('深色模式', 'sakurairo_csf'),
            ],

            [
                'id'      => 'word_color_first_dark',
                'type'    => 'color',
                'title'   => __('主要文字颜色', 'sakurairo_csf'),
                'desc'    => __('文章标题和正文内容等文字的颜色', 'sakurairo_csf'),
                'default' => '#CCCCCC'
            ],

            [
                'id'      => 'word_color_second_dark',
                'type'    => 'color',
                'title'   => __('次要文字颜色', 'sakurairo_csf'),
                'desc'    => __('帮助和页脚等文字的颜色', 'sakurairo_csf'),
                'default' => '#7d7d7d'
            ],

            [
                'id'      => 'active_color_dark',
                'type'    => 'color',
                'title'   => __('激活组件颜色', 'sakurairo_csf'),
                'desc'    => __('鼠标悬浮链接以及按钮和高亮标签等部分的颜色', 'sakurairo_csf'),
                'default' => '#FCCD00'
            ],

            [
                'id'      => 'code_block_background_color_dark',
                'type'    => 'color',
                'title'   => __('代码块背景色', 'sakurairo_csf'),
                'default' => '#24292e'
            ],

            [
                'id'     => 'widget_transparency_dark',
                'type'   => 'slider',
                'title'  => __('组件透明度', 'sakurairo_csf'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.8'
            ],

            [
                'id'     => 'background_transparency_dark',
                'type'   => 'slider',
                'title'  => __('背景透明度', 'sakurairo_csf'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.7'
            ],

            [
                'id'     => 'background_transparency_dark',
                'type'   => 'slider',
                'title'  => __('图像亮度', 'sakurairo_csf'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.7'
            ],

            [
                'id'    => 'theme_darkmode_auto',
                'type'  => 'switcher',
                'title' => __('自动切换深色模式', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'type'    => 'content',
                'content' => __(
                    '<p><strong>Client local time:</strong>Dark mode will switch on automatically from 22:00 to 7:00</p>'
                        . '<p><strong>Follow client settings:</strong>Follow client browser settings</p>'
                        . '<p><strong>Always on:</strong>Always on, except being configured by the client</p>',
                    'sakurairo_csf'
                ),
                'dependency' => ['theme_darkmode_auto', '==', 'true', '', 'true'],

            ],

            [
                'id' => 'theme_commemorate_mode_date',
                'type' => 'textarea',
                'title' => __('纪念模式日期', 'sakurairo_csf'),
                'desc' => __('一行一个，例如7-21，主题会在这些日期加上黑白滤镜', 'sakurairo_csf'),
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title'  => __('字体设置', 'sakurairo_csf'),
        'icon'      => 'fa fa-font',
        'fields' => [
            [
                'type'    => 'subheading',
                'content' => __('基本设置', 'sakurairo_csf'),
            ],

            [
                'id'     => 'global_font_size',
                'type'   => 'slider',
                'title'  => __('字体大小', 'sakurairo_csf'),
                'desc'   => __('此处以像素为单位，主题大部分组件会以此为基础调整自身字体大小，以实现等比缩放的效果', 'sakurairo_csf'),
                'step'   => '0.1',
                'unit'    => 'px',
                'min'   => '1',
                'max'   => '64',
                'default' => '16'
            ],

            [
                'id'     => 'global_font_weight',
                'type'   => 'slider',
                'title'  => __('非强调文本字重', 'sakurairo_csf'),
                'desc'   => __('Slide to adjust, the recommended value range is 300-500', 'sakurairo_csf'),
                'step'   => '10',
                'min'   => '100',
                'max'   => '1000',
                'default' => '300'
            ],

            [
                'id'     => 'global_default_font',
                'type'   => 'text',
                'title'  => __('默认字体', 'sakurairo_csf'),
            ],

            [
                'type'    => 'subheading',
                'content' => __('外部字体', 'sakurairo_csf'),
            ],

            [
                'id'     => 'gfonts_api',
                'type'   => 'text',
                'title'  => __('Google Fonts API', 'sakurairo_csf'),
                'default' => 'fonts.googleapis.com'
            ],

            [
                'id'     => 'gfonts_add_name',
                'type'   => 'text',
                'title'  => __('Google Fonts 字体名称', 'sakurairo_csf'),
                'desc'   => __('请确保添加的字体在谷歌字体库内可被引用，填写字体名称。添加的字体前面必须有”|“。如果引用多个字体，请使用“|”作为分割符，如果字体名称有空格，请用加号替代。例如：|ZCOOL+XiaoWei|Ma+Shan+Zheng', 'sakurairo_csf'),
            ],

            [
                'id'        => 'extra_fonts',
                'type'      => 'repeater',
                'title'     => __('额外字体', 'sakurairo_csf'),
                'fields'    => [
                    [
                        'id'    => 'font_name',
                        'type'  => 'text',
                        'title' => __('字体名称', 'sakurairo_csf'),
                    ],
                    [
                        'id'    => 'link',
                        'type'  => 'text',
                        'title' => __('字体链接', 'sakurairo_csf'),
                    ],
                ],
            ]
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title'  => __('导航栏', 'sakurairo_csf'),
        'icon'      => 'fa fa-map-signs',
        'fields' => [
            [
                'id'    => 'nav_logo',
                'type'  => 'upload',
                'title' => __('导航栏logo', 'sakurairo_csf'),
                'library'      => 'image',
            ],

            [
                'id' => 'nav_title',
                'type' => 'text',
                'title' => __('导航栏标题', 'sakurairo_csf'),
            ],

            [
                'id' => 'nav_title_font',
                'type' => 'text',
                'title' => __('导航栏标题字体', 'sakurairo_csf'),
            ],

            [
                'id' => 'nav_option_font',
                'type' => 'text',
                'title' => __('导航栏选项字体', 'sakurairo_csf'),
            ],

            [
                'id'    => 'navbar_distribution',
                'type'  => 'select',
                'title' => __('导航栏选项分布位置', 'sakurairo_csf'),
                'options'     => [
                    'left'  => __('左', 'sakurairo_csf'),
                    'center'  => __('中', 'sakurairo_csf'),
                    'right'  => __('右', 'sakurairo_csf'),
                    'space-evenly'  => __('均匀', 'sakurairo_csf'),
                ],
                "default" => "off",
            ],

            [
                'id' => 'navbar_option_margin',
                'type' => 'slider',
                'title' => __('导航栏选项间距', 'sakurairo_csf'),
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '2',
                'default' => '0.3',
            ],

            [
                'id' => 'nav_menu_cover_radius',
                'type' => 'slider',
                'title' => __('导航栏菜单圆角', 'sakurairo_csf'),
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '2',
                'default' => '0.3',
            ],

            [
                'id' => 'nav_menu_cover_switch',
                'type' => 'switcher',
                'title' => __('导航栏封面切换按钮', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id'    => 'nav_user_menu',
                'type'  => 'switcher',
                'title' => __('导航栏用户栏', 'sakurairo_csf'),
                'default' => true
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title' => __('前台设置', 'sakurairo_csf'),
        'icon' => 'fa fa-th-large',
        'fields' => [

            [
                'type' => 'subheading',
                'content' => __('工具栏', 'sakurairo_csf'),
            ],

            [
                'id' => 'widget_button_radius',
                'type' => 'slider',
                'title' => __('工具栏按钮圆角', 'sakurairo_csf'),
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '3',
                'default' => '0.6'
            ],

            [
                'id' => 'widget_panel_radius',
                'type' => 'slider',
                'title' => __('工具栏面板圆角', 'sakurairo_csf'),
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '2',
                'default' => '0.6'
            ],

            [
                'id' => 'widget_font',
                'type' => 'text',
                'title' => __('工具栏字体', 'sakurairo_csf'),
            ],

            [
                'id' => 'widget_wordpress_widget',
                'type' => 'switcher',
                'title' => __('工具栏wordpress组件', 'sakurairo_csf'),
                'label' => __('启用后将会显示wordpress可编辑工具栏', 'sakurairo_csf'),
                'desc' => __('你可以前往<a href="/wp-admin/widgets.php"> 此处 </a>编辑', 'sakurairo_csf'),
                'default' => false
            ],

            [
                'id' => 'widget_darkmode_switch',
                'type' => 'switcher',
                'title' => __('工具栏深色模式切换按钮', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'widget_font_switch',
                'type' => 'switcher',
                'title' => __('工具栏字体切换按钮', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id'        => 'widget_font_choice',
                'type'      => 'repeater',
                'title'     => __('工具栏可选字体', 'sakurairo_csf'),
                'desc' => __('请使用有效的字体名称，需要在全局字体设置中添加对应名称的额外字体才能生效', 'sakurairo_csf'),
                'fields'    => [
                    [
                        'id'    => 'name',
                        'type'  => 'text',
                        'title' => __('字体名称', 'sakurairo_csf'),
                    ],
                ],
            ],

            [
                'type' => 'subheading',
                'content' => __('前台背景', 'sakurairo_csf'),
            ],

            [
                'id'    => 'frontend_default_background',
                'type'  => 'upload',
                'title' => __('前台默认背景', 'sakurairo_csf'),
                'library'      => 'image',
            ],

            [
                'id'    => 'frontend_particle',
                'type'  => 'select',
                'title' => __('前台背景粒子特效', 'sakurairo_csf'),
                'options'     => [
                    'off'  => __('关闭', 'sakurairo_csf'),
                    'sakura'  => __('樱花', 'sakurairo_csf'),
                    'snow'  => __('雪', 'sakurairo_csf'),
                    'custom'  => __('自定义', 'sakurairo_csf'),
                ],
                "default" => "off",
            ],

            [
                'id'        => 'frontend_particle_builtin',
                'type'      => 'fieldset',
                'title'     => __('内建粒子特效选项', 'sakurairo_csf'),
                'desc' => __('请使用有效的字体名称，需要在全局字体设置中添加对应名称的额外字体才能生效', 'sakurairo_csf'),
                'dependency' => ['frontend_particle', 'any', 'sakura,snow', '', 'true'],
                'fields'    => [
                    [
                        'id'     => 'amount',
                        'type'   => 'slider',
                        'title'  => __('粒子数量', 'sakurairo_csf'),
                        'step'   => '1',
                        'min'   => '10',
                        'max'   => '100',
                    ],
                    [
                        'id'     => 'minsize',
                        'type'   => 'slider',
                        'title'  => __('粒子最小大小', 'sakurairo_csf'),
                        'step'   => '1',
                        'min'   => '1',
                        'max'   => '100',
                    ],
                    [
                        'id'     => 'maxsize',
                        'type'   => 'slider',
                        'title'  => __('粒子最大大小', 'sakurairo_csf'),
                        'step'   => '1',
                        'min'   => '30',
                        'max'   => '100',
                    ],
                    [
                        'id'     => 'speed',
                        'type'   => 'slider',
                        'title'  => __('粒子速度', 'sakurairo_csf'),
                        'step'   => '1',
                        'min'   => '1',
                        'max'   => '100',
                    ],
                ],
                'default'        => [
                    'amount'    => '10',
                    'minsize'    => '10',
                    'maxsize'    => '30',
                    'speed'    => '30',
                ],
            ],

            [
                'id' => 'particle_config',
                'type'     => 'code_editor',
                'sanitize' => false,
                'title' => __('自定义粒子特效实现', 'sakurairo_csf'),
                'dependency' => ['frontend_particle', '==', 'custom', '', 'true'],
                'desc' => __('参考tsParticle', 'sakurairo_csf'),
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title' => __('页尾设置', 'sakurairo_csf'),
        'icon' => 'fa fa-caret-square-o-down',
        'fields' => [
            [
                'id' => 'footer_sakura',
                'type' => 'switcher',
                'title' => __('页尾樱花', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'footer_font',
                'type' => 'text',
                'title' => __('页尾字体', 'sakurairo_csf'),
            ],

            [
                'id' => 'footer_html',
                'type'     => 'code_editor',
                'sanitize' => false,
                'title' => __('页尾html代码', 'sakurairo_csf'),
                'desc' => __('可以在此处编写页脚内容，也可以加入能接受延迟加载的统计代码，请确保它们安全', 'sakurairo_csf'),
            ],

            [
                'type' => 'subheading',
                'content' => __('一言', 'sakurairo_csf'),
            ],

            [
                'id'    => 'footer_hitokoto_select',
                'type'  => 'select',
                'title' => __('页脚一言', 'sakurairo_csf'),
                'options'     => [
                    'off'  => __('关闭', 'sakurairo_csf'),
                    'api'  => __('总是使用API', 'sakurairo_csf'),
                    'custom'  => __('总是自定义', 'sakurairo_csf'),
                    'both'  => __('各一半', 'sakurairo_csf'),
                ],
                "default" => "off",
            ],

            [
                'type' => 'content',
                'dependency' => ['footer_hitokoto_select', '!=', 'off', '', 'true'],
                'content' => __('<h4>Hitokoto API Setup Instructions</h4>'
                    . ' <p>Fill in as the example:<code> ["https://v1.hitokoto.cn/", "https://v1.hitokoto.cn/"]</code>, where the first API will be used first and the next ones will be the backup. </p>'
                    . ' <p><strong>Official API:</strong> See the <a href="https://developer.hitokoto.cn/sentence/"> documentation</a> for how to use it, and the parameter "return code" should not be anything except JSON. <a href="https://v1.hitokoto.cn/">https://v1.hitokoto.cn/</a></p>', 'sakurairo_csf'),
            ],

            [
                'id' => 'footer_hitokoto_api',
                'type' => 'textarea',
                'title' => __('一言API地址', 'sakurairo_csf'),
                'dependency' => ['footer_hitokoto_select', '!=', 'off', '', 'true'],
                'desc' => __('填写地址，格式为 JavaScript 数组', 'sakurairo_csf'),
                'default' => '["https://v1.hitokoto.cn/","https://v1.hitokoto.cn/"]'
            ],

            [
                'id' => 'footer_hitokoto_custom',
                'type' => 'textarea',
                'title' => __('一言自定义内容', 'sakurairo_csf'),
                'dependency' => ['footer_hitokoto_select', '!=', 'off', '', 'true'],
                'desc' => __('一行一句，尽量不要出现特殊字符。', 'sakurairo_csf'),
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title' => __('搜索设置', 'sakurairo_csf'),
        'icon' => 'fa fa-search',
        'fields' => [

            [
                'id' => 'nav_menu_search_switch',
                'type' => 'switcher',
                'title' => __('导航栏搜索按钮', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'search_filter',
                'type' => 'switcher',
                'title' => __('搜索页过滤栏', 'sakurairo_csf'),
                'default' => false
            ],

            [
                'id' => 'search_for_shuoshuo',
                'type' => 'switcher',
                'title' => __('在搜索结果中显示说说', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'search_for_pages',
                'type' => 'switcher',
                'title' => __('在搜索结果中显示页面', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'search_pages_can_only_admins',
                'type' => 'switcher',
                'title' => __('只有管理员可以搜索页面', 'sakurairo_csf'),
                'dependency' => [
                    ['search_for_pages', '==', 'true', '', 'true'],
                ],
                'default' => true
            ],

            [
                'id' => 'search_for_pinned_posts',
                'type' => 'switcher',
                'title' => __('在搜索结果中置顶置顶文章', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'search_results_custom_exclude',
                'type' => 'text',
                'title' => __('搜索结果排除', 'sakurairo_csf'),
                'desc' => __('从搜索结果中排除自定义ID内容，在使用自定义登录页面后推荐使用，你可以从编辑页的链接中获取，填写数字ID，例如“12,34”', 'sakurairo_csf'),
            ],

            [
                'id' => 'search_live',
                'type' => 'switcher',
                'title' => __('实时搜索', 'sakurairo_csf'),
                'label' => __('开启后前台客户端会在搜索前加载一份索引，并实时显示键入后的相关搜索结果', 'sakurairo_csf'),
                'default' => false
            ],
        ]
    ]);


    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title' => __('其他设置', 'sakurairo_csf'),
        'icon' => 'fa fa-gift',
        'fields' => [

            [
                'type' => 'subheading',
                'content' => __('效果和动画', 'sakurairo_csf'),
            ],

            [
                'id' => 'pjax',
                'type' => 'switcher',
                'title' => __('PJAX', 'sakurairo_csf'),
                'label' => __('启用后前台站内跳转将不会刷新页面，体验更好，但与第三方内容可能存在兼容性问题，请按需使用', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'pjax_keep_loading',
                'type' => 'textarea',
                'title' => __('Resources that still need refreshing in the footer after enabling PJAX', 'sakurairo_csf'),
                'dependency' => ['pjax', '==', 'true', '', 'true'],
                'desc' => __('After enabling PJAX, custom content in the footer will not be refreshed on page navigation. You can specify paths for JavaScript and stylesheet resources that need to be reloaded on each page in the footer here, one per line. These resources will be reloaded once PJAX completes content loading.', 'sakurairo_csf'),
            ],

            [
                'id' => 'top_scroll_progress',
                'type' => 'switcher',
                'title' => __('顶部阅读进度条', 'sakurairo_csf'),
                'label' => __('开启后会在页面顶部会显示进度条，进度取决于当前页面的滚动进度', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'top_loading_progress',
                'type' => 'switcher',
                'title' => __('顶部加载进度条', 'sakurairo_csf'),
                'label' => __('开启后会在页面顶部会显示进度条，进度取决于下一页的加载进度', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'pagination_mode',
                'type' => 'radio',
                'title' => __('文章列表分页导航方式', 'sakurairo_csf'),
                'options' => [
                    'ajax' => __('滚动加载', 'sakurairo_csf'),
                    'pagination' => __('传统分页', 'sakurairo_csf'),
                ],
                'default' => 'pagination'
            ],

            [
                'id' => 'pagination_ajax_wait',
                'type' => 'slider',
                'title' => __('ajax自动加载等待时间', 'sakurairo_csf'),
                'dependency' => ['pagination_mode', '==', 'ajax', '', 'true'],
                'step' => '1',
                'unit' => 's',
                'max' => '10',
                'default' => '3',
            ],

            [
                'id' => 'missing_avatars_placeholder',
                'type' => 'upload',
                'title' => __('站内头像占位', 'sakurairo_csf'),
                'library' => 'image',
            ],

            [
                'id' => 'missing_images_placeholder',
                'type' => 'upload',
                'title' => __('站内图片占位', 'sakurairo_csf'),
                'library' => 'image',
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id' => 'homepage',
        'title' => __('首页设置', 'sakurairo_csf'),
        'icon' => 'fa fa-home',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'homepage',
        'title' => __('封面设置', 'sakurairo_csf'),
        'icon' => 'fa fa-laptop',
        'fields' => [

            [
                'id' => 'cover_switch',
                'type' => 'switcher',
                'title' => __('封面开关', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'type' => 'subheading',
                'content' => __('封面信息栏', 'sakurairo_csf'),
            ],

            [
                'id' => 'cover_focus_style',
                'type' => 'select',
                'title' => __('首页聚焦显示内容', 'sakurairo_csf'),
                'options' => [
                    'off' => __('无', 'sakurairo_csf'),
                    'avatar' => __('头像', 'sakurairo_csf'),
                    'text' => __('文字', 'sakurairo_csf'),
                    'mashiro_text' => __('Mashiro特效文字', 'sakurairo_csf'),
                ],
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                ],
                'default' => 'text'
            ],

            [
                'id'    => 'cover_avatar',
                'type'  => 'upload',
                'title' => __('个人头像', 'sakurairo_csf'),
                'desc'   => __('最佳宽高比为1:1', 'sakurairo_csf'),
                'library'      => 'image',
            ],

            [
                'id'        => 'cover_title',
                'type'      => 'fieldset',
                'title'     => __('封面文字配置', 'sakurairo_csf'),
                'dependency' => ['cover_focus_style', 'any', 'text,mashiro_text', '', 'true'],
                'fields'    => [
                    [
                        'id'     => 'text',
                        'type'   => 'text',
                        'title'  => __('内容', 'sakurairo_csf'),
                    ],
                    [
                        'id'     => 'font',
                        'type'   => 'text',
                        'title'  => __('字体', 'sakurairo_csf'),
                    ],
                    [
                        'id'     => 'size',
                        'type'   => 'slider',
                        'title'  => __('大小', 'sakurairo_csf'),
                        'step'   => '0.01',
                        'unit'    => 'rem',
                        'min'   => '1',
                        'max'   => '9',
                    ],
                    [
                        'id'      => 'color',
                        'type'    => 'color',
                        'title'   => __('颜色', 'sakurairo_csf'),
                    ],
                ],
                'default'        => [
                    'text'    => '花になって',
                    'size'    => '5',
                    'color'    => '#FFF',
                ],
            ],

            [
                'id' => 'cover_infor_bar_switch',
                'type' => 'switcher',
                'title' => __('封面信息栏开关', 'sakurairo_csf'),
                'dependency' => ['cover_switch', '==', 'true', '', 'true'],
                'default' => true
            ],

            [
                'id' => 'cover_infor_bar_radius',
                'type' => 'slider',
                'title' => __('封面信息栏圆角', 'sakurairo_csf'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                    ['cover_infor_bar_switch', '==', 'true'],
                ],
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '5',
                'default' => '1'
            ],

            [
                'id'        => 'cover_signature',
                'type'      => 'fieldset',
                'title'     => __('封面签名', 'sakurairo_csf'),
                'fields'    => [
                    [
                        'id'     => 'text',
                        'type'   => 'text',
                        'title'  => __('内容', 'sakurairo_csf'),
                    ],
                    [
                        'id'     => 'font',
                        'type'   => 'text',
                        'title'  => __('字体', 'sakurairo_csf'),
                    ],
                    [
                        'id'     => 'size',
                        'type'   => 'slider',
                        'title'  => __('大小', 'sakurairo_csf'),
                        'step'   => '0.01',
                        'unit'    => 'rem',
                        'min'   => '0.1',
                        'max'   => '2',
                    ],
                ],
                'default'        => [
                    'text'    => '季節の変わり目の服は何着りゃいいんだろ',
                    'size'    => '1',
                ],
            ],

            [
                'id' => 'cover_typedjs',
                'type' => 'switcher',
                'title' => __('封面打字机效果', 'sakurairo_csf'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                    ['cover_infor_bar_switch', '==', 'true'],
                ],
                'default' => true
            ],

            [
                'id' => 'cover_typedjs_mark',
                'type' => 'switcher',
                'title' => __('封面打字机引号', 'sakurairo_csf'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                    ['cover_infor_bar_switch', '==', 'true'],
                    ['cover_typedjs', '==', 'true'],
                ],
                'default' => false
            ],

            [
                'id' => 'cover_typedjs_placeholder',
                'type'     => 'text',
                'title' => __('封面打字机占位符', 'sakurairo_csf'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                    ['cover_infor_bar_switch', '==', 'true'],
                    ['cover_typedjs', '==', 'true'],
                ],
                'default' => '疯狂造句中......'
            ],

            [
                'id' => 'cover_typedjs_config',
                'type'     => 'code_editor',
                'sanitize' => false,
                'title' => __('封面打字机配置', 'sakurairo_csf'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                    ['cover_infor_bar_switch', '==', 'true'],
                    ['cover_typedjs', '==', 'true'],
                ],
                'default' => '{"strings":["愿你保持不变 保持己见 充满热血"],"typeSpeed":140,"backSpeed":50,"loop":false,"showCursor":true}'
            ],

            [
                'type' => 'subheading',
                'content' => __('封面图片', 'sakurairo_csf'),
            ],

            [
                'id' => 'cover_random_pic_url_pc',
                'type' => 'text',
                'title' => __('PC封面图片地址', 'sakurairo_csf'),
                'desc' => __('填写图片地址或者随机图API', 'sakurairo_csf'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                ],
                'default' => 'https://api.fuukei.org/random-img/default/pc.php',
                'sanitize' => false,
                'validate' => 'csf_validate_url',
            ],

            [
                'id' => 'cover_random_pic_url_mb',
                'type' => 'text',
                'title' => __('移动端封面图片地址', 'sakurairo_csf'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                ],
                'desc' => __('填写图片地址或者随机图API，未填写则使用与PC图片相同的配置', 'sakurairo_csf'),
                'default' => 'https://api.fuukei.org/random-img/default/mobile.php',
                'sanitize' => false,
                'validate' => 'csf_validate_url',
            ],

            [
                'id' => 'cover_as_background',
                'type' => 'switcher',
                'title' => __('前台背景一体化', 'sakurairo_csf'),
                'label' => __('开启后封面背景将会变透明，以实现前台背景与封面的一体化效果', 'sakurairo_csf'),
                'dependency' => ['cover_switch', '==', 'true', '', 'true'],
                'default' => false
            ],

            [
                'id' => 'post_cover_as_background',
                'type' => 'switcher',
                'title' => __('使用特色图片作为背景', 'sakurairo_csf'),
                'label' => __('在文章页将会使用特色图片作为背景', 'sakurairo_csf'),
                'default' => false
            ],

            [
                'id' => 'cover_pic_filter',
                'type' => 'select',
                'title' => __('封面图片滤镜', 'sakurairo_csf'),
                'options' => [
                    'filter-nothing' => __('无', 'sakurairo_csf'),
                    'filter-undertint' => __('浅色滤镜', 'sakurairo_csf'),
                    'filter-dim' => __('深色滤镜', 'sakurairo_csf'),
                    'filter-grid' => __('网格滤镜', 'sakurairo_csf'),
                    'filter-dot' => __('点状滤镜', 'sakurairo_csf'),
                ],
                'dependency' => ['cover_switch', '==', 'true', '', 'true'],
                'default' => 'filter-nothing'
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'homepage',
        'title' => __('社交区域', 'sakurairo_csf'),
        'icon' => 'fa fa-share-square-o',
        'fields' => [

            [
                'id' => 'cover_social_switch',
                'type' => 'switcher',
                'title' => __('封面社交栏开关', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'cover_social_icon',
                'type' => 'image_select',
                'title' => __('社交栏图标包', 'sakurairo_csf'),
                'desc' => __('选择你喜欢的图标包。图标包引用信息详见关于主题', 'sakurairo_csf'),
                'dependency' => ['cover_social_switch', '==', 'true', '', 'true'],
                'options'     => [
                    'fluent_design'  => $vision_resource_basepath . 'options/display_icon_fd.gif',
                    'muh2'  => $vision_resource_basepath . 'options/display_icon_h2.gif',
                    'flat_colorful'  => $vision_resource_basepath . 'options/display_icon_fc.gif',
                    // 'remix_iconfont'  => $vision_resource_basepath . 'options/display_icon_svg.webp',
                ],
                'default'     => 'fluent_design'
            ],

            [
                'id'        => 'cover_social_displays',
                'type'      => 'repeater',
                'title'     => __('社交区域展示内容', 'sakurairo_csf'),
                'dependency' => ['cover_social_switch', '==', 'true', '', 'true'],
                'fields'    => [
                    [
                        'id' => 'select',
                        'type' => 'select',
                        'title' => __('显示的图标', 'sakurairo_csf'),
                        'options' => [
                            'qq' => __('QQ', 'sakurairo_csf'),
                            'wechat' => __('微信', 'sakurairo_csf'),
                            'bilibili' => __('bilibili', 'sakurairo_csf'),
                            'netease_music' => __('网易云音乐', 'sakurairo_csf'),
                            'sina' => __('新浪', 'sakurairo_csf'),
                            'github' => __('Github', 'sakurairo_csf'),
                            'telegram' => __('Telegram', 'sakurairo_csf'),
                            'steam' => __('Steam', 'sakurairo_csf'),
                            'youtube' => __('Youtube', 'sakurairo_csf'),
                            'instgram' => __('instgram', 'sakurairo_csf'),
                            'tiktok' => __('抖音', 'sakurairo_csf'),
                            'xiaohongshu' => __('小红书', 'sakurairo_csf'),
                            'discord' => __('Discord', 'sakurairo_csf'),
                            'zhihu' => __('知乎', 'sakurairo_csf'),
                            'linkedin' => __('领英', 'sakurairo_csf'),
                            'twitter' => __('推特/X', 'sakurairo_csf'),
                            'facebook' => __('facebook', 'sakurairo_csf'),
                            'email' => __('邮箱', 'sakurairo_csf'),
                            'custom' => __('自定义', 'sakurairo_csf'),
                        ],
                    ],
                    [
                        'id'   => 'icon',
                        'type' => 'upload',
                        'title' => __('自定义图标', 'sakurairo_csf'),
                    ],
                    [
                        'id'   => 'qrcode',
                        'type' => 'upload',
                        'title' => __('二维码', 'sakurairo_csf'),
                    ],
                    [
                        'id'    => 'title',
                        'type'  => 'text',
                        'title' => __('标题', 'sakurairo_csf'),
                    ],
                    [
                        'id'    => 'link',
                        'type'  => 'text',
                        'title' => __('链接', 'sakurairo_csf'),
                    ],
                ],
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'homepage',
        'title' => __('首页布局', 'sakurairo_csf'),
        'icon' => 'fa fa-bars',
        'fields' => [

            array(
                'id' => 'homepage_components',
                "type" => "select",
                "title" => __("首页布局", "sakurairo_csf"),
                'desc' => __('Select the homepage components you want to display. They will appear in the order above.', 'sakurairo_csf'),
                "chosen" => true,
                "multiple" => true,
                "sortable" => true,
                "options" => array(
                    'show'  => __('展示区域', 'sakurairo_csf'),
                    'post_list'     => __('最新文章', 'sakurairo_csf'),
                    'static_page' => __('自定义页面', 'sakurairo_csf'),
                ),
                "default" => array('show', 'post_list'),
            ),

            [
                'id'          => 'homepage_static_page_id',
                'type'        => 'select',
                'title'       => __('Static Page', 'sakurairo_csf'),
                'placeholder' => __('Select a page', 'sakurairo_csf'),
                'chosen'      => true,
                'options'     => 'pages',
                'dependency'  => ['homepage_components', 'any', 'static_page', '', 'true'],
            ],

            [
                'type' => 'subheading',
                'content' => __('区域标题', 'sakurairo_csf'),
            ],

            [
                'id'        => 'homepage_show_title',
                'type'      => 'fieldset',
                'title'     => __('展示区域标题配置', 'sakurairo_csf'),
                'dependency' => ['cover_focus_style', 'any', 'text,mashiro_text', '', 'true'],
                'fields'    => [
                    [
                        'id'     => 'icon',
                        'type'   => 'text',
                        'title'  => __('图标', 'sakurairo_csf'),
                    ],
                    [
                        'id'     => 'text',
                        'type'   => 'text',
                        'title'  => __('内容', 'sakurairo_csf'),
                    ],
                ],
                'default'        => [
                    'icon'    => 'fa-solid fa-laptop',
                    'text'    => 'Display',
                ],
            ],

            [
                'id'        => 'homepage_post_list_title',
                'type'      => 'fieldset',
                'title'     => __('文章区域标题配置', 'sakurairo_csf'),
                'dependency' => ['cover_focus_style', 'any', 'text,mashiro_text', '', 'true'],
                'fields'    => [
                    [
                        'id'     => 'icon',
                        'type'   => 'text',
                        'title'  => __('图标', 'sakurairo_csf'),
                    ],
                    [
                        'id'     => 'text',
                        'type'   => 'text',
                        'title'  => __('内容', 'sakurairo_csf'),
                    ],
                ],
                'default'        => [
                    'icon'    => 'fa-regular fa-bookmark',
                    'text'    => 'Article',
                ],
            ],

            [
                'id' => 'homepage_component_title_font',
                'type' => 'text',
                'title' => __('区域标题字体', 'sakurairo_csf'),
                'default' => 'Noto Serif SC'
            ],

            [
                'id' => 'homepage_component_title_align',
                'type' => 'image_select',
                'title' => __('首页区域标题位置', 'sakurairo_csf'),
                'options' => [
                    'left' => $vision_resource_basepath . 'options/area_title_text_left.webp',
                    'right' => $vision_resource_basepath . 'options/area_title_text_right.webp',
                    'center' => $vision_resource_basepath . 'options/area_title_text_center.webp',
                ],
                'default' => 'left'
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'homepage',
        'title' => __('展示区域', 'sakurairo_csf'),
        'icon' => 'fa fa-bookmark',
        'fields' => [

            [
                'id'        => 'show_area_content',
                'type'      => 'repeater',
                'title'     => __('展示区域内容', 'sakurairo_csf'),
                'fields'    => [
                    [
                        'id'   => 'img',
                        'type' => 'upload',
                        'title' => __('图片链接', 'sakurairo_csf'),
                    ],
                    [
                        'id'    => 'title',
                        'type'  => 'text',
                        'title' => __('标题', 'sakurairo_csf'),
                    ],
                    [
                        'id'    => 'description',
                        'type'  => 'text',
                        'title' => __('描述', 'sakurairo_csf'),
                    ],
                    [
                        'id'    => 'link',
                        'type'  => 'text',
                        'title' => __('跳转链接', 'sakurairo_csf'),
                    ],
                ],
                'default'   => [
                    [
                        'img' => $vision_resource_basepath . 'series/exhibition2.webp',
                        'title' => '夏霞',
                        'description' => 'あの儚く散る花火の下で、馬鹿みたいに永遠を誓った',
                        'link' => '',
                    ],
                    [
                        'img' => $vision_resource_basepath . 'series/exhibition3.webp',
                        'title' => '雪冴ゆる',
                        'description' => '独りぽっちの冴えない僕を暗闇から連れ出してくれた',
                        'link' => '',
                    ],
                ]
            ]

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'homepage',
        'title' => __('文章区域', 'sakurairo_csf'),
        'icon'      => 'fa fa-book',
        'fields' => [

            [
                'id'         => 'post_card_with_image_design',
                'type'       => 'image_select',
                'title' => __('Article Area Card Design', 'sakurairo_csf'),
                'desc' => __('You can choose between letter design or ticket design', 'sakurairo_csf'),
                'options'    => [
                    'letter' => $vision_resource_basepath . 'options/post_list_design_letter.webp',
                    'ticket' => $vision_resource_basepath . 'options/post_list_design_ticket.webp',
                ],
                'default'    => 'letter'
            ],

            [
                'id' => 'post_card_metas',
                "type" => "select",
                "title" => __("文章卡片显示信息", "sakurairo_csf"),
                "chosen" => true,
                "multiple" => true,
                "sortable" => true,
                "options" => [
                    'author'  => __('作者', 'sakurairo_csf'),
                    'category'     => __('分类', 'sakurairo_csf'),
                    'comment_count' => __('评论数量', 'sakurairo_csf'),
                    'views' => __('浏览量', 'sakurairo_csf'),
                ],
                "default" => ['category', 'comment_count', 'views'],
            ],

            [
                'id' => 'post_card_image',
                'type' => 'radio',
                'title' => __('Article Area Featured Image Options', 'sakurairo_csf'),
                'options' => [
                    'always_with_cover' => __('始终且使用封面API', 'sakurairo_csf'),
                    'always_alone' => __('始终且使用独立API', 'sakurairo_csf'),
                    'only_feather_image' => __('仅特色图片', 'sakurairo_csf'),
                ],
                'default' => 'only_feather_image'
            ],

            [
                'id' => 'post_card_image_url',
                'type' => 'text',
                'title' => __('文章封面随机图API', 'sakurairo_csf'),
                'sanitize' => false,
                'validate' => 'iro_validate_optional_url',
            ],

            [
                'id'        => 'post_card_design',
                'type'      => 'fieldset',
                'title'     => __('文章卡片设计', 'sakurairo_csf'),
                'fields'    => [
                    [
                        'id' => 'card_radius',
                        'type' => 'slider',
                        'title' => __('文章卡片圆角', 'sakurairo_csf'),
                        'step' => '0.01',
                        'unit' => 'rem',
                        'max' => '2',
                    ],

                    [
                        'id' => 'meta_radius',
                        'type' => 'slider',
                        'title' => __('文章卡片元信息圆角', 'sakurairo_csf'),
                        'step' => '0.01',
                        'unit' => 'rem',
                        'max' => '2',
                    ],

                    [
                        'id' => 'title_radius',
                        'type' => 'slider',
                        'title' => __('文章卡片标题圆角', 'sakurairo_csf'),
                        'step' => '0.01',
                        'unit' => 'rem',
                        'max' => '2',
                    ],

                    [
                        'id' => 'title_font_size',
                        'type' => 'slider',
                        'title' => __('文章卡片标题大小', 'sakurairo_csf'),
                        'unit' => 'rem',
                        'step' => '0.01',
                        'min' => '0.5',
                        'max' => '3',
                    ],
                ],
                'default' => [
                    'card_radius'    => '0.7',
                    'meta_radius'    => '0.3',
                    'title_radius'   => '0.3',
                    'title_font_size' => '1.2',
                ],
            ],

            [
                'id' => 'post_list_with_shuoshuo',
                'type' => 'switcher',
                'title' => __('在首页显示说说', 'sakurairo_csf'),
                'default' => true
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id' => 'page',
        'title' => __('文章与页面设置', 'sakurairo_csf'),
        'icon' => 'fa fa-file-text',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'page',
        'title' => __('通用', 'sakurairo_csf'),
        'icon' => 'fa fa-compass',
        'fields' => [

            [
                'id' => 'page_patternimg',
                'type' => 'switcher',
                'title' => __('使用特色图片作为头图', 'sakurairo_csf'),
                'label' => __('启用后当文章或页面有特色图片时，将会用作头图装饰', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'page_title_font_size',
                'type' => 'slider',
                'title' => __('页面标题字体大小（无头图）', 'sakurairo_csf'),
                'step' => '0.01',
                'unit' => 'rem',
                'min' => '1',
                'max' => '4',
                'default' => '2.5'
            ],

            [
                'id' => 'page_title_font_size_with_image',
                'type' => 'slider',
                'title' => __('页面标题字体大小（有头图）', 'sakurairo_csf'),
                'step' => '0.01',
                'unit' => 'rem',
                'min' => '1',
                'max' => '4',
                'default' => '2.5'
            ],

            [
                'id' => 'page_post_toc',
                'type' => 'switcher',
                'title' => __('文章目录', 'sakurairo_csf'),
                'label' => __('在文章页显示目录(检测到内容有标题会自动生成大纲并显示)', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'page_page_toc',
                'type' => 'switcher',
                'title' => __('页面目录', 'sakurairo_csf'),
                'label' => __('在页面显示目录', 'sakurairo_csf'),
                'default' => false
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'page',
        'title' => __('评论区设置', 'sakurairo_csf'),
        'icon' => 'fa fa-comments-o',
        'fields' => [

            [
                'type' => 'subheading',
                'content' => __('评论区外观', 'sakurairo_csf'),
            ],

            [
                'id' => 'comment_input_place_holder',
                'type' => 'text',
                'title' => __('评论区输入框占位符', 'sakurairo_csf'),
                'default' => __('要来喵一句吗？', 'sakurairo_csf')
            ],

            [
                'id' => 'comment_submit_button_text',
                'type' => 'text',
                'title' => __('评论区提交按钮文本', 'sakurairo_csf'),
                'default' => __('Submit✈️', 'sakurairo_csf')
            ],

            [
                'type' => 'subheading',
                'content' => __('评论区功能', 'sakurairo_csf'),
            ],

            [
                'id'       => 'comment_smilies_list',
                'type'     => 'button_set',
                'title' => __('评论区表情', 'sakurairo_csf'),
                'desc' => __('选择要在评论区域输入框中显示的表情。全部取消选中可关闭评论区域输入框表情功能。', 'sakurairo_csf'),
                'multiple' => true,
                'options'  => [
                    'bilibili'   => __('bilibili', 'sakurairo_csf'),
                    'tieba'   => __('贴吧', 'sakurairo_csf'),
                    'yanwenzi' => __('颜文字', 'sakurairo_csf'),
                    'custom' => __('自定义', 'sakurairo_csf'),
                ],
                'default'  => ['bilibili', 'tieba', 'yanwenzi']
            ],

            [
                'id'         => 'comment_smilies_list_custom_name',
                'type'       => 'text',
                'title' => __('自定义表情包名称', 'sakurairo_csf'),
                'desc' => __('建议输入少于4个汉字的内容，以免造成移动端的兼容性问题。', 'sakurairo_csf'),
                'dependency' => ['comment_smilies_list', 'any', 'custom', '', 'true'],
                'default' => 'custom'
            ],

            [
                'id' => 'comment_useragent',
                'type' => 'switcher',
                'title' => __('评论区评论UA', 'sakurairo_csf'),
                'label' => __('开启之后页面评论区域将显示用户的浏览器，操作系统信息', 'sakurairo_csf'),
                'default' => false
            ],

            [
                'id' => 'comment_captcha',
                'type' => 'select',
                'title' => __('评论区验证码', 'sakurairo_csf'),
                'label' => __('开启后游客评论需要通过验证码验证', 'sakurairo_csf'),
                'options' => [
                    'off' => __('Off', 'sakurairo_csf'),
                    'builtin' => __('主题内建验证码', 'sakurairo_csf'),
                    'turnstile' => __('Cloudflare Turnstile', "sakurairo_csf")
                ],
                'default' => 'builtin',
            ],

            [
                'type'    => 'subheading',
                'content' => __('自定义表情包', 'sakurairo_csf'),
            ],

            [
                'id'         => 'comment_smilies_custom',
                'type'       => 'repeater',
                'title'      => __('自定义表情列表', 'sakurairo_csf'),
                'desc'       => __('表情包名称见上方「自定义表情包名称」。每行的「表情名」就是写进评论的标记，形如 {{doge}}', 'sakurairo_csf'),
                'dependency' => ['comment_smilies_list', 'any', 'custom', '', 'true'],
                'fields'     => [
                    [
                        'id'    => 'img',
                        'type'  => 'upload',
                        'title' => __('图片', 'sakurairo_csf'),
                        'desc'  => __('建议用正方形图片，尺寸接近下面的显示高度', 'sakurairo_csf'),
                    ],
                    [
                        'id'         => 'name',
                        'type'       => 'text',
                        'title'      => __('表情名', 'sakurairo_csf'),
                        'desc'       => __('写进评论的标记名，同一表情包内不能重复；不要带空格、花括号等符号', 'sakurairo_csf'),
                        'attributes' => ['placeholder' => 'doge'],
                    ],
                    [
                        'id'    => 'title',
                        'type'  => 'text',
                        'title' => __('提示文案', 'sakurairo_csf'),
                        'desc'  => __('鼠标悬停提示与图片 alt，留空则用表情名', 'sakurairo_csf'),
                    ],
                    [
                        'id'      => 'size',
                        'type'    => 'spinner',
                        'title'   => __('显示高度', 'sakurairo_csf'),
                        'desc'    => __('评论里渲染出来的高度', 'sakurairo_csf'),
                        'min'     => 0,
                        'max'     => 300,
                        'step'    => 1,
                        'unit'    => 'px',
                        'default' => 60,
                    ],
                ],
                'default'    => [],
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'page',
        'title' => __('模板页面设置', 'sakurairo_csf'),
        'icon' => 'fa fa-window-maximize',
        'fields' => [

            [
                'id' => 'page_template_data_cache',
                'type' => 'switcher',
                'title' => __('模板页数据缓存', 'sakurairo_csf'),
                'label' => __('非测试环境建议开启，关闭后渲染页面模板时将实时从目标获取数据，将极大增加源站压力', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'type' => 'subheading',
                'content' => __('追番模板设置', 'sakurairo_csf'),
            ],

            [
                'id' => 'bangumi_source',
                'type' => 'image_select',
                'title' => __('追番数据来源', 'sakurairo_csf'),
                'options' => [
                    'bilibili' => $vision_resource_basepath . 'options/bangumi_tep_bili.webp',
                    'mal' => $vision_resource_basepath . 'options/bangumi_tep_mal.webp',
                    'bangumi' => $vision_resource_basepath . 'options/bangumi_tep_bgm.webp'
                ],
                'default' => 'bilibili'
            ],

            [
                'id' => 'my_anime_list_username',
                'type' => 'text',
                'title' => __('My Anime List 用户名', 'sakurairo_csf'),
                'dependency' => ['bangumi_source', '==', 'myanimelist', '', 'true'],
                'desc' => __('https://myanimelist.net/ 上的用户名', 'sakurairo_csf'),
                'default' => ''
            ],

            [
                'id' => 'my_anime_list_sort',
                'type' => 'radio',
                'title' => __('My Anime List 顺序', 'sakurairo_csf'),
                'dependency' => ['bangumi_source', '==', 'myanimelist', '', 'true'],
                'options' => [
                    '1' => __('状态和上次更新', 'sakurairo_csf'),
                    '2' => __('上次更新', 'sakurairo_csf'),
                    '3' => __('状态', 'sakurairo_csf'),
                ],
                'default' => '1'
            ],

            [
                'id' => 'bilibili_id',
                'type' => 'text',
                'title' => __('Bilibili用户ID', 'sakurairo_csf'),
                'desc' => __('填写你的账户ID，例如 https://space.bilibili.com/13972644/，填写数字部分“13972644”', 'sakurairo_csf'),
                'default' => '13972644'
            ],

            [
                'id' => 'bilibili_cookie',
                'type' => 'text',
                'title' => __('Bilibili账户cookie', 'sakurairo_csf'),
                'desc' => __('填写你的Bilibili账户cookie，如果未设置，则无法显示未公开的状态以及观看进度', 'sakurairo_csf'),
                'default' => ''
            ],

            [
                'id' => 'bilibili_show_private_favlist',
                'type' => 'switcher',
                'title' => __('bilibili收藏显示私人收藏夹', 'sakurairo_csf'),
                'label' => __('开启后将在bilibili收藏夹模板显示私人收藏夹', 'sakurairo_csf'),
                'default' => false
            ],

            [
                'id' => 'bangumi_id',
                'type' => 'text',
                'title' => __('Bangumi 账户ID', 'sakurairo_csf'),
                'desc' => __('填写你的Bangumi账户ID，例如 https://bangumi.tv/user/944883，填写数字部分“944883”', 'sakurairo_csf'),
                'dependency' => ['bangumi_source', '==', 'bangumi', '', 'true'],
                'default' => '944883'
            ],

            [
                'type' => 'subheading',
                'content' => __('友情链接模板设置', 'sakurairo_csf'),
            ],

            [
                'id' => 'friend_link_sorting_mode',
                'type' => 'select',
                'title' => __('友情链接列表排序模式', 'sakurairo_csf'),
                'desc' => __('选择友情链接列表排序模式，默认使用“名称”排序。', 'sakurairo_csf'),
                'options' => [
                    'name' => __('名称', 'sakurairo_csf'),
                    'rating'  => __('评级', 'sakurairo_csf'),
                    'updated'  => __('更新时间', 'sakurairo_csf'),
                    'rand'  => __('随机', 'sakurairo_csf'),
                ],
                'default'     => 'name'
            ],

            [
                'id' => 'friend_link_order',
                'type' => 'select',
                'title' => __('升序或降序', 'sakurairo_csf'),
                'desc' => __('按升序或降序排序友情链接列表', 'sakurairo_csf'),
                'dependency' => ['friend_link_sorting_mode', '!=', 'rand', '', 'true'],
                'options' => [
                    'ASC' => __('升序', 'sakurairo_csf'),
                    'DESC'  => __('降序', 'sakurairo_csf'),
                ],
                'default'     => 'ASC'
            ],

            [
                'type' => 'subheading',
                'content' => __('Steam 库模板设置', 'sakurairo_csf'),
            ],

            [
                'id' => 'steam_id',
                'type' => 'text',
                'title' => __('Steam 账号 64ID', 'sakurairo_csf'),
                'desc' => __('填写你的帐号ID，例如：https://steamcommunity.com/profiles/76561199029689067/, 填写数字部分“76561199029689067”', 'sakurairo_csf'),
            ],

            [
                'id' => 'steam_key',
                'type' => 'text',
                'title' => __('Steam API 密钥', 'sakurairo_csf'),
                'desc' => __('Apply at https://steamcommunity.com/dev/apikey', 'sakurairo_csf'),
            ],

            [
                'id' => 'steam_covercdn',
                'type' => 'select',
                'title' => __('游戏封面 CDN', 'sakurairo_csf'),
                'desc' => __('根据你的目标用户选择加载封面的CDN', 'sakurairo_csf'),
                'options' => [
                    'steamchina' => __('Steam China', 'sakurairo_csf'),
                    'steamakamai'  => __('Steam akamai', 'sakurairo_csf'),
                    'steamfastly'  => __('Steam fastly', 'sakurairo_csf'),
                    'steamcloudflare'  => __('Steam cloudflare', 'sakurairo_csf'),
                ],
                'default'     => 'steamakamai'
            ],

            [
                'id' => 'steam_store',
                'type' => 'select',
                'title' => __('游戏商店链接', 'sakurairo_csf'),
                'desc' => __('选择要跳转到的游戏商店链接', 'sakurairo_csf'),
                'options' => [
                    'steam' => __('Steam', 'sakurairo_csf'),
                    'xiaoheihe'  => __('XiaoHeiHe', 'sakurairo_csf'),
                    'steamdb'  => __('SteamDB', 'sakurairo_csf'),
                ],
                'default'     => 'steam'
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id' => 'others',
        'title' => __('其他设置', 'sakurairo_csf'),
        'icon' => 'fa fa-coffee',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'others',
        'title' => __('主题定制', 'sakurairo_csf'),
        'icon' => 'fa fa-sign-in',
        'fields' => [

            [
                'type' => 'subheading',
                'content' => __('登录页', 'sakurairo_csf'),
            ],

            [
                'id' => 'login_custom_switch',
                'type' => 'switcher',
                'title' => __('定制登录页', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'id' => 'login_logo_img',
                'type' => 'upload',
                'title' => __('登录页Logo', 'sakurairo_csf'),
                'dependency' => ['custom_login_switch', '==', 'true', '', 'true'],
                'library' => 'image',
                'default' => $vision_resource_basepath . 'series/login_logo.webp'
            ],

            [
                'id' => 'login_captcha_select',
                'type' => 'select',
                'title' => __('登录页验证码', 'sakurairo_csf'),
                'options' => [
                    'off' => __('Off', 'sakurairo_csf'),
                    'builtin' => __('主题内建验证码', 'sakurairo_csf'),
                    'turnstile' => __('Cloudflare Turnstile', "sakurairo_csf")
                ],
                'default' => 'off',
            ],

            [
                'id' => 'login_urlskip',
                'type' => 'switcher',
                'title' => __('登录后跳转', 'sakurairo_csf'),
                'label' => __('开启之后管理员跳转至后台，用户跳转至主页。', 'sakurairo_csf'),
                'default' => false
            ],

            [
                'id' => 'login_language_opt',
                'type' => 'switcher',
                'title' => __('登录界面语言选项', 'sakurairo_csf'),
                'label' => __('开启之后登录界面将显示语言选项', 'sakurairo_csf'),
                'default' => false
            ],

            [
                'type' => 'subheading',
                'content' => __('仪表盘', 'sakurairo_csf'),
            ],

            [
                'id' => 'admin_background',
                'type' => 'upload',
                'title' => __('仪表盘背景图片', 'sakurairo_csf'),
                'desc' => __('设置你的仪表盘背景图片，此选项留空则显示白色背景', 'sakurairo_csf'),
                'library' => 'image',
                'default' => $vision_resource_basepath . 'series/admin_background.webp'
            ],

            [
                'id' => 'admin_left_style',
                'type' => 'image_select',
                'title' => __('仪表板设置菜单样式', 'sakurairo_csf'),
                'options' => [
                    'v1' => $vision_resource_basepath . 'options/admin_left_style_v1.webp',
                    'v2' => $vision_resource_basepath . 'options/admin_left_style_v2.webp',
                ],
                'default' => 'v1'
            ],

            [
                'id' => 'admin_first_class_color',
                'type' => 'color',
                'title' => __('仪表盘一级菜单颜色', 'sakurairo_csf'),
                'default' => '#081018'
            ],

            [
                'id' => 'admin_second_class_color',
                'type' => 'color',
                'title' => __('仪表盘二级菜单颜色', 'sakurairo_csf'),
                'default' => '#111111'
            ],

            [
                'id' => 'admin_emphasize_color',
                'type' => 'color',
                'title' => __('仪表板强调颜色', 'sakurairo_csf'),
                'default' => '#debd9c'
            ],

            [
                'id' => 'admin_text_color',
                'type' => 'color',
                'title' => __('仪表盘文本颜色', 'sakurairo_csf'),
                'default' => '#FFFFFF'
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'others',
        'title' => __('安全设置', 'sakurairo_csf'),
        'icon' => 'fa-solid fa-shield-halved',
        'fields' => [

            [
                'id' => 'builtin_captcha_level',
                'type' => 'slider',
                'title' => __('内建验证码强度', 'sakurairo_csf'),
                'desc' => __('改变内建验证码的图片混乱度', 'sakurairo_csf'),
                'step' => '1',
                'min' => '0',
                'max' => '100',
                'default' => '60'
            ],

            [
                'id' => 'turnstile_site_key',
                'type' => 'text',
                'title' => __('Turnstile Site Key', "sakurairo_csf"),
            ],

            [
                'id' => 'turnstile_secret_key',
                'type' => 'text',
                'title' => __('Turnstile Secret Key', "sakurairo_csf"),
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'others',
        'title' => __('Low Use Options', 'sakurairo_csf'),
        'icon' => 'fa fa-low-vision',
        'fields' => [

            [
                'id' => 'statistics_api',
                'type' => 'radio',
                'title' => __('Statistics API', 'sakurairo_csf'),
                'desc' => __('You can choose WP-Statistics plugin statistics or theme built-in statistics to display', 'sakurairo_csf'),
                'options' => [
                    'theme_build_in' => __('Theme Built in Statistics', 'sakurairo_csf'),
                    'wp_statistics' => __('WP-Statistics Plugin Statistics', 'sakurairo_csf'),
                ],
                'default' => 'theme_build_in'
            ],

            [
                'id' => 'statistics_format',
                'type' => 'select',
                'title' => __('Statistics display format', 'sakurairo_csf'),
                'desc' => __('You can choose from four different data display formats', 'sakurairo_csf'),
                'options' => [
                    'type_1' => __('23333 Visits', 'sakurairo_csf'),
                    'type_2' => __('23,333 Visits', 'sakurairo_csf'),
                    'type_3' => __('23 333 Visits', 'sakurairo_csf'),
                    'type_4' => __('23K Visits', 'sakurairo_csf'),
                ],
                'default' => 'type_1'
            ],

            [
                'id' => 'custom_site_header',
                'type'     => 'code_editor',
                'sanitize' => false,
                'title' => __('自定义插入 Header 代码', 'sakurairo_csf'),
                'desc' => __('填入需要高优先级加载的站点内容，可能会阻塞加载，如果不需要高优先级可以在页脚设置中插入', 'sakurairo_csf'),
            ],

            [
                'id' => 'gravatar_proxy',
                'type' => 'select',
                'title' => __('Gravatar服务代理', 'sakurairo_csf'),
                'desc' => __('你可以选择多种代理作为 Gravatar 服务代理。默认使用 Weavatar 作为 Gravatar 服务代理。', 'sakurairo_csf'),
                'options'     => [
                    'weavatar.com/avatar'  => __('Weavatar Service', 'sakurairo_csf'),
                    'gravatar.loli.net/avatar'  => __('Loli Net', 'sakurairo_csf'),
                    'gravatar.com/avatar'  => __('Gravatar官方', 'sakurairo_csf'),
                    'custom_proxy_address_of_gravatar' => __('自定义代理地址', 'sakurairo_csf'),
                ],
                'default'     => 'weavatar.com/avatar'
            ],

            [
                'id' => 'custom_proxy_address_of_gravatar',
                'type' => 'text',
                'title' => __('Custom Proxy Address', 'sakurairo_csf'),
                'desc' => __('Enter your Gravatar proxy address without starting with "http(s)://" and ending with "/". Example: gravatar.com/avatar.', 'sakurairo_csf'),
                'dependency' => ['gravatar_proxy', '==', 'custom_proxy_address_of_gravatar', '', 'true'],
                'default'     => 'gravatar.com/avatar'
            ],

            [
                'type' => 'subheading',
                'content' => __('Lightbox', 'sakurairo_csf'),
            ],

            [
                'id' => 'lightbox',
                'type' => 'select',
                'title' => __('lightbox', 'sakurairo_csf'),
                'desc' => __('请选择你需要使用的灯箱效果，wordpress在6.4后已正式支持灯箱效果，此处仅提供另一种可选的效果，其他的可以自行按需安装插件', 'sakurairo_csf'),
                'options'     => [
                    'off'  => __('off', 'sakurairo_csf'),
                    'medium_zoom'  => __('Medium Zoom', 'sakurairo_csf'),
                ],
                'default'     => 'off'
            ],

            [
                'type' => 'subheading',
                'content' => __('代码高亮', 'sakurairo_csf'),
            ],

            [
                'id' => 'code_highlight_method',
                'type' => 'select',
                'title' => __('Code Highlight Method', 'sakurairo_csf'),
                'options' => [
                    'off' => __('关闭', 'sakurairo_csf'),
                    'hljs' => 'highlight.js',
                ],
                "default" => "hljs"
            ],

            [
                'id' => 'code_katex',
                'type' => 'switcher',
                'title' => __('启用公式支持', 'sakurairo_csf'),
                'label' => __('启用主题公式支持，使用Katex，需要写入markdown区块才能渲染', 'sakurairo_csf'),
                'default' => true
            ],

            [
                'type' => 'submessage',
                'style' => 'danger',
                'content' => __('以下设置不推荐盲目进行修改，请确保你知道它是在做什么', 'sakurairo_csf'),
            ],

            [
                'id' => 'image_cdn',
                'type' => 'text',
                'title' => __('Image CDN', 'sakurairo_csf'),
                'desc' => __('Note: fill in the format https://cdn.example.org, DO NOT add a slash at the end of the url. This means that images with original path http://cdn.example.org/wp-content/uploads/2018/05/xx.png will be loaded from http://cdn.example.org/2018/05/xx.png', 'sakurairo_csf'),
                'default' => ''
            ],

            [
                'id' => 'fontawesome_source',
                'type' => 'text',
                'title' => __('Fontawesome Source', 'sakurairo_csf'),
                'desc' => __('The source link of Fontawesome icons style', 'sakurairo_csf'),
                'default' => "https://s4.zstatic.net/ajax/libs/font-awesome/6.7.2/css/all.min.css",
            ],

            [
                'id'    => 'dev_mode',
                'type'  => 'switcher',
                'title' => __('开发者模式', 'sakurairo_csf'),
                'label'   => __('启用并配置你的vite HMR客户端来正常使用', 'sakurairo_csf'),
                'default' => false,
            ],

            [
                'id' => 'dev_mode_hmr_client',
                'type' => 'text',
                'title' => __('Vite HMR客户端地址', 'sakurairo_csf'),
                'default' => 'https://wordpress:5173/@vite/client',
            ],

            [
                'id' => 'dev_mode_main_js',
                'type' => 'text',
                'title' => __('Vite 主脚本入口地址', 'sakurairo_csf'),
                'default' => "https://wordpress:5173/main.js",
            ],

            [
                'id' => 'php_notice_filter',
                'type' => 'select',
                'title' => __('PHP日志过滤', 'sakurairo_csf'),
                'options' => [
                    'inner' => __('使用PHP配置', 'sakurairo_csf'),
                    'normal' => __('只显示严重错误', 'sakurairo_csf'),
                    'all' => __('过滤大部分错误', 'sakurairo_csf'),
                ],
                "default" => "normal",
                'desc' => __('建议设置为“只显示严重错误”来防止不影响使用的php日志渲染到前端', 'sakurairo_csf'),
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'title' => __('Backup&Recovery', 'sakurairo_csf'),
        'icon'        => 'fa fa-shield',
        'description' => __('备份或恢复你的主题设置', 'sakurairo_csf'),
        'fields'      => [

            [
                'type' => 'backup',
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'title' => __('About Theme', 'sakurairo_csf'),
        'icon'        => 'fa fa-paperclip',
        'fields'      => [

            [
                'type'    => 'subheading',
                'content' => __('Version Info', 'sakurairo_csf'),
            ],

            [
                'type'    => 'content',
                'content' => __('<img src="https://s.nmxc.ltd/sakurairo_vision/@3.0/series/headlogo.webp"  alt="Theme Information" />', 'sakurairo_csf'),
            ],

            [
                'type'    => 'submessage',
                'style'   => 'normal',
                'content' => sprintf(__('Theme Sakurairo Version %s | Internal Version %s | <a href="https://github.com/mirai-mamori/Sakurairo">Project Address</a>', 'sakurairo_csf'), IRO_VERSION, INT_VERSION),
            ],

            [
                'type'    => 'subheading',
                'content' => __('Update Related', 'sakurairo_csf'),
            ],

            [
                'id'          => 'iro_update_source',
                'type'        => 'image_select',
                'title' => __('Theme Update Source', 'sakurairo_csf'),
                'options'     => [
                    'github'  => $vision_resource_basepath . 'options/update_source_github.webp',
                    'upyun'  => $vision_resource_basepath . 'options/update_source_wafpro.webp',
                    'official_building'  => $vision_resource_basepath . 'options/update_source_iro.webp',
                ],
                'desc' => __('If you are using a server set up in mainland China, please use the Upyun source or the official theme source as your theme update source', 'sakurairo_csf'),
                'default'     => 'github'
            ],

            [
                'id' => 'channel_validate_value',
                'type' => 'text',
                'title' => __('Theme Update Test Channel Disclaimer', 'sakurairo_csf'),
                'dependency' => [
                    ['core_library_basepath', '==', 'true', '', 'true'],
                    ['shared_library_basepath', '==', 'true'],
                    ['iro_update_source', '==', 'official_building'],
                ],
                'desc' => __('Please copy the text in quotes after <strong>ensure that you have carefully understood the risks associated with participating in the test and are willing to assume all consequences at your own risk</strong> (including but not limited to possible data loss) into the options text box <strong> "I agree and am willing to bear all unexpected consequences"</strong>', 'sakurairo_csf'),
            ],

            [
                'id' => 'iro_update_channel',
                'type' => 'radio',
                'title' => __('Theme Update Channel', 'sakurairo_csf'),
                'dependency' => [
                    ['channel_validate_value', '==', 'I agree and am willing to bear all unexpected consequences'],
                    ['core_library_basepath', '==', 'true', '', 'true'],
                    ['shared_library_basepath', '==', 'true'],
                    ['iro_update_source', '==', 'official_building'],
                ],
                'desc' => __('You can toggle the update channel here to participate in the testing of the new version', 'sakurairo_csf'),
                'options' => [
                    'stable' => __('Stable Channel', 'sakurairo_csf'),
                    'beta' => __('Beta Channel', 'sakurairo_csf'),
                    'preview' => __('Preview Channel', 'sakurairo_csf'),
                ],
                'default' => 'stable'
            ],

            [
                'type' => 'subheading',
                'content' => __('Resource Control', 'sakurairo_csf'),
            ],

            [
                'id' => 'vision_resource_basepath',
                'type' => 'text',
                'title' => __('Vision Resource Basepath', 'sakurairo_csf'),
                'desc' => __('This link directory structure needs to be consistent with the <a href="https://github.com/Fuukei/Sakurairo_Vision">Sakurairo Vision</a> repositories officially provided by fuukei, otherwise some resources 404 may appear. The image source officially provided by <a href="https://waf.pro/">WAFPRO</a> is adopted by default.', 'sakurairo_csf'),
                'default' => "https://s.nmxc.ltd/sakurairo_vision/@3.0/"
            ],

            [
                'type' => 'subheading',
                'content' => __('Theme Contributors', 'sakurairo_csf'),
            ],

            [
                'type'    => 'content',
                'content' => __('<img src="https://fuukei-api.nyat.icu/api/contributors" alt="Theme Contributors" width="100%" height="100%" />', 'sakurairo_csf'),
            ],

            [
                'type' => 'subheading',
                'content' => __('隐私信息', 'sakurairo_csf'),
            ],

            [
                'type'    => 'content',
                'content' => __('<p>主题尊重你的隐私</p>
        <p>但是，当你使用主题预置的中国大陆服务商提供的服务时，服务商可能会收集有关你的访问者的数据并统计数据</p>
        <p>你可以通过本地化与主题相关的资源来减少发送给第三方的信息，主题提供相关选项配置</p>', 'sakurairo_csf'),
            ],

            [
                'id' => 'send_theme_version',
                'type' => 'switcher',
                'title' => __('Send Theme Version to Fuukei', 'sakurairo_csf'),
                'label' => __('The theme will only send time and version information to Fuukei officials and the data will be cleaned regularly and used only to count version updates.', 'sakurairo_csf'),
                'default' => false
            ],

            [
                'type' => 'subheading',
                'content' => __('引用信息', 'sakurairo_csf'),
            ],

            [
                'type'    => 'content',
                'content' => __('<p>Fluent Design Icon Referenced by Paradox Fluent Icon Pack</p>
        <p>MUH2 Design Icon Referenced by 缄默 <a href="https://www.coolapk.com/apk/com.muh2.icon">MUH2 Icon Pack</a></p>', 'sakurairo_csf'),
            ],

            [
                'type'    => 'subheading',
                'content' => __('依赖信息', 'sakurairo_csf'),
            ],

            [
                'type'    => 'content',
                'content' => __('<p>Options Framework Relies on the Codestar Open Source <a href="https://github.com/Codestar/codestar-framework">Codestar Framework</a> Project</p>
        <p>Update Function Relies on YahnisElsts Open Source <a href="https://github.com/YahnisElsts/plugin-update-checker">Plugin Update Checker</a> Project</p>
        <p>Visual Editor Related Functions Relies on Themeum Open Source <a href="https://github.com/themeum/kirki">Kirki</a> Project</p>', 'sakurairo_csf'),
            ],

            [
                'type'    => 'content',
                'content' => __('<img src="https://img.shields.io/github/v/release/mirai-mamori/Sakurairo.svg?style=flat-square"  alt="Theme latest version" style="border-radius: 3px;" />  <img src="https://img.shields.io/github/release-date/mirai-mamori/Sakurairo?style=flat-square"  alt="Theme latest version release date" style="border-radius: 3px;" />  <img src="https://data.jsdelivr.com/v1/package/gh/mirai-mamori/Sakurairo/badge"  alt="Theme CDN resource access" style="border-radius: 3px;" />', 'sakurairo_csf'),
            ],

        ]
    ]);
}
