<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
        </main>
        <script id="laoke-page-config" type="application/json"><?php echo json_encode(laoke_frontend_runtime_config($this), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    </div>
    <footer class="site-footer">
        <p><?php echo htmlspecialchars(laoke_option('footerText', '写字的人，和安静的页面。'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="meta-list center">
            <span class="meta-item"><?php echo date('Y'); ?> &copy; <?php $this->options->title(); ?></span>
            <?php if (laoke_option('beian')): ?>
                <span class="meta-item"><?php echo htmlspecialchars(laoke_option('beian'), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <span class="meta-item"><a href="https://github.com/zlemoni/LaoKe" target="_blank" rel="noopener noreferrer">Theme：LaoKe</span>
        </p>
    </footer>
</div>
<svg class="laoke-svg-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <symbol id="laoke-icon-like" viewBox="0 0 24 24">
        <path d="M12 20.5c-4.92-3.31-8-6.24-8-10.18C4 7.32 6.15 5.5 8.74 5.5c1.5 0 2.95.69 3.86 1.92A4.8 4.8 0 0 1 15.46 5.5C18.05 5.5 20 7.32 20 10.32c0 3.94-3.08 6.87-8 10.18Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
    </symbol>
    <symbol id="laoke-icon-moon" viewBox="0 0 24 24">
        <path d="M17.18 14.89A6.8 6.8 0 0 1 9.11 6.82a7.25 7.25 0 1 0 8.07 8.07Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
    </symbol>
    <symbol id="laoke-icon-sun" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="3.5" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
        <path d="M12 2.75V5.25M12 18.75v2.5M5.25 12H2.75M21.25 12h-2.5M18.19 5.81 16.42 7.58M7.58 16.42 5.81 18.19M18.19 18.19l-1.77-1.77M7.58 7.58 5.81 5.81" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
    </symbol>
    <symbol id="laoke-icon-eye" viewBox="0 0 24 24">
        <path d="M2.75 12s3.5-5.75 9.25-5.75S21.25 12 21.25 12 17.75 17.75 12 17.75 2.75 12 2.75 12Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
        <circle cx="12" cy="12" r="2.75" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
    </symbol>
    <symbol id="laoke-icon-comment" viewBox="0 0 24 24">
        <path d="M6.75 18.25 3.75 20l.87-3.67A6.9 6.9 0 0 1 3 12c0-4 3.8-7.25 8.5-7.25S20 8 20 12s-3.8 7.25-8.5 7.25c-1.11 0-2.18-.18-3.17-.52Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
    </symbol>
    <symbol id="laoke-icon-arrow-up" viewBox="0 0 24 24">
        <path d="M12 19.25V4.75M5.75 11 12 4.75 18.25 11" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
    </symbol>
</svg>
<button class="back-to-top" id="back-to-top" type="button" aria-label="返回顶部" title="返回顶部">
    <svg class="back-to-top__icon" aria-hidden="true" viewBox="0 0 24 24">
        <use href="#laoke-icon-arrow-up" xlink:href="#laoke-icon-arrow-up"></use>
    </svg>
    <span class="sr-only">返回顶部</span>
</button>
<button class="theme-toggle theme-toggle--floating" type="button" data-theme-toggle aria-label="切换主题" title="切换主题">
    <svg class="theme-toggle__icon theme-toggle__icon--moon" aria-hidden="true" viewBox="0 0 24 24">
        <use href="#laoke-icon-moon" xlink:href="#laoke-icon-moon"></use>
    </svg>
    <svg class="theme-toggle__icon theme-toggle__icon--sun" aria-hidden="true" viewBox="0 0 24 24">
        <use href="#laoke-icon-sun" xlink:href="#laoke-icon-sun"></use>
    </svg>
    <span class="sr-only">切换主题</span>
</button>
<div class="laoke-code-theme-panel" id="laoke-code-theme-panel" aria-label="代码高亮主题切换">
    <button class="laoke-code-theme-btn" type="button" data-code-theme="default" title="默认深色">
        <span class="laoke-code-theme-preview laoke-code-theme-preview--dark"></span>
    </button>
    <button class="laoke-code-theme-btn" type="button" data-code-theme="tomorrow-night" title="Tomorrow Night">
        <span class="laoke-code-theme-preview laoke-code-theme-preview--dark"></span>
    </button>
    <button class="laoke-code-theme-btn" type="button" data-code-theme="okaidia" title="Okaidia">
        <span class="laoke-code-theme-preview laoke-code-theme-preview--dark"></span>
    </button>
    <button class="laoke-code-theme-btn" type="button" data-code-theme="dracula" title="Dracula">
        <span class="laoke-code-theme-preview laoke-code-theme-preview--dark"></span>
    </button>
    <button class="laoke-code-theme-btn" type="button" data-code-theme="solarized-light" title="Solarized Light">
        <span class="laoke-code-theme-preview laoke-code-theme-preview--light"></span>
    </button>
    <button class="laoke-code-theme-btn" type="button" data-code-theme="github" title="GitHub Light">
        <span class="laoke-code-theme-preview laoke-code-theme-preview--light"></span>
    </button>
</div>
<div class="laoke-barrage" id="laoke-barrage" aria-hidden="true"></div>
<script>
(function () {
    var configNode = document.getElementById('laoke-page-config');
    if (!configNode) {
        window.LaoKeConfig = window.LaoKeConfig || {};
        return;
    }

    try {
        window.LaoKeConfig = JSON.parse(configNode.textContent || '{}');
    } catch (error) {
        window.LaoKeConfig = window.LaoKeConfig || {};
    }
})();
</script>
<script defer src="<?php echo laoke_theme_url('assets/js/vendor/prism.js'); ?>"></script>
<script defer src="<?php echo laoke_theme_url('assets/js/vendor/viewimages.js'); ?>"></script>
<script defer src="<?php echo laoke_theme_url('assets/js/vendor/aplayer.min.js'); ?>"></script>
<script defer src="<?php echo laoke_theme_url('assets/js/main.js'); ?>"></script>
<script defer src="<?php echo laoke_theme_url('assets/js/ajaxify.js'); ?>"></script>
</body>
</html>
