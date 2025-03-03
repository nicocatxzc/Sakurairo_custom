const nav = document.querySelector('nav');
if (!nav.classList.contains('sakura_nav')) {
    init_iro_nav();
}
function init_iro_nav() {
    // 导航栏长度限制
    function initNavWidth() {
        const nav = document.querySelector('nav');
        const checkWidth = () => {
            if (nav.offsetWidth > 1200) {
                nav.style.overflowX = 'hidden';
                nav.style.maxWidth = '1200px';
            } else {
                nav.style.overflowX = '';
                nav.style.maxWidth = '';
            }
        };
        checkWidth();
        window.addEventListener('resize', checkWidth);
    }

    document.addEventListener('DOMContentLoaded', initNavWidth);
    document.addEventListener('pjax:complete', initNavWidth);

    let siteHeader = document.querySelector('.site-header');
    let navWrapper = siteHeader.querySelector('.nav-search-wrapper');
    let siteNav = siteHeader.querySelector('nav');
    let bgNext = siteHeader.querySelector('.bg-switch');
    let articleTitle = document.querySelector('#main-container .entry-title');
    let navArticleTitle = document.querySelector('.nav-article-title');

    function ishome() {
        const isHomePage = window.location.pathname === '/';
        const inCustomize = new URLSearchParams(window.location.search).has('customize_theme');
        
        if (isHomePage && (!window.location.search || inCustomize)) {
            return true;
        } else {
            return false;
        }
    }

    function getScrollProgress() {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        
        if (scrollHeight === 0) {
            return 0;
        }
        
        return (scrollTop / scrollHeight) * 100;
    }

    if (bgNext) {
        let bgrect = bgNext.getBoundingClientRect();
        let bgwidth = bgrect.width;
        bgNext.style.maxWidth = bgwidth + 'px';
    }

    function bgSwitchState () {
        if (!bgNext) {
            return
        }
        if (!ishome()) {
            bgNext.classList.add('hide-width');
        } else {
            bgNext.classList.remove('hide-width');
        }

    }

    let titleWidth = null;
    function createTitle() {

        articleTitle = document.querySelector('#main-container .entry-title');

        if (!articleTitle) {
            if (navArticleTitle) {
                navArticleTitle.remove();
            }
            return;
        }
        
        if (articleTitle) {
            navArticleTitle = document.createElement('div');
            navArticleTitle.className = 'nav-article-title';
            navArticleTitle.textContent = articleTitle.textContent;
            
            siteNav.insertAdjacentElement('afterend', navArticleTitle);

            document.addEventListener('scroll',showTitle);
            titleWidth = navArticleTitle.getBoundingClientRect().width;
            navWidth = siteNav.getBoundingClientRect().width;
            dw = titleWidth - navWidth;
            navWrapper.style.setProperty("--dw", `${dw}px`);
        }
    }

    function showSubMenu () {
        navWrapper.style.overflow = 'unset';
    }

    function hideSubMenu() {
        navWrapper.style.removeProperty('overflow');
    }

    function showTitle () {

        if (articleTitle) {
            if (getScrollProgress() > 1 ) {
                navWrapper.dataset.scrollswap = "true";
                hideSubMenu();
            } else {
                hideTitle();
                hideSubMenu();
            }
        } else {
            delete navWrapper.dataset.scrollswap;
            showSubMenu();
        }
    }

    function hideTitle () {
        if (articleTitle) {
            delete navWrapper.dataset.scrollswap;
            showSubMenu()
        } else {
            delete navWrapper.dataset.scrollswap;
            showSubMenu()
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        createTitle ();
        bgSwitchState ();
    });

    document.addEventListener('pjax:complete', () => {
        createTitle ();
        bgSwitchState ();
    });

    siteHeader.addEventListener('mouseenter',hideTitle);
    siteHeader.addEventListener('mouseleave',showTitle);

};//iro_nav function

//以上仅新版导航栏，以下是通用部分
document.addEventListener("DOMContentLoaded", () => {

    //防止子菜单量子叠加
    const menuItems = document.querySelectorAll('nav .menu > li');
    let activeSubMenu = null;

    menuItems.forEach(item => {
        const subMenu = item.querySelector('.sub-menu');

        if (!subMenu) return;

        //鼠标移入时激活子菜单
        item.addEventListener('mouseenter', () => {
            if (activeSubMenu && activeSubMenu !== subMenu) {
                //有且仅有一个激活
                activeSubMenu.classList.remove('active');
            }

            //更新并激活当前子菜单
            subMenu.classList.add('active');
            activeSubMenu = subMenu;
        });
    });
    //放叠加结束

    //子菜单对齐
    const subMenus = document.querySelectorAll("nav .menu > li .sub-menu");

    subMenus.forEach(subMenu => {
        const MainMenu = subMenu.parentElement;

        // 获取渲染后的宽度
        const MainMenuWidth = MainMenu.getBoundingClientRect().width;
        const subMenuWidth = subMenu.getBoundingClientRect().width;

        // 偏移计算，确保子菜单居中
        const offsetX = (subMenuWidth - MainMenuWidth) / 2;

        // 设置初始样式
        const BasicSubMenuStyle = `translateY(-10px) translateX(${offsetX}px)`;
        subMenu.style.transform = BasicSubMenuStyle;

        // 设置偏移量
        MainMenu.addEventListener("mouseenter", () => {
            subMenu.style.transform = `translateY(0) translateX(${offsetX}px)`;
        });
        MainMenu.addEventListener("mouseleave", () => {
            subMenu.style.transform = BasicSubMenuStyle;
        });
    });
    //子菜单对齐结束

    //以下是窄屏/移动端通用部分
    //移动端菜单开关
    //通用部分
    let moNavButton = document.querySelector(".mo-nav-button");
    let moTocButton = document.querySelector(".mo-toc-button");
    let moNavMenu = document.querySelector(".mobile-nav");
    let moTocMenu = document.querySelector(".mo_toc_panel");
    let moHeader = document.querySelector(".site-header");
    let navTransitionHandler = null;
    let panelTransitionHandler = null;

    let panelOrderAnime = null;

    //动画监听
    function isAnyPanelOpen() {
        return moNavMenu.classList.contains("open") || moTocMenu.classList.contains("open");
    }

    function isHeaderHover() {
        const bgColor = getComputedStyle(moHeader).backgroundColor;
        return bgColor !== "transparent" && bgColor !== "rgba(0, 0, 0, 0)"; //不透明即视为Hover
    }

    function openMenu(panel, button) {

        if (isHeaderHover()) { //hover会产生背景动画，干扰相关功能，但是无需判断动画先后，直接呼出即可
            panel.classList.add("open");
            button.classList.add("open");
            moHeader.classList.add("bg");
            return;
        }

        if (navTransitionHandler) {
            moHeader.removeEventListener("transitionend", navTransitionHandler);
            navTransitionHandler = null;
            panel.classList.add("open");
            button.classList.add("open");
            moHeader.classList.add("bg");
            return;
        }

        // 先给导航栏添加背景
        if (!moHeader.classList.contains("bg")) {
            moHeader.classList.add("bg");
            button.classList.add("open");
            navTransitionHandler = function (e) {
                if (e.propertyName === "background-color") {
                    panel.classList.add("open");
                    moHeader.removeEventListener("transitionend", navTransitionHandler);
                    navTransitionHandler = null;
                }
            };
            moHeader.addEventListener("transitionend", navTransitionHandler);
        } else {
            // 已有背景
            panel.classList.add("open");
            button.classList.add("open");
        }
    }

    function closeMenu(panel, button, CloseDirect = false) {

        if (CloseDirect) { //直接关闭，hover不需要考虑背景动画顺序
            panel.classList.remove("open");
            button.classList.remove("open");
            moHeader.classList.remove("bg");
            return;
        }

        if (panelTransitionHandler) {
            panel.removeEventListener("transitionend", panelTransitionHandler);
            panelTransitionHandler = null;
            if (!isAnyPanelOpen()) {
                moHeader.classList.remove("bg");
            }
            return;
        }

        panel.classList.remove("open");
        button.classList.remove("open");

        panelTransitionHandler = function (e) {
            if (e.propertyName === "max-height") {
                // 所有面板都关闭后才撤销导航栏背景
                if (!isAnyPanelOpen()) {
                    moHeader.classList.remove("bg");
                }
                panel.removeEventListener("transitionend", panelTransitionHandler);
                panelTransitionHandler = null;
            }
        };
        panel.addEventListener("transitionend", panelTransitionHandler);
    }

    function closeThenOpen(oldPanel, newPanel) {
        let oldButton, newButton;
        // 根据面板判断对应按钮
        if (oldPanel === moNavMenu) {
            oldButton = moNavButton;
        } else if (oldPanel === moTocMenu) {
            oldButton = moTocButton;
        }

        if (newPanel === moNavMenu) {
            newButton = moNavButton;
        } else if (newPanel === moTocMenu) {
            newButton = moTocButton;
        }

        closeMenu(oldPanel, oldButton);
        banAllButton(); //动画期间禁止交互
        panelOrderAnime = function (e) {
            if (e.propertyName === "max-height") {
                oldPanel.removeEventListener("transitionend", panelOrderAnime);
                openMenu(newPanel, newButton);
                activeAllButton();
            }
        }
        oldPanel.addEventListener("transitionend", panelOrderAnime);
    }

    function banAllButton() {
        moNavButton.style.pointerEvents = "none";
        moTocButton.style.pointerEvents = "none";
    }
    function activeAllButton() {
        moNavButton.style.pointerEvents = "auto";
        moTocButton.style.pointerEvents = "auto";
    }

    // 面板
    moNavButton.addEventListener("click", function (event) {
        event.stopPropagation();

        if (moTocMenu.classList.contains("open")) {
            closeThenOpen(moTocMenu, moNavMenu);
            return;
        }
        if (moNavMenu.classList.contains("open")) {
            closeMenu(moNavMenu, moNavButton, isHeaderHover());
        } else {
            openMenu(moNavMenu, moNavButton);
        }
    });

    moTocButton.addEventListener("click", function (event) {
        event.stopPropagation();

        if (moNavMenu.classList.contains("open")) {
            closeThenOpen(moNavMenu, moTocMenu);
            generateMoToc();
            return;
        }
        if (moTocMenu.classList.contains("open")) {
            closeMenu(moTocMenu, moTocButton, isHeaderHover());
        } else {
            openMenu(moTocMenu, moTocButton);
            generateMoToc();
        }
    });

    let moUserOption = document.querySelector(".mo_toc_panel .mo-user-options");
    function generateMoToc() {
        //复制菜单
        let mainToc = document.querySelector("#main-container .toc-container .toc");
        let headToc = document.querySelector(".site-header .mo_toc_panel .mo_toc");

        if (mainToc && headToc) { //都存在
            if (mainToc.hasChildNodes()) { //不为空
                tocContent = mainToc.cloneNode(true); //复制
                moUserOption.style.display = "none"; //隐藏用户栏并显示目录
                headToc.style.display = "";

                tocContent.querySelectorAll("li").forEach(li => { //遍历层级
                    let subol = li.querySelector("ol")
                    if (subol) {
                        //含子目录
                        li.classList.add("have-child");

                        subol.classList.remove('is-collapsible');
                        subol.classList.remove('is-collapsed');
                    }
                });

                tocContent.className = "mo-toc-content";
                tocContent.removeAttribute("style");
                headToc.innerHTML = ""; //清空目标容器，防止重复
                headToc.appendChild(tocContent);
                let activeli = headToc.querySelector(".is-active-li") || headToc.querySelector("li"); //滚动至tocbot的高亮进度
                if (activeli) {
                    activeli.scrollIntoView({ block: "center" });
                }
            } else { //目录为空
                if (headToc) {
                    headToc.style.display = "none"; //隐藏目录
                }
                moUserOption.style.display = ""; //展示用户栏
            }
        } else { //没有目录
            if (headToc) {
                headToc.style.display = "none";
            }
            moUserOption.style.display = "";
        }
    }

    //二级菜单
    document.querySelectorAll(".open_submenu").forEach(function (toggle) {
        toggle.addEventListener("click", function (event) {
            event.stopPropagation();

            let parentLi = this.closest("li");
            let currentSubMenu = parentLi.querySelector(".sub-menu");

            //互斥
            document.querySelectorAll(".sub-menu.open").forEach(otherSubMenu => {
                if (otherSubMenu !== currentSubMenu) {
                    otherSubMenu.classList.remove("open");
                    let otherToggle = otherSubMenu.closest("li").querySelector(".open_submenu");
                    if (otherToggle) {
                        otherToggle.classList.remove("open");
                    }
                }
            });

            if (currentSubMenu) {
                currentSubMenu.classList.toggle("open");
                this.classList.toggle("open");
            }
        });
    });

    // 点击选项关闭
    document.querySelectorAll(".mobile-nav a, .mo_toc_panel a").forEach(link => {
        link.addEventListener("click", () => {
            closeMenu(moNavMenu, moNavButton);
            closeMenu(moTocMenu, moTocButton);
        });
    });

    // 点击空白处关闭
    document.addEventListener("click", function (event) {
        let navButton = document.querySelector(".mo-nav-button");
        let tocButton = document.querySelector(".mo-toc-button");

        if (
            moNavMenu.classList.contains("open") &&
            !moNavMenu.contains(event.target) &&
            !navButton.contains(event.target)
        ) {
            closeMenu(moNavMenu, navButton, false);

            moNavMenu.removeEventListener("transitionend", panelOrderAnime);
            moTocMenu.removeEventListener("transitionend", panelOrderAnime);
            activeAllButton();
        }

        if (
            moTocMenu.classList.contains("open") &&
            !moTocMenu.contains(event.target) &&
            !tocButton.contains(event.target)
        ) {
            closeMenu(moTocMenu, tocButton, false);

            moNavMenu.removeEventListener("transitionend", panelOrderAnime);
            moTocMenu.removeEventListener("transitionend", panelOrderAnime);
            activeAllButton();
        }

        if (
            !moNavMenu.contains(event.target) &&
            !moTocMenu.contains(event.target) &&
            !navButton.contains(event.target) &&
            !tocButton.contains(event.target)
        ) {
            moNavMenu.classList.remove("open");
            moTocMenu.classList.remove("open");
            navButton.classList.remove("open");
            tocButton.classList.remove("open");
            moHeader.removeEventListener("transitionend", navTransitionHandler);
            moNavMenu.removeEventListener("transitionend", panelTransitionHandler);
            moTocMenu.removeEventListener("transitionend", panelTransitionHandler);
            navTransitionHandler = null;
            panelTransitionHandler = null;

            moNavMenu.removeEventListener("transitionend", panelOrderAnime);
            moTocMenu.removeEventListener("transitionend", panelOrderAnime);
            activeAllButton();
        }

        // 关闭所有展开的二级菜单
        document.querySelectorAll(".sub-menu.open").forEach(function (subMenu) {
            if (!subMenu.contains(event.target)) {
                subMenu.classList.remove("open");
                let submenuToggle = subMenu.closest("li").querySelector(".open_submenu");
                if (submenuToggle) {
                    submenuToggle.classList.remove("open");
                }
            }
        });
    });

    //自动收起搜索界面
    var moSearcgInput = document.querySelector(".mo-menu-search .search-input");
    function moSearchClose() {
        moSearcgInput.blur();
        closeMenu(moNavMenu, moNavButton);
    }
    moSearcgInput.addEventListener("focus", function () {
        document.addEventListener('pjax:complete', moSearchClose);
    });
    moSearcgInput.addEventListener("blur", function () {
        document.removeEventListener("pjax:complete", moSearchClose);
    });


    //下面是自动收起、展开导航栏部分
    let lastScrollTop = 0;
    // 阈值
    const scrollThreshold = document.documentElement.scrollHeight * 0.01;

    window.addEventListener("scroll", function () {

        if (window.innerWidth < 860) {

            let moScrollTop = window.scrollY || document.documentElement.scrollTop;

            if (moScrollTop > scrollThreshold) {
                if (moScrollTop > lastScrollTop) {
                    // 向下滚动
                    if (navTransitionHandler) {
                        moHeader.removeEventListener("transitionend", navTransitionHandler);
                        navTransitionHandler = null;
                    }

                    if (panelTransitionHandler) {
                        moNavMenu.removeEventListener("transitionend", panelTransitionHandler);
                        moTocMenu.removeEventListener("transitionend", panelTransitionHandler);
                        panelTransitionHandler = null;
                    }

                    moHeader.classList.add("mo-hide");
                    moHeader.classList.remove("bg");
                    moNavMenu.classList.remove("open");
                    moNavButton.classList.remove("open");
                    moTocMenu.classList.remove("open");
                    moTocButton.classList.remove("open");

                    moNavMenu.removeEventListener("transitionend", panelOrderAnime); //移除动画监听并重新激活按钮
                    moTocMenu.removeEventListener("transitionend", panelOrderAnime);
                    activeAllButton();

                    // 同时关闭所有展开的二级菜单
                    document.querySelectorAll(".mobile-nav .sub-menu.open").forEach(function (subMenu) {
                        subMenu.classList.remove("open");
                    });
                    document.querySelectorAll(".mobile-nav .open_submenu.open").forEach(function (toggle) {
                        toggle.classList.remove("open");
                    });
                } else {
                    // 向上滚动
                    moHeader.classList.remove("mo-hide");
                    moHeader.classList.add("bg");
                }
            } else {
                // 滚动距离小于阈值
                moHeader.classList.remove("mo-hide");
            }
            if (moScrollTop <= 0 && !isAnyPanelOpen()) {
                moHeader.classList.remove("bg");
            }

            lastScrollTop = moScrollTop;
        }
    });
    moHeader.classList.remove("bg");
});