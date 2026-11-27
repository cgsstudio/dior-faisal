<?php
/**
 * Stripe Checkout webhook: finalize paid Dior Medical appointments.
 */
if (!defined('ABSPATH')) {
    exit;
}
final class Dior_Stripe_Webhook {
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route('dior-stripe/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(WP_REST_Request $request) {
        $secret = (string) get_option('dior_stripe_test_webhook_secret', '');
        if ($secret === '') {
            return new WP_REST_Response(['error' => 'Webhook signing secret is not configured'], 500);
        }

        $header = (string) $request->get_header('stripe-signature');
        $parts = [];
        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]][] = $pair[1];
            }
        }
        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? [];
        if (!$timestamp || abs(time() - $timestamp) > 300 || !$signatures) {
            return new WP_REST_Response(['error' => 'Invalid or expired Stripe signature'], 400);
        }

        $payload = $request->get_body();
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $valid = false;
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            return new WP_REST_Response(['error' => 'Stripe signature verification failed'], 400);
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || empty($event['type'])) {
            return new WP_REST_Response(['error' => 'Invalid event payload'], 400);
        }

        if (!in_array($event['type'], ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return new WP_REST_Response(['received' => true, 'ignored' => true], 200);
        }

        $session = $event['data']['object'] ?? [];
        if (($session['payment_status'] ?? '') !== 'paid') {
            // Async payment methods can complete later; wait for async_payment_succeeded.
            return new WP_REST_Response(['received' => true, 'awaiting_payment' => true], 200);
        }

        $session_id = sanitize_text_field($session['id'] ?? '');
        $token = sanitize_text_field($session['metadata']['dior_booking_token'] ?? '');
        if ($session_id === '' || $token === '') {
            return new WP_REST_Response(['error' => 'Missing checkout session ID or booking token'], 400);
        }

        $completed = get_transient('dior_completed_stripe_booking_' . md5($session_id));
        if (is_array($completed) && !empty($completed['appointment'])) {
            return new WP_REST_Response(['received' => true, 'already_processed' => true], 200);
        }

        $pending = get_transient('dior_pending_stripe_booking_' . $token);
        if (!is_array($pending) || empty($pending['patient_id'])) {
            return new WP_REST_Response(['error' => 'Pending booking not found or expired'], 404);
        }

        // Reuse the existing verified Stripe finalizer; it checks the session directly with Stripe.
        $_GET['dior_stripe_status'] = 'success';
        $_GET['dior_booking_token'] = $token;
        $_GET['session_id'] = $session_id;
        Dior_Stripe_Booking::handle_stripe_return();

        $notice = Dior_Stripe_Booking::get_last_notice();
        $completed = get_transient('dior_completed_stripe_booking_' . md5($session_id));
        if (is_array($completed) && !empty($completed['appointment'])) {
            return new WP_REST_Response(['received' => true, 'appointment_created' => true], 200);
        }

        return new WP_REST_Response([
            'error' => is_array($notice) ? ($notice['message'] ?? 'Appointment finalization failed') : 'Appointment finalization failed',
        ], 500);
    }
}