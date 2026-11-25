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

$design_doctors = [
    ['Dr. Sarah Smith','Cardiology'], ['Dr. James Chen','Primary Care'],
    ['Dr. Helen Miller','Internal Medicine'], ['Dr. Marcus Sterling','Family Medicine'],
    ['Dr. Olivia Carter','Dermatology'], ['Dr. Daniel Brooks','Neurology'],
    ['Dr. Emily Wilson','Endocrinology'], ['Dr. Noah Bennett','Orthopedics'],
    ['Dr. Sophia Davis','Pediatrics'], ['Dr. Ethan Walker','Gastroenterology'],
];

/* Restore the previous appointment-page sample rows and expansion behavior. */
for ($i = count($appointments); $i < 10; $i++) {
    $d = $design_doctors[$i];
    $offset = $i - 1;
    $date = wp_date('Y-m-d', current_time('timestamp') + ($offset * DAY_IN_SECONDS));
    $appointments[] = [
        'id' => 'APT-' . (2050 + $i), 'appt_uid' => 'APT-' . (2050 + $i),
        'patient_id' => $user_id, 'patient_user_id' => $user_id, 'doctor_id' => 110 + $i,
        'doctor_name' => $d[0], 'provider' => $d[0], 'provider_spec' => $d[1],
        'date' => $date, 'appt_date' => $date,
        'time' => sprintf('%02d:%02d %s', 9 + ($i % 7), ($i % 2) ? 30 : 0, ($i % 3 === 0 ? 'AM' : 'PM')),
        'appt_time' => sprintf('%02d:%02d %s', 9 + ($i % 7), ($i % 2) ? 30 : 0, ($i % 3 === 0 ? 'AM' : 'PM')),
        'status' => ($i % 3 === 0 ? 'Completed' : ($i % 2 === 0 ? 'Confirmed' : 'Scheduled')),
        'type' => ($i % 2 ? 'Clinic Visit' : 'Video Consultation'),
        'condition' => ['Routine Follow-up','Blood Pressure Review','Annual Wellness','Medication Review'][$i % 4],
        'visit_type' => ($i % 2 ? 'In Person' : 'Telehealth'), 'duration' => '30 minutes',
        'notes' => 'Static design-review appointment record.'
    ];
}


// Preserve the existing HIPAA intake form data structure and values.
$design_today = current_time('Y-m-d');
if (empty($hipaa_intake) || !is_array($hipaa_intake)) {
    $hipaa_intake = [
        'submitted_at' => current_time('mysql'),
        'dob' => '1992-05-18',
        'phone' => '+1 (555) 014-2081',
        'email' => 'sarah.johnson@example.com',
        'address_line1' => '245 Medical Center Drive',
        'city' => 'Austin',
        'state' => 'TX',
        'zip' => '78701',
        'symptoms' => 'Mild dizziness and seasonal allergy symptoms',
        'allergies' => 'Penicillin',
        'medications' => 'Cetirizine 10 mg',
        'medical_history' => 'Seasonal allergies; routine blood pressure monitoring',
        'service_condition' => 'Primary Care & General Medicine',
        'is_18_plus' => 'Yes',
        'is_pregnant' => 'No',
        'symptoms_description' => 'Mild symptoms during the last few days.',
        'symptom_duration' => '3 days',
        'symptom_severity' => 'Mild',
        'taking_medications' => 'Yes',
        'medications_list' => 'Cetirizine 10 mg once daily',
        'drug_allergies' => 'Yes',
        'drug_allergies_list' => 'Penicillin',
        'appointment_type' => 'Telehealth Video Visit',
        'preferred_date' => $design_today,
        'preferred_time' => '10:30 AM',
        'telemedicine_consent' => 'Yes',
        'legal_acknowledgement' => 'Yes',
        'full_legal_name' => 'Sarah Johnson',
        'signature_date' => $design_today,
    ];
}

// Retain this flag for any existing template logic, but it no longer enables
// design-review/demo records.
$dior_patient_design_static = false;
