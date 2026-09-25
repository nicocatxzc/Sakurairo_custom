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

export const aiApi = {
    selftest: () => request("/ai/selftest"),
    models: (refresh = false) => request(`/ai/models${refresh ? "?refresh=1" : ""}`),
    chat: (messages, model, signal) =>
        request("/ai/chat", { method: "POST", data: { messages, model }, signal }),
    posts: (params = {}) => {
        const search = new URLSearchParams();

        Object.entries(params).forEach(([key, value]) => {
            if (value === undefined || value === null || value === "") {
                return;
            }
            search.append(key, typeof value === "boolean" ? (value ? "1" : "0") : value);
        });

        return request(`/ai/posts?${search.toString()}`);
    },
    // 单字段资源：GET 生成（不落库，回给前端核对）、PUT 写入
    postKeyword: (id) => request(`/ai/posts/keyword?id=${id}`),
    postDescription: (id) => request(`/ai/posts/description?id=${id}`),
    savePostKeyword: (id, value) =>
        request("/ai/posts/keyword", { method: "PUT", data: { id, value } }),
    savePostDescription: (id, value) =>
        request("/ai/posts/description", { method: "PUT", data: { id, value } }),
};
