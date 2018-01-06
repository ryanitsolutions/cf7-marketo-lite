<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Marketo Lite DB Class
 * 
 * @author Ryan IT Solutions
 * @version since 1.0
 */

class CF7_MarketoLite_DB {

	public static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct(){

		// Leads Describe
		add_action( 'cf7mkto_save_leads_describe', array( $this, 'save_leads_describe' ), 10, 1 );
		// Marketo List
		add_action( 'cf7mkto_save_marketo_list', array( $this, 'save_marketo_list' ));

		// Reset Table
		add_action( 'cf7mkto_db_reset_table', array( $this, 'db_reset_table' ), 10, 1 );

		//Get Data
		add_filter( 'cf7mkto_get_describe_fields', array( $this, 'get_describe_fields' ), 10, 1 );
		add_filter( 'cf7mkto_check_exist_field', array( $this, 'check_exist_field' ), 10, 1 );
		
		//Check marketo data is exist
		add_action( 'admin_init', array( 'CF7_MarketoLite_DB', 'check_marketo_describe_data' ) );
		add_action( 'admin_init', array( $this, 'check_marketo_list_data' ) );


	}

	/**
	 *  Marketo Save Leads Describe into Database
	 *
	 * @param array $query
	 * @return void
	 *
	 */

	public function save_leads_describe( $query ){

		global $wpdb;

		$db_table = $wpdb->prefix . 'marketo_leads_describe';

		$wpdb->insert( 
				$db_table , 
				array( 
					'display_name' 	=> esc_sql( $query [ 'display_name' ] ) , 
					'data_type' 	=> esc_sql( $query [ 'data_type' ] ) , 
					'length' 		=> esc_sql( $query [ 'length' ] ) , 
					'rest_name' 	=> esc_sql( $query [ 'rest_name' ] ) , 
					'rest_read_only' => esc_sql( $query [ 'rest_read_only' ] ) , 
					'rest_id' 		=> esc_sql( $query [ 'rest_id' ] ) , 
					'enable' 		=> esc_sql( $query [ 'enable' ] ) , 
				), 
				array( 
					'%s', 
					'%s', 
					'%s', 
					'%s', 
					'%d', 
					'%d',
					'%d', 
				) 
			);

	}

	/**
	 * WP Reset Table Data
	 *
	 * @param string $table_name
	 * @return void
	 *
	 */

	public function db_reset_table( $table_name ){
		global $wpdb;

		if( ! empty( $table_name ) ){
			$table  = $wpdb->prefix . $table_name;
			$wpdb->query( "TRUNCATE TABLE $table" );
		}
		
	}

	/**
	 * Plugin DB Table Installer
	 *
	 * @return void
	 *
	 */

	public static function db_installer(){
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$charset_collate 	= $wpdb->get_charset_collate();

		// Markrto Leads
   		$describe 	 		= $wpdb->prefix . "marketo_leads_describe"; 
   		$sql_describe 		= "CREATE TABLE IF NOT EXISTS $describe (
							  `id` bigint(20) NOT NULL AUTO_INCREMENT,
							  `display_name` varchar(200) NOT NULL,
							  `data_type` varchar(50) NOT NULL,
							  `length` varchar(20) NOT NULL,
							  `rest_name` varchar(200) NOT NULL,
							  `rest_read_only` tinyint(1) NOT NULL,
							  `rest_id` bigint(20) NOT NULL,
							  `enable` tinyint(1) NOT NULL,
							   KEY (`rest_name`),
							   PRIMARY KEY (`id`)
							) $charset_collate;";
   		
		dbDelta( $sql_describe );

	}


	/**
	 * Get Marketo Fields Describe info
	 *
	 * @param string $data_type
	 * @return array 
	 *
	 */

	public function get_describe_fields( $data_type  = 'string' ){

		global $wpdb;

		$describe = $wpdb->prefix . "marketo_leads_describe"; 

		$leads = $wpdb->get_results( 
						"
						SELECT id, display_name, rest_name 
						FROM $describe
						WHERE enable = '1' AND data_type = '". esc_sql( $data_type ) ."'
						"
					);

		return $leads;

	}

	/**
	 * Check DB Marketo Describe Field 
	 * 
	 * @param string $rest_name
	 * @return array
	 *
	 */

	public function check_exist_field( $rest_name ){

		global $wpdb;

		$describe = $wpdb->prefix . "marketo_leads_describe"; 

		$leads = $wpdb->get_results( 
						"SELECT id, display_name, rest_name
						   FROM $describe  
						  WHERE enable = '1' 
						    AND rest_name = '". esc_sql( $rest_name ) ."'"
						);

		return $leads;
	}

	/**
	 * Marketo Save List
	 *
	 * @param string $$query
	 *
	 * @return ''
	 *
	 */

	public function save_marketo_list( $query ){
		global $wpdb;

		$db_table = $wpdb->prefix . 'marketo_list';

		$wpdb->insert( 
				$db_table , 
				array( 
					'mkto_list_id' 		=> esc_sql( $query [ 'mkto_list_id' ] ) , 
					'mkto_name' 		=> esc_sql( $query [ 'mkto_name' ] ) , 
					'mkto_programName' 	=> esc_sql( $query [ 'mkto_programName' ] ) , 
					'mkto_workspaceName'=> esc_sql( $query [ 'mkto_workspaceName' ] ) , 
					'mkto_createdAt' 	=> esc_sql( $query [ 'mkto_createdAt' ] ) , 
					'mkto_updatedAt' 	=> esc_sql( $query [ 'mkto_updatedAt' ] ) , 
				), 
				array( 
					'%d', 
					'%s', 
					'%s', 
					'%s', 
					'%s', 
					'%s',
				) 
			);
	}

	/**
	 * Marketo Get List or Program
	 *
	 * @return array
	 *
	 */

	public function get_marketo_list(){

		global $wpdb;

		$db_table = $wpdb->prefix . "marketo_list"; 

		$list = $wpdb->get_results( " SELECT * FROM $db_table " );

		return $list;
	}

	/**
	 * Get Marketo Lst by ID
	 *
	 * @param int $id
	 *
	 * @return array
	 *
	 */

	public function get_marketo_list_by_id( $id ){

		global $wpdb;

		$db_table = $wpdb->prefix . "marketo_list"; 

		$list = $wpdb->get_results( 
						"SELECT *
						   FROM $db_table  
						  WHERE mkto_list_id = '". esc_sql( $id ) ."'"
						);

		return $list;
	}

	public static function check_marketo_describe_data(){
		global $wpdb;

		$describe = $wpdb->prefix . "marketo_leads_describe"; 

		if( $wpdb->get_var("SHOW TABLES LIKE '$describe'") != $describe ) {
			self::db_installer();
			return;
		}

		$leads    =  $wpdb->get_var( "SELECT COUNT(*) FROM $describe" );

		if( $leads <= 0  ){

			$api_creds 				= WPCF7::get_option( 'cf7_marketo' );

			if( ! empty($api_creds) && array_key_exists( 'on' , $api_creds )){

				$marketo_id 	= $api_creds['on'][ 'marketo_munchkin_id' ];

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
			}

			
		}
		
	}

	public function check_marketo_list_data(){

		 $terms = get_terms( 'marketo_list', array( 'hide_empty' => false ) );

		 if( empty($terms) ){

		 	$api_creds 	= WPCF7::get_option( 'cf7_marketo' );

			if( ! empty($api_creds) && array_key_exists( 'on' , $api_creds )){

				$marketo_id 	= $api_creds['on'][ 'marketo_munchkin_id' ];

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

			}
		 			
		 }
		 
	}


}