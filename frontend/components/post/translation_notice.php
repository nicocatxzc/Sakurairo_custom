<?php
$iro_i18n_post_id  = (int) get_the_ID();
$iro_i18n_outdated = iro_i18n_translation_outdated($iro_i18n_post_id);
$iro_i18n_skeleton = iro_i18n_is_skeleton($iro_i18n_post_id);
$iro_i18n_source   = ($iro_i18n_outdated || $iro_i18n_skeleton) ? iro_i18n_group_source($iro_i18n_post_id) : null;
?>
<?php if ($iro_i18n_outdated && $iro_i18n_source instanceof WP_Post) : ?>
    <div class="translation-notice is-outdated" role="note">
        <i class="fa-icon-solid fa-clock-rotate-left icon" aria-hidden="true"></i>
        <span class="notice-text">
            <?= esc_html(sprintf(
                /* translators: %s: 原文最后一次改动的日期时间 */
                __('原文已于 %s 更新，本页译文可能与之存在差异。', 'sakurairo'),
                iro_i18n_source_version($iro_i18n_source->ID)
            )) ?>
        </span>
        <a class="notice-link" href="<?= esc_url(iro_i18n_translation_permalink($iro_i18n_source)) ?>">
            <?= esc_html__('查看最新原文', 'sakurairo') ?>
        </a>
    </div>
<?php elseif ($iro_i18n_skeleton && $iro_i18n_source instanceof WP_Post) : ?>
    <div class="translation-notice is-untranslated" role="note">
        <i class="fa-icon-solid fa-circle-info icon" aria-hidden="true"></i>
        <span class="notice-text"><?= esc_html__('本页尚未翻译，以下内容为原文。', 'sakurairo') ?></span>
        <a class="notice-link" href="<?= esc_url(iro_i18n_translation_permalink($iro_i18n_source)) ?>">
            <?= esc_html__('查看原文页面', 'sakurairo') ?>
        </a>
    </div>
<?php endif; ?>
