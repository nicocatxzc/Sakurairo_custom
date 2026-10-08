<?php //样式定义
?>
<style>
    :root {
        --widget-transparency: <?= iro_opt('widget_transparency', 0.8) ?>;
        --background-transparency: <?= iro_opt('background_transparency', 0.8) ?>;
        --word-color-first: <?= iro_opt('word_color_first', '#505050') ?>;
        --word-color-second: <?= iro_opt('word_color_second', '#00000080') ?>;
        --word-color-third: #0000004d;
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

        <?php // 工具块（frontend/components/block/widgets）的卡片外观：
        // 背景与描边由「侧边栏组件背景」一起开关，圆角由「侧边栏项目圆角」。
        // 边框取 --border-sketch 而不是裸的 --border-color-sketch：深色模式把后者
        // 整体换成了 rgba()，直接拼 rgba(var(...)) 会失效。
        // 变量挂在组件上而不是侧栏上，工具块放进正文里同样成立
        ?>--iro-widget-tools-background: <?= iro_opt("layout_side_bar_items_background", false) ? "var(--widget-background-color)" : "transparent" ?>;
        --iro-widget-tools-border: <?= iro_opt("layout_side_bar_items_background", false) ? "var(--border-sketch)" : "none" ?>;
        --iro-widget-tools-radius: <?= iro_opt("layout_side_bar_item_radius", 0.6) ?>rem;
        --iro-column-radius: <?= iro_opt("layout_side_bar_radius", 0.6) ?>rem;

        --page-background-color: rgba(255, 255, 255, var(--background-transparency));
        --code-background: <?= iro_opt('code_block_background_color', '#e1e4e8') ?>;
    }

    :root.dark {
        --widget-transparency: <?= iro_opt('widget_transparency_dark', 0.8) ?>;
        --background-transparency: <?= iro_opt('background_transparency_dark', 0.7) ?>;
        --word-color-first: <?= iro_opt('word_color_first_dark', '#CCCCCC') ?>;
        --word-color-second: <?= iro_opt('word_color_second_dark', '#999999') ?>;
        --word-color-third: #7d7d7d;
        --word-color-first-reverse: <?= iro_opt('word_color_first', '#505050') ?>;

        --widget-background: 26, 26, 26;
        --widget-background-reverse: 255, 255, 255;
        <?php // 深色组件背景继承计算区的 --widget-dark-bg，再叠上透明度 
        ?>--widget-background-color: color-mix(in srgb, var(--widget-dark-bg) calc(var(--widget-transparency) * 100%), transparent);
        --widget-background-color-reverse: rgba(var(--widget-background-reverse), var(--widget-transparency));
        --widget-shadow-shine-color: rgba(26, 26, 26, 0.8);
        --widget-shadow-shining-color: var(--widget-dark-shining);
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

<?php //颜色计算区：全部由 --theme-base-color 派生，取色只需覆写这一个变量 
?>
<style>
    <?php
    // 计算区里需要「当前模式 + 反转」两个名字的 token
    $iro_reversible_tokens = [
        'active-color',
        'button-bg',
        'button-text',
        'button-border',
        'button-hover-bg',
        'button-hover-text',
        'button-hover-border',
        'button-active-bg',
        'button-active-text',
        'button-active-border',
    ];
    ?> :root {
        <?php // 取色钩子 --theme-base-color 刻意不定义：colorthief 成功时由 JS 写到 <html> 内联样式，
        // 内联优先于本规则，两个模式的基色会同时改用它；清除后自动回落各自的设置项。 
        ?>--theme-base-color-light: var(--theme-base-color, <?= iro_opt('active_color', '#00b0f0') ?>);
        --theme-base-color-dark: var(--theme-base-color, <?= iro_opt('active_color_dark', '#FCCD00') ?>);

        <?php // 纯变量：带 color 的中间量，不直接给组件用。兜底值为不钳制时的旧表现 
        ?>--lt-active-color: var(--theme-base-color-light);
        --lt-button-bg-color: var(--theme-base-color-light);
        --lt-button-text-color: #000;
        --lt-button-border-color: var(--lt-button-bg-color);
        --dk-active-color: var(--theme-base-color-dark);
        --dk-button-bg-color: var(--theme-base-color-dark);
        --dk-button-text-color: #000;
        --dk-button-border-color: var(--dk-button-bg-color);

        <?php // 可直接用的属性：不带 color 
        ?>--lt-button-bg: var(--lt-button-bg-color);
        --lt-button-text: var(--lt-button-text-color);
        --lt-button-border: var(--lt-button-border-color);
        --lt-button-hover-bg: color-mix(in srgb, var(--lt-button-bg-color) 86%, #fff);
        --lt-button-hover-text: var(--lt-button-text-color);
        --lt-button-hover-border: var(--lt-button-hover-bg);
        --lt-button-active-bg: color-mix(in srgb, var(--lt-button-bg-color) 74%, #fff);
        --lt-button-active-text: var(--lt-button-text-color);
        --lt-button-active-border: var(--lt-button-active-bg);

        --dk-button-bg: var(--dk-button-bg-color);
        --dk-button-text: var(--dk-button-text-color);
        --dk-button-border: var(--dk-button-border-color);
        --dk-button-hover-bg: color-mix(in srgb, var(--dk-button-bg-color) 86%, #fff);
        --dk-button-hover-text: var(--dk-button-text-color);
        --dk-button-hover-border: var(--dk-button-hover-bg);
        --dk-button-active-bg: color-mix(in srgb, var(--dk-button-bg-color) 74%, #fff);
        --dk-button-active-text: var(--dk-button-text-color);
        --dk-button-active-border: var(--dk-button-active-bg);

        <?php // 深色组件背景：黑与主题色混出；发光色同理 
        ?>--widget-dark-bg: color-mix(in srgb, var(--theme-base-color-dark) 3%, #1a1a1a);
        --widget-dark-shining: var(--theme-base-color-dark);

        <?php foreach ($iro_reversible_tokens as $iro_token): ?>--<?= $iro_token ?>: var(--lt-<?= $iro_token ?>);
        --<?= $iro_token ?>-reverse: var(--dk-<?= $iro_token ?>);
        <?php endforeach; ?>
    }

    @supports (color: oklch(from red l c h)) {

        <?php
        // 两侧联立钳制：填充压到亮侧（0.70<=L<=0.87）、文字压到暗侧（L<=0.31），
        // 同一色相下留出的亮度差保证 >=4.5:1（全色域 13782 个采样色实测最差 4.64:1）。
        //  文字保留站长色相、彩度上限 0.12，所以是「同色相的深色」而不是纯黑。
        //  两个预设（蓝 L=0.713 / 金 L=0.864）都在 0.70..0.87 内，填充原值保留。
        // 悬浮/按下用固定 L 步长而不是按比例混白：混白的 ΔL 正比于 (1-L)，
        //  填充越亮越看不出变化（近白填充的 ΔL 会掉到 0.003，等于没变化），
        //  加法步长则恒定 0.055。上界 0.87 正是「按下态最亮 0.98、不会顶到纯白」的上限。
        //  三态都往亮侧走，只会把深色文字衬得更清楚，实测对比度只增不减。
        ?> :root {
            --lt-button-bg-color: oklch(from var(--theme-base-color-light) clamp(0.70, l, 0.87) min(c, 0.2) h / 1);
            --lt-button-text-color: oklch(from var(--theme-base-color-light) min(l, 0.31) min(c, 0.12) h / 1);
            --lt-button-hover-bg: oklch(from var(--lt-button-bg-color) calc(l + 0.055) c h / 1);
            --lt-button-active-bg: oklch(from var(--lt-button-bg-color) calc(l + 0.11) c h / 1);

            --dk-button-bg-color: oklch(from var(--theme-base-color-dark) clamp(0.70, l, 0.87) min(c, 0.2) h / 1);
            --dk-button-text-color: oklch(from var(--theme-base-color-dark) min(l, 0.31) min(c, 0.12) h / 1);
            --dk-button-hover-bg: oklch(from var(--dk-button-bg-color) calc(l + 0.055) c h / 1);
            --dk-button-active-bg: oklch(from var(--dk-button-bg-color) calc(l + 0.11) c h / 1);
        }
    }

    :root.dark {
        <?php foreach ($iro_reversible_tokens as $iro_token): ?>--<?= $iro_token ?>: var(--dk-<?= $iro_token ?>);
        --<?= $iro_token ?>-reverse: var(--lt-<?= $iro_token ?>);
        <?php endforeach; ?>
    }
</style>

<style>
    :root {
        --global-font-size: <?= iro_opt('global_font_size', 16) ?>;
        --global-font-weight: <?= iro_opt('global_font_weight', 300) ?>;
        font-family: var(--global-font-family, <?= iro_opt('global_default_font', '') ?>);
    }
</style>

<?php //字体延迟解锁 
?>
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
<?php endif; ?>

<?php // 背景按需解锁
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

<?php // 切换页面时需要改变的样式 
?>
<style id="iro_theme_style_dymanic_vars">
    <?php if (iro_opt("post_cover_as_background", false) && is_single() && get_the_post_thumbnail_url(get_post(), 'full')): ?>body {
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

$use_sakura_post_style = iro_opt("page_style", "sakura") === "sakura" && is_singular();
?>

<div id="iro_post_style">
    <?php if ($use_sakura_post_style): ?>
        <?php if ($dev_mode): ?>
            <script type="module" src="<?= rtrim(dirname(iro_opt("dev_mode_main_js")), '/\\') . '/components/post/post-sakura.scss' ?>"></script>
        <?php else: ?>
            <link rel="stylesheet" href="<?= get_template_directory_uri() . '/frontend/dist/post-sakura.css?ver=' . INT_VERSION ?>">
        <?php endif; ?>
    <?php endif; ?>
</div>

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

// 性能门控
require_once __DIR__ . '/theme_performance.php';
