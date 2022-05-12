<?php
class OmnivaLt_Cronjob
{
  public static function init()
  {
    add_filter('cron_schedules', array(__CLASS__, 'add_weekly'));

    add_action('omnivalt_location_update', array(__CLASS__, 'generate_locations_file'));

    register_activation_hook(WP_PLUGIN_DIR . '/' . OMNIVALT_BASENAME, array(__CLASS__, 'activation'));
    register_deactivation_hook(WP_PLUGIN_DIR . '/' . OMNIVALT_BASENAME, array(__CLASS__, 'deactivation'));
  }

  public static function activation()
  {
    if ( ! wp_next_scheduled('omnivalt_location_update')) {
      wp_schedule_event(time(), 'daily', 'omnivalt_location_update');
    }
  }

  public static function deactivation()
  {
    wp_clear_scheduled_hook('omnivalt_location_update');
  }

  public static function add_weekly($schedules)
  {
    $schedules['daily'] = array(
      'interval' => 86400,
      'display' => __('Once daily', 'omnivalt'),
    );
    return $schedules;
  }

  public static function generate_locations_file()
  {
    self::log('Preparing locations update...', true, true);
    $location_params = OmnivaLt_Core::get_configs('locations');
    if ( empty($location_params['source_url']) ) {
      self::log('Empty source URL.', false);
      return;
    }
    
    $url = $location_params['source_url'];
    $fp = fopen(OMNIVALT_DIR . "locations_new.json", "w");
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_FILE, $fp);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($curl);
    curl_close($curl);
    fclose($fp);

    $new_data = file_get_contents(OMNIVALT_DIR . "locations_new.json");
    if ( json_decode($new_data) ) {
      rename(OMNIVALT_DIR . "locations_new.json", OMNIVALT_DIR . "locations.json");
      self::log('Locations updated.', false);
    } else {
      self::log('Failed.', false);
    }
  }

  public static function log($message, $show_date = true, $next_same_line = false)
  {
    $message = ($show_date) ? current_time('Y-m-d H:i:s') . ' ' . $message : $message;
    $message = ($next_same_line) ? $message . ' ' : $message . PHP_EOL;

    file_put_contents(OMNIVALT_DIR . 'var/logs/cronjob.log', $message, FILE_APPEND);
  }
}
