<?php
/*
 * Gravatar头像使用中国服务器
 */
function gravatar_cn(string $url): string
{
    $gravatar_url = array('0.gravatar.com/avatar', '1.gravatar.com/avatar', '2.gravatar.com/avatar', 'secure.gravatar.com/avatar');
    if (iro_opt('gravatar_proxy') == 'custom_proxy_address_of_gravatar') {
        return str_replace($gravatar_url, iro_opt('custom_proxy_address_of_gravatar'), $url);
    } else {
        return str_replace($gravatar_url, iro_opt('gravatar_proxy'), $url);
    }
}
if (iro_opt('gravatar_proxy')) {
    add_filter('get_avatar_url', 'gravatar_cn', 4);
}
