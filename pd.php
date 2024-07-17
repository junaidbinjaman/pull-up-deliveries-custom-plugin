<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://junaidbinjaman.com
 * @since             1.0.0
 * @package           Pd
 *
 * @wordpress-plugin
 * Plugin Name:       Pull Up Deliveries Custom Plugin
 * Plugin URI:        https://https://pullupdeliveries.net/plugin
 * Description:       This is a custom plugin developed explicitly for pull up deliveries.
 * Version:           1.0.0
 * Author:            Junaid Bin Jaman
 * Author URI:        https://junaidbinjaman.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pd
 * Domain Path:       /languages
 */

use Automattic\WooCommerce\Admin\Overrides\Order;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PD_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pd-activator.php
 */
function activate_pd() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pd-activator.php';
	Pd_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pd-deactivator.php
 */
function deactivate_pd() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pd-deactivator.php';
	Pd_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pd' );
register_deactivation_hook( __FILE__, 'deactivate_pd' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pd.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pd() {
	$plugin = new Pd();
	$plugin->run();
}
run_pd();

/**
 * Undocumented function
 *
 * @return void
 */
function update_shipping_cost() {
	check_ajax_referer( 'update_shipping_cost', 'nonce' );

	$shipping_cost = isset( $_POST['shipping_cost'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_cost'] ) ) : 10;

	WC()->session->set( 'custom_shipping_cost', $shipping_cost );

	WC()->cart->calculate_shipping();

	wp_send_json_success( $shipping_cost );
}

add_action( 'wp_ajax_update_shipping_cost', 'update_shipping_cost' );
add_action( 'wp_ajax_nopriv_update_shipping_cost', 'update_shipping_cost' );


/**
 * Undocumented function
 *
 * @param array $rates The shipping rates.
 * @param array $package The products.
 * @return array
 */
function change_rates( $rates, $package ) {

	$cost = WC()->session->get( 'custom_shipping_cost' );

	foreach ( $rates as $rate_key => $rate ) {
		$rate->label              = 'Delivery';
		$rates[ $rate_key ]->cost = $cost;
	}

	return $rates;
}

add_filter( 'woocommerce_package_rates', 'change_rates', 10, 2 );

add_action(
	'woocommerce_checkout_update_order_review',
	function ( $posted_detail ) {
		global $woocommerce;
		$packages = $woocommerce->cart->get_shipping_packages();

		foreach ( $packages as $package_key => $package ) {
			$session_key = 'shipping_for_package_' . $package_key;
			WC()->session->set( $session_key, null ); // Unset the session key.
		}

		// Trigger shipping rate recalculation.
		$woocommerce->cart->calculate_totals();
	}
);

/**
 * Undocumented function
 *
 * @param array $fields The billing fields.
 * @return array
 */
function custom_remove_billing_address_fields( $fields ) {

	unset( $fields['billing_address_2'] );
	unset( $fields['billing_city'] );
	unset( $fields['billing_postcode'] );
	unset( $fields['billing_country'] );
	unset( $fields['billing_state'] );
	$fields['billing_address_1']['required'] = false;

	return $fields;
}

add_filter( 'woocommerce_billing_fields', 'custom_remove_billing_address_fields' );

// Remove the "Ship to a different address?" checkbox.
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

/**
 * Undocumented function
 *
 * @return void
 */
function pd_load_maps_data() {
	check_ajax_referer( 'update_shipping_cost', 'nonce' );

	$all_options       = get_option( 'options', array() );
	$store_lat         = isset( $all_options['store_lat'] ) ? $all_options['store_lat'] : false;
	$store_lng         = isset( $all_options['store_lng'] ) ? $all_options['store_lng'] : false;
	$store_marker_msg  = isset( $all_options['store_msg'] ) ? $all_options['store_msg'] : false;
	$store_marker_icon = isset( $all_options['marker_icon_url'] ) ? $all_options['marker_icon_url'] : false;
	$store_marker_size = isset( $all_options['marker_icon_size'] ) ? $all_options['marker_icon_size'] : false;

	$customer_marker_url  = isset( $all_options['customer_icon_url'] ) ? $all_options['customer_icon_url'] : false;
	$customer_marker_size = isset( $all_options['customer_icon_size'] ) ? $all_options['customer_icon_size'] : false;
	$customer_marker_msg  = isset( $all_options['customer_marker_msg'] ) ? $all_options['customer_marker_msg'] : false;

	$store_map_data = array(
		'lat'              => $store_lat,
		'lng'              => $store_lng,
		'msg'              => $store_marker_msg,
		'marker_icon'      => $store_marker_icon,
		'marker_icon_size' => $store_marker_size,
	);

	$customer_location_data = array(
		'marker_url'  => $customer_marker_url,
		'marker_size' => $customer_marker_size,
		'msg'         => $customer_marker_msg,
	);

	$location_data = array( $store_map_data, $customer_location_data );

	wp_send_json_success( $location_data );
}

add_action( 'wp_ajax_pd_load_maps_data', 'pd_load_maps_data' );
add_action( 'wp_ajax_nopriv_pd_load_maps_data', 'pd_load_maps_data' );

/**
 * Undocumented function
 *
 * @return void
 */
function meta_box_initializer() {
	$current_screen = get_current_screen()->id;

	if ( 'shop_order' !== $current_screen && 'woocommerce_page_wc-orders' !== $current_screen ) {
		return;
	}

	add_meta_box(
		'order_delivery_status',
		'Order Delivery Status',
		'order_delivery_status__callback',
		$current_screen,
		'side',
		'core'
	);
}

/**
 * Undocumented function
 *
 * @param object $post The post object.
 * @return void
 */
function order_delivery_status__callback( $post ) {
	$current_delivery_status = get_post_meta( $post->get_id(), 'pd_order_delivery_status', true );

	wp_nonce_field( 'save_order_delivery_status', 'pd_delivery_status_nonce' );

	echo '<label>Select a status<label>';
	echo '<select name="pd_delivery_status">';
	echo '<option value="">Select</option>';
	echo '<option value="1" ' . selected( $current_delivery_status, '1', false ) . ' >Your Order Has Been Received</option>';
	echo '<option value="2" ' . selected( $current_delivery_status, '2', false ) . ' >We are making the order now</option>';
	echo '<option value="3" ' . selected( $current_delivery_status, '3', false ) . ' >Your Order Is Ready</option>';
	echo '<option value="4" ' . selected( $current_delivery_status, '4', false ) . ' >We finna dispatch the driver</option>';
	echo '<option value="5" ' . selected( $current_delivery_status, '5', false ) . ' >The driver is on the way!</option>';
	echo '<option value="6" ' . selected( $current_delivery_status, '6', false ) . ' >Well.. Ok Denn 🫡</option>';
	echo '</select>';

	echo '<div class="wrapper"><h2 style="padding: 0px; margin-top: 20px">Delivery Destination</h2>';
	echo '<p>' . esc_html( get_post_meta( $post->get_id(), 'customer_lat_lng', true ) ) . '</p>';
	echo '</div>';
}

add_action( 'add_meta_boxes', 'meta_box_initializer' );

/**
 * Undocumented function
 *
 * @param int    $order_id The order id.
 * @param object $order The order object.
 * @return void
 */
function save_order_delivery_status( $order_id, $order ) {
	if ( ! isset( $_POST['pd_delivery_status_nonce'] ) || ! wp_verify_nonce( $_POST['pd_delivery_status_nonce'], 'save_order_delivery_status' ) ) { // phpcs:ignore
		return;
	}

	if ( ! isset( $_POST['pd_delivery_status'] ) ) {
		return;
	}

	$selected_delivery_status = sanitize_text_field( wp_unslash( $_POST['pd_delivery_status'] ) );

	update_post_meta( $order_id, 'pd_order_delivery_status', $selected_delivery_status );
}

add_action( 'woocommerce_process_shop_order_meta', 'save_order_delivery_status', 10, 2 );


add_action(
	'init',
	function () {
		add_shortcode( 'order_delivery_step', 'order_delivery_step_shortcode' );
	}
);

/**
 * Shortcode callback function.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function order_delivery_step_shortcode( $atts ) {
	$order_id = isset( $_GET['order-id'] ) ? sanitize_text_field( wp_unslash( $_GET['order-id'] ) ) : '0'; // phpcs:ignore

	if ( '0' === $order_id ) {
		return 0;
	}

	$atts = shortcode_atts(
		array(
			'current_step' => 1,
		),
		$atts,
		'order_delivery_step'
	);

	$current_delivery_status = intval( $atts['current_step'] );

	$active_delivery_status = get_post_meta( $order_id, 'pd_order_delivery_status', true );

	$str = '<div class="pd-delivery-status-num" style="' . pd_get_color( $active_delivery_status, $current_delivery_status ) . '">' . $current_delivery_status . '</div>';

	return $str;
}

/**
 * Get color based on delivery status comparison.
 *
 * @param int $active_delivery_status Active delivery status.
 * @param int $current_delivery_status Current delivery status.
 * @return string Background color.
 */
function pd_get_color( $active_delivery_status, $current_delivery_status ) {
	if ( $current_delivery_status < $active_delivery_status ) {
		return 'background-color: #ffffff; color: #000000';
	}

	if ( $current_delivery_status == $active_delivery_status ) {
		return 'background-color: #dd2928;';
	}

	if ( $current_delivery_status > $active_delivery_status ) {
		return 'background-color: #000000; color: #ffffff';
	}
}

add_action( 'woocommerce_thankyou', 'custom_order_confirmation_action', 10, 1 );

function custom_order_confirmation_action( $order_id ) {
	if ( ! $order_id ) {
		return;
	}

	$order_tracker_page = add_query_arg(
		array(
			'order-id' => $order_id,
		),
		home_url( '/order-tracker' )
	);

	wp_safe_redirect( $order_tracker_page );
}

function custom_checkout_field_before_order_notes( $checkout ) {

	woocommerce_form_field(
		'pd-customer-destination-lat-lng',
		array(
			'type'  => 'hidden',
			'class' => array( 'form-row-wide pd-customer-destination-lat-lng' ),
		),
		$checkout->get_value( 'custom_field' )
	);
}

add_action( 'woocommerce_before_order_notes', 'custom_checkout_field_before_order_notes' );

function save_pd_customer_destination_lat_lng( $order_id ) {
	$customer_destination_lat_lng = $_POST['pd-customer-destination-lat-lng'];

	update_post_meta( $order_id, 'customer_lat_lng', $customer_destination_lat_lng );
}

add_action( 'woocommerce_checkout_update_order_meta', 'save_pd_customer_destination_lat_lng' );
