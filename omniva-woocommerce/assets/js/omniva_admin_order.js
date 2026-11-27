var $ = jQuery;

$(document).ready(function() {
  omnivalt_hideTerminalOptions($("#omniva-order-country").val());

  $(document).on('change','#_shipping_country', function() {
    omnivalt_hideTerminalOptions($("#_shipping_country").val());
  });

  const services = document.querySelector('.omnivalt-order-services');
  if (services && window.omnivaltOrderServices) {
    omnivalt_bindOrderServices(services, window.omnivaltOrderServices);
  }
});

function omnivalt_hideTerminalOptions(show_only) {
  if (show_only) {
    $("#omnivalt_terminal optgroup").hide();
    $('#omnivalt_terminal optgroup[data-country="'+show_only+'"]').show();
  }
}

function omnivalt_bindOrderServices(services, settings) {
  const inputs = $(services).find('input[data-service]');
  const status = services.querySelector('.omnivalt-order-service-status');
  const summary = document.querySelector('.omnivalt-order-services-summary');
  const summaryText = summary.querySelector('[data-services]');
  const selectedServices = JSON.parse(summaryText.dataset.services);
  const form = services.closest('form');
  let saving = false;

  if (form) {
    form.addEventListener('submit', function(event) {
      if (saving) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
    }, true);
  }

  services.addEventListener('click', function(event) {
    const input = event.target;
    if (!input.matches('input[data-service]')) {
      return;
    }
    if (!event.isTrusted || saving) {
      event.preventDefault();
      return;
    }

    const service = input.dataset.service;
    const value = input.checked ? 'yes' : 'no';
    const hadFocus = document.activeElement === input;
    let confirmed = false;
    saving = true;
    inputs.prop('disabled', true);
    status.dataset.state = 'saving';
    status.textContent = settings.saving;

    $.ajax({
      url: settings.url,
      method: 'POST',
      dataType: 'json',
      timeout: 5000,
      data: {
        action: 'omnivalt_save_order_service',
        order_id: services.dataset.orderId,
        nonce: services.dataset.nonce,
        service: service,
        value: value
      }
    }).done(function(response) {
      if (!response || response.success !== true || !response.data || response.data.value !== value) {
        return;
      }

      confirmed = true;
      input.defaultChecked = value === 'yes';
      if (input.defaultChecked) {
        selectedServices[service] = input.labels[0].textContent;
      } else {
        delete selectedServices[service];
      }
      const titles = Object.values(selectedServices);
      summaryText.textContent = titles.join(', ');
      summary.hidden = titles.length === 0;
    }).always(function() {
      input.checked = input.defaultChecked;
      status.dataset.state = confirmed ? 'saved' : 'error';
      status.textContent = confirmed ? settings.saved : settings.error;
      saving = false;
      inputs.prop('disabled', false);
      if (hadFocus && document.activeElement === document.body) {
        input.focus();
      }
    });
  });

  inputs.prop('disabled', false);
}
