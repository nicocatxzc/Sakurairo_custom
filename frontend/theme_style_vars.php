<?php
ob_start();
?>
<?php foreach (iro_opt("extra_fonts", []) as $font): ?> @font-face {
    font-family: '<?= $font["name"] ?>';
    src: url('<?= $font["link"] ?>');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
    }

<?php endforeach; ?>
<?php $iro_extra_font_faces = ob_get_clean(); ?>
<?php if (trim($iro_extra_font_faces) !== ''): ?>
    <style id="iro_extra_fonts" media="not all">
        <?= $iro_extra_font_faces ?>
    </style>
    <noscript>
        <style>
            <?= $iro_extra_font_faces ?>
        </style>
    </noscript>
    <script>
        // 外链字体默认关在 media="not all" 里，浏览器不会去下载。
        // 等首屏最大内容绘制稳定后再放开：字体既不和首屏抢带宽，也不会被算成首屏资源。
        // 用户开启省流时不放开，直接不用外链字体。
        (function() {
            function openExtraFonts() {
                var extraFonts = document.getElementById("iro_extra_fonts");
                if (extraFonts && extraFonts.media !== "all") {
                    extraFonts.media = "all";
                }
            }

            var connection = navigator.connection;
            if ((connection && connection.saveData) || matchMedia("(prefers-reduced-data: reduce)").matches) {
                return;
            }

            var settleTimer = 0;

            function openAfterLargestPaint() {
                clearTimeout(settleTimer);
                settleTimer = setTimeout(openExtraFonts, 600);
            }

            // 页面一直没有 LCP 候选，或候选一直在更新时兜底
            setTimeout(openExtraFonts, 10000);
            try {
                new PerformanceObserver(openAfterLargestPaint).observe({
                    type: "largest-contentful-paint",
                    buffered: true
                });
            } catch (e) {
                window.addEventListener("load", openAfterLargestPaint);
            }
        })();
    </script>
<?php endif; ?>
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
        --widget-shadow-shine-color: rgb(232, 232, 232);
        --widget-shadow-shining-color: rgb(232, 232, 232);
        --widget-shadow-shadow-color: rgba(0, 0, 0, 0.1);
        --widget-shadow-shine: 0 0.1rem 1.8rem -0.25rem var(--widget-shadow-shine-color);
        --widget-shadow-shining: 0 0.1rem 1.8rem 0.7rem var(--widget-shadow-shining-color);
        --widget-shadow-shadow: 0 0.3rem 1rem var(--widget-shadow-shadow-color);

        --border-color-sketch: 0, 0, 0;
        --border-color-shine: 255, 255, 255;
        --border-sketch: 0.1rem solid rgba(var(--border-color-sketch), 0.1);
        --border-shine: 0.1rem solid rgb(var(--border-color-shine));

        --page-background-color: rgba(255, 255, 255, var(--background-transparency));
        --code-background: <?= iro_opt('code_block_background_color', '#e1e4e8') ?>;
    }

    @media (min-width: 861px) {
        :root {
            --cover-background-img-pc: url(<?= iro_opt('cover_random_pic_select') === 'builtin' ? add_query_arg('size', 'pc', rest_url('sakura/v1/gallery')) : iro_opt('cover_random_pic_url_pc') ?>);
        }
    }

    :root {
        --cover-background-img-mb: url(<?= iro_opt('cover_random_pic_select') === 'builtin' ? add_query_arg('size', 'mb', rest_url('sakura/v1/gallery')) : iro_opt('cover_random_pic_url_mb') ?>);
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

    <?php endif; ?><?php if (iro_opt('cover_as_background', false)): ?>body {
        background-image: var(--cover-background-img-pc, --cover-background-img-mb);
    }

    @media (max-width: 860px) {
        body {
            background-image: var(--cover-background-img-mb, --cover-background-img-pc);
        }
    }

    <?php endif; ?> :root.dark {
        --active-color: <?= iro_opt('active_color_dark', '#FCCD00') ?>;
        --active-color-reverse: <?= iro_opt('active_color', '#00b0f0') ?>;
        --widget-transparency: <?= iro_opt('widget_transparency_dark', 0.8) ?>;
        --background-transparency: <?= iro_opt('background_transparency_dark', 0.7) ?>;
        --word-color-first: <?= iro_opt('word_color_first_dark', '#CCCCCC') ?>;
        --word-color-second: <?= iro_opt('word_color_second_dark', '#999999') ?>;
        --word-color-third: #7d7d7d;
        --word-color-first-reverse: <?= iro_opt('word_color_first', '#505050') ?>;

        --widget-background: 26, 26, 26;
        --widget-background-reverse: 255, 255, 255;
        --widget-background-color: rgba(var(--widget-background), var(--widget-transparency));
        --widget-background-color-reverse: rgba(var(--widget-background-reverse), var(--widget-transparency));
        --widget-shadow-shine-color: rgba(26, 26, 26, 0.8);
        --widget-shadow-shining-color: var(--active-color);
        --widget-shadow-shadow-color: rgba(0, 0, 0, 0.2);
        --widget-shadow-shine: 0 0.1rem 1.2rem -0.25rem var(--widget-shadow-shine-color);
        --widget-shadow-shining: 0 0.1rem 2rem -0.25rem var(--widget-shadow-shining-color);
        --widget-shadow-shadow: 0 0.3rem 1rem var(--widget-shadow-shadow-color);

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
<style id="iro_theme_style_dymanic_vars">
    <?php if (iro_opt("post_cover_as_background", false) && is_single()): ?>body {
        background-image: url(<?= iro_media_optimize_image_url(get_the_post_thumbnail_url(get_post(), 'full')) ?>);
    }

    <?php endif; ?>
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
