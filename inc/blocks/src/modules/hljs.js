import { addFilter } from "@wordpress/hooks";
import {
    useBlockProps,
    BlockControls,
    PlainText,
} from "@wordpress/block-editor";
import { ToolbarGroup, DropdownMenu } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import { chevronDown } from "@wordpress/icons";
import createI18n from "../i18n";
// 支持语言表
import { HLJS_LANGUAGES } from "../../../../frontend/hljs-languages";

let lang = createI18n({
    "zh-CN": {
        hljsTitle: "高亮语言设置",
        hljsLabel: "高亮显示遵循的语法",
        hljsPlaceholder: "此处编写代码...",
        hljsAuto: "自动识别",
    },
    "zh-TW": {
        hljsTitle: "高亮語言設置",
        hljsLabel: "高亮顯示遵循的語法",
        hljsPlaceholder: "此處編寫代碼...",
        hljsAuto: "自動識別",
    },
    ja: {
        hljsTitle: "シンタックスハイライト設定",
        hljsLabel: "ハイライト対象の言語文法",
        hljsPlaceholder: "コードを入力...",
        hljsAuto: "自動識別",
    },
    en: {
        hljsTitle: "Syntax Highlighting Settings",
        hljsLabel: "Language grammar for highlighting",
        hljsPlaceholder: "Enter your code here...",
        hljsAuto: "Auto Detect",
    },
});

const languages = [
    { label: lang.hljsAuto || "Auto Detect", value: "" },
    ...HLJS_LANGUAGES,
];

export default function hljsSupport() {
    function CodeEdit({ attributes, setAttributes }) {
        const { content, language } = attributes;
        const blockProps = useBlockProps();

        let currentLang = language || "";
        let selectedItem =
            languages.find((l) => l.value === currentLang) || languages[0];
        let labelText = selectedItem.label;

        return (
            <Fragment>
                <BlockControls>
                    <ToolbarGroup>
                        <DropdownMenu
                            icon={chevronDown}
                            label={lang.hljsTitle}
                            text={labelText}
                            controls={languages.map(({ value, label }) => ({
                                title: label,
                                isActive: currentLang === value,
                                onClick: () =>
                                    setAttributes({ language: value }),
                            }))}
                        />
                    </ToolbarGroup>
                </BlockControls>

                <pre {...blockProps}>
                    <code
                        className={currentLang ? `language-${currentLang}` : ""}
                    >
                        <PlainText
                            value={content}
                            onChange={(newContent) =>
                                setAttributes({ content: newContent })
                            }
                            placeholder={lang.hljsPlaceholder}
                        />
                    </code>
                </pre>
            </Fragment>
        );
    }

    const extendCoreCodeBlock = (settings) => {
        if (settings.name !== "core/code") {
            return settings;
        }

        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                language: {
                    type: "string",
                    default: "",
                },
            },
            edit: CodeEdit,
        };
    };

    addFilter(
        "blocks.registerBlockType",
        "sakurairo/code-language-support",
        extendCoreCodeBlock,
    );
}
