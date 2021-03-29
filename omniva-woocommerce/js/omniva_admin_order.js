var $ = jQuery;

$(document).ready(function() {
  omnivalt_hideTerminalOptions($("#omniva-order-country").val());

  $(document).on('change','#_shipping_country', function() {
    omnivalt_hideTerminalOptions($("#_shipping_country").val());
  });
});

function omnivalt_hideTerminalOptions(show_only) {
  if (show_only) {
    $("#omnivalt_terminal optgroup").hide();
    $('#omnivalt_terminal optgroup[data-country="'+show_only+'"]').show();
  }
}