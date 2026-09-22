import classicPagination from "../../../app/utils/classicPagination";

_iro.hooks.onPageLoaded(() => {
    const pageSteam = document.querySelector(".page-steam");
    if (!pageSteam) return;

    const steamList = pageSteam.querySelector(".steam-list");
    if (steamList) {
        classicPagination(steamList, steamList, "steam_list");
    }
});

