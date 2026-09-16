// 根据宽度计算每页显示数量
function getSocialPageSize(width) {
    if (width < 310) return 1;
    if (width < 360) return 2;
    if (width < 410) return 3;
    if (width < 460) return 4;
    if (width < 510) return 5;
    if (width < 560) return 6;
    if (width < 610) return 7;
    return 8;
}

// 分页器
class SocialPager {
    constructor(container) {
        this.container = container;
        this.items = Array.from(container.querySelectorAll(".social-item"));
        this.total = this.items.length;

        this.prevBtn = container.querySelector(".site-pagination.prev");
        this.nextBtn = container.querySelector(".site-pagination.next");

        this.pageSize = 0;
        this.pages = [];
        this.currentIndex = 0;
        this.state = "next"; // "prev" | "next"

        this._build();
        this._bind();
    }

    _build() {
        this.pageSize = getSocialPageSize(window.innerWidth);
        this.pages = [];

        for (let i = 0; i < this.total; i += this.pageSize) {
            const group = [];
            for (let j = i; j < Math.min(i + this.pageSize, this.total); j++) {
                group.push(j);
            }
            this.pages.push(group);
        }

        this.currentIndex = 0;
        this._apply();
        this._togglePagination();
    }

    _apply() {
        const current = this.pages[this.currentIndex] || [];

        let showItem;
        let pageCon = this.container.querySelector(".page-container");
        this.items.forEach((item, index) => {
            if (current.includes(index)) {
                if (!showItem) {
                    showItem = item;
                    pageCon.style.overflow = "hidden";
                    showItem.addEventListener(
                        "transitionend",
                        (e) => {
                            setTimeout(() => {
                                pageCon.style.overflow = "visible";
                            }, 0);
                        },
                        { once: true },
                    );
                }
            }
            item.classList.toggle("show", current.includes(index));
            item.classList.toggle("prev", this.state === "prev");
            item.classList.toggle("next", this.state === "next");
        });
    }

    _togglePagination() {
        const canPagination = this.total > this.pageSize;
        const display = canPagination ? true : false;
        if (this.prevBtn) this.prevBtn.classList.toggle("hide", !display);
        if (this.nextBtn) this.nextBtn.classList.toggle("hide", !display);
    }

    prev() {
        const n = this.pages.length;
        if (!n) return;
        this.currentIndex = (this.currentIndex - 1 + n) % n;
        this.state = "prev";
        this._apply();
    }

    next() {
        const n = this.pages.length;
        if (!n) return;
        this.currentIndex = (this.currentIndex + 1) % n;
        this.state = "next";
        this._apply();
    }

    resize() {
        this._build();
    }

    _bind() {
        if (this.prevBtn) {
            this.prevBtn.addEventListener("click", () => this.prev());
        }
        if (this.nextBtn) {
            this.nextBtn.addEventListener("click", () => this.next());
        }
    }
}

// 全局 Pager 管理
const socialPagers = new Set();

function initSocialPagers() {
    document.querySelectorAll(".social-links").forEach((container) => {
        // 防止重复初始化
        if (container.dataset.pagerInit === "1") return;
        container.dataset.pagerInit = "1";
        socialPagers.add(new SocialPager(container));
    });
}

// 清理已被 PJAX 移除的 pager
function cleanupSocialPagers() {
    for (const pager of socialPagers) {
        if (!document.contains(pager.container)) {
            socialPagers.delete(pager);
        }
    }
}

// resize
let socialResizeTimer = null;
window.addEventListener("resize", () => {
    clearTimeout(socialResizeTimer);
    socialResizeTimer = setTimeout(() => {
        cleanupSocialPagers();
        socialPagers.forEach((pager) => pager.resize());
    }, 100);
});

// 注册到总线
_iro.hooks["DOMContentLoaded"].add(() => {
    initSocialPagers();
});
