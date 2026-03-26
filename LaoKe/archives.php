<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * 归档页
 *
 * @package custom
 */
?>
<?php $this->need('header.php'); ?>

<?php
$groups = [];
$allCids = [];
$this->widget('Widget_Contents_Post_Recent', 'pageSize=10000')->to($archives);
while ($archives->next()) {
    $year = date('Y', $archives->created);
    if (!isset($groups[$year])) {
        $groups[$year] = [];
    }

    $cid = (int) $archives->cid;
    $allCids[] = $cid;

    $groups[$year][] = [
        'cid' => $cid,
        'title' => $archives->title,
        'permalink' => $archives->permalink,
        'date' => date('c', $archives->created),
        'dateLabel' => date('m-d', $archives->created),
        'commentsNum' => (int) $archives->commentsNum
    ];
}
$viewsMap = laoke_get_views_map($allCids);
$postTotal = laoke_total_posts();
$wordTotal = laoke_total_words();
$viewTotal = laoke_total_views();
$siteDays = laoke_site_running_days();
$currentYear = date('Y');
$defaultOpenYear = isset($groups[$currentYear]) ? $currentYear : '';
if ($defaultOpenYear === '' && !empty($groups)) {
    $years = array_keys($groups);
    $defaultOpenYear = (string) $years[0];
}
?>

<section class="page-intro">
    <h1 class="center"><?php $this->title(); ?></h1>
</section>

<section class="archive-stats archive-stats-grid">
    <article class="stat-card">
        <span class="stat-card__label">文章总数</span>
        <strong class="stat-card__value" data-countup="<?php echo $postTotal; ?>"><?php echo number_format($postTotal); ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-card__label">字数统计</span>
        <strong class="stat-card__value" data-countup="<?php echo $wordTotal; ?>" data-suffix=" 字"><?php echo number_format($wordTotal); ?> 字</strong>
    </article>
    <article class="stat-card">
        <span class="stat-card__label">访问次数</span>
        <strong class="stat-card__value" data-countup="<?php echo $viewTotal; ?>" data-suffix=" 次"><?php echo number_format($viewTotal); ?> 次</strong>
    </article>
    <article class="stat-card">
        <span class="stat-card__label">建站天数</span>
        <strong class="stat-card__value" data-countup="<?php echo $siteDays; ?>" data-suffix=" 天"><?php echo number_format($siteDays); ?> 天</strong>
    </article>
</section>

<section class="archive-timeline">
    <header class="section-head center">
        <h2>时间归档</h2>
    </header>
    <?php foreach ($groups as $year => $items): ?>
        <details class="archive-year"<?php if ((string) $year === (string) $defaultOpenYear): ?> open<?php endif; ?>>
            <summary class="archive-year__head">
                <span class="archive-year__summary">
                    <span class="archive-year__title"><?php echo $year; ?></span>
                    <span class="archive-year__count"><?php echo count($items); ?> 篇文章</span>
                </span>
                <span class="archive-year__toggle" aria-hidden="true"></span>
            </summary>
            <ol class="archive-year__list">
                <?php foreach ($items as $item): ?>
                    <li class="archive-entry">
                        <time class="archive-entry__date" datetime="<?php echo $item['date']; ?>"><?php echo $item['dateLabel']; ?></time>
                        <div class="archive-entry__main">
                            <a class="archive-entry__title" href="<?php echo htmlspecialchars($item['permalink'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></a>
                            <div class="archive-entry__meta">
                                <span class="archive-entry__badge">
                                    <svg class="archive-entry__icon" aria-hidden="true" viewBox="0 0 24 24">
                                        <use href="#laoke-icon-eye" xlink:href="#laoke-icon-eye"></use>
                                    </svg>
                                    <span><?php echo number_format((int) ($viewsMap[$item['cid']] ?? 0)); ?></span>
                                </span>
                                <span class="archive-entry__badge">
                                    <svg class="archive-entry__icon" aria-hidden="true" viewBox="0 0 24 24">
                                        <use href="#laoke-icon-comment" xlink:href="#laoke-icon-comment"></use>
                                    </svg>
                                    <span><?php echo number_format((int) $item['commentsNum']); ?></span>
                                </span>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </details>
    <?php endforeach; ?>
</section>

<?php $this->need('footer.php'); ?>
