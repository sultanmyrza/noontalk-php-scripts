# Noon Talk PHP Script

This PHP script handles push notifications for the Noon Talk application using [Expo's Push Notification Service](https://docs.expo.dev/push-notifications/sending-notifications/). Below are instructions on how to run the server and test it using the provided curl commands.

## Running the Server

1. Start the PHP development server:
```bash
php -S 0.0.0.0:8080
```

## Testing the API

There are three ways to test the notification endpoints using the provided curl files:

### 1. Send Single Notification
Use `send-single.curl`:
```bash
curl -X POST http://localhost:8080/send-single \
  -H "Content-Type: application/json" \
  -d '{
    "to": "ExponentPushToken[XXXXXXXXXXXXXXXXXXXXX]",
    "title": "Hello User",
    "body": "This is a test notification message",
    "_contentAvailable": true,
    "priority": "high",
    "data": {
      "content-available": 1,
      "messageId": "12345",
      "type": "testing_foreground_notification",
      "timestamp": 1648236589,
      "additionalInfo": "Custom data example"
    }
  }'
```

### 2. Send Batch Notifications
Use `send-batch.curl`:
```bash
curl -X POST http://localhost:8080/send-batch \
  -H "Content-Type: application/json" \
  -d '{
    "notifications": [
      {
        "to": "ExponentPushToken[XXXXXXXXXXXXXXXXXXXXX]",
        "title": "Batch Message 1",
        "body": "This is the first notification in batch",
        "_contentAvailable": true,
        "priority": "high",
        "data": {
          "content-available": 1,
          "messageId": "batch_1",
          "type": "batch_notification"
        }
      },
      {
        "to": "ExponentPushToken[YYYYYYYYYYYYYYYYYYYYY]",
        "title": "Batch Message 2",
        "body": "This is the second notification in batch",
        "_contentAvailable": true,
        "priority": "high",
        "data": {
          "content-available": 1,
          "messageId": "batch_2",
          "type": "batch_notification"
        }
      }
    ]
  }'
```

### 3. Send Gzipped Notifications
Use `send-gzip.curl` for sending compressed notification data with the same payload format as batch notifications.

### 4. Send Headless Notifications
Use `send-headless.curl` for sending silent background notifications that don't display any visual alerts:
```bash
curl -X POST http://localhost:8080/send-headless \
  -H "Content-Type: application/json" \
  -d '{
    "to": "ExponentPushToken[XXXXXXXXXXXXXXXXXXXXX]",
    "_contentAvailable": true,
    "data": {
      "content-available": 1,
      "messageId": "headless_1",
      "type": "background_sync",
      "timestamp": 1648236589,
      "operation": "sync_data"
    }
  }'
```

Headless notifications are useful for:
- Silent background data synchronization
- Triggering background tasks
- Updating app content without user interaction
- Maintaining real-time data consistency

## Supported Notification Fields

The API supports all Expo Push Notification fields, including but not limited to:

- `to`: (Required) The Expo push token
- `title`: The title of the notification
- `body`: The message body
- `_contentAvailable`: Boolean for content-available flag
- `priority`: Notification priority ("default", "normal", "high")
- `data`: Custom data object for your application
- Any other fields supported by Expo's Push API

## File Decryption Endpoint

The server also supports decrypting and saving encrypted files using AES-256-CBC encryption.

### Testing File Decryption
Use curl to test file decryption:
```bash
curl -X POST http://localhost:8080/decrypt-file \
  -F "file=@path/to/your/encrypted/file.txt"
```

The server will:
1. Receive the encrypted file
2. Decrypt it using AES-256-CBC with the configured key and IV
3. Save the decrypted content to the `tmp-uploads` directory
4. Return the path to the decrypted file

### Response Format
Success response:
```json
{
    "message": "File uploaded and decrypted successfully!",
    "path": "/path/to/decrypted/file.txt"
}
```

Error response:
```json
{
    "error": "Error message here"
}
```

## React Native File Upload Endpoint

The server provides a dedicated endpoint for handling encrypted file uploads from React Native applications.

### Endpoint Details
- URL: `/upload-encrypted`
- Method: `PATCH`
- Headers:
  - `Content-Type`: File MIME type (e.g., 'video/mp4', 'image/jpeg')
  - `X-Encrypted`: Set to 'true'

### Features
- Accepts AES-256-CBC encrypted binary content
- Automatically detects file type from Content-Type header
- Supports video and image uploads
- Handles base64 encoded content
- Saves decrypted files with appropriate extensions

### Example Usage with React Native
```javascript
const task = FileSystem.createUploadTask(
  'http://localhost:8080/upload-encrypted',
  encryptedFileUri,
  {
    uploadType: FileSystem.FileSystemUploadType.BINARY_CONTENT,
    headers: {
      "Content-Type": "video/mp4",
      "X-Encrypted": "true",
    },
    httpMethod: "PATCH",
  }
);

const result = await task.uploadAsync();
```

### Response Format
Success response:
```json
{
    "message": "File uploaded and decrypted successfully!",
    "path": "/path/to/decrypted/file.mp4"
}
```

Error response:
```json
{
    "error": "Error message here"
}
```

## Note
- Replace the `ExponentPushToken[XXXXXXXXXXXXXXXXXXXXX]` with actual Expo push tokens
- Ensure you have PHP installed on your system
- The server must be running on port 8080 for the curl commands to work
- Make sure the `tmp-uploads` directory is writable by the web server
- For encrypted uploads, ensure your AES key and IV match between client and server