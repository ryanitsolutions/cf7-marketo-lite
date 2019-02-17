<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Marketo AJAX Class
 * 
 * @author Ryan IT Solutions
 * @version since 1.0
 */

class CF7_Marketo_Ajax
{

	public static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct(){
	
		add_action( 'init', array( $this, 'marketo_script_enqueuer' ) ) ;	
		//add_action( 'wp_ajax_nopriv_marketo-search-existing-list', array( $this, 'search_existing_list' ) );
		add_action( 'wp_ajax_marketo-search-existing-list', array( $this, 'search_existing_list') );
		add_action( 'wp_ajax_marketo-add-existing-list', array( $this, 'add_existing_list') );
		add_action( 'wp_ajax_nopriv_marketo-search-existing-list', array( $this, 'add_existing_list' ) );
	}


	public function marketo_script_enqueuer(){

		wp_register_script( 'marketo_script', plugins_url( 'admin/assets/js/marketo-admin.js', dirname(__FILE__) ), array('jquery') );
		wp_localize_script( 'marketo_script', 'marketoAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'marketo_script' );

	}

	public function search_existing_list(){

		if ( !wp_verify_nonce( $_REQUEST['nonce'], "marketo_search_existing_list_nonce")) {
	      exit;
	    }   

	   if( $marketo_list_name = sanitize_text_field($_REQUEST[ 'list_name' ] ) ) {

	   		$result = apply_filters( 'cf7mkto_get_list_by_name', '', $marketo_list_name   );

	   		if( ! empty($result[ 'response_body' ]->result ) && $result[ 'response_code' ] == 200 ){
	   			
	   			$data_list = $result[ 'response_body' ]->result;
	            $params = array();

	            $nonce = wp_create_nonce( "marketo_add_existing_list_nonce" );    

	            $table = '';
	            $table .='<table class="widefat">';    
	            $table .='<thead>';    
	            $table .='<tr>';    
	            $table .='<th scope="col"><span><strong>' .__( 'Marketo List Name' ) . '</strong></span></th>';    
	            $table .='<th scope="col"><span><strong>' .__( 'Marketo Program Name' ) . '</strong></span></th>';    
	            $table .='<th scope="col" size="20"><span><strong>' .__( 'Action' ) . '</strong></span></th>';    
	            $table .='</tr>';    
	            $table .='</thead>';    
	            $table .='<tbody>';    

	            foreach ($data_list as $key => $value) {

					$mkto_list_id       = ! empty($value->id) ? $value->id : '';
					$mkto_name      	= ! empty($value->name) ? $value->name : '';
					$mkto_programName   = ! empty($value->programName) ? $value->programName : '';
					$mkto_workspaceName = ! empty($value->workspaceName) ? $value->workspaceName : '';
					$mkto_createdAt 	= ! empty($value->createdAt) ? $value->createdAt : '';
					$mkto_updatedAt 	= ! empty($value->updatedAt) ? $value->updatedAt : '';

					//$name = sprintf( '%1$s (%2$s)', ucwords($mkto_name), $mkto_list_id );
					$name = ucwords($mkto_name);

                   	$table .='<tr>';    
		            $table .='<td class="column-primary" style="word-wrap: break-word;">' . __( $name ) . '</td>';    
		            $table .='<td style="word-wrap: break-word;">' . __( $mkto_programName ) . '</td>';    
		            $table .='<td><a href="javascript:;" class="button button-secondary add_marketo_list_name_action_btn" data-mkto_list_id="' . __( $mkto_list_id ) . '" data-mkto_name="' . __( $mkto_name ) . '" data-mkto_programname="' . __( $mkto_programName ) . '" data-mkto_workspacename="' . __( $mkto_workspaceName ) . '" data-mkto_createdat="' . __( $mkto_createdAt ) . '" data-mkto_updatedat="' . __( $mkto_updatedAt ) . '" data-nonce="'.$nonce.'" >Add</a> <span class="spinner" style=""></span></td>';    
		            $table .='</tr>';  
	            }
	            $table .='</tbody>';    

	            $table .='</table>';    

	   			$response[ 'response_code' ] = 1;
	   			$response[ 'response_body' ] = $table;
	   			$response[ 'response_message' ] = __( sprintf( 'Marketo list name %1$s record(s) found.', count($data_list) ) );

	   			

	   		} else {
	   			$response[ 'response_code' ] = 0;
	   			$response[ 'response_message' ] = __( 'Marketo list name not found!' );
	   		}

	   		echo wp_json_encode( $response );

	   } else {

	   		$response[ 'response_code' ] = 0;
	   		$response[ 'response_message' ] = __( 'Marketo list name not found!' );

	   		echo wp_json_encode( $response );
	   }
		
		exit;
	}

	public function add_existing_list(){

		if ( !wp_verify_nonce( $_REQUEST['nonce'], "marketo_add_existing_list_nonce")) {
	      exit;
	    }  

	    $mkto_list_id = sanitize_text_field( $_REQUEST[ 'mkto_list_id' ] );
	    $mkto_name = sanitize_text_field( $_REQUEST[ 'mkto_name' ] );
	    $mkto_programname = sanitize_text_field( $_REQUEST[ 'mkto_programname' ] );
	    $mkto_workspacename = sanitize_text_field( $_REQUEST[ 'mkto_workspacename' ] );
	    $mkto_createdat = sanitize_text_field( $_REQUEST[ 'mkto_createdat' ] );
	    $mkto_updatedat = sanitize_text_field( $_REQUEST[ 'mkto_updatedat' ] );

		$params[ 'mkto_list_id' ]     = ! empty($mkto_list_id) ? $mkto_list_id : '';
		$params[ 'mkto_name' ]        = ! empty($mkto_name) ? $mkto_name : '';
		$params[ 'mkto_programName' ] = ! empty($mkto_programname) ? $mkto_programname : '';
		$params[ 'mkto_workspaceName' ] = ! empty($mkto_workspacename) ? $mkto_workspacename : '';
		$params[ 'mkto_createdAt' ]   = ! empty($mkto_createdat) ? $mkto_createdat : '';
		$params[ 'mkto_updatedAt' ]   = ! empty($mkto_updatedat) ? $mkto_updatedatt : '';

		$name = sprintf( '%1$s (%2$s)', ucwords($params[ 'mkto_name' ]), $params[ 'mkto_list_id' ] );

		$t = term_exists( $name, 'marketo_list',0 );

		if(  $t == 0 || $t == null || empty($t) ){

			if( ! empty( $params[ 'mkto_programName' ])){
			  $description = $params[ 'mkto_name' ] . ' | '. $params[ 'mkto_programName' ];
			} else {
			  $description = $params[ 'mkto_name' ];
			}

			$term = wp_insert_term(
			      $name, // the term 
			      'marketo_list', // the taxonomy
			      array(
			        'description'=> $description,
			        'slug' => strtolower($name),
			        'parent'=> 0  // get numeric term id
			      )
			    );

			if ( ! is_wp_error( $term ) ) {
			  $t_id = $term[ 'term_id' ];
			  update_option( "mkto_taxonomy_term_list_$t_id", $params[ 'mkto_list_id' ] );  
			}

			$response[ 'response_code' ] = 1;
   			$response[ 'response_message' ] = sprintf( '<span style="%1$s"><strong>%2$s</strong></span>', 'color:#ee7600;', __( 'Marketo list name successfully saved!, please reload/refresh the page.' )); 

   			echo wp_json_encode( $response );

		} else {
			$response[ 'response_code' ] = 0;
	   		$response[ 'response_message' ] = sprintf( '<span style="%1$s"><strong>%2$s</strong></span>', 'color:#FF0000;', __( 'Marketo list name is already exist!' ) ); 


	   		echo wp_json_encode( $response );
		} 

	    exit;

	}
}