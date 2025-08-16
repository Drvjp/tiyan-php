<?php
// api/get_data.php
header('Content-Type: application/json');

$data_url = 'https://data-gold-xi.vercel.app/data.json'; // *** 请替换为实际的 Vercel 数据文件 URL ***
$json_data = @file_get_contents($data_url);

if ($json_data === FALSE) {
    http_response_code(500); // 内部服务器错误
    echo json_encode(['error' => '无法从 Vercel 获取数据']);
    exit();
}

$data = json_decode($json_data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Vercel 数据格式错误']);
    exit();
}

echo $json_data; // 直接输出获取到的 JSON 数据
?>