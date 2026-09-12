import { createApp } from "vue";
import App from "./List.vue";

let commentApp = null;
function mountComment() {
    const commentList = document.querySelector(".comment-list")?.parentElement;
    if (commentList) {
        commentApp = createApp(App);
        commentApp.mount(commentList);
    }
    _iro.hooks["pjax:start"].add(
        () => {
            if (commentApp) {
                commentApp.unmount();
                commentApp = null;
            }
        },
        { once: true },
    );
}

_iro.hooks.onPageLoaded(mountComment);
