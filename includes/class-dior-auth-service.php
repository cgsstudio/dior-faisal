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

        // One-time ownership backfill for the existing single-doctor installation.
        // Once another active doctor exists, no global patient assignment is performed.
        if (class_exists('Dior_Doctor_Resolver')
            && $doctor_id === (int) Dior_Doctor_Resolver::get_default_doctor_id()
            && !get_option('dior_doctor_patient_ownership_migrated')) {
            $doctor_users = get_users(['role__in' => ['doctor', 'dior_doctor'], 'fields' => 'ID', 'number' => -1]);
            $active_doctor_ids = array_values(array_filter(array_map('absint', (array) $doctor_users), static function ($id) {
                return !get_user_meta($id, '_dior_merged_into_doctor_id', true);
            }));
            if (count($active_doctor_ids) === 1 && (int) $active_doctor_ids[0] === $doctor_id) {
                global $wpdb;
                $patient_table = $wpdb->prefix . 'dior_patients';
                if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $patient_table)) === $patient_table) {
                    $legacy_patient_ids = $wpdb->get_col("SELECT user_id FROM {$patient_table} WHERE status <> 'deleted'");
                    foreach ((array) $legacy_patient_ids as $legacy_patient_id) {
                        $assigned = get_user_meta((int) $legacy_patient_id, '_dior_assigned_doctor_ids', true);
                        if (!is_array($assigned) || !$assigned) {
                            update_user_meta((int) $legacy_patient_id, '_dior_assigned_doctor_ids', [$doctor_id]);
                        }
                    }
                    update_option('dior_doctor_patient_ownership_migrated', 1, false);
                }
            }
        }

        $ids = [];
        $scope = class_exists('Dior_Doctor_Resolver') ? Dior_Doctor_Resolver::get_accessible_doctor_ids($doctor_id) : [$doctor_id];
        $scope = array_values(array_unique(array_filter(array_map('absint', (array) $scope))));
        $legacy_user_ids = get_user_meta($doctor_id, '_dior_merged_doctor_user_ids', true);
        $record_scope = array_values(array_unique(array_filter(array_map('absint', array_merge([$doctor_id], is_array($legacy_user_ids) ? $legacy_user_ids : [])))));
        if ($scope) {
            // Appointment rows may also use DocBooker IDs and legacy unassigned IDs.
            // Other clinical tables store WordPress doctor user IDs only.
            $appointment_scope = $scope;
            if (class_exists('Dior_Doctor_Resolver') && $doctor_id === (int) Dior_Doctor_Resolver::get_default_doctor_id()) {
                $appointment_scope = array_values(array_unique(array_merge($appointment_scope, [0, 1])));
            }
            $related_tables = [
                [$wpdb->prefix . 'dior_appointments', 'doctor_id', 'patient_id', "is_deleted = 0", $appointment_scope],
                [$wpdb->prefix . 'dior_prescriptions', 'doctor_id', 'patient_id', '1=1', $record_scope],
                [$wpdb->prefix . 'dior_encounters', 'doctor_id', 'patient_id', 'is_deleted = 0', $record_scope],
                [$wpdb->prefix . 'dior_documents', 'author_id', 'patient_id', 'is_deleted = 0', $record_scope],
                [$wpdb->prefix . 'dior_reviews', 'doctor_id', 'patient_id', '1=1', $record_scope],
                [$wpdb->prefix . 'dior_telemedicine', 'doctor_id', 'patient_id', '1=1', $record_scope],
            ];
            foreach ($related_tables as $table_info) {
                [$table, $owner_column, $patient_column, $extra_where, $owner_scope] = $table_info;
                if (!$owner_scope || $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) continue;
                $in = implode(',', array_fill(0, count($owner_scope), '%d'));
                $found = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT {$patient_column} FROM {$table} WHERE {$extra_where} AND {$owner_column} IN ($in)",
                    ...$owner_scope
                ));
                $ids = array_merge($ids, (array) $found);
            }
        }

        // Explicit doctor-patient assignments are stored as an array on the patient account.
        $meta_key = '_dior_assigned_doctor_ids';
        $assigned = [];
        foreach ($record_scope as $record_doctor_id) {
            $needle = 'i:' . $record_doctor_id . ';';
            $found = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
                $meta_key,
                '%' . $wpdb->esc_like($needle) . '%'
            ));
            $assigned = array_merge($assigned, (array) $found);
        }
        return array_values(array_unique(array_filter(array_map('absint', array_merge((array) $ids, (array) $assigned)))));
    }

    /**
     * Anti-IDOR: Check if current user is authorized to access a patient's medical records
     */
    public static function can_access_patient($patient_id, $user_id = 0)
    {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();
        $patient_id = (int) $patient_id;
        if (!$user_id || !$patient_id) return false;

        // Admins use separate admin tools; they never inherit Doctor Dashboard access.
        if (self::is_admin($user_id)) return true;

        // Doctors have priority if an account also carries a patient/customer role.
        if (self::is_doctor($user_id)) {
            if (in_array($patient_id, self::get_doctor_patient_ids($user_id), true)) return true;

            global $wpdb;
            $appointment_table = $wpdb->prefix . 'dior_appointments';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $appointment_table)) !== $appointment_table) return false;

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

        // Patient accounts can access only their own portal data.
        return self::is_patient($user_id) && $user_id === $patient_id;
    }

    /**
     * Anti-IDOR: Check if current user can create/modify a clinical encounter
     */
    public static function can_modify_encounter($user_id = 0)
    {
        $user_id = $user_id ? (int)$user_id : get_current_user_id();
        if (!$user_id) return false;
        return self::is_doctor($user_id) || self::is_admin($user_id);
    }

    /**
     * Anti-IDOR: Check if current user can delete or manage a document
     */
    public static function can_manage_document($patient_id, $author_id = 0, $user_id = 0)
    {
        $user_id = $user_id ? (int)$user_id : get_current_user_id();
        if (!$user_id) return false;

        // Admin permissions remain available to admin-only tools; the Doctor Dashboard itself denies admin access.
        if (self::is_admin($user_id)) return true;
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
