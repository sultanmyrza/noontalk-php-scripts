# Noon Talk PHP Script

This PHP script handles push notifications for the Noon Talk application. Below are instructions on how to run the server and test it using the provided curl commands.

## Running the Server

1. Start the PHP development server:
```bash
php -S localhost:8080
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
    "body": "This is a test notification message"
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
        "body": "This is the first notification in batch"
      },
      {
        "to": "ExponentPushToken[YYYYYYYYYYYYYYYYYYYYY]",
        "title": "Batch Message 2",
        "body": "This is the second notification in batch"
      }
    ]
  }'
```

### 3. Send Gzipped Notifications
Use `send-gzip.curl` for sending compressed notification data.

## Note
- Replace the `ExponentPushToken[XXXXXXXXXXXXXXXXXXXXX]` with actual Expo push tokens
- Ensure you have PHP installed on your system
- The server must be running on port 8080 for the curl commands to work