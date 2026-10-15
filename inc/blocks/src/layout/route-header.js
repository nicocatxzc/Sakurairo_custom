import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "页头",
        description: "由主题按路由输出的页头，位置锁死、不可编辑。",
        hint: "页头 · 由主题按路由输出",
    },
    "zh-TW": {
        blockTitle: "頁首",
        description: "由主題依路由輸出的頁首，位置鎖死、不可編輯。",
        hint: "頁首 · 由主題依路由輸出",
    },
    ja: {
        blockTitle: "ページヘッダー",
        description: "テーマがルートごとに出力するヘッダー。位置は固定で編集できません。",
        hint: "ヘッダー · テーマがルートごとに出力",
    },
    en: {
        blockTitle: "Page header",
        description: "The header the theme prints per route; locked in place and not editable.",
        hint: "Header · printed by the theme per route",
    },
});

function edit() {
    const blockProps = useBlockProps({
        className: "iro-block-placeholder iro-route-header",
    });

    return (
        <div {...blockProps}>
            <strong>{lang.blockTitle}</strong>
            <span>{lang.hint}</span>
        </div>
    );
}

// 这个块只在编辑器里占位：前台仍然是主题按路由输出 page-header，
// 所以它不进 PHP 侧的注册表，保存下来的也只有一个空注释
function save() {
    return null;
}

export default function routeHeaderBlock() {
    registerBlockType("sakurairo/route-header", {
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
            lock: { type: "object", default: { remove: true, move: true } },
        },
        edit,
        save,
    });
}
