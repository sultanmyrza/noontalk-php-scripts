<?php

/**
 * Simple REST API for Sending Push Notifications using Expo's HTTP/2 API
 * 
 * This script provides three different methods for sending push notifications:
 * 1. Single notification: Send one notification at a time
 * 2. Batch notifications: Send up to 100 notifications in a single request
 * 3. Gzip compressed notifications: Send batch notifications with compression for bandwidth efficiency
 * 
 * Endpoints:
 * - POST /send-single: Send a single notification
 * - POST /send-batch: Send multiple notifications (up to 100)
 * - POST /send-gzip: Send multiple notifications with gzip compression
 * 
 * Requirements:
 * - PHP 7.4 or later
 * - cURL with HTTP/2 support
 * - Gzip support (for compression endpoint)
 */

// Set content type to JSON
header('Content-Type: application/json');

/**
 * Function to handle HTTP responses
 *
 * @param int $statusCode HTTP status code
 * @param array|string $data Response data
 * @return never
 */
function respond(int $statusCode, $data)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Send a single push notification to Expo's Push API
 * 
 * This is the simplest method for sending notifications. Use this when you only need
 * to send one notification at a time.
 *
 * @param array $notification The notification payload
 * @return array Response containing HTTP code and API response
 */
function sendPushNotification(array $notification): array
{
    // Validate required fields
    if (!isset($notification['to'])) {
        return [
            "httpCode" => 400,
            "response" => ["error" => "Missing required field: to"]
        ];
    }

    // Expo Push API endpoint
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
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "httpCode" => $httpCode,
        "response" => json_decode($response, true)
    ];
}

/**
 * Send multiple push notifications in a single batch request
 * 
 * Benefits over single notifications:
 * - More efficient for sending multiple notifications
 * - Reduces number of HTTP requests
 * - Can send up to 100 notifications in one request
 * - Better performance for bulk notifications
 *
 * @param array $notifications Array of notification objects
 * @return array Response containing HTTP code and API response
 */
function sendBatchPushNotifications(array $notifications): array
{
    // Validate required fields for each notification
    foreach ($notifications as $notification) {
        if (!isset($notification['to'])) {
            return [
                "httpCode" => 400,
                "response" => ["error" => "Each notification must have a 'to' field"]
            ];
        }
    }

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

/**
 * Send multiple push notifications with gzip compression
 * 
 * Benefits over regular batch:
 * - Significantly reduced bandwidth usage (especially for large batches)
 * - Faster transmission of large payloads
 * - Recommended for sending large numbers of notifications
 * - Useful in bandwidth-constrained environments
 *
 * @param array $notifications Array of notification objects
 * @return array Response containing HTTP code and API response
 */
function sendGzipCompressedPushNotifications(array $notifications): array
{
    // Validate required fields for each notification
    foreach ($notifications as $notification) {
        if (!isset($notification['to'])) {
            return [
                "httpCode" => 400,
                "response" => ["error" => "Each notification must have a 'to' field"]
            ];
        }
    }

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
            "httpCode" => 400,
            "response" => ["error" => "Failed to encode notifications to JSON: " . json_last_error_msg()]
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

/**
 * Send a headless push notification
 * 
 * Benefits:
 * - No visible notification to the user
 * - Useful for data synchronization
 * - Can wake up the app in the background
 * - Ideal for silent content updates
 *
 * @param array $notification The notification payload
 * @return array Response containing HTTP code and API response
 */
function sendHeadlessPushNotification(array $notification): array
{
    // Validate required fields
    if (!isset($notification['to'])) {
        return [
            "httpCode" => 400,
            "response" => ["error" => "Missing required field: to"]
        ];
    }

    // Ensure this is a headless notification by setting required fields
    $notification['_contentAvailable'] = true;
    $notification['content_available'] = true;

    // Remove visual notification fields if present
    unset($notification['title']);
    unset($notification['body']);
    unset($notification['sound']);

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
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));

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
        $response = sendPushNotification($data);
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

    case '/send-headless':
        $response = sendHeadlessPushNotification($data);
        respond($response["httpCode"], $response["response"]);
        break;

    default:
        respond(404, ["error" => "Endpoint not found."]);
}
