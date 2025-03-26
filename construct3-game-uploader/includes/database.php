<?php
// Database setup for Construct3 Game Uploader

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function c3gu_activate() {
    global $wpdb;
    $table_name = C3GU_TABLE;
    $charset_collate = $wpdb->get_charset_collate();

    // Define the full table structure
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
        wp_mkdir_p(C3GU_UPLOAD_DIR);
    }

    // Ensure existing table is updated to allow NULL for custom_width and custom_height
    $installed_version = get_option('c3gu_version', '0.0.0');
    if (version_compare($installed_version, '1.1.0', '<')) {
        $wpdb->query("ALTER TABLE $table_name 
            MODIFY custom_width INT DEFAULT NULL,
            MODIFY custom_height INT DEFAULT NULL");
        // Optional: Clear custom sizes for non-custom orientations
        $wpdb->query("UPDATE $table_name SET custom_width = NULL, custom_height = NULL WHERE orientation != 'custom'");
        update_option('c3gu_version', '1.1.0');
    }
}