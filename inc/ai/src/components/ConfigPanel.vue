<script setup>
import { computed, markRaw, ref } from "vue";
import {
    ChatDotRound,
    Files,
    Monitor,
    Setting,
} from "@element-plus/icons-vue";
import PanelConfig from "./PanelConfig.vue";
import PanelChat from "./PanelChat.vue";
import PanelSyetem from "./PanelSystem.vue";
import PostManagement from "./PostManagement.vue";

const active = ref("management");

const panels = [
    {
        key: "management",
        label: "文章管理",
        icon: markRaw(Files),
        component: PostManagement,
    },
    {
        key: "chat",
        label: "测试对话",
        icon: markRaw(ChatDotRound),
        component: PanelChat,
    },
    {
        key: "config",
        label: "系统配置",
        icon: markRaw(Setting),
        component: PanelConfig,
    },
    {
        key: "diagnose",
        label: "系统信息",
        icon: markRaw(Monitor),
        component: PanelSyetem,
    },
];

const currentPanel = computed(
    () => panels.find((p) => p.key === active.value) ?? panels[0],
);

const handleSelect = (key) => {
    active.value = key;
};
</script>

<template>
    <div class="iro-ai-page">
        <el-container class="iro-ai-main" direction="vertical">
            <el-header class="iro-ai-header">
                <el-text tag="b" size="large" class="iro-ai-header__title">
                    {{ currentPanel.label }}
                </el-text>
            </el-header>

            <el-container class="iro-ai-body">
                <el-aside class="iro-ai-aside" width="200px">
                    <el-menu :default-active="active" @select="handleSelect">
                        <el-menu-item v-for="panel in panels" :key="panel.key" :index="panel.key">
                            <el-icon>
                                <component :is="panel.icon" />
                            </el-icon>
                            <span>{{ panel.label }}</span>
                        </el-menu-item>
                    </el-menu>
                </el-aside>

                <el-main class="iro-ai-content">
                    <keep-alive>
                        <component :is="currentPanel.component" />
                    </keep-alive>
                </el-main>
            </el-container>
        </el-container>
    </div>
</template>

<style lang="scss">
.iro-ai-page {
    margin: 1.5rem 1.5rem 0 0;
}

.iro-ai-main {
    height: 85vh;
    border-radius: 1rem;
    background-color: #fff;
    box-shadow: 0 0 1rem rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.iro-ai-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    height: 3.5rem;
    padding: 0 3.5rem;
    border-bottom: 1px solid var(--el-border-color-lighter);

    &__title {
        text-align: center;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__close {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
    }
}

.iro-ai-body {
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

.iro-ai-aside {
    background-color: var(--el-bg-color);
    border-right: 1px solid var(--el-border-color-lighter);
    overflow-y: auto;

    .el-menu {
        height: 100%;
        border-right: none;
    }
}

.iro-ai-content {
    display: flex;
    flex-direction: column;
    padding: 0;
    overflow: hidden;
    background-color: var(--el-fill-color-blank);

    >* {
        flex: 1;
        min-width: 0;
        min-height: 0;
        overflow: auto;
    }
}
</style>
