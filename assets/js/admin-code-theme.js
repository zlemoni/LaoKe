(function (window, document) {
    "use strict";

    var THEMES = {
        'default': { name: '默认深色', bg: '#2d2d2d', text: '#ccc', comment: '#999' },
        'tomorrow-night': { name: 'Tomorrow Night', bg: '#2d2d2d', text: '#ccc', comment: '#999' },
        'okaidia': { name: 'Okaidia', bg: '#272822', text: '#f8f8f2', comment: '#75715e' },
        'dracula': { name: 'Dracula', bg: '#282a36', text: '#f8f8f2', comment: '#6272a4' },
        'solarized-light': { name: 'Solarized Light', bg: '#fdf6e3', text: '#657b83', comment: '#93a1a1' },
        'github': { name: 'GitHub Light', bg: '#f8f8f8', text: '#333', comment: '#999' }
    };

    var STYLE_ID = 'laoke-admin-code-preview-dynamic';
    var currentTheme = 'default';

    function getThemeStyleElement() {
        var el = document.getElementById(STYLE_ID);
        if (!el) {
            el = document.createElement('style');
            el.id = STYLE_ID;
            document.head.appendChild(el);
        }
        return el;
    }

    function applyThemeCSS(theme) {
        var t = THEMES[theme] || THEMES['default'];
        var style = getThemeStyleElement();
        var css = [
            '#wmd-preview {',
            '  background-color: ' + t.bg + ' !important;',
            '  color: ' + t.text + ' !important;',
            '}',
            '#wmd-preview .comment,',
            '#wmd-preview .hljs-comment {',
            '  color: ' + t.comment + ' !important;',
            '}',
            '.typecho-list-table pre,',
            '.typecho-list-table code {',
            '  background: ' + t.bg + ' !important;',
            '  color: ' + t.text + ' !important;',
            '}'
        ].join('\n');
        style.textContent = css;
    }

    function updatePreview(theme) {
        applyThemeCSS(theme);
        var preview = document.querySelector('#wmd-preview');
        if (preview) {
            preview.style.backgroundColor = THEMES[theme].bg;
            preview.style.color = THEMES[theme].text;
        }
    }

    function initThemeSwitcher() {
        var select = document.querySelector('select[name="codeTheme"]');
        if (!select) return;

        var container = document.createElement('div');
        container.className = 'laoke-admin-code-theme-selector';
        container.style.cssText = 'margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;';

        var label = document.createElement('p');
        label.style.cssText = 'margin: 0 0 10px 0; font-weight: 600; color: #333;';
        label.textContent = '代码高亮主题（点击切换即时预览）';
        container.appendChild(label);

        var btnContainer = document.createElement('div');
        btnContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px;';
        container.appendChild(btnContainer);

        currentTheme = select.value;

        Object.keys(THEMES).forEach(function(key) {
            var theme = THEMES[key];
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'laoke-admin-code-theme-btn' + (key === currentTheme ? ' is-active' : '');
            btn.dataset.theme = key;
            btn.style.cssText = 'padding: 8px 14px; border: 2px solid ' + (key === currentTheme ? '#0969da' : 'transparent') + '; border-radius: 6px; background: ' + theme.bg + '; color: ' + theme.text + '; cursor: pointer; font-size: 12px; transition: all 0.2s ease;';
            btn.textContent = theme.name;
            btn.title = '点击应用 ' + theme.name;

            btn.addEventListener('click', function() {
                select.value = key;
                btnContainer.querySelectorAll('button').forEach(function(b) {
                    b.style.borderColor = 'transparent';
                });
                btn.style.borderColor = '#0969da';
                currentTheme = key;
                updatePreview(key);
            });

            btnContainer.appendChild(btn);
        });

        select.parentNode.insertBefore(container, select.nextSibling);

        select.addEventListener('change', function() {
            currentTheme = select.value;
            btnContainer.querySelectorAll('button').forEach(function(btn) {
                var key = btn.dataset.theme;
                btn.style.borderColor = (key === currentTheme) ? '#0969da' : 'transparent';
            });
            updatePreview(currentTheme);
        });

        updatePreview(currentTheme);
    }

    function init() {
        if (document.querySelector('.typecho-content')) {
            initThemeSwitcher();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window, document);
