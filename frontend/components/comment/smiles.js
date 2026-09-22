import { createApp } from "vue";

let smilesApp = null;
let smilesSlot = null;

function unmountSmiles() {
    smilesApp?.unmount();
    smilesApp = null;

    smilesSlot?.remove();
    smilesSlot = null;
}

async function mountSmiles() {
    if (smilesApp || !document.getElementById("emotion-toggle")) return;

    const { default: Smiles } = await import("./Smiles.vue");
    if (smilesApp) return;

    smilesSlot = document.createElement("div");
    smilesSlot.id = "iro-smiles-slot";
    document.body.appendChild(smilesSlot);

    smilesApp = createApp(Smiles);
    smilesApp.mount(smilesSlot);

    _iro.hooks["pjax:success"].add(unmountSmiles, { once: true });
}

_iro.hooks.onPageLoaded(mountSmiles);
