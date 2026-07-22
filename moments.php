<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * 时光机页
 *
 * @package custom
 */
?>
<?php $this->need('header.php'); ?>

<?php $introHtml = trim((string) laoke_content_html($this)); ?>
<section class="page-intro page-intro--feature page-intro--moments">
    <div class="page-intro__body">
        <h1><?php $this->title(); ?></h1>
        <div class="page-intro__description post-content">
            <?php if ($introHtml !== ''): ?>
                <?php echo $introHtml; ?>
            <?php else: ?>
                <p>比文章短一些，比即时消息慢一些。</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php $this->need('comments.php'); ?>

<?php $this->need('footer.php'); ?>
