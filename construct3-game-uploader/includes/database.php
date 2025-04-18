<?php
// Database setup for Construct3 Game Uploader

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function c3gu_activate() {
    global $wpdb;
    $table_name = c3gu_get_table_name(); // Use dynamic function
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        folder_path varchar(255) NOT NULL,
        page_id bigint(20) DEFAULT NULL,
        orientation varchar(20) DEFAULT 'landscape',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        custom_width INT DEFAULT NULL,
        custom_height INT DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Create upload directory if it doesn’t exist
    if (!file_exists(C3GU_UPLOAD_DIR)) {
        if (!wp_mkdir_p(C3GU_UPLOAD_DIR)) {
            error_log('Failed to create upload directory: ' . C3GU_UPLOAD_DIR);
        }
    }

    // Optionally, handle database version updates here
    $installed_version = get_option('c3gu_db_version', '0.0.0');
    if (version_compare($installed_version, '1.0.0', '<')) {
        // Add any schema updates if needed
        update_option('c3gu_db_version', '1.0.0');
    }
}