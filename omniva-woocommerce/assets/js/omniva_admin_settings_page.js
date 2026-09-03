jQuery(function($) {
  'use strict';

  var $root = $('#omnivalt-settings-root');
  var pageSettings = window.omnivaltSettingsPage || {};
  var textSettings = pageSettings.txt || {};

  if (!$root.length) {
    return;
  }

  var refreshShippingMethodsLayout = initializeShippingMethodsLayout();

  initializeSettingsDependencies(refreshShippingMethodsLayout);
  initializePriceTables();
  initializePositionSortable();
  $root.removeClass('is-layout-pending');

  function getAvailableMethods() {
    var apiCountry = $root.find('#woocommerce_omnivalt_api_country').val();
    var availableMethods = pageSettings.available_methods || {};

    return availableMethods[apiCountry] || {};
  }

  function isMethodAvailable(countryMethods, methodKey) {
    if (!countryMethods) {
      return false;
    }

    var aliases = {
      'pt': ['pt', 'terminal', 'pickup'],
      'c': ['c', 'courier'],
      'cp': ['cp', 'courier_plus'],
      'pc': ['pc', 'private_customer'],
      'pn': ['pn', 'post_near'],
      'ps': ['ps', 'post_specific'],
      'lc': ['lc', 'letter_courier'],
      'lp': ['lp', 'letter_post']
    };
    var methodAliases = aliases[methodKey] || [methodKey];

    for (var i = 0; i < methodAliases.length; i++) {
      if ($.inArray(methodAliases[i], countryMethods) !== -1) {
        return true;
      }
    }

    return false;
  }

  function initializeSettingsDependencies(refreshLayout) {
    var methodFieldClasses = {
      'pt': 'terminal',
      'c': 'courier',
      'cp': 'courier_plus',
      'pc': 'private_customer',
      'pn': 'post_near',
      'ps': 'post_specific',
      'lc': 'letter_courier',
      'lp': 'letter_post'
    };

    function toggleRows($checkbox, fieldsSelector) {
      $root.find(fieldsSelector).closest('tr').toggleClass('hidden', !$checkbox.is(':checked'));
    }

    function refreshMethodRows($checkbox) {
      var methodKey = $checkbox.closest('[data-omniva-method-group]').attr('data-method-key');
      var fieldClass = methodFieldClasses[methodKey];

      if (fieldClass) {
        toggleRows($checkbox, '.omniva_' + fieldClass);
      }
    }

    function refreshApiCountryMethods() {
      var availableMethods = getAvailableMethods();

      $root.find('input[id^="woocommerce_omnivalt_method_"]').each(function() {
        var $checkbox = $(this);
        var methodKey = $checkbox.closest('[data-omniva-method-group]').attr('data-method-key');
        var methodAvailable = false;

        $.each(availableMethods, function(countryCode, countryMethods) {
          if (isMethodAvailable(countryMethods, methodKey)) {
            methodAvailable = true;
            return false;
          }
        });

        if (!methodAvailable) {
          $checkbox.prop('checked', false);
        }

        $checkbox.prop('disabled', !methodAvailable);
        refreshMethodRows($checkbox);
      });
    }

    function refreshPluginState() {
      var $enabled = $root.find('#woocommerce_omnivalt_enabled');
      var $notice = $root.find('#omnivalt-plugin-disabled-notice');

      if (!$enabled.length || $enabled.is(':checked')) {
        $notice.remove();
        return;
      }

      if (!$notice.length) {
        $('<span id="omnivalt-plugin-disabled-notice" class="omnivalt-settings-page__save-notice"></span>')
          .text(textSettings.disabled_notice || '')
          .appendTo($root.find('[data-settings-save-notices]').first());
      }
    }

    function refreshDebugFields() {
      var $debug = $root.find('#woocommerce_omnivalt_debug_mode');
      var debugEnabled = !$debug.length || $debug.is(':checked');
      var showDeveloperFields = window.location.hash.slice(1) === 'dev';

      if ($debug.length) {
        toggleRows($debug, '.omniva_debug');
      }

      $root.find('.omniva_dev').closest('tr').toggleClass('hidden', !debugEnabled || !showDeveloperFields);
    }

    function refreshAutoLabelFields() {
      var $autoLabels = $root.find('#woocommerce_omnivalt_auto_generate_labels');

      if ($autoLabels.length) {
        toggleRows($autoLabels, '.omniva_auto_labels');
      }
    }

    function refreshFreeShippingField($checkbox) {
      var $field = $checkbox.closest('.prices-free').find('.price_free');

      $field
        .toggleClass('disabled', !$checkbox.is(':checked'))
        .prop('readonly', !$checkbox.is(':checked'));
    }

    function refreshCouponField($checkbox) {
      var $field = $checkbox.closest('.prices-coupon').find('.price_coupon');

      $field
        .toggleClass('disabled', !$checkbox.is(':checked'))
        .prop('disabled', !$checkbox.is(':checked'));
    }

    $.each(methodFieldClasses, function(methodKey) {
      var $checkbox = $root.find('#woocommerce_omnivalt_method_' + methodKey);

      if ($checkbox.length) {
        refreshMethodRows($checkbox);
      }
    });

    $root.find('.prices-free input[type="checkbox"]').each(function() {
      refreshFreeShippingField($(this));
    });
    $root.find('.prices-coupon input[type="checkbox"]').each(function() {
      refreshCouponField($(this));
    });

    refreshApiCountryMethods();
    refreshPluginState();
    refreshDebugFields();
    refreshAutoLabelFields();
    refreshLayout();

    $root.on('change', '#woocommerce_omnivalt_enabled', refreshPluginState);
    $root.on('change', '#woocommerce_omnivalt_debug_mode', refreshDebugFields);
    $root.on('change', '#woocommerce_omnivalt_auto_generate_labels', refreshAutoLabelFields);
    $root.on('change', '#woocommerce_omnivalt_api_country', function() {
      refreshApiCountryMethods();
      refreshLayout();
    });
    $root.on('change', 'input[id^="woocommerce_omnivalt_method_"]', function() {
      refreshMethodRows($(this));
      refreshLayout();
    });
    $root.on('change', '.prices-free input[type="checkbox"]', function() {
      refreshFreeShippingField($(this));
    });
    $root.on('change', '.prices-coupon input[type="checkbox"]', function() {
      refreshCouponField($(this));
    });
    $root.on('click', '.debug-row .date', function() {
      var $date = $(this);

      $date.toggleClass('active');
      $date.siblings('textarea').stop(true, true).slideToggle('slow');
    });

    window.addEventListener('hashchange', refreshDebugFields);
  }

  function initializeShippingMethodsLayout() {
    var $layout = $root.find('[data-omniva-shipping-layout]');
    if (!$layout.length) {
      return $.noop;
    }

    var $groups = $layout.find('[data-omniva-method-group]');

    $groups.each(function() {
      var $group = $(this);

      $group.find('[data-omniva-destination]').each(function() {
        var $destination = $(this);
        var destinationId = $destination.attr('id');
        var destinationTitle = $.trim($destination.find('.omniva-delivery-country__destination-header .omniva-delivery-country__title').first().text());
        var $destinationTab = $group.find('[data-omniva-country-tab]').filter(function() {
          return $(this).attr('data-omniva-destination-id') === destinationId;
        }).first();
        var $destinationButton = $destinationTab.find('[role="tab"]').first();
        var $destinationToggle = $destinationTab.find('input[data-omniva-destination-toggle]').first();

        $destination.data('omniva-destination-tab', $destinationTab);
        $destinationTab.data('omniva-destination', $destination);
        $destinationButton.data('omniva-destination', $destination);
        $destinationToggle.data('omniva-destination', $destination);

        $destinationButton.on('click', function() {
          activateDestination($(this).data('omniva-destination'));
        });

        if ($destination.attr('data-destination-type') === 'country') {
          var $countryToggle = $destinationTab.find('[data-omniva-country-toggle-wrap] input[type="checkbox"]').first();

          $countryToggle
            .attr('data-omniva-country-toggle', 'true')
            .attr('aria-label', destinationTitle)
            .data('omniva-destination', $destination);

          $destination.find('.block-prices[data-method]').each(function() {
            $(this).data('omniva-country-toggle', $countryToggle);
          });
          return;
        }

        $destination.find('.block-prices[data-method]').each(function() {
          var $block = $(this);
          var $methodHeader = $block.prev('.omniva-delivery-country__method-header');
          var $regionToggle = $methodHeader.find('input[type="checkbox"]').first();
          var regionTitle = $.trim($methodHeader.find('label').first().text()) || destinationTitle;

          $regionToggle
            .attr('data-omniva-country-toggle', 'true')
            .attr('aria-label', regionTitle)
            .data('omniva-destination', $destination);
          $block.data('omniva-country-toggle', $regionToggle);
        });
      });
    });

    $root.on('change', 'input[data-omniva-country-toggle]', function() {
      var $destination = getDestinationForControl($(this));

      if (!$destination.length) {
        return;
      }

      $destination.find('.block-prices[data-method]').each(function() {
        updateCountryBlock($(this), false);
      });

      if ($(this).is(':checked')) {
        activateDestination($destination);
      } else {
        refreshDestinationTabs($destination.closest('[data-omniva-method-group]'));
      }
    });
    $root.on('change', 'input[data-omniva-destination-toggle]', function() {
      var $destination = getDestinationForControl($(this));

      if (!$destination.length) {
        return;
      }

      var enabled = $(this).is(':checked');

      $destination.find('input[data-omniva-country-toggle]').each(function() {
        var $regionToggle = $(this);
        if ($regionToggle.is(':checked') !== enabled) {
          $regionToggle.prop('checked', enabled).trigger('change');
        }
      });

      if (enabled) {
        activateDestination($destination);
      } else {
        refreshDestinationTabs($destination.closest('[data-omniva-method-group]'));
      }
    });

    refreshShippingMethodsLayout();
    return refreshShippingMethodsLayout;

    function refreshShippingMethodsLayout() {
      var availableMethods = getAvailableMethods();

      $groups.each(function() {
        var $group = $(this);
        var methodKey = $group.attr('data-method-key');
        if (methodKey === 'international') {
          $group.find('[data-omniva-destination] .block-prices[data-method]').each(function() {
            updateCountryBlock($(this), false);
          });
          $group.toggle($group.find('[data-omniva-destination]').length > 0);
          refreshDestinationTabs($group);
          return;
        }

        var $globalField = $root.find('#woocommerce_omnivalt_method_' + methodKey);
        var methodEnabled = !$globalField.length || ($globalField.is(':checked') && !$globalField.is(':disabled'));
        $group.toggleClass('is-method-disabled', !methodEnabled);

        $group.find('[data-omniva-destination]').each(function() {
          var $destination = $(this);
          var countryCode = $destination.attr('data-destination-key');
          var allowed = isMethodAvailable(availableMethods[countryCode], methodKey);
          var $destinationTab = $destination.data('omniva-destination-tab');

          $destination.toggleClass('is-unavailable', !allowed);
          if ($destinationTab && $destinationTab.length) {
            $destinationTab.toggleClass('is-unavailable', !allowed);
          }
          $destination.find('.block-prices[data-method]').each(function() {
            var $block = $(this);
            $block.toggleClass('is-method-disabled', !methodEnabled || !allowed);
            updateCountryBlock($block, !methodEnabled || !allowed);
          });
        });

        refreshDestinationTabs($group);
      });
    }

    function updateCountryBlock($block, methodDisabled) {
      var $destination = $block.closest('[data-omniva-destination]');
      var $destinationTab = $destination.data('omniva-destination-tab');
      var $toggle = $block.data('omniva-country-toggle');
      if (!$toggle || !$toggle.length) {
        $toggle = $block.find('input[data-omniva-country-toggle]').first();
      }
      if (!$toggle.length) {
        $toggle = $destination.find('input[data-omniva-country-toggle]').first();
      }
      var enabled = !$toggle.length || $toggle.is(':checked');
      var editable = enabled && !methodDisabled;
      $block.toggleClass('is-country-disabled', !enabled);
      $block.toggleClass('disabled', !!methodDisabled);
      $block.find('.sec-prices, .sec-other').toggleClass('disabled', !editable);
      if ($destination.length) {
        var $countryToggles = $destination.find('input[data-omniva-country-toggle]');
        if (!$countryToggles.length && $destinationTab && $destinationTab.length) {
          $countryToggles = $destinationTab.find('input[data-omniva-country-toggle]');
        }
        var hasEnabledBlock = $countryToggles.filter(':checked').length > 0;
        $destination.toggleClass('is-country-disabled', !hasEnabledBlock);
        if ($destinationTab && $destinationTab.length) {
          $destinationTab.toggleClass('is-country-disabled', !hasEnabledBlock);
          $destinationTab.find('[role="tab"]')
            .prop('disabled', !hasEnabledBlock)
            .attr('aria-disabled', hasEnabledBlock ? 'false' : 'true');
          $destinationTab.find('input[data-omniva-destination-toggle]').prop('checked', hasEnabledBlock);
        }
      }
    }

    function getDestinationForControl($control) {
      var $destination = $control.closest('[data-omniva-destination]');
      if ($destination.length) {
        return $destination;
      }

      $destination = $control.data('omniva-destination');
      if ($destination && $destination.length) {
        return $destination;
      }

      $destination = $control.closest('[data-omniva-country-tab]').data('omniva-destination');
      return ($destination && $destination.length) ? $destination : $();
    }

    function activateDestination($destination) {
      if (!$destination || !$destination.length || $destination.hasClass('is-unavailable')) {
        return;
      }

      var $list = $destination.closest('[data-method-country-list]');
      var $group = $destination.closest('[data-omniva-method-group]');
      var $tabs = $group.find('[data-method-country-tabs]').first();
      var $destinationTab = $destination.data('omniva-destination-tab');

      if (!$list.length || !$destinationTab || !$destinationTab.length || $destination.hasClass('is-country-disabled') || $destinationTab.hasClass('is-country-disabled')) {
        return;
      }

      $list.children('[data-omniva-destination]')
        .removeClass('is-active')
        .attr('aria-hidden', 'true')
        .attr('hidden', 'hidden');
      $tabs.children('[data-omniva-country-tab]')
        .removeClass('is-active')
        .find('[role="tab"]')
        .attr('aria-selected', 'false')
        .attr('tabindex', '-1');

      $destination.addClass('is-active').attr('aria-hidden', 'false').removeAttr('hidden');
      $destinationTab.addClass('is-active');
      $destinationTab.find('[role="tab"]')
        .attr('aria-selected', 'true')
        .attr('tabindex', '0');
    }

    function refreshDestinationTabs($group) {
      var $destinations = $group.find('[data-omniva-destination]');
      var $availableDestinations = $destinations.filter(':not(.is-unavailable):not(.is-country-disabled)');
      var $activeDestination = $destinations.filter('.is-active').first();

      if (
        !$activeDestination.length ||
        $activeDestination.hasClass('is-unavailable') ||
        $activeDestination.hasClass('is-country-disabled')
      ) {
        $activeDestination = $availableDestinations.first();
      }

      if ($activeDestination.length) {
        activateDestination($activeDestination);
        return;
      }

      $destinations.removeClass('is-active').attr('aria-hidden', 'true').attr('hidden', 'hidden');
      $group.find('[data-method-country-tabs] [data-omniva-country-tab]')
        .removeClass('is-active')
        .find('[role="tab"]')
        .attr('aria-selected', 'false')
        .attr('tabindex', '-1');
    }

  }

  function initializePriceTables() {
    var rowSequence = 0;

    $root.find('.prices-table > table').each(function() {
      var $table = $(this);
      var initialRows = $table.find('.row-values').toArray();

      checkAllRows($table);
      checkTableAddRowButton($table);

      $table.data('omnivaltPricesReset', function() {
        var $footer = $table.find('.row-footer').first();

        $table.find('.row-values').detach();
        $.each(initialRows, function(index, row) {
          $(row).insertBefore($footer);
        });
        checkAllRows($table);
        checkTableAddRowButton($table);
      });
    });

    $root.find('.sec-prices .price_type').each(function() {
      showPricesSection($(this), false);
    });

    $root.on('change', '.prices-table .row-values .column-value input[type="number"]', function() {
      var $table = $(this).closest('table');

      checkAllRows($table);
      checkTableAddRowButton($table);
    });
    $root.on('click', '.omniva-fake-btn', function() {
      var action = $(this).attr('data-action');

      if (action === 'add_prices_table_row') {
        addPricesTableRow($(this));
      } else if (action === 'remove_prices_table_row') {
        removePricesTableRow($(this));
      }
    });
    $root.on('change', '.price_type', function() {
      showPricesSection($(this), true);
    });

    function addPricesTableRow($button) {
      var $buttonRow = $button.closest('tr');
      var $table = $button.closest('table');
      var $previousRows = $buttonRow.parent().find('.row-values');
      var valueStep = $table.attr('data-step1');
      var priceStep = $table.attr('data-step2');
      var decimals = countDecimals(valueStep);
      var valueFrom = 0;
      var fromLabel = valueFrom.toFixed(decimals);
      var rowKey = String(new Date().getTime()) + String(rowSequence++);

      if ($previousRows.length) {
        var previousValue = $previousRows.last().find('.column-value input[type="number"]').val();

        if (previousValue) {
          valueFrom = parseFloat(previousValue) + parseFloat(valueStep);
          fromLabel = valueFrom.toFixed(decimals);
        } else if (previousValue === '') {
          fromLabel = '???';
        }
      }

      var $valueColumn = $('<td class="column-value"></td>');
      var $from = $('<span class="row-from"></span>');
      var $fromValue = $('<span class="value-from"></span>')
        .attr('data-step', parseFloat(valueStep))
        .text(fromLabel);
      var $valueInput = $('<input type="number" class="input-text regular-input" placeholder="...">')
        .attr({
          'name': $table.attr('data-name') + '[' + rowKey + '][value]',
          'id': $table.attr('data-id') + '_value_' + rowKey,
          'step': valueStep,
          'min': valueFrom
        });
      var $priceInput = $('<input type="number" class="input-text regular-input">')
        .attr({
          'name': $table.attr('data-name') + '[' + rowKey + '][price]',
          'id': $table.attr('data-id') + '_price_' + rowKey,
          'step': priceStep,
          'min': 0
        });
      var $remove = $('<div class="omniva-fake-btn" data-action="remove_prices_table_row">X</div>');
      var $newRow = $('<tr class="row-values"></tr>');

      $from.append($fromValue, document.createTextNode(' - '));
      $valueColumn.append($from, $valueInput);
      $newRow.append(
        $valueColumn,
        $('<td class="column-price"></td>').append($priceInput),
        $('<td class="column-actions"></td>').append($remove)
      );
      $newRow.insertBefore($buttonRow);
      checkTableAddRowButton($table);
    }

    function removePricesTableRow($button) {
      var $table = $button.closest('table');

      $button.closest('tr').remove();
      checkAllRows($table);
      checkTableAddRowButton($table);
    }

    function countDecimals(value) {
      var numericValue = parseFloat(value);

      if (numericValue % 1 !== 0) {
        return String(value).split('.')[1].length;
      }

      return 0;
    }

    function checkTableAddRowButton($table) {
      var $button = $table.find('.row-footer .column-add .omniva-fake-btn');
      var $valueFields = $table.find('.row-values .column-value input[type="number"]');

      if (!$button.length) {
        return;
      }

      if (!$valueFields.length || $valueFields.last().val()) {
        $button.removeClass('disabled');
      } else {
        $button.addClass('disabled');
      }
    }

    function checkAllRows($table) {
      var valueStep = $table.attr('data-step1');
      var decimals;
      var $rows;
      var previousValue = 0;

      if (valueStep === undefined || valueStep === null || valueStep === '') {
        return;
      }

      decimals = countDecimals(valueStep);
      $rows = $table.find('.row-values').filter(function() {
        return $(this).find('.column-value .value-from').length &&
          $(this).find('.column-value input[type="number"]').length;
      });

      $rows.each(function(index) {
        var $row = $(this);
        var $input = $row.find('.column-value input[type="number"]').first();
        var $from = $row.find('.column-value .value-from').first();
        var nextValue = index + 1 < $rows.length
          ? $rows.eq(index + 1).find('.column-value input[type="number"]').val()
          : '';
        var fromValue = index === 0 ? 0 : parseFloat(previousValue) + parseFloat(valueStep);
        var minimum = index === 0 ? 0 : parseFloat(previousValue) + parseFloat(valueStep);

        $from.text(fromValue.toFixed(decimals));
        $input.attr('min', minimum.toFixed(decimals));

        if (index + 1 < $rows.length) {
          if ($input.val() === '') {
            $input.val((index === 0 ? parseFloat(previousValue) : minimum).toFixed(decimals));
          } else if (parseFloat($input.val()) <= parseFloat(previousValue)) {
            $input.val(minimum.toFixed(decimals));
          }
        } else if ($input.val() !== '' && parseFloat($input.val()) <= parseFloat(previousValue)) {
          $input.val(minimum.toFixed(decimals));
        }

        if (nextValue) {
          $input.attr('max', (parseFloat(nextValue) - parseFloat(valueStep)).toFixed(decimals));
        } else {
          $input.removeAttr('max');
        }

        previousValue = $input.val();
      });
    }

    function showPricesSection($select, animate) {
      var $section = $select.closest('.sec-prices');
      var selectedType = $select.val();
      var sections = {
        'simple': $section.find('.prices-single'),
        'weight': $section.find('.prices-table.table-weight'),
        'amount': $section.find('.prices-table.table-amount'),
        'boxsize': $section.find('.prices-table.table-boxsize')
      };

      $.each(sections, function(type, $elements) {
        var visible = selectedType === type;

        if (animate) {
          $elements.stop(true, true)[visible ? 'slideDown' : 'slideUp']('slow');
        } else {
          $elements.toggle(visible);
        }
      });
    }
  }

  function initializePositionSortable() {
    $root.find('.field-position').each(function() {
      var $fieldset = $(this);
      var $table = $fieldset.children('table').first();
      var $rows = $table.find('tr');
      var $list = $('<ol class="omnivalt-position-list" data-settings-position-list></ol>');
      var items = [];

      if (!$table.length || !$rows.length) {
        return;
      }

      for (var rowIndex = 0; rowIndex < $rows.length; rowIndex += 2) {
        var $labelCells = $rows.eq(rowIndex).children('th');
        var $valueCells = $rows.eq(rowIndex + 1).children('td');

        $labelCells.each(function(cellIndex) {
          var $input = $valueCells.eq(cellIndex).find('input[type="number"]').first();

          if (!$input.length) {
            return;
          }

          items.push({
            input: $input,
            title: $.trim($(this).text())
          });
        });
      }

      if (!items.length) {
        return;
      }

      $.each(items, function(index, item) {
        var $handle = $('<span class="omnivalt-position-list__handle" aria-hidden="true">&#8942;</span>');
        var $title = $('<span class="omnivalt-position-list__title"></span>').text(item.title);
        var $listItem = $('<li class="omnivalt-position-list__item"></li>');

        $listItem.append($handle, $title, item.input);
        $list.append($listItem);
      });

      $table.replaceWith($list);
      sortPositionItems($list);
      var initialOrder = $list.children('.omnivalt-position-list__item').toArray();

      if ($.fn.sortable) {
        $list.sortable({
          axis: 'y',
          cursor: 'grabbing',
          forcePlaceholderSize: true,
          placeholder: 'omnivalt-position-list__placeholder',
          update: function() {
            updatePositionValues($list);
          }
        });
      }

      $list.data('omnivaltPositionReset', function() {
        $.each(initialOrder, function(index, item) {
          $list.append(item);
        });

        if ($.fn.sortable && $list.hasClass('ui-sortable')) {
          $list.sortable('refresh');
        }
      });
    });

    function updatePositionValues($list) {
      $list.children('.omnivalt-position-list__item').each(function(index) {
        $(this).find('input[type="number"]').val(index + 1).trigger('change');
      });
    }

    function sortPositionItems($list) {
      var positioned = [];
      var unpositioned = [];
      var ordered = [];

      $list.children('.omnivalt-position-list__item').each(function(index) {
        var value = $(this).find('input[type="number"]').val();
        var position = parseInt(value, 10);

        if (value !== '' && !isNaN(position) && position !== 0) {
          positioned.push({
            item: this,
            position: position,
            originalIndex: index
          });
        } else {
          unpositioned.push(this);
        }
      });

      positioned.sort(function(first, second) {
        if (first.position === second.position) {
          return first.originalIndex - second.originalIndex;
        }

        return first.position - second.position;
      });

      $.each(positioned, function(index, positionedItem) {
        var targetIndex = positionedItem.position > 0 ? positionedItem.position - 1 : 0;

        while (ordered[targetIndex]) {
          targetIndex++;
        }

        ordered[targetIndex] = positionedItem.item;
      });

      $.each(unpositioned, function(index, item) {
        var targetIndex = 0;

        while (ordered[targetIndex]) {
          targetIndex++;
        }

        ordered[targetIndex] = item;
      });

      $.each(ordered, function(index, item) {
        if (item) {
          $list.append(item);
        }
      });
    }
  }

  // Keep sender details visually grouped as a compact data-entry form while
  // preserving WooCommerce's generated field names and values.
  $root.find( [
    '#woocommerce_omnivalt_company',
    '#woocommerce_omnivalt_shop_name',
    '#woocommerce_omnivalt_shop_city',
    '#woocommerce_omnivalt_shop_address',
    '#woocommerce_omnivalt_shop_postcode',
    '#woocommerce_omnivalt_shop_email',
    '#woocommerce_omnivalt_bank_account'
  ].join(', ') ).each(function() {
    var $input = $(this);
    var $row = $input.closest('tr');
    var $label = $row.children('th').find('label').first();

    if (!$label.length) {
      return;
    }

    $row.addClass('omnivalt-floating-field');
    $input.wrap('<span class="omnivalt-floating-input"></span>');

    var $wrapper = $input.parent();
    $label.addClass('omnivalt-floating-input__label').prependTo($wrapper);

    function updateFloatingLabel() {
      $wrapper.toggleClass('is-active', $input.is(':focus') || $input.val().length > 0);
    }

    $input.on('focus blur input change', updateFloatingLabel);
    updateFloatingLabel();
  });

  var $pickupStart = $root.find('#woocommerce_omnivalt_pick_up_start');
  var $pickupEnd = $root.find('#woocommerce_omnivalt_pick_up_end');

  if ($pickupStart.length && $pickupEnd.length) {
    function validatePickupWindow() {
      var startTime = $pickupStart.val();
      var endTime = $pickupEnd.val();
      var isInvalid = startTime && endTime && startTime >= endTime;
      var message = textSettings.pickup_window_invalid || '';

      $pickupEnd[0].setCustomValidity(isInvalid ? message : '');

      return !isInvalid;
    }

    $pickupStart.add($pickupEnd).on('input change', validatePickupWindow);
    $root.find('form').on('submit', validatePickupWindow);
    validatePickupWindow();
  }

  function refreshSettingsTab($tab) {
    var selectedTab = $tab.attr('data-settings-tab');

    $root.find('[data-settings-card]').each(function() {
      var $card = $(this);
      var cardTab = $card.attr('data-settings-tab');

      $card.toggle(!cardTab || cardTab === selectedTab);
    });
  }

  function syncSettingsTabUrl($tab) {
    var selectedTab = $tab.attr('data-settings-tab');
    var $form = $root.find('form').first();
    var pageUrl;
    var formUrl;

    if (!selectedTab || !window.URL) {
      return;
    }

    pageUrl = new window.URL(window.location.href);
    pageUrl.searchParams.set('tab', selectedTab);
    window.history.replaceState({}, '', pageUrl.toString());

    if (!$form.length) {
      return;
    }

    formUrl = new window.URL($form.attr('action'), window.location.href);
    formUrl.searchParams.set('tab', selectedTab);
    $form.attr('action', formUrl.toString());
  }

  $root.find('.omniva-tabs__tab').on('click', function() {
    var $tab = $(this);

    $tab.addClass('is-active').attr('aria-current', 'page');
    $tab.siblings('.omniva-tabs__tab').removeClass('is-active').removeAttr('aria-current');
    refreshSettingsTab($tab);
    syncSettingsTabUrl($tab);
  });

  refreshSettingsTab($root.find('.omniva-tabs__tab.is-active').first());

  if (pageSettings.phone && pageSettings.phone.countries) {
    var phoneSettings = pageSettings.phone;

    // Replace the visible phone control with a country-aware input and keep the original field for submission.
    $root.find('#woocommerce_omnivalt_shop_phone, #woocommerce_omnivalt_shop_mobile').each(function() {
      var $original = $(this);
      var isMobile = $original.attr('id') === 'woocommerce_omnivalt_shop_mobile';
      var selectedCountry = $root.find('#woocommerce_omnivalt_shop_countrycode').val() || 'LT';
      var storedValue = $original.val() || '';
      var $wrapper = $('<span class="omnivalt-phone-input"></span>');
      var $country = $('<button type="button" class="omnivalt-phone-input__country" aria-expanded="false"></button>')
        .attr('aria-label', textSettings.country_calling_code || '');
      var $number = $('<input type="tel" class="omnivalt-phone-input__number" inputmode="tel" autocomplete="tel-national">');
      var $dropdown = $('<div class="omnivalt-phone-input__dropdown" role="listbox"></div>').hide();

      $.each(phoneSettings.countries, function(countryCode, countryData) {
        var prefix = '+' + countryData.dial_code;
        if (storedValue.indexOf(prefix) === 0) {
          selectedCountry = countryCode;
          storedValue = storedValue.substring(prefix.length);
          return false;
        }
      });

      $number.val(storedValue.replace(/\D/g, '')).attr('placeholder', phoneSettings.placeholder);
      $number.prop('required', isMobile);
      $original.attr('type', 'hidden').addClass('omnivalt-phone-input__value');
      $original.after($wrapper);
      $wrapper.append($country, $number, $dropdown);

      function flag(countryCode) {
        return $('<img class="omnivalt-phone-input__flag" alt="">').attr('src', phoneSettings.flag_url + countryCode.toLowerCase() + '.svg');
      }

      function renderCountryPicker() {
        var countryData = phoneSettings.countries[selectedCountry];

        $country.empty().append(
          flag(selectedCountry),
          $('<span class="omnivalt-phone-input__dial-code"></span>').text('+' + countryData.dial_code),
          $('<span class="omnivalt-phone-input__chevron" aria-hidden="true"></span>')
        );
        $dropdown.empty();
        $.each(phoneSettings.countries, function(countryCode, optionData) {
          var $option = $('<button type="button" class="omnivalt-phone-input__option" role="option"></button>');
          $option.attr('aria-selected', countryCode === selectedCountry ? 'true' : 'false');
          $option.toggleClass('is-selected', countryCode === selectedCountry);
          $option.append(flag(countryCode), $('<span></span>').text('+' + optionData.dial_code), $('<span></span>').text(optionData.name));
          $option.on('click', function() {
            selectedCountry = countryCode;
            renderCountryPicker();
            $dropdown.hide();
            $country.attr('aria-expanded', 'false').focus();
            syncPhone();
          });
          $dropdown.append($option);
        });
      }

      function syncPhone() {
        var countryData = phoneSettings.countries[selectedCountry];
        var number = $number.val().replace(/\D/g, '').slice(0, countryData.max);
        var isValid = !number || (number.length >= countryData.min && number.length <= countryData.max && (!isMobile || new RegExp(countryData.mobile).test(number)));

        $number.val(number);
        $number.attr('maxlength', countryData.max);
        $number[0].setCustomValidity(isValid ? '' : phoneSettings.invalid);
        $original.val(number ? '+' + countryData.dial_code + number : '');
      }

      $country.on('click', function() {
        var isOpen = $dropdown.is(':visible');
        $dropdown.toggle(!isOpen);
        $country.attr('aria-expanded', isOpen ? 'false' : 'true');
      });
      $root.on('mousedown', function(event) {
        if (!$(event.target).closest($wrapper).length) {
          $dropdown.hide();
          $country.attr('aria-expanded', 'false');
        }
      });
      $country.on('keydown', function(event) {
        if (event.key === 'Escape') {
          $dropdown.hide();
          $country.attr('aria-expanded', 'false');
        }
      });
      $number.on('input change blur', syncPhone);
      renderCountryPicker();
      syncPhone();

      var initialPhoneValue = $original.val() || '';
      var initialPhoneCountry = selectedCountry;

      $original.data('omnivaltPhoneReset', function() {
        var countryData = phoneSettings.countries[initialPhoneCountry];
        var number = initialPhoneValue;
        var prefix = '+' + countryData.dial_code;

        if (number.indexOf(prefix) === 0) {
          number = number.substring(prefix.length);
        }

        selectedCountry = initialPhoneCountry;
        $number.val(number.replace(/\D/g, ''));
        renderCountryPicker();
        syncPhone();
      });
    });
  }

  $root.find('select.omnivalt-multiselect[multiple]').each(function() {
    // Keep the original select in the form and use a searchable UI for selecting multiple values.
    var $select = $(this);
    var $picker = $('<div class="omniva-picker"></div>');
    var $selectedWrap = $('<div class="omniva-picker__selected-wrap"></div>');
    var $selectedHeader = $('<div class="omniva-picker__selected-header"></div>');
    var $selectedCount = $('<span class="omniva-picker__selected-count"></span>');
    var $clear = $('<button type="button" class="omniva-picker__clear"></button>').text(textSettings.clear_all || '');
    var $selected = $('<div class="omniva-picker__selected"></div>');
    var $inputWrap = $('<div class="omniva-picker__input-wrap"></div>');
    var $input = $('<input type="search" class="omniva-input omniva-picker__search" autocomplete="off" aria-expanded="false" aria-haspopup="listbox">');
    var $dropdown = $('<div class="omniva-picker__dropdown" role="listbox" aria-multiselectable="true"></div>').hide();

    if ($select.attr('id') === 'woocommerce_omnivalt_restricted_categories') {
      $input.attr('placeholder', textSettings.search_categories || '');
    } else if ($select.attr('id') === 'woocommerce_omnivalt_restricted_shipclass') {
      $input.attr('placeholder', textSettings.search_shipping_classes || '');
    } else {
      $input.attr('placeholder', textSettings.search || '');
    }
    $selectedHeader.append($selectedCount, $clear);
    $selectedWrap.append($selectedHeader, $selected);
    $inputWrap.append($input);
    $picker.append($selectedWrap, $inputWrap, $dropdown);
    $select.addClass('omnivalt-multiselect__source');
    $picker.insertAfter($select);

    function getValues() {
      return $select.val() || [];
    }

    function setValues(values) {
      $select.val(values).trigger('change');
    }

    function renderSelected() {
      var values = getValues();

      $selected.empty();
      if (!values.length) {
        $selectedWrap.hide();
        return;
      }

      $selectedWrap.show();
      $selectedCount.text((textSettings.selected_count || '%d').replace('%d', values.length));
      $select.find('option:selected').each(function() {
        var optionValue = $(this).val();
        var $tag = $('<span class="omniva-picker__tag"></span>');
        var $remove = $('<button type="button" class="omniva-picker__remove">&times;</button>')
          .attr('aria-label', textSettings.remove_selected_item || '');

        $tag.append($('<span></span>').text($(this).text()), $remove);
        $remove.on('click', function() {
          setValues(getValues().filter(function(value) {
            return value !== optionValue;
          }));
        });
        $selected.append($tag);
      });
    }

    function renderOptions() {
      var query = $input.val().toLowerCase();
      var values = getValues();
      var $list = $('<div class="omniva-picker__list"></div>');
      var count = 0;

      $select.find('option').each(function() {
        var value = $(this).val();
        var label = $(this).text();
        var isSelected = values.indexOf(value) !== -1;

        if (label.toLowerCase().indexOf(query) === -1) {
          return;
        }

        count++;
        var $option = $('<button type="button" class="omniva-picker__option" role="option"></button>');
        $option.toggleClass('omniva-picker__option--selected', isSelected);
        $option.attr('aria-selected', isSelected ? 'true' : 'false');
        $option.append(
          $('<span class="omniva-picker__option-check"></span>').text(isSelected ? '\u2713' : ''),
          $('<span class="omniva-picker__option-label"></span>').text(label)
        );
        $option.on('mousedown', function(event) {
          event.preventDefault();
          var nextValues = getValues().slice();
          var index = nextValues.indexOf(value);

          if (index === -1) {
            nextValues.push(value);
          } else {
            nextValues.splice(index, 1);
          }

          setValues(nextValues);
        });
        $list.append($option);
      });

      if (!count) {
        $list.append($('<p class="omniva-picker__empty"></p>').text(textSettings.no_matches || ''));
      }

      $dropdown.empty().append($list);
    }

    $clear.on('click', function() {
      setValues([]);
    });
    $input.on('focus input', function() {
      renderOptions();
      $dropdown.show();
      $input.attr('aria-expanded', 'true');
    });
    $input.on('keydown', function(event) {
      if (event.key === 'Escape') {
        $dropdown.hide();
        $input.attr('aria-expanded', 'false');
      }
    });
    $root.on('mousedown', function(event) {
      if (!$(event.target).closest($picker).length) {
        $dropdown.hide();
        $input.attr('aria-expanded', 'false');
      }
    });
    $select.on('change', function() {
      renderSelected();
      if ($dropdown.is(':visible')) {
        renderOptions();
      }
    });

    renderSelected();
  });

  initializeSettingsSaveBar();

  function initializeSettingsSaveBar() {
    var $form = $root.find('form').first();
    var $status = $root.find('[data-settings-save-status]');
    var $statusText = $status.find('[data-settings-save-status-text]');
    var $discard = $root.find('[data-settings-discard]');
    var $save = $root.find('.woocommerce-save-button').first();

    if (!$form.length || !$status.length || !$discard.length || !$save.length) {
      return;
    }

    var initialControls = [];
    var savedFormState;
    var updateTimer = null;

    $form.find(':input').each(function() {
      var $control = $(this);
      var value = $control.val();

      initialControls.push({
        element: this,
        type: (this.type || '').toLowerCase(),
        value: $.isArray(value) ? value.slice() : value,
        checked: this.checked
      });
    });

    savedFormState = $form.serialize();

    function renderSaveState() {
      var hasUnsavedChanges = $form.serialize() !== savedFormState;
      var statusLabel = hasUnsavedChanges ? $status.attr('data-unsaved-label') : $status.attr('data-saved-label');

      $status
        .toggleClass('is-unsaved', hasUnsavedChanges)
        .toggleClass('is-saved', !hasUnsavedChanges);
      $statusText.text(statusLabel || '');
      $discard.prop('hidden', !hasUnsavedChanges);
      $save.prop('disabled', !hasUnsavedChanges);
    }

    function scheduleSaveStateUpdate() {
      if (updateTimer) {
        window.clearTimeout(updateTimer);
      }

      updateTimer = window.setTimeout(function() {
        updateTimer = null;
        renderSaveState();
      }, 0);
    }

    $form.on('input.omnivaltSettingsSave change.omnivaltSettingsSave', ':input', scheduleSaveStateUpdate);

    $discard.on('click', function() {
      $.each(initialControls, function(index, controlState) {
        var $control = $(controlState.element);

        if (!$control.length) {
          return;
        }

        $control.val(controlState.value);
        if (controlState.type === 'checkbox' || controlState.type === 'radio') {
          $control.prop('checked', controlState.checked);
        }
      });

      $form.find('.prices-table > table').each(function() {
        var resetPrices = $(this).data('omnivaltPricesReset');

        if ($.isFunction(resetPrices)) {
          resetPrices();
        }
      });
      $form.find('.omnivalt-phone-input__value').each(function() {
        var resetPhone = $(this).data('omnivaltPhoneReset');

        if ($.isFunction(resetPhone)) {
          resetPhone();
        }
      });
      $form.find('[data-settings-position-list]').each(function() {
        var resetPositionList = $(this).data('omnivaltPositionReset');

        if ($.isFunction(resetPositionList)) {
          resetPositionList();
        }
      });

      $form.find(':input').trigger('change');
      renderSaveState();
    });

    renderSaveState();
  }
});
