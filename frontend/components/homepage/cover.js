import {
    applyExtractedColor,
    clearExtractedColor,
    loadColorFromUrl,
} from "../../app/utils/themeColor";

let typedInstance = null;
let coverObjectUrl = null;
let coverRequestId = 0;

function isHomePage() {
    return !!document.querySelector(".page-home");
}

function pickCoverUrl() {
    const config = _iro?.config ?? {};
    const isMobile = window.matchMedia("(max-width: 860px)").matches;
    const url = isMobile ? config.cover_random_pic_url_mb : config.cover_random_pic_url_pc;
    return url || config.cover_random_pic_url_pc || config.cover_random_pic_url_mb || "";
}

function withCacheBuster(url) {
    if (!url) {
        return "";
    }
    return url + (url.includes("?") ? "&" : "?") + "iro_cover=" + Math.random().toString(36).slice(2);
}

function releaseCoverObjectUrl() {
    if (coverObjectUrl) {
        URL.revokeObjectURL(coverObjectUrl);
        coverObjectUrl = null;
    }
}

// 封面变量写在根元素上：开启 cover_as_background 时封面是 body 的背景图，
// 必须由 :root 向下级联，写在 .homepage-cover 上不会影响 body。
function coverRoot() {
    return document.documentElement;
}

// 清除上一页写入的内联变量，保证 pjax 返回时恢复样式表默认值
function resetCover() {
    coverRequestId += 1;
    clearExtractedColor();
    releaseCoverObjectUrl();
    coverRoot().style.removeProperty("--cover-background-img-pc");
    coverRoot().style.removeProperty("--cover-background-img-mb");
}

// 重新拉取一张随机封面，并保证取色与显示来自同一张图
async function refreshCover(extract) {
    const requestId = (coverRequestId += 1);
    const url = withCacheBuster(pickCoverUrl());
    if (!url) {
        return;
    }
    const { color, objectUrl } = await loadColorFromUrl(url);
    if (requestId !== coverRequestId) {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
        return;
    }
    if (objectUrl) {
        releaseCoverObjectUrl();
        coverObjectUrl = objectUrl;
        coverRoot().style.setProperty("--cover-background-img-pc", `url("${objectUrl}")`);
        coverRoot().style.setProperty("--cover-background-img-mb", `url("${objectUrl}")`);
    } else {
        // 图片无法被脚本读取时退回直接引用，至少保证封面被刷新
        coverRoot().style.setProperty("--cover-background-img-pc", `url("${url}")`);
        coverRoot().style.setProperty("--cover-background-img-mb", `url("${url}")`);
    }
    if (extract && color) {
        applyExtractedColor(color);
    }
}

// 文章页开启特色图片作为背景时，同步重算主题色
async function applyArticleBackgroundColor() {
    const requestId = coverRequestId;
    const url = _iro?.page?.post_image;
    if (!url) {
        return;
    }
    const { color } = await loadColorFromUrl(url);
    if (color && requestId === coverRequestId) {
        applyExtractedColor(color);
    }
}

_iro.hooks.onPageLoaded(async () => {
    const cover = document.querySelector(".homepage-cover");
    const isHome = isHomePage();
    const config = _iro?.config ?? {};

    resetCover();

    if (cover) {
        // 封面适时隐藏
        if (!isHome) {
            cover.classList.add("hide");
        } else {
            cover.classList.remove("hide");
        }

        // 换图按钮：桌面与移动各有一个，且每次加载 DOM 都是新的，
        // 直接覆盖 onclick 不会重复绑定
        document.querySelectorAll(".cover-toggle").forEach((toggle) => {
            toggle.onclick = () => {
                refreshCover(!!config.extract_theme_skin_from_cover);
            };
        });
    }

    // 首页封面取色
    if (isHome && cover && config.extract_theme_skin_from_cover) {
        refreshCover(true);
    }

    // 文章页使用特色图片作为背景时重新计算
    if (
        !isHome &&
        config.extract_theme_skin_from_cover &&
        config.post_cover_as_background &&
        _iro?.page?.is_singular
    ) {
        applyArticleBackgroundColor();
    }

    // 封面打字机
    const typed_config = _iro?.config?.typed_config;
    if (typedInstance) {
        typedInstance.destroy();
    }
    if (typed_config) {
        const config = JSON.parse(typed_config);
        const typed_el = document.querySelector("#typed");
        if (typed_el) {
            typed_el.innerHTML = "";
            // typed.js 已打进启动 chunk，这里只控制初始化时机
            const { default: Typed } = await import("typed.js");
            if (document.querySelector("#typed") === typed_el) {
                typedInstance = new Typed(typed_el, config);
            }
        }
    }

    const video = document.querySelector(".cover-video");
    if (video) {
        if (!isHome) {
            video.pause();
        } else {
            video.play();
        }
    }
});
