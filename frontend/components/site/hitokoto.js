_iro.hooks.DOMContentLoaded.add(async () => {
    const hitokoto = document.querySelector("#footer_hitokoto");
    if (!hitokoto) return;
    if (hitokoto.innerHTML.trim().length > 1) return;
    if (hitokoto) {
        const api_group = _iro.config.hitokoto_apis || [
            "https://v1.hitokoto.cn/",
        ];
        if (api_group.length == 0) {
            console.warn("一言API: 路径为空");
        }
        for (const api_path of api_group) {
            try {
                const { data } = await axios.get(api_path);
                const text = `${data.hitokoto}——${data?.from_who ?? data?.from}`;
                hitokoto.innerHTML = text;
                break;
            } catch (e) {
                console.warn(`一言API: 尝试联系"${api_path}"时出错。 `, e);
                continue;
            }
        }
    }
});
