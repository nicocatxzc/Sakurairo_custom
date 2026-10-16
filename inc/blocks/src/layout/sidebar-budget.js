import { store as blockEditorStore } from "@wordpress/block-editor";
import { useSelect } from "@wordpress/data";

export const SIDE_BAR = "sakurairo/site-side-bar";

// 全页侧栏上限：顶层的页面布局与路由容器里的页面内容容器共用这个预算，
// 因为前台同一行只排得下这么多侧栏
export const MAX_SIDE_BARS = 2;

/** 统计当前编辑页所有侧栏容器，含嵌套在页面内容容器里的 */
export function useSideBarCount() {
    return useSelect((selectStore) => {
        const countIn = (blocks) =>
            blocks.reduce((sum, block) => {
                const self = block.name === SIDE_BAR ? 1 : 0;
                return (
                    sum +
                    self +
                    countIn(selectStore(blockEditorStore).getBlocks(block.clientId))
                );
            }, 0);

        return countIn(selectStore(blockEditorStore).getBlocks());
    }, []);
}
