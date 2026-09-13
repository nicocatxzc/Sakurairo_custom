import tocbot from "tocbot";

_iro.hooks.onPageLoaded(() => {
    const toc = document.querySelector(".toc");
    if (toc) {
        tocbot.init({
            tocSelector: "#toc",
            contentSelector: ".post-content",
            headingSelector: "h1, h2, h3, h4, h5",

            scrollSmooth: true,
            scrollSmoothDuration: 300,
            headingsOffset: 80,

            throttleTimeout: 100,

            enableUrlHashUpdateOnScroll: false,
        });
    }
});
