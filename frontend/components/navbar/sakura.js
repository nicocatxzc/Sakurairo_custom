import bus from "../../app/bus";

const header = document.querySelector(".site-header.sakura");
bus.on("scroll:update", (data) => {
    const { progress, direction } = data;

    if (progress >= 5 || direction == "down") {
        header.classList.add("bg");
    } else {
        header.classList.remove("bg");
    }
});

let activeSubMenu = null;

//防止子菜单量子叠加
const nav = header.querySelector(".menu");
console.log(nav)
if (nav) {
    const items = nav.querySelectorAll("li");

    items.forEach((item) => {
        console.log(item)
        const subMenu = item.querySelector(".sub-menu");
        if (!subMenu) return;

        const mainMenu = item;
        const MainMenuWidth = mainMenu.getBoundingClientRect().width;
        const subMenuWidth = subMenu.getBoundingClientRect().width;
        const offsetX = (subMenuWidth - MainMenuWidth) / 2;
        const BasicSubMenuStyle = `translateY(-10px) translateX(-${offsetX}px)`;
        subMenu.style.transform = BasicSubMenuStyle;

        // 鼠标事件
        mainMenu.addEventListener("mouseenter", () => {
            if (activeSubMenu && activeSubMenu !== subMenu) {
                activeSubMenu.classList.remove("active");
            }
            subMenu.classList.add("active");
            subMenu.style.transform = `translateY(0) translateX(-${offsetX}px)`;
            activeSubMenu = subMenu;
        });
        mainMenu.addEventListener("mouseleave", () => {
            subMenu.style.transform = BasicSubMenuStyle;
        });
    });
}
