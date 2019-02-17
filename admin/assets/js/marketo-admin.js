jQuery(document).ready( function() {

   jQuery(".marketo_add_exist_list_btn").click( function(e) {
      e.preventDefault(); 
      nonce = jQuery(this).attr("data-nonce");
      marketo_exist_list_name = jQuery( ".marketo_exist_list_name" );
      marketo_search_message = jQuery( ".marketo_search_message" );
      marketo_search_existing_list = jQuery( ".marketo_search_existing_list" );
      marketo_add_exist_list_btn = jQuery( ".marketo_add_exist_list_btn" );
      marketo_search_existing_list.html( "loading ..." );
      marketo_search_message.html( "" );

      if( marketo_exist_list_name.val() == '' ){
         return false;
      }

      marketo_exist_list_name.attr( 'disabled', true );
      marketo_add_exist_list_btn.attr( 'disabled', true );

      jQuery.ajax({
         type : "post",
         dataType : "json",
         url : marketoAjax.ajaxurl,
         data : {action: "marketo-search-existing-list", nonce: nonce, list_name : marketo_exist_list_name.val()},
         success: function(response) {
            
            marketo_search_message.html( "" );
            marketo_search_existing_list.html( "" );
            marketo_exist_list_name.removeAttr( 'disabled' );
            marketo_add_exist_list_btn.removeAttr( 'disabled' );

            if(response.response_code == 1 ) {
               marketo_search_message.html(response.response_message);
               marketo_search_existing_list.html(response.response_body);
            }
            else {
               marketo_search_message.html(response.response_message);
               marketo_search_existing_list.html( '' );
            }
         }
      });   

   });

   jQuery( "body" ).delegate( '.add_marketo_list_name_action_btn', 'click', function(e) {
      e.preventDefault(); 

      $this = jQuery(this);

      marketo_search_message = jQuery( ".marketo_search_message" );
      marketo_search_message.html( "" );

      $data = {
         mkto_list_id : $this.data( "mkto_list_id" ),
         mkto_name : $this.data( "mkto_name" ),
         mkto_programname : $this.data( "mkto_programname" ),
         mkto_workspacename : $this.data( "mkto_workspacename" ),
         mkto_createdat : $this.data( "mkto_createdat" ),
         mkto_updatedat : $this.data( "mkto_updatedat" ),
         nonce : $this.data( "nonce" ),
         action: "marketo-add-existing-list"
      };

      
      $this.next( ".spinner" ).css( 'visibility', 'visible'  );

      jQuery.ajax({
         type : "post",
         dataType : "json",
         url : marketoAjax.ajaxurl,
         data : $data ,
         success: function(response) {
            $this.next( ".spinner" ).css( 'visibility', 'hidden'  );
            if(response.response_code == 1 ) {
               marketo_search_message.html(response.response_message);
            }
            else {
               marketo_search_message.html(response.response_message);
            }
         }
      });   
   });


   jQuery('.marketo_select_list').select2();
   

});