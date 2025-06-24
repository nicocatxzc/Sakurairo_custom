import { setCategories, getCategories } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';
import hljsSupport from './modules/hljs';
import noticeBlock from './modules/notice'
import showcardBlock from './modules/showcard'
import conversationBlock from './modules/converstation'
import bilibiliBlock from './modules/bilibili';

domReady(() => {
	// 获取已有分类
	const existing = getCategories();

	// 插入分类
	const updated = [
		...existing.slice(0, 1),
		{
			slug: 'sakurairo',
			title: 'Sakurairo',
		},
		...existing.slice(1),
	];

	// 更新分类
	setCategories(updated);
});

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
