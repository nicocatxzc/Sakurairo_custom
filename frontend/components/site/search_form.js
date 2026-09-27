import { onClickOutside } from "@vueuse/core";
import api from "../../app/utils/api";
import LocalSearch from "../../app/utils/localsearch";

// 首次呼出后异步请求索引
let searchIndex = null;
let searchIndexPromise = null;
function ensureSearchIndex() {
    if (searchIndex) return Promise.resolve(searchIndex);
    if (!searchIndexPromise) {
        searchIndexPromise = (async () => {
            try {
                const { data } = await api.get(
                    `${_iro.config.iro_api}/search_index`,
                );
                if (!data) return null;
                searchIndex = await LocalSearch(data);
                return searchIndex;
            } catch (error) {
                // 失败后允许下次呼出重试
                searchIndexPromise = null;
                return null;
            }
        })();
    }
    return searchIndexPromise;
}

_iro.hooks["DOMContentLoaded"].add(() => {
    const searchButton = document.querySelector(".site-header .button.search");
    if (searchButton) {
        const searchForm = document.querySelector(".search-model");
        searchButton.addEventListener("click", () => {
            searchForm.classList.toggle("show");
            if (searchForm.classList.contains("show")) {
                void ensureSearchIndex();
            }
        });
        onClickOutside(
            searchForm,
            () => {
                searchForm.classList.remove("show");
            },
            {
                ignore: [searchButton],
            },
        );
        searchForm
            .querySelector(".close.button")
            .addEventListener("click", () => {
                searchForm.classList.remove("show");
            });
        const searchInput = searchForm.querySelector(".search-input");
        searchInput.addEventListener("keyup", (key) => {
            if (key?.code == "Enter" || key?.code == "NumpadEnter") {
                _iro.navigate(`/?s=${searchInput.value}`);
                searchForm.classList.remove("show");
            }
        });

        const searchList = searchForm.querySelector(".search-list");
        if (searchList) {
            searchInput.addEventListener("change", async () => {
                const index = await ensureSearchIndex();
                if (!index) return;
                const result = await index.search(searchInput.value);
                let html = "";
                (result ?? []).forEach((post) => {
                    html += renderSearchItem(
                        post?.url,
                        post?.title,
                        post?.snip,
                    );
                });
                searchList.querySelector(".post-list").innerHTML = html;
            });
        }
    }
});

function renderSearchItem(url, title, snip) {
    return /* html */ `
    <li class="post-item">
        <a src="${url}">
            <header class="item-title">${title}</header>
            <span class="snip">${snip}</span>
        </a>
    </li>`;
}
