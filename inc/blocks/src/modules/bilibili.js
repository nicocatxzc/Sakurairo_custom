import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl } from "@wordpress/components";
import { Fragment, createElement } from "@wordpress/element";
import createI18n from "../i18n";

let lang = createI18n({
    "zh-CN": {
        blockTitle: "Bilibili 视频",
        placeholder: "请输入 Bilibili 视频 ID（如 BV1xx、av123456）",
        label: "视频 ID",
        error: "无效的视频 ID，请输入 BV 或 av 编号。",
    },
    "zh-TW": {
        blockTitle: "Bilibili 視頻",
        placeholder: "請輸入 Bilibili 視頻 ID（例如 BV1xx、av123456）",
        label: "視頻 ID",
        error: "無效的視頻 ID，請輸入 BV 或 av 編號。",
    },
    ja: {
        blockTitle: "Bilibili ビデオ",
        placeholder:
            "Bilibiliの動画ID（例：BV1xx、av123456）を入力してください",
        label: "動画 ID",
        error: "無効な動画IDです。BVまたはav形式で入力してください。",
    },
    en: {
        blockTitle: "Bilibili Video",
        placeholder: "Enter Bilibili Video ID (e.g. BV1xx or av123456)",
        label: "Video ID",
        error: "Invalid video ID. Please enter BV or av format.",
    },
});

export default function bilibiliBlock() {
    function edit({ attributes, setAttributes }) {
        const { videoId, isExample } = attributes;

        if (isExample) {
            return (
                <img
                    src="https://docs.fuukei.org/short-code/bvcode.png"
                    alt="预览"
                    style={{ width: "100%", height: "auto", display: "block" }}
                />
            );
        }

        const blockProps = useBlockProps();

        let iframe = null;
        const trimmed = (videoId || "").trim();

        if (/^av\d+$/i.test(trimmed)) {
            const avid = trimmed.replace(/^av/i, "");
            iframe = `https://player.bilibili.com/player.html?avid=${avid}&page=1&autoplay=0&danmaku=0`;
        } else if (/^BV[a-zA-Z0-9]+$/.test(trimmed)) {
            const bvid = trimmed;
            iframe = `https://player.bilibili.com/player.html?bvid=${bvid}&page=1&autoplay=0&danmaku=0`;
        }

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={lang.label}>
                        <TextControl
                            label={lang.label}
                            value={videoId}
                            onChange={(val) => setAttributes({ videoId: val })}
                            placeholder={lang.placeholder}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    {iframe ? (
                        <div
                            style={{
                                position: "relative",
                                padding: "30% 45%",
                            }}
                        >
                            <iframe
                                src={iframe}
                                sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts"
                                allowFullScreen
                                style={{
                                    pointerEvents: "none",
                                    position: "absolute",
                                    width: "100%",
                                    height: "100%",
                                    left: 0,
                                    top: 0,
                                    border: "none",
                                    overflow: "hidden",
                                }}
                            ></iframe>
                        </div>
                    ) : (
                        <TextControl
                            label={lang.label}
                            value={videoId}
                            onChange={(val) => setAttributes({ videoId: val })}
                            placeholder={lang.placeholder}
                            help={videoId ? lang.error : ""}
                        />
                    )}
                </div>
            </Fragment>
        );
    }
    registerBlockType("sakurairo/vbilibili", {
        apiVersion: 2,
        title: lang.blockTitle,
        icon: createElement("i", { className: "fa-brands fa-bilibili" }),
        category: "sakurairo",
        supports: {
            html: false,
        },
        attributes: {
            videoId: {
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
                videoId: "",
                isExample: true,
            },
        },
    });
}
