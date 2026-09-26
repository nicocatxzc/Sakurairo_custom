import { reactive, shallowRef } from "vue";

// 编辑器页的悬浮面板挂在 body 上的独立 Shadow DOM 应用里，与触发按钮不是同一棵组件树，
// 开合状态只能靠这个模块级单例共享
export const panel = reactive({ open: false });

// “点击外部关闭”必须排除触发按钮：打开面板的那一次点击同样落在面板外部，
// 不排除的话面板刚打开就会被立刻关掉
export const panelTrigger = shallowRef(null);
