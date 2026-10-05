// 挂件每秒更新一次目标点，由css动画补间，
// 随机从左或右游入，横穿水面后游出可视区，短暂停顿再换一个重新游入。
// 统一换算成波浪容器自身的局部坐标，封面是相对定位时同样成立。

const TICK = 1000;
const DRIFT_STEP = 40; // 每秒的水平位移，决定横穿水面要多快
const RESPAWN_DELAY = 1500; // 游出可视区后隔多久换一个重新游入
const BOB_AMPLITUDE = 3;
const DROP_DURATION = 800; // 与 .iro-wave__floating--dropping 的 0.8s 对齐
const STAR_MIN_COUNT = 14;
const STAR_AREA_PER_STAR = 2800;
const READY = "1";
const HIDDEN = "iro-wave__floating--hidden";
const FLOATING = "iro-wave__floating--floating";
const DRAGGING = "iro-wave__floating--dragging";
const DROPPING = "iro-wave__floating--dropping";

// 星星只生成一次，非首页时波浪是 display: none，量不到尺寸，
// 所以等到它可见后（tick 里）再生成
function buildStars(wave) {
    const host = wave.querySelector(".iro-wave__stars");

    if (!host || host.dataset.iroStars === READY) {
        return;
    }

    host.dataset.iroStars = READY;

    const count = Math.max(
        STAR_MIN_COUNT,
        Math.round((wave.clientWidth * wave.clientHeight) / STAR_AREA_PER_STAR),
    );
    const fragment = document.createDocumentFragment();

    for (let index = 0; index < count; index += 1) {
        const star = document.createElement("i");

        star.style.setProperty("--iro-star-x", `${(Math.random() * 100).toFixed(2)}%`);
        star.style.setProperty("--iro-star-y", `${(5 + Math.random() * 90).toFixed(2)}%`);
        star.style.setProperty("--iro-star-size", `${(1 + Math.random() * 2.2).toFixed(2)}px`);
        star.style.setProperty("--iro-star-speed", `${(2.4 + Math.random() * 3.6).toFixed(2)}s`);
        star.style.setProperty("--iro-star-delay", `${(Math.random() * 4).toFixed(2)}s`);
        fragment.appendChild(star);
    }

    host.appendChild(fragment);
}

function initWave(wave) {
    // 波浪在 pjax 容器之外，整个会话只有一份，重复进入只初始化一次
    if (wave.dataset.iroWaveReady === READY) {
        return;
    }

    wave.dataset.iroWaveReady = READY;

    if (wave.getClientRects().length) {
        buildStars(wave);
    }

    const items = Array.from(wave.querySelectorAll(".iro-wave__floating")).map((el) => ({ el }));

    // 同一时刻只放一个挂件在水面上，其余收起来等下一次刷新
    let ready = false;
    let active = -1;
    let waiting = false;
    let waitTimer = 0;
    let x = 0;
    let dx = DRIFT_STEP;
    let phase = 0;
    let angle = 0;
    let dragging = false;
    let dropping = false;
    let pointerId = null;
    let grabX = 0;
    let grabY = 0;

    // 图没下载完时量不到真实宽度，出生点会算错、挂件一出现就露在水面上，
    // 所以等素材就绪再开始游；素材卡住时也要有个上限，不能永远不出场
    Promise.race([
        Promise.all(items.map(({ el }) => el.querySelector("img")?.decode().catch(() => {}))),
        new Promise((resolve) => window.setTimeout(resolve, 4000)),
    ]).then(() => {
        ready = true;
    });

    // 水面基准线就是容器底边：挂件底边贴着它，下半截正好落在水层里
    const waterline = (item) => wave.clientHeight - item.el.offsetHeight;

    const apply = (item) => {
        item.el.style.left = `${x}px`;
        item.el.style.top = `${waterline(item) + Math.sin(phase) * BOB_AMPLITUDE}px`;
        item.el.style.transform = `rotate(${angle}deg)`;
    };

    const retire = () => {
        if (active < 0) {
            return;
        }

        items[active].el.classList.add(HIDDEN);
        active = -1;
        waiting = true;
        window.clearTimeout(waitTimer);
        waitTimer = window.setTimeout(() => {
            waiting = false;
        }, RESPAWN_DELAY);
    };

    const spawn = () => {
        if (active >= 0) {
            items[active].el.classList.add(HIDDEN);
        }

        let index = Math.floor(Math.random() * items.length);

        // 配置了多张时不要连着刷出同一张
        if (items.length > 1 && index === active) {
            index = (index + 1) % items.length;
        }

        active = index;
        const item = items[active];
        const width = item.el.offsetWidth || item.el.offsetHeight;

        dx = Math.random() > 0.5 ? DRIFT_STEP : -DRIFT_STEP;
        x = dx > 0 ? -width : wave.clientWidth;
        phase = Math.random() * Math.PI * 2;
        angle = 0;

        // 出生点不能带过渡，否则会从上一轮的落点补间过来
        item.el.classList.remove(HIDDEN, DRAGGING, DROPPING, FLOATING);
        apply(item);
        void item.el.offsetWidth;
        item.el.classList.add(FLOATING);
    };

    items.forEach((item) => {
        const { el } = item;

        el.classList.add(HIDDEN);

        el.addEventListener("pointerdown", (event) => {
            if (event.button !== 0 || active < 0 || items[active] !== item) {
                return;
            }

            event.preventDefault();
            const rect = el.getBoundingClientRect();

            dragging = true;
            pointerId = event.pointerId;
            grabX = event.clientX - rect.left;
            grabY = event.clientY - rect.top;
            el.classList.remove(FLOATING, DROPPING);
            el.classList.add(DRAGGING);
            el.setPointerCapture(event.pointerId);
        });

        el.addEventListener("pointermove", (event) => {
            if (!dragging || event.pointerId !== pointerId) {
                return;
            }

            const rect = wave.getBoundingClientRect();

            x = event.clientX - rect.left - grabX;
            el.style.left = `${x}px`;
            el.style.top = `${event.clientY - rect.top - grabY}px`;
        });

        const endDrag = (event) => {
            if (!dragging || event.pointerId !== pointerId) {
                return;
            }

            dragging = false;
            pointerId = null;
            el.classList.remove(DRAGGING);

            // 拖到可视区外就当它自己游走了，直接进入刷新流程
            if (x < -el.offsetWidth || x > wave.clientWidth) {
                retire();
                return;
            }

            dropping = true;
            angle = 0;
            el.classList.add(DROPPING);
            apply(item);
            window.setTimeout(() => {
                dropping = false;
                el.classList.remove(DROPPING);
                el.classList.add(FLOATING);
            }, DROP_DURATION);
        };

        el.addEventListener("pointerup", endDrag);
        el.addEventListener("pointercancel", endDrag);
    });

    window.setInterval(() => {
        // 非首页时封面 display: none，整块跳过，不做无意义的重排
        if (!wave.getClientRects().length) {
            return;
        }

        buildStars(wave);

        if (!items.length) {
            return;
        }

        if (active < 0) {
            if (ready && !waiting) {
                spawn();
            }
            return;
        }

        if (dragging || dropping) {
            return;
        }

        const item = items[active];
        const width = item.el.offsetWidth;

        x += dx;

        // 整只游出可视区就收起来，等一小会儿换一个重新游入
        if (x < -width || x > wave.clientWidth) {
            retire();
            return;
        }

        phase += 0.4;
        angle = Math.random() * 4 - 2;
        apply(item);
    }, TICK);
}

_iro.hooks.onPageLoaded(() => {
    document.querySelectorAll(".iro-wave").forEach((wave) => {
        // 封面没渲染时波浪挂在封面容器外，没有封面替它隐藏，得自己跟着首页状态走
        if (!wave.closest(".homepage-cover")) {
            wave.classList.toggle("hide", !_iro.page?.is_home);
        }

        initWave(wave);
    });
});
