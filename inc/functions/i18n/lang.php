<?php

/**
 * 请求语言：内容说哪种语言，界面就渲染成哪种语言
 *
 * 地址上不再有语言前缀。一篇译文就是一篇普通文章，靠别名后缀（自动生成的 `foo-ja`）
 * 区分、靠 `_iro_i18n_path` 与原文归组，因此：
 * - **单篇**：语言随正在看的那一篇的标记；
 * - **列表与归档**：按访客答过的 cookie 筛选内容（见 `iro_i18n_filter_frontend_query()`），
 *   没答过就是站点默认语言——`/` 与分类页不再属于某个语言。
 *
 * 判语言只认「内容 + cookie」，不看 `Accept-Language`：同一份地址对同一个访客始终一致，
 * 而爬虫不带 cookie，拿到的永远是默认语言那一份。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 访客答过的语言（cookie）
 *
 * 前台在首次访问的询问里写入；没答过、或答的不是启用语言时返回空串。
 */
function iro_i18n_language_cookie(): string
{
    $cookie = isset($_COOKIE[IRO_I18N_LANGUAGE_COOKIE])
        ? strtolower(trim((string) wp_unslash($_COOKIE[IRO_I18N_LANGUAGE_COOKIE])))
        : '';

    return in_array($cookie, iro_i18n_languages(), true) ? $cookie : '';
}

/**
 * 本次请求的语言
 *
 * 单篇随内容：分享出去的译文地址不该渲染成默认语言的界面——译文页的日期格式、
 * `<html lang>` 与主题文案都该是译文那一种。
 *
 * 其余页面没有「这一篇」可依，按 cookie，没答过就是站点默认语言。列表内容与界面
 * 语言都照这个口径，两者必须一致。
 *
 * 主查询跑完（`wp`）之前拿不到单篇对象，那时只能按 cookie 判断，稍后由
 * `iro_i18n_apply_locale()` 在 `wp` 上再收口一次。
 */
function iro_i18n_current_language(): string
{
    global $wp_query;

    if ($wp_query instanceof WP_Query && is_singular()) {
        $post = get_queried_object();

        if ($post instanceof WP_Post) {
            return iro_i18n_post_language($post->ID);
        }
    }

    $cookie = iro_i18n_language_cookie();

    return $cookie === '' ? iro_i18n_default_language() : $cookie;
}

/**
 * 取一篇内容的对外链接
 *
 * 副本有自己的别名（自动生成的 `foo-ja`，早期副本还可能是 `-2`、`-3`），那就是它的
 * 地址；语言不再体现在地址上，这里也不拼任何前缀。
 */
function iro_i18n_translation_permalink(WP_Post $post): string
{
    return (string) get_permalink($post);
}

/**
 * 语言切换器数据：每种启用语言一行
 *
 * `exists` 说明该语言下有没有版本；为假时链接指向原文，不留一个 404。
 *
 * 只有单篇有「同一篇的另一种语言」这回事。列表、归档、搜索各语言共用同一个地址，
 * `url` 留空表示「就停在本页」——切换语言只换界面与筛选，由前端原地重载那一页。
 *
 * `locales` 是该语言的浏览器 locale 候选（已归一化成小写 + 下划线），交给前台判断
 * 「访客偏好的是不是这一种」：这张表只在语言定义里写一份，前台不另抄。
 *
 * `name` 是该语言自己的名字（`English`、`日本語`），**不是**当前界面语言下的译名：
 * 切换器要的是「访客认出自己那种语言」，一个英文访客在中文界面上也得看见 English。
 *
 * @return array<int,array{code:string,name:string,url:string,current:bool,exists:bool,edit:string,locales:string[]}>
 */
function iro_i18n_language_links(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $current     = iro_i18n_current_language();
    $definitions = iro_i18n_language_definitions();
    $post_id     = is_singular() ? (int) get_queried_object_id() : 0;
    // 本次请求的单篇内容与它的基准路径：切换器与 hreflang 都从这一份数据里取
    $post  = [
        'id'   => $post_id,
        'path' => $post_id > 0 ? iro_i18n_get_path($post_id) : '',
        'type' => $post_id > 0 ? (string) (get_post_type($post_id) ?: 'post') : 'post',
    ];
    $map   = $post_id > 0 ? iro_i18n_group_map($post_id) : [];
    $links = [];

    foreach (iro_i18n_languages() as $code) {
        $url = '';

        if ($post_id > 0) {
            if ($post['path'] === '') {
                $queried = get_post($post_id);

                $url = $queried instanceof WP_Post ? iro_i18n_translation_permalink($queried) : (string) home_url('/');
            } else {
                // 该语言下还没有版本时指向原文，免得给一个 404
                $target = iro_i18n_translation_post($post['path'], $code, [$post['type']])
                    ?? iro_i18n_base_post($post['path'], [$post['type']]);

                $url = $target instanceof WP_Post ? iro_i18n_translation_permalink($target) : (string) home_url('/');
            }
        }

        $links[] = [
            'code'    => $code,
            'name'    => iro_i18n_language_native_name($code),
            'url'     => $url,
            'current' => $code === $current,
            'exists'  => $post['path'] === '' || isset($map[$code]),
            'edit'    => isset($map[$code]) ? (string) get_edit_post_link($map[$code]->ID, 'raw') : '',
            'locales' => array_values(array_unique(array_map(
                static fn(string $item): string => strtolower(str_replace('-', '_', $item)),
                array_merge(
                    [(string) ($definitions[$code]['locale'] ?? '')],
                    (array) ($definitions[$code]['locales'] ?? [])
                )
            ))),
        ];
    }

    $cache = $links;

    return $cache;
}

/**
 * 要跟随访客语言重新载入的翻译域
 *
 * 只处理主题自己的文案域。CSF 设置页、可视化编辑器、更新检查器各有自己的域，
 * 那些是后台界面的语言，不该跟着访客走。
 *
 * @return string[]
 */
function iro_i18n_text_domains(): array
{
    return (array) apply_filters('iro_i18n_text_domains', ['sakurairo']);
}

/**
 * 把本次请求的语言落到界面 locale 上
 *
 * `determine_locale` 只决定「取哪份 .mo」，它管不到 `$wp_locale`：月份、星期、上下午名都住在
 * `WP_Locale` 里，而那是 `wp-settings.php` 按**站点语言**建好的，早于本模块。不重建它，英文页面的
 * `M` 会渲染成「9 月」。
 *
 * 这里不走 `switch_to_locale()`：它开头就比 `determine_locale()` 与目标 locale，而本模块的
 * `determine_locale` 过滤器已经让两者相等，于是恒返回 false；它另一个前置条件
 * `get_available_languages()` 只扫 `WP_LANG_DIR`，也看不到主题 `languages/` 下的语言包。
 * 所以照 `WP_Locale_Switcher::change_locale()` 的步骤自己走一遍。语言在这里是「本次请求临时切」，
 * 请求结束随进程回收，不写任何持久化状态。
 *
 * 两个时机各收口一次：`wp_loaded` 上只有 cookie 可依（列表页的界面语言到此就定了），
 * `wp` 上主查询已经跑完，单篇才知道正看的是哪一篇——与 cookie 选的不是同一种时补第二次。
 */
function iro_i18n_apply_locale(): void
{
    static $applied = null;

    if (!iro_is_frontend()) {
        return;
    }

    $locale = iro_i18n_locale(iro_i18n_current_language());

    // `$wp_locale` 与核心语言包都是按站点语言建的，请求语言就是站点语言（或已经切过）时没有可重建的东西
    if ($locale === '' || $locale === iro_i18n_site_locale() || $locale === $applied) {
        return;
    }

    $applied = $locale;

    load_default_textdomain($locale);

    $GLOBALS['wp_locale'] = new WP_Locale();

    // 文本域是惰性加载的，卸掉让它们按新 locale 重新载一次
    foreach (iro_i18n_text_domains() as $domain) {
        unload_textdomain((string) $domain, true);
        get_translations_for_domain((string) $domain);
    }
}

if (iro_i18n_enabled()) {
    add_action('wp_loaded', 'iro_i18n_apply_locale', 5);
    add_action('wp', 'iro_i18n_apply_locale', 5);

    /**
     * 访客答过语言询问（cookie）时，把他送到该语言的同一篇
     *
     * 只认 cookie，不看 `Accept-Language`：爬虫不会带这个 cookie，因此不会被跳转，
     * 也就没有「按语言给不同结果」的嫌疑；没答过询问的访客也不会被搬走——他先看到
     * 默认语言的页面，再由前台问要不要换。
     *
     * 只在默认语言的原文上搬。访客点进某个语言的副本，那是他这一次明确的导航意图，
     * 按 cookie 改掉会让分享出去的译文链接失效。该语言还没有版本时也不搬：
     * 过去只会落到原文，等于原地重载。
     *
     * 「答的就是这一篇的语言」必须排除：默认语言的原文在默认语言的组里就是它自己，
     * 搬过去还是同一个地址，成了自己跳自己。
     */
    add_action('template_redirect', function (): void {
        if (!is_singular()) {
            return;
        }

        $cookie = iro_i18n_language_cookie();

        if ($cookie === '' || $cookie === iro_i18n_current_language()) {
            return;
        }

        $post = get_queried_object();

        if (!$post instanceof WP_Post || !iro_i18n_is_default_language(iro_i18n_post_language($post->ID))) {
            return;
        }

        $path = iro_i18n_get_path($post->ID);

        if ($path === '') {
            return;
        }

        $target = iro_i18n_translation_post($path, $cookie, [$post->post_type]);

        if (!$target instanceof WP_Post) {
            return;
        }

        wp_safe_redirect(iro_i18n_translation_permalink($target), 302);
        exit;
    }, 1);

    /**
     * 让 `determine_locale()` 在前台返回本次请求的语言
     *
     * determine_locale 走用户语言时我们排它后面，把它在前台的决定覆盖掉。语言判定只读内容
     * 语言与 cookie，不依赖查询变量，因此在解析请求之前就能得出结果；早于 `wp_loaded` 的代码
     * （以及按需加载语言包的 `_load_textdomain_just_in_time()`）都要靠这条。
     * 后台不动：那边由 WordPress 的用户语言设置负责。
     */
    add_filter('determine_locale', function (string $locale): string {
        if (!iro_is_frontend()) {
            return $locale;
        }

        return iro_i18n_locale(iro_i18n_current_language());
    }, 25);

    /**
     * 让 `get_locale()` 在前台返回本次请求的语言
     *
     * 与 `determine_locale` 问的不是同一件事：那条决定「取哪份 .mo」，这条是「这个请求算哪种语言」，
     * `number_format_i18n()`、日期与数字格式化都会问它。走过滤器而不是改 `$GLOBALS['locale']`，
     * 与 `WP_Locale_Switcher` 的做法一致。
     */
    add_filter('locale', function (string $locale): string {
        if (!iro_is_frontend()) {
            return $locale;
        }

        return iro_i18n_locale(iro_i18n_current_language());
    }, 25);

    /**
     * `<html lang>` 跟随请求语言
     *
     * 只改语言标记，不切 WordPress 的 locale：译文是人工写的独立文章，
     * 主题文案的翻译由站点自身的语言包负责，两者不该互相牵动。
     *
     * 后台与登录页不动：那里的这个标记是 WordPress 的用户语言，后台界面（AI 面板按
     * `document.documentElement.lang` 选文案）读的正是它。
     */
    add_filter('language_attributes', function (string $output): string {
        if (!iro_is_frontend()) {
            return $output;
        }

        $locale   = iro_i18n_locale(iro_i18n_current_language());
        $replaced = preg_replace('/\blang=(["\']).*?\1/i', 'lang="' . esc_attr($locale) . '"', $output, 1);

        return $replaced === null ? $output : $replaced;
    }, 10, 1);

    /**
     * hreflang 注解
     *
     * 只有单篇有「同一篇的其它语言版本」这回事。列表与归档各语言共用同一个地址，
     * 标注出去会是几条指向自己的重复注解，所以那里一条都不输出。
     *
     * 主题自己的 SEO 只输出 description / og / JSON-LD，规范的 canonical 一直是核心
     * 在 `wp_head` 打的，所以这里也不接管。
     *
     * 只作 SEO 用途。前台切换器**不**消费这些 link：swup 只替换列出的容器，
     * 换页后 head 里的 alternate 还是上一篇的旧值，靠它跳转会跳错；切换器用的是
     * 页面配置里的 `langs`。
     */
    add_action('wp_head', function (): void {
        if (!is_singular()) {
            return;
        }

        $links = iro_i18n_language_links();

        if (count($links) < 2) {
            return;
        }

        $x_default = '';

        foreach ($links as $link) {
            if ($link['url'] === '') {
                continue;
            }

            if ($link['code'] === iro_i18n_default_language()) {
                $x_default = $link['url'];
            }

            // 该语言下没有真实版本就不标注：指向原文的 hreflang 会被判定为错误的语言信号
            if (!$link['exists']) {
                continue;
            }
?>
            <link rel="alternate" hreflang="<?= esc_attr($link['code']) ?>" href="<?= esc_url($link['url']) ?>">
        <?php
        }

        if ($x_default !== '') {
        ?>
            <link rel="alternate" hreflang="x-default" href="<?= esc_url($x_default) ?>">
<?php
        }
    }, 2);

    /**
     * 未翻译副本不进搜索引擎
     */
    add_filter('wp_robots', function (array $robots): array {
        if (is_singular() && iro_i18n_is_skeleton((int) get_queried_object_id())) {
            unset($robots['index'], $robots['follow']);
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }

        return $robots;
    });
}
