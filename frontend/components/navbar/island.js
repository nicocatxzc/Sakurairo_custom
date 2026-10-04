import bus from "../../app/bus";

const header = document.querySelector(".site-header.island");

// header 由服务端渲染且位于 pjax 容器之外，pjax 不会重渲染它 —— 所有状态都在这里跟着页面变，
// 动画一律交给 island.scss 的类/属性切换。
if (header) {
    const wrapper = header.querySelector(".menu-wrapper");
    const menu = wrapper.querySelector("nav");
    const navTitle = wrapper.querySelector(".nav-article-title");

    // 换内容的时长，和 island.scss 里的 transition 保持一致
    const SWAP_MS = 400;
    // 鼠标移开后菜单再留一会儿才换回标题
    const HOLD_MS = 3000;

    let holding = false;
    let holdTimer = 0;
    let unclipTimer = 0;

    // 标题比菜单宽多少：换成标题时胶囊该长出来还是收回去。
    // 两个都是真实元素，直接量就行 —— 上游那套把节点克隆进 body 再量是因为它的标题是动态插入的
    const measure = () => {
        if (!navTitle) {
            return;
        }
        const delta = navTitle.getBoundingClientRect().width - menu.getBoundingClientRect().width;
        wrapper.style.setProperty("--dw", Math.round(delta) + "px");
    };

    const articleTitle = () => document.querySelector(".page-header .post-title");

    // 导航栏不参与 pjax 替换，标题文字得自己跟着正文更到当前页，否则切页后会留着上一篇的
    const syncTitle = () => {
        const source = articleTitle();
        if (!navTitle || !source || navTitle.textContent === source.textContent) {
            return;
        }
        navTitle.textContent = source.textContent;
    };

    const applySwap = () => {
        const title = articleTitle();
        // 标题滚出视口上方才换；没有正文标题的页面（首页、归档等）永远不换
        const swap = !!navTitle && !!title && title.getBoundingClientRect().top < 0 && !holding;
        if (wrapper.hasAttribute("data-scrollswap") === swap) {
            return;
        }
        // 菜单行要滑出胶囊外，交换期间必须裁剪；换完就得松开，否则下拉菜单会被切掉
        wrapper.classList.add("is-swapping");
        clearTimeout(unclipTimer);
        unclipTimer = setTimeout(() => wrapper.classList.remove("is-swapping"), SWAP_MS + 100);
        wrapper.toggleAttribute("data-scrollswap", swap);
    };

    // transitionend 比定时器准，但动画被打断时不会触发，所以上面留了兜底
    wrapper.addEventListener("transitionend", (event) => {
        if (event.target === menu && event.propertyName === "transform") {
            clearTimeout(unclipTimer);
            wrapper.classList.remove("is-swapping");
        }
    });

    header.addEventListener("mouseenter", () => {
        holding = true;
        clearTimeout(holdTimer);
        applySwap();
    });

    header.addEventListener("mouseleave", () => {
        clearTimeout(holdTimer);
        holdTimer = setTimeout(() => {
            holding = false;
            applySwap();
        }, HOLD_MS);
    });

    bus.on("scroll:update", applySwap);

    bus.on("resize:update", () => {
        measure();
        applySwap();
    });

    _iro.hooks.onPageLoaded(() => {
        holding = false;
        clearTimeout(holdTimer);
        clearTimeout(unclipTimer);
        wrapper.classList.remove("is-swapping");
        wrapper.removeAttribute("data-scrollswap");
        header.classList.toggle("is-home", !!_iro?.page?.is_home);

        // 等本页的正文标题就位再量宽度，字体晚到会让量出来的值偏小
        requestAnimationFrame(() => {
            syncTitle();
            measure();
            applySwap();
        });
    });

    document.fonts?.ready.then(measure);
}
