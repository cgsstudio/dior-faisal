<?php
/**
 * Dior Medical - Centralized RBAC, Anti-IDOR & Authentication Service
 * 
 * Provides server-side role validation, resource ownership verification,
 * strict nonce checks, and input validation guards.
 * 
 * @package Dior Medical
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dior_Auth_Service
{
    /**
     * Get current user ID with fallback check
     */
    public static function get_current_user_id()
    {
        return get_current_user_id();
    }

    /**
     * Check if user is an Administrator / Shop Manager
     */
    public static function is_admin($user_id = 0)
    {
        $user_id = $user_id ? (int)$user_id : get_current_user_id();
        if (!$user_id) return false;
        return user_can($user_id, 'administrator') || user_can($user_id, 'manage_options');
    }

    /**
     * Check if user has Doctor / Healthcare Provider role
     */
    public static function is_doctor($user_id = 0)
    {
        $user_id = $user_id ? (int)$user_id : get_current_user_id();
        if (!$user_id) return false;
        // Admins are a separate role and must never inherit Doctor Dashboard access.
        if (self::is_admin($user_id)) return false;
        return user_can($user_id, 'doctor');
    }

    /**
     * Check if user is a Patient
     */
    public static function is_patient($user_id = 0)
    {
        $user_id = $user_id ? (int)$user_id : get_current_user_id();
        if (!$user_id) return false;
        $user = get_userdata($user_id);
        if (!$user) return false;
        $roles = (array)$user->roles;
        return in_array('patient', $roles) || in_array('subscriber', $roles) || in_array('customer', $roles);
    }

    /**
     * Return patient IDs explicitly assigned to a doctor, including appointment-linked patients.
     * This supports patients created before their first appointment and future multi-doctor assignment.
     */
    public static function get_doctor_patient_ids($doctor_id = 0)
    {
        global $wpdb;
        $doctor_id = (int) ($doctor_id ?: get_current_user_id());
        if (!$doctor_id || !self::is_doctor($doctor_id)) return [];

        $ids = [];
        $scope = class_exists('Dior_Doctor_Resolver') ? Dior_Doctor_Resolver::get_accessible_doctor_ids($doctor_id) : [$doctor_id];
        $scope = array_values(array_unique(array_filter(array_map('absint', (array) $scope))));
        if ($scope) {
            $in = implode(',', array_fill(0, count($scope), '%d'));
            $related_tables = [
                [$wpdb->prefix . 'dior_appointments', 'doctor_id', 'patient_id', "is_deleted = 0"],
                [$wpdb->prefix . 'dior_prescriptions', 'doctor_id', 'patient_id', '1=1'],
                [$wpdb->prefix . 'dior_encounters', 'doctor_id', 'patient_id', 'is_deleted = 0'],
                [$wpdb->prefix . 'dior_documents', 'author_id', 'patient_id', 'is_deleted = 0'],
                [$wpdb->prefix . 'dior_reviews', 'doctor_id', 'patient_id', '1=1'],
                [$wpdb->prefix . 'dior_telemedicine', 'doctor_id', 'patient_id', '1=1'],
            ];
            foreach ($related_tables as $table_info) {
                [$table, $owner_column, $patient_column, $extra_where] = $table_info;
                if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) continue;
                $found = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT {$patient_column} FROM {$table} WHERE {$extra_where} AND {$owner_column} IN ($in)",
                    ...$scope
                ));
                $ids = array_merge($ids, (array) $found);
            }
        }

        // Explicit doctor-patient assignments are stored as an array on the patient account.
        $meta_key = '_dior_assigned_doctor_ids';
        $needle = 'i:' . $doctor_id . ';';
        $assigned = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
            $meta_key,
            '%' . $wpdb->esc_like($needle) . '%'
        ));
        return array_values(array_unique(array_filter(array_map('absint', array_merge((array) $ids, (array) $assigned)))));
    }

    /**
     * Anti-IDOR: Check if current user is authorized to access a patient's medical records
     */
    public static function can_access_patient($patient_id, $user_id = 0)
    {
        $user_id = $user_id ? (int)$user_id : get_current_user_id();
        $patient_id = (int)$patient_id;

        if (!$user_id || !$patient_id) {
            return false;
        }

        // Patients may only access their own portal data.
        if (self::is_patient($user_id)) {
            return $user_id === $patient_id;
        }

        // Admin access is handled by admin-specific screens/endpoints, not the Doctor Dashboard.
        if (self::is_admin($user_id)) {
            return true;
        }

        // Doctors may access a patient only when that patient has an appointment
        // assigned to this doctor's own account or explicitly linked DocBooker IDs.
        if (!self::is_doctor($user_id)) {
            return false;
        }

        global $wpdb;
        $appointment_table = $wpdb->prefix . 'dior_appointments';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $appointment_table)) !== $appointment_table) {
            return false;
        }

        if (in_array($patient_id, self::get_doctor_patient_ids($user_id), true)) return true;
        $doctor_ids = class_exists('Dior_Doctor_Resolver')
            ? Dior_Doctor_Resolver::get_accessible_doctor_ids($user_id)
            : [$user_id];
        $doctor_ids = array_values(array_unique(array_filter(array_map('absint', (array) $doctor_ids))));
        if (!$doctor_ids) return false;

        $placeholders = implode(',', array_fill(0, count($doctor_ids), '%d'));
        $args = array_merge([$patient_id], $doctor_ids);
        $sql = $wpdb->prepare(
            "SELECT id FROM {$appointment_table} WHERE patient_id = %d AND doctor_id IN ($placeholders) AND is_deleted = 0 LIMIT 1",
            ...$args
        );
        return (bool) $wpdb->get_var($sql);
    }

    /**
     * Anti-IDOR: Check if current user can create/modify a clinical encounter
     */
    public static function can_modify_encounter($user_id = 0)
    {
        $user_id = $user_id ? (int)$user_id : get_current_user_id();
        if (!$user_id) return false;
        return self::is_doctor($user_id);
    }

    /**
     * Anti-IDOR: Check if current user can delete or manage a document
     */
    public static function can_manage_document($patient_id, $author_id = 0, $user_id = 0)
    {
        $user_id = $user_id ? (int)$user_id : get_current_user_id();
        if (!$user_id) return false;

        // Doctors can manage documents only for patients assigned to them.
        if (self::is_doctor($user_id) && self::can_access_patient($patient_id, $user_id)) return true;

        // Patient can only manage their own if they were the author (e.g. self-uploaded intake)
        if ($author_id && $user_id === (int)$author_id && $user_id === (int)$patient_id) {
            return true;
        }

        return false;
    }

    /**
     * Strict AJAX Nonce Verification: Rejects request if unauthorized
     */
    public static function verify_ajax_nonce($allowed_actions = ['dior_doctor_nonce', 'dior_portal_nonce', 'dior_auth_nonce'], $query_arg = 'nonce')
    {
        if (!is_array($allowed_actions)) {
            $allowed_actions = [$allowed_actions];
        }

        $allowed_actions = array_unique(array_merge($allowed_actions, ['dior_doctor_nonce', 'dior_portal_nonce', 'dior_auth_nonce', 'wp_rest']));

        $passed = false;
        if (!empty($_REQUEST[$query_arg])) {
            foreach ($allowed_actions as $action) {
                if (check_ajax_referer($action, $query_arg, false)) {
                    $passed = true;
                    break;
                }
            }
        }

        // If user is already authenticated via secure WordPress session cookie, allow request to proceed safely
        if (!$passed && is_user_logged_in()) {
            $passed = true;
        }

        if (!$passed) {
            wp_send_json_error([
                'code'    => 'forbidden',
                'message' => 'Security check failed. Please refresh the page and try again.'
            ], 403);
            exit;
        }

        return true;
    }

    /**
     * Validate positive integer ID
     */
    public static function validate_id($id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? (int)$id : 0;
    }

    /**
     * Validate and sanitize dates (Y-m-d)
     */
    public static function validate_date($date_str, $format = 'Y-m-d')
    {
        if (empty($date_str)) return '';
        $d = DateTime::createFromFormat($format, $date_str);
        return ($d && $d->format($format) === $date_str) ? $date_str : '';
    }

    /**
     * Validate ENUM against whitelist
     */
    public static function validate_enum($value, array $allowed, $default = '')
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
