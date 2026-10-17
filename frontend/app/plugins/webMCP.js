const manifest = readManifest();

const implementations = {
    search_site: searchSite,
    read_page: readPage,
    get_llms_txt: getLlmsTxt,
};

if (manifest && document.modelContext && typeof document.modelContext.registerTool === "function") {
    registerTools(manifest).catch((error) =>
        console.warn("WebMCP tool registration failed:", error),
    );
}

function readManifest() {
    const node = document.getElementById("iro_webmcp_tools");

    if (!node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent);
    } catch {
        return null;
    }
}

async function registerTools(payload) {
    const registrations = (payload.tools ?? [])
        .filter((tool) => typeof implementations[tool.name] === "function")
        .map((tool) =>
            document.modelContext.registerTool({
                name: tool.name,
                description: tool.description,
                inputSchema: tool.inputSchema,
                execute: (input, options) =>
                    implementations[tool.name](input ?? {}, payload, options?.signal),
            }),
        );

    const results = await Promise.allSettled(registrations);

    results.forEach((result) => {
        if (result.status === "rejected") {
            console.warn("WebMCP tool registration failed:", result.reason);
        }
    });
}

async function searchSite({ keyword, limit = 10 }, payload, signal) {
    const query = String(keyword ?? "").trim();

    if (query === "") {
        throw new Error("A non-empty keyword is required.");
    }

    const url = new URL(payload.home);
    url.searchParams.set("s", query);
    url.searchParams.set("md", "");

    const html = await fetchText(url, signal);
    const doc = new DOMParser().parseFromString(html, "text/html");
    const items = [...doc.querySelectorAll(".post-card")]
        .slice(0, Math.max(1, Number(limit) || 10))
        .map((card) => {
            const link = card.querySelector(".post-title a") ?? card.querySelector("a[href]");

            if (!link) {
                return "";
            }

            const title = (card.querySelector(".post-title")?.textContent ?? link.textContent).trim();
            const excerpt = (card.querySelector(".post-excerpt")?.textContent ?? "").trim();

            return `- [${title}](${link.getAttribute("href")})${excerpt ? ": " + excerpt : ""}`;
        })
        .filter(Boolean);

    return textResult(items.length > 0 ? items.join("\n") : `No posts found for "${query}".`);
}

async function readPage({ url }, payload, signal) {
    const target = new URL(url, payload.home);

    if (target.origin !== location.origin) {
        throw new Error("Only pages on this site can be read.");
    }

    target.searchParams.set("md", "");

    const response = await fetch(target, {
        signal,
        headers: { Accept: "text/markdown" },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status} while fetching ${target}`);
    }

    // 没有 md 形态的页面类型会被 302 回原地址，此时拿到的是整页 HTML
    if (response.redirected) {
        throw new Error(`${url} has no markdown version; use a link from llms.txt instead.`);
    }

    return textResult(await response.text());
}

async function getLlmsTxt(_input, payload, signal) {
    return textResult(await fetchText(payload.llms, signal));
}

async function fetchText(url, signal) {
    const response = await fetch(url, {
        signal,
        headers: { Accept: "text/markdown" },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status} while fetching ${url}`);
    }

    return response.text();
}

function textResult(text) {
    return {
        content: [{ type: "text", text }],
    };
}
