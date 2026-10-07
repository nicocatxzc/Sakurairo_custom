import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "目录容器",
        contentPanel: "内容",
        titleLabel: "标题",
        titleHelp: "留空则不显示标题",
        note: "目录由正文标题自动生成，前台交给 tocbot 填充；没有标题时不显示。",
    },
    "zh-TW": {
        blockTitle: "目錄容器",
        contentPanel: "內容",
        titleLabel: "標題",
        titleHelp: "留空則不顯示標題",
        note: "目錄由正文標題自動生成，前台交給 tocbot 填充；沒有標題時不顯示。",
    },
    ja: {
        blockTitle: "目次コンテナ",
        contentPanel: "内容",
        titleLabel: "タイトル",
        titleHelp: "空の場合はタイトルを表示しません",
        note: "目次は本文の見出しから自動生成され、フロント側で tocbot が描画します。見出しが無い場合は表示されません。",
    },
    en: {
        blockTitle: "TOC Container",
        contentPanel: "Content",
        titleLabel: "Title",
        titleHelp: "Empty hides the title",
        note: "Built from the post headings and filled by tocbot on the front end; hidden when there are no headings.",
    },
});

function edit({ attributes, setAttributes }) {
    const { title } = attributes;

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.contentPanel} initialOpen={true}>
                    <TextControl
                        label={lang.titleLabel}
                        value={title}
                        onChange={(val) => setAttributes({ title: val })}
                        help={lang.titleHelp}
                    />
                </PanelBody>
            </InspectorControls>

            <Placeholder title={lang.blockTitle} summary={lang.note} />
        </Fragment>
    );
}

export default function tocBlock() {
    registerBlockType("sakurairo/sidebar-toc", {
        apiVersion: 3,
        title: lang.blockTitle,
        description: lang.note,
        icon: "list-view",
        category: "sakurairo-tools",
        attributes: {
            title: { type: "string", default: "" },
        },
        edit,
        save() {
            return null;
        },
    });
}
