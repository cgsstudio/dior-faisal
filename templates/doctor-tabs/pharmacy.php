<?php
/**
 * Pharmacy — Medicine List + Add Medicine.
 *
 * The list is rendered from wp_dior_medicines (scoped to the doctors the
 * current user can act for) and every row action is handled by
 * assets/js/dior-pharmacy.js against the dior_doc_medicine_* AJAX endpoints.
 *
 * @package Dior
 */

$dior_pharm_medicines = class_exists('Dior_Doctor_Dynamic') ? Dior_Doctor_Dynamic::render_medicines() : [];
$dior_pharm_nonce     = wp_create_nonce('dior_doctor_nonce');

/**
 * Display formatting helpers local to this template.
 */
if (!function_exists('dior_pharm_money')) {
    function dior_pharm_money($value)
    {
        $number = (float) $value;
        $decimals = (floor($number) == $number) ? 0 : 2;
        return '$' . number_format($number, $decimals);
    }
}
if (!function_exists('dior_pharm_date')) {
    function dior_pharm_date($value)
    {
        if (empty($value) || $value === '0000-00-00') return '';
        $stamp = strtotime($value);
        return $stamp ? date_i18n('M j, Y', $stamp) : (string) $value;
    }
}
if (!function_exists('dior_pharm_category_icon')) {
    function dior_pharm_category_icon($category)
    {
        $map = [
            'Tablet' => 'fa-tablets',
            'Capsule' => 'fa-capsules',
            'Syrup' => 'fa-bottle-droplet',
            'Injectable' => 'fa-syringe',
            'Ointment' => 'fa-jar',
            'Drops' => 'fa-eye-dropper',
            'Inhaler' => 'fa-lungs',
        ];
        $key = (string) $category;
        return isset($map[$key]) ? $map[$key] : 'fa-pills';
    }
}
$dior_pharm_categories = ['Tablet', 'Capsule', 'Syrup', 'Injectable', 'Ointment', 'Drops', 'Inhaler', 'Other'];
?>
<!-- Medicine List Tab -->
<section class="dior-tab-panel dior-pharm-screen" id="tab-doc-pharmacy-list" style="display: none;">
    <input type="hidden" id="dior-pharmacy-nonce" value="<?php echo esc_attr($dior_pharm_nonce); ?>">

    <!-- Breadcrumb Header -->
    <div class="mb-4 dior-pharm-page-heading">
        <div>
            <h4 class="mb-0 text-dark dior-pharm-page-title">Pharmacy</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house dior-pharm-crumb-icon"></i></a></li>
                <li><span class="dior-pharm-crumb-sep">/</span></li>
                <li class="active"><span>Medicine List</span></li>
            </ul>
        </div>
    </div>

    <!-- Subtab Navigation Bar -->
    <div class="dior-subtab-nav-bar dior-pharm-subtabs px-1 mb-4">
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-pharmacy-list"
            aria-current="page"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-pharmacy-list');">
            <i class="fas fa-pills"></i> Medicine List
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-pharmacy-add"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-pharmacy-add');">
            <i class="fas fa-circle-plus"></i> Add Medicine
        </button>
    </div>

    <!-- Main Content -->
    <div class="section-body">
        <div class="view-appointment-card">

            <!-- Table Header -->
            <div class="va-header-container">
                <div class="va-title-box">
                    <h2><i class="fas fa-pills text-primary me-2"></i>Medicine List</h2>
                    <div class="va-title-line"></div>
                </div>

                <div class="va-actions-wrapper">
                    <div class="input-group input-group-sm dior-bs-search-group">
                        <input type="text" class="form-control border-start-0 ps-0" placeholder="Search records..."
                            aria-label="Search medicines">
                    </div>
                    <div class="va-actions-group">
                        <button type="button" class="va-icon-btn va-btn-success btn" title="Export to Excel"
                            aria-label="Export medicine list to CSV">
                            <i class="fas fa-file-arrow-down"></i>
                        </button>
                        <button type="button" class="va-icon-btn va-btn-info btn" title="Refresh data"
                            aria-label="Refresh medicine list">
                            <i class="fas fa-rotate-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Responsive Wrapper -->
            <div class="va-table-wrapper">
                <table class="va-table table table-hover dior-pharm-table">
                    <thead>
                        <tr>
                            <th class="text-center dior-pharm-col-select">
                                <input type="checkbox" class="va-checkbox form-check-input"
                                    aria-label="Select all medicines">
                            </th>
                            <th>MEDICINE NO <i class="fas fa-sort"></i></th>
                            <th>MEDICINE NAME <i class="fas fa-sort"></i></th>
                            <th>CATEGORY <i class="fas fa-sort"></i></th>
                            <th>COMPANY <i class="fas fa-sort"></i></th>
                            <th>PURCHASE DATE <i class="fas fa-sort"></i></th>
                            <th>PRICE <i class="fas fa-sort"></i></th>
                            <th>EXPIRY DATE <i class="fas fa-sort"></i></th>
                            <th>STOCK <i class="fas fa-sort"></i></th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($dior_pharm_medicines)): ?>
                            <?php foreach ($dior_pharm_medicines as $medicine): ?>
                                <?php
                                $purchase_date = dior_pharm_date($medicine['purchase_date'] ?? '');
                                $expiry_date   = dior_pharm_date($medicine['expiry_date'] ?? '');
                                $stock         = (int) ($medicine['stock'] ?? 0);
                                $name          = $medicine['medicine_name'] ?? '';
                                ?>
                                <tr data-medicine-id="<?php echo esc_attr($medicine['id']); ?>"
                                    data-purchase-date="<?php echo esc_attr($medicine['purchase_date'] ?? ''); ?>"
                                    data-expiry-date="<?php echo esc_attr($medicine['expiry_date'] ?? ''); ?>">
                                    <td class="text-center"><input type="checkbox"
                                            class="va-checkbox form-check-input"
                                            aria-label="<?php echo esc_attr('Select ' . $name); ?>"></td>
                                    <td data-label="Medicine No"><?php echo esc_html($medicine['medicine_no'] ?: '—'); ?></td>
                                    <td data-label="Medicine Name"><strong><?php echo esc_html($name); ?></strong></td>
                                    <td data-label="Category">
                                        <span class="dior-pharm-cat">
                                            <i class="fas <?php echo esc_attr(dior_pharm_category_icon($medicine['category'] ?? '')); ?>"></i>
                                            <?php echo esc_html($medicine['category'] ?: '—'); ?>
                                        </span>
                                    </td>
                                    <td data-label="Company"><?php echo esc_html($medicine['company_name'] ?: '—'); ?></td>
                                    <td data-label="Purchase Date">
                                        <?php if ($purchase_date): ?>
                                            <i class="far fa-calendar va-icon-blue"></i> <?php echo esc_html($purchase_date); ?>
                                        <?php else: ?>
                                            <span class="dior-pharm-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Price"><?php echo esc_html(dior_pharm_money($medicine['price'] ?? 0)); ?></td>
                                    <td data-label="Expiry Date">
                                        <?php if ($expiry_date): ?>
                                            <i class="far fa-calendar va-icon-blue"></i> <?php echo esc_html($expiry_date); ?>
                                        <?php else: ?>
                                            <span class="dior-pharm-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Stock">
                                        <span class="dior-pharm-stock<?php echo $stock <= 20 ? ' is-low' : ''; ?>"><?php echo esc_html($stock); ?></span>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="va-row-actions">
                                            <button type="button" class="va-action-btn-sm va-btn-edit btn"
                                                title="Edit medicine"
                                                aria-label="<?php echo esc_attr('Edit ' . $name); ?>"><i class="fas fa-pen"></i></button>
                                            <button type="button" class="va-action-btn-sm va-btn-delete btn"
                                                title="Delete medicine"
                                                aria-label="<?php echo esc_attr('Delete ' . $name); ?>"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="dior-pharm-empty-row">
                                <td colspan="10" class="dior-pharm-empty">
                                    <i class="fas fa-pills"></i>
                                    <strong>No medicines in your inventory yet</strong>
                                    <span>Use <em>Add Medicine</em> to record the first item.</span>
                                    <button type="button" class="btn dior-pharm-empty-cta"
                                        data-dior-pharmacy-go="doc-pharmacy-add">
                                        <i class="fas fa-circle-plus"></i> Add Medicine
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="va-footer">
                <div class="va-pagination-text">
                    <strong><?php echo esc_html(count($dior_pharm_medicines)); ?></strong>
                    <?php echo esc_html(count($dior_pharm_medicines) === 1 ? 'medicine' : 'medicines'); ?> in inventory
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add Medicine Tab -->
<section class="dior-tab-panel dior-pharm-screen" id="tab-doc-pharmacy-add" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4 dior-pharm-page-heading">
        <div>
            <h4 class="mb-0 text-dark dior-pharm-page-title">Pharmacy</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house dior-pharm-crumb-icon"></i></a></li>
                <li><span class="dior-pharm-crumb-sep">/</span></li>
                <li class="active"><span>Add Medicine</span></li>
            </ul>
        </div>
    </div>

    <!-- Subtab Navigation Bar -->
    <div class="dior-subtab-nav-bar dior-pharm-subtabs px-1 mb-4">
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-pharmacy-list"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-pharmacy-list');">
            <i class="fas fa-pills"></i> Medicine List
        </button>
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-pharmacy-add"
            aria-current="page"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-pharmacy-add');">
            <i class="fas fa-circle-plus"></i> Add Medicine
        </button>
    </div>

    <div class="section-body">
        <div class="view-appointment-card dior-pharm-form-card">
            <div class="va-header-container">
                <div class="va-title-box">
                    <h2>Add Medicine</h2>
                    <div class="va-title-line"></div>
                </div>
            </div>

            <form id="dior-pharmacy-add-form" class="dior-pharm-form-body" novalidate
                aria-label="Add medicine to inventory">
                <div class="dior-pharm-grid">
                    <div class="dior-pharm-field">
                        <label class="dior-pharm-label" for="dior-med-no">No</label>
                        <input class="dior-pharm-input" type="text" id="dior-med-no" name="medicine_no"
                            inputmode="numeric" autocomplete="off" placeholder="Auto-generated if left blank">
                    </div>

                    <div class="dior-pharm-field">
                        <label class="dior-pharm-label" for="dior-med-name">Medicine Name
                            <span class="dior-pharm-req">*</span></label>
                        <input class="dior-pharm-input" type="text" id="dior-med-name" name="medicine_name"
                            autocomplete="off" placeholder="Medicine Name" required>
                    </div>

                    <div class="dior-pharm-field">
                        <label class="dior-pharm-label" for="dior-med-category">Category
                            <span class="dior-pharm-req">*</span></label>
                        <select class="dior-pharm-input" id="dior-med-category" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($dior_pharm_categories as $category): ?>
                                <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dior-pharm-field">
                        <label class="dior-pharm-label" for="dior-med-company">Company Name
                            <span class="dior-pharm-req">*</span></label>
                        <input class="dior-pharm-input" type="text" id="dior-med-company" name="company_name"
                            autocomplete="off" placeholder="Company Name" required>
                    </div>

                    <div class="dior-pharm-field">
                        <label class="dior-pharm-label" for="dior-med-purchase">Purchase Date
                            <span class="dior-pharm-req">*</span></label>
                        <input class="dior-pharm-input" type="date" id="dior-med-purchase" name="purchase_date"
                            required>
                    </div>

                    <div class="dior-pharm-field">
                        <label class="dior-pharm-label" for="dior-med-price">Price
                            <span class="dior-pharm-req">*</span></label>
                        <input class="dior-pharm-input" type="number" id="dior-med-price" name="price" min="0"
                            step="0.01" inputmode="decimal" placeholder="Price" required>
                    </div>

                    <div class="dior-pharm-field">
                        <label class="dior-pharm-label" for="dior-med-expiry">Expired Date
                            <span class="dior-pharm-req">*</span></label>
                        <input class="dior-pharm-input" type="date" id="dior-med-expiry" name="expiry_date" required>
                    </div>

                    <div class="dior-pharm-field">
                        <label class="dior-pharm-label" for="dior-med-stock">Stock
                            <span class="dior-pharm-req">*</span></label>
                        <input class="dior-pharm-input" type="number" id="dior-med-stock" name="stock" min="0"
                            step="1" inputmode="numeric" placeholder="Stock" required>
                    </div>
                </div>

                <p class="dior-pharm-hint">
                    <i class="fas fa-circle-info"></i>
                    <span>Fields marked <span class="dior-pharm-req">*</span> are required. The record appears in
                        <strong>Medicine List</strong> as soon as you save.</span>
                </p>

                <div class="dior-pharm-actions">
                    <button type="submit" class="btn dior-pharm-btn dior-pharm-btn-primary">
                        <i class="fas fa-floppy-disk"></i> Save Medicine
                    </button>
                    <button type="reset" class="btn dior-pharm-btn dior-pharm-btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>
