import classicPagination from "../../../app/utils/classicPagination";
import BangumiDetail from "./BangumiDetail.vue";

let detailApp;
_iro.hooks.onPageLoaded(() => {
    const pageBangumi = document.querySelector(".page-bangumi");
    if (!pageBangumi) return;

    const pagenation = pageBangumi.querySelector(".site-pagination");
    const animeList = document.querySelector(".anime-list");
    if (pagenation) {
        classicPagination(animeList, animeList, "bangumi_list");
    }

    const detail = document.querySelector(".bangumi-detail");
    if (detail) {
        detailApp = createApp(BangumiDetail);
        const app = detailApp.mount(detail);

        animeList.addEventListener("click", async (event) => {
            const anime = event.target.closest(".anime-item");
            if (!anime) return;

            event.preventDefault();
            event.stopPropagation();

            const detail_data = JSON.parse(anime.dataset.detail);

            app.showAnime({
                images: anime.querySelector(".anime-image").src,
                name_cn: detail_data.name_cn,
                url: detail_data.url,
                date: detail_data.date,
                tags: detail_data.tags,
                summary: anime.querySelector(".anime-summary").innerHTML,
            });
        });
    }

    _iro.hooks["pjax:start"].add(
        () => {
            if (detailApp) {
                detailApp.unmount();
                detailApp = null;
            }
        },
        { once: true },
    );
});
