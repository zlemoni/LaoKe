<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * 相册页
 *
 * @package custom
 */
?>
<?php $this->need('header.php'); ?>

<?php if ($this->hidden): ?>
    <article class="entry entry-page entry-page--albums">
        <header class="entry-header">
            <h1 class="entry-title center"><?php $this->title(); ?></h1>
        </header>
        <div class="post-content">
            <?php $this->content(); ?>
        </div>
    </article>
<?php else: ?>
    <?php
    $albumPage = laoke_album_page_data($this);
    $introHtml = trim((string) ($albumPage['introHtml'] ?? ''));
    $albums = is_array($albumPage['albums'] ?? null) ? $albumPage['albums'] : [];
    ?>
    <section class="page-intro page-intro--albums">
        <h1 class="center"><?php $this->title(); ?></h1>
        <?php if (!empty($albums)): ?>
            <p class="center">共 <?php echo number_format(count($albums)); ?> 个相册</p>
        <?php endif; ?>
    </section>

    <?php if ($introHtml !== ''): ?>
        <section class="album-intro">
            <div class="post-content">
                <?php echo $introHtml; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="albums-page" data-album-page data-album-endpoint="<?php echo htmlspecialchars($this->permalink, ENT_QUOTES, 'UTF-8'); ?>" data-album-cid="<?php echo (int) $this->cid; ?>">
        <?php if (empty($albums)): ?>
            <p class="empty-state">还没有相册内容。</p>
        <?php else: ?>
            <div class="albums-shell is-list" data-album-shell>
                <div class="albums-grid-view" data-album-grid>
                    <div class="albums-grid">
                        <?php foreach ($albums as $index => $album): ?>
                            <?php echo laoke_render_album_card($album, $index); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="albums-detail-view" data-album-detail hidden>
                    <div class="albums-detail-body" data-album-detail-body></div>
                </div>

                <div class="albums-templates" hidden>
                    <?php foreach ($albums as $album): ?>
                        <?php if (!$album['protected'] || $album['unlocked']): ?>
                            <template data-album-template="<?php echo htmlspecialchars((string) $album['key'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo laoke_render_album_detail($album); ?>
                            </template>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="album-unlock-modal" data-album-unlock-modal aria-hidden="true">
            <div class="album-unlock-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="album-unlock-title">
                <button class="album-unlock-modal__close" type="button" data-album-unlock-close aria-label="关闭">关闭</button>
                <p class="album-unlock-modal__eyebrow">加密相册</p>
                <h2 class="album-unlock-modal__title" id="album-unlock-title" data-album-unlock-title>输入密码</h2>
                <form class="album-unlock-form" data-album-unlock-form>
                    <input type="hidden" name="album_key" value="" data-album-unlock-key>
                    <label class="input-control album-unlock-form__field">
                        <span class="sr-only">相册密码</span>
                        <input type="password" name="password" placeholder="输入访问密码" autocomplete="current-password" required data-album-unlock-input>
                    </label>
                    <p class="album-unlock-form__feedback" data-album-unlock-feedback aria-live="polite"></p>
                    <div class="album-unlock-form__actions">
                        <button class="album-unlock-form__cancel" type="button" data-album-unlock-close>取消</button>
                        <button class="album-unlock-form__submit" type="submit">解锁相册</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($this->allow('comment')): ?>
    <?php $this->need('comments.php'); ?>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
