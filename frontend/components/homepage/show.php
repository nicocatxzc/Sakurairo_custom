<ul class="showcard-list">
    <?php foreach (iro_opt("show_area_content", []) as $showcard): ?>
        <?php
        $target = $showcard["link"] ?? "";
        $isExternal = preg_match('/http/', $target);
        ?>
        <li class="showcard">
            <div class="title">
                <h3><?= $showcard["title"] ?></h3>
            </div>
            <a
                href="<?= $target ?>"
                target="<?= $isExternal ? '_blank' : '_self' ?>"
                rel="<?= $isExternal ? 'external nofollow noreferrer' : '' ?>"
                class="card-link">
                <div class="card-image">
                    <img
                        class="nuxtpic"
                        alt="showcard-image"
                        src="<?= $showcard["img"] ?>"
                        loading="lazy" />
                </div>
                <div class="card-info">
                    <p class="card-desc"><?= $showcard["description"] ?></p>
                </div>
            </a>
        </li>
    <?php endforeach; ?>
</ul>