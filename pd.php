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

add_filter( 'woocommerce_billing_fields', 'custom_remove_billing_address_fields' );

/**
 * Undocumented function
 *
 * @param array $fields The billing fields.
 * @return array
 */
function custom_remove_billing_address_fields( $fields ) {

	unset( $fields['billing_address_1'] );
	unset( $fields['billing_address_2'] );
	unset( $fields['billing_city'] );
	unset( $fields['billing_postcode'] );
	unset( $fields['billing_country'] );
	unset( $fields['billing_state'] );

	return $fields;
}

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
