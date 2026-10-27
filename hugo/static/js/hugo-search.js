/*
 * 模态框实时搜索。
 *
 * 索引路径与 WP REST 对齐：/wp-json/sakura/v1/search_index（由 layouts/index.searchindex 生成），
 * 所以 iro_api 指过去之后，既有的 WP 运行时也能直接取到同一份数据，不必另造接口。
 *
 * 保留自己的渲染与交互，是因为复用的 app.js 里那份 search_form.js 有三个不合用的地方：
 * 只在 change 时检索（不是实时）、回车跳 ?s= 结果页（静态站没有这个页面）、
 * 结果锚点用的是非法属性 src 且 url 取不到（LocalSearch 返回的是 uri）。
 */
(function () {
    "use strict";

    var MAX_RESULTS = 10;
    var DEBOUNCE = 150;

    var indexPromise = null;
    var debounceTimer = null;
    var composing = false;
    var results = [];

    function escapeHtml(text) {
        return String(text == null ? "" : text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    /* 与 WP REST 同路径：theme_config 已经把 iro_api 指到 /wp-json/sakura/v1 */
    function indexUrl() {
        var api =
            (window._iro && window._iro.config && window._iro.config.iro_api) ||
            "/wp-json/sakura/v1";
        return api + "/search_index";
    }

    function loadIndex() {
        if (!indexPromise) {
            indexPromise = fetch(indexUrl())
                .then(function (response) {
                    if (!response.ok) throw new Error("search_index " + response.status);
                    return response.json();
                })
                .catch(function (error) {
                    // 失败后允许下次呼出重试
                    indexPromise = null;
                    throw error;
                });
        }
        return indexPromise;
    }

    /* WP 那边的 categories/tags 是 [{name, uri}]，标题与摘要是 excerpt */
    function termNames(list) {
        return (list || [])
            .map(function (term) {
                return typeof term === "string" ? term : term && term.name;
            })
            .filter(Boolean);
    }

    function score(item, words) {
        var title = (item.title || "").toLowerCase();
        var summary = (item.excerpt || "").toLowerCase();
        var content = (item.content || "").toLowerCase();
        var tags = termNames(item.tags).join(" ").toLowerCase();
        var categories = termNames(item.categories).join(" ").toLowerCase();
        var total = 0;

        for (var i = 0; i < words.length; i++) {
            var word = words[i];
            var hit = 0;

            if (title.indexOf(word) !== -1) hit += 10;
            if (tags.indexOf(word) !== -1) hit += 6;
            if (categories.indexOf(word) !== -1) hit += 4;
            if (summary.indexOf(word) !== -1) hit += 3;
            if (content.indexOf(word) !== -1) hit += 1;

            if (hit === 0) return 0;
            total += hit;
        }

        return total;
    }

    function search(items, keyword) {
        var words = keyword.toLowerCase().split(/\s+/).filter(Boolean);
        if (!words.length) return [];

        return items
            .map(function (item) {
                return { item: item, weight: score(item, words) };
            })
            .filter(function (entry) {
                return entry.weight > 0;
            })
            .sort(function (a, b) {
                return b.weight - a.weight;
            })
            .slice(0, MAX_RESULTS)
            .map(function (entry) {
                return entry.item;
            });
    }

    function snippet(text) {
        var plain = String(text == null ? "" : text).replace(/\s+/g, " ").trim();
        return plain.length > 100 ? plain.slice(0, 100) + "…" : plain;
    }

    function renderItem(item) {
        return (
            '<li class="post-item">' +
            '<a href="' + escapeHtml(item.uri) + '">' +
            '<header class="item-title">' + escapeHtml(item.title) + "</header>" +
            '<span class="snip">' + escapeHtml(snippet(item.excerpt)) + "</span>" +
            "</a></li>"
        );
    }

    function setup() {
        var modal = document.querySelector(".search-model");
        if (!modal) return;
        if (modal.dataset.hugoSearchReady !== "1") {
            /*
             * 复用 WordPress 侧的前端产物时，app.js 里那份 search_form.js 也会跑：
             * 它把回车跳到 ?s= 结果页，并去找 _iro.config.iro_api 下的搜索 REST。
             * 静态站没有结果页也没有那个接口，所以换一个同结构的克隆节点，把它的监听全部丢掉，
             * 只保留标记结构，交互由这里接管。
             */
            var clone = modal.cloneNode(true);
            clone.dataset.hugoSearchReady = "1";
            modal.replaceWith(clone);
            modal = clone;
        }

        var input = modal.querySelector(".search-input");
        var list = modal.querySelector(".search-list .post-list");
        var closeButton = modal.querySelector(".close.button");

        function open() {
            // 移动端的输入框在折叠面板里，呼出弹层时先把面板收起来，免得两层叠在一起
            var openedPanel = document.querySelector('.site-header.mobile .menu-toggle[aria-expanded="true"]');
            if (openedPanel) openedPanel.click();

            modal.classList.add("show");
            // 等过渡开始再聚焦，否则部分浏览器会把焦点丢回 body
            setTimeout(function () {
                input.focus();
            }, 60);
            loadIndex().catch(function (error) {
                console.error("[hugo-search]", error);
            });
        }

        function close() {
            modal.classList.remove("show");
        }

        function render() {
            var keyword = input.value.trim();
            if (!keyword) {
                results = [];
                list.innerHTML = "";
                return;
            }

            loadIndex()
                .then(function (items) {
                    results = search(items, keyword);
                    list.innerHTML = results.length
                        ? results.map(renderItem).join("")
                        : '<li class="post-item">没有找到相关内容，换个关键词试试。</li>';
                })
                .catch(function () {
                    list.innerHTML = '<li class="post-item">搜索索引没能加载，请刷新重试。</li>';
                });
        }

        document.querySelectorAll(".site-header .button.search").forEach(function (button) {
            button.addEventListener("click", open);
        });

        // 移动端页头的输入框没有结果页可跳，聚焦时直接改成呼出模态框
        document.querySelectorAll(".site-header.mobile .search-input").forEach(function (mobileInput) {
            mobileInput.setAttribute("readonly", "readonly");
            mobileInput.addEventListener("focus", function () {
                mobileInput.blur();
                open();
            });
        });

        closeButton.addEventListener("click", close);

        input.addEventListener("compositionstart", function () {
            composing = true;
        });
        input.addEventListener("compositionend", function () {
            composing = false;
            render();
        });
        input.addEventListener("input", function () {
            if (composing) return;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(render, DEBOUNCE);
        });
        input.addEventListener("keyup", function (event) {
            if (event.code === "Escape") {
                close();
                return;
            }
            if (event.code !== "Enter" && event.code !== "NumpadEnter") return;
            if (!results.length) {
                clearTimeout(debounceTimer);
                render();
                return;
            }
            var url = results[0].uri;
            close();
            if (typeof window._iro?.navigate === "function") {
                window._iro.navigate(url);
            } else {
                window.location.href = url;
            }
        });

        document.addEventListener("click", function (event) {
            if (!modal.classList.contains("show")) return;
            if (modal.contains(event.target)) return;
            if (event.target.closest(".site-header .button.search")) return;
            close();
        });

        // 404 页的搜索表单把关键词带回首页（/?s=），这里自动呼出并直接出结果
        var keyword = new URLSearchParams(window.location.search).get("s");
        if (keyword && keyword.trim()) {
            open();
            input.value = keyword.trim();
            render();
        }
    }

    function bind() {
        if (window._iro && window._iro.hooks && window._iro.hooks.onPageLoaded) {
            window._iro.hooks.onPageLoaded(setup);
            return;
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", setup);
        } else {
            setup();
        }
    }

    bind();
})();
