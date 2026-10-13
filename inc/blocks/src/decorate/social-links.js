import { registerBlockType } from "@wordpress/blocks";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "社交区域",
        note: "社交图标占位；图标与链接来自「封面 → 社交区域」设置。",
    },
    "zh-TW": {
        blockTitle: "社交區域",
        note: "社交圖示佔位；圖示與連結來自「封面 → 社交區域」設定。",
    },
    ja: {
        blockTitle: "ソーシャル",
        note: "ソーシャルアイコンのプレースホルダー。アイコンとリンクは「カバー → ソーシャル」の設定から取得します。",
    },
    en: {
        blockTitle: "Social",
        note: "Placeholder for the social icons; icons and links come from the “Cover → Social” settings.",
    },
});

function edit() {
    return (
        <Fragment>
            <Placeholder title={lang.blockTitle} summary={lang.note} variant="bar" />
        </Fragment>
    );
}

export default function socialLinksBlock() {
    registerBlockType("sakurairo/social-links", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.note,
        icon: "share",
        category: "sakurairo-decorate",
        attributes: {},
        edit,
        save() {
            return null;
        },
    });
}
