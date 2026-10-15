import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "页面正文",
        description: "当前路由的正文，由主题按路由替换，不可删除。",
        hint: "页面正文 · 按路由自动替换",
    },
    "zh-TW": {
        blockTitle: "頁面正文",
        description: "目前路由的正文，由主題依路由替換，不可刪除。",
        hint: "頁面正文 · 依路由自動替換",
    },
    ja: {
        blockTitle: "ページ本文",
        description: "現在のルートの本文。テーマがルートごとに差し替えます。削除できません。",
        hint: "ページ本文 · ルートごとに自動差し替え",
    },
    en: {
        blockTitle: "Page content",
        description: "The body of the current route, swapped by the theme per route and not removable.",
        hint: "Page content · swapped per route",
    },
});

function edit() {
    const blockProps = useBlockProps({
        className: "iro-block-placeholder iro-route-content",
    });

    return (
        <div {...blockProps}>
            <strong>{lang.blockTitle}</strong>
            <span>{lang.hint}</span>
        </div>
    );
}

// 动态块：前台外壳与正文都由 PHP 输出（frontend/components/block/layout/route-content.php）
function save() {
    return null;
}

export default function routeContentBlock() {
    registerBlockType("sakurairo/route-content", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        category: "sakurairo-layout",
        supports: {
            html: false,
            reusable: false,
            multiple: false,
            inserter: false,
        },
        attributes: {
            lock: { type: "object", default: { remove: true } },
        },
        edit,
        save,
    });
}
