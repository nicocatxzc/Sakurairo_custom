<?php
class IroCaptcha
{
    //验证码文字

    private string $captchaText = '';

    //验证码缓存有效期

    private const CACHE_TTL = 5 * MINUTE_IN_SECONDS;

    // WordPress Transient 前缀
    private const CACHE_PREFIX = 'sakura_captcha_';

    public function __construct()
    {
        $this->captchaText = '';
    }

    /**
     * 生成四位随机验证码
     *
     * 字符范围：
     * 0-9 + A-Z + a-z
     *
     * @return void
     */
    private function create_captcha(): void
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $length = strlen($characters);

        $captcha = '';

        for ($i = 0; $i < 4; $i++) {
            $captcha .= $characters[random_int(0, $length - 1)];
        }

        $this->captchaText = $captcha;
    }

    /**
     * 将验证码保存到 WordPress Transient
     *
     * @return string 验证码 ID
     */
    private function cache_captcha(): string
    {
        // 使用 UUID 作为不可预测的缓存 ID
        $id = wp_generate_uuid4();

        $cache_key = self::CACHE_PREFIX . $id;

        // 服务端保存正确答案，5 分钟后自动过期
        set_transient(
            $cache_key,
            $this->captchaText,
            self::CACHE_TTL
        );

        return $id;
    }

    /**
     * 删除验证码缓存
     *
     * @param string $id 验证码 ID
     *
     * @return void
     */
    private function destroy_captcha(string $id): void
    {
        delete_transient(self::CACHE_PREFIX . $id);
    }

    /**
     * 创建验证码图片
     *
     * @return array
     */
    public function create_captcha_img(): array
    {
        // 动态计算验证码难度
        $level = (float) iro_opt('iro_captcha_level') / 100;

        $conf = [
            'noise'   => (int) (700 + 500 * $level),
            'curves'  => (int) (8 + 6 * $level),
            'quality' => (int) (100 - 40 * $level),
        ];

        // 创建验证码
        $this->create_captcha();

        // 缓存验证码答案
        $id = $this->cache_captcha();

        // WordPress 主题字体
        $font = get_template_directory() . '/inc/KumoFont.ttf';

        // 创建画布
        $image = imagecreatetruecolor(210, 60);

        // 背景颜色
        $background = imagecolorallocate(
            $image,
            mt_rand(200, 255),
            mt_rand(200, 255),
            mt_rand(200, 255)
        );

        imagefill($image, 0, 0, $background);

        // 绘制文字
        $chars = str_split($this->captchaText);

        foreach ($chars as $i => $char) {
            $color = imagecolorallocate(
                $image,
                mt_rand(0, 150),
                mt_rand(0, 150),
                mt_rand(0, 150)
            );

            // 4 个字符平均分布
            $x = 20 + ($i * 46) + mt_rand(-3, 3);
            $y = 40 + mt_rand(-5, 5);

            // 字体大小
            $size = 26;

            // 轻微旋转
            $angle = mt_rand(-20, 20);

            imagettftext(
                $image,
                $size,
                $angle,
                $x,
                $y,
                $color,
                $font,
                $char
            );
        }

        // 添加噪点
        for ($i = 0; $i < $conf['noise']; $i++) {
            $color = imagecolorallocate(
                $image,
                mt_rand(0, 255),
                mt_rand(0, 255),
                mt_rand(0, 255)
            );

            imagesetpixel(
                $image,
                mt_rand(0, 209),
                mt_rand(0, 59),
                $color
            );
        }

        // 添加贝塞尔曲线
        for ($i = 0; $i < $conf['curves']; $i++) {
            $color = imagecolorallocate(
                $image,
                mt_rand(50, 150),
                mt_rand(50, 150),
                mt_rand(50, 150)
            );

            // 贝塞尔曲线控制点
            $x1 = mt_rand(0, 210);
            $x2 = mt_rand(0, 210);
            $cx1 = mt_rand(0, 210);
            $cx2 = mt_rand(0, 210);

            $y1 = mt_rand(0, 60);
            $y2 = mt_rand(0, 60);
            $cy1 = mt_rand(0, 60);
            $cy2 = mt_rand(0, 60);

            // 绘制三次贝塞尔曲线
            for ($t = 0; $t <= 1; $t += 0.01) {
                $xt = (int) (
                    (1 - $t) * (1 - $t) * (1 - $t) * $x1
                    + 3 * (1 - $t) * (1 - $t) * $t * $cx1
                    + 3 * (1 - $t) * $t * $t * $cx2
                    + $t * $t * $t * $x2
                );

                $yt = (int) (
                    (1 - $t) * (1 - $t) * (1 - $t) * $y1
                    + 3 * (1 - $t) * (1 - $t) * $t * $cy1
                    + 3 * (1 - $t) * $t * $t * $cy2
                    + $t * $t * $t * $y2
                );

                imagesetpixel($image, $xt, $yt, $color);
            }
        }

        // 高斯模糊
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);

        // 输出 JPEG
        ob_start();

        imagejpeg(
            $image,
            null,
            $conf['quality']
        );

        $captchaimg = ob_get_clean();

        // 释放图片资源
        imagedestroy($image);

        // 返回数据
        return [
            'stat' => true,
            'data' => 'data:image/jpeg;base64,' . base64_encode($captchaimg),
            'msg'  => '',
            'id'   => $id,
            'time' => time(),
        ];
    }

    /**
     * 检查验证码
     *
     * @param string $captcha 用户输入的验证码
     * @param string $id      验证码 ID
     *
     * @return array
     */
    public function check_captcha(string $captcha, string $id): array
    {
        // 基础格式检查
        if (
            !preg_match(
                '/^[0-9A-Za-z]{4}$/D',
                $captcha
            )
        ) {
            return [
                'stat' => false,
                'data' => '',
                'msg'  => __('Look like you forgot to enter the captcha.', 'sakurairo'),
            ];
        }

        // UUID 格式检查
        if (
            !preg_match(
                '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i',
                $id
            )
        ) {
            return [
                'stat' => false,
                'data' => '',
                'msg'  => __('Bad Request.', 'sakurairo'),
            ];
        }

        // 从 WordPress Transient 获取正确答案
        $expected = get_transient(self::CACHE_PREFIX . $id);

        // 不存在 = 已过期 / 已经被删除 / 无效 ID
        if ($expected === false) {
            return [
                'stat' => false,
                'data' => '',
                'msg'  => __('Captcha timeout.', 'sakurairo'),
            ];
        }

        // 验证验证码
        if (hash_equals((string) $expected, $captcha)) {
            // 验证成功后立即销毁，保证验证码只能使用一次
            $this->destroy_captcha($id);

            return [
                'stat' => true,
                'data' => '',
                'msg'  => __('Captcha check passed.', 'sakurairo'),
            ];
        }

        return [
            'stat' => false,
            'data' => '',
            'msg'  => __('Captcha incorrect.', 'sakurairo'),
        ];
    }
}
