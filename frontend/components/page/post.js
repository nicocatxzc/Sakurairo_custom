// 正文里只有手工填过锚点的标题才有 id，其余标题 tocbot 会输出 href="#" 的失效链接，
// 所以初始化前按 WP 自动锚点的形状（h-<标题>）给缺 id 的标题补上
const HEADING_SELECTOR = "h1, h2, h3, h4, h5";

const headingSlug = (text) => text
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^\p{L}\p{N}\s-]/gu, "")
    .trim()
    .replace(/\s+/g, "-");

const ensureHeadingAnchors = async (content) => {
    if (!content) return;
    // 让出一轮任务队列：正文里可能有页面加载后才插入的动态块，扫早了会漏标题
    await new Promise((resolve) => setTimeout(resolve));

    const usedIds = new Set([...document.querySelectorAll("[id]")].map((el) => el.id));
    content.querySelectorAll(HEADING_SELECTOR).forEach((heading, index) => {
        if (heading.id) return;
        const base = `h-${headingSlug(heading.textContent) || `heading-${index}`}`;
        let id = base;
        // 同名标题、以及和正文里已有 id 撞车时依次加序号
        for (let n = 2; usedIds.has(id); n += 1) id = `${base}-${n}`;
        usedIds.add(id);
        heading.id = id;
    });
};

// 地址栏里的 hash 会被浏览器百分号编码（#h-快速上手 → #h-%E5%BF%AB...），
// 而 tocbot 只拿 heading.id 原文去匹配 link 的 href 属性，两者永远不相等；
// 它的「底部模式」判定在 Chrome 下又恒为真（documentElement.offsetHeight 是视口高度），
// 于是带着 hash 时的每次更新都会选中这个匹配不上的 id，清空高亮并折叠整棵目录。
// 所以点击不写 hash，带进来的 hash 定位完也抹掉。
const dropHash = () => {
    if (window.location.hash) {
        window.history.replaceState(null, "", window.location.pathname + window.location.search);
    }
};

const headingFromHash = (hash) => {
    try {
        return document.getElementById(decodeURIComponent(hash.replace(/^#/, "")));
    } catch {
        return null;
    }
};

const boundTocs = new WeakSet();

const onClickTocLink = (event) => {
    const href = event.target instanceof Element ? event.target.closest(".toc-link")?.getAttribute("href") : null;
    const heading = href ? document.getElementById(href.replace(/^#/, "")) : null;
    if (!heading) return;
    // 自己接管滚动：交给浏览器它就会写 hash，落点还会被固定导航栏盖住
    event.preventDefault();
    heading.scrollIntoView({ behavior: "smooth", block: "start" });
};

_iro.hooks.onPageLoaded(async () => {
    // 认 #toc 而不是 .toc：内建目录与侧栏目录块的外层类名不一样，只有 #toc 是二者共有的
    const toc = document.querySelector("#toc");
    if (!toc) return;
    // tocbot 已打进启动 chunk，这里只控制初始化时机
    const { default: tocbot } = await import("tocbot");
    await ensureHeadingAnchors(document.querySelector(".post-content"));
    // 带 hash 打开时自己定位一次，再把 hash 去掉
    const hashHeading = headingFromHash(window.location.hash);
    dropHash();
    tocbot.init({
        tocSelector: "#toc",
        contentSelector: ".post-content",
        headingSelector: HEADING_SELECTOR,

        // 滚动改由下面的点击处理接管，顺带避免 tocbot 在点击时冻结高亮
        scrollSmooth: false,
        scrollSmoothDuration: 300,

        // 目录链接是页内锚点，既要挡掉浏览器写 hash，也要挡掉 Swup：
        // Swup 会把这个 hash 编码后 replaceState 进地址栏（no-pjax 是它的放行开关）
        extraLinkClasses: "no-pjax",

        headingsOffset: 80,

        throttleTimeout: 100,

        enableUrlHashUpdateOnScroll: false,
    });
    hashHeading?.scrollIntoView({ block: "start" });
    // 侧栏的目录块不在 #pjax-main 里，PJAX 之后元素仍在，用 WeakSet 防止监听器叠加
    if (!boundTocs.has(toc)) {
        boundTocs.add(toc);
        toc.addEventListener("click", onClickTocLink);
    }
});
