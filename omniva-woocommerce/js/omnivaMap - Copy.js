  var searchWidget;
  var postcode = jQuery('#shipping_postcode').val();
  var autoTrigger = false;
  
  function matchCustom(params, data) {
    if (jQuery.trim(params.term) === '') {
      return data;
    }
    
    if (jQuery.trim(params.term).length == 5 && jQuery.isNumeric(jQuery.trim(params.term))) {
      autoTrigger = true;
      searchWidget.suggest(params.term);
      return data;
    }
    
    // Do not display the item if there is no 'text' property
    if (typeof data.text === 'undefined') {
      
      return null;
    }

    // `params.term` should be the term that is used for searching
    // `data.text` is the text that is displayed for the data object
    if (data.text.indexOf(params.term) > -1) {
      var modifiedData = jQuery.extend({}, data, true);
      modifiedData.text += ' (matched)';

      // You can return modified objects from here
      // This includes matching the `children` how you want in nested data sets
      return modifiedData;
    }

    // Return `null` if the term should not be displayed
    return null;
}

  jQuery(document).ready(function($) {
    
    $('body').on('click','#show-omniva-map',function(){
      postcode = $('#shipping_postcode').val();
      $('#omnivaLtModal').show();
      console.log('clicked');
    });
    $('#shipping_postcode').on('change',function(){
      postcode = $(this).val();
      console.log(postcode);
    });
    $('#billing_postcode').on('change',function(){
      if ($('#ship-to-different-address-checkbox').length > 0 && !$('#ship-to-different-address-checkbox').is(':checked')){
        postcode = $(this).val();
        console.log(postcode);
        //searchWidget.searchTerm = postcode;
        autoTrigger = true;
        searchWidget.suggest(postcode);
        //searchWidget.search();
      }
    });
    

  
  });
  var modal = document.getElementById('omnivaLtModal');
  var locations = JSON.parse(omnivadata.locations);
  window.document.onclick = function(event) {
    if (event.target == modal || event.target.id == 'omnivaLtModal' || event.target.id == 'terminalsModal') {
        document.getElementById('omnivaLtModal').style.display = "none";
    } else if(event.target.id == 'show-omniva-btn') {
        document.getElementById('omnivaLtModal').style.display = "block";
    }
}

            var select_terminal = omnivadata.select_terminal;
    
            function popTemplate(id, name, city, address, comment) {
                return {
                title: name,
                content: "<br/><b>"+city+"</b><br> " +
                            "<b>"+address+"</b><br> " +
                            comment+"<br>  " +
                            "<Button onclick='terminalSelected("+id+");' class='omniva-btn'>"+select_terminal+"</Button>",
                }
            }
    
            var text_search_placeholder = omnivadata.text_search_placeholder;
            var base_url = window.location.origin;
            var map, geocoder, markerAddress, opp = true;
            if(typeof DEBUG !== 'undefined') {
                console.log('debug is true')
                base_url += '/wordpress49' 
            }
            var image = omnivadata.omniva_plugin_url+'/sasi.png';
            var locator_img = omnivadata.omniva_plugin_url+ '/locator_img.png';
            var view, goToLayer, zoomTo, findNearest;
    
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
    
        function terminalSelected(terminal) {
          var matches = document.querySelectorAll(".omnivaOption");
          for (var i = 0; i < matches.length; i++) {
            node = matches[i]
            if ( node.value.includes(terminal)) {
              node.selected = 'selected';
            } else {
              node.selected = false;
            }
          }
                
          jQuery('select[name="omnivalt_terminal"]').val(terminal);
          jQuery('select[name="omnivalt_terminal"]').trigger("change");
          jQuery('#omnivaLtModal').hide();
        }
    
        function selectToMap(terminal_id) {
            view.when(function(){
                view.graphics.forEach(function(graphic){ 
                    var omniva = Object.assign({}, graphic.omniva);
                    if(graphic.omniva.id == terminal_id) {
                        view.zoom = 13
                        view.goTo(graphic);
                        var popup = view.popup;
                        popup.title =  omniva.name,
                        popup.content = "<b>"+omniva.address+"</b><br>"+omniva.comment+"<br>"+
                            "<Button onclick='terminalSelected("+omniva.id+");' class='omniva-btn'>"+select_terminal+"</Button>",                    
                        popup.location = graphic.geometry;      
                        popup.open();    
                    }
                }); 
            });
        }
    
    window.onload = function() {

            var element = document.getElementById('omniva-search');
            if (element)
            element.addEventListener('keypress', function(evt){
              var isEnter = evt.keyCode == 13;
              if (isEnter) {
                  evt.preventDefault();
                  selection = document.querySelector(".esri-search__suggestions-list > li");
                  if (selection)
                    selection.click();
              }
            });

    require([
      "esri/Map",
      "esri/views/MapView",
      "esri/Graphic",
      "esri/widgets/Search",
      "esri/tasks/Locator"
    ], function(
      Map, MapView, Graphic, Search, Locator
    ) {
    
      var map = new Map({
        basemap: "streets-navigation-vector"
      });
    
       view = new MapView({
        center: [23.96472, 54.999921],
        container: "map-omniva-terminals",
        map: map,
        zoom: 6
      });
    
      var markerSymbol = {
        type: "picture-marker",
        url: omnivadata.omniva_plugin_url+"/sasi.png",
        width: "24px",
       height: "30px"
      };
    
        for (i = 0; i < locations.length; i++) {  
            var graphic = new Graphic({
                geometry: {
                    type: "point",
                    longitude: locations[i][2],
                    latitude: locations[i][1],
                },
                omniva: {
                    name: locations[i][0],
                    city: locations[i][4],
                    address: locations[i][5],
                    id: locations[i][3],
                    comment: locations[i][6]
                },
                symbol: markerSymbol,
                    popupTemplate: popTemplate(locations[i][3], locations[i][0], locations[i][4], locations[i][5], locations[i][6])
                })
                view.graphics.add(graphic);
            }
    
            /* Search widget*/
            searchLoc = new Locator({ url: "https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer" }),
            searchLoc.countryCode = "LTU"
            searchWidget = new Search({
                view: view,
                position: "top-left",
                enableInfoWindow: false,
                popupEnabled: false,
                minSuggestCharacters:4,
                maxResults: 6,
                searchTerm: postcode,
                includeDefaultSources:false,
                container: "omniva-search",
                autoSelect: true,
            });
    
            sources = [{
                    locator: searchLoc,
                    countryCode: "LTU",
                    placeholder: text_search_placeholder,
                    resultSymbol: {
                        type: "picture-marker",
                        url: locator_img,
                        size: 24,
                        width: 24,
                        height: 24,
                        xoffset: 0,
                        yoffset: 0
                    }
                }
            ]
    
            searchWidget.sources = sources;
            searchWidget.renderNow();
    
            zoomTo = function(graphic, id) {
                terminalDetails(id);
                view.graphics.forEach(function(graphic){ 
                    var omniva = Object.assign({}, graphic.omniva);
                    if (graphic && graphic.omniva && graphic.omniva.id == id) {
                        view.zoom = 15
                        view.goTo(graphic);
                        /*
                        var popup = view.popup;
                        popup.title =  omniva.name,
                        popup.content = "<b>"+omniva.address+"</b><br>"+omniva.comment+"<br>"+
                        "<Button onclick='terminalSelected("+omniva.id+");' class='omniva-btn'>"+select_terminal+"</Button>",                    
                        popup.location = graphic.geometry;      
                        popup.open();    
                        */
                    }
                });  
            }
    
            function terminalDetails(id) {
                terminals = document.querySelectorAll(".omniva-details")
                for(i=0; i <terminals.length; i++) {
                    terminals[i].style.display = 'none';
                }
                id = 'omn-'+id;
                dispOmniva = document.getElementById(id)
                if(dispOmniva)
                    dispOmniva.style.display = 'block';
            }
    
           findNearest = function() {
                navigator.geolocation.getCurrentPosition(function(loc) {
                    findClosest(loc.coords.latitude, loc.coords.longitude)
                })
            }
    
            function findClosest(lat, lng) {console.log('[[FindClosest]]');
                view.zoom = 12
                view.center = [lng, lat];
                filteredGRAF = view.graphics.map(function(graphic){
                        var latitude = graphic.geometry.latitude
                        var longitude = graphic.geometry.longitude
                        var distance = calcCrow(lat, lng, latitude, longitude)
                        graphic.geometry.distance =distance.toFixed(2)
                        return graphic
                });
    
                /* Exception for ie compiler having 2014 and lower versions *//*
                if (filteredGRAF && filteredGRAF._items && filteredGRAF._items.length ) {
                    filteredGRAF = filteredGRAF._items;
                }*/
    
                filteredGRAF.sort(function(a, b) {
                    var distOne = a.geometry.distance
                    var distTwo = b.geometry.distance
                    if (parseFloat(distOne) < parseFloat(distTwo)) {
                        return -1;
                    }
                    if (parseFloat(distOne) > parseFloat(distTwo)) {
                        return 1;
                    }
                    return 0;
                })
            if (filteredGRAF.length > 0) {
                filteredGRAF = filteredGRAF.slice(1, 16);
                var count = 15, counter = 1, html = '';
    
                filteredGRAF.forEach(function(terminal){
    
                    var omniva = terminal.omniva;
                    var termGraphic = terminal;
                    var destination = [terminal.geometry.longitude, terminal.geometry.latitude]
    
                    var goTo = {
                            target: destination,
                            zoom: 5
                            }
    
                    html += '<li onclick="zoomTo(['+destination+'],'+omniva.id+')" ><div><a class="omniva-li">'+counter+'. <b>'+omniva.name+'</b></a> <b>'+terminal.geometry.distance+' km.</b>\
                                <div align="left" id="omn-'+omniva.id+'" class="omniva-details" style="display:none;"><small>\
                                '+omniva.address+' <br/>'+omniva.comment+'</small><br/>\
                                <button type="button" class="btn-marker" style="font-size:14px; padding:0px 5px;margin-bottom:10px; margin-top:5px;height:25px;" onclick="terminalSelected('+omniva.id+')">'+select_terminal+'</button>\
                                </div>\
                                </div></li>';
                    if (counter == 1 && autoTrigger){
                      autoTrigger = false;
                      terminalSelected(omniva.id);                      
                    }                    
                    counter++;
                })
    
                document.querySelector('.found_terminals').innerHTML = '<ul class="omniva-terminals-list" start="1">'+html+'</ul>';
            }
        }
    
        searchWidget.on("select-result", function(event) {
            latitude = event.result.feature.geometry.latitude;
            longitude = event.result.feature.geometry.longitude;
            findClosest(latitude, longitude);
            return true;
        });
        
        searchWidget.on("suggest-complete", function(event) {
            if (event.results.length > 0 && autoTrigger){
              var suggest = event.results[0].results[0].text;
              
                console.log(event.results[0].results[0]);
              if (suggest){
                console.log(suggest);
                searchWidget.searchTerm = suggest;
                //searchWidget.search();
                //var element = document.getElementById('omniva-search');
                var selection = document.querySelector(".esri-search__suggestions-list > li");
                  if (selection)
                    selection.click();
              }
            }
        });
    });
}
