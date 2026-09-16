import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import { TextControl } from "@wordpress/components";
import { createElement } from "@wordpress/element";
import createI18n from "../i18n";

let lang = createI18n({
    "zh-CN": {
        blockTitle: "Github 仓库卡片",
        fieldLabel: "仓库地址",
        fieldHelp: "示例：author/repo 或 https://github.com/author/repo",
    },
    "zh-TW": {
        blockTitle: "Github 倉庫卡片",
        fieldLabel: "倉庫地址",
        fieldHelp: "範例：author/repo 或 https://github.com/author/repo",
    },
    ja: {
        blockTitle: "GitHub リポジトリカード",
        fieldLabel: "リポジトリURL",
        fieldHelp: "例：author/repo または https://github.com/author/repo",
    },
    en: {
        blockTitle: "GitHub Repository Card",
        fieldLabel: "Repository URL",
        fieldHelp: "Example: author/repo or https://github.com/author/repo",
    },
});

export default function () {
    registerBlockType("sakurairo/ghcard", {
        apiVersion: 2,
        title: lang.blockTitle,
        icon: createElement("i", { className: "fa-brands fa-github" }),
        category: "sakurairo",

        attributes: {
            path: {
                type: "string",
                default: "",
            },
        },

        edit({ attributes, setAttributes }) {
            const { path } = attributes;

            const blockProps = useBlockProps();

            return (
                <div {...blockProps}>
                    <div
                        style={{
                            border: "1px solid #ddd",
                            padding: "16px",
                            borderRadius: "6px",
                            background: "#fff",
                        }}
                    >
                        <div
                            style={{
                                fontWeight: "600",
                                fontSize: "16px",
                                marginBottom: "12px",
                                display: "flex",
                                alignItems: "center",
                                gap: "8px",
                            }}
                        >
                            <i className="fa-brands fa-github"></i>
                            {lang.blockTitle}
                        </div>

                        <TextControl
                            label={lang.fieldLabel}
                            help={lang.fieldHelp}
                            value={path}
                            onChange={(value) => setAttributes({ path: value })}
                        />
                    </div>
                </div>
            );
        },

        save() {
            return null
        },
    });
}
