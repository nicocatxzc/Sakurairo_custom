<?php

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

if (!defined('ABSPATH')) {
    exit;
}

/*
 * 内置 AI Client 是 WordPress 7.0 引入的，而主题声明兼容 WordPress 6.0+。
 * 这里的 return 位于所有类声明之前，旧版本下类声明不会被解析，避免「父类不存在」的致命错误。
 */
if (!class_exists(AiClient::class)) {
    return;
}

// 必须与 createProviderMetadata() 里的 id 一致，Connectors 的凭证槽位由它推导
function iro_ai_provider_id(): string
{
    return 'sakurairo';
}

function iro_ai_api_base(): string
{
    return rtrim((string) iro_opt('ai_api_base', 'https://developer.amd.com.cn/radeon/api/v1'), '/');
}

function iro_ai_default_model(): string
{
    $model = trim((string) iro_opt('ai_model', ''));
    return $model !== '' ? $model : (string) array_key_first(iro_ai_models());
}

// 连接器的 API Key 固定在这三处槽位，官方 AI 插件与 Connectors 页只扫这三处
function iro_ai_connector_setting_name(): string
{
    return 'connectors_ai_' . str_replace('-', '_', iro_ai_provider_id()) . '_api_key';
}

function iro_ai_connector_constant_name(): string
{
    return strtoupper(str_replace('-', '_', iro_ai_provider_id())) . '_API_KEY';
}

function iro_ai_api_key(): string
{
    $api_key = trim((string) iro_opt('ai_api_key', ''));
    if ($api_key !== '') {
        return $api_key;
    }

    $constant = iro_ai_connector_constant_name();
    if (defined($constant) && is_string(constant($constant))) {
        return trim(constant($constant));
    }

    $env = getenv($constant);
    if (is_string($env)) {
        return trim($env);
    }

    return trim((string) get_option(iro_ai_connector_setting_name(), ''));
}

/*
 * 主题设置里填的 Key 要镜像到连接器槽位：Connectors 页的配置状态、官方 AI 插件的
 * 凭证门禁（has_ai_credentials）以及 core 的 Key 注入都只认那个选项。
 * 这里不能走 iro_opt()，$GLOBALS['iro_options'] 是请求开始时载入的旧值。
 */
add_action('updated_option', function ($option, $old_value, $value) {
    if ($option !== 'iro_options' || !is_array($value)) {
        return;
    }

    $api_key = trim((string) ($value['ai_api_key'] ?? ''));
    if ($api_key !== '' && get_option(iro_ai_connector_setting_name()) !== $api_key) {
        update_option(iro_ai_connector_setting_name(), $api_key);
    }
}, 10, 3);

// 兜底：设置项是这次改动之前填的（或直接从数据库改的）时，补写一次槽位
function iro_ai_sync_connector_key(): void
{
    $api_key = trim((string) iro_opt('ai_api_key', ''));
    if ($api_key !== '' && get_option(iro_ai_connector_setting_name()) !== $api_key) {
        update_option(iro_ai_connector_setting_name(), $api_key);
    }
}


//可用模型：从服务的 /models 接口发现并缓存
function iro_ai_fetch_models(): array
{
    $response = wp_remote_get(
        iro_ai_api_base() . '/models',
        [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . iro_ai_api_key()],
        ]
    );

    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
        return [];
    }

    $models = [];
    foreach (json_decode(wp_remote_retrieve_body($response), true)['data'] ?? [] as $model) {
        if (!empty($model['id'])) {
            $models[(string) $model['id']] = $model;
        }
    }
    return $models;
}

function iro_ai_models(bool $refresh = false): array
{
    if ($refresh) {
        delete_transient('iro_ai_models_cache');
    }

    $models = get_transient('iro_ai_models_cache');
    if (!is_array($models)) {
        $models = iro_ai_fetch_models();
        // 请求失败时短缓存，避免每次调用都去打接口
        set_transient('iro_ai_models_cache', $models, $models ? 6 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS);
    }

    // 服务端没列出（或拉取失败）时，保证设置里选定的模型始终可用
    $configured = trim((string) iro_opt('ai_model', ''));
    if ($configured !== '') {
        $models += [
            $configured => [
                'id' => $configured,
                'name' => $configured,
            ],
        ];
    }

    return $models;
}

/*
|--------------------------------------------------------------------------
| Provider
|--------------------------------------------------------------------------
*/

class Sakurairo_AI_Provider extends AbstractApiProvider
{
    protected static function baseUrl(): string
    {
        return iro_ai_api_base();
    }

    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            iro_ai_provider_id(),
            'Sakurairo AI',
            ProviderTypeEnum::cloud(),
            null,
            RequestAuthenticationMethod::apiKey(),
            __('Sakurairo主题AI设置提供', 'sakurairo')
        );
    }

    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        return new Sakurairo_AI_Model($modelMetadata, $providerMetadata);
    }

    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new Sakurairo_AI_Availability();
    }

    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new Sakurairo_AI_Model_Metadata();
    }
}

// model
class Sakurairo_AI_Model extends AbstractOpenAiCompatibleTextGenerationModel
{
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        // chat/completions 为相对路径，RequestOptions 承载 wp_ai_client_prompt() 注入的超时等设置
        return new Request(
            $method,
            Sakurairo_AI_Provider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }
}


// Model Metadata：把 /models 返回的模型逐个登记为 WordPress 可用的模型
class Sakurairo_AI_Model_Metadata implements ModelMetadataDirectoryInterface
{
    public function listModelMetadata(): array
    {
        $metadata = [];
        foreach (iro_ai_models() as $model_id => $model) {
            $metadata[] = $this->createMetadata((string) $model_id, (array) $model);
        }
        return $metadata;
    }

    public function hasModelMetadata(string $modelId): bool
    {
        return isset(iro_ai_models()[$modelId]);
    }

    public function getModelMetadata(string $modelId): ModelMetadata
    {
        $models = iro_ai_models();
        if (!isset($models[$modelId])) {
            throw new InvalidArgumentException('Unknown model: ' . $modelId);
        }
        return $this->createMetadata($modelId, (array) $models[$modelId]);
    }

    private function createMetadata(string $model_id, array $model): ModelMetadata
    {
        /*
         * 候选模型筛选会校验Prompt需要的选项与模型声明的支持选项，
         * 所以能力与选项都要按服务端返回的元数据登记，否则 findModelsMetadataForSupport() 会认为没有可用模型。
         */
        $parameters = array_values(array_filter((array) ($model['supported_parameters'] ?? []), 'is_string'));

        $options = [
            new SupportedOption(OptionEnum::inputModalities(), [$this->mapModalities($model['architecture']['input_modalities'] ?? ['text'])]),
            new SupportedOption(OptionEnum::outputModalities(), [$this->mapModalities($model['architecture']['output_modalities'] ?? ['text'])]),
            new SupportedOption(OptionEnum::outputMimeType(), array_merge(['text/plain'], empty($model['json_output']) ? [] : ['application/json'])),
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::customOptions()),
        ];

        $supported = [
            'temperature' => OptionEnum::temperature(),
            'top_p' => OptionEnum::topP(),
            'max_tokens' => OptionEnum::maxTokens(),
            'n' => OptionEnum::candidateCount(),
            'stop' => OptionEnum::stopSequences(),
            'presence_penalty' => OptionEnum::presencePenalty(),
            'frequency_penalty' => OptionEnum::frequencyPenalty(),
            'logprobs' => OptionEnum::logprobs(),
            'tools' => OptionEnum::functionDeclarations(),
        ];

        foreach ($supported as $parameter => $option) {
            if (in_array($parameter, $parameters, true)) {
                $options[] = new SupportedOption($option);
            }
        }

        if (!empty($model['structured_outputs'])) {
            $options[] = new SupportedOption(OptionEnum::outputSchema());
        }

        return new ModelMetadata(
            $model_id,
            (string) ($model['name'] ?? $model_id),
            [
                CapabilityEnum::textGeneration(),
                CapabilityEnum::chatHistory(),
            ],
            $options
        );
    }

    private function mapModalities(array $modalities): array
    {
        $map = [
            'text' => ModalityEnum::text(),
            'image' => ModalityEnum::image(),
            'audio' => ModalityEnum::audio(),
            'video' => ModalityEnum::video(),
            'document' => ModalityEnum::document(),
        ];

        $mapped = array_values(array_filter(array_map(static fn($modality) => $map[$modality] ?? null, $modalities)));

        return $mapped ?: [ModalityEnum::text()];
    }
}

//Provider Availability
class Sakurairo_AI_Availability implements ProviderAvailabilityInterface
{
    public function isConfigured(): bool
    {
        // 未配置凭证的 Provider 不会进入候选模型列表
        return iro_ai_api_key() !== '';
    }
}

// 向 WordPress 注册 Provider 与凭证
function iro_ai_register_provider(): void
{
    iro_ai_sync_connector_key();

    $registry = AiClient::defaultRegistry();
    $provider = Sakurairo_AI_Provider::class;

    if (!$registry->hasProvider($provider)) {
        $registry->registerProvider($provider);
    }

    // 未设置 API Key 时保留 PHP AI Client 自带的常量/环境变量兜底逻辑
    if (iro_ai_api_key() !== '') {
        $registry->setProviderRequestAuthentication(
            $provider,
            new ApiKeyRequestAuthentication(iro_ai_api_key())
        );
    }
}

add_action('init', 'iro_ai_register_provider', 10);
