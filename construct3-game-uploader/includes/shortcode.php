<?php
// Shortcode for Construct3 Game Uploader

add_shortcode('c3_game', 'c3gu_game_shortcode');
function c3gu_game_shortcode($atts) {
    global $wpdb;

    $atts = shortcode_atts(['id' => 0], $atts);
    $game_id = intval($atts['id']);

    if ($game_id <= 0) {
        return '<p>Invalid game ID.</p>';
    }

    $game = $wpdb->get_row($wpdb->prepare("SELECT folder_path, description, orientation, custom_width, custom_height FROM " . c3gu_get_table_name() . " WHERE id = %d", $game_id));
    if (!$game || !file_exists(C3GU_UPLOAD_DIR . $game->folder_path . '/index.html')) {
        return '<p>Game not found.</p>';
    }

    $game_url = C3GU_UPLOAD_URL . $game->folder_path . '/index.html';
    $iframe_id = 'c3gu-game-iframe-' . $game_id;

    // Set dimensions based on orientation or custom values
    if ($game->orientation === 'custom' && $game->custom_width && $game->custom_height) {
        $width = $game->custom_width;
        $height = $game->custom_height;
    } else {
        $width = ($game->orientation === 'portrait') ? 540 : 960;
        $height = ($game->orientation === 'portrait') ? 960 : 540;
    }
    $orientation_class = ($game->orientation === 'portrait') ? 'c3gu-portrait' : 'c3gu-landscape';

    $output = '<div class="c3gu-game-wrapper">';
    $output .= '<iframe id="' . esc_attr($iframe_id) . '" class="' . esc_attr($orientation_class) . '" src="' . esc_url($game_url) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" allowfullscreen></iframe>';
    $output .= '<div class="c3gu-button-wrapper">';
    $output .= '<button class="c3gu-fullscreen-btn" data-iframe-id="' . esc_attr($iframe_id) . '">Go Fullscreen</button>';
    $output .= '</div>';

    if (!empty($game->description)) {
        $output .= '<div class="c3gu-game-description">' . wp_kses_post($game->description) . '</div>';
    }

    $output .= '</div>';

    wp_enqueue_style('c3gu-frontend', C3GU_PLUGIN_URL . 'css/c3gu-frontend.css', [], C3GU_VERSION);
    wp_enqueue_script('c3gu-fullscreen', C3GU_PLUGIN_URL . 'js/c3gu-fullscreen.js', [], C3GU_VERSION, true);

    return $output;
}