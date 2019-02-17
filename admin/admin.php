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

		if( $this->load_assets_by_page() === true ){
			// Scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this , 'css' ) );
		}

		// Tab
		add_filter( 'wpcf7_editor_panels', array( $this , 'editor_tab' ) );

		// Actions
		add_action( 'save_post_wpcf7_contact_form', array( $this, 'set_post_type_settings' ), 10, 3  );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
        add_action( 'admin_footer', array( $this, 'admin_script_footer' ) );



	}

	public function load_assets_by_page(){

		$pages = array(
			'wpcf7',
			'wpcf7-new',
			'wpcf7-integration'
		);

		$page = ! empty($_REQUEST[ 'page' ]) ? $_REQUEST[ 'page' ] : '';

		if( in_array( $page, $pages) ){
			return true;
		}

		return false;

	}

	public function scripts(){
		
		$screen = $_REQUEST;

		wp_enqueue_script( 'marketo-scripts',  plugins_url( 'assets/js/tag-generator.js', __FILE__ ), '', '', true );
		wp_enqueue_script( 'marketo-select2',  'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', '', '', true );


	}

	public function css(){
				
		wp_register_style( 'mkto.admin.style',  plugins_url( 'assets/css/marketo-admin.css' , __FILE__ ) );
		wp_register_style( 'mkto.admin.select2.style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' );
		wp_enqueue_style( 'mkto.admin.style' );
		wp_enqueue_style( 'mkto.admin.select2.style' );
			
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
	               <select name="marketo_form_list" id="marketo_form_list" class="marketo_select_list" style="width: 100%;">
	                    <option value=""><?php echo esc_html( 'Please select...   ', 'contact-form-7' );?></option>
	                    <?php if( ! empty($marketo_list)): ?>
		                    <?php foreach ( $marketo_list as $key => $value ): ?>
		                    	<option value="<?php echo esc_attr($key);?>" <?php selected( $key, $marketo_form_list_id ); ?>><?php 
		                    		$mkto_id = get_option( 'mkto_taxonomy_term_list_' . $key  );
		                    		$name = str_replace( '('.$mkto_id.')', '', $value);
		                    	echo esc_html__($name);?></option>
		                    <?php endforeach;?>
	                    <?php endif;?>
	                </select>
	            </td>
	        </tr>

	        <tr>
	        	<td class="marketo-th-label-1" scope="row"><?php echo esc_html__( 'Not in the list?', 'contact-form-7' ); ?></td>
	            <td class="marketo-th-label-2"> <a href="javascript:;" class="button marketo-addexisting-open-dialog"><?php echo __( 'Search/Add', 'contact-form-7' )?></a>
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

	public function admin_script_footer(){

      if( ! empty( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'wpcf7'  ){
         $nonce = wp_create_nonce( "marketo_search_existing_list_nonce" );
        ?>
        <div id="marketo-addnew-dialog" class="hidden" style="max-width:800px">
          <div style="">
            <input type="text" name="marketo_exist_list_name" class="marketo_exist_list_name" size="50" placeholder="<?php echo __( 'Enter Marketo List Name' ) ?>">
          <button class="button button-primary marketo_add_exist_list_btn" data-nonce="<?php echo $nonce; ?>"  ><?php echo __( 'Search' )?></button>  
          </div>
          
          <p class="marketo_search_message"></p>
          <div class="marketo_search_existing_list"></div>
        </div>

          <script>
          jQuery( document ).ready( function( $ ) {
            $('#marketo-addnew-dialog').dialog({
              title: 'Search Marketo List Name',
              dialogClass: 'wp-dialog',
              autoOpen: false,
              draggable: false,
              width: 'auto',
              modal: true,
              resizable: false,
              closeOnEscape: true,
              position: {
                my: "center",
                at: "center",
                of: window
              },
              open: function () {
                $('.ui-widget-overlay').bind('click', function(){
                  $('#marketo-addnew-dialog').dialog('close');
                })
              },
              create: function () {
                $('.ui-dialog-titlebar-close').addClass('ui-button');
              },
            });
            $('.marketo-addexisting-open-dialog').click(function(e) {
              e.preventDefault();
              $('#marketo-addnew-dialog').dialog('open');
            });
          });
          </script>

        <?php
      }
    }

    public function admin_script(){

      if( ! empty( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'wpcf7'  ){
          wp_enqueue_script( 'jquery-ui-dialog' ); 
          wp_enqueue_style( 'wp-jquery-ui-dialog' );
      }
    }


}