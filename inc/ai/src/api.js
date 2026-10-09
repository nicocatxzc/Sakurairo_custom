import { aiOptions } from "./config";

export async function request(path, { method = "GET", data, signal } = {}) {
    const response = await fetch(`${aiOptions.restUrl}${path}`, {
        method,
        credentials: "same-origin",
        headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": aiOptions.nonce,
        },
        body: data === undefined ? undefined : JSON.stringify(data),
        signal,
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new Error(payload?.message || `请求失败（HTTP ${response.status}）`);
    }

    return payload;
}

// 只拼接有值的参数，布尔值转成 1/0
function toQuery(params = {}) {
    const search = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null || value === "") {
            return;
        }
        search.append(key, typeof value === "boolean" ? (value ? "1" : "0") : value);
    });

    return search.toString();
}

export const aiApi = {
    selftest: () => request("/ai/selftest"),
    models: (refresh = false) => request(`/ai/models${refresh ? "?refresh=1" : ""}`),
    settings: () => request("/ai/settings"),
    saveSettings: (settings) =>
        request("/ai/settings", { method: "POST", data: settings }),
    chat: (messages, model, signal) =>
        request("/ai/chat", { method: "POST", data: { messages, model }, signal }),
    posts: (params = {}) => request(`/ai/posts?${toQuery(params)}`),
    // 单字段资源：GET 生成（不落库，回给前端核对）、PUT 写入
    postTitle: (id) => request(`/ai/posts/title?id=${id}`),
    postKeyword: (id) => request(`/ai/posts/keyword?id=${id}`),
    postDescription: (id) => request(`/ai/posts/description?id=${id}`),
    savePostKeyword: (id, value) =>
        request("/ai/posts/keyword", { method: "PUT", data: { id, value } }),
    savePostDescription: (id, value) =>
        request("/ai/posts/description", { method: "PUT", data: { id, value } }),

    // 多语言内容总览：读取列表用 GET，三个维护动作都只回改动条数
    i18nOverview: (params = {}) => request(`/i18n/overview?${toQuery(params)}`),
    i18nBackfill: () => request("/i18n/backfill", { method: "POST" }),
    i18nRepairLanguage: () => request("/i18n/repair-language", { method: "POST" }),
    i18nRenameSlugs: () => request("/i18n/rename-slugs", { method: "POST" }),
};
