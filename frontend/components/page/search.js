_iro.hooks.onPageLoaded(async () => {
    const searchPage = document.querySelector(".page-search");
    if (searchPage) {
        const input = searchPage.querySelector(".search-input");
        const button = searchPage.querySelector(".search-button");
        const goSearch = () => _iro.navigate(`/?s=${input.value}`);

        const params = new URLSearchParams(window.location.search);
        const searchKey = params.get("s");

        input.value = searchKey

        input.addEventListener("keyup", (key) => {
            if (key?.code == "Enter" || key?.code == "NumpadEnter") {
                goSearch();
            }
        });
        button.addEventListener("click", () => {
            goSearch();
        });
    }
});
