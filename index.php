<?php

/**
 * Simple REST API for Sending Push Notifications
 * 
 * Endpoints:
 * 1. POST /send-single: Send a single notification.
 * 2. POST /send-batch: Send multiple notifications.
 * 3. POST /send-gzip: Send multiple notifications with gzip compression.
 * 
 * Requirements:
 * - PHP 7.4 or later
 * - cURL enabled
 * - Gzip support (default in most PHP installations)
 */

// Set content type to JSON
header('Content-Type: application/json');

/**
 * Function to handle HTTP responses.
 *
 * @param int $statusCode HTTP status code.
 * @param array|string $data Response data.
 */
function respond(int $statusCode, $data)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Define the functions for sending push notifications

function sendPushNotification(string $to, string $title, string $body): array
{
    // Expo Push API endpoint
    $url = "https://exp.host/--/api/v2/push/send";

    $headers = [
        "Host: exp.host",
        "Accept: application/json",
        "Content-Type: application/json"
    ];

    $data = [
        "to" => $to,
        "title" => $title,
        "body" => $body
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "httpCode" => $httpCode,
        "response" => json_decode($response, true)
    ];
}

function sendBatchPushNotifications(array $notifications): array
{
    $url = "https://exp.host/--/api/v2/push/send";

    $headers = [
        "Host: exp.host",
        "Accept: application/json",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notifications));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "httpCode" => $httpCode,
        "response" => json_decode($response, true)
    ];
}

function sendGzipCompressedPushNotifications(array $notifications): array
{
    $url = "https://exp.host/--/api/v2/push/send";

    $headers = [
        "Host: exp.host",
        "Accept: application/json",
        "Content-Type: application/json",
        "Content-Encoding: gzip",
        "Accept-Encoding: gzip, deflate"
    ];

    $jsonPayload = json_encode($notifications);
    if ($jsonPayload === false) {
        return [
            "httpCode" => 0,
            "response" => "Failed to encode notifications to JSON: " . json_last_error_msg()
        ];
    }
    $compressedPayload = gzencode($jsonPayload);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $compressedPayload);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "httpCode" => $httpCode,
        "response" => json_decode($response, true)
    ];
}

// Route based on the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    respond(405, ["error" => "Only POST requests are allowed."]);
}

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

if ($data === null) {
    respond(400, ["error" => "Invalid JSON payload."]);
}

switch ($requestUri) {
    case '/send-single':
        if (!isset($data['to'], $data['title'], $data['body'])) {
            respond(400, ["error" => "Missing required fields: to, title, body."]);
        }

        $response = sendPushNotification($data['to'], $data['title'], $data['body']);
        respond($response["httpCode"], $response["response"]);
        break;

    case '/send-batch':
        if (!isset($data['notifications']) || !is_array($data['notifications'])) {
            respond(400, ["error" => "Missing or invalid notifications field."]);
        }

        $response = sendBatchPushNotifications($data['notifications']);
        respond($response["httpCode"], $response["response"]);
        break;

    case '/send-gzip':
        if (!isset($data['notifications']) || !is_array($data['notifications'])) {
            respond(400, ["error" => "Missing or invalid notifications field."]);
        }

        $response = sendGzipCompressedPushNotifications($data['notifications']);
        respond($response["httpCode"], $response["response"]);
        break;

    default:
        respond(404, ["error" => "Endpoint not found."]);
}
