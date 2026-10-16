import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
    InnerBlocks,
    InspectorControls,
    store as blockEditorStore,
    useBlockProps,
} from "@wordpress/block-editor";
import { PanelBody, RangeControl } from "@wordpress/components";
import { select, useDispatch, useSelect } from "@wordpress/data";
import { Fragment, useEffect } from "@wordpress/element";
import createI18n from "../i18n";
import { MAX_SIDE_BARS, SIDE_BAR, useSideBarCount } from "./sidebar-budget";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "页面布局",
        description: "页面路由容器与两侧栏横向排布，最多三栏。",
        appender: "放入侧栏容器",
        settingPanel: "布局设置",
        columnsLabel: "栏目数",
        columnsHelp: "页面路由容器算一栏，最多三栏；页面内容容器里已用的侧栏也占名额。",
    },
    "zh-TW": {
        blockTitle: "頁面佈局",
        description: "頁面路由容器與兩側欄橫向排列，最多三欄。",
        appender: "放入側欄容器",
        settingPanel: "佈局設定",
        columnsLabel: "欄目數",
        columnsHelp:
            "頁面路由容器算一欄，最多三欄；頁面內容容器裡已用的側欄也佔名額。",
    },
    ja: {
        blockTitle: "ページレイアウト",
        description:
            "ページルートコンテナとサイドバーを横に並べます。最大 3 カラム。",
        appender: "サイドバーコンテナを配置",
        settingPanel: "レイアウト設定",
        columnsLabel: "カラム数",
        columnsHelp:
            "ページルートコンテナを 1 カラムとして数え、最大 3 カラムです。ページコンテンツコンテナで使ったサイドバーも枠を消費します。",
    },
    en: {
        blockTitle: "Page layout",
        description:
            "Lays the route container and the sidebars out in a row, three columns at most.",
        appender: "Drop a sidebar container here",
        settingPanel: "Layout settings",
        columnsLabel: "Columns",
        columnsHelp:
            "The route container counts as one column, three at most; sidebars used inside the content container also count.",
    },
});

const ROUTE = "sakurairo/pjax-main";

// 栏目容器：随路由替换的页面路由容器（固定）与可自由增删的侧栏
const ALLOWED_BLOCKS = [SIDE_BAR, ROUTE];

const MIN_COLUMNS = 1;
const MAX_COLUMNS = 3;

function edit({ clientId }) {
    const blockProps = useBlockProps({ className: "layout-grid" });
    const { columnCount, hasRoute, ownSideBars } = useSelect(
        (selectStore) => {
            const inner = selectStore(blockEditorStore).getBlocks(clientId);
            return {
                columnCount: inner.length,
                hasRoute: inner.some((block) => block.name === ROUTE),
                ownSideBars: inner.filter((block) => block.name === SIDE_BAR)
                    .length,
            };
        },
        [clientId],
    );
    const totalSideBars = useSideBarCount();
    const { insertBlock, insertBlocks, removeBlock } =
        useDispatch(blockEditorStore);

    // 侧栏是整页共用的预算：别处（页面内容容器里）已经占掉的名额要从可用栏数里扣掉
    const maxColumns = Math.min(
        MAX_COLUMNS,
        1 + MAX_SIDE_BARS - (totalSideBars - ownSideBars),
    );

    // 滑块改栏目数：页面路由容器不动，只增减侧栏，少的先补左侧再补右侧，
    // 所以路由容器永远落在栏目中间。所有插入都不改选区，焦点留在布局容器上
    const setColumnCount = (next) => {
        const inner = select(blockEditorStore).getBlocks(clientId);
        const sideBars = inner.filter((block) => block.name === SIDE_BAR);
        const hasRouteNow = inner.some((block) => block.name === ROUTE);
        const wantedSideBars = Math.min(next, maxColumns) - 1;

        sideBars
            .slice(wantedSideBars)
            .forEach((block) => removeBlock(block.clientId, false));

        if (sideBars.length < 1 && wantedSideBars >= 1) {
            insertBlock(createBlock(SIDE_BAR), 0, clientId, false);
        }
        if (sideBars.length < 2 && wantedSideBars >= 2) {
            insertBlock(createBlock(SIDE_BAR), undefined, clientId, false);
        }
        if (!hasRouteNow) {
            insertBlock(
                createBlock(ROUTE),
                wantedSideBars >= 1 ? 1 : 0,
                clientId,
                false,
            );
        }
    };

    // 空布局：拖进来就补齐「侧栏 + 页面路由容器 + 侧栏」；
    // 页面路由容器不允许消失，被删掉就补回中间。新建的块要等编辑器把插入这件事
    // 收尾之后再写，否则这一次插入会被丢掉。侧栏不补——它就是用来给用户删的
    useEffect(() => {
        if (columnCount > 0 && hasRoute) {
            return undefined;
        }
        const timer = setTimeout(() => {
            if (columnCount === 0) {
                insertBlocks(
                    [
                        createBlock(SIDE_BAR),
                        createBlock(ROUTE),
                        createBlock(SIDE_BAR),
                    ],
                    0,
                    clientId,
                    false,
                );
                return;
            }
            insertBlock(
                createBlock(ROUTE),
                Math.min(1, columnCount),
                clientId,
                false,
            );
        }, 0);
        return () => clearTimeout(timer);
    }, [clientId, columnCount, hasRoute, insertBlock, insertBlocks]);

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.settingPanel} initialOpen={true}>
                    <RangeControl
                        label={lang.columnsLabel}
                        value={columnCount}
                        min={MIN_COLUMNS}
                        max={maxColumns}
                        step={1}
                        help={lang.columnsHelp}
                        onChange={setColumnCount}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <InnerBlocks
                    allowedBlocks={ALLOWED_BLOCKS}
                    renderAppender={
                        columnCount < maxColumns
                            ? InnerBlocks.ButtonBlockAppender
                            : false
                    }
                    placeholder={lang.appender}
                />
            </div>
        </Fragment>
    );
}

// 内容里只留内部块，所以改栏目顺序这类版式调整不需要重存页面
function save() {
    return <InnerBlocks.Content />;
}

export default function layoutBlock() {
    registerBlockType("sakurairo/layout", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "layout",
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
