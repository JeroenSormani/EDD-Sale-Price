<?php
/**
 * Plugin Name: 	EDD Sale Price
 * Plugin URI:		http://jeroensormani.com
 * Description:		Put your digital products on sale.
 * Version: 		1.0.2
 * Author:			Jeroen Sormani
 * Author URI: 		http://www.jeroensormani.com/
 * Text Domain: 	edd-sale-price
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class EDD_Sale_Price.
 *
 * Main EDD_Sale_Price class initializes the plugin.
 *
 * @class		EDD_Sale_Price
 * @version		1.0.0
 * @author		Jeroen Sormani
 */
class EDD_Sale_Price {


	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string $version Plugin version number.
	 */
	public $version = '1.0.2';


	/**
	 * Plugin file.
	 *
	 * @since 1.0.0
	 * @var string $file Plugin file path.
	 */
	public $file = __FILE__;


	/**
	 * Instace of EDD_Sale_Price.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var object $instance The instance of EDD_Sale_Price.
	 */
	private static $instance;


	/**
	 * Construct.
	 *
	 * Initialize the class and plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Initialize plugin parts
		$this->init();

	}


	/**
	 * Instance.
	 *
	 * An global instance of the class. Used to retrieve the instance
	 * to use on other files/plugins/themes.
	 *
	 * @since 1.0.0
	 * @return object Instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) :
			self::$instance = new self();
		endif;

		return self::$instance;

	}


	/**
	 * Init.
	 *
	 * Initialize plugin parts.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Load textdomain
		$this->load_textdomain();

		/**
		 * Sale price class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-eddsp-sale-price.php';
		$this->price = new EDDSP_Sale_Price();

		if ( is_admin() ) :

			/**
			 * Admin product class
			 */
			require_once plugin_dir_path( __FILE__ ) . '/includes/admin/class-eddsp-admin-product.php';
			$this->admin_product = new EDDSP_Admin_Product();

		endif;

	}


	/**
	 * Textdomain.
	 *
	 * Load the textdomain based on WP language.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {

		// Load textdomain
		load_plugin_textdomain( 'edd-sale-price', false, basename( dirname( __FILE__ ) ) . '/languages' );

	}


}


/**
 * The main function responsible for returning the EDD_Sale_Price object.
 *
 * Use this function like you would a global variable, except without needing to declare the global.
 *
 * Example: <?php EDD_Sale_Price()->method_name(); ?>
 *
 * @since 1.0.0
 *
 * @return object EDD_Sale_Price class object.
 */
if ( ! function_exists( 'EDD_Sale_Price' ) ) :

 	function EDD_Sale_Price() {
		return EDD_Sale_Price::instance();
	}

endif;

EDD_Sale_Price();
