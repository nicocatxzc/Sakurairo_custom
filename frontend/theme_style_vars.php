<?php //样式定义
?>
<style>
    :root {
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

    :root.dark {
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

<style>
    :root {
        --global-font-size: <?= iro_opt('global_font_size', 16) ?>;
        --global-font-weight: <?= iro_opt('global_font_weight', 300) ?>;
        font-family: var(--global-font-family, <?= iro_opt('global_default_font', '') ?>);
    }
</style>

<?php if (iro_opt("extra_fonts", []) != []): ?>
    <style id="iro_extra_fonts" media="not all">
        <?php foreach (iro_opt("extra_fonts", []) as $font): ?>@font-face {
            font-family: '<?= $font["name"] ?>';
            src: url('<?= $font["link"] ?>');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        <?php endforeach; ?>
    </style>
    <script>
        <?php
        // 外链字体默认 media="not all" 不下载
        // 等首屏最大内容绘制稳定后再下载
        ?>
            (function() {
                function openExtraFonts() {
                    let extraFonts = document.getElementById("iro_extra_fonts");
                    if (extraFonts && extraFonts.media !== "all") {
                        extraFonts.media = "all";
                    }
                }

                <?php // 桌面端直接解锁 
                ?>
                if (window.innerWidth > 860) {
                    openExtraFonts();
                    return;
                }

                let connection = navigator.connection;
                if ((connection && connection.saveData) || matchMedia("(prefers-reduced-data: reduce)").matches) {
                    return;
                }

                let settleTimer = 0;

                function openAfterLargestPaint() {
                    clearTimeout(settleTimer);
                    settleTimer = setTimeout(openExtraFonts, 600);
                }

                <?php // 页面一直没有 LCP 候选，或候选一直在更新时兜底 
                ?>
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

<?php // 背景 
?>
<style>
    @media (min-width: 861px) {
        :root {
            --cover-background-img-pc: url(<?= iro_cover_resolve_url('pc') ?>);
            <?php if (!empty(iro_opt('frontend_default_background'))): ?>--page-background-img: url(<?= iro_opt('frontend_default_background') ?>);
            <?php endif; // 桌面端立即渲染，移动端优化下面按需解锁 
            ?>
        }
    }

    <?php if (!empty(iro_opt('frontend_default_background'))): ?>body {
        background-image: var(--page-background-img);
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

    <?php endif; ?>
</style>
<style id="iro_deferred_bg" media="not all">
    @media (max-width: 860px) {
        :root {
            --cover-background-img-mb: url(<?= iro_cover_resolve_url('mb') ?>);
            <?php if (!empty(iro_opt('frontend_default_background'))): ?>--page-background-img: url(<?= iro_opt('frontend_default_background') ?>);
            <?php endif; ?>
        }
    }
</style>
<script>
    (function() {
        function openDeferredBackground() {
            let deferred = document.getElementById("iro_deferred_bg");
            if (deferred && deferred.media !== "all") {
                deferred.media = "all";
            }
        }

        <?php // 桌面端直接解锁
        ?>
        if (window.innerWidth > 860) {
            openDeferredBackground();
            return;
        }

        <?php // 首屏大图等首个绘制完成后再挂：在此之前不参与下载，避免进入首屏窗口 
        ?>
        try {
            let observer = new PerformanceObserver(function(list) {
                for (let entry of list.getEntries()) {
                    if (entry.name === "first-contentful-paint") {
                        observer.disconnect();
                        requestAnimationFrame(openDeferredBackground);
                        break;
                    }
                }
            });
            observer.observe({
                type: "paint",
                buffered: true
            });
        } catch (e) {
            window.addEventListener("load", openDeferredBackground);
        }
        setTimeout(openDeferredBackground, 10000);
    })();
</script>

<?php // 切换页面时需要改变的样式 
?>
<style id="iro_theme_style_dymanic_vars">
    <?php if (iro_opt("post_cover_as_background", false) && is_single()): ?>body {
        background-image: url(<?= iro_media_optimize_image_url(get_the_post_thumbnail_url(get_post(), 'full')) ?>);
    }

    <?php endif; ?>
</style>

<?php
$dev_mode = iro_opt("dev_mode", false) &&
    (
        !iro_opt("dev_mode_admin_only", true) ||
        current_user_can('manage_options')
    );
// 文章排版样式独立导出为 post-sakura.css，仅在设置为 Sakura 时按需引用。
$use_sakura_post_style = iro_opt("page_style", "sakura") === "sakura";
?>

<?php if ($dev_mode): ?>
    <?php if ($use_sakura_post_style): ?>
        <script type="module" src="<?= rtrim(dirname(iro_opt("dev_mode_main_js")), '/\\') . '/components/post/post-sakura.scss' ?>"></script>
    <?php endif; ?>
<?php else: ?>
    <?php if ($use_sakura_post_style): ?>
        <link rel="stylesheet" crossorigin="" href="<?= get_template_directory_uri() . '/frontend/dist/post-sakura.css?ver=' . INT_VERSION ?>">
    <?php endif; ?>
<?php endif; ?>

<?php
/**
 * 解析封面图地址
 *
 * @param string $size 'pc' 或 'mb'
 * @return string 图片地址，取不到时回退为原始配置值
 */
function iro_cover_resolve_url(string $size): string
{
    // 内建，直接解析结果
    if (iro_opt('cover_random_pic_select') === 'builtin') {
        $request = new WP_REST_Request('GET', '/sakura/v1/gallery');
        $request->set_query_params(['size' => $size]);

        $url = rest_do_request($request)->get_headers()['Location'] ?? null;

        return is_string($url) ? $url : '';
    }

    $url = iro_opt($size === 'pc' ? 'cover_random_pic_url_pc' : 'cover_random_pic_url_mb');

    // 根据情况决定是否预取
    if (
        !$url
        || !iro_opt('iro_slow_net_optimize', true)
        || iro_opt('iro_cover_api_strategy', 'redirect') !== 'redirect'
    ) {
        return (string) $url;
    }

    return function_exists('iro_random_img_fixed_url') ? iro_random_img_fixed_url((string) $url) : (string) $url;
}

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
