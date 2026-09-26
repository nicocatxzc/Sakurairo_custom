<script setup>
import { onMounted, ref } from "vue";
import { Plus, Refresh } from "@element-plus/icons-vue";
import { TrBubbleList, TrSender, TrWelcome } from "@opentiny/tiny-robot";
import { aiApi } from "../api";
import { aiOptions } from "../config";
import { clearConversations, putConversation, readConversation } from "../storage";

const t = window.iroI18n.t;
const messages = ref([]);
const draft = ref("");
const model = ref(aiOptions.model || "");
const models = ref([]);
const loading = ref(false);
const conversationId = ref("");

let controller = null;

// tiny-robot 的角色配置：用户靠右、AI 靠左
const roleConfigs = {
    user: { placement: "end" },
    ai: { placement: "start" },
};

function createId() {
    return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
}

// 只落库纯数据，避免把 loading 状态写进 IndexedDB
function persist() {
    if (!conversationId.value) {
        return Promise.resolve();
    }

    return putConversation({
        id: conversationId.value,
        model: model.value,
        messages: messages.value.map(({ id, role, content }) => ({ id, role, content })),
    }).catch((error) => console.error("[iro-ai] 会话保存失败", error));
}

async function loadModels(refresh = false) {
    try {
        models.value = (await aiApi.models(refresh)).models ?? [];
    } catch (error) {
        console.error("[iro-ai] 模型列表获取失败", error);
    }
}

async function loadConversation() {
    let saved = null;

    try {
        saved = await readConversation();
    } catch (error) {
        console.error("[iro-ai] 会话读取失败", error);
    }

    conversationId.value = saved?.id || createId();
    model.value = saved?.model || model.value;
    messages.value = saved?.messages ?? [];
}

// 开启新对话：删掉已保存的会话，重新开始
async function newConversation() {
    controller = null;
    loading.value = false;
    draft.value = "";
    messages.value = [];

    try {
        await clearConversations();
    } catch (error) {
        console.error("[iro-ai] 会话清理失败", error);
    }

    conversationId.value = createId();
    await persist();
}

function replaceMessage(id, patch) {
    messages.value = messages.value.map((message) =>
        message.id === id ? { ...message, ...patch } : message,
    );
}

async function onModelChange() {
    await persist();
}

async function onSubmit(text) {
    const content = text.trim();

    if (!content || loading.value) {
        return;
    }

    const replyId = createId();
    messages.value = [
        ...messages.value,
        { id: createId(), role: "user", content },
        { id: replyId, role: "ai", content: "", loading: true },
    ];
    draft.value = "";
    loading.value = true;
    controller = new AbortController();

    try {
        const payload = await aiApi.chat(
            messages.value
                .filter((message) => message.id !== replyId)
                .map((message) => ({
                    role: message.role === "ai" ? "assistant" : "user",
                    content: message.content,
                })),
            model.value,
            controller.signal,
        );

        replaceMessage(replyId, { content: payload.reply, loading: false });
    } catch (error) {
        replaceMessage(replyId, {
            content:
                error.name === "AbortError"
                    ? t("（已取消）")
                    : t("请求失败：{message}", { message: error.message }),
            loading: false,
        });
    } finally {
        loading.value = false;
        controller = null;
        await persist();
    }
}

function onCancel() {
    controller?.abort();
}

onMounted(async () => {
    await Promise.all([loadConversation(), loadModels()]);
});
</script>

<template>
    <div class="iro-ai-chat">
        <div class="iro-ai-chat__bar">
            <el-select
                v-model="model"
                class="iro-ai-chat__model"
                :placeholder="t('选择模型')"
                filterable
                @change="onModelChange"
            >
                <el-option
                    v-for="item in models"
                    :key="item.id"
                    :label="item.name || item.id"
                    :value="item.id"
                />
            </el-select>
            <el-button :icon="Refresh" @click="loadModels(true)" />
            <el-button type="primary" :icon="Plus" @click="newConversation">
                {{ t("开启新对话") }}
            </el-button>
        </div>

        <el-alert
            v-if="!aiOptions.configured"
            type="warning"
            :closable="false"
            show-icon
            :title="t('未配置 API Key，测试对话不可用。')"
            class="iro-ai-chat__alert"
        />

        <div class="iro-ai-chat__body">
            <TrBubbleList
                v-if="messages.length"
                :messages="messages"
                :role-configs="roleConfigs"
                auto-scroll
            />
            <TrWelcome
                v-else
                :title="t('AI 测试对话')"
                :description="t('仅用于验证主题的 AI Provider 是否可用，对话只保存在本机浏览器。')"
                align="center"
            />
        </div>

        <div class="iro-ai-chat__footer">
            <TrSender
                v-model="draft"
                :loading="loading"
                :disabled="loading || !aiOptions.configured"
                :placeholder="t('输入内容后按 Enter 发送')"
                @submit="onSubmit"
                @cancel="onCancel"
            />
        </div>
    </div>
</template>

<style lang="scss">
.iro-ai-chat {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 1rem;
    gap: 1rem;
    box-sizing: border-box;

    &__bar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    &__model {
        width: 16rem;
    }

    &__alert {
        margin: 0;
    }

    &__body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
    }

    &__footer {
        flex: 0 0 auto;
    }
}
</style>
