import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks, InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, ToggleControl } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "栏目容器",
        description: "把侧栏里若干块包成一栏；开启吸附后这一栏会停在视口里。",
        settingPanel: "栏目设置",
        stickyLabel: "整栏吸附",
        stickyHelp: "开启后滚到正文时这一栏停在视口里；不开启就随正文滚走。",
        appender: "把这一栏要装的内容放进来",
    },
    "zh-TW": {
        blockTitle: "欄目容器",
        description: "把側欄裡若干區塊包成一欄；開啟吸附後這一欄會停在視口裡。",
        settingPanel: "欄目設定",
        stickyLabel: "整欄吸附",
        stickyHelp: "開啟後滾到正文時這一欄停在視口裡；不開啟就隨正文滾走。",
        appender: "把這一欄要裝的內容放進來",
    },
    ja: {
        blockTitle: "カラムコンテナ",
        description:
            "サイドバーの複数ブロックを 1 つのカラムにまとめます。追従を有効にすると表示されたままになります。",
        settingPanel: "カラム設定",
        stickyLabel: "カラムを追従",
        stickyHelp:
            "有効にすると本文までスクロールしてもこのカラムは表示されたままになります。",
        appender: "このカラムに入れるブロックを置いてください",
    },
    en: {
        blockTitle: "Column Container",
        description:
            "Groups sidebar blocks into one column; enable sticky to keep it in the viewport.",
        settingPanel: "Column settings",
        stickyLabel: "Sticky column",
        stickyHelp:
            "When enabled this column stays in the viewport once you scroll past the article; otherwise it scrolls away.",
        appender: "Drop the blocks for this column here",
    },
});

function edit({ attributes, setAttributes }) {
    const { sticky } = attributes;
    const blockProps = useBlockProps({
        className: `iro-widget-tools-column${sticky ? " sticky" : ""}`,
    });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.settingPanel} initialOpen={true}>
                    <ToggleControl
                        label={lang.stickyLabel}
                        checked={sticky}
                        onChange={(val) => setAttributes({ sticky: val })}
                        help={lang.stickyHelp}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <InnerBlocks
                    renderAppender={InnerBlocks.ButtonBlockAppender}
                    placeholder={lang.appender}
                />
            </div>
        </Fragment>
    );
}

// 动态块：容器元素由前台 PHP 输出（frontend/components/block/widgets/column.php），
// 内容里只留内部块，所以改吸附/圆角这类外观不需要重存文章
function save() {
    return <InnerBlocks.Content />;
}

// 旧版本把容器元素一起存进了内容里，保留旧 markup 让已有内容能自动升级
const deprecated = [
    {
        attributes: { sticky: { type: "boolean", default: false } },
        save({ attributes }) {
            const { sticky } = attributes;
            const blockProps = useBlockProps.save({
                className: `iro-column${sticky ? " sticky" : ""}`,
            });

            return (
                <div {...blockProps}>
                    <InnerBlocks.Content />
                </div>
            );
        },
    },
];

export default function columnBlock() {
    registerBlockType("sakurairo/column", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "columns",
        category: "sakurairo-tools",
        attributes: {
            sticky: { type: "boolean", default: false },
        },
        supports: {
            html: false,
            reusable: false,
        },
        edit,
        save,
        deprecated,
    });
}
