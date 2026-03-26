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
<section class="page-intro page-intro--moments">
    <h1 class="center"><?php $this->title(); ?></h1>
</section>

<?php if ($introHtml !== ''): ?>
    <section class="moment-intro">
        <div class="post-content">
            <?php echo $introHtml; ?>
        </div>
    </section>
<?php endif; ?>

<?php $this->need('comments.php'); ?>

<?php $this->need('footer.php'); ?>
