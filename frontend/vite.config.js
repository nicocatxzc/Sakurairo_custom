import { defineConfig } from "vite";
import { resolve } from "path";
import basicSsl from "@vitejs/plugin-basic-ssl";
import vue from "@vitejs/plugin-vue";
import AutoImport from "unplugin-auto-import/vite";
import Components from "unplugin-vue-components/vite";
import { ElementPlusResolver } from "unplugin-vue-components/resolvers";

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
                },
                output: {
                    entryFileNames: "app.js",
                    chunkFileNames: "[name].[hash].js",
                    assetFileNames: (assetInfo) => {
                        const name = assetInfo.names?.[0] ?? "";

                        if (name === "style.css") {
                            return "style.css";
                        }

                        return "assets/[name]-[hash][extname]";
                    },
                },
            },
        },
    };
});
