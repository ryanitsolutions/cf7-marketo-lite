<?php
/*
Plugin Name: Marketo Lite for Contact Form 7
Description: Marketo Lite for Contact Form 7 extension that can generate leads, tracking and push data into marketo server.
Author: Ryan IT Solutions
Version: 1.1
*/


if ( ! defined( 'ABSPATH' ) ) exit;

// check to make sure contact form 7 is installed and active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )) {

if ( ! class_exists( 'WPCF7_Service' ) ) {
	include_once ( plugin_dir_path( dirname( __FILE__) ) . '/contact-form-7/includes/integration.php');
}

if ( is_plugin_active( 'marketo-pro-for-cf7/marketo-pro-for-cf7.php' )) {
	return;
}

final class CF7_MarketoLite
{
	public static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct(){
		
		$this->includes();

		// Initialize
		add_action( 'wpcf7_init', 'wpcf7_marketo_lite_register_service' );
		add_action( 'plugins_loaded', array( 'CF7_MarketoLite_API' , 'instance' ) );
		add_action( 'plugins_loaded', array( 'CF7_MarketoLite_DB' , 'instance' ) );
		add_action( 'plugins_loaded', array( 'CF7_MarketoLite_Form_Text' , 'instance' ) );

		// Admin
		add_action( 'plugins_loaded', array( 'CF7_MarketoLite_Admin' , 'instance' ) );

		// Contact Form 7 
		add_action( 'wpcf7_before_send_mail', array( 'CF7_MarketoLite_Core', 'generate_leads' ) );
		add_action( 'wp_footer', array( 'CF7_MarketoLite_Core', 'tracking_code' ) );

		// Hooks 
		add_action( 'cf7mkto_save_custom_meta', array( 'CF7_MarketoLite_Core' , 'save_custom_meta' ), 10, 2 );

		// Intall Request a Quote DB tables
		register_activation_hook( __FILE__, array( 'CF7_MarketoLite_DB', 'db_installer' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(  'CF7_MarketoLite_Admin' , 'action_links' ) );
	}

	public function includes(){

		include_once $this->plugin_path() . "/includes/marketo-lite-settings.php";
		include_once $this->plugin_path() . "/includes/marketo-lite-api.php";
		include_once $this->plugin_path() . "/includes/marketo-lite-db.php";
		include_once $this->plugin_path() . "/includes/marketo-lite-form-text.php";	
		include_once $this->plugin_path() . "/includes/marketo-lite-core.php";	

		// Admin
		include_once $this->plugin_path() . "/admin/admin.php";	
	}

	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

function CF7MktoLite(){
	return CF7_MarketoLite::instance();
}

// Marketo Pro Fire 
CF7MktoLite();

} 

else {	

	function check_for_cf7(){
	// give warning if contact form 7 is not active
		?>
			<div class="error">
				<p><?php _e( '<b>Marketo Lite for Contact Form 7:</b> Contact Form 7 Plugin is not installed and / or In-active! ', 'contact-form-7' ); ?></p>
			</div>
		<?php		
	}

	add_action( 'admin_notices', 'check_for_cf7' );
}



