<style>
    :root {
        --global-font-size: <?= iro_opt('global_font_size', 16) ?>;
        --global-font-weight: <?= iro_opt('global_font_weight', 300) ?>;

        font-family: var(--global-font-family, <?= iro_opt('global_default_font', '') ?>);

        --active-color: <?= iro_opt('active_color', '#00b0f0') ?>;

        --active-color-reverse: <?= iro_opt('active_color_dark', '#FCCD00') ?>;
        --widget-transparency: <?= iro_opt('widget_transparency', 0.8) ?>;
        --background-transparency: <?= iro_opt('background_transparency', 0.8) ?>;
        --word-color-first: <?= iro_opt('word_color_first', '#505050') ?>;
        --word-color-second: <?= iro_opt('word_color_second', '#00000080') ?>;
        --word-color-third: #00000080;
        --word-color-first-reverse: <?= iro_opt('word_color_first_dark', '#CCCCCC') ?>;

        --widget-background: 255, 255, 255;
        --widget-background-reverse: 26, 26, 26;
        --widget-background-color: rgba(var(--widget-background), var(--widget-transparency));
        --widget-background-color-reverse: rgba(var(--widget-background-reverse), var(--widget-transparency));
        --widget-shadow-shine: 0 0.1rem 1.8rem -0.25rem rgb(232, 232, 232);
        --widget-shadow-shining: 0 0.1rem 1.8rem 0.7rem rgb(232, 232, 232);
        --widget-shadow-shadow: 0 0.3rem 1rem rgba(0, 0, 0, 0.1);

        --border-color-sketch: 0, 0, 0;
        --border-color-shine: 255, 255, 255;
        --border-sketch: 0.1rem solid rgba(var(--border-color-sketch), 0.1);
        --border-shine: 0.1rem solid rgb(var(--border-color-shine));

        --page-background-color: rgba(255, 255, 255, var(--background-transparency));
        --code-background: <?= iro_opt('code_block_background_color', '#e1e4e8') ?>;
    }

    <?php if (!empty(iro_opt('frontend_default_background'))): ?>body {
        background-image: url(<?= iro_opt('frontend_default_background') ?>);
        <?php if (iro_opt("frontend_background_fill_mode") == "texture"): ?>background-size: auto;
        background-position: center;
        background-repeat: repeat;
        <?php else: ?>background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        <?php endif; ?>
    }

    <?php endif; ?> :root.dark {
        --active-color: <?= iro_opt('active_color_dark', '#FCCD00') ?>;
        --active-color-reverse: <?= iro_opt('active_color', '#00b0f0') ?>;
        --widget-transparency: <?= iro_opt('widget_transparency_dark', 0.8) ?>;
        --background-transparency: <?= iro_opt('background_transparency_dark', 0.7) ?>;
        --word-color-first: <?= iro_opt('word_color_first_dark', '#CCCCCC') ?>;
        --word-color-second: <?= iro_opt('word_color_second_dark', '#7d7d7d') ?>;
        --word-color-third: #7d7d7d;
        --word-color-first-reverse: <?= iro_opt('word_color_first', '#505050') ?>;

        --widget-background: 26, 26, 26;
        --widget-background-reverse: 255, 255, 255;
        --widget-background-color: rgba(var(--widget-background), var(--widget-transparency));
        --widget-background-color-reverse: rgba(var(--widget-background-reverse), var(--widget-transparency));
        --widget-shadow-shine: 0 0.1rem 1.2rem -0.25rem rgba(26, 26, 26, 0.8);
        --widget-shadow-shining: 0 0.1rem 2rem -0.25rem var(--active-color);
        --widget-shadow-shadow: 0 0.3rem 1rem rgba(0, 0, 0, 0.2);

        --border-color-sketch: rgba(255, 255, 255, 0.1);
        --border-color-shine: #7d7d7d30;
        --border-sketch: 0.1rem solid var(--border-color-sketch);
        --border-shine: 0.1rem solid var(--border-color-shine);

        --page-background-color: rgba(51, 51, 51, var(--background-transparency));
        --code-background: <?= iro_opt('code_block_background_color_dark', '#24292e') ?>;
        --image-bright: <?= iro_opt('image_bright_dark', 0.7) ?>;
    }

    :root {
        --border-active: 0.1rem solid var(--active-color);
        --background-blur: <?= iro_opt('background_blur', 0.7) ?>;
    }

    /* 纪念模式 */
    <?php if (iro_is_commemorate_date()) { ?>html {
        filter: grayscale(100%) !important;
    }

    <?php } ?>
</style>
<?php
function iro_is_commemorate_date()
{
    $dateList = iro_opt("theme_commemorate_mode_date");

    // 把 "1-5" 和 "01-05" 都转成 "1-5"
    function normalizeDate(string $str)
    {
        $str = trim($str);
        if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $str, $m)) {
            return (int)$m[1] . '-' . (int)$m[2]; // 去掉前导零
        }
        return null;
    }

    $dates = [];
    foreach (explode("\n", $dateList) as $line) {
        $n = normalizeDate($line);
        if ($n !== null) {
            $dates[] = $n;
        }
    }

    $today = date('n-j'); // 例如 "1-5"

    return in_array($today, $dates);
}
