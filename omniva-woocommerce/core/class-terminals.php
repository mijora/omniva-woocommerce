<?php
class OmnivaLt_Terminals
{
  public static function add_terminal_to_session()
  {
    if (isset($_POST['terminal_id']) && is_numeric($_POST['terminal_id'])) {
      WC()->session->set('omnivalt_terminal_id', $_POST['terminal_id']);
    }
    wp_die();
  }

  public static function get_terminals_list( $country = "ALL", $get_list = 'terminal' ) {
    $terminals = self::read_terminals_file();
    $grouped_options = array();
    if ( is_array($terminals) ) {
      $type = 0;
      if ( $get_list === 'post' ) $type = 1;
      foreach ( $terminals as $terminal ) {
        if ( intval($terminal['TYPE']) !== $type ) {
          continue;
        }

        //if ($terminal['A0_NAME'] != $country && $country != "ALL") continue;
        if ( ! isset($grouped_options[$terminal['A0_NAME']]) ) $grouped_options[(string) $terminal['A0_NAME']] = array();
        if ( ! isset($grouped_options[$terminal['A0_NAME']][$terminal['A1_NAME']]) ) $grouped_options[(string) $terminal['A0_NAME']][(string) $terminal['A1_NAME']] = array();
        $grouped_options[(string) $terminal['A0_NAME']][(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal['NAME'];
      }
    }
    $grouped_options = self::sort_terminals_list($grouped_options);
    return ($country != "ALL" && isset($grouped_options[$country])) ? $grouped_options[$country] : $grouped_options;
  }

  public static function get_terminals_options( $selected = '', $country = "ALL", $get_list = 'terminal' )
  {
    $terminals = self::read_terminals_file();
    $parcel_terminals = '';
    
    $list_options = array(
      'list' => 'terminal',
      'type' => 0,
      'txt_select' => __('Select parcel terminal', 'omnivalt'),
      'txt_show_map' => __('Show parcel terminals map', 'omnivalt'),
    );
    if ( $get_list === 'post' ) {
      $list_options['list'] = 'post';
      $list_options['type'] = 1;
      $list_options['txt_select'] = __('Select post office', 'omnivalt');
      $list_options['txt_show_map'] = __('Show post offices map', 'omnivalt');
    }

    if ( is_array($terminals) ) {
      $grouped_options = array();
      foreach ( $terminals as $terminal ) {
        if ( intval($terminal['TYPE']) !== $list_options['type'] ) {
          continue;
        }

        if ( $terminal['A0_NAME'] != $country && $country != "ALL" ) continue;
        if ( ! isset($grouped_options[$terminal['A1_NAME']]) ) $grouped_options[(string) $terminal['A1_NAME']] = array();
        $grouped_options[(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal['NAME'];
      }
      $counter = 0;
      foreach ( $grouped_options as $city => $locs ) {
        $parcel_terminals .= '<optgroup data-id = "' . $counter . '" label = "' . $city . '">';
        
        foreach ( $locs as $key => $loc ) {
          $parcel_terminals .= '<option value = "' . $key . '" ' . ($key == $selected ? 'selected' : '') . '>' . $loc . '</option>';
        }

        $parcel_terminals .= '</optgroup>';
        $counter++;
      }
    }

    $nonce = wp_create_nonce("omniva_terminals_json_nonce");
    $omniva_settings = get_option(OmnivaLt_Core::get_configs('plugin')['settings_key']);
    $parcel_terminals = '<option value = "">' . $list_options['txt_select'] . '</option>' . $parcel_terminals;
    $set_autoselect = (isset($omniva_settings['auto_select'])) ? $omniva_settings['auto_select'] : 'yes';
    
    $script = "<script style='display:none;'>
      var omnivaTerminals = JSON.stringify(" . json_encode(self::get_terminals_for_map('', $country, $get_list)) . ");
    </script>";
    $script .= "<script style='display:none;'>
      var omniva_current_country = '" . $country . "';
      var omnivaSettings = {
        auto_select:'" . $set_autoselect . "'
      };
      var omniva_type = '" . $get_list . "';
      var omniva_current_terminal = '" . $selected . "';
      jQuery('document').ready(function($){        
        $('.omnivalt_terminal').omniva();
        $(document).trigger('omnivalt.checkpostcode');
      });
      </script>";

    $button = '';
    if ( ! isset($omniva_settings['show_map']) || isset($omniva_settings['show_map']) && $omniva_settings['show_map'] == 'yes' ) {
      $button = '<button type="button" id="show-omniva-map" class="btn btn-basic btn-sm omniva-btn" style = "display: none;">' . __('Show in map', 'omnivalt') . '<img src = "' . OMNIVALT_URL . 'assets/img/sasi.png" title = "' . $list_options['txt_show_map'] . '"/></button>';
    }
    return '<div class="terminal-container"><select class="omnivalt_terminal" name="omnivalt_terminal">' . $parcel_terminals . '</select>
      ' . $button . ' </div>' . $script;
  }

  public static function get_terminals_for_map( $selected = '', $country = "LT", $get_list = 'terminal' )
  {
    $shipping_params = OmnivaLt_Core::get_configs('shipping_params');
    $terminals = self::read_terminals_file();
    $parcel_terminals = '';
    $terminalsList = array();
    $comment_lang = (!empty($shipping_params[strtoupper($country)]['comment_lang'])) ? $shipping_params[strtoupper($country)]['comment_lang'] : 'lit';
    if ( is_array($terminals) ) {
      $type = 0;
      if ( $get_list === 'post' ) $type = 1;
      foreach ( $terminals as $terminal ) {
        if ( $terminal['A0_NAME'] != $country && isset($shipping_params[$country]) || intval($terminal['TYPE']) !== $type ) {
          continue;
        }

        if ( ! isset($grouped_options[$terminal['A1_NAME']]) ) {
          $grouped_options[(string) $terminal['A1_NAME']] = array();
        }
        $grouped_options[(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal['NAME'];

        $terminalsList[] = [$terminal['NAME'], $terminal['Y_COORDINATE'], $terminal['X_COORDINATE'], $terminal['ZIP'], $terminal['A1_NAME'], $terminal['A2_NAME'] !== 'NULL' ? $terminal['A2_NAME'] : '', str_ireplace('"', '\"', $terminal['comment_' . $comment_lang])];
      }
    }
    return $terminalsList;
  }

  public static function get_terminal_name( $terminal_code, $get_with_country = false )
  {
    $terminals = self::read_terminals_file();
    $parcel_terminals = '';
    if ( is_array($terminals) ) {
      foreach ( $terminals as $terminal ) {
        if ( (string) $terminal['ZIP'] == $terminal_code ) {
          $terminal_name = (string) $terminal['NAME'] . ', ' . $terminal['A1_NAME'];
          if ( $get_with_country ) {
            $terminal_name .= ', ' . $terminal['A0_NAME'];
          }
          return $terminal_name;
        }
      }
    }
    return false;
  }

  public static function get_terminal_address( $terminal_id, $get_with_country = false )
  {
    $terminal_name = self::get_terminal_name($terminal_id, $get_with_country);
    if ( ! $terminal_name ) {
      $terminal_name  = __('Location not found!!!', 'omnivalt');
    }
    
    return $terminal_name;
  }

  public static function terminals_modal()
  {
    return '
    <div id="omnivaLtModal" class="modal">
        <div class="omniva-modal-content">
            <div class="omniva-modal-header">
            <span class="close" id="terminalsModal">&times;</span>
            <h5 id="omnivaLt_modal_title" style="display: inline">' . __('Omniva parcel terminals', 'omnivalt') . '</h5>
            </div>
            <div class="omniva-modal-body" style="/*overflow: hidden;*/">
                <div id = "omnivaMapContainer"></div>
                <div class="omniva-search-bar" >
                    <h4 id="omnivaLt_modal_search" style="margin-top: 0px;">' . __('Parcel terminals addresses', 'omnivalt') . '</h4>
                    <div id="omniva-search">
                    <form>
                    <input type = "text" placeholder = "' . __('Enter postcode', 'omnivalt') . '"/>
                    <button type = "submit" id="map-search-button"></button>
                    </form>                    
                    <div class="omniva-autocomplete scrollbar" style = "display:none;"><ul></ul></div>
                    </div>
                    <div class = "omniva-back-to-list" style = "display:none;">' . __('Back', 'omnivalt') . '</div>
                    <div class="found_terminals scrollbar" id="style-8">
                      <ul>
                      
                      </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>';
  }

  public static function check_terminals_json_file()
  {
    if ( ! file_exists(OMNIVALT_DIR . 'locations.json') ) {
      OmnivaLt_Cronjob::generate_locations_file();
    }
  }

  public static function get_terminals_json()
  {
    if ( ! wp_verify_nonce($_REQUEST['nonce'], "omniva_terminals_json_nonce") ) {
      exit("Not allowed");
    }

    $terminals_json = self::generate_terminals_json($_REQUEST['q'], $_REQUEST['country']);
    echo json_encode($terminals_json);
    
    die();
  }

  private static function read_terminals_file()
  {
    $terminals_file = fopen(OMNIVALT_DIR . 'locations.json', "r");
    $terminals = fread($terminals_file, filesize(OMNIVALT_DIR . 'locations.json') + 10);
    fclose($terminals_file);

    return json_decode($terminals, true);
  }

  private static function generate_terminals_json( $term = "", $country = "ALL", $get_list = 'terminal' )
  {
    $c_p = false;
    if ( strlen($term) >= 4 && strlen($term) ) {
      $c_p = self::search_postcode($term, $country);
    }
    $terminals = self::read_terminals_file();
    $parcel_terminals = array();
    if ( is_array($terminals) ) {
      $grouped_options = array();
      $type = 0;
      if ( $get_list === 'post' ) $type = 1;
      foreach ( $terminals as $terminal ) {
        if ( intval($terminal['TYPE']) !== $type ) {
          continue;
        }
        if ( $terminal['A0_NAME'] != $country && $country != "ALL" ) continue;

        if ( ! isset($grouped_options[$terminal['A1_NAME']]) ) $grouped_options[(string) $terminal['A1_NAME']] = array();
        $grouped_options[(string) $terminal['A1_NAME']][(string) $terminal['ZIP']] = $terminal;
      }
      $counter = 0;
      foreach ( $grouped_options as $city => $locs ) {
        $group = array("text" =>  $city, "distance" => 0, "children" => array());
        $group_distance = false;
        foreach ( $locs as $key => $loc ) {
          if ( $term != "" && $c_p == false && stripos($loc['NAME'], $term) !== false ) {
            $group['children'][] = array("id" => $key, "text" => $loc['NAME'], "distance" => 0);
          } elseif ( is_array($c_p) ) {
            $distance = self::calc_distance($c_p[0], $c_p[1], $loc['Y_COORDINATE'], $loc['X_COORDINATE']);
            $group['children'][] = array("id" => $key, "text" => $loc['NAME'], "distance" => $distance);
            if ( $group_distance == false || $group_distance > $distance ) {
              $group_distance = $distance;
            }
          } elseif ( $term == "" ) {
            $group['children'][] = array("id" => $key, "text" => $loc['NAME'], "distance" => 0);
          }
        }
        $group['distance'] = $group_distance;
        if ( count($group['children']) && $c_p == false ) {
          $parcel_terminals[] = $group;
        } elseif ( count($group['children']) ) {
          $parcel_terminals = array_merge($parcel_terminals, $group['children']);
        }
        $counter++;
      }
    }
    if ( $c_p != false ) {
      usort($parcel_terminals, function ($a, $b) {
        return $b['distance'] > $a['distance'] ? -1 : 1;
      });
      return array_slice($parcel_terminals, 0, 8);
    }
    return $parcel_terminals;
  }

  private static function search_postcode( $postcode, $country )
  {
    if ( $postcode == "" ) return false;
    $postcode = urlencode($postcode);
    $data = file_get_contents("http://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine=" . $postcode . "," . $country . "&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson");
    if ( $data ) {
      $data = json_decode($data);
    } else {
      return false;
    }
    if ( isset($data->candidates) && count($data->candidates) ) {
      if ($data->candidates[0]->score > 90) {
        return array($data->candidates[0]->location->y, $data->candidates[0]->location->x);
      }
    }
    return false;
  }

  private static function calc_distance( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000 )
  {
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
      cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    
    return round($angle * $earthRadius / 1000, 2);
  }

  private static function sort_terminals_list( $list ) 
  {
    $sorted_list = array();
    foreach ( $list as $key => $elem ) {
      ksort($elem);
      $sorted_list[$key] = $elem;
    }

    return $sorted_list;
  }
}
