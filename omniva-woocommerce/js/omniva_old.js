jQuery('document').ready(function($){
    var current_postcode = '';
    $('input.shipping_method').on('click',function(){
        var current_method = $(this);
        if (current_method.val() == "omnivalt_pt"){
            $('.terminal-container').show();
        } else {
            $('.terminal-container').hide();
        }
    });
    $('input.shipping_method:checked').trigger('click');
    
    $( document ).on( 'change', '.omnivalt_terminal', function() {
        var terminal_id = $(this).val();
        $.ajax({
            url : omnivadata.ajax_url,
            type : 'post',
            data : {
                action : 'add_terminal_to_session',
                terminal_id : terminal_id
            },
            success : function( response ) {
               
            }
        });
    });

    $( document ).on( 'omnivalt.checkpostcode', function() {
        if ($('#ship-to-different-address-checkbox').length > 0 && $('#ship-to-different-address-checkbox').is(':checked')){
            if (current_postcode != $('#shipping_postcode').val()) {
                //console.log('postcode changed (shipping):', current_postcode, $('#shipping_postcode').val());
                current_postcode = $('#shipping_postcode').val();
                $('.terminal-container .search-input').val(current_postcode).trigger('selectpostcode');
            }
        } else {
            if (current_postcode != $('#billing_postcode').val()) {
                //console.log('postcode changed (billing):', current_postcode, $('#billing_postcode').val());
                current_postcode = $('#billing_postcode').val();
                $('.terminal-container .search-input').val(current_postcode).trigger('selectpostcode');
            }
        }
    });    
});
var omniva_addrese_change = false;
(function ( $ ) {
    $.fn.omniva = function(options) {
        var settings = $.extend({
            maxShow: 8,
            showMap: true,
        }, options );
        //console.log('called');
        var timeoutID = null;
        var currentLocationIcon = false;
        var autoSelectTerminal = false;
        var searchTimeout = null;
        var select = $(this);
        var select_terminal = omnivadata.text_select_terminal;
        var not_found = omnivadata.not_found;
        var terminalIcon = null;
        var homeIcon = null;
        var map = null;
        //var terminals = [];
        var terminals = JSON.parse(omnivaTerminals);
        var selected = false;
        var previous_list = [];
        select.hide();
        if (select.val()){
            selected = {'id':select.val(),'text':select.find('option:selected').text(),'distance':false};
        }
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
        var dropdown = $('<div class = "dropdown">'+omnivadata.text_select_terminal+'</div>');
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
        
        refreshList(false);
        
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
            searchTimeout = setTimeout(function() { suggest(search.val())}, 400);    
                  
        });
        search.on('selectpostcode',function(){
            findPosition(search.val(),true);    
                  
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
            //console.log('1');
          }

        function searchByAddress(){
            if (selected == false){
            var postcode = '';
            if (omniva_addrese_change == true){
                if ($('#ship-to-different-address-checkbox').length > 0 && $('#ship-to-different-address-checkbox').is(':checked')){
                    postcode = $('#shipping_postcode').val();
                }  
                if ($('#ship-to-different-address-checkbox').length > 0 && !$('#ship-to-different-address-checkbox').is(':checked')){
                    postcode = $('#billing_postcode').val();
                }
                if (postcode != ''){
                    search.val(postcode).trigger('selectpostcode');
                }
                //console.log('search '+postcode);
            } else {
                omniva_addrese_change = true;
            }
            $('#shipping_postcode').on('change',function(){
              if ($('#ship-to-different-address-checkbox').length > 0 && $('#ship-to-different-address-checkbox').is(':checked')){
                postcode = $(this).val();
                search.val(postcode).trigger('select');
              }
            });
            $('#billing_postcode').on('change',function(){
              if ($('#ship-to-different-address-checkbox').length > 0 && !$('#ship-to-different-address-checkbox').is(':checked')){
                postcode = $(this).val();
                search.val(postcode).trigger('select');
              }
            }); 
            }
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
                        //console.log('add-to-map');
                        html += '<li data-pos="['+[val[1], val[2]]+']" data-id="'+val[3]+'" ><div><a class="omniva-li">'+counter+'. <b>'+val[0]+'</b></a> <b>'+val['distance']+' km.</b>\
                                  <div align="left" id="omn-'+val[3]+'" class="omniva-details" style="display:none;"><small>\
                                  '+val[5]+' <br/>'+val[6]+'</small><br/>\
                                  <button type="button" class="btn-marker" style="font-size:14px; padding:0px 5px;margin-bottom:10px; margin-top:5px;height:25px;" data-id="'+val[3]+'">'+select_terminal+'</button>\
                                  </div>\
                                  </div></li>';
                    }
                } else {
                    if (settings.showMap == true ){
                        //console.log('add-to-map');
                        html += '<li data-pos="['+[val[1], val[2]]+']" data-id="'+val[3]+'" ><div><a class="omniva-li">'+(i+1)+'. <b>'+val[0]+'</b></a>\
                                  <div align="left" id="omn-'+val[3]+'" class="omniva-details" style="display:none;"><small>\
                                  '+val[5]+' <br/>'+val[6]+'</small><br/>\
                                  <button type="button" class="btn-marker" style="font-size:14px; padding:0px 5px;margin-bottom:10px; margin-top:5px;height:25px;" data-id="'+val[3]+'">'+select_terminal+'</button>\
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
            if (autoselect == true){
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
                container.removeClass('open') 
            } else {
                innerContainer.show();
                container.addClass('open');
            }
        }  
        
        function closeDropdown(){
            if (container.hasClass('open')){
                innerContainer.hide();
                container.removeClass('open') 
            } 
        }
        
        function resetList(){
   
            $.each( terminals, function( key, location ) {
                location['distance'] = false;
                
            });
    
            terminals.sort(function(a, b) {
                var distOne = a[0];
                var distTwo = b[0];
                if (parseFloat(distOne) < parseFloat(distTwo)) {
                    return -1;
                }
                if (parseFloat(distOne) > parseFloat(distTwo)) {
                    return 1;
                }
                    return 0;
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
            //console.log(address);
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
                //console.log('add');
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
          if (omniva_current_country == "LT"){
            map = L.map('omnivaMap').setView([54.999921, 23.96472], 8);
          }
          if (omniva_current_country == "LV"){
            map = L.map('omnivaMap').setView([56.8796, 24.6032], 8);
          }
          if (omniva_current_country == "EE"){
            map = L.map('omnivaMap').setView([58.7952, 25.5923], 7);
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
            
          
            terminalIcon = new Icon({iconUrl: omnivadata.omniva_plugin_url+'images/sasi.png'});
            homeIcon = new Icon2({iconUrl: omnivadata.omniva_plugin_url+'images/locator_img.png'});
            
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
                    //console.log(item);
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
            //console.log('home');
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
            //console.log(id);
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
                                          <button type="button" class="btn-marker" style="font-size:14px; padding:0px 5px;margin-bottom:10px; margin-top:5px;height:25px;" data-id="'+location[3]+'">'+select_terminal+'</button>\
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
                node = matches[i]
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
