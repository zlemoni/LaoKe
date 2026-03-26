<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php // LaoKe comments template. ?>
<?php
$owoItems = laoke_owo_items();
$isTimeMachine = laoke_is_time_machine_content($this);
$canPublishTimeMachine = $isTimeMachine && laoke_can_publish_time_machine($this);
$showCommentForm = $isTimeMachine ? $canPublishTimeMachine : $this->allow('comment');
$momentCount = $isTimeMachine ? laoke_time_machine_total_entries($this->cid) : 0;
$captchaData = (!$isTimeMachine && !$this->user->hasLogin()) ? laoke_comment_captcha_data($this) : null;
$rememberSecret = !$isTimeMachine && !empty($_POST['secret']);
$rememberCaptchaAnswer = (!$isTimeMachine && !$this->user->hasLogin()) ? trim((string) ($_POST['laoke_captcha_answer'] ?? '')) : '';

laoke_comment_render_context($this);

function threadedComments($comments, $options)
{
    $archive = laoke_comment_render_context();
    $isTimeMachine = laoke_is_time_machine_content($archive);
    $isTopLevel = (int) $comments->levels === 0;
    $parentAuthor = laoke_comment_parent_author($comments);
    $isSecretComment = !$isTimeMachine && laoke_comment_extract_secret_text((string) $comments->text) !== null;
    $showParentAuthor = $parentAuthor !== '' && (!$isSecretComment || laoke_comment_can_view_secret($comments, $archive));
    $commentId = (int) $comments->coid;
    $liked = false;
    $likes = 0;

    if ($isTimeMachine && $isTopLevel && (string) $comments->status === 'approved') {
        $likes = laoke_get_comment_likes($commentId);
        $likedMap = laoke_time_machine_liked_map([$commentId]);
        $liked = !empty($likedMap[$commentId]);
    }
?>
<li id="comment-<?php echo $commentId; ?>" class="comment-item comment-level-<?php echo (int) $comments->levels + 1; ?><?php if ($isTimeMachine && $isTopLevel): ?> moment-comment-item<?php endif; ?>">
    <article class="comment-body<?php if ($isTimeMachine && $isTopLevel): ?> moment-comment-body<?php endif; ?>">
        <div class="comment-avatar<?php if ($isTimeMachine && $isTopLevel): ?> moment-comment-avatar<?php endif; ?>">
            <img src="<?php echo laoke_comment_avatar($comments->mail, 64); ?>" alt="<?php echo htmlspecialchars((string) $comments->author(false), ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async">
        </div>
        <header class="comment-meta<?php if ($isTimeMachine && $isTopLevel): ?> moment-comment-meta<?php endif; ?>">
            <cite><?php echo htmlspecialchars((string) $comments->author(false), ENT_QUOTES, 'UTF-8'); ?></cite>
            <?php if ($isTimeMachine && $isTopLevel && (string) $comments->status === 'approved'): ?>
                <div class="moment-comment-meta__aside">
                    <time datetime="<?php echo date('c', $comments->created); ?>"><?php echo laoke_relative_time($comments->created); ?></time>
                    <button class="moment-like-button<?php if ($liked): ?> is-liked<?php endif; ?>" type="button" data-moment-like data-coid="<?php echo $commentId; ?>" aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>" aria-label="<?php echo $liked ? '取消点赞' : '点赞'; ?>">
                        <svg class="moment-like-button__icon" aria-hidden="true" viewBox="0 0 24 24">
                            <use href="#laoke-icon-like" xlink:href="#laoke-icon-like"></use>
                        </svg>
                        <span class="moment-like-button__count" data-moment-like-count><?php echo number_format($likes); ?></span>
                    </button>
                </div>
            <?php else: ?>
                <time datetime="<?php echo date('c', $comments->created); ?>"><?php echo laoke_relative_time($comments->created); ?></time>
            <?php endif; ?>
        </header>
        <div class="comment-content<?php if ($isTimeMachine && $isTopLevel): ?> moment-comment-content<?php endif; ?>">
            <?php if ($showParentAuthor): ?>
                <p class="comment-reply-target">@<?php echo htmlspecialchars($parentAuthor, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php echo laoke_comment_content_html($comments); ?>
        </div>
        <?php if (!$isTimeMachine): ?>
            <div class="comment-actions"><?php $comments->reply('回复'); ?></div>
        <?php endif; ?>
    </article>
    <?php if ($comments->children): ?>
        <ol class="comment-children">
            <?php $comments->threadedComments($options); ?>
        </ol>
    <?php endif; ?>
</li>
<?php
}
?>
<section id="comments" class="comments-area<?php if ($isTimeMachine): ?> is-time-machine<?php endif; ?>"<?php if ($isTimeMachine): ?> data-moment-like-endpoint="<?php echo htmlspecialchars($this->permalink, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
    <?php $this->comments()->to($comments); ?>
    <header class="comments-header<?php if (!$isTimeMachine): ?> center<?php else: ?> comments-header--timeline<?php endif; ?>">
        <h2><?php echo $isTimeMachine ? '时光机' : '评论'; ?></h2>
        <p>
            <?php if ($isTimeMachine): ?>
                <?php if ($momentCount <= 0): ?>
                    还没有动态
                <?php elseif ($momentCount === 1): ?>
                    1 条动态
                <?php else: ?>
                    <?php echo number_format($momentCount); ?> 条动态
                <?php endif; ?>
            <?php else: ?>
                <?php $this->commentsNum('暂无评论', '1 条评论', '%d 条评论'); ?>
            <?php endif; ?>
        </p>
        <?php if ($isTimeMachine): ?>
            <p class="moment-feedback" id="moment-feedback" aria-live="polite">
                <span class="moment-feedback__pill" data-moment-feedback-pill></span>
            </p>
        <?php endif; ?>
    </header>

    <?php if ($comments->have()): ?>
        <?php $comments->listComments([
            'before' => '<ol class="comment-list">',
            'after' => '</ol>',
            'commentStatus' => $isTimeMachine ? '这条动态正在审核中。' : '你的评论正在审核中。'
        ]); ?>
        <?php $comments->pageNav('上一页', '下一页', 1, '', [
            'wrapTag' => 'ul',
            'wrapClass' => 'pager pager-comments',
            'itemTag' => 'li',
            'currentClass' => 'current'
        ]); ?>
    <?php elseif ($isTimeMachine): ?>
        <p class="empty-state">还没有时光机内容。</p>
    <?php endif; ?>

    <?php if ($showCommentForm): ?>
        <section id="<?php $this->respondId(); ?>" class="comment-respond<?php if ($isTimeMachine): ?> comment-respond--moment<?php endif; ?>">
            <?php if (!$isTimeMachine): ?>
                <div class="comment-cancel"><?php $comments->cancelReply('取消回复'); ?></div>
            <?php endif; ?>
            <form id="comment-form" class="comment-form<?php if ($isTimeMachine): ?> comment-form--moment<?php endif; ?>" method="post" action="<?php $this->commentUrl(); ?>" novalidate data-comment-mode="<?php echo $isTimeMachine ? 'moments' : 'comments'; ?>" data-comment-minlength="<?php echo $isTimeMachine ? '1' : '3'; ?>">
                <?php $commentToken = laoke_comment_form_token($this); ?>
                <?php if ($commentToken !== ''): ?>
                    <input type="hidden" name="_" value="<?php echo htmlspecialchars($commentToken, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <?php if ($this->user->hasLogin()): ?>
                    <p class="comment-login">
                        <?php if ($isTimeMachine): ?>
                            以 <a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a> 的身份记录此刻
                            |
                            <a href="<?php $this->options->logoutUrl(); ?>">退出</a>
                        <?php else: ?>
                            已登录为 <a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a>
                            |
                            <a href="<?php $this->options->logoutUrl(); ?>">退出</a>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <div class="comment-grid">
                        <label class="input-control">
                            <span class="sr-only">昵称</span>
                            <input type="text" name="author" value="<?php $this->remember('author'); ?>" placeholder="昵称" required>
                        </label>
                        <label class="input-control">
                            <span class="sr-only">Email</span>
                            <input type="email" name="mail" value="<?php $this->remember('mail'); ?>" placeholder="Email"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>>
                        </label>
                        <label class="input-control">
                            <span class="sr-only">网址</span>
                            <input type="url" name="url" value="<?php $this->remember('url'); ?>" placeholder="https://example.com"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?>>
                        </label>
                    </div>
                <?php endif; ?>
                <textarea class="form-control" name="text" id="textarea" rows="<?php echo $isTimeMachine ? '4' : '6'; ?>" placeholder="<?php echo $isTimeMachine ? '写下这一刻...' : '写下你的评论...'; ?>" required><?php $this->remember('text'); ?></textarea>
                <?php if (!$isTimeMachine): ?>
                    <div class="comment-footer">
                        <div class="comment-footer__left">
                            <?php if (!empty($owoItems)): ?>
                                <div class="comment-toolbar">
                                    <button class="comment-emoji-toggle" type="button" data-owo-toggle aria-expanded="false" aria-controls="comment-emoji-panel">表情</button>
                                </div>
                            <?php endif; ?>
                            <div class="comment-options">
                                <label class="comment-private-toggle" title="仅评论相关方可见">
                                    <input class="comment-private-toggle__input" type="checkbox" name="secret" value="1"<?php if ($rememberSecret): ?> checked<?php endif; ?>>
                                    <span class="comment-private-toggle__indicator" aria-hidden="true"></span>
                                    <span class="comment-private-toggle__text">私密评论</span>
                                </label>
                            </div>
                        </div>
                        <div class="comment-footer__right">
                            <?php if (!$this->user->hasLogin() && is_array($captchaData)): ?>
                                <div class="comment-captcha">
                                    <label class="comment-captcha__label" for="laoke-captcha-answer">验证码</label>
                                    <div class="comment-captcha__body">
                                        <label class="comment-captcha__equation" for="laoke-captcha-answer">
                                            <span class="comment-captcha__expression"><?php echo (int) $captchaData['a']; ?> + <?php echo (int) $captchaData['b']; ?> =</span>
                                            <input class="comment-captcha__input" type="text" name="laoke_captcha_answer" id="laoke-captcha-answer" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($rememberCaptchaAnswer, ENT_QUOTES, 'UTF-8'); ?>" placeholder="?" autocomplete="off" required>
                                        </label>
                                    </div>
                                    <input type="hidden" name="laoke_captcha_a" value="<?php echo (int) $captchaData['a']; ?>">
                                    <input type="hidden" name="laoke_captcha_b" value="<?php echo (int) $captchaData['b']; ?>">
                                    <input type="hidden" name="laoke_captcha_ts" value="<?php echo (int) $captchaData['ts']; ?>">
                                    <input type="hidden" name="laoke_captcha_sig" value="<?php echo htmlspecialchars((string) $captchaData['sig'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="comment-submit">
                                <button id="misubmit" type="submit">提交评论</button>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($owoItems)): ?>
                        <div id="comment-emoji-panel" class="comment-emoji-panel" data-owo-panel aria-hidden="true">
                            <div class="comment-emoji-panel__inner">
                                <?php foreach ($owoItems as $item): ?>
                                    <button class="comment-emoji-item" type="button" data-owo-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="插入表情 <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <img src="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <p class="comment-feedback" id="comment-feedback" aria-live="polite"></p>
                <?php else: ?>
                    <?php if (!empty($owoItems)): ?>
                        <div class="comment-toolbar">
                            <button class="comment-emoji-toggle" type="button" data-owo-toggle aria-expanded="false" aria-controls="comment-emoji-panel">表情</button>
                        </div>
                        <div id="comment-emoji-panel" class="comment-emoji-panel" data-owo-panel aria-hidden="true">
                            <div class="comment-emoji-panel__inner">
                                <?php foreach ($owoItems as $item): ?>
                                    <button class="comment-emoji-item" type="button" data-owo-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="插入表情 <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <img src="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="comment-submit">
                        <button id="misubmit" type="submit">发布动态</button>
                        <p class="comment-feedback" id="comment-feedback" aria-live="polite"></p>
                    </div>
                <?php endif; ?>
            </form>
        </section>
        <?php if (!$isTimeMachine): ?>
            <?php \Utils\Helper::threadedCommentsScript(); ?>
        <?php endif; ?>
    <?php elseif ($isTimeMachine && $this->user->hasLogin()): ?>
        <p class="empty-state">当前账号没有发布权限。请确认这张时光机页面的作者归属，或使用具备编辑权限的账号登录。</p>
    <?php elseif (!$isTimeMachine && !$this->allow('comment')): ?>
        <p class="empty-state">评论已关闭。</p>
    <?php endif; ?>
</section>
