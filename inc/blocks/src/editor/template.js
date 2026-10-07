import { registerBlockType } from "@wordpress/blocks";
import { createElement } from "@wordpress/element";
import Placeholder from "../placeholder";

function createTemplateBlock({ name, title, icon = "layout" }) {
    registerBlockType(`sakurairo/${name}`, {
        title,
        icon: icon,
        apiVersion: 3,
        category: "sakurairo",

        supports: {
            html: false,
            reusable: false,
            customClassName: false,
            inserter: true,
            multiple: false,
        },

        edit() {
            return createElement(Placeholder, {
                title: `${title} 模板`,
            });
        },

        save: () => null,
    });
}

export default function registerTemplateBlocks() {
    createTemplateBlock({
        name: "friend-link",
        title: "友情链接",
        icon: createElement("i", { className: "fa-solid fa-link" }),
    });

    createTemplateBlock({
        name: "bangumi",
        title: "番剧",
        icon: createElement("i", { className: "fa-brands fa-bilibili" }),
    });

    createTemplateBlock({
        name: "favlist",
        title: "Bilibili 收藏",
        icon: createElement("i", { className: "fa-solid fa-bookmark" }),
    });

    createTemplateBlock({
        name: "steam",
        title: "Steam 库",
        icon: createElement("i", { className: "fa-brands fa-steam" }),
    });

    createTemplateBlock({
        name: "timeline",
        title: "时光轴",
        icon: createElement("i", { className: "fa-solid fa-inbox" }),
    });
}
