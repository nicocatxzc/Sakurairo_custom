<?php

if (!defined('ABSPATH')) {
    exit;
}

// WordPress 7.0 以下没有内置 AI Client，接口不可用
if (!function_exists('iro_ai_selftest_report')) {
    return;
}

// 自检：系统信息 + 接口可用性 + 可用模型
function iro_ai_rest_selftest(): WP_REST_Response
{
    return rest_ensure_response(iro_ai_selftest_report());
}

// 可用模型（?refresh=1 强制刷新缓存）
function iro_ai_rest_models(WP_REST_Request $request): WP_REST_Response
{
    return rest_ensure_response([
        'models' => iro_ai_models_list((bool) $request->get_param('refresh')),
    ]);
}

// 测试对话，messages 为 OpenAI 风格消息数组
function iro_ai_rest_chat(WP_REST_Request $request)
{
    @set_time_limit(180);

    $messages = $request->get_param('messages');
    if (!is_array($messages) || $messages === []) {
        return new WP_Error(
            'iro_ai_chat_empty',
            __('对话内容不能为空。', 'sakurairo'),
            ['status' => 400]
        );
    }

    $model = sanitize_text_field((string) $request->get_param('model'));
    $started = microtime(true);

    $reply = iro_ai_chat(
        $messages,
        array_merge(
            $model !== '' ? ['model' => $model] : [],
            ['timeout' => 120]
        )
    );

    if (is_wp_error($reply)) {
        return $reply;
    }

    return rest_ensure_response([
        'reply' => $reply,
        'model' => $model !== '' ? $model : iro_ai_default_model(),
        'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
    ]);
}

