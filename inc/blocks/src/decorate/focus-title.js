import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { ColorPicker, PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "聚焦文字",
        description: "封面标题那类大号文字，可切换普通文字与 Mashiro 特效文字。",
        settingPanel: "文字设置",
        effectLabel: "特效文字",
        effectHelp: "开启后使用 Mashiro 的 glitch 抖动效果（悬停时通道分离）。",
        fontLabel: "字体",
        fontHelp: "font-family 值，例如 “Noto Serif SC”，留空则跟随主题。",
        sizeLabel: "大小",
        weightLabel: "字重",
        weightInherit: "默认",
        weightLight: "细体 300",
        weightRegular: "常规 400",
        weightMedium: "中等 500",
        weightBold: "粗体 700",
        weightBlack: "特粗 900",
        colorLabel: "颜色",
        placeholder: "可填写标题文字",
    },
    "zh-TW": {
        blockTitle: "聚焦文字",
        description: "封面標題那類大號文字，可切換一般文字與 Mashiro 特效文字。",
        settingPanel: "文字設定",
        effectLabel: "特效文字",
        effectHelp: "開啟後使用 Mashiro 的 glitch 抖動效果（滑鼠移入時通道分離）。",
        fontLabel: "字體",
        fontHelp: "font-family 值，例如 “Noto Serif SC”，留空則跟隨主題。",
        sizeLabel: "大小",
        weightLabel: "字重",
        weightInherit: "預設",
        weightLight: "細體 300",
        weightRegular: "標準 400",
        weightMedium: "中等 500",
        weightBold: "粗體 700",
        weightBlack: "特粗 900",
        colorLabel: "顏色",
        placeholder: "可填寫標題文字",
    },
    ja: {
        blockTitle: "フォーカス文字",
        description:
            "カバーのタイトルのような大きな文字。通常の文字と Mashiro のエフェクト文字を切り替えられます。",
        settingPanel: "文字設定",
        effectLabel: "エフェクト文字",
        effectHelp: "有効にすると Mashiro のグリッチ効果（ホバーでチャンネルがずれる）を使います。",
        fontLabel: "フォント",
        fontHelp: "font-family の値（例 “Noto Serif SC”）。空ならテーマに従います。",
        sizeLabel: "サイズ",
        weightLabel: "字の太さ",
        weightInherit: "既定",
        weightLight: "細字 300",
        weightRegular: "標準 400",
        weightMedium: "中太 500",
        weightBold: "太字 700",
        weightBlack: "極太 900",
        colorLabel: "色",
        placeholder: "タイトル文字を入力",
    },
    en: {
        blockTitle: "Focus Text",
        description:
            "Large display text like the cover title, switchable between plain text and the Mashiro glitch effect.",
        settingPanel: "Text settings",
        effectLabel: "Glitch effect",
        effectHelp: "Uses Mashiro's glitch effect: the colour channels split on hover.",
        fontLabel: "Font",
        fontHelp: "A font-family value such as “Noto Serif SC”; leave empty to follow the theme.",
        sizeLabel: "Size",
        weightLabel: "Font weight",
        weightInherit: "Default",
        weightLight: "Light 300",
        weightRegular: "Regular 400",
        weightMedium: "Medium 500",
        weightBold: "Bold 700",
        weightBlack: "Black 900",
        colorLabel: "Color",
        placeholder: "Title text goes here",
    },
});

function edit({ attributes, setAttributes }) {
    const { text, effect, font, size, weight, color } = attributes;
    const blockProps = useBlockProps({
        className: `cover-title${effect ? " glitch" : ""}`,
        style: {
            fontFamily: font || undefined,
            fontSize: `${size}rem`,
            fontWeight: weight || undefined,
            color,
        },
        "data-text": text,
    });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.settingPanel} initialOpen={true}>
                    <ToggleControl
                        label={lang.effectLabel}
                        checked={effect}
                        onChange={(val) => setAttributes({ effect: val })}
                        help={lang.effectHelp}
                    />
                    <TextControl
                        label={lang.fontLabel}
                        value={font}
                        onChange={(val) => setAttributes({ font: val })}
                        help={lang.fontHelp}
                    />
                    <RangeControl
                        label={lang.sizeLabel}
                        value={size}
                        min={1}
                        max={9}
                        step={0.01}
                        onChange={(val) => setAttributes({ size: val })}
                    />
                    <SelectControl
                        label={lang.weightLabel}
                        value={weight}
                        options={[
                            { label: lang.weightInherit, value: "" },
                            { label: lang.weightLight, value: "300" },
                            { label: lang.weightRegular, value: "400" },
                            { label: lang.weightMedium, value: "500" },
                            { label: lang.weightBold, value: "700" },
                            { label: lang.weightBlack, value: "900" },
                        ]}
                        onChange={(val) => setAttributes({ weight: val })}
                    />
                    <p>
                        <strong>{lang.colorLabel}</strong>
                    </p>
                    <ColorPicker
                        color={color}
                        onChangeComplete={(val) => setAttributes({ color: val.hex })}
                        disableAlpha
                    />
                </PanelBody>
            </InspectorControls>

            <RichText
                tagName="h1"
                {...blockProps}
                value={text}
                onChange={(val) => setAttributes({ text: val })}
                placeholder={lang.placeholder}
            />
        </Fragment>
    );
}

export default function focusTitleBlock() {
    registerBlockType("sakurairo/focus-title", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "heading",
        category: "sakurairo-decorate",
        attributes: {
            text: { type: "string", default: "" },
            effect: { type: "boolean", default: false },
            font: { type: "string", default: "" },
            size: { type: "number", default: 5 },
            weight: { type: "string", default: "" },
            color: { type: "string", default: "#FFF" },
        },
        supports: {
            html: false,
            reusable: false,
        },
        edit,
        save() {
            return null;
        },
    });
}
