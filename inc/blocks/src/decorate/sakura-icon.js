import { registerBlockType } from "@wordpress/blocks";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "樱花图标",
        note: "旋转的樱花装饰，与页脚里那颗一致；页脚中的显示与否由「页脚 → 樱花」开关决定，单独插入则始终显示。",
    },
    "zh-TW": {
        blockTitle: "櫻花圖示",
        note: "旋轉的櫻花裝飾，與頁腳中的一致；頁腳內是否顯示由「頁腳 → 櫻花」開關決定，單獨插入則一律顯示。",
    },
    ja: {
        blockTitle: "桜アイコン",
        note: "フッターと同じ回転する桜の装飾です。フッターでの表示は「フッター → 桜」の設定に従い、単独で挿入した場合は常に表示します。",
    },
    en: {
        blockTitle: "Sakura Icon",
        note: "The rotating sakura ornament used in the footer. In the footer its visibility follows the “Footer → Sakura” switch; inserted on its own it always shows.",
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
