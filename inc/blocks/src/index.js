import hljsSupport from './modules/hljs';

export default function initBlocks() {
    try {
        hljsSupport();
    } catch (error) {
        console.log(`发生错误${error}`)
        console.log(error.stack)
    }
}

initBlocks();
