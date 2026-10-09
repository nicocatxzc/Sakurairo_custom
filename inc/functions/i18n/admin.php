<?php
// 内容国际化后台管理
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 语言标记的总览列，供列表页与面板共用
 */
function iro_i18n_language_status_label(WP_Post $post): string
{
    if (!iro_i18n_post_has_language($post->ID)) {
        return __('未标记', 'sakurairo');
    }

    if (iro_i18n_is_skeleton($post->ID)) {
        return __('未翻译', 'sakurairo');
    }

    return iro_i18n_language_name(iro_i18n_post_language($post->ID));
}

/**
 * 版本状态的单元格内容：待同步优先于其它状态显示
 *
 * @param array<string,string> $row iro_i18n_language_statuses() 的一行
 */
function iro_i18n_status_cell(array $row): string
{
    if ($row['url'] === '') {
        return '<span class="description">' . esc_html($row['label']) . '</span>';
    }

    $class = $row['state'] === 'outdated' ? ' style="color:#b32d2e;font-weight:600;"' : '';

    return '<a href="' . esc_url($row['url']) . '"' . $class . '>' . esc_html($row['label']) . '</a>';
}

/**
 * 同组各语言版本的标记，只取参与语言，缺一不可地显示出来
 *
 * @param array<string,WP_Post> $map
 * @return array<int,array{code:string,name:string,state:string,label:string,url:string}>
 */
function iro_i18n_language_statuses(array $map): array
{
    $default = iro_i18n_default_language();
    $rows    = [];

    foreach (iro_i18n_languages() as $code) {
        $post  = $map[$code] ?? null;
        $state = 'missing';
        $label = __('无版本', 'sakurairo');
        $url   = '';

        if ($post instanceof WP_Post) {
            $url      = (string) get_edit_post_link($post->ID, 'raw');
            $skeleton = iro_i18n_is_skeleton($post->ID);

            if (!$skeleton && $post->post_status === 'publish' && iro_i18n_translation_outdated($post->ID)) {
                $state = 'outdated';
                $label = __('待同步', 'sakurairo');
            } elseif ($post->post_status === 'publish') {
                $state = $skeleton ? 'skeleton' : 'published';
                $label = $skeleton ? __('未翻译', 'sakurairo') : __('已发布', 'sakurairo');
            } else {
                $state = 'draft';
                $label = __('草稿 / 待审', 'sakurairo');
            }
        }

        $rows[] = [
            'code'  => $code,
            'name'  => iro_i18n_language_name($code),
            'state' => $code === $default ? 'default' : $state,
            'label' => $label,
            'url'   => $url,
        ];
    }

    return $rows;
}

/**
 * 列表页的语言筛选
 *
 * 选项值用语言代号（术语别名），WP 会把 `iro_lang` 参数交给自身的 taxonomy
 * 查询处理，因此这里不需要自建查询逻辑。
 */
function iro_i18n_render_language_filter(string $post_type): void
{
    if (!in_array($post_type, iro_i18n_supported_post_types(), true)) {
        return;
    }

    $current = isset($_GET[IRO_I18N_LANGUAGE_TAXONOMY])
        ? sanitize_key((string) wp_unslash($_GET[IRO_I18N_LANGUAGE_TAXONOMY]))
        : '';
?>
    <label class="screen-reader-text" for="iro_i18n_language_filter"><?php esc_html_e('按语言筛选', 'sakurairo'); ?></label>
    <select name="<?= esc_attr(IRO_I18N_LANGUAGE_TAXONOMY) ?>" id="iro_i18n_language_filter">
        <option value=""><?php esc_html_e('全部语言', 'sakurairo'); ?></option>
        <?php foreach (iro_i18n_languages() as $code) : ?>
            <option value="<?= esc_attr($code) ?>" <?php selected($current, $code); ?>>
                <?= esc_html(iro_i18n_language_name($code)) ?>
            </option>
        <?php endforeach; ?>
    </select>
<?php
}

/**
 * 列表页按语言筛选
 *
 * 不筛选时保持全部语言，管理视图要看得到所有版本；筛选时才收窄到所选语言。
 * 收窄用的条件与前台共用 `iro_i18n_language_tax_clause()`，两边对
 * 「未标记 = 默认语言」的口径必须一致，否则列表筛选与前台归档会各说各话。
 */
function iro_i18n_filter_language_query(WP_Query $query): void
{
    global $wp_the_query;

    if (!is_admin() || $query !== $wp_the_query) {
        return;
    }

    if (!in_array($query->get('post_type'), iro_i18n_supported_post_types(), true)) {
        return;
    }

    $code = isset($_GET[IRO_I18N_LANGUAGE_TAXONOMY])
        ? sanitize_key((string) wp_unslash($_GET[IRO_I18N_LANGUAGE_TAXONOMY]))
        : '';

    if ($code === '' || !in_array($code, iro_i18n_languages(), true)) {
        return;
    }

    $term = get_term_by('slug', $code, IRO_I18N_LANGUAGE_TAXONOMY);

    if (!$term instanceof WP_Term) {
        return;
    }

    // 收窄条件必须按「筛选器选的语言」算，不能按访客语言：后台的语言来源是
    // 用户语言设置，与站长在下拉里选的那一项没有关系。
    $clause = iro_i18n_language_tax_clause($code);

    if ($clause === []) {
        return;
    }

    $tax_query = (array) $query->get('tax_query');

    // 这里是整条 tax_query 的最终态，只需再并入语言条件
    $tax_query[] = $clause;
    $tax_query[] = [
        'taxonomy' => IRO_I18N_LANGUAGE_TAXONOMY,
        'field'    => 'term_id',
        'terms'    => [(int) $term->term_id],
    ];

    $query->set('tax_query', $tax_query);
}

function iro_i18n_render_language_column(string $column, int $post_id): void
{
    if ($column !== IRO_I18N_LANGUAGE_TAXONOMY) {
        return;
    }

    $post = get_post($post_id);

    if (!$post instanceof WP_Post) {
        return;
    }

    $code = iro_i18n_post_language($post_id);

    echo '<strong>' . esc_html(iro_i18n_language_name($code)) . '</strong>';
    echo '<br><span class="description">' . esc_html(iro_i18n_language_status_label($post)) . '</span>';

    // 代号与 URL 前缀是两个值，列表里一并给出，省得去设置页反查
    if ($code !== iro_i18n_default_language()) {
        echo '<br><code style="font-size:11px;">' . esc_html(iro_i18n_prefix($code)) . '/</code>';
    }

    // 这里刻意不查同组版本：列表每行都查会拖慢后台，关联明细留给编辑页的面板
    if (iro_i18n_get_path($post_id) === '' && !iro_i18n_is_skeleton($post_id)) {
        echo '<br><span class="description">' . esc_html__('保存后建立关联', 'sakurairo') . '</span>';
    }
}

function iro_i18n_register_meta_box(): void
{
    foreach (iro_i18n_supported_post_types() as $post_type) {
        $type = get_post_type_object($post_type);

        if (!$type instanceof WP_Post_Type) {
            continue;
        }

        if (!current_user_can($type->cap->edit_posts)) {
            continue;
        }

        add_meta_box(
            'iro_i18n_translations',
            __('多语言', 'sakurairo'),
            'iro_i18n_render_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * 关联字段对编辑者只读：同组版本靠它串起来，手改会把分组改断
 */
function iro_i18n_render_meta_box(WP_Post $post): void
{
    $path = iro_i18n_get_path($post->ID);

    if ($path === '') {
        // 保存一次即会补上，这里先按当前别名展示预期值
        $path = iro_i18n_compute_path($post->ID);
        echo '<p class="description">' . esc_html__('保存后自动建立语言关联。', 'sakurairo') . '</p>';
    }

    echo '<p><strong>' . esc_html__('关联标识', 'sakurairo') . '</strong><br><code>' . esc_html($path) . '</code></p>';

    echo '<ul style="margin:0;">';

    foreach (iro_i18n_language_statuses(iro_i18n_group_map($post->ID)) as $row) {
        echo '<li style="margin:0 0 4px;">';
        echo '<strong>' . esc_html($row['name']) . '</strong>：';
        echo wp_kses_post(iro_i18n_status_cell($row));
        echo '</li>';
    }

    echo '</ul>';

    // 副本正文是原文的逐区块拷贝，因此官方 AI 插件的区块翻译可以直接在编辑器里用；
    // 这里点明该在它的语言选择器里挑哪一项，避免译者选错语言
    $code = iro_i18n_post_language($post->ID);
?>
    <p class="description" style="margin-top:8px;">
        <?= esc_html(sprintf(
            /* translators: 1: 语言代号 2: 该语言的 URL 前缀 */
            __('本页语言标识为 %1$s，对外地址前缀 /%2$s/；默认语言没有前缀。', 'sakurairo'),
            $code,
            iro_i18n_prefix($code)
        )) ?>
    </p>
    <p class="description">
        <?= esc_html(sprintf(
            /* translators: %s: 语言名与代号 */
            __('用编辑器里的 AI 区块翻译时，目标语言选「%s」。', 'sakurairo'),
            iro_i18n_language_label($code)
        )) ?>
    </p>
<?php

    iro_i18n_render_version_note($post);

    if (iro_i18n_autofuzzy_enabled()) {
        echo '<p class="description" style="margin-top:8px;">'
            . esc_html__('原文内容改动后，其余语言版本会在前台挂出「译文可能已过期」的提示；把译文核对一遍并重新发布即视为已对齐。', 'sakurairo')
            . '</p>';
    }
}

/**
 * 版本对齐情况：原文这一版是什么时候改的、本页是否落后
 *
 * 不做修订或快照对照——原文与译文是两份不同的文本，逐段 diff 看不出该改哪里，
 * 译者要看的是「原文最新版」本身。
 */
function iro_i18n_render_version_note(WP_Post $post): void
{
    if (!iro_i18n_autofuzzy_enabled() || iro_i18n_is_base_post($post->ID)) {
        return;
    }

    $source = iro_i18n_group_source($post->ID);

    if (!$source instanceof WP_Post) {
        return;
    }

    $outdated = iro_i18n_translation_outdated($post->ID);
?>
    <p class="description" style="margin-top:8px;">
        <?php if ($outdated) : ?>
            <?= esc_html(sprintf(
                /* translators: 1: 原文最后改动时间 2: 译文对齐到的那一版时间 */
                __('原文最后改动于 %1$s，本页译文对齐的是 %2$s 那一版。', 'sakurairo'),
                iro_i18n_source_version($source->ID),
                iro_i18n_source_version($post->ID)
            )) ?>
            <strong style="color:#b32d2e;"><?= esc_html__('译文已落后，前台会提示读者。', 'sakurairo') ?></strong>
        <?php else : ?>
            <?= esc_html(sprintf(
                /* translators: %s: 原文最后改动时间 */
                __('译文已对齐原文 %s 那一版。', 'sakurairo'),
                iro_i18n_source_version($post->ID)
            )) ?>
        <?php endif; ?>
    </p>
    <p style="margin:0;">
        <a class="button button-small" href="<?= esc_url((string) get_edit_post_link($source->ID, 'raw')) ?>">
            <?= esc_html__('打开原文', 'sakurairo') ?>
        </a>
        <a class="button button-small" href="<?= esc_url(iro_i18n_translation_permalink($source)) ?>" target="_blank" rel="noopener">
            <?= esc_html__('查看原文前台页面', 'sakurairo') ?>
        </a>
    </p>
<?php
}

/**
 * 手动触发一次「原文语言归位」，供站长在改过默认语言后自行校正
 */
function iro_i18n_handle_repair_language(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('权限不足。', 'sakurairo'));
    }

    check_admin_referer('iro_i18n_repair_language');

    $fixed = iro_i18n_repair_default_language();

    update_option('iro_i18n_default_repair', $fixed);

    wp_safe_redirect(add_query_arg([
        'page'              => 'iro-i18n',
        'iro_i18n_repaired' => $fixed,
    ], admin_url('tools.php')));
    exit;
}

/**
 * 把早期副本的别名改成「原文别名 + 语言代号」
 *
 * 早期副本与原文同名，被 WordPress 自动接上了 `-2`、`-3`；那串数字既不稳定也
 * 看不出属于哪种语言。这里按现在的规则重命名，让别名与语言一一对应。
 *
 * 只动别名，不动关联字段与语言标记，因此分组关系不受影响。
 *
 * @return int 改名的篇数
 */
function iro_i18n_rename_translation_slugs(): int
{
    $renamed = 0;

    foreach (iro_i18n_languages() as $code) {
        if (iro_i18n_is_default_language($code)) {
            continue;
        }

        $posts = get_posts([
            'post_type'        => iro_i18n_supported_post_types(),
            'post_status'      => iro_i18n_existing_statuses(),
            'posts_per_page'   => -1,
            'orderby'          => 'ID',
            'order'            => 'ASC',
            'suppress_filters' => false,
            'no_found_rows'    => true,
            'meta_query'       => [
                [
                    'key'   => IRO_I18N_POST_LANG_META,
                    'value' => $code,
                ],
            ],
        ]);

        foreach ($posts as $post) {
            $path = iro_i18n_get_path($post->ID);

            if ($path === '' || iro_i18n_is_base_post($post->ID)) {
                continue;
            }

            $expected = iro_i18n_skeleton_post_name($path, $code);

            if ($post->post_name === $expected) {
                continue;
            }

            wp_update_post([
                'ID'        => $post->ID,
                'post_name' => $expected,
            ]);

            $renamed++;
        }
    }

    return $renamed;
}

/**
 * 把手动触发的别名规范化
 */
function iro_i18n_handle_rename_slugs(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('权限不足。', 'sakurairo'));
    }

    check_admin_referer('iro_i18n_rename_slugs');

    $renamed = iro_i18n_rename_translation_slugs();

    wp_safe_redirect(add_query_arg([
        'page'            => 'iro-i18n',
        'iro_i18n_renamed' => $renamed,
    ], admin_url('tools.php')));
    exit;
}

/**
 * 翻译总览：按关联标识列出各组语言覆盖情况，并支持一次性补齐关联与副本
 */
function iro_i18n_render_overview_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('权限不足。', 'sakurairo'));
    }

    $post_types = iro_i18n_supported_post_types();
    $paged      = max(1, (int) ($_GET['paged'] ?? 1));
    $per_page   = 50;

    $query = new WP_Query([
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'ID',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => IRO_I18N_PATH_META,
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    $paths = [];

    foreach ($query->posts as $post) {
        $path = iro_i18n_get_path($post->ID);

        if ($path !== '') {
            $paths[$path] = true;
        }
    }

    $translations = iro_i18n_paths_translations(array_keys($paths), $post_types);
?>
    <div class="wrap">
        <h1><?php esc_html_e('翻译总览', 'sakurairo'); ?></h1>

        <p class="description">
            <?php esc_html_e('列出已建立语言关联的内容，以及各语言的版本状态。未翻译的副本默认是草稿，填好内容改成发布即可接手同一关联标识。', 'sakurairo'); ?>
        </p>

        <p class="description">
            <?= esc_html(sprintf(
                /* translators: 1: 默认语言名与代号 2: 站点语言 3: 该默认语言的路由前缀 */
                __('默认语言是「%1$s」（跟随站点语言 %2$s），无前缀路径即属于它；其余语言通过 /%3$s/ 之类的前缀访问。', 'sakurairo'),
                iro_i18n_language_label(iro_i18n_default_language()),
                iro_i18n_site_locale(),
                iro_i18n_prefix(iro_i18n_default_language())
            )) ?>
        </p>

        <?php if (isset($_GET['iro_i18n_repaired'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?= esc_html(sprintf(
                        /* translators: %d: 改动的篇数 */
                        __('已把 %d 条原文的语言标记归位到默认语言。', 'sakurairo'),
                        (int) $_GET['iro_i18n_repaired']
                    )) ?></p>
            </div>
        <?php endif; ?>

        <?php if (iro_i18n_autofuzzy_enabled()) : ?>
            <p class="description">
                <?php esc_html_e('标记为「待同步」的版本说明原文在此之后改动过，它的内容仍然在线，只是会在前台挂出可能过期的提示；把译文核对一遍并重新发布即视为已对齐。', 'sakurairo'); ?>
            </p>
        <?php endif; ?>

        <?php if (isset($_GET['iro_i18n_backfilled'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?= esc_html(sprintf(
                        /* translators: %d: 补齐的内容数量 */
                        __('已为 %d 条内容补齐语言关联与未翻译副本。', 'sakurairo'),
                        (int) $_GET['iro_i18n_backfilled']
                    )) ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>" style="margin:12px 0;">
            <?php wp_nonce_field('iro_i18n_backfill'); ?>
            <input type="hidden" name="action" value="iro_i18n_backfill">
            <button type="submit" class="button">
                <?php esc_html_e('为全部已发布内容补齐关联与未翻译副本', 'sakurairo'); ?>
            </button>
        </form>

        <?php if (current_user_can('manage_options')) : ?>
            <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>" style="margin:12px 0;">
                <?php wp_nonce_field('iro_i18n_repair_language'); ?>
                <input type="hidden" name="action" value="iro_i18n_repair_language">
                <button type="submit" class="button">
                    <?php esc_html_e('把原文的语言标记归位到默认语言', 'sakurairo'); ?>
                </button>
                <span class="description">
                    <?php esc_html_e('只动「关联标识等于自身别名」且没有副本标记的内容，即真正的原文。', 'sakurairo'); ?>
                </span>
            </form>

            <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>" style="margin:12px 0;">
                <?php wp_nonce_field('iro_i18n_rename_slugs'); ?>
                <input type="hidden" name="action" value="iro_i18n_rename_slugs">
                <button type="submit" class="button">
                    <?php esc_html_e('规范化译文别名（原文别名 + 语言代号）', 'sakurairo'); ?>
                </button>
                <span class="description">
                    <?php esc_html_e('把早期被 WordPress 接上 -2、-3 的译文别名改成「-zh-tw」这类形式；会改动已发布译文的地址。', 'sakurairo'); ?>
                </span>
            </form>
        <?php endif; ?>

        <?php if (isset($_GET['iro_i18n_renamed'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?= esc_html(sprintf(
                        /* translators: %d: 改名的篇数 */
                        __('已规范化 %d 条译文的别名。', 'sakurairo'),
                        (int) $_GET['iro_i18n_renamed']
                    )) ?></p>
            </div>
        <?php endif; ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('内容', 'sakurairo'); ?></th>
                    <th><?php esc_html_e('关联标识', 'sakurairo'); ?></th>
                    <?php foreach (iro_i18n_languages() as $code) : ?>
                        <th><?= esc_html(iro_i18n_language_name($code)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($query->posts === []) : ?>
                    <tr>
                        <td colspan="<?= esc_attr((string) (2 + count(iro_i18n_languages()))) ?>">
                            <?php esc_html_e('还没有建立语言关联的内容。编辑并保存一次，或点上方按钮批量补齐。', 'sakurairo'); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($query->posts as $post) : ?>
                    <?php
                    $map       = $translations[iro_i18n_get_path($post->ID)] ?? [];
                    $post_type = get_post_type_object($post->post_type);
                    ?>
                    <tr>
                        <td>
                            <a href="<?= esc_url((string) get_edit_post_link($post->ID, 'raw')) ?>">
                                <?= esc_html(get_the_title($post)) ?>
                            </a>
                            <span class="description">（<?= esc_html($post_type ? $post_type->labels->singular_name : $post->post_type) ?>）</span>
                        </td>
                        <td><code><?= esc_html(iro_i18n_get_path($post->ID)) ?></code></td>
                        <?php foreach (iro_i18n_language_statuses($map) as $row) : ?>
                            <td><?= wp_kses_post(iro_i18n_status_cell($row)) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($query->max_num_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?= wp_kses_post(paginate_links([
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => (int) $query->max_num_pages,
                        'prev_text' => __('«', 'sakurairo'),
                        'next_text' => __('»', 'sakurairo'),
                    ])) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php
}

/**
 * 取回尚未认领语言标记的已发布内容
 *
 * 语言隔离靠分类法查询实现，所以这些内容必须补上默认语言术语，
 * 否则一开语言筛选它们就从列表里消失。
 *
 * @param string[] $post_types
 * @return WP_Post[]
 */
function iro_i18n_posts_without_language(array $post_types = []): array
{
    $query = new WP_Query([
        'post_type'              => $post_types === [] ? iro_i18n_supported_post_types() : $post_types,
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        'tax_query'              => [
            [
                'taxonomy' => IRO_I18N_LANGUAGE_TAXONOMY,
                'operator' => 'NOT EXISTS',
            ],
        ],
    ]);

    return $query->posts;
}

/**
 * 把「原文被标成了别的语言」的内容改回默认语言
 *
 * 默认语言早期取的是「启用语言的第一个」，而设置页的 checkbox 按勾选顺序存数组，
 * 于是换个勾选次序就会换掉默认语言，批量补齐随之把全部原文标成了那种语言。
 * 现在默认语言跟随站点语言，这些内容需要一次性归位。
 *
 * 只动「原文」：关联标识等于自己的别名（页面的多级路径除外）、且没有副本标记。
 * 长度超过一段的页面属于层级路径，需要交给调用方用页面祖先判断。
 *
 * @return int 改动的篇数
 */
function iro_i18n_repair_default_language(): int
{
    $default = iro_i18n_default_language();
    $fixed   = 0;

    iro_i18n_flush_group_cache();

    foreach (iro_i18n_supported_post_types() as $post_type) {
        $posts = get_posts([
            'post_type'              => $post_type,
            'post_status'            => iro_i18n_existing_statuses(),
            'posts_per_page'         => -1,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'suppress_filters'       => false,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        ]);

        foreach ($posts as $post) {
            if (iro_i18n_is_skeleton($post->ID)) {
                continue;
            }

            $path = iro_i18n_get_path($post->ID);

            if ($path === '' || $path !== iro_i18n_compute_path($post->ID)) {
                continue;
            }

            if (iro_i18n_post_language($post->ID) === $default) {
                continue;
            }

            iro_i18n_set_post_language($post->ID, $default);
            $fixed++;
        }
    }

    if ($fixed > 0) {
        iro_i18n_flush_group_cache();
    }

    return $fixed;
}

/**
 * 后台自动跑一次归位，避免站长升级后还得自己发现「原文被标成了日语」
 *
 * 写标记 option 的时机要在归位之后并带一篇哨兵：直接写常量的话，
 * 归位自身出错时会被永久跳过。
 */
function iro_i18n_maybe_repair_default_language(): void
{
    if (!iro_i18n_enabled() || get_option('iro_i18n_default_repair') !== false) {
        return;
    }

    update_option('iro_i18n_default_repair', iro_i18n_repair_default_language());
}

/**
 * 给全部尚无语言标记的内容认领默认语言
 *
 * 语言判定把「没有标记」当作默认语言，查询侧也照此排除；数据侧若一直空着，
 * 这些内容就只存在于「判定」里，既不进默认语言的筛选结果，又会在任何按语言
 * 收窄的列表里凭空消失。这里把它们落成真实的默认语言术语，让三层口径一致。
 *
 * @return int 改动的篇数
 */
function iro_i18n_adopt_untagged_language(): int
{
    $post_types = iro_i18n_supported_post_types();

    if ($post_types === []) {
        return 0;
    }

    $query = new WP_Query([
        // 'any' 才不会让主题在 pre_get_posts 里把类型改写成单一类型而漏掉页面
        'post_type'              => count($post_types) > 1 ? 'any' : $post_types[0],
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'suppress_filters'       => false,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        'tax_query'              => [
            [
                'taxonomy' => IRO_I18N_LANGUAGE_TAXONOMY,
                'operator' => 'NOT EXISTS',
            ],
        ],
    ]);

    $fixed = 0;

    foreach ($query->posts as $post) {
        if (iro_i18n_is_skeleton($post->ID)) {
            continue;
        }

        iro_i18n_adopt_default_language($post->ID);
        $fixed++;
    }

    if ($fixed > 0) {
        iro_i18n_flush_group_cache();
    }

    return $fixed;
}

/**
 * 后台自动跑一次语言归位
 *
 * 只认哨兵，不认「是否还有未标记内容」：后者每加一篇新草稿都会重新触发，
 * 每次都全表扫一遍。
 */
function iro_i18n_maybe_adopt_untagged_language(): void
{
    if (!iro_i18n_enabled() || get_option('iro_i18n_languages_adopted') !== false) {
        return;
    }

    update_option('iro_i18n_languages_adopted', iro_i18n_adopt_untagged_language());
}

/**
 * 批量补齐：为尚无关联字段的已发布内容补关联、补语言标记，并同步未翻译副本
 */
function iro_i18n_handle_backfill(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('权限不足。', 'sakurairo'));
    }

    check_admin_referer('iro_i18n_backfill');

    $count = 0;

    foreach (iro_i18n_posts_without_language() as $post) {
        iro_i18n_adopt_default_language($post->ID);
        $count++;
    }

    $query = new WP_Query([
        'post_type'              => iro_i18n_supported_post_types(),
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        // 保留 meta 缓存：下面逐篇判关联与副本标记，关掉会退化成逐篇查库
        'meta_query'             => [
            'relation' => 'OR',
            [
                'key'     => IRO_I18N_PATH_META,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => IRO_I18N_PATH_META,
                'value'   => '',
                'compare' => '=',
            ],
        ],
    ]);

    foreach ($query->posts as $post) {
        if (iro_i18n_is_skeleton($post->ID)) {
            continue;
        }

        if (iro_i18n_ensure_path($post->ID) === '') {
            continue;
        }

        iro_i18n_sync_translations($post->ID);
        $count++;
    }

    wp_safe_redirect(add_query_arg([
        'page'                 => 'iro-i18n',
        'iro_i18n_backfilled'  => $count,
    ], admin_url('tools.php')));
    exit;
}

if (iro_i18n_enabled()) {
    add_action('admin_menu', function () {
        if (!current_user_can('edit_posts')) {
            return;
        }

        add_management_page(
            __('翻译总览', 'sakurairo'),
            __('翻译总览', 'sakurairo'),
            'edit_posts',
            'iro-i18n',
            'iro_i18n_render_overview_page'
        );
    });

    add_action('admin_post_iro_i18n_backfill', 'iro_i18n_handle_backfill');
    add_action('admin_post_iro_i18n_repair_language', 'iro_i18n_handle_repair_language');
    add_action('admin_post_iro_i18n_rename_slugs', 'iro_i18n_handle_rename_slugs');
    add_action('add_meta_boxes', 'iro_i18n_register_meta_box');
    add_action('admin_init', 'iro_i18n_maybe_repair_default_language');
    add_action('admin_init', 'iro_i18n_maybe_adopt_untagged_language');

    add_action('admin_init', function () {
        foreach (iro_i18n_supported_post_types() as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", function (array $columns): array {
                $columns[IRO_I18N_LANGUAGE_TAXONOMY] = __('语言', 'sakurairo');

                return $columns;
            });
            add_action("manage_{$post_type}_posts_custom_column", 'iro_i18n_render_language_column', 10, 2);
        }
    });

    add_action('restrict_manage_posts', 'iro_i18n_render_language_filter');

    // parse_query 时机上整条 tax_query 已由核心汇总完，这里并入语言条件不会被覆盖
    add_action('parse_query', 'iro_i18n_filter_language_query', 20);
}
