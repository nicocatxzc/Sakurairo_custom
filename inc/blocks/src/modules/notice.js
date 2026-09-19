import { registerBlockType } from "@wordpress/blocks";
import {
    useBlockProps,
    BlockControls,
    RichText,
} from "@wordpress/block-editor";
import { ToolbarGroup, ToolbarDropdownMenu } from "@wordpress/components";
import { Fragment, RawHTML } from "@wordpress/element";
import createI18n from "../i18n";

let lang = createI18n({
    "zh-CN": {
        blockTitle: "提示块",
        typeTitle: "提示类型",
        placeholder: "此处输入内容...",
        taskLabel: "任务提示",
        warningLabel: "警告提示",
        nowayLabel: "禁止提示",
        buyLabel: "允许提示",
    },
    "zh-TW": {
        blockTitle: "提示區塊",
        typeTitle: "提示類型",
        placeholder: "此處輸入內容...",
        taskLabel: "任務提示",
        warningLabel: "警告提示",
        nowayLabel: "禁止提示",
        buyLabel: "允許提示",
    },
    ja: {
        blockTitle: "ヒントブロック",
        typeTitle: "ヒントタイプ",
        placeholder: "ここに内容を入力...",
        taskLabel: "タスク",
        warningLabel: "警告",
        nowayLabel: "禁止",
        buyLabel: "許可",
    },
    en: {
        blockTitle: "Callout Block",
        typeTitle: "Callout Type",
        placeholder: "Enter content here...",
        taskLabel: "Task",
        warningLabel: "Warning",
        nowayLabel: "Forbidden",
        buyLabel: "Allowed",
    },
});

const TYPES = {
    task: {
        label: lang.taskLabel,
        // 与前台 notice.php 的图标表保持一致
        icon: "fa-regular fa-clipboard",
        className: "task",
    },
    warning: {
        label: lang.warningLabel,
        icon: "fa-solid fa-warning",
        className: "warning",
    },
    noway: {
        label: lang.nowayLabel,
        icon: "fa-solid fa-square-xmark",
        className: "noway",
    },
    buy: {
        label: lang.buyLabel,
        icon: "fa-solid fa-square-check",
        className: "buy",
    },
};

function edit({ attributes, setAttributes }) {
    const { content, type, isExample } = attributes;

    if (isExample) {
        return [
            <img
                src="https://docs.fuukei.org/short-code/noway.png"
                alt="预览"
                style={{ width: "100%", height: "auto", display: "block" }}
            />,
            <img
                src="https://docs.fuukei.org/short-code/buy.png"
                alt="预览"
                style={{ width: "100%", height: "auto", display: "block" }}
            />,
            <img
                src="https://docs.fuukei.org/short-code/warn.png"
                alt="预览"
                style={{ width: "100%", height: "auto", display: "block" }}
            />,
        ];
    }

    const typeInfo = TYPES[type];

    const blockProps = useBlockProps({
        className: `shortcodestyle ${typeInfo.className}`,
    });

    return (
        <Fragment>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="admin-generic"
                        label={lang.typeTitle}
                        controls={Object.entries(TYPES).map(
                            ([value, { label }]) => ({
                                title: label,
                                icon: false,
                                onClick: () => setAttributes({ type: value }),
                                isActive: type === value,
                            }),
                        )}
                    />
                </ToolbarGroup>
            </BlockControls>

            <div {...blockProps}>
                <span contentEditable={false}>
                    <i className={typeInfo.icon} />
                </span>
                <RichText
                    tagName="span"
                    value={content}
                    onChange={(newContent) =>
                        setAttributes({ content: newContent })
                    }
                    placeholder={lang.placeholder}
                />
            </div>
        </Fragment>
    );
}

export default function noticeBlock() {
    registerBlockType("sakurairo/notice", {
        apiVersion: 2,
        title: lang.blockTitle,
        description: "",
        icon: "format-status",
        category: "sakurairo",
        attributes: {
            content: {
                type: "string",
            },
            type: {
                type: "string",
                default: "task",
            },
            isExample: {
                type: "boolean",
                default: false,
            },
        },
        edit,
        save() {
            return null
        },
        example: {
            attributes: {
                isExample: true,
            },
        },
    });
}
