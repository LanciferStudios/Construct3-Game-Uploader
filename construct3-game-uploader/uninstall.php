<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get the global wpdb object
global $wpdb;

// Define the function to get the table name
function c3gu_get_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'c3_games';  // Adjust if your table name is different
}

// Define the table name using the dynamic function
$table_name = c3gu_get_table_name();

// Define the upload directory
$upload_dir = wp_upload_dir()['basedir'] . '/c3_games/';

// Check if the table exists before querying
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    // Get all page IDs from the games table
    $page_ids = $wpdb->get_col("SELECT page_id FROM $table_name WHERE page_id IS NOT NULL");

    // Delete associated pages
    foreach ($page_ids as $page_id) {
        wp_delete_post($page_id, true); // true forces permanent deletion
    }

    // Drop the games table
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Function to recursively delete a directory and its contents
function c3gu_delete_directory($dir) {
    if (!file_exists($dir)) {
        return;
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = "$dir/$file";
        if (is_dir($path)) {
            c3gu_delete_directory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

// Delete the c3_games upload folder
c3gu_delete_directory($upload_dir);

// Remove the c3gu_db_version option from wp_options table
delete_option('c3gu_db_version');