<?php

/**
 * 用户内容（UGC）翻译
 *
 * 导航菜单标题这类内容由站长自己写，主题发布时不可能预知，因此进不了语言包，
 * 改由设置项 `iro_i18n_user_content`（一段 JSON）承载。模板里用 `iro__()` 取值：
 *
 * 1. 先让 WordPress 的 `__()` 说话：主题语言包里已有译文就用它——那是随主题发布、
 *    经过校对的文案，不该被设置项顶掉；
 * 2. 语言包没有，再查设置项里本次请求语言的条目；
 * 3. 还是没有，就把原文登记回设置项（其余语言留空位）等译者和 AI 插件补，
 *    本次渲染照旧输出原文。
 *
 * 设置项的形态是以原文为键、语言代号为子键的对象，与 `.po` 的 msgid/msgstr 同构：
 *
 *     {
 *         "首页": { "en-us": "Home", "ja": "ホーム" },
 *         "关于": { "en-us": "About", "ja": "" }
 *     }
 *
 * 空串就是「已登记、还没有译文」，取值时一律落回原文——设置项被误改也不该把前台文案清空。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 设置项的解析结果
 *
 * `readable` 为假表示站长手写的 JSON 解析不了。此时既不取值也不回写：原文只是「看起来缺键」，
 * 拿本模块合并出来的结果覆盖回去，会把他写的内容整个毁掉。
 *
 * @return array{readable:bool,data:array<string,mixed>}
 */
function iro_i18n_user_content_state(): array
{
    static $state = null;

    if ($state !== null) {
        return $state;
    }

    $state = ['readable' => true, 'data' => []];
    $raw   = iro_opt('iro_i18n_user_content', '');

    if (!is_string($raw) || trim($raw) === '') {
        return $state;
    }

    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        $state['readable'] = false;

        return $state;
    }

    $state['data'] = $decoded;

    return $state;
}

/**
 * 原文在指定语言下的译文，没有译文或译文为空时返回空串
 *
 * 语言代号省略时取本次请求的语言。
 */
function iro_i18n_user_content_translation(string $text, ?string $code = null): string
{
    $entry = iro_i18n_user_content_state()['data'][$text] ?? null;

    if (!is_array($entry)) {
        return '';
    }

    $value = $entry[$code ?? iro_i18n_current_language()] ?? '';

    return is_scalar($value) ? trim((string) $value) : '';
}

/**
 * 需要为之补录译文的语言代号
 *
 * 默认语言的原文就是设置项的键本身，不给自己留空位：留了反而会被当成一条待填的译文。
 *
 * @return string[]
 */
function iro_i18n_user_content_targets(): array
{
    static $targets = null;

    if ($targets !== null) {
        return $targets;
    }

    $targets = array_values(array_filter(
        iro_i18n_languages(),
        static fn(string $code): bool => !iro_i18n_is_default_language($code)
    ));

    return $targets;
}

/**
 * 本次请求登记待翻译的原文，按原文去重
 *
 * 引用返回：登记散落在模板渲染途中（导航栏每一项都要调一次 `iro__()`），
 * 而写入要攒到请求结束，一次请求最多写一次设置项。
 *
 * @return array<string,true>
 */
function &iro_i18n_user_content_pending(): array
{
    static $pending = [];

    return $pending;
}

/**
 * 把没有译文的原文登记回设置项
 *
 * 原文来自站长自己填的菜单标题，不含访客输入，因此不需要权限判定；反过来也意味着
 * **不要**把文章标题这类会无限增长的动态值交给 `iro__()`，否则设置项会被撑爆。
 */
function iro_i18n_collect_user_content(string $text): void
{
    if ($text === '' || !iro_i18n_enabled() || !iro_i18n_is_frontend()) {
        return;
    }

    $state = iro_i18n_user_content_state();

    // 设置项的 JSON 解析不了就不回写，理由见 iro_i18n_user_content_state()
    if (!$state['readable']) {
        return;
    }

    if (!apply_filters('iro_i18n_collect_user_content', true)) {
        return;
    }

    $entry = $state['data'][$text] ?? null;

    // 已经登记过、每种语言也都留了位置，请求结束时就没有可写的了，连队都不必排
    if (is_array($entry) && array_diff(iro_i18n_user_content_targets(), array_keys($entry)) === []) {
        return;
    }

    $pending        = &iro_i18n_user_content_pending();
    $pending[$text] = true;
}

/**
 * 把本次请求登记到的原文合并进设置项
 *
 * 合并前重新读一次 option：渲染期间可能有别的写入，拿请求开始时的快照写回会把它们抹掉。
 */
function iro_i18n_flush_user_content(): void
{
    $pending = &iro_i18n_user_content_pending();

    if ($pending === []) {
        return;
    }

    $texts   = array_keys($pending);
    $pending = [];

    $options = get_option('iro_options');
    $raw     = is_array($options) ? ($options['iro_i18n_user_content'] ?? '') : '';

    if (!is_string($raw) || trim($raw) === '') {
        $data = [];
    } else {
        $data = json_decode($raw, true);

        // 手写的 JSON 解析不了就不动它，理由见 iro_i18n_user_content_state()
        if (!is_array($data)) {
            return;
        }
    }

    $targets = iro_i18n_user_content_targets();
    $changed = false;

    foreach ($texts as $text) {
        $entry = $data[$text] ?? [];

        // 手写成了标量，不动它
        if (!is_array($entry)) {
            continue;
        }

        foreach ($targets as $code) {
            if (array_key_exists($code, $entry)) {
                continue;
            }

            $entry[$code] = '';
            $changed      = true;
        }

        $data[$text] = $entry;
    }

    if (!$changed) {
        return;
    }

    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // 原文里混进非法 UTF-8 之类会编码失败，此时写入等于清空设置项
    if (!is_string($encoded)) {
        return;
    }

    iro_opt_update('iro_i18n_user_content', $encoded);
}

/**
 * 把设置项里的译文接进 `gettext`
 *
 * 挂在主题自己的文案域上：UGC 与主题文案混在同一个域里，正是「语言包优先」这条规则成立的前提。
 */
function iro_i18n_filter_user_content_gettext(string $translation, string $text, string $domain): string
{
    if (!iro_i18n_enabled() || !iro_i18n_is_frontend()) {
        return $translation;
    }

    if (!isset(iro_i18n_user_content_state()['data'][$text]) || !in_array($domain, iro_i18n_text_domains(), true)) {
        return $translation;
    }

    // 语言包已经给出译文就不动它
    if ($translation !== $text) {
        return $translation;
    }

    $value = iro_i18n_user_content_translation($text);

    return $value === '' ? $translation : $value;
}

/**
 * 取用户内容在本次请求语言下的译文
 *
 * 前台模板里凡站长自己写的内容（导航菜单标题等）都走这个函数而不是 `__()`。
 * 后台仍用 `__()`：那边读的是设置项原文，跟着访客语言变会让站长没法编辑。
 *
 * 模块未启用、或语言包与设置项都没有译文时返回原文，因此模板可以无条件调用它。
 */
function iro__(string $text): string
{
    $translated = translate($text, 'sakurairo');

    // 拿到空串按没有译文处理，别把前台文案清空
    if ($translated === '' || $translated === $text) {
        iro_i18n_collect_user_content($text);

        return $text;
    }

    return $translated;
}

if (iro_i18n_enabled()) {
    add_filter('gettext', 'iro_i18n_filter_user_content_gettext', 10, 3);
    add_action('shutdown', 'iro_i18n_flush_user_content');
}
