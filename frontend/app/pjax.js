import Swup from "swup";

const swup = _iro.isBackend
    ? null
    : new Swup({
          containers: [
              "#pjax-main",
              "#iro_page_config",
              "#iro_theme_style_dymanic_vars",
              // 本页用到的古腾堡区块样式
              "#iro_block_styles",
              // 单页视图才有的文章排版样式
              "#iro_post_style",
          ],
          linkSelector:
              'a[href]:not(.no-pjax):not(* .no-pjax):not([href*="/wp-login.php"]):not([href*="/wp-admin"]):not([target="_blank"]):not([download])',
          animationSelector: false,

          scrollTo: (event) => {
              const url = event.to.url;

              // 首页或 /page/* 滚动到 #articles
              if (url === "/" || url.startsWith("/page/")) {
                  const target = document.querySelector("#articles");
                  if (target) {
                      return (
                          target.getBoundingClientRect().top +
                          window.pageYOffset -
                          20
                      );
                  }
              }

              // 其他页面滚动到顶部
              return 0;
          },
      });

_iro.navigate = swup?.navigate.bind(swup);

swup?.hooks.on("visit:start", (visit) => {
    document.dispatchEvent(new CustomEvent("pjax:start", { detail: {} }));
    const url = new URL(visit.to.url, window.location.origin);

    if (url.pathname.startsWith("/page/")) {
        visit.scroll.reset = false;
    }
});

// 部分脚本不会随 PJAX 重新执行，换页后要对新内容补一次生效时机
swup?.hooks.on("content:replace", () => {
    document.querySelectorAll("#pjax-main iframe[loading='lazy']").forEach((iframe) => {
        // 被脚本用内联样式藏起来、等加载完再握手的 iframe 全靠这一次加载；
        // PJAX 后视口停在顶部，它落在懒加载阈值之外，
        // 握手永远等不到，只剩可见的兜底内容
        if (iframe.style.visibility === "hidden") {
            iframe.loading = "eager";
        }
    });

    // 重放一次 DOMContentLoaded，让部分绑定该事件的脚本对新内容重新生效。
    document.dispatchEvent(
        new CustomEvent("DOMContentLoaded", {
            bubbles: true,
            detail: { pjax: true },
        }),
    );
});

swup?.hooks.on("content:replace", () => {
    document.dispatchEvent(new CustomEvent("pjax:success", { detail: {} }));
});

swup?.hooks.on("page:view", () => {
    document.dispatchEvent(new CustomEvent("pjax:complete", { detail: {} }));
});

swup?.hooks.on("visit:end", () => {
    document.dispatchEvent(new CustomEvent("pjax:end", { detail: {} }));
});

swup?.hooks.on("visit:fail", () => {
    document.dispatchEvent(new CustomEvent("pjax:error", { detail: {} }));
});

swup?.hooks.on("visit:abort", () => {
    document.dispatchEvent(new CustomEvent("pjax:error", { detail: {} }));
    document.dispatchEvent(new CustomEvent("pjax:complete", { detail: {} }));
    document.dispatchEvent(new CustomEvent("pjax:end", { detail: {} }));
});
