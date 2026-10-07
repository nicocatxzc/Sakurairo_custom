import "./widget/toolbar";
import "./particle";
import "./search_form";
import "./progress_bar";
import "./footer/hitokoto";
import "./footer/island";
import "./player";

// 验证码是 Vue 应用，只在带评论表单的页面（文章页/独立页面）出现，首页与列表页用不到。
// 放到页面加载钩子里判一次容器再异步 import，把它从入口静态依赖里摘出去；
// 登录页仍由 components/login.js 静态加载同一份模块，两边共用同一个 chunk。
_iro.hooks.onPageLoaded(() => {
    if (document.querySelector(".captcha")) {
        import("./captcha/captcha");
    }
});
