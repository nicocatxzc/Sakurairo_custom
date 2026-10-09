<ul class="showcard-list">
    <?php foreach (iro_opt("show_area_content", []) as $showcard): ?>
        <?php
        $target = $showcard["link"] ?? "";
        $isExternal = preg_match('/http/', $target);
        ?>
        <li class="showcard">
            <div class="title">
                <h3><?= iro__((string) ($showcard["title"] ?? "")) ?></h3>
            </div>
            <a
                href="<?= $target ?>"
                target="<?= $isExternal ? '_blank' : '_self' ?>"
                rel="<?= $isExternal ? 'external nofollow noreferrer' : '' ?>"
                class="card-link">
                <div class="card-image">
                    <picture class="nuxtpic">
                        <?= iro_media_optimize_image_formats(
                            $showcard["img"],
                            [
                                'width' => 276,
                                'height' => '14rem',
                                'fit' => 'cover',
                                'sizes' => '(max-width: 800px) 92vw, (max-width: 1120px) 47vw, 276px',
                            ],
                            [
                                'alt' => 'showcard-image',
                                'loading' => 'lazy',
                            ]
                        ) ?>
                    </picture>
                </div>
                <div class="card-info">
                    <p class="card-desc"><?= iro__((string) ($showcard["description"] ?? "")) ?></p>
                </div>
            </a>
        </li>
    <?php endforeach; ?>
</ul>