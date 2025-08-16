<?php
// api/save_data.php (模拟版本)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '只允许 POST 请求']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => '无效的 JSON 数据']);
    exit();
}

// 模拟保存成功
// 在实际应用中，这里应该将 $data 写入到 Vercel Blob/Database 或其他持久化存储
error_log("模拟保存数据: " . print_r($data, true)); // 记录到服务器日志

echo json_encode(['success' => true, 'message' => '数据已接收 (模拟保存成功)']);
?>