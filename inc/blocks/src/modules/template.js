import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import { createElement } from "@wordpress/element";

function createTemplateBlock({ name, title, nodeId, icon = "layout" }) {
    registerBlockType(`sakurairo/${name}`, {
        title,
        icon: icon,
        category: "sakurairo",

        supports: {
            html: false,
            reusable: false,
            customClassName: false,
            inserter: true,
            multiple: false,
        },

        edit() {
            const blockProps = useBlockProps({
                tabIndex: -1, // 阻止光标进入
                style: {
                    width: "100%",
                    padding: "2em",
                    textAlign: "center",
                    background: "#f6f7f7",
                    border: "2px dashed #ccd0d4",
                    fontSize: "16px",
                    color: "#555",
                    userSelect: "none",
                },
            });

            return createElement(
                "div",
                {
                    ...blockProps,
                    contentEditable: false, // 不允许编辑
                },
                `${title} 模板`,
            );
        },

        // save() {
        //     return createElement("div", {
        //         id: nodeId,
        //         "data-hachimi-template": name,
        //     });
        // },
        save: () => null,
    });
}

export default function registerTemplateBlocks() {
    createTemplateBlock({
        name: "friend-link",
        title: "友情链接",
        nodeId: "hachimi-friend-link",
        icon: createElement("i", { className: "fa-solid fa-link" }),
    });

    createTemplateBlock({
        name: "bangumi",
        title: "番剧",
        nodeId: "hachimi-bangumi",
        icon: createElement("i", { className: "fa-brands fa-bilibili" }),
    });

    createTemplateBlock({
        name: "favlist",
        title: "Bilibili 收藏",
        nodeId: "hachimi-favlist",
        icon: createElement("i", { className: "fa-solid fa-bookmark" }),
    });

    createTemplateBlock({
        name: "steam",
        title: "Steam 库",
        nodeId: "hachimi-steam",
        icon: createElement("i", { className: "fa-brands fa-steam" }),
    });

    createTemplateBlock({
        name: "timeline",
        title: "时光轴",
        nodeId: "hachimi-timeline",
        icon: createElement("i", { className: "fa-solid fa-inbox" }),
    });
}
