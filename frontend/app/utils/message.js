// element-plus 只在真正弹提示时才加载
// 动态 import 会让打包器退回整个命名空间导致体积膨胀
// 因此按组件路径动态引入
_iro.message = (message, type = "info") => {
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
};
