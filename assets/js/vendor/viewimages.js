(function () {
    const ViewImages = {
        version: "1.2.0",
        overlay: null,
        image: null,
        counter: null,
        list: [],
        index: 0,
        scale: 1,
        previousFocus: null,
        previousOverflow: "",

        ensure() {
            if (this.overlay) {
                return;
            }

            const overlay = document.createElement("div");
            overlay.className = "viewimages-overlay";
            overlay.setAttribute("role", "dialog");
            overlay.setAttribute("aria-modal", "true");
            overlay.setAttribute("aria-label", "图片预览");
            overlay.innerHTML = [
                '<div class="viewimages-toolbar">',
                '<button class="viewimages-btn" data-action="zoom-out" type="button">缩小</button>',
                '<button class="viewimages-btn" data-action="zoom-in" type="button">放大</button>',
                '<button class="viewimages-btn" data-action="close" type="button">关闭</button>',
                '</div>',
                '<div class="viewimages-nav">',
                '<button class="viewimages-btn" data-action="prev" type="button">上一张</button>',
                '<button class="viewimages-btn" data-action="next" type="button">下一张</button>',
                '</div>',
                '<div class="viewimages-stage"><img alt=""></div>',
                '<div class="viewimages-counter"></div>'
            ].join("");

            document.body.appendChild(overlay);
            this.overlay = overlay;
            this.image = overlay.querySelector("img");
            this.counter = overlay.querySelector(".viewimages-counter");

            overlay.addEventListener("click", (event) => {
                const action = event.target.getAttribute("data-action");
                if (action === "close" || event.target === overlay) {
                    this.close();
                }
                if (action === "prev") {
                    this.prev();
                }
                if (action === "next") {
                    this.next();
                }
                if (action === "zoom-in") {
                    this.zoom(0.2);
                }
                if (action === "zoom-out") {
                    this.zoom(-0.2);
                }
            });

            overlay.addEventListener("wheel", (event) => {
                event.preventDefault();
                this.zoom(event.deltaY < 0 ? 0.1 : -0.1);
            }, { passive: false });

            document.addEventListener("keydown", (event) => {
                if (!this.overlay.classList.contains("is-open")) {
                    return;
                }
                if (event.key === "Escape") {
                    event.preventDefault();
                    this.close();
                }
                if (event.key === "ArrowLeft") {
                    event.preventDefault();
                    this.prev();
                }
                if (event.key === "ArrowRight") {
                    event.preventDefault();
                    this.next();
                }
                if (event.key === "Tab") {
                    const focusable = Array.from(this.overlay.querySelectorAll("button:not([disabled])"));
                    if (!focusable.length) {
                        return;
                    }
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });
        },

        bind(root) {
            this.ensure();
            const scopes = root.matches && root.matches(".post-content")
                ? [root]
                : Array.from(root.querySelectorAll(".post-content"));

            scopes.forEach((scope) => {
                const images = scope.querySelectorAll('img:not([data-viewimages-ignore="true"])');
                images.forEach((img) => {
                    if (img.dataset.viewimagesBound === "true") {
                        return;
                    }
                    img.dataset.viewimagesBound = "true";
                    if (!img.hasAttribute("tabindex")) {
                        img.tabIndex = 0;
                    }
                    img.setAttribute("role", "button");
                    img.setAttribute("aria-label", (img.alt ? img.alt + "，" : "") + "打开图片预览");
                    img.addEventListener("click", (event) => {
                        event.preventDefault();
                        const list = Array.from(scope.querySelectorAll('img:not([data-viewimages-ignore="true"])'));
                        const index = list.indexOf(img);
                        this.open(list, index);
                    });
                    img.addEventListener("keydown", (event) => {
                        if (event.key !== "Enter" && event.key !== " ") {
                            return;
                        }
                        event.preventDefault();
                        const list = Array.from(scope.querySelectorAll('img:not([data-viewimages-ignore="true"])'));
                        this.open(list, list.indexOf(img));
                    });
                });
            });
        },

        open(list, index) {
            if (!Array.isArray(list) || !list.length || index < 0 || index >= list.length) {
                return;
            }
            this.list = list;
            this.index = index;
            this.scale = 1;
            this.previousFocus = document.activeElement;
            this.previousOverflow = document.body.style.overflow;
            this.render();
            this.overlay.classList.add("is-open");
            document.body.style.overflow = "hidden";
            this.overlay.querySelector('[data-action="close"]').focus();
        },

        close() {
            if (!this.overlay) {
                return;
            }
            this.overlay.classList.remove("is-open");
            document.body.style.overflow = this.previousOverflow;
            this.list = [];
            if (this.previousFocus && this.previousFocus.isConnected) {
                this.previousFocus.focus();
            }
            this.previousFocus = null;
        },

        render() {
            const current = this.list[this.index];
            if (!current) {
                return;
            }
            this.image.src = current.currentSrc || current.src;
            this.image.alt = current.alt || "";
            this.image.style.transform = "scale(" + this.scale + ")";
            this.counter.textContent = (this.index + 1) + " / " + this.list.length;
        },

        next() {
            if (!this.list.length) {
                return;
            }
            this.index = (this.index + 1) % this.list.length;
            this.scale = 1;
            this.render();
        },

        prev() {
            if (!this.list.length) {
                return;
            }
            this.index = (this.index - 1 + this.list.length) % this.list.length;
            this.scale = 1;
            this.render();
        },

        zoom(delta) {
            if (!this.list.length) {
                return;
            }
            this.scale = Math.min(3, Math.max(0.6, this.scale + delta));
            this.image.style.transform = "scale(" + this.scale + ")";
        }
    };

    window.ViewImages = ViewImages;
})();
