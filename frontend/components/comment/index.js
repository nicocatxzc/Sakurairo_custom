_iro.hooks["DOMContentLoaded"].add(async()=>{
    const commentForm = document.querySelector("#respond.comment-respond")
    if(commentForm) {
        await import("./form")
    }
})