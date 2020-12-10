jQuery('document').ready(function($){
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_method_c", ".omniva_courier");
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_method_pt", ".omniva_terminal");
	omniva_hide_admin_field_by_all_cb(["#woocommerce_omnivalt_method_c","#woocommerce_omnivalt_method_pt"],".omniva_both");
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_debug_request",".omniva_debug_request");
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_debug_response",".omniva_debug_response");

	$( document ).on( 'change', '#woocommerce_omnivalt_method_c', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_courier");
		omniva_hide_admin_field_by_all_cb(["#woocommerce_omnivalt_method_c","#woocommerce_omnivalt_method_pt"],".omniva_both");
	});
	$( document ).on( 'change', '#woocommerce_omnivalt_method_pt', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_terminal");
		omniva_hide_admin_field_by_all_cb(["#woocommerce_omnivalt_method_c","#woocommerce_omnivalt_method_pt"],".omniva_both");
	});

	$( document ).on( 'change', '#woocommerce_omnivalt_debug_request', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_debug_request");
	});
	$( document ).on( 'change', '#woocommerce_omnivalt_debug_response', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_debug_response");
	});

	function omniva_show_admin_fields_by_cb(checkbox,fields_selector) {
		if ($(checkbox).is(':checked')) {
			$(fields_selector).closest("tr").removeClass("hidden");
		} else {
			$(fields_selector).closest("tr").addClass("hidden");
		}
	}

	function omniva_hide_admin_field_by_all_cb(checkboxes,fields_selector) {
		var hide = true;
		for (var i=0;i<checkboxes.length;i++) {
			if ($(checkboxes[i]).is(':checked')) {
				hide = false;
			}
		}
		if (hide == false) {
			$(fields_selector).closest("tr").removeClass("hidden");
		} else {
			$(fields_selector).closest("tr").addClass("hidden");
		}
	}
});