_iro.hooks.onPageLoaded(async () => {
    const toc = document.querySelector(".toc");
    if (toc) {
        // tocbot 只在存在目录的文章页按需加载
        const { default: tocbot } = await import("tocbot");
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
