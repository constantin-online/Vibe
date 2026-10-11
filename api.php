<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'user';

// === РЕГИСТРАЦИЯ (с приветственным сообщением) ===
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (mb_strlen($username) < 3 || mb_strlen($password) < 4) {
        echo json_encode(['success' => false, 'message' => 'Имя от 3 символов, пароль от 4']); exit;
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'Только латиница, цифры и _']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Неверный email']); exit;
    }
    
    $activation_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $activation_token = bin2hex(random_bytes(16));
    
    try {
        $countStmt = $db->query("SELECT COUNT(*) FROM users");
        $role = ($countStmt->fetchColumn() == 0) ? 'admin' : 'user';
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, activation_token) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role, $activation_token]);
        $newUserId = $db->lastInsertId();
        
        // Отправляем email с кодом активации
        $activateUrl = "https://vibe-app.su/activate.php?token=" . $activation_token;
        $subject = "=?UTF-8?B?" . base64_encode("Активация аккаунта на Vibe") . "?=";
        $message = "Привет, $username!\n\nКод активации: $activation_code\n\nИли перейди по ссылке: $activateUrl\n\nЕсли письма не доходят — введи код активации на сайте vibe-app.su";
        $headers = "From: Vibe <no-reply@vibe-app.su>\r\n";
        $headers .= "Reply-To: no-reply@vibe-app.su\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($email, $subject, $message, $headers);
        
        // === ПРИВЕТСТВЕННОЕ СООБЩЕНИЕ В ЛС ===
        // Находим ID админа (первый пользователь в системе)
        $adminStmt = $db->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && $admin['id'] != $newUserId) {
            $welcomeText = "Привет, $username! 👋\n\nЯ создатель Vibe. Вижу, ты только что зарегистрировался — добро пожаловать! \n\nБуду дико благодарен, если напишешь, что можно улучшить. Любой фидбек помогает сделать платформу лучше.\n\nС меня шоколадка, промокод или вечная благодарность! 🍫\n\n— Команда Vibe";
            
            $msgStmt = $db->prepare("INSERT INTO messages (sender_id, recipient_id, content, is_read) VALUES (?, ?, ?, 0)");
            $msgStmt->execute([$admin['id'], $newUserId, $welcomeText]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Регистрация успешна! Проверь почту и личные сообщения на сайте.',
            'activation_code' => $activation_code,
            'username' => $username
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Это имя или email уже занято.']);
    }
    exit;
}

// === ВХОД (с проверкой бана) ===
if ($action === 'login') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) { echo json_encode(['success' => false, 'message' => 'Пользователь не найден']); exit; }
    
    // Проверка бана
    if (!empty($user['banned_until'])) {
        $banUntil = new DateTime($user['banned_until']);
        $now = new DateTime();
        if ($banUntil > $now || $user['banned_until'] === '9999-12-31 23:59:59') {
            $banText = $user['banned_until'] === '9999-12-31 23:59:59' 
                ? 'навсегда' 
                : 'до ' . $banUntil->format('d.m.Y H:i');
            $reason = !empty($user['ban_reason']) ? '. Причина: ' . $user['ban_reason'] : '';
            echo json_encode(['success' => false, 'message' => "Ваш аккаунт заблокирован $banText$reason"]);
            exit;
        }
    }
    
    if (!$user['is_activated']) { echo json_encode(['success' => false, 'message' => 'Аккаунт не активирован. Проверь почту или введи код активации.']); exit; }
    if (!password_verify($password, $user['password'])) { echo json_encode(['success' => false, 'message' => 'Неверный пароль']); exit; }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['avatar'] = $user['avatar'];
    $_SESSION['bio'] = $user['bio'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['verified'] = $user['verified'];
    $_SESSION['social_links'] = $user['social_links'] ?? '[]';
    
    echo json_encode(['success' => true]);
    exit;
}

// === ВЫХОД ===
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// === АКТИВАЦИЯ ПО КОДУ ===
if ($action === 'activate_by_code') {
    $username = trim($_POST['username'] ?? '');
    $code = trim($_POST['code'] ?? '');
    
    if (empty($username) || empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Заполни все поля']); exit;
    }
    
    $stmt = $db->prepare("SELECT id, username, email, is_activated FROM users WHERE username = ? AND is_activated = 0");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден или уже активирован']); exit;
    }
    
    $db->prepare("UPDATE users SET is_activated = 1, activation_token = '' WHERE id = ?")->execute([$user['id']]);
    
    $subject = "=?UTF-8?B?" . base64_encode("Добро пожаловать в Vibe!") . "?=";
    $message = "Привет, {$user['username']}!\n\n🎉 Твой аккаунт на Vibe успешно активирован.\n\nПерейти на сайт: https://vibe-app.su/\n\nС уважением, команда Vibe";
    $headers = "From: Vibe <no-reply@vibe-app.su>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($user['email'], $subject, $message, $headers);
    
    echo json_encode(['success' => true, 'message' => 'Аккаунт активирован! Теперь можешь войти.']);
    exit;
}

// === ЗАГРУЗКА АВАТАРА ===
if ($action === 'upload_avatar') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    
    $avatarBase64 = $_POST['avatar'] ?? '';
    if (empty($avatarBase64)) { echo json_encode(['success' => false, 'message' => 'Нет данных']); exit; }
    
    $maxSize = 10 * 1024 * 1024;
    if (strlen($avatarBase64) > $maxSize * 1.33) {
        echo json_encode(['success' => false, 'message' => 'Файл слишком большой. Максимум 10 МБ.']); exit;
    }
    
    if (!preg_match('#^data:image/(jpeg|jpg|png|webp|gif);base64,#i', $avatarBase64)) {
        echo json_encode(['success' => false, 'message' => 'Неверный формат изображения']); exit;
    }
    
    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $avatarBase64));
    if (!$imageData) { echo json_encode(['success' => false, 'message' => 'Не удалось декодировать изображение']); exit; }
    
    $image = @imagecreatefromstring($imageData);
    if (!$image) { echo json_encode(['success' => false, 'message' => 'Не удалось обработать изображение']); exit; }
    
    $width = imagesx($image);
    $height = imagesy($image);
    if ($width < 50 || $height < 50) {
        imagedestroy($image);
        echo json_encode(['success' => false, 'message' => 'Изображение слишком маленькое (мин. 50×50)']); exit;
    }
    
    $size = min($width, $height);
    $x = ($width - $size) / 2;
    $y = ($height - $size) / 2;
    
    $square = imagecreatetruecolor($size, $size);
    imagealphablending($square, false);
    imagesavealpha($square, true);
    $transparent = imagecolorallocatealpha($square, 0, 0, 0, 127);
    imagefill($square, 0, 0, $transparent);
    imagecopy($square, $image, 0, 0, $x, $y, $size, $size);
    
    $newSize = 400;
    $resized = imagecreatetruecolor($newSize, $newSize);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
    imagefill($resized, 0, 0, $transparent);
    imagecopyresampled($resized, $square, 0, 0, 0, 0, $newSize, $newSize, $size, $size);
    
    ob_start();
    imagewebp($resized, null, 85);
    $webpData = ob_get_clean();
    
    imagedestroy($image);
    imagedestroy($square);
    imagedestroy($resized);
    
    $avatarBase64WebP = 'data:image/webp;base64,' . base64_encode($webpData);
    
    $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$avatarBase64WebP, $user_id]);
    $_SESSION['avatar'] = $avatarBase64WebP;
    
    echo json_encode(['success' => true, 'message' => 'Аватар обновлён']);
    exit;
}

// === СОЗДАНИЕ ПОСТА ===
if ($action === 'create_post') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    $content = trim($_POST['content'] ?? '');
    $image = $_POST['image'] ?? '';
    $imagesJson = $_POST['images'] ?? '';
    $gif = trim($_POST['gif'] ?? '');
    $video = $_POST['video'] ?? '';
    $location_lat = $_POST['location_lat'] ?? '';
    $location_lng = $_POST['location_lng'] ?? '';
    $location_name = trim($_POST['location_name'] ?? '');
    
    if (mb_strlen($content) < 1 && empty($image) && empty($imagesJson) && empty($gif) && empty($video) && empty($location_name)) {
        echo json_encode(['success' => false, 'message' => 'Добавьте текст, медиа или геолокацию']); exit;
    }
    
    $finalImages = $imagesJson ?: (empty($image) ? '' : json_encode([$image]));
    $lat = !empty($location_lat) ? (float)$location_lat : null;
    $lng = !empty($location_lng) ? (float)$location_lng : null;
    
    $stmt = $db->prepare("INSERT INTO posts (user_id, content, image, images, gif, video, location_lat, location_lng, location_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $content, $image, $finalImages, $gif, $video, $lat, $lng, $location_name]);
    echo json_encode(['success' => true]);
    exit;
}

// === СОЗДАНИЕ ОПРОСА ===
if ($action === 'create_poll') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    
    $question = trim($_POST['question'] ?? '');
    $options_json = $_POST['options'] ?? '[]';
    $post_id = (int)($_POST['post_id'] ?? 0);
    
    if (mb_strlen($question) < 3) {
        echo json_encode(['success' => false, 'message' => 'Вопрос должен содержать минимум 3 символа']); exit;
    }
    
    $options = json_decode($options_json, true);
    if (!is_array($options) || count($options) < 2 || count($options) > 4) {
        echo json_encode(['success' => false, 'message' => 'Нужно от 2 до 4 вариантов ответа']); exit;
    }
    
    $options = array_filter(array_map('trim', $options));
    if (count($options) < 2) {
        echo json_encode(['success' => false, 'message' => 'Минимум 2 непустых варианта']); exit;
    }
    
    try {
        $db->beginTransaction();
        
        if ($post_id == 0) {
            $stmt = $db->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
            $stmt->execute([$user_id, '']);
            $post_id = $db->lastInsertId();
        }
        
        $stmt = $db->prepare("INSERT INTO polls (post_id, question) VALUES (?, ?)");
        $stmt->execute([$post_id, $question]);
        $poll_id = $db->lastInsertId();
        
        $stmt = $db->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
        foreach ($options as $option) {
            $stmt->execute([$poll_id, $option]);
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'poll_id' => $poll_id, 'post_id' => $post_id]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ошибка создания опроса']);
    }
    exit;
}

// === ГОЛОСОВАНИЕ В ОПРОСЕ ===
if ($action === 'vote_poll') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    
    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $option_id = (int)($_POST['option_id'] ?? 0);
    
    if ($poll_id <= 0 || $option_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Неверные данные']); exit;
    }
    
    try {
        $db->beginTransaction();
        
        $check = $db->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
        $check->execute([$poll_id, $user_id]);
        if ($check->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Вы уже голосовали']);
            exit;
        }
        
        $checkOption = $db->prepare("SELECT id FROM poll_options WHERE id = ? AND poll_id = ?");
        $checkOption->execute([$option_id, $poll_id]);
        if (!$checkOption->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Вариант не найден']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO poll_votes (poll_id, user_id, option_id) VALUES (?, ?, ?)");
        $stmt->execute([$poll_id, $user_id, $option_id]);
        
        $db->exec("UPDATE poll_options SET vote_count = vote_count + 1 WHERE id = $option_id");
        
        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ошибка голосования']);
    }
    exit;
}

// === ПОЛУЧЕНИЕ РЕЗУЛЬТАТОВ ОПРОСА ===
if ($action === 'get_poll') {
    $poll_id = (int)($_GET['poll_id'] ?? 0);
    if ($poll_id <= 0) { echo json_encode(['success' => false]); exit; }
    
    $stmt = $db->prepare("SELECT * FROM polls WHERE id = ?");
    $stmt->execute([$poll_id]);
    $poll = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$poll) { echo json_encode(['success' => false]); exit; }
    
    $stmt = $db->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id");
    $stmt->execute([$poll_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_votes = array_sum(array_column($options, 'vote_count'));
    
    $user_vote = null;
    if ($user_id) {
        $stmt = $db->prepare("SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
        $stmt->execute([$poll_id, $user_id]);
        $vote = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($vote) $user_vote = $vote['option_id'];
    }
    
    foreach ($options as &$option) {
        $option['percentage'] = $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100) : 0;
    }
    
    echo json_encode([
        'success' => true,
        'poll' => $poll,
        'options' => $options,
        'total_votes' => $total_votes,
        'user_vote' => $user_vote
    ]);
    exit;
}

// === ОБНОВЛЕНИЕ ПРОФИЛЯ ===
if ($action === 'update_profile') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $social_links = $_POST['social_links'] ?? '[]';
    $new_password = $_POST['new_password'] ?? '';
    
    $links = json_decode($social_links, true);
    if (!is_array($links)) $links = [];
    $links = array_slice($links, 0, 5);
    $social_links = json_encode($links);
    
    $passwordChanged = false;
    if (mb_strlen($new_password) > 0) {
        if (mb_strlen($new_password) < 4) {
            echo json_encode(['success' => false, 'message' => 'Пароль должен содержать минимум 4 символа']); exit;
        }
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET full_name = ?, bio = ?, social_links = ?, password = ? WHERE id = ?");
        $stmt->execute([$full_name, $bio, $social_links, $hashedPassword, $user_id]);
        $passwordChanged = true;
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, bio = ?, social_links = ? WHERE id = ?");
        $stmt->execute([$full_name, $bio, $social_links, $user_id]);
    }
    
    $_SESSION['full_name'] = $full_name;
    $_SESSION['bio'] = $bio;
    $_SESSION['social_links'] = $social_links;
    
    echo json_encode([
        'success' => true,
        'message' => $passwordChanged ? 'Профиль и пароль обновлены' : 'Профиль обновлён'
    ]);
    exit;
}

// === ЗАЯВКА НА ВЕРИФИКАЦИЮ ===
if ($action === 'submit_verification') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    
    $stmt = $db->prepare("SELECT verified, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['verified'] == 1) {
        echo json_encode(['success' => false, 'message' => 'Ваш аккаунт уже верифицирован']); exit;
    }
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $documents_json = $_POST['documents'] ?? '[]';
    
    if (mb_strlen($first_name) < 2 || mb_strlen($last_name) < 2 || mb_strlen($middle_name) < 2) {
        echo json_encode(['success' => false, 'message' => 'Имя, фамилия и отчество должны содержать минимум 2 символа']); exit;
    }
    
    $documents = json_decode($documents_json, true);
    if (!is_array($documents) || count($documents) < 1 || count($documents) > 5) {
        echo json_encode(['success' => false, 'message' => 'Нужно от 1 до 5 документов']); exit;
    }
    
    $username = $user['username'];
    $userDir = __DIR__ . '/verifications/' . $username;
    if (!is_dir($userDir)) { @mkdir($userDir, 0755, true); }
    
    $savedFiles = [];
    foreach ($documents as $index => $docBase64) {
        if (empty($docBase64)) continue;
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $docBase64));
        if (!$imageData) continue;
        
        $image = @imagecreatefromstring($imageData);
        if ($image) {
            $width = imagesx($image);
            $height = imagesy($image);
            $maxDim = 1200;
            if ($width > $maxDim || $height > $maxDim) {
                $ratio = min($maxDim / $width, $maxDim / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }
            ob_start();
            imagewebp($image, null, 85);
            $webpData = ob_get_clean();
            imagedestroy($image);
            
            $filename = 'doc_' . ($index + 1) . '.webp';
            file_put_contents($userDir . '/' . $filename, $webpData);
            $savedFiles[] = $filename;
        } else {
            $ext = 'bin';
            if (strpos($docBase64, 'image/png') === 0) $ext = 'png';
            elseif (strpos($docBase64, 'image/jpeg') === 0 || strpos($docBase64, 'image/jpg') === 0) $ext = 'jpg';
            elseif (strpos($docBase64, 'application/pdf') === 0) $ext = 'pdf';
            $filename = 'doc_' . ($index + 1) . '.' . $ext;
            file_put_contents($userDir . '/' . $filename, $imageData);
            $savedFiles[] = $filename;
        }
    }
    
    if (count($savedFiles) === 0) {
        echo json_encode(['success' => false, 'message' => 'Не удалось сохранить документы']); exit;
    }
    
    $full_name = "$last_name $first_name $middle_name";
    $docs_json = json_encode($savedFiles);
    
    try {
        $stmt = $db->prepare("INSERT INTO verification_requests (user_id, real_name, status, documents) VALUES (?, ?, 'pending', ?)");
        $stmt->execute([$user_id, $full_name, $docs_json]);
        echo json_encode(['success' => true, 'message' => 'Заявка отправлена! Модераторы рассмотрят её в течение 24-48 часов.']);
    } catch (Exception $e) {
        $stmt = $db->prepare("UPDATE verification_requests SET real_name = ?, documents = ?, status = 'pending', decided_at = NULL WHERE user_id = ? AND status != 'approved'");
        $stmt->execute([$full_name, $docs_json, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Заявка обновлена! Модераторы рассмотрят её заново.']);
    }
    exit;
}

// === ЛАЙК ===
if ($action === 'like') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    $post_id = (int)($_POST['post_id'] ?? 0);
    if ($post_id > 0) {
        $check = $db->prepare("SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = ?");
        $check->execute([$user_id, $post_id]);
        if ($check->fetch()) {
            $db->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
            $db->exec("UPDATE posts SET likes = likes - 1 WHERE id = $post_id AND likes > 0");
            echo json_encode(['success' => true, 'liked' => false]);
        } else {
            $db->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
            $db->exec("UPDATE posts SET likes = likes + 1 WHERE id = $post_id");
            echo json_encode(['success' => true, 'liked' => true]);
        }
    }
    exit;
}

// === КОММЕНТАРИЙ ===
if ($action === 'add_comment') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    $post_id = (int)($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if ($post_id > 0 && mb_strlen($content) > 0) {
        $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $content]);
        echo json_encode(['success' => true]);
    }
    exit;
}

// === ПОДПИСКА ===
if ($action === 'toggle_follow') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    $target_id = (int)($_POST['target_id'] ?? 0);
    if ($target_id > 0 && $target_id != $user_id) {
        $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$user_id, $target_id]);
        if ($stmt->fetch()) {
            $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")->execute([$user_id, $target_id]);
            echo json_encode(['success' => true, 'action' => 'unfollow']);
        } else {
            $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)")->execute([$user_id, $target_id]);
            echo json_encode(['success' => true, 'action' => 'follow']);
        }
    }
    exit;
}

// === ЖАЛОБА ===
if ($action === 'report_post') {
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Нужно войти']); exit; }
    $post_id = (int)($_POST['post_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    if (!in_array($reason, ['spam', 'abuse', 'misinformation', 'violence', 'other'])) {
        echo json_encode(['success' => false, 'message' => 'Неверная причина']); exit;
    }
    if ($post_id > 0) {
        try {
            $stmt = $db->prepare("INSERT INTO reports (post_id, user_id, reason, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$post_id, $user_id, $reason]);
            echo json_encode(['success' => true, 'message' => 'Жалоба отправлена']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Вы уже жаловались на этот пост']);
        }
    }
    exit;
}

// === ТРЕКЕР ПОСЕЩЕНИЙ ===
if ($action === 'track_visit') {
    $today = date('Y-m-d');
    try {
        $db->prepare("INSERT INTO site_visits (visit_date, visit_count) VALUES (?, 1) ON CONFLICT(visit_date) DO UPDATE SET visit_count = visit_count + 1")->execute([$today]);
    } catch (Exception $e) {}
    echo json_encode(['success' => true]);
    exit;
}

// === СЧЁТЧИК НЕПРОЧИТАННЫХ ===
if ($action === 'get_unread_count') {
    if (!$user_id) { echo json_encode(['success' => true, 'unread_count' => 0]); exit; }
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = (int)$stmt->fetchColumn();
    
    $last_sender = null;
    $last_message = null;
    if ($unread_count > 0) {
        $stmt2 = $db->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.recipient_id = ? AND m.is_read = 0 ORDER BY m.created_at DESC LIMIT 1");
        $stmt2->execute([$user_id]);
        $last = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($last) {
            $last_sender = $last['username'];
            $last_message = mb_substr($last['content'], 0, 50);
        }
    }
    
    echo json_encode(['success' => true, 'unread_count' => $unread_count, 'last_sender' => $last_sender, 'last_message' => $last_message]);
    exit;
}

// === МОДЕРАЦИЯ: СТАТИСТИКА ===
if ($action === 'get_mod_stats') {
    if (!$user_id || !in_array($user_role, ['admin', 'moderator'])) { echo json_encode(['success' => false]); exit; }
    $stmt = $db->query("SELECT reason, COUNT(*) as count FROM reports WHERE status = 'pending' GROUP BY reason");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt30 = $db->prepare("SELECT COUNT(*) FROM moderator_logs WHERE moderator_id = ? AND created_at >= datetime('now', '-30 days')");
    $stmt30->execute([$user_id]);
    $handled_30d = $stmt30->fetchColumn();
    $stmtTotal = $db->prepare("SELECT COUNT(*) FROM moderator_logs WHERE moderator_id = ?");
    $stmtTotal->execute([$user_id]);
    $handled_total = $stmtTotal->fetchColumn();
    $stmtVer = $db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'");
    $pending_verifications = $stmtVer->fetchColumn();
    echo json_encode(['success' => true, 'categories' => $categories, 'handled_30d' => $handled_30d, 'handled_total' => $handled_total, 'pending_verifications' => $pending_verifications]);
    exit;
}

// === МОДЕРАЦИЯ: ЗАЯВКИ НА ВЕРИФИКАЦИЮ ===
if ($action === 'get_verification_requests') {
    if (!$user_id || !in_array($user_role, ['admin', 'moderator'])) { echo json_encode(['success' => false]); exit; }
    $stmt = $db->query("SELECT vr.*, u.username, u.full_name, u.bio, u.avatar, u.verified FROM verification_requests vr JOIN users u ON vr.user_id = u.id ORDER BY vr.created_at DESC");
    echo json_encode(['success' => true, 'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'handle_verification') {
    if (!$user_id || !in_array($user_role, ['admin', 'moderator'])) { echo json_encode(['success' => false]); exit; }
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action_type = $_POST['action_type'] ?? '';
    if ($request_id > 0 && in_array($action_type, ['approve', 'reject'])) {
        $stmt = $db->prepare("SELECT user_id FROM verification_requests WHERE id = ? AND status = 'pending'");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($req) {
            if ($action_type === 'approve') {
                $db->prepare("UPDATE users SET verified = 1 WHERE id = ?")->execute([$req['user_id']]);
            }
            $db->prepare("UPDATE verification_requests SET status = ?, decided_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$action_type === 'approve' ? 'approved' : 'rejected', $request_id]);
        }
        echo json_encode(['success' => true]);
    }
    exit;
}

// === МОДЕРАЦИЯ: ЖАЛОБЫ ===
if ($action === 'get_pending_reports') {
    if (!$user_id || !in_array($user_role, ['admin', 'moderator'])) { echo json_encode(['success' => false]); exit; }
    $stmt = $db->query("SELECT r.*, p.content, u.username as reporter_name FROM reports r JOIN posts p ON r.post_id = p.id JOIN users u ON r.user_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 50");
    echo json_encode(['success' => true, 'reports' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'handle_report') {
    if (!$user_id || !in_array($user_role, ['admin', 'moderator'])) { echo json_encode(['success' => false]); exit; }
    $report_id = (int)($_POST['report_id'] ?? 0);
    $action_type = $_POST['action_type'] ?? '';
    if ($report_id > 0 && in_array($action_type, ['resolve', 'ignore'])) {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT post_id FROM reports WHERE id = ? AND status = 'pending'");
            $stmt->execute([$report_id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($report) {
                if ($action_type === 'resolve') {
                    $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$report['post_id']]);
                    $db->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$report['post_id']]);
                }
                $db->prepare("UPDATE reports SET status = ? WHERE id = ?")->execute([$action_type === 'resolve' ? 'resolved' : 'ignored', $report_id]);
                $logStmt = $db->prepare("INSERT INTO moderator_logs (moderator_id, report_id, action) VALUES (?, ?, ?)");
                $logStmt->execute([$user_id, $report_id, $action_type]);
            }
            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Ошибка БД']);
        }
    }
    exit;
}

// === МОДЕРАТОРЫ: СПИСОК ===
if ($action === 'get_moderators_list') {
    if (!$user_id || $user_role !== 'admin') { echo json_encode(['success' => false]); exit; }
    $stmt = $db->query("SELECT u.id, u.username, u.full_name, 
        (SELECT COUNT(*) FROM moderator_logs WHERE moderator_id = u.id AND created_at >= datetime('now', '-30 days')) as handled_30d,
        (SELECT COUNT(*) FROM moderator_logs WHERE moderator_id = u.id) as handled_total
        FROM users u WHERE u.role = 'moderator' ORDER BY handled_total DESC");
    echo json_encode(['success' => true, 'moderators' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// === ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ===
function decodeImages($imagesField, $imageField) {
    if (!empty($imagesField)) { $decoded = json_decode($imagesField, true); if (is_array($decoded)) return $decoded; }
    if (!empty($imageField)) return [$imageField];
    return [];
}

// === ОПТИМИЗИРОВАННЫЕ ФУНКЦИИ ЗАГРУЗКИ ДАННЫХ ===

function loadCommentsForPosts($db, $post_ids) {
    $result = [];
    if (empty($post_ids)) return $result;
    
    $placeholders = str_repeat('?,', count($post_ids) - 1) . '?';
    $c_stmt = $db->prepare("SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id IN ($placeholders) ORDER BY c.created_at ASC");
    $c_stmt->execute($post_ids);
    
    foreach ($c_stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
        if (!isset($result[$c['post_id']])) $result[$c['post_id']] = [];
        $result[$c['post_id']][] = $c;
    }
    
    return $result;
}

function loadPollsForPosts($db, $post_ids, $user_id) {
    $result = [];
    if (empty($post_ids)) return $result;
    
    $placeholders = str_repeat('?,', count($post_ids) - 1) . '?';
    
    // Один запрос на все опросы
    $poll_stmt = $db->prepare("SELECT p.id, p.post_id, p.question, o.id as opt_id, o.option_text, o.vote_count FROM polls p LEFT JOIN poll_options o ON p.id = o.poll_id WHERE p.post_id IN ($placeholders) ORDER BY p.id, o.id");
    $poll_stmt->execute($post_ids);
    $pollsData = $poll_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Группируем по post_id
    $pollsByPost = [];
    foreach ($pollsData as $row) {
        if (!isset($pollsByPost[$row['post_id']])) {
            $pollsByPost[$row['post_id']] = [
                'poll_id' => $row['id'],
                'poll_question' => $row['question'],
                'options' => []
            ];
        }
        if ($row['opt_id']) {
            $pollsByPost[$row['post_id']]['options'][] = [
                'id' => $row['opt_id'],
                'option_text' => $row['option_text'],
                'vote_count' => $row['vote_count']
            ];
        }
    }
    
    // Голоса пользователя (один запрос)
    $userVotes = [];
    if ($user_id) {
        $vote_stmt = $db->prepare("SELECT poll_id, option_id FROM poll_votes WHERE user_id = ? AND poll_id IN (SELECT id FROM polls WHERE post_id IN ($placeholders))");
        $vote_stmt->execute(array_merge([$user_id], $post_ids));
        foreach ($vote_stmt->fetchAll(PDO::FETCH_ASSOC) as $v) {
            $userVotes[$v['poll_id']] = $v['option_id'];
        }
    }
    
    // Собираем результат
    foreach ($pollsByPost as $postId => $poll) {
        $total = array_sum(array_column($poll['options'], 'vote_count'));
        foreach ($poll['options'] as &$opt) {
            $opt['percentage'] = $total > 0 ? round(($opt['vote_count'] / $total) * 100) : 0;
        }
        unset($opt);
        
        $pollId = $poll['poll_id'];
        $result[$postId] = [
            'has_poll' => true,
            'poll_id' => $pollId,
            'poll_question' => $poll['poll_question'],
            'poll_options' => $poll['options'],
            'total_votes' => $total,
            'user_vote' => $userVotes[$pollId] ?? null
        ];
    }
    
    return $result;
}

function loadLocationsForPosts($db, $post_ids) {
    $result = [];
    if (empty($post_ids)) return $result;
    
    $placeholders = str_repeat('?,', count($post_ids) - 1) . '?';
    $loc_stmt = $db->prepare("SELECT id, location_lat, location_lng, location_name FROM posts WHERE id IN ($placeholders) AND location_name != '' AND location_name IS NOT NULL");
    $loc_stmt->execute($post_ids);
    
    foreach ($loc_stmt->fetchAll(PDO::FETCH_ASSOC) as $loc) {
        $result[$loc['id']] = [
            'has_location' => true,
            'location_lat' => (float)$loc['location_lat'],
            'location_lng' => (float)$loc['location_lng'],
            'location_name' => $loc['location_name']
        ];
    }
    
    return $result;
}

// === ПОЛУЧЕНИЕ ЛЕНТЫ (ОПТИМИЗИРОВАННО) ===
if ($action === 'get_posts') {
    $tab = $_GET['tab'] ?? 'all';
    if ($tab === 'following' && $user_id) {
        $sql = "SELECT p.*, u.username, u.full_name, u.avatar, u.role, u.verified,
                (SELECT COUNT(*) FROM follows WHERE follower_id = :uid AND following_id = p.user_id) as is_followed,
                (SELECT COUNT(*) FROM post_likes WHERE user_id = :uid2 AND post_id = p.id) as is_liked,
                (SELECT COUNT(*) FROM reports WHERE user_id = :uid3 AND post_id = p.id) as is_reported
                FROM posts p JOIN users u ON p.user_id = u.id 
                WHERE p.user_id IN (SELECT following_id FROM follows WHERE follower_id = :uid4)
                ORDER BY p.created_at DESC LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid' => $user_id, ':uid2' => $user_id, ':uid3' => $user_id, ':uid4' => $user_id]);
    } else {
        $sql = "SELECT p.*, u.username, u.full_name, u.avatar, u.role, u.verified,
                (SELECT COUNT(*) FROM follows WHERE follower_id = :uid AND following_id = p.user_id) as is_followed,
                (SELECT COUNT(*) FROM post_likes WHERE user_id = :uid2 AND post_id = p.id) as is_liked,
                (SELECT COUNT(*) FROM reports WHERE user_id = :uid3 AND post_id = p.id) as is_reported
                FROM posts p JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid' => $user_id, ':uid2' => $user_id, ':uid3' => $user_id]);
    }
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем ID всех постов
    $postIds = array_column($posts, 'id');
    
    // ОДНИМ запросом загружаем комментарии, опросы и локации для ВСЕХ постов
    $allComments = loadCommentsForPosts($db, $postIds);
    $allPolls = loadPollsForPosts($db, $postIds, $user_id);
    $allLocations = loadLocationsForPosts($db, $postIds);
    
    foreach ($posts as &$post) {
        $post['comments'] = $allComments[$post['id']] ?? [];
        $post['images_array'] = decodeImages($post['images'], $post['image']);
        
        // Добавляем данные опроса
        if (isset($allPolls[$post['id']])) {
            $post = array_merge($post, $allPolls[$post['id']]);
        } else {
            $post['has_poll'] = false;
        }
        
        // Добавляем данные локации
        if (isset($allLocations[$post['id']])) {
            $post = array_merge($post, $allLocations[$post['id']]);
        } else {
            $post['has_location'] = false;
        }
    }
    
    echo json_encode(['success' => true, 'posts' => $posts, 'current_user_id' => $user_id]);
    exit;
}

// === ПОСТЫ ПОЛЬЗОВАТЕЛЯ (ОПТИМИЗИРОВАННО) ===
if ($action === 'get_user_posts') {
    $username = trim($_GET['username'] ?? '');
    $tab = $_GET['tab'] ?? 'threads';
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) { echo json_encode(['success' => false]); exit; }
    $uid = $target['id'];
    
    if ($tab === 'replies') {
        $sql = "SELECT p.*, u.username, u.full_name, u.avatar, u.role, u.verified, 0 as is_followed, 0 as is_liked, 0 as is_reported FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id IN (SELECT post_id FROM comments WHERE user_id = ?) ORDER BY p.created_at DESC";
    } elseif ($tab === 'media') {
        $sql = "SELECT p.*, u.username, u.full_name, u.avatar, u.role, u.verified, 0 as is_followed, 0 as is_liked, 0 as is_reported FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? AND (p.image != '' OR p.images != '' OR p.gif != '' OR p.video != '') ORDER BY p.created_at DESC";
    } else {
        $sql = "SELECT p.*, u.username, u.full_name, u.avatar, u.role, u.verified, 0 as is_followed, 0 as is_liked, 0 as is_reported FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute([$uid]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $postIds = array_column($posts, 'id');
    $allComments = loadCommentsForPosts($db, $postIds);
    $allPolls = loadPollsForPosts($db, $postIds, $user_id);
    $allLocations = loadLocationsForPosts($db, $postIds);
    
    foreach ($posts as &$post) {
        $post['comments'] = $allComments[$post['id']] ?? [];
        $post['images_array'] = decodeImages($post['images'], $post['image']);
        
        if (isset($allPolls[$post['id']])) {
            $post = array_merge($post, $allPolls[$post['id']]);
        } else {
            $post['has_poll'] = false;
        }
        
        if (isset($allLocations[$post['id']])) {
            $post = array_merge($post, $allLocations[$post['id']]);
        } else {
            $post['has_location'] = false;
        }
    }
    
    echo json_encode(['success' => true, 'posts' => $posts, 'user_id' => $uid, 'tab' => $tab]);
    exit;
}

// === ПРОФИЛЬ ===
if ($action === 'get_profile') {
    $username = trim($_GET['username'] ?? '');
    $stmt = $db->prepare("SELECT id, username, full_name, bio, avatar, role, verified, social_links, created_at FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { echo json_encode(['success' => false]); exit; }
    $uid = $user['id'];
    $stats = $db->prepare("SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ?) as following_count,
        (SELECT COUNT(*) FROM follows WHERE following_id = ?) as followers_count");
    $stats->execute([$uid, $uid, $uid]);
    $user = array_merge($user, $stats->fetch(PDO::FETCH_ASSOC));
    $user['is_own'] = ($user_id == $uid);
    $user['current_user_id'] = $user_id;
    
    if ($user_id && $user_id != $uid) {
        $f = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
        $f->execute([$user_id, $uid]);
        $user['is_following'] = (bool)$f->fetch();
    } else {
        $user['is_following'] = false;
    }
    
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

// === ПОИСК ===
if ($action === 'search') {
    $query = trim($_GET['q'] ?? '');
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'users' => [], 'posts' => []]);
        exit;
    }
    $searchQuery = '%' . $query . '%';
    $stmt = $db->prepare("SELECT id, username, full_name, avatar, role, verified FROM users WHERE username LIKE ? OR full_name LIKE ? LIMIT 10");
    $stmt->execute([$searchQuery, $searchQuery]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare("SELECT p.id, p.content, p.created_at, p.user_id, u.username, u.full_name, u.avatar FROM posts p JOIN users u ON p.user_id = u.id WHERE p.content LIKE ? ORDER BY p.created_at DESC LIMIT 10");
    $stmt->execute([$searchQuery]);
    echo json_encode(['success' => true, 'users' => $users, 'posts' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// === СООБЩЕНИЯ: СПИСОК ДИАЛОГОВ ===
if ($action === 'get_conversations') {
    if (!$user_id) { echo json_encode(['success' => false]); exit; }
    $stmt = $db->prepare("SELECT DISTINCT CASE WHEN sender_id = :uid THEN recipient_id ELSE sender_id END as other_id FROM messages WHERE sender_id = :uid OR recipient_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $conversations = [];
    foreach ($partners as $p) {
        $otherId = $p['other_id'];
        $uStmt = $db->prepare("SELECT id, username, full_name, avatar FROM users WHERE id = ?");
        $uStmt->execute([$otherId]);
        $other = $uStmt->fetch(PDO::FETCH_ASSOC);
        if (!$other) continue;
        $mStmt = $db->prepare("SELECT content, created_at FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY created_at DESC LIMIT 1");
        $mStmt->execute([$user_id, $otherId, $otherId, $user_id]);
        $lastMsg = $mStmt->fetch(PDO::FETCH_ASSOC);
        $nStmt = $db->prepare("SELECT COUNT(*) as cnt FROM messages WHERE sender_id = ? AND recipient_id = ? AND is_read = 0");
        $nStmt->execute([$otherId, $user_id]);
        $unread = $nStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        $conversations[] = [
            'other_id' => $otherId,
            'other_username' => $other['username'],
            'other_fullname' => $other['full_name'],
            'other_avatar' => $other['avatar'],
            'last_message' => $lastMsg['content'] ?? '',
            'last_message_time' => $lastMsg['created_at'] ?? '',
            'unread_count' => (int)$unread
        ];
    }
    usort($conversations, function($a, $b) { return strtotime($b['last_message_time']) - strtotime($a['last_message_time']); });
    echo json_encode(['success' => true, 'conversations' => $conversations]);
    exit;
}

// === СООБЩЕНИЯ: ПЕРЕПИСКА ===
if ($action === 'get_messages') {
    if (!$user_id) { echo json_encode(['success' => false]); exit; }
    $other_username = trim($_GET['username'] ?? '');
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$other_username]);
    $other = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$other) { echo json_encode(['success' => false]); exit; }
    $other_id = $other['id'];
    $stmt = $db->prepare("SELECT m.*, u.username, u.avatar FROM messages m JOIN users u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?) ORDER BY m.created_at ASC");
    $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
    $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ? AND is_read = 0")->execute([$other_id, $user_id]);
    echo json_encode(['success' => true, 'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'other_user' => $other]);
    exit;
}

// === СООБЩЕНИЯ: ОТПРАВИТЬ ===
if ($action === 'send_message') {
    if (!$user_id) { echo json_encode(['success' => false]); exit; }
    $recipient_username = trim($_POST['username'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (empty($content)) { echo json_encode(['success' => false, 'message' => 'Пустое сообщение']); exit; }
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$recipient_username]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$recipient) { echo json_encode(['success' => false, 'message' => 'Пользователь не найден']); exit; }
    $stmt = $db->prepare("INSERT INTO messages (sender_id, recipient_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $recipient['id'], $content]);
    echo json_encode(['success' => true]);
    exit;
}
?>