/*** New method (use terminal-mapping library) ***/
function omnivalt_init_map() {
    var container_parcel_terminal = document.getElementById("omnivalt-terminal-container-map");
    if ( typeof(container_parcel_terminal) != "undefined" && container_parcel_terminal != null ) {
        if ( container_parcel_terminal.innerHTML === "" ) {
            omnivaltMap.init(container_parcel_terminal, omnivalt_terminals);
        } else {
            omnivaltMap.update_list();
        }
    }
}

(function($) {
    
    window.omnivaltMap = {
        lib: null,
        field: null,
        icons_URL: '',
        translations: {},
        params: {},

        load_data: function () {
            this.field = document.getElementById("omnivalt-terminal-selected");
            this.icons_URL = omnivalt_data.omniva_plugin_url + 'assets/img/terminal-mapping/';
            let modal_header = (omnivalt_type == 'post') ? omnivalt_data.text.modal_title_post : omnivalt_data.text.modal_title_terminal;
            this.translations = {
                modal_header: omnivalt_data.text.providers[omnivalt_provider] + " " + modal_header,
                terminal_list_header: (omnivalt_type == 'post') ? omnivalt_data.text.modal_search_title_post : omnivalt_data.text.modal_search_title_terminal,
                select_pickup_point: (omnivalt_type == 'post') ? omnivalt_data.text.select_post : omnivalt_data.text.select_terminal,
                seach_header: omnivalt_data.text.search_placeholder,
                search_btn: omnivalt_data.text.search_button,
                modal_open_btn: omnivalt_data.text.modal_open_button,
                geolocation_btn: omnivalt_data.text.use_my_location,
                your_position: omnivalt_data.text.my_position,
                nothing_found: omnivalt_data.text.not_found,
                no_cities_found: omnivalt_data.text.no_cities_found,
                geolocation_not_supported: omnivalt_data.text.geo_not_supported
            };
            this.params = {
                country: omnivalt_current_country,
                show_map: omnivalt_settings.show_map
            };
        },
        
        init: function ( container, terminals ) {
            this.load_data();
            this.lib = new TerminalMappingOmnivalt();

            this.lib.setImagesPath(this.icons_URL);
            this.lib.setTranslation(this.translations);
            this.lib.dom.setContainerParent(container);

            this.lib.setParseMapTooltip((location, leafletCoords) => {
                let tip = location.address + " [" + location.id + "]";
                if ( location.comment ) {
                    tip += "<br/><i>" + location.comment + "</i>";
                }
                return tip;
            });

            this.lib.sub('tmjs-ready', function(data) {
                omnivaltMap.load_data();
                omnivaltMap.lib.map.createIcon('omnivalt_icon', omnivaltMap.icons_URL + omnivalt_map_icon);
                omnivaltMap.lib.map.refreshMarkerIcons();

                let selected_location = data.map.getLocationById(omniva_getCookie('omniva_terminal'));
                if ( typeof(selected_location) != 'undefined' && selected_location != null ) {
                    omnivaltMap.lib.dom.setActiveTerminal(selected_location);
                    omnivaltMap.lib.publish('terminal-selected', selected_location);
                }
            });

            this.lib.sub("terminal-selected", function(data) {
                omnivaltMap.load_data();
                omnivaltMap.field.value = data.id;
                omnivaltMap.lib.dom.setActiveTerminal(data.id);
                omnivaltMap.lib.publish("close-map-modal");
                console.log("OMNIVA: Saving selected terminal...");
                omniva_setCookie('omniva_terminal', data.id, 30);
                console.log("OMNIVA: Terminal changed to " + data.id);
            });

            this.lib.init({
                country_code: this.params.country,
                identifier: 'omnivalt',
                isModal: true,
                modalParent: container,
                hideContainer: true,
                hideSelectBtn: true,
                cssThemeRule: 'tmjs-default-theme',
                customTileServerUrl: 'https://maps.omnivasiunta.lt/tile/{z}/{x}/{y}.png',
                customTileAttribution: '&copy; <a href="https://www.omniva.lt">Omniva</a>' + ' | Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
                terminalList: terminals,
            });

            this.update_list();
        },

        update_list: function() {
            var selected_postcode = this.get_postcode();

            this.lib.dom.searchNearest(selected_postcode);
            this.lib.dom.UI.modal.querySelector('.tmjs-search-input').value = selected_postcode;
        },

        get_postcode: function() {
            var postcode = "";
            var ship_to_dif_checkbox = document.getElementById("ship-to-different-address-checkbox");
            
            if ( typeof(ship_to_dif_checkbox) != "undefined" && ship_to_dif_checkbox != null && ship_to_dif_checkbox.checked ) {
                postcode = document.getElementById("shipping_postcode").value;
            } else {
                postcode = document.getElementById("billing_postcode").value;
            }

            return postcode;
        }
    };

})(jQuery);

/*** Old method (for dropdown) ***/

jQuery('document').ready(function($){
    $('input.shipping_method').on('click',function(){
        var current_method = $(this);
        if (current_method.val() == "omnivalt_pt"){
            $('.terminal-container').show();
        } else {
            $('.terminal-container').hide();
        }
    });
    $('input.shipping_method:checked').trigger('click');
 
    $(document.body).on( 'updated_wc_div', function(){
        if ($(".woocommerce-shipping-calculator").length) {
            $("select.shipping_method, :input[name^=shipping_method]:checked").trigger('change'); //TODO: Need better solution for dropdown update when in cart change country
        } else {
            $('.omnivalt_terminal').omniva(); //TODO: Not working when country select is enabled in cart
            $("select.shipping_method").trigger('click');
        }
    });

    $(document.body).on('updated_checkout', function() {
        var omniva_descriptions = $(".omnivalt-shipping-description");
        for (var i=0;i<omniva_descriptions.length;i++) {
            if ($(omniva_descriptions[i]).closest("li").find("input.shipping_method").is(':checked')) {
                $(omniva_descriptions[i]).show();
            } else {
                $(omniva_descriptions[i]).hide();
            }
        }
    });

    function omniva_getPostcode() {
        var postcode;
        if ($('#ship-to-different-address-checkbox').length && $('#ship-to-different-address-checkbox').is(':checked')) {
            if ($("#shipping_postcode").length && $("#shipping_postcode").val()) {
                postcode = $("#shipping_postcode").val();
            }
        } else {
            if ($("#billing_postcode").length && $("#billing_postcode").val()) {
                postcode = $("#billing_postcode").val();
            } else if ($("#calc_shipping_postcode").length && $("#calc_shipping_postcode").val()) {
                postcode = $("#calc_shipping_postcode").val();
            }
        }
        return postcode;
    }
});

var omniva_addrese_change = false;
(function ( $ ) {
    $.fn.omniva = function(options) {
        var settings = $.extend({
            maxShow: 8,
            showMap: true,
        }, options );
        var timeoutID = null;
        var currentLocationIcon = false;
        var autoSelectTerminal = false;
        var searchTimeout = null;
        var select = $(this);
        var not_found = omnivadata.not_found;
        var terminalIcon = null;
        var homeIcon = null;
        var map = null;
        var terminals = JSON.parse(omnivaTerminals);
        var text = {
            'select_terminal' : omnivadata.text_select_terminal,
            'map_title' : omnivadata.text_modal_title_terminal,
            'map_search_title' : omnivadata.text_modal_search_title_terminal
        };
        if (omniva_type === 'post') {
            text.select_terminal = omnivadata.text_select_post;
            text.map_title = omnivadata.text_modal_title_post;
            text.map_search_title = omnivadata.text_modal_search_title_post;
        }
        var selected = false;
        var previous_list = [];
        select.hide();
        if (select.val()){
            selected = {'id':select.val(),'text':select.find('option:selected').text(),'distance':false};
        }
        var cookie_terminal = omniva_getCookie('omniva_terminal');
        /*
        select.find('option').each(function(i,val){
           if (val.value != "")
            terminals.push({'id':val.value,'text':val.text,'distance':false}); 
           if (val.selected == true){
               selected = {'id':val.value,'text':val.text,'distance':false};
           }
               
        });
        */
        var container = $(document.createElement('div'));
        container.addClass("omniva-terminals-list");
        var dropdown = $('<div class = "dropdown">'+text.select_terminal+'</div>');
        updateSelection();
        
        var search = $('<input type = "text" placeholder = "'+omnivadata.text_enter_address+'" class = "search-input"/>');
        var loader = $('<div class = "loader"></div>').hide();
        var list = $(document.createElement('ul'));
        var showMapBtn = $('<li><a href = "#" class = "show-in-map">'+omnivadata.text_show_in_map+'</a></li>');
        var showMore = $('<div class = "show-more"><a href = "#">'+omnivadata.text_show_more+'</a></div>').hide();
        var innerContainer = $('<div class = "inner-container"></div>').hide();
        
        $(container).insertAfter(select);
        $(innerContainer).append(search,loader,list,showMore);
        $(container).append(dropdown,innerContainer);
        
        if (settings.showMap == true){
            initMap();
        }
        
        resetList();
        refreshList(false);

        $("#omnivaLt_modal_title").html(text.map_title);
        $("#omnivaLt_modal_search").html(text.map_search_title);
        
        list.on('click','a.show-in-map',function(e){
            e.preventDefault();            
            showModal();
        });
        $('.terminal-container').on('click','#show-omniva-map',function(e){
            e.preventDefault();            
            showModal();
        });
        
        showMore.on('click',function(e){
            e.preventDefault();
            showAll();
        });
        
        dropdown.on('click',function(){
            toggleDropdown();
        });
        
        select.on('change',function(){
            selected = {'id':$(this).val(),'text':$(this).find('option:selected').text(),'distance':false};
            updateSelection();
        });
        
    
        search.on('keyup',function(){
            clearTimeout(searchTimeout);      
            searchTimeout = setTimeout(function() { suggest(search.val()); }, 400);    
                  
        });
        search.on('selectpostcode',function(){
            if (omnivaSettings.auto_select != "yes") {
                var autoselect = false;
            } else {
                var autoselect = true;
            }
            findPosition(search.val(),autoselect);    
                  
        });
        
        search.on('keypress',function(event){
            if (event.which == '13') {
              event.preventDefault();
            }
        });
        
        $(document).on('mousedown',function(e){
            var container = $(".omniva-terminals-list");
            if (!container.is(e.target) && container.has(e.target).length === 0 && container.hasClass('open')) 
                toggleDropdown();
        });   
        
        $('.omniva-back-to-list').off('click').on('click',function(){
            listTerminals(terminals,0,previous_list);
            $(this).hide();
        });
       
        searchByAddress();
        if (cookie_terminal !== null) {
            var list_item = list.find('[data-id="' + cookie_terminal + '"]');
            if (list_item.length > 0) {
                list.find('li').removeClass('selected');
                list_item.addClass('selected');
                selectOption(list_item);
            }
        }
        
        
        function showModal(){
            getLocation();
            $('#omniva-search input').val(search.val());
            //$('#omniva-search button').trigger('click');
              if ($('.omniva-terminals-list input.search-input').val() != ''){
                  $('#omniva-search input').val($('.omniva-terminals-list input.search-input').val());
                 // $('#omniva-search button').trigger('click')
              }
            if (selected != false){
                $(terminals).each(function(i,val){
                    if (selected.id == val[3]){
                        zoomTo([val[1], val[2]], selected.id);
                        return false;
                    }
                });
            }
            $('#omnivaLtModal').show();
            //getLocation();
            var event;
            if(typeof(Event) === 'function') {
                event = new Event('resize');
            }else{
                event = document.createEvent('Event');
                event.initEvent('resize', true, true);
            }
            window.dispatchEvent(event);
          }

        function searchByAddress() {
            if (selected == false) {
            var postcode = '';
            if (omniva_addrese_change == true) {
                postcode = getPostcode();
                if (postcode != '') {
                    search.val(postcode).trigger('selectpostcode');
                }
            } else {
                omniva_addrese_change = true;
            }
            $('#shipping_postcode, #billing_postcode').on('change', function() {
                var cookie_terminal = omniva_getCookie('omniva_terminal');
                postcode = getPostcode();
                if (omnivaSettings.auto_select == "yes" && !cookie_terminal) {
                    search.val(postcode).trigger('selectpostcode');
                } else {
                    search.val(postcode).trigger('keyup');
                }
            });
            $(document).on('updated_checkout', function() {
                var cookie_terminal = omniva_getCookie('omniva_terminal');
                postcode = getPostcode();
                search.val(postcode).trigger('keyup');
                if (omnivaSettings.auto_select == "yes" && !cookie_terminal) {
                    search.val(postcode).trigger('selectpostcode');
                }
            });

            }
        }

        function getPostcode() {
            var postcode;
            if ($('#ship-to-different-address-checkbox').length && $('#ship-to-different-address-checkbox').is(':checked')) {
                if ($("#shipping_postcode").length && $("#shipping_postcode").val()) {
                    postcode = $("#shipping_postcode").val();
                }
            } else {
                if ($("#billing_postcode").length && $("#billing_postcode").val()) {
                    postcode = $("#billing_postcode").val();
                } else if ($("#calc_shipping_postcode").length && $("#calc_shipping_postcode").val()) {
                    postcode = $("#calc_shipping_postcode").val();
                }
            }
            return postcode;
        }

        function showAll(){
            list.find('li').show();
            showMore.hide();
        }
        
        function refreshList(autoselect){        
            $('.omniva-back-to-list').hide();
            var counter = 0;
            var city = false;
            var html = '';
            list.html('');
            $('.found_terminals').html('');
            $(terminals).each(function(i,val){
                var li = $(document.createElement("li"));
                li.attr('data-id',val[3]);
                li.html(val[0]);
                if (val['distance'] !== undefined && val['distance'] != false){
                    li.append(' <strong>' + val['distance'] + 'km</strong>');  
                    counter++;
                    if (settings.showMap == true && counter <= settings.maxShow){
                        html += '<li data-pos="['+[val[1], val[2]]+']" data-id="'+val[3]+'" ><div><a class="omniva-li">'+counter+'. <b>'+val[0]+'</b></a> <b>'+val['distance']+' km.</b>\
                                  <div align="left" id="omn-'+val[3]+'" class="omniva-details" style="display:none;"><small>\
                                  '+val[5]+' <br/>'+val[6]+'</small><br/>\
                                  <button type="button" class="btn-marker" style="font-size:14px; padding:0px 5px;margin-bottom:10px; margin-top:5px;height:25px;" data-id="'+val[3]+'">'+text.select_terminal+'</button>\
                                  </div>\
                                  </div></li>';
                    }
                } else {
                    if (settings.showMap == true ){
                        html += '<li data-pos="['+[val[1], val[2]]+']" data-id="'+val[3]+'" ><div><a class="omniva-li">'+(i+1)+'. <b>'+val[0]+'</b></a>\
                                  <div align="left" id="omn-'+val[3]+'" class="omniva-details" style="display:none;"><small>\
                                  '+val[5]+' <br/>'+val[6]+'</small><br/>\
                                  <button type="button" class="btn-marker" style="font-size:14px; padding:0px 5px;margin-bottom:10px; margin-top:5px;height:25px;" data-id="'+val[3]+'">'+text.select_terminal+'</button>\
                                  </div>\
                                  </div></li>';
                    }
                }
                if (selected != false && selected.id == val[3]){
                    li.addClass('selected');
                }
                if (counter > settings.maxShow){
                    li.hide();
                }
                if (val[4] != city){
                    var li_city = $('<li class = "city">'+val[4]+'</li>');
                    if (counter > settings.maxShow){
                        li_city.hide();
                    }
                    list.append(li_city);
                    city = val[4];
                }
                list.append(li);
            });
            list.find('li').on('click',function(){
                if (!$(this).hasClass('city')){
                    list.find('li').removeClass('selected');
                    $(this).addClass('selected');
                    selectOption($(this));
                }
            });

            if ( autoselect == true && !cookie_terminal && ($('#shipping_postcode').val() || $('#billing_postcode').val()) ){
                var first = list.find('li:not(.city):first');
                list.find('li').removeClass('selected');
                first.addClass('selected');
                selectOption(first);
            }
            var selectedLi = list.find('li.selected');
            var topOffset = 0;
            /*
            if (selectedLi !== undefined){
                topOffset = selectedLi.offset().top - list.offset().top + list.scrollTop();                
            }
            console.log(topOffset);
            */
            list.scrollTop(topOffset);
            if (settings.showMap == true){
                document.querySelector('.found_terminals').innerHTML = '<ul class="omniva-terminals-listing" start="1">'+html+'</ul>';
                if (selected != false && selected.id != 0){
                    map.eachLayer(function (layer) { 
                        if (layer.options.terminalId !== undefined && L.DomUtil.hasClass(layer._icon, "active")){
                            L.DomUtil.removeClass(layer._icon, "active");
                        }
                        if (layer.options.terminalId == selected.id) {
                            //layer.setLatLng([newLat,newLon])
                            L.DomUtil.addClass(layer._icon, "active");
                        } 
                    });
                }
            }
        }
        
        function selectOption(option){
            select.val(option.attr('data-id'));
            omniva_setCookie('omniva_terminal', option.attr('data-id'), 30);
            select.trigger('change');
            selected = {'id':option.attr('data-id'),'text':option.text(),'distance':false};
            updateSelection();
            closeDropdown();
        }
        
        function updateSelection(){
            if (selected != false){
                dropdown.html(selected.text); 
            }
        }
        
        function toggleDropdown(){
            if (container.hasClass('open')){
                innerContainer.hide();
                container.removeClass('open');
            } else {
                innerContainer.show();
                container.addClass('open');
            }
        }  
        
        function closeDropdown(){
            if (container.hasClass('open')){
                innerContainer.hide();
                container.removeClass('open');
            } 
        }
        
        function resetList(){
   
            $.each( terminals, function( key, location ) {
                location['distance'] = false;
            });
    
            /*terminals.sort(function(a, b) { //Old sort
                var distOne = a[0];
                var distTwo = b[0];
                if (parseFloat(distOne) < parseFloat(distTwo)) {
                    return -1;
                }
                if (parseFloat(distOne) > parseFloat(distTwo)) {
                    return 1;
                }
                    return 0;
            });*/
            terminals.sort(function(a, b){ //Sort by name
                var nameOne = a[0];
                var nameTwo = b[0];
                return a[4].localeCompare(b[4]) || a[0].localeCompare(b[0]);
            });
        }
        
        function calculateDistance(y,x){
   
            $.each( terminals, function( key, location ) {
                distance = calcCrow(y, x, location[1], location[2]);
                location['distance'] = distance.toFixed(2);
                
            });
    
            terminals.sort(function(a, b) {
                var distOne = a['distance'];
                var distTwo = b['distance'];
                if (parseFloat(distOne) < parseFloat(distTwo)) {
                    return -1;
                }
                if (parseFloat(distOne) > parseFloat(distTwo)) {
                    return 1;
                }
                    return 0;
            });   
        }
        
        function toRad(Value) 
        {
           return Value * Math.PI / 180;
        }
    
        function calcCrow(lat1, lon1, lat2, lon2) 
        {
          var R = 6371;
          var dLat = toRad(lat2-lat1);
          var dLon = toRad(lon2-lon1);
          var lat1 = toRad(lat1);
          var lat2 = toRad(lat2);
    
          var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(lat1) * Math.cos(lat2); 
          var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
          var d = R * c;
          return d;
        }
        
        function findPosition(address,autoselect){
            if (address == "" || address.length < 3){
                resetList();
                showMore.hide();
                refreshList(autoselect);
                return false;
            }
            $.getJSON( "https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine="+address+"&sourceCountry="+omniva_current_country+"&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson", function( data ) {
              if (data.candidates != undefined && data.candidates.length > 0){
                calculateDistance(data.candidates[0].location.y,data.candidates[0].location.x);
                refreshList(autoselect);
                list.prepend(showMapBtn);
                showMore.show();
                if (settings.showMap == true){
                    setCurrentLocation([data.candidates[0].location.y,data.candidates[0].location.x]);
                }
              }
            });
        }
        
        function suggest(address){
            $.getJSON( "https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/suggest?text="+address+"&f=pjson&sourceCountry="+omniva_current_country+"&maxSuggestions=1", function( data ) {
              if (data.suggestions != undefined && data.suggestions.length > 0){
                findPosition(data.suggestions[0].text,false);
              }
            });
        }
        
        function initMap(){
            $('#omnivaMapContainer').html('<div id="omnivaMap"></div>');
            map = L.map('omnivaMap');
            if (omniva_current_country == "LT") {
                map.setView([54.999921, 23.96472], 8);
            }
            if (omniva_current_country == "LV") {
                map.setView([56.8796, 24.6032], 8);
            }
            if (omniva_current_country == "EE"){
                map.setView([58.7952, 25.5923], 7);
            }
            if (omniva_current_country == "FI"){
                map.setView([61.9241, 25.7482], 6);
            }
            L.tileLayer('https://maps.omnivasiunta.lt/tile/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.omniva.lt">Omniva</a>' +
                    ' | Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
            }).addTo(map);

            var Icon = L.Icon.extend({
                options: {
                    //shadowUrl: 'leaf-shadow.png',
                    iconSize:     [29, 34],
                    //shadowSize:   [50, 64],
                    iconAnchor:   [15, 34],
                    //shadowAnchor: [4, 62],
                    popupAnchor:  [-3, -76]
                }
            });
          
          var Icon2 = L.Icon.extend({
                options: {
                    iconSize:     [32, 32],
                    iconAnchor:   [16, 32]
                }
            });
            
          
            terminalIcon = new Icon({iconUrl: omnivadata.omniva_plugin_url+'assets/img/sasi.png'});
            homeIcon = new Icon2({iconUrl: omnivadata.omniva_plugin_url+'assets/img/locator_img.png'});
            
          var locations = JSON.parse(omnivaTerminals);
            jQuery.each( locations, function( key, location ) {
              L.marker([location[1], location[2]], {icon: terminalIcon, terminalId:location[3] }).on('click',function(e){ listTerminals(locations,0,this.options.terminalId);terminalDetails(this.options.terminalId);}).addTo(map);
            });
          
          //show button
          $('#show-omniva-map').show(); 
          
          $('#terminalsModal').on('click',function(){$('#omnivaLtModal').hide();});
          $('#omniva-search form input').off('keyup focus').on('keyup focus',function(){
                clearTimeout(timeoutID);      
                timeoutID = setTimeout(function(){ autoComplete($('#omniva-search form input').val())}, 500);    
                      
            });
            
            $('.omniva-autocomplete ul').off('click').on('click','li',function(){
                $('#omniva-search form input').val($(this).text());
                /*
                if ($(this).attr('data-location-y') !== undefined){
                    setCurrentLocation([$(this).attr('data-location-y'),$(this).attr('data-location-x')]);
                    calculateDistance($(this).attr('data-location-y'),$(this).attr('data-location-x'));
                    refreshList(false);
                }
                */
                $('#omniva-search form').trigger('submit');
                $('.omniva-autocomplete').hide();
            });
            $(document).click(function(e){
                var container = $(".omniva-autocomplete");
                if (!container.is(e.target) && container.has(e.target).length === 0) 
                    container.hide();
            });
          
            $('#terminalsModal').on('click',function(){
                $('#omnivaLtModal').hide();
            });
            $('#omniva-search form').off('submit').on('submit',function(e){
              e.preventDefault();
              var postcode = $('#omniva-search form input').val();
              findPosition(postcode,false);
            });
            $('.found_terminals').on('click','li',function(){
                zoomTo(JSON.parse($(this).attr('data-pos')),$(this).attr('data-id'));
            });
            $('.found_terminals').on('click','li button',function(){
                terminalSelected($(this).attr('data-id'));
            });
        }
        
        function autoComplete(address){
            var founded = [];
            $('.omniva-autocomplete ul').html('');
            $('.omniva-autocomplete').hide();
            if (address == "" || address.length < 3) return false;
            $('#omniva-search form input').val(address);
            //$.getJSON( "https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine="+address+"&sourceCountry="+omniva_current_country+"&category=&outFields=Postal,StAddr&maxLocations=5&forStorage=false&f=pjson", function( data ) {
            $.getJSON( "https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/suggest?text="+address+"&sourceCountry="+omniva_current_country+"&f=pjson&maxSuggestions=4", function( data ) {
              if (data.suggestions != undefined && data.suggestions.length > 0){
                  $.each(data.suggestions ,function(i,item){
                    //if (founded.indexOf(item.attributes.StAddr) == -1){
                        //const li = $("<li data-location-y = '"+item.location.y+"' data-location-x = '"+item.location.x+"'>"+item.address+"</li>");
                        const li = $("<li data-magickey = '"+item.magicKey+"' data-text = '"+item.text+"'>"+item.text+"</li>");
                        $(".omniva-autocomplete ul").append(li);
                    //}
                    //if (item.attributes.StAddr != ""){
                    //    founded.push(item.attributes.StAddr);
                    //}
                  });
              }
                  if ($(".omniva-autocomplete ul li").length == 0){
                      $(".omniva-autocomplete ul").append('<li>'+not_found+'</li>');
                  }
              $('.omniva-autocomplete').show();
            });
        }
        
        function terminalDetails(id) {
            /*
            terminals = document.querySelectorAll(".omniva-details")
            for(i=0; i <terminals.length; i++) {
                terminals[i].style.display = 'none';
            }
            */
            $('.omniva-terminals-listing li div.omniva-details').hide();
            id = 'omn-'+id;
            dispOmniva = document.getElementById(id)
            if(dispOmniva){
                dispOmniva.style.display = 'block';
            }      
        }
        
        function getLocation() {
          if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(loc) {
                if (selected == false){
                    setCurrentLocation([loc.coords.latitude, loc.coords.longitude]);
                }
            });
          } 
        }
        
        function setCurrentLocation(pos){
            if (currentLocationIcon){
              map.removeLayer(currentLocationIcon);
            }
            currentLocationIcon = L.marker(pos, {icon: homeIcon}).addTo(map);
            map.setView(pos,16);
            //calculateDistance(pos[0],pos[1]);
            //refreshList(false);
        }
        function listTerminals(locations,limit,id){
              if (limit === undefined){
                  limit=0;
              }
              if (id === undefined){
                  id=0;
              }
             var html = '', counter=1;
             if (id != 0 && !$.isArray(id)){
                previous_list = [];
                $('.found_terminals li').each(function(){
                    previous_list.push($(this).attr('data-id'));
                });
                $('.omniva-back-to-list').show();
             }
             if ($.isArray(id)){
                previous_list = []; 
             }
            $('.found_terminals').html('');
            $.each( locations, function( key, location ) {
              if (limit != 0 && limit < counter){
                return false;
              }
              if ($.isArray(id)){
                if ( $.inArray( location[3], id) == -1){
                    return true;
                }
              }
              else if (id !=0 && id != location[3]){
                return true;
              }
              if (autoSelectTerminal && counter == 1){
                terminalSelected(location[3],false);
              }
              var destination = [location[1], location[2]]
              var distance = 0;
              if (location['distance'] != undefined){
                distance = location['distance'];
              }
              html += '<li data-pos="['+destination+']" data-id="'+location[3]+'" ><div><a class="omniva-li">'+counter+'. <b>'+location[0]+'</b></a>';
              if (distance != 0) {
              html += ' <b>'+distance+' km.</b>';
              }
               html += '<div align="left" id="omn-'+location[3]+'" class="omniva-details" style="display:none;"><small>\
                                          '+location[5]+' <br/>'+location[6]+'</small><br/>\
                                          <button type="button" class="btn-marker" style="font-size:14px; padding:0px 5px;margin-bottom:10px; margin-top:5px;height:25px;" data-id="'+location[3]+'">'+text.select_terminal+'</button>\
                                          </div>\
                                          </div></li>';
                                              
                              counter++;           
                               
            });
            document.querySelector('.found_terminals').innerHTML = '<ul class="omniva-terminals-listing" start="1">'+html+'</ul>';
            if (id != 0){
                map.eachLayer(function (layer) { 
                    if (layer.options.terminalId !== undefined && L.DomUtil.hasClass(layer._icon, "active")){
                        L.DomUtil.removeClass(layer._icon, "active");
                    }
                    if (layer.options.terminalId == id) {
                        //layer.setLatLng([newLat,newLon])
                        L.DomUtil.addClass(layer._icon, "active");
                    } 
                });
            }
        }
        
        function zoomTo(pos, id){
            terminalDetails(id);
            map.setView(pos,14);
            map.eachLayer(function (layer) { 
                if (layer.options.terminalId !== undefined && L.DomUtil.hasClass(layer._icon, "active")){
                    L.DomUtil.removeClass(layer._icon, "active");
                }
                if (layer.options.terminalId == id) {
                    //layer.setLatLng([newLat,newLon])
                    L.DomUtil.addClass(layer._icon, "active");
                } 
            });
        }
        
        function terminalSelected(terminal,close) {
          if (close === undefined){
              close = true;
          }
              var matches = document.querySelectorAll(".omnivaOption");
              for (var i = 0; i < matches.length; i++) {
                node = matches[i];
                if ( node.value.includes(terminal)) {
                  node.selected = 'selected';
                } else {
                  node.selected = false;
                }
              }
                    
              $('select[name="omnivalt_terminal"]').val(terminal);
              $('select[name="omnivalt_terminal"]').trigger("change");
              if (close){
                $('#omnivaLtModal').hide();
            }
        }
        
        return this;
    };
 
}( jQuery ));
