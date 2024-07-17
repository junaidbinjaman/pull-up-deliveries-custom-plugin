<?php
add_action( 'add_meta_boxes', 'meta_box_init__callback' );

add_action( 'add_meta_boxes', 'meta_box_init__callback' );

function meta_box_init__callback() {
	$current_screen = get_current_screen()->id;

	if ( 'shop_order' === $current_screen || 'woocommerce_page_wc-orders' ) {
		add_meta_box(
			'pd_delivery_manager',
			'Delivery Manager',
			'order_delivery_manager_callback',
			$current_screen,
			'side',
			'core'
		);
	}
}

function order_delivery_manager_callback( $post ) {
	$order_delivery_status = get_post_meta( $post->get_id(), 'order_delivery_status', true );

	// Add a nonce field for security.
	wp_nonce_field( 'save_order_delivery_status', 'order_delivery_status_nonce' );

	echo '<select name="order_delivery_status">';
	echo '<option value="">Select</option>';
	echo '<option value="1"' . selected( $order_delivery_status, '1', false ) . '>Your Order Has Been Received</option>';
	echo '<option value="2"' . selected( $order_delivery_status, '2', false ) . '>We are making the order now</option>';
	echo '<option value="3"' . selected( $order_delivery_status, '3', false ) . '>Your Order Is Ready</option>';
	echo '<option value="4"' . selected( $order_delivery_status, '4', false ) . '>We finna dispatch the driver</option>';
	echo '<option value="5"' . selected( $order_delivery_status, '5', false ) . '>The driver is on the way!</option>';
	echo '<option value="6"' . selected( $order_delivery_status, '6', false ) . '>Well.. Ok Denn 🫡</option>';
	echo '</select>';
}

add_action( 'woocommerce_process_shop_order_meta', 'save_wc_order_custom_meta', 10, 2 );

function save_wc_order_custom_meta( $post_id, $post ) {
	// Verify nonce for security.
	if ( ! isset( $_POST['order_delivery_status_nonce'] ) || ! wp_verify_nonce( $_POST['order_delivery_status_nonce'], 'save_order_delivery_status' ) ) {
		return;
	}

	// Check if the custom field is set.
	if ( isset( $_POST['order_delivery_status'] ) ) {
		// Sanitize user input.
		$order_delivery_status = sanitize_text_field( $_POST['order_delivery_status'] );

		// Update the meta field in the database.
		update_post_meta( $post_id, 'order_delivery_status', $order_delivery_status );
	}
}