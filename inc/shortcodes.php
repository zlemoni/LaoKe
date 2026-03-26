<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

function laoke_shortcode_attrs($raw)
{
    $attributes = [];
    if (!preg_match_all('/([a-zA-Z][\w-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/u', (string) $raw, $matches, PREG_SET_ORDER)) {
        return $attributes;
    }

    foreach ($matches as $match) {
        $value = isset($match[2]) && $match[2] !== '' ? $match[2] : ($match[3] ?? '');
        $attributes[strtolower((string) $match[1])] = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    }

    return $attributes;
}

function laoke_restore_shortcode_fragments($html, array $fragments)
{
    foreach ($fragments as $token => $fragment) {
        if (!empty($fragment['block'])) {
            $html = preg_replace('/<p>\s*' . preg_quote($token, '/') . '\s*<\/p>/u', (string) $fragment['html'], (string) $html);
        }

        $html = str_replace($token, (string) $fragment['html'], (string) $html);
    }

    return (string) $html;
}

function laoke_restore_raw_segments($text, array $segments)
{
    if (empty($segments)) {
        return (string) $text;
    }

    return strtr((string) $text, $segments);
}

function laoke_protect_raw_segments($text, array &$segments)
{
    $text = preg_replace_callback('/(^|[\r\n])(```|~~~)[^\n]*\n.*?\n\2[ \t]*(?=(?:\r?\n|$))/su', function ($matches) use (&$segments) {
        $token = '%%LAOKE_RAW_' . count($segments) . '%%';
        $segments[$token] = substr((string) $matches[0], strlen((string) $matches[1]));
        return (string) $matches[1] . $token;
    }, (string) $text);

    $text = preg_replace_callback('/<pre\b[^>]*>.*?<\/pre>|<code\b[^>]*>.*?<\/code>/isu', function ($matches) use (&$segments) {
        $token = '%%LAOKE_RAW_' . count($segments) . '%%';
        $segments[$token] = (string) $matches[0];
        return $token;
    }, (string) $text);

    return (string) $text;
}

function laoke_render_basic_text_html($text, $isMarkdown = true)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    if ($isMarkdown) {
        $html = \Utils\Markdown::convert($text);
    } else {
        static $autoParagraph = null;
        if ($autoParagraph === null) {
            $autoParagraph = new \Utils\AutoP();
        }
        $html = $autoParagraph->parse($text);
    }

    return laoke_process_content_html((string) $html);
}

function laoke_unwrap_single_paragraph($html)
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }

    if (preg_match('/^\s*<p>(.*)<\/p>\s*$/isu', $html, $matches)) {
        $inner = (string) $matches[1];
        if (stripos($inner, '<p') === false && stripos($inner, '</p>') === false) {
            return $inner;
        }
    }

    return $html;
}

function laoke_sanitize_shortcode_url($url)
{
    $url = trim((string) $url);
    if ($url === '' || preg_match('/^\s*javascript:/iu', $url)) {
        return '';
    }

    return $url;
}

function laoke_sanitize_shortcode_color($color)
{
    $color = trim((string) $color);
    if ($color === '') {
        return '';
    }

    if (!preg_match('/^[#(),.%\sa-zA-Z0-9-]+$/u', $color)) {
        return '';
    }

    return $color;
}

function laoke_shortcode_fragment_token(array &$fragments, $html, $block = false)
{
    $token = '%%LAOKE_SC_' . count($fragments) . '%%';
    $fragments[$token] = [
        'html' => (string) $html,
        'block' => (bool) $block
    ];

    return $block ? "\n\n" . $token . "\n\n" : $token;
}

function laoke_render_shortcode_inline_fragment($content, $text, $isMarkdown = true)
{
    return laoke_unwrap_single_paragraph(laoke_render_shortcode_content($content, $text, $isMarkdown, ['allowAlbums' => true]));
}

function laoke_shortcode_tip_meta($type)
{
    $type = strtolower(trim((string) $type));
    $map = [
        'share' => ['class' => 'is-share', 'label' => '资料'],
        'yellow' => ['class' => 'is-warning', 'label' => '提示'],
        'red' => ['class' => 'is-danger', 'label' => '注意'],
        'blue' => ['class' => 'is-info', 'label' => '信息'],
        'green' => ['class' => 'is-success', 'label' => '推荐']
    ];

    return $map[$type] ?? $map['blue'];
}

function laoke_render_tip_shortcode($type, $bodyHtml)
{
    $meta = laoke_shortcode_tip_meta($type);

    return '<aside class="shortcode-callout ' . htmlspecialchars((string) $meta['class'], ENT_QUOTES, 'UTF-8') . '">' .
        '<div class="shortcode-callout__body">' . (string) $bodyHtml . '</div>' .
    '</aside>';
}

function laoke_render_collapse_shortcode($label, $bodyHtml, $isOpen)
{
    $title = trim((string) $label) !== '' ? trim((string) $label) : '折叠内容';

    return '<section class="shortcode-collapse' . ($isOpen ? ' is-open' : '') . '" data-shortcode-collapse>' .
        '<button class="shortcode-collapse__toggle" type="button" data-shortcode-collapse-toggle aria-expanded="' . ($isOpen ? 'true' : 'false') . '">' .
            '<span class="shortcode-collapse__label">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>' .
            '<span class="shortcode-collapse__icon" aria-hidden="true"></span>' .
        '</button>' .
        '<div class="shortcode-collapse__body"' . ($isOpen ? '' : ' hidden') . ' data-shortcode-collapse-body>' . (string) $bodyHtml . '</div>' .
    '</section>';
}

function laoke_render_tabs_shortcode($tabs)
{
    if (empty($tabs)) {
        return '';
    }

    $nav = '';
    $panels = '';

    foreach ($tabs as $index => $tab) {
        $isActive = $index === 0;
        $buttonClass = 'shortcode-tabs__button' . ($isActive ? ' is-active' : '');
        $panelClass = 'shortcode-tabs__panel' . ($isActive ? ' is-active' : '');

        $nav .= '<button class="' . $buttonClass . '" type="button" data-shortcode-tab="' . $index . '" aria-pressed="' . ($isActive ? 'true' : 'false') . '">' .
            htmlspecialchars((string) $tab['label'], ENT_QUOTES, 'UTF-8') .
        '</button>';

        $panels .= '<div class="' . $panelClass . '" data-shortcode-tab-panel="' . $index . '"' . ($isActive ? '' : ' hidden') . '>' .
            (string) $tab['html'] .
        '</div>';
    }

    return '<section class="shortcode-tabs" data-shortcode-tabs>' .
        '<div class="shortcode-tabs__nav" data-shortcode-tabs-nav>' . $nav . '</div>' .
        '<div class="shortcode-tabs__panels">' . $panels . '</div>' .
    '</section>';
}

function laoke_render_timeline_shortcode($items)
{
    if (empty($items)) {
        return '';
    }

    $entries = [];

    foreach ($items as $item) {
        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $time = trim((string) ($item['time'] ?? ''));
        $bodyHtml = trim((string) ($item['html'] ?? ''));

        $entry = '<article class="shortcode-timeline__item">';
        $entry .= '<span class="shortcode-timeline__axis" aria-hidden="true"><span class="shortcode-timeline__dot"></span></span>';
        $entry .= '<div class="shortcode-timeline__card">';
        $entry .= '<div class="shortcode-timeline__head">';
        $entry .= '<h3 class="shortcode-timeline__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>';
        if ($time !== '') {
            $entry .= '<p class="shortcode-timeline__time">' . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $entry .= '</div>';

        if ($bodyHtml !== '' && laoke_has_visible_content_html($bodyHtml)) {
            $entry .= '<div class="shortcode-timeline__body">' . $bodyHtml . '</div>';
        }

        $entry .= '</div></article>';
        $entries[] = $entry;
    }

    if (empty($entries)) {
        return '';
    }

    return '<section class="shortcode-timeline">' . implode('', $entries) . '</section>';
}

function laoke_render_photos_shortcode($rawBody)
{
    $images = laoke_extract_album_images((string) $rawBody);
    if (empty($images)) {
        return '';
    }

    $html = '<section class="shortcode-photos">';
    foreach ($images as $index => $image) {
        $caption = trim((string) ($image['title'] !== '' ? $image['title'] : $image['alt']));
        $html .= '<figure class="shortcode-photos__item">';
        $html .= '<img ' . laoke_image_source_attrs((string) $image['src'], $index < 3) . ' alt="' . htmlspecialchars((string) $image['alt'], ENT_QUOTES, 'UTF-8') . '"';
        if (trim((string) $image['title']) !== '') {
            $html .= ' title="' . htmlspecialchars((string) $image['title'], ENT_QUOTES, 'UTF-8') . '"';
        }
        $html .= '>';
        if ($caption !== '') {
            $html .= '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>';
        }
        $html .= '</figure>';
    }
    $html .= '</section>';

    return $html;
}

function laoke_render_shortcode_button($labelHtml, $url, $color)
{
    $style = '';
    $color = laoke_sanitize_shortcode_color($color);
    if ($color !== '') {
        $style = ' style="--shortcode-button-accent:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '"';
    }

    return '<a class="shortcode-button" href="' . htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer"' . $style . '>' . (string) $labelHtml . '</a>';
}

function laoke_render_shortcode_mask_text($bodyHtml)
{
    return '<span class="shortcode-mask-text">' . (string) $bodyHtml . '</span>';
}

function laoke_render_shortcode_color_text($bodyHtml, $color)
{
    $style = '';
    $color = laoke_sanitize_shortcode_color($color);
    if ($color !== '') {
        $style = ' style="color:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '"';
    }

    return '<span class="shortcode-colored-text"' . $style . '>' . (string) $bodyHtml . '</span>';
}

function laoke_render_shortcode_access_notice($mode)
{
    $mode = $mode === 'hide' ? 'hide' : 'login';
    if ($mode === 'hide') {
        $title = '评论可见';
        $desc = '评论并通过审核后，这段内容会自动显示。';
        $action = '<a href="#comments">去评论</a>';
    } else {
        $title = '登录可见';
        $desc = '登录后即可查看这段内容。';
        $action = '';
    }

    return '<div class="shortcode-access shortcode-access--' . $mode . '">' .
        '<p class="shortcode-access__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</p>' .
        '<p class="shortcode-access__desc">' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '</p>' .
        ($action !== '' ? '<p class="shortcode-access__action">' . $action . '</p>' : '') .
    '</div>';
}

function laoke_shortcode_viewer_has_approved_comment($content)
{
    static $cache = [];

    if (!is_object($content)) {
        return false;
    }

    $cid = (int) ($content->cid ?? 0);
    if ($cid <= 0) {
        return false;
    }

    $mail = '';
    if (method_exists($content, 'remember')) {
        $mail = trim((string) $content->remember('mail', true));
    }

    if ($mail === '') {
        return false;
    }

    $cacheKey = $cid . '|' . strtolower($mail);
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $db = Typecho_Db::get();
    $row = $db->fetchRow(
        $db->select('coid')
            ->from('table.comments')
            ->where('cid = ?', $cid)
            ->where('status = ?', 'approved')
            ->where('mail = ?', $mail)
            ->limit(1)
    );

    $cache[$cacheKey] = !empty($row);
    return $cache[$cacheKey];
}

function laoke_render_shortcode_video($src)
{
    return '<div class="shortcode-media shortcode-media--video"><video controls preload="metadata"><source src="' . htmlspecialchars((string) $src, ENT_QUOTES, 'UTF-8') . '"></video></div>';
}

function laoke_render_shortcode_bilibili($bv, $page = 1)
{
    $page = max(1, (int) $page);
    $src = 'https://player.bilibili.com/player.html?bvid=' . rawurlencode((string) $bv) . '&page=' . $page;

    return '<div class="shortcode-media shortcode-media--bilibili"><iframe src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>';
}

function laoke_shortcode_summary_text($text, $limit = 60)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    if (mb_strlen($text, 'UTF-8') > $limit) {
        return mb_substr($text, 0, $limit, 'UTF-8') . '...';
    }

    return $text;
}

function laoke_link_card_summary_from_text($rawText)
{
    $text = preg_replace('/\[(?:\/)?(?:album|photos|tip|bkc|button|colour|tabs|tab-pane|collapse|login|hide|mp3|timeline|timeline-item)\b[^\]]*\]/iu', ' ', (string) $rawText);
    $text = preg_replace('/\[(?:bilibili|video)\b[^\]]*\]|\[cid="[^"]*"\]/iu', ' ', (string) $text);
    $text = preg_replace('/!\[([^\]]*)\]\(([^)\r\n]+)\)/u', '$1', (string) $text);
    $text = laoke_strip_text(\Utils\Markdown::convert((string) $text));
    return laoke_shortcode_summary_text($text, 60);
}

function laoke_link_card_data($cid)
{
    static $cache = [];

    $cid = (int) $cid;
    if ($cid <= 0) {
        return null;
    }

    if (array_key_exists($cid, $cache)) {
        return $cache[$cid];
    }

    $db = Typecho_Db::get();
    $article = $db->fetchRow(
        $db->select()
            ->from('table.contents')
            ->where('cid = ?', $cid)
            ->where('status = ?', 'publish')
            ->where('type = ?', 'post')
            ->limit(1)
    );

    if (empty($article)) {
        $cache[$cid] = null;
        return null;
    }

    $widget = Typecho_Widget::widget('Widget_Abstract_Contents');
    $content = $widget->push($article);
    $banner = $db->fetchRow(
        $db->select('str_value')
            ->from('table.fields')
            ->where('cid = ?', $cid)
            ->where('name = ?', 'banner')
            ->limit(1)
    );

    $cover = '';
    if (!empty($banner['str_value'])) {
        $cover = trim((string) $banner['str_value']);
    }

    $cache[$cid] = [
        'title' => trim((string) ($content['title'] ?? '')),
        'permalink' => trim((string) ($content['permalink'] ?? '')),
        'summary' => laoke_link_card_summary_from_text((string) ($content['text'] ?? '')),
        'cover' => $cover
    ];

    return $cache[$cid];
}

function laoke_render_link_card_shortcode($cid)
{
    $card = laoke_link_card_data($cid);
    if (!$card || trim((string) $card['title']) === '' || trim((string) $card['permalink']) === '') {
        return '';
    }

    $cover = trim((string) $card['cover']);
    $html = '<article class="shortcode-link-card"><a class="shortcode-link-card__link" href="' . htmlspecialchars((string) $card['permalink'], ENT_QUOTES, 'UTF-8') . '">';
    $html .= '<span class="shortcode-link-card__body">';
    $html .= '<strong class="shortcode-link-card__title">' . htmlspecialchars((string) $card['title'], ENT_QUOTES, 'UTF-8') . '</strong>';
    if (trim((string) $card['summary']) !== '') {
        $html .= '<span class="shortcode-link-card__desc">' . htmlspecialchars((string) $card['summary'], ENT_QUOTES, 'UTF-8') . '</span>';
    }
    $html .= '</span>';

    if ($cover !== '') {
        $html .= '<span class="shortcode-link-card__cover"><img ' . laoke_image_source_attrs($cover, false) . ' alt="' . htmlspecialchars((string) $card['title'], ENT_QUOTES, 'UTF-8') . '" data-viewimages-ignore="true"></span>';
    }

    $html .= '</a></article>';
    return $html;
}

function laoke_render_shortcode_mp3($songId)
{
    return '<div class="shortcode-mp3" data-shortcode-mp3 data-song-id="' . htmlspecialchars((string) $songId, ENT_QUOTES, 'UTF-8') . '">' .
        '<div class="shortcode-mp3__status" data-shortcode-mp3-status>正在加载音频...</div>' .
    '</div>';
}

function laoke_render_shortcode_content($content, $text, $isMarkdown = true, array $options = [])
{
    $text = (string) $text;
    if (trim($text) === '') {
        return '';
    }

    $allowAlbums = !array_key_exists('allowAlbums', $options) || (bool) $options['allowAlbums'];
    $fragments = [];
    $protected = [];
    $seenAlbumKeys = [];
    $working = laoke_protect_raw_segments($text, $protected);

    if ($allowAlbums) {
        $working = preg_replace_callback('/\[album\b([^\]]*)\](.*?)\[\/album\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments, &$seenAlbumKeys) {
            $attributes = laoke_parse_album_shortcode_attributes((string) $matches[1]);
            $album = laoke_build_album_data($content, $attributes, (string) $matches[2]);
            if (!$album || isset($seenAlbumKeys[$album['key']])) {
                return (string) $matches[0];
            }

            $seenAlbumKeys[$album['key']] = true;
            return laoke_shortcode_fragment_token($fragments, laoke_render_inline_album_shortcode($content, $album), true);
        }, $working);
    }

    $working = preg_replace_callback('/\[timeline\](.*?)\[\/timeline\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $items = [];
        if (!preg_match_all('/\[timeline-item\b([^\]]*)\](.*?)\[\/timeline-item\]/isu', (string) $matches[1], $itemMatches, PREG_SET_ORDER)) {
            return '';
        }

        foreach ($itemMatches as $itemMatch) {
            $attributes = laoke_shortcode_attrs((string) $itemMatch[1]);
            $title = trim((string) ($attributes['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'time' => trim((string) ($attributes['time'] ?? '')),
                'html' => laoke_render_shortcode_content($content, (string) $itemMatch[2], $isMarkdown, ['allowAlbums' => true])
            ];
        }

        $html = laoke_render_timeline_shortcode($items);
        if ($html === '') {
            return '';
        }

        return laoke_shortcode_fragment_token($fragments, $html, true);
    }, $working);

    $working = preg_replace_callback('/\[tabs\](.*?)\[\/tabs\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $tabs = [];
        if (!preg_match_all('/\[tab-pane\b([^\]]*)\](.*?)\[\/tab-pane\]/isu', (string) $matches[1], $tabMatches, PREG_SET_ORDER)) {
            return (string) $matches[0];
        }

        foreach ($tabMatches as $index => $tabMatch) {
            $attributes = laoke_shortcode_attrs((string) $tabMatch[1]);
            $label = trim((string) ($attributes['label'] ?? ''));
            $tabs[] = [
                'label' => $label !== '' ? $label : '标签 ' . ($index + 1),
                'html' => laoke_render_shortcode_content($content, (string) $tabMatch[2], $isMarkdown, ['allowAlbums' => true])
            ];
        }

        return laoke_shortcode_fragment_token($fragments, laoke_render_tabs_shortcode($tabs), true);
    }, $working);

    $working = preg_replace_callback('/\[collapse\b([^\]]*)\](.*?)\[\/collapse\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $attributes = laoke_shortcode_attrs((string) $matches[1]);
        $status = strtolower(trim((string) ($attributes['status'] ?? '')));
        $isOpen = $status === 'collapse-block' || $status === 'open' || $status === 'expanded';
        $html = laoke_render_collapse_shortcode(
            (string) ($attributes['label'] ?? ''),
            laoke_render_shortcode_content($content, (string) $matches[2], $isMarkdown, ['allowAlbums' => true]),
            $isOpen
        );

        return laoke_shortcode_fragment_token($fragments, $html, true);
    }, $working);

    $working = preg_replace_callback('/\[tip\b([^\]]*)\](.*?)\[\/tip\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $attributes = laoke_shortcode_attrs((string) $matches[1]);
        $html = laoke_render_tip_shortcode(
            (string) ($attributes['type'] ?? ''),
            laoke_render_shortcode_content($content, (string) $matches[2], $isMarkdown, ['allowAlbums' => true])
        );

        return laoke_shortcode_fragment_token($fragments, $html, true);
    }, $working);

    $working = preg_replace_callback('/\[photos(?:\b[^\]]*)?\](.*?)\[\/photos\]/isu', function ($matches) use (&$fragments) {
        $html = laoke_render_photos_shortcode((string) $matches[1]);
        if ($html === '') {
            return (string) $matches[0];
        }

        return laoke_shortcode_fragment_token($fragments, $html, true);
    }, $working);

    $working = preg_replace_callback('/\[login\](.*?)\[\/login\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $canView = laoke_current_user_can_edit_content($content) || \Widget\User::alloc()->hasLogin();
        $html = $canView
            ? laoke_render_shortcode_content($content, (string) $matches[1], $isMarkdown, ['allowAlbums' => true])
            : laoke_render_shortcode_access_notice('login');

        return laoke_shortcode_fragment_token($fragments, $html, true);
    }, $working);

    $working = preg_replace_callback('/\[hide\](.*?)\[\/hide\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $canView = laoke_current_user_can_edit_content($content) || laoke_shortcode_viewer_has_approved_comment($content);
        $html = $canView
            ? laoke_render_shortcode_content($content, (string) $matches[1], $isMarkdown, ['allowAlbums' => true])
            : laoke_render_shortcode_access_notice('hide');

        return laoke_shortcode_fragment_token($fragments, $html, true);
    }, $working);

    $working = preg_replace_callback('/\[bilibili\b([^\]]*)\]/iu', function ($matches) use (&$fragments) {
        $attributes = laoke_shortcode_attrs((string) $matches[1]);
        $bv = trim((string) ($attributes['bv'] ?? ''));
        if ($bv === '') {
            return (string) $matches[0];
        }

        return laoke_shortcode_fragment_token($fragments, laoke_render_shortcode_bilibili($bv, (int) ($attributes['p'] ?? 1)), true);
    }, $working);

    $working = preg_replace_callback('/\[video\b([^\]]*)\]/iu', function ($matches) use (&$fragments) {
        $attributes = laoke_shortcode_attrs((string) $matches[1]);
        $src = laoke_sanitize_shortcode_url((string) ($attributes['src'] ?? ''));
        if ($src === '') {
            return (string) $matches[0];
        }

        return laoke_shortcode_fragment_token($fragments, laoke_render_shortcode_video($src), true);
    }, $working);

    $working = preg_replace_callback('/\[cid="([^"]+)"\]/u', function ($matches) use (&$fragments) {
        $html = laoke_render_link_card_shortcode((int) $matches[1]);
        if ($html === '') {
            return (string) $matches[0];
        }

        return laoke_shortcode_fragment_token($fragments, $html, true);
    }, $working);

    $working = preg_replace_callback('/\[mp3\](.*?)\[\/mp3\]/isu', function ($matches) use (&$fragments) {
        $songId = trim((string) $matches[1]);
        if ($songId === '') {
            return (string) $matches[0];
        }

        return laoke_shortcode_fragment_token($fragments, laoke_render_shortcode_mp3($songId), true);
    }, $working);

    $working = preg_replace_callback('/\[button\b([^\]]*)\](.*?)\[\/button\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $attributes = laoke_shortcode_attrs((string) $matches[1]);
        $url = laoke_sanitize_shortcode_url((string) ($attributes['url'] ?? ''));
        if ($url === '') {
            return (string) $matches[0];
        }

        $labelHtml = laoke_render_shortcode_inline_fragment($content, (string) $matches[2], $isMarkdown);
        return laoke_shortcode_fragment_token($fragments, laoke_render_shortcode_button($labelHtml, $url, (string) ($attributes['color'] ?? '')), false);
    }, $working);

    $working = preg_replace_callback('/\[colour\b([^\]]*)\](.*?)\[\/colour\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $attributes = laoke_shortcode_attrs((string) $matches[1]);
        $html = laoke_render_shortcode_color_text(
            laoke_render_shortcode_inline_fragment($content, (string) $matches[2], $isMarkdown),
            (string) ($attributes['type'] ?? '')
        );
        return laoke_shortcode_fragment_token($fragments, $html, false);
    }, $working);

    $working = preg_replace_callback('/\[bkc\](.*?)\[\/bkc\]/isu', function ($matches) use ($content, $isMarkdown, &$fragments) {
        $html = laoke_render_shortcode_mask_text(laoke_render_shortcode_inline_fragment($content, (string) $matches[1], $isMarkdown));
        return laoke_shortcode_fragment_token($fragments, $html, false);
    }, $working);

    $working = laoke_restore_raw_segments($working, $protected);
    $html = laoke_render_basic_text_html($working, $isMarkdown);
    $html = laoke_restore_shortcode_fragments($html, $fragments);

    return laoke_render_owo_html($html);
}
