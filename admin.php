<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /');
    exit;
}

function getFolderSize($path) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}
function formatBytes($bytes) {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' ГБ';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' МБ';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' КБ';
    return $bytes . ' Б';
}

function sendActivationEmail($email, $username, $token) {
    $activateUrl = "https://vibe-app.su/activate.php?token=" . $token;
    $subject = "=?UTF-8?B?" . base64_encode("Активация аккаунта на Vibe") . "?=";
    $message = "Привет, $username!\n\nДля активации аккаунта перейди по ссылке:\n$activateUrl\n\nЕсли ссылка не работает, используй код активации на сайте: $token\n\nЕсли ты не регистрировался, просто проигнорируй это письмо.";
    $headers = "From: Vibe <no-reply@vibe-app.su>\r\n";
    $headers .= "Reply-To: no-reply@vibe-app.su\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($email, $subject, $message, $headers);
}

function sendWelcomeEmail($email, $username) {
    $subject = "=?UTF-8?B?" . base64_encode("Добро пожаловать в Vibe!") . "?=";
    $message = "Привет, $username!\n\nТвой аккаунт на Vibe успешно активирован.\n\nПерейти на сайт: https://vibe-app.su/\n\nС уважением, команда Vibe";
    $headers = "From: Vibe <no-reply@vibe-app.su>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($email, $subject, $message, $headers);
}

$action = $_GET['action'] ?? 'dashboard';
$searchQ = $_GET['q'] ?? '';

// === АКТИВАЦИЯ ПОЛЬЗОВАТЕЛЯ ===
if ($action === 'activate_user' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT id, username, email, is_activated, activation_token FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && !$user['is_activated']) {
        $db->prepare("UPDATE users SET is_activated = 1 WHERE id = ?")->execute([$userId]);
        if (!empty($user['activation_token'])) {
            @sendWelcomeEmail($user['email'], $user['username']);
        }
        header('Location: /admin.php?action=users&success=user_activated');
    } else {
        header('Location: /admin.php?action=users&error=already_activated');
    }
    exit;
}

// === ПОВТОРНАЯ ОТПРАВКА ПИСЬМА ===
if ($action === 'resend_activation' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT id, username, email, activation_token FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && !empty($user['activation_token'])) {
        sendActivationEmail($user['email'], $user['username'], $user['activation_token']);
        header('Location: /admin.php?action=users&success=email_resent');
    } else {
        header('Location: /admin.php?action=users&error=no_token');
    }
    exit;
}

// === БАН ПОЛЬЗОВАТЕЛЯ ===
if ($action === 'ban_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $days = (int)($_POST['ban_days'] ?? 0);
    $reason = trim($_POST['ban_reason'] ?? '');
    
    if ($userId > 0 && $userId != $_SESSION['user_id']) {
        if ($days == 0) {
            $banUntil = '9999-12-31 23:59:59';
        } else {
            $banUntil = date('Y-m-d H:i:s', strtotime("+$days days"));
        }
        
        $stmt = $db->prepare("UPDATE users SET banned_until = ?, ban_reason = ? WHERE id = ?");
        $stmt->execute([$banUntil, $reason, $userId]);
        
        header('Location: /admin.php?action=users&success=user_banned');
    } else {
        header('Location: /admin.php?action=users&error=cannot_ban_self');
    }
    exit;
}

// === РАЗБАН ПОЛЬЗОВАТЕЛЯ ===
if ($action === 'unban_user' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE users SET banned_until = NULL, ban_reason = '' WHERE id = ?");
    $stmt->execute([$userId]);
    header('Location: /admin.php?action=users&success=user_unbanned');
    exit;
}

// === СОХРАНЕНИЕ ПОЛЬЗОВАТЕЛЯ ===
if ($action === 'save_user' && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $fullName = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $verified = isset($_POST['verified']) ? 1 : 0;
    $avatar = trim($_POST['avatar'] ?? '');
    $db->prepare("UPDATE users SET full_name = ?, bio = ?, role = ?, verified = ?, avatar = ? WHERE id = ?")->execute([$fullName, $bio, $role, $verified, $avatar, $userId]);
    header('Location: /admin.php?action=users&success=user_updated'); exit;
}

// === УДАЛЕНИЕ ПОЛЬЗОВАТЕЛЯ ===
if ($action === 'delete_user' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    if ($userId != $_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        $db->prepare("DELETE FROM posts WHERE user_id = ?")->execute([$userId]);
        $db->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$userId]);
    }
    header('Location: /admin.php?action=users&success=user_deleted'); exit;
}

// === УДАЛЕНИЕ ПОСТА ===
if ($action === 'delete_post' && isset($_GET['id'])) {
    $postId = (int)$_GET['id'];
    $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);
    $db->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$postId]);
    header('Location: /admin.php?action=posts&success=post_deleted'); exit;
}

// === ОБРАБОТКА ЖАЛОБЫ ===
if ($action === 'resolve_report' && isset($_GET['id'])) {
    $reportId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT post_id FROM reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($report) {
        $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$report['post_id']]);
        $db->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$report['post_id']]);
        $db->prepare("DELETE FROM reports WHERE id = ?")->execute([$reportId]);
    }
    header('Location: /admin.php?action=reports&success=report_resolved'); exit;
}
if ($action === 'ignore_report' && isset($_GET['id'])) {
    $db->prepare("DELETE FROM reports WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: /admin.php?action=reports&success=report_ignored'); exit;
}

// === ВЕРИФИКАЦИЯ ===
if ($action === 'approve_verification' && isset($_GET['id'])) {
    $requestId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT user_id FROM verification_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$requestId]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($req) {
        $db->prepare("UPDATE users SET verified = 1 WHERE id = ?")->execute([$req['user_id']]);
        $db->prepare("UPDATE verification_requests SET status = 'approved', decided_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$requestId]);
    }
    header('Location: /admin.php?action=verification_requests&success=verification_approved'); exit;
}
if ($action === 'reject_verification' && isset($_GET['id'])) {
    $db->prepare("UPDATE verification_requests SET status = 'rejected', decided_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: /admin.php?action=verification_requests&success=verification_rejected'); exit;
}

// === СТАТИСТИКА ===
$stats = [
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'posts' => $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'comments' => $db->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'reports' => $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
    'verified' => $db->query("SELECT COUNT(*) FROM users WHERE verified = 1")->fetchColumn(),
    'pending_verifications' => $db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetchColumn(),
    'moderators' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'moderator'")->fetchColumn(),
    'not_activated' => $db->query("SELECT COUNT(*) FROM users WHERE is_activated = 0")->fetchColumn(),
    'banned' => $db->query("SELECT COUNT(*) FROM users WHERE banned_until IS NOT NULL AND (banned_until = '9999-12-31 23:59:59' OR banned_until > datetime('now'))")->fetchColumn(),
];

$today = date('Y-m-d');
$todayVisits = $db->prepare("SELECT visit_count FROM site_visits WHERE visit_date = ?");
$todayVisits->execute([$today]);
$stats['today_visits'] = $todayVisits->fetchColumn() ?: 0;

$monthStart = date('Y-m-01');
$monthVisits = $db->prepare("SELECT SUM(visit_count) FROM site_visits WHERE visit_date >= ?");
$monthVisits->execute([$monthStart]);
$stats['month_visits'] = $monthVisits->fetchColumn() ?: 0;

$totalVisits = $db->query("SELECT COALESCE(SUM(visit_count), 0) FROM site_visits")->fetchColumn();
$stats['total_visits'] = $totalVisits;

$chartData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $db->prepare("SELECT COALESCE(SUM(visit_count), 0) FROM site_visits WHERE visit_date LIKE ?");
    $stmt->execute([$month . '%']);
    $chartData[] = ['month' => date('M', strtotime($month . '-01')), 'visits' => $stmt->fetchColumn()];
}

$siteSize = getFolderSize(__DIR__);
$dbSize = filesize('database.sqlite');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка — Vibe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 8px; color: #374151; font-size: 14px; text-decoration: none; transition: all 0.15s; }
        .nav-link:hover { background: #f3f4f6; color: #000; }
        .nav-link.active { background: #000; color: #fff; }
        .nav-link svg { width: 20px; height: 20px; flex-shrink: 0; }
        .stat-card { border-radius: 16px; padding: 24px; color: #fff; position: relative; overflow: hidden; min-height: 140px; }
        .stat-card .stat-icon { position: absolute; right: -10px; bottom: -10px; opacity: 0.15; }
        .stat-card .stat-icon svg { width: 120px; height: 120px; }
        .stat-card .stat-value { font-size: 42px; font-weight: 800; line-height: 1; }
        .stat-card .stat-label { font-size: 14px; opacity: 0.9; margin-top: 8px; font-weight: 500; }
        .stat-card .stat-sub { font-size: 12px; opacity: 0.75; margin-top: 4px; }
        .bg-gradient-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-gradient-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .bg-gradient-orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .bg-gradient-red { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .bg-gradient-green { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .bg-gradient-dark { background: linear-gradient(135deg, #434343 0%, #000000 100%); }
        .bg-gradient-teal { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .table-row { border-bottom: 1px solid #f3f4f6; }
        .table-row:last-child { border-bottom: none; }
        .btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.15s; display: inline-block; border: none; cursor: pointer; }
        .btn-danger { background: #fee2e2; color: #dc2626; } .btn-danger:hover { background: #fecaca; }
        .btn-success { background: #dcfce7; color: #16a34a; } .btn-success:hover { background: #bbf7d0; }
        .btn-gray { background: #f3f4f6; color: #374151; } .btn-gray:hover { background: #e5e7eb; }
        .btn-primary { background: #000; color: #fff; } .btn-primary:hover { background: #333; }
        .btn-blue { background: #dbeafe; color: #1d4ed8; } .btn-blue:hover { background: #bfdbfe; }
        .btn-activate { background: #fef3c7; color: #92400e; } .btn-activate:hover { background: #fde68a; }
        .btn-resend { background: #e0f2fe; color: #0369a1; } .btn-resend:hover { background: #bae6fd; }
        .btn-ban { background: #fee2e2; color: #991b1b; } .btn-ban:hover { background: #fecaca; }
        .badge-mod { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; background: #1d9bf0; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 900; margin-right: 4px; vertical-align: middle; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
            <div class="p-6 border-b border-gray-200">
                <h1 class="text-2xl font-black">Vibe</h1>
                <p class="text-xs text-gray-400 mt-1">Панель администратора</p>
                <p class="text-xs text-gray-500 mt-2">Вы: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></strong></p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="?action=dashboard" class="nav-link <?= $action === 'dashboard' ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Дашборд
                </a>
                <a href="?action=users" class="nav-link <?= $action === 'users' || $action === 'edit_user' ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Пользователи
                </a>
                <a href="?action=moderators" class="nav-link <?= $action === 'moderators' ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Модераторы
                </a>
                <a href="?action=verification_requests" class="nav-link <?= $action === 'verification_requests' ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Заявки на верификацию
                    <?php if ($stats['pending_verifications'] > 0): ?><span class="ml-auto bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $stats['pending_verifications'] ?></span><?php endif; ?>
                </a>
                <a href="?action=verified" class="nav-link <?= $action === 'verified' ? 'active' : '' ?>">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    Верифицированные
                    <?php if ($stats['verified'] > 0): ?><span class="ml-auto bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $stats['verified'] ?></span><?php endif; ?>
                </a>
                <a href="?action=posts" class="nav-link <?= $action === 'posts' ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Посты
                </a>
                <a href="?action=reports" class="nav-link <?= $action === 'reports' ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Жалобы
                    <?php if ($stats['reports'] > 0): ?><span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $stats['reports'] ?></span><?php endif; ?>
                </a>
                <a href="/pages-admin.php" class="nav-link" target="_blank">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Страницы сайта
                </a>
                <a href="?action=settings" class="nav-link <?= $action === 'settings' ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Настройки
                </a>
            </nav>
            <div class="p-4 border-t border-gray-200 space-y-1">
                <a href="/" class="nav-link">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    На сайт
                </a>
                <a href="/api.php?action=logout" class="nav-link text-red-600 hover:bg-red-50">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Выйти
                </a>
            </div>
        </aside>

        <main class="flex-1 p-8">
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6 text-sm">
                    <?php
                    $s = $_GET['success'];
                    if ($s === 'user_deleted') echo 'Пользователь удалён';
                    elseif ($s === 'user_updated') echo 'Профиль обновлён';
                    elseif ($s === 'user_activated') echo '✅ Пользователь активирован. Письмо отправлено.';
                    elseif ($s === 'email_resent') echo '📧 Письмо с кодом активации отправлено повторно';
                    elseif ($s === 'user_banned') echo '🔒 Пользователь заблокирован';
                    elseif ($s === 'user_unbanned') echo ' Пользователь разблокирован';
                    elseif ($s === 'post_deleted') echo 'Пост удалён';
                    elseif ($s === 'report_resolved') echo 'Жалоба обработана';
                    elseif ($s === 'report_ignored') echo 'Жалоба проигнорирована';
                    elseif ($s === 'verification_approved') echo 'Заявка одобрена';
                    elseif ($s === 'verification_rejected') echo 'Заявка отклонена';
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6 text-sm">
                    <?php
                    $e = $_GET['error'];
                    if ($e === 'already_activated') echo 'Этот пользователь уже активирован';
                    elseif ($e === 'no_token') echo 'У пользователя нет токена активации';
                    elseif ($e === 'cannot_ban_self') echo 'Нельзя забанить самого себя';
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($action === 'dashboard'): ?>
                <h2 class="text-3xl font-bold mb-6">Дашборд</h2>
                
                <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-bold mb-4">Среднемесячная статистика посещений</h3>
                    <div style="height: 300px;">
                        <canvas id="visitsChart"></canvas>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-6">
                    <div class="stat-card bg-gradient-purple">
                        <div class="stat-value"><?= $stats['users'] ?></div>
                        <div class="stat-label">Пользователи</div>
                        <div class="stat-sub">Управление пользователями</div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
                    </div>
                    <div class="stat-card bg-gradient-blue">
                        <div class="stat-value"><?= $stats['posts'] ?></div>
                        <div class="stat-label">Посты</div>
                        <div class="stat-sub">Всего публикаций</div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                    </div>
                    <div class="stat-card bg-gradient-orange">
                        <div class="stat-value"><?= $stats['comments'] ?></div>
                        <div class="stat-label">Комментарии</div>
                        <div class="stat-sub">Всего комментариев</div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></div>
                    </div>
                    <div class="stat-card bg-gradient-green">
                        <div class="stat-value"><?= $stats['today_visits'] ?></div>
                        <div class="stat-label">Визиты сегодня</div>
                        <div class="stat-sub">За <?= date('d.m.Y') ?></div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    </div>
                    <div class="stat-card bg-gradient-teal">
                        <div class="stat-value"><?= $stats['month_visits'] ?></div>
                        <div class="stat-label">Визиты в этом месяце</div>
                        <div class="stat-sub">С <?= date('01.m.Y') ?></div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
                    </div>
                    <div class="stat-card bg-gradient-dark">
                        <div class="stat-value"><?= $stats['total_visits'] ?></div>
                        <div class="stat-label">Всего визитов</div>
                        <div class="stat-sub">За всё время</div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    </div>
                    <div class="stat-card bg-gradient-red">
                        <div class="stat-value"><?= $stats['reports'] ?></div>
                        <div class="stat-label">Ожидают жалобы</div>
                        <div class="stat-sub">Требуют внимания</div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
                        <div class="stat-value"><?= $stats['not_activated'] ?></div>
                        <div class="stat-label">Не активировано</div>
                        <div class="stat-sub">Ожидают подтверждения</div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #ef4444 0%, #991b1b 100%);">
                        <div class="stat-value"><?= $stats['banned'] ?></div>
                        <div class="stat-label">Забанено</div>
                        <div class="stat-sub">Заблокированных аккаунтов</div>
                        <div class="stat-icon"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 text-sm">Верифицированные</span>
                            <svg class="w-8 h-8 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="text-3xl font-bold text-blue-500"><?= $stats['verified'] ?></div>
                        <div class="text-xs text-gray-400 mt-1">Пользователей с галочкой</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 text-sm">Модераторы</span>
                            <svg class="w-8 h-8 text-purple-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="text-3xl font-bold text-purple-500"><?= $stats['moderators'] ?></div>
                        <div class="text-xs text-gray-400 mt-1">Активных модераторов</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 text-sm">Заявки на верификацию</span>
                            <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="text-3xl font-bold text-yellow-500"><?= $stats['pending_verifications'] ?></div>
                        <div class="text-xs text-gray-400 mt-1">Ожидают рассмотрения</div>
                    </div>
                </div>

                <script>
                const ctx = document.getElementById('visitsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($chartData, 'month')) ?>,
                        datasets: [{
                            label: 'Посещения',
                            data: <?= json_encode(array_column($chartData, 'visits')) ?>,
                            backgroundColor: 'rgba(99, 102, 241, 0.6)',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            borderWidth: 2,
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
                </script>

            <?php elseif ($action === 'users'): ?>
                <h2 class="text-3xl font-bold mb-6">Пользователи</h2>
                
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4 text-sm text-blue-800">
                    <strong>💡 Как активировать пользователей:</strong> Если письмо не доходит, нажми <strong>"Активировать"</strong> — аккаунт станет активным мгновенно.
                </div>
                
                <div class="mb-4">
                    <form method="GET" class="flex gap-2">
                        <input type="hidden" name="action" value="users">
                        <input type="text" name="q" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Поиск по имени, email или username..." class="flex-1 p-3 border border-gray-300 rounded-xl outline-none focus:border-black">
                        <button type="submit" class="bg-black text-white px-6 py-3 rounded-xl font-bold">Найти</button>
                        <?php if ($searchQ): ?><a href="?action=users" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-bold">Сбросить</a><?php endif; ?>
                    </form>
                </div>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">ID</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Username</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Email</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Имя</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Статус</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Бан</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Роль</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM users WHERE username LIKE :q OR email LIKE :q OR full_name LIKE :q ORDER BY id DESC";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':q' => '%' . $searchQ . '%']);
                            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user):
                                $isBanned = false;
                                $banText = '';
                                if (!empty($user['banned_until'])) {
                                    if ($user['banned_until'] === '9999-12-31 23:59:59') {
                                        $isBanned = true;
                                        $banText = 'Навсегда';
                                    } else {
                                        $banUntil = new DateTime($user['banned_until']);
                                        $now = new DateTime();
                                        if ($banUntil > $now) {
                                            $isBanned = true;
                                            $banText = 'До ' . $banUntil->format('d.m.Y');
                                        }
                                    }
                                }
                            ?>
                                <tr class="table-row <?= $isBanned ? 'bg-red-50' : '' ?>">
                                    <td class="p-4 text-sm text-gray-500"><?= $user['id'] ?></td>
                                    <td class="p-4 font-bold text-sm"><?= htmlspecialchars($user['username']) ?></td>
                                    <td class="p-4 text-sm"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="p-4 text-sm"><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td class="p-4 text-sm">
                                        <?php if ($user['is_activated']): ?>
                                            <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-bold">✓ Активен</span>
                                        <?php else: ?>
                                            <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded font-bold">⏳ Ожидает</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-sm">
                                        <?php if ($isBanned): ?>
                                            <span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded font-bold">🔒 <?= htmlspecialchars($banText) ?></span>
                                            <?php if (!empty($user['ban_reason'])): ?>
                                                <div class="text-xs text-gray-500 mt-1" title="<?= htmlspecialchars($user['ban_reason']) ?>">Причина: <?= htmlspecialchars(mb_substr($user['ban_reason'], 0, 30)) ?>...</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-sm">
                                        <?php if ($user['role'] === 'admin'): ?><span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded font-bold">АДМИН</span>
                                        <?php elseif ($user['role'] === 'moderator'): ?><span class="badge-mod">М</span>
                                        <?php else: ?><span class="text-gray-500 text-xs">user</span><?php endif; ?>
                                        <?php if ($user['verified'] == 1): ?><svg class="w-4 h-4 text-blue-500 inline-block ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg><?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <?php if (!$user['is_activated']): ?>
                                            <a href="?action=activate_user&id=<?= $user['id'] ?>" class="btn btn-activate mr-1" onclick="return confirm('Активировать аккаунт <?= htmlspecialchars($user['username']) ?>?')">Активировать</a>
                                            <a href="?action=resend_activation&id=<?= $user['id'] ?>" class="btn btn-resend mr-1" onclick="return confirm('Отправить письмо повторно?')">📧 Письмо</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <?php if ($isBanned): ?>
                                                <a href="?action=unban_user&id=<?= $user['id'] ?>" class="btn btn-success mr-1" onclick="return confirm('Разблокировать <?= htmlspecialchars($user['username']) ?>?')">🔓 Разбан</a>
                                            <?php else: ?>
                                                <button onclick="openBanModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')" class="btn btn-ban mr-1">🔒 Бан</button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <a href="?action=edit_user&id=<?= $user['id'] ?>" class="btn btn-primary mr-1">Редактировать</a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="?action=delete_user&id=<?= $user['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить пользователя?')">Удалить</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($action === 'edit_user' && isset($_GET['id'])): ?>
                <?php
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([(int)$_GET['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user): ?>
                    <div class="bg-white p-8 rounded-xl shadow-sm text-center">Пользователь не найден</div>
                <?php else: ?>
                    <div class="flex items-center gap-4 mb-6">
                        <a href="?action=users" class="text-2xl">←</a>
                        <h2 class="text-3xl font-bold">Редактирование: <?= htmlspecialchars($user['username']) ?></h2>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <form method="POST" action="?action=save_user">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Имя (отображаемое)</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Аватар (URL или base64)</label>
                                <input type="text" name="avatar" value="<?= htmlspecialchars($user['avatar']) ?>" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black text-sm">
                                <?php if ($user['avatar']): ?><img src="<?= htmlspecialchars($user['avatar']) ?>" class="w-20 h-20 rounded-full mt-2 object-cover"><?php endif; ?>
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Биография</label>
                                <textarea name="bio" rows="3" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black"><?= htmlspecialchars($user['bio']) ?></textarea>
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Роль</label>
                                <select name="role" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                                    <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Модератор</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                </select>
                            </div>
                            <div class="mb-6">
                                <label class="flex items-center gap-3 cursor-pointer p-3 bg-gray-50 rounded-xl">
                                    <input type="checkbox" name="verified" value="1" <?= $user['verified'] == 1 ? 'checked' : '' ?> class="w-5 h-5">
                                    <div>
                                        <div class="font-medium">Верифицированный аккаунт</div>
                                        <div class="text-xs text-gray-500">Пользователь получит синюю галочку ✓</div>
                                    </div>
                                </label>
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                                <a href="?action=users" class="btn btn-gray">Отмена</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'moderators'): ?>
                <h2 class="text-3xl font-bold mb-6">Список модераторов</h2>
                <?php
                $stmt = $db->query("SELECT u.id, u.username, u.full_name, 
                    (SELECT COUNT(*) FROM moderator_logs WHERE moderator_id = u.id AND created_at >= datetime('now', '-30 days')) as handled_30d,
                    (SELECT COUNT(*) FROM moderator_logs WHERE moderator_id = u.id) as handled_total 
                    FROM users u WHERE u.role = 'moderator' ORDER BY handled_total DESC");
                $mods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($mods) === 0):
                ?>
                    <div class="bg-white p-12 rounded-xl shadow-sm text-center text-gray-400">
                        <div class="font-medium">Пока нет назначенных модераторов</div>
                        <div class="text-sm mt-2">Вы можете назначить модератора, отредактировав роль пользователя во вкладке "Пользователи"</div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">ID</th>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Username</th>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Имя</th>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">За 30 дней</th>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">За всё время</th>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mods as $mod): ?>
                                    <tr class="table-row">
                                        <td class="p-4 text-sm text-gray-500"><?= $mod['id'] ?></td>
                                        <td class="p-4 font-bold text-sm"><?= htmlspecialchars($mod['username']) ?></td>
                                        <td class="p-4 text-sm"><?= htmlspecialchars($mod['full_name']) ?></td>
                                        <td class="p-4 text-sm font-bold text-blue-600"><?= $mod['handled_30d'] ?></td>
                                        <td class="p-4 text-sm font-bold text-green-600"><?= $mod['handled_total'] ?></td>
                                        <td class="p-4"><a href="?action=edit_user&id=<?= $mod['id'] ?>" class="btn btn-primary">Редактировать</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'verification_requests'): ?>
                <h2 class="text-3xl font-bold mb-6">Заявки на верификацию</h2>
                <?php
                $stmt = $db->query("SELECT vr.*, u.username, u.full_name, u.bio, u.avatar, u.verified FROM verification_requests vr JOIN users u ON vr.user_id = u.id ORDER BY vr.created_at DESC");
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $pending = array_filter($requests, fn($r) => $r['status'] === 'pending');
                if (count($pending) === 0): ?>
                    <div class="bg-white p-12 rounded-xl shadow-sm text-center text-gray-400">
                        <div class="font-medium">Заявок на верификацию пока нет</div>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pending as $req): 
                            $docs = json_decode($req['documents'] ?? '[]', true);
                            if (!is_array($docs)) $docs = [];
                        ?>
                            <div class="bg-white p-6 rounded-xl shadow-sm border-2 border-blue-100">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-4">
                                        <?php if ($req['avatar']): ?><img src="<?= htmlspecialchars($req['avatar']) ?>" class="w-14 h-14 rounded-full object-cover">
                                        <?php else: ?><div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-xl"><?= strtoupper(substr($req['username'], 0, 1)) ?></div><?php endif; ?>
                                        <div>
                                            <div class="font-bold text-lg"><?= htmlspecialchars($req['username']) ?></div>
                                            <div class="text-gray-500 text-sm"><?= htmlspecialchars($req['full_name']) ?></div>
                                            <div class="text-xs text-gray-400 mt-1">Заявка: <?= $req['created_at'] ?></div>
                                        </div>
                                    </div>
                                    <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">На рассмотрении</span>
                                </div>
                                
                                <?php if (!empty($req['real_name'])): ?>
                                    <div class="bg-blue-50 p-3 rounded-lg mb-3">
                                        <div class="text-xs text-blue-600 mb-1 font-bold"> Настоящее ФИО:</div>
                                        <div class="text-gray-800 font-medium"><?= htmlspecialchars($req['real_name']) ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($req['bio']): ?>
                                    <div class="bg-gray-50 p-3 rounded-lg mb-3"><div class="text-xs text-gray-400 mb-1">Биография:</div><div class="text-gray-800 text-sm"><?= htmlspecialchars($req['bio']) ?></div></div>
                                <?php endif; ?>
                                
                                <?php if (count($docs) > 0): ?>
                                    <div class="bg-gray-50 p-3 rounded-lg mb-3">
                                        <div class="text-xs text-gray-400 mb-2 font-bold">📎 Документы (<?= count($docs) ?>):</div>
                                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
                                            <?php foreach ($docs as $doc): 
                                                $docPath = '/verifications/' . htmlspecialchars($req['username']) . '/' . htmlspecialchars($doc);
                                                $isImage = preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $doc);
                                            ?>
                                                <div class="relative group">
                                                    <?php if ($isImage): ?>
                                                        <a href="<?= $docPath ?>" target="_blank">
                                                            <img src="<?= $docPath ?>" class="w-full h-24 object-cover rounded-lg border border-gray-200 hover:border-blue-500 transition cursor-pointer">
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= $docPath ?>" target="_blank" class="flex items-center justify-center h-24 bg-white rounded-lg border border-gray-200 hover:border-blue-500 transition text-xs text-gray-600 p-2 text-center break-all">
                                                            📄 <?= htmlspecialchars($doc) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex gap-3 flex-wrap">
                                    <a href="/?page=profile&user=<?= urlencode($req['username']) ?>" target="_blank" class="btn btn-blue">Открыть профиль →</a>
                                    <a href="?action=approve_verification&id=<?= $req['id'] ?>" class="btn btn-success" onclick="return confirm('Одобрить верификацию для <?= htmlspecialchars($req['username']) ?>?')">✓ Одобрить</a>
                                    <a href="?action=reject_verification&id=<?= $req['id'] ?>" class="btn btn-danger" onclick="return confirm('Отклонить заявку <?= htmlspecialchars($req['username']) ?>?')">✗ Отклонить</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'verified'): ?>
                <h2 class="text-3xl font-bold mb-6">Верифицированные пользователи</h2>
                <?php
                $stmt = $db->query("SELECT * FROM users WHERE verified = 1 ORDER BY id DESC");
                $vUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($vUsers) === 0): ?>
                    <div class="bg-white p-12 rounded-xl shadow-sm text-center text-gray-400"><div class="font-medium">Пока нет верифицированных пользователей</div></div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Username</th>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Настоящее ФИО</th>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Email</th>
                                    <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vUsers as $u): ?>
                                    <tr class="table-row">
                                        <td class="p-4 font-bold text-sm"><?= htmlspecialchars($u['username']) ?></td>
                                        <td class="p-4 text-sm"><?= htmlspecialchars($u['full_name']) ?></td>
                                        <td class="p-4 text-sm text-gray-500"><?= htmlspecialchars($u['email']) ?></td>
                                        <td class="p-4"><a href="?action=edit_user&id=<?= $u['id'] ?>" class="btn btn-primary">Редактировать</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'posts'): ?>
                <h2 class="text-3xl font-bold mb-6">Посты</h2>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">ID</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Автор</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Контент</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Дата</th>
                                <th class="text-left p-4 text-xs font-semibold text-gray-500 uppercase">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $db->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC LIMIT 50");
                            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $post):
                            ?>
                                <tr class="table-row">
                                    <td class="p-4 text-sm text-gray-500"><?= $post['id'] ?></td>
                                    <td class="p-4 font-bold text-sm"><?= htmlspecialchars($post['username']) ?></td>
                                    <td class="p-4 text-sm max-w-xs truncate"><?= htmlspecialchars(substr($post['content'], 0, 60)) ?></td>
                                    <td class="p-4 text-xs text-gray-500"><?= $post['created_at'] ?></td>
                                    <td class="p-4">
                                        <a href="/?post=<?= $post['id'] ?>" target="_blank" class="btn btn-blue mr-2" title="Открыть пост"> Открыть</a>
                                        <a href="?action=delete_post&id=<?= $post['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить пост?')">Удалить</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($action === 'reports'): ?>
                <h2 class="text-3xl font-bold mb-6">Жалобы</h2>
                <?php if ($stats['reports'] === 0): ?>
                    <div class="bg-white p-12 rounded-xl shadow-sm text-center text-gray-400"><div class="font-medium">Жалоб нет</div></div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php
                        $stmt = $db->query("SELECT r.*, p.content, p.id as post_id, u.username FROM reports r JOIN posts p ON r.post_id = p.id JOIN users u ON r.user_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at DESC");
                        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $report):
                            $reasons = ['spam' => 'Спам', 'abuse' => 'Оскорбления', 'misinformation' => 'Дезинформация', 'violence' => 'Насилие', 'other' => 'Другое'];
                        ?>
                            <div class="bg-white p-6 rounded-xl shadow-sm">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <div class="text-xs text-gray-400 mb-1">Жалоба #<?= $report['id'] ?> · <?= $report['created_at'] ?></div>
                                        <div class="font-bold text-lg"><?= $reasons[$report['reason']] ?? $report['reason'] ?></div>
                                        <div class="text-sm text-gray-500 mt-1">Пожаловался: <strong><?= htmlspecialchars($report['username']) ?></strong></div>
                                    </div>
                                    <a href="/?page=profile&user=<?= urlencode($report['username']) ?>" target="_blank" class="btn btn-gray text-xs">Открыть профиль →</a>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg mb-4"><div class="text-xs text-gray-400 mb-1">Текст поста #<?= $report['post_id'] ?>:</div><div class="text-gray-800 text-sm"><?= htmlspecialchars($report['content']) ?></div></div>
                                <div class="flex gap-3">
                                    <a href="?action=resolve_report&id=<?= $report['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить этот пост?')">Удалить пост</a>
                                    <a href="?action=ignore_report&id=<?= $report['id'] ?>" class="btn btn-gray">Игнорировать</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'settings'): ?>
                <h2 class="text-3xl font-bold mb-6">Настройки</h2>
                <div class="bg-white p-6 rounded-xl shadow-sm mb-6">
                    <h3 class="text-lg font-bold mb-4">Информация о системе</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Версия PHP</span><span class="font-medium"><?= phpversion() ?></span></div>
                        <div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Размер БД</span><span class="font-medium"><?= formatBytes($dbSize) ?></span></div>
                        <div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Размер сайта</span><span class="font-medium"><?= formatBytes($siteSize) ?></span></div>
                    </div>
                </div>
                
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                    <strong>⚠️ Проблема с отправкой писем?</strong><br>
                    На shared-хостинге функция mail() часто работает нестабильно. Если письма не доходят:
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li>Активируй пользователей вручную кнопкой "Активировать"</li>
                        <li>Пользователь может активировать аккаунт сам через код на сайте</li>
                        <li>Настрой SPF/DKIM записи в DNS для домена vibe-app.su</li>
                    </ul>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Модалка бана -->
    <div id="ban-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl relative">
            <h2 class="text-xl font-bold mb-2">🔒 Заблокировать пользователя</h2>
            <p class="text-gray-600 text-sm mb-4">Пользователь: <strong id="ban-username"></strong></p>
            
            <form method="POST" action="?action=ban_user">
                <input type="hidden" name="user_id" id="ban-user-id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Срок блокировки (в днях)</label>
                    <input type="number" name="ban_days" id="ban-days" min="0" value="1" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black">
                    <p class="text-xs text-gray-500 mt-1">0 = блокировка навсегда, 1 = 1 день, 7 = неделя, 30 = месяц</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Причина блокировки</label>
                    <textarea name="ban_reason" id="ban-reason" rows="3" class="w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black resize-none" placeholder="Например: Спам, оскорбления, нарушение правил..."></textarea>
                </div>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-red-800"><strong>⚠️ Внимание:</strong> Забаненный пользователь не сможет войти в аккаунт до окончания срока блокировки.</p>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" onclick="closeBanModal()" class="flex-1 py-3 rounded-xl border border-gray-300 font-bold">Отмена</button>
                    <button type="submit" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold hover:bg-red-700 transition">🔒 Заблокировать</button>
                </div>
            </form>
            
            <button onclick="closeBanModal()" class="absolute top-4 right-4 text-gray-400 text-2xl">&times;</button>
        </div>
    </div>

    <script>
    function openBanModal(userId, username) {
        document.getElementById('ban-user-id').value = userId;
        document.getElementById('ban-username').textContent = '@' + username;
        document.getElementById('ban-days').value = 1;
        document.getElementById('ban-reason').value = '';
        document.getElementById('ban-modal').classList.remove('hidden');
    }

    function closeBanModal() {
        document.getElementById('ban-modal').classList.add('hidden');
    }
    </script>
</body>
</html>