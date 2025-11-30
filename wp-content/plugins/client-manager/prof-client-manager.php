<?php
/**
 * Plugin Name:       Professional Client Manager
 * Plugin URI:        https://example.com/
 * Description:       A comprehensive and secure client management system for WordPress, inspired by professional data handling practices.
 * Version:           2.1.7
 * Author:            Your Name Here
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       prof-client-manager
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die;
}

define('PCM_VERSION', '2.1.7');

/**
 * Runs the database table creation/update.
 */
function pcm_run_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Table for clients
    $table_name_clients = $wpdb->prefix . 'pcm_clients';
    $sql_clients = "CREATE TABLE $table_name_clients (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        company varchar(255) DEFAULT '' NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(50) DEFAULT '' NOT NULL,
        website_url varchar(255) DEFAULT '' NOT NULL,
        username varchar(255) DEFAULT '' NOT NULL,
        password text NOT NULL,
        status varchar(50) NOT NULL,
        lead_source varchar(100) DEFAULT '' NOT NULL,
        first_contact_date date NOT NULL,
        objectives text,
        vault_entry_name varchar(255) DEFAULT '' NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_clients);

    // Table for requirements
    $table_name_reqs = $wpdb->prefix . 'pcm_client_requirements';
    $sql_reqs = "CREATE TABLE $table_name_reqs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_id mediumint(9) NOT NULL,
        requirement_title varchar(255) NOT NULL,
        requirement_details text,
        priority varchar(50) DEFAULT 'Medium' NOT NULL,
        status varchar(50) DEFAULT 'Pending' NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY client_id (client_id)
    ) $charset_collate;";
    dbDelta($sql_reqs);

    // Table for invoices
    $table_name_invoices = $wpdb->prefix . 'pcm_invoices';
    $sql_invoices = "CREATE TABLE $table_name_invoices (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_id mediumint(9) NOT NULL,
        invoice_number varchar(255) NOT NULL,
        invoice_date date NOT NULL,
        due_date date NOT NULL,
        line_items longtext,
        subtotal decimal(10,2) NOT NULL DEFAULT '0.00',
        tax_rate decimal(5,2) NOT NULL DEFAULT '0.00',
        tax_amount decimal(10,2) NOT NULL DEFAULT '0.00',
        total decimal(10,2) NOT NULL DEFAULT '0.00',
        currency varchar(10) NOT NULL DEFAULT '$',
        notes text,
        status varchar(50) DEFAULT 'Draft' NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY client_id (client_id)
    ) $charset_collate;";
    dbDelta($sql_invoices);
    
    // Table for proposals
    $table_name_proposals = $wpdb->prefix . 'pcm_proposals';
    $sql_proposals = "CREATE TABLE $table_name_proposals (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_id mediumint(9) NOT NULL,
        title varchar(255) NOT NULL,
        scope text,
        timeline text,
        pricing text,
        status varchar(50) DEFAULT 'Draft' NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY client_id (client_id)
    ) $charset_collate;";
    dbDelta($sql_proposals);

    // Table for organisation details
    $table_name_org = $wpdb->prefix . 'pcm_organisation_details';
    $sql_org = "CREATE TABLE $table_name_org (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        logo_path varchar(255) DEFAULT '' NOT NULL,
        address text,
        phone varchar(50) DEFAULT '' NOT NULL,
        email varchar(255) DEFAULT '' NOT NULL,
        website varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_org);
}

/**
 * The code that runs during plugin activation.
 */
function pcm_activate() {
    pcm_run_install();
    add_option('pcm_db_version', PCM_VERSION);
}
register_activation_hook(__FILE__, 'pcm_activate');

/**
 * Check for DB updates on plugin load to handle updates.
 */
function pcm_update_db_check() {
    if (get_option('pcm_db_version') != PCM_VERSION) {
        pcm_run_install();
        update_option('pcm_db_version', PCM_VERSION);
    }
}
add_action('plugins_loaded', 'pcm_update_db_check');


/**
 * Add main menu page.
 */
function pcm_add_admin_menu() {
    add_menu_page(
        'Client Manager',
        'Client Manager',
        'manage_options',
        'prof-client-manager',
        'pcm_render_main_page',
        'dashicons-businessperson',
        25
    );
}
add_action('admin_menu', 'pcm_add_admin_menu');

/**
 * Enqueue scripts for chart data and media uploader.
 */
function pcm_enqueue_scripts($hook) {
    if ('toplevel_page_prof-client-manager' != $hook) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);

    global $wpdb;
    // Client Status Data
    $table_clients = $wpdb->prefix . 'pcm_clients';
    $client_results = $wpdb->get_results("SELECT status, COUNT(id) as count FROM $table_clients GROUP BY status", ARRAY_A);
    $client_status_data = ['Active' => 0, 'Prospect' => 0, 'At Risk' => 0, 'Former' => 0];
    foreach($client_results as $row) {
        if(array_key_exists($row['status'], $client_status_data)){
            $client_status_data[$row['status']] = (int)$row['count'];
        }
    }

    // Requirement Status Data
    $table_reqs = $wpdb->prefix . 'pcm_client_requirements';
    $req_results = $wpdb->get_results("SELECT status, COUNT(id) as count FROM $table_reqs GROUP BY status", ARRAY_A);
    $req_status_data = ['Pending' => 0, 'In Progress' => 0, 'Completed' => 0];
    foreach($req_results as $row) {
        if(array_key_exists($row['status'], $req_status_data)){
            $req_status_data[$row['status']] = (int)$row['count'];
        }
    }

    // Invoice Status Data
    $table_invoices = $wpdb->prefix . 'pcm_invoices';
    $invoice_results = $wpdb->get_results("SELECT status, COUNT(id) as count FROM $table_invoices GROUP BY status", ARRAY_A);
    $invoice_status_data = ['Draft' => 0, 'Sent' => 0, 'Paid' => 0, 'Overdue' => 0];
    foreach($invoice_results as $row) {
        if(array_key_exists($row['status'], $invoice_status_data)){
            $invoice_status_data[$row['status']] = (int)$row['count'];
        }
    }

    // Proposal Status Data
    $table_proposals = $wpdb->prefix . 'pcm_proposals';
    $proposal_results = $wpdb->get_results("SELECT status, COUNT(id) as count FROM $table_proposals GROUP BY status", ARRAY_A);
    $proposal_status_data = ['Draft' => 0, 'Sent' => 0, 'Accepted' => 0, 'Declined' => 0];
    foreach($proposal_results as $row) {
        if(array_key_exists($row['status'], $proposal_status_data)){
            $proposal_status_data[$row['status']] = (int)$row['count'];
        }
    }
    
    wp_register_script('pcm-data-provider', '');
    wp_enqueue_script('pcm-data-provider');
    wp_add_inline_script('pcm-data-provider', 
        'const pcm_client_chart_data = ' . json_encode(array_values($client_status_data)) . '; '.
        'const pcm_client_chart_labels = ' . json_encode(array_keys($client_status_data)) . '; '.
        'const pcm_req_chart_data = ' . json_encode(array_values($req_status_data)) . '; '.
        'const pcm_req_chart_labels = ' . json_encode(array_keys($req_status_data)) . '; '.
        'const pcm_invoice_chart_data = ' . json_encode(array_values($invoice_status_data)) . '; '.
        'const pcm_invoice_chart_labels = ' . json_encode(array_keys($invoice_status_data)) . '; '.
        'const pcm_proposal_chart_data = ' . json_encode(array_values($proposal_status_data)) . '; '.
        'const pcm_proposal_chart_labels = ' . json_encode(array_keys($proposal_status_data)) . ';'
    );
}
add_action('admin_enqueue_scripts', 'pcm_enqueue_scripts');

/**
 * Handle form submissions and redirects before the page renders.
 */
function pcm_handle_form_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'prof-client-manager') {
        return;
    }
    
    global $wpdb;
    $table_clients = $wpdb->prefix . 'pcm_clients';
    $table_reqs = $wpdb->prefix . 'pcm_client_requirements';
    $table_invoices = $wpdb->prefix . 'pcm_invoices';
    $table_proposals = $wpdb->prefix . 'pcm_proposals';
    $table_org = $wpdb->prefix . 'pcm_organisation_details';

    // Handle Client form submission
    if (isset($_POST['pco_submit']) && check_admin_referer('pcm_add_edit_nonce')) {
        $id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        $data = array(
            'name' => sanitize_text_field($_POST['client_name']), 'company' => sanitize_text_field($_POST['client_company']),
            'email' => sanitize_email($_POST['client_email']), 'phone' => sanitize_text_field($_POST['client_phone']),
            'website_url' => esc_url_raw($_POST['website_url']), 'username' => sanitize_text_field($_POST['username']),
            'password' => sanitize_text_field($_POST['password']), 'status' => sanitize_text_field($_POST['client_status']),
            'lead_source' => sanitize_text_field($_POST['lead_source']), 'first_contact_date' => sanitize_text_field($_POST['first_contact_date']),
            'objectives' => sanitize_textarea_field($_POST['client_objectives']), 'vault_entry_name' => sanitize_text_field($_POST['vault_entry_name']),
        );
        if ($id > 0) { $wpdb->update($table_clients, $data, array('id' => $id)); } 
        else { $data['created_at'] = current_time('mysql'); $wpdb->insert($table_clients, $data); }
        wp_redirect(admin_url('admin.php?page=prof-client-manager&updated=true'));
        exit;
    }
    
    // Handle Requirement form submission
    if (isset($_POST['pco_req_submit']) && check_admin_referer('pcm_add_edit_req_nonce')) {
        $id = isset($_POST['req_id']) ? intval($_POST['req_id']) : 0;
        $data = array(
            'client_id' => intval($_POST['req_client_id']), 'requirement_title' => sanitize_text_field($_POST['req_title']),
            'requirement_details' => sanitize_textarea_field($_POST['req_details']), 'priority' => sanitize_text_field($_POST['req_priority']),
            'status' => sanitize_text_field($_POST['req_status']),
        );
        if ($id > 0) { $wpdb->update($table_reqs, $data, array('id' => $id)); }
        else { $data['created_at'] = current_time('mysql'); $wpdb->insert($table_reqs, $data); }
        wp_redirect(admin_url('admin.php?page=prof-client-manager&updated=true'));
        exit;
    }

    // Handle Invoice form submission
    if (isset($_POST['pco_invoice_submit']) && check_admin_referer('pcm_add_edit_invoice_nonce')) {
        $id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
        $line_items = array();
        if (isset($_POST['invoice_item_desc'])) {
            for ($i = 0; $i < count($_POST['invoice_item_desc']); $i++) {
                if (!empty($_POST['invoice_item_desc'][$i])) {
                    $line_items[] = array(
                        'description' => sanitize_text_field($_POST['invoice_item_desc'][$i]),
                        'quantity' => floatval($_POST['invoice_item_qty'][$i]),
                        'price' => floatval($_POST['invoice_item_price'][$i]),
                    );
                }
            }
        }
        $subtotal = floatval($_POST['invoice_subtotal']);
        $tax_rate = floatval($_POST['invoice_tax_rate']);
        $tax_amount = $subtotal * ($tax_rate / 100);
        $total = $subtotal + $tax_amount;

        $data = array(
            'client_id' => intval($_POST['invoice_client_id']),
            'invoice_number' => sanitize_text_field($_POST['invoice_number']),
            'invoice_date' => sanitize_text_field($_POST['invoice_date']),
            'due_date' => sanitize_text_field($_POST['invoice_due_date']),
            'line_items' => json_encode($line_items),
            'subtotal' => $subtotal,
            'tax_rate' => $tax_rate,
            'tax_amount' => $tax_amount,
            'total' => $total,
            'currency' => sanitize_text_field($_POST['invoice_currency']),
            'notes' => sanitize_textarea_field($_POST['invoice_notes']),
            'status' => sanitize_text_field($_POST['invoice_status']),
        );
        if ($id > 0) { $wpdb->update($table_invoices, $data, array('id' => $id)); }
        else { $data['created_at'] = current_time('mysql'); $wpdb->insert($table_invoices, $data); }
        wp_redirect(admin_url('admin.php?page=prof-client-manager&updated=true'));
        exit;
    }
    
    // Handle Proposal form submission
    if (isset($_POST['pco_proposal_submit']) && check_admin_referer('pcm_add_edit_proposal_nonce')) {
        $id = isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0;
        $data = array(
            'client_id' => intval($_POST['proposal_client_id']),
            'title' => sanitize_text_field($_POST['proposal_title']),
            'scope' => sanitize_textarea_field($_POST['proposal_scope']),
            'timeline' => sanitize_textarea_field($_POST['proposal_timeline']),
            'pricing' => sanitize_textarea_field($_POST['proposal_pricing']),
            'status' => sanitize_text_field($_POST['proposal_status']),
        );
        if ($id > 0) { $wpdb->update($table_proposals, $data, array('id' => $id)); }
        else { $data['created_at'] = current_time('mysql'); $wpdb->insert($table_proposals, $data); }
        wp_redirect(admin_url('admin.php?page=prof-client-manager&updated=true'));
        exit;
    }

    // Handle Organisation form submission
    if (isset($_POST['pco_org_submit']) && check_admin_referer('pcm_add_edit_org_nonce')) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $data = array(
            'company_name' => sanitize_text_field($_POST['org_company_name']),
            'address' => sanitize_textarea_field($_POST['org_address']),
            'phone' => sanitize_text_field($_POST['org_phone']),
            'email' => sanitize_email($_POST['org_email']),
            'website' => esc_url_raw($_POST['org_website']),
            'logo_path' => sanitize_text_field($_POST['org_logo_path']),
        );
        
        if (isset($_FILES['org_logo_file']) && !empty($_FILES['org_logo_file']['name'])) {
            $uploadedfile = $_FILES['org_logo_file'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile && !isset($movefile['error'])) {
                $data['logo_path'] = $movefile['file'];
            }
        }

        $existing = $wpdb->get_row("SELECT * FROM $table_org LIMIT 1");
        if ($existing) { $wpdb->update($table_org, $data, array('id' => $existing->id)); }
        else { $wpdb->insert($table_org, $data); }
        wp_redirect(admin_url('admin.php?page=prof-client-manager&updated=true'));
        exit;
    }

    // Handle PDF generation
    if (isset($_GET['action']) && $_GET['action'] == 'download_pdf' && isset($_GET['invoice_id'])) {
        $tcpdf_path = plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php';
        if (!file_exists($tcpdf_path)) {
            wp_redirect(admin_url('admin.php?page=prof-client-manager&tcpdf_missing=true'));
            exit;
        }
        if (check_admin_referer('pcm_download_pdf_nonce_' . intval($_GET['invoice_id']))) {
            $id = intval($_GET['invoice_id']);
            $invoice = $wpdb->get_row("SELECT i.*, c.name as client_name, c.company, c.email FROM $table_invoices i LEFT JOIN $table_clients c ON i.client_id = c.id WHERE i.id = $id");
            $org_details = $wpdb->get_row("SELECT * FROM $table_org LIMIT 1");
            if ($invoice) {
                pcm_generate_invoice_pdf($invoice, $org_details);
            }
        }
    }
    
    // Handle Proposal PDF generation
    if (isset($_GET['action']) && $_GET['action'] == 'download_proposal_pdf' && isset($_GET['proposal_id'])) {
        $tcpdf_path = plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php';
        if (!file_exists($tcpdf_path)) {
            wp_redirect(admin_url('admin.php?page=prof-client-manager&tcpdf_missing=true'));
            exit;
        }
        if (check_admin_referer('pcm_download_proposal_pdf_nonce_' . intval($_GET['proposal_id']))) {
            $id = intval($_GET['proposal_id']);
            $proposal = $wpdb->get_row("SELECT p.*, c.name as client_name, c.company, c.email FROM $table_proposals p LEFT JOIN $table_clients c ON p.client_id = c.id WHERE p.id = $id");
            $org_details = $wpdb->get_row("SELECT * FROM $table_org LIMIT 1");
            if ($proposal) {
                pcm_generate_proposal_pdf($proposal, $org_details);
            }
        }
    }

    // Handle Client deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['client_id'])) {
        if(check_admin_referer('pcm_delete_nonce_'.intval($_GET['client_id']))){
            $id = intval($_GET['client_id']);
            $wpdb->delete($table_clients, array('id' => $id));
            wp_redirect(admin_url('admin.php?page=prof-client-manager&deleted=true'));
            exit;
        }
    }

    // Handle Requirement deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete_req' && isset($_GET['req_id'])) {
        if(check_admin_referer('pcm_delete_req_nonce_'.intval($_GET['req_id']))){
            $id = intval($_GET['req_id']);
            $wpdb->delete($table_reqs, array('id' => $id));
            wp_redirect(admin_url('admin.php?page=prof-client-manager&deleted=true'));
            exit;
        }
    }

    // Handle Invoice deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete_invoice' && isset($_GET['invoice_id'])) {
        if(check_admin_referer('pcm_delete_invoice_nonce_'.intval($_GET['invoice_id']))){
            $id = intval($_GET['invoice_id']);
            $wpdb->delete($table_invoices, array('id' => $id));
            wp_redirect(admin_url('admin.php?page=prof-client-manager&deleted=true'));
            exit;
        }
    }
    
    // Handle Proposal deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete_proposal' && isset($_GET['proposal_id'])) {
        if(check_admin_referer('pcm_delete_proposal_nonce_'.intval($_GET['proposal_id']))){
            $id = intval($_GET['proposal_id']);
            $wpdb->delete($table_proposals, array('id' => $id));
            wp_redirect(admin_url('admin.php?page=prof-client-manager&deleted=true'));
            exit;
        }
    }
}
add_action('admin_init', 'pcm_handle_form_actions');


/**
 * Render the main plugin page.
 */
function pcm_render_main_page() {
    global $wpdb;
    $table_clients = $wpdb->prefix . 'pcm_clients';
    $table_reqs = $wpdb->prefix . 'pcm_client_requirements';
    $table_invoices = $wpdb->prefix . 'pcm_invoices';
    $table_proposals = $wpdb->prefix . 'pcm_proposals';
    $table_org = $wpdb->prefix . 'pcm_organisation_details';

    $client_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['client_id'])) {
        $id = intval($_GET['client_id']);
        $client_to_edit = $wpdb->get_row("SELECT * FROM $table_clients WHERE id = $id");
    }

    $req_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit_req' && isset($_GET['req_id'])) {
        $id = intval($_GET['req_id']);
        $req_to_edit = $wpdb->get_row("SELECT * FROM $table_reqs WHERE id = $id");
    }
    
    $invoice_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit_invoice' && isset($_GET['invoice_id'])) {
        $id = intval($_GET['invoice_id']);
        $invoice_to_edit = $wpdb->get_row("SELECT * FROM $table_invoices WHERE id = $id");
    }
    
    $proposal_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit_proposal' && isset($_GET['proposal_id'])) {
        $id = intval($_GET['proposal_id']);
        $proposal_to_edit = $wpdb->get_row("SELECT * FROM $table_proposals WHERE id = $id");
    }

    $org_details = $wpdb->get_row("SELECT * FROM $table_org LIMIT 1");
    $clients = $wpdb->get_results("SELECT * FROM $table_clients ORDER BY name ASC");
    $requirements = $wpdb->get_results("SELECT r.*, c.name as client_name FROM $table_reqs r LEFT JOIN $table_clients c ON r.client_id = c.id ORDER BY r.created_at DESC");
    $invoices = $wpdb->get_results("SELECT i.*, c.name as client_name, c.email as client_email FROM $table_invoices i LEFT JOIN $table_clients c ON i.client_id = c.id ORDER BY i.invoice_date DESC");
    $proposals = $wpdb->get_results("SELECT p.*, c.name as client_name, c.email as client_email FROM $table_proposals p LEFT JOIN $table_clients c ON p.client_id = c.id ORDER BY p.created_at DESC");
    ?>
    <div class="wrap pcm-app-container">
        <div class="pcm-header">
            <h1 class="pcm-title">Professional Client Manager</h1>
            <p class="pcm-subtitle">Your central hub for client data and relationship management.</p>
        </div>

        <?php
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Item saved successfully.</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Item deleted successfully.</p></div>';
        }
        if (isset($_GET['tcpdf_missing'])) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>TCPDF Library Missing:</strong> PDF generation is disabled. Please download the TCPDF library from <a href="https://tcpdf.org/" target="_blank">tcpdf.org</a> and place it in a folder named `tcpdf` inside the `prof-client-manager` plugin directory.</p></div>';
        }
        ?>

        <div class="pcm-security-warning">
            <div class="pcm-warning-icon">⚠️</div>
            <div>
                <h3 class="pcm-warning-title">Important Security Notice</h3>
                <p class="pcm-warning-text">Storing passwords directly in this database is a significant security risk. For professional, secure credential management, please use the "Credential Vault Entry Name" field to reference an entry in a dedicated password manager (like Bitwarden, Keeper, or 1Password). This is the industry-standard best practice.</p>
            </div>
        </div>

        <div class="pcm-tabs">
            <button class="pcm-tab-link active" data-tab="dashboard">Dashboard</button>
            <button class="pcm-tab-link" data-tab="all-clients">All Clients</button>
            <button class="pcm-tab-link" data-tab="add-client">Add New Client</button>
            <button class="pcm-tab-link" data-tab="add-requirement">Add Requirement</button>
            <button class="pcm-tab-link" data-tab="requirements-list">Requirements List</button>
            <button class="pcm-tab-link" data-tab="create-proposal">Create Proposal</button>
            <button class="pcm-tab-link" data-tab="proposals-list">Proposals List</button>
            <button class="pcm-tab-link" data-tab="generate-invoice">Generate Invoice</button>
            <button class="pcm-tab-link" data-tab="invoices-list">Invoices List</button>
            <button class="pcm-tab-link" data-tab="organisation">Organisation</button>
        </div>

        <div id="dashboard" class="pcm-tab-content active">
            <div class="pcm-dashboard-grid">
                <div class="pcm-card">
                    <h2 class="pcm-card-title">Client Status Overview</h2>
                    <div class="pcm-chart-container">
                        <canvas id="clientStatusChart"></canvas>
                    </div>
                </div>
                <div class="pcm-card">
                    <h2 class="pcm-card-title">Requirements Status</h2>
                    <div class="pcm-chart-container">
                        <canvas id="requirementsStatusChart"></canvas>
                    </div>
                </div>
                <div class="pcm-card">
                    <h2 class="pcm-card-title">Invoice Status</h2>
                    <div class="pcm-chart-container">
                        <canvas id="invoiceStatusChart"></canvas>
                    </div>
                </div>
                <div class="pcm-card">
                    <h2 class="pcm-card-title">Proposal Status</h2>
                    <div class="pcm-chart-container">
                        <canvas id="proposalStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div id="all-clients" class="pcm-tab-content">
             <div class="pcm-card">
                <h2 class="pcm-card-title">Client List</h2>
                <div class="pcm-table-wrapper">
                    <table class="pcm-table">
                        <thead>
                            <tr>
                                <th>Name</th><th>Email</th><th>Phone</th><th>Website</th><th>Credentials</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clients) : foreach ($clients as $client) : ?>
                                <tr>
                                    <td>
                                        <div class="pcm-font-bold"><?php echo isset($client->name) ? esc_html($client->name) : ''; ?></div>
                                        <div class="pcm-text-sm pcm-text-gray"><?php echo isset($client->company) ? esc_html($client->company) : ''; ?></div>
                                    </td>
                                    <td><a href="mailto:<?php echo isset($client->email) ? esc_attr($client->email) : ''; ?>" class="pcm-link"><?php echo isset($client->email) ? esc_html($client->email) : ''; ?></a></td>
                                    <td><a href="tel:<?php echo isset($client->phone) ? esc_attr($client->phone) : ''; ?>" class="pcm-link"><?php echo isset($client->phone) ? esc_html($client->phone) : ''; ?></a></td>
                                    <td><a href="<?php echo isset($client->website_url) ? esc_url($client->website_url) : '#'; ?>" target="_blank" class="pcm-link"><?php echo isset($client->website_url) ? esc_html($client->website_url) : ''; ?></a></td>
                                    <td>
                                        <div class="pcm-credentials">
                                            <button class="pcm-copy-btn" data-clipboard="<?php echo isset($client->username) ? esc_attr($client->username) : ''; ?>">Copy User</button>
                                            <button class="pcm-copy-btn" data-clipboard="<?php echo isset($client->password) ? esc_attr($client->password) : ''; ?>">Copy Pass</button>
                                        </div>
                                    </td>
                                    <td><span class="pcm-status-badge status-<?php echo isset($client->status) ? esc_attr(strtolower(str_replace(' ','-',$client->status))) : ''; ?>"><?php echo isset($client->status) ? esc_html($client->status) : ''; ?></span></td>
                                    <td>
                                        <div class="pcm-actions">
                                            <a href="?page=prof-client-manager&action=edit&client_id=<?php echo $client->id; ?>" class="pcm-edit-btn">Edit</a>
                                            <a href="<?php echo wp_nonce_url('?page=prof-client-manager&action=delete&client_id='.$client->id, 'pcm_delete_nonce_'.$client->id); ?>" class="pcm-delete-btn" onclick="return confirm('Are you sure you want to delete this client?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="7">No clients found. Add one to get started!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="add-client" class="pcm-tab-content">
            <div class="pcm-card">
                 <h2 class="pcm-card-title"><?php echo $client_to_edit ? 'Edit Client' : 'Add New Client'; ?></h2>
                 <form method="POST" action="?page=prof-client-manager" class="pcm-form">
                    <input type="hidden" name="client_id" value="<?php echo $client_to_edit ? esc_attr($client_to_edit->id) : '0'; ?>">
                    <?php wp_nonce_field('pcm_add_edit_nonce'); ?>
                    <div class="pcm-form-grid">
                        <div class="pcm-form-column">
                             <div class="pcm-form-group"><label for="client_name">Client Name</label><input type="text" id="client_name" name="client_name" value="<?php echo $client_to_edit && isset($client_to_edit->name) ? esc_attr($client_to_edit->name) : ''; ?>" required /></div>
                             <div class="pcm-form-group"><label for="client_company">Company</label><input type="text" id="client_company" name="client_company" value="<?php echo $client_to_edit && isset($client_to_edit->company) ? esc_attr($client_to_edit->company) : ''; ?>" /></div>
                             <div class="pcm-form-group"><label for="client_email">Email</label><input type="email" id="client_email" name="client_email" value="<?php echo $client_to_edit && isset($client_to_edit->email) ? esc_attr($client_to_edit->email) : ''; ?>" required /></div>
                             <div class="pcm-form-group"><label for="client_phone">Phone</label><input type="text" id="client_phone" name="client_phone" value="<?php echo $client_to_edit && isset($client_to_edit->phone) ? esc_attr($client_to_edit->phone) : ''; ?>" /></div>
                             <div class="pcm-form-group"><label for="client_status">Status</label><select id="client_status" name="client_status" required><option value="Prospect" <?php selected($client_to_edit && isset($client_to_edit->status) ? $client_to_edit->status : '', 'Prospect'); ?>>Prospect</option><option value="Active" <?php selected($client_to_edit && isset($client_to_edit->status) ? $client_to_edit->status : '', 'Active'); ?>>Active</option><option value="At Risk" <?php selected($client_to_edit && isset($client_to_edit->status) ? $client_to_edit->status : '', 'At Risk'); ?>>At Risk</option><option value="Former" <?php selected($client_to_edit && isset($client_to_edit->status) ? $client_to_edit->status : '', 'Former'); ?>>Former</option></select></div>
                        </div>
                        <div class="pcm-form-column">
                             <div class="pcm-form-group"><label for="website_url">Website URL</label><input type="url" id="website_url" name="website_url" value="<?php echo $client_to_edit && isset($client_to_edit->website_url) ? esc_attr($client_to_edit->website_url) : ''; ?>" /></div>
                             <div class="pcm-form-group"><label for="username">Username</label><input type="text" id="username" name="username" value="<?php echo $client_to_edit && isset($client_to_edit->username) ? esc_attr($client_to_edit->username) : ''; ?>" /></div>
                             <div class="pcm-form-group"><label for="password">Password</label><input type="password" id="password" name="password" value="<?php echo $client_to_edit && isset($client_to_edit->password) ? esc_attr($client_to_edit->password) : ''; ?>" /></div>
                             <div class="pcm-form-group"><label for="vault_entry_name">Credential Vault Entry Name</label><input type="text" id="vault_entry_name" name="vault_entry_name" value="<?php echo $client_to_edit && isset($client_to_edit->vault_entry_name) ? esc_attr($client_to_edit->vault_entry_name) : ''; ?>" placeholder="Recommended: e.g., 'Client Name - WP Admin'" /></div>
                             <div class="pcm-form-group"><label for="lead_source">Lead Source</label><input type="text" id="lead_source" name="lead_source" value="<?php echo $client_to_edit && isset($client_to_edit->lead_source) ? esc_attr($client_to_edit->lead_source) : ''; ?>" /></div>
                        </div>
                        <div class="pcm-form-group pcm-form-full-width"><label for="first_contact_date">First Contact Date</label><input type="date" id="first_contact_date" name="first_contact_date" value="<?php echo $client_to_edit && isset($client_to_edit->first_contact_date) ? esc_attr($client_to_edit->first_contact_date) : ''; ?>" required /></div>
                        <div class="pcm-form-group pcm-form-full-width"><label for="client_objectives">Key Business Objectives</label><textarea id="client_objectives" name="client_objectives" rows="4"><?php echo $client_to_edit && isset($client_to_edit->objectives) ? esc_textarea($client_to_edit->objectives) : ''; ?></textarea></div>
                    </div>
                    <div class="pcm-form-actions"><button type="submit" name="pco_submit" class="pcm-submit-btn"><?php echo $client_to_edit ? 'Update Client' : 'Add Client'; ?></button><?php if ($client_to_edit) : ?><a href="?page=prof-client-manager" class="pcm-cancel-btn">Cancel Edit</a><?php endif; ?></div>
                </form>
            </div>
        </div>
        
        <div id="add-requirement" class="pcm-tab-content">
            <div class="pcm-card">
                 <h2 class="pcm-card-title"><?php echo $req_to_edit ? 'Edit Requirement' : 'Add New Requirement'; ?></h2>
                 <form method="POST" action="?page=prof-client-manager" class="pcm-form">
                    <input type="hidden" name="req_id" value="<?php echo $req_to_edit ? esc_attr($req_to_edit->id) : '0'; ?>">
                    <?php wp_nonce_field('pcm_add_edit_req_nonce'); ?>
                    <div class="pcm-form-grid">
                        <div class="pcm-form-column">
                            <div class="pcm-form-group"><label for="req_client_id">Client</label><select id="req_client_id" name="req_client_id" required><option value="">Select a Client</option><?php foreach($clients as $client) { echo '<option value="'.esc_attr($client->id).'" '.selected($req_to_edit && isset($req_to_edit->client_id) ? $req_to_edit->client_id : '', $client->id, false).'>'.esc_html($client->name).'</option>'; } ?></select></div>
                            <div class="pcm-form-group"><label for="req_title">Requirement Title</label><input type="text" id="req_title" name="req_title" value="<?php echo $req_to_edit && isset($req_to_edit->requirement_title) ? esc_attr($req_to_edit->requirement_title) : ''; ?>" required /></div>
                        </div>
                        <div class="pcm-form-column">
                            <div class="pcm-form-group"><label for="req_priority">Priority</label><select id="req_priority" name="req_priority"><option value="Low" <?php selected($req_to_edit && isset($req_to_edit->priority) ? $req_to_edit->priority : '', 'Low'); ?>>Low</option><option value="Medium" <?php selected($req_to_edit && isset($req_to_edit->priority) ? $req_to_edit->priority : 'Medium', 'Medium'); ?>>Medium</option><option value="High" <?php selected($req_to_edit && isset($req_to_edit->priority) ? $req_to_edit->priority : '', 'High'); ?>>High</option></select></div>
                            <div class="pcm-form-group"><label for="req_status">Status</label><select id="req_status" name="req_status"><option value="Pending" <?php selected($req_to_edit && isset($req_to_edit->status) ? $req_to_edit->status : 'Pending', 'Pending'); ?>>Pending</option><option value="In Progress" <?php selected($req_to_edit && isset($req_to_edit->status) ? $req_to_edit->status : '', 'In Progress'); ?>>In Progress</option><option value="Completed" <?php selected($req_to_edit && isset($req_to_edit->status) ? $req_to_edit->status : '', 'Completed'); ?>>Completed</option></select></div>
                        </div>
                        <div class="pcm-form-group pcm-form-full-width"><label for="req_details">Requirement Details</label><textarea id="req_details" name="req_details" rows="5"><?php echo $req_to_edit && isset($req_to_edit->requirement_details) ? esc_textarea($req_to_edit->requirement_details) : ''; ?></textarea></div>
                    </div>
                    <div class="pcm-form-actions"><button type="submit" name="pco_req_submit" class="pcm-submit-btn"><?php echo $req_to_edit ? 'Update Requirement' : 'Add Requirement'; ?></button><?php if ($req_to_edit) : ?><a href="?page=prof-client-manager" class="pcm-cancel-btn">Cancel Edit</a><?php endif; ?></div>
                 </form>
            </div>
        </div>

        <div id="requirements-list" class="pcm-tab-content">
            <div class="pcm-card">
                <h2 class="pcm-card-title">All Requirements</h2>
                <div class="pcm-table-wrapper">
                    <table class="pcm-table">
                        <thead><tr><th>Requirement</th><th>Client</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if ($requirements) : foreach ($requirements as $req) : ?>
                            <tr>
                                <td><?php echo isset($req->requirement_title) ? esc_html($req->requirement_title) : ''; ?></td>
                                <td><?php echo isset($req->client_name) ? esc_html($req->client_name) : 'N/A'; ?></td>
                                <td><span class="pcm-priority-badge priority-<?php echo isset($req->priority) ? esc_attr(strtolower($req->priority)) : ''; ?>"><?php echo isset($req->priority) ? esc_html($req->priority) : ''; ?></span></td>
                                <td><span class="pcm-status-badge status-req-<?php echo isset($req->status) ? esc_attr(strtolower(str_replace(' ','-',$req->status))) : ''; ?>"><?php echo isset($req->status) ? esc_html($req->status) : ''; ?></span></td>
                                <td>
                                    <div class="pcm-actions">
                                        <a href="?page=prof-client-manager&action=edit_req&req_id=<?php echo $req->id; ?>" class="pcm-edit-btn">Edit</a>
                                        <a href="<?php echo wp_nonce_url('?page=prof-client-manager&action=delete_req&req_id='.$req->id, 'pcm_delete_req_nonce_'.$req->id); ?>" class="pcm-delete-btn" onclick="return confirm('Are you sure?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="5">No requirements found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div id="create-proposal" class="pcm-tab-content">
            <div class="pcm-card">
                <h2 class="pcm-card-title"><?php echo $proposal_to_edit ? 'Edit Proposal' : 'Create New Proposal'; ?></h2>
                <form method="POST" action="?page=prof-client-manager" class="pcm-form">
                    <input type="hidden" name="proposal_id" value="<?php echo $proposal_to_edit ? esc_attr($proposal_to_edit->id) : '0'; ?>">
                    <?php wp_nonce_field('pcm_add_edit_proposal_nonce'); ?>
                    <div class="pcm-form-grid">
                        <div class="pcm-form-column">
                            <div class="pcm-form-group"><label for="proposal_client_id">Client</label><select id="proposal_client_id" name="proposal_client_id" required><option value="">Select a Client</option><?php foreach($clients as $client) { echo '<option value="'.esc_attr($client->id).'" '.selected($proposal_to_edit && isset($proposal_to_edit->client_id) ? $proposal_to_edit->client_id : '', $client->id, false).'>'.esc_html($client->name).'</option>'; } ?></select></div>
                        </div>
                        <div class="pcm-form-column">
                            <div class="pcm-form-group"><label for="proposal_title">Proposal Title</label><input type="text" id="proposal_title" name="proposal_title" value="<?php echo $proposal_to_edit && isset($proposal_to_edit->title) ? esc_attr($proposal_to_edit->title) : ''; ?>" required /></div>
                        </div>
                    </div>
                    <div class="pcm-form-group"><label for="proposal_scope">Scope of Work</label><textarea id="proposal_scope" name="proposal_scope" rows="5"><?php echo $proposal_to_edit && isset($proposal_to_edit->scope) ? esc_textarea($proposal_to_edit->scope) : ''; ?></textarea></div>
                    <div class="pcm-form-group"><label for="proposal_timeline">Timeline</label><textarea id="proposal_timeline" name="proposal_timeline" rows="3"><?php echo $proposal_to_edit && isset($proposal_to_edit->timeline) ? esc_textarea($proposal_to_edit->timeline) : ''; ?></textarea></div>
                    <div class="pcm-form-group"><label for="proposal_pricing">Pricing</label><textarea id="proposal_pricing" name="proposal_pricing" rows="3"><?php echo $proposal_to_edit && isset($proposal_to_edit->pricing) ? esc_textarea($proposal_to_edit->pricing) : ''; ?></textarea></div>
                    <div class="pcm-form-group"><label for="proposal_status">Status</label><select id="proposal_status" name="proposal_status"><option value="Draft" <?php selected($proposal_to_edit && isset($proposal_to_edit->status) ? $proposal_to_edit->status : 'Draft', 'Draft'); ?>>Draft</option><option value="Sent" <?php selected($proposal_to_edit && isset($proposal_to_edit->status) ? $proposal_to_edit->status : '', 'Sent'); ?>>Sent</option><option value="Accepted" <?php selected($proposal_to_edit && isset($proposal_to_edit->status) ? $proposal_to_edit->status : '', 'Accepted'); ?>>Accepted</option><option value="Declined" <?php selected($proposal_to_edit && isset($proposal_to_edit->status) ? $proposal_to_edit->status : '', 'Declined'); ?>>Declined</option></select></div>
                    <div class="pcm-form-actions"><button type="submit" name="pco_proposal_submit" class="pcm-submit-btn"><?php echo $proposal_to_edit ? 'Update Proposal' : 'Save Proposal'; ?></button><?php if ($proposal_to_edit) : ?><a href="?page=prof-client-manager" class="pcm-cancel-btn">Cancel Edit</a><?php endif; ?></div>
                </form>
            </div>
        </div>

        <div id="proposals-list" class="pcm-tab-content">
            <div class="pcm-card">
                <h2 class="pcm-card-title">All Proposals</h2>
                <div class="pcm-table-wrapper">
                    <table class="pcm-table">
                        <thead><tr><th>Title</th><th>Client</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if ($proposals) : foreach ($proposals as $proposal) : ?>
                            <tr>
                                <td><?php echo isset($proposal->title) ? esc_html($proposal->title) : ''; ?></td>
                                <td><?php echo isset($proposal->client_name) ? esc_html($proposal->client_name) : 'N/A'; ?></td>
                                <td><span class="pcm-status-badge status-proposal-<?php echo isset($proposal->status) ? esc_attr(strtolower($proposal->status)) : ''; ?>"><?php echo isset($proposal->status) ? esc_html($proposal->status) : ''; ?></span></td>
                                <td>
                                    <div class="pcm-actions">
                                        <a href="?page=prof-client-manager&action=edit_proposal&proposal_id=<?php echo $proposal->id; ?>" class="pcm-edit-btn">Edit</a>
                                        <a href="<?php echo wp_nonce_url('?page=prof-client-manager&action=download_proposal_pdf&proposal_id='.$proposal->id, 'pcm_download_proposal_pdf_nonce_'.$proposal->id); ?>" class="pcm-edit-btn" target="_blank">PDF</a>
                                        <a href="mailto:<?php echo isset($proposal->client_email) ? esc_attr($proposal->client_email) : ''; ?>?subject=Proposal:%20<?php echo isset($proposal->title) ? esc_attr($proposal->title) : ''; ?>&body=Dear%20<?php echo isset($proposal->client_name) ? esc_attr($proposal->client_name) : ''; ?>,%0D%0A%0D%0APlease%20find%20our%20proposal%20attached.%0D%0A%0D%0AThank%20you,%0D%0A[Your Name]" class="pcm-edit-btn">Send</a>
                                        <a href="<?php echo wp_nonce_url('?page=prof-client-manager&action=delete_proposal&proposal_id='.$proposal->id, 'pcm_delete_proposal_nonce_'.$proposal->id); ?>" class="pcm-delete-btn" onclick="return confirm('Are you sure?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="4">No proposals found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div id="generate-invoice" class="pcm-tab-content">
            <div class="pcm-card">
                 <h2 class="pcm-card-title"><?php echo $invoice_to_edit ? 'Edit Invoice' : 'Generate New Invoice'; ?></h2>
                 <form method="POST" action="?page=prof-client-manager" class="pcm-form" id="pcm-invoice-form">
                    <input type="hidden" name="invoice_id" value="<?php echo $invoice_to_edit ? esc_attr($invoice_to_edit->id) : '0'; ?>">
                    <?php wp_nonce_field('pcm_add_edit_invoice_nonce'); ?>
                    <div class="pcm-form-grid">
                        <div class="pcm-form-column">
                            <div class="pcm-form-group"><label for="invoice_client_id">Client</label><select id="invoice_client_id" name="invoice_client_id" required><option value="">Select a Client</option><?php foreach($clients as $client) { echo '<option value="'.esc_attr($client->id).'" '.selected($invoice_to_edit && isset($invoice_to_edit->client_id) ? $invoice_to_edit->client_id : '', $client->id, false).'>'.esc_html($client->name).'</option>'; } ?></select></div>
                            <div class="pcm-form-group"><label for="invoice_number">Invoice Number</label><input type="text" id="invoice_number" name="invoice_number" value="<?php echo $invoice_to_edit && isset($invoice_to_edit->invoice_number) ? esc_attr($invoice_to_edit->invoice_number) : 'INV-'.(count($invoices) + 1); ?>" required /></div>
                        </div>
                        <div class="pcm-form-column">
                            <div class="pcm-form-group"><label for="invoice_date">Invoice Date</label><input type="date" id="invoice_date" name="invoice_date" value="<?php echo $invoice_to_edit && isset($invoice_to_edit->invoice_date) ? esc_attr($invoice_to_edit->invoice_date) : date('Y-m-d'); ?>" required /></div>
                            <div class="pcm-form-group"><label for="invoice_due_date">Due Date</label><input type="date" id="invoice_due_date" name="invoice_due_date" value="<?php echo $invoice_to_edit && isset($invoice_to_edit->due_date) ? esc_attr($invoice_to_edit->due_date) : ''; ?>" required /></div>
                        </div>
                    </div>
                    <hr class="pcm-form-divider" />
                    <h3 class="pcm-card-subtitle">Line Items</h3>
                    <div id="invoice-items-wrapper">
                        <?php 
                        $line_items = $invoice_to_edit && isset($invoice_to_edit->line_items) ? json_decode($invoice_to_edit->line_items, true) : [['description' => '', 'quantity' => 1, 'price' => '']];
                        foreach ($line_items as $item) :
                        ?>
                        <div class="pcm-invoice-item">
                            <input type="text" name="invoice_item_desc[]" placeholder="Item Description" value="<?php echo esc_attr($item['description']); ?>" required />
                            <input type="number" name="invoice_item_qty[]" placeholder="Qty" class="pcm-input-sm" value="<?php echo esc_attr($item['quantity']); ?>" step="any" required />
                            <input type="number" name="invoice_item_price[]" placeholder="Unit Price" class="pcm-input-sm" value="<?php echo esc_attr($item['price']); ?>" step="any" required />
                            <button type="button" class="pcm-remove-item-btn">Remove</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="pcm-add-item-btn" class="pcm-edit-btn">Add Item</button>

                    <hr class="pcm-form-divider" />
                    <div class="pcm-invoice-totals">
                        <div>
                            <div class="pcm-form-group"><label for="invoice_notes">Notes</label><textarea id="invoice_notes" name="invoice_notes" rows="3"><?php echo $invoice_to_edit && isset($invoice_to_edit->notes) ? esc_textarea($invoice_to_edit->notes) : ''; ?></textarea></div>
                            <div class="pcm-form-group"><label for="invoice_status">Status</label><select id="invoice_status" name="invoice_status"><option value="Draft" <?php selected($invoice_to_edit && isset($invoice_to_edit->status) ? $invoice_to_edit->status : 'Draft', 'Draft'); ?>>Draft</option><option value="Sent" <?php selected($invoice_to_edit && isset($invoice_to_edit->status) ? $invoice_to_edit->status : '', 'Sent'); ?>>Sent</option><option value="Paid" <?php selected($invoice_to_edit && isset($invoice_to_edit->status) ? $invoice_to_edit->status : '', 'Paid'); ?>>Paid</option><option value="Overdue" <?php selected($invoice_to_edit && isset($invoice_to_edit->status) ? $invoice_to_edit->status : '', 'Overdue'); ?>>Overdue</option></select></div>
                        </div>
                        <div class="pcm-totals-box">
                            <div><span>Subtotal:</span> <span id="invoice-subtotal-display">$0.00</span></div>
                            <div class="pcm-form-group"><label for="invoice_currency">Currency</label><select id="invoice_currency" name="invoice_currency"><option value="$" <?php selected($invoice_to_edit && isset($invoice_to_edit->currency) ? $invoice_to_edit->currency : '$', '$'); ?>>USD ($)</option><option value="€" <?php selected($invoice_to_edit && isset($invoice_to_edit->currency) ? $invoice_to_edit->currency : '', '€'); ?>>EUR (€)</option><option value="£" <?php selected($invoice_to_edit && isset($invoice_to_edit->currency) ? $invoice_to_edit->currency : '', '£'); ?>>GBP (£)</option><option value="₹" <?php selected($invoice_to_edit && isset($invoice_to_edit->currency) ? $invoice_to_edit->currency : '', '₹'); ?>>INR (₹)</option></select></div>
                            <div><span>Tax (%):</span> <input type="number" id="invoice_tax_rate" name="invoice_tax_rate" value="<?php echo $invoice_to_edit && isset($invoice_to_edit->tax_rate) ? esc_attr($invoice_to_edit->tax_rate) : '0'; ?>" step="any" /></div>
                            <div><span>Tax Amount:</span> <span id="invoice-tax-amount-display">$0.00</span></div>
                            <div class="pcm-total-final"><span>Total:</span> <span id="invoice-total-display">$0.00</span></div>
                            <input type="hidden" name="invoice_subtotal" id="invoice_subtotal_hidden" />
                        </div>
                    </div>
                    <div class="pcm-form-actions"><button type="submit" name="pco_invoice_submit" class="pcm-submit-btn"><?php echo $invoice_to_edit ? 'Update Invoice' : 'Save Invoice'; ?></button><?php if ($invoice_to_edit) : ?><a href="?page=prof-client-manager" class="pcm-cancel-btn">Cancel Edit</a><?php endif; ?></div>
                 </form>
            </div>
        </div>

        <div id="invoices-list" class="pcm-tab-content">
            <div class="pcm-card">
                <h2 class="pcm-card-title">All Invoices</h2>
                <div class="pcm-table-wrapper">
                    <table class="pcm-table">
                        <thead><tr><th>Invoice #</th><th>Client</th><th>Date</th><th>Due Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if ($invoices) : foreach ($invoices as $invoice) : ?>
                            <tr>
                                <td><?php echo isset($invoice->invoice_number) ? esc_html($invoice->invoice_number) : ''; ?></td>
                                <td><?php echo isset($invoice->client_name) ? esc_html($invoice->client_name) : 'N/A'; ?></td>
                                <td><?php echo isset($invoice->invoice_date) ? date_format(date_create($invoice->invoice_date), 'M j, Y') : ''; ?></td>
                                <td><?php echo isset($invoice->due_date) ? date_format(date_create($invoice->due_date), 'M j, Y') : ''; ?></td>
                                <td><?php echo (isset($invoice->currency) ? esc_html($invoice->currency) : '$') . (isset($invoice->total) ? number_format($invoice->total, 2) : '0.00'); ?></td>
                                <td><span class="pcm-status-badge status-invoice-<?php echo isset($invoice->status) ? esc_attr(strtolower($invoice->status)) : ''; ?>"><?php echo isset($invoice->status) ? esc_html($invoice->status) : ''; ?></span></td>
                                <td>
                                    <div class="pcm-actions">
                                        <a href="?page=prof-client-manager&action=edit_invoice&invoice_id=<?php echo $invoice->id; ?>" class="pcm-edit-btn">Edit</a>
                                        <a href="<?php echo wp_nonce_url('?page=prof-client-manager&action=download_pdf&invoice_id='.$invoice->id, 'pcm_download_pdf_nonce_'.$invoice->id); ?>" class="pcm-edit-btn" target="_blank">PDF</a>
                                        <a href="mailto:<?php echo isset($invoice->client_email) ? esc_attr($invoice->client_email) : ''; ?>?subject=Invoice%20<?php echo isset($invoice->invoice_number) ? esc_attr($invoice->invoice_number) : ''; ?>&body=Dear%20<?php echo isset($invoice->client_name) ? esc_attr($invoice->client_name) : ''; ?>,%0D%0A%0D%0APlease%20find%20your%20invoice%20attached.%0D%0A%0D%0AThank%20you,%0D%0A[Your Name]" class="pcm-edit-btn">Send</a>
                                        <a href="<?php echo wp_nonce_url('?page=prof-client-manager&action=delete_invoice&invoice_id='.$invoice->id, 'pcm_delete_invoice_nonce_'.$invoice->id); ?>" class="pcm-delete-btn" onclick="return confirm('Are you sure?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="7">No invoices found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="organisation" class="pcm-tab-content">
            <div class="pcm-card">
                <h2 class="pcm-card-title">Organisation Details</h2>
                <form method="POST" action="?page=prof-client-manager" class="pcm-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('pcm_add_edit_org_nonce'); ?>
                    <div class="pcm-form-group">
                        <label for="org_company_name">Company Name</label>
                        <input type="text" id="org_company_name" name="org_company_name" value="<?php echo $org_details && isset($org_details->company_name) ? esc_attr($org_details->company_name) : ''; ?>" required />
                    </div>
                    <div class="pcm-form-group">
                        <label for="org_logo_file">Logo</label>
                        <input type="file" id="org_logo_file" name="org_logo_file" />
                        <input type="hidden" name="org_logo_path" value="<?php echo $org_details && isset($org_details->logo_path) ? esc_attr($org_details->logo_path) : ''; ?>" />
                        <?php if ($org_details && !empty($org_details->logo_path)) : 
                            $upload_dir = wp_upload_dir();
                            $logo_url = str_replace(ABSPATH, get_site_url().'/', $org_details->logo_path);
                        ?>
                        <p>Current logo:</p>
                        <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px; margin-top: 10px;" />
                        <?php endif; ?>
                    </div>
                    <div class="pcm-form-group">
                        <label for="org_address">Address</label>
                        <textarea id="org_address" name="org_address" rows="4"><?php echo $org_details && isset($org_details->address) ? esc_textarea($org_details->address) : ''; ?></textarea>
                    </div>
                    <div class="pcm-form-group">
                        <label for="org_phone">Phone</label>
                        <input type="text" id="org_phone" name="org_phone" value="<?php echo $org_details && isset($org_details->phone) ? esc_attr($org_details->phone) : ''; ?>" />
                    </div>
                    <div class="pcm-form-group">
                        <label for="org_email">Email</label>
                        <input type="email" id="org_email" name="org_email" value="<?php echo $org_details && isset($org_details->email) ? esc_attr($org_details->email) : ''; ?>" />
                    </div>
                    <div class="pcm-form-group">
                        <label for="org_website">Website</label>
                        <input type="url" id="org_website" name="org_website" value="<?php echo $org_details && isset($org_details->website) ? esc_attr($org_details->website) : ''; ?>" />
                    </div>
                    <div class="pcm-form-actions">
                        <button type="submit" name="pco_org_submit" class="pcm-submit-btn">Save Details</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <?php
}

/**
 * Add inline CSS and JS to the admin head.
 */
function pcm_admin_inline_assets() {
    $screen = get_current_screen();
    if ($screen->id !== 'toplevel_page_prof-client-manager') return;
    ?>
    <style type="text/css">
        :root { --pcm-bg: #f8fafc; --pcm-card-bg: #ffffff; --pcm-border: #e2e8f0; --pcm-text: #334155; --pcm-text-light: #64748b; --pcm-primary: #2563eb; --pcm-primary-hover: #1d4ed8; --pcm-danger: #dc2626; --pcm-danger-hover: #b91c1c; --pcm-warning-bg: #fefce8; --pcm-warning-border: #facc15; --pcm-warning-text: #854d0e; }
        .pcm-app-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"; color: var(--pcm-text); }
        .pcm-header { margin-bottom: 2rem; }
        .pcm-title { font-size: 2.25rem; font-weight: 800; color: #1e293b; margin: 0; }
        .pcm-subtitle { font-size: 1.125rem; color: var(--pcm-text-light); margin-top: 0.25rem; }
        .pcm-security-warning { display: flex; align-items: flex-start; background-color: var(--pcm-warning-bg); border: 1px solid var(--pcm-warning-border); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem; }
        .pcm-warning-icon { font-size: 1.5rem; margin-right: 1rem; }
        .pcm-warning-title { font-weight: 600; color: var(--pcm-warning-text); margin: 0 0 0.25rem 0; }
        .pcm-warning-text { color: var(--pcm-warning-text); margin: 0; font-size: 0.875rem; }
        .pcm-tabs { border-bottom: 1px solid var(--pcm-border); margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; }
        .pcm-tab-link { background: none; border: none; padding: 0.75rem 1.5rem; cursor: pointer; font-size: 1rem; color: var(--pcm-text-light); border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .pcm-tab-link.active { color: var(--pcm-primary); border-bottom-color: var(--pcm-primary); font-weight: 600; }
        .pcm-tab-content { display: none; }
        .pcm-tab-content.active { display: block; }
        .pcm-card { background-color: var(--pcm-card-bg); border: 1px solid var(--pcm-border); border-radius: 0.75rem; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.05); padding: 1.5rem; }
        .pcm-card-title { font-size: 1.25rem; font-weight: 600; margin: 0 0 1rem 0; color: #1e293b; }
        .pcm-card-subtitle { font-size: 1.1rem; font-weight: 600; margin: 1rem 0 0.5rem 0; }
        .pcm-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .pcm-chart-container { position: relative; height: 350px; max-width: 450px; margin: 1rem auto; }
        .pcm-table-wrapper { overflow-x: auto; }
        .pcm-table { width: 100%; border-collapse: collapse; }
        .pcm-table th, .pcm-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--pcm-border); }
        .pcm-table th { font-size: 0.75rem; text-transform: uppercase; color: var(--pcm-text-light); font-weight: 600; }
        .pcm-font-bold { font-weight: 600; }
        .pcm-text-sm { font-size: 0.875rem; }
        .pcm-text-gray { color: var(--pcm-text-light); }
        .pcm-link { color: var(--pcm-primary); text-decoration: none; }
        .pcm-link:hover { text-decoration: underline; }
        .pcm-credentials, .pcm-actions { display: flex; gap: 0.5rem; }
        .pcm-copy-btn, .pcm-edit-btn, .pcm-delete-btn, .pcm-submit-btn, .pcm-cancel-btn { border: 1px solid var(--pcm-border); background-color: var(--pcm-card-bg); color: var(--pcm-text); font-size: 0.875rem; padding: 0.375rem 0.75rem; border-radius: 0.375rem; cursor: pointer; text-decoration: none; display: inline-block; line-height: 1.5; white-space: nowrap; }
        .pcm-copy-btn:hover, .pcm-edit-btn:hover { background-color: #f1f5f9; }
        .pcm-delete-btn { border-color: var(--pcm-danger); color: var(--pcm-danger); }
        .pcm-delete-btn:hover { background-color: var(--pcm-danger); color: white; }
        .pcm-status-badge, .pcm-priority-badge { padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; display: inline-block; }
        .status-prospect { background-color: #eef2ff; color: #4338ca; }
        .status-active { background-color: #dcfce7; color: #166534; }
        .status-at-risk { background-color: #fee2e2; color: #991b1b; }
        .status-former { background-color: #f1f5f9; color: #475569; }
        .status-req-pending { background-color: #fef9c3; color: #854d0e; }
        .status-req-in-progress { background-color: #dbeafe; color: #1e40af; }
        .status-req-completed { background-color: #dcfce7; color: #166534; }
        .status-invoice-draft { background-color: #f1f5f9; color: #475569; }
        .status-invoice-sent { background-color: #dbeafe; color: #1e40af; }
        .status-invoice-paid { background-color: #dcfce7; color: #166534; }
        .status-invoice-overdue { background-color: #fee2e2; color: #991b1b; }
        .status-proposal-draft { background-color: #f1f5f9; color: #475569; }
        .status-proposal-sent { background-color: #dbeafe; color: #1e40af; }
        .status-proposal-accepted { background-color: #dcfce7; color: #166534; }
        .status-proposal-declined { background-color: #fee2e2; color: #991b1b; }
        .priority-low { background-color: #f0f9ff; color: #0369a1; }
        .priority-medium { background-color: #fffbeb; color: #b45309; }
        .priority-high { background-color: #fef2f2; color: #b91c1c; }
        .pcm-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .pcm-form-column { display: flex; flex-direction: column; gap: 1rem; }
        .pcm-form-full-width { grid-column: 1 / -1; }
        .pcm-form-group label { font-weight: 500; margin-bottom: 0.5rem; display: block; }
        .pcm-form-group input[type="text"], .pcm-form-group input[type="email"], .pcm-form-group input[type="password"], .pcm-form-group input[type="url"], .pcm-form-group input[type="date"], .pcm-form-group select, .pcm-form-group textarea { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--pcm-border); border-radius: 0.375rem; }
        .pcm-form-actions { margin-top: 1.5rem; display: flex; gap: 0.75rem; }
        .pcm-submit-btn { background-color: var(--pcm-primary); color: white; border-color: var(--pcm-primary); }
        .pcm-submit-btn:hover { background-color: var(--pcm-primary-hover); }
        .pcm-form-divider { border: none; border-top: 1px solid var(--pcm-border); margin: 2rem 0; }
        .pcm-invoice-item { display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; }
        .pcm-invoice-item input[type="text"] { flex-grow: 1; }
        .pcm-input-sm { width: 100px; }
        .pcm-remove-item-btn { background: none; border: none; color: var(--pcm-danger); cursor: pointer; font-size: 1.2rem; }
        #pcm-add-item-btn { margin-top: 1rem; }
        .pcm-invoice-totals { display: flex; justify-content: space-between; margin-top: 2rem; flex-wrap: wrap; }
        .pcm-totals-box { width: 300px; background-color: var(--pcm-bg); padding: 1rem; border-radius: 0.5rem; }
        .pcm-totals-box > div { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .pcm-totals-box input { width: 60px; text-align: right; }
        .pcm-total-final { font-size: 1.2rem; font-weight: 600; border-top: 2px solid var(--pcm-border); padding-top: 0.5rem; }
        @media (max-width: 782px) { .pcm-form-grid { grid-template-columns: 1fr; } .pcm-invoice-totals { flex-direction: column; } .pcm-totals-box { width: 100%; margin-top: 1rem; } }
    </style>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching logic
            const tabLinks = document.querySelectorAll('.pcm-tab-link');
            const tabContents = document.querySelectorAll('.pcm-tab-content');
            
            function switchTab(tabId) {
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                const activeLink = document.querySelector(`.pcm-tab-link[data-tab="${tabId}"]`);
                const activeContent = document.getElementById(tabId);
                if(activeLink) activeLink.classList.add('active');
                if(activeContent) activeContent.classList.add('active');
            }

            tabLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    switchTab(link.dataset.tab);
                });
            });

            // Copy to clipboard logic
            document.querySelectorAll('.pcm-copy-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const textToCopy = button.dataset.clipboard;
                    if (!textToCopy) return;
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        const originalText = button.textContent;
                        button.textContent = 'Copied!';
                        setTimeout(() => { button.textContent = originalText; }, 2000);
                    });
                });
            });

            // Handle edit link to switch tab
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'edit') {
                switchTab('add-client');
            } else if (urlParams.get('action') === 'edit_req') {
                switchTab('add-requirement');
            } else if (urlParams.get('action') === 'edit_invoice') {
                switchTab('generate-invoice');
            } else if (urlParams.get('action') === 'edit_proposal') {
                switchTab('create-proposal');
            }

            // Chart.js logic
            if (typeof Chart !== 'undefined' && document.getElementById('clientStatusChart')) {
                const clientCtx = document.getElementById('clientStatusChart').getContext('2d');
                new Chart(clientCtx, {
                    type: 'doughnut',
                    data: {
                        labels: pcm_client_chart_labels,
                        datasets: [{
                            label: 'Client Status',
                            data: pcm_client_chart_data,
                            backgroundColor: ['#22c55e', '#f59e0b', '#ef4444', '#64748b'],
                            borderColor: 'var(--pcm-card-bg)',
                            borderWidth: 4,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '65%',
                        plugins: { legend: { position: 'bottom', labels: { padding: 20 } } }
                    }
                });
            }

            if (typeof Chart !== 'undefined' && document.getElementById('requirementsStatusChart')) {
                const reqCtx = document.getElementById('requirementsStatusChart').getContext('2d');
                new Chart(reqCtx, {
                    type: 'bar',
                    data: {
                        labels: pcm_req_chart_labels,
                        datasets: [{
                            label: 'Requirements',
                            data: pcm_req_chart_data,
                            backgroundColor: ['#f59e0b', '#3b82f6', '#22c55e'],
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                        plugins: { legend: { display: false } }
                    }
                });
            }

            if (typeof Chart !== 'undefined' && document.getElementById('invoiceStatusChart')) {
                const invoiceCtx = document.getElementById('invoiceStatusChart').getContext('2d');
                new Chart(invoiceCtx, {
                    type: 'bar',
                    data: {
                        labels: pcm_invoice_chart_labels,
                        datasets: [{
                            label: 'Invoices',
                            data: pcm_invoice_chart_data,
                            backgroundColor: ['#9ca3af', '#3b82f6', '#22c55e', '#ef4444'],
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                        plugins: { legend: { display: false } }
                    }
                });
            }
            
            if (typeof Chart !== 'undefined' && document.getElementById('proposalStatusChart')) {
                const proposalCtx = document.getElementById('proposalStatusChart').getContext('2d');
                new Chart(proposalCtx, {
                    type: 'bar',
                    data: {
                        labels: pcm_proposal_chart_labels,
                        datasets: [{
                            label: 'Proposals',
                            data: pcm_proposal_chart_data,
                            backgroundColor: ['#9ca3af', '#3b82f6', '#22c55e', '#ef4444'],
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                        plugins: { legend: { display: false } }
                    }
                });
            }

            // Invoice form logic
            const invoiceForm = document.getElementById('pcm-invoice-form');
            if (invoiceForm) {
                const itemsWrapper = document.getElementById('invoice-items-wrapper');
                const addItemBtn = document.getElementById('pcm-add-item-btn');
                const taxRateInput = document.getElementById('invoice_tax_rate');
                const currencySelect = document.getElementById('invoice_currency');

                const calculateTotals = () => {
                    let subtotal = 0;
                    itemsWrapper.querySelectorAll('.pcm-invoice-item').forEach(item => {
                        const qty = parseFloat(item.querySelector('input[name="invoice_item_qty[]"]').value) || 0;
                        const price = parseFloat(item.querySelector('input[name="invoice_item_price[]"]').value) || 0;
                        subtotal += qty * price;
                    });
                    
                    const taxRate = parseFloat(taxRateInput.value) || 0;
                    const taxAmount = subtotal * (taxRate / 100);
                    const total = subtotal + taxAmount;
                    const currencySymbol = currencySelect.value;

                    document.getElementById('invoice-subtotal-display').textContent = currencySymbol + subtotal.toFixed(2);
                    document.getElementById('invoice_subtotal_hidden').value = subtotal.toFixed(2);
                    document.getElementById('invoice-tax-amount-display').textContent = currencySymbol + taxAmount.toFixed(2);
                    document.getElementById('invoice-total-display').textContent = currencySymbol + total.toFixed(2);
                };

                addItemBtn.addEventListener('click', () => {
                    const newItem = document.createElement('div');
                    newItem.className = 'pcm-invoice-item';
                    newItem.innerHTML = `
                        <input type="text" name="invoice_item_desc[]" placeholder="Item Description" required />
                        <input type="number" name="invoice_item_qty[]" placeholder="Qty" class="pcm-input-sm" value="1" step="any" required />
                        <input type="number" name="invoice_item_price[]" placeholder="Unit Price" class="pcm-input-sm" step="any" required />
                        <button type="button" class="pcm-remove-item-btn">Remove</button>
                    `;
                    itemsWrapper.appendChild(newItem);
                });

                itemsWrapper.addEventListener('click', (e) => {
                    if (e.target.classList.contains('pcm-remove-item-btn')) {
                        e.target.closest('.pcm-invoice-item').remove();
                        calculateTotals();
                    }
                });

                invoiceForm.addEventListener('input', calculateTotals);
                calculateTotals(); // Initial calculation on page load
            }
        });
    </script>
    <?php
}
add_action('admin_head', 'pcm_admin_inline_assets');

/**
 * Generate PDF for an invoice.
 */
function pcm_generate_invoice_pdf($invoice, $org_details) {
    $tcpdf_path = plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        return; // Silently fail if TCPDF is not there, notice is shown on the main page.
    }
    require_once($tcpdf_path);
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($org_details->company_name);
    $pdf->SetTitle('Invoice ' . $invoice->invoice_number);
    $pdf->SetSubject('Invoice');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    $pdf->AddPage();

    // Logo and Company Details
    if (!empty($org_details->logo_path) && file_exists($org_details->logo_path)) {
        $pdf->Image($org_details->logo_path, 15, 10, 40, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, $org_details->company_name, 0, false, 'R', 0, '', 0, false, 'M', 'M');
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 10, $org_details->address, 0, 'R', 0, 1, '', '', true);
    $pdf->Ln(15);
    
    $pdf->SetFont('helvetica', '', 9);

    $html = '
        <table cellpadding="5">
            <tr>
                <td><b>To:</b><br>' . esc_html($invoice->company) . '<br>' . esc_html($invoice->email) . '</td>
                <td align="right"><b>Invoice #:</b> ' . esc_html($invoice->invoice_number) . '<br><b>Date:</b> ' . date_format(date_create($invoice->invoice_date), 'M j, Y') . '<br><b>Due Date:</b> ' . date_format(date_create($invoice->due_date), 'M j, Y') . '</td>
            </tr>
        </table>
        <br><br>
        <table border="1" cellpadding="5">
            <tr bgcolor="#eee">
                <th width="50%"><b>Description</b></th>
                <th width="15%" align="center"><b>Quantity</b></th>
                <th width="15%" align="right"><b>Unit Price</b></th>
                <th width="20%" align="right"><b>Amount</b></th>
            </tr>';

    $line_items = json_decode($invoice->line_items, true);
    foreach ($line_items as $item) {
        $html .= '<tr>
                    <td>' . esc_html($item['description']) . '</td>
                    <td align="center">' . $item['quantity'] . '</td>
                    <td align="right">' . esc_html($invoice->currency) . number_format($item['price'], 2) . '</td>
                    <td align="right">' . esc_html($invoice->currency) . number_format($item['quantity'] * $item['price'], 2) . '</td>
                  </tr>';
    }

    $html .= '</table>
        <br><br>
        <table cellpadding="5">
            <tr>
                <td width="70%"></td>
                <td width="30%">
                    <table border="1" cellpadding="5">
                        <tr><td><b>Subtotal</b></td><td align="right">' . esc_html($invoice->currency) . number_format($invoice->subtotal, 2) . '</td></tr>
                        <tr><td><b>Tax (' . $invoice->tax_rate . '%)</b></td><td align="right">' . esc_html($invoice->currency) . number_format($invoice->tax_amount, 2) . '</td></tr>
                        <tr bgcolor="#eee"><td><b>Total</b></td><td align="right"><b>' . esc_html($invoice->currency) . number_format($invoice->total, 2) . '</b></td></tr>
                    </table>
                </td>
            </tr>
        </table>';
    
    if(!empty($invoice->notes)){
        $html .= '<br><br><b>Notes:</b><br>' . nl2br(esc_html($invoice->notes));
    }

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('invoice-' . $invoice->invoice_number . '.pdf', 'I');
    exit;
}

/**
 * Generate PDF for a proposal.
 */
function pcm_generate_proposal_pdf($proposal, $org_details) {
    $tcpdf_path = plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        return;
    }
    require_once($tcpdf_path);
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($org_details->company_name);
    $pdf->SetTitle('Proposal: ' . $proposal->title);
    $pdf->SetSubject('Proposal');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    $pdf->AddPage();

    // Logo and Company Details
    if (!empty($org_details->logo_path) && file_exists($org_details->logo_path)) {
        $pdf->Image($org_details->logo_path, 15, 10, 40, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, $org_details->company_name, 0, false, 'R', 0, '', 0, false, 'M', 'M');
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 10, $org_details->address, 0, 'R', 0, 1, '', '', true);
    $pdf->Ln(15);
    
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 15, 'PROPOSAL', 0, false, 'L', 0, '', 0, false, 'M', 'M');
    $pdf->Ln(15);
    
    $pdf->SetFont('helvetica', '', 10);

    $html = '
        <table cellpadding="5">
            <tr>
                <td><b>To:</b><br>' . esc_html($proposal->client_name) . '<br>' . esc_html($proposal->company) . '<br>' . esc_html($proposal->email) . '</td>
                <td align="right"><b>Date:</b> ' . date_format(date_create($proposal->created_at), 'M j, Y') . '</td>
            </tr>
        </table>
        <br><br>
        <h2>' . esc_html($proposal->title) . '</h2>
        <hr>
        <h3>Scope of Work</h3>
        <p>' . nl2br(esc_html($proposal->scope)) . '</p>
        <h3>Timeline</h3>
        <p>' . nl2br(esc_html($proposal->timeline)) . '</p>
        <h3>Pricing</h3>
        <p>' . nl2br(esc_html($proposal->pricing)) . '</p>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('proposal-' . sanitize_title($proposal->title) . '.pdf', 'I');
    exit;
}
