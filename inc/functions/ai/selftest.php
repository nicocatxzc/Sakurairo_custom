<?php

use WordPress\AiClient\AiClient;

// 系统与配置信息
function iro_ai_system_info(): array
{
    $registry = AiClient::defaultRegistry();
    $provider = iro_ai_provider_id();

    return [
        'php' => PHP_VERSION,
        'wordpress' => get_bloginfo('version'),
        'theme' => wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version'),
        'sdk' => AiClient::VERSION,
        'supports_ai' => function_exists('wp_supports_ai') && wp_supports_ai(),
        'provider' => $provider,
        'provider_registered' => $registry->hasProvider($provider),
        'provider_configured' => $registry->isProviderConfigured($provider),
        'key_source' => iro_ai_api_key_source(),
        'api_base' => iro_ai_api_base(),
        'default_model' => iro_ai_default_model(),
        'checked_at' => current_time('mysql'),
    ];
}

// 接口可用性：探测 {api_base}/models，成功时顺带刷新模型缓存
function iro_ai_endpoint_check(): array
{
    $url = iro_ai_api_base() . '/models';
    $started = microtime(true);

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => ['Authorization' => 'Bearer ' . iro_ai_api_key()],
    ]);

    $result = [
        'url' => $url,
        'reachable' => false,
        'http_code' => 0,
        'latency' => (int) round((microtime(true) - $started) * 1000),
        'models' => 0,
        'error' => null,
    ];

    if (is_wp_error($response)) {
        $result['error'] = $response->get_error_message();
        return $result;
    }

    $result['http_code'] = (int) wp_remote_retrieve_response_code($response);
    if ($result['http_code'] !== 200) {
        $result['error'] = sprintf(__('接口返回 HTTP %d', 'sakurairo'), $result['http_code']);
        return $result;
    }

    $models = iro_ai_parse_models((string) wp_remote_retrieve_body($response));
    iro_ai_cache_models($models);

    $result['reachable'] = true;
    $result['models'] = count($models);

    return $result;
}

// 可用模型列表，供设置面板展示与测试对话切换
function iro_ai_models_list(bool $refresh = false): array
{
    $models = [];

    foreach (iro_ai_models($refresh) as $id => $metadata) {
        $architecture = (array) ($metadata['architecture'] ?? []);

        $models[] = [
            'id' => (string) $id,
            'name' => (string) ($metadata['name'] ?? $id),
            'context_length' => (int) ($metadata['context_length'] ?? 0),
            'input_modalities' => array_values((array) ($architecture['input_modalities'] ?? ['text'])),
            'output_modalities' => array_values((array) ($architecture['output_modalities'] ?? ['text'])),
        ];
    }

    return $models;
}

// 自检汇总：接口探测成功时已刷新模型缓存，这里直接取用
function iro_ai_selftest_report(): array
{
    return [
        'system' => iro_ai_system_info(),
        'endpoint' => iro_ai_endpoint_check(),
        'models' => iro_ai_models_list(),
    ];
}
