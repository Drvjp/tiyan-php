<?php
// admin.php
session_start(); // 启动会话以存储登录状态

// 简单的登录检查
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // 未登录，重定向到登录页面
    header('Location: login.php');
    exit();
}

// 从 Vercel 获取完整数据
$data_url = 'https://data-gold-xi.vercel.app/data.json'; // *** 请替换为实际的 Vercel 数据文件 URL ***
$json_data = @file_get_contents($data_url);
if ($json_data === FALSE) {
    $gameData = [
        'rooms' => [
            ['id' => 'room1', 'name' => '包厢A', 'machines' => [], 'note' => '', 'activeSessions' => []],
            ['id' => 'room2', 'name' => '包厢B', 'machines' => [], 'note' => '', 'activeSessions' => []]
        ],
        'machines' => [
            ['id' => 'machine1', 'name' => 'PS5-01', 'image' => 'https://placehold.co/120x120/blue/white?text=PS5'],
            // ... 其他默认机器 ...
        ],
        'games' => [
            ['id' => 'game1', 'name' => '战神4', 'image' => 'https://placehold.co/120x120/orange/white?text=战神4', 'tags' => ['动作', '冒险']]
        ],
        'machineGames' => []
    ];
} else {
    $gameData = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
         $gameData = ['rooms' => [], 'machines' => [], 'games' => [], 'machineGames' => []];
    }
    // 确保必要字段存在
    if (!isset($gameData['rooms'])) $gameData['rooms'] = [];
    if (!isset($gameData['machines'])) $gameData['machines'] = [];
    if (!isset($gameData['games'])) $gameData['games'] = [];
    if (!isset($gameData['machineGames'])) $gameData['machineGames'] = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>管理员后台 - 电玩店包厢管理系统</title>
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
            padding: 10px;
            touch-action: manipulation;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .admin-header h1 {
            color: white;
            font-size: 1.8rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            text-align: center;
            flex: 1;
        }
        /* 统一按钮样式 - 基础类 */
        .btn {
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn-primary {
            background: #667eea;
            color: white;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
        }
        .btn-info {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        .btn-success {
            background: #4ade80;
            color: white;
            box-shadow: 0 2px 4px rgba(74, 222, 128, 0.3);
        }
        .btn-warning {
            background: #f59e0b;
            color: white;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }
        .btn-danger {
            background: #ef4444;
            color: white;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }
        /* 功能按钮区域 */
        .action-buttons {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }
        .action-btn {
            flex: 1;
            min-width: 100px;
        }
        /* 搜索框样式 */
        .search-container {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 8px;
            padding: 2px 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex: 1;
            max-width: 300px;
        }
        .search-container input {
            border: none;
            outline: none;
            padding: 6px;
            font-size: 14px;
            flex: 1;
            min-width: 0;
        }
        .search-container button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #667eea;
            font-size: 16px;
        }
        /* 主要内容区域 */
        .main-content {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        /* 可用机器区域 */
        .available-machines {
            flex: 1;
            min-width: 260px;
            background: white;
            padding: 12px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .available-machines h2 {
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #667eea;
            font-size: 1.1rem;
        }
        .machine-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px;
        }
        .machine-card {
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            position: relative;
            cursor: -webkit-grab;
            cursor: grab;
            user-select: none;
            display: flex;
            flex-direction: column;
        }
        .machine-image {
            width: 100%;
            height: 70px;
            background: #f1f5f9;
            background-size: cover;
            background-position: center;
        }
        .machine-name {
            padding: 6px;
            text-align: center;
            font-weight: 600;
            color: #333;
            font-size: 12px;
            word-wrap: break-word;
        }
        /* 机器卡片底部按钮区域 */
        .machine-actions {
            display: flex;
            border-top: 1px solid #e2e8f0;
        }
        .machine-action-btn {
            flex: 1;
            padding: 6px 0;
            border: none;
            background: #f1f5f9;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.2s;
            color: #475569;
        }
        .machine-action-btn:hover {
            background: #e2e8f0;
        }
        .machine-action-btn.view {
            color: #3b82f6;
        }
        .machine-action-btn.edit {
            color: #10b981;
        }
        .machine-action-btn.delete {
            color: #ef4444;
        }
        /* 包厢区域 */
        .game-rooms {
            flex: 2;
            min-width: 280px;
            display: grid;
            /* 默认使用 auto-fit */
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 12px;
        }
        /* 强制在大于 500px 的屏幕上至少显示两列 (针对折叠屏) */
        @media (min-width: 501px) {
            .game-rooms {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            }
        }
        /* 在更大的屏幕上显示更多列 */
        @media (min-width: 1025px) {
            .game-rooms {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            }
        }
        /* 隐藏已使用的包厢的类 */
        .game-rooms.hide-used .room-card.used {
            display: none;
        }
        .room-card {
            background: white;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 4px solid transparent;
        }
        .room-card.used {
            border-left-color: #667eea;
        }
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #667eea;
        }
        .room-header h3 {
            color: #333;
            font-size: 1.05rem;
            font-weight: 600;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .room-controls {
            display: flex;
            gap: 3px;
        }
        .room-edit, .room-delete {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 12px;
        }
        .machine-dropzone {
            min-height: 50px;
            background: #f8fafc;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 10px;
            border: 2px dashed #cbd5e1;
            position: relative;
            z-index: 1;
            font-size: 12px;
        }
        .machine-dropzone.empty {
            display: flex;
            justify-content: center;
            align-items: center;
            color: #94a3b8;
            font-style: italic;
            font-size: 11px;
            min-height: 50px;
        }
        .machine-in-room {
            background: #dbeafe;
            padding: 6px;
            margin: 2px 0;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid #93c5fd;
            position: relative;
            font-size: 12px;
        }
        .machine-name {
            font-weight: 600;
            color: #2563eb;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        /* 为包厢内的机器添加查看按钮 */
        .machine-view-btn {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 12px;
            margin-right: 4px;
        }
        .machine-remove-btn {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 12px;
        }
        .session-controls {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        .session-btn {
            flex: 1;
            min-width: 70px;
            padding: 8px;
        }
        .note-section {
            margin-top: 6px;
        }
        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .note-header label {
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        .note-content {
            background: #f8fafc;
            padding: 6px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            min-height: 30px;
            font-size: 12px;
            color: #333;
            word-wrap: break-word;
            line-height: 1.4;
        }
        .session-info {
            background: #f0fdf4;
            padding: 6px;
            border-radius: 6px;
            font-size: 11px;
            color: #16a34a;
            margin-bottom: 8px;
            border: 1px solid #bbf7d0;
        }
        .session-info h4 {
            margin-bottom: 3px;
            color: #059669;
            font-size: 12px;
        }
        .session-info p {
            margin: 1px 0;
            font-size: 10px;
        }
        .time-left {
            font-weight: 600;
            color: #dc2626;
        }
        /* 模态框样式 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(2px);
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 12px;
            max-width: 95%;
            width: 350px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e0e0;
        }
        .modal-header h3 {
            color: #333;
            font-size: 1.2rem;
        }
        .modal-close {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 18px;
        }
        .form-group {
            margin-bottom: 12px;
        }
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            font-size: 13px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
        }
        .form-group textarea {
            min-height: 60px;
            resize: vertical;
        }
        .installed-games {
            margin: 10px 0;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 6px;
            min-height: 50px;
        }
        .installed-games h4 {
            font-size: 14px;
            margin-bottom: 8px;
            color: #333;
        }
        .game-option {
            display: inline-block;
            background: #e2e8f0;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin: 2px 4px 2px 0;
            color: #475569;
            cursor: pointer;
        }
        .game-option.add {
            background: #667eea;
            color: white;
        }
        .game-option.remove {
            background: #ef4444;
            color: white;
        }
        /* 游戏列表模态框内的搜索框 */
        .games-modal-search-container {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            background: #f1f5f9;
            border-radius: 6px;
            padding: 2px 6px;
        }
        .games-modal-search-container input {
            border: none;
            outline: none;
            padding: 4px;
            font-size: 12px;
            flex: 1;
            background: transparent;
        }
        .games-modal-search-container button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px;
            color: #667eea;
            font-size: 14px;
        }
        /* 新增：全局搜索区域样式 */
        .global-search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }
        .global-search-box {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 8px;
            padding: 2px 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex: 1;
            max-width: 300px;
        }
        .global-search-box input {
            border: none;
            outline: none;
            padding: 6px;
            font-size: 14px;
            flex: 1;
            min-width: 0;
        }
        .global-search-box button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #667eea;
            font-size: 16px;
        }
        .export-btn {
            flex: 0 0 auto; /* 不伸缩，保持原始大小 */
        }
        /* 新增：全局搜索结果区域样式 */
        .global-search-results {
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 15px;
            display: none; /* 默认隐藏 */
        }
        .global-search-results h3 {
            margin-bottom: 10px;
            color: #333;
            font-size: 1.1rem;
        }
        .global-search-results ul {
            list-style-type: none;
            padding-left: 0;
        }
        .global-search-results li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .global-search-results li:last-child {
            border-bottom: none;
        }
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            .action-buttons, .global-search-container {
                justify-content: center;
                flex-direction: column;
            }
            .search-container, .global-search-box {
                max-width: 100%;
                margin-top: 5px;
            }
            .session-controls {
                flex-direction: column;
            }
            /* 在手机上强制一列 */
            .game-rooms {
                grid-template-columns: 1fr;
            }
            .available-machines, .game-rooms {
                min-width: 100%;
            }
            .machine-list {
                grid-template-columns: repeat(3, 1fr);
            }
            .modal-content {
                width: 95%;
                margin: 20px;
            }
            .export-btn {
                width: 100%; /* 在小屏幕上按钮占满宽度 */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>🎮 管理员后台</h1>
            <div>
                <a href="logout.php" class="btn btn-danger">登出</a> <!-- 新增登出链接 -->
                <button id="backToHome" class="btn btn-primary">返回首页</button>
            </div>
        </div>
        <div class="action-buttons">
            <button id="addRoom" class="action-btn btn btn-info">添加包厢</button>
            <button id="addMachine" class="action-btn btn btn-success">添加游戏机</button>
            <button id="editGames" class="action-btn btn btn-warning">编辑游戏目录</button>
        </div>
        <!-- 新增：全局搜索和导出区域 -->
        <div class="global-search-container">
             <div class="global-search-box">
                <input type="text" id="globalSearchInput" placeholder="搜索游戏名称...">
                <button id="globalSearchButton">🔍</button>
            </div>
            <button id="exportData" class="btn btn-success export-btn">导出数据</button>
        </div>
        <!-- 新增：全局搜索结果显示区域 -->
        <div class="global-search-results" id="globalSearchResults">
            <h3>搜索结果</h3>
            <ul id="globalSearchResultsList"></ul>
        </div>
        <!-- 隐藏/显示已使用包厢按钮 -->
        <div style="text-align: center; margin-bottom: 10px;">
            <button id="toggleUsedRooms" class="btn btn-primary">隐藏已使用包厢</button>
        </div>
        <div class="main-content">
            <!-- 可用机器区域 -->
            <div class="available-machines">
                <h2>可用机器</h2>
                <div class="machine-list" id="availableMachines"></div>
            </div>
            <!-- 包厢区域 -->
            <div class="game-rooms" id="gameRooms"></div>
        </div>
    </div>
    <!-- 编辑游戏机模态框 -->
    <div class="modal" id="editMachineModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editMachineTitle">编辑游戏机</h3>
                <button class="modal-close btn btn-danger" id="closeEditMachineModal">×</button>
            </div>
            <div class="edit-machine-form">
                <div class="form-group">
                    <label for="machineName">机器名称</label>
                    <input type="text" id="machineName" placeholder="输入机器名称">
                </div>
                <div class="form-group">
                    <label for="machineImage">图片URL</label>
                    <input type="text" id="machineImage" placeholder="输入图片URL">
                </div>
                <div class="installed-games">
                    <h4>已安装的游戏</h4>
                    <div id="installedGamesList"></div>
                </div>
                <div class="installed-games">
                    <h4>可添加的游戏</h4>
                    <div id="availableGamesList"></div>
                </div>
                <button id="saveMachineBtn" class="btn btn-success">保存</button>
            </div>
        </div>
    </div>
    <!-- 编辑包厢模态框 -->
    <div class="modal" id="editRoomModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editRoomTitle">编辑包厢</h3>
                <button class="modal-close btn btn-danger" id="closeEditRoomModal">×</button>
            </div>
            <div class="form-group">
                <label for="roomName">包厢名称</label>
                <input type="text" id="roomName" placeholder="输入包厢名称">
            </div>
            <button id="saveRoomBtn" class="btn btn-success">保存</button>
        </div>
    </div>
    <!-- 编辑备注模态框 -->
    <div class="modal" id="editNoteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editNoteTitle">编辑备注</h3>
                <button class="modal-close btn btn-danger" id="closeEditNoteModal">×</button>
            </div>
            <div class="form-group">
                <label for="noteContent">备注内容</label>
                <textarea id="noteContent" placeholder="输入备注内容..."></textarea>
            </div>
            <button id="saveNoteBtn" class="btn btn-success">保存备注</button>
        </div>
    </div>
    <!-- 游戏列表模态框 (带搜索) -->
    <div class="modal" id="gamesModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="gamesModalTitle">游戏列表</h3>
                <button class="modal-close btn btn-danger" id="closeGamesModal">×</button>
            </div>
            <!-- 添加搜索框到游戏列表模态框 -->
            <div class="games-modal-search-container">
                <input type="text" id="gamesModalSearch" placeholder="搜索已安装游戏...">
                <button id="clearGamesModalSearch">×</button>
            </div>
            <div id="gamesList"></div>
        </div>
    </div>
    <!-- 选择机器模态框 -->
    <div class="modal" id="selectMachineModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="selectMachineTitle">选择机器</h3>
                <button class="modal-close btn btn-danger" id="closeSelectMachineModal">×</button>
            </div>
            <div id="machineOptions"></div>
        </div>
    </div>
    <!-- 输入时长模态框 -->
    <div class="modal" id="durationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="durationTitle">选择时长</h3>
                <button class="modal-close btn btn-danger" id="closeDurationModal">×</button>
            </div>
            <div id="durationOptions"></div>
        </div>
    </div>
    <!-- 加时续玩密码模态框 -->
    <div class="modal" id="extendPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>加时续玩</h3>
                <button class="modal-close btn btn-danger" id="closeExtendPasswordModal">×</button>
            </div>
            <p id="extendMinutesText">续玩 <span id="extendMinutes">0</span> 分钟</p>
            <input type="password" id="extendPasswordInput" class="password-input" placeholder="输入密码">
            <div style="display: flex; justify-content: center; gap: 10px; margin-top: 15px;">
                <button class="btn btn-danger" id="cancelExtend">取消</button>
                <button class="btn btn-success" id="confirmExtend">确认</button>
            </div>
        </div>
    </div>
    <script>
        // 全局变量
        let gameData = null;
        let currentEditingMachineId = null;
        let currentEditingRoomId = null;
        let currentNoteRoomId = null;
        let currentSelectingMachine = null;
        let currentRoomId = null;
        let currentMachineId = null;
        let extendMinutes = 0;
        let dragSource = null;
        // const ADMIN_PASSWORD = '123456'; // 移除前端密码检查
        let hideUsedRooms = false;
        // DOM元素缓存
        const elements = {
            backToHome: document.getElementById('backToHome'),
            addRoom: document.getElementById('addRoom'),
            addMachine: document.getElementById('addMachine'),
            editGames: document.getElementById('editGames'),
            availableMachines: document.getElementById('availableMachines'),
            gameRooms: document.getElementById('gameRooms'),
            editMachineModal: document.getElementById('editMachineModal'),
            closeEditMachineModal: document.getElementById('closeEditMachineModal'),
            editMachineTitle: document.getElementById('editMachineTitle'),
            machineName: document.getElementById('machineName'),
            machineImage: document.getElementById('machineImage'),
            installedGamesList: document.getElementById('installedGamesList'),
            availableGamesList: document.getElementById('availableGamesList'),
            saveMachineBtn: document.getElementById('saveMachineBtn'),
            editRoomModal: document.getElementById('editRoomModal'),
            closeEditRoomModal: document.getElementById('closeEditRoomModal'),
            editRoomTitle: document.getElementById('editRoomTitle'),
            roomName: document.getElementById('roomName'),
            saveRoomBtn: document.getElementById('saveRoomBtn'),
            editNoteModal: document.getElementById('editNoteModal'),
            closeEditNoteModal: document.getElementById('closeEditNoteModal'),
            editNoteTitle: document.getElementById('editNoteTitle'),
            noteContent: document.getElementById('noteContent'),
            saveNoteBtn: document.getElementById('saveNoteBtn'),
            gamesModal: document.getElementById('gamesModal'),
            closeGamesModal: document.getElementById('closeGamesModal'),
            gamesModalTitle: document.getElementById('gamesModalTitle'),
            gamesList: document.getElementById('gamesList'),
            // 添加游戏列表模态框的搜索元素
            gamesModalSearch: document.getElementById('gamesModalSearch'),
            clearGamesModalSearch: document.getElementById('clearGamesModalSearch'),
            selectMachineModal: document.getElementById('selectMachineModal'),
            closeSelectMachineModal: document.getElementById('closeSelectMachineModal'),
            selectMachineTitle: document.getElementById('selectMachineTitle'),
            machineOptions: document.getElementById('machineOptions'),
            durationModal: document.getElementById('durationModal'),
            closeDurationModal: document.getElementById('closeDurationModal'),
            durationTitle: document.getElementById('durationTitle'),
            durationOptions: document.getElementById('durationOptions'),
            extendPasswordModal: document.getElementById('extendPasswordModal'),
            closeExtendPasswordModal: document.getElementById('closeExtendPasswordModal'),
            extendMinutes: document.getElementById('extendMinutes'),
            extendPasswordInput: document.getElementById('extendPasswordInput'),
            confirmExtend: document.getElementById('confirmExtend'),
            cancelExtend: document.getElementById('cancelExtend'),
            toggleUsedRooms: document.getElementById('toggleUsedRooms'),
            // 新增：全局搜索和导出相关元素
            globalSearchInput: document.getElementById('globalSearchInput'),
            globalSearchButton: document.getElementById('globalSearchButton'),
            exportData: document.getElementById('exportData'),
            globalSearchResults: document.getElementById('globalSearchResults'),
            globalSearchResultsList: document.getElementById('globalSearchResultsList')
        };

        // 加载数据 (直接使用 PHP 获取的数据)
        function loadData() {
            // 直接使用 PHP 获取的数据
            gameData = <?php echo json_encode($gameData); ?>;
            // 确保 machineGames 结构正确
            gameData.machines.forEach(machine => {
                if (!gameData.machineGames[machine.id]) {
                    gameData.machineGames[machine.id] = [];
                }
            });
            renderAvailableMachines();
            renderGameRooms();
        }

        // 获取默认数据 (简化)
        function getDefaultData() {
            return <?php echo json_encode($gameData); ?>;
        }

        // 修改保存数据函数，使其调用新的 PHP API 脚本
        function saveData() {
            if (!gameData) return;

            // 发送数据到 PHP 脚本
            fetch('api/save_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(gameData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('网络响应错误 ' + response.status);
                }
                return response.text(); // 假设 PHP 返回文本消息
            })
            .then(data => {
                console.log('保存成功:', data);
                // showMessage("数据已保存"); // 显示成功消息
                // 可以在这里添加其他成功后的逻辑
            })
            .catch(error => {
                console.error('保存失败:', error);
                showMessage('保存失败: ' + error.message); // 显示错误消息
            });
        }

        // 设置事件监听器
        function setupEventListeners() {
            if (elements.backToHome) {
                elements.backToHome.addEventListener('click', () => {
                    window.location.href = 'index.php'; // 修改为 index.php
                });
            }
            if (elements.addRoom) elements.addRoom.addEventListener('click', addRoom);
            if (elements.addMachine) elements.addMachine.addEventListener('click', addMachine);
            if (elements.editGames) elements.editGames.addEventListener('click', () => {
                window.location.href = 'games.php'; // 修改为 games.php
            });
            if (elements.closeEditMachineModal) elements.closeEditMachineModal.addEventListener('click', () => {
                elements.editMachineModal.style.display = 'none';
            });
            if (elements.saveMachineBtn) elements.saveMachineBtn.addEventListener('click', saveMachine);
            if (elements.closeEditRoomModal) elements.closeEditRoomModal.addEventListener('click', () => {
                elements.editRoomModal.style.display = 'none';
            });
            if (elements.saveRoomBtn) elements.saveRoomBtn.addEventListener('click', saveRoom);
            if (elements.closeEditNoteModal) elements.closeEditNoteModal.addEventListener('click', () => {
                elements.editNoteModal.style.display = 'none';
            });
            if (elements.saveNoteBtn) elements.saveNoteBtn.addEventListener('click', saveNote);
            // 游戏列表模态框事件 (带搜索)
            if (elements.closeGamesModal) elements.closeGamesModal.addEventListener('click', () => {
                elements.gamesModal.style.display = 'none';
                // 关闭时清空搜索框
                if (elements.gamesModalSearch) elements.gamesModalSearch.value = '';
            });
            // 添加游戏列表模态框的搜索事件
            if (elements.gamesModalSearch) elements.gamesModalSearch.addEventListener('input', filterGamesInModal);
            if (elements.clearGamesModalSearch) elements.clearGamesModalSearch.addEventListener('click', () => {
                if (elements.gamesModalSearch) elements.gamesModalSearch.value = '';
                filterGamesInModal();
            });
            if (elements.closeSelectMachineModal) elements.closeSelectMachineModal.addEventListener('click', () => {
                elements.selectMachineModal.style.display = 'none';
            });
            if (elements.closeDurationModal) elements.closeDurationModal.addEventListener('click', () => {
                elements.durationModal.style.display = 'none';
            });
            if (elements.closeExtendPasswordModal) elements.closeExtendPasswordModal.addEventListener('click', () => {
                elements.extendPasswordModal.style.display = 'none';
            });
            if (elements.confirmExtend) elements.confirmExtend.addEventListener('click', checkExtendPassword);
            if (elements.cancelExtend) elements.cancelExtend.addEventListener('click', () => {
                elements.extendPasswordModal.style.display = 'none';
            });
            if (elements.extendPasswordInput) elements.extendPasswordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    checkExtendPassword();
                }
            });
            if (elements.toggleUsedRooms) {
                elements.toggleUsedRooms.addEventListener('click', toggleUsedRooms);
            }
            // 新增：全局搜索和导出事件监听器
            if (elements.globalSearchButton) elements.globalSearchButton.addEventListener('click', performGlobalSearch);
            if (elements.globalSearchInput) elements.globalSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performGlobalSearch();
                }
            });
            if (elements.exportData) elements.exportData.addEventListener('click', exportCurrentData);
        }

        // 切换隐藏/显示已使用包厢
        function toggleUsedRooms() {
            hideUsedRooms = !hideUsedRooms;
            if (elements.gameRooms) {
                if (hideUsedRooms) {
                    elements.gameRooms.classList.add('hide-used');
                    if (elements.toggleUsedRooms) elements.toggleUsedRooms.textContent = '显示已使用包厢';
                } else {
                    elements.gameRooms.classList.remove('hide-used');
                    if (elements.toggleUsedRooms) elements.toggleUsedRooms.textContent = '隐藏已使用包厢';
                }
            }
        }

        // 过滤游戏列表模态框中的游戏
        function filterGamesInModal() {
            if (!elements.gamesList || !elements.gamesModalSearch) return;
            const searchTerm = elements.gamesModalSearch.value.trim().toLowerCase();
            const gameItems = elements.gamesList.querySelectorAll('.game-item');
            gameItems.forEach(item => {
                const gameName = item.querySelector('.game-name')?.textContent.toLowerCase() || '';
                if (gameName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // 新增：执行全局搜索
        function performGlobalSearch() {
            if (!elements.globalSearchInput || !elements.globalSearchResults || !elements.globalSearchResultsList || !gameData) return;
            const searchTerm = elements.globalSearchInput.value.trim().toLowerCase();
            if (!searchTerm) {
                showMessage("请输入要搜索的游戏名称");
                return;
            }
            elements.globalSearchResultsList.innerHTML = ''; // 清空之前的结果
            let foundMachines = []; // 存储找到的机器名称
            // 遍历所有游戏，找到匹配名称的游戏ID
            const matchingGameIds = gameData.games
                .filter(game => game.name.toLowerCase().includes(searchTerm))
                .map(game => game.id);
            if (matchingGameIds.length === 0) {
                 // 如果没有找到匹配的游戏名称，则显示提示
                const listItem = document.createElement('li');
                listItem.textContent = `未找到名称包含 "${searchTerm}" 的游戏。`;
                elements.globalSearchResultsList.appendChild(listItem);
            } else {
                // 遍历所有机器的已安装游戏列表
                gameData.machines.forEach(machine => {
                    const machineGameIds = gameData.machineGames[machine.id] || [];
                    // 检查该机器是否安装了任何一个匹配的游戏
                    const isGameInstalled = matchingGameIds.some(gameId => machineGameIds.includes(gameId));
                    if (isGameInstalled) {
                        foundMachines.push(machine.name);
                    }
                });
                if (foundMachines.length > 0) {
                    // 如果找到了安装该游戏的机器，则显示列表
                    foundMachines.forEach(machineName => {
                        const listItem = document.createElement('li');
                        listItem.textContent = `机器: ${machineName}`;
                        elements.globalSearchResultsList.appendChild(listItem);
                    });
                } else {
                    // 如果没有机器安装了这个游戏，则显示提示
                     matchingGameIds.forEach(gameId => {
                         const game = gameData.games.find(g => g.id === gameId);
                         if (game) {
                            const listItem = document.createElement('li');
                            listItem.textContent = `游戏 "${game.name}" 未安装在任何机器上。`;
                            elements.globalSearchResultsList.appendChild(listItem);
                         }
                     });
                }
            }
            // 显示结果区域
            elements.globalSearchResults.style.display = 'block';
        }

        // 新增：导出当前数据
        function exportCurrentData() {
            if (!gameData) {
                showMessage("暂无数据可导出");
                return;
            }
            try {
                const dataStr = JSON.stringify(gameData, null, 2); // 格式化为可读的JSON
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const link = document.createElement('a');
                link.href = URL.createObjectURL(dataBlob);
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-'); // 创建安全的文件名时间戳
                link.download = `game_room_data_${timestamp}.json`;
                link.click();
                URL.revokeObjectURL(link.href); // 清理内存
                showMessage("数据已导出");
            } catch (e) {
                console.error("导出数据失败:", e);
                showMessage("导出数据失败");
            }
        }

        // 渲染可用机器
        function renderAvailableMachines() {
            if (!elements.availableMachines || !gameData) return;
            const container = elements.availableMachines;
            container.innerHTML = '';
            const usedMachines = new Set();
            gameData.rooms.forEach(room => {
                room.machines.forEach(machine => usedMachines.add(machine.id));
            });
            gameData.machines.forEach(machine => {
                if (!usedMachines.has(machine.id)) {
                    const machineEl = createMachineCard(machine, true);
                    container.appendChild(machineEl);
                }
            });
        }

        // 创建机器卡片
        function createMachineCard(machine, isAvailable = false) {
            const div = document.createElement('div');
            div.className = 'machine-card';
            div.draggable = true;
            div.dataset.machineId = machine.id;
            div.style.userSelect = 'none';
            const image = document.createElement('div');
            image.className = 'machine-image';
            image.style.backgroundImage = `url(${machine.image})`;
            div.appendChild(image);
            const name = document.createElement('div');
            name.className = 'machine-name';
            name.textContent = machine.name;
            div.appendChild(name);
            if (isAvailable) {
                const actions = document.createElement('div');
                actions.className = 'machine-actions';
                const viewBtn = document.createElement('button');
                viewBtn.className = 'machine-action-btn view btn';
                viewBtn.innerHTML = '👁️';
                viewBtn.title = '查看游戏';
                viewBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    showGames(machine.id);
                });
                const editBtn = document.createElement('button');
                editBtn.className = 'machine-action-btn edit btn';
                editBtn.innerHTML = '✏️';
                editBtn.title = '编辑机器';
                editBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    editMachine(machine.id);
                });
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'machine-action-btn delete btn';
                deleteBtn.innerHTML = '🗑️';
                deleteBtn.title = '删除机器';
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteMachine(machine.id);
                });
                actions.appendChild(viewBtn);
                actions.appendChild(editBtn);
                actions.appendChild(deleteBtn);
                div.appendChild(actions);
            }
            div.addEventListener('dragstart', handleDragStart);
            div.addEventListener('dragend', handleDragEnd);
            return div;
        }

        // 处理拖拽开始
        function handleDragStart(e) {
            if (e.target.dataset.machineId) {
                dragSource = e.target;
                e.dataTransfer.setData('text/plain', e.target.dataset.machineId);
                e.dataTransfer.effectAllowed = 'move';
                setTimeout(() => {
                    e.target.style.opacity = '0.5';
                }, 0);
            }
        }

        // 处理拖拽结束
        function handleDragEnd(e) {
            if (e.target.style) {
                e.target.style.opacity = '1';
            }
            dragSource = null;
        }

        // 处理拖拽悬停
        function handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }

        // 处理放置
        function handleDrop(e, roomId) {
            e.preventDefault();
            e.stopPropagation();
            if (!dragSource) return;
            const machineId = e.dataTransfer.getData('text/plain');
            const room = gameData.rooms.find(r => r.id === roomId);
            const machine = gameData.machines.find(m => m.id === machineId);
            if (room && machine) {
                if (room.machines.length > 0) {
                    if (!confirm(`包厢 "${room.name}" 已经有机器了。您确定要再添加一台 "${machine.name}" 吗？`)) {
                        dragSource = null;
                        return;
                    }
                }
                if (!room.machines.find(m => m.id === machineId)) {
                    room.machines.push({...machine});
                    saveData(); // 修改为 saveData
                    renderAvailableMachines();
                    renderGameRooms();
                }
            }
            dragSource = null;
        }

        // 渲染包厢
        function renderGameRooms() {
            if (!elements.gameRooms || !gameData) return;
            const container = elements.gameRooms;
            container.innerHTML = '';
            gameData.rooms.forEach(room => {
                const roomEl = createRoomCard(room);
                container.appendChild(roomEl);
            });
        }

        // 创建包厢卡片
        function createRoomCard(room) {
            const div = document.createElement('div');
            div.className = 'room-card';
            if (room.machines.length > 0) {
                div.classList.add('used');
            }
            div.dataset.roomId = room.id;
            let machinesHtml = '';
            if (room.machines.length === 0) {
                machinesHtml = '<div class="machine-dropzone empty">拖拽机器到这里</div>';
            } else {
                machinesHtml = '<div class="machine-dropzone">';
                room.machines.forEach(machine => {
                    const session = room.activeSessions[machine.id];
                    machinesHtml += `
                        <div class="machine-in-room">
                            <button class="machine-view-btn btn btn-info" title="查看游戏">👁️</button>
                            <span class="machine-name">${machine.name}</span>
                            <button class="machine-remove-btn btn btn-danger">×</button>
                        </div>
                    `;
                });
                machinesHtml += '</div>';
            }
            const session = Object.values(room.activeSessions).find(s => s);
            let sessionInfo = '';
            if (session) {
                const startTime = new Date(session.startTime).toLocaleTimeString();
                const timeLeft = session.timeLeft !== null ? formatTimeLeft(session.timeLeft) : '';
                sessionInfo = `
                    <div class="session-info">
                        <h4>🎮 游戏中</h4>
                        <p>开始: ${startTime}</p>
                        <p class="time-left">剩余: ${timeLeft}</p>
                    </div>
                `;
            }
            div.innerHTML = `
                <div class="room-header">
                    <h3>${room.name}</h3>
                    <div class="room-controls">
                        <button class="room-edit btn btn-info" title="编辑包厢名称">✏️</button>
                        <button class="room-delete btn btn-danger" title="删除包厢">×</button>
                    </div>
                </div>
                ${machinesHtml}
                <div class="session-controls">
                    <button class="session-btn btn btn-primary" onclick="startNewSession('${room.id}')">首次游玩</button>
                    <button class="session-btn btn btn-warning" onclick="showExtendModal('${room.id}')">加时续玩</button>
                    <button class="session-btn btn btn-danger" onclick="endSession('${room.id}')">结束游玩</button>
                </div>
                <div class="note-section">
                    <div class="note-header">
                        <label>备注:</label>
                        <button class="btn btn-info edit-note-btn">编辑</button>
                    </div>
                    <div class="note-content">${room.note || '暂无备注'}</div>
                </div>
                ${sessionInfo}
            `;
            const machineDropzone = div.querySelector('.machine-dropzone');
            if (machineDropzone && !machineDropzone.querySelector('.empty')) {
                machineDropzone.addEventListener('dragover', handleDragOver);
                machineDropzone.addEventListener('drop', (e) => handleDrop(e, room.id));
            } else if (machineDropzone) {
                machineDropzone.addEventListener('dragover', handleDragOver);
                machineDropzone.addEventListener('drop', (e) => handleDrop(e, room.id));
            }
            const machineInRooms = div.querySelectorAll('.machine-in-room');
            machineInRooms.forEach(machineEl => {
                const viewBtn = machineEl.querySelector('.machine-view-btn');
                const nameSpan = machineEl.querySelector('.machine-name');
                const removeBtn = machineEl.querySelector('.machine-remove-btn');
                const machineName = nameSpan.textContent;
                const machine = room.machines.find(m => m.name === machineName);
                const machineId = machine ? machine.id : null;
                if (machineId) {
                    if (viewBtn) {
                        viewBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            showGames(machineId);
                        });
                    }
                    if (nameSpan) {
                        nameSpan.addEventListener('click', (e) => {
                            e.stopPropagation();
                            showGames(machineId);
                        });
                    }
                }
                if (removeBtn) {
                    removeBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (machineId) {
                            removeMachine(room.id, machineId);
                        }
                    });
                }
            });
            const editBtn = div.querySelector('.room-edit');
            if (editBtn) {
                editBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    editRoom(room.id);
                });
            }
            const deleteBtn = div.querySelector('.room-delete');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteRoom(room.id);
                });
            }
            const editNoteBtn = div.querySelector('.edit-note-btn');
            if (editNoteBtn) {
                editNoteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    editNote(room.id);
                });
            }
            return div;
        }

        // 首次游玩
        function startNewSession(roomId) {
            const room = gameData.rooms.find(r => r.id === roomId);
            if (!room || room.machines.length === 0) {
                showMessage('请先为包厢添加游戏机！');
                return;
            }
            if (room.machines.length === 1) {
                showDurationModal(roomId, room.machines[0].id);
            } else {
                showSelectMachineModal(roomId);
            }
        }

        // 显示选择机器模态框
        function showSelectMachineModal(roomId) {
            const room = gameData.rooms.find(r => r.id === roomId);
            if (!room) return;
            elements.selectMachineTitle.textContent = '选择游戏机';
            elements.machineOptions.innerHTML = '';
            room.machines.forEach(machine => {
                const btn = document.createElement('button');
                btn.className = 'btn btn-primary';
                btn.style.display = 'block';
                btn.style.width = '100%';
                btn.style.margin = '5px 0';
                btn.textContent = machine.name;
                btn.onclick = () => {
                    elements.selectMachineModal.style.display = 'none';
                    showDurationModal(roomId, machine.id);
                };
                elements.machineOptions.appendChild(btn);
            });
            elements.selectMachineModal.style.display = 'flex';
        }

        // 显示选择时长模态框
        function showDurationModal(roomId, machineId) {
            elements.durationTitle.textContent = '选择时长';
            elements.durationOptions.innerHTML = '';
            const durations = [
                { value: 1, label: '1小时' },
                { value: 3, label: '3小时' },
                { value: 6, label: '6小时' },
                { value: 0, label: '计时模式' }
            ];
            durations.forEach(d => {
                const btn = document.createElement('button');
                btn.className = 'btn btn-primary';
                btn.style.display = 'block';
                btn.style.width = '100%';
                btn.style.margin = '5px 0';
                btn.textContent = d.label;
                btn.onclick = () => {
                    elements.durationModal.style.display = 'none';
                    startSession(roomId, machineId, d.value);
                };
                elements.durationOptions.appendChild(btn);
            });
            elements.durationModal.style.display = 'flex';
        }

        // 开始会话
        function startSession(roomId, machineId, duration) {
            const room = gameData.rooms.find(r => r.id === roomId);
            if (!room) return;
            const startTime = new Date();
            const endTime = duration === 0 ? null : new Date(startTime.getTime() + duration * 60 * 60 * 1000);
            room.activeSessions[machineId] = {
                startTime: startTime.toISOString(),
                endTime: endTime ? endTime.toISOString() : null,
                duration: duration,
                timeLeft: duration === 0 ? null : duration * 60 * 60
            };
            saveData(); // 修改为 saveData
            renderGameRooms();
            if (duration > 0) {
                setTimeout(() => {
                    showMessage(`${room.name} 的游戏时间已到！`);
                }, duration * 60 * 60 * 1000);
            }
            showMessage(`开始游戏，时长: ${duration === 0 ? '计时模式' : duration + '小时'}`);
        }

        // 显示加时续玩模态框
        function showExtendModal(roomId) {
            const room = gameData.rooms.find(r => r.id === roomId);
            if (!room || room.machines.length === 0) {
                showMessage('请先为包厢添加游戏机！');
                return;
            }
            const activeMachineId = Object.keys(room.activeSessions).find(id => room.activeSessions[id]);
            if (!activeMachineId) {
                showMessage('当前没有进行中的游戏会话！');
                return;
            }
            if (room.activeSessions[activeMachineId].duration === 0) {
                showMessage('计时模式无法加时，请使用首次游玩功能！');
                return;
            }
            currentRoomId = roomId;
            currentMachineId = activeMachineId;
            extendMinutes = 30;
            elements.extendMinutes.textContent = extendMinutes;
            elements.extendPasswordInput.value = '';
            elements.extendPasswordModal.style.display = 'flex';
            setTimeout(() => {
                elements.extendPasswordInput.focus();
            }, 100);
        }

        // 检查加时续玩密码
        function checkExtendPassword() {
            const password = elements.extendPasswordInput.value;
            // 移除前端密码检查
            // if (password === ADMIN_PASSWORD) {
                addExtendTime();
                elements.extendPasswordModal.style.display = 'none';
            // } else {
            //     showMessage('密码错误，请重试！');
            //     elements.extendPasswordInput.value = '';
            //     elements.extendPasswordInput.focus();
            // }
        }

        // 添加加时时间
        function addExtendTime() {
            const room = gameData.rooms.find(r => r.id === currentRoomId);
            if (room && room.activeSessions[currentMachineId]) {
                const session = room.activeSessions[currentMachineId];
                const additionalSeconds = extendMinutes * 60;
                session.timeLeft += additionalSeconds;
                const endTime = new Date(new Date(session.endTime).getTime() + additionalSeconds * 1000);
                session.endTime = endTime.toISOString();
                saveData(); // 修改为 saveData
                renderGameRooms();
                showMessage(`成功为 ${room.name} 续玩 ${extendMinutes} 分钟`);
                setTimeout(() => {
                    showMessage(`${room.name} 的游戏时间已到！`);
                }, additionalSeconds * 1000);
            }
        }

        // 结束游玩
        function endSession(roomId) {
            const room = gameData.rooms.find(r => r.id === roomId);
            if (room) {
                const activeMachineId = Object.keys(room.activeSessions).find(id => room.activeSessions[id]);
                if (activeMachineId) {
                    const machine = room.machines.find(m => m.id === activeMachineId);
                    delete room.activeSessions[activeMachineId];
                    saveData(); // 修改为 saveData
                    renderGameRooms();
                    showMessage(`${machine ? machine.name : '游戏机'} 的游戏会话已结束`);
                } else {
                    showMessage('当前没有进行中的游戏会话');
                }
            }
        }

        // 编辑机器
        function editMachine(machineId) {
            const machine = gameData.machines.find(m => m.id === machineId);
            if (!machine) return;
            currentEditingMachineId = machineId;
            if (elements.editMachineTitle) {
                elements.editMachineTitle.textContent = `编辑 ${machine.name}`;
            }
            if (elements.machineName) elements.machineName.value = machine.name;
            if (elements.machineImage) elements.machineImage.value = machine.image;
            renderMachineGames(machineId);
            elements.editMachineModal.style.display = 'flex';
        }

        // 保存机器
        function saveMachine() {
            if (!currentEditingMachineId || !elements.machineName || !elements.machineImage) return;
            const name = elements.machineName.value.trim();
            const image = elements.machineImage.value.trim();
            if (!name) {
                showMessage('机器名称不能为空');
                return;
            }
            if (!image) {
                showMessage('图片URL不能为空');
                return;
            }
            const machine = gameData.machines.find(m => m.id === currentEditingMachineId);
            if (machine) {
                machine.name = name;
                machine.image = image;
                saveData(); // 修改为 saveData
                renderAvailableMachines();
                renderGameRooms();
                elements.editMachineModal.style.display = 'none';
                showMessage('机器信息已更新');
            }
        }

        // 渲染机器游戏列表
        function renderMachineGames(machineId) {
            if (!elements.availableGamesList || !elements.installedGamesList) return;
            const availableGamesList = elements.availableGamesList;
            const installedGamesList = elements.installedGamesList;
            availableGamesList.innerHTML = '';
            installedGamesList.innerHTML = '';
            const machineGames = gameData.machineGames[machineId] || [];
            const availableGames = gameData.games.filter(game => !machineGames.includes(game.id));
            if (availableGames.length === 0) {
                availableGamesList.innerHTML = '<p style="color: #666; font-size: 12px;">无可添加的游戏</p>';
            } else {
                availableGames.forEach(game => {
                    const gameOption = document.createElement('span');
                    gameOption.className = 'game-option add';
                    gameOption.textContent = game.name;
                    gameOption.addEventListener('click', () => {
                        addGameToMachine(machineId, game.id);
                    });
                    availableGamesList.appendChild(gameOption);
                });
            }
            if (machineGames.length === 0) {
                installedGamesList.innerHTML = '<p style="color: #666; font-size: 12px;">暂无已安装游戏</p>';
            } else {
                machineGames.forEach(gameId => {
                    const game = gameData.games.find(g => g.id === gameId);
                    if (game) {
                        const gameOption = document.createElement('span');
                        gameOption.className = 'game-option remove';
                        gameOption.textContent = game.name;
                        gameOption.addEventListener('click', () => {
                            removeGameFromMachine(machineId, gameId);
                        });
                        installedGamesList.appendChild(gameOption);
                    }
                });
            }
        }

        // 添加游戏到机器
        function addGameToMachine(machineId, gameId) {
            if (!gameData.machineGames[machineId]) {
                gameData.machineGames[machineId] = [];
            }
            if (!gameData.machineGames[machineId].includes(gameId)) {
                gameData.machineGames[machineId].push(gameId);
                saveData(); // 修改为 saveData
                renderMachineGames(machineId);
                showMessage('游戏已添加');
            }
        }

        // 从机器移除游戏
        function removeGameFromMachine(machineId, gameId) {
            if (gameData.machineGames[machineId]) {
                gameData.machineGames[machineId] = gameData.machineGames[machineId].filter(id => id !== gameId);
                saveData(); // 修改为 saveData
                renderMachineGames(machineId);
                showMessage('游戏已移除');
            }
        }

        // 显示游戏列表 (带搜索)
        function showGames(machineId) {
            const machine = gameData.machines.find(m => m.id === machineId);
            if (!machine) return;
            elements.gamesModalTitle.textContent = `${machine.name} 已安装游戏`;
            elements.gamesList.innerHTML = '';
            const machineGames = gameData.machineGames[machineId] || [];
            const games = gameData.games.filter(game => machineGames.includes(game.id));
            if (games.length === 0) {
                elements.gamesList.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">暂无游戏</p>';
            } else {
                games.forEach(game => {
                    const gameItem = document.createElement('div');
                    gameItem.className = 'game-item'; // 为过滤添加类名
                    gameItem.style.margin = '8px 0';
                    gameItem.style.padding = '8px';
                    gameItem.style.border = '1px solid #e2e8f0';
                    gameItem.style.borderRadius = '6px';
                    gameItem.style.backgroundColor = '#f8fafc';
                    const image = document.createElement('div');
                    image.style.width = '100%';
                    image.style.height = '80px';
                    image.style.background = '#f1f5f9';
                    image.style.backgroundSize = 'cover';
                    image.style.backgroundPosition = 'center';
                    image.style.borderRadius = '4px';
                    image.style.marginBottom = '6px';
                    if (game.image && game.image.trim() !== '') {
                        image.style.backgroundImage = `url(${game.image.trim()})`;
                    } else {
                        image.textContent = '无图片';
                        image.style.display = 'flex';
                        image.style.alignItems = 'center';
                        image.style.justifyContent = 'center';
                        image.style.color = '#94a3b8';
                        image.style.fontSize = '12px';
                    }
                    gameItem.appendChild(image);
                    const name = document.createElement('div');
                    name.className = 'game-name'; // 为过滤添加类名
                    name.style.fontWeight = '600';
                    name.style.color = '#333';
                    name.style.fontSize = '13px';
                    name.style.marginBottom = '4px';
                    name.textContent = game.name;
                    gameItem.appendChild(name);
                    const tags = document.createElement('div');
                    tags.style.display = 'flex';
                    tags.style.flexWrap = 'wrap';
                    tags.style.gap = '2px';
                    if (game.tags && game.tags.length > 0) {
                        game.tags.forEach(tag => {
                            const tagSpan = document.createElement('span');
                            tagSpan.style.background = '#667eea';
                            tagSpan.style.color = 'white';
                            tagSpan.style.padding = '2px 6px';
                            tagSpan.style.borderRadius = '12px';
                            tagSpan.style.fontSize = '0.7rem';
                            tagSpan.style.marginRight = '3px';
                            tagSpan.style.marginBottom = '3px';
                            tagSpan.textContent = tag;
                            tags.appendChild(tagSpan);
                        });
                    }
                    gameItem.appendChild(tags);
                    elements.gamesList.appendChild(gameItem);
                });
            }
            elements.gamesModal.style.display = 'flex';
            // 清空模态框内的搜索框
            if (elements.gamesModalSearch) elements.gamesModalSearch.value = '';
        }

        // 添加包厢
        function addRoom() {
            const name = prompt('请输入新包厢的名称:', `包厢${gameData.rooms.length + 1}`);
            if (name && name.trim() !== '') {
                const newRoom = {
                    id: `room${Date.now()}`,
                    name: name.trim(),
                    machines: [],
                    note: '',
                    activeSessions: {}
                };
                gameData.rooms.push(newRoom);
                saveData(); // 修改为 saveData
                renderGameRooms();
                showMessage('新包厢已添加');
            }
        }

        // 编辑包厢
        function editRoom(roomId) {
            const room = gameData.rooms.find(r => r.id === roomId);
            if (!room) return;
            currentEditingRoomId = roomId;
            if (elements.editRoomTitle) {
                elements.editRoomTitle.textContent = `编辑 ${room.name}`;
            }
            if (elements.roomName) elements.roomName.value = room.name;
            elements.editRoomModal.style.display = 'flex';
            setTimeout(() => {
                if (elements.roomName) elements.roomName.focus();
            }, 100);
        }

        // 保存包厢
        function saveRoom() {
            if (!currentEditingRoomId || !elements.roomName) return;
            const name = elements.roomName.value.trim();
            if (!name) {
                showMessage('包厢名称不能为空');
                return;
            }
            const room = gameData.rooms.find(r => r.id === currentEditingRoomId);
            if (room) {
                room.name = name;
                saveData(); // 修改为 saveData
                renderGameRooms();
                elements.editRoomModal.style.display = 'none';
                showMessage('包厢名称已更新');
            }
        }

        // 添加游戏机
        function addMachine() {
            const name = prompt('请输入新游戏机的名称:');
            if (name && name.trim() !== '') {
                const newMachine = {
                    id: `machine${Date.now()}`,
                    name: name.trim(),
                    image: 'https://placehold.co/120x120/blue/white?text=' + encodeURIComponent(name.trim())
                };
                gameData.machines.push(newMachine);
                gameData.machineGames[newMachine.id] = [];
                saveData(); // 修改为 saveData
                renderAvailableMachines();
                renderGameRooms();
                showMessage('新游戏机已添加');
            }
        }

        // 删除机器
        function deleteMachine(machineId) {
            const machine = gameData.machines.find(m => m.id === machineId);
            if (!machine) return;
            const inUse = gameData.rooms.some(room =>
                room.machines.some(m => m.id === machineId)
            );
            if (inUse) {
                showMessage('无法删除正在使用的机器，请先将其从包厢中移除');
                return;
            }
            if (confirm(`确定要删除游戏机 "${machine.name}" 吗？`)) {
                gameData.machines = gameData.machines.filter(m => m.id !== machineId);
                delete gameData.machineGames[machineId];
                saveData(); // 修改为 saveData
                renderAvailableMachines();
                renderGameRooms();
                showMessage('游戏机已删除');
            }
        }

        // 删除包厢
        function deleteRoom(roomId) {
            if (gameData.rooms.length <= 1) {
                showMessage('至少需要保留一个包厢');
                return;
            }
            if (confirm('确定要删除这个包厢吗？包厢内的所有数据将被清除。')) {
                gameData.rooms = gameData.rooms.filter(r => r.id !== roomId);
                saveData(); // 修改为 saveData
                renderAvailableMachines();
                renderGameRooms();
                showMessage('包厢已删除');
            }
        }

        // 从包厢移除机器
        function removeMachine(roomId, machineId) {
            const room = gameData.rooms.find(r => r.id === roomId);
            if (room) {
                room.machines = room.machines.filter(m => m.id !== machineId);
                if (room.activeSessions[machineId]) {
                    delete room.activeSessions[machineId];
                }
                saveData(); // 修改为 saveData
                renderAvailableMachines();
                renderGameRooms();
            }
        }

        // 编辑备注
        function editNote(roomId) {
            const room = gameData.rooms.find(r => r.id === roomId);
            if (!room) return;
            currentNoteRoomId = roomId;
            if (elements.noteContent) {
                elements.noteContent.value = room.note || '';
            }
            elements.editNoteModal.style.display = 'flex';
            setTimeout(() => {
                if (elements.noteContent) {
                    elements.noteContent.focus();
                }
            }, 100);
        }

        // 保存备注
        function saveNote() {
            if (!currentNoteRoomId || !elements.noteContent) return;
            const room = gameData.rooms.find(r => r.id === currentNoteRoomId);
            if (room) {
                room.note = elements.noteContent.value;
                saveData(); // 修改为 saveData
                renderGameRooms();
                elements.editNoteModal.style.display = 'none';
                showMessage('备注已保存');
            }
        }

        // 显示消息
        function showMessage(message) {
            alert(message);
        }

        // 格式化时间
        function formatTimeLeft(seconds) {
            if (seconds === null) return '';
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours}h${minutes}m${secs}s`;
        }

        // 启动定时器
        function startTimer() {
            setInterval(() => {
                if (!gameData) return;
                let updated = false;
                gameData.rooms.forEach(room => {
                    Object.keys(room.activeSessions).forEach(machineId => {
                        const session = room.activeSessions[machineId];
                        if (session && session.timeLeft !== null && session.timeLeft > 0) {
                            const newTimeLeft = Math.max(0, session.timeLeft - 1);
                            if (newTimeLeft !== session.timeLeft) {
                                session.timeLeft = newTimeLeft;
                                updated = true;
                            }
                        }
                    });
                });
                if (updated) {
                    // saveData(); // 不需要每次都保存，只在用户操作时保存
                    renderGameRooms();
                }
            }, 1000);
        }

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
            setupEventListeners();
            startTimer();
        });

        // 暴露全局函数
        window.startNewSession = startNewSession;
        window.showExtendModal = showExtendModal;
        window.endSession = endSession;
        window.editNote = editNote;
        window.editRoom = editRoom;
        window.deleteRoom = deleteRoom;
        window.removeMachine = removeMachine;
        window.showGames = showGames;
        window.editMachine = editMachine;
        window.deleteMachine = deleteMachine;
        window.addRoom = addRoom;
        window.addMachine = addMachine;
    </script>
</body>
</html>