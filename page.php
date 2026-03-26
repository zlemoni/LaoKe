<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<article class="entry entry-page">
    <header class="entry-header">
        <h1 class="entry-title center"><?php $this->title(); ?></h1>
        <p class="entry-meta center meta-list">
            <time class="meta-item" datetime="<?php $this->date('c'); ?>"><?php echo laoke_relative_time($this->created); ?></time>
        </p>
    </header>

    <div class="toc-anchor">
        <aside class="post-toc" id="post-toc" aria-label="页面目录"></aside>
    </div>

    <div class="post-content">
        <?php echo laoke_content_html($this); ?>
    </div>
</article>

<?php if ($this->allow('comment')): ?>
    <?php $this->need('comments.php'); ?>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
