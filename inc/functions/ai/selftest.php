<?php
use WordPress\AiClient\AiClient;

/*
|--------------------------------------------------------------------------
| 自检报告：/wp-admin/admin.php?iro_act=test
|
| 可选参数：
| - tool=chat|summary|keywords|translate  只跑其中一项
| - model=xxx                             指定模型
| - refresh=1                             强制刷新模型缓存
|--------------------------------------------------------------------------
*/

function iro_ai_self_test(): string
{
    @set_time_limit(300);

    // operator 动作在 init 之前执行，这里先注册一次，保证状态栏与调用都准确
    iro_ai_register_provider();

    $requested_tool = sanitize_key(wp_unslash($_GET['tool'] ?? ''));
    $model = sanitize_text_field(wp_unslash($_GET['model'] ?? '')) ?: iro_ai_default_model();
    $timeout = absint(wp_unslash($_GET['timeout'] ?? '')) ?: 90;
    $registry = AiClient::defaultRegistry();

    $lines = [
        '==== AI 自检报告 ====',
        '时间             : ' . current_time('mysql'),
        'WordPress        : ' . get_bloginfo('version'),
        '主题             : ' . wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version'),
        'AI Client SDK    : ' . AiClient::VERSION,
        'wp_supports_ai() : ' . var_export(wp_supports_ai(), true),
        'Provider         : ' . iro_ai_provider_id(),
        '注册状态         : ' . ($registry->hasProvider(iro_ai_provider_id()) ? '已注册' : '未注册')
            . ' / ' . ($registry->isProviderConfigured(iro_ai_provider_id()) ? '已配置凭证' : '未配置凭证'),
        '接口地址         : ' . iro_ai_api_base(),
        '默认模型         : ' . iro_ai_default_model(),
        '本次使用模型     : ' . $model,
        '请求超时         : ' . $timeout . ' 秒（可用 &timeout=秒 调整）',
        '',
    ];

    $models = iro_ai_models(!empty($_GET['refresh']));
    $lines[] = sprintf('==== 可用模型（%d 个，缓存 6 小时，加 &refresh=1 强制刷新）====', count($models));
    foreach ($models as $model_id => $metadata) {
        $architecture = (array) ($metadata['architecture'] ?? []);
        $lines[] = sprintf(
            ' - %-18s ctx %-8s in[%s] out[%s]',
            $model_id,
            $metadata['context_length'] ?? '-',
            implode('/', (array) ($architecture['input_modalities'] ?? ['text'])),
            implode('/', (array) ($architecture['output_modalities'] ?? ['text']))
        );
    }
    $lines[] = '';

    $tools = [
        'chat' => static fn(): string|WP_Error => iro_ai_generate('Hello', ['model' => $model, 'timeout' => $timeout]),
        'summary' => static fn(): string|WP_Error => iro_ai_summarize_text(iro_ai_self_test_sample(), ['model' => $model, 'timeout' => $timeout]),
        'keywords' => static fn(): string|WP_Error => iro_ai_keywords(iro_ai_self_test_sample(), 5, ['model' => $model, 'timeout' => $timeout]),
        'translate' => static fn(): string|WP_Error => iro_ai_translate(iro_ai_self_test_sample(), 'en', ['model' => $model, 'timeout' => $timeout]),
        // 直接渲染 shortcode，验证前台输出链路
        'render' => static function () use ($model, $timeout): string|WP_Error {
            $sample = iro_ai_self_test_sample();
            return do_shortcode('[ai_summary text="' . $sample . '"]')
                . "\n" . do_shortcode('[ai_keywords text="' . $sample . '" limit="3"]');
        },
    ];

    if ($requested_tool !== '' && !isset($tools[$requested_tool])) {
        $lines[] = '未知的 tool 参数：' . $requested_tool . '（可用：' . implode(', ', array_keys($tools)) . '）';
    } else {
        $lines[] = '==== 调用测试 ====';
        foreach ($tools as $name => $callback) {
            if ($requested_tool !== '' && $requested_tool !== $name) {
                continue;
            }
            $start = microtime(true);
            $result = $callback();
            $lines[] = sprintf(
                'iro_ai_%-10s (%.2fs): %s',
                $name,
                microtime(true) - $start,
                iro_ai_self_test_format($result)
            );
        }
        $lines[] = '';
    }

    $lines[] = '==== 短代码 ====';
    foreach (['ai_summary', 'ai_keywords', 'ai_translate'] as $shortcode) {
        $lines[] = sprintf('[%s] : %s', $shortcode, shortcode_exists($shortcode) ? '已注册' : '未注册');
    }
    $lines[] = '';
    $lines[] = '==== 用法 ====';
    $lines[] = '自检      : ' . admin_url('admin.php?iro_act=test');
    $lines[] = '单项自检  : ' . admin_url('admin.php?iro_act=test&tool=summary');
    $lines[] = '指定模型  : ' . admin_url('admin.php?iro_act=test&tool=chat&model=GLM-5.3-Flash');
    $lines[] = '强制刷新  : ' . admin_url('admin.php?iro_act=test&refresh=1');
    $lines[] = '模板调用  : iro_ai_summarize_post($post_id) / iro_ai_keywords($text) / iro_ai_translate($text, "en")';

    return implode("\n", $lines) . "\n";
}

function iro_ai_self_test_sample(): string
{
    return 'Sakurairo 是一款基于 WordPress 的主题，支持深色模式、PJAX 无刷新跳转与丰富的页面动效，并提供可视化设置面板。';
}

function iro_ai_self_test_format(string|WP_Error $result): string
{
    if (is_wp_error($result)) {
        return 'WP_Error [' . $result->get_error_code() . '] ' . $result->get_error_message();
    }
    return trim($result) === '' ? '(空回复)' : trim($result);
}
