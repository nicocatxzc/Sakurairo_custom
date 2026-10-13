import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks, InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, RangeControl } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "动态布局容器",
        description: "把若干块包成一个卡片容器，宽度、圆角与内边距可调。",
        settingPanel: "容器设置",
        widthLabel: "宽度（%）",
        radiusLabel: "圆角",
        paddingYLabel: "上下内边距",
        paddingXLabel: "左右内边距",
        appender: "把要放进容器的块拖到这里",
    },
    "zh-TW": {
        blockTitle: "動態佈局容器",
        description: "把若干區塊包成一個卡片容器，寬度、圓角與內邊距可調。",
        settingPanel: "容器設定",
        widthLabel: "寬度（%）",
        radiusLabel: "圓角",
        paddingYLabel: "上下內邊距",
        paddingXLabel: "左右內邊距",
        appender: "把要放進容器的區塊拖到這裡",
    },
    ja: {
        blockTitle: "動的レイアウトコンテナ",
        description:
            "複数のブロックをまとめるカードコンテナ。幅・角丸・余白を調整できます。",
        settingPanel: "コンテナ設定",
        widthLabel: "幅（%）",
        radiusLabel: "角丸",
        paddingYLabel: "上下の余白",
        paddingXLabel: "左右の余白",
        appender: "このコンテナに入れるブロックを置いてください",
    },
    en: {
        blockTitle: "Dynamic Layout Container",
        description:
            "A card container that groups blocks; width, corner radius and padding are adjustable.",
        settingPanel: "Container settings",
        widthLabel: "Width (%)",
        radiusLabel: "Corner radius",
        paddingYLabel: "Vertical padding",
        paddingXLabel: "Horizontal padding",
        appender: "Drop the blocks for this container here",
    },
});

function edit({ attributes, setAttributes }) {
    const { radius, width, paddingY, paddingX } = attributes;
    const blockProps = useBlockProps({
        className: "iro-widgets-container",
        style: {
            "--iro-widgets-radius": `${radius}rem`,
            "--iro-widgets-width": `${width}%`,
            "--iro-widgets-padding-y": `${paddingY}rem`,
            "--iro-widgets-padding-x": `${paddingX}rem`,
        },
    });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.settingPanel} initialOpen={true}>
                    <RangeControl
                        label={lang.widthLabel}
                        value={width}
                        min={0}
                        max={100}
                        step={1}
                        onChange={(val) => setAttributes({ width: val })}
                    />
                    <RangeControl
                        label={lang.radiusLabel}
                        value={radius}
                        min={0}
                        max={5}
                        step={0.01}
                        onChange={(val) => setAttributes({ radius: val })}
                    />
                    <RangeControl
                        label={lang.paddingYLabel}
                        value={paddingY}
                        min={0}
                        max={5}
                        step={0.01}
                        onChange={(val) => setAttributes({ paddingY: val })}
                    />
                    <RangeControl
                        label={lang.paddingXLabel}
                        value={paddingX}
                        min={0}
                        max={5}
                        step={0.01}
                        onChange={(val) => setAttributes({ paddingX: val })}
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

// 内容里只留内部块，所以改宽度/圆角/内边距不需要重存文章
function save() {
    return <InnerBlocks.Content />;
}

export default function widgetsContainerBlock() {
    registerBlockType("sakurairo/widgets-container", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "welcome-widgets-menus",
        category: "sakurairo-decorate",
        attributes: {
            radius: { type: "number", default: 1 },
            width: { type: "number", default: 65 },
            paddingY: { type: "number", default: 1 },
            paddingX: { type: "number", default: 1 },
        },
        supports: {
            html: false,
            reusable: false,
        },
        edit,
        save,
    });
}
