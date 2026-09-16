import api from "../../app/utils/api";
import classicPagination from "../../app/utils/classicPagination";

_iro.hooks.onPageLoaded(() => {
    let postList = document.querySelector(".post-list");

    if (postList) {
        if (_iro.config.pagination_mode == "ajax") {
            let next_page = postList.querySelector(".ajax-pagination");

            let paginationObserver = null;
            let paginationTimer = null;
            let paginationSentinel = null;
            let isLoading = false;

            // 销毁分页相关
            function cleanupPagination() {
                if (paginationObserver) {
                    paginationObserver.disconnect();
                    paginationObserver = null;
                }

                if (paginationTimer) {
                    clearTimeout(paginationTimer);
                    paginationTimer = null;
                }

                if (paginationSentinel) {
                    paginationSentinel.remove();
                    paginationSentinel = null;
                }
            }

            function createPaginationSentinel() {
                if (!postList || !postList.parentNode) {
                    return;
                }

                paginationSentinel = document.createElement("div");

                paginationSentinel.className = "ajax-pagination-sentinel";

                Object.assign(paginationSentinel.style, {
                    width: "1px",
                    height: "1px",
                    margin: "0",
                    padding: "0",
                    opacity: "0",
                    pointerEvents: "none",
                });

                // 放在 postList 外部、结束标签之后
                postList.insertAdjacentElement("afterend", paginationSentinel);

                paginationObserver = new IntersectionObserver(
                    (entries) => {
                        if (!entries[0].isIntersecting || isLoading) {
                            return;
                        }

                        // 找当前最新的分页组件
                        next_page = postList.querySelector(".ajax-pagination");

                        if (!next_page) {
                            return;
                        }

                        const wait =
                            Number(_iro.config.pagination_ajax_wait) || 0;

                        // 防止同一个 sentinel 重复启动 timer
                        if (paginationTimer) {
                            return;
                        }

                        if (wait > 0) {
                            paginationTimer = setTimeout(() => {
                                paginationTimer = null;

                                if (!isLoading) {
                                    loadNextPage();
                                }
                            }, wait * 1000);
                        } else {
                            loadNextPage();
                        }
                    },
                    {
                        rootMargin: "200px 0px",
                    },
                );

                paginationObserver.observe(paginationSentinel);
            }

            async function loadNextPage() {
                if (isLoading) {
                    return;
                }

                // 取消计划的自动翻页
                if (paginationTimer) {
                    clearTimeout(paginationTimer);
                    paginationTimer = null;
                }

                // 每次加载前都重新获取当前分页
                next_page = postList.querySelector(".ajax-pagination");

                if (!next_page) {
                    return;
                }

                const href = next_page.href;

                isLoading = true;

                try {
                    const res = await api.get(href, {
                        headers: {
                            "X-Template-Part": "post_list",
                        },
                    });

                    postList.insertAdjacentHTML("beforeend", res.data);
                    next_page.parentElement.remove();

                    // 会导致 swup 混乱
                    // history.pushState({}, "", href);

                    postList = document.querySelector(".post-list");

                    if (!postList) {
                        return;
                    }

                    // 获取 AJAX 返回内容中的下一页
                    next_page = postList.querySelector(".ajax-pagination");

                    // 没有下一页了
                    if (!next_page) {
                        cleanupPagination();
                        return;
                    }

                    // 不滚动，会造成无限翻页
                    // postList.scrollIntoView({
                    //     behavior: "smooth",
                    //     block: "end",
                    // });
                } catch (error) {
                    console.error("Failed to load next page:", error);
                } finally {
                    isLoading = false;
                }
            }

            postList.addEventListener("click", async (event) => {
                const link = event.target.closest(".ajax-pagination");
                if (!link) return;

                if (!link.href) return;

                event.preventDefault();
                event.stopPropagation();

                if (isLoading) return;
                await loadNextPage();
            });

            // pjax销毁资源
            document.addEventListener("pjax:complete", () => {
                cleanupPagination();

                document.removeEventListener("pjax:complete", onPjaxComplete);
            });

            createPaginationSentinel();
        } else {
            classicPagination(postList, postList, "post_list", ".page-numbers");
        }
    }

    const commentList = document.querySelector(".comment-list");

    if (commentList) {
        classicPagination(
            commentList,
            commentList,
            "comment_list",
            ".page-numbers",
        );
    }
});
