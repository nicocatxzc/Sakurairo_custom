<?php

/**
 * 请求语言与前缀路由
 *
 * URL 口径：无前缀 = 站点默认语言；`/xx/…` = 该语言版本。
 * 因此各语言版本共用同一段路径，只有前缀不同——副本不再靠别名区分
 * （`xxx-tw-untitled` 那种别名会把真实路径顶掉，前台只剩一个后缀）。
 *
 * 解析与输出分两段：
 * - `parse_request`：把带前缀的请求还原成正确的文章对象；
 * - `post_link` / `page_link`：把非默认语言版本的链接重新带上前缀。
 *
 * 前缀路由只覆盖文章与页面（含多级页面）。语言下的归档、分类页暂不开放：
 * 需要把分类/标签查询也翻译成前缀并另做一套 canonical，不属于本次范围。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 请求 URI 的语言前缀，没有则返回空串
 *
 * 站点装在子目录时直接退出：`/blog/tw/…` 里 `blog` 是 WordPress 自己的
 * 路径，前缀判定会连着它一起算错。
 */
function iro_i18n_request_prefix(): string
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = '';

    if (trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/') !== '') {
        return $cached;
    }

    $path = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');

    if ($path === '') {
        return $cached;
    }

    $segment = strtolower((string) strtok($path, '/'));

    if ($segment === '') {
        return $cached;
    }

    // 归并回定义里声明的写法，URL 大小写不同不影响解析
    foreach (iro_i18n_language_definitions() as $definition) {
        $prefix = (string) $definition['prefix'];

        if (strtolower($prefix) === $segment) {
            $cached = $prefix;
            break;
        }
    }

    return $cached;
}

/**
 * 本次请求的语言代号
 *
 * 只由地址决定：带前缀就是那一种，无前缀就是站点默认语言。
 *
 * 渲染不看 cookie、也不看 `Accept-Language`：同一份 HTML 必须对所有人一致，否则页面
 * 缓存与爬虫拿到的东西会随访客而变。访客选过什么只用于跳转——答过首次访问询问的访客
 * 访问无前缀地址时会被搬到他的语言（见下面 `template_redirect` 上那条闭包）。
 */
function iro_i18n_current_language(): string
{
    $prefix = iro_i18n_request_prefix();

    if ($prefix !== '') {
        $segment = strtolower($prefix);

        foreach (iro_i18n_language_definitions() as $code => $definition) {
            if (strtolower((string) $definition['prefix']) === $segment) {
                return (string) $code;
            }
        }
    }

    return iro_i18n_default_language();
}

/**
 * 剥掉语言前缀的请求路径（不含前后斜杠）
 */
function iro_i18n_stripped_request(): string
{
    $path   = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
    $prefix = iro_i18n_request_prefix();

    if ($prefix === '') {
        return $path;
    }

    return trim(substr($path, strlen($prefix)), '/');
}

/**
 * 给链接加语言前缀，默认语言不加
 *
 * 前缀取 `iro_i18n_prefix()` 的简化形态，因此链接里看到的是 `/en/…`、
 * `/jp/…`，而已用代号（`en-us`、`ja`）出现在设置项与 meta 里。
 *
 * 三种形态都要照顾：`/foo`、`/?p=1`、已带前缀的链接（幂等）。
 */
function iro_i18n_prefix_url(string $url, string $code): string
{
    if ($url === '' || iro_i18n_is_default_language($code)) {
        return $url;
    }

    $prefix = iro_i18n_prefix($code);
    $home   = home_url('/');

    if (strpos($url, $home) !== 0) {
        return $url;
    }

    $rest = substr($url, strlen($home));

    if ($rest === '' || $rest === $prefix || strpos($rest, $prefix . '/') === 0) {
        return $url;
    }

    // 无固定链接时链接形如 `/?p=1`，前缀只能插在问号前
    if (strpos($rest, '?') === 0) {
        return $home . $prefix . '/' . $rest;
    }

    return $home . $prefix . '/' . ltrim($rest, '/');
}

/**
 * 取一篇内容的对外链接
 *
 * 前缀由 `post_link` / `page_link` 过滤器统一加，这里不再自己拼，
 * 免得同一段前缀被加两次。
 */
function iro_i18n_translation_permalink(WP_Post $post): string
{
    return (string) get_permalink($post);
}

/**
 * 语言切换器数据：每种启用语言一行
 *
 * `exists` 说明该语言下有没有真实版本；为假时链接指向原文，
 * 由路由层把访客带回去，而不是给一个 404。
 *
 * 链接不在这里拼前缀：单篇内容的地址由 `post_link` / `page_link` 过滤器带，
 * 归档与首页才用 `iro_i18n_prefix_url()`，免得同一段前缀被加两次。
 *
 * `locales` 是该语言的浏览器 locale 候选（已归一化成小写 + 下划线），交给前台判断
 * 「访客偏好的是不是这一种」：这张表只在语言定义里写一份，前台不另抄。
 *
 * @return array<int,array{code:string,name:string,url:string,current:bool,exists:bool,prefix:string,edit:string,locales:string[]}>
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
        $url = null;

        if ($post_id > 0) {
            if ($post['path'] === '') {
                $queried = get_post($post_id);

                $url = $queried instanceof WP_Post ? iro_i18n_translation_permalink($queried) : (string) home_url('/');
            } else {
                // 该语言下还没有版本时指向原文：路由层会把访客带回去，而不是给 404
                $target = iro_i18n_translation_post($post['path'], $code, [$post['type']])
                    ?? iro_i18n_base_post($post['path'], [$post['type']]);

                $url = $target instanceof WP_Post ? iro_i18n_translation_permalink($target) : (string) home_url('/');
            }
        }

        if ($url === null && (is_front_page() || is_home())) {
            $url = iro_i18n_prefix_url(home_url('/'), $code);
        }

        if ($url === null && (is_category() || is_tag() || is_tax())) {
            $term = get_queried_object();

            if ($term instanceof WP_Term) {
                $link = get_term_link($term);

                if (!is_wp_error($link)) {
                    $url = iro_i18n_prefix_url((string) $link, $code);
                }
            }
        }

        if ($url === null && is_author()) {
            $url = iro_i18n_prefix_url((string) get_author_posts_url((int) get_queried_object_id()), $code);
        }

        if ($url === null && is_search()) {
            $url = iro_i18n_prefix_url((string) get_search_link(), $code);
        }

        if ($url === null) {
            $request = iro_i18n_stripped_request();

            $url = iro_i18n_prefix_url($request === '' ? home_url('/') : home_url('/' . $request . '/'), $code);
        }

        $links[] = [
            'code'    => $code,
            'name'    => iro_i18n_language_name($code),
            'url'     => $url,
            'current' => $code === $current,
            'exists'  => $post['path'] === '' || isset($map[$code]),
            'prefix'  => iro_i18n_prefix($code),
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
 * 把前缀请求的目标写回查询变量
 *
 * 保留其余查询变量（其它插件在更早的钩子上加的），只清掉会被解析成
 * 别的对象的路径类变量，否则 `/tw/foo/` 会同时带着「找页面 foo」的意图。
 */
function iro_i18n_set_route_target(WP $wp, WP_Post $post): void
{
    foreach (['pagename', 'name', 'attachment', 'category_name', 'tag', 'error', 'p', 'page_id'] as $key) {
        unset($wp->query_vars[$key]);
    }

    if ($post->post_type === 'page') {
        $wp->query_vars['page_id']   = $post->ID;
        $wp->query_vars['post_type'] = 'page';

        return;
    }

    $wp->query_vars['p']         = $post->ID;
    $wp->query_vars['post_type'] = $post->post_type;
    $wp->query_vars['name']      = $post->post_name;
}

/**
 * 非默认语言的版本必须活在自己的前缀下
 *
 * 少了这一条，译文链接要么指向无前缀的原文路径、要么被 canonical 抹掉前缀，
 * 译者的版本等于公开不到前台。
 *
 * 第二个参数按 `WP_Post` 接：`get_permalink()` 在 link-template.php 里把文章对象
 * 传给这两个过滤器（不是 ID），声明成 int 会在列表页一渲染就抛 TypeError。
 * 但过滤器的第二个参数名义上是「文章 ID 或对象」，其它调用方可能给 ID，所以两种都收。
 *
 * @param string          $url
 * @param WP_Post|int     $post
 */
function iro_i18n_filter_post_link(string $url, $post): string
{
    if ($url === '') {
        return $url;
    }

    $post = get_post($post);

    if (!$post instanceof WP_Post) {
        return $url;
    }

    $code = iro_i18n_post_language($post->ID);

    return iro_i18n_is_default_language($code) ? $url : iro_i18n_prefix_url($url, $code);
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

if (iro_i18n_enabled()) {
    /**
     * 语言前缀生效时的解析
     *
     * 走 `parse_request` 而不是 `pre_get_posts`：路由需要直接设定查询对象，
     * 只改查询参数会被 canonical 再改回来。
     *
     * 前缀请求分三类处理：
     * - 语言首页：清空查询变量，交给主页查询；
     * - 单篇内容：按 `_iro_i18n_path` + 语言定位，写回 `p`/`page_id`；
     * - 归档等其它结构：把请求路径还原成无前缀形态后**重跑一次核心解析**，
     *   直接复用 WordPress 自己的 rewrite 规则，而不是在这里重造一套分类/标签/作者路由。
     */
    add_action('parse_request', function (WP $wp): void {
        /**
         * 语言前缀与语言分类法查询变量都是前台概念。后台列表的语言筛选正是靠
         * `?iro_lang=` 参数工作，若在这里一起拦掉，筛选链接会被 302 打回首页。
         */
        if (!iro_is_frontend()) {
            return;
        }

        // 订阅源与 sitemap/robots 走自己的路由，不参与语言前缀
        $request_path = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');

        if (strpos($request_path, 'feed') !== false) {
            return;
        }

        // 语言分类法只是标记载体，不该有归档页；带查询变量的访问一律收敛回首页
        if (isset($wp->query_vars[IRO_I18N_LANGUAGE_TAXONOMY]) && $wp->query_vars[IRO_I18N_LANGUAGE_TAXONOMY] !== '') {
            wp_safe_redirect(home_url('/'), 302);
            exit;
        }

        $prefix = iro_i18n_request_prefix();

        if ($prefix === '') {
            return;
        }

        $code = iro_i18n_current_language();
        $rest = iro_i18n_stripped_request();

        // 语言首页：交给主页查询，语言链接自行拼前缀
        if ($rest === '') {
            $wp->query_vars = [];

            return;
        }

        /**
         * 单篇内容优先按真实别名命中：副本别名带语言后缀（`foo-en-us`），
         * 只有别名对不上时才退化到「组基准路径 + 语言」。
         *
         * 从末段往前逐段试：固定链接可能带分类基础名（`share/post-name`），
         * 而 `post_name` 只是末段——整条路径当别名查不到，末段单独才查得到。
         */
        $segments = array_values(array_filter(explode('/', $rest), static fn(string $part): bool => $part !== ''));
        $slug     = null;

        for ($index = count($segments) - 1; $index >= 0; $index--) {
            foreach (
                get_posts([
                    'name'             => (string) $segments[$index],
                    'post_type'        => iro_i18n_supported_post_types(),
                    'post_status'      => ['publish'],
                    'posts_per_page'   => 5,
                    'suppress_filters' => false,
                    'no_found_rows'    => true,
                ]) as $found
            ) {
                // 只认本语言已发布的版本：草稿副本是给译者预备的落点，不该被访客走到
                if (iro_i18n_post_language($found->ID) !== $code) {
                    continue;
                }

                $found_path = iro_i18n_get_path($found->ID);

                if ($found_path !== '') {
                    $slug = ['path' => $found_path, 'post' => $found];

                    break 2;
                }
            }
        }

        if ($slug !== null) {
            if (iro_i18n_is_default_language($code)) {
                // 默认语言不该有带前缀的对外地址，收敛到无前缀版本
                wp_safe_redirect(iro_i18n_translation_permalink($slug['post']), 302);
                exit;
            }

            /**
             * 别名带语言后缀时，`/en/foo/` 与 `/en/foo-en-us/` 都会落到同一篇；
             * 收敛到该篇的真实地址，避免一个版本有多个可访问入口。
             */
            $canonical = iro_i18n_translation_permalink($slug['post']);

            if ($canonical !== '' && untrailingslashit($canonical) !== untrailingslashit(home_url('/' . iro_i18n_prefix($code) . '/' . $rest . '/'))) {
                wp_safe_redirect($canonical, 302);
                exit;
            }

            iro_i18n_set_route_target($wp, $slug['post']);

            return;
        }

        /**
         * 不是别名命中的内容再看是不是归档；都不是才按关联字段找单篇。
         *
         * 路径长短不一（页面可以有多级），所以按段数从长到短试，每一级都用
         * `_iro_i18n_path` 做一次精确查询。每级还要试「剥掉语言代号后缀」的候选：
         * 副本别名是 `{原文别名}-{代号}`（WordPress 不允许同类型下别名重复），
         * 而组的基准路径不含该后缀。
         *
         * 末段最后试：固定链接形如 `/%category%/%postname%/` 时请求路径含分类基础名
         * （`share/sakurairo_introduction`），而关联字段存的是别名（`sakurairo_introduction`）。
         * 它只在前面都没命中时才采用，且只在真的查到组时才生效——分类归档
         * `category/tutorial` 与别名为 `tutorial` 的文章同理由后续的核心规则裁决，
         * 与 WordPress 自己「先文章规则、后分类规则」的次序一致。
         */
        $post_types = iro_i18n_supported_post_types();
        $candidates = [];

        for ($take = count($segments); $take > 0; $take--) {
            $candidate = implode('/', array_slice($segments, 0, $take));
            $head      = array_slice($segments, 0, $take);
            $last      = (string) end($head);
            $variants  = [];

            foreach (iro_i18n_language_definitions() as $language_code => $definition) {
                $suffix = '-' . $language_code;

                if (substr($last, -strlen($suffix)) !== $suffix) {
                    continue;
                }

                $stripped = substr($last, 0, -strlen($suffix));

                if ($stripped === '') {
                    continue;
                }

                $variant_head            = $head;
                $variant_head[$take - 1] = $stripped;
                $variants[]              = implode('/', $variant_head);
            }

            $candidates = array_merge($candidates, [$candidate], $variants);
        }

        // 早期副本用的是 WordPress 自动接上的 -2 / -3，同样要能匹配回组
        for ($take = count($segments); $take > 0; $take--) {
            $head = array_slice($segments, 0, $take);

            if (!preg_match('/^(.*)-\d+$/', (string) end($head), $matches) || $matches[1] === '') {
                continue;
            }

            $head[$take - 1] = $matches[1];
            $candidates[]    = implode('/', $head);
        }

        // 末段最后试
        $candidates[] = (string) end($segments);

        $path = '';

        foreach (array_unique(array_filter($candidates, static fn(string $item): bool => $item !== '')) as $candidate) {
            if (iro_i18n_get_group($candidate, $post_types) !== []) {
                $path = $candidate;
                break;
            }
        }

        if ($path === '') {
            /**
             * 前缀请求不是单篇内容时，按核心的 rewrite 规则解成归档查询
             *
             * `/en/category/tutorial/` 这类地址核心本来匹配不了（它看到的是
             * `en/category/tutorial`），若不在前缀层还原，分类页切语言就会 404 或落到首页。
             *
             * 这里**只做 `WP::parse_request()` 里那段 rewrite 匹配**，不复用它整个方法：
             * 那个方法读 `$_SERVER['REQUEST_URI']` 与 `$_GET`，要复用得先把三个超全局换掉、
             * 解析完再还原，而它自己还会再次触发 `parse_request` 钩子（递归），
             * 期间任何 `exit`（比如 302）都会让「还原」被跳过，把坏状态留给同一个
             * PHP-FPM 进程里的下一个请求。改写 `$wp->query_vars` 是同样的效果，且没有副作用。
             *
             * 规则表与匹配顺序和核心逐字一致，所以分类、标签、作者、日期、搜索等路由
             * 全部自动跟着语言前缀工作，不需要在这里重造一套。
             */
            if (!get_option('permalink_structure')) {
                return;
            }

            $archive_request = $rest . '/';
            $rules           = $GLOBALS['wp_rewrite']->wp_rewrite_rules();

            if (!is_array($rules) || $rules === []) {
                return;
            }

            foreach ($rules as $pattern => $query) {
                if (!preg_match("#^$pattern#", $archive_request, $matches)) {
                    continue;
                }

                // 与核心一致的占位符替换
                $query = preg_replace("!^.+\?!", '', $query);
                $query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

                parse_str($query, $perma_query_vars);

                if ($perma_query_vars === []) {
                    continue;
                }

                // 别名与分类名要按当前语言的实际值过滤（这里只做路由，不做权限判定）
                $wp->query_vars = $perma_query_vars;

                // 带上原始查询串，否则 `?paged=`、`?s=` 之类会被丢掉
                foreach ($_GET as $key => $value) {
                    if (isset($wp->public_query_vars) && in_array($key, $wp->public_query_vars, true)) {
                        $wp->query_vars[$key] = $value;
                    }
                }

                return;
            }

            return;
        }

        // 非默认语言优先取它自己的版本；取不到则落到原文
        $target = iro_i18n_is_default_language($code)
            ? iro_i18n_base_post($path)
            : (iro_i18n_translation_post($path, $code) ?? iro_i18n_base_post($path));

        if (!$target instanceof WP_Post) {
            return;
        }

        /**
         * 默认语言不保留带前缀的对外地址：`/zh-cn/foo/` 这类地址理论上不该存在，
         * 但访客可能从别处带过来，一律 302 收敛到无前缀地址。
         * 用 302 而不是 301——译文发布之后同一个地址就该能打开。
         */
        if (iro_i18n_is_default_language($code)) {
            wp_safe_redirect(iro_i18n_translation_permalink($target), 302);
            exit;
        }

        // 服务的版本必须与请求的语言一致，否则 302 到它的正确地址
        if (iro_i18n_post_language($target->ID) === $code) {
            iro_i18n_set_route_target($wp, $target);

            return;
        }

        wp_safe_redirect(iro_i18n_translation_permalink($target), 302);
        exit;
    }, 5);

    /**
     * 访客答过语言选择（cookie）时，把他送到该语言的同一页
     *
     * 只认 cookie，不看 `Accept-Language`：爬虫不会带这个 cookie，因此不会被跳转，
     * 也就没有「按语言给不同结果」的嫌疑；没答过询问的访客也不会被搬走——他先看到
     * 当前语言的页面，再由前台问要不要换。
     *
     * 只搬无前缀地址。带前缀的地址是访客这一次明确的导航意图（切换器也是先写 cookie
     * 再跳到目标地址），按 cookie 改掉它会让分享出去的译文链接失效。
     *
     * 单篇只在该语言真的有版本时才过去：没有版本时带前缀的地址会被
     * `iro_i18n_parse_request()` 收敛回原文，一来一回就是死循环。
     */
    add_action('template_redirect', function (): void {
        if (iro_i18n_request_prefix() !== '') {
            return;
        }

        $cookie = isset($_COOKIE[IRO_I18N_LANGUAGE_COOKIE])
            ? strtolower(trim((string) wp_unslash($_COOKIE[IRO_I18N_LANGUAGE_COOKIE])))
            : '';

        // 没答过、答的不是启用语言、或者地址已经就是那一种，都没有可搬的
        if (!in_array($cookie, iro_i18n_languages(), true) || $cookie === iro_i18n_current_language()) {
            return;
        }

        if (is_singular()) {
            $post = get_queried_object();
            $path = $post instanceof WP_Post ? iro_i18n_get_path($post->ID) : '';

            if ($path === '') {
                return;
            }

            $target = iro_i18n_translation_post($path, $cookie, [$post->post_type]);

            if (!$target instanceof WP_Post) {
                return;
            }

            wp_safe_redirect(iro_i18n_translation_permalink($target), 302);
            exit;
        }

        // 首页、归档、搜索：把当前路径原样搬到该语言的前缀下
        if (!is_home() && !is_front_page() && !is_archive() && !is_search()) {
            return;
        }

        // 订阅源与 sitemap 走自己的路由，别把它们的地址改掉
        $request = trim((string) ($GLOBALS['wp']->request ?? ''), '/');

        if (strpos($request, 'feed') !== false || strpos($request, 'sitemap') !== false) {
            return;
        }

        // 无前缀请求的 `$wp->request` 不含前缀，可以直接原样搬（带前缀的上面已经放行）
        $url   = home_url('/' . iro_i18n_prefix($cookie) . '/' . ($request === '' ? '' : $request . '/'));
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

        wp_safe_redirect($query === '' ? $url : $url . '?' . $query, 302);
        exit;
    }, 1);

    /**
     * 无前缀地址上是别的语言的版本时，收敛回它自己的前缀地址
     *
     * 前缀路由只接管带前缀的请求，无前缀的那条由核心按别名解析，而副本别名是 WordPress
     * 为避开重名接上的 `-2`、`-3`（新副本是 `foo-en-us`）——于是 `/share/foo-2/` 会把英文版
     * 原样服务在默认语言的地址下：语言隔离失效，同一篇内容还多出一个入口。
     *
     * 在查询之后按结果判断：主查询已经把这篇查出来了，不必再去逐段猜别名，
     * 顺带覆盖 `?p=123` 这种没有别名的形态。
     *
     * 判定只看内容与地址，不看 cookie、也不看 `Accept-Language`，所以爬虫拿到的页面
     * 与真实访客一致，不存在「按语言给不同结果」的嫌疑。
     */
    add_action('template_redirect', function (): void {
        if (iro_i18n_request_prefix() !== '' || !is_singular() || is_feed()) {
            return;
        }

        $post = get_queried_object();

        if (!$post instanceof WP_Post) {
            return;
        }

        // 默认语言的原文就该住在这里
        if (iro_i18n_is_default_language(iro_i18n_post_language($post->ID))) {
            return;
        }

        wp_safe_redirect(iro_i18n_translation_permalink($post), 302);
        exit;
    }, 1);

    /**
     * 让 `determine_locale()` 在前台返回本次请求的语言
     *
     * determine_locale 走用户语言时我们排它后面，把它在前台的决定覆盖掉。语言判定只读
     * `$_SERVER['REQUEST_URI']` 与 cookie，不依赖查询变量，因此在解析请求之前就能得出结果；
     * 早于 `wp_loaded` 的代码（以及按需加载语言包的 `_load_textdomain_just_in_time()`）都要靠这条。
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
     * 让 WordPress 的 locale 跟随本次请求的语言
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
     * 放在 `wp_loaded`：它早于 `wp()` 触发的 `parse_request`，也早于任何模板输出；
     * 而 `init`（用户语言那一步）已经跑完，顺序上正好由我们收口。
     */
    add_action('wp_loaded', function (): void {
        if (!iro_is_frontend()) {
            return;
        }

        $locale = iro_i18n_locale(iro_i18n_current_language());

        // `$wp_locale` 与核心语言包都是按站点语言建的，请求语言就是站点语言时没有可重建的东西
        if ($locale === '' || $locale === iro_i18n_site_locale()) {
            return;
        }

        load_default_textdomain($locale);

        $GLOBALS['wp_locale'] = new WP_Locale();

        // 文本域是惰性加载的，卸掉让它们按新 locale 重新载一次
        foreach (iro_i18n_text_domains() as $domain) {
            unload_textdomain((string) $domain, true);
            get_translations_for_domain((string) $domain);
        }
    }, 5);

    add_filter('post_link', 'iro_i18n_filter_post_link', 10, 2);
    add_filter('page_link', 'iro_i18n_filter_post_link', 10, 2);

    /**
     * 前缀请求由这里负责解析，canonical 再插手会把前缀当成错误路径抹掉
     */
    add_filter('redirect_canonical', function (string|false $redirect): string|false {
        return iro_i18n_request_prefix() === '' ? $redirect : false;
    }, 10, 1);

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
     * 主题自己的 SEO 只输出 description / og / JSON-LD，规范的 canonical 一直是核心
     * 在 `wp_head` 打的，所以这里也不接管。
     *
     * 只作 SEO 用途。前台切换器**不**消费这些 link：swup 只替换列出的容器，
     * 换页后 head 里的 alternate 还是上一篇的旧值，靠它跳转会跳错；切换器用的是
     * 页面配置里的 `langs`。
     */
    add_action('wp_head', function (): void {
        if (!is_singular() && !is_home() && !is_front_page() && !is_archive()) {
            return;
        }

        $links = iro_i18n_language_links();

        if (count($links) < 2) {
            return;
        }

        foreach ($links as $link) {
            // 该语言下没有真实版本就不标注：指向原文的 hreflang 会被判定为错误的语言信号
            if (!$link['exists']) {
                continue;
            }
?>
            <link rel="alternate" hreflang="<?= esc_attr($link['code']) ?>" href="<?= esc_url($link['url']) ?>">
        <?php
        }
        ?>
        <link rel="alternate" hreflang="x-default" href="<?= esc_url(iro_i18n_prefix_url(home_url('/'), iro_i18n_default_language())) ?>">
<?php
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
