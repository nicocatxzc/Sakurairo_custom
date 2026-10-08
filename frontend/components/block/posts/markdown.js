import parseMarkdown from "../../app/utils/parseMarkdown";

_iro.hooks.onPageLoaded(async () => {
    const markdown = document.querySelectorAll(".wp-block-hachimi-markdown");
    markdown.forEach(async (e) => {
        const mdtext = e.querySelector("pre").innerHTML;
        const rendered = await parseMarkdown(mdtext);
        e.innerHTML = rendered;
    });
});
