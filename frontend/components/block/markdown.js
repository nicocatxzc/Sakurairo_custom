import parseMarkdown from "../../app/utils/parseMarkdown";

_iro.hooks.onPageLoaded(() => {
    const markdown = document.querySelectorAll(".wp-block-hachimi-markdown");
    markdown.forEach((e) => {
        const mdtext = e.querySelector("pre").innerHTML;
        const rendered = parseMarkdown(mdtext);
        e.innerHTML = rendered;
    });
});
