(function () {
    const ViewImages = {
        version: "1.2.0",
        overlay: null,
        image: null,
        counter: null,
        list: [],
        index: 0,
        scale: 1,

        ensure() {
            if (this.overlay) {
                return;
            }

            const overlay = document.createElement("div");
            overlay.className = "viewimages-overlay";
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
                    this.close();
                }
                if (event.key === "ArrowLeft") {
                    this.prev();
                }
                if (event.key === "ArrowRight") {
                    this.next();
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
                    img.addEventListener("click", (event) => {
                        event.preventDefault();
                        const list = Array.from(scope.querySelectorAll('img:not([data-viewimages-ignore="true"])'));
                        const index = list.indexOf(img);
                        this.open(list, index);
                    });
                });
            });
        },

        open(list, index) {
            this.list = list;
            this.index = index;
            this.scale = 1;
            this.render();
            this.overlay.classList.add("is-open");
            document.body.style.overflow = "hidden";
        },

        close() {
            this.overlay.classList.remove("is-open");
            document.body.style.overflow = "";
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
            this.index = (this.index + 1) % this.list.length;
            this.scale = 1;
            this.render();
        },

        prev() {
            this.index = (this.index - 1 + this.list.length) % this.list.length;
            this.scale = 1;
            this.render();
        },

        zoom(delta) {
            this.scale = Math.min(3, Math.max(0.6, this.scale + delta));
            this.image.style.transform = "scale(" + this.scale + ")";
        }
    };

    window.ViewImages = ViewImages;
})();
