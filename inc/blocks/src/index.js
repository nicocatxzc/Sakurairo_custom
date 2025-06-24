import hljsSupport from './modules/hljs';
import noticeBlock from './modules/notice'
import showcardBlock from './modules/showcard'
import conversationBlock from './modules/converstation'
import bilibiliBlock from './modules/bilibili';

export default function initBlocks() {
    try {
        hljsSupport();
        noticeBlock();
        showcardBlock();
        conversationBlock();
        bilibiliBlock();
    } catch (error) {
        console.log(`发生错误${error}`)
        console.log(error.stack)
    }
}

initBlocks();
