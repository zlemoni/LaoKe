<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<section class="page-intro">
    <h1><?php $this->archiveTitle([
        'category' => _t('%s'),
        'search' => _t('搜索：%s'),
        'tag' => _t('标签：%s'),
        'author' => _t('作者：%s')
    ], '', ''); ?></h1>
    <?php if (!$this->is('category')): ?>
        <p>当前列表保持单栏、文字优先和简洁分页。</p>
    <?php endif; ?>
</section>

<?php if ($this->have()): ?>
    <?php while ($this->next()): ?>
        <article class="stream-item">
            <h2 class="stream-title center"><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h2>
            <p class="stream-meta center meta-list">
                <time class="meta-item" datetime="<?php $this->date('c'); ?>"><?php echo laoke_relative_time($this->created); ?></time>
                <span class="meta-item">阅读 <?php echo laoke_get_views($this); ?></span>
            </p>
            <p class="stream-excerpt"><?php echo htmlspecialchars(laoke_excerpt($this, 150), ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="stream-tags"><?php laoke_render_categories($this, 3); ?></div>
        </article>
        <hr class="stream-divider">
    <?php endwhile; ?>

    <ul class="pager">
        <li class="previous"><?php $this->pageLink('上一页', 'prev'); ?></li>
        <li class="next"><?php $this->pageLink('下一页', 'next'); ?></li>
    </ul>
<?php else: ?>
    <section class="empty-state">
        <h2>没有匹配内容。</h2>
        <p>换个关键词，或者从导航回到首页。</p>
    </section>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
