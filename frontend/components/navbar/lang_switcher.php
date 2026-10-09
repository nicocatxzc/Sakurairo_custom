<?php
$iro_lang_links = iro_i18n_language_links();
?>
<?php if (count($iro_lang_links) > 1) : ?>
    <div class="lang-switcher" data-lang-switcher>
        <button
            type="button"
            class="lang-toggle"
            aria-expanded="false"
            aria-haspopup="true"
            aria-label="<?= esc_attr__('切换语言', 'sakurairo') ?>">
            <i class="fa-icon-solid fa-language icon"></i>
            <span class="lang-current">
                <?php foreach ($iro_lang_links as $iro_lang_link) : ?>
                    <?php if ($iro_lang_link['current']) : ?>
                        <?= esc_html($iro_lang_link['name']) ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </span>
        </button>
        <ul class="lang-menu"></ul>
    </div>
<?php endif; ?>
