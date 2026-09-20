<script setup>
import { VueTurnstile } from "vue-cloudflare-turnstile";

const siteKey = _iro.config.turnstile_site_key

async function verify(token) {
    
    console.log(res);
}
</script>

<template>
    <VueTurnstile :site-key=" siteKey ?? ''" @success="verify" />
</template>

<style></style>
