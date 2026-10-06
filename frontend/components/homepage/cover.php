<?php if (iro_opt("cover_switch", true) != false && iro_opt('cover_height', 100) != 0) : ?>
    <div class="homepage-cover <?= is_home() ? '' : 'hide' ?>"
        style="
        --cover-height: <?= iro_opt('cover_height', 100) ?>dvh; 
        --cover-filter-grid: url(<?= iro_opt('vision_resource_basepath', BASIC_VISION_RESOURCE_PATH) ?>basic/grid.png);
        --cover-filter-dot: url(<?= iro_opt('vision_resource_basepath', BASIC_VISION_RESOURCE_PATH) ?>basic/dot.gif);">
        <?php if (iro_opt("cover_video", false)): ?>
            <video class="cover-video" src="<?= iro_opt("cover_video_source") ?>" <?= iro_opt("cover_video_loop", false) ? "loop" : "" ?> muted autoplay></video>
        <?php endif; ?>
        <figure
            class="cover
        <?= iro_opt('cover_as_background', false) ? 'transparent' : '' ?>
        <?= iro_opt('cover_video', false) ? 'transparent' : '' ?>
        ">
            <div class="cover-filter <?= esc_attr(iro_opt('cover_pic_filter', 'filter-nothing')) ?>"></div>
            <div class="cover-info">
                <div class="center">
                    <?php if (iro_opt("cover_focus_style") != "off") : ?>
                        <?php if (iro_opt("cover_focus_style", "text") == "avatar"): ?>
                            <picture class="nuxtpic">
                                <?= iro_media_optimize_image_formats(
                                    iro_opt('cover_avatar'),
                                    ['width' => '7.5rem', 'height' => '7.5rem'],
                                    ['class' => 'cover-avatar']
                                ) ?>
                            </picture>
                        <?php else: ?>
                            <h1
                                class="cover-title"
                                style="
                                font-family: <?= iro_opt("cover_title", [])["font"] ?? "" ?>;
                                font-size: <?= iro_opt("cover_title", [])["size"] ?? 5 ?>rem;
                                color: <?= iro_opt("cover_title", [])["color"] ?? "#FFF" ?>;
                            ">
                                <?= iro_opt("cover_title", [])["text"] ?? "" ?>
                            </h1>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (iro_opt("cover_infor_bar_switch", true)): ?>
                        <div class="socials" style="
                        border-radius:<?= iro_opt("cover_infor_bar_radius", 1) ?>rem;
                        ">
                            <?php if (iro_opt("cover_typedjs")): ?>
                                <div
                                    class="typed-container">
                                    <?php if (iro_opt("cover_typedjs_mark")): ?>
                                        <i class="fa-icon-solid fa-quote-left"></i>
                                    <?php endif; ?>
                                    <span id="typed" class="typed">
                                        <?= iro_opt("cover_typedjs_placeholder") ?>
                                    </span>
                                    <?php if (iro_opt("cover_typedjs_mark")): ?>
                                        <i class="fa-icon-solid fa-quote-right"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (iro_opt("cover_signature", [])["text"] ?? ""): ?>
                                <div class="signature">
                                    <p
                                        style="
                                            font-family:<?= iro_opt("cover_signature", [])["font"] ?? "" ?>;
                                            font-size:<?= iro_opt("cover_signature", [])["size"] ?? 1 ?>rem;
                                        ">
                                        <?= iro_opt("cover_signature", [])["text"] ?? "" ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <?php require_once get_template_directory() . '/frontend/components/homepage/social_links.php'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </figure>
        <?php require_once get_template_directory() . '/frontend/components/homepage/wave.php'; ?>
    </div>
<?php endif; ?>
<?php //只引入一次，如果封面未开启但波浪开启且全屏定位则导入 
?>
<?php if (iro_opt('homepage_wave_position', 'cover') === 'window'): ?>
    <?php require_once get_template_directory() . '/frontend/components/homepage/wave.php'; ?>
<?php endif; ?>