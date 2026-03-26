# LaoKe 主题

LaoKe 是一个基于 Typecho 的纯文字、单栏、极简博客主题。

- **作者**：Zhang
- **链接**：<https://lao.ke>

## 特性

### 基础功能

- 响应式设计，适配各种屏幕尺寸
- 深色/浅色模式切换（支持自动跟随系统）
- 文章目录导航（自动生成）
- 阅读进度条
- 图片灯箱（ViewImages）
- Prism 代码高亮

### 评论增强

- 评论头像服务（支持 WeAvatar、Cravatar、Sep.cc、自定义）
- 私密评论（仅评论相关方可见）
- 时光机（微语）功能
- 弹幕评论展示
- 评论点赞
- 表情包支持（OwO 表情面板）
- 算术验证码防护

### 内容展示

- 相册功能（支持密码保护）
- 友链页面
- 文章归档页面
- 时光机页面
- 丰富的短代码支持

### 短代码

```
[tip type="blue"]提示内容[/tip]
[tip type="red"]注意内容[/tip]
[tip type="green"]推荐内容[/tip]
[tip type="yellow"]提示内容[/tip]
[tip type="share"]资料内容[/tip]
```

```
[collapse label="点击展开"]折叠的内容[/collapse]
```

```
[tabs]
[tab-pane label="标签1"]内容1[/tab-pane]
[tab-pane label="标签2"]内容2[/tab-pane]
[/tabs]
```

```
[timeline]
[timeline-item title="标题" time="2024-01-01"]内容[/timeline-item]
[/timeline]
```

```
[album]
![图片描述](图片地址)
[/album]
```

```
[photos]
![图片1](地址1)
![图片2](地址2)
[/photos]
```

```
[button color="#1890ff"]按钮文字[/button]
```

```
[video src="视频地址"][/video]
```

```
[bilibili bv="BV号"][/bilibili]
```

```
[mp3]歌曲ID[/mp3]
```

```
[login]登录可见内容[/login]
```

```
[hide]评论可见内容[/hide]
```

```
[color color="red"]彩色文字[/color]
```

```
[mks]隐藏内容[/mks]
```

```
[cid="123"][/cid] 链接卡片
```

### 增强功能

- AJAX 无刷新页面切换
- 图片懒加载
- 自定义 Head 代码
- 主题设置备份与恢复
- 文章访问统计

## 安装

1. 下载主题文件夹，上传到 Typecho 主题目录 `usr/themes/LaoKe`
2. 登录 Typecho 后台，进入「控制台」→「外观」，启用 LaoKe 主题
3. 进入「设置外观」配置主题选项

## 配置选项

### 基础设置

- **页脚文案**：显示在页脚左侧的简短说明
- **备案号**：可留空
- **建站时间**：按 YYYY-MM-DD 格式填写

### 评论设置

- **头像源**：WeAvatar / Cravatar / Sep.cc / 自定义
- **自定义头像源**：选择自定义时生效

### 功能增强

- **AJAX 无感切换**：提升页面切换体验
- **阅读进度条**：显示在页面顶部
- **图片懒加载**：延迟加载图片以提升性能
- **目录触发字数**：文章正文超过该值时启用目录
- **Meting 接口地址**：用于 \[mp3] 短代码

### 弹幕设置

- 开启/关闭弹幕
- 首页弹幕
- 文章页弹幕
- 移动端弹幕
- 弹幕透明度
- 弹幕显示间隔

## 页面模板

主题自带以下页面模板：

### 相册页 (albums.php)

创建独立页面，选择「相册」模板

### 友链页 (links.php)

创建独立页面，选择「链接」模板
需配合 Links 插件使用

### 时光机 (moments.php)

创建独立页面，选择「时光机」模板

### 归档页 (archives.php)

创建独立页面，选择「归档」模板

## 自定义

### 自定义 Head 代码

在主题设置中添加代码，会插入到 `</head>` 标签前

### 自定义 CSS

可以通过 `customHead` 添加自定义样式

## 文件结构

```
LaoKe/
├── assets/          # 静态资源
│   ├── css/         # 样式文件
│   └── js/          # 脚本文件
├── inc/             # 核心功能模块
│   ├── integrations.php
│   └── shortcodes.php
├── 404.php
├── albums.php       # 相册页模板
├── archive.php      # 分类/标签页
├── archives.php     # 归档页模板
├── comments.php     # 评论模板
├── footer.php       # 页脚
├── functions.php    # 主题函数
├── header.php      # 页头
├── index.php       # 首页模板
├── links.php       # 友链模板
├── moments.php     # 时光机模板
├── page.php        # 独立页面模板
├── post.php        # 文章页模板
└── screenshot.png  # 主题截图
```

## 依赖

- Typecho 1.2+
- PHP 7.4+

## 许可

基于 Typecho 主题协议开源。

