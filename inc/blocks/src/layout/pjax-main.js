import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks, useBlockProps } from "@wordpress/block-editor";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "主内容容器",
        description: "正文与页面内容的位置。",
        appender: "放入要显示的正文内容",
    },
    "zh-TW": {
        blockTitle: "主內容容器",
        description: "正文與頁面內容的位置。",
        appender: "放入要顯示的正文內容",
    },
    ja: {
        blockTitle: "メインコンテンツコンテナ",
        description: "本文とページ内容の位置。",
        appender: "表示する本文の内容を置いてください",
    },
    en: {
        blockTitle: "Main Content Container",
        description: "Where the article and page content go.",
        appender: "Drop the content for this container here",
    },
});

function edit() {
    const blockProps = useBlockProps({ className: "pjax-main" });

    return (
        <div {...blockProps}>
            <InnerBlocks
                renderAppender={InnerBlocks.ButtonBlockAppender}
                placeholder={lang.appender}
            />
        </div>
    );
}

function save() {
    return <InnerBlocks.Content />;
}

export default function pjaxMainBlock() {
    registerBlockType("sakurairo/pjax-main", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "align-pull-right",
        category: "sakurairo-layout",
        supports: {
            html: false,
            reusable: false,
            multiple: false,
        },
        edit,
        save,
    });
}
