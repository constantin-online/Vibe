<?php
require_once 'db.php';

$token = $_GET['token'] ?? '';

// === СЦЕНАРИЙ 1: Токен не передан или пустой ===
if (empty($token)) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ошибка — Vibe</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-2xl shadow-lg text-center max-w-md mx-4">
            <div class="text-6xl mb-4">❌</div>
            <h1 class="text-2xl font-bold mb-2">Неверная ссылка</h1>
            <p class="text-gray-600 mb-6">Ссылка активации недействительна или истекла. Возможно, вы уже активировали аккаунт.</p>
            <a href="/" class="inline-block bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">На главную</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// === СЦЕНАРИЙ 2: Ищем пользователя по токену ===
$stmt = $db->prepare("SELECT id, username, email, is_activated FROM users WHERE activation_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ссылка недействительна — Vibe</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-2xl shadow-lg text-center max-w-md mx-4">
            <div class="text-6xl mb-4">️</div>
            <h1 class="text-2xl font-bold mb-2">Ссылка недействительна</h1>
            <p class="text-gray-600 mb-6">Возможно, она уже была использована или срок её действия истёк. Попробуйте активировать аккаунт через код на сайте.</p>
            <a href="/" class="inline-block bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">На главную</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// === СЦЕНАРИЙ 3: Аккаунт уже активирован ===
if ($user['is_activated']) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Уже активирован — Vibe</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-2xl shadow-lg text-center max-w-md mx-4">
            <div class="text-6xl mb-4">✅</div>
            <h1 class="text-2xl font-bold mb-2">Аккаунт уже активирован</h1>
            <p class="text-gray-600 mb-6">Твой аккаунт <strong><?= htmlspecialchars($user['username']) ?></strong> уже активен. Ты можешь войти на сайт.</p>
            <a href="/" class="inline-block bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">Войти в Vibe</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// === СЦЕНАРИЙ 4: Успешная активация ===

// 1. Активируем аккаунт
$db->prepare("UPDATE users SET is_activated = 1, activation_token = '' WHERE id = ?")->execute([$user['id']]);

// 2. Отправляем приветственное письмо
$subject = "=?UTF-8?B?" . base64_encode("Добро пожаловать в Vibe!") . "?=";
$message = "Привет, {$user['username']}!\n\n"
         . " Поздравляем! Твой аккаунт на Vibe успешно активирован.\n\n"
         . "Теперь ты можешь:\n"
         . "• Публиковать посты и делиться мыслями\n"
         . "• Подписываться на других пользователей\n"
         . "• Ставить лайки и оставлять комментарии\n"
         . "• Получать верификацию\n\n"
         . "Перейти на сайт: https://vibe-app.su/\n\n"
         . "С уважением, команда Vibe";
$headers = "From: Vibe <no-reply@vibe-app.su>\r\n";
$headers .= "Reply-To: no-reply@vibe-app.su\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
@mail($user['email'], $subject, $message, $headers);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Активация успешна — Vibe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-lg text-center max-w-md mx-4">
        <div class="text-6xl mb-4">🎉</div>
        <h1 class="text-2xl font-bold mb-2">Аккаунт активирован!</h1>
        <p class="text-gray-600 mb-2">Привет, <strong><?= htmlspecialchars($user['username']) ?></strong>!</p>
        <p class="text-gray-600 mb-4">Твой аккаунт успешно активирован.</p>
        <p class="text-sm text-gray-500 mb-6">Мы отправили приветственное письмо на <strong><?= htmlspecialchars($user['email']) ?></strong>. Проверь папку «Спам», если не видишь его.</p>
        <a href="/" class="inline-block bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">Войти в Vibe</a>
    </div>
</body>
</html>