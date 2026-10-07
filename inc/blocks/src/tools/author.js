import { registerBlockType } from "@wordpress/blocks";
import {
    InspectorControls,
    MediaUpload,
    MediaUploadCheck,
} from "@wordpress/block-editor";
import {
    Button,
    PanelBody,
    TextControl,
    ToggleControl,
} from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import createI18n from "../i18n";
import Placeholder from "../placeholder";

const lang = createI18n({
    "zh-CN": {
        blockTitle: "博主信息",
        contentPanel: "内容",
        displayPanel: "显示",
        titleLabel: "标题",
        nameLabel: "名称",
        nameHelp: "留空使用站点标题",
        descriptionLabel: "简介",
        descriptionHelp: "留空使用站点副标题",
        avatarLabel: "头像",
        avatarPick: "选择头像",
        avatarChange: "更换头像",
        avatarClear: "移除头像",
        avatarHelp: "留空使用站点图标",
        showStatsLabel: "显示站点统计",
    },
    "zh-TW": {
        blockTitle: "博主資訊",
        contentPanel: "內容",
        displayPanel: "顯示",
        titleLabel: "標題",
        nameLabel: "名稱",
        nameHelp: "留空使用站點標題",
        descriptionLabel: "簡介",
        descriptionHelp: "留空使用站點副標題",
        avatarLabel: "頭像",
        avatarPick: "選擇頭像",
        avatarChange: "更換頭像",
        avatarClear: "移除頭像",
        avatarHelp: "留空使用站點圖示",
        showStatsLabel: "顯示站點統計",
    },
    ja: {
        blockTitle: "ブログ主情報",
        contentPanel: "内容",
        displayPanel: "表示",
        titleLabel: "タイトル",
        nameLabel: "名前",
        nameHelp: "空の場合はサイトタイトルを使用",
        descriptionLabel: "説明",
        descriptionHelp: "空の場合はキャッチフレーズを使用",
        avatarLabel: "アバター",
        avatarPick: "アバターを選択",
        avatarChange: "アバターを変更",
        avatarClear: "アバターを削除",
        avatarHelp: "空の場合はサイトアイコンを使用",
        showStatsLabel: "サイト統計を表示",
    },
    en: {
        blockTitle: "Author Info",
        contentPanel: "Content",
        displayPanel: "Display",
        titleLabel: "Title",
        nameLabel: "Name",
        nameHelp: "Empty uses the site title",
        descriptionLabel: "Description",
        descriptionHelp: "Empty uses the site tagline",
        avatarLabel: "Avatar",
        avatarPick: "Select avatar",
        avatarChange: "Change avatar",
        avatarClear: "Remove avatar",
        avatarHelp: "Empty uses the site icon",
        showStatsLabel: "Show site stats",
    },
});

function edit({ attributes, setAttributes }) {
    const { title, name, description, avatar, showStats } = attributes;

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={lang.contentPanel} initialOpen={true}>
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) =>
                                setAttributes({ avatar: media.url })
                            }
                            allowedTypes={["image"]}
                            render={({ open }) => (
                                <Button variant="secondary" onClick={open}>
                                    {avatar
                                        ? lang.avatarChange
                                        : lang.avatarPick}
                                </Button>
                            )}
                        />
                    </MediaUploadCheck>
                    {avatar ? (
                        <Button
                            variant="link"
                            isDestructive
                            onClick={() => setAttributes({ avatar: "" })}
                        >
                            {lang.avatarClear}
                        </Button>
                    ) : null}
                    <TextControl
                        label={lang.avatarLabel}
                        value={avatar}
                        onChange={(val) => setAttributes({ avatar: val })}
                        help={lang.avatarHelp}
                    />
                    <TextControl
                        label={lang.titleLabel}
                        value={title}
                        onChange={(val) => setAttributes({ title: val })}
                    />
                    <TextControl
                        label={lang.nameLabel}
                        value={name}
                        onChange={(val) => setAttributes({ name: val })}
                        help={lang.nameHelp}
                    />
                    <TextControl
                        label={lang.descriptionLabel}
                        value={description}
                        onChange={(val) => setAttributes({ description: val })}
                        help={lang.descriptionHelp}
                    />
                </PanelBody>
                <PanelBody title={lang.displayPanel} initialOpen={false}>
                    <ToggleControl
                        label={lang.showStatsLabel}
                        checked={showStats}
                        onChange={(val) => setAttributes({ showStats: val })}
                    />
                </PanelBody>
            </InspectorControls>

            <Placeholder title={lang.blockTitle} />
        </Fragment>
    );
}

export default function authorBlock() {
    registerBlockType("sakurairo/sidebar-author", {
        apiVersion: 3,
        title: lang.blockTitle,
        icon: "admin-users",
        category: "sakurairo-tools",
        attributes: {
            title: { type: "string", default: "" },
            avatar: { type: "string", default: "" },
            name: { type: "string", default: "" },
            description: { type: "string", default: "" },
            showStats: { type: "boolean", default: true },
        },
        edit,
        save() {
            return null;
        },
    });
}
