<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Refresh } from "@element-plus/icons-vue";
import { aiApi } from "../api";

const t = window.iroI18n.t;

const TYPE_LABELS = { post: "文章", page: "页面" };

// 状态代号 => 字典键，与 inc/functions/i18n/admin.php 里的状态判定一一对应
const STATE_LABELS = {
    missing: "无版本",
    outdated: "待同步",
    skeleton: "未翻译",
    published: "已发布",
    draft: "草稿 / 待审",
};

const STATE_TYPES = {
    missing: "info",
    outdated: "danger",
    skeleton: "warning",
    published: "success",
    draft: "info",
};

const rows = ref([]);
const languages = ref([]);
const locale = ref("");
const autofuzzy = ref(false);
const loading = ref(false);
// 正在跑的维护动作名，用来只给那一个按钮上转圈
const acting = ref("");
const pagination = reactive({ total: 0, pages: 1 });
const query = reactive({ search: "", page: 1, per_page: 50 });

const defaultLanguage = computed(
    () => languages.value.find((lang) => lang.is_default) ?? null,
);

// 默认语言是哪一种、其余语言走什么前缀，由服务端给出的事实拼出来
const defaultNote = computed(() => {
    const lang = defaultLanguage.value;

    if (lang === null) {
        return "";
    }

    // 举例要用「其它语言」的前缀：默认语言自己的前缀不是其余语言的入口
    const sample = languages.value.find((item) => !item.is_default) ?? lang;

    return t(
        "默认语言是「{label}」（跟随站点语言 {locale}），无前缀路径即属于它；其余语言通过 /{prefix}/ 之类的前缀访问。",
        {
            label: `${t(lang.name)}（${lang.code}）`,
            locale: locale.value,
            prefix: sample.prefix,
        },
    );
});

function stateLabel(state) {
    return t(STATE_LABELS[state] ?? state);
}

function languageName(code) {
    return languages.value.find((lang) => lang.code === code)?.name ?? code;
}

async function load() {
    loading.value = true;

    try {
        const data = await aiApi.i18nOverview({ ...query });
        // 状态数组按同一语言顺序给出，转成按代号取值，省得靠列序对齐
        rows.value = (data.items ?? []).map((item) => ({
            ...item,
            cells: Object.fromEntries(
                (item.statuses ?? []).map((cell) => [cell.code, cell]),
            ),
        }));
        languages.value = data.languages ?? [];
        locale.value = data.locale ?? "";
        autofuzzy.value = Boolean(data.autofuzzy);
        pagination.total = data.total ?? 0;
        pagination.pages = data.pages ?? 1;
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

// 三个动作共用一套编排：确认 → 调用 → 回显结果 → 重新拉取
async function act(action, label, success) {
    try {
        await ElMessageBox.confirm(
            t("确认执行「{action}」？", { action: label }),
            t("确认"),
            {
                confirmButtonText: t("继续"),
                cancelButtonText: t("取消"),
                type: "warning",
            },
        );
    } catch {
        // 取消不是错误，直接收场
        return;
    }

    acting.value = action;

    try {
        const data = await aiApi[action]();
        ElMessage.success(success(data));
        await load();
    } catch (error) {
        ElMessage.error(error.message);
    } finally {
        acting.value = "";
    }
}

function backfill() {
    return act(
        "i18nBackfill",
        t("为全部已发布内容补齐关联与未翻译副本"),
        (data) => {
            const { checked = 0, marked = 0, linked = 0, copies = 0 } = data;

            // 一条都没改时要说清核对过多少条：否则「0 条」看起来就像按钮没生效
            return marked + linked + copies === 0
                ? t(
                      "已核对 {checked} 条已发布内容，语言标记、关联标识与未翻译副本都已齐全，无需改动。",
                      { checked },
                  )
                : t(
                      "已核对 {checked} 条已发布内容：新增语言标记 {marked} 条、关联标识 {linked} 条、未翻译副本 {copies} 份。",
                      { checked, marked, linked, copies },
                  );
        },
    );
}

function repairLanguage() {
    return act(
        "i18nRepairLanguage",
        t("把原文的语言标记归位到默认语言"),
        (data) => t("已把 {count} 条原文的语言标记归位到默认语言。", { count: data.count ?? 0 }),
    );
}

function renameSlugs() {
    return act(
        "i18nRenameSlugs",
        t("规范化译文别名（原文别名 + 语言代号）"),
        (data) => t("已规范化 {count} 条译文的别名。", { count: data.count ?? 0 }),
    );
}

onMounted(load);
</script>

<template>
    <div class="iro-ai-translation">
        <div class="iro-ai-translation__line">
            <el-text size="small">{{ defaultNote }}</el-text>

            <div class="iro-ai-translation__search">
                <el-input v-model="query.search" size="small" :placeholder="t('搜索标题或内容')" clearable
                    @keyup.enter="onSearch" @clear="onSearch" />
                <el-button size="small" :icon="Refresh" :loading="loading" @click="onSearch" />
            </div>
        </div>

        <el-text size="small" type="info">
            {{ t("每篇内容只登记一行：同一关联标识的各语言版本合并在该行内，内容列显示站点语言的版本，各语言列指向该语言版本的编辑页。未翻译的副本默认是草稿，填好内容改成发布即可接手同一关联标识。") }}
        </el-text>

        <el-text v-if="autofuzzy" size="small" type="info">
            {{ t("标记为「待同步」的版本说明原文在此之后改动过，它的内容仍然在线，只是会在前台挂出可能过期的提示；把译文核对一遍并重新发布即视为已对齐。") }}
        </el-text>

        <div class="iro-ai-translation__line">
            <el-button size="small" :loading="acting === 'i18nBackfill'" @click="backfill">
                {{ t("为全部已发布内容补齐关联与未翻译副本") }}
            </el-button>

            <el-tooltip :content="t('只动「关联标识等于自身别名」且没有副本标记的内容，即真正的原文。')" placement="top">
                <el-button size="small" :loading="acting === 'i18nRepairLanguage'" @click="repairLanguage">
                    {{ t("把原文的语言标记归位到默认语言") }}
                </el-button>
            </el-tooltip>

            <el-tooltip :content="t('把早期被 WordPress 接上 -2、-3 的译文别名改成「-zh-tw」这类形式；会改动已发布译文的地址。')" placement="top">
                <el-button size="small" :loading="acting === 'i18nRenameSlugs'" @click="renameSlugs">
                    {{ t("规范化译文别名（原文别名 + 语言代号）") }}
                </el-button>
            </el-tooltip>
        </div>

        <div class="iro-ai-translation__table">
            <el-table v-loading="loading" :data="rows" size="small" border height="100%"
                :empty-text="t('还没有建立语言关联的内容。编辑并保存一次，或点上方按钮批量补齐。')">
                <el-table-column :label="t('内容')" min-width="240">
                    <template #default="{ row }">
                        <a :href="row.edit_link" target="_blank" rel="noopener">{{ row.title }}</a>
                        <div class="iro-ai-translation__meta">
                            <el-tag size="small" effect="plain">
                                {{ t(TYPE_LABELS[row.type] ?? row.type) }}
                            </el-tag>
                            <!-- 站点语言那一版不在时，内容列是别的语言，标明是哪一种 -->
                            <el-tag v-if="row.lang !== defaultLanguage?.code" size="small" effect="plain"
                                type="warning">
                                {{ t(languageName(row.lang)) }}
                            </el-tag>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column :label="t('关联标识')" min-width="180">
                    <template #default="{ row }">
                        <code>{{ row.path }}</code>
                    </template>
                </el-table-column>

                <el-table-column v-for="lang in languages" :key="lang.code" min-width="120">
                    <template #header>
                        <span>{{ t(lang.name) }}</span>
                        <code v-if="!lang.is_default" class="iro-ai-translation__prefix">/{{ lang.prefix }}/</code>
                    </template>
                    <template #default="{ row }">
                        <el-tag v-if="row.cells[lang.code]" size="small" effect="plain"
                            :type="STATE_TYPES[row.cells[lang.code].state] ?? 'info'">
                            <a v-if="row.cells[lang.code].url" :href="row.cells[lang.code].url" target="_blank"
                                rel="noopener" class="iro-ai-translation__link">
                                {{ stateLabel(row.cells[lang.code].state) }}
                            </a>
                            <template v-else>{{ stateLabel(row.cells[lang.code].state) }}</template>
                        </el-tag>
                    </template>
                </el-table-column>
            </el-table>
        </div>

        <div class="iro-ai-translation__footer">
            <el-text size="small">{{ t("共 {total} 条", { total: pagination.total }) }}</el-text>
            <el-pagination :current-page="query.page" :page-size="query.per_page" :total="pagination.total"
                :page-sizes="[20, 50, 100]" layout="sizes, prev, pager, next" size="small"
                @current-change="query.page = $event; load()"
                @size-change="query.per_page = $event; query.page = 1; load()" />
        </div>
    </div>
</template>

<style lang="scss">
.iro-ai-translation {
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

    &__table {
        flex: 1;
        min-height: 0;
    }

    &__meta {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.25rem;
    }

    &__prefix {
        margin-left: 0.25rem;
        font-size: 11px;
        color: var(--el-text-color-secondary);
    }

    // 标签里的链接跟标签同色，避免出现两种强调色
    &__link {
        color: inherit;
        text-decoration: none;
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
}

@media screen and (max-width: 782px) {

    // 窄屏下统计文案与分页器挤在一行会把表格压掉大半，分页器换行靠右
    .iro-ai-translation__footer {
        flex-wrap: wrap;

        .el-pagination {
            margin-left: auto;
        }
    }
}
</style>
