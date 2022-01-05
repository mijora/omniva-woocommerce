(function($) {
  $(document).on('change','input.manifest-item', function() {
    $("#call_quantity").val($('input.manifest-item:checkbox:checked').length);
    $("#call_quantity").trigger("change");
  });
  $(document).on('change','input.check-all', function() {
    $("#call_quantity").val($('input.manifest-item:checkbox:checked').length);
    $("#call_quantity").trigger("change");
  });

  $(document).on('click','#omniva-call-btn', function() {
    $("#call_quantity").trigger("change");
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
})(jQuery);
