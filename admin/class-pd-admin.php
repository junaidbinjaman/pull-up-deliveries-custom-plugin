<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://junaidbinjaman.com
 * @since      1.0.0
 *
 * @package    Pd
 * @subpackage Pd/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pd
 * @subpackage Pd/admin
 * @author     Junaid Bin Jaman <me@junaidbinjaman.com>
 */
class Pd_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param     string $plugin_name The name of this plugin.
	 * @param     string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pd_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pd_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pd-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pd_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pd_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/pd-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Retrieve the calculated shipping cost and store that in the php session storage.
	 *
	 * @return void
	 */
	public function update_shipping_cost() {
		check_ajax_referer( 'update_shipping_cost', 'nonce' );

		$shipping_cost = isset( $_POST['shipping_cost'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_cost'] ) ) : 10;

		WC()->session->set( 'custom_shipping_cost', $shipping_cost );

		WC()->cart->calculate_shipping();

		wp_send_json_success( $shipping_cost );
	}

	/**
	 * Load google map data from database and send them to the frontend
	 *
	 * @return void
	 */
	public function pd_load_maps_data() {
		check_ajax_referer( 'update_shipping_cost', 'nonce' );

		$all_options       = get_option( 'options', array() );
		$store_lat         = isset( $all_options['store_lat'] ) ? $all_options['store_lat'] : false;
		$store_lng         = isset( $all_options['store_lng'] ) ? $all_options['store_lng'] : false;
		$store_marker_msg  = isset( $all_options['store_msg'] ) ? $all_options['store_msg'] : false;
		$store_marker_icon = isset( $all_options['marker_icon_url'] ) ? $all_options['marker_icon_url'] : false;
		$store_marker_size = isset( $all_options['marker_icon_size'] ) ? $all_options['marker_icon_size'] : false;
		$country_code      = isset( $all_options['country_code'] ) ? $all_options['country_code'] : false;

		$customer_marker_url  = isset( $all_options['customer_icon_url'] ) ? $all_options['customer_icon_url'] : false;
		$customer_marker_size = isset( $all_options['customer_icon_size'] ) ? $all_options['customer_icon_size'] : false;
		$customer_marker_msg  = isset( $all_options['customer_marker_msg'] ) ? $all_options['customer_marker_msg'] : false;

		$store_map_data = array(
			'lat'              => $store_lat,
			'lng'              => $store_lng,
			'msg'              => $store_marker_msg,
			'marker_icon'      => $store_marker_icon,
			'marker_icon_size' => $store_marker_size,
			'country_code'     => $country_code,
		);

		$customer_location_data = array(
			'marker_url'  => $customer_marker_url,
			'marker_size' => $customer_marker_size,
			'msg'         => $customer_marker_msg,
		);

		$location_data = array( $store_map_data, $customer_location_data );

		wp_send_json_success( $location_data );
	}

	/**
	 * Initializes all the meta boxes for admin dashboard
	 *
	 * @return void
	 */
	public function meta_box_initializer() {
		$current_screen = get_current_screen()->id;

		if ( 'shop_order' !== $current_screen && 'woocommerce_page_wc-orders' !== $current_screen ) {
			return;
		}

		add_meta_box(
			'order_delivery_status',
			'Order Delivery Status',
			array( $this, 'order_delivery_status__callback' ),
			$current_screen,
			'side',
			'core'
		);
	}

	/**
	 * Order delivery address and status meta box
	 *
	 * @param object $post The post object.
	 * @return void
	 */
	public function order_delivery_status__callback( $post ) {
		$current_delivery_status   = get_post_meta( $post->get_id(), 'pd_order_delivery_status', true );
		$customer_delivery_address = get_post_meta( $post->get_id(), 'pd_customer_delivery_address', true );

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
		echo '<p>Delivery Address: ' . esc_html( $customer_delivery_address ) . '</p>';
		echo '<p>' . esc_html( get_post_meta( $post->get_id(), 'customer_lat_lng', true ) ) . '</p>';
		echo '</div>';
	}

	/**
	 * Save order delivery status
	 *
	 * @param int    $order_id The order id.
	 * @param object $order The order object.
	 * @return void
	 */
	public function save_order_delivery_status( $order_id, $order ) {
		if ( ! isset( $_POST['pd_delivery_status_nonce'] ) || ! wp_verify_nonce( $_POST['pd_delivery_status_nonce'], 'save_order_delivery_status' ) ) { // phpcs:ignore
			return;
		}

		if ( ! isset( $_POST['pd_delivery_status'] ) ) {
			return;
		}

		$selected_delivery_status = sanitize_text_field( wp_unslash( $_POST['pd_delivery_status'] ) );

		update_post_meta( $order_id, 'pd_order_delivery_status', $selected_delivery_status );
	}
}
