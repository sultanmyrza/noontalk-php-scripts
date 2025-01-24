<?php

/**
 * Simple REST API for Sending Push Notifications using Expo's HTTP/2 API
 * 
 * This script provides the following functionalities:
 * 1. Single notification: Send one notification at a time
 * 2. Batch notifications: Send up to 100 notifications in a single request
 * 3. Gzip compressed notifications: Send batch notifications with compression for bandwidth efficiency
 * 4. Headless notifications: Send silent background notifications
 * 5. File decryption: Decrypt and save uploaded encrypted files
 * 
 * Endpoints:
 * - POST /send-single: Send a single notification
 * - POST /send-batch: Send multiple notifications (up to 100)
 * - POST /send-gzip: Send multiple notifications with gzip compression
 * - POST /send-headless: Send silent background notifications
 * - POST /decrypt-file: Upload, decrypt and save encrypted files
 * - PATCH /upload-encrypted: Handle encrypted file upload from React Native app
 * 
 * Requirements:
 * - PHP 7.4 or later
 * - cURL with HTTP/2 support
 * - Gzip support (for compression endpoint)
 * - OpenSSL support (for file decryption)
 */

// Configuration for file decryption
define('AES_KEY', "Noon$Talker$2025.01.24"); // 32 bytes
define('AES_IV', "1234567890123456"); // 16 bytes
define('UPLOAD_DIR', __DIR__ . '/tmp-uploads/');

// Ensure upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

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

/**
 * Handle file decryption and saving
 * 
 * @return array Response containing status and message/error
 */
function handleFileDecryption(): array
{
    if (empty($_FILES['file']['tmp_name'])) {
        return [
            "httpCode" => 400,
            "response" => ["error" => "No file uploaded."]
        ];
    }

    $uploadedFile = $_FILES['file']['tmp_name'];
    $encryptedContent = file_get_contents($uploadedFile);

    // Decrypt content
    $decryptedContent = openssl_decrypt(
        $encryptedContent,
        "aes-256-cbc",
        AES_KEY,
        OPENSSL_RAW_DATA,
        AES_IV
    );

    if ($decryptedContent === false) {
        return [
            "httpCode" => 400,
            "response" => ["error" => "Failed to decrypt file."]
        ];
    }

    // Save decrypted content
    $decryptedFilePath = UPLOAD_DIR . 'decrypted_' . time() . '.txt';
    if (!file_put_contents($decryptedFilePath, $decryptedContent)) {
        return [
            "httpCode" => 500,
            "response" => ["error" => "Failed to save decrypted file."]
        ];
    }

    return [
        "httpCode" => 200,
        "response" => [
            "message" => "File uploaded and decrypted successfully!",
            "path" => $decryptedFilePath
        ]
    ];
}

/**
 * Handle encrypted file upload from React Native app
 * 
 * @return array Response containing status and message/error
 */
function handleEncryptedUpload(): array
{
    // Verify it's an encrypted upload
    if (!isset($_SERVER['HTTP_X_ENCRYPTED']) || $_SERVER['HTTP_X_ENCRYPTED'] !== 'true') {
        return [
            "httpCode" => 400,
            "response" => ["error" => "Missing encryption header."]
        ];
    }

    // Get the raw POST data (binary content)
    $encryptedContent = file_get_contents('php://input');
    if (empty($encryptedContent)) {
        return [
            "httpCode" => 400,
            "response" => ["error" => "No content received."]
        ];
    }

    // Decrypt content
    $decryptedContent = openssl_decrypt(
        $encryptedContent,
        "aes-256-cbc",
        AES_KEY,
        OPENSSL_RAW_DATA,
        AES_IV
    );

    if ($decryptedContent === false) {
        return [
            "httpCode" => 400,
            "response" => ["error" => "Failed to decrypt content."]
        ];
    }

    // Get content type and determine file extension
    $contentType = $_SERVER['HTTP_CONTENT_TYPE'] ?? 'video/mp4';
    $extension = match ($contentType) {
        'video/mp4' => 'mp4',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        default => 'mp4'
    };

    // Save decrypted content with proper extension
    $decryptedFilePath = UPLOAD_DIR . 'upload_' . time() . '.' . $extension;
    if (!file_put_contents($decryptedFilePath, base64_decode($decryptedContent))) {
        return [
            "httpCode" => 500,
            "response" => ["error" => "Failed to save decrypted file."]
        ];
    }

    return [
        "httpCode" => 200,
        "response" => [
            "message" => "File uploaded and decrypted successfully!",
            "path" => $decryptedFilePath
        ]
    ];
}

// Route based on the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Allow both POST and PATCH methods
if ($method !== 'POST' && $method !== 'PATCH') {
    respond(405, ["error" => "Only POST and PATCH requests are allowed."]);
}

// Handle PATCH request for file upload separately
if ($method === 'PATCH' && $requestUri === '/upload-encrypted') {
    $response = handleEncryptedUpload();
    respond($response["httpCode"], $response["response"]);
    exit;
}

// For POST requests, parse JSON payload
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

if ($data === null && $method === 'POST') {
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

    case '/decrypt-file':
        $response = handleFileDecryption();
        respond($response["httpCode"], $response["response"]);
        break;

    case '/upload-encrypted':
        $response = handleEncryptedUpload();
        respond($response["httpCode"], $response["response"]);
        break;

    default:
        respond(404, ["error" => "Endpoint not found."]);
}
