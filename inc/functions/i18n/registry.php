<?php

/**
 * 多语言注册表：语言集合、默认语言、locale 与路由前缀映射
 *
 * 一个语言有两个标识，职责不同，不要混用：
 * - **代号**：全站统一标识，用在设置项、post meta、术语别名、后台展示。
 *   形如 `zh-cn`、`en-us`，与官方 AI 插件（ai/content-translation）的语言枚举同构；
 * - **前缀**：只出现在 URL 上，取简化形态 `cn`、`en`、`jp`、`tw`。
 *
 * 两者在 `iro_i18n_language_definitions()` 里成对声明，代码里不要再手写字面量。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 内置语言定义
 *
 * `prefix` 只用于 URL 前缀，其余场景一律用数组键（代号）。
 * `date_format` 是 `wp_date()` 的格式串，见 `iro_i18n_date_format()`。
 *
 * @return array<string,array{prefix:string,name:string,locale:string,locales:string[],date_format:string}>
 */
function iro_i18n_language_definitions(): array
{
    return [
        'zh-cn' => [
            'prefix'      => 'cn',
            'name'        => '简中',
            'locale'      => 'zh_CN',
            'locales'     => ['zh_cn', 'zh-hans', 'zh_hans'],
            'date_format' => 'Y年m月d日',
        ],
        'zh-tw' => [
            'prefix'      => 'tw',
            'name'        => '繁中',
            'locale'      => 'zh_TW',
            'locales'     => ['zh_tw', 'zh-hant', 'zh_hant'],
            'date_format' => 'Y年m月d日',
        ],
        'en-us' => [
            'prefix'      => 'en',
            'name'        => '英语',
            'locale'      => 'en_US',
            'locales'     => ['en_us', 'en_gb', 'en_ca', 'en_au', 'en_nz'],
            'date_format' => 'M j, Y',
        ],
        'ja' => [
            'prefix'      => 'jp',
            'name'        => '日语',
            'locale'      => 'ja',
            'locales'     => ['ja', 'ja_jp'],
            'date_format' => 'Y年m月d日',
        ],
    ];
}

function iro_i18n_enabled(): bool
{
    return (bool) iro_opt('iro_i18n_switch', false);
}

/**
 * 站点启用的语言代号，始终过滤回内置定义
 *
 * @return string[]
 */
function iro_i18n_languages(): array
{
    $configured = iro_opt('iro_i18n_languages', []);

    if (!is_array($configured)) {
        return [];
    }

    $definitions = iro_i18n_language_definitions();

    return array_values(array_filter(
        array_map('strval', $configured),
        static fn(string $code): bool => isset($definitions[$code])
    ));
}

/**
 * 代号对应的 URL 前缀，未知代号原样返回
 *
 * 未知代号意味着有过滤器新增了语言却没给前缀，那种情况下用代号兜底比返回空串安全。
 */
function iro_i18n_prefix(string $code): string
{
    return iro_i18n_language_definitions()[$code]['prefix'] ?? $code;
}

/**
 * 把一个 locale 写法归并到语言代号，对不上返回空串
 *
 * 逐个比对候选写法而不是全等：WordPress 的 locale 形态不统一
 * （`zh_CN`、`ja`、`en_GB` 都合法），且简繁还有 Hans/Hant 变体。
 * 同时用于站点语言与浏览器 Accept-Language。
 */
function iro_i18n_code_from_locale(string $locale): string
{
    $locale = strtolower(str_replace('-', '_', trim($locale)));

    if ($locale === '') {
        return '';
    }

    foreach (iro_i18n_language_definitions() as $code => $definition) {
        $candidates = array_map(
            static fn(string $item): string => strtolower(str_replace('-', '_', $item)),
            array_merge([(string) $definition['locale']], (array) $definition['locales'])
        );

        if (in_array($locale, $candidates, true)) {
            return (string) $code;
        }

        // 同一个语言的地域变体（如 en_GB 之于 en_US）按主语言归并
        foreach ($candidates as $candidate) {
            if (strpos($candidate, '_') !== false && strpos($locale . '_', $candidate . '_') === 0) {
                return (string) $code;
            }
        }
    }

    return '';
}

/**
 * 站点基础语言（设置 → 常规 → 站点语言）
 *
 * 取 option 而不是 `get_locale()`：后者会被 `switch_to_locale()` 改写，
 * 而 `inc/theme_init/translation.php` 在 `init` 上把 locale 切成**当前登录用户**
 * 的语言。默认语言是「原文用哪种语言写的」这个站点事实，不该随谁登录而改变——
 * 否则换个人登录就会把全部原文重新标成另一种语言。
 */
function iro_i18n_site_locale(): string
{
    $locale = (string) get_option('WPLANG', '');

    return $locale === '' ? 'en_US' : $locale;
}

/** 访客语言选择的 cookie 名；前台切换器与服务端都认这一个 */
const IRO_I18N_LANGUAGE_COOKIE = 'iro-language';

/**
 * 访客语言：显式选择的 cookie 优先，其次浏览器偏好
 *
 * 服务端是唯一权威——前台切换器只负责把选择写进 cookie，并跳到服务端为这一语言
 * 算好的地址，不由前端拼 URL，也不在前端判定语言。cookie 的值是统一语言代号
 * （`zh-cn`／`en-us`），不是 URL 前缀。
 *
 * 没有 cookie 时解析 `Accept-Language` 的 q 值排序，逐个归并到语言代号，
 * 第一个命中的即为偏好语言。
 */
function iro_i18n_visitor_language(): string
{
    $cookie = isset($_COOKIE[IRO_I18N_LANGUAGE_COOKIE])
        ? strtolower(trim((string) wp_unslash($_COOKIE[IRO_I18N_LANGUAGE_COOKIE])))
        : '';

    if (in_array($cookie, iro_i18n_languages(), true)) {
        return $cookie;
    }

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
 * 默认语言：无前缀路径所代表的语言
 * 优先级：显式设置项 → 站点语言 → 启用语言的第一个。
 */
function iro_i18n_default_language(): string
{
    $languages = iro_i18n_languages();
    $fallback  = $languages === [] ? 'zh-cn' : $languages[0];

    foreach ([
        (string) apply_filters('iro_i18n_default_language', ''),
        (string) iro_opt('iro_i18n_default_language', ''),
    ] as $explicit) {
        if ($explicit !== '' && in_array($explicit, $languages, true)) {
            return $explicit;
        }
    }

    // 站点语言归并到启用语言：先看同语言变体，再看同主语言的其它变体
    // （站点是 `en_GB`、只启用了 `en-us` 时不该丢掉英语）
    $site = iro_i18n_site_locale();
    $code = iro_i18n_code_from_locale($site);

    if ($code !== '' && in_array($code, $languages, true)) {
        return $code;
    }

    $primary = strtok($site, '-_');

    if ($primary === false || $primary === '') {
        return $fallback;
    }

    foreach ($languages as $candidate) {
        $candidate_primary = strtok((string) $candidate, '-_');

        if ($candidate_primary !== false && strcasecmp($candidate_primary, (string) $primary) === 0) {
            return $candidate;
        }
    }

    return $fallback;
}

/**
 * 语言代号对应的 locale，供 hreflang 与 `<html lang>` 使用
 *
 * 取不到时退回站点语言而不是 `get_locale()`：后者可能已被切到登录用户的语言。
 */
function iro_i18n_locale(string $code): string
{
    return iro_i18n_language_definitions()[$code]['locale'] ?? iro_i18n_site_locale();
}

/**
 * 当前语言的日期展示格式
 *
 * 日期格式**不能**由译文片段拼装。`wp_date()`/`date_i18n()` 把格式串里每一个字符都当格式符
 * 解释，而英文译文 `Year`、`Month`、`Day` 里的 `e`、`a`、`r`、`o`、`t`、`h` 会被就地展开成
 * 时区名、上下午、RFC 日期等，整串日期直接变成乱码。所以每种语言各写一条完整格式。
 *
 * 模块未启用时跟随 WordPress 自己的 locale（含登录用户语言），此时主题文案由站点语言包
 * 决定，日期就该跟它一致，而不是跟着访问者的浏览器。
 */
function iro_i18n_date_format(): string
{
    $definitions = iro_i18n_language_definitions();
    $code        = iro_i18n_enabled() ? iro_i18n_current_language() : iro_i18n_code_from_locale(get_locale());

    return (string) ($definitions[$code]['date_format'] ?? $definitions['zh-cn']['date_format'] ?? 'Y-m-d');
}

/**
 * 语言代号对应的显示名
 */
function iro_i18n_language_name(string $code): string
{
    return iro_i18n_language_definitions()[$code]['name'] ?? $code;
}

/**
 * 启用语言是否为默认语言
 */
function iro_i18n_is_default_language(string $code): bool
{
    return $code === iro_i18n_default_language();
}

