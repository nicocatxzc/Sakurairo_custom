// 全局类型声明：本文件必须保持脚本（script）形态，一旦出现顶层 import/export 就会变成模块，
// 其中声明的 _iro / Window 将不再进入全局作用域，所有文件都会退回 TS2304 Cannot find name '_iro'。

type IroHookFn = () => void;

interface IroHookOptions {
    once?: boolean;
}

type IroHookItem = IroHookFn | [IroHookFn, IroHookOptions];

interface IroHookQueue extends Array<IroHookItem> {
    add(fn: IroHookFn, options?: IroHookOptions): number;
    push(...items: IroHookItem[]): number;
}

interface IroDomReadyHook {
    push(fn: IroHookFn): void;
    add(fn: IroHookFn, options?: IroHookOptions): void;
}

interface IroHooks {
    DOMContentLoaded: IroDomReadyHook;
    "pjax:start": IroHookQueue;
    "pjax:success": IroHookQueue;
    "pjax:complete": IroHookQueue;
    "pjax:end": IroHookQueue;
    "pjax:error": IroHookQueue;
    onPageLoaded(fn: IroHookFn): void;
}

interface IroParticleBuiltin {
    amount?: number;
    speed?: number;
    minsize?: number;
    maxsize?: number;
    [key: string]: unknown;
}

/** 对应 theme_config.php 中 #iro_theme_config 的输出，供收紧 config 类型时使用 */
interface IroThemeConfig {
    language?: string;
    api?: string;
    ajaxurl?: string;
    iro_api?: string;
    typed_config?: unknown;
    particle?: {
        select?: string;
        builtin?: IroParticleBuiltin;
        config?: unknown;
    };
    pagination_mode?: string;
    pagination_ajax_wait?: number;
    missing_images?: string;
    missing_avatars?: string;
    bangumi_source?: string;
    lightbox?: string;
    code_highlight?: string;
    code_katex?: boolean;
    turnstile_site_key?: string;
    [key: string]: unknown;
}

/** 对应 #iro_page_config */
interface IroPageConfig {
    post_id?: number;
    is_home?: boolean;
    [key: string]: unknown;
}

/** 对应 #iro_user_config */
interface IroUserConfig {
    id?: number;
    name?: string;
    email?: string;
    roles?: string[];
    description?: string;
    slug?: string;
    avatar?: {
        url_96?: string;
        url_150?: string;
        url_300?: string;
    };
    [key: string]: unknown;
}

interface IroScrollUpdateEvent {
    progress: number;
    direction: "up" | "down" | "none";
}

/** mitt 事件总线，实例见 app/bus.js */
interface IroBus {
    on(
        type: "scroll:update",
        handler: (event: IroScrollUpdateEvent) => void,
    ): void;
    on(type: string, handler: (event: any) => void): void;
    off(
        type: "scroll:update",
        handler?: (event: IroScrollUpdateEvent) => void,
    ): void;
    off(type: string, handler?: (event: any) => void): void;
    emit(type: "scroll:update", event: IroScrollUpdateEvent): void;
    emit(type: string, event?: any): void;
}

interface IroUtils {
    missImg?: (ele: Element | null) => void;
    missAvatar?: (ele: Element | null) => void;
    [key: string]: unknown;
}

type IroMessageType = "success" | "warning" | "info" | "error";

/** 主题前端全局命名空间，运行时由 app/index.ts 组装 */
interface IroNamespace {
    hooks: IroHooks;
    // 暂用 any，收紧时替换为 IroThemeConfig
    config: any;
    page: IroPageConfig;
    user: IroUserConfig;
    bus: IroBus;
    utils: IroUtils;
    navigate: (url: string, options?: Record<string, unknown>) => Promise<unknown>;
    message: (message: string, type?: IroMessageType) => void;
}

declare const _iro: IroNamespace;

interface Window {
    _iro: IroNamespace;
}
