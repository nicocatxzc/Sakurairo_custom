import { onClickOutside } from "@vueuse/core";
import api from "../../app/utils/api";
import LocalSearch from "../../app/utils/localsearch";

_iro.hooks["DOMContentLoaded"].add(async () => {
    const searchButton = document.querySelector(".site-header .button.search");
    if (searchButton) {
        const searchForm = document.querySelector(".search-model");
        searchButton.addEventListener("click", () => {
            searchForm.classList.toggle("show");
        });
        onClickOutside(searchForm, () => {
            searchForm.classList.remove("show");
        });
        searchForm.querySelector(".close.button").addEventListener("click",()=>{
            searchForm.classList.remove("show");
        })
        const searchInput = searchForm.querySelector(".search-input");
        searchInput.addEventListener("keyup", (key) => {
            if (key?.code == "Enter") {
                _iro.navigate(`/?s=${searchInput.value}`)
            }
        });

        const searchList = searchForm.querySelector(".search-list");
        if (searchList) {
            try {
                const { data } = await api.get(
                    `${_iro.config.iro_api}/search_index`,
                );
                if (data) {
                    const index = await LocalSearch(data);
                    searchInput.addEventListener("change", async () => {
                        const result = await index.search(searchInput.value);
                        console.log(result);
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
            } catch (error) {}
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
