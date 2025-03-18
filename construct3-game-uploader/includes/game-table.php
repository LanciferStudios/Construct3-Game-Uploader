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
            'title' => ['title', true],       // Sort by title, default to ascending
            'created_at' => ['created_at', true], // Sort by date, default to ascending
        ];
    }

    function prepare_items() {
		global $wpdb;

		// Whitelist allowed orderby columns
		$allowed_orderby = ['id', 'title', 'created_at'];
		$orderby = !empty($_GET['orderby']) && in_array($_GET['orderby'], $allowed_orderby) ? $_GET['orderby'] : 'id';

		// Ensure order is valid
		$order = !empty($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'DESC';

		// Fetch data
		$this->items = $wpdb->get_results("SELECT * FROM " . C3GU_TABLE . " ORDER BY $orderby $order");
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
            return '[c3_game id="' . $item->id . '"]';
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
                $game = $wpdb->get_row($wpdb->prepare("SELECT folder_path, page_id FROM " . C3GU_TABLE . " WHERE id = %d", $game_id));
                if ($game) {
                    c3gu_delete_directory(C3GU_UPLOAD_DIR . $game->folder_path);
                    if ($game->page_id) wp_delete_post($game->page_id, true);
                    $wpdb->delete(C3GU_TABLE, ['id' => $game_id], ['%d']);
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
        $game = $wpdb->get_row($wpdb->prepare("SELECT folder_path, page_id FROM " . C3GU_TABLE . " WHERE id = %d", $game_id));
        if ($game) {
            c3gu_delete_directory(C3GU_UPLOAD_DIR . $game->folder_path);
            if ($game->page_id) wp_delete_post($game->page_id, true);
            $wpdb->delete(C3GU_TABLE, ['id' => $game_id], ['%d']);
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
    </div>
    <?php
}