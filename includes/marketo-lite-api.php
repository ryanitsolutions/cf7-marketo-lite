<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Marketo Lite API Class
 * 
 * @author Ryan IT Solutions
 * @version since 1.0
 */

class CF7_MarketoLite_API {

	protected $base_url; 
	protected $mkto_replace_id;
	protected $marketo_id;
	protected $client_id;
	protected $client_secret;
	protected $cf7_id;
	protected $workspace;
	protected $root_tpl_folder;
	protected $background_tpl_folder;
	protected $root_email_folder;

	protected $timeout;
	protected $sslverify;
	public $option_prefix = "cf7_mkto_id_";


	public static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function __construct(){
		
		$this->mkto_replace_id 	= 'marketo-account-id';
		$this->cf7_id 			= 'cf7_mkto_id_7770';
		$api_creds 				= WPCF7::get_option( 'cf7_marketo' );

		if( ! empty($api_creds) && array_key_exists( 'on' , $api_creds )){

			$this->marketo_id 	= $api_creds['on'][ 'marketo_munchkin_id' ];
			$this->client_id 	= $api_creds['on'][ 'marketo_client_id' ];
			$this->client_secret = $api_creds['on'][ 'marketo_client_secret' ];
			
		}
			
		$this->base_url 		= 'https://' .$this->mkto_replace_id. '.mktorest.com';	
		$this->sslverify 		= true;
		$this->timeout          = 90;

		// Marketo Tokens 
		add_action( 'cf7mkto_generate_identity_access_token', array( $this, 'generate_access_token' ) );
		add_filter( 'cf7mkto_get_access_token_creds', array( $this, 'get_access_token_creds' ));

		// Marketo Leads 
		add_filter( 'cf7mkto_get_leads_describe', array( $this, 'get_leads_describe' ), 10, 1 );
		add_filter( 'cf7mkto_get_leads_activities', array( $this, 'get_leads_activities' ), 10, 2 );
		add_filter( 'cf7mkto_get_leads', array( $this, 'get_leads' ), 10, 1 );
		add_filter( 'cf7mkto_get_lead', array( $this, 'get_lead' ), 10, 2 );
		add_action( 'cf7mkto_create_leads', array( $this, 'create_leads' ), 10, 1 );
		add_filter( 'cf7mkto_create_leads', array( $this, 'create_leads' ), 10, 1 );
		add_filter( 'cf7mkto_associate_lead', array( $this, 'lead_associate' ), 10, 2 );
		add_filter( 'cf7mkto_delete_lead', array( $this, 'delete_lead' ), 10, 1 );

		//Static Lists : Marketo Lists Controller
		add_filter( 'cf7mkto_get_list_records', array( $this, 'get_list_records' ), 10, 1 );

		// Add leads to Static List
		add_action( 'cf7mkto_add_lead_to_list', array( $this, 'add_lead_to_list' ), 10, 2 );
		add_filter( 'cf7mkto_add_lead_to_list', array( $this, 'add_lead_to_list' ), 10, 2 );

		// Paging Tokens
        add_filter( 'cf7mkto_get_pagingtoken', array( $this, 'get_pagingtoken' ) );
		
	}

	/**
     * Marketo API Create and Update Leads
     *
     * @param array $form_fields
     *
     * @return array
     *
     */


	public function create_leads( $form_fields ){

		$marketo_id = $this->marketo_id;

		if( empty($marketo_id)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = $base_url . '/rest/v1/leads.json';
		
		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ] ,
						  'Content-Type' => 'application/json'
						);

		if( empty($form_fields) && !is_array($form_fields))
				$form_fields = array();

		// Set Body Params
		$params = array(
			'action' => 'createOrUpdate',
			'lookupField' => 'email',
			'input' => array( $form_fields )
		);	

		
		// Fire
		$request = $this->call( $path, json_encode($params), $headers, 'POST' );

		//save to db_table_leads 

		return $request;

	}

	/**
	 * Marketo API Remote Post 
	 *
	 * @param string $path
	 * @param array $params
	 * @param array $hraders
	 * @param string $method
	 *
	 * @return array | object
	 *
	 */

	public function call( $path = '' , $params = array(), $headers = array(), $method = 'POST' ){

	    $request = wp_remote_post( $path ,
	                    array(
	                        'method'    => $method,
	                        'headers'   => $headers,
	                        'body'      => $params,
	                        'timeout'   => $this->timeout,
	                        'sslverify' => $this->sslverify,
	                        'user-agent'    => 'CF7 Marketo Lite API Integration'
	                    ) );

		if ( is_wp_error( $request ) ) {
		    $result[ 'code' ] = false;
		    $result[ 'message' ] = $request->get_error_message();
		} else {
			$result[ 'code' ] 			= true;
			$result[ 'response_code' ] 	= $request['response']['code'];
			$result[ 'response_message' ] 	= $request['response']['message'];
			$result[ 'response_body' ] 		= json_decode($request['body']);
		}

	     return $result;

	}

	/**
	 * Generate Identity Access Token
	 *
	 * @return ''
	 */

	public function generate_access_token(){

		$marketo_id 	= $this->marketo_id;
		$client_id 		= $this->client_id;
		$client_secret 	= $this->client_secret;
		$cf7_id 		= $this->cf7_id;

		if( $client_id == '' )
			return;

		if( $client_secret == '' )
			return;		

		if( $cf7_id == '' )
			return;		


		$base_url = str_replace( $this->mkto_replace_id , $marketo_id , $this->base_url );

		$path = add_query_arg( 
			array(
				'grant_type' 	=> 'client_credentials',
				'client_id'  	=> $client_id,
				'client_secret' => $client_secret
			), $base_url . '/identity/oauth/token' );


		$request = $this->call( $path, 'GET' );

		if( $request[ 'response_code' ] == 200 && $request[ 'code' ] == 1 ){

			$creds = array(
				'access_token' 	=> $request[ 'response_body' ]->access_token,
				'token_type' 	=> $request[ 'response_body' ]->token_type,
				'expires_in' 	=> $request[ 'response_body' ]->expires_in,
				'scope' 		=> $request[ 'response_body' ]->scope,
				'token_due' 	=> ($request[ 'response_body' ]->expires_in + time()),

			);

			$this->update_access_token( $cf7_id, $creds );
		}

	}


	/**
	 * Save Token Credentials to WordPress Option
	 *
	 * @param string $cf7_id
	 * @param array $creds
	 *
	 * @return ''
	 */

	public function update_access_token( $cf7_id = '',  $creds = array() ){

		if($cf7_id == '' )
			return;

		if(empty($creds))
			return;

		if ( get_option( $this->option_prefix . $cf7_id ) !== false ) {
			update_option( $this->option_prefix . $cf7_id, $creds );
		} else {
			$deprecated = null;
		    $autoload = 'no';
		    add_option( $this->option_prefix . $cf7_id , $creds, $deprecated, $autoload );
		}

	}

	/**
	 * Show Access Tokens from Wordpress Options
	 *
	 * @return array|object
	 */

	public function get_access_token_creds(){
		$cf7_id  = $this->cf7_id;	
		return get_option( $this->option_prefix . $cf7_id );
	}

	/**
	 * Check the token if expire
	 *
	 * @return boolean
	 */	

	public function validate_expire_access_token(){
		$results = $this->get_access_token_creds();

		if( ! empty($results) && ! empty($results[ 'token_due' ]) ){
			// Re-generate Token
			if( time() >= $results[ 'token_due' ] ){
				return true;
			} 
		} 

		return false;
	}

	/**
	 * Marketo Get Leads by Filter Type and Filter Values
	 *
	 * @param int $params
	 *
	 * @return array | object
	 *
	 */

	public function get_leads( $params ){

		$marketo_id = $this->marketo_id;

		if( empty($marketo_id)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = add_query_arg( 
			array(
				'filterType' 	=> 'email',
				'filterValues' 	=> 'yahoo',
				'fields'		=> '',
				'batchSize' 	=> '10',
				'nextPageToken' => ''				
			), $base_url . '/rest/v1/leads.json' );
		
		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ] );
		
		// Set Body Params
		$params = array();	
		
		// Fire
		$request = $this->call( $path, $params, $headers, 'GET' );

		return $request;

	}


	/**
	 * Marketo Get Lead by ID
	 *
	 * @param int $lead_id
	 * @param array $fields
	 *
	 * @return array | object
	 *
	 */

	public function get_lead( $lead_id, $fields ){

		$marketo_id = $this->marketo_id;

		if( empty($marketo_id)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		if(empty($lead_id)) return;

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = add_query_arg( 
			array(
				'fields' 	=> implode(',', $fields)			
			),  $base_url . '/rest/v1/lead/'.$lead_id.'.json' );
		
		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ] );
		
		// Set Body Params
		$params = array();	
		
		// Fire
		$request = $this->call( $path, $params, $headers, 'GET' );

		return $request;

	}

	/**
	 * Marketo Lead Associate
	 *
	 * @param int $lead_id
	 * @param string $cookie
	 *
	 * @return array | object
	 *
	 */

	public function lead_associate( $lead_id, $cookie ){

		$marketo_id = $this->marketo_id;

		if( empty($marketo_id)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		if(empty($lead_id)) return;

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = add_query_arg( 
			array(
				'cookie' 	=> $cookie,
			), $base_url . '/rest/v1/lead/'.$lead_id.'.json' );
		
		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ] );
		
		// Set Body Params
		$params = array();	
		
		// Fire
		$request = $this->call( $path, $params, $headers, 'GET' );

		return $request;

	}	

	/**
	 * Marketo Leads Activities
	 *
	 * @param array $leadIds
	 *
	 */

	public function get_leads_activities( $leadIds , $nextPageToken = '' ){

		$marketo_id = $this->marketo_id;

		if( empty($marketo_id)) return;

		if( empty($nextPageToken)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = add_query_arg( 
			array(
				'nextPageToken' => $nextPageToken,
				//'activityTypeIds' => '1',
				'activityTypeIds' => '12',
				'fields' => 'firstName,lastName,department',
				'leadIds' 	=>  $leadIds,
			), $base_url . '/rest/v1/activities.json');
		
		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ] );
		
		// Set Body Params
		$params = array();	
		
		// Fire
		$request = $this->call( $path, $params, $headers, 'GET' );

		return $request;
	}

	/**
	 *  Marketo Leads Describe
	 *
	 * @retuen array | object
	 *
	 */

	public function get_leads_describe( $marketo_id = '' ){
		
		$marketo_id = ! empty($this->marketo_id) ? $this->marketo_id : $marketo_id ;
		
		if( empty($marketo_id)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );
		
		$path = $base_url . '/rest/v1/leads/describe.json';	
		
		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ] );
		
		// Set Body Params
		$params = array();	
		
		// Fire
		$request = $this->call( $path, $params, $headers, 'GET' );

		return $request;
	}

	/**
	 * Marketo Get List Record
	 *
	 * @return array
	 */


	public function get_list_records( $marketo_id = '' ){

		$marketo_id = ! empty($this->marketo_id) ? $this->marketo_id : $marketo_id;

		if ( empty($marketo_id)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = $base_url . '/rest/v1/lists.json';	

		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ] );

		// Set Body Params
		$params = array();	
		
		// Fire
		$request = $this->call( $path, $params, $headers, 'GET' );

		return $request;	
	}

	/**
	 * Marketo add lead to static list
	 *
	 * @param string $list_id
	 * @param array $data_ids
	 *
	 * @retuen array
	 *
	 */

	public function add_lead_to_list( $list_id, $data_ids = array() ) {

		$marketo_id = $this->marketo_id;

		if( empty($marketo_id)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		// Check if list_id is empty
		if( empty($list_id) )
			return;

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = $base_url . sprintf( '/rest/v1/lists/%1$s/leads.json', $list_id );	

		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ],
						  'Content-Type' => 'application/json' 
						);

		// Set Body Params
		$params = array(
			'input' => array( $data_ids )
		);
		
		// Fire
		$request = $this->call( $path, json_encode($params), $headers, 'POST' );
		
		return $request;	

	}

	
	public function get_pagingtoken(){

		$marketo_id = $this->marketo_id;

		if( empty($marketo_id)) return;

		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = add_query_arg( 
			array(
				'sinceDatetime' => date_i18n( 'Y-m-dTg:i:s', time() ),
			), $base_url . '/rest/v1/activities/pagingtoken.json');

		//echo "<pre>"; print_r($path); echo "</pre>";
		//exit;
		
		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ] );
		
		// Set Body Params
		$params = array();	
		
		// Fire
		$request = $this->call( $path, $params, $headers, 'GET' );

		return $request;


	}

	public function delete_lead( $id ){

		$marketo_id = $this->marketo_id;

		if( empty($marketo_id)) return;

		if( empty($id)) return;


		if( $this->validate_expire_access_token() === true ){
			// Re-Generate Token
			do_action( 'cf7mkto_generate_identity_access_token', true );
		}

		// Get Access Token
		$token = $this->get_access_token_creds();	

		$base_url = str_replace($this->mkto_replace_id, $marketo_id , $this->base_url );

		// Set API Path
		$path = add_query_arg( 
			array(
				'id' => $id 
			), $base_url . '/rest/v1/leads/delete.json' );

		
		// Set Access Token
		$headers = array( 'Authorization' => 'Bearer ' . $token[ 'access_token' ],
						  'Content-Type' => 'application/json' 
						);
		
		// Set Body Params
		$params = array(
				//'deleteLeadRequest' => array( 'input' => array( $id )  )
		);	
		
		// Fire
		$request = $this->call( $path, $params, $headers, 'POST' );

		return $request;

	}

	

}

