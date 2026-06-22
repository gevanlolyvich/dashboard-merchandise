<?php
header('Content-Type: application/json; charset=utf-8');

$allowedOrigins = ['http://127.0.0.1:5500', 'http://localhost:5500', 'http://localhost', 'http://127.0.0.1', 'http://localhost:8000', 'http://127.0.0.1:8000', 'https://merchandise.jxboard.id'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pathInfo = $_SERVER['PATH_INFO'] ?? ($_GET['_path'] ?? '');
$targetUrl = 'http://172.16.0.17:3100/api/v1/ginee/' . ltrim($pathInfo, '/') . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
// remove internal _path param from forwarded query
$targetUrl = preg_replace('/[&?]_path=[^&]*/', '', $targetUrl);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Gagal terhubung ke Ginee API: ' . $error]);
    exit;
}

http_response_code($httpCode);
echo $response;
