<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/functions/comment/smiles_data.php';

/**
 * 评论表情面板数据
 * 根为表情包数组，包内 items 为具体表情，结构约定见 smiles_data.php
 */
function iro_rest_get_smiley_packs()
{
    return rest_ensure_response(iro_get_smiley_packs());
}
