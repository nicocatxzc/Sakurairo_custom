import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks, useBlockProps } from "@wordpress/block-editor";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "吸附容器",
        description:
            "滚到正文后这一块仍然留在视口里；容器外的侧栏内容会照常滚走。",
        appender: "把需要一直可见的块放进来",
    },
    "zh-TW": {
        blockTitle: "吸附容器",
        description:
            "滾到正文後這一塊仍然留在視口裡；容器外的側欄內容會照常滾走。",
        appender: "把需要一直可見的區塊放進來",
    },
    ja: {
        blockTitle: "追従コンテナ",
        description:
            "本文までスクロールしてもこのブロックは表示されたままになります。コンテナ外のサイドバー内容は通常どおり流れます。",
        appender: "常に表示したいブロックを入れてください",
    },
    en: {
        blockTitle: "Sticky Container",
        description:
            "This block stays in the viewport once you scroll past the article; sidebar content outside the container scrolls away.",
        appender: "Drop the blocks that should stay visible",
    },
});

function edit() {
    const blockProps = useBlockProps({ className: "iro-sticky" });

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
    const blockProps = useBlockProps.save({ className: "iro-sticky" });

    return (
        <div {...blockProps}>
            <InnerBlocks.Content />
        </div>
    );
}

export default function stickyBlock() {
    registerBlockType("sakurairo/sticky", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "sticky",
        category: "sakurairo-tools",
        attributes: {},
        supports: {
            html: false,
            reusable: false,
        },
        edit,
        save,
    });
}
