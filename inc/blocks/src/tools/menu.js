import { registerBlockType } from "@wordpress/blocks";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "侧栏导航",
        note: "读取「侧栏」菜单位置的菜单，结构与移动端导航栏一致；菜单标签按原样 HTML 输出，可以直接在标签里写图标。",
    },
    "zh-TW": {
        blockTitle: "側欄導覽",
        note: "讀取「側欄」菜單位置的菜單，結構與行動端導覽一致；菜單標籤按原樣 HTML 輸出，可以直接在標籤裡寫圖示。",
    },
    ja: {
        blockTitle: "サイドバーナビ",
        note: "「サイドバー」メニュー位置のメニューを読み込み、構造はモバイルナビと同じです。ラベルは HTML のまま出力されるので、アイコンも直接書けます。",
    },
    en: {
        blockTitle: "Sidebar Nav",
        note: "Reads the menu assigned to the “侧栏” location and renders it like the mobile navbar. Labels are output as raw HTML, so icons can be written directly.",
    },
});

function edit() {
    return (
        <Fragment>
            <Placeholder title={lang.blockTitle} summary={lang.note} />
        </Fragment>
    );
}

export default function menuBlock() {
    registerBlockType("sakurairo/sidebar-menu", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.note,
        icon: "menu-alt",
        category: "sakurairo-tools",
        attributes: {},
        edit,
        save() {
            return null;
        },
    });
}
