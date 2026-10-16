import { registerBlockType } from "@wordpress/blocks";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "文章头信息",
        note: "标题下方的时间、作者、阅读量等条目，显示哪些由主题设置决定",
    },
    "zh-TW": {
        blockTitle: "文章頭資訊",
        note: "標題下方的時間、作者、閱讀量等條目，顯示哪些由主題設定決定",
    },
    ja: {
        blockTitle: "記事メタ情報",
        note: "タイトル下の日時・著者・閲覧数など。表示する項目はテーマ設定に従います",
    },
    en: {
        blockTitle: "Post Meta",
        note: "Date, author and view count under the title; which ones show is up to the theme settings",
    },
});

function edit() {
    return (
        <Fragment>
            <Placeholder title={lang.blockTitle} summary={lang.note} />
        </Fragment>
    );
}

export default function headMetasBlock() {
    registerBlockType("sakurairo/head-metas", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.note,
        icon: "list-view",
        category: "sakurairo-decorate",
        attributes: {},
        edit,
        save() {
            return null;
        },
    });
}
