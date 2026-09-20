<script setup>
import { VueTurnstile } from "vue-cloudflare-turnstile";

const siteKey = _iro.config.turnstile_site_key;

const turnstileToken = ref("");

async function verify(token) {
    turnstileToken.value = token;
    const { data } = axios.post(`${_iro.config.iro_api}/captcha/turnstile`, {
        turnstile_token: turnstileToken.value,
    });
}
</script>

<template>
    <input type="input" name="turnstile_token" hidden :value="turnstileToken" />
    <VueTurnstile :site-key="siteKey ?? ''" @success="verify" :theme="'auto'"/>
</template>

<style></style>
