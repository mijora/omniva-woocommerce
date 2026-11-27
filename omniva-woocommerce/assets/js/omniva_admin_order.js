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
  const summaryText = summary && summary.querySelector('[data-services]');

  if (!status || !summaryText) {
    return;
  }

  let selectedServices;
  try {
    selectedServices = JSON.parse(summaryText.dataset.services);
  } catch (error) {
    return;
  }

  if (!selectedServices || typeof selectedServices !== 'object' || Array.isArray(selectedServices)) {
    return;
  }

  const form = services.closest('form');
  const submitButtons = form
    ? Array.from(form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"], input[type="image"]'))
    : [];
  // Statuses the endpoint returns before writing anything; any other failure may follow a committed save.
  const rejectedStatuses = [400, 403, 404, 405];
  let saving = false;

  if (form) {
    form.addEventListener('submit', function(event) {
      if (saving) {
        event.preventDefault();
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
    let outcome = 'unknown';
    saving = true;
    inputs.prop('disabled', true);
    const submitButtonStates = submitButtons.map(function(button) {
      return button.disabled;
    });
    submitButtons.forEach(function(button) {
      button.disabled = true;
    });
    status.dataset.state = 'saving';
    status.textContent = settings.saving;

    $.ajax({
      url: settings.url,
      method: 'POST',
      dataType: 'json',
      timeout: 15000,
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

      outcome = 'saved';
      input.defaultChecked = value === 'yes';
      if (input.defaultChecked) {
        selectedServices[service] = input.labels[0].textContent;
      } else {
        delete selectedServices[service];
      }
      const titles = Object.values(selectedServices);
      summaryText.textContent = titles.join(', ');
      summary.hidden = titles.length === 0;
    }).fail(function(xhr) {
      const body = xhr.responseJSON;
      if (rejectedStatuses.indexOf(xhr.status) !== -1 && body && body.success === false) {
        outcome = 'rejected';
      }
    }).always(function() {
      input.checked = input.defaultChecked;
      if (outcome === 'unknown') {
        // The server may have stored the value, so keep this service locked until the order is reloaded.
        input.indeterminate = true;
        input.dataset.state = 'unknown';
        status.dataset.state = 'error';
        status.textContent = settings.error;
      } else {
        status.dataset.state = outcome === 'saved' ? 'saved' : 'error';
        status.textContent = outcome === 'saved' ? settings.saved : settings.rejected;
      }
      saving = false;
      inputs.filter(function() {
        return this.dataset.state !== 'unknown';
      }).prop('disabled', false);
      submitButtons.forEach(function(button, index) {
        button.disabled = submitButtonStates[index];
      });
      if (hadFocus && document.activeElement === document.body) {
        input.focus();
      }
    });
  });

  delete status.dataset.state;
  status.textContent = '';
  inputs.prop('disabled', false);
}
