<script>
    (function() {
        <?php
        // 首屏阶段标记独立挂在 window 上：主包可能晚于这些阶段下载完，
        // _iro.hooks 靠它补
        ?>
        let iroPerformance = window.iroPerformance = window.iroPerformance || {
            fcp: false,
            lcp: false
        };

        function mark(stage) {
            if (iroPerformance[stage]) {
                return;
            }

            iroPerformance[stage] = true;
            document.dispatchEvent(new CustomEvent("performance:" + stage));
        }

        function openStyle(id) {
            let style = document.getElementById(id);
            if (style && style.media !== "all") {
                style.media = "all";
            }
        }

        let connection = navigator.connection;
        let skipFonts = (connection && connection.saveData) ||
            matchMedia("(prefers-reduced-data: reduce)").matches;
        let gateFonts = window.innerWidth <= 860 && !skipFonts;
        try {
            if (window.innerWidth > 860) {
                openStyle("iro_deferred_bg");

                if (!skipFonts) {
                    openStyle("iro_extra_fonts");
                }

                mark("fcp");
                mark("lcp");
            } else {
                <?php
                // 首屏大图等首个绘制完成后再挂：在此之前不参与下载，避免进入首屏窗口
                ?>
                try {
                    let paintObserver = new PerformanceObserver(function(list) {
                        for (let entry of list.getEntries()) {
                            if (entry.name === "first-contentful-paint") {
                                paintObserver.disconnect();
                                mark("fcp");
                                requestAnimationFrame(function() {
                                    openStyle("iro_deferred_bg");
                                });
                                break;
                            }
                        }
                    });
                    paintObserver.observe({
                        type: "paint",
                        buffered: true
                    });
                } catch (e) {
                    window.addEventListener("load", function() {
                        mark("fcp");
                        requestAnimationFrame(function() {
                            openStyle("iro_deferred_bg");
                        });
                    }, {
                        once: true
                    });
                }
                <?php
                // 外链字体默认 media="not all" 不下载，等首屏最大内容绘制稳定后再下载
                // 桌面端立即加载
                // 省流永不加载
                ?>
                let settleTimer = 0;

                function settleLargestPaint() {
                    clearTimeout(settleTimer);
                    settleTimer = setTimeout(function() {
                        mark("lcp");
                        if (gateFonts) {
                            openStyle("iro_extra_fonts");
                        }
                    }, 600);
                }

                try {
                    new PerformanceObserver(settleLargestPaint).observe({
                        type: "largest-contentful-paint",
                        buffered: true
                    });
                } catch (e) {
                    window.addEventListener("load", settleLargestPaint);
                }

            }
        } catch (e) {
            console.log(e)
        }
        <?php
        // 观察器缺失或一直没有 LCP 候选时兜底，保证两个阶段一定产生
        ?>
        setTimeout(function() {
            mark("fcp");
            mark("lcp");
            openStyle("iro_deferred_bg");
            if (gateFonts) {
                openStyle("iro_extra_fonts");
            }
        }, 10000);
    })();
</script>