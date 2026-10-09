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

/** 访客语言选择的 cookie 名；前台切换器与服务端都认这一个 */
const IRO_I18N_LANGUAGE_COOKIE = 'iro-language';

/** cookie 有效期，跟浏览器能接受的常见上限（约 400 天）对齐 */
const IRO_I18N_LANGUAGE_COOKIE_TTL = 34560000;

/**
 * 本模块只对前台生效
 *
 * 后台的语言列、语言筛选、翻译面板是管理功能，读写的是「内容被标成了哪种语言」，
 * 不该跟着访客偏好走；后台的界面语言仍由 WordPress 的用户语言设置决定。
 *
 * `determine_locale` 可能在很早就被调用（插件加载阶段就在 `is_admin()` 定义之前），
 * 因此这里先确认函数存在。
 */
function iro_i18n_is_frontend(): bool
{
    if (!function_exists('is_admin')) {
        return false;
    }

    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return false;
    }

    return !(defined('REST_REQUEST') && REST_REQUEST);
}

/**
 * cookie 里的语言，未设置或对不上启用集合时返回空串
 *
 * 值就是统一语言代号（`zh-cn`／`en-us`），因此前端写进去的必须是代号，
 * 不能是 URL 前缀。
 */
function iro_i18n_cookie_language(): string
{
    if (!isset($_COOKIE[IRO_I18N_LANGUAGE_COOKIE])) {
        return '';
    }

    $value = strtolower(trim((string) wp_unslash($_COOKIE[IRO_I18N_LANGUAGE_COOKIE])));

    return in_array($value, iro_i18n_languages(), true) ? $value : '';
}

/**
 * 浏览器偏好里第一个能对上内置语言的语言
 *
 * 只在访客没有 cookie 时生效：解析 `Accept-Language` 的 q 值排序，
 * 逐个归并到语言代号，第一个命中的即为偏好语言。
 */
function iro_i18n_browser_language(): string
{
    $header = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');

    if ($header === '') {
        return '';
    }

    $candidates = [];

    foreach (explode(',', $header) as $position => $part) {
        $pieces = explode(';', trim($part));
        $locale = trim((string) array_shift($pieces));
        $weight = 1.0;

        foreach ($pieces as $piece) {
            $piece = trim($piece);

            if (stripos($piece, 'q=') === 0) {
                $weight = (float) substr($piece, 2);
            }
        }

        // 权重相同就按声明顺序，用下标做次级排序键
        $candidates[] = ['locale' => $locale, 'weight' => $weight, 'order' => $position];
    }

    usort($candidates, static fn(array $a, array $b): int => $b['weight'] <=> $a['weight'] ?: $a['order'] <=> $b['order']);

    foreach ($candidates as $candidate) {
        if ($candidate['weight'] <= 0.0) {
            continue;
        }

        $code = iro_i18n_code_from_locale((string) $candidate['locale']);

        if ($code !== '' && in_array($code, iro_i18n_languages(), true)) {
            return $code;
        }
    }

    return '';
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

    if ($segment === '' || !in_array($segment, iro_i18n_prefixes(), true)) {
        return $cached;
    }

    // 归一化成定义里声明的写法，URL 大小写不同不影响解析
    foreach (iro_i18n_language_definitions() as $definition) {
        if (strtolower((string) $definition['prefix']) === $segment) {
            $cached = (string) $definition['prefix'];
            break;
        }
    }

    return $cached;
}

/**
 * 本次请求的语言代号
 *
 * 优先级：URL 前缀 > 访客偏好 cookie > 站点默认语言。
 * 前缀排在最前，因为它是访客这一次明确的导航意图；cookie 只是「上次选过什么」，
 * 不该把已经点进 `/en-us/…` 的人再拽回别处。
 */
function iro_i18n_current_language(): string
{
    $prefix = iro_i18n_request_prefix();

    if ($prefix !== '') {
        $code = iro_i18n_code_by_prefix($prefix);

        if ($code !== '') {
            return $code;
        }
    }

    $visitor = iro_i18n_visitor_language();

    return $visitor !== '' ? $visitor : iro_i18n_default_language();
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
 * 取某语言下当前请求对应的链接，供语言切换器使用
 *
 * 单篇内容的链接由 `post_link` / `page_link` 过滤器带前缀，这里不重复拼，
 * 免得同一段前缀被加两次。
 */
function iro_i18n_translation_url(string $code): string
{
    $current = iro_i18n_current_post();

    if ($current['id'] > 0) {
        if ($current['path'] === '') {
            $post = get_post($current['id']);

            return $post instanceof WP_Post ? iro_i18n_translation_permalink($post) : (string) home_url('/');
        }

        // 该语言下还没有版本时指向原文：路由层会把访客带回去，而不是给 404
        $target = iro_i18n_translation_post($current['path'], $code, [$current['type']])
            ?? iro_i18n_base_post($current['path'], [$current['type']]);

        return $target instanceof WP_Post ? iro_i18n_translation_permalink($target) : (string) home_url('/');
    }

    if (is_front_page() || is_home()) {
        return iro_i18n_prefix_url(home_url('/'), $code);
    }

    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();

        if ($term instanceof WP_Term) {
            $link = get_term_link($term);

            if (!is_wp_error($link)) {
                return iro_i18n_prefix_url((string) $link, $code);
            }
        }
    }

    if (is_author()) {
        return iro_i18n_prefix_url((string) get_author_posts_url((int) get_queried_object_id()), $code);
    }

    if (is_search()) {
        return iro_i18n_prefix_url((string) get_search_link(), $code);
    }

    $request = iro_i18n_stripped_request();

    return $request === ''
        ? iro_i18n_prefix_url(home_url('/'), $code)
        : iro_i18n_prefix_url(home_url('/' . $request . '/'), $code);
}

/**
 * 当前请求的单篇内容与它的基准路径
 *
 * 语言切换器和 hreflang 都要这两样，各自查一次会白跑一遍分组查询。
 *
 * @return array{id:int,path:string,type:string}
 */
function iro_i18n_current_post(): array
{
    static $current = null;

    if ($current !== null) {
        return $current;
    }

    $post_id = is_singular() ? (int) get_queried_object_id() : 0;

    $current = [
        'id'   => $post_id,
        'path' => $post_id > 0 ? iro_i18n_get_path($post_id) : '',
        'type' => $post_id > 0 ? (string) (get_post_type($post_id) ?: 'post') : 'post',
    ];

    return $current;
}

/**
 * 语言切换器数据：每种启用语言一行
 *
 * `exists` 说明该语言下有没有真实版本；为假时链接指向原文，
 * 由路由层把访客带回去，而不是给一个 404。
 *
 * @return array<int,array{code:string,name:string,url:string,current:bool,exists:bool,prefix:string,edit:string}>
 */
function iro_i18n_language_links(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $current = iro_i18n_current_language();
    $post    = iro_i18n_current_post();
    $map     = $post['id'] > 0 ? iro_i18n_group_map($post['id']) : [];
    $links   = [];

    foreach (iro_i18n_languages() as $code) {
        $links[] = [
            'code'    => $code,
            'name'    => iro_i18n_language_name($code),
            'url'     => iro_i18n_translation_url($code),
            'current' => $code === $current,
            'exists'  => $post['path'] === '' || isset($map[$code]),
            'prefix'  => iro_i18n_prefix($code),
            'edit'    => isset($map[$code]) ? (string) get_edit_post_link($map[$code]->ID, 'raw') : '',
        ];
    }

    $cache = $links;

    return $cache;
}

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
function iro_i18n_parse_request(WP $wp): void
{
    /**
     * 语言前缀与语言分类法查询变量都是前台概念。后台列表的语言筛选正是靠
     * `?iro_lang=` 参数工作，若在这里一起拦掉，筛选链接会被 302 打回首页。
     */
    if (!iro_i18n_is_frontend()) {
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
     */
    $slug = iro_i18n_match_slug($rest);

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

    // 不是别名命中的内容再看是不是归档；都不是才按关联字段找单篇
    $path = iro_i18n_match_path($rest, iro_i18n_supported_post_types());

    if ($path === '') {
        iro_i18n_route_archive($wp, $rest);

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
}

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
function iro_i18n_route_archive(WP $wp, string $rest): void
{
    if (!get_option('permalink_structure')) {
        return;
    }

    $request = $rest . '/';
    $rules   = $GLOBALS['wp_rewrite']->wp_rewrite_rules();

    if (!is_array($rules) || $rules === []) {
        return;
    }

    foreach ($rules as $pattern => $query) {
        if (!preg_match("#^$pattern#", $request, $matches)) {
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
 * 把去掉前缀后的请求路径匹配到某个基准路径，匹配不到返回空串
 *
 * 路径长短不一（页面可以有多级），所以按段数从长到短试，
 * 每一级都用 `_iro_i18n_path` 做一次精确查询。
 *
 * 每级还要试一次「剥掉语言代号后缀」的候选：副本别名是 `{原文别名}-{代号}`
 * （WordPress 不允许同类型下别名重复），而组的基准路径不含该后缀。
 *
 * 还要试「末段」：固定链接形如 `/%category%/%postname%/` 时，请求路径含分类基础名
 * （`share/sakurairo_introduction`），而关联字段存的是别名（`sakurairo_introduction`）。
 * 末段候选只在前面都没命中时才试，且只在真的查到组时才采用——
 * 分类归档 `category/tutorial` 与别名为 `tutorial` 的文章同理由后续的核心规则裁决，
 * 与 WordPress 自己「先文章规则、后分类规则」的次序一致。
 *
 * @param string[] $post_types
 */
function iro_i18n_match_path(string $rest, array $post_types): string
{
    $segments = array_values(array_filter(explode('/', $rest), static fn(string $part): bool => $part !== ''));

    $candidates = [];

    for ($take = count($segments); $take > 0; $take--) {
        $candidate  = implode('/', array_slice($segments, 0, $take));
        $candidates = array_merge($candidates, [$candidate], iro_i18n_path_variants($candidate));
    }

    // 末段最后试
    $candidates[] = (string) end($segments);

    foreach (array_unique(array_filter($candidates, static fn(string $item): bool => $item !== '')) as $candidate) {
        if (iro_i18n_get_group($candidate, $post_types) !== []) {
            return $candidate;
        }
    }

    return '';
}

/**
 * 按别名精确找「当前语言下应该服务的那一篇」
 *
 * 关联字段存的是组基准路径，而副本的别名带语言后缀（`foo-en-us`），
 * 于是 `/en/foo-en-us/` 这类地址经 `iro_i18n_match_path()` 只能得到基准路径 `foo`，
 * 再按语言取到的仍是同一篇，调用方的「地址规范化」就会把它重定向到它自己。
 * 这里先按真实别名命中原样返回，规范化只在别名对不上时才出手。
 *
 * 从末段往前逐段试：固定链接可能带分类基础名（`share/post-name`），
 * 而 `post_name` 只是末段——整条路径当别名查不到，末段单独才查得到。
 *
 * @return array{path:string,post:WP_Post}|null
 */
function iro_i18n_match_slug(string $rest): ?array
{
    $segments = array_values(array_filter(explode('/', $rest), static fn(string $part): bool => $part !== ''));
    $language = iro_i18n_current_language();

    for ($index = count($segments) - 1; $index >= 0; $index--) {
        $slug = (string) $segments[$index];

        foreach (get_posts([
            'name'             => $slug,
            'post_type'        => iro_i18n_supported_post_types(),
            'post_status'      => ['publish'],
            'posts_per_page'   => 5,
            'suppress_filters' => false,
            'no_found_rows'    => true,
        ]) as $post) {
            // 只认本语言已发布的版本：草稿副本是给译者预备的落点，不该被访客走到
            if (iro_i18n_post_language($post->ID) !== $language) {
                continue;
            }

            $path = iro_i18n_get_path($post->ID);

            if ($path !== '') {
                return ['path' => $path, 'post' => $post];
            }
        }
    }

    return null;
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
 * 访客偏好语言与当前地址不一致时，把人送到该语言的正确地址
 *
 * 两种情形分别处理：
 *
 * 1. 地址不带前缀：访客偏好不是默认语言时补上前缀，这就是「首次访问按浏览器
 *    偏好识别语言」的落地方式。
 * 2. 地址带前缀、而访客偏好是默认语言：默认语言没有对外前缀，此时必须落回
 *    无前缀地址。少了这一条，前端切换器把 cookie 改成默认语言后重新请求同一个
 *    带前缀地址，服务端仍然按前缀给回那种语言——表现就是「切不回简中」。
 *    判断只用 `iro_i18n_visitor_language()`（cookie → 浏览器偏好），不看当前语言：
 *    当前语言此刻就是由该前缀决定的，拿它比永远相等。
 *
 * 目标是剥掉前缀的路径，不是 `$wp->request` —— 后者原样带着前缀，会重定向到自己。
 * 单篇在这一支里同样放行：无前缀地址就是它的默认语言版本；万一该版本不存在，
 * `iro_i18n_parse_request()` 会接手，因为「前缀必须与版本语言一致」对它不成立。
 *
 * 归档、首页这类地址本身就带前缀也照样成立：前缀已经由 `iro_i18n_route_archive()`
 * 还原成核心查询，所以 `/en/uncategorized/` 是能正常打开的页面。
 */
function iro_i18n_redirect_preferred_language(): void
{
    // 订阅源与 sitemap 走自己的路由，别把它们的地址改掉
    $request = trim((string) ($GLOBALS['wp']->request ?? ''), '/');

    if (strpos($request, 'feed') !== false || strpos($request, 'sitemap') !== false) {
        return;
    }

    $preferred = iro_i18n_visitor_language();
    $prefix    = iro_i18n_request_prefix();

    if ($preferred === '') {
        return;
    }

    if ($preferred === iro_i18n_default_language()) {
        if ($prefix === '') {
            return;
        }

        $rest = iro_i18n_stripped_request();
        $url  = home_url('/' . ($rest === '' ? '' : $rest . '/'));
    } else {
        if ($prefix !== '' || is_singular()) {
            return;
        }

        $target = iro_i18n_prefix($preferred);

        if ($target === '') {
            return;
        }

        $url = home_url('/' . $target . '/' . ($request === '' ? '' : $request . '/'));
    }

    // 保留查询串，否则 `?s=` 之类的参数会被丢掉
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

    wp_safe_redirect($query === '' ? $url : $url . '?' . $query, 302);
    exit;
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
 */
function iro_i18n_switch_locale(): void
{
    if (!iro_i18n_is_frontend()) {
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
}

/**
 * 让 `determine_locale()` 在前台返回本次请求的语言
 *
 * 早于 `wp_loaded` 的代码（以及按需加载语言包的 `_load_textdomain_just_in_time()`）都要靠这条。
 * 后台不动：那边由 WordPress 的用户语言设置负责。
 */
function iro_i18n_filter_determine_locale(string $locale): string
{
    if (!iro_i18n_is_frontend()) {
        return $locale;
    }

    return iro_i18n_locale(iro_i18n_current_language());
}

/**
 * 让 `get_locale()` 在前台返回本次请求的语言
 *
 * 与 `determine_locale` 问的不是同一件事：那条决定「取哪份 .mo」，这条是「这个请求算哪种语言」，
 * `number_format_i18n()`、日期与数字格式化都会问它。走过滤器而不是改 `$GLOBALS['locale']`，
 * 与 `WP_Locale_Switcher` 的做法一致。
 */
function iro_i18n_filter_locale(string $locale): string
{
    if (!iro_i18n_is_frontend()) {
        return $locale;
    }

    return iro_i18n_locale(iro_i18n_current_language());
}

/**
 * 前缀请求由这里负责解析，canonical 再插手会把前缀当成错误路径抹掉
 */
function iro_i18n_disable_canonical(string|false $redirect): string|false
{
    return iro_i18n_request_prefix() === '' ? $redirect : false;
}

/**
 * `<html lang>` 跟随请求语言
 *
 * 只改语言标记，不切 WordPress 的 locale：译文是人工写的独立文章，
 * 主题文案的翻译由站点自身的语言包负责，两者不该互相牵动。
 */
function iro_i18n_language_attributes(string $output): string
{
    $locale = iro_i18n_locale(iro_i18n_current_language());

    $replaced = preg_replace('/\blang=(["\']).*?\1/i', 'lang="' . esc_attr($locale) . '"', $output, 1);

    return $replaced === null ? $output : $replaced;
}

/**
 * hreflang 注解
 *
 * 主题自己的 SEO 只输出 description / og / JSON-LD，规范的 canonical 一直是核心
 * 在 `wp_head` 打的，所以这里也不接管。
 *
 * 只作 SEO 用途。前台切换器**不**消费这些 link：swup 只替换列出的容器，
 * 换页后 head 里的 alternate 还是上一篇的旧值，靠它跳转会跳错。
 * 地址一律由服务端在 302 里给出。
 */
function iro_i18n_output_head_links(): void
{
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
}

/**
 * 未翻译副本不进搜索引擎
 */
function iro_i18n_robots(array $robots): array
{
    if (is_singular() && iro_i18n_is_skeleton((int) get_queried_object_id())) {
        unset($robots['index'], $robots['follow']);
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }

    return $robots;
}

if (iro_i18n_enabled()) {
    add_action('parse_request', 'iro_i18n_parse_request', 5);
    // 查询已经跑完，此时才判得出「这是个正常归档还是 404」，据此决定要不要按偏好跳转
    add_action('template_redirect', 'iro_i18n_redirect_preferred_language', 1);

    /**
     * determine_locale 走用户语言时我们排它后面，把它在前台的决定覆盖掉。
     * 语言判定只读 `$_SERVER['REQUEST_URI']` 与 cookie，不依赖查询变量，
     * 因此在这里（解析请求之前）就能得出结果。
     */
    add_filter('determine_locale', 'iro_i18n_filter_determine_locale', 25);
    add_filter('locale', 'iro_i18n_filter_locale', 25);
    /**
     * 重建 `$wp_locale` 与核心语言包。放在 `wp_loaded`：它早于 `wp()` 触发的 `parse_request`，
     * 也早于任何模板输出；而 `init`（用户语言那一步）已经跑完，顺序上正好由我们收口。
     */
    add_action('wp_loaded', 'iro_i18n_switch_locale', 5);

    add_filter('post_link', 'iro_i18n_filter_post_link', 10, 2);
    add_filter('page_link', 'iro_i18n_filter_post_link', 10, 2);
    add_filter('redirect_canonical', 'iro_i18n_disable_canonical', 10, 1);
    add_filter('language_attributes', 'iro_i18n_language_attributes', 10, 1);
    add_action('wp_head', 'iro_i18n_output_head_links', 2);
    add_filter('wp_robots', 'iro_i18n_robots');
}
