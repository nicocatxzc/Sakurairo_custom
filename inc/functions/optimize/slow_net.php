<?php
if (iro_opt("iro_slow_net_optimize", true)) {
    header('Accept-CH: RTT, Save-Data, ECT, Downlink');
    $iro_is_slow_net = (function () {
        $rtt      = $_SERVER['HTTP_RTT'] ?? null;          // 延迟
        $saveData = $_SERVER['HTTP_SAVE_DATA'] ?? null;    // 节流模式
        $ect      = $_SERVER['HTTP_ECT'] ?? null;          // 网络环境
        $downlink = $_SERVER['HTTP_DOWNLINK'] ?? null;     // 下载速度

        if ($saveData === 'on') {
            return true;
        } elseif ($ect !== null && in_array($ect, ['slow-2g', '2g'], true)) {
            return true;
        } elseif ($rtt !== null && (int)$rtt > 200) {
            return true;
        } elseif ($downlink !== null && (float)$downlink < 1.5) {
            return true;
        }
        return false;
    })();

    global $iro_is_slow_net;

    if ($iro_is_slow_net) {
        global $iro_options;
        $iro_options["extra_fonts"] = [];
        $iro_options["frontend_particle"] = "off";
        $iro_options["particle_config"] = '';
        $iro_options["footer_hitokoto_select"] = 'off';
        $iro_options["footer_player_mode"] = 'off';
        $iro_options["post_card_image"] = 'only_feather_image';
        $iro_options["iro_image_quality"] = 70;
    }
}
