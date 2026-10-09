<?php

/**
 * 语言代号迁移：把早期版本的简化代号换成统一代号
 *
 * 早期代号直接拿中文/日文简称当标识（`cn`/`tw`/`en`/`jp`），既和官方 AI 插件的
 * 语言枚举对不上，也让「URL 前缀」和「内容标识」共用同一个值。现在统一代号是
 * `zh-cn`/`zh-tw`/`en-us`/`ja`，简化形态只留在 URL 前缀上。
 *
 * 一次性动作，用 option 做哨兵。故意用 `$wpdb` 直改而不是 `wp_update_term`：
 * 迁移必须在分类法注册之前完成，否则 `init` 上的建术语会先按新代号建出一批
 * 重复术语，旧术语连着的文章就全悬空了。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 旧代号 => 新代号
 *
 * 键就是各语言现在的 URL 前缀——早期版本拿前缀当代号用，所以这份映射
 * 直接从语言定义推导，新增语言时不必再维护第二张表。
 *
 * @return array<string,string>
 */
function iro_i18n_legacy_code_map(): array
{
    $map = [];

    foreach (iro_i18n_language_definitions() as $code => $definition) {
        $map[(string) $definition['prefix']] = (string) $code;
    }

    foreach ((array) apply_filters('iro_i18n_legacy_code_map', []) as $legacy => $code) {
        if (iro_i18n_is_language((string) $code)) {
            $map[(string) $legacy] = (string) $code;
        }
    }

    return $map;
}

/**
 * 把 option 里的语言数组按映射表改写
 *
 * @param array<string,string> $map 旧代号 => 新代号
 */
function iro_i18n_migrate_option_codes(array $map): void
{
    $options = get_option('iro_options');

    if (!is_array($options)) {
        return;
    }

    $changed = false;

    foreach (['iro_i18n_languages', 'iro_i18n_default_language'] as $key) {
        if (!isset($options[$key])) {
            continue;
        }

        $value = $options[$key];

        if (is_array($value)) {
            $mapped = array_map(
                static fn($code): string => $map[(string) $code] ?? (string) $code,
                $value
            );

            if ($mapped !== $value) {
                $options[$key] = array_values($mapped);
                $changed       = true;
            }

            continue;
        }

        $code = (string) $value;

        if ($code !== '' && isset($map[$code])) {
            $options[$key] = $map[$code];
            $changed       = true;
        }
    }

    if (!$changed) {
        return;
    }

    update_option('iro_options', $options);

    // iro_opt() 读的是启动时载入的全局副本，这里同步刷新，
    // 免得迁移后的首个请求仍按旧代号判定
    $GLOBALS['iro_options'] = $options;
}

/**
 * 改术语别名与显示名
 *
 * 别名是语言判定的可见载体；文章与术语的关系挂在 term_id 上，
 * 因此改别名不影响任何已有归属。
 *
 * @param array<string,string> $map 旧代号 => 新代号
 */
function iro_i18n_migrate_term_codes(array $map): void
{
    global $wpdb;

    foreach ($map as $old => $new) {
        $term = get_term_by('slug', (string) $old, IRO_I18N_LANGUAGE_TAXONOMY);

        if (!$term instanceof WP_Term) {
            continue;
        }

        $wpdb->update(
            $wpdb->terms,
            ['slug' => $new, 'name' => iro_i18n_language_name($new)],
            ['term_id' => $term->term_id],
            ['%s', '%s'],
            ['%d']
        );

        update_term_meta($term->term_id, IRO_I18N_LANGUAGE_TERM_META, $new);
        clean_term_cache($term->term_id, IRO_I18N_LANGUAGE_TAXONOMY);
    }
}

/**
 * 改文章语言标记
 *
 * 语言缺失的历史内容不补写：那类内容按「未标记 = 默认语言」处理，
 * 补写是批量补齐的职责。
 *
 * @param array<string,string> $map 旧代号 => 新代号
 */
function iro_i18n_migrate_post_codes(array $map): void
{
    global $wpdb;

    foreach ($map as $old => $new) {
        $wpdb->update(
            $wpdb->postmeta,
            ['meta_value' => $new],
            ['meta_key' => IRO_I18N_POST_LANG_META, 'meta_value' => (string) $old],
            ['%s'],
            ['%s', '%s']
        );
    }
}

/**
 * 跑一次代号迁移；已迁移过则直接返回
 */
function iro_i18n_maybe_migrate_codes(): void
{
    if (!iro_i18n_enabled() || get_option('iro_i18n_codes_migrated') === '1') {
        return;
    }

    $map = iro_i18n_legacy_code_map();

    iro_i18n_migrate_option_codes($map);
    iro_i18n_migrate_term_codes($map);
    iro_i18n_migrate_post_codes($map);

    update_option('iro_i18n_codes_migrated', '1');
}

if (iro_i18n_enabled()) {
    // 必须早于 taxonomy.php 的 init(10) 建术语，否则会先按新代号建出重复术语
    add_action('init', 'iro_i18n_maybe_migrate_codes', 1);
}
