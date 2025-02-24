<?php
// helpers

function delete_post_thumbnail_and_file($post_id)
{
	// Get thumbnail ID
	$thumbnail_id = get_post_thumbnail_id($post_id);

	if ($thumbnail_id) {
		// Remove thumbnail association
		delete_post_thumbnail($post_id);

		// Delete the attachment and file
		wp_delete_attachment($thumbnail_id, true);  // true means force delete file
	}
}


function upload_image_from_url($image_url)
{
	$image = wp_remote_get($image_url);

	if (is_wp_error($image)) {
		error_log('Error fetching image: ' . $image_url);
		return $image;
	}

	$image_body = wp_remote_retrieve_body($image);
	$image_name = basename($image_url);

	$upload = wp_upload_bits($image_name, null, $image_body);

	if (!$upload['error']) {
		$wp_filetype = wp_check_filetype($upload['file']);
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($image_name),
			'post_content' => '',
			'post_status' => 'inherit',
		);

		$attach_id = wp_insert_attachment($attachment, $upload['file']);
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
		wp_update_attachment_metadata($attach_id, $attach_data);

		error_log('Image uploaded: ' . $image_url . ' (Attachment ID: ' . $attach_id . ')');

		return $attach_id;
	}

	error_log('Error uploading image: ' . $image_url . '. Error: ' . $upload['error']);
	return new WP_Error('upload_error', $upload['error']);
}


function get_image_by_url($image_url)
{
	$args = array(
		'post_type' => 'attachment',
		'meta_query' => array(
			array(
				'key' => '_wp_attached_file',
				'value' => basename($image_url),
				'compare' => 'LIKE'
			),
		),
		'numberposts' => 1,
	);

	$existing_image = get_posts($args);

	if ($existing_image) {
		return $existing_image[0]->ID;
	}

	return false;
}
