import classicPagination from "../../../app/utils/classicPagination"
_iro.hooks.onPageLoaded(()=>{
    const pageBangumi = document.querySelector(".page-bangumi");
    if(!pageBangumi) return;

    const pagenation = pageBangumi.querySelector(".site-pagination")
    const animeList = document.querySelector(".anime-list")
    if (pagenation) {
        classicPagination(animeList,animeList,"bangumi_list")
    }
})