import { checkEmail } from "../../app/utils/check";
import md5 from "md5";

const commentForm = document.querySelector(
    "#respond.comment-respond",
) as HTMLElement;

const avatar = commentForm.querySelector(".avatar") as HTMLImageElement;
const email = commentForm.querySelector("#email") as HTMLInputElement;

let originAvatar = avatar.src;
email.addEventListener("change", () => {
    if (!checkEmail(email.value)) {
        avatar.src = originAvatar;
        return;
    }
    const gravatar =
        "https://gravatar.com/avatar/" + md5(email.value) + ".jpg?s=" + 80 + "&d=mm";
    avatar.src = gravatar;
});
