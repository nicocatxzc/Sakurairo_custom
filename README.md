**简体中文** | [繁體中文](README_tw.md) | [English](README_en.md) | [日本語](README_ja.md)

[![image](https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/banner-cn.webp)](https://github.com/mirai-mamori/Sakurairo)

<h1 align="left">Theme Sakurairo </h1>

> 一款多彩、友好、功能全面、体验完善的 WordPress 主题，。

[![GitHub release](https://img.shields.io/github/v/release/mirai-mamori/Sakurairo.svg?style=for-the-badge&logo=appveyor)](https://github.com/mirai-mamori/Sakurairo/releases/latest)[![GitHub Release Date](https://img.shields.io/github/release-date/mirai-mamori/Sakurairo?style=for-the-badge&logo=appveyor)](https://github.com/mirai-mamori/Sakurairo/releases)![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/mirai-mamori/Sakurairo?style=for-the-badge&logo=appveyor)[![jsDelivr hits (GitHub)](https://img.shields.io/jsdelivr/gh/hm/Fuukei/Public_Repository?color=red&logo=jsdelivr&logoColor=red&style=for-the-badge)](https://www.jsdelivr.com/package/gh/mirai-mamori/sakurairo)

![Alt](https://repobeats.axiom.co/api/embed/292776675b642d6dc86f264f4b71ed411ee9be91.svg "Repobeats analytics image")

## 功能简介

- **界面**：多彩、友好、响应式布局，支持浅色 / 暗色模式与自定义主题色。
- **性能**：使用高效的代码分割，且内置大量优化，首屏与浏览速度极佳。
- **AI 辅助阅读**：文章内提供 AI SEO优化，兼容 WordPress 官方 AI 插件。
- **首页与封面**：支持图片 / 视频封面、特色图背景与背景填充模式。
- **文章**：目录、代码高亮、图片灯箱、阅读量统计、SEO 优化与站点地图。
- **评论区**：Markdown 渲染、评论表情、邮件通知与 SMTP 集成。
- **后台设置**：CodeStar Framework 设置面板与 Kirki 可视化编辑器。
- **国际化**：内置简体中文、繁體中文、English、日本語。

## 下载及使用相关

- 你可以在仓库release自行下载或拉取源码仓库使用构建脚本打包（需要nodejs24+debian环境）

- 使用本主题的博客：<https://docs.fuukei.org/demo/>

- 主题交流：[QQ群:784229925](https://jq.qq.com/?_wv=1027&k=U5UJjRik)  ＆  [Telegram群:fksakurairo](https://t.me/fksakurairo)

- 如果在使用过程中遇到了任何问题，请**访问**本主题的 [支持文档](https://docs.fuukei.org)

- 在确认你遇到的现象确实是一个 Bug 后，请在 [Issues](https://github.com/mirai-mamori/Sakurairo/issues/new/choose) 提交问题，并为该问题尽可能的描述清楚，
按照提供的 issue 模板进行填写，谢谢配合。

## 项目结构

本主题的前后端结构已做过一次重构：模板、样式与脚本按组件归位，设置项各自归入独立目录。主要目录如下：

```tree
Sakurairo/
├── frontend/            # 前台前端工程
│   ├── app/             # 入口、PJAX、暗色模式、工具函数
│   ├── components/      # 组件目录：PHP 模板 + SCSS + JS 同目录
│   └── dist/            # 构建产物
├── inc/
│   ├── ai/              # 后台 AI 面板工程
│   ├── blocks/          # 古腾堡自定义区块
│   ├── api/             # REST 路由
│   ├── functions/       # 主题功能
│   ├── libs/            # 功能库
│   └── theme_init/      # 主题初始化与选项读取
├── opt/
│   ├── csf/             # CodeStar Framework
│   ├── customizer/      # Kirki 可视化编辑器
│   └── theme-options.php
├── translation/         # 翻译源文件
├── languages/           # 编译后的翻译
├── package.json         # pnpm workspace 脚本入口
├── package.sh           # 发布打包脚本
├── pnpm-workspace.yaml  # workspace 成员与构建白名单
└── style.css            # 主题头信息
```

前台资源由 Vite 构建，产物 `frontend/dist/`、`inc/blocks/build/`、`inc/ai/dist/` 均不入库；从 Git 克隆后需在主题根执行 `pnpm install` 与 `pnpm build` 后再使用。

## 赞助商

<a href="https://waf.pro/"><img src="https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/wafpro.webp" alt="warpro" width="200"></a>  

## 感谢每一位支持我们的你

<a href="https://afdian.com/a/mamori"><img alt="afdian" height="50" src="https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/afdian.webp"></a>

[![image](https://fuukei-api.nyat.icu/api/sponsors)](https://afdian.com/a/mamori)

## 主题贡献

<a href="https://github.com/mirai-mamori/Sakurairo/graphs/contributors"><img src="https://fuukei-api.nyat.icu/api/contributors" alt="contributors" height="100%" width="100%"></a>

## 主题说明

### 开源相关

- 本主题**基于 [Sakura V3 Series](https://github.com/mashirozx/sakura/tree/3.x) 主题进行重构开发**，使用 [GPL V2.0](https://github.com/mirai-mamori/Sakurairo/blob/master/LICENSE) 协议开源。

- 本主题使用了部分来自互联网的特效。由于版权及开源协议不明，无法具体说明相关信息。如果本主题使用到您制作的特效，烦请您通过邮件（me#hareru.org）来取得联系。

### 引用相关

- 本主题社交网络图标中，流畅设计图标引用于由 Paradox 设计的 Fluent 图标包

- 本主题社交网络图标中，沐氢图标引用于由缄默设计的沐氢图标包

### 依赖相关

- 本主题使用 Codestar [Codestar Framework](https://github.com/Codestar/codestar-framework) 作为设置框架

- 本主题使用 YahnisElsts [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) 以提供主题更新功能

- 本主题使用 Themeum [Kirki](https://github.com/themeum/kirki) 以提供可视化编辑器相关功能

## 希望你喜欢

- Star 趋势  [![GitHub stars](https://img.shields.io/github/stars/mirai-mamori/Sakurairo?logo=github&style=social)](https://github.com/mirai-mamori/Sakurairo/stargazers)

[![Stargazers over time](https://starchart.cc/mirai-mamori/Sakurairo.svg)](https://github.com/mirai-mamori/Sakurairo/stargazers)
