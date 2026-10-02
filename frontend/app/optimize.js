/**
 * 弱网/省流信号。
 *
 * navigator.connection 实测无效
 * 这里仅返回明确弱网信号
 */
function isDataSaver() {
    const connection = navigator.connection;
    return (
        connection?.saveData === true ||
        matchMedia("(prefers-reduced-data: reduce)").matches
    );
}

_iro.optimize = {
    /** 用户开启了省流 */
    saveData: isDataSaver(),
};
