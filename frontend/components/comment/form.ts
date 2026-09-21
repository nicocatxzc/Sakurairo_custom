import { checkEmail } from "../../app/utils/check";
import md5 from "md5";

_iro.hooks.onPageLoaded(() => {
    const commentForm = document.querySelector(
        "#respond.comment-respond",
    ) as HTMLElement;
    if (!commentForm) return;

    const avatar = commentForm.querySelector(".avatar") as HTMLImageElement;
    const email = commentForm.querySelector("#email") as HTMLInputElement;

    let originAvatar = avatar.src;
    email.addEventListener("change", () => {
        if (!checkEmail(email.value)) {
            avatar.src = originAvatar;
            return;
        }
        const gravatar =
            "https://gravatar.com/avatar/" +
            md5(email.value) +
            ".jpg?s=" +
            80 +
            "&d=mm";
        avatar.src = gravatar;
    });

    const commentList = document.querySelector(".comment-list") as HTMLElement;
    const form = document.getElementById("commentform") as HTMLFormElement;
    const textarea = document.getElementById("comment") as HTMLTextAreaElement;
    const replyContext = document.getElementById(
        "reply-context",
    ) as HTMLSpanElement;
    const commentParent = document.getElementById(
        "comment_parent",
    ) as HTMLInputElement;

    // 回复目标
    let replyTarget: any = {};

    function setReplyTarget(id: string, name: string) {
        replyTarget = {
            id: String(id),
            name: String(name),
        };

        replyContext.hidden = false;

        replyContext.innerHTML = /* html */ `
                <span class="reply">
                    正在回复给
                    <a
                        class="reply-target"
                        href="#comment-${escapeHtml(replyTarget.id)}"
                    >
                        @${escapeHtml(replyTarget.name)}
                    </a>
                    <a
                        href="#"
                        class="cancel-reply"
                    >
                        取消回复?
                    </a>
                </span>
            `;

        commentParent.value = replyTarget.id;

        textarea.focus();
    }

    function clearReplyTarget() {
        replyTarget = {};

        replyContext.hidden = true;
        replyContext.innerHTML = "";

        // 默认顶级评论
        commentParent.value = "0";
    }

    // 防止 author/comment 等内容被当成 HTML 注入
    function escapeHtml(value: string) {
        const div = document.createElement("div");
        div.textContent = value ?? "";
        return div.innerHTML;
    }

    // 监听回复点击
    commentList.addEventListener("click", (event) => {
        console.log(222);
        if (event.target) {
            console.log(111);
            const replyButton = event.target.closest(".reply-button");

            if (!replyButton || !commentList.contains(replyButton)) {
                return;
            }

            event.preventDefault();

            const commentId = replyButton.dataset.commentid;
            const commentAuthor = replyButton.dataset.commentauthor;

            if (!commentId) {
                return;
            }

            setReplyTarget(commentId, commentAuthor || "");
        }
    });

    // 取消回复

    replyContext.addEventListener("click", (event) => {
        if (event.target) {
            const cancelButton = event.target.closest(".cancel-reply");

            if (!cancelButton) {
                return;
            }

            event.preventDefault();

            clearReplyTarget();
            textarea.focus();
        }
    });

    //表单提交

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        // 收集所有数据
        const formData = new FormData(form);

        // 这些字段在下方手动映射，不能按原名直接提交（author 在 REST 里是用户 ID）
        const mappedFields = [
            "author",
            "email",
            "url",
            "comment",
            "comment_post_ID",
            "comment_parent",
            "captcha_id",
            "captcha_text",
        ];

        const requestData: any = {
            /**
             * 未手动收集的字段按原名扁平提交
             * （_wp_unfiltered_html_comment、enable_markdown 等）
             */
            ...Object.fromEntries(
                [...formData.entries()].filter(
                    ([name]) => !mappedFields.includes(name),
                ),
            ),

            author_name: formData.get("author") || "",
            author_email: formData.get("email") || "",
            author_url: formData.get("url") || "",
            content: formData.get("comment") || "",
            post: Number(formData.get("comment_post_ID") || 0),
            parent: Number(formData.get("comment_parent") || 0),

            /**
             * 验证码字段
             */
            captcha_id: formData.get("captcha_id") || "",
            captcha_text: formData.get("captcha_text") || "",
        };

        /**
         * 基础校验
         */
        if (!requestData.post) {
            _iro.message("找不到评论上下文，请刷新重试", "error");
            return;
        }

        if (!requestData.content.trim()) {
            _iro.message("评论不能为空", "warning");
            textarea.focus();
            return;
        }

        /**
         * 防止重复提交
         */
        const submitButton = form.querySelector("#submit") as HTMLButtonElement;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.dataset.oldValue = submitButton.value;
            submitButton.value = "提交中…";
        }

        try {
            const response = await fetch(form.action, {
                method: "POST",
                credentials: "same-origin",

                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },

                body: JSON.stringify(requestData),
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(
                    result?.message || result?.msg || "评论提交失败。",
                );
            }

            if (!result.rendered) {
                throw new Error("服务器没有返回 rendered 评论内容。");
            }

            insertRenderedComment(result.rendered);

            textarea.value = "";

            clearReplyTarget();
            document.dispatchEvent(
                new CustomEvent("captcha:refresh", { detail: {} }),
            );
        } catch (error: any) {
            console.error("[Comment]", error);

            _iro.message(error.message || "评论发送失败，请稍后再试", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.value =
                    submitButton.dataset.oldValue || "发表评论";
            }
        }
    });

    // ajax评论
    function insertRenderedComment(rendered: string) {
        const template = document.createElement("template");

        template.innerHTML = rendered.trim();

        const commentElement = template.content.firstElementChild;

        if (!commentElement) {
            throw new Error("rendered 评论 HTML 无效。");
        }

        /**
         * 如果当前是回复某条评论：
         * 插到目标评论后面。
         *
         * 否则：
         * 默认插到列表最前面。
         */
        if (replyTarget.id) {
            const target = document.getElementById(`comment-${replyTarget.id}`);

            if (target) {
                target.insertAdjacentElement("afterend", commentElement);
            } else {
                commentList.prepend(commentElement);
            }
        } else {
            commentList.prepend(commentElement);
        }

        // 滚动到目标评论
        commentElement.scrollIntoView({
            behavior: "smooth",
            block: "center",
        });
    }
});
