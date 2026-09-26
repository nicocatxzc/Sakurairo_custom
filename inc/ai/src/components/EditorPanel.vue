<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { Check, Close, MagicStick } from "@element-plus/icons-vue";
import { onClickOutside } from "@vueuse/core";
import { ElMessage } from "element-plus";
import VueDraggableResizable from "vue-draggable-resizable";
import { aiApi } from "../api";
import { aiOptions } from "../config";
import { panel, panelTrigger } from "../panel";

const t = window.iroI18n.t;

// 三栏共用一套版式
const SECTIONS = [
    { field: "title", label: t("标题"), placeholder: t("输入新的标题") },
    { field: "excerpt", label: t("摘要"), placeholder: t("输入新的摘要"), textarea: true },
    { field: "tags", label: t("标签"), placeholder: t("用英文逗号分隔") },
];
// 字段 → 单字段接口：GET 只生成不落库，写回由 wp.data 完成
const GENERATORS = {
    title: aiApi.postTitle,
    excerpt: aiApi.postDescription,
    tags: aiApi.postKeyword,
};

const rootRef = ref(null);
// 面板是挂在 body 上的独立应用，编辑器数据只能从 wp.data 现取
const editorStore = window.wp?.data?.select("core/editor");
// core/editor 的 postId 要等编辑器异步 setupEditor 之后才有值，面板挂载时读到的是 null，
// 必须跟着 subscribe 更新，否则生成/应用按钮会一直处于不可用
const postId = ref(0);
// core 里的 post type 记录是异步解析的，刚挂载时可能还没到，先按不支持算，订阅里再修正
const tagsSupported = ref(false);

// 编辑器里的旧值：随 wp.data 变化，包括撤销、自动保存这些不是面板发起的改动
const old = reactive({ title: "", excerpt: "", tags: "" });
// 面板里的临时值：没被改过就跟着旧值走，改过就保留到点应用为止
const draft = reactive({ title: "", excerpt: "", tags: "" });
// 每栏正在进行的动作："" | "generate" | "apply"
const busy = reactive({ title: "", excerpt: "", tags: "" });

let unsubscribe = null;

// 标签在编辑器里存的是 term id 数组，名字要反查 core 数据源；
// 解析器还没拿到数据时返回 null，数据到位后 subscribe 会再触发一次
function readTagNames(ids) {
    if (ids.length === 0) {
        return "";
    }

    const terms = window.wp.data.select("core").getEntityRecords("taxonomy", "post_tag", {
        include: ids,
        per_page: 100,
    });

    return terms === null ? null : terms.map((term) => term.name).join(", ");
}

function readOld() {
    if (!editorStore) {
        return;
    }

    postId.value = Number(editorStore.getCurrentPostId() ?? 0);

    // 页面这类不支持标签的类型只有标签一栏不可用；post type 记录可能比面板挂载晚到
    const taxonomies = window.wp.data.select("core").getPostType(editorStore.getCurrentPostType() ?? "")?.taxonomies ?? [];

    tagsSupported.value = taxonomies.includes("post_tag");

    const current = {
        title: editorStore.getEditedPostAttribute("title") ?? "",
        excerpt: editorStore.getEditedPostAttribute("excerpt") ?? "",
        tags: tagsSupported.value ? readTagNames(editorStore.getEditedPostAttribute("tags") ?? []) : "",
    };

    for (const { field } of SECTIONS) {
        // null = 标签名还没解析出来，保留上一次的值
        if (current[field] === null) {
            continue;
        }

        if (draft[field] === old[field]) {
            draft[field] = current[field];
        }

        old[field] = current[field];
    }
}

// 写入标签要的是 term id 数组：先按名字找已有标签，找不到再新建，与自带的标签面板同一套做法
async function resolveTagIds(names) {
    const ids = [];

    for (const name of names) {
        const terms = await window.wp.data.resolveSelect("core").getEntityRecords("taxonomy", "post_tag", {
            search: name,
            per_page: 100,
        });
        const hit = (terms ?? []).find((term) => term.name.toLowerCase() === name.toLowerCase());

        if (hit) {
            ids.push(hit.id);
            continue;
        }

        try {
            ids.push((await window.wp.data.dispatch("core").saveEntityRecord("taxonomy", "post_tag", { name })).id);
        } catch (error) {
            // 同名标签已存在时接口回 term_exists，并带上已有 term_id
            if (!error?.data?.term_id) {
                throw error;
            }

            ids.push(error.data.term_id);
        }
    }

    return ids;
}

async function generate(field) {
    if (!postId.value) {
        ElMessage.warning(t("文章保存后才能生成内容。"));
        return;
    }

    if (busy[field]) {
        return;
    }

    busy[field] = "generate";

    try {
        draft[field] = (await GENERATORS[field](postId.value)).value ?? "";
    } catch (error) {
        ElMessage.error(error.message);
    } finally {
        busy[field] = "";
    }
}

async function apply(field) {
    if (busy[field]) {
        return;
    }

    busy[field] = "apply";

    try {
        if (field === "tags") {
            const names = [...new Set(draft.tags.split(/[,，]/).map((tag) => tag.trim()).filter(Boolean))];

            await window.wp.data.dispatch("core/editor").editPost({ tags: await resolveTagIds(names) });
        } else {
            window.wp.data.dispatch("core/editor").editPost({ [field]: draft[field] });
        }

        ElMessage.success(t("已应用到编辑器。"));
    } catch (error) {
        ElMessage.error(error.message);
    } finally {
        busy[field] = "";
    }
}

// 拖拽位移是相对面板 CSS 定位处的偏移：按下时记下那个锚点，之后每帧都按锚点算边界，
// 不依赖元素当下的渲染位置（同一帧里的多次移动来不及重排）
let anchor = { left: 0, top: 0 };
let accepted = { x: 0, y: 0 };

function rememberAnchor() {
    const el = rootRef.value?.$el;

    if (!el) {
        return false;
    }

    const rect = el.getBoundingClientRect();

    anchor = { left: rect.left - accepted.x, top: rect.top - accepted.y };

    return true;
}

// 拖拽组件默认不做边界限制，用它的 onDrag 钩子把面板挡在视口内（返回 false 即拒绝这一帧）
function clampDrag(x, y) {
    const el = rootRef.value?.$el;

    if (!el) {
        return false;
    }

    const left = anchor.left + x;
    const top = anchor.top + y;

    if (left < 0 || top < 0 || left + el.offsetWidth > window.innerWidth || top + el.offsetHeight > window.innerHeight) {
        return false;
    }

    accepted = { x, y };

    return true;
}

// 面板里的输入框要靠“自身包含”的判定才不算点击外部，跨 Shadow DOM 判断由 vueuse 负责；
// detectIframe 是为了点进古腾堡的画布 iframe（点击不会冒泡到父文档）时也能关闭
onClickOutside(rootRef, () => (panel.open = false), { ignore: [panelTrigger], detectIframe: true });

onMounted(() => {
    readOld();
    unsubscribe = window.wp?.data?.subscribe(readOld);
});

onBeforeUnmount(() => unsubscribe?.());
</script>

<template>
    <VueDraggableResizable v-show="panel.open" ref="rootRef" class-name="iro-ai-editor-panel" :w="352" h="auto"
        :z="1000000" :resizable="false" drag-handle=".iro-ai-editor-panel__header" :onDrag="clampDrag"
        :onDragStart="rememberAnchor">
        <div class="iro-ai-editor-panel__header">
            <el-text tag="b">{{ t("AI 内容助手") }}</el-text>
            <el-button size="small" :icon="Close" circle text :aria-label="t('关闭面板')" @click="panel.open = false" />
        </div>

        <div class="iro-ai-editor-panel__body">
            <el-alert v-if="!aiOptions.configured" class="iro-ai-editor-panel__alert" type="warning"
                :closable="false" show-icon :title="t('未配置 API Key，无法生成内容。')" />

            <div v-for="section in SECTIONS" :key="section.field" class="iro-ai-editor-panel__field">
                <div class="iro-ai-editor-panel__label">
                    <el-text tag="b" size="small">{{ section.label }}</el-text>
                    <el-text v-if="section.field === 'tags' && !tagsSupported" size="small" type="info">
                        {{ t("该内容类型不支持标签") }}
                    </el-text>
                </div>

                <el-text size="small" class="iro-ai-editor-panel__old" :class="{ 'is-empty': !old[section.field] }">
                    {{ t("旧：{value}", { value: old[section.field] || t("（无）") }) }}
                </el-text>

                <el-input v-if="section.field === 'tags'" v-model="draft[section.field]" size="small"
                    :disabled="!tagsSupported" :placeholder="section.placeholder" />
                <el-input v-else v-model="draft[section.field]" size="small"
                    :type="section.textarea ? 'textarea' : 'text'"
                    :autosize="section.textarea ? { minRows: 3, maxRows: 6 } : undefined"
                    :placeholder="section.placeholder" />

                <div class="iro-ai-editor-panel__actions">
                    <el-button size="small" :icon="MagicStick" :loading="busy[section.field] === 'generate'"
                        :disabled="busy[section.field] === 'apply' || !postId || (section.field === 'tags' && !tagsSupported)"
                        @click="generate(section.field)">
                        {{ t("生成") }}
                    </el-button>
                    <el-button type="primary" size="small" :icon="Check" :loading="busy[section.field] === 'apply'"
                        :disabled="busy[section.field] === 'generate' || draft[section.field] === old[section.field] || (section.field === 'tags' && !tagsSupported)"
                        @click="apply(section.field)">
                        {{ t("应用") }}
                    </el-button>
                </div>
            </div>
        </div>
    </VueDraggableResizable>
</template>

<style lang="scss">
.iro-ai-editor-panel {
    position: fixed;
    top: calc(var(--wp-admin--admin-bar--height, 32px) + 1.5rem);
    right: 1.5rem;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - var(--wp-admin--admin-bar--height, 32px) - 3rem);
    overflow: hidden;
    border-radius: 0.5rem;
    background-color: #fff;
    box-shadow: 0 0 1rem rgba(0, 0, 0, 0.15);
    box-sizing: border-box;

    * {
        box-sizing: border-box;
    }

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.625rem 0.75rem;
        border-bottom: 1px solid var(--el-border-color-lighter, #ebeef5);
        touch-action: none;
        cursor: move;
    }

    &__body {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        overflow-y: auto;
    }

    &__field {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
        padding: 0.75rem;
    }

    &__alert {
        margin: 0.75rem 0.75rem 0;
    }

    &__label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    &__old {
        display: -webkit-box;
        box-orient: vertical;
        line-clamp: 3;
        overflow: hidden;
        color: var(--el-text-color-secondary);
        word-break: break-all;

        &.is-empty {
            color: var(--el-text-color-placeholder);
        }
    }

    &__actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    &__footer {
        flex: 0 0 auto;
    }
}
</style>
