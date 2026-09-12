<?php
//Fix register email bug </>
function new_user_message_fix($message)
{
    $show_register_ip = '注册IP | Registration IP: ' . get_the_user_ip() . ' (' . \Sakura\API\IpLocationParse::getIpLocationByIp(get_the_user_ip()) . ")\r\n\r\n如非本人操作请忽略此邮件 | Please ignore this email if this was not your operation.\r\n\r\n";
    $message = str_replace('To set your password, visit the following address:', $show_register_ip . '在此设置密码 | To set your password, visit the following address:', $message);
    $message = str_replace('<', '', $message);
    $message = str_replace('>', "\r\n\r\n设置密码后在此登录 | Login here after setting password: ", $message);
    return $message;
}
add_filter('wp_new_user_notification_email', 'new_user_message_fix');