# AGENTS.md

面向 AI 编码代理的项目说明。本文件只写**相关文件**与**通用约定**，不记录版本号、具体配置值、现存实现清单等易变事实——需要这些时直接读对应文件（主题头在 `style.css`，各工程配置在各自的 `package.json` / `vite.config.js` / `tsconfig.json`）。

---

## 1. 项目性质

Sakurairo 是一个 **WordPress 主题**（非插件，不是插件，不要按插件的方式组织或加载代码），GPL-2.0。

- 后端是 PHP 模板 + 函数库；前端样式/脚本由独立工程构建成产物，PHP 只引用产物。
- 注释、文档、后台文案以中文为主，README 另有多语言版本。
- 环境要求（PHP / WordPress 版本、是否依赖新版 WordPress 特性）以 `style.css` 主题头与相关代码里的能力判断为准，不写死在本文件里。

---

## 2. 目录与文件职责

```tree
Sakurairo/
├── package.json / pnpm-workspace.yaml / pnpm-lock.yaml  # ★ workspace 根：脚本入口、成员清单、唯一锁文件
├── package.sh               # ★ 发布打包脚本（安装依赖 + 全量构建 + 剔除开发文件 + 生成 zip）
├── node_modules/ / temp/    # 依赖物理位置 / 打包中间产物（均不入库）
├── functions.php            # 后端入口：定义常量，按顺序 require 各模块（新增后端模块在此登记）
├── style.css                # 仅主题头（名称/版本/依赖声明），不含样式
├── header.php / footer.php / index.php / comments.php / 404.php
│                            # index.php = 主模板 + 局部渲染分发；header.php 含 Customizer 预览合并与 PJAX 保活脚本
├── frontend/                # ★ 前台源码（Vite 工程，workspace 成员）
│   ├── main.js / style.scss / layout.scss / icons.scss   # 入口与顶层样式汇总
│   ├── vite.config.js       # 入口定义、codeSplitting 分组、产物命名、dev server
│   ├── app/                 # 客户端核心运行时（非组件）：index.ts 提供 window._iro 与 hook；bus / pjax / darkmode / stores / utils / plugins
│   ├── components/          # ★ 每个组件「PHP 局部 + JS 行为 + SCSS」成对出现
│   │   ├── index.js         # 组件 JS 的聚合入口（新增组件 JS 必须在此登记）
│   │   ├── component_register.php  # PHP「函数组件」注册
│   │   └── block/ comment/ homepage/ navbar/ page/ post/ site/ slots/ icons/
│   ├── theme_config.php     # 把后端配置注入为 JSON <script>
│   ├── theme_style_vars.php # 输出全局/动态 CSS 变量
│   ├── types/               # 手写全局类型声明 + unplugin 自动生成的 d.ts（均入库）
│   └── dist/                # 构建产物（不入库）
├── inc/
│   ├── api.php + api/       # REST 路由注册与各命名空间的实现
│   ├── blocks/              # ★ 古腾堡编辑器工程（@wordpress/scripts，workspace 成员）
│   │   ├── src/             # 编辑器源码（index.js 汇总 editor/* 与 tools/*）
│   │   ├── build/           # 构建产物（不入库，PHP 引用）
│   │   ├── render.php       # 前台 shortcode / block 渲染
│   │   └── iro_blocks.php   # 编辑器资源入队与配置注入
│   ├── ai/                  # ★ AI 后台面板工程（Vite + Vue，workspace 成员）：src/ 源码 + dist/ 产物（不入库）
│   ├── functions/           # 后端功能模块：tools/ip/seo/nav_bar/operator/cust_wp/wp_cn/sitemap/smtp/player/enqueue_assets
│   │   ├── ai/              # AI Provider、生成工具、自检、后台页面
│   │   ├── comment/ content/ custom/ optimize/   # 分区子模块（optimize = 内置图片服务与压缩）
│   ├── libs/                # vendored 第三方 PHP 类
│   ├── theme_init/          # 主题初始化：support / translation / shuoshuo / wp_fix / check / iro_opt
│   ├── dash-scheme.php      # 后台设置页动态 CSS（独立输出，不经 WP 引导，不可用 WP 函数）
│   └── option-scheme.php    # 后台 / 登录页 CSS 皮肤（含 PHP 插值）
├── opt/                     # ★ 后台设置框架
│   ├── option-framework.php / theme-options.php   # 加载框架；所有后台设置项的唯一定义处
│   ├── csf/                 # 框架实现与字段（vendored，一般不改）
│   └── customizer/          # 可视化编辑器（含内置 kirki/）
├── translation/             # 翻译源稿 *.po / *.pot（入库）
├── languages/               # 编译产物 *.mo（不入库）
├── update-checker/          # 内置更新检查器（vendored，勿改）
└── .github/                 # Issue 模板等
```

---

## 3. 关键机制与对应文件

以下每节都是「改这里要遵守的约定」，不是当前实现清单。

### 3.1 设置项系统

- 主题设置集中存放在**单个 WP option 数组**中，读 `iro_opt($key, $default)`、写 `iro_opt_update($key, $value)`；实现与常量定义在 `inc/theme_init/iro_opt.php`。
- **新增设置项必须三处对齐**：后台字段定义（`opt/theme-options.php`）、读取处的默认值、以及需要在设置框架之间同步的映射键（可视化编辑器字段靠映射键写回同一 option）。映射键写错的表现是「预览正常、保存后失效」。

### 3.2 配置注入 → `window._iro`

`frontend/theme_config.php` 以 `<script type="application/json">` 输出若干份配置，前台在 `frontend/app/index.ts` 里解析为 `_iro.config` / `_iro.page` / `_iro.user` 等。

- 前台与后台/登录页注入的份数不同，因此「是否存在前台专属配置节点」被用作**服务端权威的前台标记**（`_iro.isBackend`），不要改成靠 DOM 里某个表单/元素是否存在来判断。
- PJAX 会替换其中部分配置节点，因此配置必须在**页面加载 hook** 里解析，而不是模块顶层解析一次。
- `_iro` 及其成员的类型声明集中在一个手写的全局声明文件中（`frontend/types/`）。它是**脚本形态**的声明文件：不要加顶层 `import`/`export`（会变成模块、全局声明失效）；新增 `_iro` 成员时同步补声明，并可从主题根跑前台的类型检查。

### 3.3 Hook 与初始化时机

`frontend/app/index.ts` 暴露 `_iro.hooks`（初次 DOM 就绪 + 各 PJAX 生命周期，并有「首次加载与 PJAX 完成」的合并钩子）。

- **组件 JS 一律通过 hook 初始化**，不要写顶层 DOM 操作或顶层事件监听，否则 PJAX 跳转后失效。
- 自带顶层监听/定时器的模块要自己判断是否为后台页面，后台不应运行前台运行时行为。
- 跨组件通信优先走事件总线 `_iro.bus` 与 PJAX 生命周期，不要各自加全局监听（如视口变化应复用统一的 resize 广播）。

### 3.4 PJAX

- PJAX 用 Swup，**替换容器列表固定**：由 `frontend/app/pjax.js` 指定。Swup 是在新文档里按选择器找同名节点替换，**缺一个容器就整块不更新**，因此这些容器必须在每个页面上都存在（即使为空）。改动容器 id 时，PHP 侧输出处必须同步。
- 链接默认走 PJAX；需要绕过的加 `no-pjax` 类（AJAX 翻页等与局部渲染冲突的链接必须加）。

### 3.5 局部模板渲染

`index.php` 读取请求头后只渲染片段（不输出整页），前端 AJAX 翻页/加载更多依赖它。

- 需要支持片段渲染的模板，在文件首行声明对应的全局标记，并把「容器开合」用条件劈开，使同一文件既能整页输出也能只输出片段。
- 新增可局部渲染的类型时，`index.php` 的分发与前端请求处要同时更新。

### 3.6 组件约定

新增一个前台组件，三件事缺一不可：

1. **PHP**：`frontend/components/<area>/<name>.php`，用 `require_once` 挂进对应模板，或注册为「函数组件」；模板写法见 §4.2。
2. **JS**：`frontend/components/<area>/<name>.js`，并在**聚合入口**登记（组件进 `frontend/components/index.js`，核心进 `frontend/app/index.ts`）。**不登记就不会被打包**。
3. **样式**：同名 `.scss`，在对应分区 `index.scss` 里 `@use`。

- 复杂交互可用 Vue SFC；前后台都会用到的组件样式要放进独立 `.scss` 统一管理，不要放在 SFC 的 `<style scoped>` 里（会变成异步 CSS，首屏多一次请求）。
- 需要脱离主 bundle 独立运行的脚本要自行兜底：能拿到 hook 就走 hook，否则直接挂载；配置解析也要有等价回退。

### 3.7 REST 接口

- 统一命名空间与统一权限回调（nonce 校验、以及在此之上的权限校验）由一个集中文件定义，各接口实现放在 `inc/api/`。
- **nonce 的 action 必须是 WordPress 标准的 `wp_rest`**：带 cookie 的请求，内核会用同一请求头按该 action 预先校验，自定义 action 会在进入 `permission_callback` 之前就被拒绝。
- 新增接口要同时更新前端的调用处与（如有必要的）注入给前端的配置。

### 3.8 翻译

- 新文案统一使用主题 textdomain；CSF 设置页、可视化编辑器、更新检查器各有自己的 textdomain 与语言包目录，不要混用。
- **只改翻译源稿**（`translation/*.po` / `.pot`），`.mo` 是构建产物、不入库、会被打包流程覆盖。

### 3.9 区块与 AI 面板

- 编辑器区块在 `inc/blocks/src/`，前台渲染与 shortcode 在 `inc/blocks/render.php`；编辑器资源的入队实现里有**刻意的双重注册**（外层文档 + 区块画布），不要当成冗余删掉。
- AI 面板是独立后台工程（`inc/ai/` + `inc/functions/ai/`），前台不加载，产物由 PHP 以模块脚本加载。
- AI 能力依赖较新的 WordPress 特性：相关 PHP 靠**能力检测**（类/函数是否存在）提前 `return`，旧版本下后台看不到入口属预期行为，不要当 bug 去补依赖。
- AI 面板用样式隔离容器（Shadow DOM）避免被后台样式污染，新增样式不要假设全局 CSS 会命中。
- 上述两个工程的**源码与产物分离**：产物不入库，改完源码必须本地构建才能生效，且缺失产物时 PHP 侧是静默跳过而不报错。

---

## 4. 编码规范

### 4.1 PHP

- 会产生副作用的文件（直接输出内容、注册路由/钩子）顶部加 `if (!defined('ABSPATH')) { exit; }`；纯函数定义文件不加。判断依据是「能否被直接访问并产生副作用」，不是无差别全覆盖。
- 新函数统一加 `iro_` / `sakurairo_` 前缀；可能被重复 require 的核心函数用 `if (!function_exists(...))` 包裹。**前缀规则不回溯**：历史函数名即使风格不一致也保持原样，不要改名。
- 新函数带参数与返回类型标注；历史函数普遍没有，不要顺手大规模补。
- 输出必须转义（`esc_html` / `esc_attr` / `esc_url` / `esc_js` / `esc_html__`），输入必须清洗（`sanitize_*` / `intval`）。
- **4 空格缩进**。仅 vendored 库与两个独立输出的后台皮肤文件保持其原有 tabs 风格，不要跨文件统一。

### 4.2 PHP 组件模板 —— 类 Vue 风格

组件模板是「PHP 版的 Vue 模板」：

- 条件/循环用替代语法（`if/endif`、`foreach/endforeach`、`switch/endswitch`）包裹 HTML，结束标签独占一行、与被包裹的 HTML 同缩进层级。
- 动态值一律用短标签内联输出，表达式直接写在标签属性/文本里（含默认值），不要为了拼字符串先赋值再 `echo`。
- **只使用一次的表达式一律内联**；仅当结果被复用多次、或长表达式需要拆解时才提取变量。不要为了好读而在模板顶部堆一批 `$xxx = ...;`。
- 模板参考以「零中间变量、表达式内联（含 `?? 默认值`）」的文件为准；历史文件里的空格风格、松散比较、单双引号混用等属于遗留，不要模仿。
- 结构性重复（容器开合等）才抽成函数组件，其余保持单文件模板。

### 4.3 注释

- **默认不写注释**。只在三种情况下写：说明关键机制/非显而易见取舍、标注容易踩坑处、记录无法从代码检索到的外部行为（内核/框架/浏览器约定）。
- 复述代码的注释一律不要。
- 形式：过程内的「为什么」用 `//`（多行也连写多个 `//`）；函数、全局对象、类型字段的契约用 `/** */`。**源码中不使用普通 `/* */` 块注释**。

### 4.4 JS / Vue / SCSS

- ESM、**4 空格缩进**、双引号；使用全局 `_iro` 时直接写 `_iro`，不要重复声明 `window._iro`。
- 类型检查开了未使用变量/参数检查，多余声明会导致检查失败；`.vue` 不保证能被现有工具链检查，仍按同样风格书写。
- **请求工具按「所在工程的依赖上下文 + 工具自身职责」选，不要一刀切**：
  - 三个工程的依赖互相隔离，某个子包可能没有 axios，那种目录只能用 `fetch`（或它自带的薄封装）。
  - 全局只有一个「仅 GET 的缓存封装」工具，它没有 baseURL、不注入 nonce，只适用于**可缓存的 GET 读**；写操作与必须每次新鲜的读都用裸请求工具并自行带 nonce。
  - 二进制/blob、非 REST 的原生表单提交用 `fetch`。
  - 判断不了就跟所在文件的既有写法，不要引入新范式。
- SCSS 用 Dart Sass 模块语法 `@use ... as <语义命名>`；唯一例外是导入第三方 `.css`（`.css` 不适用 `@use`），其余不要用 `@import`。

### 4.5 通用

- 不引入新的第三方库，除非确实必要并同步更新锁文件；不修改 vendored 上游代码目录（更新检查器、CSF、Kirki 等）。
- 优先复用已有机制（hook、事件总线、设置项、既有工具函数），而不是新增平行机制。

---

## 5. 构建与依赖

- 依赖由**主题根的 pnpm workspace 统一管理**：`pnpm install` 只在主题根执行一次，依赖物理装在根 `node_modules/`，子工程目录里只有软链。
- **不要在子工程目录里 `pnpm install`，也不要在那里新增 `pnpm-workspace.yaml` / 锁文件**：一旦出现，pnpm 会把该目录当成独立 workspace 根、静默忽略根锁文件，安装结果与提交的锁文件不一致且没有警告。
- 锁文件只有一个；`packageManager` 只在根 `package.json` 声明；不要用 npm/yarn。
- 根 `package.json` 脚本约定：`build:*` 分别构建单个工程，`dev` / `dev:ai` 分别起各工程的 dev server，`build` 是**发布打包**（并非单纯构建）——只想要构建产物时用分工程的脚本。
- **构建产物一律不入库**：前台产物、区块产物、AI 面板产物都由本地构建生成（前端 unplugin 自动生成的类型声明文件属例外，需要提交）。缺失产物时 PHP 侧多为静默跳过，表现为「区块/面板不出现」而不是报错，新 clone 后必须自行构建。
- 依赖解析按成员隔离，同一库的 CJS / ESM、不同框架版本可以在不同工程内并存。
- 打包脚本会校验子目录无残留锁文件、冻结锁文件安装、全量构建，并在产物中剔除源码与开发文件。

### 5.1 后端构建产物的加载约定

- PHP 按**固定入口名**引用产物，因此入口文件名是契约，改变入口要同步 PHP。
- 供不同页面单独加载的入口要保持独立（例如仅登录页用、仅特定文章排版用的样式/脚本），不要并入首屏入口，否则首屏会多下载。
- 需要按需加载的模块用动态 `import`；但要确认构建分组不会把按需依赖提升进启动 chunk——首屏用不到的第三方依赖需要显式声明为惰性分组。
- 共享代码必须落进显式分组：**入口 chunk 被别的 chunk 反向引用会导致同一模块被下载执行两次**，构建里有守卫会直接报错。
- 首屏关键 chunk 由 PHP 反查产物文件名后预载；预载 URL 必须与实际 import 解析结果逐字一致，因此不要给 chunk 之间的 import 加查询串，保留内容哈希、由 PHP 按模式查找。

---

## 6. 验证与自检

项目**没有** PHP 单元测试框架，也没有提交式的格式/风格检查命令（编辑器插件已覆盖格式化，提交即视为格式检查通过）。因此验证手段是：

1. PHP 改动先跑语法检查，再人工复核模板标签、括号/引号闭合与 `<?php ?>` 配对。
2. 前端改动按范围跑对应构建（或类型检查）——`.js` 只做智能提示级别的纳入，类型检查主要覆盖 `.ts`。
3. 新增设置项：确认后台字段、读取默认值、映射键一致。
4. 新增组件：确认 PHP 与 JS 两侧都已登记，且初始化挂在页面对应的 hook 上。
5. 新增/修改 `_iro` 成员：同步全局类型声明并跑前台类型检查。
6. 增删依赖：只改对应工程的 `package.json`，回主题根更新锁文件，确认子目录没有冒出锁文件。
7. 改翻译：只改源稿，必要时重新编译。
8. vendored 目录与打包脚本剔除的开发文件不要改出依赖。

### 6.1 调用 shell 的约定

- 命令**尽量重定向输出到文件再读文件**，不要依赖裸命令回显；需要一次取回时用「重定向 + tail」的形式。
- 长耗时命令（安装、构建、打包）放后台并落盘，之后读日志判断成败。
- 日志放系统临时目录、命名带统一前缀，避免污染仓库工作区。
- **以日志内容为准**：一次没拿到输出不代表成功或失败，不要据此下结论或重复执行。
