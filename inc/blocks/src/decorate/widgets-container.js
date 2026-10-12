import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks, InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, RangeControl } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "动态布局容器",
        description: "自带圆角与内边距的卡片容器，宽度和排列交给外层布局。",
        settingPanel: "容器设置",
        radiusLabel: "圆角",
        paddingLabel: "内边距",
        appender: "把要放进容器的块拖到这里",
    },
    "zh-TW": {
        blockTitle: "動態佈局容器",
        description: "自帶圓角與內邊距的卡片容器，寬度與排列交給外層佈局。",
        settingPanel: "容器設定",
        radiusLabel: "圓角",
        paddingLabel: "內邊距",
        appender: "把要放進容器的區塊拖到這裡",
    },
    ja: {
        blockTitle: "動的レイアウトコンテナ",
        description:
            "角丸と内側の余白を持つカードコンテナ。幅や並び順は外側のレイアウトに任せます。",
        settingPanel: "コンテナ設定",
        radiusLabel: "角丸",
        paddingLabel: "内側の余白",
        appender: "このコンテナに入れるブロックを置いてください",
    },
    en: {
        blockTitle: "Dynamic Layout Container",
        description:
            "A card container with its own corner radius and padding; width and arrangement come from the outer layout.",
        settingPanel: "Container settings",
        radiusLabel: "Corner radius",
        paddingLabel: "Padding",
        appender: "Drop the blocks for this container here",
    },
});

function edit({ attributes, setAttributes }) {
    const { radius, padding } = attributes;
    const blockProps = useBlockProps({
        className: "iro-widgets-container",
        style: {
            "--iro-widgets-radius": `${radius}rem`,
            "--iro-widgets-padding": `${padding}rem`,
        },
    });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.settingPanel} initialOpen={true}>
                    <RangeControl
                        label={lang.radiusLabel}
                        value={radius}
                        min={0}
                        max={5}
                        step={0.01}
                        onChange={(val) => setAttributes({ radius: val })}
                    />
                    <RangeControl
                        label={lang.paddingLabel}
                        value={padding}
                        min={0}
                        max={5}
                        step={0.01}
                        onChange={(val) => setAttributes({ padding: val })}
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

// 内容里只留内部块，所以改圆角/内边距不需要重存文章
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
            padding: { type: "number", default: 1 },
        },
        supports: {
            html: false,
            reusable: false,
        },
        edit,
        save,
    });
}
