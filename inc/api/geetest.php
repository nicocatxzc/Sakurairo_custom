<?php

/**
 * 极验行为验证第四代二次校验
 *
 * captcha_key 只用于在服务端生成 sign_token，不能下发到前端。
 *
 * @param array $params 客户端回传的 lot_number / captcha_output / pass_token / gen_time
 * @return bool 校验是否通过
 */
function iro_verify_geetest(array $params): bool
{
    $captchaId  = iro_opt('geetest_captcha_id');
    $captchaKey = iro_opt('geetest_captcha_key');

    // 请求参数可能来自 JSON body，非标量（数组等）一律当作缺失，避免类型转换告警
    $value = static fn(string $key): string => is_scalar($params[$key] ?? null)
        ? trim((string) $params[$key])
        : '';

    $lotNumber     = $value('lot_number');
    $captchaOutput = $value('captcha_output');
    $passToken     = $value('pass_token');
    $genTime       = $value('gen_time');

    if (!$captchaId || !$captchaKey || !$lotNumber || !$captchaOutput || !$passToken || !$genTime) {
        return false;
    }

    $response = wp_safe_remote_post(
        'https://gcaptcha4.geetest.com/validate?captcha_id=' . rawurlencode($captchaId),
        [
            'timeout' => 15,
            'body'    => [
                'lot_number'     => $lotNumber,
                'captcha_output' => $captchaOutput,
                'pass_token'     => $passToken,
                'gen_time'       => $genTime,
                // hash_hmac 默认输出小写十六进制，极验要求的 sign_token 正是 hex
                'sign_token'     => hash_hmac('sha256', $lotNumber, (string) $captchaKey),
            ],
        ]
    );

    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
        return false;
    }

    $result = json_decode(wp_remote_retrieve_body($response), true);

    return is_array($result) && ($result['result'] ?? '') === 'success';
}
