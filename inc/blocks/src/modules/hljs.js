import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import {useBlockProps,BlockControls,PlainText,} from '@wordpress/block-editor';
import {ToolbarGroup,ToolbarDropdownMenu,} from '@wordpress/components';
import { Fragment } from '@wordpress/element';

let lang = {}

switch ((iroBlockEditor.language || window.navigator.language || "zh-CN").replace("_", "-")) {
    case "zh-CN":
    case "zh-Hans":
        lang = {
            hljsTitle: "高亮语言设置",
            hljsLabel: "高亮显示遵循的语法",
            hljsPlaceholder: "此处编写代码...",
			hljsAuto: "自动识别"
        };
        break;
    case "zh-TW":
    case "zh-HK":
    case "zh-MO":
        lang = {
            hljsTitle: "高亮語言設置",
            hljsLabel: "高亮顯示遵循的語法",
            hljsPlaceholder: "此處編寫代碼...",
			hljsAuto: "自動識別"
        };
        break;
    case "ja":
    case "ja-JP":
        lang = {
            hljsTitle: "シンタックスハイライト設定",
            hljsLabel: "ハイライト対象の言語文法",
            hljsPlaceholder: "コードを入力...",
			hljsAuto: "自動識別"
        };
        break;
    default:
        lang = {
            hljsTitle: "Syntax Highlighting Settings",
            hljsLabel: "Language grammar for highlighting",
            hljsPlaceholder: "Enter your code here...",
			hljsAuto: "Auto Detect"
        };
}

const languages = [
	{ label: lang.hljsAuto || "Auto Detect", value: '' },
	{ label: 'HTML', value: 'html' },
	{ label: 'CSS', value: 'css' },
    { label: 'JavaScript', value: 'javascript' },
    { label: 'TypeScript', value: 'typescript' },
	{ label: 'PHP', value: 'php' },
	{ label: 'SCSS', value: 'scss' },
    { label: 'Vue', value: 'vue' },
    { label: 'React', value: 'jsx' },
    { label: 'Python', value: 'python' },
    { label: 'Java', value: 'java' },
	{ label: 'JSON', value: 'json' },
	{ label: 'Dart', value: 'dart' },
    { label: 'C', value: 'c' },
    { label: 'C++', value: 'cpp' },
    { label: 'C#', value: 'csharp' },
    { label: 'Go', value: 'go' },
	{ label: 'Lua', value: 'lua' },
    { label: 'Swift', value: 'swift' },
    { label: 'Kotlin', value: 'kotlin' },
    { label: 'Ruby', value: 'ruby' },
    { label: 'Rust', value: 'rust' },
	{ label: 'YAML', value: 'yaml' },
    { label: 'TOML', value: 'toml' },
    { label: 'INI', value: 'ini' },
    { label: 'SQL', value: 'sql' },
    { label: 'XML', value: 'xml' },
	{ label: 'Markdown', value: 'markdown' },
];

export default function hljsSupport() {
	function CodeEdit({ attributes, setAttributes }) {
		const { content, language } = attributes;
		const blockProps = useBlockProps();

		return (
			<Fragment>
				<BlockControls>
					<ToolbarGroup>
						<ToolbarDropdownMenu
							label={lang.hljsTitle}
							controls={languages.map(({ value, label }) => ({
								title: label,
								icon: false,
								onClick: () => setAttributes({ language: value }),
								isActive: language === value,
							}))}
						/>
					</ToolbarGroup>
				</BlockControls>

				<pre {...blockProps}>
					<code className={language ? `language-${language}` : ''}>
						<PlainText
							value={content}
							onChange={(newContent) => setAttributes({ content: newContent })}
							placeholder={lang.hljsPlaceholder}
						/>
					</code>
				</pre>
			</Fragment>
		);
	}

	const extendCoreCodeBlock = (settings) => {
		if (settings.name !== 'core/code') {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				language: {
					type: 'string',
					default: '',
				},
			},
			edit: CodeEdit,
		};
	};

	addFilter(
		'blocks.registerBlockType',
		'sakurairo/code-language-support',
		extendCoreCodeBlock
	);
}