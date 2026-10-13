import { registerBlockType } from "@wordpress/blocks";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "一言",
        note: "随机展示一句话，内容来自「页脚 → 一言」设置。",
    },
    "zh-TW": {
        blockTitle: "一言",
        note: "隨機顯示一句話，內容來自「頁腳 → 一言」設定。",
    },
    ja: {
        blockTitle: "ひとこと",
        note: "一文をランダムに表示します。内容は「フッター → ひとこと」の設定から取得します。",
    },
    en: {
        blockTitle: "Hitokoto",
        note: "Shows a random quote; the content comes from the “Footer → Hitokoto” settings.",
    },
});

function edit() {
    return (
        <Fragment>
            <Placeholder title={lang.blockTitle} summary={lang.note} />
        </Fragment>
    );
}

export default function hitokotoBlock() {
    registerBlockType("sakurairo/hitokoto", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.note,
        icon: "format-quote",
        category: "sakurairo-decorate",
        attributes: {},
        edit,
        save() {
            return null;
        },
    });
}
