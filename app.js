// ============================================
// Vibe App - JavaScript
// ============================================

// === ГЛОБАЛЬНЫЕ КОНСТАНТЫ (передаются из PHP) ===
const IS_LOGGED_IN = window.IS_LOGGED_IN || false;
const CURRENT_USER_ID = window.CURRENT_USER_ID || 0;
const PAGE_NAME = window.PAGE_NAME || '';
const USERNAME_PARAM = window.USERNAME_PARAM || '';
const PROFILE_DATA = window.PROFILE_DATA || { full_name: '', bio: '', social_links: '' };
const GIPHY_API_KEY = window.GIPHY_API_KEY || '';

// ============================================
// EMOJI ДАННЫЕ
// ============================================
const EMOJI_DATA = {
    smileys: { title: 'Смайлики', emojis: ['😀','😃','😄','😁','','😅','🤣','','🙂','🙃','😉','😊','😇','🥰','','🤩','😘','😗','😚','😙','🥲','😋','😛','😜','','😝','🤑','','🤭','🤫','','🤐','🤨','','😑','😶','😏','','🙄','😬','😮‍💨','🤥','😌','😔','😪','🤤','','😷','🤒','🤕','🤢','🤮','','🥴','😵','🤠','🥳','🥸','😎','🤓','🧐'] },
    gestures: { title: 'Жесты', emojis: ['👋','🤚','️','✋','🖖','','🤌','🤏','️','🤞','🤟','','🤙','👈','','👆','','👇','☝️','','👎','✊','','🤛','🤜','','🙌','👐','','🤝','🙏','✍️','💅','🤳','💪','🦾','🦵','🦶','👂','🦻','👃','🧠','🫀','🫁','🦷','🦴','','👁️','👅','','👶','🧒','','👧','🧑','','🧔','👩','','👴','👵'] },
    animals: { title: 'Животные', emojis: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐻‍❄️','🐨','🐯','🦁','🐮','🐷','🐽','🐸','🐵','🙈','🙉','🙊','🐒','🐔','🐧','🐦','🐤','🐣','🐥','','🦅','🦉','','🐺','🐗','','🦄','🐝','','🐛','🦋','','🐞','🐜','','🪲','🪳','','🕷️','🕸️','🦂','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈'] },
    food: { title: 'Еда', emojis: ['🍏','','🍐','🍊','','🍌','🍉','','🍓','🫐','','🍒','🍑','','🍍','🥥','','🍅','🍆','','🥦','🥬','','🌶️','🫑','','🥕','🫒','','🧅','🥔','','🥐','🥯','','🥖','🥨','','🥚','🍳','','🥞','🧇','','🥩','🍗','','🦴','🌭','','🍟','🍕','','🥪','🥙','','🌮','🌯','','🥗','🥘','','🥫','🍝','','🍲','🍛','','🍱','🥟','','🍤','🍙','','🍘','🍥','','🥮','🍢','','🍨','🍩','','🎂','🍰','','🥧','🍫','','🍭','🍯'] },
    objects: { title: 'Объекты', emojis: ['⚽','🏀','','⚾','🥎','','🏐','🏉','','🎱','🪀','','🏸','🏒','','🥍','🏏','','🥅','⛳','🪁','🏹','🎣','🤿','🥊','🥋','🎽','🛹','🛼','🛷','⛸️','','🎿','⛷️','🏂','🏋️','🤼','🤸','🤺','🤾','🏌️','🏇','','🏄','','🤽','🚣','','🚵','🚴','','🥇','🥈','','🏅','🎖️','️','🎗️','🎫','🎟️','🎪','','🎭','🩰','','🎬','🎤','','🎼','🎹','','🪘','🎷','','🪗','🎸','','🎻','🎲','️','🎯','🎳','','🎰','🧩'] },
    symbols: { title: 'Символы', emojis: ['❤️','🧡','','💚','💙','💜','','🤍','🤎','','❣️','💕','💞','💓','💗','💖','💘','💝','💟','☮️','️','☪️','🕉️','☸️','✡️','🔯','🕎','☯️','☦️','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓','','⚛️','🉑','☢️','☣️','📴','📳','🈶','','🈸','🈺','️','✴️','🆚','','🉐','㊙️','㊗️','🈴','','🈹','🈲','️','🅱️','🆎','🆑','🅾️','','❌','⭕','🛑','⛔','📛','🚫','💯','💢','♨️','','🚯','🚳','','🔞','📵','','❗','❕','❓','❔','‼️','⁉️','','🔆','〽️','️','🚸','🔱','⚜️','🔰','♻️','✅','🈯','','❇️','✳️','','🌐','💠','️','🌀','💤','','🚾','♿','🅿️','🛗','','🈂️','🛂','🛃','🛄','🛅','🚹','🚺','🚼','🚻','🚮','🎦','📶','🈁','','ℹ️','🔤','','🔠','🆖','','🆙','🆒','','🆓','0️⃣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟','','#️⃣','*️','⏏️','▶️','️','⏯️','⏹️','⏺️','⏭️','⏮️','⏩','','⏫','⏬','️','🔼','🔽','️','⬅️','⬆️','⬇️','↗️','️','↙️','↖️','️','↔️','↪️','️','⤴️','️','🔀','🔁','','🔄','🔃','','🎶','➕','➖','➗','✖️','🟰','♾️','💲','💱','™️','©️','®️','👁🗨','🔚','','🔛','🔝','','〰️','➰','➿','✔️','☑️','🔘','🔴','🟠','','🟢','🔵','','⚫','⚪','','🔺','','🔸','🔹','','🔷','🔳','','▪️','▫️','','◽','◼️','◻️','🟥','🟧','','🟩','🟦','','⬛','⬜',''] }
};

// ============================================
// ПЕРЕМЕННЫЕ СОСТОЯНИЯ
// ============================================
let currentEmojiCategory = 'smileys';
let emojiSearchQuery = '';
let pollOptions = ['', ''];
let currentPollData = null;
let selectedGifUrl = '';
let gifSearchTimeout = null;
let currentTab = 'all';
let selectedImages = [];
let selectedVideoBase64 = '';
let currentReportPostId = null;
let selectedReason = null;
let currentSharePostId = null;
let profileTab = 'threads';
let selectedDocs = [];
let msgRecipientUsername = '';
let currentConversationUser = null;
let lastUnreadCount = 0;
let pollInterval = null;
let locationMap = null;
let locationMarker = null;
let selectedLocation = { lat: null, lng: null, name: '' };
let locationSearchTimeout = null;

// ============================================
// ЗВУКОВАЯ СИСТЕМА
// ============================================
let audioContext = null;
let soundEnabled = true;

function initAudio() {
    if (audioContext) {
        if (audioContext.state === 'suspended') audioContext.resume().catch(() => {});
        return;
    }
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) { soundEnabled = false; return; }
        audioContext = new AudioContext();
        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();
        osc.type = 'sine';
        osc.frequency.value = 440;
        gain.gain.setValueAtTime(0.01, audioContext.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.05);
        osc.connect(gain);
        gain.connect(audioContext.destination);
        osc.start(audioContext.currentTime);
        osc.stop(audioContext.currentTime + 0.05);
    } catch(e) { soundEnabled = false; }
}

function playNotificationSound() {
    if (!soundEnabled) return;
    if (!audioContext) { initAudio(); if (!audioContext) return; }
    if (audioContext.state === 'suspended') {
        audioContext.resume().then(() => playBeep()).catch(() => { soundEnabled = false; });
        return;
    }
    playBeep();
}

function playBeep() {
    if (!audioContext) return;
    try {
        const now = audioContext.currentTime;
        [{ freq: 880, time: 0, duration: 0.3 }, { freq: 1320, time: 0.15, duration: 0.3 }].forEach(note => {
            const osc = audioContext.createOscillator();
            const gain = audioContext.createGain();
            osc.type = 'sine';
            osc.frequency.value = note.freq;
            gain.gain.setValueAtTime(0.3, now + note.time);
            gain.gain.exponentialRampToValueAtTime(0.01, now + note.time + note.duration);
            osc.connect(gain);
            gain.connect(audioContext.destination);
            osc.start(now + note.time);
            osc.stop(now + note.time + note.duration);
        });
    } catch(e) {}
}

document.addEventListener('click', function initSoundOnClick() {
    initAudio();
    document.removeEventListener('click', initSoundOnClick);
}, { once: true });

document.addEventListener('touchstart', function initSoundOnTouch() {
    initAudio();
    document.removeEventListener('touchstart', initSoundOnTouch);
}, { once: true });

// ============================================
// УТИЛИТЫ
// ============================================
function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function parseMarkdown(text) {
    if (!text) return '';
    let html = escapeHtml(text);
    html = html.replace(/^# (.+)$/gm, '<h3 class="text-xl font-bold mt-2 mb-1">$1</h3>');
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
    html = html.replace(/__(.+?)__/g, '<u>$1</u>');
    html = html.replace(/~~(.+?)~~/g, '<s>$1</s>');
    html = html.replace(/\n/g, '<br>');
    return html;
}

function formatMessageTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'только что';
    if (diff < 3600) return Math.floor(diff / 60) + ' мин.';
    if (diff < 86400) return Math.floor(diff / 3600) + ' ч.';
    if (diff < 604800) return Math.floor(diff / 86400) + ' дн.';
    return date.toLocaleDateString('ru-RU');
}

function getUserBadges(user) {
    let badges = '';
    if (user.role === 'moderator') badges += '<span class="badge-mod" title="Модератор">М</span>';
    if (user.verified == 1) badges += '<span class="badge-verified"><svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>';
    return badges;
}

// ============================================
// УВЕДОМЛЕНИЯ (ТОСТЫ)
// ============================================
function showToast(title, text, onClick) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast';
    const soundStatus = soundEnabled
        ? '<span class="text-xs text-green-600 ml-2">🔊</span>'
        : '<button onclick="event.stopPropagation(); initAudio(); soundEnabled=true; this.textContent=\'🔊\'; this.className=\'text-xs text-green-600 ml-2\';" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded ml-2 hover:bg-blue-200 cursor-pointer">🔇 Включить звук</button>';
    toast.innerHTML = `<div class="flex items-start gap-2"><span class="toast-icon">💬</span><div class="flex-1"><div class="flex items-center flex-wrap"><div class="toast-title">${escapeHtml(title)}</div>${soundStatus}</div><div class="toast-text">${escapeHtml(text)}</div></div><span class="toast-close" onclick="event.stopPropagation(); this.parentElement.parentElement.remove()">&times;</span></div>`;
    if (onClick) {
        toast.onclick = (e) => {
            if (!e.target.classList.contains('toast-close') && e.target.tagName !== 'BUTTON') {
                onClick();
                toast.remove();
            }
        };
    }
    container.appendChild(toast);
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

function updateUnreadBadge(count) {
    const badge = document.getElementById('msg-badge');
    const badgeMobile = document.getElementById('msg-badge-mobile');
    if (count > 0) {
        if (badge) { badge.textContent = count > 99 ? '99+' : count; badge.classList.remove('hidden'); }
        if (badgeMobile) { badgeMobile.textContent = count > 99 ? '99+' : count; badgeMobile.classList.remove('hidden'); }
        document.title = `(${count}) Vibe — Делись моментами`;
    } else {
        if (badge) badge.classList.add('hidden');
        if (badgeMobile) badgeMobile.classList.add('hidden');
        document.title = 'Vibe — Делись моментами | vibe-app.su';
    }
}

function startMessagePolling() {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(async () => {
        try {
            const res = await fetch('api.php?action=get_unread_count&t=' + Date.now());
            const data = await res.json();
            if (data.success && data.unread_count !== undefined) {
                const newCount = data.unread_count;
                if (newCount > lastUnreadCount && lastUnreadCount >= 0) {
                    playNotificationSound();
                    if (data.last_sender) showToast('Новое сообщение', `От @${data.last_sender}: ${data.last_message || ''}`, () => { window.location.href = '/?page=messages'; });
                    const navMsg = document.getElementById('nav-messages');
                    if (navMsg) { navMsg.classList.add('msg-pulse'); setTimeout(() => navMsg.classList.remove('msg-pulse'), 2000); }
                }
                lastUnreadCount = newCount;
                updateUnreadBadge(newCount);
                if (PAGE_NAME === 'messages') {
                    if (currentConversationUser) await loadConversationMessages(currentConversationUser);
                    else await loadMessages();
                }
            }
        } catch(e) {}
    }, 5000);
}

window.addEventListener('beforeunload', () => { if (pollInterval) clearInterval(pollInterval); });

// ============================================
// АВТОРИЗАЦИЯ
// ============================================
function showAuthModal(mode) {
    document.getElementById('auth-modal').classList.remove('hidden');
    if (mode === 'login') {
        document.getElementById('login-form').classList.remove('hidden');
        document.getElementById('register-form').classList.add('hidden');
    } else {
        document.getElementById('login-form').classList.add('hidden');
        document.getElementById('register-form').classList.remove('hidden');
    }
    document.getElementById('auth-message').textContent = '';
}

function closeAuthModal() { document.getElementById('auth-modal').classList.add('hidden'); }

function toggleAuthMode() {
    document.getElementById('login-form').classList.toggle('hidden');
    document.getElementById('register-form').classList.toggle('hidden');
    document.getElementById('auth-message').textContent = '';
}

async function doLogin() {
    const login = document.getElementById('login-login').value.trim();
    const password = document.getElementById('login-password').value;
    const msg = document.getElementById('auth-message');
    msg.textContent = 'Вход...';
    const res = await fetch('api.php', { method: 'POST', body: new URLSearchParams({action:'login', login, password}) });
    const data = await res.json();
    if (data.success) location.href = '/';
    else { msg.textContent = data.message; msg.className = 'text-center mt-3 text-sm text-red-500 font-bold'; }
}

async function doRegister() {
    const username = document.getElementById('reg-username').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    const msg = document.getElementById('auth-message');
    msg.textContent = 'Регистрация...';
    const res = await fetch('api.php', { method: 'POST', body: new URLSearchParams({action:'register', username, email, password}) });
    const data = await res.json();
    if (data.success) {
        const code = data.activation_code || '------';
        msg.innerHTML = `<div class="text-green-600 font-bold mb-2">✅ Регистрация успешна!</div><div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-left"><div class="text-xs text-amber-800 mb-1">📩 Проверь почту (и папку «Спам»)</div><div class="text-xs text-amber-800 mt-2 font-bold">🔑 Твой код активации:</div><div class="text-2xl font-black text-center my-2 tracking-widest text-amber-900">${code}</div><button onclick="showCodeActivation('${username}')" class="mt-2 w-full bg-amber-500 text-white py-2 rounded-lg text-sm font-bold hover:bg-amber-600 transition">Ввести код активации →</button></div>`;
        msg.className = 'text-center mt-3 text-sm';
    } else {
        msg.textContent = data.message;
        msg.className = 'text-center mt-3 text-sm text-red-500 font-bold';
    }
}

async function logout() {
    await fetch('api.php?action=logout');
    location.href = '/';
}

function showCodeActivation(username) {
    closeAuthModal();
    document.getElementById('code-activation-modal').classList.remove('hidden');
    document.getElementById('code-username').value = username;
    document.getElementById('code-activation-code').value = '';
    document.getElementById('code-activation-message').textContent = '';
}

function closeCodeActivation() { document.getElementById('code-activation-modal').classList.add('hidden'); }

async function submitCodeActivation() {
    const username = document.getElementById('code-username').value.trim();
    const code = document.getElementById('code-activation-code').value.trim();
    const msg = document.getElementById('code-activation-message');
    if (!username || !code) { msg.textContent = 'Заполни все поля'; msg.className = 'text-red-500 text-sm mt-2'; return; }
    msg.textContent = 'Активация...';
    msg.className = 'text-gray-600 text-sm mt-2';
    const res = await fetch('api.php', { method: 'POST', body: new URLSearchParams({action:'activate_by_code', username, code}) });
    const data = await res.json();
    msg.textContent = data.message;
    msg.className = (data.success ? 'text-green-600' : 'text-red-500') + ' text-sm mt-2';
    if (data.success) setTimeout(() => { closeCodeActivation(); showAuthModal('login'); document.getElementById('login-login').value = username; }, 1500);
}

// ============================================
// СОЗДАНИЕ ПОСТА
// ============================================
function handlePostEnter(e) {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        createPost();
    }
}

function handleImagesSelect(input) {
    if (!input.files) return;
    selectedImages = [];
    Array.from(input.files).slice(0, 10).forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
            selectedImages.push(e.target.result);
            if (selectedImages.length === Math.min(input.files.length, 10)) renderImagesPreview();
        };
        reader.readAsDataURL(file);
    });
}

function renderImagesPreview() {
    const container = document.getElementById('images-preview');
    container.innerHTML = '';
    if (selectedImages.length === 0) { container.classList.add('hidden'); return; }
    container.classList.remove('hidden');
    container.className = `photos-grid mt-2 ${selectedImages.length === 1 ? 'cols-1' : (selectedImages.length <= 4 ? 'cols-2' : 'cols-3')}`;
    selectedImages.forEach((src, i) => {
        const div = document.createElement('div');
        div.className = 'relative';
        div.innerHTML = `<img src="${src}"><button onclick="selectedImages.splice(${i},1);renderImagesPreview()" class="absolute top-1 right-1 bg-black/70 text-white rounded-full w-6 h-6">×</button>`;
        container.appendChild(div);
    });
}

function handleVideoSelect(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 50 * 1024 * 1024) { alert('Видео слишком большое. Максимум 50 МБ.'); input.value = ''; return; }
    const reader = new FileReader();
    reader.onload = function(e) {
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.onloadedmetadata = function() {
            if (video.duration > 60) { alert('Видео не должно превышать 1 минуту.'); input.value = ''; return; }
            selectedVideoBase64 = e.target.result;
            document.getElementById('preview-video').src = selectedVideoBase64;
            document.getElementById('video-preview').classList.remove('hidden');
            document.getElementById('images-preview').classList.add('hidden');
            document.getElementById('gif-selected').classList.add('hidden');
            document.getElementById('poll-preview').classList.add('hidden');
            document.getElementById('location-preview').classList.add('hidden');
            selectedImages = [];
            selectedGifUrl = '';
            currentPollData = null;
            selectedLocation = { lat: null, lng: null, name: '' };
        };
        video.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function removeVideo() {
    selectedVideoBase64 = '';
    document.getElementById('video-input').value = '';
    document.getElementById('video-preview').classList.add('hidden');
}

async function createPost() {
    const content = document.getElementById('post-content').value.trim();
    if (!content && selectedImages.length === 0 && !selectedVideoBase64 && !selectedGifUrl && !currentPollData && !selectedLocation.lat) return;
    const btn = document.getElementById('post-btn');
    btn.disabled = true;
    btn.textContent = '...';
    const formData = new URLSearchParams();
    formData.append('action', 'create_post');
    formData.append('content', content);
    formData.append('images', JSON.stringify(selectedImages));
    formData.append('video', selectedVideoBase64);
    formData.append('gif', selectedGifUrl);
    if (selectedLocation.lat) {
        formData.append('location_lat', selectedLocation.lat);
        formData.append('location_lng', selectedLocation.lng);
        formData.append('location_name', selectedLocation.name);
    }
    await fetch('api.php', { method: 'POST', body: formData });

    if (currentPollData) {
        const postsRes = await fetch('api.php?action=get_posts&tab=all');
        const postsData = await postsRes.json();
        if (postsData.posts && postsData.posts.length > 0) {
            const latestPost = postsData.posts[0];
            const pollFormData = new URLSearchParams();
            pollFormData.append('action', 'create_poll');
            pollFormData.append('question', currentPollData.question);
            pollFormData.append('options', JSON.stringify(currentPollData.options));
            pollFormData.append('post_id', latestPost.id);
            await fetch('api.php', { method: 'POST', body: pollFormData });
        }
    }

    document.getElementById('post-content').value = '';
    selectedImages = [];
    selectedVideoBase64 = '';
    selectedGifUrl = '';
    currentPollData = null;
    selectedLocation = { lat: null, lng: null, name: '' };
    if (locationMarker) { locationMap.removeLayer(locationMarker); locationMarker = null; }
    document.getElementById('images-preview').classList.add('hidden');
    document.getElementById('video-preview').classList.add('hidden');
    document.getElementById('gif-selected').classList.add('hidden');
    document.getElementById('poll-preview').classList.add('hidden');
    document.getElementById('location-preview').classList.add('hidden');
    btn.disabled = false;
    btn.textContent = 'Опубликовать';
    loadPosts();
}

// ============================================
// ЛЕНТА ПОСТОВ
// ============================================
async function loadPosts() {
    const res = await fetch(`api.php?action=get_posts&tab=${currentTab}`);
    const data = await res.json();
    const feed = document.getElementById('feed');
    feed.innerHTML = '';
    if (!data.posts || data.posts.length === 0) {
        feed.innerHTML = '<div class="text-center text-gray-500 py-10">Пока ничего нет</div>';
        return;
    }
    data.posts.forEach(post => renderPost(post, data.current_user_id, feed));
}

function renderPost(post, currentUserId, container) {
    const isOwn = post.user_id == currentUserId;
    let mediaHtml = '';
    if (post.images_array && post.images_array.length > 0) {
        const cols = post.images_array.length === 1 ? 'cols-1' : (post.images_array.length <= 4 ? 'cols-2' : 'cols-3');
        mediaHtml = `<div class="photos-grid ${cols}">` + post.images_array.map(src => `<img src="${src}">`).join('') + `</div>`;
    } else if (post.gif) {
        mediaHtml = `<div class="mt-3 relative"><img src="${post.gif}" class="rounded-2xl w-full max-h-96 object-cover" loading="lazy"><div class="gif-badge">GIF</div></div>`;
    } else if (post.video) {
        mediaHtml = `<video src="${post.video}" class="mt-3 rounded-2xl w-full max-h-96" controls></video>`;
    }

    let pollHtml = '';
    if (post.has_poll && post.poll_options) {
        pollHtml = '<div class="mt-3 p-4 bg-gray-50 rounded-xl border border-gray-200">';
        pollHtml += `<div class="font-bold mb-3">${escapeHtml(post.poll_question)}</div>`;
        pollHtml += '<div class="space-y-2">';
        const totalVotes = post.total_votes || 0;
        const hasVoted = post.user_vote !== null;
        post.poll_options.forEach(opt => {
            const isSelected = post.user_vote === opt.id;
            const percentage = opt.percentage || 0;
            if (hasVoted) {
                pollHtml += `<div class="poll-option voted ${isSelected ? 'selected' : ''}"><div class="poll-bar" style="width: ${percentage}%"></div><div class="poll-option-content"><span>${escapeHtml(opt.option_text)}</span><span class="poll-percentage">${percentage}%</span></div></div>`;
            } else {
                pollHtml += `<div class="poll-option" onclick="votePoll(${post.poll_id}, ${opt.id}, this)"><div class="poll-option-content"><span>${escapeHtml(opt.option_text)}</span></div></div>`;
            }
        });
        pollHtml += '</div>';
        pollHtml += `<div class="text-xs text-gray-500 mt-2">${totalVotes} ${totalVotes === 1 ? 'голос' : (totalVotes >= 2 && totalVotes <= 4 ? 'голоса' : 'голосов')}</div>`;
        pollHtml += '</div>';
    }

    let locationHtml = '';
    if (post.has_location && post.location_name) {
        locationHtml = `<div class="location-badge mt-2"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span class="text-blue-600 hover:underline cursor-pointer" onclick="openLocationViewer(${post.location_lat}, ${post.location_lng}, '${escapeHtml(post.location_name)}')">${escapeHtml(post.location_name)}</span></div>`;
    }

    const reportIcon = `<svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.5L19.5 19h-15L12 5.5zM11 10v4h2v-4h-2zm0 6v2h2v-2h-2z"/></svg>`;
    const avatarHtml = post.avatar
        ? `<img src="${post.avatar}" class="w-10 h-10 rounded-full object-cover cursor-pointer" onclick="goToProfile('${escapeHtml(post.username)}')">`
        : `<div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold cursor-pointer" onclick="goToProfile('${escapeHtml(post.username)}')">${escapeHtml(post.username.charAt(0).toUpperCase())}</div>`;

    let commentsHtml = '';
    if (post.comments && post.comments.length > 0) {
        commentsHtml = '<div class="mt-3 space-y-2 border-t border-gray-100 pt-3">';
        post.comments.forEach(c => {
            const cAvatar = c.avatar
                ? `<img src="${c.avatar}" class="w-8 h-8 rounded-full object-cover">`
                : `<div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center text-white text-xs font-bold">${c.username.charAt(0).toUpperCase()}</div>`;
            commentsHtml += `<div class="flex gap-2">${cAvatar}<div class="flex-1 bg-gray-50 rounded-2xl px-3 py-2"><div class="text-sm"><span class="font-bold">${escapeHtml(c.username)}</span></div><div class="text-sm text-gray-800 md-content">${parseMarkdown(c.content)}</div></div></div>`;
        });
        commentsHtml += '</div>';
    }

    const commentInputHtml = IS_LOGGED_IN ? `<div class="mt-3 flex gap-2 items-center border-t border-gray-100 pt-3"><input type="text" id="comment-input-${post.id}" placeholder="Написать комментарий..." class="comment-input flex-1" onkeydown="if(event.key==='Enter')addComment(${post.id})"><button onclick="addComment(${post.id})" class="text-blue-500 font-bold text-sm hover:text-blue-700">→</button></div>` : '';

    const div = document.createElement('div');
    div.className = 'p-4 hover:bg-gray-50';
    div.innerHTML = `<div class="flex gap-3"><div class="shrink-0">${avatarHtml}</div><div class="flex-1"><div class="flex items-center gap-2"><a href="/?page=profile&user=${escapeHtml(post.username)}" class="font-bold hover:underline">${escapeHtml(post.username)}</a>${getUserBadges(post)}<span class="text-gray-400 text-sm">· ${new Date(post.created_at).toLocaleDateString('ru-RU')}</span></div><p class="mt-1 md-content">${parseMarkdown(post.content)}</p>${mediaHtml}${locationHtml}${pollHtml}<div class="flex gap-4 mt-3"><button onclick="likePost(${post.id}, this)" class="post-btn-action ${post.is_liked ? 'liked' : ''}"><svg class="w-5 h-5" fill="${post.is_liked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg><span>${post.likes}</span></button><button onclick="openShareModal(${post.id})" class="post-btn-action"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg></button>${!isOwn ? `<button onclick="openReportModal(${post.id})" class="post-btn-action" title="Пожаловаться">${reportIcon}</button>` : ''}</div>${commentsHtml}${commentInputHtml}</div></div>`;
    container.appendChild(div);
}

async function votePoll(pollId, optionId, element) {
    if (!IS_LOGGED_IN) { showAuthModal('login'); return; }
    if (element.classList.contains('voted')) return;
    const formData = new URLSearchParams({action:'vote_poll', poll_id:pollId, option_id:optionId});
    const res = await fetch('api.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) { loadPosts(); }
    else { alert(data.message || 'Ошибка голосования'); }
}

async function addComment(postId) {
    const input = document.getElementById(`comment-input-${postId}`);
    if (!input) return;
    const content = input.value.trim();
    if (!content) return;
    const res = await fetch('api.php', { method: 'POST', body: new URLSearchParams({action:'add_comment', post_id:postId, content}) });
    const data = await res.json();
    if (data.success) { input.value = ''; loadPosts(); }
}

async function likePost(postId, btn) {
    const res = await fetch('api.php', { method: 'POST', body: new URLSearchParams({action:'like', post_id:postId}) });
    const data = await res.json();
    if (data.success) {
        const svg = btn.querySelector('svg'), count = btn.querySelector('span');
        if (data.liked) { btn.classList.add('liked'); svg.setAttribute('fill', 'currentColor'); count.textContent = parseInt(count.textContent) + 1; }
        else { btn.classList.remove('liked'); svg.setAttribute('fill', 'none'); count.textContent = Math.max(0, parseInt(count.textContent) - 1); }
    }
}

function switchTab(tab) {
    currentTab = tab;
    document.getElementById('tab-all').className = tab === 'all' ? 'flex-1 tab-btn active' : 'flex-1 tab-btn';
    const tf = document.getElementById('tab-following');
    if (tf) tf.className = tab === 'following' ? 'flex-1 tab-btn active' : 'flex-1 tab-btn';
    loadPosts();
}

function openReportModal(id) {
    if (!IS_LOGGED_IN) { showAuthModal('login'); return; }
    currentReportPostId = id;
    document.getElementById('report-modal').classList.remove('hidden');
    document.getElementById('submit-report-btn').disabled = true;
}

function closeReportModal() { document.getElementById('report-modal').classList.add('hidden'); }

function selectReason(r, btn) {
    selectedReason = r;
    document.querySelectorAll('#report-reasons button').forEach(b => b.classList.remove('bg-gray-100'));
    btn.classList.add('bg-gray-100');
    document.getElementById('submit-report-btn').disabled = false;
}

async function submitReport() {
    const res = await fetch('api.php', { method: 'POST', body: new URLSearchParams({action:'report_post', post_id:currentReportPostId, reason:selectedReason}) });
    const data = await res.json();
    if (data.success) { closeReportModal(); alert('Отправлено'); }
    else alert(data.message);
}

function openShareModal(id) {
    currentSharePostId = id;
    const url = `https://vibe-app.su/?post=${id}`;
    document.getElementById('share-content').innerHTML = `<div class="space-y-2"><label class="text-sm font-medium">Ссылка на пост</label><div class="flex gap-2"><input type="text" value="${url}" readonly class="flex-1 p-2 border rounded text-sm"><button onclick="navigator.clipboard.writeText('${url}');alert('Скопировано!')" class="bg-black text-white px-4 py-2 rounded text-sm">Копировать</button></div></div><div class="space-y-2 mt-4"><label class="text-sm font-medium">Код для встраивания (iframe)</label><textarea readonly class="w-full p-3 border rounded text-xs bg-gray-50 h-24 font-mono" onclick="this.select(); navigator.clipboard.writeText(this.value); alert('Код скопирован!')">&lt;iframe src="https://vibe-app.su/?post=${id}" width="100%" height="300" frameborder="0" style="border-radius: 12px; border: 1px solid #e5e7eb;"&gt;&lt;/iframe&gt;</textarea></div>`;
    document.getElementById('share-modal').classList.remove('hidden');
}

function closeShareModal() { document.getElementById('share-modal').classList.add('hidden'); }

function openNewPost() {
    document.getElementById('post-content').focus();
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function doSearch(q) {
    if(q.length < 2) document.getElementById('search-results').innerHTML = '';
}

// ============================================
// ПРОФИЛЬ
// ============================================
async function loadProfile() {
    const res = await fetch(`api.php?action=get_profile&username=${encodeURIComponent(USERNAME_PARAM)}`);
    const data = await res.json();
    if (!data.success) { document.getElementById('profile-content').innerHTML = '<div class="text-center py-10 text-gray-500">Пользователь не найден</div>'; return; }
    const u = data.user;
    let avatarHtml;
    if (u.avatar) {
        const clickHandler = u.is_own ? `onclick="openAvatarModal()"` : '';
        const wrapperClass = u.is_own ? 'profile-avatar-wrapper' : '';
        avatarHtml = `<div class="${wrapperClass}" ${clickHandler}><img src="${u.avatar}" class="w-20 h-20 rounded-full object-cover shrink-0"></div>`;
    } else {
        const clickHandler = u.is_own ? `onclick="openAvatarModal()"` : '';
        const wrapperClass = u.is_own ? 'profile-avatar-wrapper' : '';
        avatarHtml = `<div class="${wrapperClass}" ${clickHandler}><div class="w-20 h-20 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-2xl shrink-0">${(u.full_name || u.username).charAt(0).toUpperCase()}</div></div>`;
    }
    const displayName = u.full_name || u.username;
    const badges = getUserBadges(u);
    const isOwn = u.is_own;
    let socialLinksHtml = '';
    try {
        const links = JSON.parse(u.social_links || '[]');
        if (Array.isArray(links) && links.length > 0) {
            socialLinksHtml = '<div class="flex flex-col gap-1 mt-3">';
            links.forEach(link => { if (link && link.trim()) socialLinksHtml += `<a href="${escapeHtml(link)}" target="_blank" rel="nofollow noopener" class="text-blue-500 hover:underline text-sm break-all">${escapeHtml(link)}</a>`; });
            socialLinksHtml += '</div>';
        }
    } catch(e) {}
    let actionButtons = '';
    if (isOwn) {
        actionButtons = `<div class="flex gap-2 mb-6 flex-wrap"><button onclick="openEditProfile()" class="flex-1 py-2 rounded-lg border border-gray-300 font-bold text-sm">Редактировать профиль</button>${u.verified != 1 ? `<button onclick="openVerificationModal()" class="flex-1 py-2 rounded-lg border border-blue-300 text-blue-600 font-bold text-sm hover:bg-blue-50">Запросить верификацию</button>` : ''}</div>`;
    } else {
        actionButtons = `<div class="flex gap-2 mb-6 flex-wrap"><button onclick="toggleFollowProfile(${u.id}, this)" class="flex-1 py-2 rounded-lg ${u.is_following ? 'bg-gray-100 text-black' : 'bg-black text-white'} font-bold text-sm">${u.is_following ? 'Отписаться' : 'Подписаться'}</button><button onclick="openMessageModal('${escapeHtml(u.username)}')" class="flex-1 py-2 rounded-lg border border-gray-300 font-bold text-sm flex items-center justify-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>Написать</button></div>`;
    }
    document.getElementById('profile-content').innerHTML = `<div class="flex items-start justify-between mb-6"><div class="flex-1"><h2 class="text-2xl font-bold">${escapeHtml(displayName)} ${badges}</h2><p class="text-gray-500">@${escapeHtml(u.username)}</p>${u.bio ? `<p class="text-gray-800 mt-2 md-content">${parseMarkdown(u.bio)}</p>` : ''}${socialLinksHtml}</div>${avatarHtml}</div><div class="flex gap-4 text-sm text-gray-500 mb-4"><span><strong class="text-black">${u.posts_count || 0}</strong> публикаций</span><span><strong class="text-black">${u.following_count || 0}</strong> подписок</span><span><strong class="text-black">${u.followers_count || 0}</strong> подписчиков</span></div>${actionButtons}<div class="flex border-b border-gray-200"><button onclick="switchProfileTab('threads')" id="ptab-threads" class="tab-btn active flex-1">Публикации</button><button onclick="switchProfileTab('replies')" id="ptab-replies" class="tab-btn flex-1">Ответы</button><button onclick="switchProfileTab('media')" id="ptab-media" class="tab-btn flex-1">Медиа</button></div><div id="profile-posts" class="divide-y divide-gray-100 mt-2"><div class="text-center text-gray-500 py-6">Загрузка...</div></div>`;
    loadProfilePosts();
}

async function loadProfilePosts() {
    try {
        const res = await fetch(`api.php?action=get_user_posts&username=${encodeURIComponent(USERNAME_PARAM)}&tab=${profileTab}`);
        const data = await res.json();
        const container = document.getElementById('profile-posts');
        container.innerHTML = '';
        if (!data.success || !data.posts || data.posts.length === 0) { container.innerHTML = '<div class="text-center text-gray-500 py-6">Ничего нет</div>'; return; }
        data.posts.forEach(post => renderPost(post, null, container));
    } catch(e) { console.error(e); }
}

function switchProfileTab(tab) {
    profileTab = tab;
    ['threads','replies','media'].forEach(t => {
        const el = document.getElementById(`ptab-${t}`);
        if (el) el.className = t === tab ? 'tab-btn active flex-1' : 'tab-btn flex-1';
    });
    loadProfilePosts();
}

async function toggleFollowProfile(targetId, btn) {
    const formData = new URLSearchParams({action:'toggle_follow', target_id:targetId});
    const res = await fetch('api.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
        if (data.action === 'follow') { btn.textContent = 'Отписаться'; btn.className = 'flex-1 py-2 rounded-lg bg-gray-100 text-black font-bold text-sm'; }
        else { btn.textContent = 'Подписаться'; btn.className = 'flex-1 py-2 rounded-lg bg-black text-white font-bold text-sm'; }
        loadProfile();
    }
}

function goToProfile(username) { window.location.href = `/?page=profile&user=${encodeURIComponent(username)}`; }

// ============================================
// EMOJI
// ============================================
function openEmojiModal() {
    if (!IS_LOGGED_IN) return;
    document.getElementById('emoji-modal').classList.remove('hidden');
    document.getElementById('emoji-search').value = '';
    emojiSearchQuery = '';
    currentEmojiCategory = 'smileys';
    renderEmojiGrid();
    setTimeout(() => document.getElementById('emoji-search').focus(), 100);
}

function closeEmojiModal() { document.getElementById('emoji-modal').classList.add('hidden'); }

function switchEmojiCategory(category) {
    currentEmojiCategory = category;
    emojiSearchQuery = '';
    document.getElementById('emoji-search').value = '';
    document.querySelectorAll('.emoji-tab').forEach(tab => { tab.classList.toggle('active', tab.dataset.category === category); });
    renderEmojiGrid();
}

function renderEmojiGrid() {
    const grid = document.getElementById('emoji-grid');
    grid.innerHTML = '';
    const emojisToShow = EMOJI_DATA[currentEmojiCategory].emojis;
    const title = document.createElement('div');
    title.className = 'col-span-8 emoji-category-title';
    title.textContent = EMOJI_DATA[currentEmojiCategory].title;
    grid.appendChild(title);
    emojisToShow.forEach(emoji => {
        const btn = document.createElement('div');
        btn.className = 'emoji-btn';
        btn.textContent = emoji;
        btn.onclick = () => insertEmoji(emoji);
        grid.appendChild(btn);
    });
}

function filterEmojis(query) {
    emojiSearchQuery = query.toLowerCase().trim();
    renderEmojiGrid();
}

function insertEmoji(emoji) {
    const textarea = document.getElementById('post-content');
    if (!textarea) return;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + emoji + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
    closeEmojiModal();
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('emoji-modal');
    if (modal && !modal.classList.contains('hidden') && !modal.contains(e.target) && !e.target.closest('[onclick*="openEmojiModal"]')) {
        closeEmojiModal();
    }
});

// ============================================
// POLL (ОПРОСЫ)
// ============================================
function openPollModal() {
    if (!IS_LOGGED_IN) return;
    document.getElementById('poll-modal').classList.remove('hidden');
    document.getElementById('poll-question').value = '';
    document.getElementById('poll-message').textContent = '';
    pollOptions = ['', ''];
    renderPollOptions();
}

function closePollModal() { document.getElementById('poll-modal').classList.add('hidden'); }

function renderPollOptions() {
    const container = document.getElementById('poll-options-container');
    container.innerHTML = '';
    pollOptions.forEach((opt, i) => {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'poll-option-input w-full p-3 border border-gray-300 rounded-xl outline-none focus:border-black';
        input.placeholder = `Вариант ${i + 1}`;
        input.value = opt;
        input.maxLength = 50;
        input.oninput = (e) => { pollOptions[i] = e.target.value; };
        container.appendChild(input);
    });
    const addBtn = document.getElementById('add-option-btn');
    addBtn.style.display = pollOptions.length >= 4 ? 'none' : 'block';
}

function addPollOption() {
    if (pollOptions.length < 4) { pollOptions.push(''); renderPollOptions(); }
}

function createPoll() {
    const question = document.getElementById('poll-question').value.trim();
    const msg = document.getElementById('poll-message');
    if (question.length < 3) { msg.textContent = 'Вопрос должен содержать минимум 3 символа'; msg.className = 'text-red-500 text-sm mt-2'; return; }
    const validOptions = pollOptions.filter(o => o.trim().length > 0);
    if (validOptions.length < 2) { msg.textContent = 'Нужно минимум 2 варианта ответа'; msg.className = 'text-red-500 text-sm mt-2'; return; }
    currentPollData = { question, options: validOptions };
    document.getElementById('poll-preview-question').textContent = question;
    document.getElementById('poll-preview-options').innerHTML = validOptions.map((opt, i) => `<div class="text-sm">${i + 1}. ${escapeHtml(opt)}</div>`).join('');
    document.getElementById('poll-preview').classList.remove('hidden');
    closePollModal();
    msg.textContent = '';
}

function removePoll() {
    currentPollData = null;
    document.getElementById('poll-preview').classList.add('hidden');
}

// ============================================
// GIF
// ============================================
function openGifModal() {
    if (!IS_LOGGED_IN) return;
    document.getElementById('gif-modal').classList.remove('hidden');
    document.getElementById('gif-search').value = '';
    document.getElementById('gif-results').innerHTML = '<div class="col-span-3 text-center text-gray-400 py-8">Начните вводить запрос для поиска GIF</div>';
}

function closeGifModal() { document.getElementById('gif-modal').classList.add('hidden'); }

function searchGifs(query) {
    clearTimeout(gifSearchTimeout);
    if (query.length < 2) { document.getElementById('gif-results').innerHTML = '<div class="col-span-3 text-center text-gray-400 py-8">Введите минимум 2 символа</div>'; return; }
    gifSearchTimeout = setTimeout(async () => {
        const results = document.getElementById('gif-results');
        results.innerHTML = '<div class="col-span-3 text-center text-gray-400 py-8">Загрузка...</div>';
        try {
            const res = await fetch(`https://api.giphy.com/v1/gifs/search?api_key=${GIPHY_API_KEY}&q=${encodeURIComponent(query)}&limit=21&rating=g`);
            const data = await res.json();
            if (!data.data || data.data.length === 0) { results.innerHTML = '<div class="col-span-3 text-center text-gray-400 py-8">Ничего не найдено</div>'; return; }
            results.innerHTML = '';
            data.data.forEach(gif => {
                const div = document.createElement('div');
                div.className = 'relative cursor-pointer group overflow-hidden rounded-lg';
                div.innerHTML = `<img src="${gif.images.fixed_height_small.url}" class="w-full h-24 object-cover" loading="lazy"><div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition rounded-lg flex items-center justify-center"><svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>`;
                div.onclick = () => selectGif(gif.images.original.url, gif.title || 'GIF');
                results.appendChild(div);
            });
        } catch(e) { results.innerHTML = '<div class="col-span-3 text-center text-red-500 py-8">Ошибка загрузки GIF</div>'; }
    }, 500);
}

function selectGif(url, title) {
    selectedGifUrl = url;
    document.getElementById('gif-selected-img').src = url;
    document.getElementById('gif-selected').classList.remove('hidden');
    setTimeout(() => closeGifModal(), 300);
}

function removeSelectedGif() {
    selectedGifUrl = '';
    document.getElementById('gif-selected').classList.add('hidden');
    document.getElementById('gif-selected-img').src = '';
}

// ============================================
// РЕДАКТОР ТЕКСТА
// ============================================
function openEditorModal() {
    if (!IS_LOGGED_IN) return;
    document.getElementById('editor-modal').classList.remove('hidden');
    const postContent = document.getElementById('post-content');
    const editorTextarea = document.getElementById('editor-textarea');
    if (postContent.value.trim()) {
        editorTextarea.value = postContent.value;
        postContent.value = '';
    } else {
        editorTextarea.value = '';
    }
    setTimeout(() => editorTextarea.focus(), 100);
}

function closeEditorModal() { document.getElementById('editor-modal').classList.add('hidden'); }

function formatText(type) {
    const textarea = document.getElementById('editor-textarea');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const selectedText = text.substring(start, end);
    const content = selectedText || (type === 'heading' ? 'Заголовок' : 'текст');

    let formattedText = '';
    let cursorOffset = 0;

    switch(type) {
        case 'bold': formattedText = `**${content}**`; cursorOffset = 2; break;
        case 'italic': formattedText = `*${content}*`; cursorOffset = 1; break;
        case 'underline': formattedText = `__${content}__`; cursorOffset = 2; break;
        case 'strikethrough': formattedText = `~~${content}~~`; cursorOffset = 2; break;
        case 'heading': formattedText = `# ${content}`; cursorOffset = 0; break;
    }

    textarea.value = text.substring(0, start) + formattedText + text.substring(end);
    textarea.focus();

    if (!selectedText) {
        const contentStart = start + (type === 'heading' ? 2 : (type === 'italic' ? 1 : 2));
        textarea.selectionStart = contentStart;
        textarea.selectionEnd = contentStart + content.length;
    } else {
        textarea.selectionStart = textarea.selectionEnd = start + formattedText.length - cursorOffset;
    }
}

function saveEditorText() {
    const editorText = document.getElementById('editor-textarea').value;
    const postContent = document.getElementById('post-content');
    if (postContent.value.trim() && editorText.trim()) {
        postContent.value = postContent.value.trimEnd() + '\n\n' + editorText;
    } else {
        postContent.value = editorText;
    }
    postContent.focus();
    closeEditorModal();
}

// ============================================
// ГЕОЛОКАЦИЯ
// ============================================
function openLocationModal() {
    if (!IS_LOGGED_IN) return;
    document.getElementById('location-modal').classList.remove('hidden');
    document.getElementById('location-search').value = '';
    document.getElementById('location-search-results').classList.add('hidden');
    document.getElementById('location-message').textContent = '';

    setTimeout(() => {
        if (!locationMap) {
            locationMap = L.map('location-map').setView([55.7558, 37.6173], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 19
            }).addTo(locationMap);

            locationMap.on('click', function(e) {
                setLocationMarker(e.latlng.lat, e.latlng.lng);
            });
        } else {
            locationMap.invalidateSize();
        }

        if (selectedLocation.lat && selectedLocation.lng) {
            locationMap.setView([selectedLocation.lat, selectedLocation.lng], 14);
            setLocationMarker(selectedLocation.lat, selectedLocation.lng, false);
        }
    }, 100);
}

function closeLocationModal() { document.getElementById('location-modal').classList.add('hidden'); }

function setLocationMarker(lat, lng, reverseGeocode = true) {
    selectedLocation.lat = lat;
    selectedLocation.lng = lng;

    if (locationMarker) {
        locationMarker.setLatLng([lat, lng]);
    } else {
        locationMarker = L.marker([lat, lng], { draggable: true }).addTo(locationMap);
        locationMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            selectedLocation.lat = pos.lat;
            selectedLocation.lng = pos.lng;
            reverseGeocodeLocation(pos.lat, pos.lng);
        });
    }

    if (reverseGeocode) {
        reverseGeocodeLocation(lat, lng);
    }
}

async function reverseGeocodeLocation(lat, lng) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
        const data = await res.json();
        if (data && data.display_name) {
            const addr = data.address || {};
            let shortName = '';
            if (addr.city || addr.town || addr.village) {
                shortName = addr.city || addr.town || addr.village;
            } else if (addr.state) {
                shortName = addr.state;
            }
            if (addr.road) {
                shortName += (shortName ? ', ' : '') + addr.road;
                if (addr.house_number) shortName += ' ' + addr.house_number;
            }
            if (!shortName) shortName = data.display_name.split(',').slice(0, 2).join(',').trim();

            selectedLocation.name = shortName;
            document.getElementById('location-message').textContent = '📍 ' + shortName;
        }
    } catch(e) { console.error('Geocode error:', e); }
}

function searchLocation(query) {
    clearTimeout(locationSearchTimeout);
    const resultsDiv = document.getElementById('location-search-results');

    if (query.length < 3) { resultsDiv.classList.add('hidden'); return; }

    locationSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`);
            const data = await res.json();

            if (data.length === 0) {
                resultsDiv.innerHTML = '<div class="p-3 text-sm text-gray-500">Ничего не найдено</div>';
                resultsDiv.classList.remove('hidden');
                return;
            }

            resultsDiv.innerHTML = '';
            data.forEach(place => {
                const div = document.createElement('div');
                div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0';
                div.innerHTML = `<div class="font-medium text-sm">${place.display_name.split(',').slice(0, 3).join(',')}</div><div class="text-xs text-gray-500">${place.type}</div>`;
                div.onclick = () => {
                    const lat = parseFloat(place.lat);
                    const lng = parseFloat(place.lon);
                    locationMap.setView([lat, lng], 15);
                    setLocationMarker(lat, lng, false);
                    selectedLocation.name = place.display_name.split(',').slice(0, 3).join(',').trim();
                    resultsDiv.classList.add('hidden');
                    document.getElementById('location-search').value = selectedLocation.name;
                };
                resultsDiv.appendChild(div);
            });
            resultsDiv.classList.remove('hidden');
        } catch(e) { console.error('Search error:', e); }
    }, 500);
}

function saveLocation() {
    if (!selectedLocation.lat || !selectedLocation.lng) {
        document.getElementById('location-message').textContent = '⚠️ Выберите место на карте';
        document.getElementById('location-message').className = 'text-sm mt-2 text-red-500';
        return;
    }
    document.getElementById('location-preview-name').textContent = selectedLocation.name || 'Выбранное место';
    document.getElementById('location-preview').classList.remove('hidden');
    closeLocationModal();
}

function removeLocation() {
    selectedLocation = { lat: null, lng: null, name: '' };
    if (locationMarker) { locationMap.removeLayer(locationMarker); locationMarker = null; }
    document.getElementById('location-preview').classList.add('hidden');
}

function openLocationViewer(lat, lng, name) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl relative">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold">📍 ${escapeHtml(name)}</h2>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-400 text-2xl">&times;</button>
                </div>
                <div id="viewer-map" style="height: 300px; border-radius: 12px;"></div>
                <a href="https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=15/${lat}/${lng}" target="_blank" class="block mt-3 text-center text-blue-600 hover:underline text-sm">Открыть в OpenStreetMap →</a>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    setTimeout(() => {
        const map = L.map('viewer-map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);
        L.marker([lat, lng]).addTo(map);
    }, 100);

    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
}

// ============================================
// СООБЩЕНИЯ
// ============================================
function openMessageModal(username) {
    if (!IS_LOGGED_IN) { showAuthModal('login'); return; }
    msgRecipientUsername = username;
    document.getElementById('msg-recipient-name').textContent = '@' + username;
    document.getElementById('msg-content').value = '';
    document.getElementById('msg-message').textContent = '';
    document.getElementById('message-modal').classList.remove('hidden');
}

function closeMessageModal() { document.getElementById('message-modal').classList.add('hidden'); }

async function sendMessage() {
    const content = document.getElementById('msg-content').value.trim();
    const msg = document.getElementById('msg-message');
    if (!content) { msg.textContent = 'Введите сообщение'; msg.className = 'text-red-500 text-sm mt-2'; return; }
    if (content.length < 2) { msg.textContent = 'Сообщение слишком короткое'; msg.className = 'text-red-500 text-sm mt-2'; return; }
    msg.textContent = 'Отправка...';
    msg.className = 'text-gray-600 text-sm mt-2';
    const formData = new URLSearchParams({action:'send_message', username: msgRecipientUsername, content});
    const res = await fetch('api.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) { msg.textContent = '✅ Сообщение отправлено!'; msg.className = 'text-green-600 text-sm mt-2'; setTimeout(() => { closeMessageModal(); }, 1000); }
    else { msg.textContent = data.message || 'Ошибка отправки'; msg.className = 'text-red-500 text-sm mt-2'; }
}

async function loadMessages() {
    const container = document.getElementById('messages-content');
    if (!container) return;
    try {
        const res = await fetch('api.php?action=get_conversations');
        const data = await res.json();
        if (!data.success || !data.conversations || data.conversations.length === 0) {
            container.innerHTML = `<div class="text-center py-16"><svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg><div class="text-gray-500 font-medium">У вас пока нет диалогов</div><div class="text-gray-400 text-sm mt-1">Найдите пользователя и нажмите "Написать"</div></div>`;
            return;
        }
        let html = '<div class="divide-y divide-gray-100">';
        data.conversations.forEach(conv => {
            const avatarHtml = conv.other_avatar ? `<img src="${conv.other_avatar}" class="w-12 h-12 rounded-full object-cover">` : `<div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">${(conv.other_fullname || conv.other_username).charAt(0).toUpperCase()}</div>`;
            const lastMsg = conv.last_message ? escapeHtml(conv.last_message.substring(0, 50)) + (conv.last_message.length > 50 ? '...' : '') : 'Нет сообщений';
            const time = conv.last_message_time ? formatMessageTime(conv.last_message_time) : '';
            const unreadBadge = conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : '';
            html += `<div class="dialog-item" onclick="openConversation('${escapeHtml(conv.other_username)}')">${avatarHtml}<div class="flex-1 min-w-0"><div class="flex items-center justify-between"><div class="font-bold text-sm">${escapeHtml(conv.other_fullname || conv.other_username)}</div><div class="text-xs text-gray-400">${time}</div></div><div class="flex items-center justify-between mt-1"><div class="text-sm text-gray-500 truncate">${lastMsg}</div>${unreadBadge}</div></div></div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    } catch(e) { container.innerHTML = '<div class="text-center py-10 text-red-500">Ошибка загрузки сообщений</div>'; }
}

async function openConversation(username) {
    currentConversationUser = username;
    const container = document.getElementById('messages-content');
    container.innerHTML = `<div class="flex flex-col" style="height: calc(100vh - 140px);"><div class="flex items-center gap-3 pb-3 border-b border-gray-200 mb-3"><button onclick="loadMessages()" class="text-2xl">←</button><div class="font-bold">${escapeHtml(username)}</div></div><div id="conversation-messages" class="flex-1 overflow-y-auto space-y-2 pb-3"><div class="text-center text-gray-400 py-4">Загрузка...</div></div><div class="flex gap-2 pt-3 border-t border-gray-200"><input type="text" id="conv-input" placeholder="Написать сообщение..." class="flex-1 p-3 border border-gray-300 rounded-xl outline-none" onkeydown="if(event.key==='Enter')sendConvMessage()"><button onclick="sendConvMessage()" class="bg-black text-white px-5 py-3 rounded-xl font-bold">→</button></div></div>`;
    await loadConversationMessages(username);
}

async function loadConversationMessages(username) {
    const container = document.getElementById('conversation-messages');
    if (!container) return;
    try {
        const res = await fetch(`api.php?action=get_messages&username=${encodeURIComponent(username)}`);
        const data = await res.json();
        if (!data.success || !data.messages) { container.innerHTML = '<div class="text-center text-gray-400 py-4">Ошибка загрузки</div>'; return; }
        if (data.messages.length === 0) { container.innerHTML = '<div class="text-center text-gray-400 py-4">Начните диалог первым!</div>'; return; }
        let html = '';
        data.messages.forEach(m => {
            const isSent = m.sender_id == CURRENT_USER_ID;
            const time = formatMessageTime(m.created_at);
            html += `<div class="flex ${isSent ? 'justify-end' : 'justify-start'}"><div class="msg-bubble ${isSent ? 'sent' : 'received'}"><div>${escapeHtml(m.content)}</div><div class="msg-time">${time}</div></div></div>`;
        });
        container.innerHTML = html;
        container.scrollTop = container.scrollHeight;
    } catch(e) { container.innerHTML = '<div class="text-center text-red-500 py-4">Ошибка</div>'; }
}

async function sendConvMessage() {
    const input = document.getElementById('conv-input');
    if (!input) return;
    const content = input.value.trim();
    if (!content || !currentConversationUser) return;
    const formData = new URLSearchParams({action:'send_message', username: currentConversationUser, content});
    const res = await fetch('api.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) { input.value = ''; await loadConversationMessages(currentConversationUser); }
    else { alert(data.message || 'Ошибка отправки'); }
}

// ============================================
// РЕДАКТИРОВАНИЕ ПРОФИЛЯ
// ============================================
function openEditProfile() {
    document.getElementById('edit-fullname').value = PROFILE_DATA.full_name;
    document.getElementById('edit-bio').value = PROFILE_DATA.bio;
    document.getElementById('edit-social-links').value = PROFILE_DATA.social_links;
    document.getElementById('edit-new-password').value = '';
    document.getElementById('edit-confirm-password').value = '';
    document.getElementById('edit-profile-modal').classList.remove('hidden');
}

function closeEditProfile() { document.getElementById('edit-profile-modal').classList.add('hidden'); }

async function saveProfile() {
    const full_name = document.getElementById('edit-fullname').value.trim();
    const bio = document.getElementById('edit-bio').value.trim();
    const social_links_raw = document.getElementById('edit-social-links').value.trim();
    const new_password = document.getElementById('edit-new-password').value;
    const confirm_password = document.getElementById('edit-confirm-password').value;
    if (new_password.length > 0 || confirm_password.length > 0) {
        if (new_password.length < 4) { alert('Пароль должен содержать минимум 4 символа'); return; }
        if (new_password !== confirm_password) { alert('Пароли не совпадают'); return; }
    }
    const links = social_links_raw.split('\n').map(s => s.trim()).filter(s => s.length > 0).slice(0, 5);
    const formData = new URLSearchParams({action:'update_profile', full_name, bio, social_links: JSON.stringify(links), new_password});
    const res = await fetch('api.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) { alert(data.message || 'Сохранено'); closeEditProfile(); location.reload(); }
    else { alert(data.message || 'Ошибка'); }
}

// ============================================
// АВАТАР
// ============================================
function openAvatarModal() {
    if (!IS_LOGGED_IN) return;
    document.getElementById('avatar-modal').classList.remove('hidden');
    document.getElementById('avatar-message').textContent = '';
    document.getElementById('avatar-input').value = '';
    document.getElementById('avatar-preview').classList.add('hidden');
}

function closeAvatarModal() { document.getElementById('avatar-modal').classList.add('hidden'); }

async function uploadAvatar() {
    const input = document.getElementById('avatar-input');
    if (!input.files || !input.files[0]) { document.getElementById('avatar-message').textContent = 'Выберите файл'; document.getElementById('avatar-message').className = 'text-red-500 text-sm mb-3'; return; }
    const file = input.files[0];
    if (file.size > 10 * 1024 * 1024) { document.getElementById('avatar-message').textContent = 'Файл слишком большой. Максимум 10 МБ.'; document.getElementById('avatar-message').className = 'text-red-500 text-sm mb-3'; return; }
    const msg = document.getElementById('avatar-message');
    msg.textContent = 'Загрузка...';
    msg.className = 'text-gray-600 text-sm mb-3';
    const reader = new FileReader();
    reader.onload = async function(e) {
        const formData = new URLSearchParams({action:'upload_avatar', avatar:e.target.result});
        try {
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) { msg.textContent = 'Аватар обновлён!'; msg.className = 'text-green-600 text-sm mb-3'; setTimeout(() => { closeAvatarModal(); location.reload(); }, 1000); }
            else { msg.textContent = data.message || 'Ошибка'; msg.className = 'text-red-500 text-sm mb-3'; }
        } catch(err) { msg.textContent = 'Ошибка сети'; msg.className = 'text-red-500 text-sm mb-3'; }
    };
    reader.readAsDataURL(file);
}

// ============================================
// ВЕРИФИКАЦИЯ
// ============================================
function openVerificationModal() {
    if (!IS_LOGGED_IN) { showAuthModal('login'); return; }
    document.getElementById('verification-modal').classList.remove('hidden');
    document.getElementById('verification-message').textContent = '';
    document.getElementById('ver-first-name').value = '';
    document.getElementById('ver-last-name').value = '';
    document.getElementById('ver-middle-name').value = '';
    document.getElementById('ver-docs-input').value = '';
    selectedDocs = [];
    renderDocsPreview();
}

function closeVerificationModal() { document.getElementById('verification-modal').classList.add('hidden'); }

function handleDocsSelect(input) {
    if (!input.files) return;
    const files = Array.from(input.files).slice(0, 5);
    let processed = 0;
    files.forEach(file => {
        if (file.size > 10 * 1024 * 1024) { document.getElementById('verification-message').textContent = `Файл "${file.name}" слишком большой (макс. 10 МБ)`; document.getElementById('verification-message').className = 'text-red-500 text-sm mt-2'; return; }
        const reader = new FileReader();
        reader.onload = function(e) { selectedDocs.push({ name: file.name, data: e.target.result }); processed++; if (processed === files.length) renderDocsPreview(); };
        reader.readAsDataURL(file);
    });
    input.value = '';
}

function renderDocsPreview() {
    const container = document.getElementById('docs-preview');
    container.innerHTML = '';
    selectedDocs.forEach((doc, i) => {
        const div = document.createElement('div');
        div.className = 'doc-preview';
        div.innerHTML = `<img src="${doc.data}"><button onclick="selectedDocs.splice(${i},1);renderDocsPreview()" class="remove-doc">×</button>`;
        container.appendChild(div);
    });
}

async function submitVerification() {
    const firstName = document.getElementById('ver-first-name').value.trim();
    const lastName = document.getElementById('ver-last-name').value.trim();
    const middleName = document.getElementById('ver-middle-name').value.trim();
    const msg = document.getElementById('verification-message');
    if (!firstName || !lastName || !middleName) { msg.textContent = 'Заполните все текстовые поля'; msg.className = 'text-red-500 text-sm mt-2'; return; }
    if (selectedDocs.length === 0) { msg.textContent = 'Прикрепите минимум 1 документ'; msg.className = 'text-red-500 text-sm mt-2'; return; }
    msg.textContent = 'Отправка заявки...';
    msg.className = 'text-gray-600 text-sm mt-2';
    const formData = new URLSearchParams({ action: 'submit_verification', first_name: firstName, last_name: lastName, middle_name: middleName, documents: JSON.stringify(selectedDocs.map(d => d.data)) });
    const res = await fetch('api.php', { method: 'POST', body: formData });
    const data = await res.json();
    msg.textContent = data.message;
    msg.className = (data.success ? 'text-green-600' : 'text-red-500') + ' text-sm mt-2';
    if (data.success) setTimeout(() => { closeVerificationModal(); }, 2000);
}

// ============================================
// КНОПКА "ВВЕРХ"
// ============================================
window.addEventListener('scroll', function() {
    const btn = document.getElementById('scroll-up-btn');
    if (btn) { if (window.scrollY > 300) btn.classList.add('visible'); else btn.classList.remove('visible'); }
});

function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }

// ============================================
// ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Трекер посещений
    fetch('api.php?action=track_visit').catch(() => {});

    // Аватар — превью при выборе файла
    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                if (file.size > 10 * 1024 * 1024) { document.getElementById('avatar-message').textContent = 'Файл слишком большой. Максимум 10 МБ.'; document.getElementById('avatar-message').className = 'text-red-500 text-sm mb-3'; return; }
                const reader = new FileReader();
                reader.onload = function(ev) { document.getElementById('avatar-preview-img').src = ev.target.result; document.getElementById('avatar-preview').classList.remove('hidden'); document.getElementById('avatar-message').textContent = ''; };
                reader.readAsDataURL(file);
            }
        });
    }

    // Emoji grid
    renderEmojiGrid();

    // Поиск локации
    const locSearch = document.getElementById('location-search');
    if (locSearch) {
        locSearch.addEventListener('input', function() { searchLocation(this.value); });
        locSearch.addEventListener('focus', function() { if (this.value.length >= 3) searchLocation(this.value); });
    }

    // Закрытие результатов поиска локации при клике вне
    document.addEventListener('click', function(e) {
        const resultsDiv = document.getElementById('location-search-results');
        const searchInput = document.getElementById('location-search');
        if (resultsDiv && !resultsDiv.contains(e.target) && e.target !== searchInput) {
            resultsDiv.classList.add('hidden');
        }
    });

    // Загрузка страницы
    if (PAGE_NAME === 'profile') loadProfile();
    if (PAGE_NAME === 'home' || PAGE_NAME === '') loadPosts();
    if (PAGE_NAME === 'messages') loadMessages();

    // Polling уведомлений
    if (IS_LOGGED_IN) {
        fetch('api.php?action=get_unread_count').then(r => r.json()).then(data => { if (data.success) { lastUnreadCount = data.unread_count || 0; updateUnreadBadge(lastUnreadCount); } }).catch(() => {});
        startMessagePolling();
    }

    // Service Worker
    if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/sw.js').catch(() => {}); }
});