import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks, InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, RangeControl, ToggleControl } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "栏目容器",
        description: "把若干块包成一栏，可选整栏背景、圆角与吸附。",
        settingPanel: "栏目设置",
        appearancePanel: "栏目外观",
        stickyLabel: "整栏吸附",
        stickyHelp: "开启后滚到正文时这一栏停在视口里；不开启就随正文滚走。",
        backgroundLabel: "整栏背景",
        backgroundHelp: "开启后这一栏会画上卡片背景，与其他栏目分开。",
        radiusLabel: "圆角",
        appender: "把这一栏要装的内容放进来",
    },
    "zh-TW": {
        blockTitle: "欄目容器",
        description: "把若干區塊包成一欄，可選整欄背景、圓角與吸附。",
        settingPanel: "欄目設定",
        appearancePanel: "欄目外觀",
        stickyLabel: "整欄吸附",
        stickyHelp: "開啟後滾到正文時這一欄停在視口裡；不開啟就隨正文滾走。",
        backgroundLabel: "整欄背景",
        backgroundHelp: "開啟後這一欄會畫上卡片背景，與其他欄目分開。",
        radiusLabel: "圓角",
        appender: "把這一欄要裝的內容放進來",
    },
    ja: {
        blockTitle: "カラムコンテナ",
        description:
            "複数のブロックを 1 つのカラムにまとめます。背景・角丸・追従を選べます。",
        settingPanel: "カラム設定",
        appearancePanel: "カラムの外観",
        stickyLabel: "カラムを追従",
        stickyHelp:
            "有効にすると本文までスクロールしてもこのカラムは表示されたままになります。",
        backgroundLabel: "カラムの背景",
        backgroundHelp: "有効にするとこのカラムにカード背景が付き、他のカラムと分かれます。",
        radiusLabel: "角丸",
        appender: "このカラムに入れるブロックを置いてください",
    },
    en: {
        blockTitle: "Column Container",
        description:
            "Groups blocks into one column, with optional background, corner radius and sticky behaviour.",
        settingPanel: "Column settings",
        appearancePanel: "Column appearance",
        stickyLabel: "Sticky column",
        stickyHelp:
            "When enabled this column stays in the viewport once you scroll past the article; otherwise it scrolls away.",
        backgroundLabel: "Column background",
        backgroundHelp: "Draws a card background behind this column so it reads apart from the others.",
        radiusLabel: "Corner radius",
        appender: "Drop the blocks for this column here",
    },
});

function edit({ attributes, setAttributes }) {
    const { sticky, background, radius } = attributes;
    const blockProps = useBlockProps({
        className: `iro-widget-tools-column${sticky ? " sticky" : ""}${
            background ? " with-background" : ""
        }`,
        style: { "--iro-column-radius": `${radius}rem` },
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
                <PanelBody title={lang.appearancePanel} initialOpen={true}>
                    <ToggleControl
                        label={lang.backgroundLabel}
                        checked={background}
                        onChange={(val) => setAttributes({ background: val })}
                        help={lang.backgroundHelp}
                    />
                    <RangeControl
                        label={lang.radiusLabel}
                        value={radius}
                        onChange={(val) => setAttributes({ radius: val })}
                        min={0}
                        max={2}
                        step={0.01}
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
            background: { type: "boolean", default: true },
            radius: { type: "number", default: 0.6 },
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
