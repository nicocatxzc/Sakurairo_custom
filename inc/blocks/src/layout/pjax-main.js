import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
    InnerBlocks,
    store as blockEditorStore,
    useBlockProps,
} from "@wordpress/block-editor";
import { useDispatch, useSelect } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "页面路由容器",
        description: "页头与页面布局的位置，整块随路由替换，结构锁死。",
    },
    "zh-TW": {
        blockTitle: "頁面路由容器",
        description: "頁首與頁面佈局的位置，整塊隨路由替換，結構鎖死。",
    },
    ja: {
        blockTitle: "ページルートコンテナ",
        description:
            "ヘッダーとページレイアウトの位置。ルートごとに丸ごと差し替わり、構造は固定です。",
    },
    en: {
        blockTitle: "Route container",
        description:
            "Holds the page header and the page layout; swapped per route with a locked structure.",
    },
});

const HEADER = "sakurairo/route-header";
const LAYOUT = "sakurairo/layout";

function edit({ clientId }) {
    const blockProps = useBlockProps({ className: "pjax-main" });
    const { hasHeader, hasLayout } = useSelect(
        (selectStore) => {
            const inner = selectStore(blockEditorStore).getBlocks(clientId);
            return {
                hasHeader: inner.some((block) => block.name === HEADER),
                hasLayout: inner.some((block) => block.name === LAYOUT),
            };
        },
        [clientId],
    );
    const { insertBlock } = useDispatch(blockEditorStore);

    // 结构锁死：页头与页面布局都必须存在，缺了就补回来。插入一律不改选区，
    // 焦点留在路由容器上
    useEffect(() => {
        if (hasHeader && hasLayout) {
            return undefined;
        }
        const timer = setTimeout(() => {
            if (!hasHeader) {
                insertBlock(createBlock(HEADER), 0, clientId, false);
            }
            if (!hasLayout) {
                insertBlock(createBlock(LAYOUT), 1, clientId, false);
            }
        }, 0);
        return () => clearTimeout(timer);
    }, [clientId, hasHeader, hasLayout, insertBlock]);

    return (
        <div {...blockProps}>
            <InnerBlocks
                allowedBlocks={[HEADER, LAYOUT]}
                renderAppender={false}
                template={[[HEADER], [LAYOUT]]}
            />
        </div>
    );
}

// 动态块：外壳由前台 PHP 输出（frontend/components/block/layout/pjax-main.php），
// 那里先渲染主题按路由给的页头，再放布局
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
        edit,
        save,
    });
}
