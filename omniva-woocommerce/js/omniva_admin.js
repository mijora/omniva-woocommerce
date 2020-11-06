jQuery('document').ready(function($){
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_method_c", ".omniva_courier");
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_method_pt", ".omniva_terminal");

	$( document ).on( 'change', '#woocommerce_omnivalt_method_c', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_courier");
	});
	$( document ).on( 'change', '#woocommerce_omnivalt_method_pt', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_terminal");
	});

	function omniva_show_admin_fields_by_cb(checkbox,fields_selector) {
		if ($(checkbox).is(':checked')) {
			$(fields_selector).closest("tr").removeClass("hidden");
		} else {
			$(fields_selector).closest("tr").addClass("hidden");
		}
	}
});