import { useBlockProps } from "@wordpress/block-editor";
import createI18n from "./i18n";

const lang = createI18n({
    "zh-CN": {
        hint: "动态区块，外观由前台 PHP 渲染",
    },
    "zh-TW": {
        hint: "動態區塊，外觀由前台 PHP 渲染",
    },
    ja: {
        hint: "動的ブロックです。表示はフロント側の PHP が生成します",
    },
    en: {
        hint: "Dynamic block, rendered by PHP on the front end",
    },
});

/**
 * 动态块的编辑器占位符
 * @param {{title: string, summary?: string}} props
 */
export default function Placeholder({ title, summary }) {
    const blockProps = useBlockProps({ className: "iro-block-placeholder" });

    return (
        <div {...blockProps} contentEditable={false}>
            <strong>{title}</strong>
            <span>{summary || lang.hint}</span>
        </div>
    );
}
