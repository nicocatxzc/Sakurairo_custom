// 由 inc/functions/ai/options.php 以 <script id="iro_ai_config" type="application/json"> 注入
function readAiOptions() {
    const fallback = {
        restUrl: "",
        nonce: "",
        provider: "",
        model: "",
        apiBase: "",
        configured: false,
        tools: [],
    };

    const el = document.getElementById("iro_ai_config");

    if (!el) {
        return { ...fallback, ...(window.iroAiOptions ?? {}) };
    }

    try {
        return { ...fallback, ...JSON.parse(el.textContent || "{}") };
    } catch (error) {
        console.error("[iro-ai] 配置解析失败", error);
        return fallback;
    }
}

export const aiOptions = readAiOptions();
