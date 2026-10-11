<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Модерация | Vibe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-black">Панель модератора Vibe</h1>
            <a href="index.php" class="text-blue-600 hover:underline">← На сайт</a>
        </div>

        <!-- Статистика модератора -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" id="mod-stats">
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="text-sm text-gray-500 mb-1">Обработано за 30 дней</div>
                <div class="text-4xl font-bold text-blue-600" id="stat-30d">0</div>
                <div class="text-xs text-gray-400 mt-2">Сбрасывается автоматически</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="text-sm text-gray-500 mb-1">Обработано за всё время</div>
                <div class="text-4xl font-bold text-green-600" id="stat-total">0</div>
                <div class="text-xs text-gray-400 mt-2">Неизменная общая сумма</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="text-sm text-gray-500 mb-1">Ваша роль</div>
                <div class="text-2xl font-bold text-purple-600"><?= ucfirst($_SESSION['role']) ?></div>
            </div>
        </div>

        <!-- Категории жалоб -->
        <div class="bg-white p-6 rounded-xl shadow-sm mb-8">
            <h2 class="text-lg font-bold mb-4">Ожидающие жалобы по категориям</h2>
            <div class="flex flex-wrap gap-3" id="categories-list">
                <div class="text-gray-500">Загрузка...</div>
            </div>
        </div>

        <!-- Список жалоб -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 font-bold">Последние жалобы</div>
            <div id="reports-list" class="divide-y divide-gray-100">
                <div class="p-8 text-center text-gray-500">Загрузка...</div>
            </div>
        </div>
    </div>

<script>
const reasonNames = {
    'spam': '📧 Спам',
    'abuse': '😡 Оскорбления',
    'misinformation': '❌ Дезинформация',
    'violence': '⚠️ Насилие',
    'other': '📝 Другое'
};

async function loadModData() {
    // Статистика
    const statsRes = await fetch('api.php?action=get_mod_stats');
    const stats = await statsRes.json();
    if (stats.success) {
        document.getElementById('stat-30d').textContent = stats.handled_30d;
        document.getElementById('stat-total').textContent = stats.handled_total;
        
        const catContainer = document.getElementById('categories-list');
        catContainer.innerHTML = '';
        if (stats.categories.length === 0) {
            catContainer.innerHTML = '<div class="text-green-600 font-medium">Нет ожидающих жалоб 🎉</div>';
        } else {
            stats.categories.forEach(c => {
                catContainer.innerHTML += `<span class="bg-red-50 text-red-700 px-4 py-2 rounded-full font-bold border border-red-200">${reasonNames[c.reason] || c.reason}: ${c.count}</span>`;
            });
        }
    }

    // Список жалоб
    const reportsRes = await fetch('api.php?action=get_pending_reports');
    const reportsData = await reportsRes.json();
    const list = document.getElementById('reports-list');
    list.innerHTML = '';
    
    if (!reportsData.reports || reportsData.reports.length === 0) {
        list.innerHTML = '<div class="p-8 text-center text-gray-500">Нет жалоб для рассмотрения</div>';
        return;
    }

    reportsData.reports.forEach(r => {
        list.innerHTML += `
            <div class="p-6">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">${reasonNames[r.reason] || r.reason}</span>
                        <span class="text-gray-500 text-sm ml-2">Жалоба от @${r.reporter_name} · ${r.created_at}</span>
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg mb-4 text-sm text-gray-800">"${r.content}"</div>
                <div class="flex gap-3">
                    <button onclick="handleReport(${r.id}, 'resolve')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-bold text-sm transition">Удалить пост (Жалоба верна)</button>
                    <button onclick="handleReport(${r.id}, 'ignore')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-bold text-sm transition">Игнорировать (Жалоба ложная)</button>
                </div>
            </div>
        `;
    });
}

async function handleReport(reportId, action) {
    if (!confirm(action === 'resolve' ? 'Удалить этот пост?' : 'Отклонить эту жалобу?')) return;
    const res = await fetch('api.php', { 
        method: 'POST', 
        body: new URLSearchParams({action: 'handle_report', report_id: reportId, action_type: action}) 
    });
    const data = await res.json();
    if (data.success) {
        loadModData(); // Перезагружаем данные, счетчики обновятся автоматически
    } else {
        alert('Ошибка: ' + (data.message || 'Неизвестно'));
    }
}

loadModData();
</script>
</body>
</html>