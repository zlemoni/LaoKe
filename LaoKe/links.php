<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * 友链页
 *
 * @package custom
 */
?>
<?php $this->need('header.php'); ?>

<?php
$linkGroups = laoke_links_grouped();
?>

<section class="page-intro">
    <h1 class="center"><?php $this->title(); ?></h1>
</section>

<?php if (empty($linkGroups)): ?>
    <section class="empty-state">
        <h2>还没有友链数据。</h2>
        <p>请先启用 Links 插件，再在后台添加链接。</p>
    </section>
<?php else: ?>
    <div class="links-groups">
        <?php foreach ($linkGroups as $groupName => $links): ?>
            <section class="links-group">
                <header class="links-group__head">
                    <h2><?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p>共 <?php echo count($links); ?> 个站点</p>
                </header>
                <div class="links-list">
                    <?php foreach ($links as $link): ?>
                        <?php
                        $host = parse_url($link['url'], PHP_URL_HOST);
                        $host = $host ? preg_replace('/^www\./i', '', $host) : $link['url'];
                        ?>
                        <a class="link-item" href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener external nofollow" data-link-latency="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="link-item__icon">
                                <?php if (!empty($link['image'])): ?>
                                    <img src="<?php echo laoke_image_placeholder(); ?>" data-src="<?php echo htmlspecialchars($link['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php else: ?>
                                    <?php echo htmlspecialchars(mb_substr($link['name'], 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </span>
                            <span class="link-item__body">
                                <span class="link-item__top">
                                    <strong><?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="link-item__host"><?php echo htmlspecialchars($host, ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                                <span class="link-item__desc"><?php echo htmlspecialchars($link['description'] ?: '这个朋友暂时没有填写描述。', ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                            <span class="link-item__aside">
                                <span class="link-item__latency is-checking" data-latency-badge>检测中</span>
                                <span class="link-item__arrow" aria-hidden="true">↗</span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
