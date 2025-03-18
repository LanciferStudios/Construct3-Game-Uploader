<?php
// Edit Game page for Construct3 Game Uploader

function c3gu_edit_game_page() {
    global $wpdb;

    $game_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $game = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . C3GU_TABLE . " WHERE id = %d", $game_id));

    if (!$game) {
        echo '<div class="notice notice-error"><p>Game not found.</p></div>';
        return;
    }

    if (isset($_POST['c3gu_edit_game']) && check_admin_referer('c3gu_edit_game_' . $game_id, 'c3gu_edit_game_nonce')) {
        $title = sanitize_text_field($_POST['title']);
        $description = wp_kses_post(stripslashes($_POST['description']));
        $orientation = sanitize_text_field($_POST['orientation']);
        $custom_width = ($orientation === 'custom') ? intval($_POST['custom_width']) : null;
        $custom_height = ($orientation === 'custom') ? intval($_POST['custom_height']) : null;

        if (empty($title) || !in_array($orientation, ['landscape', 'portrait', 'custom']) || 
            ($orientation === 'custom' && ($custom_width <= 0 || $custom_height <= 0))) {
            echo '<div class="notice notice-error"><p>Title, valid orientation, and positive width/height for custom sizes are required.</p></div>';
        } else {
            $data = [
                'title' => $title,
                'description' => $description,
                'orientation' => $orientation,
                'custom_width' => $custom_width,
                'custom_height' => $custom_height
            ];
            $where = ['id' => $game_id];

            if (!empty($_FILES['game_zip']['name'])) {
                $upload = wp_handle_upload($_FILES['game_zip'], ['test_form' => false, 'mimes' => ['zip' => 'application/zip']]);
                if (isset($upload['error'])) {
                    echo '<div class="notice notice-error"><p>File upload failed: ' . esc_html($upload['error']) . '</p></div>';
                } else {
                    $old_folder = C3GU_UPLOAD_DIR . $game->folder_path;
                    if (file_exists($old_folder)) {
                        c3gu_delete_directory($old_folder);
                    }
                    $extract_dir = C3GU_UPLOAD_DIR . $game->folder_path;
                    $zip = new ZipArchive();
                    if ($zip->open($upload['file']) === true) {
                        $zip->extractTo($extract_dir);
                        $zip->close();
                        unlink($upload['file']);
                        if (!file_exists($extract_dir . '/index.html')) {
                            c3gu_delete_directory($extract_dir);
                            echo '<div class="notice notice-error"><p>No index.html in ZIP file.</p></div>';
                            return;
                        }
                    } else {
                        echo '<div class="notice notice-error"><p>ZIP extraction failed.</p></div>';
                        return;
                    }
                }
            }

            $updated = $wpdb->update(C3GU_TABLE, $data, $where, ['%s', '%s', '%s', '%d', '%d'], ['%d']);
            if ($updated !== false) {
                if ($title !== $game->title && $game->page_id) {
                    wp_update_post(['ID' => $game->page_id, 'post_title' => $title]);
                }
                // Re-fetch the game data after update to reflect changes
                $game = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . C3GU_TABLE . " WHERE id = %d", $game_id));
                echo '<div class="notice notice-success"><p>Game updated successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to update game: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        }
    }

    // Enqueue the admin JavaScript
    wp_enqueue_script('c3gu-admin', C3GU_PLUGIN_URL . 'js/c3gu-admin.js', [], C3GU_VERSION, true);

    ?>
    <div class="wrap c3gu-wrap">
        <h1>Edit Game</h1>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('c3gu_edit_game_' . $game_id, 'c3gu_edit_game_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="title">Title</label></th>
                    <td><input type="text" name="title" id="title" class="regular-text" value="<?php echo esc_attr($game->title); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td>
                        <?php
                        wp_editor($game->description, 'description', [
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
                            <option value="landscape" <?php selected($game->orientation, 'landscape'); ?>>Landscape</option>
                            <option value="portrait" <?php selected($game->orientation, 'portrait'); ?>>Portrait</option>
                            <option value="custom" <?php selected($game->orientation, 'custom'); ?>>Custom</option>
                        </select>
                        <span class="description">Defaults: Landscape 960x540, Portrait 540x960</span>
                    </td>
                </tr>
                <tr class="custom-size-row" style="display: <?php echo $game->orientation === 'custom' ? '' : 'none'; ?>;">
                    <th><label for="custom_width">Custom Width (px)</label></th>
                    <td><input type="number" name="custom_width" id="custom_width" min="1" value="<?php echo esc_attr($game->custom_width ?: 960); ?>" class="small-text" required></td>
                </tr>
                <tr class="custom-size-row" style="display: <?php echo $game->orientation === 'custom' ? '' : 'none'; ?>;">
                    <th><label for="custom_height">Custom Height (px)</label></th>
                    <td><input type="number" name="custom_height" id="custom_height" min="1" value="<?php echo esc_attr($game->custom_height ?: 540); ?>" class="small-text" required></td>
                </tr>
                <tr>
                    <th><label for="game_zip">Replace Game ZIP (optional)</label></th>
                    <td><input type="file" name="game_zip" id="game_zip" accept=".zip"> <em>Leave blank to keep existing files.</em></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="c3gu_edit_game" class="button button-primary" value="Update Game">
                <a href="<?php echo admin_url('admin.php?page=c3gu-games'); ?>" class="button">Back to Games</a>
            </p>
        </form>
    </div>
    <?php
}