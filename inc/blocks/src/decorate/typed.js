import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, TextareaControl, ToggleControl } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "打字机",
        description: "typed.js 打字机；配置写在块上则内联到前台，留空则用主题的全局配置。",
        settingPanel: "打字机设置",
        configLabel: "typed.js 配置",
        configHelp: "JSON 格式，字段参考 typed.js；留空则用「封面 → 封面打字机配置」。",
        markLabel: "带引号",
        markHelp: "在文字两侧加一对引号图标。",
        placeholder: "可填写占位字符串",
    },
    "zh-TW": {
        blockTitle: "打字機",
        description: "typed.js 打字機；設定寫在區塊上會內聯到前台，留空則用主題的全域設定。",
        settingPanel: "打字機設定",
        configLabel: "typed.js 設定",
        configHelp: "JSON 格式，欄位參考 typed.js；留空則用「封面 → 封面打字機設定」。",
        markLabel: "帶引號",
        markHelp: "在文字兩側加一對引號圖示。",
        placeholder: "可填寫佔位字串",
    },
    ja: {
        blockTitle: "タイプライター",
        description:
            "typed.js のタイプライター。ブロックに設定を書くとフロント側に埋め込み、空ならテーマの全体設定を使います。",
        settingPanel: "タイプライター設定",
        configLabel: "typed.js 設定",
        configHelp: "JSON 形式。項目は typed.js を参照。空なら「カバー → タイプライター設定」を使います。",
        markLabel: "引用符を付ける",
        markHelp: "テキストの両側に引用符アイコンを付けます。",
        placeholder: "任意のプレースホルダー文字列",
    },
    en: {
        blockTitle: "Typewriter",
        description:
            "typed.js typewriter; a config set on the block is inlined on the front end, otherwise the theme-wide config is used.",
        settingPanel: "Typewriter settings",
        configLabel: "typed.js config",
        configHelp:
            "JSON, using typed.js fields; leave empty to use “Cover → Cover typewriter config”.",
        markLabel: "Quote marks",
        markHelp: "Add a pair of quote icons around the text.",
        placeholder: "Placeholder text goes here",
    },
});

function edit({ attributes, setAttributes }) {
    const { config, mark, placeholder } = attributes;
    const blockProps = useBlockProps({ className: "typed-container" });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.settingPanel} initialOpen={true}>
                    <TextareaControl
                        label={lang.configLabel}
                        value={config}
                        onChange={(val) => setAttributes({ config: val })}
                        help={lang.configHelp}
                    />
                    <ToggleControl
                        label={lang.markLabel}
                        checked={mark}
                        onChange={(val) => setAttributes({ mark: val })}
                        help={lang.markHelp}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {mark && <i className="fa-icon-solid fa-quote-left"></i>}
                <RichText
                    tagName="span"
                    className="typed"
                    value={placeholder}
                    onChange={(val) => setAttributes({ placeholder: val })}
                    placeholder={lang.placeholder}
                />
                {mark && <i className="fa-icon-solid fa-quote-right"></i>}
            </div>
        </Fragment>
    );
}

export default function typedBlock() {
    registerBlockType("sakurairo/typed", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.description,
        icon: "editor-textcolor",
        category: "sakurairo-decorate",
        attributes: {
            config: { type: "string", default: "" },
            mark: { type: "boolean", default: false },
            placeholder: { type: "string", default: "" },
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
