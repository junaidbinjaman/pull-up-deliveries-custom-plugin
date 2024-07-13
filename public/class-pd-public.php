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
}
