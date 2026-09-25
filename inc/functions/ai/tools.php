<?php

use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

// WordPress 7.0 以下没有内置 AI Client，此时不注册 shortcode，避免调用不存在的函数
if (!function_exists('iro_ai_register_provider')) {
    return;
}

function iro_ai_generate(string $prompt, array $args = []): string|WP_Error
{
    $args = array_merge(
        [
            'model' => iro_ai_default_model(), // 模型名称
            'system' => '', // 系统指令
            'temperature' => null, // 采样温度
            'max_tokens' => null, // 最大token数
            'timeout' => null, // 超时时间
        ],
        $args
    );

    iro_ai_register_provider();

    $builder = wp_ai_client_prompt($prompt)
        ->using_model_preference([iro_ai_provider_id(), $args['model']]);

    if ($args['system'] !== '') {
        $builder->using_system_instruction($args['system']);
    }
    if ($args['temperature'] !== null) {
        $builder->using_temperature((float) $args['temperature']);
    }
    if ($args['max_tokens'] !== null) {
        $builder->using_max_tokens((int) $args['max_tokens']);
    }
    if ($args['timeout'] !== null) {
        $builder->using_request_options(RequestOptions::fromArray(['timeout' => (float) $args['timeout']]));
    }

    return $builder->generate_text();
}

function iro_ai_post_content(int $post_id, int $limit = 12000): string|WP_Error
{
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error(
            'iro_ai_post_not_found',
            __('文章不存在。', 'sakurairo')
        );
    }

    // rendered 内容
    $rendered = apply_filters('the_content', $post->post_content);

    // 去标签、去多余空白，截断到上限
    // $text = trim(wp_strip_all_tags($rendered));
    $text = trim($rendered);
    if ($text === '') {
        return new WP_Error(
            'iro_ai_empty_content',
            __('文章内容为空，无法处理。', 'sakurairo')
        );
    }

    return mb_substr($text, 0, $limit);
}

/**
 * 工具：提取文章关键词
 */
function iro_ai_post_keywords(int $post_id, array $args = []): string|WP_Error
{
    $text = iro_ai_post_content($post_id);
    if (is_wp_error($text)) {
        return $text;
    }

    return iro_ai_generate(
        __('提取下面内容的 5 个关键词，用“,”分隔，不要编号、不要引号、不要换行、不要任何解释或前缀：', 'sakurairo')
            . "\n\n" . $text,
        $args + ['temperature' => 0.2]
    );
}

/**
 * 工具：生成文章摘要
 */
function iro_ai_post_summary(int $post_id, array $args = []): string|WP_Error
{
    $text = iro_ai_post_content($post_id);
    if (is_wp_error($text)) {
        return $text;
    }

    return iro_ai_generate(
        __('请总结下面文章的主要内容，要求适用于页面的meta description标签，长度在100字以内，使用与文章内容相同的语言，直接陈述文章内容，不要换行、不要引号、不要 markdown、不要任何解释，只输出总结内容本身。：', 'sakurairo') . "\n\n" . $text,
        $args + [
            'system' => __('你是一位专业的SEO编辑，输出简洁、准确、可直接用于 meta description 的纯文本。', 'sakurairo'),
        ]
    );
}
