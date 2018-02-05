<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Marketo Lite Admin Class
 * 
 * @author Ryan IT Solutions
 * @version since 1.0
 */

class CF7_MarketoLite_Admin
{
	
	public static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct(){

		$marketo_settings = WPCF7::get_option( 'cf7_marketo' );
		
		if( empty($marketo_settings) || $marketo_settings == null ) return;
		
		if( ! array_key_exists( 'on', $marketo_settings)) return;

		// Scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this , 'css' ) );

		// Tab
		add_filter( 'wpcf7_editor_panels', array( $this , 'editor_tab' ) );

		// Actions
		add_action( 'save_post_wpcf7_contact_form', array( $this, 'set_post_type_settings' ), 10, 3  );

	}

	public function scripts(){
		
		$screen = $_REQUEST;

		wp_enqueue_script( 'marketo-scripts',  plugins_url( 'assets/js/tag-generator.js', __FILE__ ), '', '', true );


	}

	public function css(){
				
		wp_register_style( 'mkto.admin.style',  plugins_url( 'assets/css/marketo-admin.css' , __FILE__ ) );
		wp_enqueue_style( 'mkto.admin.style' );
			
	}

	public static function editor_tab( $panels ){
		
		$new_page = array(
			'marketo' => array(
				'title' => __( 'Marketo', 'contact-form-7' ),
				'callback' => array(  'CF7_MarketoLite_Admin' , 'additional_tab_settings' )
			)
		);
		
		$panels = array_merge($panels, $new_page);

		return $panels;
		
	}

	public static function additional_tab_settings(){

		?>
		<div class="marketo-container">
	    <table id="marketo_metabox_settings" class="form-table">
	        <tr valign="top">
	            <td class="marketo-th-label-2" scope="row" colspan="2">
	            	<h2><?php esc_html_e( 'Marketo Form Settings', 'contact-form-7' ); ?></h2>
	            </td>
	        </tr>

	        <tr valign="top">
	            <td class="marketo-th-label-1" scope="row"><?php esc_html_e( 'Enable', 'contact-form-7' ); ?></td>
	            <td class="marketo-th-label-2">
	                <input type="checkbox" 
	                    name="marketo_enable_form" 
	                    id="marketo_enable_form"
	                    class="marketo-enable-form"
	                    <?php checked( get_post_meta( wp_kses_post($_REQUEST[ 'post' ]), 'marketo_enable_form', true ), 1 ); ?>
	                />
	            </td>
	        </tr>

	        <tr valign="top">
	            <td class="marketo-th-label-1" scope="row"><?php echo esc_html__( 'Marketo List', 'contact-form-7' ); ?></td>
	            <td class="marketo-th-label-2">
	            	<?php
	            		$marketo_list = apply_filters( 'cf7mkto_get_marketo_list', false );
	            		$marketo_form_list_id =  get_post_meta( wp_kses_post($_REQUEST[ 'post' ]), 'marketo_form_list', true );

	            	?>
	               <select name="marketo_form_list" id="marketo_form_list">
	                    <option value=""><?php echo esc_html( 'Please select...   ', 'contact-form-7' );?></option>
	                    <?php if( ! empty($marketo_list)): ?>
		                    <?php foreach ( $marketo_list as $key => $value ): ?>
		                    	<option value="<?php echo esc_attr($key);?>" <?php selected( $key, $marketo_form_list_id ); ?>><?php echo esc_html__($value);?></option>
		                    <?php endforeach;?>
	                    <?php endif;?>
	                </select>
	            </td>
	        </tr>

		    </table>
		</div> <!-- end of container -->

		<?php
	}

	public function set_post_type_settings( $post_id, $post, $update ){

		$post_type = get_post_type($post_id);

		// If this isn't a 'wpcf7_contact_form' post, don't update it.
    	if ( "wpcf7_contact_form" != $post_type ) return;

    	//echo "<pre>"; print_r($_POST); echo "</pre>";

    	if ( isset( $_POST['marketo_enable_form'] ) && $_POST['marketo_enable_form'] == 'on' ) {
	        update_post_meta( $post_id, 'marketo_enable_form', TRUE );
	    } else {
	        update_post_meta( $post_id, 'marketo_enable_form', FALSE );
	    }

	    if ( isset( $_POST['marketo_form_list'] ) ) {
	        update_post_meta( $post_id, 'marketo_form_list', sanitize_text_field( $_POST['marketo_form_list'] ) );
	    }

	}

	public static function action_links( $links){
		$plugin_links = array(
			'<a href="https://codecanyon.net/item/marketo-pro-for-contact-form-7/21201340" target="_blank">' . __( 'Go Pro' ) . '</a>',
			'<a href="https://codecanyon.net/item/marketo-pro-for-contact-form-7/21201340/support" target="_blank">' . __( 'Premium Support' ) . '</a>',
		);

	return array_merge( $plugin_links, $links );
	}


}