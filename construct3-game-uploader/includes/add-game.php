<?php
// Add New Game page for Construct3 Game Uploader

function c3gu_add_new_game_page() {
    global $wpdb;

    if (isset($_POST['c3gu_add_game']) && check_admin_referer('c3gu_add_game', 'c3gu_add_game_nonce')) {
        $title = sanitize_text_field($_POST['title']);
        $description = wp_kses_post(stripslashes($_POST['description']));
        $orientation = sanitize_text_field($_POST['orientation']);
        $custom_width = ($orientation === 'custom') ? intval($_POST['custom_width']) : null;
        $custom_height = ($orientation === 'custom') ? intval($_POST['custom_height']) : null;

        if (empty($title) || empty($_FILES['game_zip']['name']) || !in_array($orientation, ['landscape', 'portrait', 'custom']) || 
            ($orientation === 'custom' && ($custom_width <= 0 || $custom_height <= 0))) {
            echo '<div class="notice notice-error"><p>Please provide a title, ZIP file, valid orientation, and positive width/height for custom sizes.</p></div>';
        } else {
            $upload = wp_handle_upload($_FILES['game_zip'], ['test_form' => false, 'mimes' => ['zip' => 'application/zip']]);
            if (isset($upload['error'])) {
                echo '<div class="notice notice-error"><p>File upload failed: ' . esc_html($upload['error']) . '</p></div>';
            } else {
                $wpdb->insert(C3GU_TABLE, [
                    'title' => $title,
                    'description' => $description,
                    'orientation' => $orientation,
                    'custom_width' => $custom_width,
                    'custom_height' => $custom_height
                ], ['%s', '%s', '%s', '%d', '%d']);
                $game_id = $wpdb->insert_id;

                if ($game_id) {
                    $folder_path = "game_$game_id";
                    $extract_dir = C3GU_UPLOAD_DIR . $folder_path;
                    $zip = new ZipArchive();
                    if ($zip->open($upload['file']) === true) {
                        $zip->extractTo($extract_dir);
                        $zip->close();
                        unlink($upload['file']);
                        if (file_exists($extract_dir . '/index.html')) {
                            $wpdb->update(C3GU_TABLE, ['folder_path' => $folder_path], ['id' => $game_id], ['%s'], ['%d']);
                            $page_data = [
                                'post_title' => $title,
                                'post_content' => '[c3_game id="' . $game_id . '"]',
                                'post_status' => 'publish',
                                'post_type' => 'page',
                                'post_author' => get_current_user_id(),
                                'post_name' => sanitize_title($title),
                            ];
                            $page_id = wp_insert_post($page_data);
                            if (!is_wp_error($page_id)) {
                                $wpdb->update(C3GU_TABLE, ['page_id' => $page_id], ['id' => $game_id], ['%d'], ['%d']);
                                echo '<div class="notice notice-success"><p>Game added! <a href="' . esc_url(get_permalink($page_id)) . '">View Page</a></p></div>';
                            } else {
                                echo '<div class="notice notice-error"><p>Page creation failed: ' . esc_html($page_id->get_error_message()) . '</p></div>';
                            }
                        } else {
                            c3gu_delete_directory($extract_dir);
                            $wpdb->delete(C3GU_TABLE, ['id' => $game_id], ['%d']);
                            echo '<div class="notice notice-error"><p>No index.html in ZIP file.</p></div>';
                        }
                    } else {
                        $wpdb->delete(C3GU_TABLE, ['id' => $game_id], ['%d']);
                        echo '<div class="notice notice-error"><p>ZIP extraction failed.</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-error"><p>Database insertion failed: ' . esc_html($wpdb->last_error) . '</p></div>';
                }
            }
        }
    }

    // Enqueue the admin JavaScript
    wp_enqueue_script('c3gu-admin', C3GU_PLUGIN_URL . 'js/c3gu-admin.js', [], C3GU_VERSION, true);

    ?>
    <div class="wrap c3gu-wrap">
        <h1>Add New Game</h1>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('c3gu_add_game', 'c3gu_add_game_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="title">Title</label></th>
                    <td><input type="text" name="title" id="title" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td>
                        <?php
                        wp_editor('', 'description', [
                            'textarea_name' => 'description',
                            'media_buttons' => false,
                            'teeny' => true,
                            'textarea_rows' => 9,
                        ]);
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="orientation">Orientation</label></th>
                    <td>
                        <select name="orientation" id="orientation">
                            <option value="landscape">Landscape</option>
                            <option value="portrait">Portrait</option>
                            <option value="custom">Custom</option>
                        </select>
                        <span class="description">Landscape (960x540), Portrait (540x960), Custom size</span>
                    </td>
                </tr>
                <tr class="custom-size-row" style="display: none;">
                    <th><label for="custom_width">Custom Width (px)</label></th>
                    <td><input type="number" name="custom_width" id="custom_width" min="1" value="960" class="small-text" required></td>
                </tr>
                <tr class="custom-size-row" style="display: none;">
                    <th><label for="custom_height">Custom Height (px)</label></th>
                    <td><input type="number" name="custom_height" id="custom_height" min="1" value="540" class="small-text" required></td>
                </tr>
                <tr>
                    <th><label for="game_zip">Game ZIP File</label></th>
                    <td><input type="file" name="game_zip" id="game_zip" accept=".zip" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="c3gu_add_game" class="button button-primary" value="Add Game">
				<a href="<?php echo admin_url('admin.php?page=c3gu-games'); ?>" class="button">Back to Games</a>
            </p>
        </form>
    </div>
    <?php
}