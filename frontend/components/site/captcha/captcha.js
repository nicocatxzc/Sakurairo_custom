import { createApp } from "vue";
import Builtin from "./builtin.vue";
import Turnstile from "./turnstile.vue";

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

_iro.hooks.onPageLoaded(mountCaptcha);
_iro.hooks["pjax:start"].add(unmountCaptcha);
document.addEventListener("captcha:refresh", () => {
    unmountCaptcha();
    mountCaptcha();
});
