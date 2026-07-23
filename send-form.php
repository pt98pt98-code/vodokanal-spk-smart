<?php

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'message' => 'Метод не поддерживается'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$configPath = dirname(__DIR__) . '/telegram-config.php';

if (!is_file($configPath)) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'message' => 'Конфигурация не найдена'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

require_once $configPath;

$name = trim(strip_tags($_POST['name'] ?? ''));
$phone = trim(strip_tags($_POST['phone'] ?? ''));
$email = trim(strip_tags($_POST['email'] ?? ''));
$service = trim(strip_tags($_POST['service'] ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));
$page = trim(strip_tags($_POST['page'] ?? ''));

if ($name === '' || $phone === '') {
    http_response_code(422);

    echo json_encode([
        'ok' => false,
        'message' => 'Укажите имя и телефон'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$text = "Новая заявка с сайта СПК Смарт\n\n";
$text .= "Имя: {$name}\n";
$text .= "Телефон: {$phone}\n";

if ($email !== '') {
    $text .= "Email: {$email}\n";
}

if ($service !== '') {
    $text .= "Услуга: {$service}\n";
}

if ($message !== '') {
    $text .= "Сообщение: {$message}\n";
}

if ($page !== '') {
    $text .= "Страница: {$page}\n";
}

$telegramUrl = 'https://api.telegram.org/bot'
    . TELEGRAM_BOT_TOKEN
    . '/sendMessage';

$postData = http_build_query([
    'chat_id' => TELEGRAM_CHAT_ID,
    'text' => $text
]);

$ch = curl_init($telegramUrl);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$result = json_decode((string) $response, true);

if (!is_array($result) || ($result['ok'] ?? false) !== true) {
    http_response_code(502);

    echo json_encode([
        'ok' => false,
        'message' => 'Telegram не принял сообщение',
        'telegram_error' => $result['description'] ?? $curlError ?: 'Нет ответа',
        'http_code' => $httpCode
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Заявка отправлена'
], JSON_UNESCAPED_UNICODE);