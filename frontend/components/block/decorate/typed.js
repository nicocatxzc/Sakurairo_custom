let typedInstances = [];

_iro.hooks.onPageLoaded(async () => {
    typedInstances.forEach((instance) => instance.destroy());
    typedInstances = [];

    const containers = document.querySelectorAll(".typed-container");
    if (!containers.length) return;

    const global_config = _iro?.config?.typed_config;

    for (const container of containers) {
        const node = container.querySelector(".typed");
        if (!node) continue;

        // 区块内联配置优先；没有就回退全局配置，两者都空时保留预渲染的占位文本
        const inline = container.dataset.typed ? JSON.parse(container.dataset.typed) : null;
        const raw = inline?.config ?? global_config;
        const config = typeof raw === "string" ? JSON.parse(raw) : raw;
        if (!config || !Object.keys(config).length) continue;

        node.innerHTML = "";
        // typed.js 已打进启动 chunk，这里只控制初始化时机
        const { default: Typed } = await import("typed.js");
        if (document.contains(node)) {
            typedInstances.push(new Typed(node, config));
        }
    }
});
