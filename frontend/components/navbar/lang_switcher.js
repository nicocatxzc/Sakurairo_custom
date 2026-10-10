// 语言切换：把选择写进 cookie，再跳到服务端为这一语言算好的地址。
//
// 不自己剥掉前缀重新请求：各语言版本的别名并不相同（译文是 foo-en-us，早期副本是
// WordPress 接上的 -2、-3），去前缀的那条路径属于原文，按它解析只会给回同一个版本，
// 选默认语言时表现为「跳过去还是原来那一篇」。也不读 <head> 里的 alternate ——
// swup 只替换列出的容器，那些链接换页后还是上一篇的旧值。地址一律取页面配置里的
// langs[].url，它每次换页都会随 #iro_page_config 重建。
//
// 导航栏在 pjax 容器之外，语言列表挂在会被替换的 #iro_page_config 上，
// 所以每次页面加载都要按新配置重建菜单。
import { stripLangPrefix } from "../../app/utils/langPrefix.js";

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

_iro.hooks.onPageLoaded(() => {
    const switchers = document.querySelectorAll("[data-lang-switcher]");
    if (!switchers.length) return;

    const langs = _iro.page?.langs;
    const usable = Array.isArray(langs) && langs.length > 1;

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
