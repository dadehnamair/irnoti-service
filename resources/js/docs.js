/*
 * irnoti — API docs page behaviour
 * Code-sample language tabs, copy-to-clipboard, mobile sidebar toggle and
 * syntax highlighting. Loaded only on /developers pages.
 */
import hljs from 'highlight.js/lib/core';

import bash from 'highlight.js/lib/languages/bash';
import php from 'highlight.js/lib/languages/php';
import javascript from 'highlight.js/lib/languages/javascript';
import python from 'highlight.js/lib/languages/python';
import csharp from 'highlight.js/lib/languages/csharp';
import java from 'highlight.js/lib/languages/java';
import json from 'highlight.js/lib/languages/json';
import xml from 'highlight.js/lib/languages/xml';

hljs.registerLanguage('bash', bash);
hljs.registerLanguage('curl', bash);
hljs.registerLanguage('shell', bash);
hljs.registerLanguage('php', php);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('python', python);
hljs.registerLanguage('csharp', csharp);
hljs.registerLanguage('java', java);
hljs.registerLanguage('json', json);
hljs.registerLanguage('xml', xml);

function highlightAll() {
    document.querySelectorAll('pre code[class*="language-"], .docs-prose pre code').forEach((el) => {
        if (!el.dataset.highlighted) {
            hljs.highlightElement(el);
        }
    });
}

function initCodeTabs() {
    document.querySelectorAll('[data-code-tabs]').forEach((group) => {
        const tabs = Array.from(group.querySelectorAll('.docs-code-tab'));
        const panels = Array.from(group.querySelectorAll('.docs-code-panel'));

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => {
                tabs.forEach((t) => {
                    t.classList.remove('is-active');
                    t.setAttribute('aria-selected', 'false');
                });
                panels.forEach((p) => {
                    p.classList.remove('is-active');
                    p.hidden = true;
                });

                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');
                if (panels[index]) {
                    panels[index].classList.add('is-active');
                    panels[index].hidden = false;
                }
            });
        });
    });
}

function initCopyButtons() {
    document.querySelectorAll('.docs-copy').forEach((button) => {
        button.addEventListener('click', async () => {
            let text = button.dataset.copy;

            if (!text && button.dataset.copyTarget) {
                const target = document.getElementById(button.dataset.copyTarget);
                text = target ? target.innerText : '';
            }

            if (!text) return;

            try {
                await navigator.clipboard.writeText(text);
                const original = button.textContent;
                button.textContent = 'کپی شد';
                button.classList.add('is-done');
                setTimeout(() => {
                    button.textContent = original;
                    button.classList.remove('is-done');
                }, 1600);
            } catch (e) {
                /* clipboard unavailable — no-op */
            }
        });
    });
}

function initSidebarToggle() {
    const toggle = document.querySelector('[data-docs-sidebar-toggle]');
    const sidebar = document.querySelector('[data-docs-sidebar]');
    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', () => {
        const open = sidebar.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    highlightAll();
    initCodeTabs();
    initCopyButtons();
    initSidebarToggle();
});
