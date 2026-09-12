function checkEmail(email:string) {
    return email.match(
        /^[A-Za-z0-9\u4e00-\u9fa5]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/,
    );
}

export {checkEmail}