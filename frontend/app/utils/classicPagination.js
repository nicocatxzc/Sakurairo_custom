function classicPagination(
    list,
    replaceTarget,
    templatePart = "template",
    pageNumberSelector = ".page-numbers",
) {
    list.addEventListener("click", async (event) => {
        const link = event.target.closest(pageNumberSelector);
        if (!link) return;

        if (!link.href) return;

        event.preventDefault();
        event.stopPropagation();

        if (link.dataset.loading === true) return;
        link.dataset.loading = true;

        try {
            const res = await api.get(link.href, {
                headers: {
                    "X-Template-Part": templatePart,
                },
            });

            replaceTarget.innerHTML = res.data;

            // 会导致swup混乱
            // history.pushState({}, "", link.href);

            replaceTarget.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
        } catch (err) {
            console.error("翻页失败:", err);
        } finally {
            link.dataset.loading = false;
        }
    });
}
export default classicPagination;
