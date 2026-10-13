import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks, useBlockProps } from "@wordpress/block-editor";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "侧边栏容器",
        description: "全高的一栏，通常放在正文两侧。",
        appender: "放入要放进侧栏的内容",
    },
    "zh-TW": {
        blockTitle: "側邊欄容器",
        description: "全高的一欄，通常放在正文兩側。",
        appender: "放入要放進側欄的內容",
    },
    ja: {
        blockTitle: "サイドバーコンテナ",
        description: "全高のカラムで、通常は本文の左右に置きます。",
        appender: "サイドバーに入れるブロックを置いてください",
    },
    en: {
        blockTitle: "Sidebar Container",
        description: "A full-height column, usually placed beside the content.",
        appender: "Drop the blocks for this sidebar here",
    },
});

function edit() {
    const blockProps = useBlockProps({ className: "site-side-bar" });

    return (
        <div {...blockProps}>
            <InnerBlocks
                renderAppender={InnerBlocks.ButtonBlockAppender}
                placeholder={lang.appender}
            />
        </div>
    );
}

// 侧边栏由前台动态渲染
function save() {
    return <InnerBlocks.Content />;
}

export default function siteSideBarBlock() {
    registerBlockType("sakurairo/site-side-bar", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "align-pull-left",
        category: "sakurairo-layout",
        supports: {
            html: false,
            reusable: false,
        },
        edit,
        save,
    });
}
