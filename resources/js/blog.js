/*
 * irnoti — blog article behaviour: syntax highlighting for code blocks and a
 * thin reading-progress bar. Loaded only on /blog pages.
 */
import hljs from 'highlight.js/lib/core';

import bash from 'highlight.js/lib/languages/bash';
import php from 'highlight.js/lib/languages/php';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import xml from 'highlight.js/lib/languages/xml';

hljs.registerLanguage('bash', bash);
hljs.registerLanguage('shell', bash);
hljs.registerLanguage('php', php);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('json', json);
hljs.registerLanguage('xml', xml);

function highlight() {
    document.querySelectorAll('.blog-prose pre code').forEach((el) => {
        if (!el.dataset.highlighted) {
            hljs.highlightElement(el);
        }
    });
}

function readingProgress() {
    const article = document.querySelector('.blog-post');
    const bar = document.querySelector('.blog-progress');
    if (!article || !bar) return;

    const update = () => {
        const rect = article.getBoundingClientRect();
        const total = rect.height - window.innerHeight;
        const passed = Math.min(Math.max(-rect.top, 0), Math.max(total, 0));
        bar.style.transform = `scaleX(${total > 0 ? passed / total : 0})`;
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
}

document.addEventListener('DOMContentLoaded', () => {
    highlight();
    readingProgress();
});
