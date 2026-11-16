<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Fetch patient list for prescription assignment dropdown
$patients_query = $wpdb->get_results("SELECT ID, display_name, user_email FROM {$wpdb->users} ORDER BY display_name ASC LIMIT 100");
$patients_list = [];
foreach ($patients_query as $pq) {
    $puid = get_user_meta($pq->ID, 'patient_id', true) ?: ('DM-' . (10000 + $pq->ID));
    $patients_list[] = [
        'id' => $pq->ID,
        'uid' => $puid,
        'name' => $pq->display_name ?: $pq->user_email
    ];
}
?>

<style>
    /* ── Digital Prescriptions — desktop chrome ─────────────────────────────────
       Colours, borders, radii and shadows come from the shared sub-page design
       system in dior-custom.css (§12). Only sizing specific to this registry is
       declared here; the mobile card layout lives in the stylesheet under
       "DIGITAL PRESCRIPTIONS — mobile card layout".                            */
    #tab-doc-e-prescriptions .va-icon-btn {
        width: 40px !important;
        height: 40px !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 18px !important;
        cursor: pointer !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    #tab-doc-e-prescriptions .va-icon-btn i {
        font-size: 18px !important;
        line-height: 1 !important;
    }

    #tab-doc-e-prescriptions .va-action-btn-sm {
        width: 34px !important;
        height: 34px !important;
        min-width: 34px !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 15px !important;
        border: none !important;
        cursor: pointer !important;
    }

    #tab-doc-e-prescriptions .va-action-btn-sm i {
        font-size: 15px !important;
        line-height: 1 !important;
    }

    #tab-doc-e-prescriptions .va-btn-edit {
        background: rgba(26, 82, 142, 0.1) !important;
        color: #1A528E !important;
    }

    #tab-doc-e-prescriptions .va-btn-edit:hover {
        background: #1A528E !important;
        color: #ffffff !important;
    }

    #tab-doc-e-prescriptions .va-btn-delete {
        background: rgba(239, 68, 68, 0.1) !important;
        color: #EF4444 !important;
    }

    #tab-doc-e-prescriptions .va-btn-delete:hover {
        background: #EF4444 !important;
        color: #ffffff !important;
    }

    #tab-doc-e-prescriptions .va-btn-view {
        background: rgba(44, 108, 177, 0.1) !important;
        color: #2C6CB1 !important;
    }

    #tab-doc-e-prescriptions .va-btn-view:hover {
        background: #2C6CB1 !important;
        color: #ffffff !important;
    }

    /* Prescription registry cell presentation (desktop). */
    #tab-doc-e-prescriptions .pr-rx-title {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        color: #0F172A !important;
        font-weight: 700 !important;
    }

    #tab-doc-e-prescriptions .pr-rx-title i {
        color: #C5A059 !important;
        font-size: 12px !important;
    }

    #tab-doc-e-prescriptions .pr-multi-line {
        white-space: normal !important;
        line-height: 1.45 !important;
    }

    #tab-doc-e-prescriptions .pr-patient-profile {
        gap: 10px !important;
    }

    #tab-doc-e-prescriptions .pr-patient-avatar {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 0 0 30px !important;
        width: 30px !important;
        height: 30px !important;
        border-radius: 50% !important;
        background: #E8F0F9 !important;
        color: #1A528E !important;
        font-size: 12px !important;
        font-weight: 700 !important;
    }

    #tab-doc-e-prescriptions .pr-empty-cell {
        text-align: center !important;
        padding: 40px 16px !important;
        color: #64748B !important;
    }

    #tab-doc-e-prescriptions .pr-empty-icon {
        display: block !important;
        font-size: 26px !important;
        color: #CBD5E1 !important;
        margin-bottom: 10px !important;
    }

    #tab-doc-e-prescriptions .pr-empty-cell strong {
        display: block !important;
        font-size: 15px !important;
        color: #0F172A !important;
        font-weight: 700 !important;
        margin-bottom: 4px !important;
    }

    #tab-doc-e-prescriptions .pr-empty-cell span {
        display: block !important;
        font-size: 13px !important;
    }
</style>

<section class="dior-tab-panel dior-prescriptions-screen" id="tab-doc-e-prescriptions" style="display: none;">
    <!-- Page Heading -->
    <div class="mb-4 pr-page-heading">
        <div>
            <h4 class="mb-0 text-dark pr-page-title">Digital Prescriptions</h4>
            <p class="pr-page-sub">Every e-prescription issued from this account, in one registry.</p>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house"></i></a></li>
                <li>/</li>
                <li class="active"><span>Digital Prescriptions</span></li>
            </ul>
        </div>
    </div>

    <div class="view-appointment-card">
        <!-- Header -->
        <div class="va-header-container">
            <div class="va-title-box">
                <h2><i class="fas fa-prescription-bottle-medical text-primary me-2"></i>Digital Prescriptions</h2>
                <div class="va-title-line"></div>
            </div>
            <div class="va-actions-box pr-toolbar-actions">
                <div class="input-group input-group-sm dior-bs-search-group pr-search-wrap">
                    <input type="text" id="pr-search" class="dior-search-input form-control border-start-0 ps-0"
                        placeholder="Search records..." aria-label="Search prescriptions">
                </div>
                <button type="button" class="va-icon-btn va-btn-primary btn" id="pr-add-btn"
                    title="Add Medication Prescription (FDA Approved)" aria-label="Add new prescription"
                    onclick="diorOpenFdaPrescriptionModal()">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" class="va-icon-btn va-btn-success btn" id="pr-export-btn"
                    title="Export CSV" aria-label="Export prescriptions to CSV"
                    onclick="diorExportPrescriptionsCsv()">
                    <i class="fas fa-file-arrow-down"></i>
                </button>
                <button type="button" class="va-icon-btn va-btn-info btn" id="pr-refresh-btn" title="Refresh"
                    aria-label="Refresh" onclick="window.location.reload()">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="va-table-wrapper pr-table-wrapper">
            <table class="va-table table table-hover pr-responsive-table" id="pr-table">
                <thead>
                    <tr>
                        <th class="pr-select-heading"><input type="checkbox" class="va-checkbox form-check-input"
                                id="pr-select-all" aria-label="Select all prescriptions"></th>
                        <th>Prescription ID <i class="fas fa-sort"></i></th>
                        <th>Title <i class="fas fa-sort"></i></th>
                        <th>Patient Name <i class="fas fa-sort"></i></th>
                        <th>Date <i class="fas fa-sort"></i></th>
                        <th>Medications <i class="fas fa-sort"></i></th>
                        <th>Dosage <i class="fas fa-sort"></i></th>
                        <th>Frequency <i class="fas fa-sort"></i></th>
                        <th>Duration <i class="fas fa-sort"></i></th>
                        <th>Doctor <i class="fas fa-sort"></i></th>
                        <th>Status <i class="fas fa-sort"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pr-tbody">
                    <?php
                    // Restrict prescription visibility to the current doctor's records; administrators may view all.
                    if (current_user_can('manage_options')) {
                        $prescriptions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dior_prescriptions ORDER BY id DESC");
                    } else {
                        $prescriptions = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}dior_prescriptions WHERE doctor_id = %d ORDER BY id DESC",
                            get_current_user_id()
                        ));
                    }

                    // Split a multi-drug cell into one entry per line so desktop and the mobile
                    // card layout render an identical medication list.
                    $pr_split_cell = function ($raw_value, $pattern = '/\|\s*|;\s*|\r\n|\n/') {
                        $parts = preg_split($pattern, trim((string) $raw_value));
                        $parts = array_filter(array_map('trim', (array) $parts), function ($part) {
                            return $part !== '';
                        });
                        return !empty($parts) ? array_values($parts) : [trim((string) $raw_value)];
                    };

                    if (!empty($prescriptions)):
                        foreach ($prescriptions as $p):
                            $badge_class = 'va-badge-completed';
                            if (strtolower($p->status) === 'completed') {
                                $badge_class = 'va-badge-confirmed';
                            } elseif (strtolower($p->status) === 'cancelled') {
                                $badge_class = 'va-badge-cancelled';
                            } elseif (strtolower($p->status) === 'pending' || strtolower($p->status) === 'draft') {
                                $badge_class = 'va-badge-pending';
                            }

                            $pr_date = !empty($p->prescription_date) ? $p->prescription_date : $p->created_at;
                            $pr_date_label = $pr_date ? date('M d, Y', strtotime($pr_date)) : '—';
                            $pr_id = $p->prescription_id ?: $p->prescription_uid;
                            $pr_title = !empty($p->title) ? $p->title : 'General Care Prescription';

                            $view_json = htmlspecialchars(json_encode([
                                'id' => $pr_id,
                                'title' => $pr_title,
                                'patient_name' => $p->patient_name,
                                'date' => $pr_date_label,
                                'medications' => $p->medications ?: $p->medication,
                                'dosage' => $p->dosage,
                                'frequency' => $p->frequency,
                                'duration' => $p->duration,
                                'doctor_name' => $p->doctor_name ?: 'Dr. Marcus Sterling, DO',
                                'status' => $p->status,
                                'instructions' => $p->instructions ?: '',
                                'quantity' => $p->quantity ?: 'As prescribed',
                                'refills' => $p->refills ?: 0,
                            ]), ENT_QUOTES, 'UTF-8');

                            $med_lines = $pr_split_cell($p->medications ?: $p->medication, '/;\s*|\r\n|\n/');
                            $dose_lines = $pr_split_cell($p->dosage);
                            $freq_lines = $pr_split_cell($p->frequency);
                            $dur_lines = $pr_split_cell($p->duration);
                            ?>
                            <tr data-id="<?php echo esc_attr($pr_id); ?>">
                                <td class="pr-select-cell"><input type="checkbox"
                                        class="va-checkbox pr-row-check form-check-input"
                                        aria-label="Select prescription"></td>
                                <td data-label="Prescription ID"><strong><?php echo esc_html($pr_id); ?></strong></td>
                                <td data-label="Title">
                                    <span class="pr-rx-title">
                                        <i class="fa-solid fa-file-waveform"></i> <?php echo esc_html($pr_title); ?>
                                    </span>
                                </td>
                                <td data-label="Patient Name">
                                    <div class="va-user-profile pr-patient-profile">
                                        <span class="pr-patient-avatar"><?php echo esc_html(strtoupper(substr(trim((string) $p->patient_name), 0, 1)) ?: 'P'); ?></span>
                                        <span class="va-user-name"><?php echo esc_html($p->patient_name ?: '—'); ?></span>
                                    </div>
                                </td>
                                <td data-label="Date"><i class="far fa-calendar va-icon-blue"></i> <?php echo esc_html($pr_date_label); ?></td>
                                <td data-label="Medications" class="pr-multi-cell">
                                    <?php foreach ($med_lines as $m_line): ?>
                                        <div class="pr-multi-line"><?php echo esc_html($m_line); ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td data-label="Dosage" class="pr-multi-cell">
                                    <?php foreach ($dose_lines as $d_line): ?>
                                        <div class="pr-multi-line"><?php echo esc_html($d_line); ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td data-label="Frequency" class="pr-multi-cell">
                                    <?php foreach ($freq_lines as $f_line): ?>
                                        <div class="pr-multi-line"><?php echo esc_html($f_line); ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td data-label="Duration" class="pr-multi-cell">
                                    <?php foreach ($dur_lines as $du_line): ?>
                                        <div class="pr-multi-line"><?php echo esc_html($du_line); ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td data-label="Doctor"><?php echo esc_html($p->doctor_name ?: 'Dr. Marcus Sterling, DO'); ?></td>
                                <td data-label="Status"><span
                                        class="va-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html(ucfirst($p->status)); ?></span>
                                </td>
                                <td data-label="Actions" class="pr-actions-cell">
                                    <div class="va-row-actions">
                                        <button type="button" class="va-action-btn-sm va-btn-view pr-view-btn btn"
                                            title="View Prescription Details"
                                            onclick="diorOpenPrescriptionDetailModal(<?php echo $view_json; ?>)"><i
                                                class="fas fa-eye"></i></button>
                                        <button type="button" class="va-action-btn-sm va-btn-edit pr-edit-btn btn"
                                            title="Edit Prescription"
                                            onclick="diorOpenFdaPrescriptionModal()"><i
                                                class="fas fa-pen-to-square"></i></button>
                                        <button type="button" class="va-action-btn-sm va-btn-delete pr-delete-btn btn"
                                            title="Delete Prescription" onclick="diorDeletePrescriptionRow(this)"><i
                                                class="fas fa-trash-can"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        endforeach;
                    else:
                        ?>
                        <tr id="pr-empty-row">
                            <td colspan="12" class="pr-empty-cell">
                                <i class="fa-solid fa-prescription-bottle-medical pr-empty-icon"></i>
                                <strong>No digital prescriptions yet</strong>
                                <span>Click the <i class="fas fa-plus"></i> button above to issue the first
                                    prescription.</span>
                            </td>
                        </tr>
                        <?php
                    endif;
                    ?>
                </tbody>
            </table>
        </div>

        <div class="va-footer pr-footer">
            <div class="va-pagination-text"><?php echo count($prescriptions ?: []); ?> total prescriptions</div>
        </div>
    </div>
</section>

<!-- ============================================================== -->
<!-- DIGITAL PRESCRIPTION BUILDER MODAL (CLEAN REGISTRATION STYLE) -->
<!-- ============================================================== -->
<div class="dior-fda-modal-overlay" id="dior-fda-prescription-modal" role="dialog" aria-modal="true"
    aria-labelledby="dior-fda-title-text">
    <div class="dior-fda-dialog dior-prescription-reg-card">
        <input type="hidden" id="dior-fda-nonce" value="<?php echo esc_attr(wp_create_nonce('dior_doctor_nonce')); ?>">

        <!-- Clean Modal Header (Registration Form Style) -->
        <div class="dior-fda-header">
            <div class="dior-fda-header-title-wrap">
                <div class="dior-fda-brand-icon">
                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                </div>
                <div>
                    <h3 class="dior-fda-header-title" id="dior-fda-title-text">New Clinical Prescription</h3>
                    <p class="dior-fda-header-sub">FDA Approved Clinical Prescription Builder &bull; Official Digital
                        e-Rx</p>
                </div>
            </div>
            <button type="button" class="dior-fda-close-btn btn" onclick="diorCloseFdaPrescriptionModal()"
                aria-label="Close dialog">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <!-- Clean Modal Body -->
        <div class="dior-fda-body">
            <input type="hidden" id="dior-fda-nonce"
                value="<?php echo esc_attr(wp_create_nonce('dior_doctor_nonce')); ?>">

            <!-- SECTION 1: Patient & Prescription Information -->
            <div class="dior-reg-section-title">
                <i class="fas fa-id-card-clip text-primary"></i> 1. Prescription &amp; Patient Information
            </div>
            <div class="dior-reg-form-row dior-reg-grid-3">
                <div class="dior-form-group">
                    <label for="dior-fda-presc-title">Prescription Title / Diagnosis <span
                            class="dior-req-star">*</span></label>
                    <input type="text" id="dior-fda-presc-title" class="dior-login-input form-control"
                        placeholder="e.g. Type 2 Diabetes Management, Hypertension Plan"
                        value="General Medical Prescription" required>
                </div>
                <div class="dior-form-group">
                    <label for="dior-fda-patient-select">Prescribe For Patient <span
                            class="dior-req-star">*</span></label>
                    <select id="dior-fda-patient-select" class="dior-login-input dior-reg-select form-select" required>
                        <?php
                        if (!empty($patients_list)) {
                            foreach ($patients_list as $pat) {
                                echo '<option value="' . esc_attr($pat['id']) . '">' . esc_html($pat['name']) . ' (' . esc_html($pat['uid']) . ')</option>';
                            }
                        } else {
                            echo '<option value="2">Default Patient (DM-10002)</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="dior-form-group">
                    <label for="dior-fda-presc-date">Prescription Date <span class="dior-req-star">*</span></label>
                    <input type="date" id="dior-fda-presc-date" class="dior-login-input form-control"
                        value="<?php echo current_time('Y-m-d'); ?>" required>
                </div>
            </div>

            <!-- SECTION 2: Medication Details -->
            <div class="dior-reg-section-title" style="margin-top: 18px;">
                <i class="fas fa-pills text-primary"></i> 2. Medication Details &amp; Dosage
            </div>

            <div class="dior-form-group" style="position:relative;">
                <label for="dior-fda-med-search">Medication Name / Search (FDA Catalog) <span
                        class="dior-req-star">*</span></label>
                <div class="dior-search-input-wrap">
                    <input type="text" id="dior-fda-med-search"
                        class="dior-login-input dior-search-input-padded form-control"
                        placeholder="Type to search FDA medications (e.g. Metformin, Sporanox, Amoxicillin...) or enter custom drug"
                        autocomplete="off" oninput="diorOnFdaSearchInput(this.value)">
                    <span class="dior-badge-fda-verify"><i class="fas fa-shield-halved"></i> openFDA</span>
                </div>
                <!-- Autocomplete Dropdown -->
                <div id="dior-fda-autocomplete-dropdown" class="dior-fda-autocomplete-dropdown"></div>
            </div>

            <div class="dior-reg-form-row dior-reg-grid-3">
                <div class="dior-form-group">
                    <label for="dior-fda-strength">Strength / Concentration <span class="dior-req-star">*</span></label>
                    <input type="text" id="dior-fda-strength" class="dior-login-input form-control"
                        placeholder="e.g. 500 mg, 100 mg/5ml" value="500 mg">
                </div>
                <div class="dior-form-group">
                    <label for="dior-fda-dose-form">Dosage Form <span class="dior-req-star">*</span></label>
                    <select class="dior-login-input dior-reg-select form-select" id="dior-fda-dose-form"
                        onchange="window.diorFdaState.doseForm=this.value;">
                        <option value="Tablet(s)" selected>Tablet(s)</option>
                        <option value="Capsule(s)">Capsule(s)</option>
                        <option value="Syrup">Syrup</option>
                        <option value="Suspension">Suspension</option>
                        <option value="Injection">Injection</option>
                        <option value="Inhaler">Inhaler</option>
                        <option value="Drops">Drops</option>
                        <option value="Cream">Cream</option>
                        <option value="Ointment">Ointment</option>
                        <option value="Patch">Patch</option>
                        <option value="Powder">Powder</option>
                    </select>
                </div>
                <div class="dior-form-group">
                    <label for="dior-fda-route">Route of Administration <span class="dior-req-star">*</span></label>
                    <select class="dior-login-input dior-reg-select form-select" id="dior-fda-route"
                        onchange="window.diorFdaState.route=this.value;">
                        <option value="Orally" selected>Orally (By Mouth)</option>
                        <option value="Topical">Topical (Skin)</option>
                        <option value="Sublingual">Sublingual (Under Tongue)</option>
                        <option value="Inhalation">Inhalation</option>
                        <option value="Nasal">Nasal (Nose)</option>
                        <option value="Ophthalmic">Ophthalmic (Eye)</option>
                        <option value="Otic">Otic (Ear)</option>
                        <option value="Intravenous (IV)">Intravenous (IV)</option>
                        <option value="Intramuscular (IM)">Intramuscular (IM)</option>
                    </select>
                </div>
            </div>

            <div class="dior-reg-form-row dior-reg-grid-3">
                <div class="dior-form-group">
                    <label for="dior-fda-frequency">Frequency <span class="dior-req-star">*</span></label>
                    <select class="dior-login-input dior-reg-select form-select" id="dior-fda-frequency"
                        onchange="window.diorFdaState.frequency=this.value;">
                        <option value="Daily (Once a day)" selected>Once Daily (QD)</option>
                        <option value="Twice Daily (BID)">Twice Daily (BID)</option>
                        <option value="Three Times Daily (TID)">Three Times Daily (TID)</option>
                        <option value="Four Times Daily (QID)">Four Times Daily (QID)</option>
                        <option value="Every 8 Hours">Every 8 Hours</option>
                        <option value="Every 12 Hours">Every 12 Hours</option>
                        <option value="Once Weekly">Once Weekly</option>
                        <option value="As Needed (PRN)">As Needed (PRN)</option>
                        <option value="Alternate Days">Alternate Days</option>
                    </select>
                </div>
                <div class="dior-form-group">
                    <label for="dior-fda-intake">Meal / Intake Timing <span class="dior-req-star">*</span></label>
                    <select class="dior-login-input dior-reg-select form-select" id="dior-fda-intake"
                        onchange="window.diorFdaState.intake=this.value;">
                        <option value="After Food" selected>After Food (Post Meal)</option>
                        <option value="Before Food">Before Food (Empty Stomach)</option>
                        <option value="With Meals">With Meals</option>
                        <option value="At Bedtime">At Bedtime (Night)</option>
                        <option value="In the Morning">In the Morning</option>
                        <option value="Not Applicable">Not Applicable</option>
                    </select>
                </div>
                <div class="dior-form-group">
                    <label for="dior-fda-duration">Treatment Duration <span class="dior-req-star">*</span></label>
                    <select class="dior-login-input dior-reg-select form-select" id="dior-fda-duration">
                        <option value="3 Day(s)">3 Days</option>
                        <option value="5 Day(s)">5 Days</option>
                        <option value="7 Day(s)" selected>7 Days (1 Week)</option>
                        <option value="14 Day(s)">14 Days (2 Weeks)</option>
                        <option value="30 Day(s)">30 Days (1 Month)</option>
                        <option value="60 Day(s)">60 Days (2 Months)</option>
                        <option value="90 Day(s)">90 Days (3 Months)</option>
                        <option value="Continuous / Till Review">Continuous / Till Review</option>
                    </select>
                </div>
            </div>

            <!-- SECTION 3: Instructions & Quantity -->
            <div class="dior-reg-section-title" style="margin-top: 18px;">
                <i class="fas fa-clipboard-check text-primary"></i> 3. Instructions &amp; Quantity
            </div>

            <div class="dior-reg-form-row dior-reg-grid-2">
                <div class="dior-form-group">
                    <label for="dior-fda-quantity">Dispense Quantity</label>
                    <input type="text" id="dior-fda-quantity" class="dior-login-input form-control"
                        placeholder="e.g. 30 Tablets" value="30 Tablets">
                </div>
                <div class="dior-form-group">
                    <label for="dior-fda-refills">Refills Authorized</label>
                    <select class="dior-login-input dior-reg-select form-select" id="dior-fda-refills">
                        <option value="0" selected>0 Refills</option>
                        <option value="1">1 Refill</option>
                        <option value="2">2 Refills</option>
                        <option value="3">3 Refills</option>
                        <option value="5">5 Refills</option>
                    </select>
                </div>
            </div>

            <div class="dior-form-group">
                <label for="dior-fda-custom-notes">Patient Sig / Doctor's Special Instructions</label>
                <textarea id="dior-fda-custom-notes" class="dior-login-input dior-reg-textarea form-control" rows="2"
                    placeholder="e.g. Take 1 tablet daily with a full glass of water after meals. Complete full prescribed course."></textarea>
            </div>

            <!-- Add More Medication Action Strip -->
            <div class="dior-add-med-strip">
                <div>
                    <strong style="color:#0F172A; font-size:13px; display:block;">Prescribing Multiple
                        Medications?</strong>
                    <span style="color:#64748B; font-size:12px;">Add this medicine to the slip and enter details for the
                        next drug.</span>
                </div>
                <button type="button" class="dior-btn-add-secondary btn" onclick="diorFdaAddMedicineToList(event)">
                    <i class="fas fa-plus me-1"></i> Add Medication to Slip
                </button>
            </div>

            <!-- Multi-Medication Cart / Added Items Section -->
            <div id="dior-fda-prescribed-list-wrap" class="dior-fda-prescribed-list-wrap" style="display:none;">
                <div class="dior-fda-prescribed-list-header">
                    <span><i class="fas fa-list-check me-1"></i> Medications Added to this Prescription (<strong
                            id="dior-fda-prescribed-count">0</strong>):</span>
                </div>
                <div id="dior-fda-prescribed-items" class="dior-fda-prescribed-items">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

        </div>

        <!-- Clean Modal Footer -->
        <div class="dior-fda-footer">
            <button type="button" class="dior-btn-reg-cancel btn" onclick="diorCloseFdaPrescriptionModal()">
                Cancel
            </button>
            <button type="button" id="dior-fda-btn-submit" class="dior-btn-reg-submit btn"
                onclick="diorSubmitFdaPrescription(event)">
                <i class="fas fa-file-prescription me-1"></i> Save &amp; Issue Prescription <span
                    id="dior-fda-rx-btn-count" style="display:none; font-weight:700;">(1)</span>
            </button>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- DIOR MEDICAL - VIEW PRESCRIPTION DETAILS MODAL                -->
<!-- ============================================================== -->
<div class="dior-fda-modal-overlay" id="dior-prescription-view-modal" role="dialog" aria-modal="true"
    aria-labelledby="dior-view-rx-title">
    <div class="dior-fda-dialog dior-view-rx-dialog">
        <div class="dior-fda-header">
            <div class="dior-fda-header-title-wrap">
                <div class="dior-fda-brand-icon">
                    <i class="fa-solid fa-file-prescription"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="dior-view-rx-id-badge" id="dior-view-rx-id">RX-000000</span>
                        <span class="badge-solid col-green" id="dior-view-rx-status"
                            style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase;">Active</span>
                    </div>
                    <h3 class="dior-fda-header-title" id="dior-view-rx-title" style="margin-top: 4px; font-size: 18px;">
                        General Care Prescription</h3>
                </div>
            </div>
            <button type="button" class="dior-fda-close-btn btn" onclick="diorClosePrescriptionDetailModal()"
                aria-label="Close dialog">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="dior-fda-body">
            <!-- Metadata Highlights Bar -->
            <div class="dior-view-rx-meta-bar">
                <div class="dior-view-meta-item">
                    <span class="meta-label"><i class="fas fa-user-circle"></i> Patient</span>
                    <strong class="meta-value" id="dior-view-rx-patient">diormedical (DM-10001)</strong>
                </div>
                <div class="dior-view-meta-item">
                    <span class="meta-label"><i class="fas fa-user-doctor"></i> Prescribing Doctor</span>
                    <strong class="meta-value" id="dior-view-rx-doctor">Dr. Marcus Sterling, DO</strong>
                </div>
                <div class="dior-view-meta-item">
                    <span class="meta-label"><i class="far fa-calendar-check"></i> Date Prescribed</span>
                    <strong class="meta-value" id="dior-view-rx-date">Oct 14, 2026</strong>
                </div>
                <div class="dior-view-meta-item">
                    <span class="meta-label"><i class="fas fa-hospital"></i> Clinic</span>
                    <strong class="meta-value">Dior Medical Beverly Hills</strong>
                </div>
            </div>

            <!-- Medication List Section -->
            <div class="dior-view-rx-section">
                <div class="dior-view-section-header">
                    <h4><i class="fa-solid fa-pills" style="color:#d4af37;"></i> Prescribed Medications</h4>
                    <span class="badge"
                        style="background: rgba(44, 108, 177, 0.1); color: #2C6CB1; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;">Verified
                        Regimen</span>
                </div>
                <div id="dior-view-rx-meds-list" class="dior-view-meds-container">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Clinical Instructions / Notes -->
            <div class="dior-view-rx-notes-card">
                <div class="dior-view-notes-title"><i class="fa-solid fa-notes-medical" style="color: #d4af37;"></i>
                    Attending Physician Instructions & Notes</div>
                <p id="dior-view-rx-notes" class="dior-view-notes-content">Take as directed with full glass of water.
                </p>
            </div>
        </div>

        <div class="dior-fda-footer">
            <div class="dior-fda-footer-left">
                <span style="font-size: 12px; color: #64748b;"><i class="fas fa-lock" style="color:#10b981;"></i>
                    Digitally Verified & Signed Telehealth Record</span>
            </div>
            <div class="dior-fda-footer-actions">
                <button type="button" class="dior-btn-gold-secondary btn" onclick="window.print()"
                    style="padding: 10px 18px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; color: #1e293b; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-print"></i> Print Prescription
                </button>
                <button type="button" class="dior-fda-btn-saverx btn" onclick="diorClosePrescriptionDetailModal()"
                    style="padding: 10px 22px;">
                    <i class="fas fa-check"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Filter Prescriptions Table.
    // The generic table controller (dior-static-table-interactions.js) already
    // paginates and searches this registry through the shared search field, so
    // this helper only hides rows when it is called directly from a legacy
    // caller and never fights the paginator for control of row.style.display.
    function diorFilterPrescriptions(val) {
        var filter = (val || '').toLowerCase();
        var rows = document.querySelectorAll('#tab-doc-e-prescriptions #pr-table tbody tr');
        rows.forEach(function (row) {
            if (!row.hasAttribute('data-id')) return; // leave the empty-state row alone
            var txt = (row.textContent || '').toLowerCase();
            row.style.display = (!filter || txt.indexOf(filter) !== -1) ? '' : 'none';
        });
    }

    // Export Prescriptions to CSV
    function diorExportPrescriptionsCsv() {
        var rows = document.querySelectorAll('#tab-doc-e-prescriptions table.va-table tr');
        var csv = [];
        rows.forEach(function (row) {
            var cols = row.querySelectorAll('th, td');
            var rowData = [];
            cols.forEach(function (col, idx) {
                if (idx === 0 || idx === cols.length - 1) return; // skip checkbox and actions
                rowData.push('"' + col.innerText.replace(/"/g, '""').trim() + '"');
            });
            if (rowData.length) csv.push(rowData.join(','));
        });
        var blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'digital-prescriptions-' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
    }

    // Delete Prescription Row (Visual + Feedback)
    function diorDeletePrescriptionRow(btn) {
        Swal.fire({
            title: 'Delete Prescription?',
            text: 'Are you sure you want to delete this prescription record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            var tr = btn.closest('tr');
            if (tr) {
                tr.style.opacity = '0.4';
                setTimeout(function () { tr.remove(); }, 300);
                Swal.fire({ icon: 'success', title: 'Deleted', text: 'Prescription record removed.', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
            }
        });
    }
</script>