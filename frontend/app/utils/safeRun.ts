/**
 * 安全地执行指定方法
 * @param fn 
 */
function safeRun(fn: Function) {
    try {
        fn();
    } catch (error) {
        console.error(fn, "在执行过程中发生错误", error);
    }
}
export default safeRun;
