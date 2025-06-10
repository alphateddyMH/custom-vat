<?php
/**
 * The import/export functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The import/export functionality of the plugin.
 *
 * Handles importing and exporting tax rates.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Import_Export {

    /**
     * Database instance.
     *
     * @since    2.0.0
     * @access   private
     * @var      EDD_Custom_VAT_Database    $db    Database instance.
     */
    private $db;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->db = new EDD_Custom_VAT_Database();
    }

    /**
     * Render the import/export section.
     *
     * @since    2.0.0
     */
    public function render_import_export_section() {
        ?>
        <div class="edd-custom-vat-settings-section">
            <h2><?php _e('Import/Export Tax Rates', 'edd-custom-vat'); ?></h2>
            
            <div class="edd-custom-vat-import-export-wrapper">
                <div class="edd-custom-vat-export-section">
                    <h3><i class="fas fa-file-export"></i> <?php _e('Export Tax Rates', 'edd-custom-vat'); ?></h3>
                    <p><?php _e('Export all custom tax rates to a CSV file.', 'edd-custom-vat'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('edd_custom_vat_export', 'edd_custom_vat_export_nonce'); ?>
                        <input type="hidden" name="edd_custom_vat_action" value="export" />
                        <p>
                            <button type="submit" class="button button-primary">
                                <i class="fas fa-download"></i> <?php _e('Export to CSV', 'edd-custom-vat'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <div class="edd-custom-vat-import-section">
                    <h3><i class="fas fa-file-import"></i> <?php _e('Import Tax Rates', 'edd-custom-vat'); ?></h3>
                    <p><?php _e('Import custom tax rates from a CSV file.', 'edd-custom-vat'); ?></p>
                    
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('edd_custom_vat_import', 'edd_custom_vat_import_nonce'); ?>
                        <input type="hidden" name="edd_custom_vat_action" value="import" />
                        <p>
                            <input type="file" name="edd_custom_vat_import_file" accept=".csv" required />
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">
                                <i class="fas fa-upload"></i> <?php _e('Import from CSV', 'edd-custom-vat'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            
            <div class="edd-custom-vat-bulk-actions">
                <h3><i class="fas fa-tools"></i> <?php _e('Bulk Actions', 'edd-custom-vat'); ?></h3>
                
                <div class="edd-custom-vat-bulk-action-section">
                    <h4><?php _e('Delete All Tax Rates', 'edd-custom-vat'); ?></h4>
                    <p><?php _e('This will delete all custom tax rates for all products.', 'edd-custom-vat'); ?></p>
                    
                    <form method="post" action="" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete all tax rates? This action cannot be undone.', 'edd-custom-vat'); ?>');">
                        <?php wp_nonce_field('edd_custom_vat_delete_all', 'edd_custom_vat_delete_all_nonce'); ?>
                        <input type="hidden" name="edd_custom_vat_action" value="delete_all" />
                        <p>
                            <button type="submit" class="button button-secondary">
                                <i class="fas fa-trash-alt"></i> <?php _e('Delete All Tax Rates', 'edd-custom-vat'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            
            <div class="edd-custom-vat-info-box">
                <h3><i class="fas fa-info-circle"></i> <?php _e('CSV Format', 'edd-custom-vat'); ?></h3>
                <p><?php _e('The CSV file should have the following format:', 'edd-custom-vat'); ?></p>
                <pre>Product Name,Product ID,Country,Tax Rate
"My Product",123,DE,19
"Another Product",456,FR,20</pre>
                <p><?php _e('The first row should be the header row. The Product ID and Country columns are required.', 'edd-custom-vat'); ?></p>
            </div>
            
            <?php
            // Display import results if available
            $import_results = get_transient('edd_custom_vat_import_results');
            if ($import_results) {
                delete_transient('edd_custom_vat_import_results');
                ?>
                <div class="edd-custom-vat-import-results">
                    <h3><?php _e('Import Results', 'edd-custom-vat'); ?></h3>
                    <p>
                        <?php 
                        printf(
                            __('Successfully imported %d tax rates. Failed to import %d tax rates.', 'edd-custom-vat'),
                            $import_results['success'],
                            $import_results['failed']
                        ); 
                        ?>
                    </p>
                    <?php if (!empty($import_results['errors'])) : ?>
                        <div class="edd-custom-vat-import-errors">
                            <h4><?php _e('Errors', 'edd-custom-vat'); ?></h4>
                            <ul>
                                <?php foreach ($import_results['errors'] as $error) : ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * Process export request.
     *
     * @since    2.0.0
     */
    public function process_export() {
        if (!isset($_POST['edd_custom_vat_action']) || 'export' !== $_POST['edd_custom_vat_action']) {
            return;
        }

        // Check nonce
        if (!isset($_POST['edd_custom_vat_export_nonce']) || !wp_verify_nonce($_POST['edd_custom_vat_export_nonce'], 'edd_custom_vat_export')) {
            wp_die(__('Security check failed.', 'edd-custom-vat'));
        }

        // Check user capabilities
        if (!current_user_can('manage_shop_settings')) {
            wp_die(__('You do not have permission to export tax rates.', 'edd-custom-vat'));
        }

        // Generate CSV data
        $csv_data = $this->db->export_tax_rates();

        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=edd-custom-vat-rates-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV data
        echo $csv_data;
        exit;
    }

    /**
     * Process import request.
     *
     * @since    2.0.0
     */
    public function process_import() {
        if (!isset($_POST['edd_custom_vat_action']) || 'import' !== $_POST['edd_custom_vat_action']) {
            return;
        }

        // Check nonce
        if (!isset($_POST['edd_custom_vat_import_nonce']) || !wp_verify_nonce($_POST['edd_custom_vat_import_nonce'], 'edd_custom_vat_import')) {
            wp_die(__('Security check failed.', 'edd-custom-vat'));
        }

        // Check user capabilities
        if (!current_user_can('manage_shop_settings')) {
            wp_die(__('You do not have permission to import tax rates.', 'edd-custom-vat'));
        }

        // Check if file was uploaded
        if (!isset($_FILES['edd_custom_vat_import_file']) || $_FILES['edd_custom_vat_import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('No file uploaded or upload error.', 'edd-custom-vat'));
        }

        // Get file contents
        $file = $_FILES['edd_custom_vat_import_file'];
        
        // Check file type
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ('csv' !== strtolower($file_ext)) {
            wp_die(__('Invalid file type. Please upload a CSV file.', 'edd-custom-vat'));
        }

        // Read file contents
        $csv_data = file_get_contents($file['tmp_name']);
        
        // Process import
        $results = $this->db->import_tax_rates($csv_data);
        
        // Store results in transient for display
        set_transient('edd_custom_vat_import_results', $results, 60);
        
        // Redirect back to import page
        wp_redirect(admin_url('edit.php?post_type=download&page=edd-custom-vat&tab=import-export'));
        exit;
    }

    /**
     * Process delete all request.
     *
     * @since    2.0.0
     */
    public function process_delete_all() {
        if (!isset($_POST['edd_custom_vat_action']) || 'delete_all' !== $_POST['edd_custom_vat_action']) {
            return;
        }

        // Check nonce
        if (!isset($_POST['edd_custom_vat_delete_all_nonce']) || !wp_verify_nonce($_POST['edd_custom_vat_delete_all_nonce'], 'edd_custom_vat_delete_all')) {
            wp_die(__('Security check failed.', 'edd-custom-vat'));
        }

        // Check user capabilities
        if (!current_user_can('manage_shop_settings')) {
            wp_die(__('You do not have permission to delete all tax rates.', 'edd-custom-vat'));
        }

        // Delete all tax rates
        $this->db->delete_all_tax_rates();
        
        // Clear cache
        $cache = new EDD_Custom_VAT_Cache();
        $cache->clear_all_caches();
        
        // Add admin notice
        add_settings_error(
            'edd_custom_vat',
            'edd_custom_vat_delete_all',
            __('All tax rates have been deleted.', 'edd-custom-vat'),
            'updated'
        );
        
        // Redirect back to import page
        wp_redirect(admin_url('edit.php?post_type=download&page=edd-custom-vat&tab=import-export'));
        exit;
    }
}
