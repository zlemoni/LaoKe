<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<section class="page-intro page-intro-home">
    <h1><?php $this->options->title(); ?></h1>
    <p><?php $this->options->description(); ?></p>
</section>

<?php if ($this->have()): ?>
    <?php while ($this->next()): ?>
        <article class="stream-item" itemscope itemtype="https://schema.org/BlogPosting">
            <h2 class="stream-title center" itemprop="headline">
                <a href="<?php $this->permalink(); ?>" itemprop="url"><?php $this->title(); ?></a>
            </h2>
            <p class="stream-meta stream-meta--capsules center meta-list">
                <time class="meta-item" datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php echo laoke_relative_time($this->created); ?></time>
                <span class="meta-item">评论 <?php $this->commentsNum('0', '1', '%d'); ?></span>
                <span class="meta-item">阅读 <?php echo laoke_get_views($this); ?></span>
                <?php if (!empty($this->categories)): ?>
                    <span class="meta-item"><?php $this->category(', '); ?></span>
                <?php endif; ?>
            </p>
            <p class="stream-excerpt" itemprop="description"><?php echo htmlspecialchars(laoke_excerpt($this, 150), ENT_QUOTES, 'UTF-8'); ?></p>
        </article>
        <hr class="stream-divider">
    <?php endwhile; ?>

    <ul class="pager">
        <li class="previous"><?php $this->pageLink('上一页', 'prev'); ?></li>
        <li class="next"><?php $this->pageLink('下一页', 'next'); ?></li>
    </ul>
<?php else: ?>
    <section class="empty-state">
        <h2>还没有文章。</h2>
        <p>发布第一篇文章后，这里就会出现内容流。</p>
    </section>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
