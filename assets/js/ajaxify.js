(function () {
    if (!window.LaoKeConfig || !window.LaoKeConfig.ajax) {
        return;
    }

    let activeController = null;
    let navigationId = 0;
    window.history.scrollRestoration = "manual";

    function decodeHash(hash) {
        const value = String(hash || "").replace(/^#/, "");
        try {
            return decodeURIComponent(value);
        } catch (error) {
            return value;
        }
    }

    function saveScrollPosition() {
        const state = window.history.state || {};
        window.history.replaceState(Object.assign({}, state, {
            url: state.url || window.location.href,
            scrollX: window.scrollX,
            scrollY: window.scrollY
        }), "", window.location.href);
    }

    function normalizeNavHref(href) {
        if (!href) {
            return "";
        }

        try {
            const url = new URL(href, window.location.origin);
            const pathname = url.pathname.replace(/\/+$/, "") || "/";
            const search = url.search || "";
            return pathname + search;
        } catch (error) {
            return href;
        }
    }

    function syncNavigation(doc, targetUrl) {
        const currentNav = document.querySelector("#nav");
        const nextNav = doc.querySelector("#nav");

        if (!currentNav) {
            return;
        }

        const currentLinks = Array.from(currentNav.querySelectorAll("a[href]"));
        currentLinks.forEach(function (link) {
            link.classList.remove("selected");
            link.removeAttribute("aria-current");
        });

        if (nextNav) {
            const nextSelected = Array.from(nextNav.querySelectorAll("a.selected[href]"));
            nextSelected.forEach(function (nextLink) {
                const targetKey = normalizeNavHref(nextLink.getAttribute("href"));
                const match = currentLinks.find(function (link) {
                    return normalizeNavHref(link.getAttribute("href")) === targetKey;
                });

                if (match) {
                    match.classList.add("selected");
                    match.setAttribute("aria-current", "page");
                }
            });

            if (nextSelected.length) {
                const selected = currentNav.querySelector("a[aria-current='page']");
                if (selected && window.matchMedia("(max-width: 900px)").matches) {
                    selected.scrollIntoView({ block: "nearest", inline: "center" });
                }
                return;
            }
        }

        const activeKey = normalizeNavHref(targetUrl);
        const activeLink = currentLinks.find(function (link) {
            return normalizeNavHref(link.getAttribute("href")) === activeKey;
        });

        if (activeLink) {
            activeLink.classList.add("selected");
            activeLink.setAttribute("aria-current", "page");
            if (window.matchMedia("(max-width: 900px)").matches) {
                activeLink.scrollIntoView({ block: "nearest", inline: "center" });
            }
        }
    }

    function isInternalLink(link) {
        const href = link.getAttribute("href");
        if (!href || href.startsWith("#")) {
            return false;
        }

        const url = new URL(href, window.location.href);
        if (url.origin !== window.location.origin) {
            return false;
        }

        if (link.target === "_blank" || link.hasAttribute("download") || link.classList.contains("no-ajax")) {
            return false;
        }

        if (url.pathname.indexOf("/admin") === 0) {
            return false;
        }

        return true;
    }

    async function navigate(url, push, restorePosition) {
        if (push) {
            saveScrollPosition();
        }
        if (activeController) {
            activeController.abort();
        }
        const controller = new AbortController();
        const currentNavigationId = ++navigationId;
        activeController = controller;

        const currentRoot = document.querySelector("#ajax-root");
        if (window.LaoKe && typeof window.LaoKe.beforePageSwap === "function") {
            window.LaoKe.beforePageSwap();
        }

        document.documentElement.classList.add("is-loading");
        if (currentRoot) {
            currentRoot.classList.remove("is-ready");
        }

        try {
            const response = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                credentials: "same-origin",
                signal: controller.signal
            });
            const contentType = response.headers.get("content-type") || "";
            if (!contentType.includes("text/html")) {
                window.location.href = url;
                return;
            }
            const html = await response.text();
            if (currentNavigationId !== navigationId) {
                return;
            }
            const doc = new DOMParser().parseFromString(html, "text/html");
            const nextRoot = doc.querySelector("#ajax-root");

            if (!response.ok || !nextRoot || !currentRoot) {
                window.location.href = url;
                return;
            }

            currentRoot.innerHTML = nextRoot.innerHTML;
            currentRoot.dataset.tocThreshold = nextRoot.dataset.tocThreshold || currentRoot.dataset.tocThreshold || "1500";
            document.title = doc.title;
            document.body.className = doc.body.className;
            syncNavigation(doc, url);

            const description = doc.querySelector('meta[name="description"]');
            const currentDescription = document.querySelector('meta[name="description"]');
            if (description && currentDescription) {
                currentDescription.setAttribute("content", description.getAttribute("content") || "");
            }

            const canonical = doc.querySelector('link[rel="canonical"]');
            const currentCanonical = document.querySelector('link[rel="canonical"]');
            if (canonical && currentCanonical) {
                currentCanonical.setAttribute("href", canonical.getAttribute("href") || url);
            }

            if (push) {
                window.history.pushState({ url: url }, "", url);
            }

            if (window.LaoKe) {
                if (typeof window.LaoKe.hydrateConfig === "function") {
                    window.LaoKe.hydrateConfig(currentRoot);
                }
                if (typeof window.LaoKe.runEmbeddedScripts === "function") {
                    window.LaoKe.runEmbeddedScripts(currentRoot);
                }
                window.LaoKe.initPage(document);
            }
            const target = new URL(url, window.location.href);
            const hashTarget = target.hash ? document.getElementById(decodeHash(target.hash)) : null;
            if (restorePosition && Number.isFinite(restorePosition.y)) {
                window.scrollTo(Number(restorePosition.x) || 0, restorePosition.y);
            } else {
                window.scrollTo(0, hashTarget ? hashTarget.getBoundingClientRect().top + window.scrollY - 24 : 0);
            }
            const mainContent = document.getElementById("main-content");
            const focusTarget = hashTarget || mainContent;
            if (focusTarget) {
                focusTarget.setAttribute("tabindex", "-1");
                focusTarget.focus({ preventScroll: true });
            }
        } catch (error) {
            if (error && error.name === "AbortError") {
                return;
            }
            window.location.href = url;
        } finally {
            if (currentNavigationId === navigationId) {
                activeController = null;
                document.documentElement.classList.remove("is-loading");
            }
        }
    }

    document.addEventListener("click", function (event) {
        const link = event.target.closest("a");
        if (!link) {
            return;
        }
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        if (!isInternalLink(link)) {
            return;
        }

        const url = new URL(link.href, window.location.href);
        if (url.hash && url.pathname === window.location.pathname) {
            return;
        }

        event.preventDefault();
        navigate(url.toString(), true);
    });

    window.addEventListener("popstate", function (event) {
        const url = event.state && event.state.url ? event.state.url : window.location.href;
        navigate(url, false, {
            x: event.state && Number(event.state.scrollX),
            y: event.state && Number(event.state.scrollY)
        });
    });

    window.history.replaceState({
        url: window.location.href,
        scrollX: window.scrollX,
        scrollY: window.scrollY
    }, "", window.location.href);
})();
