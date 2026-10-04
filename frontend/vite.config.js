import { defineConfig } from "vite";
import { resolve } from "path";
import basicSsl from "@vitejs/plugin-basic-ssl";
import vue from "@vitejs/plugin-vue";
import AutoImport from "unplugin-auto-import/vite";
import Components from "unplugin-vue-components/vite";
import { ElementPlusResolver } from "unplugin-vue-components/resolvers";

// 入口文件在 PHP 里以 app.js?ver=INT_VERSION 引用，chunk 之间却是相对路径互相 import。
// 只要「入口 chunk 被别的 chunk 反向引用」，浏览器就会把 app.js?ver=... 与 ./app.js 当成两个模块图，整包下载并执行两次。
// 因此被多处共享的代码必须落在独立 chunk。
//
// 首屏用不到的大件各自独立成 chunk，只在对应场景按需加载；
// 其余启动链上的东西（主题运行时 + axios/swup/lodash-es/vue 等）全部合进 iro-core 一个 chunk。
const LAZY_VENDOR_GROUPS = [
    // 注意要带上无作用域的元包 tsparticles：它内部 import 了全部 @tsparticles/*，
    // 漏掉它就会被并进启动 chunk，把整个粒子集重新绑回首屏。
    ["vendor-particles", ["@tsparticles", "tsparticles"]],
    ["vendor-highlight", ["highlight\\.js"]],
    ["vendor-markdown", ["markdown-it", "markdown-it-texmath", "katex"]],
    // element-plus 只被按需加载的 Vue 组件与 _iro.message 动态引用，单独成 chunk 才不会回流进启动链
    ["vendor-element", ["element-plus"]],
    // aplayer 只在页脚播放器里按需加载
    ["vendor-aplayer", ["aplayer"]],
];

// pnpm 的真实路径是 node_modules/.pnpm/<pkg>@<ver>/node_modules/<pkg>/...
const pkgTest = (names) =>
    new RegExp(
        `node_modules[\\\\/](?:\\.pnpm[\\\\/][^\\\\/]+[\\\\/]node_modules[\\\\/])?(?:${names.join("|")})[\\\\/]`,
    );

// 按需大件的匹配器：启动 group 靠它把自己排除在这些包之外
const LAZY_VENDOR_TESTS = LAZY_VENDOR_GROUPS.map(([, names]) => pkgTest(names));
const isLazyVendor = (id) => LAZY_VENDOR_TESTS.some((test) => test.test(id));

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
            // Vue 编译器会在源码目录旁写下 .<name>.<pid>.<uuid>.tmpdir/<name>.tmp 临时文件并立刻删除。
            // watcher 在它被删掉之后才去 fs.watch，Node 抛 EBUSY 且当作 FSWatcher 的 error 事件，
            // 未捕获即终止整个 dev server（Windows 上必现）。忽略这些临时产物即可，node_modules/.git 等默认忽略项不受影响。
            watch: {
                ignored: ["**/*.tmpdir", "**/*.tmpdir/**"],
            },
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
            rolldownOptions: {
                input: {
                    app: resolve(import.meta.dirname, "main.js"),

                    // 登录页样式
                    login: resolve(
                        import.meta.dirname,
                        "components/login.js",
                    ),
                    // 文章排版样式入口
                    "post-sakura": resolve(
                        import.meta.dirname,
                        "components/post/post-sakura.js",
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

                        if (name === "login.css") {
                            return "login.css";
                        }

                        if (name === "post-sakura.css") {
                            return "post-sakura.css";
                        }

                        return "assets/[name]-[hash][extname]";
                    },
                    codeSplitting: {
                        // Rolldown 会把不参与 group 匹配的共享模块（典型是 Vite 注入的 __vitePreload helper
                        // \0vite/preload-helper.js）塞进**优先级最高**的 group 的 chunk。优先级若被大件拿走，
                        // 入口就会静态 import 整个 400 KB 粒子包。
                        // 因此启动 group 拿最高优先级，同时显式排除下面那些按需大件。
                        groups: [
                            {
                                name: "iro-core",
                                test: (id) =>
                                    !/\.s?css$/.test(id) &&
                                    !isLazyVendor(id) &&
                                    (id.includes("vite/preload-helper") ||
                                        id.includes("export-helper") ||
                                        id.includes("/frontend/app/") ||
                                        /node_modules[\\/]/.test(id)),
                                priority: 200,
                            },
                            ...LAZY_VENDOR_GROUPS.map(([name, names], index) => ({
                                name,
                                test: pkgTest(names),
                                priority: 100 - index,
                            })),
                        ],
                    },
                },
            },
        },
    };
});
