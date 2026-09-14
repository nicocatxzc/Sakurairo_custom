import api from "../../app/utils/api";

_iro.hooks.onPageLoaded(() => {
    let postList = document.querySelector(".post-list");

    if (postList) {
        if (_iro.config.pagination_mode == "ajax") {
            let next_page = postList.querySelector(".ajax-pagination");
            preNextPage();
            async function loadNextPage() {
                const res = await api.get(next_page.href, {
                    headers: {
                        "X-Template-Part": "post_list",
                    },
                });

                postList.insertAdjacentHTML("beforeend", res.data);

                // 会导致swup混乱
                // history.pushState({}, "", link.href);

                postList = document.querySelector(".post-list");
                postList.scrollIntoView({
                    behavior: "smooth",
                    block: "end",
                });
                next_page?.remove();
                next_page = postList.querySelector(".ajax-pagination");
                preNextPage();
            }
            function preNextPage() {
                if (next_page) {
                    next_page.addEventListener("click", async (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        await loadNextPage();
                    });
                }
            }
        } else {
            postList.addEventListener("click", async (event) => {
                const link = event.target.closest(".page-numbers");
                if (!link) return;

                if (!link.href) return;

                event.preventDefault();
                event.stopPropagation();

                if (link.dataset.loading === "1") return;
                link.dataset.loading = "1";

                try {
                    const res = await api.get(link.href, {
                        headers: {
                            "X-Template-Part": "post_list",
                        },
                    });

                    postList.innerHTML = res.data;

                    // 会导致swup混乱
                    // history.pushState({}, "", link.href);

                    postList.scrollIntoView({
                        behavior: "smooth",
                        block: "start",
                    });
                } catch (err) {
                    console.error("翻页失败:", err);
                } finally {
                    link.dataset.loading = "0";
                }
            });
        }
    }

    const commentList = document.querySelector(".comment-list")?.parentElement;

    if (commentList) {
        commentList.addEventListener("click", async (event) => {
            const link = event.target.closest(".page-numbers");
            if (!link) return;

            if (!link.href) return;

            event.preventDefault();
            event.stopPropagation();

            if (link.dataset.loading === "1") return;
            link.dataset.loading = "1";

            try {
                const res = await api.get(link.href, {
                    headers: {
                        "X-Template-Part": "comment_list",
                    },
                });

                commentList.innerHTML = res.data;

                // 会导致swup混乱
                // history.pushState({}, "", link.href);

                commentList.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
            } catch (err) {
                console.error("翻页失败:", err);
            } finally {
                link.dataset.loading = "0";
            }
        });
    }
});
