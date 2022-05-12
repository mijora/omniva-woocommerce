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
        $.ajax({
            url : omnivaltdata.ajax_url,
            type : 'post',
            data : {
                action : 'add_terminal_to_session',
                terminal_id : terminal_id
            },
            success : function( response ) {
               
            }
        });
    })
});