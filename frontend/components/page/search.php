<div class="page-search">
    <?php iro_content_container_start() ?>
    <header class="search-header flex-center">
        <div class="search-box flex-center">
            <i class="fa-solid fa-search search-icon"></i>

            <input
                type="text"
                class="search-input"
                placeholder="搜索文章、标题或摘要"
                @keyup.enter="gotoSearch" />

            <button
                class="search-button"
                :disabled="!inputKeyword"
                @click="gotoSearch">
                搜索
            </button>
        </div>
    </header>

    <?php require_once get_template_directory() . '/frontend/components/post/list.php'; ?>
    <?php iro_content_container_end() ?>
</div>