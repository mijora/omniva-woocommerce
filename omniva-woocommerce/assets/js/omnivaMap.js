
var omvivaBinded = false;
var omnivaFilterCount = false;
var omnivaMap = {

  init : function(){
    
  var postcode = "";  
  
    var self = this;
      var omnivaCachedSearch = [];
//jQuery('document').ready(function(jQuery){
  var currentLocationIcon = false;
  var autoSelectTerminal = false;
  jQuery('#omnivaMapContainer').html('<div id="omnivaMap"></div>');
  if (omniva_current_country == "LT"){
    var map = L.map('omnivaMap').setView([54.999921, 23.96472], 8);
  } else if (omniva_current_country == "LV"){
    var map = L.map('omnivaMap').setView([56.8796, 24.6032], 8);
  } else if (omniva_current_country == "EE"){
    var map = L.map('omnivaMap').setView([58.7952, 25.5923], 7);
  } else {
    var map = L.map('omnivaMap').setView([54.999921, 23.96472], 8);
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
	var terminalIcon = new Icon({iconUrl: omnivadata.omniva_plugin_url+'assets/img/sasi.png'});
  var homeIcon = new Icon2({iconUrl: omnivadata.omniva_plugin_url+'assets/img/locator_img.png'});
  var select_terminal = omnivadata.text_select_terminal;
  var not_found = omnivadata.not_found;
  
  var locations = JSON.parse(omnivaTerminals);
    jQuery.each( locations, function( key, location ) {
      L.marker([location[1], location[2]], {icon: terminalIcon, terminalId:location[3] }).on('click',function(e){ listTerminals(locations,0,this.options.terminalId);terminalDetails(this.options.terminalId);}).addTo(map);
    });
  
  //show button
  jQuery('#show-omniva-map').show();
  if (!omvivaBinded){     
   
    jQuery('#omniva-search form').on('submit',function(e){
      e.preventDefault();
      var postcode = jQuery('#omniva-search form input').val();
      searchPostcode(postcode);
    });
    
    var timeoutID = null;
    
    jQuery('#omniva-search form input').on('keyup focus',function(){
        clearTimeout(timeoutID);      
        timeoutID = setTimeout(function(){ autoComplete(jQuery('#omniva-search form input').val())}, 500);    
              
    });
    
    jQuery('.omniva-autocomplete ul').on('click','li',function(){
        if (jQuery(this).attr('data-location-y') !== undefined){
            setCurrentLocation([jQuery(this).attr('data-location-y'),jQuery(this).attr('data-location-x')]);
        }
        jQuery('.omniva-autocomplete').hide();
    });
    jQuery(document).click(function(e){
        var container = jQuery(".omniva-autocomplete");
        if (!container.is(e.target) && container.has(e.target).length === 0) 
            container.hide();
    });
  
    jQuery('#terminalsModal').on('click',function(){jQuery('#omnivaLtModal').hide();});
    //jQuery('body').on('click','#show-omniva-map',showModal);
    
    /*
    jQuery('#shipping_postcode').on('change',function(){
      if (jQuery('#ship-to-different-address-checkbox').length > 0 && jQuery('#ship-to-different-address-checkbox').is(':checked')){
        postcode = jQuery(this).val();
        searchPostcode(postcode);
        autoSelectTerminal = true;
      }
    });
    jQuery('#billing_postcode').on('change',function(){
      if (jQuery('#ship-to-different-address-checkbox').length > 0 && !jQuery('#ship-to-different-address-checkbox').is(':checked')){
        postcode = jQuery(this).val();
        searchPostcode(postcode);
        autoSelectTerminal = true;
      }
    });
    */
    
      
    
    
     omvivaBinded = true;
  }
  /*
  jQuery('.omnivalt_terminal').on('select2:open', function (e) {
        jQuery(".omnivalt_terminal").data("select2").dropdown.$search.val(postcode).trigger('keyup');
        //console.log(postcode);
    });
  if (postcode == ""){
        if (jQuery('#ship-to-different-address-checkbox').length > 0 && jQuery('#ship-to-different-address-checkbox').is(':checked')){
            postcode = jQuery('#shipping_postcode').val();
        }  
        if (jQuery('#ship-to-different-address-checkbox').length > 0 && !jQuery('#ship-to-different-address-checkbox').is(':checked')){
            postcode = jQuery('#billing_postcode').val();
        }
        searchPostcode(postcode);
        autoSelectTerminal = true;
      }
      */
      /*
  function showModal(){
      if (jQuery('.omniva-terminals-list input.search-input').val() != ''){
          jQuery('#omniva-search input').val(jQuery('.omniva-terminals-list input.search-input').val());
          jQuery('#omniva-search button').trigger('click')
      }
    jQuery('#omnivaLtModal').show();
    getLocation();
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
  */
   
    jQuery('#shipping_postcode, #billing_postcode').trigger('change');
 
  function getLocation() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(loc) {
          if (postcode == ""){
        setCurrentLocation([loc.coords.latitude, loc.coords.longitude]);
          }
        });
      } 
    }
    
  function searchPostcode(postcode){
    if (postcode == "") return false;
    jQuery('#omniva-search form input').val(postcode);
    jQuery.getJSON( "http://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine="+postcode+"&sourceCountry="+omniva_current_country+"&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson", function( data ) {
      if (data.candidates != undefined && data.candidates.length > 0){
        if (data.candidates[0].score > 90){
          setCurrentLocation([data.candidates[0].location.y,data.candidates[0].location.x]);
        } else {
          jQuery('.found_terminals').html(not_found);
        }
      } else {
        jQuery('.found_terminals').html(not_found);
      }
    });
  }
  
  function autoComplete(address){
    jQuery('.omniva-autocomplete ul').html('');
    jQuery('.omniva-autocomplete').hide();
    if (address == "" || address.length < 3) return false;
    jQuery('#omniva-search form input').val(address);
    jQuery.getJSON( "http://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine="+address+"&sourceCountry="+omniva_current_country+"&category=&outFields=Postal&maxLocations=5&forStorage=false&f=pjson", function( data ) {
      if (data.candidates != undefined && data.candidates.length > 0){
          jQuery.each(data.candidates ,function(i,item){
            //console.log(item);
            const li = jQuery("<li data-location-y = '"+item.location.y+"' data-location-x = '"+item.location.x+"'>"+item.address+"</li>");
            jQuery(".omniva-autocomplete ul").append(li);
          });
      }
          if (jQuery(".omniva-autocomplete ul li").length == 0){
              jQuery(".omniva-autocomplete ul").append('<li>'+not_found+'</li>');
          }
      jQuery('.omniva-autocomplete').show();
    });
  }
  
  /*
  getTerminalsId = function(postcode){
    if (postcode == "") return false;
    if (omnivaCachedSearch[postcode] !== undefined) return omnivaCachedSearch[postcode];
    //jQuery('#omniva-search form input').val(postcode);

    jQuery.ajax({ dataType: "json", async:false, url:"http://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine="+postcode+"&sourceCountry="+omniva_current_country+"&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson", success : function( data ) {
      if (data.candidates != undefined && data.candidates.length > 0){
        if (data.candidates[0].score > 90){
          var terminals = findClosest([data.candidates[0].location.y,data.candidates[0].location.x],true);
          //console.log(terminals);
          var filteredTerminals = [];
          var counter = 0;
          jQuery.each( terminals, function( key, location ) {
            filteredTerminals.push([location[3],location['distance'],location[4]]);
            counter++;
            if (counter>=8) return false;
          });
          omnivaCachedSearch[postcode] = filteredTerminals;
          return filteredTerminals;
        } else {
          omnivaCachedSearch[postcode] = [];
        }
        return false;
        
    }}
    });
  }
  */
  setCurrentLocation = function(pos){
    if (currentLocationIcon){
      map.removeLayer(currentLocationIcon);
    }
    //console.log('home');
    currentLocationIcon = L.marker(pos, {icon: homeIcon}).addTo(map);
    map.setView(pos,16);
    findClosest(pos);
  }
  
  function listTerminals(locations,limit,id){
      if (limit === undefined){
          limit=0;
      }
      if (id === undefined){
          id=0;
      }
     var html = '', counter=1;
    jQuery('.found_terminals').html('');
    jQuery.each( locations, function( key, location ) {
      if (limit != 0 && limit < counter){
        return false;
      }
      if (id !=0 && id != location[3]){
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
      html += '<li onclick="zoomTo(['+destination+'],'+location[3]+')" ><div><a class="omniva-li">'+counter+'. <b>'+location[0]+'</b></a> <b>'+distance+' km.</b>\
                                  <div align="left" id="omn-'+location[3]+'" class="omniva-details" style="display:none;"><small>\
                                  '+location[5]+' <br/>'+location[6]+'</small><br/>\
                                  <button type="button" class="btn-marker" style="font-size:14px; padding:0px 5px;margin-bottom:10px; margin-top:5px;height:25px;" onclick="terminalSelected('+location[3]+')">'+select_terminal+'</button>\
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
  
  zoomTo = function(pos, id){
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
  
  terminalSelected = function(terminal,close) {
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
                
          jQuery('select[name="omnivalt_terminal"]').val(terminal);
          jQuery('select[name="omnivalt_terminal"]').trigger("change");
          if (close){
            jQuery('#omnivaLtModal').hide();
          }
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
        
  function findClosest(pos,filter) {
    if (filter === undefined){
        filter = false;
    }
      jQuery.each( locations, function( key, location ) {
        distance = calcCrow(pos[0], pos[1], location[1], location[2]);
        location['distance'] = distance.toFixed(2);
        
      });
    
                locations.sort(function(a, b) {
                    var distOne = a['distance']
                    var distTwo = b['distance']
                    if (parseFloat(distOne) < parseFloat(distTwo)) {
                        return -1;
                    }
                    if (parseFloat(distOne) > parseFloat(distTwo)) {
                        return 1;
                    }
                    return 0;
                })
    
        if (filter){
          return locations;
        } else {
        listTerminals(locations,8);
        }
  }
       
  }    
}