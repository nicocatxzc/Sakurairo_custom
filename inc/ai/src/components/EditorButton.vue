<script setup>
import { onMounted, ref } from "vue";
import { MagicStick } from "@element-plus/icons-vue";
import { panel, panelTrigger } from "../panel";

const buttonRef = ref(null);

onMounted(() => {
    panelTrigger.value = buttonRef.value;
});
</script>

<template>
    <el-button ref="buttonRef" class="iro-ai-trigger" type="primary" :icon="MagicStick" :aria-expanded="panel.open"
        @click="panel.open = !panel.open">
        打开AI工具
    </el-button>
</template>

<style lang="scss">
.iro-ai-trigger {
    width: 100%;
}
</style>
