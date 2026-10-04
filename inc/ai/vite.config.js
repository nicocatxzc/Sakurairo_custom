import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vite";
import { resolve } from "path";
import basicSsl from "@vitejs/plugin-basic-ssl";

// https://vite.dev/config/
export default defineConfig({
    plugins: [vue(), basicSsl()],
    server: {
        https: true,
        port: 5174,
        host: "0.0.0.0",
        strictPort: true,
        cors: true,
        // 同 frontend/vite.config.js：忽略 Vue 编译器在源码目录旁留下的 .tmpdir 临时文件，
        // 否则 watcher 对已被删除的临时文件 fs.watch 会抛 EBUSY 并终止 dev server。
        watch: {
            ignored: ["**/*.tmpdir", "**/*.tmpdir/**"],
        },
        hmr: {
            protocol: "wss",
            host: "wordpress",
            port: 5174,
            clientPort: 5174,
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
                main: resolve(import.meta.dirname, "src/main.js"),
            },
            output: {
                entryFileNames: "[name].js",
                chunkFileNames: "[name].[hash].js",
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.names?.[0] ?? "";

                    if (name === "style.css" || name === "main.css") {
                        return "style.css";
                    }

                    return "assets/[name]-[hash][extname]";
                },
            },
        },
    },
});
