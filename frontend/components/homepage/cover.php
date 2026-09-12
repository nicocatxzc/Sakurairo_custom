<div class="homepage-cover <?= is_home() ? '' : 'hide' ?>">
    <figure
        class="cover
        <?= iro_opt('cover_as_background', false) ? 'transparent' : '' ?>
        "
        style="
        --background-img-pc: url(<?= iro_opt('cover_random_pic_url_pc') ?>); 
        --background-img-mb: url(<?= iro_opt('cover_random_pic_url_mb') ?>);">
        <div class="cover-info">
            <div class="center">
                <?php if (iro_opt("cover_focus_style") != "off") : ?>
                    <?php if (iro_opt("cover_focus_style", "text") == "avatar"): ?>
                        <img
                            src="<?= iro_opt('cover_avatar') ?>"
                            class="nuxtpic cover-avatar" />
                    <?php else: ?>
                        <h1
                            class="cover-title"
                            style="
                                font-family: <?= iro_opt("cover_title")["font"] ?>;
                                font-size: <?= iro_opt("cover_title")["size"] ?? 5 ?>rem;
                                color: <?= iro_opt("cover_title")["color"] ?? "#FFF" ?>rem;
                            ">
                            <?= iro_opt("cover_title")["text"] ?>
                        </h1>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (iro_opt("cover_infor_bar_switch", true)): ?>
                    <div class="socials" style="
                        board-radius:<?= iro_opt("cover_infor_bar_radius", 1) ?>rem;
                        ">
                        <?php if (iro_opt("cover_typedjs")): ?>
                            <div
                                class="typed-container">
                                <?php if (iro_opt("cover_typedjs_mark")): ?>
                                    <i class="fa-solid fa-quote-left"></i>
                                <?php endif; ?>
                                <span id="typed" class="typed">
                                    <?= iro_opt("cover_typedjs_placeholder") ?>
                                </span>
                                <?php if (iro_opt("cover_typedjs_mark")): ?>
                                    <i class="fa-solid fa-quote-right"></i>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (iro_opt("cover_signature")["text"]): ?>
                            <div class="signature">
                                <p
                                    style="
                                            font-family:<?= iro_opt("cover_signature")["font"] ?? ""?>;
                                            font-size:<?= iro_opt("cover_signature")["size"] ?? 1 ?>rem;
                                        ">
                                    <?= iro_opt("cover_signature")["text"] ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        <HomepageSocialLinks />
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </figure>
</div>