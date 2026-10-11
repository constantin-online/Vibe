<?php
require_once 'db.php';
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT username, full_name, bio, avatar, role, verified, social_links FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userData) {
        $_SESSION['username'] = $userData['username'];
        $_SESSION['full_name'] = $userData['full_name'];
        $_SESSION['bio'] = $userData['bio'];
        $_SESSION['avatar'] = $userData['avatar'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['verified'] = $userData['verified'];
        $_SESSION['social_links'] = $userData['social_links'];
    }
}

$isLoggedIn = isset($_SESSION['user_id']);
$page = $_GET['page'] ?? '';
$username_param = $_GET['user'] ?? '';
$search_mode = isset($_GET['search']) && $_GET['search'] === '1';

$socialLinksArray = [];
try {
    $decoded = json_decode($_SESSION['social_links'] ?? '[]', true);
    if (is_array($decoded)) $socialLinksArray = $decoded;
} catch (Exception $e) {}
$socialLinksText = implode("\n", $socialLinksArray);
?>
<!DOCTYPE html>
<html lang="ru">
    <link rel="shortcut icon" href="/favicon.ico">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vibe — Делись моментами | vibe-app.su</title>
    <meta name="description" content="Vibe — социальная сеть для обмена мыслями, фото и видео.">
    <meta name="keywords" content="vibe, социальная сеть, общение, фото, видео">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#000000">
    <link rel="apple-touch-icon" href="/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { background: #fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 999px; font-size: 16px; color: #000; text-decoration: none; transition: background 0.15s; }
        .sidebar-link:hover { background: #f0f0f0; }
        .sidebar-link.active { font-weight: 700; }
        .post-btn-action { display: flex; align-items: center; gap: 6px; color: #666; transition: color 0.15s; cursor: pointer; }
        .post-btn-action:hover { color: #000; }
        .post-btn-action.liked { color: #ff3040; }
        .post-btn-action.liked svg { fill: #ff3040; }
        .tab-btn { padding: 16px 0; font-size: 15px; color: #666; border-bottom: 2px solid transparent; cursor: pointer; }
        .tab-btn.active { color: #000; font-weight: 700; border-bottom-color: #000; }
        .floating-up { position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px; border-radius: 50%; background: #000; color: #fff; display: none; align-items: center; justify-content: center; font-size: 24px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 40; }
        .floating-up:hover { background: #333; }
        .floating-up.visible { display: flex; }
        .photos-grid { display: grid; gap: 4px; border-radius: 16px; overflow: hidden; margin-top: 12px; }
        .photos-grid.cols-1 { grid-template-columns: 1fr; }
        .photos-grid.cols-2 { grid-template-columns: 1fr 1fr; }
        .photos-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .photos-grid img, .photos-grid video { width: 100%; aspect-ratio: 1; object-fit: cover; }
        .photos-grid.cols-1 img, .photos-grid.cols-1 video { aspect-ratio: 16/9; }
        .badge-mod { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; background: #1d9bf0; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 900; margin-left: 4px; }
        .badge-verified { display: inline-flex; align-items: center; margin-left: 4px; cursor: help; position: relative; }
        .badge-verified:hover::after { content: 'Верифицированный аккаунт'; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #000; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; white-space: nowrap; margin-bottom: 4px; pointer-events: none; z-index: 100; }
        .profile-avatar-wrapper { position: relative; cursor: pointer; display: inline-block; }
        .profile-avatar-wrapper:hover::after { content: '📷'; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.4); border-radius: 9999px; font-size: 24px; }
        .comment-input { border: 1px solid #e5e7eb; border-radius: 999px; padding: 8px 16px; font-size: 14px; outline: none; transition: border-color 0.15s; }
        .comment-input:focus { border-color: #000; }
        .modal-scroll { max-height: 90vh; overflow-y: auto; }
        .doc-preview { position: relative; display: inline-block; }
        .doc-preview img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; }
        .doc-preview .remove-doc { position: absolute; top: -6px; right: -6px; background: #ef4444; color: #fff; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; border: 2px solid #fff; }
        .msg-bubble { max-width: 75%; padding: 10px 14px; border-radius: 18px; margin-bottom: 8px; word-wrap: break-word; }
        .msg-bubble.sent { background: #000; color: #fff; margin-left: auto; border-bottom-right-radius: 4px; }
        .msg-bubble.received { background: #f3f4f6; color: #000; border-bottom-left-radius: 4px; }
        .msg-time { font-size: 11px; opacity: 0.6; margin-top: 4px; }
        .dialog-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: pointer; transition: background 0.15s; }
        .dialog-item:hover { background: #f9fafb; }
        .unread-badge { background: #ef4444; color: #fff; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 999px; min-width: 20px; text-align: center; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
        .toast { background: #fff; border-left: 4px solid #000; border-radius: 12px; padding: 14px 18px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); min-width: 280px; max-width: 360px; pointer-events: auto; animation: toastIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; transition: transform 0.2s; }
        .toast:hover { transform: scale(1.02); }
        .toast.hiding { animation: toastOut 0.3s ease-in forwards; }
        @keyframes toastIn { from { opacity: 0; transform: translateX(100px) scale(0.8); } to { opacity: 1; transform: translateX(0) scale(1); } }
        @keyframes toastOut { from { opacity: 1; transform: translateX(0) scale(1); } to { opacity: 0; transform: translateX(100px) scale(0.8); } }
        .toast-icon { font-size: 20px; margin-right: 10px; }
        .toast-title { font-weight: 700; font-size: 14px; margin-bottom: 2px; }
        .toast-text { font-size: 13px; color: #666; }
        .toast-close { position: absolute; top: 8px; right: 12px; color: #999; cursor: pointer; font-size: 18px; }
        .toast-close:hover { color: #000; }
        @keyframes pulse-red { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }
        .msg-pulse { animation: pulse-red 0.6s ease-in-out 3; }
        .gif-badge { position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.7); color: #fff; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; }
        .emoji-btn { font-size: 28px; padding: 6px; border-radius: 8px; cursor: pointer; transition: background 0.15s; text-align: center; }
        .emoji-btn:hover { background: #f3f4f6; transform: scale(1.15); }
        .emoji-category-title { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin: 12px 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }
        .emoji-tab { padding: 8px 12px; font-size: 20px; border-radius: 8px; cursor: pointer; transition: background 0.15s; }
        .emoji-tab:hover { background: #f3f4f6; }
        .emoji-tab.active { background: #000; }
        .poll-option { padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; cursor: pointer; transition: all 0.2s; position: relative; overflow: hidden; }
        .poll-option:hover { border-color: #000; background: #f9fafb; }
        .poll-option.voted { cursor: default; }
        .poll-option.voted:hover { border-color: #e5e7eb; background: #fff; }
        .poll-option.selected { border-color: #000; background: #f3f4f6; }
        .poll-bar { position: absolute; top: 0; left: 0; height: 100%; background: #f3f4f6; z-index: 0; transition: width 0.5s ease; }
        .poll-option-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; }
        .poll-percentage { font-weight: 700; font-size: 14px; }
        .md-content strong { font-weight: 700; }
        .md-content em { font-style: italic; }
        .md-content u { text-decoration: underline; }
        .md-content s { text-decoration: line-through; color: #6b7280; }
        .editor-format-btn { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: background 0.15s; color: #374151; }
        .editor-format-btn:hover { background: #e5e7eb; }
        .editor-format-btn.active { background: #000; color: #fff; }
        #location-map { height: 300px; width: 100%; border-radius: 12px; z-index: 1; }
        #viewer-map { height: 300px; width: 100%; border-radius: 12px; z-index: 1; }
        .location-badge { display: inline-flex; align-items: center; gap: 4px; color: #6b7280; font-size: 13px; margin-top: 8px; }
        .location-badge svg { width: 14px; height: 14px; flex-shrink: 0; }
        .footer-link { color: #9ca3af; font-size: 12px; transition: color 0.15s; }
        .footer-link:hover { color: #4b5563; }
        .page-content h1 { font-size: 1.875rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 1rem; }
        .page-content h2 { font-size: 1.5rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        .page-content h3 { font-size: 1.25rem; font-weight: 700; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .page-content h4 { font-size: 1.1rem; font-weight: 700; margin-top: 1rem; margin-bottom: 0.5rem; }
        .page-content p { margin-bottom: 1rem; line-height: 1.6; color: #374151; }
        .page-content ul { list-style-type: disc; list-style-position: inside; margin-bottom: 1rem; padding-left: 1rem; }
        .page-content ul li { margin-bottom: 0.25rem; color: #374151; }
        .page-content ol { list-style-type: decimal; list-style-position: inside; margin-bottom: 1rem; padding-left: 1rem; }
        .page-content ol li { margin-bottom: 0.25rem; color: #374151; }
        .page-content a { color: #2563eb; text-decoration: underline; }
        .page-content a:hover { color: #1d4ed8; }
        .page-content strong { font-weight: 700; }
        .page-content em { font-style: italic; }
        .page-content blockquote { border-left: 4px solid #e5e7eb; padding-left: 1rem; margin: 1rem 0; color: #6b7280; font-style: italic; }
        .page-content img { max-width: 100%; border-radius: 12px; margin: 1rem 0; }
        .page-content hr { border: none; border-top: 1px solid #e5e7eb; margin: 2rem 0; }
        .page-content code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.9em; }
        .page-content pre { background: #f3f4f6; padding: 1rem; border-radius: 8px; overflow-x: auto; margin: 1rem 0; }
        
        /* Skeleton loader */
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) { 
            .sidebar-desktop { display: none !important; } 
            .main-content { margin-left: 0 !important; } 
            .bottom-nav { display: flex !important; }
            .modal-scroll { max-height: 95vh; }
            .toast-container { top: 10px; right: 10px; left: 10px; }
            .toast { min-width: auto; max-width: 100%; }
        }
        @media (min-width: 769px) { .bottom-nav { display: none !important; } }
    </style>
</head>
<body class="text-black">

<div class="toast-container" id="toast-container"></div>

<?php if ($isLoggedIn): ?>
<aside class="sidebar-desktop fixed left-0 top-0 h-screen w-64 border-r border-gray-200 p-4 flex flex-col">
    <a href="/" class="mb-6 px-4"><h1 class="text-3xl font-black">Vibe</h1></a>
    <nav class="flex-1 space-y-1">
        <a href="/" class="sidebar-link <?= ($page === 'home' && !$search_mode) || $page === '' ? 'active' : '' ?>">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Для вас
        </a>
        <a href="/?search=1" class="sidebar-link <?= $search_mode ? 'active' : '' ?>">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Поиск
        </a>
        <a href="/?page=messages" class="sidebar-link <?= $page === 'messages' ? 'active' : '' ?>" id="nav-messages">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Сообщения
            <span id="msg-badge" class="hidden ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
        </a>
        <a href="/?page=profile&user=<?= $_SESSION['username'] ?>" class="sidebar-link">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Профиль
        </a>
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator'): ?>
        <a href="/moderator.php" class="sidebar-link text-blue-600" target="_blank">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Модерация
        </a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="/admin.php" class="sidebar-link text-purple-600" target="_blank">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Админка
        </a>
        <?php endif; ?>
    </nav>
    
    <!-- Футер-меню -->
    <div class="px-4 py-3 border-t border-gray-200 mt-auto">
        <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-400">
            <a href="/?page=blog" class="footer-link">Блог</a>
            <a href="/?page=about" class="footer-link">О Vibe</a>
            <a href="/?page=legal" class="footer-link">Правовая информация</a>
        </div>
        <div class="mt-2 text-xs text-gray-400">
            <a href="/?page=content-info" class="footer-link">Информация о контенте</a>
        </div>
    </div>

    <button onclick="logout()" class="sidebar-link text-red-500">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Выйти
    </button>
</aside>
<?php endif; ?>

<div class="main-content <?= $isLoggedIn ? 'md:ml-64' : '' ?> min-h-screen">
    <div class="max-w-2xl mx-auto border-r border-gray-200 min-h-screen">
        <?php if ($page === 'home' || $page === ''): ?>
        
        <div id="search-panel" class="<?= $search_mode ? '' : 'hidden' ?> border-b border-gray-200 p-4 bg-white">
            <input type="text" id="search-input" placeholder="Поиск" class="w-full p-3 rounded-lg bg-gray-100 outline-none" oninput="doSearch(this.value)">
            <div id="search-results" class="mt-3 space-y-2"></div>
        </div>
        
        <div id="main-feed" class="<?= $search_mode ? 'hidden' : '' ?>">
            <?php if ($isLoggedIn): ?>
            <div class="border-b border-gray-200 p-4">
                <div class="flex gap-3">
                    <?php if ($_SESSION['avatar']): ?>
                        <img src="<?= htmlspecialchars($_SESSION['avatar']) ?>" class="w-10 h-10 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold"><?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <textarea id="post-content" rows="2" placeholder="Что нового?" class="w-full resize-none outline-none text-lg" maxlength="500" onkeydown="handlePostEnter(event)"></textarea>
                        <div id="images-preview" class="hidden mt-2 photos-grid cols-2"></div>
                        <div id="video-preview" class="hidden mt-2 relative">
                            <video id="preview-video" class="rounded-2xl w-full max-h-80" controls></video>
                            <button onclick="removeVideo()" class="absolute top-2 right-2 bg-black/60 text-white rounded-full w-7 h-7 flex items-center justify-center">×</button>
                        </div>
                        <div id="gif-selected" class="hidden mt-2 relative">
                            <img id="gif-selected-img" class="rounded-2xl w-full max-h-80 object-cover">
                            <button onclick="removeSelectedGif()" class="absolute top-2 right-2 bg-black/60 text-white rounded-full w-7 h-7 flex items-center justify-center">×</button>
                            <div class="absolute top-2 left-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full font-bold">GIF</div>
                        </div>
                        <div id="poll-preview" class="hidden mt-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    <span class="font-bold text-sm">Опрос</span>
                                </div>
                                <button onclick="removePoll()" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
                            </div>
                            <div id="poll-preview-question" class="font-medium mb-2"></div>
                            <div id="poll-preview-options" class="space-y-1 text-sm text-gray-600"></div>
                        </div>
                        <div id="location-preview" class="hidden mt-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="font-medium text-sm" id="location-preview-name"></span>
                                </div>
                                <button onclick="removeLocation()" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-3">
                            <div class="flex gap-2">
                                <label class="cursor-pointer text-gray-500 hover:text-black p-2" title="Фото">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <input type="file" id="images-input" accept="image/*" multiple class="hidden" onchange="handleImagesSelect(this)">
                                </label>
                                <label class="cursor-pointer text-gray-500 hover:text-black p-2" title="Видео (макс. 1 мин)">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <input type="file" id="video-input" accept="video/*" class="hidden" onchange="handleVideoSelect(this)">
                                </label>
                                <button onclick="openGifModal()" class="text-gray-500 hover:text-black p-2" title="GIF">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <rect x="3" y="5" width="18" height="14" rx="2" ry="2"/>
                                        <text x="6" y="15" font-size="7" font-weight="bold" fill="currentColor" font-family="Arial">GIF</text>
                                    </svg>
                                </button>
                                <button onclick="openEmojiModal()" class="text-gray-500 hover:text-black p-2" title="Смайлики">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                                        <line x1="9" y1="9" x2="9.01" y2="9"/>
                                        <line x1="15" y1="9" x2="15.01" y2="9"/>
                                    </svg>
                                </button>
                                <button onclick="openPollModal()" class="text-gray-500 hover:text-black p-2" title="Опрос">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </button>
                                <button onclick="openEditorModal()" class="text-gray-500 hover:text-black p-2" title="Редактор текста">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button onclick="openLocationModal()" class="text-gray-500 hover:text-black p-2" title="Геолокация">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </button>
                            </div>
                            <button onclick="createPost()" class="bg-black text-white px-5 py-2 rounded-full font-bold disabled:opacity-40" id="post-btn">Опубликовать</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="p-8 text-center border-b border-gray-200 bg-gray-50/50">
                <h2 class="text-2xl font-bold mb-2">Добро пожаловать в Vibe</h2>
                <p class="text-gray-600 mb-6">Делись моментами, фото и видео с друзьями.</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="showAuthModal('login')" class="bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">Войти</button>
                    <button onclick="showAuthModal('register')" class="bg-white text-black border-2 border-black px-6 py-3 rounded-full font-bold hover:bg-gray-100 transition">Регистрация</button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="flex border-b border-gray-200">
                <button onclick="switchTab('all')" id="tab-all" class="flex-1 tab-btn active">Для вас</button>
                <?php if ($isLoggedIn): ?><button onclick="switchTab('following')" id="tab-following" class="flex-1 tab-btn">Подписки</button><?php endif; ?>
            </div>
            
            <!-- Skeleton loader -->
            <div id="feed">
                <div class="p-4 border-b border-gray-100">
                    <div class="flex gap-3">
                        <div class="w-10 h-10 rounded-full skeleton"></div>
                        <div class="flex-1">
                            <div class="h-4 skeleton w-1/4 mb-2"></div>
                            <div class="h-3 skeleton w-3/4 mb-1"></div>
                            <div class="h-3 skeleton w-1/2"></div>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-b border-gray-100">
                    <div class="flex gap-3">
                        <div class="w-10 h-10 rounded-full skeleton"></div>
                        <div class="flex-1">
                            <div class="h-4 skeleton w-1/4 mb-2"></div>
                            <div class="h-3 skeleton w-3/4 mb-1"></div>
                            <div class="h-3 skeleton w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($page === 'profile' && $username_param): ?>
        <header class="sticky top-0 bg-white/95 backdrop-blur border-b border-gray-200 px-4 py-3 z-30 flex items-center gap-4">
            <a href="/" class="text-2xl">←</a>
            <h1 class="text-lg font-bold"><?= htmlspecialchars($username_param) ?></h1>
        </header>
        <div id="profile-content" class="p-4">
            <div class="text-center py-10 skeleton" style="height: 200px; border-radius: 12px;"></div>
        </div>
        
        <?php elseif ($page === 'messages' && $isLoggedIn): ?>
        <header class="sticky top-0 bg-white/95 backdrop-blur border-b border-gray-200 px-4 py-3 z-30 flex items-center gap-4">
            <a href="/" class="text-2xl">←</a>
            <h1 class="text-lg font-bold">Сообщения</h1>
        </header>
        <div id="messages-content" class="p-4">
            <div class="text-center py-10 skeleton" style="height: 200px; border-radius: 12px;"></div>
        </div>

        <?php elseif (in_array($page, ['blog', 'about', 'legal', 'content-info'])): 
            $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
            $stmt->execute([$page]);
            $cmsPage = $stmt->fetch(PDO::FETCH_ASSOC);
            $pageTitle = $cmsPage['title'] ?? ucfirst(str_replace('-', ' ', $page));
        ?>
        <header class="sticky top-0 bg-white/95 backdrop-blur border-b border-gray-200 px-4 py-3 z-30 flex items-center gap-4">
            <a href="/" class="text-2xl">←</a>
            <h1 class="text-lg font-bold"><?= htmlspecialchars($pageTitle) ?></h1>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/pages-admin.php?action=edit&slug=<?= urlencode($page) ?>" target="_blank" class="ml-auto text-sm text-blue-600 hover:underline">✏️ Редактировать</a>
            <?php endif; ?>
        </header>
        <div class="p-6 max-w-2xl mx-auto">
            <?php if ($cmsPage && !empty($cmsPage['content'])): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6 page-content">
                    <?= $cmsPage['content'] ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
                    <div class="text-6xl mb-4">📝</div>
                    <h2 class="text-2xl font-bold mb-2">Страница в разработке</h2>
                    <p class="text-gray-600 mb-4">Контент этой страницы ещё не добавлен.</p>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="/pages-admin.php?action=edit&slug=<?= urlencode($page) ?>" class="inline-block bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">Добавить контент →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <div class="p-10 text-center">
            <h2 class="text-2xl font-bold mb-2">Страница не найдена</h2>
            <a href="/" class="text-blue-500 hover:underline">На главную</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isLoggedIn): ?>
<button onclick="scrollToTop()" id="scroll-up-btn" class="floating-up" title="Наверх">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 15l7-7 7 7"/></svg>
</button>
<nav class="bottom-nav fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 hidden">
    <div class="flex justify-around items-center h-14">
        <a href="/" class="p-2"><svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></a>
        <a href="/?page=messages" class="p-2 relative" id="nav-messages-mobile">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span id="msg-badge-mobile" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">0</span>
        </a>
        <a href="/?page=profile&user=<?= $_SESSION['username'] ?>" class="p-2"><svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></a>
    </div>
</nav>
<?php endif; ?>

<!-- Модалка Входа/Регистрации -->
<div id="auth-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl relative">
        <h2 class="text-2xl font-bold mb-4 text-center">Vibe</h2>
        <div id="login-form">
            <input type="text" id="login-login" placeholder="Имя пользователя или email" class="w-full mb-3 p-3 border border-gray-300 rounded-xl outline-none">
            <input type="password" id="login-password" placeholder="Пароль" class="w-full mb-4 p-3 border border-gray-300 rounded-xl outline-none">
            <button onclick="doLogin()" class="w-full bg-black text-white py-3 rounded-xl font-bold">Войти</button>
            <p class="text-center mt-4 text-sm text-gray-600">Нет аккаунта? <button onclick="toggleAuthMode()" class="font-bold underline">Зарегистрироваться</button></p>
        </div>
        <div id="register-form" class="hidden">
            <input type="text" id="reg-username" placeholder="Имя пользователя (латиница)" class="w-full mb-3 p-3 border border-gray-300 rounded-xl outline-none">
            <input type="email" id="reg-email" placeholder="Email" class="w-full mb-3 p-3 border border-gray-300 rounded-xl outline-none">
            <input type="password" id="reg-password" placeholder="Пароль (мин. 4 символа)" class="w-full mb-2 p-3 border border-gray-300 rounded-xl outline-none">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 flex gap-2 items-start">
                <span class="text-amber-600 text-lg leading-none">⚠️</span>
                <p class="text-xs text-amber-800 leading-tight">После регистрации мы отправим письмо для активации. <strong>Проверь папку «Спам»</strong>.</p>
            </div>
            <button onclick="doRegister()" class="w-full bg-black text-white py-3 rounded-xl font-bold">Зарегистрироваться</button>
            <p class="text-center mt-4 text-sm text-gray-600">Есть аккаунт? <button onclick="toggleAuthMode()" class="font-bold underline">Войти</button></p>
        </div>
        <p id="auth-message" class="text-center mt-3 text-sm font-medium"></p>
        <button onclick="closeAuthModal()" class="absolute top-4 right-4 text-gray-400 text-2xl">&times;</button>
    </div>
</div>

<!-- Модалка Жалобы -->
<div id="report-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl relative">
        <h2 class="text-xl font-bold mb-2">Пожаловаться</h2>
        <div id="report-reasons" class="space-y-2 mb-4">
            <button class="w-full text-left p-3 rounded-xl border hover:bg-gray-50" onclick="selectReason('spam', this)">📧 Спам</button>
            <button class="w-full text-left p-3 rounded-xl border hover:bg-gray-50" onclick="selectReason('abuse', this)">😡 Оскорбления</button>
            <button class="w-full text-left p-3 rounded-xl border hover:bg-gray-50" onclick="selectReason('other', this)">📝 Другое</button>
        </div>
        <button onclick="submitReport()" class="w-full bg-black text-white py-3 rounded-xl font-bold" id="submit-report-btn" disabled>Отправить</button>
        <button onclick="closeReportModal()" class="absolute top-4 right-4 text-2xl">&times;</button>
    </div>
</div>

<!-- Модалка Поделиться -->
<div id="share-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl relative">
        <h2 class="text-xl font-bold mb-4">Поделиться</h2>
        <div id="share-content" class="space-y-4"></div>
        <button onclick="closeShareModal()" class="absolute top-4 right-4 text-2xl">&times;</button>
    </div>
</div>

<!-- Модалка Верификации -->
<div id="verification-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl relative modal-scroll">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-2">Заявка на верификацию</h2>
            <p class="text-gray-600 text-sm mb-4">Заполните все поля для подтверждения личности.</p>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Имя <span class="text-red-500">*</span></label>
                    <input type="text" id="ver-first-name" placeholder="Иван" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Фамилия <span class="text-red-500">*</span></label>
                    <input type="text" id="ver-last-name" placeholder="Иванов" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Отчество <span class="text-red-500">*</span></label>
                    <input type="text" id="ver-middle-name" placeholder="Иванович" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Документы <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-500 mb-2">1-5 фото. JPG, PNG. Макс. 10 МБ.</p>
                    <label class="flex items-center justify-center gap-2 p-4 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-black transition">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                        <span class="text-sm text-gray-600">Добавить документы</span>
                        <input type="file" id="ver-docs-input" accept="image/*" multiple class="hidden" onchange="handleDocsSelect(this)">
                    </label>
                    <div id="docs-preview" class="flex flex-wrap gap-2 mt-3"></div>
                </div>
            </div>
            <p id="verification-message" class="text-sm mt-3"></p>
            <button onclick="submitVerification()" class="w-full bg-black text-white py-3 rounded-xl font-bold mt-4">Отправить заявку</button>
        </div>
        <button onclick="closeVerificationModal()" class="absolute top-4 right-4 text-gray-400 text-2xl">&times;</button>
    </div>
</div>

<!-- Модалка загрузки аватара -->
<?php if ($isLoggedIn): ?>
<div id="avatar-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl relative">
        <h2 class="text-xl font-bold mb-4">Загрузить аватар</h2>
        <p class="text-gray-500 text-sm mb-4">Максимум 10 МБ. JPG, PNG, WebP.</p>
        <input type="file" id="avatar-input" accept="image/*" class="w-full mb-4 p-3 border border-gray-300 rounded-xl">
        <div id="avatar-preview" class="hidden mb-4 flex justify-center">
            <img id="avatar-preview-img" class="w-32 h-32 rounded-full object-cover border-2 border-gray-200">
        </div>
        <p id="avatar-message" class="text-sm mb-3"></p>
        <div class="flex gap-2">
            <button onclick="closeAvatarModal()" class="flex-1 py-3 rounded-xl border border-gray-300 font-bold">Отмена</button>
            <button onclick="uploadAvatar()" class="flex-1 py-3 rounded-xl bg-black text-white font-bold">Загрузить</button>
        </div>
        <button onclick="closeAvatarModal()" class="absolute top-4 right-4 text-gray-400 text-2xl">&times;</button>
    </div>
</div>
<?php endif; ?>

<!-- Модалка редактирования профиля -->
<?php if ($isLoggedIn): ?>
<div id="edit-profile-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl relative modal-scroll">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">Редактировать профиль</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Имя</label>
                    <input type="text" id="edit-fullname" class="w-full p-3 border border-gray-300 rounded-xl outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">О себе</label>
                    <textarea id="edit-bio" rows="2" class="w-full p-3 border border-gray-300 rounded-xl outline-none resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ссылки на соцсети (до 5)</label>
                    <textarea id="edit-social-links" rows="2" placeholder="https://t.me/username&#10;https://instagram.com/..." class="w-full p-3 border border-gray-300 rounded-xl outline-none resize-none font-mono text-sm"></textarea>
                </div>
                <div class="border-t border-gray-200 my-3"></div>
                <div>
                    <h3 class="text-sm font-bold text-gray-700 mb-1"> Смена пароля</h3>
                    <p class="text-xs text-gray-500 mb-2">Оставьте пустым, если не хотите менять.</p>
                    <input type="password" id="edit-new-password" placeholder="Новый пароль (мин. 4 символа)" class="w-full mb-2 p-3 border border-gray-300 rounded-xl outline-none">
                    <input type="password" id="edit-confirm-password" placeholder="Повторите пароль" class="w-full p-3 border border-gray-300 rounded-xl outline-none">
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button onclick="closeEditProfile()" class="flex-1 py-3 rounded-xl border border-gray-300 font-bold">Отмена</button>
                <button onclick="saveProfile()" class="flex-1 py-3 rounded-xl bg-black text-white font-bold">Сохранить</button>
            </div>
        </div>
        <button onclick="closeEditProfile()" class="absolute top-4 right-4 text-gray-400 text-2xl">&times;</button>
    </div>
</div>
<?php endif; ?>

<!-- Модалка ввода кода активации -->
<div id="code-activation-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl relative">
        <h2 class="text-xl font-bold mb-2">Активация по коду</h2>
        <p class="text-gray-600 text-sm mb-4">Введи код, показанный при регистрации.</p>
        <label class="block text-sm font-medium mb-1">Имя пользователя</label>
        <input type="text" id="code-username" class="w-full mb-3 p-3 border border-gray-300 rounded-xl outline-none bg-gray-50" readonly>
        <label class="block text-sm font-medium mb-1">Код активации (6 цифр)</label>
        <input type="text" id="code-activation-code" placeholder="123456" maxlength="6" class="w-full mb-3 p-3 border border-gray-300 rounded-xl outline-none text-center text-2xl font-mono tracking-widest">
        <p id="code-activation-message" class="text-sm mb-3"></p>
        <button onclick="submitCodeActivation()" class="w-full bg-black text-white py-3 rounded-xl font-bold">Активировать</button>
        <button onclick="closeCodeActivation()" class="absolute top-4 right-4 text-gray-400 text-2xl">&times;</button>
    </div>
</div>

<!-- Модалка отправки ЛС -->
<?php if ($isLoggedIn): ?>
<div id="message-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl relative">
        <h2 class="text-xl font-bold mb-2">Написать сообщение</h2>
        <p class="text-gray-600 text-sm mb-4">Пользователь: <strong id="msg-recipient-name"></strong></p>
        <textarea id="msg-content" rows="4" placeholder="Введите сообщение..." class="w-full p-3 border border-gray-300 rounded-xl outline-none resize-none mb-3"></textarea>
        <p id="msg-message" class="text-sm mb-3"></p>
        <div class="flex gap-2">
            <button onclick="closeMessageModal()" class="flex-1 py-3 rounded-xl border border-gray-300 font-bold">Отмена</button>
            <button onclick="sendMessage()" class="flex-1 py-3 rounded-xl bg-black text-white font-bold">Отправить</button>
        </div>
        <button onclick="closeMessageModal()" class="absolute top-4 right-4 text-gray-400 text-2xl">&times;</button>
    </div>
</div>
<?php endif; ?>

<!-- Модалка поиска GIF -->
<?php if ($isLoggedIn): ?>
<div id="gif-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl relative modal-scroll">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Выберите GIF</h2>
                <button onclick="closeGifModal()" class="text-gray-400 text-2xl">&times;</button>
            </div>
            <div class="relative mb-4">
                <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="gif-search" placeholder="Поиск GIF..." class="w-full p-3 pl-10 border border-gray-300 rounded-xl outline-none focus:border-black" oninput="searchGifs(this.value)">
            </div>
            <div id="gif-results" class="grid grid-cols-3 gap-2 max-h-96 overflow-y-auto">
                <div class="col-span-3 text-center text-gray-400 py-8">Начните вводить запрос для поиска GIF</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Модалка эмодзи -->
<?php if ($isLoggedIn): ?>
<div id="emoji-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl relative modal-scroll">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Выберите смайлик</h2>
                <button onclick="closeEmojiModal()" class="text-gray-400 text-2xl">&times;</button>
            </div>
            <div class="relative mb-3">
                <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="emoji-search" placeholder="Поиск эмодзи..." class="w-full p-3 pl-10 border border-gray-300 rounded-xl outline-none focus:border-black" oninput="filterEmojis(this.value)">
            </div>
            <div class="flex gap-1 mb-3 overflow-x-auto pb-2 border-b border-gray-200" id="emoji-tabs">
                <div class="emoji-tab active" data-category="smileys" onclick="switchEmojiCategory('smileys')">😀</div>
                <div class="emoji-tab" data-category="gestures" onclick="switchEmojiCategory('gestures')">👋</div>
                <div class="emoji-tab" data-category="animals" onclick="switchEmojiCategory('animals')">🐶</div>
                <div class="emoji-tab" data-category="food" onclick="switchEmojiCategory('food')"></div>
                <div class="emoji-tab" data-category="objects" onclick="switchEmojiCategory('objects')">⚽</div>
                <div class="emoji-tab" data-category="symbols" onclick="switchEmojiCategory('symbols')">❤️</div>
            </div>
            <div id="emoji-grid" class="grid grid-cols-8 gap-1 max-h-80 overflow-y-auto"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Модалка опроса -->
<?php if ($isLoggedIn): ?>
<div id="poll-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl relative modal-scroll">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Создать опрос</h2>
                <button onclick="closePollModal()" class="text-gray-400 text-2xl">&times;</button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Вопрос <span class="text-red-500">*</span></label>
                    <input type="text" id="poll-question" placeholder="Задайте вопрос..." class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black" maxlength="100">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Варианты ответа <span class="text-red-500">*</span> (2-4)</label>
                    <div id="poll-options-container" class="space-y-2">
                        <input type="text" class="poll-option-input w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black" placeholder="Вариант 1" maxlength="50">
                        <input type="text" class="poll-option-input w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black" placeholder="Вариант 2" maxlength="50">
                    </div>
                    <button onclick="addPollOption()" id="add-option-btn" class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">+ Добавить вариант</button>
                </div>
            </div>
            <p id="poll-message" class="text-sm mt-3"></p>
            <button onclick="createPoll()" class="w-full bg-black text-white py-3 rounded-xl font-bold mt-4">Создать опрос</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Модалка редактора текста -->
<?php if ($isLoggedIn): ?>
<div id="editor-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl relative">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <button onclick="closeEditorModal()" class="text-gray-600 hover:text-black font-medium">Отмена</button>
                <h2 class="text-xl font-bold">Вложение с текстом</h2>
                <button onclick="saveEditorText()" class="text-gray-400 hover:text-black font-medium">Готово</button>
            </div>
            <div class="flex gap-1 mb-3 p-2 bg-gray-50 rounded-lg border border-gray-200">
                <button onclick="formatText('bold')" class="editor-format-btn" title="Жирный"><span class="font-bold text-lg">B</span></button>
                <button onclick="formatText('italic')" class="editor-format-btn" title="Курсив"><span class="italic text-lg font-serif">I</span></button>
                <button onclick="formatText('underline')" class="editor-format-btn" title="Подчёркнутый"><span class="underline text-lg">U</span></button>
                <button onclick="formatText('strikethrough')" class="editor-format-btn" title="Зачёркнутый"><span class="line-through text-lg">S</span></button>
                <div class="w-px bg-gray-300 mx-1"></div>
                <button onclick="formatText('heading')" class="editor-format-btn" title="Заголовок"><span class="font-bold text-lg">H</span></button>
            </div>
            <textarea id="editor-textarea" rows="8" placeholder="Расскажите ещё подробнее..." class="w-full p-4 border border-gray-300 rounded-xl outline-none focus:border-black resize-none text-base"></textarea>
            <p class="text-xs text-gray-400 mt-2">Выделите текст и нажмите кнопку форматирования. Markdown: **жирный**, *курсив*, __подчёрк__, ~~зачёрк~~</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Модалка геолокации -->
<?php if ($isLoggedIn): ?>
<div id="location-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl relative">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <button onclick="closeLocationModal()" class="text-gray-600 hover:text-black font-medium">Отмена</button>
                <h2 class="text-xl font-bold">Выберите место</h2>
                <button onclick="saveLocation()" class="text-blue-600 hover:text-blue-800 font-medium">Готово</button>
            </div>
            <div class="relative mb-3">
                <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="location-search" placeholder="Поиск места..." class="w-full p-3 pl-10 border border-gray-300 rounded-xl outline-none focus:border-black">
                <div id="location-search-results" class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto z-10"></div>
            </div>
            <div id="location-map"></div>
            <p class="text-xs text-gray-500 mt-2">Кликните по карте, чтобы поставить метку. Или воспользуйтесь поиском.</p>
            <p id="location-message" class="text-sm mt-2"></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Передача переменных в JavaScript и подключение app.js -->
<script>
    window.IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
    window.CURRENT_USER_ID = <?= $isLoggedIn ? (int)$_SESSION['user_id'] : 0 ?>;
    window.PAGE_NAME = '<?= $page ?>';
    window.USERNAME_PARAM = '<?= $username_param ?>';
    window.PROFILE_DATA = {
        full_name: <?= json_encode($_SESSION['full_name'] ?? '') ?>,
        bio: <?= json_encode($_SESSION['bio'] ?? '') ?>,
        social_links: <?= json_encode($socialLinksText) ?>
    };
    window.GIPHY_API_KEY = '<?= defined('GIPHY_API_KEY') ? GIPHY_API_KEY : '' ?>';
</script>
<script src="/app.js?v=<?= filemtime(__DIR__ . '/app.js') ?>"></script>
</body>
</html>