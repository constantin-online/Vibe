<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/html; charset=utf-8');

$dbFile = __DIR__ . '/database.sqlite';

if (!file_exists($dbFile)) {
    touch($dbFile);
    chmod($dbFile, 0666);
}

$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// === ТАБЛИЦА ПОЛЬЗОВАТЕЛЕЙ ===
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    full_name TEXT DEFAULT '',
    avatar TEXT DEFAULT '',
    bio TEXT DEFAULT '',
    social_links TEXT DEFAULT '[]',
    role TEXT DEFAULT 'user',
    verified INTEGER DEFAULT 0,
    is_activated INTEGER DEFAULT 0,
    activation_token TEXT DEFAULT '',
    banned_until DATETIME DEFAULT NULL,
    ban_reason TEXT DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// === ТАБЛИЦА ПОСТОВ ===
$db->exec("CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    image TEXT DEFAULT '',
    images TEXT DEFAULT '',
    gif TEXT DEFAULT '',
    video TEXT DEFAULT '',
    location_lat REAL DEFAULT NULL,
    location_lng REAL DEFAULT NULL,
    location_name TEXT DEFAULT '',
    likes INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

// === ТАБЛИЦА КОММЕНТАРИЕВ ===
$db->exec("CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(post_id) REFERENCES posts(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

// === ТАБЛИЦА ПОДПИСОК ===
$db->exec("CREATE TABLE IF NOT EXISTS follows (
    follower_id INTEGER NOT NULL,
    following_id INTEGER NOT NULL,
    PRIMARY KEY(follower_id, following_id),
    FOREIGN KEY(follower_id) REFERENCES users(id),
    FOREIGN KEY(following_id) REFERENCES users(id)
)");

// === ТАБЛИЦА ЛАЙКОВ ===
$db->exec("CREATE TABLE IF NOT EXISTS post_likes (
    user_id INTEGER NOT NULL,
    post_id INTEGER NOT NULL,
    PRIMARY KEY(user_id, post_id),
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(post_id) REFERENCES posts(id)
)");

// === ТАБЛИЦА СООБЩЕНИЙ ===
$db->exec("CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    recipient_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(sender_id) REFERENCES users(id),
    FOREIGN KEY(recipient_id) REFERENCES users(id)
)");

// === ТАБЛИЦА ЖАЛОБ ===
$db->exec("CREATE TABLE IF NOT EXISTS reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    reason TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(post_id) REFERENCES posts(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

// === ЖУРНАЛ ДЕЙСТВИЙ МОДЕРАТОРОВ ===
$db->exec("CREATE TABLE IF NOT EXISTS moderator_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    moderator_id INTEGER NOT NULL,
    report_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(moderator_id) REFERENCES users(id),
    FOREIGN KEY(report_id) REFERENCES reports(id)
)");

// === ЗАЯВКИ НА ВЕРИФИКАЦИЮ ===
$db->exec("CREATE TABLE IF NOT EXISTS verification_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    real_name TEXT DEFAULT '',
    documents TEXT DEFAULT '[]',
    status TEXT DEFAULT 'pending',
    reason TEXT DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    decided_at DATETIME DEFAULT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

// === СТАТИСТИКА ПОСЕЩЕНИЙ ===
$db->exec("CREATE TABLE IF NOT EXISTS site_visits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    visit_date DATE NOT NULL,
    visit_count INTEGER DEFAULT 0,
    UNIQUE(visit_date)
)");

// === ОПРОСЫ ===
$db->exec("CREATE TABLE IF NOT EXISTS polls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    question TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE
)");

$db->exec("CREATE TABLE IF NOT EXISTS poll_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    poll_id INTEGER NOT NULL,
    option_text TEXT NOT NULL,
    vote_count INTEGER DEFAULT 0,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE
)");

$db->exec("CREATE TABLE IF NOT EXISTS poll_votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    poll_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    option_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(poll_id, user_id),
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(option_id) REFERENCES poll_options(id)
)");

// === CMS СТРАНИЦЫ ===
$db->exec("CREATE TABLE IF NOT EXISTS pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    content TEXT DEFAULT '',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Страницы по умолчанию
$defaultPages = [
    ['blog', 'Блог Vibe', 'Здесь будут новости платформы.'],
    ['about', 'О Vibe', 'Vibe — социальная сеть для обмена мыслями, фото и видео.'],
    ['legal', 'Правовая информация', 'Пользовательское соглашение и политика конфиденциальности.'],
    ['content-info', 'Информация о контенте', 'Правила публикации и модерации контента.']
];
foreach ($defaultPages as $p) {
    try {
        $stmt = $db->prepare("INSERT INTO pages (slug, title, content) VALUES (?, ?, ?)");
        $stmt->execute($p);
    } catch (Exception $e) {}
}

// === ДОБАВЛЕНИЕ НОВЫХ ПОЛЕЙ В СУЩЕСТВУЮЩИЕ ТАБЛИЦЫ ===
try { $db->exec("ALTER TABLE posts ADD COLUMN gif TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE posts ADD COLUMN images TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE posts ADD COLUMN video TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE posts ADD COLUMN location_lat REAL DEFAULT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE posts ADD COLUMN location_lng REAL DEFAULT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE posts ADD COLUMN location_name TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN verified INTEGER DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN social_links TEXT DEFAULT '[]'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN is_activated INTEGER DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN activation_token TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN email TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN full_name TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN avatar TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE verification_requests ADD COLUMN real_name TEXT DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE verification_requests ADD COLUMN documents TEXT DEFAULT '[]'"); } catch (Exception $e) {}

// === ПОЛЯ ДЛЯ БАНА ===
try { $db->exec("ALTER TABLE users ADD COLUMN banned_until DATETIME DEFAULT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE users ADD COLUMN ban_reason TEXT DEFAULT ''"); } catch (Exception $e) {}
?>