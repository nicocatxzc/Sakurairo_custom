<script setup>
import { useTimeoutFn } from "@vueuse/core";

let id = ref("");
let image = ref("");
let answer = ref("");
let inputRef = useTemplateRef("captchaInput");
const t = _iro.i18n.t;

onMounted(async () => {
    await getCaptcha();
});

let imageShow = ref(false);
const { start: startHideTimer, stop: stopHideTimer } = useTimeoutFn(
    () => {
        setImageShow(false);
    },
    1000,
    {
        immediate: false,
    },
);

function setImageShow(stat) {
    stopHideTimer();
    if (stat) {
        inputRef.value.placeholder = t("点击图片可以刷新");
    } else {
        inputRef.value.placeholder = t("点击显示验证码");
    }
    imageShow.value = stat;
}

async function getCaptcha() {
    stopHideTimer();
    image.value =
        "data:image/svg+xml;charset=utf-8,%3Csvg%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cstyle%3E.spinner_ajPY%7Btransform-origin%3Acenter%3Banimation%3Aspinner_AtaB%20.75s%20infinite%20linear%7D%40keyframes%20spinner_AtaB%7B100%25%7Btransform%3Arotate(360deg)%7D%7D%3C%2Fstyle%3E%3Cpath%20d%3D%22M12%2C1A11%2C11%2C0%2C1%2C0%2C23%2C12%2C11%2C11%2C0%2C0%2C0%2C12%2C1Zm0%2C19a8%2C8%2C0%2C1%2C1%2C8-8A8%2C8%2C0%2C0%2C1%2C12%2C20Z%22%20opacity%3D%22.25%22%2F%3E%3Cpath%20d%3D%22M10.14%2C1.16a11%2C11%2C0%2C0%2C0-9%2C8.92A1.59%2C1.59%2C0%2C0%2C0%2C2.46%2C12%2C1.52%2C1.52%2C0%2C0%2C0%2C4.11%2C10.7a8%2C8%2C0%2C0%2C1%2C6.66-6.61A1.42%2C1.42%2C0%2C0%2C0%2C12%2C2.69h0A1.57%2C1.57%2C0%2C0%2C0%2C10.14%2C1.16Z%22%20class%3D%22spinner_ajPY%22%2F%3E%3C%2Fsvg%3E";
    answer.value = "";
    let { data } = await axios.get(`${_iro.config.iro_api}/captcha`);
    image.value = data.data;
    id.value = data.id;
}
// async function verifyCaptcha() {
//     await axios.post(`${_iro.config.iro_api}/captcha`, {
//         captcha_id: id.value,
//         captcha_text: answer.value,
//     });
// }
</script>

<template>
    <div ref="captchaContainer" class="captcha-container flex-center">
        <div class="image-button flex-center">
            <i
                class="fa-icon-solid fa-circle-info icon"
                @click="setImageShow(!imageShow)"
            />
            <img
                :src="image"
                class="image"
                :class="{
                    show: imageShow,
                }"
                :alt="t('验证码')"
                :title="t('点击刷新')"
                @click="getCaptcha"
                @mouseleave="startHideTimer"
            />
        </div>
        <input
            type="text"
            v-model="id"
            name="captcha_id"
            class="captcha_id"
            hidden
        />
        <input
            ref="captchaInput"
            v-model="answer"
            name="captcha_text"
            type="text"
            class="input"
            :placeholder="t('点击显示验证码')"
            autocomplete="off"
            @click="setImageShow(true)"
            @focus="setImageShow(true)"
            @blur="startHideTimer"
        />
        <!-- <button class="button" type="button" @click="verifyCaptcha()">
            点击验证
        </button> -->
    </div>
</template>

<style scoped>
.captcha-container {
    position: relative;
    gap: 0.3rem;
    height: 2.75rem;
    padding: 0.2rem;
    background-color: var(--widget-background-color);
    border: var(--border-sketch);
    border-radius: 0.5rem;
}
.image-button {
    height: 2rem;
    width: 2rem;
    border: var(--border-sketch);
    border-radius: 0.5rem;
}
.image {
    position: absolute;
    visibility: hidden;
    height: fit-content;
    width: fit-content;
    object-fit: contain;
    transform: translateY(-70%) translateX(40%);
    border-radius: 0.5rem;
}
.image.show {
    visibility: visible;
}

.input {
    margin: auto;
    width: 10rem;
    height: 100%;
    padding: 0.5rem;
    font-size: 1rem;
    border: var(--border-sketch);
    outline: none;
}
.captcha_id {
    display: none;
}
.button {
    height: 100%;
    width: 6rem;
    border: var(--border-sketch);
}
</style>
