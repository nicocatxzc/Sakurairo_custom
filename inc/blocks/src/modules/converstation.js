import { registerBlockType } from "@wordpress/blocks";
import {
    useBlockProps,
    BlockControls,
    RichText,
    MediaUpload,
    MediaUploadCheck,
} from "@wordpress/block-editor";
import {
    ToolbarGroup,
    ToolbarButton,
    TextControl,
} from "@wordpress/components";
import { Fragment, createElement } from "@wordpress/element";
import createI18n from "../i18n";

let lang = createI18n({
    "zh-CN": {
        blockTitle: "对话块",
        imageLabel: "设置头像",
        directionLabel: "切换方向",
        placeholder: "请输入对话内容…",
    },
    "zh-TW": {
        blockTitle: "對話區塊",
        imageLabel: "設定大頭貼",
        directionLabel: "切換方向",
        placeholder: "請輸入對話內容…",
    },
    ja: {
        blockTitle: "会話ブロック",
        imageLabel: "アバター設定",
        directionLabel: "方向切替",
        placeholder: "ここに会話内容を入力…",
    },
    en: {
        blockTitle: "Conversations Block",
        imageLabel: "Set Avatar",
        directionLabel: "Toggle Direction",
        placeholder: "Enter conversation text…",
    },
});

export default function conversationBlock() {
    function edit({ attributes, setAttributes }) {
        const { avatar, direction, content, isExample } = attributes;

        if (isExample) {
            return (
                <img
                    src="https://docs.fuukei.org/short-code/dis.png"
                    alt="预览"
                    style={{ width: "100%", height: "auto", display: "block" }}
                />
            );
        }

        const blockProps = useBlockProps({
            className: "conversations-code",
            style: {
                display: "flex",
                flexDirection: direction,
            },
        });
        // 切换方向
        const toggleDirection = () => {
            setAttributes({
                direction: direction === "row" ? "row-reverse" : "row",
            });
        };
        return (
            <Fragment>
                <BlockControls>
                    <ToolbarGroup>
                        <MediaUploadCheck>
                            <MediaUpload
                                onSelect={(media) =>
                                    setAttributes({ avatar: media.url })
                                }
                                allowedTypes={["image"]}
                                value={avatar}
                                render={({ open }) => (
                                    <ToolbarButton
                                        icon="format-image"
                                        label={lang.imageLabel}
                                        onClick={open}
                                    />
                                )}
                            />
                        </MediaUploadCheck>

                        <ToolbarButton
                            icon={
                                direction === "row"
                                    ? "arrow-right-alt"
                                    : "arrow-left-alt"
                            }
                            label={lang.directionLabel}
                            onClick={toggleDirection}
                        />
                    </ToolbarGroup>
                </BlockControls>
                <div
                    {...blockProps}
                    style={{ display: "flex", flexDirection: direction }}
                >
                    {avatar ? (
                        <img src={avatar} alt="" />
                    ) : (
                        <TextControl
                            placeholder={lang.imageLabel + " URL…"}
                            value={avatar}
                            onChange={(url) => setAttributes({ avatar: url })}
                        />
                    )}
                    <RichText
                        tagName="div"
                        className="conversations-code-text"
                        placeholder={lang.placeholder}
                        value={content}
                        onChange={(value) => setAttributes({ content: value })}
                    />
                </div>
            </Fragment>
        );
    }

    registerBlockType("sakurairo/conversation", {
        apiVersion: 2,
        title: lang.blockTitle,
        icon: createElement("i", { className: "fa-regular fa-comments" }),
        category: "sakurairo",
        attributes: {
            avatar: {
                type: "string",
                default: "",
            },
            direction: {
                type: "string",
                default: "row",
            },
            content: {
                type: "string",
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
