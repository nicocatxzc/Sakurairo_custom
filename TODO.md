# 选项迁移 TODO（3.0.11 → 3.1.0）

对比对象：

- 旧：`temp/Sakurairo/opt/options/theme-options.php`（Sakurairo 3.0.11）
- 新：`opt/options/theme-options.php`（当前 3.1.0，字段改中文标题、大量 id 重命名/重组）

统计：旧版 **307** 个字段，新版 **158** 个；旧版中 **247** 个 id 在新版不存在。逐个核对后分为四类：

| 分类 | 数量 | 处理 |
| --- | --- | --- |
| 已改名/已合并平替 | 124（含 16 项需改代码） | 见【TODO-1】与文末对照表 |
| 无平替（功能/配置能力真的没了） | 107 | **TODO-2，逐项决定补回与否** |
| 有平替但能力削弱 | 6 | TODO-2B，需复核 |
| 由新机制取代（不是丢失） | 10 | 见「由新机制取代」一节 |

> 注意：旧版选项值仍留在数据库的 `iro_options` 里，所以**已改名的项在升级后仍"看起来能用"，一旦在后台动过就断链**；全新安装则直接吃默认值。

---

## TODO-1 改名遗漏：面板已改名，代码仍在读旧 key

这些项后台已经显示为新设置，但代码读的还是 3.0.11 的 key，改设置**不会生效**（值只能靠数据库里的旧记录兜着）。逐条改读取处的 key 即可。

| 旧 key | 新设置 id | 仍读旧 key 的位置 | 说明 |
| --- | --- | --- | --- |
| `custom_login_switch` | `login_custom_switch` | `inc/functions/custom/login.php:5`；`opt/options/theme-options.php:1792`（`login_logo_img` 的 dependency 里也写成旧 key） | 后台「定制登录页」开关失效 |
| `theme_skin` | `word_color_first`（或 `active_color`） | `inc/functions/custom/login.php:51,74,78` | 登录页配色不跟随主题色 |
| `theme_skin_matching` | `active_color` / `word_color_second` | `inc/functions/custom/login.php:60`、`404.php`、`inc/functions/comment/reply_mail.php` | 同上；`theme_skin_dark` → `active_color_dark` 一并核对 |
| `iro_captcha_level` | `builtin_captcha_level` | `inc/libs/Captcha.php:84` | 内建验证码强度设置失效 |
| `site_header_insert` | `custom_site_header` | `header.php:72` | 「自定义插入 Header 代码」失效 |
| `yiyan_api` | `footer_hitokoto_api` | `frontend/theme_config.php:42`（`hitokoto_apis`） | 页脚一言 API 地址设置失效（`footer_yiyan` → `footer_hitokoto_select` 同理） |
| `reception_background` | `frontend_default_background` | `frontend/theme_style_vars.php:35` | 前台默认背景设置失效 |
| `theme_darkmode_img_bright` | 新版「图像亮度」字段 | `frontend/theme_style_vars.php:64` | 新版该字段 id 被误写成重复的 `background_transparency_dark`（见 TODO-3） |
| `load_in_svg` | 无新设置（新版只有 `missing_images_placeholder` / `missing_avatars_placeholder`） | `inc/functions/comment/filter.php:48,50` | 图片占位 SVG 无 UI，代码仍读（旧值空则输出空 src） |
| `post_cover_options` | `post_card_image` | `inc/functions/tools.php:35` | 文章封面图来源设置失效 |
| `post_cover` | `post_card_image_url` | `inc/functions/tools.php:36` | 文章封面随机图 API 失效 |
| `random_graphs_options` | `cover_random_pic_url_pc` / `_mb` | `inc/functions/tools.php:40,45,50` | 封面随机图来源设置失效 |
| `random_graphs_link` | `cover_random_pic_url_pc` | `inc/functions/tools.php:46,50` | 同上 |
| `mail_notify` / `admin_notify` | 无新设置 | `inc/functions/comment/reply_mail.php:21,22` | 评论邮件通知开关已无 UI，代码仍在读 |
| `aplayer_server` / `aplayer_playlistid` | 无新设置 | `inc/functions/operator.php:44` | 播放器整块已删，此处残留 |

---

## TODO-2 无平替：功能/配置能力已消失

以下按功能域列出，建议逐条决定「补回 / 明确放弃 / 改为硬编码」。

### C1. 页脚在线音乐播放器（整块删除，8 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `aplayer_server` | select | Footer Online Music Player | 播放器开关（off/netEase…） |
| `custom_music_api` | text | Use custom Meting API or playlist | 自定义 Meting API |
| `aplayer_server_proxy` | text | Footer Online Music Player Proxy | 播放代理 |
| `aplayer_playlistid` | text | Footer Online Music Player Songlist ID | 歌单 ID |
| `aplayer_order` | select | Footer Online Music Player Mode | 播放模式 |
| `aplayer_preload` | select | Footer Online Music Player Preload | 预加载 |
| `aplayer_volume` | slider | Default Volume of Footer Online Music Player | 默认音量 |
| `aplayer_cookie` | textarea | Netease Cloud Music Cookies | 网易云 cookie（VIP 曲目） |

新版前台已无任何播放器渲染代码（`grep aplayer frontend/` 为空），只剩 `inc/functions/operator.php` 的 Meting 跳转与 `/sakura/v1/meting/aplayer` 接口。若要保留播放器，需要补回前端组件 + 上述设置。

### C2. ChatGPT 相关（整块删除，9 项）

| 旧 id | 类型 | 标题 |
| --- | --- | --- |
| `chatgpt_endpoint` | text | ChatGPT Base URL |
| `chatgpt_access_token` | text | ChatGPT API keys |
| `chatgpt_max_tokens` | slider | ChatGPT Max Tokens |
| `chatgpt_model` | text | ChatGPT Model |
| `chatgpt_api_request_timeout` | slider | ChatGPT API Request Timeout |
| `chatgpt_auto_article_summarize` | switcher | ChatGPT Auto Article Summarize |
| `chatgpt_exclude_ids` | text | 不参与摘要的文章 ID |
| `chatgpt_init_prompt` | textarea | 文章摘要 Init Prompt |
| `chatgpt_annotations_prompt` | textarea | 文章注释 Init Prompt |

新版选项、`inc/blocks/`、`frontend/` 中已无 ChatGPT 痕迹（旧版的「AI 摘要 / 术语注释」能力随之消失）。

### C3. 评论区：图片上传、地理位置、通知（20 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `comment_area` | radio | 评论区展开/收起 | |
| `comment_area_image` | upload | 评论区右下背景图 | |
| `comment_location` | switcher | 评论显示地理位置 | 位置功能整组删除 |
| `show_location_in_manage` | switcher | 管理页显示位置信息 | |
| `save_location` | switcher | 位置信息持久化 | |
| `comment_private_message` | switcher | 私密评论 | |
| `qq_avatar_link` | select | QQ 头像链接加密 | |
| `img_upload_api` | select | 评论区上传图接口 | 图床上传整组删除 |
| `img_upload_max_size` | slider | 最大上传体积 | |
| `imgur_client_id` | text | Imgur Client ID | |
| `imgur_upload_image_proxy` | text | Imgur 上传代理 | |
| `smms_client_id` | text | SM.MS Secret Token | |
| `chevereto_api_key` | text | Chevereto API v1 Key | |
| `cheverto_url` | text | Chevereto Address | |
| `lsky_api_key` | text | Lsky Pro v1 Token | |
| `lsky_url` | text | Lsky Pro Address | |
| `comment_image_proxy` | text | 评论图片代理（weserv） | |
| `mail_notify` | switcher | 用户邮件回复通知 | 代码仍在读，见 TODO-1 |
| `admin_notify` | switcher | 管理员邮件回复通知 | 同上 |
| `smilies_proxy` | text | 自定义表情 CDN 代理 | 表情改为 repeater 上传后代理设置消失 |

### C4. 登录页/仪表盘验证码（5 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `captcha_select` | select | Captcha Selection | 旧版覆盖「登录页 + 仪表盘」，新版只剩 `login_captcha_select`（登录页），仪表盘验证码无平替 |
| `vaptcha_vid` | text | Vaptcha VID | 新版只保留内建 / Turnstile |
| `vaptcha_key` | text | Vaptcha KEY | |
| `vaptcha_scene` | select | Vaptcha Scene | |
| `turnstile_theme` | select | Turnstile Theme | 新版 Turnstile 固定主题 |

### C5. 封面：动画与装饰（17 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `cover_full_screen` | switcher | 封面全屏 | 新版封面固定全屏 |
| `cover_half_screen_curve` | switcher | 封面弧形遮挡 | |
| `cover_animation` | switcher | 封面入场动画 | 打字机（`cover_typedjs`）还在，入场动画删除 |
| `cover_animation_time` | slider | 封面动画时长 | |
| `wave_effects` | switcher | 封面波浪效果 | 新版无 |
| `drop_down_arrow` | switcher | 封面下拉箭头 | 整组下拉箭头（含颜色/移动端）删除 |
| `drop_down_arrow_mobile` | switcher | 移动端下拉箭头 | |
| `drop_down_arrow_color` | color | 下拉箭头颜色 | |
| `drop_down_arrow_dark_color` | color | 下拉箭头颜色（深色） | |
| `infor_bar_style` | image_select | 封面信息栏样式（v1/v2） | 新版只有 v1 布局 |
| `social_area_radius` | slider | 社交栏圆角 | 新版无 |
| `cache_cover` | switcher | 封面随机图缓存 | 新版无 |
| `cover_video` | switcher | 封面视频 | 封面视频整组删除 |
| `cover_video_loop` | switcher | 封面视频循环 | |
| `cover_video_live` | switcher | 封面视频自动续播 | |
| `cover_video_link` | text | 封面视频基础路径 | |
| `cover_video_title` | text | 封面视频文件名 | |

### C6. 文章页（新版没有对应分区，13 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `article_meta_show_in_head` | select | 正文前显示 Meta | 新版无（卡片 meta 是另一套 `post_card_metas`） |
| `article_title_line` | switcher | 标题下划线动画 | |
| `inline_code_background_color` | color | 行内代码背景色 | 新版的 `code_block_background_color` 是代码块，不是行内代码 |
| `inline_code_background_color_in_dark_mode` | color | 行内代码背景色（深色） | |
| `article_function` | switcher | 文章功能栏 | 整块删除 |
| `article_lincenses` | select | 文章版权协议 | `grep license frontend/` 已无 |
| `reward_area` | fieldset | 文章赞赏 | 整块删除 |
| `author_profile_avatar` | switcher | 作者名片头像 | 作者名片整组删除 |
| `author_profile_name` | switcher | 作者名片名称 | |
| `author_profile_quote` | switcher | 作者名片签名 | |
| `article_modified_time` | switcher | 最后更新时间 | |
| `article_tag` | switcher | 文章标签 | |
| `article_nextpre` | switcher | 上一篇/下一篇 | 新版无上一篇/下一篇导航 |

### C7. 加载占位、性能与动画（14 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `preload_animation` | switcher | 预加载动画 | 整组删除 |
| `preload_animation_color1` | color | 预加载配色 A | |
| `preload_animation_color2` | color | 预加载配色 B | |
| `preload_blur` | slider | 预加载模糊过渡时长 | |
| `load_out_svg` | text | 控件加载占位 SVG | 新版无 SVG 占位（`puff-load` 已无引用），只剩图片/头像占位 |
| `load_nextpage_svg` | text | 下一页加载占位 SVG | |
| `load_in_svg` | text | 图片加载占位 SVG | 代码仍在读，见 TODO-1 |
| `page_lazyload` | switcher | 页面懒加载 | 新版无开关 |
| `page_lazyload_spinner` | text | 懒加载占位图 | |
| `entry_content_style` | radio | 页面布局风格（sakurairo/…） | 新版无 |
| `page_title_animation` | switcher | 页面标题动画 | 整组删除 |
| `page_title_animation_time` | slider | 页面标题动画时长 | |
| `clipboard_ref` | switcher | 复制时追加出处 | 新版无（`frontend/components/post/render.js` 只做代码复制） |
| `smoothscroll_option` | switcher | 全局平滑滚动 | 新版无开关 |

### C8. 页脚（3 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `footer_direction` | select | 页脚内容分布（columns/…） | 新版无 |
| `footer_load_occupancy` | switcher | 页脚加载占用查询 | 新版无 |
| `footer_upyun` | switcher | 页脚又拍云联盟 logo | 新版无 |

### C9. 搜索（2 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `search_area_background` | upload | 搜索区域背景图 | 新版无 |
| `live_search_comment` | switcher | 实时搜索包含评论 | 新版 `search_live` 无该细分 |

### C10. 前台与小组件（2 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `reception_background_size` | select | 前台背景缩放方式 | 新版固定 |
| `unlisted_avatar` | upload | 导航栏未登录用户头像 | 新版无 |

### C11. 友链模板（2 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `friend_link_align` | image_select | 友链单元对齐 | 新版无 |
| `friend_link_form` | switcher | 友链申请表单 | 新版无 |

### C12. 展示区胶囊组件（整块删除，3 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `capsule_components` | select(多选) | Capsule Components | 统计胶囊：文章数 / 评论数 / 访问量 / 链接数 / 作者数，新版无 |
| `show_medal_capsules` | switcher | 勋章胶囊（里程碑） | 新版无 |
| `stat_announcement_text` | textarea | 公告胶囊文本 | 新版无 |

### C13. 低使用杂项（8 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `google_analytics_id` | text | Google Analytics Id | 新版无（请用插件或 `footer_html`） |
| `site_custom_style` | code_editor | 自定义 CSS | **新版没有对应入口**，只能走子主题/Additional CSS |
| `time_zone_fix` | slider | 评论时区修正 | 新版无 |
| `ghcard_proxy` | switcher | GitHub 卡片服务端代理 | 新版无 |
| `classify_display` | text | 首页分类不显示（分类 ID） | 新版无 |
| `image_category` | text | 图片展示分类（分类 ID） | 新版无 |
| `cookie_version` | text | 前端缓存版本控制 | 新版无 |
| `hide_login_portal` | switcher | 隐藏登录入口 | 新版无 |

### C14. 导航样式（2 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `choice_of_nav_style` | image_select | 导航菜单样式选择 | 新版只有一套导航，样式选择与「精灵岛」导航一并消失 |
| `nav_menu_style` | select | 精灵岛导航样式 | 同上（其内部 `distribution` / `option_spacing` 对应新 `navbar_distribution` / `navbar_option_margin`） |

### C15. 文章列表卡片（2 项）

| 旧 id | 类型 | 标题 | 备注 |
| --- | --- | --- | --- |
| `post_list_ticket_type` | image_select | 卡片内标题样式（card/非 card） | 新版卡片设计 fieldset 无该细分 |
| `article_meta_background_compatible` | switcher | Meta 背景兼容模式 | 新版无 |

---

## TODO-2B 有平替但有损（需复核是否可接受）

这些项都能在新版找到对应设置，但能力被削弱，建议确认是否需要补回细节。

| 旧 id | 平替 | 损失点 |
| --- | --- | --- |
| `theme_darkmode_strategy` | `theme_darkmode_auto` | 旧的可选「手动/定时/跟随系统」，新版只剩一个自动开关 |
| `avatar_radius` | `cover_infor_bar_radius` | 旧版头像圆角（100px）与信息栏圆角（15px）分开，新版合并成一个值 |
| `signature_radius` | `cover_infor_bar_radius` | 同上 |
| `wechat_copy_switch` | `cover_social_displays`（微信项） | repeater 里没有「点击复制微信号」的字段与交互 |
| `qq_copy_switch` | `cover_social_displays`（QQ 项） | 同上 |
| `smilies_dir` | `comment_smilies_custom` | 旧版是「填目录 + 后台更新表情列表」，新版只能在 repeater 里逐条上传 |

---

## TODO-3 新版设置面板自身的问题

1. **重复 id**：`opt/options/theme-options.php` 的「外观设置」里有两个 `background_transparency_dark`（一个「背景透明度」、一个「图像亮度」）。后者应是图片亮度（原 `theme_darkmode_img_bright`），需要改成独立 id 并同步 `frontend/theme_style_vars.php:64` 的读取。
2. **新设置未被任何代码读取**（面板上有、改了没效果），取 grep 结果如下，建议逐个确认是否漏了接线：
   `builtin_captcha_level`(实际读的是 `iro_captcha_level`)、`channel_validate_value`、`comment_useragent`、`cover_pic_filter`、`cover_social_switch`、`cover_switch`、`custom_site_header`、`dev_mode_hmr_client`、`dev_mode_main_js`、`extra_fonts`、`footer_hitokoto_api`、`homepage_component_title_font`、`login_custom_switch`、`login_urlskip`、`nav_menu_cover_switch`、`page_patternimg`、`page_template_data_cache`、`page_title_font_size`、`page_title_font_size_with_image`、`post_card_image`、`post_card_image_url`、`post_cover_as_background`、`post_list_with_shuoshuo`、`search_live`、`steam_covercdn`、`steam_store`、`theme_darkmode_auto`、`widget_darkmode_switch`、`widget_wordpress_widget`。
   （其中一部分应该是通过 `_iro.config` 注入前端后在 JS 里消费，属正常；但 `custom_site_header` / `footer_hitokoto_api` / `login_custom_switch` / `builtin_captcha_level` / `page_patternimg` 这几个是明确的旧 key 残留，见 TODO-1。）
3. **Kirki 自定义器仍全部指向 3.0.11 的 key**：`opt/customizer/init.php` 里 143 个 `iro_key` 没有一个出现在新版 id 列表里（如 `theme_skin`、`personal_avatar`、`infor_bar`、`post_list_ticket_type`…）。也就是说自定义器里除 `cover_switch`、`nav_user_menu`、`nav_menu_cover_radius` 等少数同名项外，**保存后都写进了没人读的键**。要么同步改 `iro_key`，要么明确废弃自定义器。

---

## 由新机制取代（不算丢失，无需补回）

| 旧 id | 说明 |
| --- | --- |
| `extract_theme_skin_from_cover` | 新版已注释掉开关，改为默认从封面图取色，不再提供关闭选项 |
| `extract_article_highlight_from_feature` | 同上（从特色图取高亮色） |
| `code_highlight_prism_line_number_all` | 代码高亮改为 `code_highlight_method`（hljs 为主），Prism 的一整套自配置删除 |
| `code_highlight_prism_autoload_path` | 同上 |
| `code_highlight_prism_theme_light` | 同上 |
| `code_highlight_prism_theme_dark` | 同上 |
| `lightgallery_option` | 灯箱改为 `lightbox`（WordPress 6.4 内置 / 主题自带二选一） |
| `enable_theme_mathjax` | 公式改为 `code_katex`（KaTeX） |
| `core_library_basepath` | 新版前端资源由 Vite 打包进 `frontend/dist`，不再需要「本地化 / 公共 CDN」三件套；第三方资源改用 `image_cdn`、`fontawesome_source` |
| `shared_library_basepath` | 同上 |
| `lib_cdn_path` | 同上 |
| `external_vendor_lib` | 同上 |

---

## 旧值迁移提示

选项是**整块 `iro_options` 数组**，改名的项在升级后不会自动继承：

- 已改名项（如 `theme_skin` → `word_color_first`、`style_menu_radius` → `widget_button_radius`、`signature_*` → `cover_signature`）：数据库里还是旧键的值，面板显示的是新键（默认值）。**需要写一次性迁移脚本把旧键值搬到新键**，否则用户看到的样式会突然变回默认。
- 合并进 fieldset/repeater 的项（`text_logo` → `cover_title`、`signature_typing_json` → `cover_typedjs_config`、`post_list_card_radius` → `post_card_design.card_radius`、`missing_avatars_default` → `missing_avatars_placeholder` 等）：注意父键不同，同样需要迁移。

---

## 附录：旧 → 新 平替对照（供核对，无需改动）

```text
personal_avatar                             ->  cover_avatar
text_logo_options                           ->  cover_focus_style
text_logo                                   ->  cover_title
iro_logo                                    ->  nav_logo
theme_skin                                  ->  word_color_first   [改名遗漏：代码仍读旧 key]
theme_skin_matching                         ->  active_color / word_color_second   [改名遗漏：代码仍读旧 key]
theme_skin_dark                             ->  active_color_dark
theme_darkmode_img_bright                   ->  （新版字段 id 被误写成 background_transparency_dark）   [改名遗漏：代码仍读旧 key]
theme_darkmode_widget_transparency          ->  widget_transparency_dark
theme_darkmode_background_transparency      ->  background_transparency_dark
theme_commemorate_mode                      ->  theme_commemorate_mode_date
reference_exter_font                        ->  extra_fonts
exter_font                                  ->  extra_fonts（repeater）
sakura_nav_style                            ->  navbar_distribution + navbar_option_margin
nav_menu_font                               ->  nav_title_font / nav_option_font
nav_text_logo                               ->  nav_title + nav_title_font
cover_random_graphs_switch                  ->  nav_menu_cover_switch
style_menu_radius                           ->  widget_button_radius
style_menu_selection_radius                 ->  widget_panel_radius
style_menu_font                             ->  widget_font
sakura_widget                               ->  widget_wordpress_widget
widget_daynight                             ->  widget_darkmode_switch
reception_background_blur                   ->  background_blur
reception_background                        ->  frontend_default_background   [改名遗漏：代码仍读旧 key]
reception_background_transparency           ->  background_transparency
global_font_2                               ->  widget_font_choice / extra_fonts
footer_info                                 ->  footer_html
footer_text_font                            ->  footer_font
footer_addition                             ->  footer_html
footer_yiyan                                ->  footer_hitokoto_select   [改名遗漏：代码仍读旧 key]
yiyan_api                                   ->  footer_hitokoto_api   [改名遗漏：代码仍读旧 key]
nav_menu_search                             ->  nav_menu_search_switch
only_admin_can_search_pages                 ->  search_pages_can_only_admins
sticky_pinned_content                       ->  search_for_pinned_posts
custom_exclude_search_results               ->  search_results_custom_exclude
live_search                                 ->  search_live
sakura_falling_effects                      ->  frontend_particle = sakura
particles_effects                           ->  frontend_particle
particles_json                              ->  particle_config
poi_pjax                                    ->  pjax
nprogress_on                                ->  top_loading_progress
pagenav_style                               ->  pagination_mode
page_auto_load                              ->  pagination_mode + pagination_ajax_wait
missing_avatars_default                     ->  missing_avatars_placeholder
missing_images_default                      ->  missing_images_placeholder
infor_bar                                   ->  cover_infor_bar_switch
homepage_widget_transparency                ->  widget_transparency
signature_text                              ->  cover_signature[text]
signature_font                              ->  cover_signature[font]
signature_font_size                         ->  cover_signature[size]
signature_typing                            ->  cover_typedjs
signature_typing_marks                      ->  cover_typedjs_mark
signature_typing_placeholder                ->  cover_typedjs_placeholder
signature_typing_json                       ->  cover_typedjs_config
random_graphs_options                       ->  cover_random_pic_url_pc / _mb   [改名遗漏：代码仍读旧 key]
random_graphs_mts                           ->  cover_random_pic_url_mb
random_graphs_link                          ->  cover_random_pic_url_pc   [改名遗漏：代码仍读旧 key]
random_graphs_link_mobile                   ->  cover_random_pic_url_mb
site_bg_as_cover                            ->  cover_as_background
post_cover_as_bg                            ->  post_cover_as_background
random_graphs_filter                        ->  cover_pic_filter
social_area                                 ->  cover_social_switch
social_display_icon                         ->  cover_social_icon
wechat_qrcode_switch                        ->  cover_social_displays[wechat]
wechat_qrcode                               ->  cover_social_displays[qrcode]
wechat_id                                   ->  cover_social_displays[wechat]
wechat_url                                  ->  cover_social_displays[link]
qq_qrcode_switch                            ->  cover_social_displays[qq]
qq_qrcode                                   ->  cover_social_displays[qrcode]
qq_id                                       ->  cover_social_displays[qq]
qq_url                                      ->  cover_social_displays[link]
bili                                        ->  cover_social_displays[bilibili]
wangyiyun                                   ->  cover_social_displays[netease_music]
sina                                        ->  cover_social_displays[sina]
github                                      ->  cover_social_displays[github]
telegram                                    ->  cover_social_displays[telegram]
steam                                       ->  cover_social_displays[steam]
youtube                                     ->  cover_social_displays[youtube]
instagram                                   ->  cover_social_displays[instgram]
douyin                                      ->  cover_social_displays[tiktok]
xiaohongshu                                 ->  cover_social_displays[xiaohongshu]
discord                                     ->  cover_social_displays[discord]
zhihu                                       ->  cover_social_displays[zhihu]
linkedin                                    ->  cover_social_displays[linkedin]
twitter                                     ->  cover_social_displays[twitter]
facebook                                    ->  cover_social_displays[facebook]
email_name                                  ->  cover_social_displays[email]
email_domain                                ->  cover_social_displays[email]
diysocialicons                              ->  cover_social_displays[custom]
static_page_id                              ->  homepage_static_page_id
exhibition_area_icon                        ->  homepage_show_title
exhibition_area_title                       ->  homepage_show_title
post_area_icon                              ->  homepage_post_list_title
post_area_title                             ->  homepage_post_list_title
area_title_font                             ->  homepage_component_title_font
area_title_text_align                       ->  homepage_component_title_align
exhibition                                  ->  show_area_content
article_meta_displays                       ->  post_card_metas
post_list_design                            ->  post_card_with_image_design
post_cover_options                          ->  post_card_image   [改名遗漏：代码仍读旧 key]
post_cover                                  ->  post_card_image_url   [改名遗漏：代码仍读旧 key]
post_list_card_radius                       ->  post_card_design[card_radius]
post_meta_radius                            ->  post_card_design[meta_radius]
post_list_title_radius                      ->  post_card_design[title_radius]
post_title_font_size                        ->  post_card_design[title_font_size]
show_shuoshuo_on_home_page                  ->  post_list_with_shuoshuo
patternimg                                  ->  page_patternimg
load_in_svg                                 ->  （新版无对应设置）   [改名遗漏：代码仍读旧 key]
article_title_font_size                     ->  page_title_font_size_with_image
article_auto_toc                            ->  page_post_toc
page_temp_title_font_size                   ->  page_title_font_size_with_image
bangumi_cache                               ->  page_template_data_cache
steam_cache                                 ->  page_template_data_cache
comment_placeholder_text                    ->  comment_input_place_holder
smilies_list                                ->  comment_smilies_list
smilies_name                                ->  comment_smilies_list_custom_name
comment_captcha_select                      ->  comment_captcha
mail_notify                                 ->  （新版无对应设置）   [改名遗漏：代码仍读旧 key]
admin_notify                                ->  （新版无对应设置）   [改名遗漏：代码仍读旧 key]
custom_login_switch                         ->  login_custom_switch   [改名遗漏：代码仍读旧 key]
iro_captcha_level                           ->  builtin_captcha_level   [改名遗漏：代码仍读旧 key]
site_header_insert                          ->  custom_site_header   [改名遗漏：代码仍读旧 key]
lightgallery_option                         ->  lightbox
enable_theme_mathjax                        ->  code_katex
```
