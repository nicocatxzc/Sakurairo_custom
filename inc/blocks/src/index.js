import hljsSupport from './modules/hljs';
import noticeBlock from './modules/notice'

export default function initBlocks() {
    try {
        hljsSupport();
        noticeBlock();
    } catch (error) {
        console.log(`发生错误${error}`)
        console.log(error.stack)
    }
}

initBlocks();
