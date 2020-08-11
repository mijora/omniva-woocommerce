jQuery('document').ready(function($){

    if (omnivadata.is_in_cart)
        omnivaInit();

    $(document.body).on('updated_checkout updated_wc_div updated_shipping_method', function(event) {
        omnivaInit()
        // console.log(event.type);
    });
});

function omnivaInit() {
    var omniva_element = jQuery('input[name^="shipping_method"][value="omnivalt_pt"]');

    omniva_element.omniva({
        country_code: omniva_country,
        terminals: omniva_terminals,
        translate: omnivadata,
        path_to_img: omnivadata.path_to_img,
        callback: (terminal_id, is_clicked) => {
            if (is_clicked) {
                jQuery.ajax({
                    url : omnivadata.add_terminal_to_session,
                    type : 'post',
                    data : {
                        terminal_id : terminal_id
                    },
                }).done(function(response) {
                    console.log(response);
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus + ': ' + errorThrown);
                });
            }
        }
    });

    if (jQuery('input[name^="shipping_method"]:checked').val() === "omnivalt_pt"){
        omniva_element.trigger('omniva.show')
    } else {
        omniva_element.trigger('omniva.hide')
    }

    if (omniva_selected_terminal && !isNaN(+omniva_selected_terminal)) {
        omniva_element.trigger('omniva.select_terminal', omniva_selected_terminal)
    }
}