<?php
class OmnivaLt_Cronjob
{
  public static function init()
  {
    add_filter('cron_schedules', array(__CLASS__, 'add_weekly'));

    add_action('omnivalt_location_update', array(__CLASS__, 'do_daily_update'));
  }

  public static function add_weekly($schedules)
  {
    $schedules['daily'] = array(
      'interval' => 86400,
      'display' => __('Once daily', 'omnivalt'),
    );
    return $schedules;
  }

  public static function do_daily_update()
  {
    file_put_contents(OMNIVALT_DIR . 'logs/cronjob.log', current_time('Y-m-d H:i:s') . ' Preparing locations update...' . PHP_EOL, FILE_APPEND);
    $location_params = omnivalt_configs('locations');
    
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
    if (json_decode($new_data)) {
      rename(OMNIVALT_DIR . "locations_new.json", OMNIVALT_DIR . "locations.json");
      file_put_contents(OMNIVALT_DIR . 'logs/cronjob.log', current_time('Y-m-d H:i:s') . ' Locations updated.' . PHP_EOL, FILE_APPEND);
    }
  }
}
