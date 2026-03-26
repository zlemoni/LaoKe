<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

function laoke_option_bool($name, $default = false)
{
    $value = laoke_option($name, $default ? '1' : '0');

    if (is_bool($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value === 1;
    }

    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return (bool) $default;
    }

    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function laoke_frontend_font_presets_legacy()
{
    $serif = 'Georgia, "Palatino Linotype", "Book Antiqua", "Source Han Serif SC", "Noto Serif CJK SC", serif';
    $ui = '"Helvetica Neue", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif';
    $song = '"STSong", "Songti SC", "Source Han Serif SC", "Noto Serif CJK SC", serif';

    return [
        'default' => [
            'label' => _t('默认衬线'),
            'body' => $serif,
            'title' => $serif,
            'ui' => $ui,
            'links' => []
        ],
        'mixed' => [
            'label' => _t('正文衬线 / 标题黑体'),
            'body' => $serif,
            'title' => $ui,
            'ui' => $ui,
            'links' => []
        ],
        'song' => [
            'label' => _t('宋体风格'),
            'body' => $song,
            'title' => $song,
            'ui' => $ui,
            'links' => []
        ],
        'sans' => [
            'label' => _t('全站无衬线'),
            'body' => $ui,
            'title' => $ui,
            'ui' => $ui,
            'links' => []
        ]
    ];
}

function laoke_frontend_font_presets()
{
    $serif = 'Georgia, "Palatino Linotype", "Book Antiqua", "Source Han Serif SC", "Noto Serif CJK SC", serif';
    $ui = '"Helvetica Neue", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif';
    $song = '"STSong", "Songti SC", "Source Han Serif SC", "Noto Serif CJK SC", serif';

    return [
        'default' => [
            'label' => _t('默认衬线'),
            'body' => $serif,
            'title' => $serif,
            'ui' => $ui,
            'links' => []
        ],
        'mixed' => [
            'label' => _t('正文衬线 / 标题无衬线'),
            'body' => $serif,
            'title' => $ui,
            'ui' => $ui,
            'links' => []
        ],
        'song' => [
            'label' => _t('宋体风格'),
            'body' => $song,
            'title' => $song,
            'ui' => $ui,
            'links' => []
        ],
        'sans' => [
            'label' => _t('全站无衬线'),
            'body' => $ui,
            'title' => $ui,
            'ui' => $ui,
            'links' => []
        ]
    ];
}

function laoke_frontend_font_config()
{
    $presets = laoke_frontend_font_presets();
    $defaultPreset = $presets['default'];
    $enabled = laoke_option_bool('fontEnabled', true);
    $presetKey = trim((string) laoke_option('fontPreset', 'default'));
    $customUrl = trim((string) laoke_option('fontCustomUrl', ''));
    $customFamily = trim((string) laoke_option('fontCustomFamily', ''));

    if (!$enabled) {
        return [
            'enabled' => false,
            'preset' => 'default',
            'links' => [],
            'body' => $defaultPreset['body'],
            'title' => $defaultPreset['title'],
            'ui' => $defaultPreset['ui']
        ];
    }

    if ($presetKey === 'custom' && $customFamily !== '') {
        $family = '"' . str_replace(['"', "\r", "\n"], '', $customFamily) . '"';

        return [
            'enabled' => true,
            'preset' => 'custom',
            'links' => $customUrl !== '' ? [$customUrl] : [],
            'body' => $family . ', ' . $defaultPreset['body'],
            'title' => $family . ', ' . $defaultPreset['title'],
            'ui' => $family . ', ' . $defaultPreset['ui']
        ];
    }

    if (!isset($presets[$presetKey])) {
        $presetKey = 'default';
    }

    $preset = $presets[$presetKey];

    return [
        'enabled' => true,
        'preset' => $presetKey,
        'links' => $preset['links'],
        'body' => $preset['body'],
        'title' => $preset['title'],
        'ui' => $preset['ui']
    ];
}

function laoke_css_font_value($value)
{
    return trim(preg_replace('/\s+/u', ' ', (string) $value));
}

function laoke_css_custom_property_value($value)
{
    $value = laoke_css_font_value($value);
    return str_replace(['</', '<', '>'], ['<\/', '', ''], $value);
}

function laoke_render_frontend_head_assets()
{
    $font = laoke_frontend_font_config();

    foreach (array_unique($font['links']) as $link) {
        $href = trim((string) $link);
        if ($href === '') {
            continue;
        }

        echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    echo '<style id="laoke-font-vars">:root{'
        . '--font-body:' . laoke_css_custom_property_value($font['body']) . ';'
        . '--font-title:' . laoke_css_custom_property_value($font['title']) . ';'
        . '--font-ui:' . laoke_css_custom_property_value($font['ui']) . ';'
        . '}</style>' . "\n";
}

function laoke_barrage_opacity()
{
    $raw = trim((string) laoke_option('barrageOpacity', '82'));
    if ($raw === '' || !is_numeric($raw)) {
        return 0.82;
    }

    $value = (float) $raw;
    if ($value > 1) {
        $value = $value / 100;
    }

    if ($value < 0.35) {
        $value = 0.35;
    } elseif ($value > 0.96) {
        $value = 0.96;
    }

    return round($value, 2);
}

function laoke_barrage_scope($archive)
{
    if (!is_object($archive) || !laoke_option_bool('barrageEnabled', true)) {
        return '';
    }

    if ($archive->is('index') && laoke_option_bool('barrageHomeEnabled', true)) {
        return 'home';
    }

    if ($archive->is('post') && !laoke_is_time_machine_content($archive) && laoke_option_bool('barragePostEnabled', true)) {
        return 'post';
    }

    return '';
}

function laoke_barrage_comment_html($text)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/\s+/u', ' ', $text);
    if (mb_strlen($text, 'UTF-8') > 56) {
        $text = mb_substr($text, 0, 56, 'UTF-8') . '...';
    }

    return laoke_render_owo_html(nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')));
}

function laoke_barrage_item_from_row(array $row)
{
    $text = trim((string) ($row['text'] ?? ''));
    if ($text === '' || laoke_comment_extract_secret_text($text) !== null) {
        return null;
    }

    $html = laoke_barrage_comment_html($text);
    if ($html === '') {
        return null;
    }

    $author = trim((string) ($row['author'] ?? '匿名访客'));
    $mail = trim((string) ($row['mail'] ?? ''));
    $title = trim((string) ($row['content_title'] ?? ''));

    return [
        'author' => $author !== '' ? $author : '匿名访客',
        'avatar' => laoke_comment_avatar($mail, 40),
        'contentHtml' => $html,
        'title' => $title
    ];
}

function laoke_barrage_home_rows($pool = 120)
{
    $db = Typecho_Db::get();

    return $db->fetchAll(
        $db->select(
            'table.comments.coid',
            'table.comments.author',
            'table.comments.mail',
            'table.comments.text',
            'table.contents.title AS content_title'
        )
        ->from('table.comments')
        ->join('table.contents', 'table.contents.cid = table.comments.cid', Typecho_Db::INNER_JOIN)
        ->where('table.comments.status = ?', 'approved')
        ->where('table.comments.type = ?', 'comment')
        ->where('table.contents.status = ?', 'publish')
        ->where('(table.contents.template IS NULL OR table.contents.template <> ?)', 'moments.php')
        ->order('table.comments.coid', Typecho_Db::SORT_DESC)
        ->limit((int) $pool)
    );
}

function laoke_barrage_post_rows($cid)
{
    $db = Typecho_Db::get();

    return $db->fetchAll(
        $db->select(
            'table.comments.coid',
            'table.comments.author',
            'table.comments.mail',
            'table.comments.text'
        )
        ->from('table.comments')
        ->where('table.comments.cid = ?', (int) $cid)
        ->where('table.comments.status = ?', 'approved')
        ->where('table.comments.type = ?', 'comment')
        ->order('table.comments.coid', Typecho_Db::SORT_DESC)
    );
}

function laoke_collect_barrage_items($archive, $limit = 24)
{
    $scope = laoke_barrage_scope($archive);
    if ($scope === '') {
        return [];
    }

    $rows = $scope === 'home'
        ? laoke_barrage_home_rows(120)
        : laoke_barrage_post_rows((int) $archive->cid);

    if ($scope === 'home' && count($rows) > 1) {
        shuffle($rows);
    }

    $items = [];
    foreach ($rows as $row) {
        $item = laoke_barrage_item_from_row((array) $row);
        if ($item === null) {
            continue;
        }

        $items[] = $item;
        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function laoke_frontend_barrage_config($archive)
{
    $scope = laoke_barrage_scope($archive);
    $items = $scope !== '' ? laoke_collect_barrage_items($archive) : [];

    return [
        'enabled' => $scope !== '' && !empty($items),
        'desktopOnly' => !laoke_option_bool('barrageMobileEnabled', false),
        'scope' => $scope,
        'opacity' => laoke_barrage_opacity(),
        'interval' => 2400,
        'items' => $items
    ];
}

function laoke_frontend_runtime_config($archive)
{
    $font = laoke_frontend_font_config();

    return [
        'ajax' => laoke_feature_enabled('ajax'),
        'progress' => laoke_feature_enabled('progress'),
        'lazyload' => laoke_feature_enabled('lazyload'),
        'metingEndpoint' => trim((string) laoke_option('metingEndpoint', 'https://api.injahow.cn/meting/')) ?: 'https://api.injahow.cn/meting/',
        'barrage' => laoke_frontend_barrage_config($archive),
        'fonts' => [
            'enabled' => $font['enabled'],
            'preset' => $font['preset'],
            'links' => $font['links'],
            'variables' => [
                'body' => $font['body'],
                'title' => $font['title'],
                'ui' => $font['ui']
            ]
        ]
    ];
}

function laoke_add_integrated_theme_options($form)
{
    $toggleOptions = ['1' => _t('开启'), '0' => _t('关闭')];

    $barrageEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'barrageEnabled',
        $toggleOptions,
        '1',
        _t('评论弹幕'),
        _t('将评论弹幕功能内聚到主题内部。')
    );
    $form->addInput($barrageEnabled);

    $barrageHomeEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'barrageHomeEnabled',
        $toggleOptions,
        '1',
        _t('首页弹幕'),
        _t('首页展示全站随机评论弹幕，并自动排除时光机评论。')
    );
    $form->addInput($barrageHomeEnabled);

    $barragePostEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'barragePostEnabled',
        $toggleOptions,
        '1',
        _t('文章页弹幕'),
        _t('文章页只展示当前文章的已审核评论弹幕。')
    );
    $form->addInput($barragePostEnabled);

    $barrageMobileEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'barrageMobileEnabled',
        ['0' => _t('默认关闭'), '1' => _t('开启')],
        '0',
        _t('移动端弹幕'),
        _t('默认仅桌面端显示弹幕，如有需要可单独开启移动端。')
    );
    $form->addInput($barrageMobileEnabled);

    $barrageOpacity = new \Typecho\Widget\Helper\Form\Element\Text(
        'barrageOpacity',
        null,
        '82',
        _t('弹幕透明度'),
        _t('支持填写 0.35-0.96 或 35-96，只影响弹幕胶囊底色，不影响文字和头像。')
    );
    $form->addInput($barrageOpacity);

    $fontEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'fontEnabled',
        $toggleOptions,
        '1',
        _t('前台字体系统'),
        _t('通过主题预设或自定义样式链接切换前台字体。')
    );
    $form->addInput($fontEnabled);

    $fontPresetOptions = [];
    foreach (laoke_frontend_font_presets() as $key => $preset) {
        $fontPresetOptions[$key] = $preset['label'];
    }
    $fontPresetOptions['custom'] = _t('自定义字体');

    $fontPreset = new \Typecho\Widget\Helper\Form\Element\Select(
        'fontPreset',
        $fontPresetOptions,
        'default',
        _t('字体预设'),
        _t('选择主题内置字体组合，或切换到自定义字体。')
    );
    $form->addInput($fontPreset);

    $fontCustomUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'fontCustomUrl',
        null,
        null,
        _t('自定义字体样式链接'),
        _t('例如字体服务返回的 CSS 链接，仅在“自定义字体”模式下生效。')
    );
    $form->addInput($fontCustomUrl);

    $fontCustomFamily = new \Typecho\Widget\Helper\Form\Element\Text(
        'fontCustomFamily',
        null,
        null,
        _t('自定义字体名称'),
        _t('例如 LXGW WenKai、HarmonyOS Sans。')
    );
    $form->addInput($fontCustomFamily);
    return;

    $adminThemeEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminThemeEnabled',
        $toggleOptions,
        '1',
        _t('后台美化'),
        _t('启用 LaoKe 风格的后台界面、登录页和编辑器增强。')
    );
    $form->addInput($adminThemeEnabled);

    $adminLoginEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminLoginEnabled',
        $toggleOptions,
        '1',
        _t('登录页美化'),
        _t('启用 LaoKe 风格的后台登录页。')
    );
    $form->addInput($adminLoginEnabled);

    $adminAppearance = new \Typecho\Widget\Helper\Form\Element\Select(
        'adminAppearance',
        [
            'auto' => _t('跟随系统'),
            'light' => _t('浅色'),
            'dark' => _t('深色')
        ],
        'auto',
        _t('后台配色'),
        _t('控制后台默认明暗主题。')
    );
    $form->addInput($adminAppearance);

    $adminNavPosition = new \Typecho\Widget\Helper\Form\Element\Select(
        'adminNavPosition',
        [
            'top' => _t('顶部导航'),
            'left' => _t('左侧导航')
        ],
        'top',
        _t('后台导航布局'),
        _t('推荐先使用顶部导航；左侧导航适合更宽的后台操作区。')
    );
    $form->addInput($adminNavPosition);

    $adminAnimationEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminAnimationEnabled',
        $toggleOptions,
        '1',
        _t('后台动效'),
        _t('控制后台轻量过渡和悬浮动效。')
    );
    $form->addInput($adminAnimationEnabled);

    $adminPageCardsEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminPageCardsEnabled',
        $toggleOptions,
        '1',
        _t('后台页面增强'),
        _t('增强仪表盘、插件页、主题页以及列表和表单的显示风格。')
    );
    $form->addInput($adminPageCardsEnabled);

    $adminEditorMode = new \Typecho\Widget\Helper\Form\Element\Select(
        'adminEditorMode',
        [
            'native' => _t('原生编辑器'),
            'vditor' => _t('Vditor'),
            'dual' => _t('双模式')
        ],
        'dual',
        _t('后台编辑器模式'),
        _t('支持原生编辑器、Vditor 或页面内双模式切换。')
    );
    $form->addInput($adminEditorMode);

    $adminPwaEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminPwaEnabled',
        ['0' => _t('关闭'), '1' => _t('开启')],
        '0',
        _t('后台 PWA'),
        _t('将后台安装为轻量 PWA，仅缓存后台壳层和主题自有静态资源。')
    );
    $form->addInput($adminPwaEnabled);

    $adminPwaName = new \Typecho\Widget\Helper\Form\Element\Text(
        'adminPwaName',
        null,
        null,
        _t('PWA 名称'),
        _t('留空时自动使用“站点名称 + 后台”。')
    );
    $form->addInput($adminPwaName);

    $adminPwaIcon = new \Typecho\Widget\Helper\Form\Element\Text(
        'adminPwaIcon',
        null,
        null,
        _t('PWA 图标 URL'),
        _t('建议填写 192x192 或 512x512 的图标地址。')
    );
    $form->addInput($adminPwaIcon);
}

function laoke_add_integrated_theme_options_legacy($form)
{
    $barrageEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'barrageEnabled',
        ['1' => _t('开启'), '0' => _t('关闭')],
        '1',
        _t('评论弹幕'),
        _t('将评论弹幕功能内聚到主题内部。')
    );
    $form->addInput($barrageEnabled);

    $barrageHomeEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'barrageHomeEnabled',
        ['1' => _t('开启'), '0' => _t('关闭')],
        '1',
        _t('首页弹幕'),
        _t('首页展示全站随机评论弹幕，自动排除时光机页面评论。')
    );
    $form->addInput($barrageHomeEnabled);

    $barragePostEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'barragePostEnabled',
        ['1' => _t('开启'), '0' => _t('关闭')],
        '1',
        _t('文章页弹幕'),
        _t('文章内展示当前文章的已审核评论弹幕。')
    );
    $form->addInput($barragePostEnabled);

    $barrageMobileEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'barrageMobileEnabled',
        ['0' => _t('默认关闭'), '1' => _t('开启')],
        '0',
        _t('移动端弹幕'),
        _t('默认仅桌面端显示弹幕，移动端可按需开启。')
    );
    $form->addInput($barrageMobileEnabled);
    $barrageOpacity = new \Typecho\Widget\Helper\Form\Element\Text(
        'barrageOpacity',
        null,
        '82',
        _t('弹幕透明度'),
        _t('支持填写 0.35-0.96 或 35-96，只影响弹幕胶囊底色，不影响文字和头像。')
    );
    $form->addInput($barrageOpacity);

    $fontEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'fontEnabled',
        ['1' => _t('开启'), '0' => _t('关闭')],
        '1',
        _t('前台字体系统'),
        _t('通过主题预设或自定义链接切换前台字体。')
    );
    $form->addInput($fontEnabled);

    $fontPresetOptions = [];
    foreach (laoke_frontend_font_presets() as $key => $preset) {
        $fontPresetOptions[$key] = $preset['label'];
    }
    $fontPresetOptions['custom'] = _t('自定义字体');

    $fontPreset = new \Typecho\Widget\Helper\Form\Element\Select(
        'fontPreset',
        $fontPresetOptions,
        'default',
        _t('字体预设'),
        _t('选择主题内置字体组合，或切换到自定义字体。')
    );
    $form->addInput($fontPreset);

    $fontCustomUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'fontCustomUrl',
        null,
        null,
        _t('自定义字体样式链接'),
        _t('例如字体服务返回的 CSS 链接，仅在“自定义字体”模式下生效。')
    );
    $form->addInput($fontCustomUrl);

    $fontCustomFamily = new \Typecho\Widget\Helper\Form\Element\Text(
        'fontCustomFamily',
        null,
        null,
        _t('自定义字体名称'),
        _t('例如 LXGW WenKai、HarmonyOS Sans。')
    );
    $form->addInput($fontCustomFamily);
    return;

    $adminThemeEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminThemeEnabled',
        ['1' => _t('开启'), '0' => _t('关闭')],
        '1',
        _t('后台美化'),
        _t('启用 LaoKe 风格的后台界面、登录页和编辑器增强。')
    );
    $form->addInput($adminThemeEnabled);

    $adminLoginEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminLoginEnabled',
        ['1' => _t('开启'), '0' => _t('关闭')],
        '1',
        _t('登录页美化'),
        _t('启用 LaoKe 风格的后台登录页。')
    );
    $form->addInput($adminLoginEnabled);

    $adminAppearance = new \Typecho\Widget\Helper\Form\Element\Select(
        'adminAppearance',
        [
            'auto' => _t('跟随系统'),
            'light' => _t('浅色'),
            'dark' => _t('深色')
        ],
        'auto',
        _t('后台配色'),
        _t('控制后台默认明暗主题。')
    );
    $form->addInput($adminAppearance);

    $adminNavPosition = new \Typecho\Widget\Helper\Form\Element\Select(
        'adminNavPosition',
        [
            'left' => _t('左侧导航'),
            'top' => _t('顶部导航')
        ],
        'left',
        _t('后台导航布局'),
        _t('左侧导航更适合后台增强布局，顶部导航更接近原版。')
    );
    $form->addInput($adminNavPosition);

    $adminAnimationEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminAnimationEnabled',
        ['1' => _t('开启'), '0' => _t('关闭')],
        '1',
        _t('后台动效'),
        _t('控制后台轻量过渡和悬浮动效。')
    );
    $form->addInput($adminAnimationEnabled);

    $adminPageCardsEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminPageCardsEnabled',
        ['1' => _t('开启'), '0' => _t('关闭')],
        '1',
        _t('后台页面增强'),
        _t('增强仪表盘、插件页、主题页以及列表/表单的展示风格。')
    );
    $form->addInput($adminPageCardsEnabled);

    $adminEditorMode = new \Typecho\Widget\Helper\Form\Element\Select(
        'adminEditorMode',
        [
            'native' => _t('原生编辑器'),
            'vditor' => _t('Vditor'),
            'dual' => _t('双模式')
        ],
        'dual',
        _t('后台编辑器模式'),
        _t('支持原生编辑器、Vditor 或页面内双模式切换。')
    );
    $form->addInput($adminEditorMode);

    $adminPwaEnabled = new \Typecho\Widget\Helper\Form\Element\Radio(
        'adminPwaEnabled',
        ['0' => _t('关闭'), '1' => _t('开启')],
        '0',
        _t('后台 PWA'),
        _t('将后台安装为轻量 PWA，仅缓存后台壳层和主题自有静态资源。')
    );
    $form->addInput($adminPwaEnabled);

    $adminPwaName = new \Typecho\Widget\Helper\Form\Element\Text(
        'adminPwaName',
        null,
        null,
        _t('PWA 名称'),
        _t('留空时自动使用“站点名称 + 后台”。')
    );
    $form->addInput($adminPwaName);

    $adminPwaIcon = new \Typecho\Widget\Helper\Form\Element\Text(
        'adminPwaIcon',
        null,
        null,
        _t('PWA 图标 URL'),
        _t('建议填写 192x192 或 512x512 的图标地址。')
    );
    $form->addInput($adminPwaIcon);
}
