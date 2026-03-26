<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php
$contentHtml = trim((string) laoke_content_html($this));
$hasVisibleContent = laoke_has_visible_content_html($contentHtml);
$hasTags = !empty($this->tags);
?>

<article class="entry entry-post" itemscope itemtype="https://schema.org/BlogPosting">
    <header class="entry-header">
        <h1 class="entry-title center" itemprop="headline"><?php $this->title(); ?></h1>
        <p class="entry-meta center meta-list">
            <time class="meta-item" datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php echo laoke_relative_time($this->created); ?></time>
            <span class="meta-item"><?php $this->category(', '); ?></span>
            <span class="meta-item">阅读 <?php echo laoke_get_views($this); ?></span>
            <span class="meta-item">字数 <?php echo laoke_word_count($this); ?></span>
        </p>
        <?php if ($hasTags): ?>
            <p class="entry-tags center"><?php laoke_render_tags($this, 6); ?></p>
        <?php endif; ?>
    </header>

    <?php if ($hasVisibleContent): ?>
        <div class="toc-anchor">
            <aside class="post-toc" id="post-toc" aria-label="文章目录"></aside>
        </div>

        <div class="post-content" itemprop="articleBody">
            <?php echo $contentHtml; ?>
        </div>
    <?php endif; ?>

    <?php
    $prevPost = laoke_adjacent_post($this, 'prev');
    $nextPost = laoke_adjacent_post($this, 'next');
    ?>
    <?php if ($hasVisibleContent): ?>
        <ul class="pager post-pager">
            <li class="previous<?php if (!$prevPost): ?> is-empty<?php endif; ?>">
                <?php if ($prevPost): ?>
                    <a class="post-pager__link" href="<?php echo htmlspecialchars((string) $prevPost['permalink'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars((string) $prevPost['title'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="post-pager__eyebrow">上一篇</span>
                        <span class="post-pager__title"><?php echo htmlspecialchars((string) $prevPost['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php else: ?>
                    <span class="post-pager__link post-pager__link--empty">
                        <span class="post-pager__eyebrow">上一篇</span>
                        <span class="post-pager__title">无</span>
                    </span>
                <?php endif; ?>
            </li>
            <li class="next<?php if (!$nextPost): ?> is-empty<?php endif; ?>">
                <?php if ($nextPost): ?>
                    <a class="post-pager__link" href="<?php echo htmlspecialchars((string) $nextPost['permalink'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars((string) $nextPost['title'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="post-pager__eyebrow">下一篇</span>
                        <span class="post-pager__title"><?php echo htmlspecialchars((string) $nextPost['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php else: ?>
                    <span class="post-pager__link post-pager__link--empty">
                        <span class="post-pager__eyebrow">下一篇</span>
                        <span class="post-pager__title">无</span>
                    </span>
                <?php endif; ?>
            </li>
        </ul>
    <?php endif; ?>
</article>

<?php $this->need('comments.php'); ?>
<?php $this->need('footer.php'); ?>
