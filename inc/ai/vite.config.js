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
        rollupOptions: {
            input: {
                main: resolve(import.meta.dirname, "src/main.js"),
            },
            output: {
                entryFileNames: "[name].js",
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
});
