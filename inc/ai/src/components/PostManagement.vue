<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import {
    Check,
    Document,
    MagicStick,
    PriceTag,
    Refresh,
    RefreshLeft,
} from "@element-plus/icons-vue";
import { ElMessage } from "element-plus";
import { aiApi } from "../api";
import { aiOptions } from "../config";

// 一次请求只处理一篇文章的一个字段：后端不维护批量，改由前端编排并发，避免单请求超时
const GENERATE_CONCURRENCY = 2;
const SAVE_CONCURRENCY = 4;
// 实质性内容少于该字数的页面视为模板页
const MIN_LENGTH = 30;
// 字段与接口路径（/ai/posts/{field}）、表格列、存库位置的对应关系
// description → post_excerpt；keyword → 原生标签 post_tag（不写自定义字段）
const FIELDS = ["description", "keyword"];
const FIELD_NEW = { description: "new_excerpt", keyword: "new_keywords" };
const FIELD_OLD = { description: "excerpt", keyword: "keywords" };
const FIELD_FLAG = { description: "has_excerpt", keyword: "has_keywords" };

const t = window.iroI18n.t;

const tableRef = ref(null);
const rows = ref([]);
// 只镜像表格自身的选中行：能勾选的永远是当前渲染出来的行，不跨页、不维护 id 集合
const selection = ref([]);
const loading = ref(false);
const generating = ref(false);
const cancelled = ref(false);
const applying = ref(false);
const progress = reactive({ done: 0, total: 0 });
const pagination = reactive({ total: 0, pages: 1, skipped: 0 });
const counts = reactive({
    all: 0,
    post: 0,
    page: 0,
    no_excerpt: 0,
    no_keywords: 0,
});
const fields = reactive({ description: true, keyword: true });
const query = reactive({
    type: "all",
    filter: "all",
    search: "",
    skip_thin: true,
    page: 1,
    per_page: 20,
    orderby: "date",
    order: "desc",
});

const TYPE_LABELS = { post: "文章", page: "页面" };

const dirtyRows = computed(() => rows.value.filter((row) => isDirty(row)));
// 服务端只标记 matched（是否符合当前筛选），不符合的行由前端屏蔽
const maskedCount = computed(
    () => rows.value.filter((row) => !row.matched).length,
);
const percentage = computed(() =>
    progress.total === 0
        ? 0
        : Math.round((progress.done / progress.total) * 100),
);

// 输入框里的值跟库里的不一样 = 这格有待应用的更新
function isDirtyField(row, field) {
    return (
        row[FIELD_NEW[field]] !== row[FIELD_OLD[field]] &&
        (field !== "keyword" || row.tags_supported)
    );
}

// 行内被改过的字段：单篇应用与全部应用都只提交这些字段
function dirtyFields(row) {
    return FIELDS.filter((field) => isDirtyField(row, field));
}

function isDirty(row) {
    return dirtyFields(row).length > 0;
}

async function load() {
    loading.value = true;

    try {
        const data = await aiApi.posts({ ...query });
        // 两份数据：excerpt/keywords 是库里的本地值（负责渲染与比对），new_* 是待应用的新值；
        // 应用成功后直接把新值写回本地值
        rows.value = (data.items ?? []).map((item) => ({
            ...item,
            new_excerpt: item.excerpt ?? "",
            new_keywords: item.keywords ?? "",
            generating: "",
            applying: false,
        }));
        pagination.total = data.total ?? 0;
        pagination.pages = data.pages ?? 1;
        pagination.skipped = data.skipped ?? 0;
        Object.assign(counts, data.counts ?? {});
        // 换了一批行，表格里的勾选不再对应任何一行，直接清空
        tableRef.value?.clearSelection();
    } catch (error) {
        ElMessage.error(error.message);
    } finally {
        loading.value = false;
    }
}

function onSearch() {
    query.page = 1;
    load();
}

async function generate() {
    const targets = selection.value;

    if (targets.length === 0) {
        ElMessage.warning(t("请先勾选要处理的文章。"));
        return;
    }

    const wanted = FIELDS.filter((field) => fields[field]);

    if (wanted.length === 0) {
        ElMessage.warning(t("请至少勾选一项要生成的内容。"));
        return;
    }

    // 一个任务 = 一篇文章的一个字段，两个都勾选就是两倍任务量；
    // 关键词写的是原生标签，不支持标签的类型不进队列
    const queue = [];
    let skipped = 0;

    targets.forEach(({ id, tags_supported }) => {
        wanted.forEach((field) => {
            if (field === "keyword" && !tags_supported) {
                skipped++;
                return;
            }

            queue.push({ id, field });
        });
    });

    if (queue.length === 0) {
        ElMessage.warning(t("所选内容都不支持要生成的字段。"));
        return;
    }

    generating.value = true;
    cancelled.value = false;
    progress.done = 0;
    progress.total = queue.length;

    let failed = 0;

    // 固定数量的 worker 从队列取任务：逐条发请求，但不必等下一条返回
    const worker = async () => {
        while (queue.length > 0 && !cancelled.value) {
            const task = queue.shift();

            try {
                mergeGenerated(task.field, await fetchGenerated(task));
            } catch (error) {
                failed++;
                showRowError(task.id, error.message);
            }

            progress.done++;
        }
    };

    await Promise.all(
        Array.from(
            { length: Math.min(GENERATE_CONCURRENCY, queue.length) },
            () => worker(),
        ),
    );

    generating.value = false;

    const skippedText =
        skipped > 0
            ? t("（略过 {count} 项：页面不支持标签）", { count: skipped })
            : "";

    if (failed > 0) {
        ElMessage.warning(
            t("生成结束，其中 {failed} 条失败（见表格内提示）。{skipped}", {
                failed,
                skipped: skippedText,
            }),
        );
    } else if (!cancelled.value) {
        ElMessage.success(
            t("已生成 {done} 条{skipped}，核对后可应用。", {
                done: progress.done,
                skipped: skippedText,
            }),
        );
    }
}

function fetchGenerated({ id, field }) {
    return field === "keyword"
        ? aiApi.postKeyword(id)
        : aiApi.postDescription(id);
}

// 单篇单字段生成，方便只补某篇文章的摘要或关键词
async function generateRow(row, field) {
    if (row.generating || (field === "keyword" && !row.tags_supported)) {
        return;
    }

    row.generating = field;
    row.error = "";

    try {
        mergeGenerated(field, await fetchGenerated({ id: row.id, field }));
    } catch (error) {
        showRowError(row.id, error.message);
    } finally {
        row.generating = "";
    }
}

function showRowError(id, message) {
    const row = rows.value.find((r) => r.id === id);

    if (row) {
        row.error = message;
    }
}

// 生成结果按字段写回该行：可选的行都在当前页，不会出现“没有对应行”的情况
function mergeGenerated(field, item) {
    const row = rows.value.find((r) => r.id === item.id);

    if (row) {
        row[FIELD_NEW[field]] = item.value;
        row.error = "";
    }
}

async function applyRows(targets) {
    // 只提交改过的字段：一篇文章可能只应用摘要或只应用关键词
    const queue = [];

    targets.forEach((row) => {
        dirtyFields(row).forEach((field) => {
            queue.push({ id: row.id, field, value: row[FIELD_NEW[field]] });
        });
    });

    if (queue.length === 0) {
        ElMessage.warning(t("没有需要应用的修改。"));
        return;
    }

    const total = queue.length;
    applying.value = true;
    targets.forEach((row) => {
        row.applying = true;
    });

    let failed = 0;

    const worker = async () => {
        while (queue.length > 0) {
            const task = queue.shift();

            try {
                const item =
                    task.field === "keyword"
                        ? await aiApi.savePostKeyword(task.id, task.value)
                        : await aiApi.savePostDescription(task.id, task.value);
                const row = rows.value.find((r) => r.id === item.id);

                // 写入成功即用服务端返回值覆盖本地值
                if (row) {
                    row[FIELD_OLD[task.field]] = item.value;
                    row[FIELD_NEW[task.field]] = item.value;
                    row[FIELD_FLAG[task.field]] = item.value !== "";
                    row.error = "";
                }
            } catch (error) {
                failed++;
                showRowError(task.id, error.message);
            }
        }
    };

    await Promise.all(
        Array.from({ length: Math.min(SAVE_CONCURRENCY, queue.length) }, () =>
            worker(),
        ),
    );

    applying.value = false;
    targets.forEach((row) => {
        row.applying = false;
    });

    if (failed > 0) {
        ElMessage.warning(t("已应用，其中 {failed} 项写入失败。", { failed }));
    } else {
        ElMessage.success(
            total === 1
                ? t("已应用到文章。")
                : t("已应用 {total} 项。", { total }),
        );
    }

    // 批量应用后数量统计已过期，重新拉取；单篇应用只更新该行，避免覆盖其他未应用的编辑
    if (targets.length > 1) {
        await load();
    }
}

onMounted(load);
</script>

<template>
    <div class="iro-ai-management">
        <div class="iro-ai-management__line">
            <el-radio-group v-model="query.type" size="small" @change="onSearch">
                <el-radio-button value="all">{{ t("全部 {count}", { count: counts.all }) }}</el-radio-button>
                <el-radio-button value="post">{{ t("文章 {count}", { count: counts.post }) }}</el-radio-button>
                <el-radio-button value="page">{{ t("页面 {count}", { count: counts.page }) }}</el-radio-button>
            </el-radio-group>

            <el-radio-group v-model="query.filter" size="small" @change="onSearch">
                <el-radio-button value="all">{{ t("不筛选") }}</el-radio-button>
                <el-radio-button value="no_excerpt">{{ t("无摘要 {count}", { count: counts.no_excerpt }) }}</el-radio-button>
                <el-radio-button value="no_keywords">{{ t("无关键词 {count}", { count: counts.no_keywords }) }}</el-radio-button>
            </el-radio-group>

            <el-checkbox v-model="query.skip_thin" size="small" @change="onSearch">
                {{ t("略过实质性内容少于 {min} 字的模板页面", { min: MIN_LENGTH }) }}
            </el-checkbox>

            <div class="iro-ai-management__search">
                <el-input v-model="query.search" size="small" :placeholder="t('搜索标题或内容')" clearable @keyup.enter="onSearch"
                    @clear="onSearch" />
                <el-button size="small" :icon="Refresh" :loading="loading" @click="onSearch" />
            </div>
        </div>

        <div class="iro-ai-management__line">
            <el-text size="small">{{ t("已选 {count} 条", { count: selection.length }) }}</el-text>
            <!-- 全选只作用于未被屏蔽的行 -->
            <el-button size="small" @click="rows.forEach((row) => tableRef?.toggleRowSelection(row, row.matched))">
                {{ t("全选本页") }}
            </el-button>
            <el-button size="small" :disabled="selection.length === 0" @click="tableRef?.clearSelection()">
                {{ t("清空选择") }}
            </el-button>

            <el-divider direction="vertical" />

            <el-checkbox v-model="fields.description" size="small">{{ t("摘要") }}</el-checkbox>
            <el-checkbox v-model="fields.keyword" size="small">{{ t("关键词") }}</el-checkbox>
            <el-button type="primary" size="small" :icon="MagicStick" :loading="generating"
                :disabled="selection.length === 0" @click="generate">
                {{ t("生成") }}
            </el-button>
            <el-button v-if="generating" size="small" @click="cancelled = true">{{ t("停止") }}</el-button>

            <el-button type="success" size="small" :icon="Check" :loading="applying" :disabled="dirtyRows.length === 0"
                @click="applyRows(dirtyRows)">
                {{ t("全部应用（{count}）", { count: dirtyRows.length }) }}
            </el-button>
        </div>

        <el-alert v-if="!aiOptions.configured" type="warning" :closable="false" show-icon
            :title="t('未配置 API Key，无法生成摘要与关键词。')" />
        <div v-if="generating || progress.done > 0" class="iro-ai-management__progress">
            <el-progress :percentage="percentage" :stroke-width="10" />
            <el-text size="small">{{ t("正在生成 {done} / {total}", { done: progress.done, total: progress.total }) }}</el-text>
        </div>

        <div class="iro-ai-management__table">
            <!-- cell-class-name 拿到的 column.property 就是列上的 prop，也就是字段名 -->
            <el-table ref="tableRef" v-loading="loading" :data="rows"
                :row-class-name="({ row }) => (row.matched ? '' : 'is-masked')"
                :cell-class-name="({ column, row }) => (FIELDS.includes(column.property) && isDirtyField(row, column.property) ? 'is-dirty' : '')"
                size="small" border height="100%" @selection-change="selection = $event">
                <el-table-column type="selection" width="40" :selectable="(row) => row.matched" />

                <el-table-column :label="t('标题')" min-width="230">
                    <template #default="{ row }">
                        <a :href="row.edit_link" target="_blank" rel="noopener">{{ row.title }}</a>
                        <div class="iro-ai-management__tags">
                            <el-tag size="small" effect="plain" :type="row.type === 'page' ? 'warning' : 'info'">
                                {{ t(TYPE_LABELS[row.type] ?? row.type) }}
                            </el-tag>
                            <el-tag size="small" effect="plain" :type="row.content_length < MIN_LENGTH
                                ? 'danger'
                                : 'info'
                                ">
                                {{ t("{count} 字", { count: row.content_length }) }}
                            </el-tag>
                        </div>
                        <el-text v-if="row.error" size="small" type="danger">{{
                            row.error
                        }}</el-text>
                    </template>
                </el-table-column>
                <el-table-column prop="description" :label="t('摘要')" min-width="320">
                    <template #default="{ row }">
                        <el-text size="small" class="iro-ai-management__old" :class="{ 'is-empty': !row.has_excerpt }">
                            {{ t("旧：{value}", { value: row.excerpt || t("（无）") }) }}
                        </el-text>
                        <el-input v-model="row.new_excerpt" type="textarea" size="small"
                            :autosize="{ minRows: 2, maxRows: 4 }" :placeholder="t('新的摘要')" />
                    </template>
                </el-table-column>

                <el-table-column prop="keyword" :label="t('标签')" min-width="260">
                    <template #default="{ row }">
                        <el-text size="small" class="iro-ai-management__old" :class="{ 'is-empty': !row.has_keywords }">
                            {{ t("旧：{value}", { value: row.keywords || t("（无）") }) }}
                        </el-text>
                        <el-input v-model="row.new_keywords" size="small" :disabled="!row.tags_supported" :placeholder="row.tags_supported
                            ? t('写入文章标签，用英文逗号分隔')
                            : t('该内容类型不支持标签')
                            " />
                    </template>
                </el-table-column>

                <el-table-column :label="t('操作')" width="176" fixed="right">
                    <template #default="{ row }">
                        <el-tooltip :content="t('生成摘要')" placement="top">
                            <el-button size="small" :icon="Document" :loading="row.generating === 'description'"
                                :disabled="generating" @click="generateRow(row, 'description')" />
                        </el-tooltip>
                        <el-tooltip :content="t('生成标签')" placement="top">
                            <el-button size="small" :icon="PriceTag" :loading="row.generating === 'keyword'"
                                :disabled="generating || !row.tags_supported" @click="generateRow(row, 'keyword')" />
                        </el-tooltip>
                        <el-tooltip :content="t('应用')" placement="top">
                            <el-button type="primary" size="small" :icon="Check" :loading="row.applying"
                                :disabled="!isDirty(row)" @click="applyRows([row])" />
                        </el-tooltip>
                        <el-tooltip :content="t('还原为已保存的值')" placement="top">
                            <el-button size="small" :icon="RefreshLeft" :disabled="!isDirty(row)"
                                @click="row.new_excerpt = row.excerpt; row.new_keywords = row.keywords; row.error = ''" />
                        </el-tooltip>
                    </template>
                </el-table-column>
            </el-table>
        </div>

        <div class="iro-ai-management__footer">
            <el-text size="small">
                {{ t("共 {total} 条", { total: pagination.total }) }}
                <template v-if="pagination.skipped > 0">
                    {{ t("（本页已略过 {count} 个内容过短的页面）", { count: pagination.skipped }) }}
                </template>
                <template v-if="maskedCount > 0">
                    {{ t("（已屏蔽 {count} 条不符合当前筛选）", { count: maskedCount }) }}
                </template>
            </el-text>
            <el-pagination :current-page="query.page" :page-size="query.per_page" :total="pagination.total"
                :page-sizes="[10, 20, 50, 100]" layout="sizes, prev, pager, next" size="small"
                @current-change="query.page = $event; load()"
                @size-change="query.per_page = $event; query.page = 1; load()" />
        </div>
    </div>
</template>

<style lang="scss">
.iro-ai-management {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    height: 100%;
    padding: 1rem;
    box-sizing: border-box;
    overflow: hidden;

    &__line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem 0.75rem;
    }

    &__search {
        display: flex;
        gap: 0.5rem;
        margin-left: auto;

        .el-input {
            width: 14rem;
        }
    }

    &__progress {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    &__table {
        flex: 1;
        min-height: 0;

        .el-table__row.is-masked {
            display: none;
        }

        // 与库里的值不同 = 待应用，整格淡绿；输入框透明，底色才透得出来
        .el-table__cell.is-dirty {
            background-color: var(--el-color-success-light-9);

            .el-input__wrapper,
            .el-textarea__inner {
                background-color: transparent;
            }
        }
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        margin-top: 0.25rem;
    }

    &__old {
        display: -webkit-box;
        box-orient: vertical;
        line-clamp: 3;
        overflow: hidden;
        margin-bottom: 0.25rem;
        color: var(--el-text-color-secondary);
        word-break: break-all;

        &.is-empty {
            color: var(--el-text-color-placeholder);
        }
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
}
</style>
