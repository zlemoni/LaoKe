<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<section class="page-intro page-intro-home">
    <p class="page-intro__eyebrow">Latest writing</p>
    <h1>文章与近况</h1>
    <p>一些持续发生的记录，按时间缓慢排列。</p>
</section>

<?php if ($this->have()): ?>
    <div class="stream-list">
    <?php while ($this->next()): ?>
        <article class="stream-item" itemscope itemtype="https://schema.org/BlogPosting">
            <time class="stream-date" datetime="<?php $this->date('c'); ?>" itemprop="datePublished">
                <span class="stream-date__day"><?php $this->date('d'); ?></span>
                <span class="stream-date__year-month"><?php $this->date('Y.m'); ?></span>
            </time>
            <div class="stream-item__body">
                <h2 class="stream-title" itemprop="headline">
                    <a href="<?php $this->permalink(); ?>" itemprop="url"><?php $this->title(); ?></a>
                </h2>
                <p class="stream-excerpt" itemprop="description"><?php echo htmlspecialchars(laoke_excerpt($this, 150), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="stream-meta meta-list">
                    <span class="meta-item"><?php echo laoke_relative_time($this->created); ?></span>
                    <span class="meta-item">评论 <?php $this->commentsNum('0', '1', '%d'); ?></span>
                    <span class="meta-item">阅读 <?php echo laoke_get_views($this); ?></span>
                    <?php if (!empty($this->categories)): ?>
                        <span class="meta-item"><?php $this->category(', '); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </article>
    <?php endwhile; ?>
    </div>

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
