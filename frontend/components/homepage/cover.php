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
                            <?= iro_render_decorate('focus-title', [
                                'text' => iro__((string) (iro_opt("cover_title", [])["text"] ?? "")),
                                'effect' => iro_opt("cover_focus_style", "text") == "mashiro_text",
                                'font' => iro_opt("cover_title", [])["font"] ?? "",
                                'size' => iro_opt("cover_title", [])["size"] ?? 5,
                                'color' => iro_opt("cover_title", [])["color"] ?? "#FFF",
                            ]) ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (iro_opt("cover_infor_bar_switch", true)): ?>
                        <?php ob_start(); ?>
                            <?php if (iro_opt("cover_typedjs")): ?>
                                <?= iro_render_decorate('typed', [
                                    'mark' => (bool) iro_opt("cover_typedjs_mark"),
                                    'placeholder' => iro__((string) iro_opt("cover_typedjs_placeholder")),
                                ]) ?>
                            <?php endif; ?>
                            <?php if (iro_opt("cover_signature", [])["text"] ?? ""): ?>
                                <div class="signature">
                                    <p
                                        style="
                                            font-family:<?= iro_opt("cover_signature", [])["font"] ?? "" ?>;
                                            font-size:<?= iro_opt("cover_signature", [])["size"] ?? 1 ?>rem;
                                        ">
                                        <?= iro__((string) (iro_opt("cover_signature", [])["text"] ?? "")) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <?= iro_render_decorate('social-links') ?>
                        <?php
                        // 常规渲染即把预设参数当区块参数填进去
                        echo iro_render_decorate('widgets-container', [
                            'radius' => iro_opt("cover_infor_bar_radius", 1),
                            'padding' => 1,
                            'preset' => true,
                        ], ob_get_clean());
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </figure>
        <?php require_once get_theme_file_path('/frontend/components/homepage/wave.php'); ?>
    </div>
<?php endif; ?>
<?php //只引入一次，如果封面未开启但波浪开启且全屏定位则导入 
?>
<?php if (iro_opt('homepage_wave_position', 'cover') === 'window'): ?>
    <?php require_once get_theme_file_path('/frontend/components/homepage/wave.php'); ?>
<?php endif; ?>