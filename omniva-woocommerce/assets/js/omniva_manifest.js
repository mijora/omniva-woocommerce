(function($) {
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

  /* Functions */
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
        setTimeout(function() {
          $('#selected-orders').hide();
        }, 600);
      } else {
        omniva_setCookie(omnivaglobals.cookie_checked_list, JSON.stringify(cookie_value), 12*60);
      }

      omniva_remove_selected_item($('#selected-orders .item[data-id="' + value + '"]'));
    }
  }

  function omniva_add_selected_item(value) {
    var element = $('<span class="item" data-id="' + value + '">#' + value + '<span class="dashicons dashicons-no"></span></span>');
    element.appendTo('#selected-orders');
    element.addClass('adding');
    setTimeout(function() {
      $(element).removeClass('adding');
    }, 600);
  }

  function omniva_remove_selected_item(element) {
    $(element).addClass('removing');
    setTimeout(function() {
      $(element).remove();
    }, 600);
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
      $('.manifest-item').prop('checked', false);
      $('.check-all').prop('checked', false);
      $(form_selector).submit();
    }
  }
})(jQuery);
