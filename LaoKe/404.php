<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<section class="empty-state">
    <p class="eyebrow">404</p>
    <h1>页面不存在。</h1>
    <p>你访问的内容可能已删除、已移动，或者链接本身就是错的。</p>
    <p><a href="<?php $this->options->siteUrl(); ?>">返回首页</a></p>
</section>

<?php $this->need('footer.php'); ?>
