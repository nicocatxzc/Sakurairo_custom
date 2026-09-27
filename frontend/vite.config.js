import { defineConfig } from "vite";
import { resolve } from "path";
import basicSsl from "@vitejs/plugin-basic-ssl";
import vue from "@vitejs/plugin-vue";
import AutoImport from "unplugin-auto-import/vite";
import Components from "unplugin-vue-components/vite";
import { ElementPlusResolver } from "unplugin-vue-components/resolvers";

// 入口文件在 PHP 里以 app.js?ver=INT_VERSION 引用，chunk 之间却是相对路径互相 import。
// 只要「入口 chunk 被别的 chunk 反向引用」，浏览器就会把 app.js?ver=... 与 ./app.js 当成两个模块图，整包下载并执行两次。
// 因此被多处共享的代码必须落在独立 chunk：这里只拆 package.json 里直接依赖的大件，
// 链式依赖统一并入 vendor-misc。
// 首屏一定会用到的运行时与网络层合并成 vendor-core：拆得太碎会产生大量几 KB 的往返请求，
// 而它们几乎同时被入口用到。其余大件都是非首屏 / 按需加载，各自独立成 chunk，
// 避免被并进 vendor-core 而重新绑上首屏静态链。
const VENDOR_GROUPS = [
    ["vendor-core", ["axios", "axios-cache-interceptor", "swup", "lodash-es"]],
    ["vendor-vue", ["vue", "@vue", "@vueuse"]],
    ["vendor-particles", ["@tsparticles"]],
    ["vendor-highlight", ["highlight\\.js"]],
    ["vendor-markdown", ["markdown-it", "markdown-it-texmath", "katex"]],
    ["vendor-element", ["element-plus"]],
    // aplayer 只在页脚播放器里按需加载，单独成 chunk，避免被 vendor-misc 卷入首屏静态链
    ["vendor-aplayer", ["aplayer"]],
];

// pnpm 的真实路径是 node_modules/.pnpm/<pkg>@<ver>/node_modules/<pkg>/...
const pkgTest = (names) =>
    new RegExp(
        `node_modules[\\\\/](?:\\.pnpm[\\\\/][^\\\\/]+[\\\\/]node_modules[\\\\/])?(?:${names.join("|")})[\\\\/]`,
    );

export default defineConfig(() => {
    return {
        // 开发服务器配置
        plugins: [
            basicSsl(),
            vue(),
            Components({
                resolvers: [ElementPlusResolver()],
                dts: "types/components.d.ts",
                root: import.meta.dirname,
                dirs: ["./components", "./app"],
                include: [/\.vue$/, /\.vue\?vue/],
            }),
            AutoImport({
                resolvers: [ElementPlusResolver()],
                dts: "types/auto-imports.d.ts",
                imports: [
                    "vue",
                    {
                        axios: [["default", "axios"]],
                        "lodash-es": [["default", "_"]],
                    },
                ],
                dirs: ["./app/utils"],
            }),
            {
                name: "iro-entry-import-guard",
                generateBundle(_, bundle) {
                    const entries = new Set(
                        Object.values(bundle)
                            .filter(
                                (item) => item.type === "chunk" && item.isEntry,
                            )
                            .map((item) => item.fileName),
                    );

                    for (const chunk of Object.values(bundle)) {
                        if (
                            chunk.type !== "chunk" ||
                            entries.has(chunk.fileName)
                        ) {
                            continue;
                        }

                        for (const imported of chunk.imports) {
                            if (imported === "app.js") {
                                this.error(
                                    `${chunk.fileName} 反向引用了入口 chunk app.js：` +
                                        `PHP 侧以 app.js?ver=INT_VERSION 加载，两个 URL 会被当成两份模块图重复下载。` +
                                        `请把该 chunk 依赖的共享模块加入 codeSplitting.groups。`,
                                );
                            }
                        }
                    }
                },
            },
        ],
        server: {
            https: true,
            port: 5173,
            host: "0.0.0.0",
            strictPort: true,
            cors: true,
            hmr: {
                protocol: "wss",
                host: "wordpress",
                port: 5173,
                clientPort: 5173,
            },
            headers: {
                "Access-Control-Allow-Origin": "*",
            },
        },

        // 构建配置
        base: "./",
        build: {
            sourcemap: true,
            outDir: "dist",
            emptyOutDir: true,
            rollupOptions: {
                input: {
                    app: resolve(import.meta.dirname, "main.js"),
                    captcha: resolve(
                        import.meta.dirname,
                        "components/site/captcha/captcha.js",
                    ),
                },
                output: {
                    entryFileNames: "[name].js",
                    chunkFileNames: "[name].[hash].js",
                    assetFileNames: (assetInfo) => {
                        const name = assetInfo.names?.[0] ?? "";

                        if (name === "app.css") {
                            return "style.css";
                        }

                        if (name === "captcha.css") {
                            return "captcha.css";
                        }

                        return "assets/[name]-[hash][extname]";
                    },
                    codeSplitting: {
                        groups: [
                            ...VENDOR_GROUPS.map(([name, names], index) => {
                                const pkg = pkgTest(names);
                                return {
                                    name,
                                    // Vite 注入的动态 import 预加载 helper（__vitePreload）没有真实模块 id 规律，
                                    // 不显式接管时会被 Rolldown 随便塞进某个 vendor chunk（实测是 vendor-particles），
                                    // 让入口静态 import 整个 tsparticles。这里把它并进首屏的 vendor-core。
                                    test:
                                        name === "vendor-core"
                                            ? (id) =>
                                                  pkg.test(id) ||
                                                  id.includes(
                                                      "vite/preload-helper",
                                                  )
                                            : pkg,
                                    priority: 100 - index,
                                };
                            }),
                            // 主题侧共享运行时：动态 chunk（pjax / 滚动 / 阅读量 / 表情包 / 粒子插件）都要用 _iro 与 api，
                            // 一旦留在入口 chunk 就会反向引用 app.js。message.js 内部再动态 import element-plus，仍保持懒加载。
                            {
                                name: "iro-core",
                                test: (id) =>
                                    id.includes("/frontend/app/") &&
                                    !/\.s?css$/.test(id),
                                priority: 60,
                            },
                            // 其余第三方依赖
                            {
                                name: "vendor-misc",
                                test: /node_modules[\\/]/,
                                priority: 10,
                            },
                        ],
                    },
                },
            },
        },
    };
});
