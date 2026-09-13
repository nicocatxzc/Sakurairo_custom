import mediumZoom from 'medium-zoom';
import 'medium-zoom/dist/style.css';

// 全局存储当前 zoom 实例
let zoomInstance = null;

// 初始化灯箱
function initLightbox() {
    // 销毁旧实例
    if (zoomInstance) {
        zoomInstance.detach();
        zoomInstance = null;
    }

    // 查找所有符合条件的图片
    const container = document.querySelector('.post-content');
    if (!container) return;

    const images = [];
    const links = container.querySelectorAll('a');

    links.forEach((a) => {
        const img = a.querySelector('img');
        if (img) {
            images.push(img);
            // 阻止 a 标签的默认跳转行为
            a.addEventListener('click', (e) => {
                e.preventDefault();
            });
            // 可添加标记以便 CSS 或调试
            img.dataset.zoomable = '';
        }
    });

    // 初始化 medium-zoom
    if (images.length) {
        zoomInstance = mediumZoom(images, {
            background: 'rgba(0, 0, 0, 0.85)', // 深色背景
            margin: 24,
            scrollOffset: 0,
        });
    }
}

_iro.hooks.onPageLoaded(initLightbox)