<div
    class="search-model flex-center site-model">
    <div class="search-header flex-center">
        <h3 class="search-title"><?= __("搜索",'sakurairo') ?></h3>
        <div class="close button" @click="modelStore.search=false">
            <i class="fa-icon-solid fa-close icon"></i>
        </div>
        <div class="search-input-wrapper flex-center">
            <i class="fa-icon-solid fa-search icon"></i>
            <input class="search-input" type="text" autocomplete="off" tabindex="0" placeholder="<?= __("想找点什么呢？",'sakurairo') ?>">
        </div>
        <!-- <span v-if="keyword?.length > 1" class="tip">#按Enter键继续</span> -->
    </div>
    <div class="search-list flex-center">
        <ul class="post-list">
        </ul>
    </div>
</div>