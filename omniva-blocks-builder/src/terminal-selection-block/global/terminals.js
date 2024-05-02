import { txt } from './text';
import { getOmnivaData } from './omniva';
import { isObjectEmpty, insertAfter, getJsonDataFromUrl } from './utils';

const markSelectControlValue = ( selectElem, value ) => {
    selectElem.value = value;
    const event = new Event('change', { bubbles: true });
    selectElem.dispatchEvent(event);
};

export const getTerminalsByCountry = ( country, type ) => {
    return fetch(`${getOmnivaData().ajax_url}?action=omnivalt_get_terminals&country=${country}&type=${type}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error fetching terminals:', error);
        return [];
    });
};

export const loadMap = () => {
    return {
        lib: null,
        elements: {},
        translations: {},
        params: {},

        load_data: function ( params ) {
            this.elements = {
                org_field: this.set_param(params, 'org_field', null),
                map_container: this.set_param(params, 'map_container', null)
            };
            this.params = {
                provider: this.set_param(params, 'provider', 'omniva'),
                terminals_type: this.set_param(params, 'terminals_type', 'terminal'),
                selected_terminal: this.set_param(params, 'selected_terminal', ''),
                icons_url: this.set_param(params, 'icons_url', `${getOmnivaData().plugin_url}assets/img/terminal-mapping/`),
                country: this.set_param(params, 'country', 'LT'),
                //show_map: getOmnivaData().show_map,
                map_icon: this.set_param(params, 'map_icon', 'omnivalt_icon.png')
            };
            const modal_header = (this.params.terminals_type == 'post') ? txt.map.modal_title_post : txt.map.modal_title_terminal;
            const provider = (this.params.provider in txt.providers) ? txt.providers[this.params.provider] : txt.providers.omniva;
            this.translations = {
                modal_header: provider + " " + modal_header,
                terminal_list_header: (this.params.terminals_type == 'post') ? txt.map.modal_search_title_post : txt.map.modal_search_title_terminal,
                select_pickup_point: (this.params.terminals_type == 'post') ? txt.map.select_post : txt.map.select_terminal,
                seach_header: txt.map.search_placeholder,
                search_btn: txt.map.search_button,
                select_btn: txt.map.select_button,
                modal_open_btn: txt.map.modal_open_button,
                geolocation_btn: txt.map.use_my_location,
                your_position: txt.map.my_position,
                nothing_found: txt.map.not_found,
                no_cities_found: txt.map.no_cities_found,
                geolocation_not_supported: txt.map.geo_not_supported
            };
        },

        set_param: function ( all_params, param_key, fail_value = null ) {
            if ( ! (param_key in all_params) ) {
                return fail_value;
            }
            return all_params[param_key];
        },

        init: function ( terminals ) {
            if ( ! this.elements.map_container ) {
                this.error('Failed to get a container for the map');
                return;
            }
            this.lib = new TerminalMappingOmnivalt();
            
            this.lib.setImagesPath(this.params.icons_url);
            this.lib.setTranslation(this.translations);
            this.lib.dom.setContainerParent(this.elements.map_container);

            this.lib.setParseMapTooltip(( location, leafletCoords ) => {
                let tip = location.address + " [" + location.id + "]";
                if ( location.comment ) {
                    tip += "<br/><i>" + location.comment + "</i>";
                }
                return tip;
            });

            this.build_actions(this);

            this.lib.init({
                country_code: this.params.country,
                identifier: 'omnivalt',
                isModal: true,
                modalParent: this.elements.map_container,
                hideContainer: true,
                hideSelectBtn: true,
                cssThemeRule: 'tmjs-default-theme',
                customTileServerUrl: 'https://maps.omnivasiunta.lt/tile/{z}/{x}/{y}.png',
                customTileAttribution: '&copy; <a href="https://www.omniva.lt">Omniva</a>' + ' | Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
                terminalList: terminals,
            });
        },

        build_actions: function( thisMap ) {
            this.lib.sub('tmjs-ready', function( data ) {
                thisMap.lib.map.createIcon('omnivalt_icon', thisMap.params.icons_url + thisMap.params.map_icon);
                thisMap.lib.map.refreshMarkerIcons();
                
                let selected_location = data.map.getLocationById(thisMap.params.selected_terminal);
                if ( typeof(selected_location) != 'undefined' && selected_location != null ) {
                    thisMap.lib.dom.setActiveTerminal(selected_location);
                    thisMap.lib.publish('terminal-selected', selected_location);
                }
            });

            this.lib.sub("terminal-selected", function( data ) {
                markSelectControlValue(thisMap.elements.org_field, data.id);
                thisMap.lib.dom.setActiveTerminal(data.id);
                thisMap.lib.publish("close-map-modal");
            });
        },

        set_search_value: function( value ) {
            value = value.trim();
            if ( value == '' ) {
                return;
            }

            this.lib.dom.searchNearest(value);
            this.lib.dom.UI.modal.querySelector('.tmjs-search-input').value = value;
        },

        error: function( error_text ) {
            console.error('OMNIVA MAP:', error_text);
        }
    };
};

export const removeMap = ( mapContainer ) => {
    while ( mapContainer.firstChild ) {
        mapContainer.removeChild(mapContainer.lastChild);
    }
};

export const loadCustomSelect = () => {
    return {
        map: null,
        selected: {},
        terminals: [],
        elements: {},
        params: {},
        translations: {},
        loaded: false,

        load_data: function( params ) {
            this.elements = {
                org_field: this.set_param(params, 'org_field', null),
                custom_container: this.set_param(params, 'custom_container', null),
                this_dropdown: null,
                this_list: null,
                this_container: null,
                this_inner_container: null,
                this_loader: null,
                this_search: null,
                this_search_msg: null,
                this_show_more: null
            };
            this.params = {
                provider: this.set_param(params, 'provider', 'omniva'),
                terminals_type: this.set_param(params, 'terminals_type', 'terminal'),
                country: this.set_param(params, 'country', 'LT'),
                max_show: this.set_param(params, 'max_show', 8),
                active_timeout: null,
                autoselect: this.set_param(params, 'autoselect', true),
                selected_terminal: this.set_param(params, 'selected_terminal', '')
            };
            const provider = (this.params.provider in txt.providers) ? txt.providers[this.params.provider] : txt.providers.omniva;
            this.translations = {
                not_found: txt.select.not_found,
                too_short: txt.select.search_too_short,
                select_terminal: (this.params.terminals_type == 'post') ? txt.select.post_select : txt.select.terminal_select,
                enter_address: txt.select.enter_address,
                show_more: txt.select.show_more
            };
        },

        set_param: function ( all_params, param_key, fail_value = null ) {
            if ( ! (param_key in all_params) ) {
                return fail_value;
            }
            return all_params[param_key];
        },

        init: function() {
            if ( isObjectEmpty(this.elements) ) {
                this.error('Load data is required before initialization');
                return;
            }
            if ( ! this.elements.custom_container ) {
                this.error('Failed to get a container for the custom field');
                return;
            }

            if ( this.elements.org_field.value ) {
                this.set_selected();
            }

            let listElem, link, linkText = null;
            
            this.elements.this_container = document.createElement('div');
            this.elements.this_container.classList.add('omnivalt-terminals-list');
            
            this.elements.this_dropdown = document.createElement('div');
            this.elements.this_dropdown.classList.add('dropdown');
            this.elements.this_dropdown.innerHTML = this.translations.select_terminal;
            this.update_element_dropdown();

            this.elements.this_search = document.createElement('input');
            this.elements.this_search.type = 'text';
            this.elements.this_search.classList.add('search-input');
            this.elements.this_search.placeholder = this.translations.enter_address;

            this.elements.this_search_msg = document.createElement('span');
            this.elements.this_search_msg.classList.add('search-msg');
            this.show_element_search_msg(false);

            this.elements.this_loader = document.createElement('div');
            this.elements.this_loader.classList.add('omnivalt-loader');
            this.show_element_loader(false);

            this.elements.this_list = document.createElement('ul');

            this.elements.this_show_more = document.createElement('div');
            this.elements.this_show_more.classList.add('show-more');
            link = document.createElement('a');
            linkText = document.createTextNode(this.translations.show_more);
            link.appendChild(linkText);
            link.href = '#';
            this.elements.this_show_more.appendChild(link);

            this.elements.this_inner_container = document.createElement('div');
            this.elements.this_inner_container.classList.add('inner-container');
            this.show_element_inner_container(false);

            this.elements.this_inner_container.append(
                this.elements.this_search,
                this.elements.this_search_msg,
                this.elements.this_loader,
                this.elements.this_list,
                this.elements.this_show_more
            );
            this.elements.this_container.append(
                this.elements.this_dropdown,
                this.elements.this_inner_container
            );
            this.elements.custom_container.append(this.elements.this_container);

            this.reset_terminals();
            this.refresh_element_list();

            /* Events */
            this.elements.this_show_more.addEventListener('click', (e) => {
                e.preventDefault();
                this.show_element_all_options();
            });

            this.elements.this_dropdown.addEventListener('click', (e) => {
                this.toggle_dropdown();
            });

            this.elements.org_field.addEventListener('click', (e) => {
                this.set_selected();
                this.update_element_dropdown();
                this.elements.this_list.querySelector('li[data-id="' + this.elements.org_field.value + '"').classList.add('selected');
            });

            this.elements.this_search.addEventListener('keyup', () => {
                this.show_element_search_msg(false);
                this.show_element_loader(true);
                clearTimeout(this.params.active_timeout);
                this.params.active_timeout = setTimeout(() => {
                    this.params.autoselect = false;
                    this.geo_suggest(this.elements.this_search.value);
                }, 400);
            });

            this.elements.this_search.addEventListener('keyup', (e) => {
                if ( e.which == '13' ) {
                    e.preventDefault();
                }
            });

            document.addEventListener('mousedown', (e) => {
                if ( this.elements.this_container != e.target
                    && ! this.elements.this_container.contains(e.target) 
                    && this.elements.this_container.classList.contains('open')
                ) {
                    this.toggle_dropdown(true);
                }
            });

            this.loaded = true;
        },

        set_terminals: function( terminals ) {
            for ( let i = 0; i < terminals.length; i++ ) {
                terminals[i]['distance'] = false;
            }
            this.terminals = terminals;
        },

        set_selected: function() {
            this.selected = {
                id: this.params.selected_terminal,
                text: this.elements.org_field.options[this.elements.org_field.selectedIndex].text,
                distance: false
            };
        },

        set_search_value: function( value ) {
            this.elements.this_search.value = value.trim();
            this.geo_suggest(this.elements.this_search.value);
        },

        activate_autoselect: function() {
            if ( this.params.selected_terminal == '' ) {
                let firstElem = this.elements.this_list.querySelector('li:not(.city)');
                this.mark_element_list_selected(firstElem);
            }
        },

        update_element_dropdown: function() {
            if ( 'text' in this.selected ) {
                this.elements.this_dropdown.innerHTML = this.selected.text;
            }
        },

        show_element_loader: function( show = false ) {
            if ( show ) {
                this.elements.this_loader.style.display = 'block';
            } else {
                this.elements.this_loader.style.display = 'none';
            }
        },

        show_element_search_msg: function( show = false ) {
            if ( show ) {
                this.elements.this_search_msg.style.display = 'block';
            } else {
                this.elements.this_search_msg.style.display = 'none';
            }
        },

        show_element_inner_container: function( show = false ) {
            if ( show ) {
                this.elements.this_inner_container.style.display = 'block';
            } else {
                this.elements.this_inner_container.style.display = 'none';
            }
        },

        refresh_element_list: function() {
            let counter = 0;
            let city = false;
            let html = '';
            let listElem, listCityElem, boldTextElem, textElem, selectedElem = null;
            let topOffset = 0;

            this.clear_element_list();

            for ( let terminal of this.terminals ) {
                listElem = document.createElement('li');
                listElem.setAttribute('data-id', terminal.id);
                listElem.innerHTML = terminal.name;
                if ( 'distance' in terminal && terminal.distance !== false ) {
                    boldTextElem = document.createElement('strong');
                    textElem = document.createTextNode('' + terminal.distance + 'km');
                    boldTextElem.appendChild(textElem);
                    listElem.innerHTML += ' ' + boldTextElem.outerHTML;
                    counter++;
                } else {
                    this.elements.this_show_more.style.display = 'none';
                }
                if ( 'id' in this.selected && this.selected.id == terminal.id ) {
                    listElem.classList.add('selected');
                }
                if ( counter > this.params.max_show ) {
                    listElem.style.display = 'none';
                }
                if ( city != terminal.city ) {
                    listCityElem = document.createElement('li');
                    listCityElem.classList.add('city');
                    listCityElem.innerHTML = terminal.city;
                    if ( counter > this.params.max_show ) {
                        listCityElem.style.display = 'none';
                    }
                    this.elements.this_list.append(listCityElem);
                    city = terminal.city;
                }
                this.elements.this_list.append(listElem);
            }

            this.elements.this_list.querySelectorAll('li:not(.city)').forEach(el => el.addEventListener('click', () => {
                this.mark_element_list_selected(el);
            }));
        },

        clear_element_list: function() {
            while ( this.elements.this_list.firstChild ) {
                this.elements.this_list.removeChild(this.elements.this_list.lastChild);
            }
        },

        unmark_element_list_selected: function() {
            let selectedElem = this.elements.this_list.querySelector('li.selected');
            if ( selectedElem ) {
                selectedElem.classList.remove('selected');
            }
        },

        mark_element_list_selected: function( listElem ) {
            this.unmark_element_list_selected();
            
            const selectedTerminal = listElem.getAttribute('data-id');
            
            listElem.classList.add('selected');
            markSelectControlValue(this.elements.org_field, selectedTerminal);
            this.params.selected_terminal = selectedTerminal;
            this.set_selected();
            this.update_element_dropdown();
            this.toggle_dropdown(true);
        },

        toggle_dropdown: function( forceClose = false ) {
            if ( this.elements.this_container.classList.contains('open') || forceClose ) {
                this.show_element_inner_container(false);
                this.elements.this_container.classList.remove('open');
            } else {
                this.show_element_inner_container(true);
                this.elements.this_container.classList.add('open');
            }
        },

        show_element_all_options: function() {
            this.elements.this_list.querySelectorAll('li').forEach(el => {
                el.style.display = '';
                this.elements.this_show_more.style.display = 'none';
            });
        },

        hide_element_all_options: function() {
            this.elements.this_list.querySelectorAll('li').forEach(el => {
                el.style.display = 'none';
                this.elements.this_show_more.style.display = '';
            });
        },

        reset_terminals: function() {
            for ( let i = 0; i < this.terminals.length; i++ ) {
                this.terminals[i].distance = false;
            }
            this.terminals.sort(function(a, b) { //Sort by name
                return a.city.localeCompare(b.city) || b.name - a.name;
            });
        },

        geo_find_position: async function( address ) {
            if ( address == '' || address.length < 3 ) {
                this.reset_terminals();
                this.show_element_all_options();
                this.refresh_element_list();
                return false;
            }

            let url = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine=' + address + '&sourceCountry=' + this.params.country + '&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson';
            const fetchData = async () => {
                const data = await getJsonDataFromUrl(url);
                return data;
            };
            let location_result = await fetchData();

            if ( 'candidates' in location_result && location_result.candidates.length ) {
                let location = location_result.candidates[0];
                this.sort_list_by_distance(location.location.y, location.location.x);
                this.refresh_element_list();
                this.elements.this_show_more.style.display = '';
                if ( this.params.autoselect ) {
                    this.activate_autoselect();
                }
            }
        },

        geo_suggest: async function( address ) {
            if ( address == '' || address.length < 3 ) {
                this.reset_terminals();
                this.show_element_all_options();
                this.refresh_element_list();
                this.show_element_loader(false);
                if ( address.length ) {
                    this.elements.this_search_msg.innerHTML = this.translations.too_short;
                    this.show_element_search_msg(true);
                }
                return false;
            }

            let url = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/suggest?text=' + address + '&f=pjson&sourceCountry=' + this.params.country + '&maxSuggestions=1';
            const fetchData = async () => {
                const data = await getJsonDataFromUrl(url);
                return data;
            };
            let suggest_result = await fetchData();

            if ( 'suggestions' in suggest_result && suggest_result.suggestions.length ) {
                this.geo_find_position(suggest_result.suggestions[0].text);
            } else {
                this.elements.this_search_msg.innerHTML = this.translations.not_found;
                this.show_element_search_msg(true);
                this.hide_element_all_options();
            }
            this.show_element_loader(false);
        },

        sort_list_by_distance: function( y, x ) {
            let distance;
            for ( let i = 0; i < this.terminals.length; i++ ) {
                distance = this.calculate_distance(y, x, this.terminals[i].coords.lat, this.terminals[i].coords.lng);
                this.terminals[i].distance = distance.toFixed(2);
            }

            this.terminals.sort(( a, b ) => {
                let dist1 = a.distance;
                let dist2 = b.distance;
                if ( parseFloat(dist1) < parseFloat(dist2) ) {
                    return -1;
                }
                if ( parseFloat(dist1) > parseFloat(dist2) ) {
                    return 1;
                }
                return 0;
            });
        },

        calculate_distance: function( lat1, lon1, lat2, lon2 ) {
            let R = 6371;
            let dLat = this.to_radius(lat2 - lat1);
            let dLon = this.to_radius(lon2 - lon1);
            lat1 = this.to_radius(lat1);
            lat2 = this.to_radius(lat2);

            let a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.sin(dLon / 2) * Math.sin(dLon / 2) * Math.cos(lat1) * Math.cos(lat2);
            let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            let d = R * c;
            return d;
        },

        to_radius( value ) {
            return value * Math.PI / 180;
        },

        error: function( error_text ) {
            console.error('OMNIVA CUSTOM SELECT:', error_text);
        }
    };
};
