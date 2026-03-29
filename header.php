<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="auto">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="renderer" content="webkit">
    <meta name="color-scheme" content="light dark">
    <meta name="description" content="<?php echo htmlspecialchars(laoke_meta_description($this), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php $this->permalink(); ?>">
    <title><?php echo htmlspecialchars(laoke_page_title($this), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php laoke_render_frontend_head_assets(); ?>
    <link rel="preload" href="<?php echo laoke_theme_url('assets/css/base.css'); ?>" as="style">
    <link rel="preload" href="<?php echo laoke_theme_url('assets/css/layout.css'); ?>" as="style">
    <link rel="preload" href="<?php echo laoke_theme_url('assets/css/components.css'); ?>" as="style">
    <link rel="preload" href="<?php echo laoke_theme_url('assets/css/pages.css'); ?>" as="style">
    <link rel="preload" href="<?php echo laoke_theme_url('assets/js/main.js'); ?>" as="script" crossorigin="anonymous">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo laoke_theme_url('assets/css/base.css'); ?>">
    <link rel="stylesheet" href="<?php echo laoke_theme_url('assets/css/layout.css'); ?>">
    <link rel="stylesheet" href="<?php echo laoke_theme_url('assets/css/components.css'); ?>">
    <link rel="stylesheet" href="<?php echo laoke_theme_url('assets/css/pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo laoke_theme_url('assets/css/vendor/viewimages.css'); ?>">
    <link rel="stylesheet" href="<?php echo laoke_theme_url('assets/css/vendor/aplayer.min.css'); ?>">
    <?php $this->header(); ?>
    <?php laoke_render_json_ld($this); ?>
    <?php echo laoke_option('customHead'); ?>
</head>
<body id="page" class="<?php echo laoke_body_class($this); ?>">
<div class="progress-bar" id="reading-progress" aria-hidden="true"></div>
<a class="skip-link" href="#main-content">跳到内容</a>
<div class="container">
    <header class="site-header">
        <div class="header-wrap">
            <button id="nav-toggle" class="nav-toggle" type="button" aria-controls="nav" aria-expanded="false">菜单</button>
            <nav id="nav" class="site-nav" aria-label="主导航">
                <ul>
                    <li>
                        <a<?php if ($this->is('index')): ?> class="selected"<?php endif; ?> href="<?php $this->options->siteUrl(); ?>">首页</a>
                    </li>
                    <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
                    <?php while ($pages->next()): ?>
                        <li>
                            <a<?php if ($this->is('page', $pages->slug)): ?> class="selected"<?php endif; ?> href="<?php $pages->permalink(); ?>"><?php $pages->title(); ?></a>
                        </li>
                    <?php endwhile; ?>
                    <li>
                        <button class="nav-link theme-toggle theme-toggle--nav" type="button" data-theme-toggle aria-label="切换主题" title="切换主题">
                            <svg class="theme-toggle__icon theme-toggle__icon--moon" aria-hidden="true" viewBox="0 0 24 24">
                                <use href="#laoke-icon-moon" xlink:href="#laoke-icon-moon"></use>
                            </svg>
                            <svg class="theme-toggle__icon theme-toggle__icon--sun" aria-hidden="true" viewBox="0 0 24 24">
                                <use href="#laoke-icon-sun" xlink:href="#laoke-icon-sun"></use>
                            </svg>
                            <span class="sr-only">切换主题</span>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    <div id="ajax-root" class="main-shell" data-ajax-root data-toc-threshold="<?php echo (int) laoke_option('tocThreshold', '1500'); ?>">
        <main class="main" id="main-content">
