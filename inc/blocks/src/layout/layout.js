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

const lang = createI18n({
    "zh-CN": {
        blockTitle: "页面布局",
        description: "页面正文与侧栏横向排布，最多三栏。",
        appender: "放入侧栏容器",
        settingPanel: "布局设置",
        columnsLabel: "栏目数",
        columnsHelp: "页面正文算一栏，最多三栏。",
    },
    "zh-TW": {
        blockTitle: "頁面佈局",
        description: "頁面正文與側欄橫向排列，最多三欄。",
        appender: "放入側欄容器",
        settingPanel: "佈局設定",
        columnsLabel: "欄目數",
        columnsHelp: "頁面正文算一欄，最多三欄。",
    },
    ja: {
        blockTitle: "ページレイアウト",
        description: "ページ本文とサイドバーを横に並べます。最大 3 カラム。",
        appender: "サイドバーコンテナを配置",
        settingPanel: "レイアウト設定",
        columnsLabel: "カラム数",
        columnsHelp: "ページ本文を 1 カラムとして数え、最大 3 カラムです。",
    },
    en: {
        blockTitle: "Page layout",
        description: "Lays the page content and sidebars out in a row, three columns at most.",
        appender: "Drop a sidebar container here",
        settingPanel: "Layout settings",
        columnsLabel: "Columns",
        columnsHelp: "The page content counts as one column; three at most.",
    },
});

const SIDE_BAR = "sakurairo/site-side-bar";
const CONTENT = "sakurairo/route-content";

// 栏目容器：页面正文（固定）与侧边栏容器。布局只排它们的先后，正文永远是其中一栏
const ALLOWED_BLOCKS = [SIDE_BAR, CONTENT];

const MIN_COLUMNS = 1;
const MAX_COLUMNS = 3;

function edit({ clientId }) {
    const blockProps = useBlockProps({ className: "layout-grid" });
    const { columnCount, hasContent } = useSelect(
        (selectStore) => {
            const inner = selectStore(blockEditorStore).getBlocks(clientId);
            return {
                columnCount: inner.length,
                hasContent: inner.some((block) => block.name === CONTENT),
            };
        },
        [clientId],
    );
    const { insertBlock, insertBlocks, removeBlock } =
        useDispatch(blockEditorStore);

    // 滑块改栏目数：页面正文不动，多出来的侧栏从末尾收掉、少的先补左侧再补右侧，
    // 所以正文永远落在栏目中间。所有插入都不改选区，焦点留在布局容器上
    const setColumnCount = (next) => {
        const inner = select(blockEditorStore).getBlocks(clientId);
        const sideBars = inner.filter((block) => block.name === SIDE_BAR);
        const hasContentNow = inner.some((block) => block.name === CONTENT);
        const wantedSideBars = next - 1;

        sideBars
            .slice(wantedSideBars)
            .forEach((block) => removeBlock(block.clientId, false));

        if (sideBars.length < 1 && wantedSideBars >= 1) {
            insertBlock(createBlock(SIDE_BAR), 0, clientId, false);
        }
        if (sideBars.length < 2 && wantedSideBars >= 2) {
            insertBlock(createBlock(SIDE_BAR), undefined, clientId, false);
        }
        if (!hasContentNow) {
            insertBlock(
                createBlock(CONTENT),
                wantedSideBars >= 1 ? 1 : 0,
                clientId,
                false,
            );
        }
    };

    // 页面正文不允许消失：被删掉就补回来。新建的块要等编辑器把插入这件事收尾之后再写，
    // 否则这一次插入会被丢掉
    useEffect(() => {
        if (columnCount > 0 && hasContent) {
            return undefined;
        }
        const timer = setTimeout(() => {
            if (columnCount === 0) {
                insertBlocks([createBlock(CONTENT)], 0, clientId, false);
                return;
            }
            insertBlock(createBlock(CONTENT), Math.min(1, columnCount), clientId, false);
        }, 0);
        return () => clearTimeout(timer);
    }, [clientId, columnCount, hasContent, insertBlock, insertBlocks]);

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.settingPanel} initialOpen={true}>
                    <RangeControl
                        label={lang.columnsLabel}
                        value={columnCount}
                        min={MIN_COLUMNS}
                        max={MAX_COLUMNS}
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
                        columnCount < MAX_COLUMNS
                            ? InnerBlocks.ButtonBlockAppender
                            : false
                    }
                    placeholder={lang.appender}
                />
            </div>
        </Fragment>
    );
}

// 动态块：栅格元素由前台 PHP 输出（frontend/components/block/layout/layout.php），
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
