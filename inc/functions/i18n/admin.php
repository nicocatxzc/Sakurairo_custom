<?php
// 内容国际化后台管理：列表列、语言筛选、编辑页面板与批量维护动作
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 同组各语言版本的标记，只取参与语言，缺一不可地显示出来
 *
 * @param array<string,WP_Post> $map
 * @return array<int,array{code:string,default:bool,name:string,state:string,label:string,url:string}>
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
            'code'    => $code,
            // 默认语言只多一个标记，状态照旧按版本情况判定：原文那一版不会被判定成待同步
            'default' => $code === $default,
            'name'    => iro_i18n_language_name($code),
            'state'   => $state,
            'label'   => $label,
            'url'     => $url,
        ];
    }

    return $rows;
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

if (iro_i18n_enabled()) {
    /**
     * 编辑页的多语言面板
     *
     * 关联字段对编辑者只读：同组版本靠它串起来，手改会把分组改断。
     */
    add_action('add_meta_boxes', function (): void {
        foreach (iro_i18n_supported_post_types() as $post_type) {
            $type = get_post_type_object($post_type);

            if (!$type instanceof WP_Post_Type || !current_user_can($type->cap->edit_posts)) {
                continue;
            }

            add_meta_box(
                'iro_i18n_translations',
                __('多语言', 'sakurairo'),
                function (WP_Post $post): void {
                    $path = iro_i18n_get_path($post->ID);

                    if ($path === '') {
                        // 保存一次即会补上，这里先按当前别名展示预期值
                        $path = iro_i18n_compute_path($post->ID);
                        echo '<p class="description">' . esc_html__('保存后自动建立语言关联。', 'sakurairo') . '</p>';
                    }

                    echo '<p><strong>' . esc_html__('关联标识', 'sakurairo') . '</strong><br><code>' . esc_html($path) . '</code></p>';
                    echo '<ul style="margin:0;">';

                    foreach (iro_i18n_language_statuses(iro_i18n_group_map($post->ID)) as $row) {
                        // 待同步优先于其它状态显示
                        $cell = $row['url'] === ''
                            ? '<span class="description">' . esc_html($row['label']) . '</span>'
                            : '<a href="' . esc_url($row['url']) . '"'
                            . ($row['state'] === 'outdated' ? ' style="color:#b32d2e;font-weight:600;"' : '')
                            . '>' . esc_html($row['label']) . '</a>';

                        echo '<li style="margin:0 0 4px;">';
                        echo '<strong>' . esc_html($row['name']) . '</strong>：';
                        echo wp_kses_post($cell);
                        echo '</li>';
                    }

                    echo '</ul>';

                    // 副本正文是原文的逐区块拷贝，因此官方 AI 插件的区块翻译可以直接在编辑器里用；
                    // 这里点明该在它的语言选择器里挑哪一项，避免译者选错语言
                    $code = iro_i18n_post_language($post->ID);
                    $name = iro_i18n_language_name($code);
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
                        $name === $code ? $code : $name . '（' . $code . '）'
                    )) ?>
                </p>
                <?php
                    /**
                     * 版本对齐情况：原文这一版是什么时候改的、本页是否落后
                     *
                     * 不做修订或快照对照——原文与译文是两份不同的文本，逐段 diff 看不出该改哪里，
                     * 译者要看的是「原文最新版」本身。
                     */
                    if (iro_i18n_autofuzzy_enabled() && !iro_i18n_is_base_post($post->ID)) {
                        $source = iro_i18n_group_source($post->ID);

                        if ($source instanceof WP_Post) {
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
                    }

                    if (iro_i18n_autofuzzy_enabled()) {
                        echo '<p class="description" style="margin-top:8px;">'
                            . esc_html__('原文内容改动后，其余语言版本会在前台挂出「译文可能已过期」的提示；把译文核对一遍并重新发布即视为已对齐。', 'sakurairo')
                            . '</p>';
                    }
                },
                $post_type,
                'side',
                'default'
            );
        }
    });

    /**
     * 后台自动跑一次归位，避免站长升级后还得自己发现「原文被标成了日语」
     *
     * 写标记 option 的时机要在归位之后并带一篇哨兵：直接写常量的话，
     * 归位自身出错时会被永久跳过。
     */
    add_action('admin_init', function (): void {
        if (!iro_i18n_enabled() || get_option('iro_i18n_default_repair') !== false) {
            return;
        }

        update_option('iro_i18n_default_repair', iro_i18n_repair_default_language());
    });

    /**
     * 后台自动跑一次语言归位：给全部尚无语言标记的内容认领默认语言
     *
     * 语言判定把「没有标记」当作默认语言，查询侧也照此排除；数据侧若一直空着，
     * 这些内容就只存在于「判定」里，既不进默认语言的筛选结果，又会在任何按语言
     * 收窄的列表里凭空消失。这里把它们落成真实的默认语言术语，让三层口径一致。
     *
     * 只认哨兵，不认「是否还有未标记内容」：后者每加一篇新草稿都会重新触发，
     * 每次都全表扫一遍。
     */
    add_action('admin_init', function (): void {
        if (!iro_i18n_enabled() || get_option('iro_i18n_languages_adopted') !== false) {
            return;
        }

        $post_types = iro_i18n_supported_post_types();
        $fixed      = 0;

        if ($post_types !== []) {
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
        }

        update_option('iro_i18n_languages_adopted', $fixed);
    });

    add_action('admin_init', function () {
        foreach (iro_i18n_supported_post_types() as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", function (array $columns): array {
                $columns[IRO_I18N_LANGUAGE_TAXONOMY] = __('语言', 'sakurairo');

                return $columns;
            });

            add_action("manage_{$post_type}_posts_custom_column", function (string $column, int $post_id): void {
                if ($column !== IRO_I18N_LANGUAGE_TAXONOMY) {
                    return;
                }

                $post = get_post($post_id);

                if (!$post instanceof WP_Post) {
                    return;
                }

                $code  = iro_i18n_post_language($post_id);
                $label = __('未标记', 'sakurairo');

                if (iro_i18n_post_has_language($post_id)) {
                    $label = iro_i18n_is_skeleton($post_id)
                        ? __('未翻译', 'sakurairo')
                        : iro_i18n_language_name($code);
                }

                echo '<strong>' . esc_html(iro_i18n_language_name($code)) . '</strong>';
                echo '<br><span class="description">' . esc_html($label) . '</span>';

                // 代号与 URL 前缀是两个值，列表里一并给出，省得去设置页反查
                if ($code !== iro_i18n_default_language()) {
                    echo '<br><code style="font-size:11px;">' . esc_html(iro_i18n_prefix($code)) . '/</code>';
                }

                // 这里刻意不查同组版本：列表每行都查会拖慢后台，关联明细留给编辑页的面板
                if (iro_i18n_get_path($post_id) === '' && !iro_i18n_is_skeleton($post_id)) {
                    echo '<br><span class="description">' . esc_html__('保存后建立关联', 'sakurairo') . '</span>';
                }
            }, 10, 2);
        }
    });

    /**
     * 列表页的语言筛选
     *
     * 选项值用语言代号，WP 会把 `iro_lang` 参数交给自身的 taxonomy
     * 查询处理，因此这里不需要自建查询逻辑。
     */
    add_action('restrict_manage_posts', function (string $post_type): void {
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
    });

    /**
     * 列表页按语言筛选
     *
     * 不筛选时保持全部语言，管理视图要看得到所有版本；筛选时才收窄到所选语言。
     * 收窄用的条件与前台共用 `iro_i18n_language_tax_clause()`，两边对
     * 「未标记 = 默认语言」的口径必须一致，否则列表筛选与前台归档会各说各话。
     *
     * parse_query 时机上整条 tax_query 已由核心汇总完，这里并入语言条件不会被覆盖。
     */
    add_action('parse_query', function (WP_Query $query): void {
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
    }, 20);
}
