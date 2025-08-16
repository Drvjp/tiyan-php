<?php
// games.php
// 内容与 index.php 完全相同
session_start();
$data_url = 'https://data-gold-xi.vercel.app/data.json'; // *** 请替换为实际的 Vercel 数据文件 URL ***
$json_data = @file_get_contents($data_url);
if ($json_data === FALSE) {
    $gameData = [
        'games' => [
            ['id' => 'default', 'name' => '默认游戏', 'image' => 'https://placehold.co/120x120/cccccc/ffffff?text=默认', 'tags' => ['默认']]
        ]
    ];
} else {
    $gameData = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
         $gameData = ['games' => []];
    }
    if (!isset($gameData['games'])) $gameData['games'] = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>电玩店包厢管理系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            cursor: pointer;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        /* 首页样式 */
        .games-waterfall {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        .game-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .game-image {
            width: 100%;
            height: 130px;
            background: #f8fafc;
            background-size: cover;
            background-position: center;
            border-bottom: 2px solid #667eea;
        }
        .game-info {
            padding: 12px;
        }
        .game-name {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .game-tag {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-right: 3px;
            margin-bottom: 3px;
        }
        /* 模态框样式 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .modal-header h3 {
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        .password-input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
        }
        .password-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 8px;
        }
        .password-btn.cancel {
            background: #ef4444;
            color: white;
        }
        .password-btn.confirm {
            background: #667eea;
            color: white;
        }
        /* 搜索功能 */
        .search-container {
            position: relative;
            margin: 0 auto 20px;
            max-width: 400px;
        }
        .search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
        }
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" id="header">
            <h1>🎮 电玩店包厢管理系统</h1>
            <p>欢迎体验我们的游戏世界</p>
        </div>
        <div class="search-container">
            <input type="text" id="globalSearch" class="search-input" placeholder="搜索游戏...">
            <span class="search-icon">🔍</span>
        </div>
        <div class="games-waterfall" id="gamesWaterfall"></div>
    </div>
    <!-- 密码输入模态框 -->
    <div class="modal" id="passwordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>管理员登录</h3>
            </div>
            <p>请输入管理员密码</p>
            <input type="password" id="passwordInput" class="password-input" placeholder="输入密码">
            <div>
                <button class="password-btn cancel" id="cancelPassword">取消</button>
                <button class="password-btn confirm" id="confirmPassword">确认</button>
            </div>
        </div>
    </div>
    <script>
        // 全局变量
        let gameData = null;
        let clickCount = 0;
        let lastClickTime = 0;
        // const ADMIN_PASSWORD = '123456'; // 移除前端密码检查
        // DOM元素缓存
        const elements = {
            header: document.getElementById('header'),
            gamesWaterfall: document.getElementById('gamesWaterfall'),
            passwordModal: document.getElementById('passwordModal'),
            passwordInput: document.getElementById('passwordInput'),
            confirmPassword: document.getElementById('confirmPassword'),
            cancelPassword: document.getElementById('cancelPassword'),
            globalSearch: document.getElementById('globalSearch')
        };

        // 加载数据 (直接使用 PHP 获取的数据)
        function loadData() {
            // 直接使用 PHP 获取的数据
            gameData = <?php echo json_encode($gameData); ?>;
            renderGamesWaterfall();
        }

        // 获取默认数据 (简化)
        function getDefaultData() {
            return <?php echo json_encode($gameData); ?>;
        }

        // 设置事件监听器
        function setupEventListeners() {
            // 标题点击事件 (跳转到登录页面)
            if (elements.header) {
                elements.header.addEventListener('click', handleHeaderClick);
            }
            // 密码输入事件 (移除或简化)
            // if (elements.confirmPassword) elements.confirmPassword.addEventListener('click', checkPassword);
            if (elements.cancelPassword) elements.cancelPassword.addEventListener('click', () => {
                elements.passwordModal.style.display = 'none';
            });
            // 全局搜索
            if (elements.globalSearch) {
                elements.globalSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    filterGames(searchTerm);
                });
            }
        }

        // 处理标题点击（用于进入管理员模式）
        function handleHeaderClick() {
            const now = Date.now();
            if (now - lastClickTime < 1000) {
                clickCount++;
            } else {
                clickCount = 1;
            }
            lastClickTime = now;
            if (clickCount >= 5) {
                // 不再显示模态框，直接跳转到登录页面
                window.location.href = 'login.php';
                clickCount = 0;
            }
        }

        // 过滤游戏
        function filterGames(searchTerm) {
            if (!elements.gamesWaterfall || !gameData) return;
            const container = elements.gamesWaterfall;
            container.innerHTML = '';
            const allGames = [...gameData.games];
            const filteredGames = allGames.filter(game =>
                game.name.toLowerCase().includes(searchTerm)
            );
            filteredGames.sort((a, b) => a.name.localeCompare(b.name));
            if (filteredGames.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: white; font-size: 1.2rem; margin-top: 20px;">未找到匹配的游戏</p>';
                return;
            }
            filteredGames.forEach(game => {
                const gameCard = document.createElement('div');
                gameCard.className = 'game-card';
                gameCard.innerHTML = `
                    <div class="game-image" style="background-image: url(${game.image})"></div>
                    <div class="game-info">
                        <div class="game-name">${game.name}</div>
                        <div class="game-tags">
                            ${game.tags.map(tag => `<span class="game-tag">${tag}</span>`).join('')}
                        </div>
                    </div>
                `;
                container.appendChild(gameCard);
            });
        }

        // 渲染游戏瀑布流
        function renderGamesWaterfall() {
            if (!elements.gamesWaterfall || !gameData) return;
            const container = elements.gamesWaterfall;
            container.innerHTML = '';
            const allGames = [...gameData.games];
            allGames.sort((a, b) => a.name.localeCompare(b.name));
            allGames.forEach(game => {
                const gameCard = document.createElement('div');
                gameCard.className = 'game-card';
                gameCard.innerHTML = `
                    <div class="game-image" style="background-image: url(${game.image})"></div>
                    <div class="game-info">
                        <div class="game-name">${game.name}</div>
                        <div class="game-tags">
                            ${game.tags.map(tag => `<span class="game-tag">${tag}</span>`).join('')}
                        </div>
                    </div>
                `;
                container.appendChild(gameCard);
            });
        }

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
            setupEventListeners();
        });
    </script>
</body>
</html>