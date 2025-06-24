import { __ } from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps,RichText,BlockControls,InspectorControls} from "@wordpress/block-editor";
import { PanelBody,TextControl} from "@wordpress/components";
import { Fragment,createElement } from "@wordpress/element";

let lang = {};
switch ((iroBlockEditor.language || window.navigator.language || "zh-CN").replace("_","-")) {
	case "zh-CN":
	case "zh-Hans":
		lang = {
			blockTitle: "Bilibili 视频",
			placeholder: "请输入 Bilibili 视频 ID（如 BV1xx、av123456）",
			label: "视频 ID",
			error: "无效的视频 ID，请输入 BV 或 av 编号。",
		};
		break;
	case "zh-TW":
	case "zh-HK":
	case "zh-MO":
		lang = {
			blockTitle: "Bilibili 視頻",
			placeholder: "請輸入 Bilibili 視頻 ID（例如 BV1xx、av123456）",
			label: "視頻 ID",
			error: "無效的視頻 ID，請輸入 BV 或 av 編號。",
		};
		break;
	case "ja":
	case "ja-JP":
		lang = {
			blockTitle: "Bilibili ビデオ",
			placeholder: "Bilibiliの動画ID（例：BV1xx、av123456）を入力してください",
			label: "動画 ID",
			error: "無効な動画IDです。BVまたはav形式で入力してください。",
		};
		break;
	default:
		lang = {
			blockTitle: "Bilibili Video",
			placeholder: "Enter Bilibili Video ID (e.g. BV1xx or av123456)",
			label: "Video ID",
			error: "Invalid video ID. Please enter BV or av format.",
		};
}

export default function bilibiliBlock(){
    function edit({ attributes, setAttributes }){
        const { videoId } = attributes;
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
		                                overflow: "hidden"
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
        title: lang.blockTitle,
        icon: createElement('i', { className: 'fa-brands fa-bilibili' }),
        category: "sakurairo",
        supports: {
            html: false,
        },
        attributes: {
            videoId: {
                type: "string",
            },
        },
        edit,
        save({ attributes }) {
			const id = attributes.videoId?.trim();
			if (!id) return null;

			let src = "";
			if (/^av\d+$/i.test(id)) {
				const avid = id.replace(/^av/i, "");
				src = `https://player.bilibili.com/player.html?avid=${avid}&page=1&autoplay=0&danmaku=0`;
			} else if (/^BV[a-zA-Z0-9]+$/.test(id)) {
				src = `https://player.bilibili.com/player.html?bvid=${id}&page=1&autoplay=0&danmaku=0`;
			}

			if (!src) return null;

			return (
				<div style={{ position: "relative", padding: "30% 45%" }}>
					<iframe
						src={src}
						sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts"
						allowFullScreen
						style={{
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
			);
		},
    });
}
