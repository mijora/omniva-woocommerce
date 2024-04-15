import { txt } from './text';
import { getOmnivaData } from './omniva';

export const getTerminalsByCountry = (country, type) => {
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

        load_data: function (params) {
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
            }
            const modal_header = (this.params.terminals_type == 'post') ? txt.map.modal_title_post : txt.map.modal_title_terminal;
            const provider = (this.params.provider in txt.providers) ? txt.providers[this.params.provider] : txt.providers.omniva;
            this.translations = {
                modal_header: provider + " " + modal_header, //TODO: Prideti provider gavima
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
                console.error('OMNIVA MAP: Failed to get a container for the map');
                return;
            }
            this.lib = new TerminalMappingOmnivalt();
            
            this.lib.setImagesPath(this.params.icons_url);
            this.lib.setTranslation(this.translations);
            this.lib.dom.setContainerParent(this.elements.map_container);

            this.lib.setParseMapTooltip((location, leafletCoords) => {
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

        build_actions: function(thisMap) {
            this.lib.sub('tmjs-ready', function(data) {
                thisMap.lib.map.createIcon('omnivalt_icon', thisMap.params.icons_url + thisMap.params.map_icon);
                thisMap.lib.map.refreshMarkerIcons();
                
                let selected_location = data.map.getLocationById(thisMap.params.selected_terminal);
                if ( typeof(selected_location) != 'undefined' && selected_location != null ) {
                    thisMap.lib.dom.setActiveTerminal(selected_location);
                    thisMap.lib.publish('terminal-selected', selected_location);
                }
            });

            this.lib.sub("terminal-selected", function(data) {
                thisMap.elements.org_field.value = data.id;
                const event = new Event('change', { bubbles: true });
                thisMap.elements.org_field.dispatchEvent(event);
                thisMap.lib.dom.setActiveTerminal(data.id);
                thisMap.lib.publish("close-map-modal");
            });
        }
    };
};

export const removeMap = ( mapContainer ) => {
    while ( mapContainer.firstChild ) {
        mapContainer.removeChild(mapContainer.lastChild);
    }
};
