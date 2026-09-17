_iro.utils.missImg = (ele) => missing_image(ele, "image");
_iro.utils.missAvatar = (ele) => missing_image(ele, "avatar");
function missing_image(element, type = "image") {
    if (!element || element.tagName !== "IMG") return;

    // 重试次数
    let retry = parseInt(element.dataset.retry || "0", 10);

    if (retry < 2) {
        element.dataset.retry = String(retry + 1);

        const base = element.dataset.src || element.src.split("?")[0];
        const sep = base.includes("?") ? "&" : "?";
        element.src = `${base}${sep}_retry=${retry + 1}&_t=${Date.now()}`;
        return;
    }

    const fallback =
        type == "image"
            ? (_iro.config?.missing_images ?? "")
            : (_iro.config?.missing_avatars ?? "");

    element.onerror = null;
    element.removeAttribute("onerror");

    if (fallback && element.src !== fallback) {
        element.src = fallback;
    }
}
