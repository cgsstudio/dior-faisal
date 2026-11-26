<?php
/**
 * Doctor Accounts module: doctor-scoped manual bills and expense records.
 * Existing Stripe/payment ledgers are read-only and are not modified here.
 */
if (!defined('ABSPATH')) exit;

final class Dior_Doctor_Accounts {
    private static function table($name) { global $wpdb; return $wpdb->prefix . 'dior_' . $name; }
    private static function doctor_ids() {
        $id = get_current_user_id();
        $ids = class_exists('Dior_Doctor_Resolver') ? Dior_Doctor_Resolver::get_accessible_doctor_ids($id) : [$id];
        return array_values(array_unique(array_filter(array_map('absint', (array) $ids))));
    }
    private static function auth() {
        if (!is_user_logged_in() || !class_exists('Dior_Doctor_Dashboard') || !Dior_Doctor_Dashboard::can_access_for_dynamic()) {
            wp_send_json_error(['message' => 'Unauthorized.'], 403);
        }
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dior_doctor_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }
    }
    public static function init() {
        add_action('plugins_loaded', [__CLASS__, 'install_schema'], 21);
        add_action('wp_ajax_dior_doc_bill_save', [__CLASS__, 'bill_save']);
        add_action('wp_ajax_dior_doc_expense_save', [__CLASS__, 'expense_save']);
        add_action('wp_ajax_dior_doc_expense_delete', [__CLASS__, 'expense_delete']);
    }
    public static function install_schema() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $cc = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE " . self::table('doctor_bills') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            bill_uid VARCHAR(64) NOT NULL,
            doctor_id BIGINT UNSIGNED NOT NULL,
            patient_id BIGINT UNSIGNED NOT NULL,
            service_type VARCHAR(190) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            tax DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            payment_method VARCHAR(60) NOT NULL DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'Pending',
            due_date DATE NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY bill_uid (bill_uid),
            KEY doctor_id (doctor_id),
            KEY patient_id (patient_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $cc;");
        dbDelta("CREATE TABLE " . self::table('doctor_expenses') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            expense_uid VARCHAR(64) NOT NULL,
            doctor_id BIGINT UNSIGNED NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            expense_date DATE NOT NULL,
            vendor VARCHAR(190) NULL,
            payment_method VARCHAR(60) NULL,
            department VARCHAR(100) NULL,
            approval_status VARCHAR(30) NOT NULL DEFAULT 'Approved',
            payment_status VARCHAR(30) NOT NULL DEFAULT 'Paid',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY expense_uid (expense_uid),
            KEY doctor_id (doctor_id),
            KEY expense_date (expense_date)
        ) $cc;");
    }
    public static function bill_save() {
        self::auth(); global $wpdb;
        $patient_id = absint($_POST['patient_id'] ?? 0);
        $patient = get_userdata($patient_id);
        if (!$patient || !in_array('patient', (array) $patient->roles, true) || !Dior_Doctor_Dynamic::patient_belongs_to_doctor($patient_id, get_current_user_id())) {
            wp_send_json_error(['message' => 'Select a valid patient account.']);
        }
        $service = sanitize_text_field(wp_unslash($_POST['service_type'] ?? ''));
        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        $tax = round((float) ($_POST['tax'] ?? 0), 2);
        $discount = round((float) ($_POST['discount'] ?? 0), 2);
        $method = sanitize_text_field($_POST['payment_method'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'Pending');
        if (!$service || $amount <= 0 || $tax < 0 || $discount < 0 || !$method) {
            wp_send_json_error(['message' => 'Enter valid service, amount and payment method.']);
        }
        if (!in_array($status, ['Pending', 'Paid', 'Overdue'], true)) $status = 'Pending';
        $total = max(0, round($amount + $tax - $discount, 2));
        $ok = $wpdb->insert(self::table('doctor_bills'), [
            'bill_uid' => 'DBILL-' . strtoupper(wp_generate_password(10, false, false)),
            'doctor_id' => get_current_user_id(),
            'patient_id' => $patient_id,
            'service_type' => $service,
            'amount' => $amount,
            'tax' => $tax,
            'discount' => $discount,
            'total_amount' => $total,
            'payment_method' => $method,
            'status' => $status,
            'due_date' => sanitize_text_field($_POST['due_date'] ?? '') ?: null,
            'notes' => sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')),
            'created_at' => current_time('mysql'),
        ]);
        if (!$ok) wp_send_json_error(['message' => 'Bill could not be saved. Please retry.'], 500);
        wp_send_json_success(['message' => 'Bill saved successfully.', 'bill_uid' => $wpdb->insert_id, 'total' => $total]);
    }
    public static function expense_save() {
        self::auth(); global $wpdb;
        $category = sanitize_text_field(wp_unslash($_POST['category'] ?? ''));
        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        $date = sanitize_text_field($_POST['expense_date'] ?? '');
        if (!$category || $amount <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(['message' => 'Enter a category, valid amount and date.']);
        }
        $ok = $wpdb->insert(self::table('doctor_expenses'), [
            'expense_uid' => 'EXP-' . strtoupper(wp_generate_password(10, false, false)),
            'doctor_id' => get_current_user_id(),
            'category' => $category,
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'amount' => $amount,
            'expense_date' => $date,
            'vendor' => sanitize_text_field(wp_unslash($_POST['vendor'] ?? '')),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? ''),
            'department' => sanitize_text_field($_POST['department'] ?? ''),
            'approval_status' => 'Approved',
            'payment_status' => sanitize_text_field($_POST['payment_status'] ?? 'Paid'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        if (!$ok) wp_send_json_error(['message' => 'Expense could not be saved. Please retry.'], 500);
        wp_send_json_success(['message' => 'Expense saved successfully.']);
    }
    public static function expense_delete() {
        self::auth(); global $wpdb;
        $id = absint($_POST['id'] ?? 0);
        $ids = self::doctor_ids();
        if (!$id || !$ids) wp_send_json_error(['message' => 'Invalid expense.']);
        $in = implode(',', array_fill(0, count($ids), '%d'));
        $sql = $wpdb->prepare('DELETE FROM ' . self::table('doctor_expenses') . " WHERE id=%d AND doctor_id IN ($in)", array_merge([$id], $ids));
        $deleted = $wpdb->query($sql);
        if ($deleted !== 1) wp_send_json_error(['message' => 'Expense not found or access denied.'], 403);
        wp_send_json_success(['message' => 'Expense deleted.']);
    }
}
Dior_Doctor_Accounts::init();
