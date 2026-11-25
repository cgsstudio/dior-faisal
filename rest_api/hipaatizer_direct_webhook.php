<?php

/**
 * Dior Medical - Direct HIPAAtizer Webhook Receiver
 *
 * Purpose:
 * - Receives HIPAAtizer POST requests without going through /wp-json/ REST routing.
 * - Avoids server/WAF rules that may return 403 for WordPress REST requests.
 * - Uses the same validated HIPAAtizer importer as the plugin REST endpoint.
 *
 * URL:
 * https://YOUR-DOMAIN/diormedical/wp-content/plugins/dior-medical-dashboard/rest_api/hipaatizer_direct_webhook.php?token=YOUR_SECRET
 */

// Load WordPress so all plugin/database/user functions are available.
$wp_load = '';
$curr_dir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    $curr_dir = dirname($curr_dir);
    if (file_exists($curr_dir . '/wp-load.php')) {
        $wp_load = $curr_dir . '/wp-load.php';
        break;
    }
}

if (!$wp_load || !file_exists($wp_load)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode([
        'success' => false,
        'code' => 'wordpress_not_found',
        'message' => 'WordPress core could not be loaded.',
    ]);
    exit;
}

require_once $wp_load;

header('Content-Type: application/json; charset=utf-8');

// Handle CORS preflight for browser/Postman testing.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST, OPTIONS');
    echo wp_json_encode([
        'success' => false,
        'code' => 'method_not_allowed',
        'message' => 'Only POST requests are accepted.',
    ]);
    exit;
}

// The plugin is normally loaded by WordPress and this file therefore already
// has access to dior_fallback_save_hipaa_intake(). Require the REST bridge only
// as a safe fallback for unusual WordPress/plugin loading situations.
if (!function_exists('dior_fallback_save_hipaa_intake')) {
    $bridge_file = __DIR__ . '/hipaatizer_rest_api.php';
    if (file_exists($bridge_file)) {
        require_once $bridge_file;
    }
}

// Validate the shared webhook token configured in HIPAAtizer.
// Accept the live token DiorHipaa2026X9K7M4P8, constants, or DB option.
$received_token = isset($_GET['token']) ? (string) wp_unslash($_GET['token']) : '';
if ($received_token === '' && isset($_POST['token'])) {
    $received_token = (string) wp_unslash($_POST['token']);
}

$valid_tokens = array_filter([
    'DiorHipaa2026X9K7M4P8',
    defined('DIOR_HIPAATIZER_WEBHOOK_SECRET') ? (string) DIOR_HIPAATIZER_WEBHOOK_SECRET : '',
    (string) get_option('dior_hipaatizer_webhook_secret', ''),
]);

// Persist the default secret to database if not already stored
if (empty(get_option('dior_hipaatizer_webhook_secret'))) {
    update_option('dior_hipaatizer_webhook_secret', 'DiorHipaa2026X9K7M4P8', false);
}

$token_matched = false;
foreach ($valid_tokens as $secret) {
    if ($secret !== '' && hash_equals($secret, $received_token)) {
        $token_matched = true;
        break;
    }
}

if (!$token_matched) {
    http_response_code(401);
    echo wp_json_encode([
        'success' => false,
        'code' => 'invalid_webhook_token',
        'message' => 'Invalid HIPAAtizer webhook token.',
    ]);
    exit;
}

// Read and decode the JSON body sent by HIPAAtizer.
$raw_body = file_get_contents('php://input');
$payload = json_decode($raw_body, true);

if (!is_array($payload) || empty($payload)) {
    // Fallback: check $_POST form variables
    if (!empty($_POST) && is_array($_POST)) {
        $payload = $_POST;
    }
}

if (!is_array($payload) || empty($payload)) {
    http_response_code(400);
    echo wp_json_encode([
        'success' => false,
        'code' => 'invalid_json',
        'message' => 'The webhook body is empty or is not valid JSON.',
    ]);
    exit;
}

try {
    if (!function_exists('dior_fallback_save_hipaa_intake')) {
        throw new RuntimeException('Dior Medical HIPAAtizer importer is not available.');
    }

    // Use the same importer that already passed the Postman test. It supports
    // both internal HIPAAtizer keys and exact inserted-variable labels.
    dior_fallback_save_hipaa_intake($payload);

    http_response_code(200);
    echo wp_json_encode([
        'success' => true,
        'message' => 'HIPAAtizer data received and synchronized.',
    ]);
} catch (Throwable $e) {
    // Do not expose stack traces or sensitive PHI in the HTTP response.
    error_log('[Dior HIPAAtizer Direct Webhook] ' . $e->getMessage());

    http_response_code(500);
    echo wp_json_encode([
        'success' => false,
        'code' => 'webhook_processing_error',
        'message' => 'HIPAAtizer data could not be synchronized.',
    ]);
}

exit;
