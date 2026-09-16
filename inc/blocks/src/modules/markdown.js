import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import { createElement } from "@wordpress/element";

function encodeBase64(str) {
    const bytes = new TextEncoder().encode(str); // UTF-8
    let binary = "";
    for (const b of bytes) {
        binary += String.fromCharCode(b);
    }
    return btoa(binary);
}

export default function markdownBlock() {
    registerBlockType("hachimi/markdown", {
        title: "Markdown",
        icon: createElement("i", { className: "fa-brands fa-markdown" }),
        category: "sakurairo",

        attributes: {
            content: {
                type: "string",
                default: "",
            },
        },

        supports: {
            html: false,
            customClassName: false,
            reusable: false,
        },

        edit({ attributes, setAttributes }) {
            const { content } = attributes;
            const blockProps = useBlockProps();

            return createElement(
                "div",
                blockProps,
                createElement("textarea", {
                    value: content,
                    onChange: (e) => setAttributes({ content: e.target.value }),
                    placeholder: "在此输入 Markdown，支持公式 $...$ / $$...$$",
                    style: {
                        width: "100%",
                        minHeight: "200px",
                        fontFamily: "monospace",
                        fontSize: "14px",
                        lineHeight: "1.5",
                        padding: "1em",
                        boxSizing: "border-box",
                        resize: "vertical",
                    },
                }),
            );
        },

        save({ attributes }) {
            const raw = attributes.content || "";
            const encoded = encodeBase64(raw);

            return createElement(
                "div",
                {
                    id: "hachimi-markdown",
                    "data-md": encoded,
                },
                createElement("pre", {}, raw),
            );
        },
    });
}
