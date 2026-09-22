<script setup lang="ts">
import VueDraggableResizable from "vue-draggable-resizable";
import { onClickOutside } from "@vueuse/core";
import api from "../../app/utils/api";

interface SmileyItem {
    id: string;
    type: "image" | "text";
    code: string;
    title: string;
    alt?: string;
    src?: string;
    text?: string;
    size?: number;
}

interface SmileyPack {
    id: string;
    title: string;
    type: "image" | "text";
    code_format: string | null;
    insert: {
        mode?: "token" | "raw";
        wrap?: [string, string];
        wrap_requires_markdown?: boolean;
    };
    url_format: string | null;
    items: SmileyItem[];
}

/** 面板尺寸 */
const PANEL_WIDTH = 300;
const PANEL_HEIGHT = 300;
const MOBILE_HEIGHT = 200;
/** 面板与视口/开关之间的最小留白 */
const GAP = 8;

const packs = ref<SmileyPack[]>([]);
const open = ref(false);
const activePack = ref("");
const posX = ref(0);
const posY = ref(0);
/** 弹出动画的原点 */
const originX = ref(0);
const originY = ref(0);
const isMobile = ref(false);
/** 拖动过之后不再自动归位 */
const moved = ref(false);

const panelRef = useTemplateRef("panel");
/** 点开关、评论输入区、Markdown 开关不算“外部点击”，面板保持展开 */
const toggleEl = ref<HTMLElement | null>(null);
const commentBoxEl = ref<Element | null>(null);
const markdownToggleEl = ref<Element | null>(null);

const mediaQuery = window.matchMedia("(max-width: 860px)");

const panelWidth = computed(() =>
    isMobile.value ? Math.max(window.innerWidth - GAP * 2, 200) : PANEL_WIDTH,
);
const panelHeight = computed(() =>
    isMobile.value ? MOBILE_HEIGHT : PANEL_HEIGHT,
);

function clamp(value: number, min: number, max: number) {
    return Math.min(Math.max(value, min), Math.max(min, max));
}

/** 贴到开关下方（放不下就放到上方），再夹进视口 */
function placePanel() {
    if (moved.value || !toggleEl.value) return;

    const rect = toggleEl.value.getBoundingClientRect();
    const width = panelWidth.value;
    const height = panelHeight.value;

    let top = rect.bottom + GAP;
    if (top + height > window.innerHeight) {
        top = rect.top - height - GAP;
    }

    posX.value = clamp(
        rect.left + rect.width / 2 - width / 2,
        GAP,
        window.innerWidth - width - GAP,
    );
    posY.value = clamp(top, GAP, window.innerHeight - height - GAP);
}

function openPanel() {
    placePanel();
    refreshOrigin();
    open.value = true;
}

// 将面板形变原点改为按钮位置
function refreshOrigin() {
    if (!toggleEl.value) return;

    const rect = toggleEl.value.getBoundingClientRect();

    originX.value = rect.x;
    originY.value = rect.y;
}

/**
 * 拖动时同步坐标：位置记下来（之后不再自动归位），弹出原点重新指向开关
 *
 * @param left 面板左边界（视口坐标）
 * @param top  面板上边界（视口坐标）
 */
function onDragging(left: number, top: number) {
    posX.value = left;
    posY.value = top;
    moved.value = true;

    refreshOrigin();
}

function closePanel() {
    open.value = false;
}

function togglePanel() {
    // 开关只在有表情包时输出，这里再兜一层
    if (!packs.value.length) return;

    open.value ? closePanel() : openPanel();
}

function markdownEnabled() {
    return (
        (document.getElementById("enable_markdown") as HTMLInputElement | null)
            ?.checked === true
    );
}

/** 写入评论框：token 包写 code，文本包按 insert 声明决定是否套 Markdown 反引号 */
function insertSmiley(pack: SmileyPack, item: SmileyItem) {
    const textarea = document.getElementById(
        "comment",
    ) as HTMLTextAreaElement | null;
    if (!textarea) return;

    let text = item.code || item.text || "";

    if (pack.insert?.mode === "raw") {
        text = item.text || text;

        const wrap = pack.insert.wrap;
        if (
            wrap?.length === 2 &&
            (!pack.insert.wrap_requires_markdown || markdownEnabled())
        ) {
            text = wrap[0] + text + wrap[1];
        }
    }

    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? start;
    const caret = start + text.length;

    textarea.value =
        textarea.value.slice(0, start) + text + textarea.value.slice(end);
    textarea.focus({ preventScroll: true });
    textarea.setSelectionRange(caret, caret);
}

function onBreakpointChange(event: MediaQueryListEvent) {
    isMobile.value = event.matches;
    if (!open.value) return;

    placePanel();
    refreshOrigin();
}

onMounted(async () => {
    toggleEl.value = document.getElementById("emotion-toggle");
    commentBoxEl.value = document.querySelector(".comment-form-comment");
    markdownToggleEl.value = document.querySelector(".markdown-toggle");

    isMobile.value = mediaQuery.matches;

    toggleEl.value?.addEventListener("click", togglePanel);
    mediaQuery.addEventListener("change", onBreakpointChange);

    try {
        const { data } = await api.get<SmileyPack[]>(
            `${_iro.config.iro_api}/comment/smiles`,
        );
        packs.value = Array.isArray(data) ? data : [];
        activePack.value = packs.value[0]?.id ?? "";
    } catch (error) {
        console.error("[Smiles]", error);
    }
});

// 点面板、开关、输入区之外的地方收起；target 每次事件现取，面板重渲染也不受影响
onClickOutside(panelRef, closePanel, {
    ignore: [toggleEl, commentBoxEl, markdownToggleEl],
});

onBeforeUnmount(() => {
    toggleEl.value?.removeEventListener("click", togglePanel);
    mediaQuery.removeEventListener("change", onBreakpointChange);
});
</script>

<template>
    <VueDraggableResizable
        ref="panel"
        class="emotion-box no-select"
        :class="{ open }"
        :style="{ transformOrigin: `${originX}px ${originY}px` }"
        :draggable="!isMobile"
        :resizable="false"
        :w="panelWidth"
        :h="panelHeight"
        :x="posX"
        :y="posY"
        :z="99"
        drag-handle=".emotion-header"
        :prevent-deactivation="true"
        @dragging="onDragging"
    >
        <div class="emotion-header no-select">Woooooow ヾ(≧∇≦*)ゝ</div>

        <table class="motion-switcher-table">
            <tr>
                <th
                    v-for="pack in packs"
                    :key="pack.id"
                    :class="[
                        `${pack.id}-bar`,
                        { 'on-hover': pack.id === activePack },
                    ]"
                    @click="activePack = pack.id"
                >
                    {{ pack.title }}
                </th>
            </tr>
        </table>

        <div
            v-for="pack in packs"
            v-show="pack.id === activePack"
            :key="pack.id"
            class="motion-container"
            :class="[
                `${pack.id}-container`,
                pack.type === 'image' ? 'is-image' : 'is-text',
            ]"
        >
            <template v-if="pack.type === 'image'">
                <span
                    v-for="item in pack.items"
                    :key="item.id"
                    :title="item.title"
                    @click="insertSmiley(pack, item)"
                >
                    <img
                        :class="{ 'emoji-img': !item.size }"
                        :src="item.src"
                        :alt="item.alt"
                        :style="item.size ? { height: `${item.size}px` } : null"
                        loading="lazy"
                    />
                </span>
            </template>

            <template v-else>
                <span
                    v-for="item in pack.items"
                    :key="item.id"
                    class="emoji-item"
                    :title="item.title"
                    @click="insertSmiley(pack, item)"
                >
                    {{ item.text }}
                </span>
            </template>
        </div>
    </VueDraggableResizable>
</template>

<style scoped lang="scss">
.emotion-box {
    display: flex;
    flex-direction: column;

    // vue-draggable-resizable 用行内 transform 定位，锚点要固定在视口左上角
    position: fixed;
    left: 0;
    top: 0;

    overflow: hidden;
    box-sizing: border-box;

    background-color: var(--widget-background-color);
    border: var(--border-shine);
    border-radius: 0.6rem;
    box-shadow: var(--widget-shadow-shine);

    scale: 0;
    pointer-events: none;
    transition: scale 0.3s ease-in-out;

    &.open {
        scale: 1;
        pointer-events: auto;
    }

    .emotion-header {
        width: 100%;
        padding: 5px;

        text-align: center;
        color: var(--word-color-first);
        background-color: rgba(var(--widget-background-reverse), 0.06);
        border-bottom: var(--border-sketch);

        cursor: grab;
        user-select: none;
        touch-action: none;

        &:active {
            cursor: grabbing;
        }
    }

    .motion-switcher-table {
        width: 100%;
        margin: 0;
        table-layout: fixed;
        border-collapse: collapse;

        th {
            padding: 8px;
            text-align: center;
            font-weight: 700;
            color: var(--word-color-second);
            border-radius: 5px;
            cursor: pointer;

            &:hover {
                background-color: rgba(var(--widget-background-reverse), 0.05);
            }

            &.on-hover {
                color: var(--active-color);
            }
        }
    }

    .motion-container {
        flex: 1;
        min-height: 0;
        margin-bottom: 5px;
        overflow: auto;
        border-radius: 5px;

        scrollbar-width: none;

        &::-webkit-scrollbar {
            width: 0;
            height: 0;
            background-color: transparent;
        }

        &.is-image {
            padding-left: 1rem;
        }

        img {
            margin: 7px;
            vertical-align: middle;
            cursor: pointer;
            transition: transform 0.2s ease-in-out;

            &:hover {
                transform: scale(1.2);
            }

            &.emoji-img {
                width: 32px;
                height: 32px;
            }
        }

        .emoji-item {
            display: inline-block;
            margin: 3px;
            padding: 3px 5px;

            color: var(--word-color-first);
            border-radius: 4px;
            cursor: pointer;

            &:hover {
                background-color: rgba(var(--widget-background-reverse), 0.06);
            }
        }
    }
}

@media (max-width: 860px) {
    .emotion-box .emotion-header {
        display: none;
    }
}
</style>
