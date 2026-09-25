<script setup>
const { toggle } = panel;
</script>

<template>
    <button
        type="button"
        class="button button-primary iro-ai-trigger"
        @click="toggle()"
    >
        <span class="dashicons dashicons-superhero" aria-hidden="true"></span>
        打开AI工具
    </button>
</template>

<style lang="scss"></style>
