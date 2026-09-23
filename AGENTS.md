# AGENTS.md

面向 AI 编码代理的项目说明。动手改代码前请先读完本文件，尤其是「运行时架构」与「常见陷阱」两节。

---

## 1. 项目概览

**Sakurairo** 是一个 WordPress 主题（非插件），基于 [Sakura V3 Series](https://github.com/mashirozx/sakura) 重构，GPL-2.0 开源。

- 主题版本定义在 `style.css` 头部（当前 `3.1.0`），`functions.php` 里读取为常量 `IRO_VERSION`。
- 环境要求（`style.css` 中声明）：**PHP >= 8.0**、**WordPress >= 6.0**、Tested up to 6.9。
- `style.css` **只包含主题头注释，不含任何样式**。所有真实样式都在 `frontend/**/*.scss`，经 Vite 编译成 `frontend/dist/style.css`。
- 项目内所有注释、文档、后台文案以中文为主（README 另有 `README_en.md` / `README_ja.md` / `README_tw.md`）。
- 分支：`master` 为主分支，`preview` 由 CI 自动提交 JS 产物。

---

## 2. 环境与工具链（推荐配置，默认视为已存在）

下列工具链是**推荐配置，默认按「本机已就绪」处理**：直接调用即可，不必先 `command -v` 探测，也不必先解释「本机有没有」。

| 工具     | 推荐版本       | 用途                                                         |
| -------- | -------------- | ------------------------------------------------------------ |
| PHP CLI  | >= 8.0（8.4+） | `php -l` 语法检查、跑一次性调试脚本                          |
| Composer | 2.x            | 仅安装第三方 PHP 库时需要（主题本体不依赖 vendor 自动加载）  |
| Node.js  | >= 20          | `frontend/` 与 `inc/blocks/` 的构建                          |
| pnpm     | >= 9           | 两个前端工程共用的唯一包管理器（workspace 统一安装，**不要用 npm/yarn**） |

- PHP 改动先跑 `php -l <文件>` 挡住语法错（`<?php ?>` 配对、字符串闭合、模板标签），再做人工逐行比对；全量检查命令见 §5「PHP 语法检查」。
- 项目没有 PHP 单元测试框架、没有 PHPCS/ESLint/Stylelint 配置。**验证手段 = 前端构建 + `php -l` + 人工审阅**。
- 编辑器默认自带 Vue (Official)、Stylelint、Prettier、markdownlint、PHP Intelephense、ESLint、es6-string-html、Auto Rename Tag、Auto Close Tag 插件：**提交即视为格式检查通过**，不必再单独执行格式化/风格检查命令。
- 个别工具**确实缺失**时（如 `composer` 未装），跳过依赖它的步骤、改用等价手段即可，不要为了补装工具而中断任务。
- `frontend/` 与 `inc/blocks/` 由主题根的 **pnpm workspace 统一管理**：只在主题根 `pnpm install` 一次，依赖物理装在根 `node_modules/`，两个子目录里只剩指向它的软链（几十 KB）。子目录各自安装、各自锁文件的旧流程已废弃，见 §5。

### 2.1 调用 shell 的约定（重要）

内置 shell 工具偶发**不回显输出**（命令已执行但结果为空），因此：

- 命令一律**尽量重定向输出到文件**，再读文件取结果：`pnpm build > /tmp/iro-build.log 2>&1`，然后读 `/tmp/iro-build.log`，根据系统环境自行适配临时目录路径。
- 需要在一次调用里拿到结果时，用 `<cmd> > /tmp/iro-<name>.log 2>&1; tail -n 50 /tmp/iro-<name>.log`，不要依赖裸命令的回显。
- 长耗时命令（`pnpm install` / `pnpm build`）放后台并落盘：`nohup pnpm build > /tmp/iro-build.log 2>&1 &`，随后再读日志判断成败。
- 日志文件统一放 `/tmp/`，命名形如 `/tmp/iro-<用途>.log`，避免污染仓库工作区。
- **以日志文件内容为准**：一次没拿到输出不代表命令失败或成功，别急着下结论或重复执行。

---

## 3. 目录结构

```tree
Sakurairo/
├── package.json             # ★ 根工程：pnpm workspace 的脚本入口（build / build:frontend / build:blocks）
├── pnpm-workspace.yaml      # ★ workspace 成员清单（frontend、inc/blocks）+ allowBuilds 白名单
├── pnpm-lock.yaml           # ★ 唯一锁文件，同时覆盖 frontend 与 inc/blocks
├── node_modules/            # 依赖物理位置（workspace 共享，.gitignore；子目录里只有软链）
├── functions.php            # 入口：定义常量、按顺序 require 所有模块
├── style.css                # 仅主题头（名称/版本/依赖声明）
├── header.php               # <head>，含 Customizer 预览合并逻辑、CSS 变量输出
├── index.php                # 主模板 + 「局部渲染」分发（X-Template-Part）
├── footer.php / comments.php / 404.php
├── frontend/                # ★ 前端源码（Vite 工程；workspace 成员，锁文件在主题根）
│   ├── main.js              # 入口：样式 + app + components
│   ├── style.scss / layout.scss
│   ├── vite.config.js       # 双入口 app/captcha、产物重命名、HTTPS dev server
│   ├── package.json / pnpm-lock.yaml / tsconfig.json
│   ├── app/                 # 客户端核心（非组件）
│   │   ├── index.ts         # window._iro 命名空间 + hook 系统 + 配置解析
│   │   ├── pjax.js          # Swup 实例，桥接 pjax:* 事件
│   │   ├── bus.js           # mitt 事件总线（window._iro.bus）
│   │   ├── darkmode.js      # 深色模式（cookie: darkmode）
│   │   ├── stores/scroll.js # 滚动进度广播 scroll:update
│   │   ├── utils/           # api.js(axios+缓存) / missImg / classicPagination / parseMarkdown ...
│   │   └── plugins/         # postViews.js（阅读量上报），在 app/index.ts 末尾 import
│   ├── components/          # ★ PHP 局部 + JS 行为 成对出现
│   │   ├── block/           # 古腾堡块的前台渲染（notice/showcard/bvideo/ghcard/conversation）
│   │   ├── comment/  homepage/  navbar/  page/  post/  site/  slots/
│   │   ├── component_register.php  # 注册 PHP「函数组件」（content_container/pagination）
│   │   └── index.js         # 汇总 import 所有组件 JS（新增 JS 必须在此登记）
│   ├── theme_config.php     # 把 PHP 配置以 <script type="application/json"> 注入
│   ├── theme_style_vars.php # 输出 :root / :root.dark 的 CSS 变量
│   ├── types/               # ★ iro.d.ts（手写的 _iro 全局类型）+ unplugin 自动生成的 d.ts（均纳入版本管理）
│   └── dist/                # 构建产物（.gitignore，不提交）
├── inc/
│   ├── api.php              # rest_api_init，注册 sakura/v1 路由
│   ├── api/                 # bangumi / bilibili_favlist / captcha / turnstile / comments / search_index / post_view
│   ├── blocks/              # ★ 古腾堡编辑器工程（@wordpress/scripts；workspace 成员，锁文件在主题根）
│   │   ├── src/             # index.js + modules/*.js + style.scss
│   │   ├── build/           # 构建产物（.gitignore，不提交；改完 src 必须本地构建）
│   │   ├── render.php       # 前台 shortcode + register_block_type 渲染
│   │   └── iro_blocks.php   # 编辑器脚本/样式入队、注入 window.iroBlockEditor
│   ├── functions/
│   │   ├── tools.php / ip.php / seo.php / nav_bar.php / operator.php(iro_act) / cust_wp.php
│   │   ├── comment/  content/  custom/
│   │   └── enqueue_assets.php   # 前台 JS/CSS 入队（dev_mode 时指向 Vite）
│   ├── libs/                # 第三方类：Captcha / Meting / Parsedown / wp-api-menus
│   ├── theme_init/          # support / translation / shuoshuo / wp_fix / check / iro_opt
│   └── option-scheme.php    # 后台设置页的 CSS 皮肤（含 PHP 插值）
├── opt/                     # ★ 后台设置框架（Codestar CSF 的私有分支）
│   ├── option-framework.php # 载入 classes/setup.class.php + options/theme-options.php
│   ├── options/theme-options.php  # ★ 所有后台设置项的唯一定义处（Sakurairo_CSF::createSection）
│   ├── classes/  fields/  functions/  assets/   # 框架实现，一般不要改
│   ├── customizer/          # ★ Kirki 可视化编辑器（index.php / init.php + 内置 kirki/）
│   └── languages/           # 设置框架翻译（textdomain: sakurairo_csf）
├── languages/               # 主题翻译（textdomain: sakurairo）*.po/*.mo
├── update-checker/          # 内置 Plugin Update Checker v5（vendor 目录，勿改）
└── _helper/                 # 历史遗留脚本（见「常见陷阱」）
```

---

## 4. 运行时架构（重点）

### 4.1 设置项系统：`iro_opt()`

所有主题设置存放在**单个 WP option `iro_options`（数组）** 中。

```php
iro_opt('key', $default);          // 读（Customizer 预览时优先取 theme_mod）
iro_opt_update('key', $value);     // 写
$GLOBALS['iro_options'];           // 完整数组
```

- 定义位置：`inc/theme_init/iro_opt.php`；后台 UI 定义在 `opt/options/theme-options.php`（CSF）。
- **新增设置项时要在两处对齐**：CSF 后台字段（`opt/options/theme-options.php`）与前台读取处的默认值。
- Kirki Customizer（`opt/customizer/init.php`）通过字段里的 `iro_key` / `iro_subkey` 映射写回 `iro_options`：
  - 预览中：`header.php` 的 `use_customize_data()` 把临时 `theme_mod` 合并进 `theme_mod('iro_options')`，供 `iro_opt()` 读到。
  - 保存后：`opt/customizer/index.php` 的 `update_customize_to_iro_options()` 合并进 `iro_options`。
  - 因此自定义器字段的 `iro_key` 写错会「预览正常、保存后失效」。

### 4.2 配置注入 → `window._iro`

`frontend/theme_config.php` 在页面上输出三个 JSON `<script>`：

| id                  | JS 侧         | 内容                                                                                             |
| ------------------- | ------------- | ------------------------------------------------------------------------------------------------ |
| `#iro_theme_config` | `_iro.config` | 站点/接口/粒子/分页/lightbox 等全局配置                                                          |
| `#iro_page_config`  | `_iro.page`   | `post_id`、`is_home`、`is_singular`（列表页的 `post_id` 是循环首篇，判定单页要靠 `is_singular`） |
| `#iro_user_config`  | `_iro.user`   | 当前用户 id/name/avatar...                                                                       |

在 `frontend/app/index.ts` 的 `initFrontConfig()`（注册于 `onPageLoaded`）里解析。**PJAX 后会重新解析**，因为 `#iro_page_config` 是 Swup 的替换容器之一。

### 4.3 Hook 系统（`window._iro.hooks`）

`frontend/app/index.ts` 提供：

```js
_iro.hooks.DOMContentLoaded.push(fn)      // 初次 DOM 就绪
_iro.hooks.DOMContentLoaded.add(fn, {once:true})
_iro.hooks["pjax:start"|"pjax:success"|"pjax:complete"|"pjax:end"|"pjax:error"].push/add(fn)
_iro.hooks.onPageLoaded(fn)               // = DOMContentLoaded + pjax:complete（最常用）
```

**组件 JS 必须通过 hook 初始化**，不要直接写顶层 DOM 操作，否则 PJAX 跳转后失效。典型写法见 `frontend/components/site/progress_bar.js`、`frontend/components/page/template/bangumi.js`。

其他全局：`_iro.bus`（mitt 事件总线，如 `scroll:update`）、`_iro.navigate`（Swup 导航）、`_iro.utils`（`missImg` / `missAvatar` 等）。

`_iro` 及其成员的类型统一定义在 `frontend/types/iro.d.ts`（`declare const _iro` + `interface Window`），各文件直接写 `_iro` 即可获得提示。该文件是**手写的全局声明**，新增 `_iro` 成员时要同步补上；`_iro.config` 目前是 `any`，其形状（对应 `theme_config.php` 的三个 JSON）记录在同文件的 `IroThemeConfig` / `IroPageConfig` / `IroUserConfig` 中，收紧类型时替换即可。

### 4.4 PJAX / Swup

- `frontend/app/pjax.js` 用 Swup，替换容器：`.layout-slot` 与 `#iro_page_config`。
- 链接默认走 PJAX；需要跳过的加 `class="no-pjax"`（分页器已自动加，见 `frontend/components/slots/pagination.php`）。
- Swup 生命周期 → 原生 `CustomEvent`：`pjax:start / success / complete / end / error`。
- `scrollTo` 逻辑：首页与 `/page/*` 滚到 `#articles`，其他页面回到顶部。
- 另有「PJAX 保持加载资源」的 inline 脚本在 `header.php`（选项 `pjax_keep_loading`），通过 `data-pjax-keep-loading` 标记管理。

### 4.5 局部模板渲染（`X-Template-Part`）

`index.php` 顶部读取请求头 `X-Template-Part`，命中时只渲染片段（不输出 `<html>`）：

| 值                              | 渲染内容                             |
| ------------------------------- | ------------------------------------ |
| 任意非空（首页/归档/作者/搜索） | `frontend/components/post/list.php`  |
| `comment_list`                  | `comments_template()`                |
| `bangumi_list`                  | `page/template/bangumi.php`          |
| `bilibili_favlist`              | `page/template/bilibili_favlist.php` |
| `steam_list`                    | `page/template/steam.php`            |

前端通过 `frontend/app/utils/classicPagination.js` 携带该头发起请求（AJAX 翻页/加载更多），并收到 `global $iro_only_template`。

### 4.6 组件约定：PHP 局部 + JS 模块成对

新增一个前台组件时，通常需要：

1. **PHP**：`frontend/components/<area>/<name>.php`，用 `require_once` 挂进 `index.php`、`footer.php`、`comments.php` 或 `frontend/components/component_register.php`。
   - 模板写法遵循 §6 的「PHP 组件模板 —— 类 Vue 风格」（`foreach/endforeach`、`if/endif` 包裹 HTML + `<?= ?>` 内联输出）。
   - 可复用 UI 用「函数组件」形式（见 `slots/content_container.php` 的 `iro_content_container_start/end()`、`slots/pagination.php` 的 `iro_post_pagination()`）。
2. **JS**：`frontend/components/<area>/<name>.js`，并在聚合入口登记：`frontend/components/index.js`（组件）或 `frontend/app/index.ts`（核心）。**不登记 = 不会被打包**。
3. **样式**：同名 `.scss`，并在对应的 `index.scss` 里 `@use`（顶层汇总见 `frontend/style.scss`）。
4. 少量复杂交互用 Vue SFC（`captcha/builtin.vue`、`page/template/BangumiDetail.vue`、`site/Model.vue`）。Vue/Element Plus 已配 `unplugin-auto-import` + `unplugin-vue-components`，`components/` 与 `app/` 目录下的组件无需手动注册。

页面分发在 `index.php`：`home.php` / `post.php` / `search.php` / `author.php` / `archive.php`。

### 4.7 古腾堡区块与短代码

- 编辑器端：`inc/blocks/src/`（`index.js` 汇总 `modules/*`），用 `@wordpress/scripts` 构建。
- 前台端：`inc/blocks/render.php` 注册短代码（`[friend_link]`、`[bangumi]`、`[favlist]`、`[archive]` 等）和 `register_block_type('sakurairo/*')`。
- 编辑器样式在 `inc/blocks/iro_blocks.php` 中**注册两次**（外层文档 + `enqueue_block_assets` iframe），这是为兼容 WP 7.1 画布 iframe 的刻意设计，勿删。
- `inc/blocks/build/` **不纳入版本管理**（与 `frontend/dist` 一致）。改完 `src/` 必须在本地重新 build 才生效；发布/部署/打包前务必跑过 `pnpm build:blocks`，否则新 clone 出来的主题缺编辑器资源（`iro_blocks.php` 检测到产物缺失会跳过入队，不会报错，但编辑器里所有 Sakurairo 区块都不会出现）。

### 4.8 REST API

统一命名空间 `sakura/v1`（`inc/api.php` + `inc/api/*.php`），前端基址 `_iro.config.iro_api`：

- `/captcha`（GET 生成 / POST 校验）、`/captcha/turnstile`
- `/search_index`、`/comments/*`
- `/bangumi/bangumi|bilibili|mal`、`/favlist/all|detail`
- `/post/views`（GET 读取阅读量 / POST 上报计数，`inc/api/post_view.php`）

**nonce 约定**：`iro_get_basic_theme_config()`（`frontend/theme_config.php`）随基础配置输出 `nonce`（`wp_create_nonce('wp_rest')`），前端经 `X-WP-Nonce` 头或 `nonce` 参数回传；`iro_rest_check_nonce()`（定义在 `inc/api.php`，全局可用）作为 `permission_callback` 统一校验，新接口直接复用它即可。**action 必须保持 `wp_rest`**：带 cookie 的请求，内核会先用同名头按 `wp_rest` 校验一次，自定义 action 会在进入 `permission_callback` 之前就被 403（`rest_cookie_invalid_nonce`）。另注意 `wp_verify_nonce` 允许前后各一个 tick，整页缓存站点里页面上的 nonce 最长 12~24h 内仍有效，超时后上报只会静默收到 403。

新增接口要同时更新 `frontend/theme_config.php` 暴露的配置（如需）与前端调用处。

### 4.9 翻译

| textdomain              | 语言包目录                  | 用途              |
| ----------------------- | --------------------------- | ----------------- |
| `sakurairo`             | `languages/`                | 主题前台/部分后台 |
| `sakurairo_csf`         | `opt/languages/`            | CSF 设置页        |
| `Sakurairo_C`           | `opt/customizer/` 内联      | Kirki Customizer  |
| `plugin-update-checker` | `update-checker/languages/` | 更新检查器        |

新文案统一用 `sakurairo`。历史文件里残留 `'iro'` 域名（如 `inc/functions/nav_bar.php`），新代码不要模仿。

### 4.10 其他值得知道的机制

- `inc/functions/operator.php`：`?iro_act=xxx` 的后台跳转/动作分发（bangumi、mal、steam、playlist、del_exist_theme）。
- `inc/theme_init/check.php`：主题目录名必须是 `Sakurairo`，否则后台提示并尝试重命名。
- `inc/functions/content/query.php`：`pre_get_posts` 干预主查询（首页/搜索/归档允许 `shuoshuo` 等类型），并让置顶文章在非搜索页优先。
- `inc/theme_init/shuoshuo.php`：注册 `shuoshuo` 自定义文章类型。
- `inc/functions/content/post_views.php`：阅读量统计（meta key `views`）。计数由 `frontend/app/plugins/postViews.js` 驻留 3 秒后 POST `/post/views` 触发，`iro_set_post_views($post_id)` 是唯一入口（原 `add_action('get_header', 'iro_set_post_views')` 已移除，避免与服务端即时计数重复）。
- `update-checker/` 在 `functions.php` 顶部引入，并依 `iro_update_source` 选择更新源。

---

## 5. 构建与常用命令

### 依赖安装（主题根目录，唯一入口）

```bash
pnpm install          # 唯一一次安装：frontend 与 inc/blocks 的依赖一起装好
pnpm build            # = pnpm -r --if-present run build，依次构建 frontend、inc/blocks
pnpm build:frontend   # 只构建前台（= pnpm --filter frontend run build）
pnpm build:blocks     # 只构建区块（= pnpm --filter iro_blocks run build）
```

- 锁文件只有一个：根 `pnpm-lock.yaml`；pnpm 版本只在根 `package.json` 的 `packageManager` 里声明。
- **不要在 `frontend/` 或 `inc/blocks/` 里执行 `pnpm install`**：子目录一旦出现自己的 `pnpm-workspace.yaml` / `pnpm-lock.yaml`，pnpm 就会把该目录当成独立 workspace 根、静默忽略根锁文件（见 §7 第 15 条）。
- 依赖解析仍按成员隔离：`lodash`(CJS) 与 `lodash-es`、React 18（区块）与 Vue 3（前台）并存互不干扰。

### 前端（`frontend/`）

```bash
cd frontend           # 以下命令在 frontend/ 里执行（根目录没有 dev 脚本）
pnpm dev              # Vite dev server: https://0.0.0.0:5173（HMR host = "wordpress"）
pnpm build            # 产出 frontend/dist/{app.js,style.css,captcha.css,assets/*}
pnpm exec tsc --noEmit  # 类型检查（tsconfig 已覆盖 app/、components/、main.js）
```

- 这三条命令也可以从主题根用 `pnpm --filter frontend run dev` / `pnpm --filter frontend run build` / `pnpm --filter frontend exec tsc --noEmit` 执行；只是**别在子目录里 `pnpm install`**。

- 本地开发需在**站点选项里打开 `dev_mode`**，`inc/functions/enqueue_assets.php` 会改为加载 `https://wordpress:5173/@vite/client` 与 `/main.js`。
- dev server 使用自签 HTTPS，且 HMR `host: "wordpress"`；本机需能把 `wordpress` 解析到该容器/主机（官方 docker 环境已配置）。
- `frontend/dist/` 已被 `.gitignore`，**不要提交**（`inc/blocks/build/` 同样不提交）。
- Vite 产物命名由 `assetFileNames` 定制：`app.css` → `style.css`，`captcha.css` → `captcha.css`（验证码样式独立于主样式）。
- `pnpm build` 不做类型检查；`.vue` 的检查需 `vue-tsc`，但当前 `vue-tsc` 与工程内的 `typescript@7` 不兼容（`ERR_PACKAGE_PATH_NOT_EXPORTED: './lib/tsc'`），只能用 `tsc` 覆盖 `.ts`/`.js`。
- 构建/类型检查遵循 §2.1 的 shell 约定：`pnpm build > /tmp/iro-fe-build.log 2>&1`，然后读日志判断成败。
- `components/**/*.js` 是 `checkJs: false` 的 JS：纳入 tsconfig 只为拿到 `_iro` 等智能提示，不会因 JS 里的小毛病报错。

### 区块（`inc/blocks/`）

```bash
pnpm build:blocks     # 主题根执行；= pnpm --filter iro_blocks run build
# 等价写法（仍可用）：cd inc/blocks && pnpm build
# 实际命令：wp-scripts build src/index.js → inc/blocks/build/
```

- `frontend/types/*.d.ts`（unplugin 自动生成）**属于版本管理内容，需要提交**；`inc/blocks/build/**` 与 `frontend/dist/**` 一样**不提交**（`.gitignore` 已覆盖）。
- 用 pnpm 而非 npm/yarn；lockfile 是主题根的 `pnpm-lock.yaml`。

### 其他

- `_helper/do.py`（CSS 压缩）、`_helper/update_js.sh`、`.github/workflows/update.yml` 均指向**已不存在的** `css/src/`、`js/`、`attached/Sakurairo_Scripts` 目录，属于历史遗留。新流程请用 `frontend/` 的 Vite 构建，不要复用这些脚本。

### 提交前自检清单

1. 主题根 `pnpm build` 通过（或按改动范围跑 `pnpm build:frontend` / `pnpm build:blocks`）。
2. 若新增设置项：确认 CSF 字段、`iro_opt()` 读取、默认值三处一致。
3. 若新增组件 JS：确认已在 `frontend/components/index.js`（或 `app/index.ts`）登记，并且初始化挂在 `_iro.hooks.onPageLoaded` 上。
4. 若改动区块：本地已跑 `pnpm build:blocks`（产物不入库，但要落到工作区才会生效）。
5. 若新增/修改 `_iro` 的成员（`hooks` / `bus` / `navigate` / `message` / `utils` 等）：同步 `frontend/types/iro.d.ts`，并在主题根跑 `pnpm --filter frontend exec tsc --noEmit`。
6. PHP：`php -l <改动文件>` 全部通过，再人工复核模板标签、括号/引号闭合与 `<?php ?>` 配对。
7. 若增删/升级依赖：只改成员自己的 `package.json`，然后回主题根跑 `pnpm install` 更新根 `pnpm-lock.yaml`；确认 `frontend/`、`inc/blocks/` 里没有冒出 `pnpm-lock.yaml` 或 `pnpm-workspace.yaml`。

---

## 6. 编码规范

### PHP

- 每个可被直接访问的文件顶部加 `if (!defined('ABSPATH')) { exit; }`。
- 新函数统一加前缀 `iro_` 或 `sakurairo_`；可能与其他主题/插件冲突的函数用 `if (!function_exists(...))` 包裹。
- 输出必须转义：`esc_html()` / `esc_attr()` / `esc_url()` / `esc_js()` / `esc_html__()`。
- 输入必须清洗：`sanitize_key()` / `sanitize_text_field()` / `intval()`；POST 读取可用 `iro_get_post_key()`（`inc/functions/tools.php`）。
- 4 空格缩进（`opt/` 与部分历史文件用 tabs，**在既有文件内保持原风格**）。

### PHP 组件模板（`frontend/components/**/*.php`）—— 类 Vue 风格

组件模板是「PHP 版的 Vue 模板」，写法遵循：

- **条件/循环用替代语法包裹 HTML**：`if / elseif / else / endif`、`foreach / endforeach`、`while / endwhile`、`switch / endswitch`，冒号紧贴条件，`endforeach;` 等独占一行并与被包裹的 HTML 同缩进层级。
- **动态值一律用短标签内联输出**：`<?= ... ?>`（配合转义函数），不要为了拼字符串而先赋值再 `echo`。
- **表达式内联在标签属性/文本中**，例如：

```php
<?php foreach (iro_opt("widget_font_choice", []) as $font): ?>
    <button data-name="<?= $font["name"] ?>"><?= $font["name"] ?></button>
<?php endforeach; ?>

<?php if (has_post_thumbnail()) : ?>
    <img src="<?= esc_url(get_the_post_thumbnail_url(get_the_ID(), 'medium_large')) ?>">
<?php endif; ?>
```

- 参考实现：`post/card/with_image.php`、`post/list.php`、`site/widget.php`。
- 需要循环内做多分支时用 `switch/endswitch`（见 `post/card/with_image.php` 的 `post_card_metas` 输出）。
- 结构性重复（容器开合）才抽成函数组件（如 `iro_content_container_start/end()`），其余保持单文件模板。

### 注释

- **非必要不加注释**，默认不写。
- 仅在以下情况才写注释：
  1. 说明**重要作用的实现**（关键机制、非显而易见的取舍）；
  2. 该处**容易引发问题/踩坑**（如 PJAX 后失效、构建产物提交规则）；
  3. **无法从代码本身检索到的外部行为**（如 WordPress 核心行为、WP 7.1 编辑器 iframe 只消费 `enqueue_block_assets`、浏览器/框架的隐式约定）。
- 能直接从代码读懂的、逻辑直白的，**一律不注释**（不要写「循环输出文章列表」这类复述代码的注释）。
- 形式：**非多行说明优先用 `//`**（PHP 与 JS 均同），只有确实需要多行时才用块注释。

### 变量

- **只使用一次的表达式一律内联**，不要先赋给变量再使用（如仅为取 `get_the_category()[0]` 而先写 `$categories = get_the_category();` —— 历史模板里存在此类写法，新代码不要沿用）。
- 只有当同一结果被复用 ≥2 次、或为了显著提升可读性（长表达式拆解）时，才提取变量。
- 模板里优先把表达式直接写进 `<?= ?>`，而不是在模板顶部堆一堆 `$xxx = ...;`。

### JS / Vue / SCSS（`frontend/`）

- ESM + **4 空格缩进** + 双引号（与现有文件保持一致）。
- 类型：`tsconfig` 为 `strict: false`，但 `noUnusedLocals/Parameters: true` —— **未使用的变量会导致类型检查报错**，删除多余声明。
- 使用全局 `_iro`（直接写 `_iro`，不要重复声明 `window._iro`）。
- 事件通信优先用 `_iro.bus`（mitt）与 PJAX 生命周期 hook。
- 需要脱离主 bundle 独立运行的脚本（如 `site/captcha/captcha.js`）要自行兜底：先判 `window?._iro?.hooks`，否则从 `#iro_theme_config` 解析配置。
- 网络请求统一用 `frontend/app/utils/api.js`（axios + `axios-cache-interceptor`，GET 缓存 5 分钟），不要直接用裸 `fetch`。
- SCSS 用 `@use ... as ...`（Dart Sass 模块语法），不要用 `@import`。

### 通用

- 调用 shell 遵循 §2.1：命令输出一律重定向到 `/tmp/iro-*.log` 再读文件，不依赖内置 shell 的回显。
- 不引入新的第三方库，除非确实必要并同步更新 `pnpm-lock.yaml`（项目已内置 vue、element-plus、swup、animejs、tsparticles、markdown-it、katex、highlight.js、tocbot、medium-zoom、typed.js 等）。
- 不修改 `update-checker/vendor`、`update-checker/Puc`、`opt/classes`、`opt/fields`、`opt/customizer/kirki` —— 它们是 vendored 上游代码。

---

## 7. 常见陷阱（踩过就不要重复踩）

1. **JS 不登记就不会生效**：只创建 `.js` 文件而不在聚合入口 import，Vite 不会打包它。
2. **顶层 DOM 操作在 PJAX 后失效**：必须放进 `_iro.hooks.onPageLoaded()`。
3. **`frontend/dist` 与 `inc/blocks/build` 都不提交**：两者都由本地 `pnpm build` 产出，新 clone 必须先构建；`inc/blocks/build/` 缺失时 PHP 侧会静默跳过入队（见 `iro_blocks.php` 的 `file_exists` 判断），表现为「编辑器里没有 Sakurairo 区块」而非报错。
4. **Customizer 的 `iro_key` 写错**：预览看似正常，保存后设置丢失。
5. **`style.css` 不是样式文件**：往里写 CSS 不会被加载（只有主题头）。
6. **主题文件夹名必须为 `Sakurairo`**：否则触发 `inc/theme_init/check.php` 的重命名/告警逻辑。
7. **编辑器 iframe 样式**：`iro_blocks.php` 的双重注册是必要的兼容处理。
8. **分页/自定义 AJAX 链接要加 `no-pjax`**，否则会被 Swup 拦截，与局部渲染逻辑冲突。
9. **PHP 改动必须过 `php -l`**：模板里 `<?php ?>` 配对、字符串闭合、替代语法（`endforeach;` 等）肉眼极易漏看，命令能挡掉绝大部分低级错误。
10. **`_helper/` 里的脚本已过时**，不要按它们的路径去构建资源。
11. **新增前台功能时 PHP 与 JS 都要登记**：漏掉任一侧都会「页面有结构但无交互」或「脚本打包了但没人用」。
12. **`frontend/types/iro.d.ts` 必须保持脚本形态**：加了顶层 `import`/`export` 就变成模块，`_iro` / `Window` 的全局声明随即失效，全项目报 `TS2304 Cannot find name '_iro'`。要引用外部类型请用 `import("xxx").Yyy` 这种内联写法。
13. **在 `.ts` / `.vue` 里写了 `_iro` 却报「找不到名称」**：说明该文件没被 tsconfig 的 `include` 覆盖（会退回 VS Code 的 inferred project），而不是语法错误。
14. **别给 REST nonce 换 action**：`X-WP-Nonce` 头里必须放 `wp_create_nonce('wp_rest')`。内核 `rest_cookie_check_errors()`（`wp-includes/rest-api.php`）会拿这个头按 `wp_rest` 先校验一遍，用自定义 action 只会拿回 `rest_cookie_invalid_nonce`，连 `permission_callback` 都进不去。
15. **不要在 `frontend/`、`inc/blocks/` 里跑 `pnpm install`**：这两个目录只要出现自己的 `pnpm-workspace.yaml`（哪怕里面只有 `allowBuilds`），pnpm 就把该目录当成独立 workspace 根，静默生成子锁文件并彻底无视根 `pnpm-lock.yaml`，安装结果与提交的锁文件不一致且没有任何警告。依赖统一在主题根装。
16. **两个子目录里的 `node_modules` 只是软链农场**：物理包全部在根 `node_modules/`（约 1G），子目录各几十 KB。删掉子目录的 `node_modules` 不影响仓库，回主题根重跑 `pnpm install` 会重新生成；不要为了「只装前台」而去子目录单独装。

---

## 8. 相关文档

- 使用文档：<https://docs.fuukei.org>
- 上游仓库：<https://github.com/mirai-mamori/Sakurairo>
- 问题反馈：`.github/ISSUE_TEMPLATE/`（bug / feature / help）
