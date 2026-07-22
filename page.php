<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php
$contentHtml = trim((string) laoke_content_html($this));
$hasVisibleContent = laoke_has_visible_content_html($contentHtml);
?>

<article class="entry entry-page entry-page--standard">
    <header class="page-intro page-intro--feature page-intro--standard">
        <div class="page-intro__body">
            <h1><?php $this->title(); ?></h1>
            <p><time datetime="<?php $this->date('c'); ?>"><?php echo laoke_relative_time($this->created); ?></time>更新</p>
        </div>
    </header>

    <?php if ($hasVisibleContent): ?>
        <div class="toc-anchor">
            <aside class="post-toc" id="post-toc" aria-label="页面目录"></aside>
        </div>

        <div class="post-content">
            <?php echo $contentHtml; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h2>暂无正文</h2>
            <p>这个页面还没有发布内容。</p>
        </div>
    <?php endif; ?>
</article>

<?php if ($this->allow('comment')): ?>
    <?php $this->need('comments.php'); ?>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
