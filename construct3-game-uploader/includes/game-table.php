<?php
// Game list table for Construct3 Game Uploader

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class C3GU_Games_List_Table extends WP_List_Table {
    function get_columns() {
        return [
            'cb' => '<input type="checkbox" />',
            'id' => 'ID',
            'title' => 'Title',
            'shortcode' => 'Shortcode',
            'orientation' => 'Orientation',
            'created_at' => 'Date',
        ];
    }

    function get_sortable_columns() {
        return [
            'title' => ['title', true],
            'created_at' => ['created_at', true],
        ];
    }

    function prepare_items() {
        global $wpdb;

        $allowed_orderby = ['id', 'title', 'created_at'];
        $orderby = !empty($_GET['orderby']) && in_array($_GET['orderby'], $allowed_orderby) ? $_GET['orderby'] : 'id';
        $order = !empty($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'DESC';

        $this->items = $wpdb->get_results("SELECT * FROM " . c3gu_get_table_name() . " ORDER BY $orderby $order");
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
                return $item->id;
            case 'created_at':
                $date_format = get_option('date_format');
                $time_format = get_option('time_format');
                return date_i18n($date_format . ' \a\t ' . $time_format, strtotime($item->created_at));
            case 'shortcode':
                $shortcode = '[c3_game id="' . $item->id . '"]';
                return '<button type="button" class="copy-shortcode" data-shortcode="' . esc_attr($shortcode) . '">
                            <span class="screen-reader-text">Copy shortcode for ' . esc_html($item->title) . '</span>
                            ' . esc_html($shortcode) . '
                            <span class="tooltip">Click to copy</span>
                            <span class="copied" style="display: none;">Copied!</span>
                        </button>';
            case 'orientation':
                return ucfirst($item->orientation);
            default:
                return '';
        }
    }

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="game_ids[]" value="%s" />', $item->id);
    }

    function column_title($item) {
        $edit_url = admin_url('admin.php?page=c3gu-games&action=edit&id=' . $item->id);
        $delete_url = wp_nonce_url(admin_url('admin.php?page=c3gu-games&action=delete&id=' . $item->id), 'c3gu_delete_game_' . $item->id);
        $view_url = $item->page_id ? get_permalink($item->page_id) : false;
        $title = '<strong><a href="' . esc_url($edit_url) . '" class="row-title">' . esc_html($item->title) . '</a></strong>';
        $actions = [
            'edit' => '<span class="edit"><a href="' . esc_url($edit_url) . '">Edit</a></span>',
        ];
        if ($view_url) {
            $actions['view'] = '<span class="view"><a href="' . esc_url($view_url) . '" target="_blank">View Page</a></span>';
        }
        $actions['delete'] = '<span class="trash"><a href="' . esc_url($delete_url) . '" onclick="return confirm(\'Are you sure?\');">Delete</a></span>';
        $row_actions = '<div class="row-actions">' . implode(' | ', $actions) . '</div>';
        return $title . $row_actions;
    }

    function get_bulk_actions() {
        return ['delete' => 'Delete'];
    }

    function process_bulk_action() {
        global $wpdb;
        if ('delete' === $this->current_action() && !empty($_POST['game_ids']) && check_admin_referer('bulk-' . $this->_args['plural'])) {
            $game_ids = array_map('intval', $_POST['game_ids']);
            foreach ($game_ids as $game_id) {
                $game = $wpdb->get_row($wpdb->prepare("SELECT folder_path, page_id FROM " . c3gu_get_table_name() . " WHERE id = %d", $game_id));
                if ($game) {
                    c3gu_delete_directory(C3GU_UPLOAD_DIR . $game->folder_path);
                    if ($game->page_id) wp_delete_post($game_id, true);
                    $wpdb->delete(c3gu_get_table_name(), ['id' => $game_id], ['%d']);
                }
            }
            add_settings_error('c3gu_messages', 'bulk_delete', 'Selected games deleted successfully.', 'success');
        }
    }
}

function c3gu_all_games_page() {
    global $wpdb;

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && check_admin_referer('c3gu_delete_game_' . $_GET['id'])) {
        $game_id = intval($_GET['id']);
        $game = $wpdb->get_row($wpdb->prepare("SELECT folder_path, page_id FROM " . c3gu_get_table_name() . " WHERE id = %d", $game_id));
        if ($game) {
            c3gu_delete_directory(C3GU_UPLOAD_DIR . $game->folder_path);
            if ($game->page_id) wp_delete_post($game_id, true);
            $wpdb->delete(c3gu_get_table_name(), ['id' => $game_id], ['%d']);
            add_settings_error('c3gu_messages', 'single_delete', 'Game deleted successfully.', 'success');
        }
    }

    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        c3gu_edit_game_page();
        return;
    }

    $table = new C3GU_Games_List_Table();
    $table->process_bulk_action();
    $table->prepare_items();
    ?>
    <div class="wrap c3gu-wrap">
        <h1 class="wp-heading-inline">Construct3 Games</h1>
        <a href="<?php echo admin_url('admin.php?page=c3gu-add-new'); ?>" class="page-title-action">Add New</a>
        <?php settings_errors('c3gu_messages'); ?>
        <form method="post">
            <?php $table->display(); ?>
        </form>
        <!-- Inline CSS for styling the copy button and tooltips -->
        <style>
            .copy-shortcode {
                position: relative;
                cursor: pointer;
                background: none;
                border: none;
                padding: 0;
                color: #0073aa;
                text-decoration: underline;
            }
            .copy-shortcode:hover {
                color: #005177;
            }
            .tooltip {
                display: none;
                position: absolute;
                background: #333;
                color: #fff;
                padding: 5px 10px;
                border-radius: 3px;
                top: -30px;
                left: 50%;
                transform: translateX(-50%);
                white-space: nowrap;
                font-size: 12px;
                z-index: 10;
            }
            .copied {
                display: none;
                position: absolute;
                background: #333;
                color: #fff;
                padding: 5px 10px;
                border-radius: 3px;
                top: -30px;
                left: 50%;
                transform: translateX(-50%);
                white-space: nowrap;
                font-size: 12px;
                z-index: 10;
            }
            .copy-shortcode:hover .tooltip {
                display: block;
            }
            .screen-reader-text {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                border: 0;
            }
        </style>
        <!-- Inline JavaScript for copy functionality -->
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                console.log("C3GU copy script loaded"); // Log when script initializes
                document.querySelectorAll(".copy-shortcode").forEach(button => {
                    button.addEventListener("click", function(event) {
                        event.preventDefault();
                        const shortcode = this.getAttribute("data-shortcode");
                        const copied = this.querySelector(".copied");
                        console.log("Attempting to copy shortcode: " + shortcode); // Log click action

                        // Try Clipboard API
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(shortcode)
                                .then(() => {
                                    console.log("Successfully copied via Clipboard API: " + shortcode);
                                    copied.style.display = "block";
                                    setTimeout(() => {
                                        copied.style.display = "none";
                                        console.log("Hid 'Copied!' message");
                                    }, 1000);
                                })
                                .catch(err => {
                                    console.error("Clipboard API failed: ", err);
                                    fallbackCopy(shortcode, copied);
                                });
                        } else {
                            console.log("Clipboard API unavailable, using fallback");
                            fallbackCopy(shortcode, copied);
                        }
                    });
                });

                function fallbackCopy(text, copiedElement) {
                    console.log("Running fallback copy for: " + text);
                    const textarea = document.createElement("textarea");
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand("copy");
                        console.log("Successfully copied via fallback: " + text);
                        copiedElement.style.display = "block";
                        setTimeout(() => {
                            copiedElement.style.display = "none";
                            console.log("Hid 'Copied!' message in fallback");
                        }, 1000);
                    } catch (err) {
                        console.error("Fallback copy failed: ", err);
                    }
                    document.body.removeChild(textarea);
                }
            });
        </script>
    </div>
    <?php
}