<script setup>
import { useScriptTag } from "@vueuse/core";

const captchaId = _iro.config?.captcha?.geetest_captcha_id ?? "";

// 客户端只负责拿到这四项，校验交给服务端在业务动作里做
const lotNumber = ref("");
const captchaOutput = ref("");
const passToken = ref("");
const genTime = ref("");

// 验证按钮的宿主，同时用来定位所在的表单
const box = useTemplateRef("box");

// immediate 关掉：PJAX 与验证码刷新都会重挂载组件，SDK 只需执行一次
const { load } = useScriptTag("https://static.geetest.com/v4/gt4.js", undefined, {
    immediate: false,
});

let captcha = null;
let form = null;
let ready = false;
let pendingSubmit = false;
let destroyed = false;

function notify(message, type = "warning") {
    if (_iro.message) {
        _iro.message(message, type);
        return;
    }

    alert(message);
}

function validated() {
    return Boolean(
        lotNumber.value &&
        captchaOutput.value &&
        passToken.value &&
        genTime.value,
    );
}

// 没验证就提交时拦下并拉起验证，通过后自动重提，避免用户白填一遍表单
function onSubmit(event) {
    if (validated()) return;

    event.preventDefault();
    event.stopPropagation();

    if (!ready) {
        notify("验证码加载中，请稍后重试");
        return;
    }

    pendingSubmit = true;
    captcha.showCaptcha();
}

onMounted(async () => {
    if (!captchaId) {
        console.warn("[captcha] 未配置极验 Captcha ID");
        return;
    }

    try {
        if (!window.initGeetest4) {
            await load();
        }
    } catch (error) {
        console.error("[captcha] 极验脚本加载失败", error);
        return;
    }

    if (destroyed || typeof window.initGeetest4 !== "function") return;

    window.initGeetest4({ captchaId, product: "popup" }, (instance) => {
        if (destroyed) {
            instance.destroy?.();
            return;
        }

        captcha = instance;
        captcha.appendTo(box.value);

        captcha
            .onReady(() => {
                ready = true;
                form = box.value?.closest("form") ?? null;
                form?.addEventListener("submit", onSubmit, true);
            })
            .onSuccess(async () => {
                const result = captcha.getValidate();
                if (!result) return;

                lotNumber.value = result.lot_number;
                captchaOutput.value = result.captcha_output;
                passToken.value = result.pass_token;
                genTime.value = result.gen_time;

                if (!pendingSubmit || !form) return;

                pendingSubmit = false;
                // 隐藏字段绑的是 ref，等 DOM 更新完再提交，否则表单读到的是空值
                await nextTick();
                form.requestSubmit();
            })
            .onFail((fail) => {
                console.warn("[captcha] 极验验证未通过", fail);
            })
            .onClose(() => {
                pendingSubmit = false;
            })
            .onError((error) => {
                console.error("[captcha] 极验加载异常", error);
                notify("验证加载异常，请刷新页面后重试", "error");
            });
    });
});

onBeforeUnmount(() => {
    destroyed = true;
    form?.removeEventListener("submit", onSubmit, true);
    captcha?.destroy?.();
    captcha = null;
});
</script>

<template>
    <input type="hidden" name="lot_number" :value="lotNumber" />
    <input type="hidden" name="captcha_output" :value="captchaOutput" />
    <input type="hidden" name="pass_token" :value="passToken" />
    <input type="hidden" name="gen_time" :value="genTime" />
    <div ref="box" class="geetest-box"></div>
</template>
