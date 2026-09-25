<script setup>
import { aiOptions } from "../config";

// 工具项由 PHP 的 iro_ai_panel_tools 过滤器提供，具体行为在监听方接入（后续接 REST 接口）
function runTool(tool) {
    document.dispatchEvent(new CustomEvent("iro-ai:run-tool", { detail: tool }));
}
</script>

<template>
    <div class="iro-ai-content">
        <dl class="iro-ai-content__meta">
            <dt>模型</dt>
            <dd>{{ aiOptions.model || "未设置" }}</dd>
            <dt>接口</dt>
            <dd>{{ aiOptions.apiBase || "未设置" }}</dd>
            <dt>凭证</dt>
            <dd :class="{ 'is-missing': !aiOptions.configured }">
                {{ aiOptions.configured ? "已配置" : "未配置" }}
            </dd>
        </dl>

        <div v-if="aiOptions.tools.length" class="iro-ai-content__tools">
            <button
                v-for="tool in aiOptions.tools"
                :key="tool.id"
                type="button"
                class="button"
                @click="runTool(tool)"
            >
                {{ tool.label }}
            </button>
        </div>
        <p v-else class="iro-ai-content__empty">暂无可用的 AI 工具。</p>
    </div>
</template>
