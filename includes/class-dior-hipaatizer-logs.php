<?php
/**
 * Admin-only HIPAAtizer intake inspection page.
 *
 * Displays data already stored by the existing HIPAAtizer importer.
 * This page does not receive webhooks, alter patient records, or create logs.
 *
 * @package Dior Medical
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Dior_HIPAAtizer_Logs
{
    /**
     * Register the temporary admin page.
     */
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
    }

    /**
     * Add a top-level page restricted to site administrators.
     */
    public static function register_menu()
    {
        add_menu_page(
            'HIPAAtizer Data Logs',
            'HIPAAtizer Logs',
            'manage_options',
            'dior-hipaatizer-logs',
            [__CLASS__, 'render_page'],
            'dashicons-clipboard',
            58
        );
    }

    /**
     * Find one patient by WordPress user ID or exact email address.
     *
     * Requiring an exact identifier avoids listing large sets of sensitive
     * patient records in the admin screen.
     */
    private static function find_patient($search)
    {
        if (ctype_digit($search)) {
            $user = get_userdata((int) $search);
            return ($user && class_exists('Dior_Auth_Service') && Dior_Auth_Service::is_patient($user->ID)) ? $user : null;
        }

        if (is_email($search)) {
            $user = get_user_by('email', sanitize_email($search));
            return ($user && in_array('patient', (array) $user->roles, true)) ? $user : null;
        }

        return null;
    }

    /**
     * Safely render a scalar or structured value.
     */
    private static function render_value($value)
    {
        if ($value === '' || $value === null || $value === false) {
            return '<span class="dior-log-empty">Not stored</span>';
        }

        if (is_array($value) || is_object($value)) {
            $value = wp_json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return '<pre class="dior-log-json">' . esc_html((string) $value) . '</pre>';
        }

        return esc_html((string) $value);
    }

    /**
     * Render the admin inspection screen.
     */
    public static function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'dior-medical'));
        }

        $search = isset($_GET['dior_patient_search'])
            ? sanitize_text_field(wp_unslash($_GET['dior_patient_search']))
            : '';
        $patient = null;
        $notice = '';

        if (isset($_GET['dior_patient_search'])) {
            check_admin_referer('dior_hipaatizer_logs_search');

            if ($search === '') {
                $notice = 'Enter a patient WordPress User ID or exact email address.';
            } else {
                $patient = self::find_patient($search);
                if (!$patient) {
                    $notice = 'No patient account matched that User ID or email address.';
                }
            }
        }

        ?>
        <div class="wrap dior-hipaa-logs-wrap">
            <h1>HIPAAtizer Data Logs</h1>
            <p>Inspect HIPAAtizer intake information already stored against a patient account. This screen is read-only and does not trigger a new webhook request.</p>

            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="dior-hipaa-search">
                <input type="hidden" name="page" value="dior-hipaatizer-logs">
                <?php wp_nonce_field('dior_hipaatizer_logs_search'); ?>
                <label for="dior-patient-search"><strong>Patient WordPress User ID or exact email</strong></label>
                <input type="text" id="dior-patient-search" name="dior_patient_search" value="<?php echo esc_attr($search); ?>" class="regular-text" placeholder="e.g. 123 or patient@example.com" required>
                <?php submit_button('Search Patient', 'primary', '', false); ?>
            </form>

            <?php if ($notice) : ?>
                <div class="notice notice-warning"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <?php if ($patient) :
                $user_id = (int) $patient->ID;
                $intake = get_user_meta($user_id, 'dior_hipaa_intake', true);
                $intake = is_array($intake) ? $intake : [];
                $raw_payload = isset($intake['raw_data']) ? $intake['raw_data'] : [];
                $raw_payload_exists = !empty($raw_payload);
                $submitted = get_user_meta($user_id, 'dior_hipaa_intake_submitted', true);

                $fields = [
                    'Height' => ['meta' => 'height', 'intake' => 'height'],
                    'Weight' => ['meta' => 'weight', 'intake' => 'weight'],
                    'Blood Group' => ['meta' => 'blood_group', 'intake' => 'blood_group'],
                    'First Name' => ['meta' => 'first_name', 'intake' => 'first_name'],
                    'Last Name' => ['meta' => 'last_name', 'intake' => 'last_name'],
                    'Phone' => ['meta' => 'phone', 'intake' => 'phone'],
                    'Date of Birth' => ['meta' => 'dob', 'intake' => 'dob'],
                    'Gender' => ['meta' => 'gender', 'intake' => 'gender'],
                    'Address' => ['meta' => 'address', 'intake' => 'address'],
                    'City' => ['meta' => 'city', 'intake' => 'city'],
                    'State' => ['meta' => 'state', 'intake' => 'state'],
                    'ZIP / PIN Code' => ['meta' => 'zip', 'intake' => 'zip'],
                ];
                ?>
                <hr>
                <h2>Patient Account</h2>
                <table class="widefat striped dior-log-table">
                    <tbody>
                        <tr><th>WordPress User ID</th><td><?php echo esc_html((string) $user_id); ?></td></tr>
                        <tr><th>Name</th><td><?php echo esc_html($patient->display_name); ?></td></tr>
                        <tr><th>Email</th><td><?php echo esc_html($patient->user_email); ?></td></tr>
                        <tr><th>HIPAAtizer Submitted Flag</th><td><?php echo $submitted ? '<span class="dior-log-status">Submitted</span>' : 'Not marked as submitted'; ?></td></tr>
                    </tbody>
                </table>

                <h2>Received / Imported Intake Values</h2>
                <p>These values are read from the saved <code>dior_hipaa_intake</code> record.</p>
                <table class="widefat striped dior-log-table">
                    <thead><tr><th>Field</th><th>Saved Intake Value</th><th>WordPress User Meta Value</th></tr></thead>
                    <tbody>
                    <?php foreach ($fields as $label => $keys) :
                        $intake_value = $intake[$keys['intake']] ?? '';
                        $meta_value = get_user_meta($user_id, $keys['meta'], true);
                        ?>
                        <tr>
                            <th><?php echo esc_html($label); ?></th>
                            <td><?php echo self::render_value($intake_value); ?></td>
                            <td><?php echo self::render_value($meta_value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <h2>Original Stored HIPAAtizer Payload</h2>
                <?php if ($raw_payload_exists) : ?>
                    <p class="description">This is the raw payload retained by the existing importer at the time of submission.</p>
                    <details class="dior-log-details">
                        <summary>View stored raw JSON payload</summary>
                        <?php echo self::render_value($raw_payload); ?>
                    </details>
                <?php else : ?>
                    <div class="notice notice-warning inline"><p>No original raw payload was found in this patient's saved intake record. Older submissions may contain only normalized fields; this page cannot reconstruct an original request that was never stored.</p></div>
                <?php endif; ?>

                <h2>Complete Stored Intake Record</h2>
                <details class="dior-log-details">
                    <summary>View all saved HIPAAtizer intake fields</summary>
                    <?php echo self::render_value($intake); ?>
                </details>
            <?php endif; ?>
        </div>

        <style>
            .dior-hipaa-logs-wrap .dior-hipaa-search { display:flex; align-items:end; flex-wrap:wrap; gap:12px; margin:20px 0; }
            .dior-hipaa-logs-wrap .dior-hipaa-search label { display:flex; flex-direction:column; gap:7px; }
            .dior-hipaa-logs-wrap .dior-log-table { max-width:1100px; margin:12px 0 28px; }
            .dior-hipaa-logs-wrap .dior-log-table th { width:220px; }
            .dior-hipaa-logs-wrap .dior-log-empty { color:#b32d2e; font-weight:600; }
            .dior-hipaa-logs-wrap .dior-log-status { color:#087a45; font-weight:600; }
            .dior-hipaa-logs-wrap .dior-log-json { white-space:pre-wrap; overflow-wrap:anywhere; max-height:520px; overflow:auto; background:#f6f7f7; border:1px solid #dcdcde; padding:14px; }
            .dior-hipaa-logs-wrap .dior-log-details { max-width:1100px; margin:12px 0 28px; }
            .dior-hipaa-logs-wrap .dior-log-details summary { cursor:pointer; font-weight:600; padding:10px 0; }
        </style>
        <?php
    }
}

Dior_HIPAAtizer_Logs::init();
