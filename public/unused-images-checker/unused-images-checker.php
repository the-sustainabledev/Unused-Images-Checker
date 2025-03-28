<?php
/**
 * Plugin Name: Unused Images Checker
 * Plugin URI: https://nicklewis.dev
 * Description: Lists media library images not used in posts, pages, ACF fields, or as featured images.
 * Version: 1.0.0
 * Author: Nick Lewis
 * Author URI:  https://nicklewis.dev
 */

if (!defined('ABSPATH')) exit;

// Add submenu under Media
add_action('admin_menu', function () {
	add_media_page(
		'Unused Images',
		'Unused Images',
		'manage_options',
		'unused-images',
		'uic_render_page'
	);
});


// Enqueue admin JS
add_action('admin_enqueue_scripts', function ($hook) {
	if ($hook !== 'media_page_unused-images') return;
	wp_enqueue_script('uic-admin', plugin_dir_url(__FILE__) . 'uic-admin.js', ['jquery'], '1.0', true);
	wp_localize_script('uic-admin', 'uic_ajax', [
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('uic_delete_image')
	]);
});

// AJAX handler for deleting images
add_action('wp_ajax_uic_delete_image', function () {
	check_ajax_referer('uic_delete_image', 'nonce');
	if (!current_user_can('delete_posts')) wp_send_json_error('Permission denied');

	$attachment_id = intval($_POST['id']);
	if (get_post_type($attachment_id) !== 'attachment') wp_send_json_error('Not an attachment');

	$deleted = wp_delete_attachment($attachment_id, true);
	if ($deleted) {
		wp_send_json_success();
	} else {
		wp_send_json_error('Delete failed');
	}
});


// handle bulk delete
add_action('admin_init', 'uic_handle_bulk_delete');

function uic_handle_bulk_delete() {
	if (
		isset($_POST['action']) &&
		$_POST['action'] === 'delete' &&
		!empty($_POST['uic_bulk_delete']) &&
		current_user_can('delete_posts')
	) {
		check_admin_referer('uic_bulk_delete_nonce');

		$deleted = 0;
		foreach ((array) $_POST['uic_bulk_delete'] as $id) {
			$id = intval($id);
			if (current_user_can('delete_post', $id) && get_post_type($id) === 'attachment') {
				if (wp_delete_attachment($id, true)) {
					$deleted++;
				}
			}
		}

		delete_transient('uic_unused_images');

		// Redirect early before output starts
		wp_safe_redirect(add_query_arg('uic_deleted', $deleted, menu_page_url('unused-images', false)));
		exit;
	}
}


// render out the page in the admin
function uic_render_page() {
	$per_page = 3;

	$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
	$offset = ($paged - 1) * $per_page;

	$all_unused_images = uic_get_unused_images();
	$total = count($all_unused_images);
	$paged_images = array_slice($all_unused_images, $offset, $per_page);

	echo '<div class="wrap"><h1>Unused Images</h1>';

	// Show notice of deleted images, if there are any
	if (!empty($_GET['uic_deleted'])) {
		$deleted = intval($_GET['uic_deleted']);
		echo '<div class="notice notice-success is-dismissible"><p>';
		printf(__('%d image(s) deleted successfully.'), $deleted);
		echo '</p></div>';
	}

	if (empty($paged_images)) {
		echo '<p><strong>All images are in use 🎉</strong></p>';
		return;
	} else {
		if($total === 1){
			echo '<h2>There is '.$total.' image that is not in use.</h2>';
		} else {
			echo '<h2>There are '.$total.' images that are not in use.</h2>';
		}
		echo '<p>Please note: All associated crops and sizes of the image will be permenantly deleted.</p>';
	}

	echo '<form method="post">';
	wp_nonce_field('uic_bulk_delete_nonce');

	echo '<table class="wp-list-table widefat fixed striped media">
	<thead>
		<tr>
			<td id="cb" class="manage-column column-cb check-column">
				<input type="checkbox" id="cb-select-all" />
			</td>
			<th>Thumbnail</th>
			<th>Filename</th>
			<th>Upload Date</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>';

	foreach ($paged_images as $image) {
		$id = $image->ID;
		$thumb = wp_get_attachment_image($id, [80, 80]);
		$file = esc_html(basename(get_attached_file($id)));
		$date = esc_html($image->post_date);
		$edit_url = get_edit_post_link($id);

		echo "<tr id='uic-row-$id'>
			<th scope='row' class='check-column'>
				<input type='checkbox' name='uic_bulk_delete[]' value='" . esc_attr($id) . "' />
			</th>
			<td>$thumb</td>
			<td>$file</td>
			<td>$date</td>
			<td>
				<a class='button' href='" . esc_url($edit_url) . "' target='_blank' title='View this image in the media library'>View in library</a>
				" . uic_render_delete_button($id) . "
			</td>
		</tr>";

	}

	echo '</tbody></table>';

	echo '<div class="tablenav bottom">';
		echo '<div class="alignleft actions bulkactions">';
			echo '<select name="action">';
				echo '<option value="-1">Bulk Actions</option>';
				echo '<option value="delete">Delete</option>';
			echo '</select>';
			submit_button('Apply', 'action', false, false);
		echo '</div>';

		// Pagination
		$total_pages = ceil($total / $per_page);

		echo '<div class="tablenav-pages">';
			echo '<span class="displaying-num">' . esc_html($total) . ' items</span>';

			if ($total_pages > 1) {
				$page_links = paginate_links([
					'base'      => add_query_arg('paged', '%#%'),
					'format'    => '',
					'prev_text' => '«',
					'next_text' => '»',
					'total'     => $total_pages,
					'current'   => $paged,
					'type'      => 'array',
				]);

				echo '<span class="pagination-links">';

				// Previous Page
				if ($paged > 1) {
					echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $paged - 1)) . '"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>';
				} else {
					echo '<span class="tablenav-pages-navspan button disabled"><span aria-hidden="true">‹</span></span>';
				}

				// Page Number Input
				echo '<span id="table-paging" class="paging-input">';
					echo '<span class="tablenav-paging-text">';
						echo '<span class="current-page">' . esc_attr($paged) . '</span>';
						echo ' of <span class="total-pages">' . esc_html($total_pages) . '</span>';
					echo '</span>';
				echo '</span>';

				// Next Page
				if ($paged < $total_pages) {
					echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $paged + 1)) . '"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>';
				} else {
					echo '<span class="tablenav-pages-navspan button disabled"><span aria-hidden="true">›</span></span>';
				}

				echo '</span>'; // .pagination-links
			}
			echo '</div>'; // .tablenav-pages
		echo '</div>'; // .tablenav
	echo '</form>';

	echo '</div>';
}


// render the delete button with checks for capabilities
function uic_render_delete_button($id) {
	if (current_user_can('edit_posts')) {
		return "<button class='button uic-delete-btn' data-id='$id' title='Delete the image from the media library'>Delete</button>";
	} else {
		return "<button class='button disabled' disabled title='You do not have permission to delete images.'>Delete</button>";
	}
}


function uic_get_unused_images() {
	global $wpdb;

	$all_images = get_posts([
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'numberposts'    => -1,
		'fields'         => 'ids',
	]);

	if (empty($all_images)) return [];

	// Check all post content
	$post_ids = $wpdb->get_col("
		SELECT ID FROM $wpdb->posts
		WHERE post_type NOT IN ('revision', 'nav_menu_item')
		AND post_status IN ('publish', 'draft', 'private')
	");

	$used_ids_in_content = [];
	foreach ($post_ids as $post_id) {
		$content = get_post_field('post_content', $post_id);
		if (preg_match_all('/wp-image-(\d+)/', $content, $matches)) {
			$used_ids_in_content = array_merge($used_ids_in_content, $matches[1]);
		}
	}

	// Featured images
	$featured_image_ids = $wpdb->get_col("
		SELECT meta_value FROM $wpdb->postmeta
		WHERE meta_key = '_thumbnail_id'
	");

	// ACF image fields
	function uic_get_used_attachment_ids_from_meta() {
		global $wpdb;
		$results = $wpdb->get_results("
			SELECT meta_value FROM $wpdb->postmeta
		");

		$attachment_ids = [];

		foreach ($results as $row) {
			$value = maybe_unserialize($row->meta_value);
			$attachment_ids = array_merge($attachment_ids, uic_extract_attachment_ids($value));
		}

		return array_unique(array_filter($attachment_ids));
	}

	function uic_extract_attachment_ids($value) {
		$found = [];

		if (is_numeric($value) && get_post_type($value) === 'attachment') {
			$found[] = (int) $value;
		} elseif (is_array($value) || is_object($value)) {
			foreach ((array) $value as $item) {
				$found = array_merge($found, uic_extract_attachment_ids($item));
			}
		}

		return $found;
	}
	$acf_image_ids = uic_get_used_attachment_ids_from_meta();

	// Combine all used IDs
	$used_ids = array_map('intval', array_unique(array_merge(
		$used_ids_in_content,
		$featured_image_ids,
		$acf_image_ids
	)));

	$unused = array_diff($all_images, $used_ids);

	if (empty($unused)) return [];

	return get_posts([
		'post_type' => 'attachment',
		'posts_per_page' => -1,
		'post__in' => $unused,
		'orderby' => 'post_date',
		'order' => 'DESC',
	]);
}
