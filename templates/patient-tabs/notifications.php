<?php
/**
 * Dior Medical — Patient Notifications Center (Matched to Doctor Dashboard Design)
 * 100% Bootstrap 5 with Native Component Standards
 */

$user_id = get_current_user_id();
$patient_notif_rows = [];

if (class_exists('Dior_Patient_Portal_Data') && method_exists('Dior_Patient_Portal_Data', 'get_patient_notifications')) {
    $patient_notif_rows = Dior_Patient_Portal_Data::get_patient_notifications($user_id);
}

// Compute notification metrics
$total_count = count($patient_notif_rows);
$unread_count = 0;
$read_count = 0;
$clinical_count = 0;

foreach ($patient_notif_rows as $row) {
    if (empty($row['is_read'])) {
        $unread_count++;
    } else {
        $read_count++;
    }
    $t = strtolower($row['type'] ?? '');
    if (in_array($t, ['appointment', 'intake', 'questionnaire', 'clinical', 'prescription'], true)) {
        $clinical_count++;
    }
}
?>

<section class="dior-tab-panel notifications-tab-panel" id="tab-notifications"
    style="display: none; background-color: #f8fafc; padding: 20px; position: relative; overflow: hidden;">

    <!-- Scoped Style Overrides for Notifications Panel -->
    <style>
        #tab-notifications .dior-notif-stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            width: 100%;
        }

        #tab-notifications .dior-notif-stat-col {
            flex: 1 1 calc(25% - 12px);
            min-width: 220px;
        }

        @media (max-width: 992px) {
            #tab-notifications .dior-notif-stat-col {
                flex: 1 1 calc(50% - 12px);
            }
        }

        @media (max-width: 576px) {
            #tab-notifications .dior-notif-stat-col {
                flex: 1 1 100%;
            }
        }

        #tab-notifications .dior-page-watermark,
        #tab-notifications .page-watermark,
        #tab-notifications .dior-big-title {
            display: none !important;
        }
    </style>

    <!-- Header & Breadcrumb -->
    <div class="mb-4"
        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; position: relative; z-index: 3;">
        <div>
            <h4 class="mb-0 text-dark" style="font-size: 20px; font-weight: 700; color: #0F172A;">
                <i class="fas fa-bell text-primary me-2"></i>Patient Notifications
            </h4>
        </div>

    </div>

    <div class="section-body" style="position: relative; z-index: 2;">

        <!-- Stat Cards (4-Column Grid) -->
        <div class="dior-notif-stats-row mb-4">

            <!-- Stat 1: Total Notifications (Indigo) -->
            <div class="dior-notif-stat-col">
                <div class="stat-card stat-card-indigo">
                    <div class="stat-glow"></div>
                    <div class="stat-inner">
                        <div class="d-flex justify-content-between align-items-start mb-2"
                            style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <span class="stat-label">Total Notifications</span>
                                <h3 class="stat-value text-indigo mt-1 mb-0" id="dior-sys-total-stat">
                                    <?php echo $total_count; ?>
                                </h3>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fas fa-bell"></i></div>
                        </div>
                        <div class="stat-footer d-flex align-items-center"
                            style="display: flex; align-items: center; margin-top: auto; padding-top: 12px;">
                            <span class="stat-badge badge-indigo">
                                <i class="fas fa-clock-rotate-left text-xs me-1"></i> Live Stream
                            </span>
                            <span class="stat-subtext ms-2" style="margin-left: 8px;">Real-time log</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat 2: Unread Items (Amber) -->
            <div class="dior-notif-stat-col">
                <div class="stat-card stat-card-amber">
                    <div class="stat-glow"></div>
                    <div class="stat-inner">
                        <div class="d-flex justify-content-between align-items-start mb-2"
                            style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <span class="stat-label">Unread Items</span>
                                <h3 class="stat-value text-amber mt-1 mb-0" id="dior-sys-unread-stat">
                                    <?php echo $unread_count; ?>
                                </h3>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fas fa-envelope-open-text"></i></div>
                        </div>
                        <div class="stat-footer d-flex align-items-center"
                            style="display: flex; align-items: center; margin-top: auto; padding-top: 12px;">
                            <span class="stat-badge badge-amber">
                                <i class="fas fa-hourglass-half text-xs me-1"></i> Pending Action
                            </span>
                            <span class="stat-subtext ms-2" style="margin-left: 8px;">Requires review</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat 3: Appointments & Care (Rose) -->
            <div class="dior-notif-stat-col">
                <div class="stat-card stat-card-rose">
                    <div class="stat-glow"></div>
                    <div class="stat-inner">
                        <div class="d-flex justify-content-between align-items-start mb-2"
                            style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <span class="stat-label">Appointments &amp; Care</span>
                                <h3 class="stat-value text-rose mt-1 mb-0" id="dior-sys-care-stat">
                                    <?php echo $clinical_count; ?>
                                </h3>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fas fa-calendar-check"></i></div>
                        </div>
                        <div class="stat-footer d-flex align-items-center"
                            style="display: flex; align-items: center; margin-top: auto; padding-top: 12px;">
                            <span class="stat-badge badge-rose">
                                <i class="fas fa-heart-pulse text-xs me-1"></i> Care Stream
                            </span>
                            <span class="stat-subtext ms-2" style="margin-left: 8px;">Visits &amp; consults</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat 4: Account Status (Emerald) -->
            <div class="dior-notif-stat-col">
                <div class="stat-card stat-card-emerald">
                    <div class="stat-glow"></div>
                    <div class="stat-inner">
                        <div class="d-flex justify-content-between align-items-start mb-2"
                            style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <span class="stat-label">Account Health</span>
                                <h3 class="stat-value text-emerald mt-1 mb-0">100%</h3>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fas fa-shield-check"></i></div>
                        </div>
                        <div class="stat-footer d-flex align-items-center"
                            style="display: flex; align-items: center; margin-top: auto; padding-top: 12px;">
                            <span class="stat-badge badge-emerald">
                                <i class="fas fa-circle-check text-xs me-1"></i> Active State
                            </span>
                            <span class="stat-subtext ms-2" style="margin-left: 8px;">HIPAA Encrypted</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Main Feed Card -->
        <div class="row">
            <div class="col-12" style="width: 100%;">
                <div class="card main-feed-card"
                    style="border-radius: 14px; background: #ffffff; border: 1px solid #E2E8F0; box-shadow: 0 4px 18px rgba(15,23,42,.05); overflow: hidden;">

                    <!-- Card Header -->
                    <div class="card-header border-0 pb-0 pt-3 px-4"
                        style="padding: 20px 24px 14px 24px; border-bottom: 1px solid #f1f5f9; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
                        <div class="d-flex align-items-center gap-2"
                            style="display: flex; align-items: center; gap: 10px;">
                            <h4 class="card-title-text mb-0"
                                style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0;">
                                <i class="fas fa-bell me-2 text-primary"
                                    style="color: #1A528E !important; margin-right: 6px;"></i>Notification Activity Feed
                            </h4>
                            <span class="unread-count-chip" id="dior-sys-unread-chip"><?php echo $unread_count; ?>
                                Unread</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap" style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-sm btn-outline-action"
                                onclick="diorMarkAllSystemNotificationsRead()">
                                <i class="fas fa-check-double me-1" style="margin-right: 4px;"></i> Mark All Read
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-action text-danger"
                                onclick="diorClearReadSystemNotifications()">
                                <i class="fas fa-trash me-1" style="margin-right: 4px;"></i> Clear Read
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-action text-danger"
                                onclick="diorDeleteAllSystemNotifications()">
                                <i class="fas fa-trash-can me-1" style="margin-right: 4px;"></i> Delete All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-action"
                                onclick="diorDownloadPatientNotifCSV()">
                                <i class="fas fa-file-arrow-down me-1" style="margin-right: 4px;"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-action"
                                onclick="diorRefreshLivePatientNotifs()" title="Refresh Notifications">
                                <i class="fas fa-rotate-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Filters Bar -->
                    <div class="filters-bar px-4 py-3 border-top"
                        style="padding: 14px 24px; background: #f8fafc; border-bottom: 1px solid #E2E8F0;">
                        <div class="row align-items-center g-3">
                            <div style="flex: 0 0 auto; display: flex; justify-content: space-between;">
                                <div class="segmented-nav">
                                    <button type="button" class="segmented-tab active btn"
                                        onclick="diorFilterSystemNotif(this, 'all')">
                                        All <span class="count-pill"
                                            id="dior-pill-all"><?php echo $total_count; ?></span>
                                    </button>
                                    <button type="button" class="segmented-tab btn"
                                        onclick="diorFilterSystemNotif(this, 'unread')">
                                        Unread <span class="count-pill"
                                            id="dior-pill-unread"><?php echo $unread_count; ?></span>
                                    </button>
                                    <button type="button" class="segmented-tab btn"
                                        onclick="diorFilterSystemNotif(this, 'read')">
                                        Read <span class="count-pill"
                                            id="dior-pill-read"><?php echo $read_count; ?></span>
                                    </button>
                                </div>
                                <div class="input-group input-group-sm dior-bs-search-group" style="width: 240px;">
                                    <input type="text" id="dior-emerg-search-input"
                                        class="form-control border-start-0 ps-0" placeholder="Search records..."
                                        aria-label="Search box" onkeyup="diorFilterEmerg()">
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Activity Feed Items List -->
                    <div class="card-body p-0">
                        <div class="notifications-list-wrapper" id="dior-system-notif-list">

                            <?php if (!empty($patient_notif_rows)): ?>
                                <?php foreach ($patient_notif_rows as $n):
                                    $n_id = $n['id'] ?? uniqid('notif_');
                                    $n_title = $n['title'] ?? 'Notification';
                                    $n_message = $n['message'] ?? '';
                                    $n_type_raw = strtolower($n['type'] ?? 'system');
                                    $is_unread = empty($n['is_read']);
                                    $read_state = $is_unread ? 'unread' : 'read';

                                    // Format timestamp
                                    $n_ts = !empty($n['timestamp']) ? (int) $n['timestamp'] : (!empty($n['created_at']) ? strtotime($n['created_at']) : time());
                                    $date_formatted = date('M d, Y', $n_ts);
                                    $time_formatted = date('h:i A', $n_ts);

                                    // Resolve Icon, Priority & Action Target based on notification type
                                    $type_class = 'notification-type-system';
                                    $icon = 'fa-bell';
                                    $priority_badge = 'badge-priority-medium';
                                    $priority_text = 'Medium';
                                    $action_label = 'View Details';
                                    $action_tab = 'overview';

                                    if (strpos($n_type_raw, 'appointment') !== false || strpos($n_type_raw, 'consult') !== false || strpos($n_type_raw, 'visit') !== false) {
                                        $type_class = 'notification-type-appointment';
                                        $icon = 'fa-calendar-check';
                                        $priority_badge = 'badge-priority-high';
                                        $priority_text = 'Appointment';
                                        $action_label = 'View Visits';
                                        $action_tab = 'appointments';
                                    } elseif (strpos($n_type_raw, 'rx') !== false || strpos($n_type_raw, 'prescription') !== false || strpos($n_type_raw, 'med') !== false) {
                                        $type_class = 'notification-type-user';
                                        $icon = 'fa-prescription-bottle-medical';
                                        $priority_badge = 'badge-priority-high';
                                        $priority_text = 'Prescription';
                                        $action_label = 'View Prescriptions';
                                        $action_tab = 'docs_meds';
                                    } elseif (strpos($n_type_raw, 'payment') !== false || strpos($n_type_raw, 'bill') !== false || strpos($n_type_raw, 'invoice') !== false) {
                                        $type_class = 'notification-type-billing';
                                        $icon = 'fa-receipt';
                                        $priority_badge = 'badge-priority-medium';
                                        $priority_text = 'Billing';
                                        $action_label = 'View Invoices';
                                        $action_tab = 'payments';
                                    } elseif (strpos($n_type_raw, 'intake') !== false || strpos($n_type_raw, 'questionnaire') !== false || strpos($n_type_raw, 'record') !== false) {
                                        $type_class = 'notification-type-update';
                                        $icon = 'fa-clipboard-list';
                                        $priority_badge = 'badge-priority-medium';
                                        $priority_text = 'Clinical Intake';
                                        $action_label = 'Medical Record';
                                        $action_tab = 'medical_record';
                                    } elseif (strpos($n_type_raw, 'security') !== false || strpos($n_type_raw, 'password') !== false || strpos($n_type_raw, 'auth') !== false) {
                                        $type_class = 'notification-type-security';
                                        $icon = 'fa-shield-halved';
                                        $priority_badge = 'badge-priority-critical';
                                        $priority_text = 'Security';
                                        $action_label = 'Security Settings';
                                        $action_tab = 'settings';
                                    }

                                    // Action click logic
                                    $action_url = $n['action_url'] ?? '';
                                    if (!empty($action_url)) {
                                        if (strpos($action_url, '#tab=') !== false) {
                                            $action_tab = str_replace('#tab=', '', $action_url);
                                            $action_onclick = "if(typeof diorSwitchTab==='function'){diorSwitchTab('" . esc_js($action_tab) . "');}else{window.location.hash='#tab=" . esc_js($action_tab) . "';}";
                                        } else {
                                            $action_onclick = "window.open('" . esc_url($action_url) . "', '_blank');";
                                        }
                                    } else {
                                        $action_onclick = "if(typeof diorSwitchTab==='function'){diorSwitchTab('" . esc_js($action_tab) . "');}else{window.location.hash='#tab=" . esc_js($action_tab) . "';}";
                                    }
                                    ?>
                                    <div class="notification-item-row <?php echo $is_unread ? 'is-unread-row' : ''; ?> <?php echo esc_attr($type_class); ?>"
                                        data-read-state="<?php echo esc_attr($read_state); ?>"
                                        data-notif-id="<?php echo esc_attr($n_id); ?>"
                                        data-is-clinical="<?php echo in_array($n_type_raw, ['appointment', 'intake', 'questionnaire', 'clinical', 'prescription'], true) ? '1' : '0'; ?>">

                                        <?php if ($is_unread): ?>
                                            <div class="unread-strip"></div>
                                        <?php endif; ?>

                                        <div class="type-icon-wrapper">
                                            <i class="fas <?php echo esc_attr($icon); ?>"></i>
                                        </div>

                                        <div class="notification-body flex-grow-1 min-w-0 me-3"
                                            style="flex: 1 1 auto; min-width: 0;">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1"
                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                <div class="d-flex align-items-center gap-2 flex-wrap"
                                                    style="display: flex; align-items: center; gap: 8px;">
                                                    <h6 class="notification-title mb-0"><?php echo esc_html($n_title); ?></h6>
                                                    <span class="badge-priority <?php echo esc_attr($priority_badge); ?>">
                                                        <span class="status-dot"></span> <?php echo esc_html($priority_text); ?>
                                                    </span>
                                                </div>
                                                <span class="timestamp-tag">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php echo esc_html($date_formatted . ' ' . $time_formatted); ?>
                                                </span>
                                            </div>

                                            <p class="notification-text mb-2"><?php echo esc_html($n_message); ?></p>

                                            <div class="notification-action-bar">
                                                <a class="btn btn-sm btn-inline-action" href="javascript:void(0)"
                                                    onclick="<?php echo $action_onclick; ?>">
                                                    <i class="fas fa-arrow-up-right-from-square me-1"
                                                        style="margin-right: 4px;"></i> <?php echo esc_html($action_label); ?>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="notification-controls d-flex align-items-center gap-1"
                                            style="display: flex; gap: 4px;">
                                            <?php if ($is_unread): ?>
                                                <button type="button" title="Mark as Read"
                                                    onclick="diorMarkRowAsRead(this, '<?php echo esc_js($n_id); ?>')"
                                                    class="btn btn-sm btn-icon-action">
                                                    <i class="fas fa-check text-success" style="color: #059669;"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" title="Delete Notification"
                                                onclick="diorDeleteNotificationRow(this, '<?php echo esc_js($n_id); ?>')"
                                                class="btn btn-sm btn-icon-action text-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-5 text-center text-muted dior-empty-state" id="dior-empty-notif-box">
                                    <i class="fas fa-bell-slash fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h5 class="fw-bold text-dark">No notifications right now</h5>
                                    <p class="text-secondary small mb-0">You're all caught up! New clinical updates,
                                        appointments, and messages will appear here.</p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- Pagination (10 notifications per page) -->
                    <div class="dior-table-pagination" id="dior-patient-notif-pagination" style="display:none;"></div>

                </div>
            </div>
        </div>

    </div>
</section>

<!-- Notification Scripts for Patient Portal -->
<script>
    function diorFilterSystemNotif(btn, filterState) {
        if (!btn || !btn.parentElement) return;
        btn.parentElement.querySelectorAll('.segmented-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const rows = document.querySelectorAll('#dior-system-notif-list .notification-item-row');
        let visibleCount = 0;
        rows.forEach(row => {
            if (filterState === 'all') {
                row.style.display = 'flex';
                visibleCount++;
            } else if (filterState === 'unread') {
                const match = row.dataset.readState === 'unread';
                row.style.display = match ? 'flex' : 'none';
                if (match) visibleCount++;
            } else if (filterState === 'read') {
                const match = row.dataset.readState === 'read';
                row.style.display = match ? 'flex' : 'none';
                if (match) visibleCount++;
            }
        });
    }

    function diorSearchSystemNotifications() {
        const input = document.getElementById('dior-sys-notif-search');
        const val = input ? input.value.toLowerCase().trim() : '';
        const rows = document.querySelectorAll('#dior-system-notif-list .notification-item-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(val) ? 'flex' : 'none';
        });
    }

    function diorUpdateNotifBadgesAndStats() {
        const allRows = document.querySelectorAll('#dior-system-notif-list .notification-item-row');
        const unreadRows = document.querySelectorAll('#dior-system-notif-list .notification-item-row[data-read-state="unread"]');
        const readRows = document.querySelectorAll('#dior-system-notif-list .notification-item-row[data-read-state="read"]');

        const total = allRows.length;
        const unread = unreadRows.length;
        const read = readRows.length;

        const statTotal = document.getElementById('dior-sys-total-stat');
        if (statTotal) statTotal.textContent = total;

        const statUnread = document.getElementById('dior-sys-unread-stat');
        if (statUnread) statUnread.textContent = unread;

        const careRows = document.querySelectorAll('#dior-system-notif-list .notification-item-row[data-is-clinical="1"]');
        const statCare = document.getElementById('dior-sys-care-stat');
        if (statCare) statCare.textContent = careRows.length;

        const chip = document.getElementById('dior-sys-unread-chip');
        if (chip) chip.textContent = unread + ' Unread';

        const pillAll = document.getElementById('dior-pill-all');
        if (pillAll) pillAll.textContent = total;

        const pillUnread = document.getElementById('dior-pill-unread');
        if (pillUnread) pillUnread.textContent = unread;

        const pillRead = document.getElementById('dior-pill-read');
        if (pillRead) pillRead.textContent = read;

        // Update topbar badges if present
        const unreadBadges = document.querySelectorAll('.unread-badge-count, .dior-notif-indicator');
        unreadBadges.forEach(b => {
            if (unread > 0) {
                b.textContent = unread;
                b.style.display = '';
            } else {
                b.style.display = 'none';
            }
        });

        const list = document.getElementById('dior-system-notif-list');
        if (list && total === 0 && !document.getElementById('dior-empty-notif-box')) {
            list.innerHTML = '<div class="p-5 text-center text-muted dior-empty-state" id="dior-empty-notif-box">' +
                '<i class="fas fa-bell-slash fa-3x mb-3 text-secondary opacity-50"></i>' +
                '<h5 class="fw-bold text-dark">No notifications right now</h5>' +
                '<p class="text-secondary small mb-0">You\'re all caught up! New clinical updates, appointments, and messages will appear here.</p>' +
                '</div>';
        }
    }

    function diorMarkRowAsRead(btn, notifId) {
        const row = btn.closest('.notification-item-row');
        if (row) {
            row.classList.remove('is-unread-row');
            row.dataset.readState = 'read';
            const strip = row.querySelector('.unread-strip');
            if (strip) strip.remove();
            btn.remove();
        }

        diorUpdateNotifBadgesAndStats();

        // Sync with WordPress backend
        const ajaxUrl = (window.dior_patient_dashboard && window.dior_patient_dashboard.ajax_url) || '/diormedical/wp-admin/admin-ajax.php';
        const nonce = (window.dior_patient_dashboard && window.dior_patient_dashboard.portal_nonce) || '';

        if (notifId) {
            const body = new URLSearchParams({
                action: 'dior_patient_mark_notification_read',
                notif_id: notifId,
                nonce: nonce
            });
            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            }).catch(() => { });
        }
    }

    function diorMarkAllSystemNotificationsRead() {
        const unreadRows = document.querySelectorAll('#dior-system-notif-list .is-unread-row');
        unreadRows.forEach(row => {
            row.classList.remove('is-unread-row');
            row.dataset.readState = 'read';
            const strip = row.querySelector('.unread-strip');
            if (strip) strip.remove();
            const checkBtn = row.querySelector('.notification-controls .btn-icon-action .fa-check');
            if (checkBtn) checkBtn.closest('button').remove();
        });

        diorUpdateNotifBadgesAndStats();

        // Sync with WordPress backend
        const ajaxUrl = (window.dior_patient_dashboard && window.dior_patient_dashboard.ajax_url) || '/diormedical/wp-admin/admin-ajax.php';
        const nonce = (window.dior_patient_dashboard && window.dior_patient_dashboard.portal_nonce) || '';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({
                action: 'dior_patient_mark_all_notifications_read',
                nonce: nonce
            }).toString()
        }).then(res => res.json()).then(data => {
            if (window.Swal) {
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: 'All notifications marked as read',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        }).catch(() => { });
    }

    function diorDeleteNotificationRow(btn, notifId) {
        const row = btn.closest('.notification-item-row');
        if (!row || !notifId) return;

        const doDelete = function () {
            const cfg = window.dior_patient_dashboard || window.dior_vars || {};
            const ajaxUrl = cfg.ajax_url || '';
            const nonce = cfg.portal_nonce || cfg.nonce || '';
            if (!ajaxUrl || !nonce) {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Session Expired', text: 'Refresh the page and try again.' });
                return;
            }

            const body = new URLSearchParams({ action: 'dior_patient_bulk_delete_notifications', nonce: nonce });
            body.append('notif_ids[]', notifId);
            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) throw new Error((data && data.data && data.data.message) || 'Notification could not be deleted.');
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => { row.remove(); diorUpdateNotifBadgesAndStats(); }, 300);
                })
                .catch(error => {
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Delete Failed', text: error.message || 'Please try again.' });
                });
        };

        if (window.Swal) {
            Swal.fire({
                title: 'Delete Notification?',
                text: 'This notification will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete!'
            }).then(result => { if (result.isConfirmed) doDelete(); });
        } else {
            doDelete();
        }
    }

    function diorClearReadSystemNotifications() {
        const readRows = Array.from(document.querySelectorAll('#dior-system-notif-list .notification-item-row[data-read-state="read"]'));
        if (!readRows.length) {
            if (window.Swal) Swal.fire({ icon: 'info', title: 'No Read Notifications', text: 'There are no read notifications to clear.' });
            return;
        }

        const notifIds = readRows.map(row => row.dataset.notifId).filter(Boolean);
        if (!notifIds.length) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Delete Failed', text: 'Notification IDs are missing. No records were removed.' });
            return;
        }

        const doClear = function () {
            const cfg = window.dior_patient_dashboard || window.dior_vars || {};
            const ajaxUrl = cfg.ajax_url || '';
            const nonce = cfg.portal_nonce || cfg.nonce || '';
            if (!ajaxUrl || !nonce) {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Session Expired', text: 'Refresh the page and try again.' });
                return;
            }

            const body = new URLSearchParams({ action: 'dior_patient_bulk_delete_notifications', nonce: nonce });
            notifIds.forEach(id => body.append('notif_ids[]', id));
            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) throw new Error((data && data.data && data.data.message) || 'Read notifications could not be deleted.');
                    readRows.forEach(row => row.remove());
                    diorUpdateNotifBadgesAndStats();
                    if (window.Swal) Swal.fire({ position: 'top-end', icon: 'success', title: 'Read notifications cleared', showConfirmButton: false, timer: 1500 });
                })
                .catch(error => {
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Delete Failed', text: error.message || 'Please try again.' });
                });
        };

        if (window.Swal) {
            Swal.fire({
                title: 'Clear Read Notifications?',
                text: 'You are about to remove ' + readRows.length + ' read notification(s).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, clear read!'
            }).then(result => { if (result.isConfirmed) doClear(); });
        } else {
            doClear();
        }
    }

    function diorDeleteAllSystemNotifications() {
        const allRows = Array.from(document.querySelectorAll('#dior-system-notif-list .notification-item-row'));
        if (!allRows.length) {
            if (window.Swal) Swal.fire({ icon: 'info', title: 'No Notifications', text: 'There are no notifications to delete.' });
            return;
        }

        const notifIds = allRows.map(row => row.dataset.notifId).filter(Boolean);
        if (!notifIds.length) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Delete Failed', text: 'Notification IDs are missing. No records were removed.' });
            return;
        }

        const doDeleteAll = function () {
            const cfg = window.dior_patient_dashboard || window.dior_vars || {};
            const ajaxUrl = cfg.ajax_url || '';
            const nonce = cfg.portal_nonce || cfg.nonce || '';
            if (!ajaxUrl || !nonce) {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Session Expired', text: 'Refresh the page and try again.' });
                return;
            }

            const body = new URLSearchParams({ action: 'dior_patient_bulk_delete_notifications', nonce: nonce });
            notifIds.forEach(id => body.append('notif_ids[]', id));
            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) throw new Error((data && data.data && data.data.message) || 'Notifications could not be deleted.');
                    allRows.forEach(row => row.remove());
                    diorUpdateNotifBadgesAndStats();
                    if (window.Swal) Swal.fire({ position: 'top-end', icon: 'success', title: 'All notifications deleted', showConfirmButton: false, timer: 1500 });
                })
                .catch(error => {
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Delete Failed', text: error.message || 'Please try again.' });
                });
        };

        if (window.Swal) {
            Swal.fire({
                title: 'Delete All Notifications?',
                text: 'You are about to permanently remove all ' + allRows.length + ' notification(s).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete all!'
            }).then(result => { if (result.isConfirmed) doDeleteAll(); });
        } else {
            doDeleteAll();
        }
    }

</script></script>

<!-- Notification pagination: 10 per page, composed with the existing filters -->
<script>
(function () {
    'use strict';

    var LIST_SELECTOR = '#dior-system-notif-list .notification-item-row';
    var BAR_ID = 'dior-patient-notif-pagination';
    var PAGE_SIZE = 10;
    var HIDDEN = 'd-none';
    var currentPage = 1;

    function allRows() {
        return Array.prototype.slice.call(document.querySelectorAll(LIST_SELECTOR));
    }

    function activeRows() {
        return allRows().filter(function (row) {
            return row.style.display !== 'none';
        });
    }

    function render(page) {
        var bar = document.getElementById(BAR_ID);
        var rows = allRows();
        if (!bar || rows.length === 0) {
            if (bar) { bar.style.display = 'none'; bar.innerHTML = ''; }
            return;
        }

        var active = activeRows();
        var total = active.length;
        var totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

        if (page > totalPages) page = totalPages;
        if (page < 1) page = 1;
        currentPage = page;

        rows.forEach(function (row) { row.classList.add(HIDDEN); });

        if (total <= PAGE_SIZE) {
            active.forEach(function (row) { row.classList.remove(HIDDEN); });
            bar.style.display = 'none';
            bar.innerHTML = '';
            return;
        }

        var start = (currentPage - 1) * PAGE_SIZE;
        var end = Math.min(start + PAGE_SIZE, total);
        active.slice(start, end).forEach(function (row) { row.classList.remove(HIDDEN); });

        var nav = '';
        nav += '<button type="button" class="dior-page-btn" data-page="' + (currentPage - 1) + '"' +
            (currentPage === 1 ? ' disabled' : '') + ' title="Previous Page">' +
            '<i class="fa-solid fa-chevron-left" style="font-size:11px;"></i></button>';

        if (totalPages <= 7) {
            for (var i = 1; i <= totalPages; i++) {
                nav += '<button type="button" class="dior-page-btn' + (i === currentPage ? ' active' : '') +
                    '" data-page="' + i + '">' + i + '</button>';
            }
        } else {
            for (var j = 1; j <= totalPages; j++) {
                if (j === 1 || j === totalPages || (j >= currentPage - 1 && j <= currentPage + 1)) {
                    nav += '<button type="button" class="dior-page-btn' + (j === currentPage ? ' active' : '') +
                        '" data-page="' + j + '">' + j + '</button>';
                } else if (j === currentPage - 2 || j === currentPage + 2) {
                    nav += '<span class="px-1 text-secondary small">...</span>';
                }
            }
        }

        nav += '<button type="button" class="dior-page-btn" data-page="' + (currentPage + 1) + '"' +
            (currentPage === totalPages ? ' disabled' : '') + ' title="Next Page">' +
            '<i class="fa-solid fa-chevron-right" style="font-size:11px;"></i></button>';

        bar.style.display = 'flex';
        bar.innerHTML =
            '<div class="dior-pagination-info">Showing <strong>' + (start + 1) + '</strong> to <strong>' +
            end + '</strong> of <strong>' + total + '</strong> entries</div>' +
            '<div class="dior-pagination-nav">' + nav + '</div>';

        bar.querySelectorAll('.dior-page-btn[data-page]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var target = parseInt(btn.getAttribute('data-page'), 10);
                if (!isNaN(target) && !btn.disabled) render(target);
            });
        });
    }

    window.diorRenderPatientNotifPage = render;
    window.diorResetPatientNotifPager = function () { render(1); };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { render(1); });
    } else {
        render(1);
    }

    function wrapAfter(name, reset) {
        var original = window[name];
        if (typeof original !== 'function') return;
        window[name] = function () {
            var result = original.apply(this, arguments);
            if (reset) {
                render(1);
            } else {
                setTimeout(function () { render(currentPage); }, 0);
            }
            return result;
        };
    }

    wrapAfter('diorFilterSystemNotif', true);
    wrapAfter('diorSearchSystemNotifications', true);
    wrapAfter('diorUpdateNotifBadgesAndStats', false);
})();
</script>