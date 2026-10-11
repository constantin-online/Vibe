<?php
session_start();
require_once 'db.php';
require_once 'config.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Проверяем, есть ли у нас verifier и device_id в сессии
    if (!isset($_SESSION['pkce_verifier']) || !isset($_SESSION['pkce_device_id'])) {
        die('Ошибка сессии: не найдены данные PKCE. Попробуйте войти заново.');
    }
    
    $code_verifier = $_SESSION['pkce_verifier'];
    $device_id = $_SESSION['pkce_device_id'];
    
    // 1. Обмениваем code на access_token (используем PKCE + device_id)
    $tokenUrl = "https://id.vk.ru/oauth2/auth";
    
    $postData = http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => VK_CLIENT_ID,
        'client_secret' => VK_CLIENT_SECRET,
        'redirect_uri' => VK_REDIRECT_URI,
        'code_verifier' => $code_verifier,
        'device_id' => $device_id
    ]);
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Очищаем данные из сессии после использования
    unset($_SESSION['pkce_verifier']);
    unset($_SESSION['pkce_device_id']);
    
    // Если первый запрос не удался, пробуем без device_id (запасной вариант)
    if ($httpCode !== 200) {
        $postDataNoDevice = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => VK_CLIENT_ID,
            'client_secret' => VK_CLIENT_SECRET,
            'redirect_uri' => VK_REDIRECT_URI,
            'code_verifier' => $code_verifier
        ]);
        
        $ch2 = curl_init($tokenUrl);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $postDataNoDevice);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch2);
        $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
    }
    
    if ($httpCode !== 200) {
        die('Ошибка получения токена VK ID. HTTP: ' . $httpCode . '. Ответ: ' . $response);
    }
    
    $tokenData = json_decode($response, true);
    
    if (!isset($tokenData['access_token'])) {
        die('Ошибка: не получен access_token. Ответ VK ID: ' . json_encode($tokenData));
    }
    
    $accessToken = $tokenData['access_token'];
    
    // 2. Получаем данные пользователя через VK ID API
    $userUrl = "https://id.vk.ru/oauth2/user_info";
    
    $ch = curl_init($userUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'access_token' => $accessToken
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $userResponse = curl_exec($ch);
    curl_close($ch);
    
    $userData = json_decode($userResponse, true);
    
    if (!isset($userData['id'])) {
        die('Ошибка получения данных пользователя. Ответ: ' . json_encode($userData));
    }
    
    $vkUserId = $userData['id'];
    $fullName = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
    
    // Аватар приходит как объект, берем url
    $avatar = '';
    if (isset($userData['avatar']) && isset($userData['avatar']['url'])) {
        $avatar = $userData['avatar']['url'];
    }
    
    $isVerified = $userData['is_verified'] ?? 0;
    $username = 'user' . $vkUserId;
    
    // 3. Проверяем существующего пользователя или создаем нового
    $stmt = $db->prepare("SELECT id, role FROM users WHERE vk_id = ?");
    $stmt->execute([$vkUserId]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        $userId = $existingUser['id'];
        $userRole = $existingUser['role'];
        $db->prepare("UPDATE users SET full_name = ?, avatar = ?, verified = ? WHERE vk_id = ?")
           ->execute([$fullName, $avatar, $isVerified, $vkUserId]);
    } else {
        $countStmt = $db->query("SELECT COUNT(*) FROM users");
        // Первый пользователь в системе становится админом
        $role = ($countStmt->fetchColumn() == 0) ? 'admin' : 'user';
        
        $db->prepare("INSERT INTO users (vk_id, username, full_name, avatar, role, verified) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$vkUserId, $username, $fullName, $avatar, $role, $isVerified]);
        $userId = $db->lastInsertId();
        $userRole = $role;
    }
    
    // 4. Создаем сессию
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['avatar'] = $avatar;
    $_SESSION['role'] = $userRole;
    $_SESSION['verified'] = $isVerified;
    
    // 5. Перенаправляем на главную
    header('Location: index.php');
    exit;
}

if (isset($_GET['error'])) {
    $errorDesc = $_GET['error_description'] ?? $_GET['error'] ?? 'Неизвестная ошибка';
    die('Ошибка авторизации: ' . htmlspecialchars($errorDesc) . '. <a href="index.php">Вернуться</a>');
}

header('Location: index.php');
exit;
?>