import hljsSupport from './modules/hljs';
import noticeBlock from './modules/notice'
import showcardBlock from './modules/showcard'
import conversationBlock from './modules/converstation'

export default function initBlocks() {
    try {
        hljsSupport();
        noticeBlock();
        showcardBlock();
        conversationBlock();
    } catch (error) {
        console.log(`发生错误${error}`)
        console.log(error.stack)
    }
}

initBlocks();
