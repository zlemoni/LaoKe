(function () {
    if (!window.LaoKeConfig || !window.LaoKeConfig.ajax) {
        return;
    }

    let loading = false;

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

    async function navigate(url, push) {
        if (loading) {
            return;
        }

        const currentRoot = document.querySelector("#ajax-root");
        if (window.LaoKe && typeof window.LaoKe.beforePageSwap === "function") {
            window.LaoKe.beforePageSwap();
        }

        loading = true;
        document.documentElement.classList.add("is-loading");
        if (currentRoot) {
            currentRoot.classList.remove("is-ready");
        }

        try {
            const response = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                credentials: "same-origin"
            });
            const html = await response.text();
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
                window.LaoKe.initPage(document);
            }
            window.scrollTo(0, 0);
        } catch (error) {
            window.location.href = url;
        } finally {
            loading = false;
            document.documentElement.classList.remove("is-loading");
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
        navigate(url, false);
    });

    window.history.replaceState({ url: window.location.href }, "", window.location.href);
})();
