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

/** 由 frontend/theme_performance.php 在 head 里提前建立的首屏阶段标记 */
interface IroPerformance {
    fcp: boolean;
    lcp: boolean;
}

interface IroHooks {
    DOMContentLoaded: IroDomReadyHook;
    /** 首个内容绘制；已过则立即执行 */
    fcp: IroDomReadyHook;
    /** LCP 候选稳定（最后一个候选后 600ms）；已过则立即执行 */
    lcp: IroDomReadyHook;
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

/** 页脚播放器前端配置，对应 theme_config.php 的 player 字段 */
interface IroPlayerConfig {
    enabled?: boolean;
    playlist?: string;
    order?: "list" | "random";
    preload?: "none" | "metadata" | "auto";
    volume?: number;
    theme?: string;
}

/** 对应 theme_config.php 中 #iro_theme_config 的输出，供收紧 config 类型时使用 */
interface IroThemeConfig {
    language?: string;
    api?: string;
    ajaxurl?: string;
    iro_api?: string;
    nonce?: string;
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
    hitokoto_apis?:Array[string];
    extract_theme_skin_from_cover?: boolean;
    extract_article_highlight_from_feature?: boolean;
    post_cover_as_background?: boolean;
    cover_random_pic_url_pc?: string;
    cover_random_pic_url_mb?: string;
    player?: IroPlayerConfig | null;
    [key: string]: unknown;
}

/** 语言切换器的一项，对应多语言模块的 iro_i18n_language_links() */
interface IroLangLink {
    code?: string;
    name?: string;
    url?: string;
    current?: boolean;
    exists?: boolean;
    prefix?: string;
    edit?: string;
}

/** 对应 #iro_page_config */
interface IroPageConfig {
    post_id?: number;
    post_image?: string;
    is_home?: boolean;
    is_singular?: boolean;
    language?: string;
    langs?: IroLangLink[];
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

/** 对应 app/stores/resize.js 广播的当前视口尺寸 */
interface IroResizeUpdateEvent {
    width: number;
    height: number;
}

/** mitt 事件总线，实例见 app/bus.js */
interface IroBus {
    on(
        type: "scroll:update",
        handler: (event: IroScrollUpdateEvent) => void,
    ): void;
    on(
        type: "resize:update",
        handler: (event: IroResizeUpdateEvent) => void,
    ): void;
    on(type: string, handler: (event: any) => void): void;
    off(
        type: "scroll:update",
        handler?: (event: IroScrollUpdateEvent) => void,
    ): void;
    off(
        type: "resize:update",
        handler?: (event: IroResizeUpdateEvent) => void,
    ): void;
    off(type: string, handler?: (event: any) => void): void;
    emit(type: "scroll:update", event: IroScrollUpdateEvent): void;
    emit(type: "resize:update", event: IroResizeUpdateEvent): void;
    emit(type: string, event?: any): void;
}

interface IroUtils {
    missImg?: (ele: Element | null) => void;
    missAvatar?: (ele: Element | null) => void;
    [key: string]: unknown;
}

/** 对应 app/optimize.js 暴露的弱网/省流信号 */
interface IroOptimize {
    /** 用户开启了省流（saveData / prefers-reduced-data） */
    saveData: boolean;
}

type IroMessageType = "success" | "warning" | "info" | "error";

/** 对应 frontend/i18n.js 导出的翻译对象，可调用或使用 .t() */
interface IroI18n {
    (text: string, params?: Record<string, string | number>): string;
    t(text: string, params?: Record<string, string | number>): string;
    readonly locale: string;
}

/** 主题前端全局命名空间，运行时由 app/index.ts 组装 */
interface IroNamespace {
    hooks: IroHooks;
    isBackend: boolean;
    config: IroThemeConfig;
    page: IroPageConfig;
    user: IroUserConfig;
    bus: IroBus;
    utils: IroUtils;
    optimize: IroOptimize;
    i18n: IroI18n;
    navigate: (
        url: string,
        options?: Record<string, unknown>,
    ) => Promise<unknown>;
    message: (message: string, type?: IroMessageType) => void;
}

declare const _iro: IroNamespace;

interface Window {
    _iro: IroNamespace;
    iroPerformance?: IroPerformance;
}
