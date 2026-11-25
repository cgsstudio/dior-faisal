<?php
/**
 * Dior Medical — patient dashboard data defaults.
 *
 * This template is included by the patient dashboard before its tab templates.
 * Never seed sample/demo records here: patient-facing clinical, appointment,
 * document, questionnaire, billing, and profile data must come only from the
 * authenticated patient's database-backed data provider.
 */

// Keep expected variables predictable without inventing patient data.
$appointments   = (isset($appointments) && is_array($appointments)) ? $appointments : [];
$prescriptions  = (isset($prescriptions) && is_array($prescriptions)) ? $prescriptions : [];
$documents      = (isset($documents) && is_array($documents)) ? $documents : [];
$questionnaires = (isset($questionnaires) && is_array($questionnaires)) ? $questionnaires : [];
$encounters     = (isset($encounters) && is_array($encounters)) ? $encounters : [];
$profile        = (isset($profile) && is_array($profile)) ? $profile : [];
$hipaa_intake   = (isset($hipaa_intake) && is_array($hipaa_intake)) ? $hipaa_intake : [];

// Prescription views share the same database-backed collection.
$patient_rx_rows = $prescriptions;

// Retain this flag for any existing template logic, but it no longer enables
// design-review/demo records.
$dior_patient_design_static = false;
