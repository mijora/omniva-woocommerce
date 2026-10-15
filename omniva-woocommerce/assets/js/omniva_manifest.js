/* global omniva_eraseCookie, omniva_getCookie, omniva_setCookie, omnivaglobals, omnivatext */
(function($) {
  /* Mobile order details */
  $(document).on('click', '.omnivalt-manifest-page__mobile-details, .omnivalt-manifest-page__orders-card .customer-name', function() {
    var button = $(this);

    if (!button.hasClass('omnivalt-manifest-page__mobile-details')) {
      button = button.closest('.column-order_customer').find('.omnivalt-manifest-page__mobile-details').first();
    }

    omniva_toggle_mobile_details(button);
  });

  function omniva_toggle_mobile_details(button) {
    var row = button.closest('tr.data-row');
    var expanded = button.attr('aria-expanded') === 'true';
    var label = expanded ? button.attr('data-show-label') : button.attr('data-hide-label');

    row.toggleClass('is-mobile-expanded', !expanded);
    button.attr('aria-expanded', expanded ? 'false' : 'true');
    button.find('span:first').text(label);
  }

  $(document).on('change', '#omnivalt-manifest-page__tabs-select', function() {
    var target = $(this).val();

    if (target) {
      window.location.href = target;
    }
  });

  $(document).on('click', '#omnivalt-manifest-page__filter-toggle', function() {
    omniva_set_filter_drawer_open(true);
  });

  $(document).on('click', '#omnivalt-manifest-page__filter-close, .omnivalt-manifest-page__filter-backdrop', function() {
    omniva_set_filter_drawer_open(false);
  });

  $(document).on('keydown', function(event) {
    if (event.key === 'Escape') {
      omniva_set_filter_drawer_open(false);
    }
  });

  /* Checkbox events */
  $(document).on('click', '.check-all', function() {
    var checked = $(this).prop('checked');
    $(this).parents('table').find('.manifest-item').each(function() {
      $(this).prop('checked', checked);
      omniva_update_checked_list(this);
    });
  });
  $(document).on('change','input.manifest-item', function() {
    $("#call_quantity").val($('input.manifest-item:checkbox:checked').length);
    $("#call_quantity").trigger("change");
    omniva_update_checked_list(this);
  });
  $(document).on('change','input.check-all', function() {
    $("#call_quantity").val($('input.manifest-item:checkbox:checked').length);
    $("#call_quantity").trigger("change");
  });

  /* Selected list */
  $(document).on('click', '#selected-orders .item', function() {
    var value = $(this).attr("data-id");
    if (value) {
      var checkbox = $('input.manifest-item[value="' + value + '"]');
      if (checkbox.length) {
        $(checkbox).prop('checked', false);
        $(checkbox).trigger('change');
      } else {
        omniva_remove_from_checked_list(value);
      }
      omniva_remove_selected_item(this);
    }
  });

  /* Call courier */
  $(document).on('click', '#omniva-courier-modal', function(e) {
    if (e.target === this) {
      $('#omniva-courier-modal').removeClass('open');
    }
  });

  $(document).on('click', '#omniva-call-btn', function(e) {
    e.preventDefault();
    $('#omniva-courier-modal .modal-content').hide();
    $('#modal-content-call').show();
    $('#omniva-courier-modal').addClass('open');
    $("#call_quantity").trigger("change");
  });

  $(document).on('click', '#omniva-call-cancel-btn', function(e) {
    e.preventDefault();
    $('#omniva-courier-modal').removeClass('open');
  });

  $(document).on('change', '#call_quantity', function(e){
    var min=parseFloat($(this).attr('min'));
    var max=parseFloat($(this).attr('max'));
    var curr=parseFloat($(this).val());
    if (curr > max) { $(this).val(max); }
    if (curr < min) { $(this).val(min); }

    if (curr <= 0) {
      $('#omniva-call-confirm-btn').prop('disabled', true);
    } else {
      $('#omniva-call-confirm-btn').prop('disabled', false);
    }
  });

  /* Cancel courier */
  $(document).on('click', '.current_calls .action-cancel', function(e) {
    e.preventDefault();
    $('#omniva-courier-modal .modal-content').hide();
    $('#modal-content-cancel').show();
    var call_id = $(this).siblings('input[name="call_id"]')[0].value;
    $('#omniva-cancel-id').val(call_id);
    $('#omniva-courier-modal').addClass('open');
  });

  /* Remove courier arrival time */
  $(document).on('click', '.current_calls .action-remove', function(e) {
    e.preventDefault();
    var call_id = $(this).siblings('input[name="call_id"]')[0].value;
    $.ajax({
      type: "post",
      dataType: "json",
      url: "/wp-admin/admin-ajax.php",
      data: {
        action: 'remove_courier_call',
        call_id: call_id
      },
      success: function(response) {
        //console.log(response);
        if ( response.status == "error" ) {
          console.log("Error", response.msg);
        }
        if ( response.status == "OK" ) {
          var all_calls = $('.current_calls input[name="call_id"]');
          for ( var i = 0; i < all_calls.length; i++ ) {
            if ( all_calls[i].value == call_id ) {
              var row = $(all_calls[i]).closest("tr");
              $(row).css("color", "#ccc");
              setTimeout(function() {
                $(row).remove();
              }, 1000);
            }
          }
        }
      },
      error: function (jqXHR, exception) {
        console.log("Critical error", jqXHR);
      }
    });
  });

  /* Submit buttons */
  $(document).on('click', '#submit_manifest_labels_1, #submit_manifest_labels_2', function() {
    omniva_submit_bulk_action('#labels-print-form');
  });

  $(document).on('click', '#submit_manifest_items_1, #submit_manifest_items_2', function() {
    omniva_submit_bulk_action('#manifest-print-form');
  });

  /* Row action tooltips */
  $(document).on('mouseenter focusin', '.omnivalt-manifest-page__row-action, .omnivalt-manifest-page__tooltip-trigger', function() {
    omniva_show_action_tooltip(this);
  });

  $(document).on('mouseleave focusout', '.omnivalt-manifest-page__row-action, .omnivalt-manifest-page__tooltip-trigger', function() {
    omniva_remove_action_tooltip();
  });

  $(function() {
    omniva_update_selected_summary();
  });

  function mark_manifest_ready() {
    $('#omnivalt-manifest-root').addClass('is-ready');
  }

  if (document.readyState === 'complete') {
    mark_manifest_ready();
  } else {
    $(window).one('load', mark_manifest_ready);
  }

  /* Functions */
  function omniva_set_filter_drawer_open(open) {
    var filters = $('#omnivalt-manifest-page__filters');
    var backdrop = $('.omnivalt-manifest-page__filter-backdrop');
    var toggle = $('#omnivalt-manifest-page__filter-toggle');

    if (!filters.length) {
      return;
    }

    filters.toggleClass('is-open', open).attr('aria-hidden', open ? 'false' : 'true');
    backdrop.toggleClass('is-visible', open);
    toggle.attr('aria-expanded', open ? 'true' : 'false');
    $('body').toggleClass('omnivalt-manifest-page__filter-open', open);

    if (!open) {
      toggle.trigger('focus');
    }
  }

  function omniva_update_checked_list(checkbox) {
    var value = $(checkbox).val();
    var cookie_value = [];

    if ($(checkbox).is(':checked')) {
      omniva_add_to_checked_list(value);
    } else {
      omniva_remove_from_checked_list(value);
    }
  }

  function omniva_add_to_checked_list(value) {
    var cookie_value = [];
    if (omniva_getCookie(omnivaglobals.cookie_checked_list) == null) {
      $('#selected-orders').show();
      cookie_value = [value];
      omniva_add_selected_item(value);
    } else {
      var current_cookie = omniva_getCookie(omnivaglobals.cookie_checked_list);
      cookie_value = JSON.parse(current_cookie);
      if (!cookie_value.includes(value)) {
        cookie_value.push(value);
        omniva_add_selected_item(value);
      }
    }
    omniva_set_selected_actions_visible(true);
    omniva_setCookie(omnivaglobals.cookie_checked_list, JSON.stringify(cookie_value), 12*60);
  }

  function omniva_remove_from_checked_list(value) {
    var cookie_value = [];
    if (omniva_getCookie(omnivaglobals.cookie_checked_list) != null) {
      var current_cookie = omniva_getCookie(omnivaglobals.cookie_checked_list);
      cookie_value = JSON.parse(current_cookie);
      for (var i=0;i<cookie_value.length;i++) {
        if (cookie_value[i] == value) {
          cookie_value.splice(i, 1);
        }
      }
      if (cookie_value.length == 0) {
        omniva_eraseCookie(omnivaglobals.cookie_checked_list);
        $('#selected-orders').hide();
        omniva_set_selected_actions_visible(false);
      } else {
        omniva_setCookie(omnivaglobals.cookie_checked_list, JSON.stringify(cookie_value), 12*60);
      }

      omniva_remove_selected_item($('#selected-orders .item[data-id="' + value + '"]'));
    }
  }

  function omniva_add_selected_item(value) {
    var checkbox = $('.manifest-item').filter(function() {
      return String($(this).val()) === String(value);
    }).first();
    var has_barcodes = checkbox.attr('data-has-barcodes') === '1' ? '1' : '0';
    var element = $('<span class="item"></span>');

    element.attr('data-id', value);
    element.attr('data-has-barcodes', has_barcodes);
    element.text('#' + value).append('<span class="dashicons dashicons-no"></span>');
    element.appendTo('#selected-orders');
  }

  function omniva_remove_selected_item(element) {
    $(element).remove();
    omniva_update_selected_summary();
  }

  function omniva_update_selected_summary() {
    var selected_orders = $('#selected-orders');
    var selected_count = selected_orders.find('.item').length;
    var has_many_selected = selected_count > 3;

    selected_orders.find('.selected-count').text(selected_count);
    selected_orders.closest('.omnivalt-manifest-page__bulk-actions').toggleClass('has-many-selected', has_many_selected);
    omniva_update_label_action_state();
  }

  function omniva_set_selected_actions_visible(visible) {
    omniva_update_selected_summary();
    $('.omnivalt-manifest-page__bulk-actions, .omnivalt-manifest-page__selection-actions, .omnivalt-manifest-page__bottom-actions').toggleClass('is-visible', visible);
  }

  function omniva_update_label_action_state() {
    var bulk_actions = $('.omnivalt-manifest-page__bulk-actions').first();
    var selected_orders = $('#selected-orders .item');
    var sender_info_complete = bulk_actions.attr('data-sender-info-complete') === '1';
    var has_existing_labels = selected_orders.filter('[data-has-barcodes="1"]').length > 0;
    var disabled = !sender_info_complete && !has_existing_labels;
    var buttons = $('#submit_manifest_labels_1, #submit_manifest_labels_2');

    buttons.prop('disabled', disabled);
    if ( disabled ) {
      buttons.attr('aria-disabled', 'true');
    } else {
      buttons.removeAttr('aria-disabled');
    }

    $('.omnivalt-manifest-page__tooltip-trigger').each(function() {
      var trigger = $(this);
      var tooltip = disabled ? trigger.attr('data-sender-tooltip') : '';
      var button_title = trigger.find('button').first().attr('title') || '';

      trigger.attr('data-tooltip', tooltip || '');
      trigger.attr('aria-label', disabled ? (tooltip || button_title) : button_title);
    });
  }

  function omniva_submit_bulk_action(form_selector) {
    var ids = [];
    $(form_selector + ' .post_id').remove();
    if (omniva_getCookie(omnivaglobals.cookie_checked_list) != null) {
      var current_cookie = omniva_getCookie(omnivaglobals.cookie_checked_list);
      ids = JSON.parse(current_cookie);
    }
    $('.manifest-item:checked').each(function() {
      var id = $(this).val();
      if (!ids.includes(id)) {
        ids.push(id);
      }
    });
    for (var i=0; i<ids.length; i++) {
      $(form_selector).append('<input type="hidden" class = "post_id" name="post[]" value = "' + ids[i] + '" />');
    }
    if (!ids.length) {
      alert(omnivatext.alert_select_orders);
    } else {
      omniva_eraseCookie(omnivaglobals.cookie_checked_list);
      $('#selected-orders .item').remove();
      $('#selected-orders').hide();
      omniva_set_selected_actions_visible(false);
      $('.manifest-item').prop('checked', false);
      $('.check-all').prop('checked', false);
      $(form_selector).submit();
    }
  }

  function omniva_show_action_tooltip(element) {
    var text = $(element).attr('data-tooltip');
    var rect;
    var tooltip;
    var top;
    var left;

    omniva_remove_action_tooltip();

    if (!text || !element.getBoundingClientRect) {
      return;
    }

    tooltip = $('<div class="omnivalt-manifest-page__floating-tooltip" role="tooltip"></div>');
    tooltip.text(text);
    tooltip.appendTo('body');

    rect = element.getBoundingClientRect();
    left = rect.left + (rect.width / 2) - (tooltip.outerWidth() / 2);
    left = Math.max(8, Math.min(left, window.innerWidth - tooltip.outerWidth() - 8));
    top = rect.top - tooltip.outerHeight() - 8;

    if (top < 8) {
      top = rect.bottom + 8;
    }

    tooltip.css({
      left: left,
      top: top
    });
  }

  function omniva_remove_action_tooltip() {
    $('.omnivalt-manifest-page__floating-tooltip').remove();
  }
})(jQuery);
