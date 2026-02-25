<?php
/**
 * QBO Disconnect Endpoint — LedgerRail
 * 
 * Intuit calls this URL when a user disconnects TrustMVP/LedgerRail
 * from their QuickBooks Online account.
 * 
 * Minimum viable: accept POST, log the event, return 200 OK.
 * 
 * Intuit docs: The disconnect URL must respond with HTTP 200.
 * POST body contains JSON with realmId identifying the QBO company.
 * 
 * @see https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization
 */

// Set response headers
header('Content-Type: application/json');
header('X-LedgerRail-Version: 1.0.0');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method Not Allowed',
        'message' => 'This endpoint only accepts POST requests.'
    ]);
    exit;
}

// Read the incoming payload
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

// Log the disconnect event
$logEntry = [
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
    'event' => 'qbo_disconnect',
    'realmId' => $payload['realmId'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'rawPayload' => $rawInput
];

// Write to log file (outside public_html ideally, but this works for MVP)
$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/disconnect_' . date('Y-m') . '.log';
@file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);

// Return 200 OK — this is what Intuit requires
http_response_code(200);
echo json_encode([
    'status' => 'ok',
    'message' => 'Disconnect acknowledged.',
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
]);
