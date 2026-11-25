<?php
/**
 * Patient dashboard data normalization.
 *
 * This file only preserves database/API-provided values. It must never
 * fabricate patient profiles, HIPAAtizer submissions, or dashboard records.
 */

if (!isset($profile) || !is_array($profile)) {
    $profile = [];
}

if (!isset($hipaa_intake) || !is_array($hipaa_intake)) {
    $hipaa_intake = [];
}

if (!isset($prescriptions) || !is_array($prescriptions)) {
    $prescriptions = [];
}
$patient_rx_rows = $prescriptions;

if (!isset($documents) || !is_array($documents)) {
    $documents = [];
}

if (!isset($questionnaires) || !is_array($questionnaires)) {
    $questionnaires = [];
}

if (!isset($appointments) || !is_array($appointments)) {
    $appointments = [];
}
