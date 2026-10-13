import { registerBlockType } from "@wordpress/blocks";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "侧栏导航",
        note: "显示「侧栏」菜单位置的菜单。",
    },
    "zh-TW": {
        blockTitle: "側欄導覽",
        note: "顯示「側欄」菜單位置的菜單。",
    },
    ja: {
        blockTitle: "サイドバーナビ",
        note: "「サイドバー」メニュー位置のメニューを表示します。",
    },
    en: {
        blockTitle: "Sidebar Nav",
        note: "Shows the menu assigned to the “侧栏” (sidebar) location.",
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
