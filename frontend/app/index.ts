import i18n from "../i18n";
import safeRun from "./utils/safeRun";

// _iro 及其成员的类型声明见 types/iro.d.ts
window._iro = window._iro || ({} as IroNamespace);

_iro.utils = {};

// 全局翻译对象，语言在运行时惰性读取 _iro.config.language
_iro.i18n = i18n;

const domReadyHooks: IroHookItem[] = [];

// 后台要复用验证码模块，因为某些复杂的原因前台模块没成功拆出来，这里先卡着不让前台逻辑执行
_iro.isBackend = !document.querySelector("#iro_page_config");

/**
 * 创建一个带 add 方法的钩子队列
 * @returns 可链式添加钩子的队列
 */
function createHookQueue(): IroHookQueue {
    const queue = [] as unknown as IroHookQueue;

    // 以不可枚举方式定义 add 方法，避免污染 for...in 遍历
    Object.defineProperty(queue, "add", {
        enumerable: false,
        value(fn: IroHookFn, options: IroHookOptions = {}): number {
            queue.push([fn, options]);
            return queue.length;
        },
    });

    return queue;
}

/**
 * 依次执行钩子队列中的函数
 * 支持普通函数与 [fn, options] 二维两种形式
 * 执行异常时捕获并打印，配置 once 的函数执行后自动移除
 * @param queue 待执行的钩子队列
 */
function runHookQueue(queue: IroHookItem[]): void {
    for (let i = 0; i < queue.length; i++) {
        const item = queue[i];

        // push(fn)
        // add(fn, options)
        const isTuple = Array.isArray(item);
        const fn = isTuple ? item[0] : item;
        const options = isTuple ? item[1] : undefined;

        if (typeof fn !== "function") {
            continue;
        }

        try {
            fn();
        } catch (error) {
            console.error(`${fn}执行发生错误`, error);
        } finally {
            if (options?.once === true) {
                // 这里删除的是当前正在执行的这一项
                queue.splice(i, 1);
                i--;
            }
        }
    }
}

/**
 * 创建一个首屏阶段钩子
 *
 * 阶段由 head 里的 theme_performance.php 提前产生，并挂在 window.iroPerformance 上；
 * 主包可能晚于阶段下载完，所以和 DOMContentLoaded 一样：已过则立即执行，未过则等事件
 * @param stage 阶段名，对应预产生的标记与 performance:<stage> 事件
 * @returns 可链式添加钩子的队列
 */
function createPaintHook(stage: "fcp" | "lcp"): IroDomReadyHook {
    const queue = createHookQueue();

    if (!_iro.isBackend) {
        document.addEventListener(`performance:${stage}`, () => {
            runHookQueue(queue);
        });
    }

    function isFired(): boolean {
        return window.iroPerformance?.[stage] === true;
    }

    return {
        push(fn: IroHookFn): void {
            if (_iro.isBackend) return;
            if (isFired()) {
                safeRun(fn);
            } else {
                queue.push(fn);
            }
        },
        add(fn: IroHookFn, options: IroHookOptions = {}): void {
            if (_iro.isBackend) return;
            if (isFired()) {
                safeRun(fn);
            } else {
                queue.push([fn, options]);
            }
        },
    };
}

_iro.hooks = {
    DOMContentLoaded: {
        push(fn: IroHookFn): void {
            if (_iro.isBackend) return;
            if (document.readyState !== "loading") {
                fn();
            } else {
                domReadyHooks.push(fn);
            }
        },
        add(fn: IroHookFn, options: IroHookOptions = {}): void {
            if (_iro.isBackend) return;
            if (document.readyState !== "loading") {
                fn();
            } else {
                domReadyHooks.push([fn, options]);
            }
        },
    },
    fcp: createPaintHook("fcp"),
    lcp: createPaintHook("lcp"),
    "pjax:start": createHookQueue(),
    "pjax:success": createHookQueue(),
    "pjax:complete": createHookQueue(),
    "pjax:end": createHookQueue(),
    "pjax:error": createHookQueue(),
    onPageLoaded: (fn: IroHookFn) => {
        _iro.hooks.DOMContentLoaded.push(fn);
        _iro.hooks["pjax:complete"].push(fn);
    },
};
import("./plugins/client");
if (!_iro.isBackend) {
    // DOM 就绪后统一执行队列
    document.addEventListener("DOMContentLoaded", () => {
        runHookQueue(domReadyHooks);
    });

    // 为所有 pjax 生命周期事件绑定钩子队列执行
    (
        [
            "pjax:start",
            "pjax:success",
            "pjax:complete",
            "pjax:end",
            "pjax:error",
        ] as const
    ).forEach((event) => {
        document.addEventListener(event, () => {
            runHookQueue(_iro.hooks[event]);
        });
    });

    function initFrontConfig(): void {
        _iro.config = JSON.parse(
            document.querySelector("#iro_theme_config")!.innerHTML,
        );
        _iro.page = JSON.parse(
            document.querySelector("#iro_page_config")?.innerHTML ?? "{}",
        );
        _iro.user = JSON.parse(
            document.querySelector("#iro_user_config")?.innerHTML ?? "{}",
        );
    }
    _iro.hooks.onPageLoaded(initFrontConfig);

    // 这部分会被提升，需要自己处理
    import("./utils/missImg");
    // 事件总线
    import("./bus");
    // 弱网优化
    import("./optimize");
    // 滚动广播
    import("./stores/scroll");
    // 视口尺寸广播
    import("./stores/resize");
    // 暗色模式
    import("./darkmode");
    // pjax
    import("./pjax");

    import("./utils/message");

    import("./plugins/postViews");
    // 主色填充上的文字色
    import("./plugins/themeContrast");
}
export default _iro;
