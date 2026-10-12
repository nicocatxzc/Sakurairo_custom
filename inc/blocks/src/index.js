import { setCategories, getCategories } from "@wordpress/blocks";
import domReady from "@wordpress/dom-ready";
// 编辑内容用的块
import hljsSupport from "./editor/hljs";
import noticeBlock from "./editor/notice";
import showcardBlock from "./editor/showcard";
import conversationBlock from "./editor/converstation";
import bilibiliBlock from "./editor/bilibili";
import markdownBlock from "./editor/markdown";
import ghcard from "./editor/ghcard";
// 页面占位模板
import templateBlocks from "./pages/template";
// 推荐放在小工具里的块
import authorBlock from "./tools/author";
import termsBlock from "./tools/terms";
import menuBlock from "./tools/menu";
import tocBlock from "./tools/toc";
import columnBlock from "./tools/column";
// 装饰块
import hitokotoBlock from "./decorate/hitokoto";
import sakuraIconBlock from "./decorate/sakura-icon";
import focusTitleBlock from "./decorate/focus-title";
import typedBlock from "./decorate/typed";
import socialLinksBlock from "./decorate/social-links";
import widgetsContainerBlock from "./decorate/widgets-container";
import "./style.scss";

domReady(() => {
    // 获取已有分类
    const existing = getCategories();

    // 插入分类
    const updated = [
        ...existing.slice(0, 1),
        {
            slug: "sakurairo",
            title: "Sakurairo",
        },
        {
            slug: "sakurairo-tools",
            title: "Sakurairo Tools",
        },
        {
            slug: "sakurairo-decorate",
            title: "Sakurairo Decorate",
        },
        {
            slug: "sakurairo-pages",
            title: "Sakurairo Pages",
        },
        ...existing.slice(1),
    ];

    // 更新分类
    setCategories(updated);
});

export default function initBlocks() {
    try {
        // 编辑内容用的块
        hljsSupport();
        noticeBlock();
        showcardBlock();
        conversationBlock();
        bilibiliBlock();
        markdownBlock();
        ghcard();
        // 页面占位模板
        templateBlocks();
        // 推荐放在小工具里的块
        authorBlock();
        termsBlock();
        menuBlock();
        tocBlock();
        columnBlock();
        // 装饰块
        hitokotoBlock();
        sakuraIconBlock();
        focusTitleBlock();
        typedBlock();
        socialLinksBlock();
        widgetsContainerBlock();
    } catch (error) {
        console.log(`发生错误${error}`);
        console.log(error.stack);
    }
}

initBlocks();
