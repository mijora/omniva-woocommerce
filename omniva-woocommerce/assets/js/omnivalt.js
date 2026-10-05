/* global omnivadata */

jQuery('document').ready(function($){
    $('input.shipping_method').on('click',function(){
        var current_method = $(this);
        if (current_method.val() == "omnivalt_pt"){
            $('.terminal-container').show();
        } else {
            $('.terminal-container').hide();
        }
    });
    $('input.shipping_method:checked').trigger('click');
    
    $( document ).on( 'change', '.omnivalt_terminal', function() {
        var terminal_id = $(this).val();

        if ( typeof omnivadata === 'undefined' || ! omnivadata.ajax_url || ! omnivadata.add_terminal_nonce ) {
            return;
        }

        $.ajax({
            url : omnivadata.ajax_url,
            type : 'post',
            data : {
                action : 'add_terminal_to_session',
                terminal_id : terminal_id,
                nonce : omnivadata.add_terminal_nonce
            },
            success : function( response ) {
               
            }
        });
    })
});
