import classicPagination from "../../../app/utils/classicPagination";

_iro.hooks.onPageLoaded(() => {
    const favlist = document.querySelector(".page-favlist");
    if (!favlist) return;
    classicPagination(favlist,favlist,"bilibili_favlist",".category")
    classicPagination(favlist,favlist,"bilibili_favlist",".page-numbers")
});
