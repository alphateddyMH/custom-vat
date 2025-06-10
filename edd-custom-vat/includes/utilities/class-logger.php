<?php
/**
 * The logger functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/utilities
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The logger functionality of the plugin.
 *
 * Handles logging for debugging purposes.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/utilities
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Logger {

    /**
     * Log file path.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $log_file    The log file path.
     */
    private $log_file;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = trailingslashit($upload_dir['basedir']) . 'edd-custom-vat-debug.log';
    }

    /**
     * Log a message.
     *
     * @since    2.0.0
     * @param    string    $message    The message to log.
     * @param    string    $level      The log level (debug, info, warning, error).
     */
    public function log($message, $level = 'debug') {
        // Check if debug mode is enabled
        if (!EDD_Custom_VAT_Settings::is_debug_mode()) {
            return;
        }

        // Format the log entry
        $timestamp = current_time('mysql');
        $entry = sprintf('[%s] [%s] %s', $timestamp, strtoupper($level), $message) . PHP_EOL;
        
        // Write to log file
        $this->write_to_log($entry);
        
        // If this is an error, also log to WordPress error log
        if ('error' === $level) {
            error_log('[EDD Custom VAT] ' . $message);
        }
    }

    /**
     * Write to the log file.
     *
     * @since    2.0.0
     * @access   private
     * @param    string    $entry    The log entry.
     */
    private function write_to_log($entry) {
        // Check if file is too large (> 10MB)
        if (file_exists($this->log_file) && filesize($this->log_file) > 10 * 1024 * 1024) {
            // Rotate log file
            $this->rotate_log();
        }
        
        // Append to log file
        file_put_contents($this->log_file, $entry, FILE_APPEND);
    }

    /**
     * Rotate the log file.
     *
     * @since    2.0.0
     * @access   private
     */
    private function rotate_log() {
        // Create a backup of the current log file
        $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        copy($this->log_file, $backup_file);
        
        // Clear the current log file
        file_put_contents($this->log_file, '');
        
        // Log the rotation
        $entry = sprintf('[%s] [INFO] Log file rotated. Backup saved as %s', current_time('mysql'), basename($backup_file)) . PHP_EOL;
        file_put_contents($this->log_file, $entry, FILE_APPEND);
    }

    /**
     * Clear the log file.
     *
     * @since    2.0.0
     */
    public function clear_log() {
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
            
            // Log the clearing
            $entry = sprintf('[%s] [INFO] Log file cleared', current_time('mysql')) . PHP_EOL;
            file_put_contents($this->log_file, $entry, FILE_APPEND);
        }
    }

    /**
     * Get the log file contents.
     *
     * @since    2.0.0
     * @return   string    The log file contents.
     */
    public function get_log_contents() {
        if (file_exists($this->log_file)) {
            return file_get_contents($this->log_file);
        }
        
        return '';
    }

    /**
     * Get the log file path.
     *
     * @since    2.0.0
     * @return   string    The log file path.
     */
    public function get_log_file_path() {
        return $this->log_file;
    }

    /**
     * Check if the log file exists.
     *
     * @since    2.0.0
     * @return   bool    True if the log file exists, false otherwise.
     */
    public function log_file_exists() {
        return file_exists($this->log_file);
    }

    /**
     * Get the log file size.
     *
     * @since    2.0.0
     * @return   int    The log file size in bytes.
     */
    public function get_log_file_size() {
        if (file_exists($this->log_file)) {
            return filesize($this->log_file);
        }
        
        return 0;
    }

    /**
     * Format log file size for display.
     *
     * @since    2.0.0
     * @param    int       $size    The size in bytes.
     * @return   string             The formatted size.
     */
    public function format_file_size($size) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $size = max($size, 0);
        $pow = floor(($size ? log($size) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $size /= pow(1024, $pow);
        
        return round($size, 2) . ' ' . $units[$pow];
    }
}
