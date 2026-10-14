/**
 * 当前页地址与菜单项地址一致（忽略 hash）时给该链接加上 .active，高亮外观由各菜单自己的样式负责
 * @param {Element | null} menu 菜单容器
 */
export default function markActiveMenu(menu) {
    if (!menu || !_iro.config?.nav_menu_keep_active) return;

    const current = location.href.split("#")[0];

    menu.querySelectorAll("a[href]").forEach((link) => {
        const href = link.getAttribute("href") ?? "";

        // 没有真实地址（空 href 与纯锚点）时解析出来都落在当前页，不能当成指向当前页的选项
        const noAddress = href === "" || href.startsWith("#");

        link.classList.toggle(
            "active",
            !noAddress && link.href.split("#")[0] === current,
        );
    });

    // 命中的是子选项时，它上面的各级父项也要一起亮（对齐 WP 的 current-menu-ancestor）。
    // 各级 li 自身的链接排在它的下级菜单之前，所以取 li 里的第一个 a 就是它自己的那个
    menu.querySelectorAll("li").forEach((item) => {
        item.querySelector("a[href]")?.classList.toggle(
            "active",
            item.querySelector("a.active") !== null,
        );
    });
}
