<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://junaidbinjaman.com
 * @since      1.0.0
 *
 * @package    Pd
 * @subpackage Pd/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Pd
 * @subpackage Pd/public
 * @author     Junaid Bin Jaman <me@junaidbinjaman.com>
 */
class Pd_Public {

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
	 * @param    string $plugin_name The name of the plugin.
	 * @param    string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pd-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * The option input is generated with jet engine option page.
		 *
		 * The input contains the google map api;
		 */
		$all_options    = get_option( 'options', array() );
		$google_map_api = isset( $all_options['google-map-api-key'] ) ? $all_options['google-map-api-key'] : false;

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
		wp_enqueue_script(
			'google-maps-api',
			"https://maps.googleapis.com/maps/api/js?key={$google_map_api}&callback=initMap&libraries=places",
			array(),
			null,
			true // Load in footer.
		);

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/pd-public.js',
			array( 'jquery', 'google-maps-api' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'ajax_object',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'update_shipping_cost' ),
			)
		);
	}

	/**
	 * Add defer to google map script
	 *
	 * @param string $tag The script tag.
	 * @param string $handle The stript id.
	 * @return string The script tag.
	 */
	public function add_defer_attribute_to_google_maps_script( $tag, $handle ) {
		if ( 'google-maps-api' === $handle ) {
			return str_replace( ' src', ' defer="defer" src', $tag );
		}

		return $tag;
	}

	/**
	 * Update the local delivery cost
	 *
	 * The function retrieve the delivery cost from woocommerce session
	 * and update the delivery cost.
	 *
	 * @param array $rates The shipping rates.
	 * @param array $package The products.
	 * @return array
	 */
	public function change_rates( $rates, $package ) {

		$cost = WC()->session->get( 'custom_shipping_cost' );

		foreach ( $rates as $rate_key => $rate ) {
			if ( 'flat_rate' === $rate->method_id ) {
				$rate->label              = 'Local Delivery';
				$rates[ $rate_key ]->cost = $cost;
			}
		}

		return $rates;
	}

	/**
	 * The function reset the session key
	 *
	 * @param object $posted_detail Unknown.
	 * @return void
	 */
	public function reset_session_key( $posted_detail ) {
		global $woocommerce;
		$packages = $woocommerce->cart->get_shipping_packages();

		foreach ( $packages as $package_key => $package ) {
			$session_key = 'shipping_for_package_' . $package_key;
			WC()->session->set( $session_key, null ); // Unset the session key.
		}

		// Trigger shipping rate recalculation.
		$woocommerce->cart->calculate_totals();
	}

	/**
	 * Remove billing address fields
	 *
	 * @param array $fields The billing fields.
	 * @return array
	 */
	public function remove_billing_address_fields( $fields ) {

		unset( $fields['billing_address_2'] );
		unset( $fields['billing_city'] );
		unset( $fields['billing_postcode'] );
		unset( $fields['billing_country'] );
		unset( $fields['billing_state'] );
		$fields['billing_address_1']['required'] = false;

		return $fields;
	}

	/**
	 * Shortcode initializer.
	 *
	 * @return void
	 */
	public function shortcode_initializer() {
		add_shortcode( 'order_delivery_step', array( $this, 'order_delivery_step_shortcode' ) );
	}

	/**
	 * Shortcode callback function.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function order_delivery_step_shortcode( $atts ) {
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

		$str = '<div class="pd-delivery-status-num" style="' . $this->pd_get_color( $active_delivery_status, $current_delivery_status ) . '">' . $current_delivery_status . '</div>';

		return $str;
	}

	/**
	 * Get color based on delivery status comparison.
	 *
	 * @param int $active_delivery_status Active delivery status.
	 * @param int $current_delivery_status Current delivery status.
	 * @return string Background color.
	 */
	public function pd_get_color( $active_delivery_status, $current_delivery_status ) {
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

	/**
	 * Redirect users to the order tracking page after placing the order
	 *
	 * @param int $order_id The order id.
	 * @return void
	 */
	public function custom_order_confirmation_action( $order_id ) {
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

	/**
	 * A hidden input field to store location let lang
	 *
	 * @param object $checkout The checkout fields.
	 * @return void
	 */
	public function custom_checkout_field_before_order_notes( $checkout ) {

		woocommerce_form_field(
			'pd-customer-destination-lat-lng',
			array(
				'type'  => 'hidden',
				'class' => array( 'form-row-wide pd-customer-destination-lat-lng' ),
			),
			$checkout->get_value( 'custom_field' )
		);
	}

	/**
	 * Save customer destination's lat lng.
	 *
	 * @param int $order_id The order id.
	 * @return void
	 */
	public function save_pd_customer_destination_lat_lng( $order_id ) {
		$customer_destination_lat_lng = $_POST['pd-customer-destination-lat-lng']; // phpcs:ignore
		update_post_meta( $order_id, 'customer_lat_lng', $customer_destination_lat_lng );
	}
}
