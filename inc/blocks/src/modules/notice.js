import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import {useBlockProps,BlockControls,RichText,} from '@wordpress/block-editor';
import {ToolbarGroup,ToolbarDropdownMenu,} from '@wordpress/components';
import { Fragment } from '@wordpress/element';

let lang = {}

switch ((iroBlockEditor.language || window.navigator.language || "zh-CN").replace("_", "-")) {
    case "zh-CN":
    case "zh-Hans":
        lang = {
            blockTitle: "提示块",
            typeTitle: "提示类型",
            typeLabel: "类型",
            placeholder: "此处输入内容..."
        };
        break;
    case "zh-TW":
    case "zh-HK":
    case "zh-MO":
        lang = {
            blockTitle: "提示區塊",
            typeTitle: "提示類型",
            typeLabel: "類型",
            placeholder: "此處輸入內容..."
        };
        break;
    case "ja":
    case "ja-JP":
        lang = {
            blockTitle: "ヒントブロック",
            typeTitle: "ヒントタイプ",
            typeLabel: "タイプ",
            placeholder: "ここに内容を入力..."
        };
        break;
    default:
        lang = {
            blockTitle: "Callout Block",
            typeTitle: "Callout Type",
            typeLabel: "Type",
            placeholder: "Enter content here..."
        };
}

const TYPES = {
	task: {
		label: '任务提示',
		icon: '<i class="fa-solid fa-clipboard-list"></i>',
		className: 'task',
	},
	warning: {
		label: '警告提示',
		icon: '<i class="fa-solid fa-triangle-exclamation"></i>',
		className: 'warning',
	},
	noway: {
		label: '禁止提示',
		icon: '<i class="fa-solid fa-square-xmark"></i>',
		className: 'noway',
	},
	buy: {
		label: '允许提示',
		icon: '<i class="fa-solid fa-square-check"></i>',
		className: 'buy',
	},
};

function edit({ attributes, setAttributes }) {
	const { content, type } = attributes;
	const blockProps = useBlockProps();
	const typeInfo = TYPES[type];

	return (
		<Fragment>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon="admin-generic"
						label={lang.typeTitle}
						controls={Object.entries(TYPES).map(([value, { label }]) => ({
							title: label,
							icon: false,
							onClick: () => setAttributes({ type: value }),
							isActive: type === value,
						}))}
					/>
				</ToolbarGroup>
			</BlockControls>

			<div {...blockProps} className={`shortcodestyle ${typeInfo.className}`}>
				<span dangerouslySetInnerHTML={{ __html: typeInfo.icon }} />
				<RichText
					tagName="span"
					value={content}
					onChange={(newContent) => setAttributes({ content: newContent })}
					placeholder={lang.placeholder}
				/>
			</div>
		</Fragment>
	);
}

export default function noticeBlock(){
	registerBlockType('sakurairo/notice-block', {
		title: lang.blockTitle,
		description: '',
		icon: 'format-status',
		category: 'common',
		attributes: {
			content: {
				type: 'string',
				source: 'text',
			},
			type: {
				type: 'string',
				default: 'task',
			},
		},
		edit,
		save({ attributes }) {
			const { content, type } = attributes;
			return `[${type}]${content}[/${type}]`;
		},
	});
}