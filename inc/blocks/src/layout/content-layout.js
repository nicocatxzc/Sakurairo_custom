import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
    InnerBlocks,
    store as blockEditorStore,
    useBlockProps,
} from "@wordpress/block-editor";
import { select, useDispatch, useSelect } from "@wordpress/data";
import { Fragment, useEffect } from "@wordpress/element";
import createI18n from "../i18n";
import { MAX_SIDE_BARS, SIDE_BAR, useSideBarCount } from "./sidebar-budget";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "页面内容容器",
        description: "页面正文与随路由切换的侧栏，横向排布。",
        appender: "放入侧栏容器",
    },
    "zh-TW": {
        blockTitle: "頁面內容容器",
        description: "頁面正文與隨路由切換的側欄，橫向排列。",
        appender: "放入側欄容器",
    },
    ja: {
        blockTitle: "ページコンテンツコンテナ",
        description:
            "ページ本文とルートごとに切り替わるサイドバーを横に並べます。",
        appender: "サイドバーコンテナを配置",
    },
    en: {
        blockTitle: "Page content container",
        description:
            "Lays the page body and the route-specific sidebars out in a row.",
        appender: "Drop a sidebar container here",
    },
});

const BODY = "sakurairo/route-content";

function edit({ clientId }) {
    const blockProps = useBlockProps({
        className: "layout-grid route-content-layout",
    });
    const { bodyCount, sideBarCount } = useSelect(
        (selectStore) => {
            const inner = selectStore(blockEditorStore).getBlocks(clientId);
            return {
                bodyCount: inner.filter((block) => block.name === BODY).length,
                sideBarCount: inner.filter(
                    (block) => block.name === SIDE_BAR,
                ).length,
            };
        },
        [clientId],
    );
    const totalSideBars = useSideBarCount();
    const { insertBlock, removeBlock } = useDispatch(blockEditorStore);

    // 页面正文容器不允许消失也不允许重复：少了补回第一个位置，多了删掉多余的。
    // 新建的块要等编辑器把插入这件事收尾之后再写
    useEffect(() => {
        if (bodyCount === 1) {
            return undefined;
        }
        const timer = setTimeout(() => {
            const inner = select(blockEditorStore).getBlocks(clientId);
            inner
                .filter((block) => block.name === BODY)
                .slice(1)
                .forEach((block) => removeBlock(block.clientId, false));
            if (bodyCount === 0) {
                insertBlock(createBlock(BODY), 0, clientId, false);
            }
        }, 0);
        return () => clearTimeout(timer);
    }, [clientId, bodyCount, insertBlock, removeBlock]);

    // 侧栏预算与顶层布局共用，够了就不再给入口
    const budgetLeft = MAX_SIDE_BARS - (totalSideBars - sideBarCount);

    return (
        <Fragment>
            <div {...blockProps}>
                <InnerBlocks
                    allowedBlocks={[SIDE_BAR, BODY]}
                    renderAppender={
                        sideBarCount < budgetLeft
                            ? InnerBlocks.ButtonBlockAppender
                            : false
                    }
                    placeholder={lang.appender}
                />
            </div>
        </Fragment>
    );
}

// 动态块：栅格元素由前台 PHP 输出（frontend/components/block/layout/content-layout.php）
function save() {
    return <InnerBlocks.Content />;
}

export default function contentLayoutBlock() {
    registerBlockType("sakurairo/content-layout", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "align-wide",
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
