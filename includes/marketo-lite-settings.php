<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Marketo Lite Settings
 * 
 * @author Ryan IT Solutions
 * @version since 1.0
 */


class CF7_MarketoLite_Settings extends WPCF7_Service {

	protected static $instance = null;
	private $enable;
		
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}

	private function __construct() {
		$this->enable = WPCF7::get_option( 'cf7_marketo' );
	}

	public function get_title() {
		return __( 'Marketo Lite', 'contact-form-7' );
	}

	public function is_active() {
		$enable = $this->get_enable();
		$purchase_code = $this->get_purchase_code( $enable );

		return $enable && $purchase_code;
	}

	public function get_categories() {
		return array( 'marketo' );
	}

	public function icon() {
	}

	public function link() {
		echo sprintf( '<a href="%1$s" target="_blank">%2$s</a>',
			'https://codecanyon.net/item/marketo-pro-for-contact-form-7/21201340',
			'Go Pro' );
	}

	public function get_enable() {
		if ( empty( $this->enable ) || ! is_array( $this->enable ) ) {
			return false;
		}

		$enable = array_keys( $this->enable );

		return $enable[0];
	}

	public function get_purchase_code( $e ) {

		$enable = (array) $this->enable;

		if ( isset( $enable[$e] ) ) {
			return $enable[$e];
		} else {
			return false;
		}
	}

	private function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wpcf7-integration', false );
		$url = add_query_arg( array( 'service' => 'cf7_marketo' ), $url );

		if ( ! empty( $args) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	public function load( $action = '' ) {
		if ( 'setup' == $action ) {
			if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
				check_admin_referer( 'wpcf7-cf7_marketo-setup' );

				$enable = isset( $_POST['enable'] ) ? trim( $_POST['enable'] ) : '';
				$marketo_munchkin_id = isset( $_POST['marketo_munchkin_id'] ) ? trim( $_POST['marketo_munchkin_id'] ) : '';
				//$marketo_base_url = isset( $_POST['marketo_base_url'] ) ? trim( $_POST['marketo_base_url'] ) : '';
				$marketo_client_id = isset( $_POST['marketo_client_id'] ) ? trim( $_POST['marketo_client_id'] ) : '';
				$marketo_client_secret = isset( $_POST['marketo_client_secret'] ) ? trim( $_POST['marketo_client_secret'] ) : '';


				if ( $enable && $marketo_client_id  && $marketo_client_secret) {
					WPCF7::update_option( 'cf7_marketo', array( $enable => 
										array( 
											'marketo_munchkin_id' => $marketo_munchkin_id,
											'marketo_client_id' => $marketo_client_id,
											'marketo_client_secret' => $marketo_client_secret,

										)
						 ) );


					// Process the Marketo api calls
					$marketo_id 	  = $marketo_munchkin_id;

					do_action( 'cf7mkto_generate_identity_access_token', true );

					$tokens = apply_filters( 'cf7mkto_get_access_token_creds', true  );

					if( ! empty($tokens[ 'access_token' ])  && ! empty($tokens[ 'token_type' ])  && ! empty($tokens[ 'expires_in' ]) && ! empty($tokens[ 'scope' ]) && !empty($tokens[ 'token_due' ])){
						
						// Leads Describe
						$leads_describe = apply_filters( 'cf7mkto_get_leads_describe', $marketo_id );

						if( $leads_describe[ 'code' ] == 1 && $leads_describe[ 'response_code' ] == 200  ){
							$data_describe = $leads_describe[ 'response_body' ]->result;

							if( ! empty($data_describe) && is_array($data_describe)){

								$params = array();

								do_action( 'cf7mkto_db_reset_table', 'marketo_leads_describe' );

								$default_lead_describe = array(
									//'mktoName', //Full Name
									'firstName', //First Name
									'middleName', //Middle Name
									'lastName', //Last Name
									'email', //Email Address
									'phone', //Phone Number
									'mobilePhone', //Mobile Phone Number
									'fax', //Fax Number
									'company', // Company Name
									'title', //Job Title
									'contactCompany', //Contact Company
									'dateOfBirth', //Date of Birth
									'address', //Address
									'city', //City
									'country', //Country
									'postalCode',//Postal Code
									'state' // State
								);

								foreach ( $data_describe as $key => $value) {

									$params[ 'display_name' ] 	= ! empty($value->displayName) ? $value->displayName : '';
									$params[ 'data_type' ]	 	= ! empty($value->dataType) ? $value->dataType : '';
									$params[ 'length' ]			= ! empty($value->length) ? $value->length : '';
									$params[ 'rest_name' ]		= ! empty($value->rest->name) ? $value->rest->name : '';
									$params[ 'rest_read_only' ]	= ! empty($value->rest->readOnly) ? $value->rest->readOnly : '';
									$params[ 'rest_id' ]		= ! empty($value->id) ? $value->id : '';

									if( in_array($params[ 'rest_name' ], $default_lead_describe)){
										$params[ 'enable' ]		= true;
									} else {
										$params[ 'enable' ]		= false;
									}

									do_action( 'cf7mkto_save_leads_describe', $params );	

								}
								
							} 
							
						}

						// Marketo List

						$marketo_list = apply_filters( 'cf7mkto_get_list_records', $marketo_id );

						if( $marketo_list[ 'code' ] == 1 && $marketo_list[ 'response_code' ] == 200  ){
							$data_list = $marketo_list[ 'response_body' ]->result;

							//do_action( 'cf7mkto_db_reset_table', 'marketo_list' );

							$params = array();

							foreach ( $data_list as $key => $value) {

									$params[ 'mkto_list_id' ] 		= ! empty($value->id) ? $value->id : '';
									$params[ 'mkto_name' ]	 		= ! empty($value->name) ? $value->name : '';
									$params[ 'mkto_programName' ]	= ! empty($value->programName) ? $value->programName : '';
									$params[ 'mkto_workspaceName' ]	= ! empty($value->workspaceName) ? $value->workspaceName : '';
									$params[ 'mkto_createdAt' ]		= ! empty($value->createdAt) ? $value->createdAt : '';
									$params[ 'mkto_updatedAt' ]		= ! empty($value->updatedAt) ? $value->updatedAt : '';

									//do_action( 'cf7mkto_save_marketo_list', $params );	
									//do_action( 'cf7mkto_reset_marketo_list' );

									$t = term_exists( ucwords($params[ 'mkto_name' ]), 'marketo_list',0 );

									if(  $t == 0 || $t == null || empty($t) ){

										$term = wp_insert_term(
												  ucwords($params[ 'mkto_name' ]), // the term 
												  'marketo_list', // the taxonomy
												  array(
												    'description'=> $params[ 'mkto_name' ],
												    'slug' => strtolower($params[ 'mkto_name' ]),
												    'parent'=> 0  // get numeric term id
												  )
												);

										$t_id = $term[ 'term_taxonomy_id' ];
										update_option( "mkto_taxonomy_term_list_$t_id", $params[ 'mkto_list_id' ] );  
									}
									

								}
						}	

					

					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );

				} elseif ( '' === $enable  ) {

					WPCF7::update_option( 'cf7_marketo', array( '' => 
										array( 
											'marketo_munchkin_id' => $marketo_munchkin_id,
											'marketo_client_id' => $marketo_client_id,
											'marketo_client_secret' => $marketo_client_secret,

										)
						 ) );
					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );


				} elseif ( '' === $enable && '' === $marketo_munchkin_id ) {
					WPCF7::update_option( 'cf7_marketo', null );
					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );

				} elseif ( '' === $enable && '' === $marketo_client_id ) {
					WPCF7::update_option( 'cf7_marketo', null );
					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );
					
				} elseif ( '' === $enable && '' === $marketo_client_secret ) {
					WPCF7::update_option( 'cf7_marketo', null );
					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );				
				} else {
					$redirect_to = $this->menu_page_url( array(
						'action' => 'setup',
						'message' => 'invalid',
					) );
				}
					

				} else {
					$redirect_to = $this->menu_page_url( array(
						'action' => 'setup',
						'message' => 'invalid',
					) );
				}

				wp_safe_redirect( $redirect_to );
				exit();
			}
		}
	}

	public function admin_notice( $message = '' ) {
		if ( 'invalid' == $message ) {
			echo sprintf(
				'<div class="error notice notice-error is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "ERROR", 'contact-form-7' ) ),
				esc_html( __( "Invalid key values.", 'contact-form-7' ) ) );
		}

		if ( 'success' == $message ) {
			echo sprintf( '<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( __( 'Settings saved.', 'contact-form-7' ) ) );
		}
	}

	public function display( $action = '' ) {
?>
<p><?php echo esc_html( __( "Marketo Lite for Contact Form 7 extension.", 'contact-form-7' ) ); ?></p>

<?php
		if ( 'setup' == $action ) {
			$this->display_setup();
			return;
		}

		if ( $this->is_active() ) {
			$enable = $this->get_enable();
			$data = $this->get_purchase_code( $enable );
?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><?php echo esc_html( __( 'Enable', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( $enable ); ?></td>
</tr>

<tr>
	<th scope="row"><?php echo esc_html( __( 'Marketo Munchkin Account Id', 'contact-form-7' ) ); ?></th>
	<td><?php echo esc_html( $data[ 'marketo_munchkin_id' ] ); ?></td>
</tr>

<tr>
	<th colspan="2"><?php echo esc_html__( 'Marketo REST API:', 'contact-form-7' ) ?></th>
</tr>

<tr>
	<th scope="row"><?php echo esc_html( __( 'Client ID', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( wpcf7_mask_password( $data[ 'marketo_client_id' ] ) ); ?></td>
</tr>

<tr>
	<th scope="row"><?php echo esc_html( __( 'Client Secret', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( wpcf7_mask_password( $data[ 'marketo_client_secret' ] ) ); ?></td>
</tr>


</tbody>
</table>

<p><a href="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>" class="button"><?php echo esc_html( __( "Reset", 'contact-form-7' ) ); ?></a></p>

<?php
		} else {
?>
<p><?php echo esc_html( __( "To use Marketo Lite for Contact Form 7, you need to  enable and activate your Marketo REST API Credentials.", 'contact-form-7' ) ); ?></p>

<p><a href="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>" class="button"><?php echo esc_html( __( "Configure", 'contact-form-7' ) ); ?></a></p>

<p><?php echo sprintf( esc_html( __( "For more details, see %s", 'contact-form-7' ) ), 
	
	wpcf7_link( __( 'http://developers.marketo.com/rest-api/', 'contact-form-7' ), __( 'Marketo REST API ', 'contact-form-7' ) )

	 ); ?></p>

<?php
		}
	}

	public function display_setup() {


		$enable 				= 'on' ===  $this->get_enable();
		$data 					= $this->get_purchase_code( $this->get_enable() );
		$marketo_munchkin_id 	= ! empty( $data[ 'marketo_munchkin_id' ] ) ? $data[ 'marketo_munchkin_id' ] : '';
		$marketo_client_id 		= ! empty( $data[ 'marketo_client_id' ] ) ? $data[ 'marketo_client_id' ] : '';
		$marketo_client_secret 	= ! empty( $data[ 'marketo_client_secret' ] ) ? $data[ 'marketo_client_secret' ] : '';

?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
<?php wp_nonce_field( 'wpcf7-cf7_marketo-setup' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="enable"><?php echo esc_html( __( 'Enable', 'contact-form-7' ) ); ?></label></th>
	<td><input type="checkbox" aria-required="true"  id="enable" name="enable" class="regular-text code" <?php checked( $enable, 1 ); ?>/></td>
</tr>

<tr>
	<th><?php echo esc_html__( 'Marketo Munchkin Account Id:', 'contact-form-7' ) ?></th>
	<td><input type="text" aria-required="true" value="<?php echo $marketo_munchkin_id; ?>" id="marketo_munchkin_id" name="marketo_munchkin_id" class="regular-text code" placeholder="eg.123-ABC-456" /></td>
</tr>

<tr>
	<th colspan="2"><?php echo esc_html__( 'Marketo REST API:', 'contact-form-7' ) ?></th>
</tr>

<tr>
	<th scope="row"><label for="marketo_client_id"><?php echo esc_html( __( 'Client ID', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" aria-required="true" value="<?php echo $marketo_client_id; ?>" id="marketo_client_id" name="marketo_client_id" class="regular-text code" /></td>
</tr>

<tr>
	<th scope="row"><label for="marketo_client_secret"><?php echo esc_html( __( 'Client Secret', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" aria-required="true" value="<?php echo $marketo_client_secret;?>" id="marketo_client_secret" name="marketo_client_secret" class="regular-text code" /></td>
</tr>


</tbody>
</table>

<p class="submit"><input type="submit" class="button button-primary" value="<?php echo esc_attr( __( 'Save', 'contact-form-7' ) ); ?>" name="submit" /></p>
</form>
<?php
	}
}

function wpcf7_marketo_lite_register_service() {
	$integration = WPCF7_Integration::get_instance();

	$categories = array(
		'marketo' => __( 'Marketo', 'contact-form-7' ),
	);

	foreach ( $categories as $name => $category ) {
		$integration->add_category( $name, $category );
	}

	$services = array(
		'cf7_marketo' => CF7_MarketoLite_Settings::instance(),
	);

	foreach ( $services as $name => $service ) {
		$integration->add_service( $name, $service );
	}
}	