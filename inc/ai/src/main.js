import { createApp } from "vue";
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'

import EditorButton from"./components/EditorButton.vue"
import ConfigPanel from "./components/ConfigPanel.vue"

const mounts = [
    ["#iro-ai-config", ConfigPanel],
    ["#iro-ai-editor", EditorButton],
];

function mountApps() {
    for (const [selector, component] of mounts) {
        const el = document.querySelector(selector);

        if (el) {
            createApp(component).use(ElementPlus).mount(el);
        }
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mountApps, { once: true });
} else {
    mountApps();
}
