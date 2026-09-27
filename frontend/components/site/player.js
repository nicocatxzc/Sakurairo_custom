let player = null;
let initialized = false;

const FALLBACK_CONFIG = {
    enabled: false,
    playlist: "",
    order: "list",
    preload: "metadata",
    volume: 0.5,
    theme: "#2980b9",
};

function ensureContainer() {
    let container = document.getElementById("aplayer-float");
    if (!container) {
        container = document.createElement("div");
        container.id = "aplayer-float";
        container.className = "aplayer";
        document.body.appendChild(container);
    }
    return container;
}

function buildOptions(container, audio, config) {
    const hasLrc = audio.length > 0 && audio[0] && audio[0].lrc;
    return {
        container,
        audio,
        fixed: true,
        // 吸底模式的模板默认给 .aplayer-info 加了 display:none，只有 mini 才会把它设为 block；
        // 初始迷你态由 APlayer 自带样式收缩，点击 miniswitcher 时 APlayer 会切回 normal 展开信息。
        mini: true,
        autoplay: false,
        mutex: true,
        lrcType: hasLrc ? 3 : 0,
        listFolded: true,
        preload: config.preload || "metadata",
        theme: config.theme || "#2980b9",
        loop: "all",
        order: config.order === "random" ? "random" : "list",
        volume: typeof config.volume === "number" ? config.volume : 0.5,
        storageName: "iro-player",
    };
}

function bindFixedPlayer() {
    const fixed = document.querySelector(".aplayer.aplayer-fixed");
    if (!fixed) return;

    const body = fixed.querySelector(".aplayer-body");
    if (body) {
        body.classList.add("ap-hover");
    }

    const secondary = document.getElementById("secondary");
    const switcher = fixed.querySelector(".aplayer-miniswitcher");
    if (switcher) {
        switcher.addEventListener("click", () => {
            const collapsed = body && body.classList.contains("ap-hover");
            if (body) {
                body.classList.toggle("ap-hover", !collapsed);
            }
            if (secondary) {
                secondary.classList.toggle("active", collapsed);
            }
        });
    }

    // 歌词默认收起，首次点击播放器时展开
    let lrcShown = false;
    fixed.addEventListener("click", () => {
        if (lrcShown) return;
        lrcShown = true;
        if (player && player.lrc && typeof player.lrc.show === "function") {
            player.lrc.show();
        }
    });
}

async function initPlayer() {
    const config = Object.assign({}, FALLBACK_CONFIG, _iro.config.player || {});
    if (!config.enabled || !config.playlist) return;

    const container = ensureContainer();
    try {
        const modules = await Promise.all([
            import("aplayer"),
            import("aplayer/dist/APlayer.min.css"),
            import("./aplayer.scss"),
        ]);
        const APlayer = modules[0].default;

        const response = await fetch(config.playlist, {
            credentials: "same-origin",
            headers: _iro.config.nonce ? { "X-WP-Nonce": _iro.config.nonce } : {},
        });
        if (!response.ok) {
            console.warn(`(APlayer) HTTP ${response.status}:${response.statusText}`);
            return;
        }

        const audio = await response.json();
        if (!Array.isArray(audio) || audio.length === 0) return;

        player = new APlayer(buildOptions(container, audio, config));
        if (player.lrc && typeof player.lrc.hide === "function") {
            player.lrc.hide();
        }
        bindFixedPlayer();
    } catch (reason) {
        console.warn("播放器初始化失败：", reason);
    }
}

_iro.hooks.DOMContentLoaded.add(() => {
    if (initialized) return;
    initialized = true;
    initPlayer();
});
