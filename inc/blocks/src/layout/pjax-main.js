import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
    InnerBlocks,
    store as blockEditorStore,
    useBlockProps,
} from "@wordpress/block-editor";
import { select, useDispatch, useSelect } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "页面路由容器",
        description: "页头与页面内容的位置，整块随路由替换。",
    },
    "zh-TW": {
        blockTitle: "頁面路由容器",
        description: "頁首與頁面內容的位置，整塊隨路由替換。",
    },
    ja: {
        blockTitle: "ページルートコンテナ",
        description:
            "ヘッダーとページ内容の位置。ルートごとに丸ごと差し替わり。",
    },
    en: {
        blockTitle: "Route container",
        description:
            "Holds the page header and the page content.",
    },
});

const HEADER = "sakurairo/route-header";
const CONTENT = "sakurairo/content-layout";

function edit({ clientId }) {
    const blockProps = useBlockProps({ className: "pjax-main" });
    const { headerCount, contentCount, innerCount } = useSelect(
        (selectStore) => {
            const inner = selectStore(blockEditorStore).getBlocks(clientId);
            return {
                headerCount: inner.filter((block) => block.name === HEADER)
                    .length,
                contentCount: inner.filter((block) => block.name === CONTENT)
                    .length,
                innerCount: inner.length,
            };
        },
        [clientId],
    );
    const { insertBlock, removeBlock } = useDispatch(blockEditorStore);

    // 结构锁死：页头与页面内容各一份，缺了就补、多了就删。插入一律不改选区，
    // 焦点留在路由容器上；新建的块要等编辑器把插入这件事收尾之后再写
    useEffect(() => {
        if (headerCount === 1 && contentCount === 1 && innerCount === 2) {
            return undefined;
        }
        const timer = setTimeout(() => {
            const inner = select(blockEditorStore).getBlocks(clientId);
            inner
                .filter((block) => block.name === HEADER)
                .slice(1)
                .forEach((block) => removeBlock(block.clientId, false));
            inner
                .filter((block) => block.name === CONTENT)
                .slice(1)
                .forEach((block) => removeBlock(block.clientId, false));
            if (headerCount === 0) {
                insertBlock(createBlock(HEADER), 0, clientId, false);
            }
            if (contentCount === 0) {
                insertBlock(createBlock(CONTENT), 1, clientId, false);
            }
        }, 0);
        return () => clearTimeout(timer);
    }, [
        clientId,
        headerCount,
        contentCount,
        innerCount,
        insertBlock,
        removeBlock,
    ]);

    return (
        <div {...blockProps}>
            <InnerBlocks
                allowedBlocks={[HEADER, CONTENT]}
                renderAppender={false}
            />
        </div>
    );
}

// 页头与内容按路由替换
function save() {
    return <InnerBlocks.Content />;
}

export default function routeContainerBlock() {
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
        attributes: {
            lock: { type: "object", default: { remove: true } },
        },
        edit,
        save,
    });
}
