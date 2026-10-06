function classicPagination(
    list,
    replaceTarget,
    templatePart = "template",
    pageNumberSelector = ".page-numbers",
) {
    list.addEventListener("click", async (event) => {
        const link = event.target.closest(pageNumberSelector);
        if (!link) return;

        if (!link.href) {
            if (link.getAttribute("href")) {
                link.href = link.getAttribute("href");
            } else {
                return;
            }
        }

        event.preventDefault();
        event.stopPropagation();

        if (link.dataset.loading) return;
        link.dataset.loading = "1";

        try {
            const res = await api.get(link.href, {
                headers: {
                    "X-Template-Part": templatePart,
                },
            });

            replaceTarget.innerHTML = res.data;

            // 会导致swup混乱
            // history.pushState({}, "", link.href);

            // 固定导航栏会遮住目标顶部，滚动时减去其高度
            const headerHeight = Math.max(
                0,
                ...[...document.querySelectorAll(".site-header")].map(
                    (header) => header.getBoundingClientRect().height,
                ),
            );
            window.scrollTo({
                top:
                    replaceTarget.getBoundingClientRect().top +
                    window.scrollY -
                    headerHeight,
                behavior: "smooth",
            });
        } catch (err) {
            console.error("翻页失败:", err);
        } finally {
            delete link.dataset.loading;
        }
    });
}
export default classicPagination;
