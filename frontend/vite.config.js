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
const VENDOR_GROUPS = [
    // @vue / @vueuse 与 vue 同属一套运行时，拆开会在 chunk 之间产生互相引用
    ["vendor-vue", ["vue", "@vue", "@vueuse"]],
    ["vendor-http", ["axios", "axios-cache-interceptor"]],
    ["vendor-particles", ["@tsparticles"]],
    ["vendor-highlight", ["highlight\\.js"]],
    ["vendor-markdown", ["markdown-it", "markdown-it-texmath", "katex"]],
    ["vendor-element", ["element-plus"]],
    // element-plus 依赖 lodash-es，主题侧（throttle）也要用：
    // 不单独拆出来会被 element-plus 一起卷进入口静态链，让 element-plus 失去懒加载
    ["vendor-lodash", ["lodash-es"]],
    ["vendor-swup", ["swup"]],
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
                            ...VENDOR_GROUPS.map(([name, names], index) => ({
                                name,
                                test: pkgTest(names),
                                priority: 100 - index,
                            })),
                            // 主题侧共享运行时：动态 chunk（pjax / 滚动 / 阅读量 / 表情包 / 粒子插件）都要用 _iro 与 api，
                            // 一旦留在入口 chunk 就会反向引用 app.js。message.js 保持独立以让 element-plus 维持懒加载。
                            {
                                name: "iro-core",
                                test: (id) =>
                                    id.includes("/frontend/app/") &&
                                    !id.includes("/app/utils/message.js") &&
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
