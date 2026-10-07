_iro.hooks.onPageLoaded(async () => {
    const searchHeader = document.querySelector(".search-header");
    if (searchHeader) {
        const input = searchHeader.querySelector(".search-input");
        const button = searchHeader.querySelector(".search-button");
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
