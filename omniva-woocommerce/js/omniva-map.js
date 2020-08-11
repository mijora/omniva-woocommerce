(function ($, window) {
  window.omniva_version = (function () { return '1.1.1'; }()); // global accesible Omniva version number
  $.fn.omniva = function (options) {
    var settings = $.extend({
      autoHide: false,
      maxShow: 8,
      showMap: true,
      country_code: 'LT',
      terminals: [],
      path_to_img: 'image/omniva/',
      selector_container: false, // false or HTMLElement
      callback: false,
      translate: null
    }, options);

    var defaultTranslate = {
      modal_header: 'Omniva terminals',
      search_bar_title: 'Omniva addresses',
      search_bar_placeholder: 'Enter postcode/address',
      search_back_to_list: 'Back to list',
      select_terminal: 'Choose terminal',
      show_on_map: 'Show on map',
      show_more: 'Show more',
      place_not_found: 'Place not found'
    }

    if (typeof options.translate !== 'undefined') {
      settings.translate = $.extend(defaultTranslate, settings.translate);
    } else {
      settings.translate = defaultTranslate;
    }

    //console.log('Omniva Initiated');

    var UI = {
      hook: $(this), // element thats been used to initialize omniva (normally radio button)
      // overlay used to show loading
      loader: $('<div class="omniva-loading-overlay" style="display: none;"></div>'),
      terminal_container: $('<div class="omniva-terminal-container" ' +
          (settings.autoHide ? 'style = "display: none;"' : '') + '></div>'),
      container: $('<div class="omniva-terminals-list"></div>'),
      show_on_map_btn: $(
          '<button type="button" class="omniva-btn">' + settings.translate.show_on_map +
          '  <img src="' + settings.path_to_img + 'sasi.png" title="' + settings.translate.show_on_map + '">' +
          '</button>'),
      dropdown: $('<div class="omniva-dropdown">' + settings.translate.select_terminal + '</div>'),
      search: $('<input type="text" placeholder="' + settings.translate.search_bar_placeholder + '" class="omniva-search-input"/>'),
      list: $('<ul></ul>'),
      showMapBtn: $('<li><a href="#" class="omniva-show-on-map">' + settings.translate.show_on_map + '</a></li>'),
      showMore: $('<div class="omniva-show-more"><a href="#">' + settings.translate.show_more + '</a></div>').hide(),
      innerContainer: $('<div class="omniva-inner-container"></div>').hide(),
      // map modal
      modal: $( // id="omnivaLtModal"
          '<div class="omniva-modal">' +
          '  <div class="omniva-modal-content">' +
          '    <div class="omniva-modal-header">' +
          '      <span class="omniva-modal-close">&times;</span>' +
          '      <h5>' + settings.translate.modal_header + '</h5>' +
          '    </div>' +
          '    <div class="omniva-modal-body">' +
          '      <div class="omniva-map-container"></div>' +
          '      <div class="omniva-search-bar">' +
          '        <h4>' + settings.translate.search_bar_title + '</h4>' +
          '        <div class="omniva-search">' +
          '          <form>' +
          '            <input type="text" placeholder="' + settings.translate.search_bar_placeholder + '" />' +
          '            <button type="submit" class="omniva-modal-search-btn"></button>' +
          '          </form>' +
          '          <div class="omniva-autocomplete omniva-scrollbar" style="display:none;">' +
          '            <ul></ul>' +
          '          </div>' +
          '        </div>' +
          '        <div class="omniva-back-to-list" style="display:none;">' + settings.translate.search_back_to_list + '</div>' +
          '        <div class="omniva-found-terminals omniva-scrollbar omniva-scrollbar-style-8">' +
          '          <ul></ul>' +
          '        </div>' +
          '      </div>' +
          '    </div>' +
          '  </div>' +
          '</div>')
    };

    var timeoutID = null;
    var currentLocationIcon = false;
    var searchTimeout = null;
    var terminalIcon = null;
    var homeIcon = null;
    var map = null;
    //var terminals = settings.terminals;
    var selected = false;
    var previous_list = false;
    var show_auto_complete = false;
    var uid = Math.random().toString(36).substr(2, 6);
    var clicked = false;

    updateSelection();

    UI.modal.appendTo(UI.terminal_container);
    if (settings.selector_container) {
      $(settings.selector_container).append(UI.terminal_container);
    } else {
      UI.terminal_container.insertAfter(UI.hook.parent());
    }
    UI.terminal_container.append(UI.loader, UI.container, UI.show_on_map_btn);
    UI.innerContainer.append(UI.search, UI.list, UI.showMore);
    UI.container.append(UI.dropdown, UI.innerContainer);

    // add images for css
    UI.modal.find('.omniva-back-to-list').css('background-image', 'url("' + settings.path_to_img + 'back.png")');
    UI.modal.find('.omniva-modal-search-btn').css('background-image', 'url("' + settings.path_to_img + 'search-w.png")');

    // Custom Events to update settings
    $(this).on('omniva.update.settings', function (e, new_settings) {
      if (typeof new_settings.translate !== 'undefined') { // there is changes to translate object
        // we are dealing with shallow copy
        var temp = $.extend({}, settings.translate);
        settings = $.extend(settings, new_settings);
        // merge old translation with new
        settings.translate = $.extend(temp, new_settings.translate);
      } else {
        settings = $.extend(settings, new_settings);
      }
    });

    // Custom Events to hide/show terminal selector
    $(this).on('omniva.show', function (e) {
      UI.terminal_container.show();
    });

    $(this).on('omniva.hide', function (e) {
      UI.terminal_container.hide();
    });

    // Custom Events to search by
    $(this).on('omniva.postcode', function (e, postcode) {
      if (!postcode) {
        return;
      }

      UI.search.val(postcode);
      findPosition(postcode, true);
    });

    $(this).on('omniva.select_terminal', function (e, id) {
      var selection = UI.list.find('li[data-id="' + id + '"]');
      if (selection.length > 0) {
        UI.list.find('li').removeClass('selected');
        selection.addClass('selected');
        selectOption(selection);
      }
    });

    // Initialize leaflet map
    if (settings.showMap == true) {
      initMap();
    }

    // Generate terminal selector
    refreshList(false);

    // Show on map button to open modal
    UI.show_on_map_btn.on('click', function (e) {
      e.preventDefault();
      showModal();
    });

    // Show on map link inside dropdown
    UI.list.on('click', 'a.omniva-show-on-map', function (e) {
      e.preventDefault();
      showModal();
    });

    // Show more link inside dropdown
    UI.showMore.on('click', function (e) {
      e.preventDefault();
      showAll();
    });

    // Dropdown toggle
    UI.dropdown.on('click', function () {
      toggleDropdown();
    });

    // Debounce search input
    UI.search.on('keyup', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function () { suggest(UI.search.val()) }, 400);
    });

    // Prevent Enter button inside dropdown
    UI.search.on('keypress', function (event) {
      if (event.which == '13') {
        event.preventDefault();
      }
    });

    // clicking outside dropdown will close it
    $(document).on('mousedown', function (e) {
      if (!UI.container.is(e.target) && UI.container.has(e.target).length === 0 && UI.container.hasClass('open'))
        toggleDropdown();
    });

    // back to list button
    UI.modal.find('.omniva-back-to-list').off('click').on('click', function () {
      listTerminals(settings.terminals, null);
      $(this).hide();
    });


    // initial search by something???
    //searchByAddress();


    function showModal() {
      settings.showMap = true;
      var searchInputEl = UI.modal.find('.omniva-search input');
      if (searchInputEl.val() !== UI.search.val()) {
        searchInputEl.val(UI.search.val());
        UI.modal.find('.omniva-search button').trigger('click');
      }
      if (selected != false) {
        zoomTo(selected.pos, selected.id);
      }
      UI.modal.show();

      var event;
      if (typeof (Event) === 'function') {
        event = new Event('resize');
      } else {
        event = document.createEvent('Event');
        event.initEvent('resize', true, true);
      }
      window.dispatchEvent(event);
    }

    // for dropdown functionality to show all the terminals
    function showAll() {
      UI.list.find('li').show();
      UI.showMore.hide();
    }

    // rebuilds terminal list inside map modal
    function refreshList(autoselect) {
      UI.modal.find('.omniva-back-to-list').hide();
      var city = false;
      var hide = false;
      var html = '';
      var foundTerminalsEl = UI.modal.find('.omniva-found-terminals');
      UI.list.html('');
      foundTerminalsEl.html('');
      $(settings.terminals).each(function (i, val) {
        var li = $('<li></li>').attr({ 'data-id': val[3], 'data-pos': '[' + [val[1], val[2]] + ']' }).text(val[0]);
        if (val['distance']) { // means we are searching
          li.append(' <strong>' + val['distance'] + 'km</strong>');
          hide = true;
        }

        html += '<li data-pos="[' + [val[1], val[2]] + ']" data-id="' + val[3] + '">' +
            '  <div>' +
            '    <a class="omniva-li">' + (i + 1) + '. <b>' + val[0] + ' ' + (val['distance'] ? val['distance'] + ' km.' : '') + '</b></a>' +
            '    <div id="' + makeUID(val[3]) + '" class="omniva-details" style="display:none;">' +
            '      <small>' + val[5] + '<br/>' + val[6] + '</small><br/>' +
            '      <button type="button" class="omniva-select-terminal-btn" data-id="' + val[3] + '">' + settings.translate.select_terminal + '</button>' +
            '    </div>' +
            '  </div></li>';

        if (selected != false && selected.id == val[3]) {
          li.addClass('selected');
        }
        if (hide &&/* counter */ (i + 1) > settings.maxShow) {
          li.hide();
        }
        if (val[4] != city) {
          var li_city = $('<li class = "omniva-city">' + val[4] + '</li>');
          if (hide &&/* counter */ (i + 1) > settings.maxShow) {
            li_city.hide();
          }
          UI.list.append(li_city);
          city = val[4];
        }
        UI.list.append(li);
      });
      UI.list.find('li').on('click', function () {
        if (!$(this).hasClass('omniva-city')) {
          UI.list.find('li').removeClass('selected');
          $(this).addClass('selected');
          clicked = true;
          selectOption($(this));
        }
      });
      if (autoselect == true) {
        var first = UI.list.find('li:not(.omniva-city):first');
        UI.list.find('li').removeClass('selected');
        first.addClass('selected');
        selectOption(first);
      }

      UI.list.scrollTop(0);
      if (settings.showMap == true) {
        foundTerminalsEl.html('<ul class="omniva-terminals-listing" start="1">' + html + '</ul>');
      }
    }

    function selectOption(option) {
      selected = { 'id': option.attr('data-id'), 'text': option.text(), 'pos': JSON.parse(option.attr('data-pos')), 'distance': false };
      updateSelection();
      closeDropdown();
    }

    function updateSelection() {
      if (!selected) {
        return;
      }

      UI.dropdown.html(selected.text);

      UI.hook.val(selected.id);
      if (settings.callback) {
        settings.callback(selected.id, clicked);
        clicked = false; // reset to default
      }
    }

    function toggleDropdown() {
      if (UI.container.hasClass('open')) {
        UI.innerContainer.hide();
        UI.container.removeClass('open')
      } else {
        UI.innerContainer.show();
        UI.innerContainer.find('.omniva-search-input').focus();
        UI.container.addClass('open');
      }
    }

    function closeDropdown() {
      if (UI.container.hasClass('open')) {
        UI.innerContainer.hide();
        UI.container.removeClass('open')
      }
    }

    // sorts terminal list by title and resets distance
    function resetList() {
      settings.terminals.sort(function (a, b) {
        a.distance = false;
        b.distance = false;
        return a[0].localeCompare(b[0]);
      });
    }

    function calculateDistance(y, x) {
      $.each(settings.terminals, function (key, location) {
        distance = calcCrow(y, x, location[1], location[2]);
        location['distance'] = distance.toFixed(2);
      });

      settings.terminals.sort(function (a, b) {
        var distOne = a['distance'];
        var distTwo = b['distance'];
        return (parseFloat(distOne) - parseFloat(distTwo));
      });
    }

    function toRad(Value) {
      return Value * Math.PI / 180;
    }

    function calcCrow(lat1, lon1, lat2, lon2) {
      var R = 6371;
      var dLat = toRad(lat2 - lat1);
      var dLon = toRad(lon2 - lon1);
      var lat1 = toRad(lat1);
      var lat2 = toRad(lat2);

      var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
          Math.sin(dLon / 2) * Math.sin(dLon / 2) * Math.cos(lat1) * Math.cos(lat2);
      var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      var d = R * c;
      return d;
    }

    function resetSelector() {
      resetList();
      UI.showMore.hide();
      refreshList(false);
    }

    function findPosition(address, autoselect) {
      // reset list
      if (address == "") {
        resetSelector();
        return false;
      }

      if (address.length < 3) {
        return false;
      }

      UI.loader.show();
      $.getJSON("https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?" + prepAddress({ singleLine: address }) + "&sourceCountry=" + settings.country_code + "&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson", function (data) {
        if (data.candidates != undefined && data.candidates.length > 0) {
          calculateDistance(data.candidates[0].location.y, data.candidates[0].location.x);
          refreshList(autoselect);
          UI.list.prepend(UI.showMapBtn);
          UI.showMore.show();
          if (settings.showMap == true) {
            setCurrentLocation([data.candidates[0].location.y, data.candidates[0].location.x]);
          }
        }
        UI.loader.hide();
      });
    }

    function suggest(address) {
      if (!address) {
        resetSelector();
        return;
      }
      if (address.length < 3) {
        return;
      }
      $.getJSON("https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/suggest?" + prepAddress({ text: address }) + "&f=pjson&sourceCountry=" + settings.country_code + "&maxSuggestions=1", function (data) {
        if (data.suggestions != undefined && data.suggestions.length > 0) {
          findPosition(data.suggestions[0].text, false);
        }
      });
    }

    // Prepares address for url (arcgis uses + instead of %20)
    function prepAddress(param) {
      return $.param(param).replace("%20", "+");
    }

    function initMap() {
      var mapEl = $('<div class="omniva-map"></div>')[0];
      UI.modal.find('.omniva-map-container').append(mapEl);
      if (settings.country_code == "LT") {
        map = L.map(mapEl).setView([54.999921, 23.96472], 8);
      }
      if (settings.country_code == "LV") {
        map = L.map(mapEl).setView([56.8796, 24.6032], 8);
      }
      if (settings.country_code == "EE") {
        map = L.map(mapEl).setView([58.7952, 25.5923], 7);
      }
      L.tileLayer('https://maps.omnivasiunta.lt/tile/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.omniva.lt">Omniva</a>' +
            ' | Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
      }).addTo(map);

      var Icon = L.Icon.extend({
        options: {
          iconSize: [29, 34],
          iconAnchor: [15, 34],
          popupAnchor: [-3, -76]
        }
      });

      var Icon2 = L.Icon.extend({
        options: {
          iconSize: [32, 32],
          iconAnchor: [16, 32]
        }
      });


      terminalIcon = new Icon({ iconUrl: settings.path_to_img + 'sasi.png' });
      homeIcon = new Icon2({ iconUrl: settings.path_to_img + 'locator_img.png' });

      jQuery.each(settings.terminals, function (key, location) {
        L.marker([location[1], location[2]], { icon: terminalIcon, terminalId: location[3] })
            .on('click', function (e) {
              terminalDetails(this.options.terminalId);
              listTerminals(settings.terminals, this.options.terminalId);
            })
            .addTo(map);
      });

      var omnivaSearchFormEl = UI.modal.find('.omniva-search form');
      var omnivaSearchInputEl = omnivaSearchFormEl.find('input');

      omnivaSearchInputEl.off('keyup focus').on('keyup focus', function () {
        clearTimeout(timeoutID);
        show_auto_complete = true;
        timeoutID = setTimeout(function () { autoComplete(omnivaSearchInputEl.val()) }, 500);
      });

      var autocompleteEl = UI.modal.find(".omniva-autocomplete");

      autocompleteEl.find('ul').off('click').on('click', 'li', function () {
        omnivaSearchInputEl.val($(this).text());
        omnivaSearchFormEl.trigger('submit');
        autocompleteEl.hide();
      });

      // closes autocomplete inside modal
      UI.modal.click(function (e) {
        if (!autocompleteEl.is(e.target) && autocompleteEl.has(e.target).length === 0) {
          autocompleteEl.hide();
        }
      });

      UI.modal.find('.omniva-modal-close').on('click', function () {
        UI.modal.hide();
      });

      omnivaSearchFormEl.off('submit').on('submit', function (e) {
        e.preventDefault();
        var postcode = omnivaSearchInputEl.val();
        UI.search.val(postcode); // send to search input outside modal
        findPosition(postcode, false);
        omnivaSearchInputEl.blur();
        show_auto_complete = false;
      });

      var foundTerminalsEl = UI.modal.find('.omniva-found-terminals');

      foundTerminalsEl.on('click', 'li', function () {
        zoomTo(JSON.parse($(this).attr('data-pos')), $(this).attr('data-id'));
      });

      foundTerminalsEl.on('click', 'li button', function () {
        clicked = true;
        terminalSelected($(this).attr('data-id'));
      });

      // populate current position
      //getLocation();
    }

    function autoComplete(address) {
      if (!show_auto_complete) {
        return;
      }
      var autocompleteEl = UI.modal.find('.omniva-autocomplete');
      var autocompleteUlEl = autocompleteEl.find('ul');
      autocompleteUlEl.html('');
      autocompleteEl.hide();
      if (address == "" || address.length < 3) return false;

      $.getJSON("https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/suggest?" + prepAddress({ text: address }) + "&sourceCountry=" + settings.country_code + "&f=pjson&maxSuggestions=4", function (data) {
        if (data.suggestions != undefined && data.suggestions.length > 0) {
          $.each(data.suggestions, function (i, item) {
            var li = $("<li data-magickey = '" + item.magicKey + "' data-text = '" + item.text + "'>" + item.text + "</li>");
            autocompleteUlEl.append(li);
          });
        } else {
          autocompleteUlEl.append('<li>' + settings.translate.place_not_found + '</li>');
        }
        autocompleteEl.show();
      });
    }

    function terminalDetails(id) {
      UI.modal.find('.omniva-details').hide();
      id = makeUID(id);
      dispOmniva = document.getElementById(id)
      if (dispOmniva) {
        dispOmniva.style.display = 'block';
      }
    }

    function getLocation() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (loc) {
          if (selected == false) {
            setCurrentLocation([loc.coords.latitude, loc.coords.longitude]);
          }
        });
      }
    }

    function setCurrentLocation(pos) {
      if (currentLocationIcon) {
        map.removeLayer(currentLocationIcon);
      }
      currentLocationIcon = L.marker(pos, { icon: homeIcon }).addTo(map);
      map.setView(pos, 16);
    }

    function listTerminals(locations, id) {
      // in case both are falsey ignore call
      if (id === null && !previous_list) {
        return;
      }

      var foundTerminalsEl = UI.modal.find('.omniva-found-terminals');

      // return to previous list
      if (id === null && previous_list) {
        foundTerminalsEl.empty().append(previous_list);
        previous_list = false;
        return;
      }

      if (id) {
        //foundTerminalsEl.find('li').hide();
        var terminal = foundTerminalsEl.find('li[data-id="' + id + '"]');
        //terminal.show();
        // update active marker if this is called from map
        updateActiveMarker(id);
        // check if activated terminal is in shown list
        if (terminal.length > 0) {
          terminal[0].scrollIntoView({ behavior: "smooth" });
          return;
        } else {
          // marker not on list, generate terminal info and enable back to list button
          var html = '';
          if (!previous_list) {
            previous_list = foundTerminalsEl.find('.omniva-terminals-listing').detach();
          }
          UI.modal.find('.omniva-back-to-list').show();

          for (var i = 0; i < locations.length; i++) {
            if (locations[i][3] == id) {
              html += '<li data-pos="[' + [locations[i][1], locations[i][2]] + ']" data-id="' + locations[i][3] + '" >' +
                  '<div>' +
                  '  <a class="omniva-li"><b>' + locations[i][0] + '</b></a>' +
                  '  <div id="' + makeUID(locations[i][3]) + '" class="omniva-details">' +
                  '  <small>' + locations[i][5] + ' <br/>' + locations[i][6] + '</small><br/>' +
                  '  <button type="button" class="omniva-select-terminal-btn" data-id="' + locations[i][3] + '">' + settings.translate.select_terminal + '</button>' +
                  '  </div>' +
                  '</div></li>';
              break;
            }
          }
          foundTerminalsEl.empty().append($('<ul class="omniva-terminals-listing" start="1">' + html + '</ul>'));
        }
      }
    }

    function makeUID(part) {
      return ['omniva', uid, part].join('-');
    }

    function zoomTo(pos, id) {
      terminalDetails(id);
      map.setView(pos, 14);
      updateActiveMarker(id);
    }

    function updateActiveMarker(id) {
      map.eachLayer(function (layer) {
        if (layer.options.terminalId !== undefined && L.DomUtil.hasClass(layer._icon, "active")) {
          L.DomUtil.removeClass(layer._icon, "active");
        }
        if (layer.options.terminalId == id) {
          L.DomUtil.addClass(layer._icon, "active");
        }
      });
    }

    function terminalSelected(terminal, close) {
      if (close === undefined) {
        close = true;
      }

      for (var i = 0; i < settings.terminals.length; i++) {
        if (settings.terminals[i][3] == terminal) {
          selected = { 'id': terminal, 'text': settings.terminals[i][0], 'pos': [settings.terminals[i][1], settings.terminals[i][2]], 'distance': false };
          updateSelection();
          break;
        }
      }

      if (close) {
        UI.modal.hide();
      }
    }

    return this;
  };

}(jQuery, window));