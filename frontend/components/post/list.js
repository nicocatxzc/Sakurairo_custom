import api from "../../app/utils/api";

_iro.hooks.onPageLoaded(() => {
    const postList = document.querySelector(".post-list");

    if (postList) {
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

                postList.scrollIntoView({ behavior: "smooth", block: "start" });
            } catch (err) {
                console.error("翻页失败:", err);
            } finally {
                link.dataset.loading = "0";
            }
        });
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

                commentList.scrollIntoView({ behavior: "smooth", block: "start" });
            } catch (err) {
                console.error("翻页失败:", err);
            } finally {
                link.dataset.loading = "0";
            }
        });
    }
});
