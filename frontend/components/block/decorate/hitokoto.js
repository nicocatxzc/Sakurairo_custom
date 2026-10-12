_iro.hooks.onPageLoaded(async () => {
    // PHP 预渲染过的（custom / both 抽中）带内容，只有空节点才去请求 API
    const nodes = document.querySelectorAll(".hitokoto");
    if (!nodes.length) return;

    const api_group = _iro.config.hitokoto_apis || ["https://v1.hitokoto.cn/"];

    if (api_group.length === 0) {
        console.warn("一言API: 路径为空");
    }

    for (const node of nodes) {
        if (node.innerHTML.trim().length > 1) continue;

        for (const api_path of api_group) {
            try {
                const { data } = await axios.get(api_path);
                node.innerHTML = `${data.hitokoto}——${data?.from_who ?? data?.from}`;
                break;
            } catch (e) {
                console.warn(`一言API: 尝试联系"${api_path}"时出错。 `, e);
                continue;
            }
        }
    }
});
