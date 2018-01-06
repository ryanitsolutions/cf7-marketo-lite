( function( $ ) {

	'use strict';

	if ( typeof wpcf7 === 'undefined' || wpcf7 === null ) {
		return;
	}

	$( ".mkto_field_text" ).on( 'change', function(e){
		var $this = $(this);

		$( ".mkto-tg-" + $this.data( 'type' ) ).val( $this.val() );

		var $form = $( this ).closest( 'form.tag-generator-panel' );
		wpcf7.taggen.normalize( $( this ) );
		wpcf7.taggen.update( $form );

	} );	

	
} )( jQuery );