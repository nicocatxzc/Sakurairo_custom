import { useBlockProps } from "@wordpress/block-editor";
import createI18n from "./i18n";

const lang = createI18n({
    "zh-CN": {
        hint: "动态区块，内容自动生成",
    },
    "zh-TW": {
        hint: "動態區塊，內容自動生成",
    },
    ja: {
        hint: "動的ブロックです。内容は自動で生成されます",
    },
    en: {
        hint: "Dynamic block; the content is generated automatically",
    },
});

/**
 * 动态块的编辑器占位符
 * @param {{title: string, summary?: string, variant?: string}} props
 */
export default function Placeholder({ title, summary, variant }) {
    const blockProps = useBlockProps({
        className: `iro-block-placeholder${variant ? ` iro-block-placeholder-${variant}` : ""}`,
    });

    return (
        <div {...blockProps} contentEditable={false}>
            <strong>{title}</strong>
            <span>{summary || lang.hint}</span>
        </div>
    );
}
