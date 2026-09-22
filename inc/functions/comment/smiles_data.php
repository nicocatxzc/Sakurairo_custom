<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 评论表情中央定义。
 *
 * 面板数据（REST: sakura/v1/comment/smiles）、评论/正文里的 token 替换、邮件模板都取自这一份数据，
 * 增删表情只改 iro_smiley_packs_definition()，不要再在各处拼 HTML 或另建替换表。
 *
 * 结构：根为表情包数组，包内 items 为具体表情。
 *
 * pack
 *   id          string      唯一标识，取值与选项 comment_smilies_list 相同
 *   title       string      面板标签
 *   type        image|text  image 是图片（有 code 才参与服务端替换），text 是可直接插入的文本
 *   code_format string|null 含单个 %s 的 token 模板，item.code = sprintf(code_format, item.id)；
 *                           text 包传 null，此时 code 就是文本本身
 *   insert      array       前端插入策略 {mode: token|raw, wrap?: [前缀, 后缀], wrap_requires_markdown?: bool}
 *   url_format  string|null image 包的图片地址模板，占位符 {base} / {type} / {id}
 *   items       array       表情定义：字符串表示只给 id，数组形式可覆写 id/title/alt/code/text/src/size
 *
 * item（iro_get_smiley_packs() 解析后）
 *   id / type / code / title         必给
 *   src                              image 包图片地址
 *   alt                              image 包 img alt，缺省取 title（text 包没有这个字段）
 *   text                             text 包显示文本
 *   size    number                   可选，显式 px 高度；只有显式给出才会写进替换出的 <img> 内联高度
 *
 * 约定
 *   - 面板标签顺序 = 本文件定义顺序，第一个被启用的包为默认展开项；选项 comment_smilies_list 只控制开关。
 *   - code_format 相同的包，服务端替换按定义先后取先者（bilibili 的 {{%s}} 先于 custom）。
 *     要区分就得改相应包的 code_format，代价是历史评论里该 token 会指向新表情。
 *   - items 为空的包不会出现在结果里（例如没填自定义表情的表情包）。
 * 注意：iro_opt() 依赖的 iro_options 在选项框架加载后可用，所有取值都发生在函数调用期，不要提到文件顶层。
 */
function iro_smiley_packs_definition()
{
    return [
        'bilibili' => [
            'title'       => 'bilibili~',
            'type'        => 'image',
            'code_format' => '{{%s}}',
            'insert'      => ['mode' => 'token'],
            'url_format'  => '{base}smilies/bili{type}/emoji_{id}.{type}',
            'items'       => [
                'baiyan',
                'bishi',
                'bizui',
                'chan',
                'dai',
                'daku',
                'dalao',
                'dalian',
                'dianzan',
                'doge',
                'facai',
                'fanu',
                'ganga',
                'guilian',
                'guzhang',
                'haixiu',
                'heirenwenhao',
                'huaixiao',
                'jingxia',
                'keai',
                'koubizi',
                'kun',
                'lengmo',
                'liubixue',
                'liuhan',
                'liulei',
                'miantian',
                'mudengkoudai',
                'nanguo',
                'outu',
                'qinqin',
                'se',
                'shengbing',
                'shengqi',
                'shuizhao',
                'sikao',
                'tiaokan',
                'tiaopi',
                'touxiao',
                'tuxue',
                'weiqu',
                'weixiao',
                'wunai',
                'xiaoku',
                'xieyanxiao',
                'yiwen',
                'yun',
                'zaijian',
                'zhoumei',
                'zhuakuang',
            ],
        ],

        'tieba' => [
            'title'       => 'Tieba',
            'type'        => 'image',
            'code_format' => '::%s::',
            'insert'      => ['mode' => 'token'],
            'url_format'  => '{base}smilies/tieba{type}/icon_{id}.{type}',
            'items'       => [
                'good',
                'han',
                'spray',
                'Grievance',
                'shui',
                'reluctantly',
                'anger',
                'tongue',
                'se',
                'haha',
                'rmb',
                'doubt',
                'tear',
                'surprised2',
                'Happy',
                'ku',
                'surprised',
                'theblackline',
                'smilingeyes',
                'spit',
                'huaji',
                'bbd',
                'hu',
                'shame',
                'naive',
                'rbq',
                'britan',
                'aa',
                'niconiconi',
                'niconiconi_t',
                'niconiconit',
                'awesome',
            ],
        ],

        'yanwenzi' => [
            'title'       => '(=・ω・=)',
            'type'        => 'text',
            'code_format' => null,
            // 反引号把颜文字包成 Markdown 行内代码，避免被斜体/列表语法吃掉；未启用 Markdown 时不能加，反引号会被原样显示
            'insert'      => [
                'mode'                   => 'raw',
                'wrap'                   => ['`', '` '],
                'wrap_requires_markdown' => true,
            ],
            'url_format'  => null,
            // 源码里是 HTML 实体（&lt; &gt;），这里统一写成原始字符，避免实体被二次转义
            'items'       => [
                '(⌒▽⌒)',
                '（￣▽￣）',
                '(=・ω・=)',
                '(｀・ω・´)',
                '(〜￣△￣)〜',
                '(･∀･)',
                '(°∀°)ﾉ',
                '(￣3￣)',
                '╮(￣▽￣)╭',
                '(´_ゝ｀)',
                '←_←',
                '→_→',
                '(<_<)',
                '(>_>)',
                '(;¬_¬)',
                '("▔□▔)/',
                '(ﾟДﾟ≡ﾟдﾟ)!?',
                'Σ(ﾟдﾟ;)',
                'Σ(￣□￣||)',
                '(’；ω；‘)',
                '（/TДT)/',
                '(^・ω・^ )',
                '(｡･ω･｡)',
                '(●￣(ｴ)￣●)',
                'ε=ε=(ノ≧∇≦)ノ',
                '(’･_･‘)',
                '(-_-#)',
                '（￣へ￣）',
                '(￣ε(#￣)Σ',
                'ヽ(‘Д’)ﾉ',
                '（#-_-)┯━┯',
                '(╯°口°)╯(┴—┴',
                '←◡←',
                '( ♥д♥)',
                '_(:3」∠)_',
                'Σ>―(〃°ω°〃)♡→',
                '⁄(⁄ ⁄•⁄ω⁄•⁄ ⁄)⁄',
                '(╬ﾟдﾟ)▄︻┻┳═一',
                '･*･:≡(　ε:)',
                '(笑)',
                '(汗)',
                '(泣)',
                '(苦笑)',
            ],
        ],

        'custom' => [
            'title'       => iro_opt('comment_smilies_list_custom_name', 'custom'),
            'type'        => 'image',
            'code_format' => '{{%s}}',
            'insert'      => ['mode' => 'token'],
            'url_format'  => null,
            // items 由 iro_get_custom_smiley_items() 读取选项 comment_smilies_custom 得到，item 自带 src/title/size
            'items'       => [],
        ],
    ];
}

/**
 * 图片资源是否优先用 webp，取决于前端是否声明支持（旧版脚本会写 su_webp cookie）
 */
function iro_is_webp()
{
    return isset($_COOKIE['su_webp']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false);
}

/**
 * url_format 里的公共占位符
 */
function iro_smiley_asset_context()
{
    static $context = null;

    if ($context !== null) {
        return $context;
    }

    return $context = [
        'base' => (string) iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/'),
        'type' => iro_is_webp() ? 'webp' : 'png',
    ];
}

function iro_smiley_resolve_url(array $pack, string $id)
{
    if (empty($pack['url_format'])) {
        return '';
    }

    return strtr(
        $pack['url_format'],
        [
            '{base}' => iro_smiley_asset_context()['base'],
            '{type}' => iro_smiley_asset_context()['type'],
            '{id}'   => $id,
        ]
    );
}

/**
 * 把定义里的紧凑写法展开成统一 item；无法解析（无 id / 图片地址为空）返回 null
 */
function iro_smiley_resolve_item(array $pack, array $item)
{
    $id = isset($item['id']) ? (string) $item['id'] : '';

    if ($id === '') {
        return null;
    }

    $resolved = [
        'id'    => $id,
        'type'  => $pack['type'],
        'title' => (string) ($item['title'] ?? $id),
    ];

    if ($pack['type'] === 'text') {
        $resolved['text'] = (string) ($item['text'] ?? $id);
        $resolved['code'] = (string) ($item['code'] ?? $resolved['text']);
    } else {
        $resolved['alt']  = (string) ($item['alt'] ?? $resolved['title']);
        $resolved['code'] = (string) ($item['code'] ?? sprintf((string) $pack['code_format'], $id));
        $resolved['src']  = (string) ($item['src'] ?? iro_smiley_resolve_url($pack, $id));

        if ($resolved['src'] === '') {
            return null;
        }
    }

    if (isset($item['size'])) {
        $resolved['size'] = (int) $item['size'];
    }

    return $resolved;
}

/**
 * 启用中的表情包（定义顺序）。选项 comment_smilies_list 为空表示关闭表情功能。
 */
function iro_get_smiley_packs()
{
    static $packs = null;

    if ($packs !== null) {
        return $packs;
    }

    $packs   = [];
    $enabled = iro_opt('comment_smilies_list', ['bilibili', 'tieba', 'yanwenzi']);

    if (!is_array($enabled) || !$enabled) {
        return $packs;
    }

    foreach (iro_smiley_packs_definition() as $pack_id => $pack) {
        if (!in_array($pack_id, $enabled, true)) {
            continue;
        }

        $definition_items = $pack_id === 'custom' ? iro_get_custom_smiley_items() : $pack['items'];
        $items            = [];

        foreach ($definition_items as $definition_item) {
            $item = iro_smiley_resolve_item($pack, is_array($definition_item) ? $definition_item : ['id' => (string) $definition_item]);

            if ($item) {
                $items[] = $item;
            }
        }

        if (!$items) {
            continue;
        }

        $packs[] = [
            'id'          => $pack_id,
            'title'       => (string) $pack['title'],
            'type'        => $pack['type'],
            'code_format' => $pack['code_format'],
            'insert'      => $pack['insert'],
            'url_format'  => $pack['url_format'],
            'items'       => $items,
        ];
    }

    return $packs;
}

/**
 * token => 图片 HTML。只有 image 包参与替换，text 包原样保留（颜文字本身就是要显示的文字）。
 * 相同 code 取定义更靠前的包，见文件头的约定。
 */
function iro_get_smiley_translations()
{
    static $translations = null;

    if ($translations !== null) {
        return $translations;
    }

    $translations = [];

    foreach (iro_get_smiley_packs() as $pack) {
        if ($pack['type'] !== 'image') {
            continue;
        }

        foreach ($pack['items'] as $item) {
            if (!isset($translations[$item['code']])) {
                $translations[$item['code']] = iro_smiley_image_html($item);
            }
        }
    }

    return $translations;
}

function iro_smiley_image_html(array $item)
{
    $size = isset($item['size']) ? ' style="height:' . $item['size'] . 'px"' : '';

    return '<img class="iro-smiley" src="' . esc_url($item['src']) . '" alt="' . esc_attr($item['alt']) . '" title="' . esc_attr($item['title']) . '" loading="lazy"' . $size . ' />';
}

/**
 * 评论/正文里的表情 token 替换成图片
 *
 * @param mixed $content
 * @return mixed
 */
function iro_replace_smilies($content)
{
    if (!is_string($content) || $content === '') {
        return $content;
    }

    $translations = iro_get_smiley_translations();

    if (!$translations) {
        return $content;
    }

    // token 都带成对分隔符（{{x}} / ::x::），不存在前缀互相吃掉的情况，可以直接 str_replace
    return str_replace(array_keys($translations), $translations, $content);
}

/**
 * 自定义表情：来自选项 comment_smilies_custom（CSF repeater），一行一个表情
 * 每行字段：img（图片地址，必填）、name（标记名，必填，即 {{name}} 里的字符）、title（提示文案，可选）、size（显示高度 px，可选）
 *
 * @return array item 定义，交给 iro_smiley_resolve_item() 补齐 code/alt
 */
function iro_get_custom_smiley_items()
{
    static $items = null;

    if ($items !== null) {
        return $items;
    }

    $items = [];
    $seen  = [];

    foreach ((array) iro_opt('comment_smilies_custom', []) as $row) {
        if (!is_array($row)) {
            continue;
        }

        // 标记会原样写进评论正文再被 str_replace 匹配，去掉会被转义（&<>"'）或与 token 分隔符、单词边界冲突的字符
        $name = (string) preg_replace('#[{}&<>"\'|`\s]+#u', '', (string) ($row['name'] ?? ''));

        // upload 字段存的是图片地址字符串，兼容取到数组的旧数据
        $img = is_array($row['img'] ?? null) ? (string) ($row['img']['url'] ?? '') : (string) ($row['img'] ?? '');
        $img = trim($img);

        // 同名表情会抢同一个 token，只保留第一条
        if ($name === '' || $img === '' || isset($seen[$name])) {
            continue;
        }

        $seen[$name] = true;

        $item = [
            'id'    => $name,
            'title' => trim((string) ($row['title'] ?? '')) ?: $name,
            'src'   => esc_url_raw($img),
        ];

        if (!empty($row['size'])) {
            $item['size'] = (int) $row['size'];
        }

        $items[] = $item;
    }

    return $items;
}
