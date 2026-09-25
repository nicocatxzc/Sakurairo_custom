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
};
