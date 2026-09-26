import { createApp } from "vue";
import Builtin from "./builtin.vue";
import Turnstile from "./turnstile.vue";
import i18n from "../../../i18n";
import "../../login.scss"

window._iro = window._iro || {};

// 登录页会单独加载 captcha 入口，此时 app/index.ts 尚未执行，需要自行挂载 i18n
_iro.i18n = _iro.i18n || i18n;

const CAPTCHAS = [
    [".captcha.builtin", Builtin],
    [".captcha.turnstile", Turnstile],
];

const apps = [];

function mountCaptcha() {
    for (const [selector, component] of CAPTCHAS) {
        document.querySelectorAll(selector).forEach((el) => {
            const app = createApp(component);
            app.mount(el);
            apps.push(app);
        });
    }
}

function unmountCaptcha() {
    apps.forEach((app) => app.unmount());
    apps.length = 0;
}

if (window?._iro?.hooks) {
    _iro.hooks.onPageLoaded(mountCaptcha);
    _iro.hooks["pjax:start"].add(unmountCaptcha);
    document.addEventListener("captcha:refresh", () => {
        unmountCaptcha();
        mountCaptcha();
    });
} else {
    _iro.config = JSON.parse(
        document.querySelector("#iro_theme_config")?.innerHTML,
    );
    mountCaptcha();
}
