<?php
// Admin menu setup for Construct3 Game Uploader

// Add admin menu
add_action('admin_menu', 'c3gu_admin_menu');
function c3gu_admin_menu() {
    add_menu_page('Construct3 Games', 'Construct3 Games', 'manage_options', 'c3gu-games', 'c3gu_all_games_page', 'dashicons-games');
    add_submenu_page('c3gu-games', 'All Games', 'All Games', 'manage_options', 'c3gu-games', 'c3gu_all_games_page');
    add_submenu_page('c3gu-games', 'Add New Game', 'Add New Game', 'manage_options', 'c3gu-add-new', 'c3gu_add_new_game_page');
    add_submenu_page('c3gu-games', 'Settings', 'Settings', 'manage_options', 'c3gu-settings', 'c3gu_settings_page');
}

// Enqueue admin styles
add_action('admin_enqueue_scripts', 'c3gu_enqueue_admin_styles');
function c3gu_enqueue_admin_styles() {
    $screen = get_current_screen();
    if (strpos($screen->id, 'c3gu-') !== false) {
        wp_enqueue_style('c3gu-admin', C3GU_PLUGIN_URL . 'css/c3gu-admin.css', [], C3GU_VERSION);
    }
}

// Placeholder for pages (to avoid undefined function errors)
// function c3gu_all_games_page() { /* Defined in game-table.php */ }
//function c3gu_add_new_game_page() { /* Defined in add-game.php */ }
function c3gu_settings_page() { echo '<div class="wrap"><h1>Settings</h1><p>Settings page coming soon.</p></div>'; }