<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /admin.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

// === СОХРАНЕНИЕ СТРАНИЦЫ ===
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($_POST['slug'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    
    if (empty($slug) || empty($title)) {
        $message = 'Заполните slug и заголовок';
        $messageType = 'error';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO pages (slug, title, content, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                                  ON CONFLICT(slug) DO UPDATE SET title = excluded.title, content = excluded.content, updated_at = CURRENT_TIMESTAMP");
            $stmt->execute([$slug, $title, $content]);
            $message = 'Страница сохранена!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Ошибка: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    $action = 'list';
}

// === УДАЛЕНИЕ СТРАНИЦЫ ===
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("DELETE FROM pages WHERE id = ?")->execute([$id]);
    $message = 'Страница удалена';
    $messageType = 'success';
    $action = 'list';
}

// === РЕДАКТИРОВАНИЕ ===
$editPage = null;
if ($action === 'edit' && isset($_GET['slug'])) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->execute([$_GET['slug']]);
    $editPage = $stmt->fetch(PDO::FETCH_ASSOC);
}

// === СПИСОК СТРАНИЦ ===
$pages = $db->query("SELECT * FROM pages ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление страницами — Vibe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold"> Управление страницами</h1>
                <p class="text-gray-600 mt-1">Редактируйте контент страниц сайта</p>
            </div>
            <div class="flex gap-2">
                <a href="/admin.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">← В админку</a>
                <?php if ($action !== 'edit' && $action !== 'new'): ?>
                <a href="?action=new" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition">+ Новая страница</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- СПИСОК СТРАНИЦ -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left p-4 text-sm font-semibold text-gray-600">Slug</th>
                            <th class="text-left p-4 text-sm font-semibold text-gray-600">Заголовок</th>
                            <th class="text-left p-4 text-sm font-semibold text-gray-600">Обновлено</th>
                            <th class="text-left p-4 text-sm font-semibold text-gray-600">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($pages as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 font-mono text-sm text-gray-600"><?= htmlspecialchars($p['slug']) ?></td>
                            <td class="p-4 font-medium"><?= htmlspecialchars($p['title']) ?></td>
                            <td class="p-4 text-sm text-gray-500"><?= $p['updated_at'] ?></td>
                            <td class="p-4">
                                <a href="?action=edit&slug=<?= urlencode($p['slug']) ?>" class="text-blue-600 hover:underline text-sm mr-3">✏️ Редактировать</a>
                                <a href="/?page=<?= urlencode($p['slug']) ?>" target="_blank" class="text-green-600 hover:underline text-sm mr-3">👁️ Просмотр</a>
                                <a href="?action=delete&id=<?= $p['id'] ?>" class="text-red-600 hover:underline text-sm" onclick="return confirm('Удалить страницу?')">🗑️ Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($pages)): ?>
                <div class="p-8 text-center text-gray-500">Страниц пока нет. Создайте первую!</div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'edit' || $action === 'new'): ?>
            <!-- РЕДАКТОР СТРАНИЦЫ -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-bold mb-4"><?= $editPage ? 'Редактировать: ' . htmlspecialchars($editPage['title']) : 'Новая страница' ?></h2>
                
                <form method="POST" action="?action=save">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug (URL-идентификатор) *</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($editPage['slug'] ?? '') ?>" 
                               class="w-full p-3 border border-gray-300 rounded-lg outline-none focus:border-black font-mono"
                               placeholder="например: blog, about, legal"
                               <?= $editPage ? 'readonly' : 'required' ?>>
                        <p class="text-xs text-gray-500 mt-1">Используется в URL: /?page=<strong>slug</strong>. Нельзя менять после создания.</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок страницы *</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($editPage['title'] ?? '') ?>" 
                               class="w-full p-3 border border-gray-300 rounded-lg outline-none focus:border-black"
                               required placeholder="Например: Блог Vibe">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Содержимое (HTML поддерживается)</label>
                        <textarea name="content" rows="20" 
                                  class="w-full p-3 border border-gray-300 rounded-lg outline-none focus:border-black font-mono text-sm"
                                  placeholder="<h2>Заголовок</h2><p>Текст...</p>"><?= htmlspecialchars($editPage['content'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Можно использовать HTML-теги: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a href="..."&gt; и т.д.</p>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="bg-black text-white px-6 py-3 rounded-lg font-bold hover:bg-gray-800 transition">💾 Сохранить</button>
                        <a href="?action=list" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-bold hover:bg-gray-300 transition">Отмена</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>