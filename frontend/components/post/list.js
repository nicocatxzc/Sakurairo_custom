import { applyExtractedColor, extractColorFromImageElement } from "../../app/utils/themeColor";

let listObserver = null;
const processedCards = new WeakSet();

async function processCard(card) {
    if (!card || processedCards.has(card)) {
        return;
    }
    processedCards.add(card);
    const image = card.querySelector(".post-thumb img");
    const color = await extractColorFromImageElement(image);
    if (color) {
        applyExtractedColor(color, card);
    }
}

function processCards(root) {
    root.querySelectorAll(".post-card").forEach((card) => {
        processCard(card);
    });
}

function observePostList(list) {
    listObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof Element)) {
                    return;
                }
                if (node.classList.contains("post-card")) {
                    processCard(node);
                    return;
                }
                processCards(node);
            });
        });
    });
    listObserver.observe(list, { childList: true, subtree: true });
}

_iro.hooks.onPageLoaded(() => {
    // 翻页/搜索会重建列表，先断开旧观察器
    if (listObserver) {
        listObserver.disconnect();
        listObserver = null;
    }
    if (!_iro?.config?.extract_article_highlight_from_feature) {
        return;
    }
    const list = document.querySelector(".post-list");
    if (!list) {
        return;
    }
    processCards(list);
    observePostList(list);
});
