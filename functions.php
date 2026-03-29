<?php
// LaoKe theme helpers.
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once __DIR__ . '/inc/shortcodes.php';
require_once __DIR__ . '/inc/integrations.php';

function laoke_theme_option_storage_key($theme = null)
{
    $theme = $theme !== null ? $theme : \Utils\Helper::options()->theme;
    return 'theme:' . trim((string) $theme, './');
}

function laoke_theme_settings_array()
{
    $db = \Typecho\Db::get();
    $row = $db->fetchRow(
        $db->select('value')
            ->from('table.options')
            ->where('name = ?', laoke_theme_option_storage_key())
            ->limit(1)
    );

    if (!is_array($row) || !isset($row['value'])) {
        return [];
    }

    $settings = json_decode((string) $row['value'], true);
    return is_array($settings) ? $settings : [];
}

function laoke_theme_settings_clean(array $settings)
{
    unset(
        $settings['__laoke_theme_backups'],
        $settings['settingsBackupAction'],
        $settings['settingsBackupChoice'],
        $settings['settingsBackupCreate'],
        $settings['settingsBackupRestore']
    );
    return $settings;
}

function laoke_theme_backups_field_name()
{
    return '__laoke_theme_backups';
}

function laoke_theme_embedded_backups(?array $settings = null)
{
    $settings = $settings !== null ? $settings : laoke_theme_settings_array();
    $field = laoke_theme_backups_field_name();

    if (!isset($settings[$field]) || !is_array($settings[$field])) {
        return [];
    }

    return array_values($settings[$field]);
}

function laoke_theme_backup_storage_key($theme = null)
{
    $theme = $theme !== null ? $theme : \Utils\Helper::options()->theme;
    return 'laoke_theme_backups:' . trim((string) $theme, './');
}

function laoke_theme_backups()
{
    $db = \Typecho\Db::get();
    $row = $db->fetchRow(
        $db->select('value')
            ->from('table.options')
            ->where('name = ?', laoke_theme_backup_storage_key())
            ->limit(1)
    );

    if (is_array($row) && isset($row['value'])) {
        $backups = json_decode((string) $row['value'], true);
        if (is_array($backups) && count($backups) > 0) {
            return array_values($backups);
        }
    }

    $embedded = laoke_theme_embedded_backups();
    if (count($embedded) > 0) {
        laoke_theme_save_backups($embedded);
        return array_values($embedded);
    }

    return [];
}

function laoke_theme_save_backups(array $backups)
{
    $json = json_encode(array_values($backups), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $db = \Typecho\Db::get();
    $key = laoke_theme_backup_storage_key();
    $row = $db->fetchRow(
        $db->select('name')
            ->from('table.options')
            ->where('name = ?', $key)
            ->limit(1)
    );

    if (is_array($row) && isset($row['name'])) {
        \Widget\Base\Options::alloc()->update(
            ['value' => $json],
            $db->sql()->where('name = ?', $key)
        );
    } else {
        \Widget\Base\Options::alloc()->insert([
            'name' => $key,
            'value' => $json,
            'user' => 0
        ]);
    }
}

function laoke_theme_backup_options_legacy()
{
    $options = ['' => _t('鏆傛棤鍙仮澶囦唤')];
    foreach (laoke_theme_backups() as $backup) {
        $id = trim((string) ($backup['id'] ?? ''));
        if ($id === '') {
            continue;
        }

        $created = trim((string) ($backup['label'] ?? ($backup['createdAt'] ?? '')));
        $count = isset($backup['settings']) && is_array($backup['settings']) ? count($backup['settings']) : 0;
        $options[$id] = $created . ' · ' . $count . ' 项';
    }

    return $options;
}

function laoke_theme_backup_options()
{
    $options = ['' => _t('暂无可用备份')];
    foreach (laoke_theme_backups() as $backup) {
        $id = trim((string) ($backup['id'] ?? ''));
        if ($id === '') {
            continue;
        }

        $created = trim((string) ($backup['label'] ?? ($backup['createdAt'] ?? '')));
        $count = isset($backup['settings']) && is_array($backup['settings']) ? count($backup['settings']) : 0;
        $options[$id] = $created . ' · ' . $count . ' 项设置';
    }

    return $options;
}

function laoke_theme_create_backup()
{
    $currentSettings = laoke_theme_settings_clean(laoke_theme_settings_array());
    $backups = laoke_theme_backups();
    array_unshift($backups, [
        'id' => date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8),
        'label' => date('Y-m-d H:i:s'),
        'createdAt' => gmdate('c'),
        'settings' => $currentSettings
    ]);

    laoke_theme_save_backups(array_slice($backups, 0, 20));
}

function laoke_theme_create_backup_from_settings(array $settings)
{
    $backups = laoke_theme_backups();
    array_unshift($backups, [
        'id' => date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8),
        'label' => date('Y-m-d H:i:s'),
        'createdAt' => gmdate('c'),
        'settings' => laoke_theme_settings_clean($settings)
    ]);

    laoke_theme_save_backups(array_slice($backups, 0, 20));
}

function laoke_theme_save_settings(array $settings, ?array $backups = null)
{
    if ($backups === null) {
        $backups = laoke_theme_embedded_backups($settings);
    }

    if (is_array($backups) && count($backups) > 0) {
        laoke_theme_save_backups($backups);
    }

    $persisted = laoke_theme_settings_clean($settings);
    $json = json_encode($persisted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $db = \Typecho\Db::get();
    $key = laoke_theme_option_storage_key();
    $row = $db->fetchRow(
        $db->select('name')
            ->from('table.options')
            ->where('name = ?', $key)
            ->limit(1)
    );

    if (is_array($row) && isset($row['name'])) {
        \Widget\Base\Options::alloc()->update(
            ['value' => $json],
            $db->sql()->where('name = ?', $key)
        );
    } else {
        \Widget\Base\Options::alloc()->insert([
            'name' => $key,
            'value' => $json,
            'user' => 0
        ]);
    }
}

function laoke_theme_find_backup($backupId)
{
    $backupId = trim((string) $backupId);
    if ($backupId === '') {
        return null;
    }

    foreach (laoke_theme_backups() as $backup) {
        if ((string) ($backup['id'] ?? '') === $backupId) {
            return $backup;
        }
    }

    return null;
}

function themeConfigHandle($settings, $isInit)
{
    $settings = is_array($settings) ? $settings : [];
    $action = trim((string) ($settings['settingsBackupAction'] ?? ''));
    if ($action === '' && !empty($settings['settingsBackupCreate'])) {
        $action = 'create_backup';
    }
    if ($action === '' && !empty($settings['settingsBackupRestore'])) {
        $action = 'restore_backup';
    }

    if (!$isInit && $action === 'create_backup') {
        laoke_theme_create_backup();
        return true;
    }

    if (!$isInit && $action === 'restore_backup') {
        $backup = laoke_theme_find_backup($settings['settingsBackupChoice'] ?? '');
        if (is_array($backup) && isset($backup['settings']) && is_array($backup['settings'])) {
            laoke_theme_save_settings($backup['settings']);
        } else {
            return true;
        }

        return true;
    }

    laoke_theme_save_settings($settings);
    return true;
}

function laoke_legacy_themeConfig($form)
{
    $backups = laoke_theme_backups();

    $backupChoice = new \Typecho\Widget\Helper\Form\Element\Select(
        'settingsBackupChoice',
        laoke_theme_backup_options(),
        count($backups) > 0 ? (string) ($backups[0]['id'] ?? '') : '',
        _t('主题设置备份'),
        count($backups) > 0
            ? _t('已检测到 %d 份备份，可直接选择恢复；创建备份会保存当前已生效的主题设置。', count($backups))
            : _t('当前还没有备份。点击“创建备份”后会保存当前已生效的主题设置。')
    );
    $backupChoice->input->setAttribute('class', 'w-100');
    $form->addInput($backupChoice);
    $backupAction = new \Typecho\Widget\Helper\Form\Element\Hidden('settingsBackupAction', null, '');
    $form->addInput($backupAction);

    $backupCreate = new \Typecho\Widget\Helper\Form\Element\Submit('settingsBackupCreate', null, _t('创建备份'));
    $backupCreate->input->setAttribute('onclick', "this.form.elements['settingsBackupAction'].value='create_backup';");
    $backupCreate->input->setAttribute('value', 'create_backup');
    $backupCreate->input->setAttribute('class', 'btn');
    $form->addInput($backupCreate);

    $backupRestore = new \Typecho\Widget\Helper\Form\Element\Submit('settingsBackupRestore', null, _t('恢复所选备份'));
    $backupRestore->input->setAttribute('onclick', "this.form.elements['settingsBackupAction'].value='restore_backup';");
    $backupRestore->input->setAttribute('value', 'restore_backup');
    $backupRestore->input->setAttribute('class', 'btn');
    if (count($backups) === 0) {
        $backupRestore->input->setAttribute('disabled', 'disabled');
    }
    $form->addInput($backupRestore);

    $footerText = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'footerText',
        null,
        '写字的人，和安静的页面。',
        _t('页脚文案'),
        _t('显示在页脚左侧的简短说明。')
    );
    $form->addInput($footerText);

    $beian = new \Typecho\Widget\Helper\Form\Element\Text(
        'beian',
        null,
        null,
        _t('备案号'),
        _t('可选，留空则不显示。')
    );
    $form->addInput($beian);

    $siteStartDate = new \Typecho\Widget\Helper\Form\Element\Text(
        'siteStartDate',
        null,
        null,
        _t('建站时间'),
        _t('按 YYYY-MM-DD 格式填写，主题会自动计算已建站天数。')
    );
    $form->addInput($siteStartDate);

    $avatarSource = new \Typecho\Widget\Helper\Form\Element\Radio(
        'avatarSource',
        [
            'weavatar' => _t('WeAvatar'),
            'sepcc' => _t('Sep.cc'),
            'cravatar' => _t('Cravatar'),
            'custom' => _t('自定义')
        ],
        'weavatar',
        _t('评论头像源'),
        _t('默认使用 https://weavatar.com/avatar/ ，也可以切换到其它头像服务。')
    );
    $form->addInput($avatarSource);

    $avatarCustomSource = new \Typecho\Widget\Helper\Form\Element\Text(
        'avatarCustomSource',
        null,
        null,
        _t('自定义头像源'),
        _t('例如 https://example.com/avatar/ ，选择自定义时生效。')
    );
    $form->addInput($avatarCustomSource);

    $tocThreshold = new \Typecho\Widget\Helper\Form\Element\Text(
        'tocThreshold',
        null,
        '1500',
        _t('目录触发字数'),
        _t('文章正文纯文字超过该值时启用目录。')
    );
    $form->addInput($tocThreshold);

    $features = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'features',
        [
            'ajax' => _t('启用 AJAX 无感切换'),
            'progress' => _t('启用阅读进度条'),
            'lazyload' => _t('启用图片懒加载')
        ],
        ['ajax', 'progress', 'lazyload'],
        _t('增强功能')
    );
    $form->addInput($features->multiMode());

    $metingEndpoint = new \Typecho\Widget\Helper\Form\Element\Text(
        'metingEndpoint',
        null,
        'https://api.injahow.cn/meting/',
        _t('Meting 接口地址'),
        _t('用于 [mp3] 短代码，需兼容 type=song&id= 查询参数。')
    );
    $form->addInput($metingEndpoint);

    $customHead = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'customHead',
        null,
        null,
        _t('Head 自定义代码'),
        _t('可选，插入到 </head> 前。')
    );
    $form->addInput($customHead);

    laoke_add_integrated_theme_options($form);
    return;

    $settingsBackupExport = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'settingsBackupExport',
        null,
        laoke_theme_settings_export_payload(),
        _t('主题设置备份'),
        _t('复制这份 JSON 可用于迁移或回滚当前 LaoKe 主题设置。')
    );
    $settingsBackupExport->input->setAttribute('readonly', 'readonly');
    $settingsBackupExport->input->setAttribute('spellcheck', 'false');
    $settingsBackupExport->input->setAttribute('class', 'mono w-100');
    $settingsBackupExport->input->setAttribute('rows', '12');
    $form->addInput($settingsBackupExport);

    $settingsBackupImport = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'settingsBackupImport',
        null,
        null,
        _t('主题设置恢复'),
        _t('将备份 JSON 粘贴到这里并保存，即可用备份内容覆盖当前主题设置。留空则按当前表单保存。')
    );
    $settingsBackupImport->input->setAttribute('spellcheck', 'false');
    $settingsBackupImport->input->setAttribute('class', 'mono w-100');
    $settingsBackupImport->input->setAttribute('rows', '10');
    $form->addInput($settingsBackupImport);
}

function themeConfig($form)
{
    $backups = laoke_theme_backups();
    $backupCount = count($backups);
    $backupAction = new \Typecho\Widget\Helper\Form\Element\Hidden('settingsBackupAction', null, '');
    $form->addInput($backupAction);

    $backupChoice = new \Typecho\Widget\Helper\Form\Element\Select(
        'settingsBackupChoice',
        laoke_theme_backup_options(),
        $backupCount > 0 ? (string) ($backups[0]['id'] ?? '') : '',
        _t('主题设置备份'),
        $backupCount > 0
            ? _t('已检测到 %d 份备份，可直接选择并恢复；创建备份会保存当前已生效的主题设置。', $backupCount)
            : _t('当前还没有备份。点击“创建备份”后会保存当前已生效的主题设置。')
    );
    $backupChoice->input->setAttribute('class', 'w-100');
    $form->addInput($backupChoice);

    $backupCreate = new \Typecho\Widget\Helper\Form\Element\Submit('settingsBackupCreate', null, _t('创建备份'));
    $backupCreate->input->setAttribute('name', 'settingsBackupCreate');
    $backupCreate->input->setAttribute('onclick', "this.form.elements['settingsBackupAction'].value='create_backup';");
    $backupCreate->input->setAttribute('value', 'create_backup');
    $backupCreate->input->setAttribute('class', 'btn');
    $form->addInput($backupCreate);

    $backupRestore = new \Typecho\Widget\Helper\Form\Element\Submit('settingsBackupRestore', null, _t('恢复所选备份'));
    $backupRestore->input->setAttribute('name', 'settingsBackupRestore');
    $backupRestore->input->setAttribute('onclick', "this.form.elements['settingsBackupAction'].value='restore_backup';");
    $backupRestore->input->setAttribute('value', 'restore_backup');
    $backupRestore->input->setAttribute('class', 'btn');
    if ($backupCount === 0) {
        $backupRestore->input->setAttribute('disabled', 'disabled');
    }
    $form->addInput($backupRestore);

    $footerText = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'footerText',
        null,
        '写字的人，和安静的页面。',
        _t('页脚文案'),
        _t('显示在页脚左侧的简短说明。')
    );
    $form->addInput($footerText);

    $beian = new \Typecho\Widget\Helper\Form\Element\Text(
        'beian',
        null,
        null,
        _t('备案号'),
        _t('可选，留空则不显示。')
    );
    $form->addInput($beian);

    $siteStartDate = new \Typecho\Widget\Helper\Form\Element\Text(
        'siteStartDate',
        null,
        null,
        _t('建站时间'),
        _t('按 YYYY-MM-DD 格式填写，主题会自动计算已建站天数。')
    );
    $form->addInput($siteStartDate);

    $avatarSource = new \Typecho\Widget\Helper\Form\Element\Radio(
        'avatarSource',
        [
            'weavatar' => _t('WeAvatar'),
            'sepcc' => _t('Sep.cc'),
            'cravatar' => _t('Cravatar'),
            'custom' => _t('自定义')
        ],
        'weavatar',
        _t('评论头像源'),
        _t('默认使用 https://weavatar.com/avatar/ ，也可以切换到其它头像服务。')
    );
    $form->addInput($avatarSource);

    $avatarCustomSource = new \Typecho\Widget\Helper\Form\Element\Text(
        'avatarCustomSource',
        null,
        null,
        _t('自定义头像源'),
        _t('例如 https://example.com/avatar/ ，选择“自定义”时生效。')
    );
    $form->addInput($avatarCustomSource);

    $tocThreshold = new \Typecho\Widget\Helper\Form\Element\Text(
        'tocThreshold',
        null,
        '1500',
        _t('目录触发字数'),
        _t('文章正文纯文字超过该值时启用目录。')
    );
    $form->addInput($tocThreshold);

    $features = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'features',
        [
            'ajax' => _t('启用 AJAX 无感切换'),
            'progress' => _t('启用阅读进度条'),
            'lazyload' => _t('启用图片懒加载')
        ],
        ['ajax', 'progress', 'lazyload'],
        _t('增强功能')
    );
    $form->addInput($features->multiMode());

    $metingEndpoint = new \Typecho\Widget\Helper\Form\Element\Text(
        'metingEndpoint',
        null,
        'https://api.injahow.cn/meting/',
        _t('Meting 接口地址'),
        _t('用于 [mp3] 短代码，需兼容 type=song&id= 查询参数。')
    );
    $form->addInput($metingEndpoint);

    $customHead = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'customHead',
        null,
        null,
        _t('页头自定义代码'),
        _t('可选，插入到 </head> 前。')
    );
    $form->addInput($customHead);

    laoke_add_integrated_theme_options($form);
}

function themeInit($archive)
{
    if (laoke_is_time_machine_content($archive)) {
        $archive->allowComment = 1;
    }

    if ($archive->request->isPost()) {
        $action = trim((string) $archive->request->get('laoke_action'));
        if ($action === 'unlock_album') {
            laoke_handle_album_unlock($archive);
        }
        if ($action === 'moment_like') {
            laoke_handle_time_machine_like($archive);
        }
    }

    \Utils\Helper::options()->commentsThreaded = true;
    \Utils\Helper::options()->commentsPageBreak = true;
    \Utils\Helper::options()->commentsPageSize = 10;
    \Utils\Helper::options()->commentsPageDisplay = 'first';
    \Utils\Helper::options()->commentsOrder = 'DESC';
    \Utils\Helper::options()->commentsMaxNestingLevels = 3;
    \Utils\Helper::options()->commentsMarkdown = true;
    \Utils\Helper::options()->commentsHTMLTagAllowed = '<a href=""> <code> <pre> <img src="" alt="" title=""> <strong> <em> <blockquote>';

    if ($archive->is('index') || $archive->is('archive') || $archive->is('category') || $archive->is('tag') || $archive->is('search') || $archive->is('author')) {
        $archive->parameter->pageSize = 10;
    }

    if ($archive->is('post')) {
        laoke_track_views($archive);
    }
}

\Typecho\Plugin::factory('Widget_Feedback')->comment_1000 = 'laoke_filter_comment_submission';
\Typecho\Plugin::factory('Widget_Feedback')->finishComment = 'laoke_after_comment_submission';
\Typecho\Plugin::factory('admin/editor-js.php')->markdownEditor = ['LaoKe_Admin_Editor', 'mountShortcodeToolbar'];
\Typecho\Plugin::factory('admin/common.php')->footer = ['LaoKe_Admin_Hooks', 'injectAdminFooter'];

class LaoKe_Admin_Hooks
{
    public static function injectAdminFooter()
    {
        $codeTheme = laoke_option('codeTheme', 'default');
        $themeUrl = laoke_theme_url('assets/css/vendor/prism-' . $codeTheme . '.css');
        if ($codeTheme === 'default') {
            $themeUrl = laoke_theme_url('assets/css/vendor/prism.css');
        }
        $themeUrl = htmlspecialchars($themeUrl, ENT_QUOTES, 'UTF-8');
        $cssUrl = htmlspecialchars(laoke_theme_url('assets/css/admin-code-theme.css'), ENT_QUOTES, 'UTF-8');
        $jsUrl = htmlspecialchars(laoke_theme_url('assets/js/admin-code-theme.js'), ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<link rel="stylesheet" id="laoke-admin-prism-theme" href="{$themeUrl}">
<link rel="stylesheet" href="{$cssUrl}">
<script src="{$jsUrl}"></script>
HTML;
    }
}

class LaoKe_Admin_Editor
{
    public static function mountShortcodeToolbar()
    {
        $config = [
            'owoItems' => array_values(laoke_owo_items())
        ];
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $cssUrl = json_encode(laoke_theme_url('assets/css/admin-shortcodes.css'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $jsUrl = json_encode(laoke_theme_url('assets/js/admin-shortcodes.js'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        echo <<<JS
(function () {
    const state = window.__laokeShortcodeEditorState = window.__laokeShortcodeEditorState || {};
    state.queue = state.queue || [];

    const mountOptions = {
        toolbar: toolbar && toolbar[0] ? toolbar[0] : toolbar,
        textarea: textarea && textarea[0] ? textarea[0] : textarea,
        editor: editor,
        config: $configJson
    };
    const cssUrl = $cssUrl;
    const scriptUrl = $jsUrl;

    function ensureStyle() {
        if (state.cssLoaded || document.getElementById('laoke-shortcode-editor-style')) {
            state.cssLoaded = true;
            return;
        }

        const link = document.createElement('link');
        link.id = 'laoke-shortcode-editor-style';
        link.rel = 'stylesheet';
        link.href = cssUrl;
        document.head.appendChild(link);
        state.cssLoaded = true;
    }

    function flushQueue() {
        if (!window.LaoKeShortcodeEditor || typeof window.LaoKeShortcodeEditor.mount !== 'function') {
            return;
        }

        const pending = state.queue.slice();
        state.queue.length = 0;

        pending.forEach(function (item) {
            if (!window.LaoKeShortcodeEditor.mount(item)) {
                state.queue.push(item);
            }
        });
    }

    ensureStyle();
    state.queue.push(mountOptions);

    if (!state.scriptRequested) {
        state.scriptRequested = true;
        const script = document.createElement('script');
        script.src = scriptUrl;
        script.async = true;
        script.onload = function () {
            state.scriptLoaded = true;
            flushQueue();
        };
        script.onerror = function () {
            state.scriptRequested = false;
        };
        document.head.appendChild(script);
    }

    if (state.scriptLoaded) {
        flushQueue();
    } else {
        window.setTimeout(flushQueue, 0);
    }
})();
JS;
    }
}

function laoke_option($name, $default = '')
{
    $settings = laoke_theme_settings_array();
    if (isset($settings[$name]) && $settings[$name] !== '') {
        return $settings[$name];
    }
    return $default;
}

function laoke_avatar_source()
{
    $preset = laoke_option('avatarSource', 'weavatar');
    $map = [
        'weavatar' => 'https://weavatar.com/avatar/',
        'sepcc' => 'https://cdn.sep.cc/avatar/',
        'cravatar' => 'https://cn.cravatar.com/avatar/'
    ];

    if ($preset === 'custom') {
        $custom = trim((string) laoke_option('avatarCustomSource', ''));
        if ($custom !== '') {
            return rtrim($custom, '/') . '/';
        }
    }

    return $map[$preset] ?? $map['weavatar'];
}

function laoke_comment_avatar($mail, $size = 48, $default = 'mp')
{
    $email = strtolower(trim((string) $mail));
    $hash = md5($email);
    $base = laoke_avatar_source();
    $query = http_build_query([
        's' => (int) $size,
        'd' => $default,
        'r' => 'g'
    ]);

    return htmlspecialchars($base . $hash . '?' . $query, ENT_QUOTES, 'UTF-8');
}

function laoke_feature_enabled($name)
{
    $features = laoke_option('features', []);
    if (is_string($features)) {
        $features = array_filter(array_map('trim', explode(',', $features)));
    }

    if (!is_array($features)) {
        return false;
    }

    return in_array($name, $features, true);
}

function laoke_theme_url($path = '')
{
    $options = Helper::options();
    return $options->themeUrl($path, $options->theme);
}

function laoke_image_placeholder()
{
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16"><rect width="24" height="16" fill="#f3f4f6"/></svg>');
}

function laoke_strip_text($html)
{
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/\s+/u", ' ', $text);
    return trim((string) $text);
}

function laoke_capture_content($archive)
{
    ob_start();
    $archive->content();
    return ob_get_clean();
}

function laoke_capture_excerpt($archive)
{
    ob_start();
    $archive->excerpt(150, '...');
    return ob_get_clean();
}

function laoke_transform_image_html($html, $src, $index)
{
    $clean = preg_replace('/\s(?:loading|decoding|fetchpriority|srcset|sizes|data-src|data-srcset)="[^"]*"/i', '', $html);
    $clean = preg_replace("/\s(?:loading|decoding|fetchpriority|srcset|sizes|data-src|data-srcset)='[^']*'/i", '', $clean);

    if (stripos($clean, 'class=') !== false) {
        $clean = preg_replace('/class="([^"]*)"/i', 'class="$1 post-image' . ($index > 0 ? ' lazy-image' : '') . '"', $clean, 1);
        $clean = preg_replace("/class='([^']*)'/i", "class='$1 post-image" . ($index > 0 ? " lazy-image" : '') . "'", $clean, 1);
    } else {
        $clean = preg_replace('/<img/i', '<img class="post-image' . ($index > 0 ? ' lazy-image' : '') . '"', $clean, 1);
    }

    if ($index === 0 || !laoke_feature_enabled('lazyload')) {
        $replacement = 'src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" loading="eager" decoding="async" fetchpriority="high"';
    } else {
        $replacement = 'src="' . laoke_image_placeholder() . '" data-src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" loading="lazy" decoding="async"';
    }

    return preg_replace('/src=("|\')(.*?)\1/i', $replacement, $clean, 1);
}

function laoke_process_content_html($html)
{
    $index = 0;
    return preg_replace_callback('/<img\b[^>]*\bsrc=("|\')(.*?)\1[^>]*>/i', function ($matches) use (&$index) {
        $transformed = laoke_transform_image_html($matches[0], $matches[2], $index);
        $index++;
        return $transformed;
    }, $html);
}

function laoke_render_content_with_album_shortcodes($archive)
{
    return laoke_render_shortcode_content($archive, (string) $archive->text, (bool) $archive->isMarkdown, ['allowAlbums' => true]);
}

function laoke_content_html($archive)
{
    static $cache = [];

    $cid = is_object($archive) ? (int) ($archive->cid ?? 0) : 0;
    $key = $cid > 0 ? (string) $cid : (is_object($archive) ? spl_object_hash($archive) : md5((string) $archive));

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $cache[$key] = laoke_render_content_with_album_shortcodes($archive);
    return $cache[$key];
}

function laoke_has_visible_content_html($html)
{
    $html = trim((string) $html);
    if ($html === '') {
        return false;
    }

    if (laoke_strip_text($html) !== '') {
        return true;
    }

    return preg_match('/<(img|figure|video|audio|iframe|table|pre|blockquote|section)\b/i', $html) === 1;
}

function laoke_excerpt($archive, $limit = 150)
{
    $text = laoke_strip_text(laoke_content_html($archive));

    if (mb_strlen($text, 'UTF-8') > $limit) {
        $text = mb_substr($text, 0, $limit, 'UTF-8') . '...';
    }

    return $text;
}

function laoke_word_count($archive)
{
    return mb_strlen(laoke_strip_text(laoke_content_html($archive)), 'UTF-8');
}

function laoke_relative_time($timestamp)
{
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return '';
    }

    $diff = time() - $timestamp;
    if ($diff <= 0) {
        return '刚刚';
    }

    if ($diff < 3600) {
        $minutes = max(1, (int) floor($diff / 60));
        return $minutes . ' 分钟前';
    }

    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' 小时前';
    }

    if ($diff < 2592000) {
        $days = (int) floor($diff / 86400);
        return $days . ' 天前';
    }

    if ($diff < 31536000) {
        $months = (int) floor($diff / 2592000);
        return max(1, $months) . ' 个月前';
    }

    $years = (int) floor($diff / 31536000);
    return max(1, $years) . ' 年前';
}

function laoke_comment_render_context($archive = null)
{
    static $context = null;

    if (func_num_args() > 0) {
        $context = $archive;
    }

    return $context;
}

function laoke_is_time_machine_content($content)
{
    if (!is_object($content)) {
        return false;
    }

    $type = trim((string) $content->type);
    $template = basename(trim((string) $content->template));

    return $type === 'page' && $template === 'moments.php';
}

function laoke_is_album_content($content)
{
    if (!is_object($content)) {
        return false;
    }

    $type = trim((string) $content->type);
    $template = basename(trim((string) $content->template));

    return $type === 'page' && $template === 'albums.php';
}

function laoke_current_user_can_edit_content($content)
{
    if (!is_object($content)) {
        return false;
    }

    $user = \Widget\User::alloc();
    if (!is_object($user) || !$user->hasLogin()) {
        return false;
    }

    if ($user->pass('editor', true)) {
        return true;
    }

    $authorId = (int) $content->authorId;
    if ($authorId <= 0 && isset($content->author) && is_object($content->author)) {
        $authorId = (int) $content->author->uid;
    }

    return $authorId > 0 && (int) $user->uid === $authorId;
}

function laoke_can_publish_time_machine($content)
{
    if (!laoke_is_time_machine_content($content)) {
        return false;
    }

    return laoke_current_user_can_edit_content($content);
}

function laoke_body_class($archive)
{
    $classes = ['theme-laoke'];

    if ($archive->is('index')) {
        $classes[] = 'is-index';
    }
    if ($archive->is('post')) {
        $classes[] = 'is-post';
    }
    if ($archive->is('page')) {
        $classes[] = 'is-page';
    }
    if ($archive->is('archive')) {
        $classes[] = 'is-archive';
    }
    if ($archive->is('single')) {
        $classes[] = 'is-single';
    }

    return implode(' ', $classes);
}

function laoke_page_title($archive)
{
    ob_start();
    $archive->archiveTitle(
        [
            'category' => _t('%s'),
            'search' => _t('搜索 %s'),
            'tag' => _t('%s'),
            'author' => _t('%s')
        ],
        '',
        ' - '
    );
    $prefix = trim(ob_get_clean());

    if ($prefix !== '') {
        return $prefix . Helper::options()->title;
    }

    return Helper::options()->title;
}

function laoke_meta_description($archive)
{
    if ($archive->is('single')) {
        return laoke_excerpt($archive, 120);
    }

    return Helper::options()->description;
}

function laoke_comment_form_token($archive)
{
    if (!is_object($archive) || !isset($archive->options) || !$archive->options->commentsAntiSpam || !$archive->is('single')) {
        return '';
    }

    $requestUrl = method_exists($archive->request, 'getRequestUrl') ? (string) $archive->request->getRequestUrl() : '';
    if ($requestUrl === '') {
        return '';
    }

    return \Widget\Security::alloc()->getToken($requestUrl);
}

function laoke_comment_captcha_ttl()
{
    return 900;
}

function laoke_comment_captcha_payload($content, $a, $b, $ts)
{
    $cid = is_object($content) && isset($content->cid) ? (int) $content->cid : 0;
    return 'comment-captcha|' . $cid . '|' . (int) $a . '|' . (int) $b . '|' . (int) $ts;
}

function laoke_comment_captcha_signature($content, $a, $b, $ts)
{
    $payload = laoke_comment_captcha_payload($content, $a, $b, $ts);
    $secret = (string) Helper::options()->secret;

    if (function_exists('hash_hmac')) {
        return hash_hmac('sha256', $payload, $secret);
    }

    return md5($secret . '|' . $payload);
}

function laoke_comment_captcha_random_number($min = 1, $max = 9)
{
    if (function_exists('random_int')) {
        return random_int((int) $min, (int) $max);
    }

    return mt_rand((int) $min, (int) $max);
}

function laoke_comment_captcha_data($content)
{
    $a = laoke_comment_captcha_random_number(1, 9);
    $b = laoke_comment_captcha_random_number(1, 9);
    $ts = time();

    return [
        'a' => $a,
        'b' => $b,
        'ts' => $ts,
        'sig' => laoke_comment_captcha_signature($content, $a, $b, $ts)
    ];
}

function laoke_comment_request_value($name)
{
    return trim((string) ($_POST[$name] ?? ''));
}

function laoke_comment_request_is_secret()
{
    $value = trim((string) ($_POST['secret'] ?? ''));
    return $value !== '' && $value !== '0';
}

function laoke_comment_is_guest_user()
{
    $user = \Widget\User::alloc();
    return !is_object($user) || !$user->hasLogin();
}

function laoke_comment_verify_guest_captcha($content)
{
    if (laoke_is_time_machine_content($content)) {
        return;
    }

    if (!laoke_comment_is_guest_user()) {
        return;
    }

    $answerRaw = laoke_comment_request_value('laoke_captcha_answer');
    $aRaw = laoke_comment_request_value('laoke_captcha_a');
    $bRaw = laoke_comment_request_value('laoke_captcha_b');
    $tsRaw = laoke_comment_request_value('laoke_captcha_ts');
    $sig = laoke_comment_request_value('laoke_captcha_sig');

    if ($answerRaw === '') {
        throw new \Typecho\Exception('请输入验证码结果。', 403);
    }

    if (!preg_match('/^\d+$/', $answerRaw)) {
        throw new \Typecho\Exception('验证码需要填写数字结果。', 403);
    }

    if (!preg_match('/^\d+$/', $aRaw) || !preg_match('/^\d+$/', $bRaw) || !preg_match('/^\d+$/', $tsRaw) || $sig === '') {
        throw new \Typecho\Exception('验证码已失效，请刷新后重试。', 403);
    }

    $a = (int) $aRaw;
    $b = (int) $bRaw;
    $ts = (int) $tsRaw;
    $now = time();

    if ($a < 0 || $a > 99 || $b < 0 || $b > 99) {
        throw new \Typecho\Exception('验证码已失效，请刷新后重试。', 403);
    }

    if ($ts <= 0 || $ts > $now + 300 || ($now - $ts) > laoke_comment_captcha_ttl()) {
        throw new \Typecho\Exception('验证码已过期，请刷新后重试。', 403);
    }

    $expected = laoke_comment_captcha_signature($content, $a, $b, $ts);
    if (!laoke_hash_equals($expected, $sig)) {
        throw new \Typecho\Exception('验证码签名校验失败，请刷新后重试。', 403);
    }

    if ((int) $answerRaw !== ($a + $b)) {
        throw new \Typecho\Exception('验证码结果不正确。', 403);
    }
}

function laoke_comment_extract_secret_text($text)
{
    $text = (string) $text;
    if (!preg_match('/^\s*\[secret\](.*?)\[\/secret\]\s*$/isu', $text, $matches)) {
        return null;
    }

    $inner = (string) $matches[1];

    if (strncmp($inner, "\r\n", 2) === 0) {
        $inner = substr($inner, 2);
    } elseif ($inner !== '' && ($inner[0] === "\r" || $inner[0] === "\n")) {
        $inner = substr($inner, 1);
    }

    if (substr($inner, -2) === "\r\n") {
        $inner = substr($inner, 0, -2);
    } elseif ($inner !== '' && (substr($inner, -1) === "\r" || substr($inner, -1) === "\n")) {
        $inner = substr($inner, 0, -1);
    }

    return preg_replace('/^(?:\s*&nbsp;|\s*&#160;|\x{00A0})/u', '', $inner, 1);
}

function laoke_comment_wrap_secret_text($text)
{
    $text = (string) $text;
    if ($text === '' || laoke_comment_extract_secret_text($text) !== null) {
        return $text;
    }

    return '[secret]' . $text . '[/secret]';
}

function laoke_secret_comment_cookie_name()
{
    return '__laoke_secret_comments';
}

function laoke_secret_comment_cookie_signature($body)
{
    $payload = 'secret-comments|' . (string) $body;
    $secret = (string) Helper::options()->secret;

    if (function_exists('hash_hmac')) {
        return hash_hmac('sha256', $payload, $secret);
    }

    return md5($secret . '|' . $payload);
}

function laoke_secret_comment_cookie_encode($payload)
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || $json === '') {
        return '';
    }

    return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
}

function laoke_secret_comment_cookie_decode($body)
{
    $body = trim((string) $body);
    if ($body === '') {
        return [];
    }

    $padding = strlen($body) % 4;
    if ($padding > 0) {
        $body .= str_repeat('=', 4 - $padding);
    }

    $json = base64_decode(strtr($body, '-_', '+/'), true);
    if (!is_string($json) || $json === '') {
        return [];
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function laoke_secret_comment_cookie_entries()
{
    $cookie = trim((string) \Typecho\Cookie::get(laoke_secret_comment_cookie_name(), ''));
    if ($cookie === '' || strpos($cookie, '.') === false) {
        return [];
    }

    [$body, $signature] = explode('.', $cookie, 2);
    if ($body === '' || $signature === '') {
        return [];
    }

    if (!laoke_hash_equals(laoke_secret_comment_cookie_signature($body), $signature)) {
        return [];
    }

    $entries = [];
    $items = laoke_secret_comment_cookie_decode($body);
    foreach ($items as $coid => $timestamp) {
        $commentId = (int) $coid;
        $createdAt = (int) $timestamp;
        if ($commentId > 0 && $createdAt > 0) {
            $entries[$commentId] = $createdAt;
        }
    }

    arsort($entries);
    return $entries;
}

function laoke_secret_comment_cookie_store(array $entries)
{
    arsort($entries);
    $entries = array_slice($entries, 0, 64, true);
    $body = laoke_secret_comment_cookie_encode($entries);
    if ($body === '') {
        return;
    }

    \Typecho\Cookie::set(
        laoke_secret_comment_cookie_name(),
        $body . '.' . laoke_secret_comment_cookie_signature($body),
        time() + 31536000
    );
}

function laoke_secret_comment_cookie_has($coid)
{
    $coid = (int) $coid;
    if ($coid <= 0) {
        return false;
    }

    $entries = laoke_secret_comment_cookie_entries();
    return isset($entries[$coid]);
}

function laoke_mark_secret_comment_author($coid)
{
    $coid = (int) $coid;
    if ($coid <= 0) {
        return;
    }

    $entries = laoke_secret_comment_cookie_entries();
    $entries[$coid] = time();
    laoke_secret_comment_cookie_store($entries);
}

function laoke_render_categories($archive, $limit = 3)
{
    $categories = $archive->categories ?: [];
    if (empty($categories)) {
        return;
    }

    $count = 0;
    foreach ($categories as $category) {
        if ($count >= $limit) {
            break;
        }
        echo '<a class="chip" href="' . htmlspecialchars($category['permalink'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') . '</a>';
        $count++;
    }
}

function laoke_render_tags($archive, $limit = 6)
{
    $tags = $archive->tags ?: [];
    if (empty($tags)) {
        echo '<span class="meta-muted">无标签</span>';
        return;
    }

    $count = 0;
    foreach ($tags as $tag) {
        if ($count >= $limit) {
            break;
        }
        echo '<a class="chip chip-light" href="' . htmlspecialchars($tag['permalink'], ENT_QUOTES, 'UTF-8') . '"># ' . htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') . '</a>';
        $count++;
    }
}

function laoke_ensure_views_column()
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;
    $db = Typecho_Db::get();
    $row = $db->fetchRow($db->select()->from('table.contents')->limit(1));
    if (is_array($row) && array_key_exists('views', $row)) {
        return;
    }

    $prefix = $db->getPrefix();
    $adapter = strtolower($db->getAdapterName());

    try {
        if (strpos($adapter, 'sqlite') !== false) {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD COLUMN views INTEGER DEFAULT 0', Typecho_Db::WRITE);
        } else {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `views` INT(10) DEFAULT 0', Typecho_Db::WRITE);
        }
    } catch (Exception $e) {
    }
}

function laoke_track_views($archive)
{
    static $tracked = [];

    $cid = (int) $archive->cid;
    if ($cid <= 0 || isset($tracked[$cid])) {
        return;
    }

    $tracked[$cid] = true;
    laoke_ensure_views_column();

    $db = Typecho_Db::get();
    $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid)->limit(1));
    $views = isset($row['views']) ? (int) $row['views'] : 0;
    $db->query($db->update('table.contents')->rows(['views' => $views + 1])->where('cid = ?', $cid), Typecho_Db::WRITE);
}

function laoke_get_views($archive)
{
    $cid = is_object($archive) ? (int) $archive->cid : (int) $archive;
    if ($cid <= 0) {
        return 0;
    }

    laoke_ensure_views_column();
    $db = Typecho_Db::get();
    $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid)->limit(1));
    return isset($row['views']) ? (int) $row['views'] : 0;
}

function laoke_get_views_map(array $cids)
{
    static $cache = [];

    $cids = array_values(array_unique(array_filter(array_map('intval', $cids))));
    if (empty($cids)) {
        return [];
    }

    $result = [];
    $missing = [];

    foreach ($cids as $cid) {
        if (array_key_exists($cid, $cache)) {
            $result[$cid] = $cache[$cid];
            continue;
        }
        $missing[] = $cid;
    }

    if (!empty($missing)) {
        laoke_ensure_views_column();
        $db = Typecho_Db::get();
        $rows = $db->fetchAll(
            $db->select('cid', 'views')
                ->from('table.contents')
                ->where('cid IN ?', $missing)
        );

        $fetched = [];
        foreach ($rows as $row) {
            $fetched[(int) $row['cid']] = isset($row['views']) ? (int) $row['views'] : 0;
        }

        foreach ($missing as $cid) {
            $cache[$cid] = $fetched[$cid] ?? 0;
            $result[$cid] = $cache[$cid];
        }
    }

    return $result;
}

function laoke_adjacent_post($archive, $direction = 'prev')
{
    if (!is_object($archive) || !method_exists($archive, 'select')) {
        return null;
    }

    $cid = isset($archive->cid) ? (int) $archive->cid : 0;
    if ($cid <= 0) {
        return null;
    }

    $direction = strtolower(trim((string) $direction)) === 'next' ? 'next' : 'prev';
    static $cache = [];
    $cacheKey = $cid . ':' . $direction;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $options = Helper::options();
    $upperTime = is_object($options) && isset($options->time) ? (int) $options->time : time();

    $query = $archive->select()
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.type = ?', (string) $archive->type)
        ->where("table.contents.password IS NULL OR table.contents.password = ''")
        ->limit(1);

    if ($direction === 'next') {
        $query->where(
            'table.contents.created > ? AND table.contents.created < ?',
            $archive->created,
            $upperTime
        )->order('table.contents.created', Typecho_Db::SORT_ASC);
    } else {
        $query->where('table.contents.created < ?', $archive->created)
            ->order('table.contents.created', Typecho_Db::SORT_DESC);
    }

    $content = \Widget\Contents\From::allocWithAlias('laoke-adjacent-' . $cacheKey, ['query' => $query]);
    if (!$content->have()) {
        $cache[$cacheKey] = null;
        return null;
    }

    $cache[$cacheKey] = [
        'title' => trim((string) $content->title),
        'permalink' => trim((string) $content->permalink)
    ];

    return $cache[$cacheKey];
}

function laoke_total_posts()
{
    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select(['COUNT(cid)' => 'count'])
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
    );
    return isset($row['count']) ? (int) $row['count'] : 0;
}

function laoke_total_categories()
{
    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select(['COUNT(mid)' => 'count'])
            ->from('table.metas')
            ->where('type = ?', 'category')
    );
    return isset($row['count']) ? (int) $row['count'] : 0;
}

function laoke_total_tags()
{
    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select(['COUNT(mid)' => 'count'])
            ->from('table.metas')
            ->where('type = ?', 'tag')
    );
    return isset($row['count']) ? (int) $row['count'] : 0;
}

function laoke_total_views()
{
    static $total = null;

    if ($total !== null) {
        return $total;
    }

    laoke_ensure_views_column();
    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select(['SUM(views)' => 'total'])
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
    );

    $total = isset($row['total']) ? (int) $row['total'] : 0;
    return $total;
}

function laoke_total_words()
{
    static $total = null;

    if ($total !== null) {
        return $total;
    }

    $db = Typecho_Db::get();
    $rows = $db->fetchAll(
        $db->select('text')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
    );

    $total = 0;
    foreach ($rows as $row) {
        $total += mb_strlen(laoke_strip_text((string) ($row['text'] ?? '')), 'UTF-8');
    }

    return $total;
}

function laoke_site_running_days()
{
    $startDate = trim((string) laoke_option('siteStartDate', ''));
    if ($startDate !== '') {
        try {
            $start = new DateTime($startDate . ' 00:00:00');
            $today = new DateTime(date('Y-m-d') . ' 00:00:00');
            $diff = (int) $start->diff($today)->format('%r%a');
            return max(0, $diff);
        } catch (Exception $e) {
        }
    }

    return max(0, (int) laoke_option('siteRunningDays', '0'));
}

function laoke_time_machine_total_entries($cid)
{
    $cid = (int) $cid;
    if ($cid <= 0) {
        return 0;
    }

    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select(['COUNT(coid)' => 'count'])
            ->from('table.comments')
            ->where('cid = ?', $cid)
            ->where('parent = ?', 0)
            ->where('type = ?', 'comment')
            ->where('status = ?', 'approved')
    );

    return isset($row['count']) ? (int) $row['count'] : 0;
}

function laoke_is_sqlite_adapter()
{
    $db = Typecho_Db::get();
    return strpos(strtolower($db->getAdapterName()), 'sqlite') !== false;
}

function laoke_ensure_comment_likes_column()
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;
    $db = Typecho_Db::get();

    try {
        $db->fetchRow($db->select('likes')->from('table.comments')->limit(1));
        return;
    } catch (Exception $e) {
    }

    $prefix = $db->getPrefix();

    try {
        if (laoke_is_sqlite_adapter()) {
            $db->query('ALTER TABLE `' . $prefix . 'comments` ADD COLUMN likes INTEGER DEFAULT 0', Typecho_Db::WRITE);
        } else {
            $db->query('ALTER TABLE `' . $prefix . 'comments` ADD `likes` INT(10) DEFAULT 0', Typecho_Db::WRITE);
        }
    } catch (Exception $e) {
    }
}

function laoke_time_machine_like_table()
{
    return Typecho_Db::get()->getPrefix() . 'laoke_comment_likes';
}

function laoke_ensure_time_machine_like_table()
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;
    $db = Typecho_Db::get();
    $table = laoke_time_machine_like_table();

    try {
        if (laoke_is_sqlite_adapter()) {
            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $table . '` (' .
                '`id` INTEGER PRIMARY KEY AUTOINCREMENT,' .
                '`coid` INTEGER NOT NULL,' .
                '`token` TEXT NOT NULL,' .
                '`created` INTEGER NOT NULL DEFAULT 0,' .
                'UNIQUE(`coid`, `token`)' .
                ')',
                Typecho_Db::WRITE
            );
            $db->query(
                'CREATE INDEX IF NOT EXISTS `' . $table . '_coid` ON `' . $table . '` (`coid`)',
                Typecho_Db::WRITE
            );
        } else {
            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $table . '` (' .
                '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,' .
                '`coid` INT UNSIGNED NOT NULL,' .
                '`token` VARCHAR(64) NOT NULL,' .
                '`created` INT UNSIGNED NOT NULL DEFAULT 0,' .
                'PRIMARY KEY (`id`),' .
                'UNIQUE KEY `coid_token` (`coid`, `token`),' .
                'KEY `coid` (`coid`)' .
                ') DEFAULT CHARSET=utf8mb4',
                Typecho_Db::WRITE
            );
        }
    } catch (Exception $e) {
    }
}

function laoke_ensure_time_machine_like_storage()
{
    laoke_ensure_comment_likes_column();
    laoke_ensure_time_machine_like_table();
}

function laoke_time_machine_like_token()
{
    $token = trim((string) \Typecho\Cookie::get('__laoke_moment_like', ''));
    if ($token !== '') {
        return $token;
    }

    try {
        $token = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $token = md5(uniqid((string) mt_rand(), true));
    }

    \Typecho\Cookie::set('__laoke_moment_like', $token, time() + 31536000);
    return $token;
}

function laoke_time_machine_liked_map(array $coids)
{
    static $cache = [];

    $coids = array_values(array_unique(array_filter(array_map('intval', $coids))));
    if (empty($coids)) {
        return [];
    }

    laoke_ensure_time_machine_like_storage();
    $token = laoke_time_machine_like_token();
    $missing = [];
    $result = [];

    foreach ($coids as $coid) {
        if (array_key_exists($coid, $cache)) {
            $result[$coid] = $cache[$coid];
            continue;
        }
        $missing[] = $coid;
    }

    if (!empty($missing)) {
        $db = Typecho_Db::get();
        $rows = $db->fetchAll(
            $db->select('coid')
                ->from(laoke_time_machine_like_table())
                ->where('token = ?', $token)
                ->where('coid IN ?', $missing)
        );

        $liked = [];
        foreach ($rows as $row) {
            $liked[(int) $row['coid']] = true;
        }

        foreach ($missing as $coid) {
            $cache[$coid] = isset($liked[$coid]);
            $result[$coid] = $cache[$coid];
        }
    }

    return $result;
}

function laoke_get_comment_likes($coid)
{
    static $cache = [];

    $coid = (int) $coid;
    if ($coid <= 0) {
        return 0;
    }

    if (isset($cache[$coid])) {
        return $cache[$coid];
    }

    laoke_ensure_comment_likes_column();
    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select('likes')
            ->from('table.comments')
            ->where('coid = ?', $coid)
            ->limit(1)
    );

    $cache[$coid] = isset($row['likes']) ? (int) $row['likes'] : 0;
    return $cache[$coid];
}

function laoke_sync_comment_likes($coid)
{
    $coid = (int) $coid;
    if ($coid <= 0) {
        return 0;
    }

    laoke_ensure_time_machine_like_storage();
    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select(['COUNT(id)' => 'count'])
            ->from(laoke_time_machine_like_table())
            ->where('coid = ?', $coid)
    );

    $count = isset($row['count']) ? (int) $row['count'] : 0;
    $db->query(
        $db->update('table.comments')->rows(['likes' => $count])->where('coid = ?', $coid),
        Typecho_Db::WRITE
    );

    return $count;
}

function laoke_time_machine_comment_row($coid)
{
    $coid = (int) $coid;
    if ($coid <= 0) {
        return null;
    }

    $db = Typecho_Db::get();
    $comment = $db->fetchRow(
        $db->select('coid', 'cid', 'parent', 'status', 'type')
            ->from('table.comments')
            ->where('coid = ?', $coid)
            ->limit(1)
    );

    if (empty($comment)) {
        return null;
    }

    $content = $db->fetchRow(
        $db->select('cid', 'type', 'template', 'status')
            ->from('table.contents')
            ->where('cid = ?', (int) $comment['cid'])
            ->limit(1)
    );

    if (empty($content)) {
        return null;
    }

    return array_merge($comment, [
        'contentType' => (string) ($content['type'] ?? ''),
        'contentTemplate' => (string) ($content['template'] ?? ''),
        'contentStatus' => (string) ($content['status'] ?? '')
    ]);
}

function laoke_throw_json($archive, array $payload, $status = 200)
{
    $archive->response->setStatus((int) $status);
    $archive->response->throwJson($payload);
}

function laoke_handle_time_machine_like($archive)
{
    $coid = $archive->request->filter('int')->get('coid');
    $toggle = trim((string) $archive->request->get('toggle'));

    if ($coid <= 0 || !in_array($toggle, ['like', 'unlike'], true)) {
        laoke_throw_json($archive, ['ok' => false, 'message' => '请求无效。'], 400);
    }

    $comment = laoke_time_machine_comment_row($coid);
    if (
        !$comment ||
        (int) ($comment['parent'] ?? 0) !== 0 ||
        ($comment['status'] ?? '') !== 'approved' ||
        ($comment['type'] ?? '') !== 'comment' ||
        ($comment['contentStatus'] ?? '') !== 'publish' ||
        ($comment['contentType'] ?? '') !== 'page' ||
        basename((string) ($comment['contentTemplate'] ?? '')) !== 'moments.php'
    ) {
        laoke_throw_json($archive, ['ok' => false, 'message' => '这条内容暂不支持点赞。'], 403);
    }

    laoke_ensure_time_machine_like_storage();
    $token = laoke_time_machine_like_token();
    $db = Typecho_Db::get();
    $table = laoke_time_machine_like_table();
    $exists = $db->fetchRow(
        $db->select('id')
            ->from($table)
            ->where('coid = ?', $coid)
            ->where('token = ?', $token)
            ->limit(1)
    );

    if ($toggle === 'like') {
        if (empty($exists)) {
            try {
                $db->query(
                    $db->insert($table)->rows([
                        'coid' => $coid,
                        'token' => $token,
                        'created' => time()
                    ]),
                    Typecho_Db::WRITE
                );
            } catch (Exception $e) {
            }
        }
        $liked = true;
    } else {
        if (!empty($exists)) {
            $db->query(
                $db->delete($table)
                    ->where('coid = ?', $coid)
                    ->where('token = ?', $token),
                Typecho_Db::WRITE
            );
        }
        $liked = false;
    }

    $likes = laoke_sync_comment_likes($coid);
    laoke_throw_json($archive, [
        'ok' => true,
        'liked' => $liked,
        'likes' => $likes
    ]);
}

function laoke_filter_time_machine_comment($comment, $content)
{
    if (!laoke_is_time_machine_content($content)) {
        return $comment;
    }

    if (!laoke_can_publish_time_machine($content)) {
        throw new \Typecho\Exception('当前页面仅允许页面作者或具备编辑权限的账号发布动态。', 403);
    }

    $text = trim((string) ($comment['text'] ?? ''));
    if ($text === '') {
        throw new \Typecho\Exception('动态内容不能为空。', 403);
    }

    $comment['parent'] = 0;
    return $comment;
}

function laoke_filter_comment_submission($comment, $content)
{
    if (laoke_is_time_machine_content($content)) {
        if (!laoke_can_publish_time_machine($content)) {
            throw new \Typecho\Exception('当前页面仅允许页面作者或具备编辑权限的账号发布动态。', 403);
        }

        $text = trim((string) ($comment['text'] ?? ''));
        if ($text === '') {
            throw new \Typecho\Exception('动态内容不能为空。', 403);
        }

        $comment['parent'] = 0;
        return $comment;
    }

    laoke_comment_verify_guest_captcha($content);

    if (laoke_comment_request_is_secret()) {
        $comment['text'] = laoke_comment_wrap_secret_text((string) ($comment['text'] ?? ''));
    }

    return $comment;
}

function laoke_after_comment_submission($comment)
{
    if (!is_object($comment)) {
        return;
    }

    $content = null;
    if (isset($comment->parentContent)) {
        $content = $comment->parentContent;
    }

    if (laoke_is_time_machine_content($content)) {
        return;
    }

    $coid = isset($comment->coid) ? (int) $comment->coid : 0;
    $text = isset($comment->text) ? (string) $comment->text : '';
    if ($coid <= 0 || laoke_comment_extract_secret_text($text) === null) {
        return;
    }

    laoke_mark_secret_comment_author($coid);
}

function laoke_owo_items()
{
    static $items = null;

    if ($items !== null) {
        return $items;
    }

    $items = [];
    $options = Helper::options();
    $dir = $options->themeFile($options->theme, 'assets/owo');
    $files = glob(rtrim($dir, '\\/') . '/*.png') ?: [];

    natsort($files);

    foreach ($files as $file) {
        $fileName = basename($file);
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        if ($name === '') {
            continue;
        }

        $items[$name] = [
            'name' => $name,
            'url' => laoke_theme_url('assets/owo/' . rawurlencode($fileName))
        ];
    }

    return $items;
}

function laoke_render_owo_html($html)
{
    $html = (string) $html;
    $items = laoke_owo_items();

    if ($html === '' || empty($items)) {
        return $html;
    }

    $protectedBlocks = [];
    $html = preg_replace_callback('/<pre\b[^>]*>.*?<\/pre>|<code\b[^>]*>.*?<\/code>/is', function ($matches) use (&$protectedBlocks) {
        $token = '<!--laoke-owo-block-' . count($protectedBlocks) . '-->';
        $protectedBlocks[$token] = $matches[0];
        return $token;
    }, $html);

    $html = preg_replace_callback('/\[owo:([^\]\r\n]+)\]/u', function ($matches) use ($items) {
        $name = trim((string) $matches[1]);

        if ($name === '' || !isset($items[$name])) {
            return $matches[0];
        }

        $item = $items[$name];
        $escapedName = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
        $escapedUrl = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');

        return '<img class="laoke-emoji comment-emoji" src="' . $escapedUrl . '" alt="' . $escapedName . '" title="' . $escapedName . '" loading="lazy" decoding="async">';
    }, $html);

    if (!empty($protectedBlocks)) {
        $html = strtr($html, $protectedBlocks);
    }

    return $html;
}

function laoke_comment_content_html($comments)
{
    $archive = laoke_comment_render_context();
    if (!laoke_is_time_machine_content($archive)) {
        $secretText = laoke_comment_extract_secret_text(is_object($comments) ? (string) $comments->text : '');
        if ($secretText !== null) {
            $html = laoke_comment_can_view_secret($comments, $archive)
                ? laoke_comment_text_html($comments, $secretText)
                : laoke_comment_private_placeholder_html();

            return laoke_render_owo_html($html);
        }
    }

    ob_start();
    $comments->content();
    return laoke_render_owo_html(ob_get_clean());
}

function laoke_comment_parent_author($comments)
{
    static $cache = [];

    $parentId = is_object($comments) && isset($comments->parent) ? (int) $comments->parent : 0;
    if ($parentId <= 0) {
        return '';
    }

    if (isset($cache[$parentId])) {
        return $cache[$parentId];
    }

    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select('author')
            ->from('table.comments')
            ->where('coid = ?', $parentId)
            ->limit(1)
    );

    $cache[$parentId] = isset($row['author']) ? trim((string) $row['author']) : '';
    return $cache[$parentId];
}

function laoke_comment_parent_mail($comments)
{
    static $cache = [];

    $parentId = is_object($comments) && isset($comments->parent) ? (int) $comments->parent : (int) $comments;
    if ($parentId <= 0) {
        return '';
    }

    if (isset($cache[$parentId])) {
        return $cache[$parentId];
    }

    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select('mail', 'status')
            ->from('table.comments')
            ->where('coid = ?', $parentId)
            ->limit(1)
    );

    $cache[$parentId] = is_array($row) && (string) ($row['status'] ?? '') === 'approved'
        ? strtolower(trim((string) ($row['mail'] ?? '')))
        : '';

    return $cache[$parentId];
}

function laoke_comment_viewer_mail($archive = null)
{
    $user = \Widget\User::alloc();
    if (is_object($user) && $user->hasLogin()) {
        return strtolower(trim((string) $user->mail));
    }

    if (is_object($archive) && method_exists($archive, 'remember')) {
        return strtolower(trim((string) $archive->remember('mail', true)));
    }

    return strtolower(trim((string) \Typecho\Cookie::get('__typecho_remember_mail', '')));
}

function laoke_comment_can_view_secret($comments, $archive = null)
{
    if (!is_object($comments)) {
        return false;
    }

    if ($archive === null) {
        $archive = laoke_comment_render_context();
    }

    if (laoke_current_user_can_edit_content($archive)) {
        return true;
    }

    if (isset($comments->coid) && laoke_secret_comment_cookie_has((int) $comments->coid)) {
        return true;
    }

    $viewerMail = laoke_comment_viewer_mail($archive);
    if ($viewerMail === '') {
        return false;
    }

    $authorMail = strtolower(trim((string) ($comments->mail ?? '')));
    if ($authorMail !== '' && laoke_hash_equals($authorMail, $viewerMail)) {
        return true;
    }

    $parentMail = laoke_comment_parent_mail($comments);
    return $parentMail !== '' && laoke_hash_equals($parentMail, $viewerMail);
}

function laoke_comment_private_placeholder_html()
{
    return '<div class="comment-private-placeholder"><p class="comment-private-placeholder__eyebrow">私密评论</p><p class="comment-private-placeholder__text">这条评论仅评论相关方可见。</p></div>';
}

function laoke_comment_text_html($comments, $text)
{
    $text = (string) $text;
    if ($text === '' || !is_object($comments)) {
        return '';
    }

    $options = Helper::options();
    $html = $options->commentsMarkdown
        ? $comments->markdown($text)
        : $comments->autoP($text);

    return \Typecho\Common::stripTags((string) $html, '<p><br>' . (string) $options->commentsHTMLTagAllowed);
}

function laoke_render_text_block($text, $isMarkdown = true, $content = null)
{
    return laoke_render_shortcode_content($content, $text, $isMarkdown, ['allowAlbums' => true]);
}

function laoke_image_source_attrs($src, $eager = false)
{
    $src = trim((string) $src);
    if ($src === '') {
        return 'src="' . laoke_image_placeholder() . '"';
    }

    $escaped = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
    if ($eager || !laoke_feature_enabled('lazyload')) {
        return 'src="' . $escaped . '" loading="eager" decoding="async"';
    }

    return 'src="' . laoke_image_placeholder() . '" data-src="' . $escaped . '" loading="lazy" decoding="async"';
}

function laoke_album_unlock_cookie_name($cid, $key)
{
    return '__laoke_album_unlock_' . (int) $cid . '_' . substr(md5((string) $key), 0, 12);
}

function laoke_hash_equals($known, $user)
{
    $known = (string) $known;
    $user = (string) $user;

    if (function_exists('hash_equals')) {
        return hash_equals($known, $user);
    }

    return $known === $user;
}

function laoke_album_unlock_signature($cid, $key, $password)
{
    $payload = (int) $cid . '|' . (string) $key . '|' . (string) $password;
    $secret = (string) Helper::options()->secret;

    if (function_exists('hash_hmac')) {
        return hash_hmac('sha256', $payload, $secret);
    }

    return md5($secret . '|' . $payload);
}

function laoke_album_is_unlocked($content, $key, $password)
{
    if ((string) $password === '') {
        return true;
    }

    if (laoke_current_user_can_edit_content($content)) {
        return true;
    }

    $cid = is_object($content) ? (int) $content->cid : (int) $content;
    if ($cid <= 0 || trim((string) $key) === '') {
        return false;
    }

    $cookie = trim((string) \Typecho\Cookie::get(laoke_album_unlock_cookie_name($cid, $key), ''));
    if ($cookie === '') {
        return false;
    }

    return laoke_hash_equals(laoke_album_unlock_signature($cid, $key, $password), $cookie);
}

function laoke_mark_album_unlocked($content, $key, $password)
{
    $cid = is_object($content) ? (int) $content->cid : (int) $content;
    if ($cid <= 0 || trim((string) $key) === '' || (string) $password === '') {
        return;
    }

    \Typecho\Cookie::set(
        laoke_album_unlock_cookie_name($cid, $key),
        laoke_album_unlock_signature($cid, $key, $password)
    );
}

function laoke_parse_album_shortcode_attributes($raw)
{
    $attributes = [];
    if (!preg_match_all('/([a-zA-Z][\w-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/u', (string) $raw, $matches, PREG_SET_ORDER)) {
        return $attributes;
    }

    foreach ($matches as $match) {
        $value = isset($match[2]) && $match[2] !== '' ? $match[2] : ($match[3] ?? '');
        $attributes[strtolower($match[1])] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    return $attributes;
}

function laoke_extract_album_images($raw)
{
    $raw = (string) $raw;
    if ($raw === '') {
        return [];
    }

    $matched = [];

    if (preg_match_all('/!\[([^\]]*)\]\(([^)\r\n]+)\)/u', $raw, $markdownMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
        foreach ($markdownMatches as $match) {
            $inside = trim((string) $match[2][0]);
            $title = '';

            if (preg_match('/^(.+?)\s+"([^"]*)"$/u', $inside, $parts)) {
                $inside = trim((string) $parts[1]);
                $title = (string) $parts[2];
            }

            $src = trim($inside, "<> \t\r\n");
            if ($src === '') {
                continue;
            }

            $matched[] = [
                'offset' => (int) $match[0][1],
                'src' => html_entity_decode($src, ENT_QUOTES, 'UTF-8'),
                'alt' => html_entity_decode((string) $match[1][0], ENT_QUOTES, 'UTF-8'),
                'title' => html_entity_decode($title, ENT_QUOTES, 'UTF-8')
            ];
        }
    }

    if (preg_match_all('/<img\b[^>]*\bsrc=(["\'])(.*?)\1[^>]*>/iu', $raw, $htmlMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
        foreach ($htmlMatches as $match) {
            $tag = (string) $match[0][0];
            $src = html_entity_decode((string) $match[2][0], ENT_QUOTES, 'UTF-8');
            $alt = '';
            $title = '';

            if (preg_match('/\balt=(["\'])(.*?)\1/iu', $tag, $altMatch)) {
                $alt = html_entity_decode((string) $altMatch[2], ENT_QUOTES, 'UTF-8');
            }
            if (preg_match('/\btitle=(["\'])(.*?)\1/iu', $tag, $titleMatch)) {
                $title = html_entity_decode((string) $titleMatch[2], ENT_QUOTES, 'UTF-8');
            }

            if (trim($src) === '') {
                continue;
            }

            $matched[] = [
                'offset' => (int) $match[0][1],
                'src' => $src,
                'alt' => $alt,
                'title' => $title
            ];
        }
    }

    usort($matched, function ($left, $right) {
        return $left['offset'] <=> $right['offset'];
    });

    $images = [];
    foreach ($matched as $item) {
        $images[] = [
            'src' => trim((string) $item['src']),
            'alt' => trim((string) $item['alt']),
            'title' => trim((string) $item['title'])
        ];
    }

    return $images;
}

function laoke_build_album_data($content, array $attributes, $rawBody)
{
    $key = trim((string) ($attributes['key'] ?? ''));
    $title = trim((string) ($attributes['title'] ?? ''));

    if ($key === '' || $title === '' || !preg_match('/^[a-zA-Z0-9-]+$/', $key)) {
        return null;
    }

    $images = laoke_extract_album_images((string) $rawBody);
    $password = trim((string) ($attributes['password'] ?? ''));
    $isUnlocked = laoke_album_is_unlocked($content, $key, $password);
    $cover = trim((string) ($attributes['cover'] ?? ''));

    if ($cover === '' && !empty($images) && ($password === '' || $isUnlocked)) {
        $cover = (string) $images[0]['src'];
    }

    return [
        'key' => $key,
        'title' => $title,
        'desc' => trim((string) ($attributes['desc'] ?? '')),
        'cover' => $cover,
        'password' => $password,
        'protected' => $password !== '',
        'unlocked' => $isUnlocked,
        'imageCount' => count($images),
        'images' => $images
    ];
}

function laoke_content_albums($archive)
{
    static $cache = [];

    $cid = is_object($archive) ? (int) $archive->cid : 0;
    if ($cid <= 0) {
        return [];
    }

    if (isset($cache[$cid])) {
        return $cache[$cid];
    }

    $text = (string) $archive->text;
    $albums = [];
    if (trim($text) === '' || !preg_match_all('/\[album\b([^\]]*)\](.*?)\[\/album\]/isu', $text, $blocks, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
        $cache[$cid] = $albums;
        return $albums;
    }

    $seenKeys = [];
    foreach ($blocks as $block) {
        $attributes = laoke_parse_album_shortcode_attributes((string) $block[1][0]);
        $album = laoke_build_album_data($archive, $attributes, (string) $block[2][0]);
        if (!$album || isset($seenKeys[$album['key']])) {
            continue;
        }

        $seenKeys[$album['key']] = true;
        $albums[] = $album;
    }

    $cache[$cid] = $albums;
    return $albums;
}

function laoke_album_page_data($archive)
{
    static $cache = [];

    $cid = is_object($archive) ? (int) $archive->cid : 0;
    if ($cid <= 0) {
        return [
            'introHtml' => '',
            'albums' => []
        ];
    }

    if (isset($cache[$cid])) {
        return $cache[$cid];
    }

    $text = trim((string) $archive->text);
    $data = [
        'introHtml' => '',
        'albums' => []
    ];

    if ($text === '') {
        $cache[$cid] = $data;
        return $data;
    }

    if (!preg_match_all('/\[album\b([^\]]*)\](.*?)\[\/album\]/isu', $text, $blocks, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
        $data['introHtml'] = laoke_render_text_block($text, (bool) $archive->isMarkdown, $archive);
        $cache[$cid] = $data;
        return $data;
    }

    $firstOffset = (int) $blocks[0][0][1];
    $introRaw = trim((string) substr($text, 0, $firstOffset));
    $data['introHtml'] = laoke_render_text_block($introRaw, (bool) $archive->isMarkdown, $archive);

    $seenKeys = [];
    foreach ($blocks as $block) {
        $attributes = laoke_parse_album_shortcode_attributes((string) $block[1][0]);
        $album = laoke_build_album_data($archive, $attributes, (string) $block[2][0]);
        if (!$album || isset($seenKeys[$album['key']])) {
            continue;
        }

        $seenKeys[$album['key']] = true;
        $data['albums'][] = $album;
    }

    $cache[$cid] = $data;
    return $data;
}

function laoke_album_by_key($archive, $key)
{
    $key = trim((string) $key);
    if ($key === '') {
        return null;
    }

    foreach (laoke_content_albums($archive) as $album) {
        if ((string) $album['key'] === $key) {
            return $album;
        }
    }

    return null;
}

function laoke_render_album_card($album, $index = 0)
{
    $isProtected = !empty($album['protected']);
    $isUnlocked = !empty($album['unlocked']);
    $cover = trim((string) ($album['cover'] ?? ''));
    $imageCount = (int) ($album['imageCount'] ?? 0);

    ob_start();
    ?>
    <article class="album-card-wrap">
        <button
            class="album-card<?php if ($isProtected): ?> is-protected<?php endif; ?><?php if ($isUnlocked): ?> is-unlocked<?php endif; ?>"
            type="button"
            data-album-card
            data-album-key="<?php echo htmlspecialchars((string) $album['key'], ENT_QUOTES, 'UTF-8'); ?>"
            data-album-locked="<?php echo $isProtected && !$isUnlocked ? 'true' : 'false'; ?>"
        >
            <span class="album-card__cover<?php if ($cover === ''): ?> is-empty<?php endif; ?>">
                <?php if ($cover !== ''): ?>
                    <img <?php echo laoke_image_source_attrs($cover, $index < 2); ?> alt="<?php echo htmlspecialchars((string) $album['title'], ENT_QUOTES, 'UTF-8'); ?>" data-viewimages-ignore="true">
                <?php else: ?>
                    <span class="album-card__placeholder">Album</span>
                <?php endif; ?>
            </span>
            <span class="album-card__body">
                <span class="album-card__title"><?php echo htmlspecialchars((string) $album['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if (trim((string) ($album['desc'] ?? '')) !== ''): ?>
                    <span class="album-card__desc"><?php echo htmlspecialchars((string) $album['desc'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
                <span class="album-card__meta">
                    <span><?php echo number_format($imageCount); ?> 张</span>
                    <?php if ($isProtected): ?>
                        <span class="album-card__badge"><?php echo $isUnlocked ? '已解锁' : '加密访问'; ?></span>
                    <?php endif; ?>
                </span>
            </span>
        </button>
    </article>
    <?php
    return trim((string) ob_get_clean());
}

function laoke_render_album_detail($album)
{
    return laoke_render_album_detail_view($album, true);
}

function laoke_render_album_detail_view($album, $showBack = true)
{
    $images = is_array($album['images'] ?? null) ? $album['images'] : [];
    $title = (string) ($album['title'] ?? '');
    $desc = trim((string) ($album['desc'] ?? ''));
    $imageCount = (int) ($album['imageCount'] ?? count($images));

    ob_start();
    ?>
    <section class="album-detail" data-album-detail-view data-album-key="<?php echo htmlspecialchars((string) $album['key'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="album-detail__head">
            <?php if ($showBack): ?>
                <button class="album-detail__back" type="button" data-album-back>返回相册</button>
            <?php endif; ?>
            <div class="album-detail__summary">
                <h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="album-detail__meta">共 <?php echo number_format($imageCount); ?> 张<?php if (!empty($album['protected'])): ?> · 已解锁<?php endif; ?></p>
                <?php if ($desc !== ''): ?>
                    <p class="album-detail__desc"><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($images)): ?>
            <p class="empty-state">这个相册还没有图片。</p>
        <?php else: ?>
            <div class="post-content album-detail__content">
                <div class="album-detail__grid">
                    <?php foreach ($images as $index => $image): ?>
                        <?php
                        $caption = trim((string) ($image['title'] !== '' ? $image['title'] : $image['alt']));
                        ?>
                        <figure class="album-detail__item">
                            <img <?php echo laoke_image_source_attrs((string) $image['src'], $index < 3); ?> alt="<?php echo htmlspecialchars((string) $image['alt'], ENT_QUOTES, 'UTF-8'); ?>"<?php if (trim((string) $image['title']) !== ''): ?> title="<?php echo htmlspecialchars((string) $image['title'], ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
                            <?php if ($caption !== ''): ?>
                                <figcaption><?php echo htmlspecialchars($caption, ENT_QUOTES, 'UTF-8'); ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return trim((string) ob_get_clean());
}

function laoke_render_inline_album_shortcode($archive, $album)
{
    $isProtected = !empty($album['protected']);
    $isUnlocked = !empty($album['unlocked']);
    $title = htmlspecialchars((string) $album['title'], ENT_QUOTES, 'UTF-8');
    $desc = trim((string) ($album['desc'] ?? ''));
    $imageCount = (int) ($album['imageCount'] ?? 0);

    ob_start();
    ?>
    <section class="album-shortcode<?php if ($isProtected && !$isUnlocked): ?> is-locked<?php endif; ?>" data-inline-album data-album-key="<?php echo htmlspecialchars((string) $album['key'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($isProtected && !$isUnlocked): ?>
            <div class="album-inline-lock">
                <p class="album-inline-lock__eyebrow">加密相册</p>
                <h3 class="album-inline-lock__title"><?php echo $title; ?></h3>
                <?php if ($desc !== ''): ?>
                    <p class="album-inline-lock__desc"><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <p class="album-inline-lock__meta">共 <?php echo number_format($imageCount); ?> 张，输入密码后查看。</p>
                <form class="album-inline-lock__form" data-inline-album-form data-album-endpoint="<?php echo htmlspecialchars((string) $archive->permalink, ENT_QUOTES, 'UTF-8'); ?>" data-album-cid="<?php echo (int) $archive->cid; ?>" data-album-key="<?php echo htmlspecialchars((string) $album['key'], ENT_QUOTES, 'UTF-8'); ?>">
                    <label class="sr-only" for="inline-album-password-<?php echo htmlspecialchars((string) $album['key'], ENT_QUOTES, 'UTF-8'); ?>">相册密码</label>
                    <input id="inline-album-password-<?php echo htmlspecialchars((string) $album['key'], ENT_QUOTES, 'UTF-8'); ?>" class="album-inline-lock__input" type="password" name="password" placeholder="输入访问密码" autocomplete="current-password" required>
                    <button class="album-inline-lock__submit" type="submit">解锁相册</button>
                    <p class="album-inline-lock__feedback" data-inline-album-feedback aria-live="polite"></p>
                </form>
            </div>
        <?php else: ?>
            <?php echo laoke_render_album_detail_view($album, false); ?>
        <?php endif; ?>
    </section>
    <?php
    return trim((string) ob_get_clean());
}

function laoke_handle_album_unlock($archive)
{
    $cid = $archive->request->filter('int')->get('cid');
    $albumKey = trim((string) $archive->request->get('album_key'));
    $password = (string) $archive->request->get('password');

    if (!is_object($archive) || $cid !== (int) $archive->cid || $albumKey === '') {
        laoke_throw_json($archive, ['ok' => false, 'message' => '请求无效。'], 400);
    }

    $album = laoke_album_by_key($archive, $albumKey);
    if (!$album) {
        laoke_throw_json($archive, ['ok' => false, 'message' => '没有找到这个相册。'], 404);
    }

    if (!empty($album['protected'])) {
        $canBypass = laoke_current_user_can_edit_content($archive);
        if (!$canBypass && !laoke_hash_equals((string) $album['password'], $password)) {
            laoke_throw_json($archive, ['ok' => false, 'message' => '密码不正确。'], 403);
        }

        laoke_mark_album_unlocked($archive, (string) $album['key'], (string) $album['password']);
    }

    $album['unlocked'] = true;
    $display = trim((string) $archive->request->get('display'));
    laoke_throw_json($archive, [
        'ok' => true,
        'albumKey' => (string) $album['key'],
        'html' => $display === 'inline' ? laoke_render_album_detail_view($album, false) : laoke_render_album_detail($album)
    ]);
}

function laoke_links_grouped()
{
    $options = Helper::options();
    if (!isset($options->plugins['activated']['Links'])) {
        return [];
    }

    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $links = $db->fetchAll($db->select()->from($prefix . 'links')->order($prefix . 'links.order', Typecho_Db::SORT_ASC));
    $grouped = [];

    foreach ($links as $link) {
        $group = trim((string) $link['sort']) !== '' ? $link['sort'] : '未分组';
        if (!isset($grouped[$group])) {
            $grouped[$group] = [];
        }
        $grouped[$group][] = $link;
    }

    return $grouped;
}

function laoke_moment_plain_text($html)
{
    $text = preg_replace('/<img\b[^>]*>/i', '', $html);
    $text = preg_replace('/<a\b[^>]*>(.*?)<\/a>/is', '$1', $text);
    $text = laoke_strip_text($text);
    return $text !== '' ? $text : '发布了一条新的动态。';
}

function laoke_moment_images($html, $limit = 9)
{
    preg_match_all('/<img\b[^>]*\bsrc=("|\')(.*?)\1[^>]*>/i', $html, $matches);
    return array_slice($matches[2], 0, $limit);
}

function laoke_is_moment_post($archive, $slug)
{
    if ($slug === '') {
        return true;
    }

    $categories = $archive->categories ?: [];
    foreach ($categories as $category) {
        if (isset($category['slug']) && $category['slug'] === $slug) {
            return true;
        }
    }

    return false;
}

function laoke_render_json_ld($archive)
{
    $site = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => Helper::options()->title,
        'url' => Helper::options()->siteUrl
    ];

    echo '<script type="application/ld+json">' . json_encode($site, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

    if ($archive->is('post')) {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $archive->title,
            'datePublished' => date('c', $archive->created),
            'dateModified' => date('c', $archive->modified),
            'mainEntityOfPage' => $archive->permalink,
            'author' => [
                '@type' => 'Person',
                'name' => $archive->author->screenName
            ],
            'description' => laoke_excerpt($archive, 120)
        ];
        echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
