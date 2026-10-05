<?php
// 波浪挂在封面内部：封面的 position: relative 就是波浪的定位上下文，
// 封面隐藏时波浪一并消失，不需要额外的显隐逻辑。
if (!iro_opt("homepage_wave_switch", false)) {
    return;
}

$iro_wave_modes = ["white", "water", "star"];
$iro_wave_light = iro_opt("homepage_wave_light", "water");
$iro_wave_dark = iro_opt("homepage_wave_dark", "star");
$iro_wave_light = in_array($iro_wave_light, $iro_wave_modes, true) ? $iro_wave_light : "water";
$iro_wave_dark = in_array($iro_wave_dark, $iro_wave_modes, true) ? $iro_wave_dark : "star";
$iro_wave_basic = iro_opt("vision_resource_basepath", "https://s.nmxc.ltd/sakurairo_vision/@3.0/") . "basic/";
?>
<div
    class="iro-wave iro-wave--<?= iro_opt("homepage_wave_position", "cover") === "window" ? "window" : "cover" ?>"
    data-wave-light="<?= esc_attr($iro_wave_light) ?>"
    data-wave-dark="<?= esc_attr($iro_wave_dark) ?>"
    style="
        --iro-wave-asset-white-front: url(<?= esc_url($iro_wave_basic . "wave1.png") ?>);
        --iro-wave-asset-white-back: url(<?= esc_url($iro_wave_basic . "wave2.png") ?>);
        --iro-wave-asset-water: url(<?= esc_url($iro_wave_basic . "wave_water.png") ?>);
    ">
    <div class="iro-wave__surface">
        <div class="iro-wave__layer iro-wave__layer--back"></div>
        <div class="iro-wave__layer iro-wave__layer--front"></div>
    </div>
    <div class="iro-wave__stars"></div>
    <?php foreach ((array) iro_opt("homepage_wave_floating", []) as $iro_wave_item): ?>
        <?php if (!empty($iro_wave_item["url"])): ?>
            <div class="iro-wave__floating">
                <img
                    src="<?= esc_url(iro_media_optimize_image_url($iro_wave_item["url"])) ?>"
                    alt=""
                    draggable="false" />
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
