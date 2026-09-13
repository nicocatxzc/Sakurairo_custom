import { createApp } from "vue";
import App from "./builtin.vue";

let captahaApp = null;
function mountCaptcha() {
    const captcha = document.querySelector(".captcha");
    if (captcha) {
        captahaApp = createApp(App);
        captahaApp.mount(captcha);
    }
    _iro.hooks["pjax:start"].add(
        () => {
            if (captahaApp) {
                captahaApp.unmount();
                captahaApp = null;
            }
        },
        { once: true },
    );
}

_iro.hooks.onPageLoaded(mountCaptcha);
document.addEventListener("captcha:refresh",mountCaptcha)