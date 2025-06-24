import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import {useBlockProps,InspectorControls,BlockControls,MediaUpload,MediaUploadCheck,URLInputButton} from '@wordpress/block-editor';
import {PanelBody,TextControl,ColorPicker,ToolbarGroup,ToolbarButton,Button} from '@wordpress/components';
import { Fragment, RawHTML } from '@wordpress/element';
import { useState } from '@wordpress/element';

const DEFAULT_ICON = 'fa-regular fa-bookmark';
const DEFAULT_COLOR = '#ffffff';
const DEFAULT_TITLE = 'untitled';

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
								<ToolbarButton icon="format-image" label="选择图片" onClick={open} />
							)}
						/>
					</MediaUploadCheck>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<PanelBody title="ShowCard 设置" initialOpen={true}>
					<TextControl
						label="FontAwesome 图标类名"
						value={icon}
						onChange={(val) => setAttributes({ icon: val })}
						help="例如 fa-solid fa-book"
					/>
					<TextControl
						label="标题"
						value={title}
						onChange={(val) => setAttributes({ title: val })}
					/>
					<TextControl
						label="图片链接（可选）"
						value={img}
						onChange={(val) => setAttributes({ img: val })}
					/>
					<p><strong>图标颜色与按钮文字颜色</strong></p>
					<ColorPicker
						color={color}
						onChangeComplete={(val) => setAttributes({ color: val.hex })}
						disableAlpha
					/>
					<p><strong>跳转链接</strong></p>
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
		title: '展示卡片',
		icon: 'id-alt',
		category: 'common',
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
