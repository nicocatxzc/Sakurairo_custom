import { registerBlockType } from "@wordpress/blocks";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "樱花图标",
        note: "旋转樱花装饰",
    },
    "zh-TW": {
        blockTitle: "櫻花圖示",
        note: "旋轉櫻花裝飾",
    },
    ja: {
        blockTitle: "桜アイコン",
        note: "回転する桜の装飾",
    },
    en: {
        blockTitle: "Sakura Icon",
        note: "Rotating sakura ornament",
    },
});

function edit() {
    return (
        <Fragment>
            <Placeholder title={lang.blockTitle} summary={lang.note} />
        </Fragment>
    );
}

export default function sakuraIconBlock() {
    registerBlockType("sakurairo/sakura-icon", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.note,
        icon: "star-filled",
        category: "sakurairo-decorate",
        attributes: {},
        edit,
        save() {
            return null;
        },
    });
}
