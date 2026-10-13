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
        blockTitle: "主布局容器",
        description: "控制全站的主要栏目布局样式。",
        appender: "放入栏目容器",
        settingPanel: "布局设置",
        columnsLabel: "栏目数",
    },
    "zh-TW": {
        blockTitle: "主佈局容器",
        description: "控制全站的主要欄目佈局樣式。",
        appender: "放入欄目容器",
        settingPanel: "佈局設定",
        columnsLabel: "欄目數",
    },
    ja: {
        blockTitle: "レイアウトコンテナ",
        description: "サイト全体のカラム構成を決めます。",
        appender: "カラムコンテナを配置",
        settingPanel: "レイアウト設定",
        columnsLabel: "カラム数",
    },
    en: {
        blockTitle: "Layout Container",
        description: "Controls the main column layout of the whole site.",
        appender: "Drop a column container here",
        settingPanel: "Layout settings",
        columnsLabel: "Columns",
    },
});

const SIDE_BAR = "sakurairo/site-side-bar";
const MAIN = "sakurairo/pjax-main";

// 栏目容器：主内容容器与侧边栏容器。布局容器只排它们的先后，不再替它们定轨道
const ALLOWED_BLOCKS = [SIDE_BAR, MAIN];

// 单栏到三栏：跟核心的栏目块一样用一条滑杆切换，而不是在插入器里占三个条目
const MIN_COLUMNS = 1;
const MAX_COLUMNS = 3;

function edit({ clientId }) {
    const blockProps = useBlockProps({ className: "layout-grid" });
    const { columnCount, hasMain } = useSelect(
        (selectStore) => {
            const inner = selectStore(blockEditorStore).getBlocks(clientId);
            return {
                columnCount: inner.length,
                hasMain: inner.some((block) => block.name === MAIN),
            };
        },
        [clientId],
    );
    const { insertBlock, removeBlock } = useDispatch(blockEditorStore);

    // 滑块改栏目数：主内容容器不动，多出来的侧栏从末尾收掉、少的先补左侧再补右侧，
    // 所以正文永远落在栏目中间
    const setColumnCount = (next) => {
        const inner = select(blockEditorStore).getBlocks(clientId);
        const sideBars = inner.filter((block) => block.name === SIDE_BAR);
        const hasMainNow = inner.some((block) => block.name === MAIN);
        const wantedSideBars = next - 1;

        sideBars
            .slice(wantedSideBars)
            .forEach((block) => removeBlock(block.clientId));

        if (sideBars.length < 1 && wantedSideBars >= 1) {
            insertBlock(createBlock(SIDE_BAR), 0, clientId);
        }
        if (sideBars.length < 2 && wantedSideBars >= 2) {
            insertBlock(createBlock(SIDE_BAR), undefined, clientId);
        }
        if (!hasMainNow) {
            insertBlock(
                createBlock(MAIN),
                wantedSideBars >= 1 ? 1 : 0,
                clientId,
            );
        }
    };

    // 主内容容器不允许消失（前台靠 #pjax-main 换页），被删掉就补回来。
    // 新建的块要等编辑器把插入这件事收尾之后再写，否则这一次插入会被丢掉
    useEffect(() => {
        if (hasMain) {
            return undefined;
        }
        const timer = setTimeout(
            () =>
                insertBlock(
                    createBlock(MAIN),
                    Math.min(1, columnCount),
                    clientId,
                ),
            0,
        );
        return () => clearTimeout(timer);
    }, [clientId, columnCount, hasMain, insertBlock]);

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
