<script setup>
import { computed, onMounted, ref } from "vue";
import { ElMessage } from "element-plus";
import { Refresh } from "@element-plus/icons-vue";
import { aiApi } from "../api";

const loading = ref(false);
const report = ref(null);

const KEY_SOURCE_LABELS = {
    theme_option: "主题设置",
    constant: "常量",
    env: "环境变量",
    connector_option: "Connectors 选项",
    none: "未配置",
};

const MODALITY_LABELS = {
    text: "文本",
    image: "图片",
    audio: "音频",
    video: "视频",
    file: "文件",
};

const systemRows = computed(() => {
    const system = report.value?.system;

    if (!system) {
        return [];
    }

    return [
        { label: "PHP", value: system.php },
        { label: "WordPress", value: system.wordpress },
        { label: "主题", value: system.theme },
        { label: "AI Client SDK", value: system.sdk },
        { label: "wp_supports_ai()", value: system.supports_ai ? "是" : "否" },
        { label: "Provider", value: system.provider },
        {
            label: "注册状态",
            value: system.provider_registered ? "已注册" : "未注册",
        },
        {
            label: "凭证状态",
            value: system.provider_configured ? "已配置" : "未配置",
        },
        {
            label: "凭证来源",
            value: KEY_SOURCE_LABELS[system.key_source] ?? system.key_source,
        },
        { label: "接口地址", value: system.api_base },
        { label: "默认模型", value: system.default_model || "未设置" },
        { label: "检测时间", value: system.checked_at },
    ];
});

const endpoint = computed(() => report.value?.endpoint ?? null);
const models = computed(() => report.value?.models ?? []);

function modalities(list) {
    return (list ?? []).map((item) => MODALITY_LABELS[item] ?? item).join(" / ");
}

async function load() {
    loading.value = true;

    try {
        report.value = await aiApi.selftest();
    } catch (error) {
        ElMessage.error(error.message);
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div v-loading="loading" class="iro-ai-system">
        <div class="iro-ai-system__bar">
            <el-text tag="b">系统信息</el-text>
            <el-button type="primary" :icon="Refresh" @click="load">
                重新检测
            </el-button>
        </div>

        <div class="iro-ai-system__body">
            <el-descriptions :column="2" border>
                <el-descriptions-item
                    v-for="row in systemRows"
                    :key="row.label"
                    :label="row.label"
                >
                    {{ row.value }}
                </el-descriptions-item>
            </el-descriptions>

            <el-alert
                v-if="endpoint"
                :type="endpoint.reachable ? 'success' : 'error'"
                :closable="false"
                show-icon
                class="iro-ai-system__endpoint"
            >
                <template #title>
                    {{
                        endpoint.reachable
                            ? `接口可用（HTTP ${endpoint.http_code}，${endpoint.latency} ms，${endpoint.models} 个模型）`
                            : "接口不可用"
                    }}
                </template>
                <template #default>
                    <span>{{ endpoint.url }}</span>
                    <span v-if="endpoint.error"> — {{ endpoint.error }}</span>
                </template>
            </el-alert>

            <el-text tag="b" class="iro-ai-system__title">
                可用模型（{{ models.length }}）
            </el-text>

            <el-table :data="models" size="small" border max-height="360">
                <el-table-column prop="id" label="模型 ID" min-width="180" />
                <el-table-column prop="context_length" label="上下文" width="110" />
                <el-table-column label="输入模态" width="130">
                    <template #default="{ row }">
                        {{ modalities(row.input_modalities) }}
                    </template>
                </el-table-column>
                <el-table-column label="输出模态" width="130">
                    <template #default="{ row }">
                        {{ modalities(row.output_modalities) }}
                    </template>
                </el-table-column>
            </el-table>
        </div>
    </div>
</template>

<style lang="scss">
.iro-ai-system {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;

    &__bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    &__title {
        margin-top: 0.5rem;
    }

    &__endpoint {
        margin: 0;
    }
}
</style>

