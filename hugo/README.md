# Sakurairo · Hugo 版

同一套前端的第二层渲染：**样式与脚本不重写**，直接吃 WordPress 侧 `frontend/` 的构建产物；Hugo 只负责把内容组织成 HTML。

## 与 WordPress 版的关系

| 层 | WordPress | Hugo |
| --- | --- | --- |
| 后端 | PHP 模板 + 主题函数 | `layouts/` 模板 |
| 配置 | 后台 CSF 设置（单个 option 数组） | `config/_default/params.toml`（键名与 CSF 字段 id 一致） |
| 样式脚本 | `frontend/` 构建到 `frontend/dist/` | 同一份产物，通过 mount 读 |
| 内容 | 数据库 | `content/` 下的 Markdown |

配置镜像的规则：`opt/theme-options.php` 里每个字段的 `id` 就是 `params.toml` 里的键名，默认值取 CSF 的 `default`。改设置时两边各改一处，键名不要翻译。

## 目录

```
hugo/
├── config/_default/
│   ├── hugo.toml          # 站点、permalinks、markup、module.mounts
│   ├── params.toml        # 全部主题设置（镜像 CSF 字段）
│   └── menus.toml         # 导航菜单
├── layouts/
│   ├── _default/{baseof,home,single,list}.html   # 整页外壳与路由分发
│   ├── _partials/
│   │   ├── head.html  asset-css.html  theme_config.html  theme_style_vars.html
│   │   ├── route.html  route-name.html  layout-style.html  footer.html
│   │   └── components/     # ★ 与 frontend/components 一比一
│   │       ├── page/       # home post archive author default
│   │       ├── post/       # list header head_metas footer navigator card/*
│   │       ├── navbar/     # sakura island mobile
│   │       ├── homepage/   # cover show wave
│   │       ├── site/       # progress_bar particle search_form footer/* widget/*
│   │       ├── slots/      # content_container pagination
│   │       ├── media/      # image
│   │       └── comment/    # placeholder
│   ├── _markup/            # render-image / render-link / render-passthrough
│   ├── _shortcodes/math.html
│   ├── index.searchindex   # 搜索索引，输出成 /wp-json/sakura/v1/search_index
│   └── 404.html
├── content/                # 示例内容，含 Markdown 语法测试页
├── static/js/hugo-search.js   # 模态框实时搜索（含 404 ?s= 自动呼出）
├── static/css/hugo-search.css # 弹层的窄屏补丁
└── package.sh              # 发布打包：自包含站点 zip → temp/
```

组件路径与 `frontend/components/` 完全对齐，改一个组件时两边能找到同名文件。`frontend/` 里同目录的 `.php`/`.scss` 不会被 Hugo 读，Hugo 只认 `.html`。

## 构建

前端产物复用 WordPress 的构建：先在主题根构建一次，再构建 Hugo。

```bash
# 主题根：生成 frontend/dist（只有这一步需要 Node）
pnpm build:frontend

# 构建站点，产物在 hugo/public
hugo -s hugo

# 本地预览（改模板/内容即时重建）
hugo server -s hugo
```

`hugo.toml` 里两条 mount 是关键：

- `../frontend → assets/frontend`：模板按 `frontend/dist/<name>` 取资源（CSS 用 `resources.Get` 做存在性探测后输出 URL）。
- `../frontend/dist → static/frontend/dist`：整目录原样发布，`style.css` 里 `url()` 引用的字体/图片才不会缺。

> 显式声明 `[[module.mounts]]` 会顶掉 Hugo 的默认挂载，因此 layouts / content / static / assets / data / i18n 必须逐个列回，漏一个就是 0 页面。

未构建前端时站点仍能出页面，只是没有样式（`asset-css.html` 找不到产物就静默跳过）。

`hugo server` 用 `--renderToMemory` 运行时，静态文件与 `frontend/dist` 的文件名清单都是在启动时定的：
**重新构建了前端就要重启 server**，否则页面还指向旧的内容哈希 chunk（文件已被 vite 清掉，运行时直接 404）。

同样是这个 server 会占着 `.hugo_build.lock`：它开着的时候再跑一次 `hugo` 构建会一直卡在拿锁上，
加 `--noBuildLock` 才能并行（`hugo server --noBuildLock` 也适用于反过来要先起构建的场景）。

## 打包

```bash
hugo/package.sh                 # 构建前端 + 打包 + 用发布包自检
hugo/package.sh --no-build      # 复用已有 frontend/dist
hugo/package.sh --no-verify     # 本地没有 hugo 时跳过自检
```

产出 `hugo/temp/Sakurairo-hugo-<版本>.zip`（版本号取主题根 `style.css` 的 `Version`），解压即可用：

```bash
unzip Sakurairo-hugo-3.1.0.zip && cd Sakurairo-hugo && hugo server
```

包是**自包含的站点**而不是 `themes/` 下的主题：本工程依赖仓库里的 `frontend/dist`，所以打包时会把产物收进
`assets/frontend/dist/`，并把 `hugo.toml` 的两条 mount 从 `../frontend`、`../frontend/dist` 改写成包内路径
（同一份产物再挂一次到 `static/frontend/dist`，不再拷贝第二份）。

前端不另起一套工程，仍是 WordPress 的那份 Vite 配置：脚本自己执行一次 `pnpm --filter frontend run build`，
把产物归位到 `frontend/dist` 再收进包（`--no-build` 可跳过）。

自检需要本地有 `hugo`：脚本会用发布包自身构建一次，确认不依赖仓库也能出页面、前端运行时和搜索索引。
**没有 hugo 也能打包**——自动跳过并在结尾标注「验证: 跳过」，也可以用 `--no-verify` 显式跳过。

包根目录还带着主题信息：`style.css`（主题头，含名称/作者/版本/许可）与 `LICENSE`。
它们是站点根下的元数据文件，不参与 Hugo 渲染、也不会出现在构建产物里，只是让分发包自己说得清是哪一版主题。

`config/site/`（个人站点快照）和 `content/posts/markdown-test.md`（语法样板）不会进包。



## 配置

`config/_default/params.toml` 是唯一一份「主题设置」，键名与 `iro_opt()` 一致，默认值从 `opt/theme-options.php` 抽出来。
里面**只留 Hugo 版模板真正读取的键**（脚本扫 `layouts/` 里的 `$p.<key>` 比对得出），WordPress 侧独有的能力
（后台面板、AI、评论、播放器、SMTP、安全头、FSE 布局、llms、统计、i18n、登录页、图片服务等）连键一起删了，
免得配置里躺着永远不生效的项。

每个键上方有一行中文说明「该填什么」：枚举型列全可选项和它们原本对应的功能，`fieldset` / `repeater`
这类结构型再补一行 `例如 …` 的结构示例，照着改即可。

布尔开关是**真布尔**（`true` / `false`），模板里直接 `{{ if $p.<key> }}` 判断。**不要写 `default true $p.<key>`**：
Hugo 的 `default` 把 `false` 也当作空值，会把「关掉」重新变回开启——这类开关一旦这样写就再也关不掉了。

`config/site/` 是**站点快照**，只在 `-e site` 时覆盖默认值，用来看自己的站挂在 Hugo 上的效果：

```bash
hugo server -s hugo -e site --port 1315    # 看快照
hugo server -s hugo                        # 看默认值
```

`config/site/params.toml` 由备份的整份设置生成，只写与默认不同的项；其中 `/wp-content/` 开头的图片路径
已补成绝对地址（图片仍在原 WordPress 站点上）。改完默认值别忘同步这份快照。

> `hugo server` 不会热重载 `config/site/` 下的改动（`config/_default/` 会），改了快照要重启一次服务。

## 内容

- 文章放 `content/posts/`，永久链接是 `/:year/:month/:contentbasename/`（不用 `:slug`，中文标题做不了 slug）。
- 首页模块顺序、卡片形态、页头页脚样式都由 `params.toml` 决定，与 WordPress 后台是同一批开关。
- 数学公式：`$...$` 与 `$$...$$` 在构建期用 KaTeX 渲染成 MathML（不额外加载前端库）。
  - 已知限制：`$$` 块里如果出现「整行只有一个 `=` 或 `-`」，Goldmark 会当成 setext 标题下划线并提前结束公式块。这类公式（矩阵、多行推导）改用 shortcode：
    ```text
    {{< math >}}
    \begin{pmatrix} a & b \\ c & d \end{pmatrix} =
    \begin{pmatrix} x \\ y \end{pmatrix}
    {{< /math >}}
    ```
- 搜索只有模态框实时检索：索引由 `layouts/index.searchindex` 生成到 `/wp-json/sakura/v1/search_index`（路径与字段照 `inc/api/search_index.php`，`iro_api` 也指向这里），首次呼出时取一次，浏览器端打分。没有搜索结果页；404 页的搜索框把关键词带回 `/?s=` 后自动呼出模态框。
- 评论：只留占位节点（`components/comment/placeholder.html`），接入第三方评论时在占位处挂载。

## 单租户

没有作者体系：不含 `authors` taxonomy，也没有作者归档页；站点的作者署名只来自 `[params.hugo.author]` 一处。

## 首屏性能阶段

`_partials/theme_style_vars.html` 输出的外链字体（`#iro_extra_fonts`）与移动端背景图（`#iro_deferred_bg`）
默认 `media="not all"`，**不参与首屏下载**；由 `_partials/theme_performance.html` 在 FCP 后解锁背景图、
LCP 稳定（600ms 无新候选）后解锁字体，省流模式（`saveData` / `prefers-reduced-data`）永不加载字体。
阶段结果写在 `window.iroPerformance` 上并广播 `performance:fcp` / `performance:lcp`，`_iro.hooks` 靠它补发事件。

这与 WordPress 版 `frontend/theme_performance.php` 是同一段逻辑，因此**不能挪到页尾**：脚本要早于首屏绘制执行，
且必须排在那些 `<style>` 节点之后。

页面头部带 `<meta name="generator" content="Hugo …">`。前端运行时据此判断「这是静态站」，
跳过只有 WordPress 才有的接口——目前是阅读量上报（`frontend/app/plugins/postViews.js` 直接整段返回，
不会去打 `/wp-json/sakura/v1/post/views`）。

## 图片 404 兜底

正文图片（`_markup/render-image.html`）与模板输出的图片（`components/media/image.html`）都带
`onerror="iroImgError(this)"`。渲染阶段不预置占位图、不做骨架，只有真的加载失败才动 `src`：
`_partials/head.html` 里内联的 `iroImgError` 转给 `_iro.utils.missImg`（先带 `_retry` 重试两次，
仍失败再换 `missing_images_placeholder`）；运行时若还没就绪，就等 `DOMContentLoaded` 再补一次。
与 WordPress 版 `header.php` + `inc/functions/content/index.php` 的 `iro_content_image_fallback` 一一对应。

## 没有对应物、已去掉的部分

FSE 区块模板与古腾堡块、区块装饰体系（sakura-icon / hitokoto / focus-title 用最小等价实现替代）、后台设置面板、AI 面板、评论系统与评论分页、播放器、llms、安全响应头、语言切换、用户菜单、内置随机图、文章浏览量。

经典的主题样式切换基本保留：`nav_style_select`（sakura / island）、`footer_style_select`（sakura / island；`custom` 是 FSE 页脚，退回 sakura）、`footer_island_style`、`cover_focus_style`（off / avatar / text / mashiro_text）、`page_style`（sakura / wordpress）。

`cover_style_select` 只剩 sakura 与 custom 两个值、custom 是 FSE 封面，所以整个键没有留下——Hugo 版固定用内置封面。

## 验证清单

```bash
hugo -s hugo --logLevel warn          # 有模板报错会直接失败
hugo -s hugo -d /tmp/out --quiet && ls /tmp/out
```

改完模板至少看一眼：首页（封面/卡片/分页）、文章页（TOC、代码高亮、灯箱、公式）、分类页、404、`/?s=关键词` 的模态框检索。
