+++
title = "Markdown 语法测试"
date = 2026-10-26T22:00:00+08:00
lastmod = 2026-10-26T22:30:00+08:00
draft = false
description = "覆盖主题会用到的基础 Markdown 语法的测试页"
categories = ["测试"]
tags = ["markdown", "测试", "代码高亮", "公式"]
[params]
  feature = "https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/afdian.webp"
  title_style = ""
+++

这一页用来核对模板对基础 Markdown 的渲染结果：标题层级、强调、列表、表格、引用、代码块、链接、图片、脚注、任务列表与公式。

## 标题层级

### 三级标题

#### 四级标题

##### 五级标题

目录（TOC）应该能收进 `h2` 到 `h4`。

## 强调与行内元素

**加粗**、*斜体*、~~删除线~~、`行内代码`、[站内链接](/about/)、[外链](https://gohugo.io/)、以及自动链接 <https://example.com>。

Hugging Face 上的 `get_peft_model` 与 `PeftModel.from_pretrained` 这类驼峰命名，行内代码不应被拆词。

## 列表

- 无序项一
- 无序项二
  - 嵌套项
  - 另一个嵌套项
- 无序项三

1. 有序项一
2. 有序项二
3. 有序项三

- [x] 已完成的任务
- [ ] 未完成的任务

## 引用

> 这是一段引用。
>
> 引用里的第二个段落，带 **加粗**。

## 表格

| 语法 | 说明 | 对齐 |
| :--- | :---: | ---: |
| `**粗体**` | 加粗 | 左 |
| `*斜体*` | 斜体 | 中 |
| `~~删除~~` | 删除线 | 右 |

## 代码块

带语言的代码块交给 highlight.js：

```js
const theme = window._iro;
if (theme && theme.hooks) {
    theme.hooks.add("page:loaded", () => {
        console.log("ready", location.pathname);
    });
}
```

```css
.post-content .hljs {
    background: var(--code-background);
    border-radius: 0.6rem;
}
```

不带语言标记的代码块：

```
plain text block
  indented line
```

行内代码含反引号：`` `code` ``。

## 数学公式

行内公式：质能方程 $E = mc^2$，以及欧拉恒等式 $e^{i\pi} + 1 = 0$。

块级公式：

$$
\int_{-\infty}^{\infty} e^{-x^2} \, dx = \sqrt{\pi}
$$

矩阵（块内出现「整行只有一个 `=`」时，Goldmark 会把它当成 setext 标题下划线从而提前结束公式块，这种公式用 `math` shortcode 包起来）：

{{< math >}}
\begin{pmatrix}
a & b \\
c & d
\end{pmatrix}
=
\begin{pmatrix}
x \\
y
\end{pmatrix}
{{< /math >}}

行内代码里的内容不会被公式化，例如 `$HOME`、`$$`。

## 图片

![示例图片](https://s.nmxc.ltd/sakurairo_vision/@3.0/readme/liberapay.webp "点击可放大")

## 分隔线

---

## HTML 片段

<div class="notice">
正文里的内联 HTML 需要保留（goldmark 的 unsafe 已打开）。
</div>

## 脚注

这里有一个脚注[^1]，还有第二个[^2]。

[^1]: 第一个脚注的内容。
[^2]: 第二个脚注的内容，带 `代码`。
