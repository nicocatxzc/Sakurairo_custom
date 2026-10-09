<script setup>
import { computed, markRaw, ref } from "vue";
import {
    ChatDotRound,
    DocumentCopy,
    Files,
    Menu,
    Monitor,
    Setting,
} from "@element-plus/icons-vue";
import { aiOptions } from "../config";
import PanelConfig from "./PanelConfig.vue";
import PanelChat from "./PanelChat.vue";
import PanelSyetem from "./PanelSystem.vue";
import PanelTranslation from "./PanelTranslation.vue";
import PostManagement from "./PostManagement.vue";

const t = window.iroI18n.t;
const active = ref("management");
// 窄屏下侧栏是覆盖式抽屉，点菜单项即收起；宽屏下不显示触发按钮，这个值恒为 false
const menuOpen = ref(false);

const panels = [
    {
        key: "management",
        label: t("文章管理"),
        icon: markRaw(Files),
        component: PostManagement,
    },
    // 多语言模块没开时该面板没有可展示的数据，直接不登记
    ...(aiOptions.i18n
        ? [
              {
                  key: "translation",
                  label: t("文章翻译"),
                  icon: markRaw(DocumentCopy),
                  component: PanelTranslation,
              },
          ]
        : []),
    {
        key: "chat",
        label: t("测试对话"),
        icon: markRaw(ChatDotRound),
        component: PanelChat,
    },
    {
        key: "config",
        label: t("系统配置"),
        icon: markRaw(Setting),
        component: PanelConfig,
    },
    {
        key: "diagnose",
        label: t("系统信息"),
        icon: markRaw(Monitor),
        component: PanelSyetem,
    },
];

const currentPanel = computed(
    () => panels.find((p) => p.key === active.value) ?? panels[0],
);

const handleSelect = (key) => {
    active.value = key;
    menuOpen.value = false;
};
</script>

<template>
    <div class="iro-ai-page">
        <el-container class="iro-ai-main" direction="vertical">
            <el-header class="iro-ai-header">
                <el-button class="iro-ai-header__toggle" text :icon="Menu" :aria-label="t('展开菜单')"
                    @click="menuOpen = true" />
                <el-text tag="b" size="large" class="iro-ai-header__title">
                    {{ currentPanel.label }}
                </el-text>
            </el-header>

            <el-container class="iro-ai-body">
                <el-aside class="iro-ai-aside" :class="{ 'is-open': menuOpen }" width="200px">
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

                <div v-if="menuOpen" class="iro-ai-mask" @click="menuOpen = false" />
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

    &__toggle {
        display: none;
        position: absolute;
        left: 0.75rem;
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

.iro-ai-mask {
    display: none;
}

// WordPress 后台的移动端断点；侧栏与后台菜单一样改成点击展开的覆盖式抽屉
@media screen and (max-width: 782px) {
    .iro-ai-page {
        margin: 0.5rem 0.5rem 0 0;
    }

    .iro-ai-header {
        padding: 0 2.5rem;

        &__toggle {
            display: inline-flex;
        }
    }

    .iro-ai-body {
        position: relative;
    }

    .iro-ai-aside {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 10;
        transform: translateX(-100%);
        transition: transform 0.2s ease-in-out;

        &.is-open {
            transform: none;
        }
    }

    .iro-ai-mask {
        display: block;
        position: absolute;
        inset: 0;
        z-index: 9;
        background-color: rgba(0, 0, 0, 0.3);
    }
}
</style>
