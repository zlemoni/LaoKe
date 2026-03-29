(function () {
    const state = {
        headerBound: false,
        themeBound: false,
        backTopBound: false,
        progressBound: false,
        linkLatencyTimer: null,
        commentEmojiBound: false,
        activeEmojiForm: null,
        momentLikePending: new Set(),
        albumUnlockPending: false,
        albumModalBound: false,
        shortcodeMp3Observer: null,
        barrageTimer: null,
        barrageIndex: 0,
        barrageVisibilityBound: false
    };

    window.LaoKeConfig = window.LaoKeConfig || {};

    function normalizeConfig(config) {
        return Object.assign({
            ajax: false,
            progress: false,
            lazyload: false,
            metingEndpoint: "https://api.injahow.cn/meting/",
            barrage: {
                enabled: false,
                desktopOnly: true,
                scope: "",
                opacity: 0.82,
                interval: 2400,
                items: []
            },
            fonts: {
                enabled: true,
                preset: "default",
                links: [],
                variables: {}
            }
        }, config || {});
    }

    function readPageConfig(root) {
        const scope = root && typeof root.querySelector === "function" ? root : document;
        const configNode = (scope && scope.id === "ajax-root"
            ? scope.querySelector("#laoke-page-config")
            : scope.querySelector("#laoke-page-config")) || document.getElementById("laoke-page-config");

        if (!configNode) {
            return null;
        }

        try {
            return JSON.parse(configNode.textContent || "{}");
        } catch (error) {
            return null;
        }
    }

    function hydrateConfig(root) {
        window.LaoKeConfig = normalizeConfig(readPageConfig(root) || window.LaoKeConfig);
        return window.LaoKeConfig;
    }

    function syncFontLinks(links) {
        const desired = Array.from(new Set((Array.isArray(links) ? links : []).map(function (href) {
            return String(href || "").trim();
        }).filter(Boolean)));
        const managed = Array.from(document.querySelectorAll('link[data-laoke-font-link="true"]'));

        managed.forEach(function (node) {
            const absoluteHref = node.href;
            const keep = desired.some(function (href) {
                try {
                    return new URL(href, window.location.href).href === absoluteHref;
                } catch (error) {
                    return false;
                }
            });

            if (!keep) {
                node.remove();
            }
        });

        desired.forEach(function (href) {
            let absoluteHref = href;
            try {
                absoluteHref = new URL(href, window.location.href).href;
            } catch (error) {}

            const exists = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).some(function (node) {
                return node.href === absoluteHref;
            });

            if (exists) {
                return;
            }

            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = href;
            link.setAttribute("data-laoke-font-link", "true");
            document.head.appendChild(link);
        });
    }

    function initFonts() {
        const fonts = window.LaoKeConfig && window.LaoKeConfig.fonts ? window.LaoKeConfig.fonts : null;
        const variables = fonts && fonts.variables ? fonts.variables : null;
        if (!variables) {
            return;
        }

        const root = document.documentElement;
        if (variables.body) {
            root.style.setProperty("--font-body", String(variables.body));
        }
        if (variables.title) {
            root.style.setProperty("--font-title", String(variables.title));
        }
        if (variables.ui) {
            root.style.setProperty("--font-ui", String(variables.ui));
        }

        syncFontLinks(fonts.links || []);
    }

    function isPageRoot(root) {
        return root === document
            || root === document.body
            || root === document.documentElement
            || !!(root && root.id === "ajax-root")
            || !!(root && typeof root.querySelector === "function" && root.querySelector("#ajax-root"));
    }

    function escapeHtml(value) {
        return String(value || "").replace(/[&<>"']/g, function (char) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            }[char];
        });
    }

    function getPreferredTheme() {
        const stored = window.localStorage.getItem("laoke-theme");
        if (stored === "light" || stored === "dark") {
            return stored;
        }
        return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute("data-theme", theme);

        const nextLabel = theme === "dark" ? "切换到日间模式" : "切换到夜间模式";
        document.querySelectorAll("[data-theme-toggle]").forEach(function (button) {
            button.setAttribute("aria-label", nextLabel);
            button.setAttribute("title", nextLabel);
            button.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
        });
    }

    function initThemeToggle() {
        applyTheme(getPreferredTheme());

        document.querySelectorAll("[data-theme-toggle]").forEach(function (button) {
            if (button.dataset.themeBound === "true") {
                return;
            }

            button.dataset.themeBound = "true";
            button.addEventListener("click", function () {
                const current = document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light";
                const next = current === "dark" ? "light" : "dark";
                window.localStorage.setItem("laoke-theme", next);
                applyTheme(next);
            });
        });

        if (state.themeBound) {
            return;
        }

        const media = window.matchMedia("(prefers-color-scheme: dark)");
        const handleChange = function () {
            if (!window.localStorage.getItem("laoke-theme")) {
                applyTheme(getPreferredTheme());
            }
        };

        if (typeof media.addEventListener === "function") {
            media.addEventListener("change", handleChange);
        } else if (typeof media.addListener === "function") {
            media.addListener(handleChange);
        }

        state.themeBound = true;
    }

    const codeThemeMap = {
        'default': 'prism.css',
        'tomorrow-night': 'prism-tomorrow-night.css',
        'okaidia': 'prism-okaidia.css',
        'dracula': 'prism-dracula.css',
        'solarized-light': 'prism-solarized-light.css',
        'github': 'prism-github.css'
    };

    function getCodeThemeFromStorage() {
        return window.localStorage.getItem('laoke-code-theme') || '';
    }

    function setCodeThemeToStorage(theme) {
        window.localStorage.setItem('laoke-code-theme', theme);
    }

    function applyCodeTheme(themeKey) {
        const themeFile = codeThemeMap[themeKey] || codeThemeMap['default'];
        const existingLink = document.getElementById('laoke-code-theme');
        if (!existingLink) {
            return;
        }
        const newLink = document.createElement('link');
        newLink.rel = 'stylesheet';
        newLink.href = existingLink.href.replace(/\/css\/vendor\/[^/]+$/, '/css/vendor/' + themeFile);
        newLink.id = 'laoke-code-theme';
        newLink.addEventListener('load', function() {
            existingLink.remove();
        });
        document.head.appendChild(newLink);
    }

    function initCodeThemeSwitch() {
        const panel = document.getElementById('laoke-code-theme-panel');
        const toggleBtn = document.getElementById('laoke-code-theme-toggle');
        if (!panel) {
            return;
        }

        const storedTheme = getCodeThemeFromStorage();
        if (storedTheme && codeThemeMap[storedTheme]) {
            applyCodeTheme(storedTheme);
        }

        const currentTheme = getCodeThemeFromStorage() || 'default';
        panel.querySelectorAll('.laoke-code-theme-btn').forEach(function(btn) {
            if (btn.dataset.codeTheme === currentTheme) {
                btn.classList.add('is-active');
            }
            btn.addEventListener('click', function() {
                const theme = this.dataset.codeTheme;
                setCodeThemeToStorage(theme);
                applyCodeTheme(theme);
                panel.querySelectorAll('.laoke-code-theme-btn').forEach(function(b) {
                    b.classList.remove('is-active');
                });
                this.classList.add('is-active');
            });
        });

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                panel.classList.toggle('is-visible');
            });
        }

        document.addEventListener('click', function(e) {
            if (!panel.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
                panel.classList.remove('is-visible');
            }
        });
    }

    function initHeaderTools() {
        const nav = document.getElementById("nav");
        const navToggle = document.getElementById("nav-toggle");

        if (!nav || !navToggle) {
            return;
        }

        if (state.headerBound) {
            nav.classList.remove("is-open");
            navToggle.setAttribute("aria-expanded", "false");
            return;
        }

        navToggle.addEventListener("click", function () {
            const open = nav.classList.toggle("is-open");
            navToggle.setAttribute("aria-expanded", open ? "true" : "false");
        });

        nav.addEventListener("click", function (event) {
            const target = event.target.closest("a");
            if (!target) {
                return;
            }
            nav.classList.remove("is-open");
            navToggle.setAttribute("aria-expanded", "false");
        });

        state.headerBound = true;
    }

    function smoothScrollTo(targetY) {
        const startY = window.scrollY;
        const distance = targetY - startY;
        const duration = 300;
        const start = performance.now();

        function frame(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            window.scrollTo(0, startY + distance * eased);
            if (progress < 1) {
                window.requestAnimationFrame(frame);
            }
        }

        window.requestAnimationFrame(frame);
    }

    function initSmoothAnchors(root) {
        root.querySelectorAll('a[href*="#"]').forEach(function (link) {
            if (link.dataset.smoothBound === "true") {
                return;
            }

            const url = new URL(link.href, window.location.href);
            if (url.pathname !== window.location.pathname || !url.hash) {
                return;
            }

            link.dataset.smoothBound = "true";
            link.addEventListener("click", function (event) {
                const target = document.querySelector(url.hash);
                if (!target) {
                    return;
                }
                event.preventDefault();
                smoothScrollTo(target.getBoundingClientRect().top + window.scrollY - 24);
                history.replaceState(history.state, "", url.hash);
            });
        });
    }

    function initBackToTop() {
        if (state.backTopBound) {
            toggleBackTop();
            return;
        }

        const button = document.getElementById("back-to-top");
        if (!button) {
            return;
        }

        button.addEventListener("click", function () {
            smoothScrollTo(0);
        });

        window.addEventListener("scroll", toggleBackTop, { passive: true });
        toggleBackTop();
        state.backTopBound = true;
    }

    function toggleBackTop() {
        const button = document.getElementById("back-to-top");
        if (!button) {
            return;
        }
        button.classList.toggle("is-visible", window.scrollY > 300);
    }

    function initLazyImages(root) {
        const images = root.querySelectorAll("img[data-src]");
        if (!images.length) {
            return;
        }

        if (!("IntersectionObserver" in window) || !window.LaoKeConfig.lazyload) {
            images.forEach(loadImage);
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                loadImage(entry.target);
                observer.unobserve(entry.target);
            });
        }, { rootMargin: "150px 0px" });

        images.forEach(function (image) {
            observer.observe(image);
        });
    }

    function loadImage(image) {
        const source = image.dataset.src;
        if (!source) {
            return;
        }
        image.src = source;
        image.removeAttribute("data-src");
        image.addEventListener("load", function () {
            image.classList.add("is-loaded");
        }, { once: true });
    }

    function formatCountValue(value, suffix) {
        return Math.round(value).toLocaleString("zh-CN") + (suffix || "");
    }

    function animateCount(element) {
        if (!element || element.dataset.countAnimated === "true") {
            return;
        }

        const target = Number(element.dataset.countup || 0);
        const suffix = element.dataset.suffix || "";
        const duration = 900;
        const start = performance.now();

        element.dataset.countAnimated = "true";

        const step = function (now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;
            element.textContent = formatCountValue(current, suffix);

            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                element.textContent = formatCountValue(target, suffix);
                element.classList.add("is-counted");
            }
        };

        window.requestAnimationFrame(step);
    }

    function initCountUp(root) {
        const numbers = root.querySelectorAll("[data-countup]");
        if (!numbers.length) {
            return;
        }

        if (!("IntersectionObserver" in window)) {
            numbers.forEach(animateCount);
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                animateCount(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.35 });

        numbers.forEach(function (item) {
            if (item.dataset.countAnimated === "true") {
                return;
            }
            observer.observe(item);
        });
    }

    function initProgress() {
        const bar = document.getElementById("reading-progress");
        if (!bar) {
            return;
        }

        const update = function () {
            const content = document.querySelector(".post-content");
            if (!window.LaoKeConfig.progress || !content) {
                bar.style.width = "0";
                return;
            }

            const rect = content.getBoundingClientRect();
            const total = content.offsetHeight - window.innerHeight;
            if (total <= 0) {
                bar.style.width = "100%";
                return;
            }

            const passed = Math.min(Math.max(-rect.top, 0), total);
            bar.style.width = (passed / total) * 100 + "%";
        };

        update();
        if (!state.progressBound) {
            window.addEventListener("scroll", update, { passive: true });
            state.progressBound = true;
        }
    }

    function getBarrageConfig() {
        const config = window.LaoKeConfig && window.LaoKeConfig.barrage ? window.LaoKeConfig.barrage : null;
        if (!config || !config.enabled || !Array.isArray(config.items) || !config.items.length) {
            return null;
        }

        if (config.desktopOnly && window.matchMedia("(max-width: 720px)").matches) {
            return null;
        }

        return config;
    }

    function getBarrageRoot() {
        return document.getElementById("laoke-barrage");
    }

    function stopBarrageLoop() {
        if (state.barrageTimer) {
            window.clearInterval(state.barrageTimer);
            state.barrageTimer = null;
        }
    }

    function destroyBarrage(resetIndex) {
        const root = getBarrageRoot();

        stopBarrageLoop();
        if (root) {
            root.innerHTML = "";
            root.setAttribute("aria-hidden", "true");
            root.classList.remove("is-active");
        }

        if (resetIndex !== false) {
            state.barrageIndex = 0;
        }
    }

    function barrageLaneTop() {
        const header = document.querySelector(".site-header");
        const headerHeight = header ? header.offsetHeight : 72;
        const base = Math.max(84, headerHeight + 22);
        const laneHeight = 62;
        const available = Math.max(window.innerHeight - base - 140, laneHeight * 3);
        const lanes = Math.max(3, Math.min(6, Math.floor(available / laneHeight)));
        const lane = Math.floor(Math.random() * lanes);
        return base + lane * laneHeight;
    }

    function createBarrageItem(config, item) {
        const node = document.createElement("div");
        const meta = [escapeHtml(item.author || "匿名访客")];
        const title = String(item.title || "").trim();
        const opacity = Math.max(0.35, Math.min(0.96, Number(config.opacity || 0.82)));

        if (title) {
            meta.push(escapeHtml(title));
        } else if (config.scope === "post") {
            meta.push("当前文章");
        }

        node.className = "laoke-barrage__item";
        node.style.top = barrageLaneTop() + "px";
        node.style.setProperty("--laoke-barrage-duration", (14 + Math.random() * 5) + "s");
        node.style.setProperty("--laoke-barrage-opacity", Math.round(opacity * 100) + "%");
        node.innerHTML = [
            '<span class="laoke-barrage__avatar">',
            '<img src="' + escapeHtml(item.avatar || "") + '" alt="" loading="lazy" decoding="async">',
            "</span>",
            '<span class="laoke-barrage__body">',
            '<span class="laoke-barrage__meta">' + meta.join(" / ") + "</span>",
            '<span class="laoke-barrage__text">' + String(item.contentHtml || "") + "</span>",
            "</span>"
        ].join("");

        node.addEventListener("animationend", function () {
            node.remove();
        });

        return node;
    }

    function pushBarrage(config) {
        const root = getBarrageRoot();
        if (!root || document.hidden) {
            return;
        }

        const item = config.items[state.barrageIndex % config.items.length];
        state.barrageIndex += 1;
        if (!item) {
            return;
        }

        root.classList.add("is-active");
        root.setAttribute("aria-hidden", "false");
        root.appendChild(createBarrageItem(config, item));
    }

    function bindBarrageViewportEvents() {
        if (state.barrageVisibilityBound) {
            return;
        }

        document.addEventListener("visibilitychange", function () {
            if (document.hidden) {
                stopBarrageLoop();
                return;
            }

            initBarrage(document);
        });

        window.addEventListener("resize", function () {
            initBarrage(document);
        });

        state.barrageVisibilityBound = true;
    }

    function initBarrage(root) {
        if (!isPageRoot(root)) {
            return;
        }

        const config = getBarrageConfig();
        bindBarrageViewportEvents();
        destroyBarrage();

        if (!config) {
            return;
        }

        pushBarrage(config);
        stopBarrageLoop();
        state.barrageTimer = window.setInterval(function () {
            pushBarrage(config);
        }, Math.max(1600, Number(config.interval || 2400)));
    }

    function initToc() {
        const toc = document.getElementById("post-toc");
        const content = document.querySelector(".post-content");
        if (!toc || !content) {
            return;
        }

        const threshold = Number(document.getElementById("ajax-root")?.dataset.tocThreshold || 1500);
        if ((content.innerText || "").trim().length < threshold) {
            toc.innerHTML = "";
            toc.classList.remove("is-visible");
            return;
        }

        const headings = Array.from(content.querySelectorAll("h2, h3, h4"));
        if (headings.length < 3) {
            toc.innerHTML = "";
            toc.classList.remove("is-visible");
            return;
        }

        const list = document.createElement("ol");
        headings.forEach(function (heading, index) {
            if (!heading.id) {
                heading.id = "heading-" + (index + 1);
            }
            const item = document.createElement("li");
            const link = document.createElement("a");
            link.href = "#" + heading.id;
            link.textContent = heading.textContent || ("章节 " + (index + 1));
            item.appendChild(link);
            list.appendChild(item);
        });

        toc.innerHTML = '<div class="post-toc__inner"><p class="post-toc__title">目录</p></div>';
        toc.querySelector(".post-toc__inner").appendChild(list);
        toc.classList.add("is-visible");
        initSmoothAnchors(toc);

        const links = Array.from(toc.querySelectorAll("a"));
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                links.forEach(function (link) {
                    link.classList.toggle("is-active", link.getAttribute("href") === "#" + entry.target.id);
                });
            });
        }, { rootMargin: "-20% 0px -65% 0px", threshold: 0.1 });

        headings.forEach(function (heading) {
            observer.observe(heading);
        });
    }

    function initCopyButtons(root) {
        root.querySelectorAll("[data-copy-link]").forEach(function (button) {
            if (button.dataset.copyBound === "true") {
                return;
            }

            button.dataset.copyBound = "true";
            button.addEventListener("click", async function () {
                const url = button.getAttribute("data-copy-link");
                try {
                    await navigator.clipboard.writeText(url);
                    button.textContent = "已复制";
                    setTimeout(function () {
                        button.textContent = "复制链接";
                    }, 1200);
                } catch (error) {
                    button.textContent = "复制失败";
                }
            });
        });
    }

    function insertTextAtCursor(field, text) {
        if (!field) {
            return;
        }

        const start = typeof field.selectionStart === "number" ? field.selectionStart : field.value.length;
        const end = typeof field.selectionEnd === "number" ? field.selectionEnd : field.value.length;
        const value = field.value || "";
        const before = value.slice(0, start);
        const after = value.slice(end);
        const insert = (before !== "" && !/\s$/.test(before) ? " " : "") + text + (after !== "" && !/^\s/.test(after) ? " " : "");
        const caret = before.length + insert.length;

        field.value = before + insert + after;
        field.focus();

        if (typeof field.setSelectionRange === "function") {
            field.setSelectionRange(caret, caret);
        }

        field.dispatchEvent(new Event("input", { bubbles: true }));
    }

    function closeCommentEmoji(form) {
        const targetForm = form || state.activeEmojiForm;
        if (!targetForm) {
            return;
        }

        const toggle = targetForm.querySelector("[data-owo-toggle]");
        const panel = targetForm.querySelector("[data-owo-panel]");
        if (!toggle || !panel) {
            if (state.activeEmojiForm === targetForm) {
                state.activeEmojiForm = null;
            }
            return;
        }

        toggle.setAttribute("aria-expanded", "false");
        panel.setAttribute("aria-hidden", "true");
        panel.classList.remove("is-open");

        if (state.activeEmojiForm === targetForm) {
            state.activeEmojiForm = null;
        }
    }

    function initCommentEmoji(root) {
        const form = root.querySelector("#comment-form");
        if (!form) {
            if (state.activeEmojiForm && !document.body.contains(state.activeEmojiForm)) {
                state.activeEmojiForm = null;
            }
            return;
        }

        const toggle = form.querySelector("[data-owo-toggle]");
        const panel = form.querySelector("[data-owo-panel]");
        const textarea = form.querySelector('textarea[name="text"]');

        if (!toggle || !panel || !textarea) {
            return;
        }

        if (!state.commentEmojiBound) {
            document.addEventListener("click", function (event) {
                const activeForm = state.activeEmojiForm;
                if (!activeForm) {
                    return;
                }

                if (!document.body.contains(activeForm)) {
                    state.activeEmojiForm = null;
                    return;
                }

                if (activeForm.contains(event.target)) {
                    return;
                }

                closeCommentEmoji(activeForm);
            });

            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape") {
                    closeCommentEmoji();
                }
            });

            state.commentEmojiBound = true;
        }

        if (form.dataset.owoBound === "true") {
            return;
        }

        form.dataset.owoBound = "true";

        toggle.addEventListener("click", function () {
            const nextOpen = !panel.classList.contains("is-open");

            if (state.activeEmojiForm && state.activeEmojiForm !== form) {
                closeCommentEmoji(state.activeEmojiForm);
            }

            if (!nextOpen) {
                closeCommentEmoji(form);
                return;
            }

            state.activeEmojiForm = form;
            toggle.setAttribute("aria-expanded", "true");
            panel.setAttribute("aria-hidden", "false");
            panel.classList.add("is-open");
        });

        panel.addEventListener("click", function (event) {
            const item = event.target.closest("[data-owo-name]");
            if (!item) {
                return;
            }

            event.preventDefault();
            insertTextAtCursor(textarea, "[owo:" + item.getAttribute("data-owo-name") + "]");
        });

        form.addEventListener("submit", function () {
            closeCommentEmoji(form);
        });
    }

    async function probeLinkLatency(url) {
        const start = performance.now();
        const controller = typeof AbortController === "function" ? new AbortController() : null;
        const timeout = window.setTimeout(function () {
            if (controller) {
                controller.abort();
            }
        }, 8000);

        try {
            await fetch(url, {
                method: "GET",
                mode: "no-cors",
                cache: "no-store",
                redirect: "follow",
                signal: controller ? controller.signal : undefined
            });

            return {
                ok: true,
                latency: Math.round(performance.now() - start)
            };
        } catch (error) {
            return {
                ok: false
            };
        } finally {
            window.clearTimeout(timeout);
        }
    }

    function paintLatencyBadge(badge, result) {
        if (!badge) {
            return;
        }

        badge.classList.remove("is-fast", "is-slow", "is-checking", "is-error");

        if (!result.ok) {
            badge.textContent = "超时";
            badge.classList.add("is-error");
            return;
        }

        badge.textContent = result.latency + "ms";
        badge.classList.add(result.latency <= 500 ? "is-fast" : "is-slow");
    }

    async function refreshLinkLatency(root) {
        const cards = Array.from(root.querySelectorAll("[data-link-latency]"));
        if (!cards.length) {
            if (state.linkLatencyTimer) {
                window.clearInterval(state.linkLatencyTimer);
                state.linkLatencyTimer = null;
            }
            return;
        }

        await Promise.all(cards.map(async function (card) {
            const badge = card.querySelector("[data-latency-badge]");
            const url = card.getAttribute("data-link-latency");
            if (!badge || !url) {
                return;
            }

            badge.textContent = "检测中";
            badge.classList.remove("is-fast", "is-slow", "is-error");
            badge.classList.add("is-checking");

            const result = await probeLinkLatency(url);
            paintLatencyBadge(badge, result);
        }));
    }

    function initLinkLatency(root) {
        const cards = root.querySelectorAll("[data-link-latency]");
        if (!cards.length) {
            if (state.linkLatencyTimer) {
                window.clearInterval(state.linkLatencyTimer);
                state.linkLatencyTimer = null;
            }
            return;
        }

        refreshLinkLatency(root);

        if (state.linkLatencyTimer) {
            return;
        }

        state.linkLatencyTimer = window.setInterval(function () {
            refreshLinkLatency(document);
        }, 300000);
    }

    function setMomentLikeState(button, liked, likes) {
        if (!button) {
            return;
        }

        const pressed = liked ? "true" : "false";
        const count = button.querySelector("[data-moment-like-count]");

        button.setAttribute("aria-pressed", pressed);
        button.setAttribute("aria-label", liked ? "取消点赞" : "点赞");
        button.classList.toggle("is-liked", !!liked);

        if (count) {
            count.textContent = Number(likes || 0).toLocaleString("zh-CN");
        }
    }

    function showMomentFeedback(root, message, tone) {
        const feedback = root.querySelector("#moment-feedback");
        if (!feedback) {
            return;
        }

        const pill = feedback.querySelector("[data-moment-feedback-pill]");
        const target = pill || feedback;
        target.textContent = message || "";
        feedback.classList.remove("is-visible", "is-success", "is-error");

        if (!message) {
            return;
        }

        if (tone === "success") {
            feedback.classList.add("is-success");
        } else if (tone === "error") {
            feedback.classList.add("is-error");
        }

        feedback.classList.add("is-visible");
        window.clearTimeout(feedback._clearTimer);
        feedback._clearTimer = window.setTimeout(function () {
            target.textContent = "";
            feedback.classList.remove("is-visible", "is-success", "is-error");
        }, 2200);
    }

    function initMomentLikes(root) {
        const container = root.matches && root.matches(".comments-area.is-time-machine")
            ? root
            : root.querySelector(".comments-area.is-time-machine");

        if (!container) {
            return;
        }

        container.querySelectorAll("[data-moment-like]").forEach(function (button) {
            if (button.dataset.likeBound === "true") {
                return;
            }

            button.dataset.likeBound = "true";
            button.addEventListener("click", async function () {
                const coid = button.getAttribute("data-coid");
                const endpoint = container.getAttribute("data-moment-like-endpoint") || window.location.href;

                if (!coid || state.momentLikePending.has(coid)) {
                    return;
                }

                const liked = button.getAttribute("aria-pressed") === "true";
                const payload = new URLSearchParams();
                payload.set("laoke_action", "moment_like");
                payload.set("coid", coid);
                payload.set("toggle", liked ? "unlike" : "like");

                state.momentLikePending.add(coid);
                button.disabled = true;
                button.classList.add("is-loading");
                showMomentFeedback(container, "");

                try {
                    const response = await fetch(endpoint, {
                        method: "POST",
                        body: payload.toString(),
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        credentials: "same-origin"
                    });
                    const result = await response.json();

                    if (!response.ok || !result || !result.ok) {
                        throw new Error(result && result.message ? result.message : "点赞失败，请稍后重试。");
                    }

                    setMomentLikeState(button, !!result.liked, Number(result.likes || 0));
                    showMomentFeedback(container, result.liked ? "点赞成功" : "已取消点赞", "success");
                } catch (error) {
                    showMomentFeedback(container, error && error.message ? error.message : "点赞失败，请稍后重试。", "error");
                } finally {
                    button.disabled = false;
                    button.classList.remove("is-loading");
                    state.momentLikePending.delete(coid);
                }
            });
        });
    }

    function findAlbumPage(root) {
        if (root.matches && root.matches("[data-album-page]")) {
            return root;
        }

        return root.querySelector("[data-album-page]");
    }

    function findAlbumTemplate(page, key) {
        return Array.from(page.querySelectorAll("template[data-album-template]")).find(function (template) {
            return template.getAttribute("data-album-template") === key;
        }) || null;
    }

    function ensureAlbumTemplate(page, key, html) {
        if (!page || !key || !html) {
            return null;
        }

        let template = findAlbumTemplate(page, key);
        const container = page.querySelector(".albums-templates");
        if (!container) {
            return null;
        }

        if (!template) {
            template = document.createElement("template");
            template.setAttribute("data-album-template", key);
            container.appendChild(template);
        }

        template.innerHTML = html;
        return template;
    }

    function setAlbumCardUnlocked(page, key) {
        const card = page.querySelector('[data-album-card][data-album-key="' + key + '"]');
        if (!card) {
            return;
        }

        card.setAttribute("data-album-locked", "false");
        card.classList.add("is-unlocked");

        const badge = card.querySelector(".album-card__badge");
        if (badge) {
            badge.textContent = "已解锁";
        }
    }

    function openAlbumDetail(page, key, html) {
        const shell = page.querySelector("[data-album-shell]");
        const grid = page.querySelector("[data-album-grid]");
        const detail = page.querySelector("[data-album-detail]");
        const detailBody = page.querySelector("[data-album-detail-body]");

        if (!shell || !grid || !detail || !detailBody) {
            return;
        }

        let templateHtml = html || "";
        if (!templateHtml) {
            const template = findAlbumTemplate(page, key);
            templateHtml = template ? template.innerHTML : "";
        }

        if (!templateHtml) {
            return;
        }

        detailBody.innerHTML = templateHtml;
        page.setAttribute("data-active-album-key", key);
        grid.hidden = true;
        detail.hidden = false;
        shell.classList.remove("is-list");
        shell.classList.add("is-detail");

        if (window.LaoKe) {
            window.LaoKe.initPage(page);
        }

        smoothScrollTo(page.getBoundingClientRect().top + window.scrollY - 24);
    }

    function closeAlbumDetail(page) {
        const shell = page.querySelector("[data-album-shell]");
        const grid = page.querySelector("[data-album-grid]");
        const detail = page.querySelector("[data-album-detail]");
        const detailBody = page.querySelector("[data-album-detail-body]");

        if (!shell || !grid || !detail || !detailBody) {
            return;
        }

        page.removeAttribute("data-active-album-key");
        detail.hidden = true;
        grid.hidden = false;
        detailBody.innerHTML = "";
        shell.classList.remove("is-detail");
        shell.classList.add("is-list");
        smoothScrollTo(page.getBoundingClientRect().top + window.scrollY - 24);
    }

    function closeAlbumUnlockModal(page) {
        const modal = page.querySelector("[data-album-unlock-modal]");
        const form = page.querySelector("[data-album-unlock-form]");
        const feedback = page.querySelector("[data-album-unlock-feedback]");
        const title = page.querySelector("[data-album-unlock-title]");

        if (!modal || !form) {
            return;
        }

        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        form.reset();
        form.removeAttribute("data-album-key");

        if (feedback) {
            feedback.textContent = "";
        }

        if (title) {
            title.textContent = "输入密码";
        }
    }

    function openAlbumUnlockModal(page, key, title) {
        const modal = page.querySelector("[data-album-unlock-modal]");
        const form = page.querySelector("[data-album-unlock-form]");
        const hiddenKey = page.querySelector("[data-album-unlock-key]");
        const input = page.querySelector("[data-album-unlock-input]");
        const heading = page.querySelector("[data-album-unlock-title]");
        const feedback = page.querySelector("[data-album-unlock-feedback]");

        if (!modal || !form || !hiddenKey || !input) {
            return;
        }

        form.setAttribute("data-album-key", key);
        hiddenKey.value = key;
        if (heading) {
            heading.textContent = title ? "解锁「" + title + "」" : "输入密码";
        }
        if (feedback) {
            feedback.textContent = "";
        }

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        window.setTimeout(function () {
            input.focus();
        }, 30);
    }

    function initAlbumPage(root) {
        const page = findAlbumPage(root);
        if (!page) {
            return;
        }

        if (!state.albumModalBound) {
            document.addEventListener("keydown", function (event) {
                if (event.key !== "Escape") {
                    return;
                }

                const openModal = document.querySelector("[data-album-unlock-modal].is-open");
                if (!openModal) {
                    return;
                }

                const host = openModal.closest("[data-album-page]");
                if (host) {
                    closeAlbumUnlockModal(host);
                }
            });

            state.albumModalBound = true;
        }

        if (page.dataset.albumBound === "true") {
            return;
        }

        page.dataset.albumBound = "true";

        page.addEventListener("click", function (event) {
            const closeTrigger = event.target.closest("[data-album-unlock-close]");
            if (closeTrigger) {
                closeAlbumUnlockModal(page);
                return;
            }

            const modal = page.querySelector("[data-album-unlock-modal]");
            if (modal && event.target === modal) {
                closeAlbumUnlockModal(page);
                return;
            }

            const back = event.target.closest("[data-album-back]");
            if (back) {
                closeAlbumDetail(page);
                return;
            }

            const card = event.target.closest("[data-album-card]");
            if (!card) {
                return;
            }

            const key = card.getAttribute("data-album-key");
            if (!key) {
                return;
            }

            const isLocked = card.getAttribute("data-album-locked") === "true";
            if (isLocked) {
                const title = card.querySelector(".album-card__title");
                openAlbumUnlockModal(page, key, title ? title.textContent.trim() : "");
                return;
            }

            openAlbumDetail(page, key);
        });

        const form = page.querySelector("[data-album-unlock-form]");
        if (!form) {
            return;
        }

        form.addEventListener("submit", async function (event) {
            event.preventDefault();

            if (state.albumUnlockPending) {
                return;
            }

            const albumKey = form.getAttribute("data-album-key") || "";
            const input = page.querySelector("[data-album-unlock-input]");
            const feedback = page.querySelector("[data-album-unlock-feedback]");
            const submit = form.querySelector('button[type="submit"]');
            const endpoint = page.getAttribute("data-album-endpoint") || window.location.href;
            const cid = page.getAttribute("data-album-cid") || "";
            const password = input ? input.value.trim() : "";

            if (!albumKey || !password) {
                if (feedback) {
                    feedback.textContent = "请输入访问密码。";
                }
                if (input) {
                    input.focus();
                }
                return;
            }

            const payload = new URLSearchParams();
            payload.set("laoke_action", "unlock_album");
            payload.set("cid", cid);
            payload.set("album_key", albumKey);
            payload.set("password", password);

            state.albumUnlockPending = true;
            if (submit) {
                submit.disabled = true;
            }
            if (feedback) {
                feedback.textContent = "正在解锁...";
            }

            try {
                const response = await fetch(endpoint, {
                    method: "POST",
                    body: payload.toString(),
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    credentials: "same-origin"
                });
                const result = await response.json();

                if (!response.ok || !result || !result.ok || !result.html) {
                    throw new Error(result && result.message ? result.message : "解锁失败，请稍后重试。");
                }

                ensureAlbumTemplate(page, result.albumKey || albumKey, result.html);
                setAlbumCardUnlocked(page, result.albumKey || albumKey);
                closeAlbumUnlockModal(page);
                openAlbumDetail(page, result.albumKey || albumKey, result.html);
            } catch (error) {
                if (feedback) {
                    feedback.textContent = error && error.message ? error.message : "解锁失败，请稍后重试。";
                }
            } finally {
                state.albumUnlockPending = false;
                if (submit) {
                    submit.disabled = false;
                }
            }
        });
    }

    function initInlineAlbums(root) {
        const forms = Array.from(root.querySelectorAll("[data-inline-album-form]"));
        forms.forEach(function (form) {
            if (form.dataset.inlineAlbumBound === "true") {
                return;
            }

            form.dataset.inlineAlbumBound = "true";
            form.addEventListener("submit", async function (event) {
                event.preventDefault();

                if (form.dataset.pending === "true") {
                    return;
                }

                const host = form.closest("[data-inline-album]");
                const input = form.querySelector('input[name="password"]');
                const feedback = form.querySelector("[data-inline-album-feedback]");
                const submit = form.querySelector('button[type="submit"]');
                const endpoint = form.getAttribute("data-album-endpoint") || window.location.href;
                const cid = form.getAttribute("data-album-cid") || "";
                const albumKey = form.getAttribute("data-album-key") || "";
                const password = input ? input.value.trim() : "";

                if (!albumKey || !password) {
                    if (feedback) {
                        feedback.textContent = "请输入访问密码。";
                    }
                    if (input) {
                        input.focus();
                    }
                    return;
                }

                const payload = new URLSearchParams();
                payload.set("laoke_action", "unlock_album");
                payload.set("cid", cid);
                payload.set("album_key", albumKey);
                payload.set("password", password);
                payload.set("display", "inline");

                form.dataset.pending = "true";
                if (submit) {
                    submit.disabled = true;
                }
                if (feedback) {
                    feedback.textContent = "正在解锁...";
                }

                try {
                    const response = await fetch(endpoint, {
                        method: "POST",
                        body: payload.toString(),
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        credentials: "same-origin"
                    });
                    const result = await response.json();

                    if (!response.ok || !result || !result.ok || !result.html || !host) {
                        throw new Error(result && result.message ? result.message : "解锁失败，请稍后重试。");
                    }

                    host.classList.remove("is-locked");
                    host.innerHTML = result.html;

                    if (window.LaoKe) {
                        window.LaoKe.initPage(host);
                    }
                } catch (error) {
                    if (feedback) {
                        feedback.textContent = error && error.message ? error.message : "解锁失败，请稍后重试。";
                    }
                } finally {
                    delete form.dataset.pending;
                    if (submit) {
                        submit.disabled = false;
                    }
                }
            });
        });
    }

    function initShortcodeCollapses(root) {
        root.querySelectorAll("[data-shortcode-collapse]").forEach(function (item) {
            if (item.dataset.bound === "true") {
                return;
            }

            const toggle = item.querySelector("[data-shortcode-collapse-toggle]");
            const body = item.querySelector("[data-shortcode-collapse-body]");
            if (!toggle || !body) {
                return;
            }

            item.dataset.bound = "true";
            toggle.addEventListener("click", function () {
                const nextOpen = toggle.getAttribute("aria-expanded") !== "true";
                toggle.setAttribute("aria-expanded", nextOpen ? "true" : "false");
                item.classList.toggle("is-open", nextOpen);
                body.hidden = !nextOpen;
            });
        });
    }

    function initShortcodeTabs(root) {
        root.querySelectorAll("[data-shortcode-tabs]").forEach(function (tabs) {
            if (tabs.dataset.bound === "true") {
                return;
            }

            const buttons = Array.from(tabs.querySelectorAll("[data-shortcode-tab]"));
            const panels = Array.from(tabs.querySelectorAll("[data-shortcode-tab-panel]"));
            if (!buttons.length || !panels.length) {
                return;
            }

            tabs.dataset.bound = "true";
            buttons.forEach(function (button) {
                button.addEventListener("click", function () {
                    const active = button.getAttribute("data-shortcode-tab");
                    buttons.forEach(function (item) {
                        const isActive = item.getAttribute("data-shortcode-tab") === active;
                        item.classList.toggle("is-active", isActive);
                        item.setAttribute("aria-pressed", isActive ? "true" : "false");
                    });
                    panels.forEach(function (panel) {
                        const isActive = panel.getAttribute("data-shortcode-tab-panel") === active;
                        panel.classList.toggle("is-active", isActive);
                        panel.hidden = !isActive;
                    });
                });
            });
        });
    }

    function buildMetingUrl(endpoint, songId) {
        const url = new URL(endpoint || "https://api.injahow.cn/meting/", window.location.href);
        url.searchParams.set("type", "song");
        url.searchParams.set("id", songId);
        return url.toString();
    }

    function normalizeMetingAudio(result) {
        let items = [];

        if (Array.isArray(result)) {
            items = result;
        } else if (result && Array.isArray(result.data)) {
            items = result.data;
        } else if (result && typeof result === "object") {
            items = [result];
        }

        return items.map(function (item, index) {
            if (!item || typeof item !== "object") {
                return null;
            }

            return {
                name: item.name || item.title || ("音频 " + (index + 1)),
                artist: item.artist || item.author || item.ar || "",
                url: item.url || item.src || "",
                cover: item.cover || item.pic || item.poster || "",
                lrc: item.lrc || item.lyric || item.lyrics || "",
                theme: item.theme || ""
            };
        }).filter(function (item) {
            return item && item.url;
        });
    }

    function hydrateShortcodeMp3(container) {
        if (!container || container.dataset.bound === "true") {
            return;
        }

        container.dataset.bound = "true";

        const songId = (container.getAttribute("data-song-id") || "").trim();
        const status = container.querySelector("[data-shortcode-mp3-status]");
        if (!songId) {
            if (status) {
                status.textContent = "歌曲 ID 为空。";
            }
            return;
        }

        if (!window.APlayer) {
            if (status) {
                status.textContent = "播放器资源未加载。";
            }
            return;
        }

        fetch(buildMetingUrl(window.LaoKeConfig && window.LaoKeConfig.metingEndpoint, songId), {
            method: "GET",
            credentials: "omit",
            cache: "no-store"
        }).then(function (response) {
            if (!response.ok) {
                throw new Error("音频接口请求失败。");
            }
            return response.json();
        }).then(function (result) {
            const audio = normalizeMetingAudio(result);
            if (!audio.length) {
                throw new Error("没有获取到音频信息。");
            }

            container.innerHTML = "";
            new window.APlayer({
                container: container,
                lrcType: 3,
                audio: audio
            });
        }).catch(function (error) {
            if (status) {
                status.textContent = error && error.message ? error.message : "音频加载失败，请稍后重试。";
            }
        });
    }

    function ensureShortcodeMp3Observer() {
        if (state.shortcodeMp3Observer || !("IntersectionObserver" in window)) {
            return state.shortcodeMp3Observer;
        }

        state.shortcodeMp3Observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                state.shortcodeMp3Observer.unobserve(entry.target);
                hydrateShortcodeMp3(entry.target);
            });
        }, { rootMargin: "220px 0px" });

        return state.shortcodeMp3Observer;
    }

    function initShortcodeMp3(root) {
        const containers = root.querySelectorAll("[data-shortcode-mp3]");
        if (!containers.length) {
            return;
        }

        if (!("IntersectionObserver" in window)) {
            containers.forEach(hydrateShortcodeMp3);
            return;
        }

        const observer = ensureShortcodeMp3Observer();
        containers.forEach(function (container) {
            if (container.dataset.bound === "true" || container.dataset.observed === "true") {
                return;
            }

            container.dataset.observed = "true";
            observer.observe(container);
        });
    }

    function validateCommentForm(form) {
        const text = form.querySelector('textarea[name="text"]');
        const author = form.querySelector('input[name="author"]');
        const mail = form.querySelector('input[name="mail"]');
        const url = form.querySelector('input[name="url"]');
        const captchaAnswer = form.querySelector('input[name="laoke_captcha_answer"]');
        const captchaA = form.querySelector('input[name="laoke_captcha_a"]');
        const captchaB = form.querySelector('input[name="laoke_captcha_b"]');
        const feedback = form.querySelector("#comment-feedback");
        const minLength = Number(form.dataset.commentMinlength || 3);
        const mode = form.dataset.commentMode || "comments";

        if (feedback) {
            feedback.textContent = "";
        }

        if (author && author.value.trim().length < 2) {
            if (feedback) {
                feedback.textContent = "昵称至少需要 2 个字符。";
            }
            author.focus();
            return false;
        }

        if (mail && mail.required && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail.value.trim())) {
            if (feedback) {
                feedback.textContent = "请输入有效的 Email。";
            }
            mail.focus();
            return false;
        }

        if (url && url.value.trim() !== "" && !/^https?:\/\/.+/i.test(url.value.trim())) {
            if (feedback) {
                feedback.textContent = "网址需以 http:// 或 https:// 开头。";
            }
            url.focus();
            return false;
        }

        if (!text || text.value.trim().length < minLength) {
            if (feedback) {
                feedback.textContent = mode === "moments" ? "至少写点什么。" : "评论内容至少需要 3 个字符。";
            }
            text.focus();
            return false;
        }

        if (captchaAnswer) {
            const answer = captchaAnswer.value.trim();
            const a = Number(captchaA ? captchaA.value : NaN);
            const b = Number(captchaB ? captchaB.value : NaN);
            const answerNumber = Number(answer);

            if (answer === "") {
                if (feedback) {
                    feedback.textContent = "请输入验证码结果。";
                }
                captchaAnswer.focus();
                return false;
            }

            if (!/^\d+$/.test(answer) || !Number.isInteger(answerNumber)) {
                if (feedback) {
                    feedback.textContent = "验证码需要填写数字结果。";
                }
                captchaAnswer.focus();
                return false;
            }

            if (!Number.isFinite(a) || !Number.isFinite(b) || answerNumber !== a + b) {
                if (feedback) {
                    feedback.textContent = "验证码结果不正确。";
                }
                captchaAnswer.focus();
                return false;
            }
        }

        return true;
    }

    function initCommentForm(root) {
        const form = root.querySelector("#comment-form");
        if (!form || form.dataset.ajaxBound === "true") {
            return;
        }

        form.dataset.ajaxBound = "true";
        form.addEventListener("submit", async function (event) {
            if (!validateCommentForm(form)) {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            const feedback = form.querySelector("#comment-feedback");
            const submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
            }
            if (feedback) {
                feedback.textContent = "正在提交...";
            }

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form),
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    credentials: "same-origin"
                });
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, "text/html");
                const newComments = doc.querySelector("#comments");
                const currentComments = document.querySelector("#comments");

                if (!response.ok || !newComments || !currentComments) {
                    window.location.href = form.action;
                    return;
                }

                currentComments.replaceWith(newComments);
                if (window.LaoKe) {
                    window.LaoKe.initPage(document);
                }
                smoothScrollTo(document.querySelector("#comments").getBoundingClientRect().top + window.scrollY - 24);
            } catch (error) {
                if (feedback) {
                    feedback.textContent = "提交失败，请稍后重试。";
                }
            } finally {
                if (submit) {
                    submit.disabled = false;
                }
            }
        });
    }

    function initPrism(root) {
        if (window.Prism && root.querySelector("pre code")) {
            window.Prism.highlightAllUnder(root);
        }
    }

    function initViewImages(root) {
        if (window.ViewImages) {
            window.ViewImages.bind(root);
        }
    }

    function showMain() {
        const main = document.getElementById("ajax-root");
        if (!main) {
            return;
        }
        requestAnimationFrame(function () {
            main.classList.add("is-ready");
        });
    }

    function beforePageSwap() {
        destroyBarrage(false);
    }

    function initPage(root) {
        if (isPageRoot(root)) {
            hydrateConfig(root);
        }

        initFonts();
        initCodeThemeSwitch();
        initHeaderTools();
        initThemeToggle();
        initSmoothAnchors(root);
        initBackToTop();
        initLazyImages(root);
        initCountUp(root);
        initProgress();
        initToc();
        initCopyButtons(root);
        initLinkLatency(root);
        initMomentLikes(root);
        initAlbumPage(root);
        initInlineAlbums(root);
        initShortcodeCollapses(root);
        initShortcodeTabs(root);
        initShortcodeMp3(root);
        initCommentEmoji(root);
        initCommentForm(root);
        initPrism(root);
        initViewImages(root);
        initBarrage(root);
        showMain();
    }

    window.LaoKe = {
        beforePageSwap: beforePageSwap,
        destroyBarrage: destroyBarrage,
        hydrateConfig: hydrateConfig,
        initPage: initPage,
        smoothScrollTo: smoothScrollTo,
        showMain: showMain
    };

    document.addEventListener("DOMContentLoaded", function () {
        hydrateConfig(document);
        initCodeThemeSwitch();
        initPage(document);
    });
})();
