import { registerBlockType } from "@wordpress/blocks";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "一言",
        note: "内容按「页脚 → 一言」设置输出：API 模式留空由前台请求填充，自定义与混合模式在服务端预渲染。",
    },
    "zh-TW": {
        blockTitle: "一言",
        note: "內容依「頁腳 → 一言」設定輸出：API 模式留空由前台請求填充，自訂與混合模式在伺服器端預先渲染。",
    },
    ja: {
        blockTitle: "ひとこと",
        note: "「フッター → ひとこと」の設定に従います。API モードは空で出力しフロント側が取得、カスタムとミックスはサーバー側で描画します。",
    },
    en: {
        blockTitle: "Hitokoto",
        note: "Output follows the “Footer → Hitokoto” setting: API mode stays empty and is filled on the front end, custom and mixed modes are pre-rendered on the server.",
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
