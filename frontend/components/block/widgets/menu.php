<?php
// 侧栏导航：条目数据取自 side_bar 菜单位置，结构与移动端导航栏一致
$menu_items = iro_get_navigation('side_bar');

if (!is_array($menu_items)) {
    $menu_items = [];
}
?>
<?php if ($menu_items): ?>
    <nav class="iro-sidebar-menu">
        <ul class="menu">
            <?php foreach ($menu_items as $item): ?>
                <?php $has_children = !empty($item['children']); ?>
                <li class="item <?= esc_attr($item['classes'] ?? '') ?><?= $has_children ? " has-children" : "" ?>">
                    <div class="item-head">
                        <a class="link" href="<?= esc_url($item['url']) ?>"><?= $item['title'] ?></a>
                        <?php if ($has_children): ?>
                            <button
                                type="button"
                                class="button"
                                aria-expanded="false"
                                aria-label="<?= esc_attr__("展开子菜单", "sakurairo") ?>"></button>
                        <?php endif ?>
                    </div>
                    <?php if ($has_children): ?>
                        <ul class="sub-menu">
                            <?php foreach ($item['children'] as $child): ?>
                                <li class="<?= esc_attr($child['classes'] ?? '') ?>">
                                    <a href="<?= esc_url($child['url']) ?>"><?= $child['title'] ?></a>
                                    <?php if (!empty($child['children'])): ?>
                                        <ul class="sub-menu">
                                            <?php foreach ($child['children'] as $grandchild): ?>
                                                <li>
                                                    <a href="<?= esc_url($grandchild['url']) ?>"><?= $grandchild['title'] ?></a>
                                                </li>
                                            <?php endforeach ?>
                                        </ul>
                                    <?php endif ?>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>
    </nav>
<?php endif ?>
