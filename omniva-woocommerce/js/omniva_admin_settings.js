jQuery('document').ready(function($){
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_method_c", ".omniva_courier");
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_method_pt", ".omniva_terminal");
	omniva_hide_admin_field_by_all_cb(["#woocommerce_omnivalt_method_c","#woocommerce_omnivalt_method_pt"],".omniva_both");
	omniva_toggle_class_by_cb("#woocommerce_omnivalt_method_c",".block-prices.courier", "disabled", false);
	omniva_toggle_class_by_cb("#woocommerce_omnivalt_method_pt",".block-prices.terminal", "disabled", false);
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_debug_mode",".omniva_debug");
	
	var pt_prices_blocks = $(".pt_enable");
	for (var i=0; i<pt_prices_blocks.length; i++) {
		var block = $(pt_prices_blocks[i]).closest('.block-prices').find('.sec-prices');
		omniva_toggle_class_by_cb(pt_prices_blocks[i], block, "disabled", false);
	}
	var c_prices_blocks = $(".c_enable");
	for (var i=0; i<c_prices_blocks.length; i++) {
		var block = $(c_prices_blocks[i]).closest('.block-prices').find('.sec-prices');
		omniva_toggle_class_by_cb(c_prices_blocks[i], block, "disabled", false);
	}
	
	var pt_prices_free = $(".pt_enable_free");
	for (var i=0; i<pt_prices_free.length; i++) {
		var field = $(pt_prices_free[i]).closest('.prices-free').find('.price_free');
		omniva_disable_by_cb(pt_prices_free[i], field, "disabled", false, "readonly");
	}
	var c_prices_free = $(".c_enable_free");
	for (var i=0; i<c_prices_free.length; i++) {
		var field = $(c_prices_free[i]).closest('.prices-free').find('.price_free');
		omniva_disable_by_cb(c_prices_free[i], field, "disabled", false, "readonly");
	}

	var pt_prices_coupon = $(".pt_enable_coupon");
	for (var i=0; i<pt_prices_coupon.length; i++) {
		var field = $(pt_prices_coupon[i]).closest('.prices-coupon').find('.price_coupon');
		omniva_disable_by_cb(pt_prices_coupon[i], field, "disabled", false);
	}
	var c_prices_coupon = $(".c_enable_coupon");
	for (var i=0; i<c_prices_coupon.length; i++) {
		var field = $(c_prices_coupon[i]).closest('.prices-coupon').find('.price_coupon');
		omniva_disable_by_cb(c_prices_coupon[i], field, "disabled", false);
	}

	$( document ).on( 'change', '#woocommerce_omnivalt_method_c', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_courier");
		omniva_hide_admin_field_by_all_cb(["#woocommerce_omnivalt_method_c","#woocommerce_omnivalt_method_pt"],".omniva_both");
		omniva_toggle_class_by_cb(this, ".block-prices.courier", "disabled", false);
	});
	$( document ).on( 'change', '#woocommerce_omnivalt_method_pt', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_terminal");
		omniva_hide_admin_field_by_all_cb(["#woocommerce_omnivalt_method_c","#woocommerce_omnivalt_method_pt"],".omniva_both");
		omniva_toggle_class_by_cb(this, ".block-prices.terminal", "disabled", false);
	});

	$( document ).on( 'change', '.pt_enable', function() {
		var block = $(this).closest('.block-prices').find('.sec-prices');
		omniva_toggle_class_by_cb(this, block, "disabled", false);
	});
	$( document ).on( 'change', '.c_enable', function() {
		var block = $(this).closest('.block-prices').find('.sec-prices');
		omniva_toggle_class_by_cb(this, block, "disabled", false);
	});

	$( document ).on( 'change', '.pt_enable_free', function() {
		var field = $(this).closest('.prices-free').find('.price_free');
		omniva_disable_by_cb(this, field, "disabled", false, "readonly");
	});
	$( document ).on( 'change', '.c_enable_free', function() {
		var field = $(this).closest('.prices-free').find('.price_free');
		omniva_disable_by_cb(this, field, "disabled", false, "readonly");
	});

	$( document ).on( 'change', '.pt_enable_coupon', function() {
		var field = $(this).closest('.prices-coupon').find('.price_coupon');
		omniva_disable_by_cb(this, field, "disabled", false);
	});
	$( document ).on( 'change', '.c_enable_coupon', function() {
		var field = $(this).closest('.prices-coupon').find('.price_coupon');
		omniva_disable_by_cb(this, field, "disabled", false);
	});

	$( document ).on( 'change', '#woocommerce_omnivalt_debug_mode', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_debug");
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

	function omniva_toggle_class_by_cb(checkbox,fields_selector, add_class, add_when_checked=true) {
		if ($(checkbox).is(':checked')) {
			if (add_when_checked) {
				$(fields_selector).addClass(add_class);
			} else {
				$(fields_selector).removeClass(add_class);
			}
		} else {
			if (add_when_checked) {
				$(fields_selector).removeClass(add_class);
			} else {
				$(fields_selector).addClass(add_class);
			}
		}
	}

	function omniva_disable_by_cb(checkbox, fields_selector, add_class="", dis_when_checked=true, property="disabled") {
		if (add_class) {
			omniva_toggle_class_by_cb(checkbox,fields_selector,add_class, dis_when_checked);
		}
		if ($(checkbox).is(':checked')) {
			if (dis_when_checked) {
				$(fields_selector).prop(prop, true);
			} else {
				$(fields_selector).prop(property, false);
			}
		} else {
			if (dis_when_checked) {
				$(fields_selector).prop(property, false);
			} else {
				$(fields_selector).prop(property, true);
			}
		}
	}
});