// 语言切换：把选择写进 cookie，再跳到服务端为这一语言算好的地址。
//
// 不自己剥掉前缀重新请求：各语言版本的别名并不相同（译文是 foo-en-us，早期副本是
// WordPress 接上的 -2、-3），去前缀的那条路径属于原文，按它解析只会给回同一个版本，
// 选默认语言时表现为「跳过去还是原来那一篇」。也不读 <head> 里的 alternate ——
// swup 只替换列出的容器，那些链接换页后还是上一篇的旧值。地址一律取页面配置里的
// langs[].url，它每次换页都会随 #iro_page_config 重建。
//
// 首次访问（cookie 里还没有答案）另问一次「要不要换成你那种语言」：服务端不按
// Accept-Language 跳转，只在答过之后按这个 cookie 把无前缀地址搬到答过的那一种语言
// （爬虫不带 cookie，所以不会被跳转）。问与换本身都发生在前台。
//
// 导航栏在 pjax 容器之外，语言列表挂在会被替换的 #iro_page_config 上，
// 所以每次页面加载都要按新配置重建菜单。
import { stripLangPrefix } from "../../app/utils/langPrefix.js";
// 静态引入：app/index 里那份是动态加载的，首屏钩子执行时可能还没到位
import { confirmDialog } from "../../app/utils/message.js";

const LANG_COOKIE = "iro-language";
const LANG_COOKIE_MAX_AGE = 34560000;
const LANG_MISSING_SUFFIX = "（未翻译）";

let outsideBound = false;

/** 收起全部语言下拉 */
function closeLangSwitchers() {
    document.querySelectorAll("[data-lang-switcher].show").forEach((switcher) => {
        switcher.classList.remove("show");
        switcher
            .querySelector(".lang-toggle")
            ?.setAttribute("aria-expanded", "false");
    });
}

/** 记录语言选择；cookie 写不进去也不能挡跳转 */
async function rememberLanguage(code) {
    try {
        await cookieStore.set({
            name: LANG_COOKIE,
            value: code,
            path: "/",
            maxAge: LANG_COOKIE_MAX_AGE,
            sameSite: "lax",
        });
    } catch {
        document.cookie = `${LANG_COOKIE}=${encodeURIComponent(code)};path=/;max-age=${LANG_COOKIE_MAX_AGE};samesite=lax`;
    }
}

/** 把标题栏与菜单的「当前语言」标记成刚选中的那一种 */
function markCurrent(switcher, code) {
    const current = switcher.querySelector(".lang-current");

    switcher.querySelectorAll(".lang-menu a").forEach((link) => {
        const isCurrent = link.dataset.lang === code;
        link.classList.toggle("current", isCurrent);

        if (isCurrent && current) {
            current.textContent = link.dataset.langName ?? link.textContent;
        }
    });
}

/** 跳到该语言版本的地址；`cache: false` 保证不是复用缓存页面 */
function navigateToLanguage(url) {
    // 服务端给的是这一语言的规范地址；拿不到时退化成剥掉前缀再问一次
    const target = String(url ?? "") || stripLangPrefix(window.location.href);

    if (typeof _iro.navigate === "function") {
        _iro.navigate(target, { cache: false });
        return;
    }

    window.location.replace(target);
}

/** cookie 里的语言选择；没答过返回空串 */
function chosenLanguage() {
    const match = document.cookie.match(
        new RegExp(`(?:^|;\\s*)${LANG_COOKIE}=([^;]*)`),
    );

    return match ? decodeURIComponent(match[1]) : "";
}

/**
 * 访客浏览器语言里第一个能对上的站点语言
 *
 * 候选表由服务端给出（`langs[].locales`）：站点启用了哪些语言、各地域变体归并到哪一种，
 * 都只在服务端的语言定义里写一份。
 */
function preferredLanguage(langs) {
    const tags = navigator.languages?.length
        ? navigator.languages
        : [navigator.language];

    for (const tag of tags) {
        const normalized = String(tag ?? "")
            .trim()
            .toLowerCase()
            .replace(/-/g, "_");

        if (normalized === "") continue;

        const exact = langs.find((lang) =>
            (lang.locales ?? []).includes(normalized),
        );

        if (exact) return exact;

        // 地域没命中就按主语言归并（en_GB 之于 en-us）
        const main = normalized.split("_")[0];
        const loose = langs.find((lang) =>
            (lang.locales ?? []).some(
                (item) => String(item).split("_")[0] === main,
            ),
        );

        if (loose) return loose;
    }

    return null;
}

/**
 * 首次访问问一次：换到访客语言的版本，还是留在这个语言
 *
 * 两个答案都要落 cookie——换就记目标语言，不换就记当前显示的语言；服务端只认这个
 * cookie，不写它就等于没答过，每次换页都会再问一遍。
 */
async function askForLanguage(langs) {
    const current = langs.find((lang) => lang.current === true);
    const preferred = preferredLanguage(langs);

    // 页面没有他那种语言的版本、或者他本来就在看那一种，都没什么可问的
    if (!current || !preferred || preferred.code === current.code) return;
    if (preferred.exists !== true || !preferred.url) return;

    // 目标地址与当前地址是同一条（草稿副本会退回原文）时也不问，换了等于没换
    if (
        new URL(preferred.url, window.location.href).pathname ===
        window.location.pathname
    ) {
        return;
    }

    const swap = await confirmDialog(
        _iro.i18n(
            "要切换到「{name}」版本吗？",
            { name: _iro.i18n(preferred.name, null, preferred.code) },
            preferred.code,
        ),
        {
            yes: _iro.i18n("是", null, preferred.code),
            no: _iro.i18n("否", null, preferred.code),
        },
    );

    await rememberLanguage(swap ? preferred.code : current.code);

    if (swap) {
        navigateToLanguage(preferred.url);
    }
}

_iro.hooks.onPageLoaded(() => {
    const langs = _iro.page?.langs;
    const usable = Array.isArray(langs) && langs.length > 1;

    // 首次访问（还没答过）才问；答过之后一切以 cookie 与 URL 为准
    if (usable && chosenLanguage() === "") {
        // 弹窗依赖按需加载的 element-plus，拉不起来就当没问过
        askForLanguage(langs).catch(() => {});
    }

    const switchers = document.querySelectorAll("[data-lang-switcher]");
    if (!switchers.length) return;

    switchers.forEach((switcher) => {
        const toggle = switcher.querySelector(".lang-toggle");
        const menu = switcher.querySelector(".lang-menu");

        if (!toggle || !menu) return;

        switcher.hidden = !usable;

        if (!usable) return;

        toggle.onclick = (event) => {
            event.stopPropagation();

            const open = !switcher.classList.contains("show");
            closeLangSwitchers();
            switcher.classList.toggle("show", open);
            toggle.setAttribute("aria-expanded", String(open));
        };

        menu.replaceChildren(
            ...langs.map((lang) => {
                const item = document.createElement("li");
                const link = document.createElement("a");
                const code = String(lang.code);

                // href 只给可访问性与「中键新开」用，跳转由下面的点击处理
                link.href = window.location.href;
                link.textContent = lang.exists
                    ? lang.name
                    : lang.name + LANG_MISSING_SUFFIX;
                link.classList.toggle("current", lang.current === true);
                link.classList.toggle("missing", lang.exists !== true);
                link.dataset.lang = code;
                link.dataset.langName = String(lang.name ?? code);

                link.addEventListener("click", async (event) => {
                    event.preventDefault();

                    if (lang.current === true) {
                        closeLangSwitchers();
                        return;
                    }

                    await rememberLanguage(code);
                    markCurrent(switcher, code);
                    closeLangSwitchers();
                    navigateToLanguage(lang.url);
                });

                item.append(link);

                return item;
            }),
        );
    });

    if (!outsideBound) {
        outsideBound = true;
        document.addEventListener("click", closeLangSwitchers);
    }
});

_iro.hooks["pjax:start"].add(closeLangSwitchers);
