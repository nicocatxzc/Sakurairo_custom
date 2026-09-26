<?php

use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

// WordPress 7.0 以下没有内置 AI Client，AI 工具不可用
if (!function_exists('iro_ai_register_provider')) {
    return;
}

// 对话历史转成 SDK 的消息对象，用于 with_history()
function iro_ai_history_messages(array $history): array
{
    $messages = [];

    foreach ($history as $turn) {
        $content = trim((string) ($turn['content'] ?? ''));
        if ($content === '') {
            continue;
        }

        $part = new MessagePart($content);
        $messages[] = ($turn['role'] ?? '') === 'assistant'
            ? new ModelMessage([$part])
            : new UserMessage([$part]);
    }

    return $messages;
}

function iro_ai_generate(string $prompt, array $args = []): string|WP_Error
{
    $args = array_merge(
        [
            'model' => iro_ai_default_model(), // 模型名称
            'system' => '', // 系统指令
            'history' => [], // 对话历史：[['role' => 'user'|'assistant', 'content' => '...'], ...]
            'temperature' => null, // 采样温度
            'max_tokens' => null, // 最大token数
            'timeout' => null, // 超时时间
        ],
        $args
    );

    iro_ai_register_provider();

    $builder = wp_ai_client_prompt($prompt);

    if (!empty($args['history'])) {
        $builder->with_history(...iro_ai_history_messages((array) $args['history']));
    }

    if ($args['model'] !== '') {
        $builder->using_model_preference([iro_ai_provider_id(), $args['model']]);
    }

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

/*
 * 工具：多轮对话
 * $messages 为 OpenAI 风格的消息数组：[['role' => 'user'|'assistant', 'content' => '...'], ...]
 * 最后一条必须是用户消息（本次提问），它之前的都作为对话历史。
 */
function iro_ai_chat(array $messages, array $args = []): string|WP_Error
{
    $turns = [];

    // 只保留最近 20 条并截断单条长度，避免把超长内容整段丢给接口
    foreach (array_slice(array_values($messages), -20) as $message) {
        $content = trim((string) ($message['content'] ?? ''));
        if ($content === '') {
            continue;
        }

        $turns[] = [
            'role' => ($message['role'] ?? '') === 'assistant' ? 'assistant' : 'user',
            'content' => mb_substr($content, 0, 8000),
        ];
    }

    // 提问必须是最后一条，否则模型只会续写
    if (($turns[count($turns) - 1]['role'] ?? '') !== 'user') {
        return new WP_Error(
            'iro_ai_chat_invalid_last_message',
            __('最后一条消息必须是用户的提问。', 'sakurairo'),
            ['status' => 400]
        );
    }

    $prompt = array_pop($turns)['content'];

    return iro_ai_generate($prompt, array_merge($args, ['history' => $turns]));
}

// 转成纯文本：去短代码、去块编辑器的 HTML 注释、去标签、解码实体、压缩空白
function iro_ai_plain_content(string $content): string
{
    $content = strip_shortcodes($content);
    $content = (string) preg_replace('/<!--.*?-->/s', '', $content);
    $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $content = (string) preg_replace('/\s+/u', ' ', $content);

    return trim($content);
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

    // 渲染 blocks/shortcode 后取纯文本，HTML 与块标记对模型没有价值
    $text = iro_ai_plain_content(apply_filters('the_content', $post->post_content));

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

/**
 * 工具：生成文章标题
 */
function iro_ai_post_title(int $post_id, array $args = []): string|WP_Error
{
    $text = iro_ai_post_content($post_id);
    if (is_wp_error($text)) {
        return $text;
    }

    return iro_ai_generate(
        __('为下面的内容拟一个标题，要求使用与内容相同的语言，概括准确、长度在 30 字以内，不要换行、不要引号、不要 markdown、不要任何解释或前缀，只输出标题本身：', 'sakurairo') . "\n\n" . $text,
        $args + [
            'system' => __('你是一位专业的编辑，输出的标题简洁、准确，不使用夸张的标题党措辞。', 'sakurairo'),
        ]
    );
}
