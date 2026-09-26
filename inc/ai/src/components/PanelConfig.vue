<script setup>
import { onMounted, ref } from "vue";
import { ElMessage } from "element-plus";
import { Refresh } from "@element-plus/icons-vue";
import { aiApi } from "../api";
import { aiOptions } from "../config";

const t = window.iroI18n.t;
const loading = ref(false);
const saving = ref(false);
const models = ref([]);
const hasKey = ref(aiOptions.configured);
const form = ref({
    ai_api_base: aiOptions.apiBase || "",
    ai_api_key: "",
    ai_model: aiOptions.model || "",
});

// 写回表单并同步注入配置：测试对话等其他面板读的是 aiOptions 这份单例
function applySettings(settings) {
    form.value = {
        ai_api_base: settings.ai_api_base ?? "",
        ai_api_key: settings.ai_api_key ?? "",
        ai_model: settings.ai_model ?? "",
    };
    hasKey.value = !!settings.configured;
    aiOptions.apiBase = settings.ai_api_base ?? "";
    aiOptions.model = settings.ai_model ?? "";
    aiOptions.configured = !!settings.configured;
}

async function load() {
    loading.value = true;

    try {
        applySettings(await aiApi.settings());
    } catch (error) {
        ElMessage.error(error.message);
    } finally {
        loading.value = false;
    }
}

async function loadModels(refresh = false) {
    try {
        models.value = (await aiApi.models(refresh)).models ?? [];
    } catch (error) {
        // 接口未配置时这里会失败，不应阻塞设置表单
        console.error("[iro-ai] 模型列表获取失败", error);
    }
}

async function save() {
    saving.value = true;

    try {
        applySettings(await aiApi.saveSettings({ ...form.value }));
        await loadModels(true);
        ElMessage.success(t("配置已保存"));
    } catch (error) {
        ElMessage.error(error.message);
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await Promise.all([load(), loadModels()]);
});
</script>

<template>
    <div v-loading="loading" class="iro-ai-config">
        <el-alert v-if="!hasKey" type="warning" :closable="false" show-icon :title="t('尚未配置 API Key，AI 功能不可用。')"
            class="iro-ai-config__alert" />

        <el-form label-position="top" class="iro-ai-config__form">
            <el-form-item :label="t('接口地址')">
                <el-input v-model="form.ai_api_base" placeholder="https://api.deepseek.com/v1" clearable />
                <div class="iro-ai-config__hint">
                    {{ t("OpenAI 兼容接口的 Base URL，主题会自动追加 /chat/completions") }}
                </div>
            </el-form-item>

            <el-form-item label="API Key">
                <el-input v-model="form.ai_api_key" type="password" show-password placeholder="sk-..." clearable />
                <div class="iro-ai-config__hint">
                    {{ t("留空即清除；保存后会同步到 WordPress Connectors 的凭证槽位") }}
                </div>
            </el-form-item>

            <el-form-item :label="t('模型名称')">
                <el-select v-model="form.ai_model" filterable allow-create default-first-option :placeholder="t('选择或输入模型名称')"
                    class="iro-ai-config__model">
                    <el-option v-for="item in models" :key="item.id" :label="item.name || item.id" :value="item.id" />
                </el-select>
                <div class="iro-ai-config__hint">
                    {{ t("可从接口返回的模型中选择，也可手动输入") }}
                </div>
            </el-form-item>
        </el-form>

        <div class="iro-ai-config__actions">
            <el-button :icon="Refresh" @click="loadModels(true)">
                {{ t("刷新模型") }}
            </el-button>
            <el-button type="primary" :loading="saving" @click="save">
                {{ t("保存配置") }}
            </el-button>
        </div>
    </div>
</template>

<style lang="scss">
.iro-ai-config {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
    box-sizing: border-box;

    &__alert {
        margin: 0;
    }

    &__form {
        max-width: 36rem;
    }

    &__model {
        width: 100%;
    }

    &__hint {
        width: 100%;
        margin-top: 0.25rem;
        color: var(--el-text-color-secondary);
        font-size: 0.75rem;
        line-height: 1.4;
    }

    &__actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
}
</style>
