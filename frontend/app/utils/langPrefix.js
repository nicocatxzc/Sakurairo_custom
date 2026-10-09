// 语言前缀的地址处理
//
// 服务端按「URL 前缀 > cookie > 浏览器偏好」判定语言，所以带着旧前缀去请求，
// 它只会照旧前缀给回同一种语言。切换器与分页滚动判断都需要「去掉语言意图」
// 的同一地址，这里统一一份实现，避免各自写一遍。
//
// 前缀取页面配置里语言链接自己的 `prefix`：与前缀的唯一定义处（设置项）同源，
// 不在这里另抄一份常量。没有该配置时返回空表，调用方退化成原行为。

/** 当前站点启用语言的 URL 前缀 */
export function getLangPrefixes() {
    return _iro.page?.langs?.map((lang) => String(lang.prefix)) ?? [];
}

/**
 * 去掉地址里的语言前缀
 *
 * 只认首段：单篇内容的层级路径里也可能出现同名段（`/info/jp/`），
 * 那一段不是路由前缀，不能动。
 */
export function stripLangPrefix(url) {
    const target = new URL(url, window.location.origin);
    const [, first, ...rest] = target.pathname.split("/");

    if (!getLangPrefixes().includes(first)) return target.href;

    target.pathname = "/" + rest.join("/");

    return target.href;
}
