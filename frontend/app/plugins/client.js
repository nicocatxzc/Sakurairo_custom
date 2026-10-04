const getTextStyle = (color) => {
    return `color:${color};font-size:12px;font-family:"Comic Sans MS", "Comic Neue", "Tahoma";font-weight:900`;
};

console.log(
    `%cTHEME \n%cSAKURAIR%cO \n%chttps://github.com/mirai-mamori/Sakurairo`,
    getTextStyle("inherit"),
    getTextStyle("inherit"),
    getTextStyle("#f1e05a"),
    getTextStyle("#65c9fe"),
);

console.log("Build version", "3.1.0");
