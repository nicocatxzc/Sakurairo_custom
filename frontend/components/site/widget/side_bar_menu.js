let menu = null;
let expandedItem = null;

/**
 * 展开/收起子菜单
 * @param {number} index 菜单项下标
 */
function toggleSubMenu(index) {
    expandedItem = expandedItem === index ? null : index;

    menu?.querySelectorAll(".menu > .item").forEach((item, i) => {
        const expanded = i === expandedItem;
        const button = item.querySelector(".button");

        button?.classList.toggle("expand", expanded);
        button?.setAttribute("aria-expanded", String(expanded));
        item.querySelector(".sub-menu")?.classList.toggle("expand", expanded);
    });
}

/** 收起所有已展开的子菜单 */
function collapse() {
    expandedItem = null;

    menu?.querySelectorAll(".expand").forEach((el) => {
        el.classList.remove("expand");
        el.setAttribute("aria-expanded", "false");
    });
}

/**
 * 初始化：侧栏在 pjax 容器之外，服务端渲染一次，只需绑定一次
 */
function initSideBarMenu() {
    const el = document.querySelector(".iro-sidebar-menu");
    if (!el || el === menu) return;

    menu = el;
    expandedItem = null;

    menu.querySelectorAll(".menu > .item").forEach((item, index) => {
        item.querySelector(".button")?.addEventListener("click", () =>
            toggleSubMenu(index),
        );
    });
}

// 跳转时收起，与移动端导航栏一致
_iro.hooks.onPageLoaded(initSideBarMenu);
_iro.hooks["pjax:start"].add(collapse);
