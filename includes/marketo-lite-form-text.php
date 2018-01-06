<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Marketo Lite Form Text Class
 * 
 * @author Ryan IT Solutions
 * @version since 1.0
 */

class CF7_MarketoLite_Form_Text {

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
		
		//Tag generator
		add_action( 'wpcf7_admin_init', array( $this, 'add_tag_generator_text' ), 15 );
		
	}

	public function add_tag_generator_text() {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'mtkto_text', __( 'marketo text', 'contact-form-7' ),
			array( $this, 'tag_generator_text' ) );
		$tag_generator->add( 'mkto_email', __( 'marketo email', 'contact-form-7' ),
			array( $this, 'tag_generator_text' ) );
		$tag_generator->add( 'mkto_url', __( 'marketo URL', 'contact-form-7' ),
			array( $this, 'tag_generator_text' ) );
		$tag_generator->add( 'mkto_tel', __( 'marketo tel', 'contact-form-7' ),
			array( $this, 'tag_generator_text' ) );
	}

	public function tag_generator_text( $contact_form, $args = '' ) {
		$args = wp_parse_args( $args, array() );
		$type = $args['id'];

		switch ( $type ) {
			case 'mtkto_text':
				$type = 'text';
				$describes = apply_filters( 'cf7mkto_get_describe_fields', 'string' ) ;
				break;

			case 'mkto_email':
				$type = 'email';
				$describes = apply_filters( 'cf7mkto_get_describe_fields', 'email' ) ;
				break;
				
			case 'mkto_url':
				$type = 'url';
				$describes = apply_filters( 'cf7mkto_get_describe_fields', 'url' ) ;
				break;
				
			case 'mkto_tel':
				$type = 'tel';
				$describes = apply_filters( 'cf7mkto_get_describe_fields', 'phone' ) ;
				break;			
			
			default:
				$type = 'text';
				$describes = apply_filters( 'cf7mkto_get_describe_fields', 'string' ) ;
				break;
		}

		if ( 'text' == $type ) {
			$description = __( "Generate a form-tag for a single-line plain text input field. For more details, see %s.", 'contact-form-7' );
		} elseif ( 'email' == $type ) {
			$description = __( "Generate a form-tag for a single-line email address input field. For more details, see %s.", 'contact-form-7' );
		} elseif ( 'url' == $type ) {
			$description = __( "Generate a form-tag for a single-line URL input field. For more details, see %s.", 'contact-form-7' );
		} elseif ( 'tel' == $type ) {
			$description = __( "Generate a form-tag for a single-line telephone number input field. For more details, see %s.", 'contact-form-7' );
		}

		$desc_link = wpcf7_link( __( 'https://contactform7.com/text-fields/', 'contact-form-7' ), __( 'Text Fields', 'contact-form-7' ) );

	?>
	<div class="control-box">
	<fieldset>
	<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

	<table class="form-table">
	<tbody>
		<tr>
		<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
		<td>
			<fieldset>
			<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
			<label><input type="checkbox" name="required" class="mkto_required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
			</fieldset>
		</td>
		</tr>

		<tr>
		<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
		<td>

			<?php
			if( ! empty($describes) && count($describes) > 0 ){
				?>

				<input type="hidden" name="name" class="tg-name oneline mkto-tg-<?php echo $type;?>" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" value="<?php echo $describes[0]->rest_name;?>"  />	

				 <select name="mkto_field_text" class="tg-name oneline mkto_field_text" data-type="<?php echo $type;?>" >
				 	<option value=""><?php echo esc_html__( 'Select Marketo Field', 'contact-form-7' ); ?></option>
				 	<?php
				 		foreach ($describes as $key => $describe) {
				 			?>
				 			<option value="<?php echo esc_attr( $describe->rest_name, 'contact-form-7' )?>"><?php echo esc_attr__( $describe->display_name, 'contact-form-7' )?></option>
				 			<?php
				 		}

				 	?>
				 </select>

				<?php
			} else {
				?>
					<input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" />		
				<?php
			}

			?>

			</td>
		</tr>

		<tr>
		<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'contact-form-7' ) ); ?></label></th>
		<td>
			
			<input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>"/>

			 <br />
		<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'contact-form-7' ) ); ?></label></td>
		</tr>

	<?php if ( in_array( $type, array( 'text', 'email', 'url' ) ) ) : ?>
		<tr>
		<th scope="row"><?php echo esc_html( __( 'Akismet', 'contact-form-7' ) ); ?></th>
		<td>
			<fieldset>
			<legend class="screen-reader-text"><?php echo esc_html( __( 'Akismet', 'contact-form-7' ) ); ?></legend>

	<?php if ( 'text' == $type ) : ?>
			<label>
				<input type="checkbox" name="akismet:author" class="option" />
				<?php echo esc_html( __( "This field requires author's name", 'contact-form-7' ) ); ?>
			</label>
	<?php elseif ( 'email' == $type ) : ?>
			<label>
				<input type="checkbox" name="akismet:author_email" class="option" />
				<?php echo esc_html( __( "This field requires author's email address", 'contact-form-7' ) ); ?>
			</label>
	<?php elseif ( 'url' == $type ) : ?>
			<label>
				<input type="checkbox" name="akismet:author_url" class="option" />
				<?php echo esc_html( __( "This field requires author's URL", 'contact-form-7' ) ); ?>
			</label>
	<?php endif; ?>

			</fieldset>
		</td>
		</tr>
	<?php endif; ?>

		<tr>
		<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
		<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
		</tr>

		<tr>
		<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
		<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
		</tr>

	</tbody>
	</table>
	</fieldset>
	</div>

	<div class="insert-box">
		<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

		<div class="submitbox">
		<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
		</div>

		<br class="clear" />

		<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
	</div>
	<?php
	}


}	