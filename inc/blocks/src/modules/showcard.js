import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import {useBlockProps,InspectorControls,BlockControls,MediaUpload,MediaUploadCheck,URLInputButton} from '@wordpress/block-editor';
import {PanelBody,TextControl,ColorPicker,ToolbarGroup,ToolbarButton,Button} from '@wordpress/components';
import { Fragment, RawHTML } from '@wordpress/element';
import { useState } from '@wordpress/element';

let lang = {};
switch ((iroBlockEditor.language || window.navigator.language || "zh-CN").replace("_", "-")) {
    case "zh-CN":
    case "zh-Hans":
        lang = {
            blockTitle: "展示卡片",
            toolbarButtonLabel: "选择图片",
            panelTitle: "展示卡片设置",
            iconClassLabel: "FontAwesome 图标类名",
            iconClassHelp: "例如 fa-solid fa-book",
            titleLabel: "标题",
            imgUrlLabel: "图片链接（可选）",
            iconColorLabel: "图标颜色与按钮文字颜色",
            linkLabel: "跳转链接",
            iconPlaceholder: "输入图标类名...",
            titlePlaceholder: "输入卡片标题..."
        };
        break;
    case "zh-TW":
    case "zh-HK":
    case "zh-MO":
        lang = {
            blockTitle: "展示卡片",
            toolbarButtonLabel: "選擇圖片",
            panelTitle: "展示卡片設定",
            iconClassLabel: "FontAwesome 圖標類名",
            iconClassHelp: "例如 fa-solid fa-book",
            titleLabel: "標題",
            imgUrlLabel: "圖片連結（可選）",
            iconColorLabel: "圖標顏色與按鈕文字顏色",
            linkLabel: "跳轉連結",
            iconPlaceholder: "輸入圖標類名...",
            titlePlaceholder: "輸入卡片標題..."
        };
        break;
    case "ja":
    case "ja-JP":
        lang = {
            blockTitle: "カード表示",
            toolbarButtonLabel: "画像を選択",
            panelTitle: "カード表示設定",
            iconClassLabel: "FontAwesomeアイコンクラス",
            iconClassHelp: "例: fa-solid fa-book",
            titleLabel: "タイトル",
            imgUrlLabel: "画像リンク（任意）",
            iconColorLabel: "アイコンとボタンテキストの色",
            linkLabel: "リンク",
            iconPlaceholder: "アイコンクラスを入力...",
            titlePlaceholder: "カードタイトルを入力..."
        };
        break;
    default:
        lang = {
            blockTitle: "ShowCard",
            toolbarButtonLabel: "Select Image",
            panelTitle: "ShowCard Settings",
            iconClassLabel: "FontAwesome Icon Classes",
            iconClassHelp: "e.g., fa-solid fa-book",
            titleLabel: "Title",
            imgUrlLabel: "Image URL (Optional)",
            iconColorLabel: "Icon & Button Text Color",
            linkLabel: "Link",
            iconPlaceholder: "Enter icon classes...",
            titlePlaceholder: "Enter card title..."
        };
}

const DEFAULT_ICON = 'fa-regular fa-bookmark';
const DEFAULT_COLOR = '#ffffff';
const DEFAULT_TITLE = lang.titlePlaceholder;

function edit({ attributes, setAttributes }) {
	const { icon, title, img, color, link } = attributes;
	const blockProps = useBlockProps();

	const onSelectImage = (media) => {
		setAttributes({ img: media.url });
	};

	return (
		<Fragment>
			<BlockControls>
				<ToolbarGroup>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={onSelectImage}
							allowedTypes={['image']}
							render={({ open }) => (
								<ToolbarButton icon="format-image" label={lang.toolbarButtonLabel}  onClick={open} />
							)}
						/>
					</MediaUploadCheck>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={lang.panelTitle} initialOpen={true}>
					<TextControl
						label={lang.iconClassLabel}
						value={icon}
						onChange={(val) => setAttributes({ icon: val })}
						help={lang.iconClassHelp}
					/>
					<TextControl
						label={lang.titleLabel}
						value={title}
						onChange={(val) => setAttributes({ title: val })}
					/>
					<TextControl
						label={lang.imgUrlLabel}
						value={img}
						onChange={(val) => setAttributes({ img: val })}
					/>
					<p><strong>{lang.iconColorLabel}</strong></p>
					<ColorPicker
						color={color}
						onChangeComplete={(val) => setAttributes({ color: val.hex })}
						disableAlpha
					/>
					<p><strong>{lang.linkLabel}</strong></p>
					<URLInputButton
						url={link}
						onChange={(newUrl) => setAttributes({ link: newUrl })}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps} className="showcard">
				<div
					className="img"
					style={{
						background: img
							? `url(${img}) center center / cover no-repeat`
							: '#ccc',
					}}
				>
					<a href={link}>
						<button
							className="showcard-button"
							style={{ color: color }}
						>
							<i className="fa-solid fa-angle-right"></i>
						</button>
					</a>
				</div>
				<div className="icon-title">
					<RawHTML>
						{`<i class="${icon}" style="color:${color} !important;"></i>`}
					</RawHTML>
					<span className="title">{title}</span>
				</div>
			</div>
		</Fragment>
	);
}

export default function showcardBlock() {
	registerBlockType('sakurairo/showcard-block', {
		title: lang.blockTitle,
		icon: 'id-alt',
		category: 'sakurairo',
		attributes: {
			icon: {
				type: 'string',
				default: DEFAULT_ICON,
			},
			title: {
				type: 'string',
				default: DEFAULT_TITLE,
			},
			img: {
				type: 'string',
				default: '',
			},
			color: {
				type: 'string',
				default: DEFAULT_COLOR,
			},
			link: {
				type: 'string',
				default: '',
			},
		},
		edit,
		save({ attributes }) {
			const { icon, title, img, color, link } = attributes;
			return (
				<div className="showcard">
					<div
						className="img"
						style={{
							background: `url(${img}) center center / cover no-repeat`,
						}}
					>
						<a href={link}>
							<button
								className="showcard-button"
								style={{ color: `${color} !important` }}
							>
								<i className="fa-solid fa-angle-right"></i>
							</button>
						</a>
					</div>
					<div className="icon-title">
						<RawHTML>
							{`<i class="${icon}" style="color:${color} !important;"></i>`}
						</RawHTML>
						<span className="title">{title}</span>
					</div>
				</div>
			);
		},
	});
}
