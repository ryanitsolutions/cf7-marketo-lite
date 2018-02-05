<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Marketo Lite Core Class
 * 
 * @author Ryan IT Solutions
 * @version since 1.0
 */


class CF7_MarketoLite_Core {
	
	public static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct(){}

	public static function generate_leads( $contact_form ){
		
		$title      = $contact_form->title();
      	$properties = $contact_form->get_properties();
      	$submission = WPCF7_Submission::get_instance();

		if ( $submission ) {
		    $posted_data = $submission->get_posted_data();
		}

		$form_id        = $contact_form->id();

		// Dont process if Marketo form is not enable
		if(  get_post_meta( wp_kses_post( $form_id ), 'marketo_enable_form', true ) === false ) return;

		// Check Marketo fields if exist
		if( ! empty( $posted_data )){
			$markto_fields = array();
			foreach ($posted_data as $key => $value) {
				$rs = apply_filters( 'cf7mkto_check_exist_field', wp_kses_post( $key )  );				
				if( ! empty($rs) ){
					$markto_fields[$key] = $value;
				}
			}
		}

		// Dont process if marketo fields not exist
		if( count($markto_fields) <= 0 ) return;

		$creds = WPCF7::get_option( 'cf7_marketo' );

		if( ! empty($creds) && array_key_exists( 'on' , $creds )){

			$marketo_id 	= $creds['on'][ 'marketo_munchkin_id' ];
			$client_id 		= $creds['on'][ 'marketo_client_id' ];
			$client_secret 	= $creds['on'][ 'marketo_client_secret' ];
			$cf7_id 		= 'cf7_mkto_id_7770';

			// Marketo API Request
			$results = apply_filters( 'cf7mkto_create_leads', $markto_fields );
			
			if( $results[ 'code' ] === true && $results[ 'response_code' ] == 200 && $results[ 'response_body' ]->success === true ){
				$lead_id		= $results[ 'response_body' ]->result[0]->id;
				$lead_status 	= $results[ 'response_body' ]->result[0]->status;
				$cookie  		= $_COOKIE[ '_mkto_trk' ];

				if( $lead_status == 'created'  ){

					$result = self::insert_new_post( $lead_id, '', 'marketo_leads', 'publish' );

					if( $result[ 'code' ] === true ){
						self::save_custom_meta( $result[ 'post_id' ], array( 'mkto_ID' => $lead_id , 'form_id' => $form_id ));
						self::save_custom_meta( $result[ 'post_id' ], $markto_fields );
					}

				} else {

					$args = array(
						'meta_key' 		=> 'mkto_ID',
						'meta_value' 	=> $lead_id,
						'post_type'  	=> 'marketo_leads',
						'meta_compare' 	=> '='
					);

					$query = new WP_Query( $args );

					if( isset( $query->post->ID ) ){
						$result = self::update_post( $query->post->ID, $lead_id, '', 'marketo_leads', 'publish' );
						self::save_custom_meta( $result[ 'post_id' ], $markto_fields );
					}

					wp_reset_postdata();					
					
				}

				// Add to Marketo List
				self::add_to_list( $form_id, $lead_id );

			}

		}

	}

	/**
     * Insert New Post
     *
     * @param string $title
     * @param string $desc
     * @param string $post_type
     * @param string $status
     * @param int $quote_id   
     * @param int $custom_post_meta
     * @return array $results
     */

	public static function insert_new_post( $title, $desc, $post_type, $status ){

		if( empty($post_type) ) return;

		$post_id    = -1;
		$item_name  = wp_kses_post( $title );
		$slug		= $item_name;

		$author     = get_current_user_id() > 0 ? get_current_user_id() : 0;

		$post_id = wp_insert_post(
			array(
			  'comment_status'   =>	'closed',
			  'ping_status'		 =>	'closed',
			  'post_author'		 =>	$author ,
			  'post_name'		 =>	$slug,
			  'post_title'		 =>	wp_kses_post( $item_name ),
			  'post_content'	 =>	wp_kses_post( $desc ),
			  'post_status'		 =>	wp_kses_post( $status ),
			  'post_type'		 =>	wp_kses_post( $post_type )
			)
		);

		if ( is_wp_error( $post_id ) ) {

			$result[ 'message' ] = $post_id->get_error_messages();
			$result[ 'code' ] = false;

		} else {

			$result[ 'code' ] = true;
			$result[ 'post_id' ] = $post_id;

		}       

	  return $result;

	}

	/**
     * Update Post
     *
     * @param int $id
     * @param string $title
     * @param string $desc
     * @param string $post_type
     * @param string $status
     * @param array $custom_post_meta
     * @return array $results
     */

   public static function update_post( $id, $title, $desc, $post_type, $status ){

   	if( empty($post_type) ) return;
   	
   	$author     = get_current_user_id() > 0 ? get_current_user_id() : 0;

	$post_id = wp_update_post(
		array(
		  'ID'	            =>	$id,
		  'post_author'		=>	$author,
		  'post_title'		=>	wp_kses_post( $title ),
		  'post_content'	=>	wp_kses_post( $desc ),
		  'post_status'		=>	wp_kses_post( $status ),
		  'post_type'		=>  wp_kses_post( $post_type )
		)
	);

	if ( is_wp_error( $post_id ) ) {

		$result[ 'message' ] = $post_id->get_error_messages();
		$result[ 'code' ] = false;

	} 

	else {

		$result[ 'code' ] = true;
		$result[ 'post_id' ] = $post_id;

	}

		return $result;       
   }

   /**
	* Save Custom Post Meta Data
	*
	* @param int $post_id
	* @param array $custom_metas
	* @return void
  	*/  

	public static function save_custom_meta( $post_id, $custom_metas ){

		if( ! empty( $post_id ) ) {

		    foreach( $custom_metas as $key => $value ) {
		        if ( ! add_post_meta(  $post_id , $key , wp_kses_post( $value ) , true ) ) {
		            update_post_meta ( $post_id, $key , wp_kses_post( $value ) );
		        }
		    }

		    wp_reset_postdata();
		    return true;

		}else{

		    wp_reset_postdata();
		    return false;
		    
		}

	}

	public static function tracking_code(){

		global $wp;

		$api_creds 		= WPCF7::get_option( 'cf7_marketo' );

		$marketo_munchkin_id = ! empty($api_creds[ 'on' ][ 'marketo_munchkin_id' ]) ? $api_creds[ 'on' ][ 'marketo_munchkin_id' ] : '';

		if( empty($marketo_munchkin_id)) return;

		?>
		<script type="text/javascript">
			/* <![CDATA[ */
			(function() {
				var didInit = false;
				function initMunchkin() {
					if(didInit === false) {
						didInit = true;
						Munchkin.init('<?php echo $marketo_munchkin_id;?>', { 'asyncOnly': true, 'disableClickDelay': true, 'customName': '<?php echo esc_html( get_the_title() );?>' });
						Munchkin.createTrackingCookie(true);

					}
				}
				var s = document.createElement('script');
				s.type = 'text/javascript';
				s.async = true;
				s.src = '//munchkin.marketo.net/munchkin.js';
				s.onreadystatechange = function() {
					if (this.readyState == 'complete' || this.readyState == 'loaded') {
						initMunchkin();
					}
				};
				s.onload = initMunchkin;
				document.getElementsByTagName('head')[0].appendChild(s);
			})();

			/* ]]> */
		</script>
		<?php
	}

	public static function add_to_list( $form_id, $lead_id ){

		if (empty($form_id)) return;

		if (empty($lead_id)) return;

		
		$creds = WPCF7::get_option( 'cf7_marketo' );

		if( ! empty($creds) && array_key_exists( 'on' , $creds )){

			$marketo_id 	= $creds['on'][ 'marketo_munchkin_id' ];
			$client_id 		= $creds['on'][ 'marketo_client_id' ];
			$client_secret 	= $creds['on'][ 'marketo_client_secret' ];
			$cf7_id 		= 'cf7_mkto_id_7770';

			// Add to Marketo list
			$term_id = get_post_meta( sanitize_text_field( $form_id ), 'marketo_form_list', true );
			$list_id = get_option( "mkto_taxonomy_term_list_$term_id" );

			if( ! empty( $list_id ) && $list_id > 0 ){

				$data_ids = array( 'id' =>  $lead_id );
				$result_list = apply_filters( 'cf7mkto_add_lead_to_list', $list_id, $data_ids ); 

				if( $result_list[ 'code' ] === true && $result_list[ 'response_code' ] == 200 && $result_list[ 'response_body' ]->success === true ){

					$args = array(
						'meta_key' 		=> 'mkto_ID',
						'meta_value' 	=> $lead_id,
						'post_type'  	=> 'marketo_leads',
						'meta_compare' 	=> '='
					);

					$query = new WP_Query( $args );
					if( isset( $query->post->ID ) ){
						$t = get_term( $term_id, 'marketo_list' );
						$r = wp_set_object_terms( $query->post->ID, $t->slug, 'marketo_list' );
					}
					
				}

			}
			wp_reset_postdata();
		}
	}

	public static function reset_marketo_list(){
		if ( is_admin() ) {
          $terms = get_terms( 'marketo_list', array( 'fields' => 'ids', 'hide_empty' => false ) );
          foreach ( $terms as $value ) {
               wp_delete_term( $value, 'marketo_list' );
          }
     	}
	}

	public static function get_marketo_list(){
		
          $terms = get_terms( 'marketo_list', array( 'hide_empty' => false ) );
          $list =  array();
          foreach ( $terms as $value ) {
          	    $term_id = $value->term_id;
          	    $list[$term_id] = $value->name;
                
          }

          return $list;
     	
	}

	public static function taxonomy(){
		$labels = array(
			'name'              => _x( 'Marketo List', 'taxonomy general name', 'contact-form-7' ),
			'singular_name'     => _x( 'List', 'taxonomy singular name', 'contact-form-7' ),
			'search_items'      => __( 'Search List', 'contact-form-7' ),
			'all_items'         => __( 'All List', 'contact-form-7' ),
			'parent_item'       => __( 'Parent List', 'contact-form-7' ),
			'parent_item_colon' => __( 'Parent List:', 'contact-form-7' ),
			'edit_item'         => __( 'Edit List', 'contact-form-7' ),
			'update_item'       => __( 'Update List', 'contact-form-7' ),
			'add_new_item'      => __( 'Add New List', 'contact-form-7' ),
			'new_item_name'     => __( 'New List Name', 'contact-form-7' ),
			'menu_name'         => __( 'List', 'contact-form-7' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'public' 			=> false,
			'rewrite'           => false,
		);

		register_taxonomy( 'marketo_list', array( 'marketo_leads' ), $args );
	}
	
}
