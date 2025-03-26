<?php
/*
 Plugin Name: Construct3 Game Uploader
 Plugin URI: https://LanciferStudios.com
 Description: Easily upload and manage Construct3 HTML5 games in WordPress.
 Version: 1.0.0-beta.1
 Author: KingLancifer
 Author URI: https://LanciferStudios.com
 License: GPL-2.0-or-later
 License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('C3GU_VERSION', '1.0.0');
define('C3GU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('C3GU_PLUGIN_URL', plugin_dir_url(__FILE__));
global $wpdb;
define('C3GU_TABLE', $wpdb->prefix . 'c3_games');
define('C3GU_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/c3_games/');
define('C3GU_UPLOAD_URL', wp_upload_dir()['baseurl'] . '/c3_games/');

// Include necessary files
require_once C3GU_PLUGIN_DIR . 'includes/functions.php'; // New shared functions file
require_once C3GU_PLUGIN_DIR . 'includes/database.php';
require_once C3GU_PLUGIN_DIR . 'includes/admin-menu.php';
require_once C3GU_PLUGIN_DIR . 'includes/game-table.php';
require_once C3GU_PLUGIN_DIR . 'includes/add-game.php';
require_once C3GU_PLUGIN_DIR . 'includes/edit-game.php';
require_once C3GU_PLUGIN_DIR . 'includes/shortcode.php';

// Register activation hook
register_activation_hook(__FILE__, 'c3gu_activate');