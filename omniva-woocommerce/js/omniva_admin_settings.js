/*** Settings ***/
jQuery('document').ready(function($){
	var all_keys = {
		"pt":"terminal",
		"c":"courier",
    "cp":"courier_plus",
    "pc":"private_customer",
		"po":"post"
	};
	var enable_only = { //Enable shipping method only in specified countries in array. If array empty, enable for all countries.
		"pt":[],
		"c":[],
    "cp":["EE"],
		"po":["EE"],
		"pc":["EE"]
	};
	for (var key in all_keys) {
		omnivalt_load_shipping_method(key, all_keys[key], all_keys);
	};
	omniva_hide_admin_field_by_all_cb(all_keys,".omniva_both");
	omniva_show_admin_fields_by_cb("#woocommerce_omnivalt_debug_mode",".omniva_debug");

	$(document).on('change', '#woocommerce_omnivalt_debug_mode', function() {
		omniva_show_admin_fields_by_cb(this, ".omniva_debug");
	});

	$(document).on('change', '#woocommerce_omnivalt_api_country', function() {
    for (var key in enable_only) {
      if (enable_only[key].length > 0) {
        if (enable_only[key].indexOf(this.value) >= 0) {
          $("#woocommerce_omnivalt_method_" + key).prop("checked", true);
        } else {
          $("#woocommerce_omnivalt_method_" + key).prop("checked", false);
        }
      }
      $("#woocommerce_omnivalt_method_" + key).trigger("change");
    }
  });

	$(document).on('click', '.debug-row .date', function() {
		if ($(this).hasClass("active")) {
			$(this).siblings("textarea").stop().slideUp("slow");
			$(this).removeClass("active");
		} else {
			$(this).siblings("textarea").stop().slideDown("slow");
			$(this).addClass("active");
		}
	});

	/** Functions **/
	function omnivalt_load_shipping_method(key, name, all_keys) {
		var activation_field = "#woocommerce_omnivalt_method_" + key;
		omniva_show_admin_fields_by_cb(activation_field, ".omniva_" + name);
		omniva_toggle_class_by_cb(activation_field,".block-prices." + name, "disabled", false);
		
		var prices_blocks = $("." + key + "_enable");
		for (var i=0; i<prices_blocks.length; i++) {
			var block = $(prices_blocks[i]).closest('.block-prices').find('.sec-prices');
			omniva_toggle_class_by_cb(prices_blocks[i], block, "disabled", false);
			var block = $(prices_blocks[i]).closest('.block-prices').find('.sec-other');
			omniva_toggle_class_by_cb(prices_blocks[i], block, "disabled", false);
		}

		var prices_free = $("." + key + "_enable_free");
		for (var i=0; i<prices_free.length; i++) {
			var field = $(prices_free[i]).closest('.prices-free').find('.price_free');
			omniva_disable_by_cb(prices_free[i], field, "disabled", false, "readonly");
		}

		var pt_prices_coupon = $("." + key + "_enable_coupon");
		for (var i=0; i<pt_prices_coupon.length; i++) {
			var field = $(pt_prices_coupon[i]).closest('.prices-coupon').find('.price_coupon');
			omniva_disable_by_cb(pt_prices_coupon[i], field, "disabled", false);
		}

		var all_activation_fields = [];
		for (var i=0;i<all_keys.length;i++) {
			all_activation_fields.push("#woocommerce_omnivalt_method_" + all_keys[i]);
		}
		$( document ).on( 'change', '#woocommerce_omnivalt_method_' + key, function() {
			omniva_show_admin_fields_by_cb(this, ".omniva_" + name);
			omniva_hide_admin_field_by_all_cb(all_activation_fields,".omniva_both");
			omniva_toggle_class_by_cb(this, ".block-prices." + name, "disabled", false);
		});

		$( document ).on( 'change', '.' + key + '_enable', function() {
			var block = $(this).closest('.block-prices').find('.sec-prices');
			omniva_toggle_class_by_cb(this, block, "disabled", false);
			var block = $(this).closest('.block-prices').find('.sec-other');
			omniva_toggle_class_by_cb(this, block, "disabled", false);
		});

		$( document ).on( 'change', '.' + key + '_enable_free', function() {
			var field = $(this).closest('.prices-free').find('.price_free');
			omniva_disable_by_cb(this, field, "disabled", false, "readonly");
		});

		$( document ).on( 'change', '.' + key + '_enable_coupon', function() {
			var field = $(this).closest('.prices-coupon').find('.price_coupon');
			omniva_disable_by_cb(this, field, "disabled", false);
		});
	}

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

/*** Tables ***/
jQuery("document").ready(function($) {
	omnivalt_checkAllRows($(".prices-table > table")[0]);
	omnivalt_checkTableAddRowButton($(".prices-table > table")[0]);

	var all_price_types_fields = $(".sec-prices .price_type");
	for (var i=0; i<all_price_types_fields.length; i++) {
		omnivalt_showPricesSection(all_price_types_fields[i]);
	}

	$(document).on( "change", '.prices-table .row-values .column-value input[type="number"]', function() {
		var table = $(this).closest("table");
		omnivalt_checkAllRows(table);
		omnivalt_checkTableAddRowButton(table);
	});

	$(document).on( "click", ".omniva-fake-btn", function() {
		var action = this.dataset.action;
		
		switch(action) {
			case "add_prices_table_row":
				omnivalt_btnAct_addPricesTableRow(this);
				break;
			case "remove_prices_table_row":
				omnivalt_btnAct_removePricesTableRow(this);
				break;
			default:
				break;
		}
	});

	$(document).on( "change", ".price_type", function() {
		omnivalt_showPricesSection(this);
	});

	/** Functions **/
	function omnivalt_btnAct_addPricesTableRow(button) {
		var btn_row = $(button).closest("tr");
		var table = $(button).closest("table");
		var prev_rows = $(button).closest("tr").parent().find(".row-values");
		var new_row = document.createElement("tr");
		var col_value = document.createElement("td");
		var col_price = document.createElement("td");
		var col_actions = document.createElement("td");
		var d = new Date();
    var date = '' + d.getMinutes() + d.getSeconds() + d.getMilliseconds();
		
		var value_from = 0;
		var value_step = $(table).data("step1");
		var decimals = omnivalt_countDecimals(value_step);
		var span_from = value_from.toFixed(decimals);
		if (prev_rows.length) {
			var prev_row = prev_rows[prev_rows.length - 1];
			var prev_value = $(prev_row).find('.column-value input[type="number"]').val();
			if (prev_value) {
				value_from = parseFloat(prev_value) + parseFloat(value_step);
				span_from = value_from.toFixed(decimals);
			}
			if (prev_value === "") {
				span_from = "???";
			}
		}

		col_value.classList.add("column-value");
		var span = document.createElement("span");
		var span_text = document.createTextNode(" - ");
		var span_value = document.createElement("span");
		span.classList.add("row-from");
		span_value.classList.add("value-from");
		span_value.dataset.step = parseFloat(value_step);
		span_value.innerText = "" + span_from;
		span.appendChild(span_value);
		span.appendChild(span_text);
		col_value.appendChild(span);
		var input = document.createElement("input");
		input.type = "number";
		input.name = $(table).data("name") + "[" + date + "][value]";
		input.id = $(table).data("id") + "_value_" + date;
		input.step = value_step;
		input.min = value_from;
		input.placeholder = "...";
		input.classList.add("input-text");
		input.classList.add("regular-input");
		input.value = "";
		col_value.appendChild(input);

		col_price.classList.add("column-price");
		var value_step = $(table).data("step2");
		var input = document.createElement("input");
		input.type = "number";
		input.name = $(table).data("name") + "[" + date + "][price]";
		input.id = $(table).data("id") + "_price_" + date;
		input.step = value_step;
		input.min = 0;
		input.classList.add("input-text");
		input.classList.add("regular-input");
		input.value = "";
		col_price.appendChild(input);

		col_actions.classList.add("column-actions");
		var button = document.createElement("div");
		button.classList.add("omniva-fake-btn");
		button.dataset.action = "remove_prices_table_row";
		button.innerHTML = "X";
		col_actions.appendChild(button);

		new_row.classList.add("row-values");
		new_row.appendChild(col_value);
		new_row.appendChild(col_price);
		new_row.appendChild(col_actions);
		btn_row[0].parentNode.insertBefore(new_row, btn_row[0]);
		//list.insertBefore(newItem, list.childNodes[0])

		omnivalt_checkTableAddRowButton(table);
	}

	function omnivalt_btnAct_removePricesTableRow(button) {
		var table = $(button).closest("table");
		var row = $(button).closest("tr");
		row.remove();

		omnivalt_checkAllRows(table);
		omnivalt_checkTableAddRowButton(table);
	}

	function omnivalt_countDecimals(value) {
    if ((value % 1) != 0) 
    	return value.toString().split(".")[1].length;  
    return 0;
  }

  function omnivalt_checkTableAddRowButton(table) {
  	var button = $(table).find(".row-footer .column-add .omniva-fake-btn");
  	var values_fields = $(table).find('.row-values .column-value input[type="number"]');
  	if (values_fields.length === 0) {
  		button[0].classList.remove("disabled");
  		return;
  	}
  	if ($(values_fields[values_fields.length - 1]).val()) {
  		button[0].classList.remove("disabled");
  	} else {
  		button[0].classList.add("disabled");
  	}
  }

  function omnivalt_checkAllRows(table) {
  	var value_step = $(table).data("step1");
  	var decimals = omnivalt_countDecimals(value_step);
  	var all_rows = $(table).find(".row-values");
  	var prev_value = 0;
  	var next_value = "";
  	for (var i=0; i<all_rows.length; i++) {
  		var span = $(all_rows[i]).find(".column-value .value-from")[0];
  		var input = $(all_rows[i]).find('.column-value input[type="number"]')[0];
  		if ((i + 1) < all_rows.length) {
  			next_value = $(all_rows[i+1]).find('.column-value input[type="number"]').val();
  		} else {
  			next_value = "";
  		}
  		var span_value = parseFloat(prev_value) + parseFloat(value_step);
  		var input_min = parseFloat(prev_value) + parseFloat(value_step);
  		if (i === 0) {
  			span_value = parseFloat(prev_value);
  			input_min = 0;
  		}
  		span.innerText = span_value.toFixed(decimals);
  		input.min = input_min.toFixed(decimals);
      if ((i+1) < all_rows.length) {
        if (input.value === "") {
          var new_value = parseFloat(prev_value);
          if (i > 0) {
            new_value = parseFloat(prev_value) + parseFloat(value_step);
          }
          input.value = new_value.toFixed(decimals);
        } else if (parseFloat(input.value) <= parseFloat(prev_value)) {
          input.value = (parseFloat(prev_value) + parseFloat(value_step)).toFixed(decimals);
        }
      }
      if ((i+1) == all_rows.length) {
      	if (input.value !== "" && parseFloat(input.value) <= parseFloat(prev_value)) {
      		input.value = (parseFloat(prev_value) + parseFloat(value_step)).toFixed(decimals);
      	}
      }
  		if (next_value) {
  			var input_max = parseFloat(next_value) - parseFloat(value_step);
  			input.max = input_max.toFixed(decimals);
  		} else {
  			input.max = "";
  		}
  		prev_value = input.value;
  	}
  }

  function omnivalt_checkNumberValue(elem, min = "", max = "", step = 0.01) { // TODO: Improve and then use
  	var value = elem.value;
  	if (min !== "" && elem.value < min) {
  		value = min.toFixed(decimals);
  	}
  	if (max !== "" && elem.value > max) {
  		value = max.toFixed(decimals);
  	}

  	value = parseFloat(value);
  	if (step !== "") {
  		var decimals = omnivalt_countDecimals(step);
  		value = value.toFixed(decimals);
  	}
  	elem.value = value;
  }

  function omnivalt_showPricesSection(select_field) {
  	var prices_single = $(select_field).closest(".sec-prices").find(".prices-single");
  	var prices_weight = $(select_field).closest(".sec-prices").find(".prices-table.table-weight");
  	var prices_amount = $(select_field).closest(".sec-prices").find(".prices-table.table-amount");
    var prices_boxsize = $(select_field).closest(".sec-prices").find(".prices-table.table-boxsize");
  	
  	if ($(select_field).val() != "simple") {
  		$(prices_single).slideUp("slow");
  	} else {
  		$(prices_single).slideDown("slow");
  	}
  	
  	if ($(select_field).val() != "weight") {
  		$(prices_weight).slideUp("slow");
  	} else {
  		$(prices_weight).slideDown("slow");
  	}
  	
  	if ($(select_field).val() != "amount") {
  		$(prices_amount).slideUp("slow");
  	} else {
  		$(prices_amount).slideDown("slow");
  	}

    if ($(select_field).val() != "boxsize") {
      $(prices_boxsize).slideUp("slow");
    } else {
      $(prices_boxsize).slideDown("slow");
    }
  }
});
