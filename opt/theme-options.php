<?php
if (class_exists('Sakurairo_CSF')) {

    $prefix = 'iro_options';

    $vision_resource_basepath = get_option('iro_options')['vision_resource_basepath'] ?? 'https://s.nmxc.ltd/sakurairo_vision/@3.0/';

    Sakurairo_CSF::createOptions($prefix, [
        'menu_title' => __('iro主题设置', 'sakurairo'),
        'menu_slug'  => 'iro_options',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'title' => __('欢迎！', 'sakurairo'),
        'icon'        => 'fa fa-podcast',
        'fields'      => [

            [
                'type'    => 'heading',
                'content' => __('感谢每一位支持我们的人！', 'sakurairo'),
            ],

            [
                'type'    => 'content',
                'content' => __('<a href="https://afdian.com/a/mamori"><img alt="afdian" height="50" src="https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/afdian.webp"></a><a href="https://liberapay.com/furina/donate"><img alt="liberapay" height="50" src="https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/liberapay.webp"></a><a href="https://app.unifans.io/c/somekawahitomi"><img alt="unifans" height="50" src="https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/unifans.webp"></a>', 'sakurairo'),
            ],

            [
                'type'    => 'content',
                'content' => __('<img src="https://fuukei-api.nyat.icu/api/sponsors"  alt="Sponsor" width="100%" height="100%" />', 'sakurairo'),
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id'    => 'preliminary',
        'title' => __('基本设置', 'sakurairo'),
        'icon'      => 'fa fa-sliders',
        'fields' => [
            [
                'id'    => 'favicon_link',
                'type'  => 'upload',
                'library'      => 'image',
                'title' => __('站点图标', 'sakurairo'),
                'desc'   => __('填写链接，它将会出现在浏览器标签页的标题旁边', 'sakurairo'),
                'default' => $vision_resource_basepath . 'basic/favicon.ico'
            ],

            [
                'id'    => 'iro_seo',
                'type'  => 'select',
                'title' => __('自动SEO', 'sakurairo'),
                'options'     => [
                    'off'  => __('不使用主题SEO', 'sakurairo'),
                    'auto'  => __('自动完善SEO', 'sakurairo'),
                    'on'  => __('总是加上所有SEO', 'sakurairo'),
                ],
                'desc'   => __('如果启用，主题将根据情况决定是否加上SEO相关页面meta属性', 'sakurairo'),
                "default" => "on",
            ],

            [
                'id' => 'iro_sitemap',
                'type' => 'switcher',
                'title' => __('使用主题提供的站点地图', 'sakurairo'),
                'default' => false
            ],

            [
                'id'     => 'iro_meta_keywords',
                'type'   => 'text',
                'title'  => __('站点关键词', 'sakurairo'),
                'dependency' => ['iro_seo', '!=', 'off', '', 'true'],
                'desc'   => __('使用英文逗号分隔，并尽量控制在五个词以内', 'sakurairo'),
            ],

            [
                'id'     => 'iro_meta_description',
                'type'   => 'text',
                'title'  => __('站点描述', 'sakurairo'),
                'dependency' => ['iro_seo', '!=', 'off', '', 'true'],
                'desc'   => __('提供一些关于网站内容的描述，控制在120字以内，它将出现在搜索引擎搜索结果条目的下方', 'sakurairo'),
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id'    => 'global',
        'title' => __('全局设置', 'sakurairo'),
        'icon'      => 'fa fa-globe',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title'  => __('外观设置', 'sakurairo'),
        'icon'      => 'fa fa-tree',
        'fields' => [
            [
                'type'    => 'subheading',
                'content' => __('主题配色', 'sakurairo'),
            ],

            // [
            //     'id' => 'extract_theme_skin_from_cover',
            //     'type' => 'switcher',
            //     'title' => __('Extract Theme Color from Cover Image', 'sakurairo'),
            //     'label' => __('Default on, Following options will be used as fallback (while cover image cannot be read by scripts)', 'sakurairo'),
            //     'default' => true
            // ],

            // [
            //     'id' => 'extract_article_highlight_from_feature',
            //     'type' => 'switcher',
            //     'title' => __('Extract Article Highlight from Featured Image', 'sakurairo'),
            //     'label' => __('Default on, The colors displayed on the article page will be taken from the article featured image', 'sakurairo'),
            //     'default' => true
            // ],

            [
                'id'      => 'word_color_first',
                'type'    => 'color',
                'title'   => __('主要文字颜色', 'sakurairo'),
                'desc'    => __('文章标题和正文内容等文字的颜色', 'sakurairo'),
                'default' => '#505050'
            ],

            [
                'id'      => 'word_color_second',
                'type'    => 'color',
                'title'   => __('次要文字颜色', 'sakurairo'),
                'desc'    => __('帮助和页脚等文字的颜色', 'sakurairo'),
                'default' => '#00000080'
            ],

            [
                'id'      => 'active_color',
                'type'    => 'color',
                'title'   => __('激活组件颜色', 'sakurairo'),
                'desc'    => __('鼠标悬浮链接以及按钮和高亮标签等部分的颜色', 'sakurairo'),
                'default' => '#00b0f0'
            ],

            [
                'id'      => 'code_block_background_color',
                'type'    => 'color',
                'title'   => __('代码块背景色', 'sakurairo'),
                'default' => '#e1e4e8'
            ],

            [
                'id'     => 'widget_transparency',
                'type'   => 'slider',
                'title'  => __('组件透明度', 'sakurairo'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.8'
            ],

            [
                'id'     => 'background_transparency',
                'type'   => 'slider',
                'title'  => __('背景透明度', 'sakurairo'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.8'
            ],

            [
                'id'     => 'background_blur',
                'type'   => 'slider',
                'title'  => __('背景模糊度', 'sakurairo'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.7'
            ],

            [
                'type'    => 'subheading',
                'content' => __('深色模式', 'sakurairo'),
            ],

            [
                'id'      => 'word_color_first_dark',
                'type'    => 'color',
                'title'   => __('主要文字颜色', 'sakurairo'),
                'desc'    => __('文章标题和正文内容等文字的颜色', 'sakurairo'),
                'default' => '#CCCCCC'
            ],

            [
                'id'      => 'word_color_second_dark',
                'type'    => 'color',
                'title'   => __('次要文字颜色', 'sakurairo'),
                'desc'    => __('帮助和页脚等文字的颜色', 'sakurairo'),
                'default' => '#7d7d7d'
            ],

            [
                'id'      => 'active_color_dark',
                'type'    => 'color',
                'title'   => __('激活组件颜色', 'sakurairo'),
                'desc'    => __('鼠标悬浮链接以及按钮和高亮标签等部分的颜色', 'sakurairo'),
                'default' => '#FCCD00'
            ],

            [
                'id'      => 'code_block_background_color_dark',
                'type'    => 'color',
                'title'   => __('代码块背景色', 'sakurairo'),
                'default' => '#24292e'
            ],

            [
                'id'     => 'widget_transparency_dark',
                'type'   => 'slider',
                'title'  => __('组件透明度', 'sakurairo'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.8'
            ],

            [
                'id'     => 'background_transparency_dark',
                'type'   => 'slider',
                'title'  => __('背景透明度', 'sakurairo'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.7'
            ],

            [
                'id'     => 'image_bright_dark',
                'type'   => 'slider',
                'title'  => __('深色模式图像亮度', 'sakurairo'),
                'step'   => '0.01',
                'min'   => '0',
                'max'   => '1',
                'default' => '0.7'
            ],

            [
                'id'    => 'theme_darkmode_auto',
                'type'  => 'switcher',
                'title' => __('自动切换深色模式', 'sakurairo'),
                'default' => true
            ],

            [
                'type'    => 'content',
                'content' => __(
                    '<p><strong>Client local time:</strong>Dark mode will switch on automatically from 22:00 to 7:00</p>'
                        . '<p><strong>Follow client settings:</strong>Follow client browser settings</p>'
                        . '<p><strong>Always on:</strong>Always on, except being configured by the client</p>',
                    'sakurairo'
                ),
                'dependency' => ['theme_darkmode_auto', '==', 'true', '', 'true'],

            ],

            [
                'id' => 'theme_commemorate_mode_date',
                'type' => 'textarea',
                'title' => __('纪念模式日期', 'sakurairo'),
                'desc' => __('一行一个，例如7-21，主题会在这些日期加上黑白滤镜', 'sakurairo'),
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title'  => __('字体设置', 'sakurairo'),
        'icon'      => 'fa fa-font',
        'fields' => [
            [
                'type'    => 'subheading',
                'content' => __('基本设置', 'sakurairo'),
            ],

            [
                'id'     => 'global_font_size',
                'type'   => 'slider',
                'title'  => __('字体大小', 'sakurairo'),
                'desc'   => __('此处以像素为单位，主题大部分组件会以此为基础调整自身字体大小，以实现等比缩放的效果', 'sakurairo'),
                'step'   => '0.1',
                'unit'    => 'px',
                'min'   => '1',
                'max'   => '64',
                'default' => '16'
            ],

            [
                'id'     => 'global_font_weight',
                'type'   => 'slider',
                'title'  => __('非强调文本字重', 'sakurairo'),
                'desc'   => __('Slide to adjust, the recommended value range is 300-500', 'sakurairo'),
                'step'   => '10',
                'min'   => '100',
                'max'   => '1000',
                'default' => '300'
            ],

            [
                'id'     => 'global_default_font',
                'type'   => 'text',
                'title'  => __('默认字体', 'sakurairo'),
            ],

            [
                'type'    => 'subheading',
                'content' => __('外部字体', 'sakurairo'),
            ],

            [
                'id'        => 'extra_fonts',
                'type'      => 'repeater',
                'title'     => __('额外字体', 'sakurairo'),
                'fields'    => [
                    [
                        'id'    => 'font_name',
                        'type'  => 'text',
                        'title' => __('字体名称', 'sakurairo'),
                    ],
                    [
                        'id'    => 'link',
                        'type'  => 'text',
                        'title' => __('字体链接', 'sakurairo'),
                    ],
                ],
            ]
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title'  => __('导航栏', 'sakurairo'),
        'icon'      => 'fa fa-map-signs',
        'fields' => [
            [
                'id'    => 'nav_logo',
                'type'  => 'upload',
                'title' => __('导航栏logo', 'sakurairo'),
                'library'      => 'image',
            ],

            [
                'id' => 'nav_title',
                'type' => 'text',
                'title' => __('导航栏标题', 'sakurairo'),
            ],

            [
                'id' => 'nav_title_font',
                'type' => 'text',
                'title' => __('导航栏标题字体', 'sakurairo'),
            ],

            [
                'id' => 'nav_option_font',
                'type' => 'text',
                'title' => __('导航栏选项字体', 'sakurairo'),
            ],

            [
                'id'    => 'navbar_distribution',
                'type'  => 'select',
                'title' => __('导航栏选项分布位置', 'sakurairo'),
                'options'     => [
                    'left'  => __('左', 'sakurairo'),
                    'center'  => __('中', 'sakurairo'),
                    'right'  => __('右', 'sakurairo'),
                    'space-evenly'  => __('均匀', 'sakurairo'),
                ],
                "default" => "off",
            ],

            [
                'id' => 'navbar_option_margin',
                'type' => 'slider',
                'title' => __('导航栏选项间距', 'sakurairo'),
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '2',
                'default' => '0.3',
            ],

            [
                'id' => 'nav_menu_cover_radius',
                'type' => 'slider',
                'title' => __('导航栏菜单圆角', 'sakurairo'),
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '2',
                'default' => '0.3',
            ],

            [
                'id' => 'nav_menu_cover_switch',
                'type' => 'switcher',
                'title' => __('导航栏封面切换按钮', 'sakurairo'),
                'default' => true
            ],

            [
                'id'    => 'nav_user_menu',
                'type'  => 'switcher',
                'title' => __('导航栏用户栏', 'sakurairo'),
                'default' => true
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title' => __('前台设置', 'sakurairo'),
        'icon' => 'fa fa-th-large',
        'fields' => [

            [
                'type' => 'subheading',
                'content' => __('工具栏', 'sakurairo'),
            ],

            [
                'id' => 'widget_button_radius',
                'type' => 'slider',
                'title' => __('工具栏按钮圆角', 'sakurairo'),
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '3',
                'default' => '0.6'
            ],

            [
                'id' => 'widget_panel_radius',
                'type' => 'slider',
                'title' => __('工具栏面板圆角', 'sakurairo'),
                'step' => '0.01',
                'unit' => 'rem',
                'max' => '2',
                'default' => '0.6'
            ],

            [
                'id' => 'widget_font',
                'type' => 'text',
                'title' => __('工具栏字体', 'sakurairo'),
            ],

            [
                'id' => 'widget_wordpress_widget',
                'type' => 'switcher',
                'title' => __('工具栏wordpress组件', 'sakurairo'),
                'label' => __('启用后将会显示wordpress可编辑工具栏', 'sakurairo'),
                'desc' => __('你可以前往<a href="/wp-admin/widgets.php"> 此处 </a>编辑', 'sakurairo'),
                'default' => false
            ],

            [
                'id' => 'widget_darkmode_switch',
                'type' => 'switcher',
                'title' => __('工具栏深色模式切换按钮', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'widget_font_switch',
                'type' => 'switcher',
                'title' => __('工具栏字体切换按钮', 'sakurairo'),
                'default' => true
            ],

            [
                'id'        => 'widget_font_choice',
                'type'      => 'repeater',
                'title'     => __('工具栏可选字体', 'sakurairo'),
                'desc' => __('请使用有效的字体名称，需要在全局字体设置中添加对应名称的额外字体才能生效', 'sakurairo'),
                'fields'    => [
                    [
                        'id'    => 'name',
                        'type'  => 'text',
                        'title' => __('字体名称', 'sakurairo'),
                    ],
                ],
            ],

            [
                'type' => 'subheading',
                'content' => __('前台背景', 'sakurairo'),
            ],

            [
                'id'    => 'frontend_default_background',
                'type'  => 'upload',
                'title' => __('前台默认背景', 'sakurairo'),
                'library'      => 'image',
            ],

            [
                'id'    => 'frontend_background_fill_mode',
                'type'  => 'select',
                'title' => __('前台背景填充模式', 'sakurairo'),
                'desc' => __('根据你选择的图片类型选择合适的填充方案，插画为缩放至填充满，纹理为复制并铺满', 'sakurairo'),
                'options'     => [
                    'pattern'  => __('插画', 'sakurairo'),
                    'texture'  => __('纹理', 'sakurairo'),
                ],
                "default" => "pattern",
            ],

            [
                'id'    => 'frontend_particle',
                'type'  => 'select',
                'title' => __('前台背景粒子特效', 'sakurairo'),
                'options'     => [
                    'off'  => __('关闭', 'sakurairo'),
                    'sakura'  => __('樱花', 'sakurairo'),
                    'snow'  => __('雪', 'sakurairo'),
                    'custom'  => __('自定义', 'sakurairo'),
                ],
                "default" => "off",
            ],

            [
                'id'        => 'frontend_particle_builtin',
                'type'      => 'fieldset',
                'title'     => __('内建粒子特效选项', 'sakurairo'),
                'desc' => __('请使用有效的字体名称，需要在全局字体设置中添加对应名称的额外字体才能生效', 'sakurairo'),
                'dependency' => ['frontend_particle', 'any', 'sakura,snow', '', 'true'],
                'fields'    => [
                    [
                        'id'     => 'amount',
                        'type'   => 'slider',
                        'title'  => __('粒子数量', 'sakurairo'),
                        'step'   => '1',
                        'min'   => '10',
                        'max'   => '100',
                    ],
                    [
                        'id'     => 'minsize',
                        'type'   => 'slider',
                        'title'  => __('粒子最小大小', 'sakurairo'),
                        'step'   => '1',
                        'min'   => '1',
                        'max'   => '100',
                    ],
                    [
                        'id'     => 'maxsize',
                        'type'   => 'slider',
                        'title'  => __('粒子最大大小', 'sakurairo'),
                        'step'   => '1',
                        'min'   => '30',
                        'max'   => '100',
                    ],
                    [
                        'id'     => 'speed',
                        'type'   => 'slider',
                        'title'  => __('粒子速度', 'sakurairo'),
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
                'title' => __('自定义粒子特效实现', 'sakurairo'),
                'dependency' => ['frontend_particle', '==', 'custom', '', 'true'],
                'desc' => __('参考tsParticle', 'sakurairo'),
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title' => __('页尾设置', 'sakurairo'),
        'icon' => 'fa fa-caret-square-o-down',
        'fields' => [
            [
                'id' => 'footer_sakura',
                'type' => 'switcher',
                'title' => __('页尾樱花', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'footer_font',
                'type' => 'text',
                'title' => __('页尾字体', 'sakurairo'),
            ],

            [
                'id' => 'footer_html',
                'type'     => 'code_editor',
                'sanitize' => false,
                'title' => __('页尾html代码', 'sakurairo'),
                'desc' => __('可以在此处编写页脚内容，也可以加入能接受延迟加载的统计代码，请确保它们安全', 'sakurairo'),
            ],

            [
                'type' => 'subheading',
                'content' => __('一言', 'sakurairo'),
            ],

            [
                'id'    => 'footer_hitokoto_select',
                'type'  => 'select',
                'title' => __('页脚一言', 'sakurairo'),
                'options'     => [
                    'off'  => __('关闭', 'sakurairo'),
                    'api'  => __('总是使用API', 'sakurairo'),
                    'custom'  => __('总是自定义', 'sakurairo'),
                    'both'  => __('各一半', 'sakurairo'),
                ],
                "default" => "off",
            ],

            [
                'type' => 'content',
                'dependency' => ['footer_hitokoto_select', '!=', 'off', '', 'true'],
                'content' => __('<h4>Hitokoto API Setup Instructions</h4>'
                    . ' <p>Fill in as the example:<code> ["https://v1.hitokoto.cn/", "https://v1.hitokoto.cn/"]</code>, where the first API will be used first and the next ones will be the backup. </p>'
                    . ' <p><strong>Official API:</strong> See the <a href="https://developer.hitokoto.cn/sentence/"> documentation</a> for how to use it, and the parameter "return code" should not be anything except JSON. <a href="https://v1.hitokoto.cn/">https://v1.hitokoto.cn/</a></p>', 'sakurairo'),
            ],

            [
                'id' => 'footer_hitokoto_api',
                'type' => 'textarea',
                'title' => __('一言API地址', 'sakurairo'),
                'dependency' => ['footer_hitokoto_select', '!=', 'off', '', 'true'],
                'desc' => __('填写地址，格式为 JavaScript 数组', 'sakurairo'),
                'default' => '["https://v1.hitokoto.cn/","https://v1.hitokoto.cn/"]'
            ],

            [
                'id' => 'footer_hitokoto_custom',
                'type' => 'textarea',
                'title' => __('一言自定义内容', 'sakurairo'),
                'dependency' => ['footer_hitokoto_select', '!=', 'off', '', 'true'],
                'desc' => __('一行一句，尽量不要出现特殊字符。', 'sakurairo'),
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title' => __('搜索设置', 'sakurairo'),
        'icon' => 'fa fa-search',
        'fields' => [

            [
                'id' => 'nav_menu_search_switch',
                'type' => 'switcher',
                'title' => __('导航栏搜索按钮', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'search_filter',
                'type' => 'switcher',
                'title' => __('搜索页过滤栏', 'sakurairo'),
                'default' => false
            ],

            [
                'id' => 'search_for_shuoshuo',
                'type' => 'switcher',
                'title' => __('在搜索结果中显示说说', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'search_for_pages',
                'type' => 'switcher',
                'title' => __('在搜索结果中显示页面', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'search_pages_can_only_admins',
                'type' => 'switcher',
                'title' => __('只有管理员可以搜索页面', 'sakurairo'),
                'dependency' => [
                    ['search_for_pages', '==', 'true', '', 'true'],
                ],
                'default' => true
            ],

            [
                'id' => 'search_for_pinned_posts',
                'type' => 'switcher',
                'title' => __('在搜索结果中置顶置顶文章', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'search_results_custom_exclude',
                'type' => 'text',
                'title' => __('搜索结果排除', 'sakurairo'),
                'desc' => __('从搜索结果中排除自定义ID内容，在使用自定义登录页面后推荐使用，你可以从编辑页的链接中获取，填写数字ID，例如“12,34”', 'sakurairo'),
            ],

            [
                'id' => 'search_live',
                'type' => 'switcher',
                'title' => __('实时搜索', 'sakurairo'),
                'label' => __('开启后前台客户端会在搜索前加载一份索引，并实时显示键入后的相关搜索结果', 'sakurairo'),
                'default' => false
            ],
        ]
    ]);


    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'global',
        'title' => __('其他设置', 'sakurairo'),
        'icon' => 'fa fa-gift',
        'fields' => [

            [
                'type' => 'subheading',
                'content' => __('效果和动画', 'sakurairo'),
            ],

            [
                'id' => 'pjax',
                'type' => 'switcher',
                'title' => __('PJAX', 'sakurairo'),
                'label' => __('启用后前台站内跳转将不会刷新页面，体验更好，但与第三方内容可能存在兼容性问题，请按需使用', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'pjax_keep_loading',
                'type' => 'textarea',
                'title' => __('Resources that still need refreshing in the footer after enabling PJAX', 'sakurairo'),
                'dependency' => ['pjax', '==', 'true', '', 'true'],
                'desc' => __('After enabling PJAX, custom content in the footer will not be refreshed on page navigation. You can specify paths for JavaScript and stylesheet resources that need to be reloaded on each page in the footer here, one per line. These resources will be reloaded once PJAX completes content loading.', 'sakurairo'),
            ],

            [
                'id' => 'top_scroll_progress',
                'type' => 'switcher',
                'title' => __('顶部阅读进度条', 'sakurairo'),
                'label' => __('开启后会在页面顶部会显示进度条，进度取决于当前页面的滚动进度', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'top_loading_progress',
                'type' => 'switcher',
                'title' => __('顶部加载进度条', 'sakurairo'),
                'label' => __('开启后会在页面顶部会显示进度条，进度取决于下一页的加载进度', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'pagination_mode',
                'type' => 'radio',
                'title' => __('文章列表分页导航方式', 'sakurairo'),
                'options' => [
                    'ajax' => __('滚动加载', 'sakurairo'),
                    'pagination' => __('传统分页', 'sakurairo'),
                ],
                'default' => 'pagination'
            ],

            [
                'id' => 'pagination_ajax_wait',
                'type' => 'slider',
                'title' => __('ajax自动加载等待时间', 'sakurairo'),
                'dependency' => ['pagination_mode', '==', 'ajax', '', 'true'],
                'step' => '1',
                'unit' => 's',
                'max' => '10',
                'default' => '3',
            ],

            [
                'id' => 'missing_avatars_placeholder',
                'type' => 'upload',
                'title' => __('站内头像占位', 'sakurairo'),
                'library' => 'image',
            ],

            [
                'id' => 'missing_images_placeholder',
                'type' => 'upload',
                'title' => __('站内图片占位', 'sakurairo'),
                'library' => 'image',
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id' => 'homepage',
        'title' => __('首页设置', 'sakurairo'),
        'icon' => 'fa fa-home',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'homepage',
        'title' => __('封面设置', 'sakurairo'),
        'icon' => 'fa fa-laptop',
        'fields' => [

            [
                'id' => 'cover_switch',
                'type' => 'switcher',
                'title' => __('封面开关', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'cover_height',
                'type' => 'slider',
                'title' => __('封面高度', 'sakurairo'),
                'desc'   => __('封面占可视窗口的百分比', 'sakurairo'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                ],
                'step' => '1',
                'unit' => 'dvh',
                'max' => '100',
                'default' => '100'
            ],

            [
                'type' => 'subheading',
                'content' => __('封面信息栏', 'sakurairo'),
            ],

            [
                'id' => 'cover_focus_style',
                'type' => 'select',
                'title' => __('首页聚焦显示内容', 'sakurairo'),
                'options' => [
                    'off' => __('无', 'sakurairo'),
                    'avatar' => __('头像', 'sakurairo'),
                    'text' => __('文字', 'sakurairo'),
                    'mashiro_text' => __('Mashiro特效文字', 'sakurairo'),
                ],
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                ],
                'default' => 'text'
            ],

            [
                'id'    => 'cover_avatar',
                'type'  => 'upload',
                'title' => __('个人头像', 'sakurairo'),
                'desc'   => __('最佳宽高比为1:1', 'sakurairo'),
                'library'      => 'image',
            ],

            [
                'id'        => 'cover_title',
                'type'      => 'fieldset',
                'title'     => __('封面文字配置', 'sakurairo'),
                'dependency' => ['cover_focus_style', 'any', 'text,mashiro_text', '', 'true'],
                'fields'    => [
                    [
                        'id'     => 'text',
                        'type'   => 'text',
                        'title'  => __('内容', 'sakurairo'),
                    ],
                    [
                        'id'     => 'font',
                        'type'   => 'text',
                        'title'  => __('字体', 'sakurairo'),
                    ],
                    [
                        'id'     => 'size',
                        'type'   => 'slider',
                        'title'  => __('大小', 'sakurairo'),
                        'step'   => '0.01',
                        'unit'    => 'rem',
                        'min'   => '1',
                        'max'   => '9',
                    ],
                    [
                        'id'      => 'color',
                        'type'    => 'color',
                        'title'   => __('颜色', 'sakurairo'),
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
                'title' => __('封面信息栏开关', 'sakurairo'),
                'dependency' => ['cover_switch', '==', 'true', '', 'true'],
                'default' => true
            ],

            [
                'id' => 'cover_infor_bar_radius',
                'type' => 'slider',
                'title' => __('封面信息栏圆角', 'sakurairo'),
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
                'title'     => __('封面签名', 'sakurairo'),
                'fields'    => [
                    [
                        'id'     => 'text',
                        'type'   => 'text',
                        'title'  => __('内容', 'sakurairo'),
                    ],
                    [
                        'id'     => 'font',
                        'type'   => 'text',
                        'title'  => __('字体', 'sakurairo'),
                    ],
                    [
                        'id'     => 'size',
                        'type'   => 'slider',
                        'title'  => __('大小', 'sakurairo'),
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
                'title' => __('封面打字机效果', 'sakurairo'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                    ['cover_infor_bar_switch', '==', 'true'],
                ],
                'default' => true
            ],

            [
                'id' => 'cover_typedjs_mark',
                'type' => 'switcher',
                'title' => __('封面打字机引号', 'sakurairo'),
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
                'title' => __('封面打字机占位符', 'sakurairo'),
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
                'title' => __('封面打字机配置', 'sakurairo'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                    ['cover_infor_bar_switch', '==', 'true'],
                    ['cover_typedjs', '==', 'true'],
                ],
                'default' => '{"strings":["愿你保持不变 保持己见 充满热血"],"typeSpeed":140,"backSpeed":50,"loop":false,"showCursor":true}'
            ],

            [
                'type' => 'subheading',
                'content' => __('封面图片', 'sakurairo'),
            ],

            [
                'id' => 'cover_random_pic_url_pc',
                'type' => 'text',
                'title' => __('PC封面图片地址', 'sakurairo'),
                'desc' => __('填写图片地址或者随机图API', 'sakurairo'),
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
                'title' => __('移动端封面图片地址', 'sakurairo'),
                'dependency' => [
                    ['cover_switch', '==', 'true', '', 'true'],
                ],
                'desc' => __('填写图片地址或者随机图API，未填写则使用与PC图片相同的配置', 'sakurairo'),
                'default' => 'https://api.fuukei.org/random-img/default/mobile.php',
                'sanitize' => false,
                'validate' => 'csf_validate_url',
            ],

            [
                'id' => 'cover_as_background',
                'type' => 'switcher',
                'title' => __('前台背景一体化', 'sakurairo'),
                'label' => __('开启后封面背景将会变透明，以实现前台背景与封面的一体化效果', 'sakurairo'),
                'dependency' => ['cover_switch', '==', 'true', '', 'true'],
                'default' => false
            ],

            [
                'id' => 'post_cover_as_background',
                'type' => 'switcher',
                'title' => __('使用特色图片作为背景', 'sakurairo'),
                'label' => __('在文章页将会使用特色图片作为背景', 'sakurairo'),
                'default' => false
            ],

            [
                'id' => 'cover_pic_filter',
                'type' => 'select',
                'title' => __('封面图片滤镜', 'sakurairo'),
                'options' => [
                    'filter-nothing' => __('无', 'sakurairo'),
                    'filter-undertint' => __('浅色滤镜', 'sakurairo'),
                    'filter-dim' => __('深色滤镜', 'sakurairo'),
                    'filter-grid' => __('网格滤镜', 'sakurairo'),
                    'filter-dot' => __('点状滤镜', 'sakurairo'),
                ],
                'dependency' => ['cover_switch', '==', 'true', '', 'true'],
                'default' => 'filter-nothing'
            ],

            array(
                'type' => 'subheading',
                'content' => __('封面视频', 'sakurairo'),
            ),

            array(
                'id' => 'cover_video',
                'type' => 'switcher',
                'title' => __('封面视频', 'sakurairo'),
                'label' => __('用视频代替封面图片', 'sakurairo'),
                'dependency' => array('cover_switch', '==', 'true', '', 'true'),
                'default' => false
            ),

            array(
                'id' => 'cover_video_loop',
                'type' => 'switcher',
                'title' => __('封面视频循环', 'sakurairo'),
                'dependency' => array(
                    array('cover_video', '==', 'true'),
                    array('cover_switch', '==', 'true', '', 'true'),
                ),
                'label' => __('开启后视频将会循环播放', 'sakurairo'),
                'default' => false
            ),

            array(
                'id' => 'cover_video_live',
                'type' => 'switcher',
                'title' => __('视频自动恢复', 'sakurairo'),
                'dependency' => array(
                    array('cover_video', '==', 'true'),
                    array('cover_switch', '==', 'true', '', 'true'),
                ),
                'label' => __('开启后，将在用户回到首页后自动恢复播放进度，需要开启PJAX', 'sakurairo'),
                'default' => false
            ),

            array(
                'id' => 'cover_video_source',
                'type' => 'upload',
                'title' => __('视频URL地址', 'sakurairo'),
                'library' => 'video',
                'dependency' => array(
                    array('cover_video', '==', 'true'),
                    array('cover_switch', '==', 'true', '', 'true'),
                ),
                'validate' => 'iro_validate_optional_url',
                'desc' => __("视频的文件地址", 'sakurairo'),
            ),
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'homepage',
        'title' => __('社交区域', 'sakurairo'),
        'icon' => 'fa fa-share-square-o',
        'fields' => [

            [
                'id' => 'cover_social_switch',
                'type' => 'switcher',
                'title' => __('封面社交栏开关', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'cover_social_icon',
                'type' => 'image_select',
                'title' => __('社交栏图标包', 'sakurairo'),
                'desc' => __('选择你喜欢的图标包。图标包引用信息详见关于主题', 'sakurairo'),
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
                'title'     => __('社交区域展示内容', 'sakurairo'),
                'dependency' => ['cover_social_switch', '==', 'true', '', 'true'],
                'fields'    => [
                    [
                        'id' => 'select',
                        'type' => 'select',
                        'title' => __('显示的图标', 'sakurairo'),
                        'options' => [
                            'qq' => __('QQ', 'sakurairo'),
                            'wechat' => __('微信', 'sakurairo'),
                            'bilibili' => __('bilibili', 'sakurairo'),
                            'netease_music' => __('网易云音乐', 'sakurairo'),
                            'sina' => __('新浪', 'sakurairo'),
                            'github' => __('Github', 'sakurairo'),
                            'telegram' => __('Telegram', 'sakurairo'),
                            'steam' => __('Steam', 'sakurairo'),
                            'youtube' => __('Youtube', 'sakurairo'),
                            'instgram' => __('instgram', 'sakurairo'),
                            'tiktok' => __('抖音', 'sakurairo'),
                            'xiaohongshu' => __('小红书', 'sakurairo'),
                            'discord' => __('Discord', 'sakurairo'),
                            'zhihu' => __('知乎', 'sakurairo'),
                            'linkedin' => __('领英', 'sakurairo'),
                            'twitter' => __('推特/X', 'sakurairo'),
                            'facebook' => __('facebook', 'sakurairo'),
                            'email' => __('邮箱', 'sakurairo'),
                            'custom' => __('自定义', 'sakurairo'),
                        ],
                    ],
                    [
                        'id'   => 'icon',
                        'type' => 'upload',
                        'title' => __('自定义图标', 'sakurairo'),
                    ],
                    [
                        'id'   => 'qrcode',
                        'type' => 'upload',
                        'title' => __('二维码', 'sakurairo'),
                    ],
                    [
                        'id'    => 'title',
                        'type'  => 'text',
                        'title' => __('标题', 'sakurairo'),
                    ],
                    [
                        'id'    => 'link',
                        'type'  => 'text',
                        'title' => __('链接', 'sakurairo'),
                    ],
                ],
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'homepage',
        'title' => __('首页布局', 'sakurairo'),
        'icon' => 'fa fa-bars',
        'fields' => [

            array(
                'id' => 'homepage_components',
                "type" => "select",
                "title" => __("首页布局", "sakurairo_csf"),
                'desc' => __('Select the homepage components you want to display. They will appear in the order above.', 'sakurairo'),
                "chosen" => true,
                "multiple" => true,
                "sortable" => true,
                "options" => array(
                    'show'  => __('展示区域', 'sakurairo'),
                    'post_list'     => __('最新文章', 'sakurairo'),
                    'static_page' => __('自定义页面', 'sakurairo'),
                ),
                "default" => array('show', 'post_list'),
            ),

            [
                'id'          => 'homepage_static_page_id',
                'type'        => 'select',
                'title'       => __('Static Page', 'sakurairo'),
                'placeholder' => __('Select a page', 'sakurairo'),
                'chosen'      => true,
                'options'     => 'pages',
                'dependency'  => ['homepage_components', 'any', 'static_page', '', 'true'],
            ],

            [
                'type' => 'subheading',
                'content' => __('区域标题', 'sakurairo'),
            ],

            [
                'id'        => 'homepage_show_title',
                'type'      => 'fieldset',
                'title'     => __('展示区域标题配置', 'sakurairo'),
                'fields'    => [
                    [
                        'id'     => 'icon',
                        'type'   => 'text',
                        'title'  => __('图标', 'sakurairo'),
                        'desc' => __('图标元素的类名，自定义前需在页面头部嵌入自定义的图标集样式，例如fontawesome,否则会不显示', 'sakurairo'),
                    ],
                    [
                        'id'     => 'text',
                        'type'   => 'text',
                        'title'  => __('内容', 'sakurairo'),
                    ],
                ],
                'default'        => [
                    'icon'    => 'fa-icon-solid fa-laptop',
                    'text'    => 'Display',
                ],
            ],

            [
                'id'        => 'homepage_post_list_title',
                'type'      => 'fieldset',
                'title'     => __('文章区域标题配置', 'sakurairo'),
                'fields'    => [
                    [
                        'id'     => 'icon',
                        'type'   => 'text',
                        'title'  => __('图标', 'sakurairo'),
                    ],
                    [
                        'id'     => 'text',
                        'type'   => 'text',
                        'title'  => __('内容', 'sakurairo'),
                    ],
                ],
                'default'        => [
                    'icon'    => 'fa-icon-regular fa-bookmark',
                    'text'    => 'Article',
                ],
            ],

            [
                'id' => 'homepage_component_title_font',
                'type' => 'text',
                'title' => __('区域标题字体', 'sakurairo'),
                'default' => 'Noto Serif SC'
            ],

            [
                'id' => 'homepage_component_title_align',
                'type' => 'image_select',
                'title' => __('首页区域标题位置', 'sakurairo'),
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
        'title' => __('展示区域', 'sakurairo'),
        'icon' => 'fa fa-bookmark',
        'fields' => [

            [
                'id'        => 'show_area_content',
                'type'      => 'repeater',
                'title'     => __('展示区域内容', 'sakurairo'),
                'fields'    => [
                    [
                        'id'   => 'img',
                        'type' => 'upload',
                        'title' => __('图片链接', 'sakurairo'),
                    ],
                    [
                        'id'    => 'title',
                        'type'  => 'text',
                        'title' => __('标题', 'sakurairo'),
                    ],
                    [
                        'id'    => 'description',
                        'type'  => 'text',
                        'title' => __('描述', 'sakurairo'),
                    ],
                    [
                        'id'    => 'link',
                        'type'  => 'text',
                        'title' => __('跳转链接', 'sakurairo'),
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
        'title' => __('文章区域', 'sakurairo'),
        'icon'      => 'fa fa-book',
        'fields' => [

            [
                'id'         => 'post_card_with_image_design',
                'type'       => 'image_select',
                'title' => __('文章区域卡片设计', 'sakurairo'),
                'desc' => __('你可以选择信件设计或者票券设计', 'sakurairo'),
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
                    'author'  => __('作者', 'sakurairo'),
                    'category'     => __('分类', 'sakurairo'),
                    'comment_count' => __('评论数量', 'sakurairo'),
                    'views' => __('浏览量', 'sakurairo'),
                ],
                "default" => ['category', 'comment_count', 'views'],
            ],

            [
                'id' => 'post_card_image',
                'type' => 'radio',
                'title' => __('文章区域装饰特色图片选项', 'sakurairo'),
                'options' => [
                    'always_with_cover' => __('始终且使用封面API', 'sakurairo'),
                    'always_alone' => __('始终且使用独立API', 'sakurairo'),
                    'only_feather_image' => __('仅特色图片', 'sakurairo'),
                ],
                'default' => 'only_feather_image'
            ],

            [
                'id' => 'post_card_image_url',
                'type' => 'text',
                'title' => __('文章封面随机图API', 'sakurairo'),
                'sanitize' => false,
                'validate' => 'iro_validate_optional_url',
            ],

            [
                'id'        => 'post_card_design',
                'type'      => 'fieldset',
                'title'     => __('文章卡片设计', 'sakurairo'),
                'fields'    => [
                    [
                        'id' => 'card_radius',
                        'type' => 'slider',
                        'title' => __('文章卡片圆角', 'sakurairo'),
                        'step' => '0.01',
                        'unit' => 'rem',
                        'max' => '2',
                    ],

                    [
                        'id' => 'meta_radius',
                        'type' => 'slider',
                        'title' => __('文章卡片元信息圆角', 'sakurairo'),
                        'step' => '0.01',
                        'unit' => 'rem',
                        'max' => '2',
                    ],

                    [
                        'id' => 'title_radius',
                        'type' => 'slider',
                        'title' => __('文章卡片标题圆角', 'sakurairo'),
                        'step' => '0.01',
                        'unit' => 'rem',
                        'max' => '2',
                    ],

                    [
                        'id' => 'title_font_size',
                        'type' => 'slider',
                        'title' => __('文章卡片标题大小', 'sakurairo'),
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
                'title' => __('在首页显示说说', 'sakurairo'),
                'default' => true
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id' => 'page',
        'title' => __('文章与页面设置', 'sakurairo'),
        'icon' => 'fa fa-file-text',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'page',
        'title' => __('通用', 'sakurairo'),
        'icon' => 'fa fa-compass',
        'fields' => [

            [
                'id' => 'page_patternimg',
                'type' => 'switcher',
                'title' => __('使用特色图片作为头图', 'sakurairo'),
                'label' => __('启用后当文章或页面有特色图片时，将会用作头图装饰', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'page_title_font_size',
                'type' => 'slider',
                'title' => __('页面标题字体大小（无头图）', 'sakurairo'),
                'step' => '0.01',
                'unit' => 'rem',
                'min' => '1',
                'max' => '4',
                'default' => '2.5'
            ],

            [
                'id' => 'page_title_font_size_with_image',
                'type' => 'slider',
                'title' => __('页面标题字体大小（有头图）', 'sakurairo'),
                'step' => '0.01',
                'unit' => 'rem',
                'min' => '1',
                'max' => '4',
                'default' => '2.5'
            ],

            [
                'id' => 'page_post_toc',
                'type' => 'switcher',
                'title' => __('文章目录', 'sakurairo'),
                'label' => __('在文章页显示目录(检测到内容有标题会自动生成大纲并显示)', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'page_page_toc',
                'type' => 'switcher',
                'title' => __('页面目录', 'sakurairo'),
                'label' => __('在页面显示目录', 'sakurairo'),
                'default' => false
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, array(
        'parent' => 'page',
        'title' => __('文章页面', 'sakurairo'),
        'icon' => 'fa fa-archive',
        'fields' => array(

            array(
                'type' => 'subheading',
                'content' => __('文章拓展区域', 'sakurairo'),
            ),

            array(
                'id' => 'article_function',
                'type' => 'switcher',
                'title' => __('文章功能栏', 'sakurairo'),
                'label' => __('默认开启，将在文章页面显示下方启用的功能', 'sakurairo'),
                'default' => true
            ),

            array(
                'id' => 'article_licenses',
                'type' => 'select',
                'title' => __('文章版权协议', 'sakurairo'),
                'dependency' => array('article_function', '==', 'true', '', 'true'),
                'label' => __('默认开启，版权协议将显示在功能栏中。也可通过文章自定义字段「license」单独指定。', 'sakurairo'),
                'options' => array(
                    false => __("不显示", "sakurairo_csf"),
                    "cc0" => "CC0 1.0",
                    "cc-by" => "CC BY 4.0",
                    "cc-by-nc" => "CC BY-NC 4.0",
                    "cc-by-nc-nd" => "CC BY-NC-ND 4.0",
                    "cc-by-nc-sa" => "CC BY-NC-SA 4.0",
                    "cc-by-nd" => "CC BY-ND 4.0",
                    "cc-by-sa" => "CC BY-SA 4.0",
                ),
                'default' => "cc-by-nc-sa"
            ),

            array(
                'type'    => 'content',
                'content' => __(
                    '<p><strong>"BY"</strong> 表示转载时须署名原作者</p>'
                        . '<p><strong>"NC"</strong> 表示不得用于商业用途</p>'
                        . '<p><strong>"ND"</strong> 表示不得演绎（不可修改后再发布）</p>'
                        . '<p><strong>"SA"</strong> 表示演绎作品须以相同协议共享</p>'
                        . '<p><strong>"CC0"</strong> 是公共领域贡献工具，允许创作者放弃版权，将作品投入全球公共领域。</p>'
                        . '<p>详细说明与法律建议请访问<a href="https://creativecommons.org/">官方网站</a></p>'
                        . '<p>若想<strong>按文章</strong>单独指定协议，请把文章自定义字段 "license" 修改（或添加）为对应格式的协议名。</p>'
                        . '<p>例如：</p>'
                        . '<ul><li><code>cc0</code> 对应 CC0 1.0</li><li><code>cc-by-nc-sa</code> 对应 CC BY-NC-SA 4.0</li></ul>',
                    'sakurairo'
                ),
                'dependency' => array('article_lincenses', '!=', 'false', '', 'true'),
            ),

            array(
                'id' => 'article_author_reward',
                'type' => 'fieldset',
                'title' => __('打赏', 'sakurairo'),
                'dependency' => array('article_function', '==', 'true', '', 'true'),
                'fields' => array(
                    array(
                        'id' => 'link',
                        'type' => 'text',
                        'title' => __('按钮链接', 'sakurairo'),
                        'desc' => __('点击打赏按钮后跳转的链接', 'sakurairo'),
                    ),
                    array(
                        'id' => 'image1',
                        'type' => 'upload',
                        'title' => __('图片', 'sakurairo'),
                        'library' => 'image',
                    ),
                    array(
                        'id' => 'link1',
                        'type' => 'text',
                        'title' => __('链接', 'sakurairo'),
                        'desc' => __('点击图片后跳转的链接', 'sakurairo'),
                    ),
                    array(
                        'id' => 'image2',
                        'type' => 'upload',
                        'title' => __('图片', 'sakurairo'),
                        'library' => 'image',
                    ),
                    array(
                        'id' => 'link2',
                        'type' => 'text',
                        'title' => __('链接', 'sakurairo'),
                        'desc' => __('点击图片后跳转的链接', 'sakurairo'),
                    ),
                ),
            ),

            array(
                'id' => 'article_author_avatar',
                'type' => 'switcher',
                'title' => __('文章作者头像', 'sakurairo'),
                'dependency' => array('article_function', '==', 'true', '', 'true'),
                'label' => __('默认开启，作者头像将显示在功能栏中', 'sakurairo'),
                'default' => true
            ),

            array(
                'id' => 'article_author_name',
                'type' => 'switcher',
                'title' => __('文章作者名称', 'sakurairo'),
                'dependency' => array('article_function', '==', 'true', '', 'true'),
                'label' => __('开启后作者名称将显示在功能栏中', 'sakurairo'),
                'default' => false
            ),

            array(
                'id' => 'article_author_quote',
                'type' => 'switcher',
                'title' => __('文章作者签名', 'sakurairo'),
                'dependency' => array('article_function', '==', 'true', '', 'true'),
                'label' => __('默认开启，作者签名将显示在功能栏中', 'sakurairo'),
                'default' => true
            ),

            array(
                'id' => 'article_modified_time',
                'type' => 'switcher',
                'title' => __('文章最后更新时间', 'sakurairo'),
                'dependency' => array('article_function', '==', 'true', '', 'true'),
                'label' => __('开启后最后更新时间将显示在功能栏中', 'sakurairo'),
                'default' => false
            ),

            array(
                'id' => 'article_tag',
                'type' => 'switcher',
                'title' => __('文章标签', 'sakurairo'),
                'dependency' => array('article_function', '==', 'true', '', 'true'),
                'label' => __('默认开启，文章标签将显示在功能栏中', 'sakurairo'),
                'default' => true
            ),

            array(
                'id' => 'article_nextpre',
                'type' => 'switcher',
                'title' => __('文章上一篇/下一篇导航', 'sakurairo'),
                'label' => __('默认开启，文章页面将显示上一篇/下一篇切换', 'sakurairo'),
                'default' => true
            ),

        )
    ));

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'page',
        'title' => __('评论区设置', 'sakurairo'),
        'icon' => 'fa fa-comments-o',
        'fields' => [

            [
                'type' => 'subheading',
                'content' => __('评论区外观', 'sakurairo'),
            ],

            [
                'id' => 'comment_input_place_holder',
                'type' => 'text',
                'title' => __('评论区输入框占位符', 'sakurairo'),
                'default' => __('要来喵一句吗？', 'sakurairo')
            ],

            [
                'id' => 'comment_submit_button_text',
                'type' => 'text',
                'title' => __('评论区提交按钮文本', 'sakurairo'),
                'default' => __('Submit✈️', 'sakurairo')
            ],

            [
                'type' => 'subheading',
                'content' => __('评论区功能', 'sakurairo'),
            ],

            [
                'id'       => 'comment_smilies_list',
                'type'     => 'button_set',
                'title' => __('评论区表情', 'sakurairo'),
                'desc' => __('选择要在评论区域输入框中显示的表情。全部取消选中可关闭评论区域输入框表情功能。', 'sakurairo'),
                'multiple' => true,
                'options'  => [
                    'bilibili'   => __('bilibili', 'sakurairo'),
                    'tieba'   => __('贴吧', 'sakurairo'),
                    'yanwenzi' => __('颜文字', 'sakurairo'),
                    'custom' => __('自定义', 'sakurairo'),
                ],
                'default'  => ['bilibili', 'tieba', 'yanwenzi']
            ],

            [
                'id'         => 'comment_smilies_list_custom_name',
                'type'       => 'text',
                'title' => __('自定义表情包名称', 'sakurairo'),
                'desc' => __('建议输入少于4个汉字的内容，以免造成移动端的兼容性问题。', 'sakurairo'),
                'dependency' => ['comment_smilies_list', 'any', 'custom', '', 'true'],
                'default' => 'custom'
            ],

            [
                'id' => 'comment_useragent',
                'type' => 'switcher',
                'title' => __('评论区评论UA', 'sakurairo'),
                'label' => __('开启之后页面评论区域将显示用户的浏览器，操作系统信息', 'sakurairo'),
                'default' => false
            ],

            [
                'id' => 'comment_captcha',
                'type' => 'select',
                'title' => __('评论区验证码', 'sakurairo'),
                'label' => __('开启后游客评论需要通过验证码验证', 'sakurairo'),
                'options' => [
                    'off' => __('Off', 'sakurairo'),
                    'builtin' => __('主题内建验证码', 'sakurairo'),
                    'turnstile' => __('Cloudflare Turnstile', "sakurairo_csf")
                ],
                'default' => 'builtin',
            ],

            [
                'type'    => 'subheading',
                'content' => __('自定义表情包', 'sakurairo'),
            ],

            [
                'id'         => 'comment_smilies_custom',
                'type'       => 'repeater',
                'title'      => __('自定义表情列表', 'sakurairo'),
                'desc'       => __('表情包名称见上方「自定义表情包名称」。每行的「表情名」就是写进评论的标记，形如 {{doge}}', 'sakurairo'),
                'dependency' => ['comment_smilies_list', 'any', 'custom', '', 'true'],
                'fields'     => [
                    [
                        'id'    => 'img',
                        'type'  => 'upload',
                        'title' => __('图片', 'sakurairo'),
                        'desc'  => __('建议用正方形图片，尺寸接近下面的显示高度', 'sakurairo'),
                    ],
                    [
                        'id'         => 'name',
                        'type'       => 'text',
                        'title'      => __('表情名', 'sakurairo'),
                        'desc'       => __('写进评论的标记名，同一表情包内不能重复；不要带空格、花括号等符号', 'sakurairo'),
                        'attributes' => ['placeholder' => 'doge'],
                    ],
                    [
                        'id'    => 'title',
                        'type'  => 'text',
                        'title' => __('提示文案', 'sakurairo'),
                        'desc'  => __('鼠标悬停提示与图片 alt，留空则用表情名', 'sakurairo'),
                    ],
                    [
                        'id'      => 'size',
                        'type'    => 'spinner',
                        'title'   => __('显示高度', 'sakurairo'),
                        'desc'    => __('评论里渲染出来的高度', 'sakurairo'),
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
        'title' => __('模板页面设置', 'sakurairo'),
        'icon' => 'fa fa-window-maximize',
        'fields' => [

            [
                'id' => 'page_template_data_cache',
                'type' => 'switcher',
                'title' => __('模板页数据缓存', 'sakurairo'),
                'label' => __('非测试环境建议开启，关闭后渲染页面模板时将实时从目标获取数据，将极大增加源站压力', 'sakurairo'),
                'default' => true
            ],

            [
                'type' => 'subheading',
                'content' => __('追番模板设置', 'sakurairo'),
            ],

            [
                'id' => 'bangumi_source',
                'type' => 'image_select',
                'title' => __('追番数据来源', 'sakurairo'),
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
                'title' => __('My Anime List 用户名', 'sakurairo'),
                'dependency' => ['bangumi_source', '==', 'myanimelist', '', 'true'],
                'desc' => __('https://myanimelist.net/ 上的用户名', 'sakurairo'),
                'default' => ''
            ],

            [
                'id' => 'my_anime_list_sort',
                'type' => 'radio',
                'title' => __('My Anime List 顺序', 'sakurairo'),
                'dependency' => ['bangumi_source', '==', 'myanimelist', '', 'true'],
                'options' => [
                    '1' => __('状态和上次更新', 'sakurairo'),
                    '2' => __('上次更新', 'sakurairo'),
                    '3' => __('状态', 'sakurairo'),
                ],
                'default' => '1'
            ],

            [
                'id' => 'bilibili_id',
                'type' => 'text',
                'title' => __('Bilibili用户ID', 'sakurairo'),
                'desc' => __('填写你的账户ID，例如 https://space.bilibili.com/13972644/，填写数字部分“13972644”', 'sakurairo'),
                'default' => '13972644'
            ],

            [
                'id' => 'bilibili_cookie',
                'type' => 'text',
                'title' => __('Bilibili账户cookie', 'sakurairo'),
                'desc' => __('填写你的Bilibili账户cookie，如果未设置，则无法显示未公开的状态以及观看进度', 'sakurairo'),
                'default' => ''
            ],

            [
                'id' => 'bilibili_show_private_favlist',
                'type' => 'switcher',
                'title' => __('bilibili收藏显示私人收藏夹', 'sakurairo'),
                'label' => __('开启后将在bilibili收藏夹模板显示私人收藏夹', 'sakurairo'),
                'default' => false
            ],

            [
                'id' => 'bangumi_id',
                'type' => 'text',
                'title' => __('Bangumi 账户ID', 'sakurairo'),
                'desc' => __('填写你的Bangumi账户ID，例如 https://bangumi.tv/user/944883，填写数字部分“944883”', 'sakurairo'),
                'dependency' => ['bangumi_source', '==', 'bangumi', '', 'true'],
                'default' => '944883'
            ],

            [
                'type' => 'subheading',
                'content' => __('友情链接模板设置', 'sakurairo'),
            ],

            [
                'id' => 'friend_link_sorting_mode',
                'type' => 'select',
                'title' => __('友情链接列表排序模式', 'sakurairo'),
                'desc' => __('选择友情链接列表排序模式，默认使用“名称”排序。', 'sakurairo'),
                'options' => [
                    'name' => __('名称', 'sakurairo'),
                    'rating'  => __('评级', 'sakurairo'),
                    'updated'  => __('更新时间', 'sakurairo'),
                    'rand'  => __('随机', 'sakurairo'),
                ],
                'default'     => 'name'
            ],

            [
                'id' => 'friend_link_order',
                'type' => 'select',
                'title' => __('升序或降序', 'sakurairo'),
                'desc' => __('按升序或降序排序友情链接列表', 'sakurairo'),
                'dependency' => ['friend_link_sorting_mode', '!=', 'rand', '', 'true'],
                'options' => [
                    'ASC' => __('升序', 'sakurairo'),
                    'DESC'  => __('降序', 'sakurairo'),
                ],
                'default'     => 'ASC'
            ],

            [
                'type' => 'subheading',
                'content' => __('Steam 库模板设置', 'sakurairo'),
            ],

            [
                'id' => 'steam_id',
                'type' => 'text',
                'title' => __('Steam 账号 64ID', 'sakurairo'),
                'desc' => __('填写你的帐号ID，例如：https://steamcommunity.com/profiles/76561199029689067/, 填写数字部分“76561199029689067”', 'sakurairo'),
            ],

            [
                'id' => 'steam_key',
                'type' => 'text',
                'title' => __('Steam API 密钥', 'sakurairo'),
                'desc' => __('Apply at https://steamcommunity.com/dev/apikey', 'sakurairo'),
            ],

            [
                'id' => 'steam_covercdn',
                'type' => 'select',
                'title' => __('游戏封面 CDN', 'sakurairo'),
                'desc' => __('根据你的目标用户选择加载封面的CDN', 'sakurairo'),
                'options' => [
                    'steamchina' => __('Steam China', 'sakurairo'),
                    'steamakamai'  => __('Steam akamai', 'sakurairo'),
                    'steamfastly'  => __('Steam fastly', 'sakurairo'),
                    'steamcloudflare'  => __('Steam cloudflare', 'sakurairo'),
                ],
                'default'     => 'steamakamai'
            ],

            [
                'id' => 'steam_store',
                'type' => 'select',
                'title' => __('游戏商店链接', 'sakurairo'),
                'desc' => __('选择要跳转到的游戏商店链接', 'sakurairo'),
                'options' => [
                    'steam' => __('Steam', 'sakurairo'),
                    'xiaoheihe'  => __('XiaoHeiHe', 'sakurairo'),
                    'steamdb'  => __('SteamDB', 'sakurairo'),
                ],
                'default'     => 'steam'
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'id' => 'others',
        'title' => __('其他设置', 'sakurairo'),
        'icon' => 'fa fa-coffee',
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'others',
        'title' => __('主题定制', 'sakurairo'),
        'icon' => 'fa fa-sign-in',
        'fields' => [

            [
                'type' => 'subheading',
                'content' => __('登录页', 'sakurairo'),
            ],

            [
                'id' => 'login_custom_switch',
                'type' => 'switcher',
                'title' => __('定制登录页', 'sakurairo'),
                'default' => true
            ],

            [
                'id' => 'login_logo_img',
                'type' => 'upload',
                'title' => __('登录页Logo', 'sakurairo'),
                'dependency' => ['login_custom_switch', '==', 'true', '', 'true'],
                'library' => 'image',
                'default' => $vision_resource_basepath . 'series/login_logo.webp'
            ],

            [
                'id' => 'login_captcha_select',
                'type' => 'select',
                'title' => __('登录页验证码', 'sakurairo'),
                'options' => [
                    'off' => __('Off', 'sakurairo'),
                    'builtin' => __('主题内建验证码', 'sakurairo'),
                    'turnstile' => __('Cloudflare Turnstile', "sakurairo_csf")
                ],
                'default' => 'off',
            ],

            [
                'id' => 'login_urlskip',
                'type' => 'switcher',
                'title' => __('登录后跳转', 'sakurairo'),
                'label' => __('开启之后管理员跳转至后台，用户跳转至主页。', 'sakurairo'),
                'default' => false
            ],

            [
                'id' => 'login_language_opt',
                'type' => 'switcher',
                'title' => __('登录界面语言选项', 'sakurairo'),
                'label' => __('开启之后登录界面将显示语言选项', 'sakurairo'),
                'default' => false
            ],

            [
                'type' => 'subheading',
                'content' => __('仪表盘', 'sakurairo'),
            ],

            [
                'id' => 'admin_background',
                'type' => 'upload',
                'title' => __('仪表盘背景图片', 'sakurairo'),
                'desc' => __('设置你的仪表盘背景图片，此选项留空则显示白色背景', 'sakurairo'),
                'library' => 'image',
                'default' => $vision_resource_basepath . 'series/admin_background.webp'
            ],

            [
                'id' => 'admin_left_style',
                'type' => 'image_select',
                'title' => __('仪表板设置菜单样式', 'sakurairo'),
                'options' => [
                    'v1' => $vision_resource_basepath . 'options/admin_left_style_v1.webp',
                    'v2' => $vision_resource_basepath . 'options/admin_left_style_v2.webp',
                ],
                'default' => 'v1'
            ],

            [
                'id' => 'admin_first_class_color',
                'type' => 'color',
                'title' => __('仪表盘一级菜单颜色', 'sakurairo'),
                'default' => '#081018'
            ],

            [
                'id' => 'admin_second_class_color',
                'type' => 'color',
                'title' => __('仪表盘二级菜单颜色', 'sakurairo'),
                'default' => '#111111'
            ],

            [
                'id' => 'admin_emphasize_color',
                'type' => 'color',
                'title' => __('仪表板强调颜色', 'sakurairo'),
                'default' => '#debd9c'
            ],

            [
                'id' => 'admin_text_color',
                'type' => 'color',
                'title' => __('仪表盘文本颜色', 'sakurairo'),
                'default' => '#FFFFFF'
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'others',
        'title' => __('安全设置', 'sakurairo'),
        'icon' => 'fa-solid fa-shield-halved',
        'fields' => [

            [
                'id' => 'builtin_captcha_level',
                'type' => 'slider',
                'title' => __('内建验证码强度', 'sakurairo'),
                'desc' => __('改变内建验证码的图片混乱度', 'sakurairo'),
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
        'title' => __('Low Use Options', 'sakurairo'),
        'icon' => 'fa fa-low-vision',
        'fields' => [

            [
                'id' => 'statistics_api',
                'type' => 'radio',
                'title' => __('Statistics API', 'sakurairo'),
                'desc' => __('You can choose WP-Statistics plugin statistics or theme built-in statistics to display', 'sakurairo'),
                'options' => [
                    'theme_build_in' => __('Theme Built in Statistics', 'sakurairo'),
                    'wp_statistics' => __('WP-Statistics Plugin Statistics', 'sakurairo'),
                ],
                'default' => 'theme_build_in'
            ],

            [
                'id' => 'statistics_format',
                'type' => 'select',
                'title' => __('Statistics display format', 'sakurairo'),
                'desc' => __('You can choose from four different data display formats', 'sakurairo'),
                'options' => [
                    'type_1' => __('23333 Visits', 'sakurairo'),
                    'type_2' => __('23,333 Visits', 'sakurairo'),
                    'type_3' => __('23 333 Visits', 'sakurairo'),
                    'type_4' => __('23K Visits', 'sakurairo'),
                ],
                'default' => 'type_1'
            ],

            [
                'id' => 'custom_site_header',
                'type'     => 'code_editor',
                'sanitize' => false,
                'title' => __('自定义插入 Header 代码', 'sakurairo'),
                'desc' => __('填入需要高优先级加载的站点内容，可能会阻塞加载，如果不需要高优先级可以在页脚设置中插入', 'sakurairo'),
            ],

            [
                'id' => 'gravatar_proxy',
                'type' => 'select',
                'title' => __('Gravatar服务代理', 'sakurairo'),
                'desc' => __('你可以选择多种代理作为 Gravatar 服务代理。默认使用 Weavatar 作为 Gravatar 服务代理。', 'sakurairo'),
                'options'     => [
                    'weavatar.com/avatar'  => __('Weavatar Service', 'sakurairo'),
                    'gravatar.loli.net/avatar'  => __('Loli Net', 'sakurairo'),
                    'gravatar.com/avatar'  => __('Gravatar官方', 'sakurairo'),
                    'custom_proxy_address_of_gravatar' => __('自定义代理地址', 'sakurairo'),
                ],
                'default'     => 'weavatar.com/avatar'
            ],

            [
                'id' => 'custom_proxy_address_of_gravatar',
                'type' => 'text',
                'title' => __('Custom Proxy Address', 'sakurairo'),
                'desc' => __('Enter your Gravatar proxy address without starting with "http(s)://" and ending with "/". Example: gravatar.com/avatar.', 'sakurairo'),
                'dependency' => ['gravatar_proxy', '==', 'custom_proxy_address_of_gravatar', '', 'true'],
                'default'     => 'gravatar.com/avatar'
            ],

            [
                'type' => 'subheading',
                'content' => __('Lightbox', 'sakurairo'),
            ],

            [
                'id' => 'lightbox',
                'type' => 'select',
                'title' => __('lightbox', 'sakurairo'),
                'desc' => __('请选择你需要使用的灯箱效果，wordpress在6.4后已正式支持灯箱效果，此处仅提供另一种可选的效果，其他的可以自行按需安装插件', 'sakurairo'),
                'options'     => [
                    'off'  => __('off', 'sakurairo'),
                    'medium_zoom'  => __('Medium Zoom', 'sakurairo'),
                ],
                'default'     => 'off'
            ],

            [
                'type' => 'subheading',
                'content' => __('代码高亮', 'sakurairo'),
            ],

            [
                'id' => 'code_highlight_method',
                'type' => 'select',
                'title' => __('Code Highlight Method', 'sakurairo'),
                'options' => [
                    'off' => __('关闭', 'sakurairo'),
                    'hljs' => 'highlight.js',
                ],
                "default" => "hljs"
            ],

            [
                'id' => 'code_katex',
                'type' => 'switcher',
                'title' => __('启用公式支持', 'sakurairo'),
                'label' => __('启用主题公式支持，使用Katex，需要写入markdown区块才能渲染', 'sakurairo'),
                'default' => true
            ],

            [
                'type' => 'submessage',
                'style' => 'danger',
                'content' => __('以下设置不推荐盲目进行修改，请确保你知道它是在做什么', 'sakurairo'),
            ],

            [
                'id'    => 'iro_image_optimize',
                'type'  => 'switcher',
                'title' => __('全站webp优化', 'sakurairo'),
                'label'   => __('将源站所有图片优化至webp', 'sakurairo'),
                'default' => false,
            ],

            [
                'id' => 'iro_image_cdn',
                'type' => 'text',
                'title' => __('图片cdn', 'sakurairo'),
                'desc' => __('将源站所有图片域名替换为该cdn域名', 'sakurairo'),
                'default' => ''
            ],

            [
                'id' => 'fontawesome_source',
                'type' => 'text',
                'title' => __('Fontawesome源', 'sakurairo'),
                'desc' => __('Fontawesome图标的加载地址，仅用于后台主题设置框架图标正常显示', 'sakurairo'),
                'default' => "https://s4.zstatic.net/ajax/libs/font-awesome/6.7.2/css/all.min.css",
            ],

            [
                'id'    => 'fontawesome_source_add_to_frontend',
                'type'  => 'switcher',
                'title' => __('将fontawesome源载入到前台', 'sakurairo'),
                'label'   => __('如果你需要使用自定义fontawesome图标，可以开启这个选项', 'sakurairo'),
                'default' => false,
            ],

            [
                'id'    => 'dev_mode',
                'type'  => 'switcher',
                'title' => __('开发者模式', 'sakurairo'),
                'label'   => __('启用并配置你的vite HMR客户端来正常使用', 'sakurairo'),
                'default' => false,
            ],

            [
                'id' => 'dev_mode_hmr_client',
                'type' => 'text',
                'title' => __('Vite HMR客户端地址', 'sakurairo'),
                'default' => 'https://wordpress:5173/@vite/client',
            ],

            [
                'id' => 'dev_mode_main_js',
                'type' => 'text',
                'title' => __('Vite 主脚本入口地址', 'sakurairo'),
                'default' => "https://wordpress:5173/main.js",
            ],

            [
                'id'    => 'dev_mode_admin_only',
                'type'  => 'switcher',
                'title' => __('开发者模式仅管理员', 'sakurairo'),
                'label'   => __('启用后只有登陆为管理员的用户才会进入开发模式，游客不受影响', 'sakurairo'),
                'default' => true,
            ],

            [
                'id' => 'php_notice_filter',
                'type' => 'select',
                'title' => __('PHP日志过滤', 'sakurairo'),
                'options' => [
                    'inner' => __('使用PHP配置', 'sakurairo'),
                    'normal' => __('只显示严重错误', 'sakurairo'),
                    'all' => __('过滤大部分错误', 'sakurairo'),
                ],
                "default" => "normal",
                'desc' => __('建议设置为“只显示严重错误”来防止不影响使用的php日志渲染到前端', 'sakurairo'),
            ],
        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'parent' => 'others',
        'title' => __('AI 摘要', 'sakurairo'),
        'icon'        => 'fa fa-magic',
        'fields'      => [

            [
                'id' => 'ai_api_base',
                'type' => 'text',
                'title' => __('接口地址', 'sakurairo'),
                'desc' => __('OpenAI 兼容接口的 Base URL，主题会自动追加 /chat/completions', 'sakurairo'),
            ],

            [
                'id' => 'ai_api_key',
                'type' => 'text',
                'attributes' => ['type' => 'password'],
                'title' => __('API Key', 'sakurairo'),
                'default' => '',
            ],

            [
                'id' => 'ai_model',
                'type' => 'text',
                'title' => __('模型名称', 'sakurairo'),
                'desc' => __('例如 Qwen3.8-27B', 'sakurairo'),
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'title' => __('Backup&Recovery', 'sakurairo'),
        'icon'        => 'fa fa-shield',
        'description' => __('备份或恢复你的主题设置', 'sakurairo'),
        'fields'      => [

            [
                'type' => 'backup',
            ],

        ]
    ]);

    Sakurairo_CSF::createSection($prefix, [
        'title' => __('About Theme', 'sakurairo'),
        'icon'        => 'fa fa-paperclip',
        'fields'      => [

            [
                'type'    => 'subheading',
                'content' => __('Version Info', 'sakurairo'),
            ],

            [
                'type'    => 'content',
                'content' => __('<img src="https://s.nmxc.ltd/sakurairo_vision/@3.0/series/headlogo.webp"  alt="Theme Information" />', 'sakurairo'),
            ],

            [
                'type'    => 'submessage',
                'style'   => 'normal',
                'content' => sprintf(__('Theme Sakurairo Version %s | Internal Version %s | <a href="https://github.com/mirai-mamori/Sakurairo">Project Address</a>', 'sakurairo'), IRO_VERSION, INT_VERSION),
            ],

            [
                'type'    => 'subheading',
                'content' => __('Update Related', 'sakurairo'),
            ],

            [
                'id'          => 'iro_update_source',
                'type'        => 'image_select',
                'title' => __('Theme Update Source', 'sakurairo'),
                'options'     => [
                    'github'  => $vision_resource_basepath . 'options/update_source_github.webp',
                    'upyun'  => $vision_resource_basepath . 'options/update_source_wafpro.webp',
                    'official_building'  => $vision_resource_basepath . 'options/update_source_iro.webp',
                ],
                'desc' => __('If you are using a server set up in mainland China, please use the Upyun source or the official theme source as your theme update source', 'sakurairo'),
                'default'     => 'github'
            ],

            [
                'id' => 'channel_validate_value',
                'type' => 'text',
                'title' => __('Theme Update Test Channel Disclaimer', 'sakurairo'),
                'dependency' => ['iro_update_source', '==', 'official_building'],
                'desc' => __('Please copy the text in quotes after <strong>ensure that you have carefully understood the risks associated with participating in the test and are willing to assume all consequences at your own risk</strong> (including but not limited to possible data loss) into the options text box <strong> "I agree and am willing to bear all unexpected consequences"</strong>', 'sakurairo'),
            ],

            [
                'id' => 'iro_update_channel',
                'type' => 'radio',
                'title' => __('Theme Update Channel', 'sakurairo'),
                'dependency' => [
                    ['channel_validate_value', '==', 'I agree and am willing to bear all unexpected consequences'],
                    ['iro_update_source', '==', 'official_building'],
                ],
                'desc' => __('You can toggle the update channel here to participate in the testing of the new version', 'sakurairo'),
                'options' => [
                    'stable' => __('Stable Channel', 'sakurairo'),
                    'beta' => __('Beta Channel', 'sakurairo'),
                    'preview' => __('Preview Channel', 'sakurairo'),
                ],
                'default' => 'stable'
            ],

            [
                'type' => 'subheading',
                'content' => __('Resource Control', 'sakurairo'),
            ],

            [
                'id' => 'vision_resource_basepath',
                'type' => 'text',
                'title' => __('Vision Resource Basepath', 'sakurairo'),
                'desc' => __('This link directory structure needs to be consistent with the <a href="https://github.com/Fuukei/Sakurairo_Vision">Sakurairo Vision</a> repositories officially provided by fuukei, otherwise some resources 404 may appear. The image source officially provided by <a href="https://waf.pro/">WAFPRO</a> is adopted by default.', 'sakurairo'),
                'default' => "https://s.nmxc.ltd/sakurairo_vision/@3.0/"
            ],

            [
                'type' => 'subheading',
                'content' => __('Theme Contributors', 'sakurairo'),
            ],

            [
                'type'    => 'content',
                'content' => __('<img src="https://fuukei-api.nyat.icu/api/contributors" alt="Theme Contributors" width="100%" height="100%" />', 'sakurairo'),
            ],

            [
                'type' => 'subheading',
                'content' => __('隐私信息', 'sakurairo'),
            ],

            [
                'type'    => 'content',
                'content' => __('<p>主题尊重你的隐私</p>
        <p>但是，当你使用主题预置的中国大陆服务商提供的服务时，服务商可能会收集有关你的访问者的数据并统计数据</p>
        <p>你可以通过本地化与主题相关的资源来减少发送给第三方的信息，主题提供相关选项配置</p>', 'sakurairo'),
            ],

            [
                'id' => 'send_theme_version',
                'type' => 'switcher',
                'title' => __('Send Theme Version to Fuukei', 'sakurairo'),
                'label' => __('The theme will only send time and version information to Fuukei officials and the data will be cleaned regularly and used only to count version updates.', 'sakurairo'),
                'default' => false
            ],

            [
                'type' => 'subheading',
                'content' => __('引用信息', 'sakurairo'),
            ],

            [
                'type'    => 'content',
                'content' => __('<p>Fluent Design Icon Referenced by Paradox Fluent Icon Pack</p>
        <p>MUH2 Design Icon Referenced by 缄默 <a href="https://www.coolapk.com/apk/com.muh2.icon">MUH2 Icon Pack</a></p>', 'sakurairo'),
            ],

            [
                'type'    => 'subheading',
                'content' => __('依赖信息', 'sakurairo'),
            ],

            [
                'type'    => 'content',
                'content' => __('<p>Options Framework Relies on the Codestar Open Source <a href="https://github.com/Codestar/codestar-framework">Codestar Framework</a> Project</p>
        <p>Update Function Relies on YahnisElsts Open Source <a href="https://github.com/YahnisElsts/plugin-update-checker">Plugin Update Checker</a> Project</p>
        <p>Visual Editor Related Functions Relies on Themeum Open Source <a href="https://github.com/themeum/kirki">Kirki</a> Project</p>', 'sakurairo'),
            ],

            [
                'type'    => 'content',
                'content' => __('<img src="https://img.shields.io/github/v/release/mirai-mamori/Sakurairo.svg?style=flat-square"  alt="Theme latest version" style="border-radius: 3px;" />  <img src="https://img.shields.io/github/release-date/mirai-mamori/Sakurairo?style=flat-square"  alt="Theme latest version release date" style="border-radius: 3px;" />  <img src="https://data.jsdelivr.com/v1/package/gh/mirai-mamori/Sakurairo/badge"  alt="Theme CDN resource access" style="border-radius: 3px;" />', 'sakurairo'),
            ],

        ]
    ]);
}
