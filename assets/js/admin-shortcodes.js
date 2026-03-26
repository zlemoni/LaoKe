(function (window, document) {
    "use strict";

    var ICONS = {
        "tip": '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M12 10v5"></path><path d="M12 7.5h.01"></path></svg>',
        "collapse": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10l5 5 5-5"></path><path d="M7 6l5 5 5-5"></path></svg>',
        "tabs": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h6"></path><path d="M12 7h8"></path><path d="M4 12h16"></path><path d="M4 17h10"></path></svg>',
        "button": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="16" height="10" rx="5"></rect><path d="M10 12h4"></path></svg>',
        "colour": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4c4 4 6 6.5 6 9a6 6 0 1 1-12 0c0-2.5 2-5 6-9z"></path></svg>',
        "bkc": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12c1.8-3 4.2-4.5 7-4.5s5.2 1.5 7 4.5c-1.8 3-4.2 4.5-7 4.5S6.8 15 5 12z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>',
        "bilibili": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="16" height="10" rx="2"></rect><path d="M10 10l5 2-5 2z"></path><path d="M9 5l1.5 2"></path><path d="M15 5l-1.5 2"></path></svg>',
        "video": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="6" width="12" height="12" rx="2"></rect><path d="M16 10l4-2v8l-4-2z"></path></svg>',
        "mp3": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6v9.5a2.5 2.5 0 1 1-2-2.45V8l6-1.5v7a2.5 2.5 0 1 1-2-2.45V6.75z"></path></svg>',
        "cid": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="6" width="16" height="12" rx="2"></rect><path d="M8 10h8"></path><path d="M8 14h5"></path></svg>',
        "login": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="11" width="12" height="9" rx="2"></rect><path d="M9 11V8.5a3 3 0 1 1 6 0V11"></path></svg>',
        "hide": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12c1.8-3 4.2-4.5 7-4.5s5.2 1.5 7 4.5c-1.8 3-4.2 4.5-7 4.5S6.8 15 5 12z"></path><path d="M7 5l10 14"></path></svg>',
        "album-public": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="6" width="14" height="12" rx="2"></rect><path d="M8 14l2.5-3 2.5 2 2-2.5 2 3.5"></path><circle cx="10" cy="9.5" r="1"></circle></svg>',
        "album-private": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="11" height="10" rx="2"></rect><path d="M7 14l2-2.5 2 2 1.5-2 2.5 3"></path><rect x="13" y="11" width="7" height="6" rx="1.5"></rect><path d="M15 11V9.8a1.8 1.8 0 1 1 3.6 0V11"></path></svg>',
        "photos": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="7" height="7" rx="1.2"></rect><rect x="13" y="5" width="7" height="7" rx="1.2"></rect><rect x="4" y="13" width="7" height="7" rx="1.2"></rect><rect x="13" y="13" width="7" height="7" rx="1.2"></rect></svg>',
        "owo": '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M9 10h.01"></path><path d="M15 10h.01"></path><path d="M9 14c1 .8 2 .8 3 .8s2 0 3-.8"></path></svg>',
        "timeline": '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 6v12"></path><circle cx="7" cy="8" r="1.5"></circle><circle cx="7" cy="16" r="1.5"></circle><path d="M11 8h6"></path><path d="M11 16h6"></path></svg>'
    };

    var BUTTONS = [
        { name: "tip", label: "提示块", icon: "tip", action: openTipDialog },
        { name: "collapse", label: "折叠块", icon: "collapse", action: openCollapseDialog },
        { name: "tabs", label: "标签页", icon: "tabs", action: insertTabsSnippet },
        { name: "button", label: "按钮", icon: "button", action: openButtonDialog },
        { name: "colour", label: "彩色文字", icon: "colour", action: openColourDialog },
        { name: "bkc", label: "遮罩文字", icon: "bkc", action: insertMaskSnippet },
        { name: "bilibili", label: "Bilibili", icon: "bilibili", action: openBilibiliDialog },
        { name: "video", label: "视频", icon: "video", action: openVideoDialog },
        { name: "mp3", label: "网易云", icon: "mp3", action: openMp3Dialog },
        { name: "cid", label: "文章卡片", icon: "cid", action: openCidDialog },
        { name: "login", label: "登录可见", icon: "login", action: insertLoginSnippet },
        { name: "hide", label: "评论可见", icon: "hide", action: insertHideSnippet },
        { name: "album-public", label: "公开相册", icon: "album-public", action: insertPublicAlbumSnippet },
        { name: "album-private", label: "加密相册", icon: "album-private", action: insertPrivateAlbumSnippet },
        { name: "photos", label: "图片宫格", icon: "photos", action: insertPhotosSnippet },
        { name: "owo", label: "正文表情", icon: "owo", action: openOwoDialog },
        { name: "timeline", label: "时间线", icon: "timeline", action: openTimelineDialog }
    ];

    function normalizeOptions(options) {
        options = options || {};

        return {
            toolbar: options.toolbar || null,
            textarea: options.textarea || null,
            editor: options.editor || null,
            config: options.config || {}
        };
    }

    function findButtonRow(toolbar) {
        if (!toolbar || typeof toolbar.querySelector !== "function") {
            return document.getElementById("wmd-button-row");
        }

        return toolbar.querySelector("#wmd-button-row") || document.getElementById("wmd-button-row");
    }

    function mount(options) {
        var context = normalizeOptions(options);
        if (!context.toolbar || !context.textarea) {
            return false;
        }

        context.toolbar.__laokeShortcodeContext = context;

        function tryMount() {
            var row = findButtonRow(context.toolbar);
            if (!row) {
                return false;
            }

            installButtons(row, context);
            context.toolbar.setAttribute("data-laoke-shortcode-mounted", "true");
            return true;
        }

        if (tryMount()) {
            return true;
        }

        if (context.toolbar.__laokeShortcodeTimer) {
            return true;
        }

        var attempts = 0;
        context.toolbar.__laokeShortcodeTimer = window.setInterval(function () {
            attempts += 1;

            if (tryMount() || attempts > 120) {
                window.clearInterval(context.toolbar.__laokeShortcodeTimer);
                context.toolbar.__laokeShortcodeTimer = null;
            }
        }, 50);

        return true;
    }

    function installButtons(row, context) {
        var spacer = row.querySelector("#wmd-laoke-spacer");
        var anchor = row.querySelector("#wmd-exit-fullscreen-button") || row.lastElementChild;

        if (!spacer) {
            spacer = document.createElement("li");
            spacer.id = "wmd-laoke-spacer";
            spacer.className = "wmd-spacer";
            spacer.setAttribute("aria-hidden", "true");
        }

        insertAfter(row, spacer, anchor);

        var reference = spacer;
        BUTTONS.forEach(function (definition) {
            var id = "wmd-laoke-" + definition.name + "-button";
            var button = row.querySelector("#" + id);

            if (!button) {
                button = createToolbarButton(definition, context);
            }

            insertAfter(row, button, reference);
            reference = button;
        });
    }

    function insertAfter(parent, node, reference) {
        if (!node) {
            return;
        }

        if (!reference || !reference.parentNode || reference.parentNode !== parent) {
            parent.appendChild(node);
            return;
        }

        if (reference.nextSibling) {
            parent.insertBefore(node, reference.nextSibling);
            return;
        }

        parent.appendChild(node);
    }

    function createToolbarButton(definition, context) {
        var button = document.createElement("li");
        button.id = "wmd-laoke-" + definition.name + "-button";
        button.className = "wmd-button wmd-laoke-button";
        button.tabIndex = 0;
        button.setAttribute("role", "button");
        button.setAttribute("title", definition.label);
        button.setAttribute("aria-label", definition.label);
        button.setAttribute("data-laoke-action", definition.name);

        var icon = document.createElement("span");
        icon.innerHTML = ICONS[definition.icon] || ICONS.tip;
        button.appendChild(icon);

        button.addEventListener("click", function (event) {
            event.preventDefault();
            definition.action(context, button);
        });

        button.addEventListener("keydown", function (event) {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            definition.action(context, button);
        });

        return button;
    }

    function dispatchEditorChange(textarea) {
        if (!textarea) {
            return;
        }

        if (window.jQuery) {
            window.jQuery(textarea).trigger("input").trigger("change");
            return;
        }

        textarea.dispatchEvent(new Event("input", { bubbles: true }));
        textarea.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function insertAtCursor(textarea, text) {
        if (!textarea || typeof text !== "string") {
            return;
        }

        textarea.focus();

        if (typeof textarea.selectionStart === "number" && typeof textarea.selectionEnd === "number") {
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var before = textarea.value.slice(0, start);
            var after = textarea.value.slice(end);
            var prefix = before && !/\n$/.test(before) && /^\n/.test(text) ? "\n" : "";
            var suffix = after && !/^\n/.test(after) && /\n$/.test(text) ? "\n" : "";
            var inserted = prefix + text + suffix;
            var caret = start + inserted.length;

            textarea.value = before + inserted + after;
            textarea.setSelectionRange(caret, caret);
        } else {
            textarea.value += text;
        }

        dispatchEditorChange(textarea);
    }

    function getSelectionText(textarea) {
        if (!textarea || typeof textarea.selectionStart !== "number" || typeof textarea.selectionEnd !== "number") {
            return "";
        }

        return textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
    }

    function wrapSelection(textarea, prefix, suffix, fallback) {
        var selected = getSelectionText(textarea).trim() || fallback;
        insertAtCursor(textarea, prefix + selected + suffix);
    }

    function blockWrapSelection(textarea, openTag, closeTag, fallback) {
        var selected = getSelectionText(textarea).trim() || fallback;
        insertAtCursor(textarea, "\n" + openTag + "\n" + selected + "\n" + closeTag + "\n");
    }

    function escapeAttribute(value) {
        return String(value || "").replace(/"/g, "&quot;").trim();
    }

    function buildAlbumSnippet(locked) {
        var password = locked ? ' password="123456"' : "";

        return [
            '[album key="album-key" title="相册标题" desc="一句描述" cover=""' + password + "]",
            "![图片1](https://example.com/image-1.jpg)",
            "![图片2](https://example.com/image-2.jpg)",
            "[/album]"
        ].join("\n");
    }

    function buildTimelineSnippet(data) {
        var firstTitle = escapeAttribute(data.item1Title || "开始");
        var firstTime = escapeAttribute(data.item1Time || "");
        var secondTitle = escapeAttribute(data.item2Title || "继续");
        var secondTime = escapeAttribute(data.item2Time || "");

        return [
            "[timeline]",
            buildTimelineItemSnippet(firstTitle, firstTime, "这里写第一条时间线内容。"),
            buildTimelineItemSnippet(secondTitle, secondTime, "这里写第二条时间线内容。"),
            "[/timeline]"
        ].join("\n");
    }

    function buildTimelineItemSnippet(title, time, body) {
        var line = '[timeline-item title="' + title + '"';
        if (time) {
            line += ' time="' + time + '"';
        }
        line += "]";

        return [
            line,
            body,
            "[/timeline-item]"
        ].join("\n");
    }

    function openPromptDialog(options) {
        options = options || {};

        var lastFocused = document.activeElement;
        var backdrop = document.createElement("div");
        backdrop.className = "wmd-prompt-background laoke-shortcode-backdrop";

        var dialog = document.createElement("div");
        dialog.className = "wmd-prompt-dialog laoke-shortcode-dialog";
        dialog.setAttribute("role", "dialog");
        dialog.setAttribute("aria-modal", "true");

        var form = document.createElement("form");
        form.className = "laoke-shortcode-dialog__form";

        if (options.title) {
            var title = document.createElement("p");
            title.className = "laoke-shortcode-dialog__title";
            title.textContent = options.title;
            form.appendChild(title);
        }

        if (options.description) {
            var desc = document.createElement("p");
            desc.className = "laoke-shortcode-dialog__desc";
            desc.textContent = options.description;
            form.appendChild(desc);
        }

        var body = document.createElement("div");
        body.className = "laoke-shortcode-dialog__body";
        form.appendChild(body);

        var actions = document.createElement("div");
        actions.className = "laoke-shortcode-dialog__actions";

        var cancel = document.createElement("button");
        cancel.type = "button";
        cancel.className = "btn btn-s";
        cancel.textContent = options.cancelText || (options.onConfirm ? "取消" : "关闭");
        actions.appendChild(cancel);

        var confirm = null;
        if (typeof options.onConfirm === "function") {
            confirm = document.createElement("button");
            confirm.type = "submit";
            confirm.className = "btn btn-s primary";
            confirm.textContent = options.confirmText || "插入";
            actions.appendChild(confirm);
        }

        form.appendChild(actions);
        dialog.appendChild(form);

        function focusField(field) {
            window.setTimeout(function () {
                if (field && typeof field.focus === "function") {
                    field.focus();
                }
            }, 20);
        }

        function closeDialog() {
            document.removeEventListener("keydown", onKeyDown, true);
            if (dialog.parentNode) {
                dialog.parentNode.removeChild(dialog);
            }
            if (backdrop.parentNode) {
                backdrop.parentNode.removeChild(backdrop);
            }
            if (lastFocused && typeof lastFocused.focus === "function") {
                lastFocused.focus();
            }
        }

        function onKeyDown(event) {
            if (event.key === "Escape") {
                event.preventDefault();
                closeDialog();
            }
        }

        var api = {
            body: body,
            form: form,
            close: closeDialog,
            focus: focusField
        };

        cancel.addEventListener("click", function () {
            closeDialog();
        });

        backdrop.addEventListener("click", function () {
            closeDialog();
        });

        form.addEventListener("submit", function (event) {
            event.preventDefault();
            if (typeof options.onConfirm !== "function") {
                closeDialog();
                return;
            }

            if (options.onConfirm(api) !== false) {
                closeDialog();
            }
        });

        if (typeof options.render === "function") {
            options.render(body, api);
        }

        document.body.appendChild(backdrop);
        document.body.appendChild(dialog);
        document.addEventListener("keydown", onKeyDown, true);

        if (confirm) {
            var initial = form.querySelector("input, textarea, select, button");
            focusField(initial);
        }

        return api;
    }

    function openFieldsDialog(context, definition) {
        var fields = {};

        openPromptDialog({
            title: definition.title,
            description: definition.description,
            confirmText: definition.confirmText,
            render: function (body, api) {
                definition.fields.forEach(function (fieldDef) {
                    var field = createField(fieldDef);
                    fields[fieldDef.name] = field.control;
                    body.appendChild(field.wrapper);
                });

                if (typeof definition.afterRender === "function") {
                    definition.afterRender(body, api, fields);
                }

                var initialName = definition.initialFocus || (definition.fields[0] && definition.fields[0].name);
                if (initialName && fields[initialName]) {
                    api.focus(fields[initialName]);
                }
            },
            onConfirm: function (api) {
                return definition.onConfirm(fields, api, context);
            }
        });
    }

    function createField(definition) {
        var wrapper = document.createElement("label");
        wrapper.className = "laoke-shortcode-dialog__field";

        var label = document.createElement("span");
        label.className = "laoke-shortcode-dialog__label";
        label.textContent = definition.label;
        wrapper.appendChild(label);

        var control;
        if (definition.type === "textarea") {
            control = document.createElement("textarea");
            control.className = "laoke-shortcode-dialog__textarea";
            control.rows = definition.rows || 4;
        } else if (definition.type === "select") {
            control = document.createElement("select");
            control.className = "laoke-shortcode-dialog__select";
            (definition.options || []).forEach(function (optionDef) {
                var option = document.createElement("option");
                option.value = optionDef.value;
                option.textContent = optionDef.label;
                control.appendChild(option);
            });
        } else {
            control = document.createElement("input");
            control.className = "laoke-shortcode-dialog__input";
            control.type = definition.type || "text";
        }

        if (definition.placeholder) {
            control.placeholder = definition.placeholder;
        }

        if (definition.value !== undefined) {
            control.value = definition.value;
        }

        if (definition.min !== undefined) {
            control.min = definition.min;
        }

        if (definition.step !== undefined) {
            control.step = definition.step;
        }

        if (definition.inputMode) {
            control.inputMode = definition.inputMode;
        }

        wrapper.appendChild(control);
        return {
            wrapper: wrapper,
            control: control
        };
    }

    function insertPublicAlbumSnippet(context) {
        insertAtCursor(context.textarea, "\n" + buildAlbumSnippet(false) + "\n");
    }

    function insertPrivateAlbumSnippet(context) {
        insertAtCursor(context.textarea, "\n" + buildAlbumSnippet(true) + "\n");
    }

    function insertPhotosSnippet(context) {
        insertAtCursor(context.textarea, [
            "",
            "[photos]",
            "![图片1](https://example.com/image-1.jpg)",
            "![图片2](https://example.com/image-2.jpg)",
            "[/photos]",
            ""
        ].join("\n"));
    }

    function insertTabsSnippet(context) {
        insertAtCursor(context.textarea, [
            "",
            "[tabs]",
            '[tab-pane label="标签页 1"]这里写第一个标签页内容[/tab-pane]',
            '[tab-pane label="标签页 2"]这里写第二个标签页内容[/tab-pane]',
            "[/tabs]",
            ""
        ].join("\n"));
    }

    function insertLoginSnippet(context) {
        blockWrapSelection(context.textarea, "[login]", "[/login]", "这里的内容登录后可见");
    }

    function insertHideSnippet(context) {
        blockWrapSelection(context.textarea, "[hide]", "[/hide]", "这里的内容评论审核通过后可见");
    }

    function insertMaskSnippet(context) {
        wrapSelection(context.textarea, "[bkc]", "[/bkc]", "这里写鼠标悬停后显示的文字");
    }

    function openTipDialog(context) {
        openFieldsDialog(context, {
            title: "插入提示块",
            description: '生成 [tip type="..."]...[/tip]。',
            fields: [
                {
                    name: "type",
                    label: "样式",
                    type: "select",
                    value: "blue",
                    options: [
                        { value: "share", label: "资料灰" },
                        { value: "yellow", label: "提示黄" },
                        { value: "red", label: "警告红" },
                        { value: "blue", label: "信息蓝" },
                        { value: "green", label: "推荐绿" }
                    ]
                }
            ],
            onConfirm: function (fields) {
                insertAtCursor(context.textarea, '[tip type="' + fields.type.value + '"]这里写提示内容[/tip]');
            }
        });
    }

    function openCollapseDialog(context) {
        openFieldsDialog(context, {
            title: "插入折叠块",
            description: '生成 [collapse status="..." label="..."]...[/collapse]。',
            fields: [
                {
                    name: "label",
                    label: "标题",
                    placeholder: "折叠标题",
                    value: "折叠标题"
                },
                {
                    name: "status",
                    label: "初始状态",
                    type: "select",
                    value: "collapse-none",
                    options: [
                        { value: "collapse-none", label: "默认折叠" },
                        { value: "collapse-block", label: "默认展开" }
                    ]
                }
            ],
            initialFocus: "label",
            onConfirm: function (fields) {
                var label = escapeAttribute(fields.label.value || "折叠标题");
                insertAtCursor(context.textarea, '[collapse status="' + fields.status.value + '" label="' + label + '"]这里写折叠内容[/collapse]');
            }
        });
    }

    function openButtonDialog(context) {
        openFieldsDialog(context, {
            title: "插入按钮",
            description: '生成 [button color="..." url="..."]...[/button]。',
            fields: [
                {
                    name: "label",
                    label: "按钮文字",
                    placeholder: "按钮文字",
                    value: getSelectionText(context.textarea).trim() || "按钮文字"
                },
                {
                    name: "url",
                    label: "链接地址",
                    placeholder: "https://example.com",
                    value: "https://example.com"
                },
                {
                    name: "color",
                    label: "强调颜色",
                    placeholder: "#111827",
                    value: "#111827"
                }
            ],
            initialFocus: "label",
            onConfirm: function (fields) {
                var label = fields.label.value.trim() || "按钮文字";
                var url = escapeAttribute(fields.url.value || "https://example.com");
                var color = escapeAttribute(fields.color.value || "#111827");
                insertAtCursor(context.textarea, '[button color="' + color + '" url="' + url + '"]' + label + "[/button]");
            }
        });
    }

    function openColourDialog(context) {
        openFieldsDialog(context, {
            title: "插入彩色文字",
            description: '生成 [colour type="..."]...[/colour]。',
            fields: [
                {
                    name: "text",
                    label: "文字内容",
                    type: "textarea",
                    rows: 3,
                    placeholder: "这里写需要上色的文字",
                    value: getSelectionText(context.textarea).trim() || "这里写彩色文字"
                },
                {
                    name: "color",
                    label: "颜色值",
                    placeholder: "#ef4444",
                    value: "#ef4444"
                }
            ],
            initialFocus: "text",
            onConfirm: function (fields) {
                var text = fields.text.value.trim() || "这里写彩色文字";
                var color = escapeAttribute(fields.color.value || "#ef4444");
                insertAtCursor(context.textarea, '[colour type="' + color + '"]' + text + "[/colour]");
            }
        });
    }

    function openBilibiliDialog(context) {
        openFieldsDialog(context, {
            title: "插入 Bilibili",
            description: '生成 [bilibili bv="..." p="..."]。',
            fields: [
                {
                    name: "bv",
                    label: "BV 号",
                    placeholder: "BV1xx411c7mD",
                    value: "BV1xx411c7mD"
                },
                {
                    name: "page",
                    label: "分 P",
                    type: "number",
                    min: "1",
                    step: "1",
                    value: "1"
                }
            ],
            initialFocus: "bv",
            onConfirm: function (fields) {
                var bv = escapeAttribute(fields.bv.value || "BV1xx411c7mD");
                var page = Math.max(1, Number(fields.page.value) || 1);
                insertAtCursor(context.textarea, '\n[bilibili bv="' + bv + '" p="' + page + '"]\n');
            }
        });
    }

    function openVideoDialog(context) {
        openFieldsDialog(context, {
            title: "插入视频",
            description: '生成 [video src="..."]。',
            fields: [
                {
                    name: "src",
                    label: "视频地址",
                    placeholder: "https://example.com/video.mp4",
                    value: "https://example.com/video.mp4"
                }
            ],
            initialFocus: "src",
            onConfirm: function (fields) {
                var src = escapeAttribute(fields.src.value || "https://example.com/video.mp4");
                insertAtCursor(context.textarea, '\n[video src="' + src + '"]\n');
            }
        });
    }

    function openMp3Dialog(context) {
        openFieldsDialog(context, {
            title: "插入网易云歌曲",
            description: "生成 [mp3]歌曲 ID[/mp3]。",
            fields: [
                {
                    name: "songId",
                    label: "歌曲 ID",
                    placeholder: "123456",
                    inputMode: "numeric",
                    value: "123456"
                }
            ],
            initialFocus: "songId",
            onConfirm: function (fields) {
                var songId = (fields.songId.value || "123456").trim() || "123456";
                insertAtCursor(context.textarea, "\n[mp3]" + songId + "[/mp3]\n");
            }
        });
    }

    function openCidDialog(context) {
        openFieldsDialog(context, {
            title: "插入文章卡片",
            description: '生成 [cid="..."]。',
            fields: [
                {
                    name: "cid",
                    label: "文章 CID",
                    type: "number",
                    min: "1",
                    step: "1",
                    placeholder: "123",
                    value: "123"
                }
            ],
            initialFocus: "cid",
            onConfirm: function (fields) {
                var cid = Math.max(1, Number(fields.cid.value) || 123);
                insertAtCursor(context.textarea, '\n[cid="' + cid + '"]\n');
            }
        });
    }

    function openTimelineDialog(context) {
        openFieldsDialog(context, {
            title: "插入时间线",
            description: "生成两条示例时间线骨架。",
            fields: [
                {
                    name: "item1Title",
                    label: "第一条标题",
                    placeholder: "开始",
                    value: "开始"
                },
                {
                    name: "item1Time",
                    label: "第一条时间",
                    placeholder: "2026-03-26",
                    value: ""
                },
                {
                    name: "item2Title",
                    label: "第二条标题",
                    placeholder: "继续",
                    value: "继续"
                },
                {
                    name: "item2Time",
                    label: "第二条时间",
                    placeholder: "可留空",
                    value: ""
                }
            ],
            initialFocus: "item1Title",
            onConfirm: function (fields) {
                insertAtCursor(context.textarea, "\n" + buildTimelineSnippet({
                    item1Title: fields.item1Title.value || "开始",
                    item1Time: fields.item1Time.value || "",
                    item2Title: fields.item2Title.value || "继续",
                    item2Time: fields.item2Time.value || ""
                }) + "\n");
            }
        });
    }

    function openOwoDialog(context) {
        var items = context.config && context.config.owoItems ? context.config.owoItems : [];

        openPromptDialog({
            title: "插入正文表情",
            description: "点击一个表情后直接插入 [owo:name] 语法。",
            render: function (body, api) {
                if (!items.length) {
                    var empty = document.createElement("p");
                    empty.className = "laoke-shortcode-dialog__empty";
                    empty.textContent = "当前主题没有可用的 owo 表情资源。";
                    body.appendChild(empty);
                    return;
                }

                var grid = document.createElement("div");
                grid.className = "laoke-shortcode-dialog__emoji-grid";

                items.forEach(function (item) {
                    var button = document.createElement("button");
                    button.type = "button";
                    button.className = "laoke-shortcode-dialog__emoji";
                    button.setAttribute("title", item.name);
                    button.setAttribute("aria-label", "插入表情 " + item.name);

                    var image = document.createElement("img");
                    image.src = item.url;
                    image.alt = item.name;
                    button.appendChild(image);

                    button.addEventListener("click", function () {
                        insertAtCursor(context.textarea, "[owo:" + item.name + "]");
                        api.close();
                    });

                    grid.appendChild(button);
                });

                body.appendChild(grid);
                api.focus(grid.querySelector("button"));
            }
        });
    }

    window.LaoKeShortcodeEditor = {
        mount: mount
    };
})(window, document);
