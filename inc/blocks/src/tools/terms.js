import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls } from "@wordpress/block-editor";
import {
    PanelBody,
    RangeControl,
    SelectControl,
    TextControl,
    ToggleControl,
} from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "分类与标签",
        contentPanel: "内容",
        displayPanel: "显示",
        taxonomyLabel: "类型",
        taxonomyCategory: "分类",
        taxonomyTag: "标签",
        titleLabel: "标题",
        titleHelp: "留空则自动使用分类/标签的名称",
        styleLabel: "样式",
        styleList: "列表",
        styleButton: "按钮",
        showCountLabel: "显示数量",
        limitLabel: "最大显示数量",
    },
    "zh-TW": {
        blockTitle: "分類與標籤",
        contentPanel: "內容",
        displayPanel: "顯示",
        taxonomyLabel: "類型",
        taxonomyCategory: "分類",
        taxonomyTag: "標籤",
        titleLabel: "標題",
        titleHelp: "留空則自動使用分類/標籤的名稱",
        styleLabel: "樣式",
        styleList: "列表",
        styleButton: "按鈕",
        showCountLabel: "顯示數量",
        limitLabel: "最大顯示數量",
    },
    ja: {
        blockTitle: "カテゴリとタグ",
        contentPanel: "内容",
        displayPanel: "表示",
        taxonomyLabel: "種類",
        taxonomyCategory: "カテゴリ",
        taxonomyTag: "タグ",
        titleLabel: "タイトル",
        titleHelp: "空の場合はカテゴリ/タグ名を自動使用",
        styleLabel: "スタイル",
        styleList: "リスト",
        styleButton: "ボタン",
        showCountLabel: "件数を表示",
        limitLabel: "最大表示件数",
    },
    en: {
        blockTitle: "Categories & Tags",
        contentPanel: "Content",
        displayPanel: "Display",
        taxonomyLabel: "Type",
        taxonomyCategory: "Categories",
        taxonomyTag: "Tags",
        titleLabel: "Title",
        titleHelp: "Empty uses the taxonomy name",
        styleLabel: "Style",
        styleList: "List",
        styleButton: "Buttons",
        showCountLabel: "Show count",
        limitLabel: "Max items",
    },
});

function edit({ attributes, setAttributes }) {
    const { taxonomy, title, style, showCount, limit } = attributes;
    const isTag = taxonomy === "post_tag";
    const typeLabel = isTag ? lang.taxonomyTag : lang.taxonomyCategory;
    const styleLabel = style === "button" ? lang.styleButton : lang.styleList;

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.contentPanel} initialOpen={true}>
                    <SelectControl
                        label={lang.taxonomyLabel}
                        value={taxonomy}
                        options={[
                            {
                                label: lang.taxonomyCategory,
                                value: "category",
                            },
                            { label: lang.taxonomyTag, value: "post_tag" },
                        ]}
                        onChange={(val) => setAttributes({ taxonomy: val })}
                    />
                    <TextControl
                        label={lang.titleLabel}
                        value={title}
                        onChange={(val) => setAttributes({ title: val })}
                        help={lang.titleHelp}
                    />
                </PanelBody>
                <PanelBody title={lang.displayPanel} initialOpen={false}>
                    <SelectControl
                        label={lang.styleLabel}
                        value={style}
                        options={[
                            { label: lang.styleList, value: "list" },
                            { label: lang.styleButton, value: "button" },
                        ]}
                        onChange={(val) => setAttributes({ style: val })}
                    />
                    <ToggleControl
                        label={lang.showCountLabel}
                        checked={showCount}
                        onChange={(val) => setAttributes({ showCount: val })}
                    />
                    <RangeControl
                        label={lang.limitLabel}
                        value={limit}
                        min={1}
                        max={100}
                        onChange={(val) => setAttributes({ limit: val })}
                    />
                </PanelBody>
            </InspectorControls>

            <Placeholder
                title={`${lang.blockTitle} · ${typeLabel}`}
                summary={`${styleLabel} · ${lang.limitLabel} ${limit}`}
            />
        </Fragment>
    );
}

export default function termsBlock() {
    registerBlockType("sakurairo/sidebar-terms", {
        apiVersion: 3,
        title: lang.blockTitle,
        icon: "category",
        category: "sakurairo-tools",
        attributes: {
            taxonomy: { type: "string", default: "category" },
            title: { type: "string", default: "" },
            style: { type: "string", default: "list" },
            showCount: { type: "boolean", default: true },
            limit: { type: "number", default: 10 },
        },
        edit,
        save() {
            return null;
        },
    });
}
