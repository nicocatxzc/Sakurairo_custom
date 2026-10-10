// element-plus 只在真正弹提示时才加载
// 动态 import 会让打包器退回整个命名空间导致体积膨胀
// 因此按组件路径动态引入
export function message(message, type = "info") {
    Promise.all([
        import("element-plus/es/components/message/style/css"),
        import("element-plus/es/components/message/index"),
    ]).then(([, { ElMessage }]) => {
        ElMessage({
            dangerouslyUseHTMLString: true,
            message: message,
            type: type,
            showClose: true,
        });
    });
}

// 二元确认框：把「取消/关闭」一并归成 false，调用方只关心选没选「是」
export function confirmDialog(message, { yes = "是", no = "否" } = {}) {
    return Promise.all([
        import("element-plus/es/components/message-box/style/css"),
        import("element-plus/es/components/message-box/index"),
    ]).then(([, { ElMessageBox }]) =>
        ElMessageBox.confirm(message, "", {
            confirmButtonText: yes,
            cancelButtonText: no,
            showClose: false,
            closeOnClickModal: false,
            distinguishCancelAndClose: false,
        }).then(
            () => true,
            () => false,
        ),
    );
}

// 挂到 _iro 上：组件在点击这类用户触发的时机直接用 _iro.message 就够了。
// confirmDialog 只在页面加载钩子里用（首次访问的语言询问），那边直接 import 本模块。
_iro.message = message;
