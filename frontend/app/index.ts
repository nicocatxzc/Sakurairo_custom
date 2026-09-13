// 全局命名空间扩展
declare global {
    interface Window {
        _iro: IroNamespace;
    }
}

interface HookOptions {
    once?: boolean;
}

type HookFn = () => void;

type HookItem = HookFn | [HookFn, HookOptions];

interface HookQueue extends Array<HookItem> {
    add(fn: HookFn, options?: HookOptions): number;
    push(...items: HookItem[]): number;
}

interface DomReadyHook {
    push(fn: HookFn): void;

    add(fn: HookFn, options?: HookOptions): void;
}

/** 主题前端全局配置对象 */
interface IroNamespace {
    hooks: {
        DOMContentLoaded: DomReadyHook;
        "pjax:start": HookQueue;
        "pjax:success": HookQueue;
        "pjax:complete": HookQueue;
        "pjax:end": HookQueue;
        "pjax:error": HookQueue;
        onPageLoaded: (fn: HookFn) => void;
    };

    config?: Record<string, unknown>;

    page?: Record<string, unknown>;

    user?: Record<string, unknown>;
}

window._iro = window._iro || ({} as IroNamespace);

const domReadyHooks: HookItem[] = [];

/**
 * 创建一个带 add 方法的钩子队列
 * @returns 可链式添加钩子的队列
 */
function createHookQueue(): HookQueue {
    const queue = [] as unknown as HookQueue;

    // 以不可枚举方式定义 add 方法，避免污染 for...in 遍历
    Object.defineProperty(queue, "add", {
        enumerable: false,
        value(fn: HookFn, options: HookOptions = {}): number {
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
function runHookQueue(queue: HookItem[]): void {
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

_iro.hooks = {
    DOMContentLoaded: {
        push(fn: HookFn): void {
            if (document.readyState !== "loading") {
                fn();
            } else {
                domReadyHooks.push(fn);
            }
        },
        add(fn: HookFn, options: HookOptions = {}): void {
            if (document.readyState !== "loading") {
                fn();
            } else {
                domReadyHooks.push([fn, options]);
            }
        },
    },
    "pjax:start": createHookQueue(),
    "pjax:success": createHookQueue(),
    "pjax:complete": createHookQueue(),
    "pjax:end": createHookQueue(),
    "pjax:error": createHookQueue(),
    onPageLoaded: (fn: HookFn) => {
        _iro.hooks.DOMContentLoaded.push(fn);
        _iro.hooks["pjax:complete"].push(fn);
    },
};

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

export default _iro;

// 事件总线
import("./bus");
// 滚动广播
import("./stores/scroll");
// 暗色模式
import("./darkmode");
// pjax
import("./pjax");

import("./utils/message")
