<?php
class OmnivaLt_Cronjob
{
  const GROUP = 'omnivalt';
  const LEGACY_CLEANUP_OPTION = 'omnivalt_cronjob_legacy_cleanup_done';
  const LOG_NAME = 'cronjob';
  const LOG_KEEP_MONTHS = 12;
  const SCHEDULES = array(
    'daily' => 86400,
    'monthly' => 2592000,
  );
  const HOOKS = array(
    'omnivalt_location_update' => array(
      'callback' => 'generate_locations_file',
      'interval' => 'daily',
    ),
    'omnivalt_send_statistic' => array(
      'callback' => 'send_statistic_data',
      'interval' => 'daily',
    ),
  );

  public static function init()
  {
    add_filter('cron_schedules', __CLASS__ . '::add_frequency');

    foreach ( self::HOOKS as $hook => $config ) {
      add_action($hook, __CLASS__ . '::' . $config['callback']);
    }

    add_action('action_scheduler_failed_execution', __CLASS__ . '::log_failed_execution', 10, 3);
    add_action('action_scheduler_failed_action', __CLASS__ . '::log_failed_action', 10, 2);
    add_action('action_scheduler_unexpected_shutdown', __CLASS__ . '::log_unexpected_shutdown', 10, 2);

    self::cleanup_legacy_actions();
  }

  public static function register_activation_hooks()
  {
    register_activation_hook(WP_PLUGIN_DIR . '/' . OMNIVALT_BASENAME, __CLASS__ . '::activation');
    register_deactivation_hook(WP_PLUGIN_DIR . '/' . OMNIVALT_BASENAME, __CLASS__ . '::deactivation');
  }

  public static function activation()
  {
    foreach ( self::HOOKS as $hook => $config ) {
      if ( ! as_next_scheduled_action($hook, null, self::GROUP) ) {
        $interval = self::get_interval_time($config['interval']);
        as_schedule_recurring_action(
          current_time('timestamp') + $interval,
          $interval,
          $hook,
          array(),
          self::GROUP
        );
        self::log(sprintf('Scheduled action "%s" (interval: %s, group: %s).', $hook, $config['interval'], self::GROUP));
      }
    }
  }

  public static function deactivation()
  {
    foreach ( self::HOOKS as $hook => $config ) {
      as_unschedule_action($hook, array(), self::GROUP);
      self::log(sprintf('Unscheduled action "%s" (group: %s).', $hook, self::GROUP));
    }
  }

  /**
   * Removes any scheduled actions for the plugin's hooks that are not
   * assigned to the plugin's group. This handles legacy actions that
   * were registered before the group was introduced.
   *
   * Runs only once per install thanks to a one-off option flag.
   */
  public static function cleanup_legacy_actions()
  {
    if ( get_option(self::LEGACY_CLEANUP_OPTION) ) {
      return;
    }

    if ( ! function_exists('as_unschedule_all_actions') ) {
      return;
    }

    self::log('Cleaning up legacy actions (without group)...');
    foreach ( self::HOOKS as $hook => $config ) {
      as_unschedule_all_actions($hook, array(), '');
    }

    update_option(self::LEGACY_CLEANUP_OPTION, OMNIVALT_VERSION, false);

    self::activation();
  }

  public static function log_failed_execution($action_id, $exception, $context = '')
  {
    $hook = self::get_action_hook($action_id);
    if ( ! self::is_plugin_hook($hook) ) {
      return;
    }
    $message = sprintf('Action "%s" (ID: %s) failed: %s', $hook, $action_id, $exception->getMessage());
    if ( ! empty($context) ) {
      $message .= ' [context: ' . $context . ']';
    }
    self::log($message);
  }

  public static function log_failed_action($action_id, $timeout)
  {
    $hook = self::get_action_hook($action_id);
    if ( ! self::is_plugin_hook($hook) ) {
      return;
    }
    self::log(sprintf('Action "%s" (ID: %s) failed - exceeded timeout of %ss.', $hook, $action_id, $timeout));
  }

  public static function log_unexpected_shutdown($action_id, $error)
  {
    $hook = self::get_action_hook($action_id);
    if ( ! self::is_plugin_hook($hook) ) {
      return;
    }
    $error_message = is_array($error) && isset($error['message']) ? $error['message'] : 'Unknown fatal error';
    self::log(sprintf('Action "%s" (ID: %s) ended with unexpected shutdown: %s', $hook, $action_id, $error_message));
  }

  private static function get_action_hook($action_id)
  {
    if ( ! function_exists('ActionScheduler') ) {
      return '';
    }
    try {
      $store = ActionScheduler::store();
      $action = $store->fetch_action($action_id);
      return method_exists($action, 'get_hook') ? $action->get_hook() : '';
    } catch ( \Exception $e ) {
      return '';
    }
  }

  private static function is_plugin_hook($hook)
  {
    return ($hook && isset(self::HOOKS[$hook]));
  }

  public static function add_frequency($schedules)
  {
    if ( ! isset($schedules['daily']) ) {
      $schedules['daily'] = array(
        'interval' => self::SCHEDULES['daily'],
        'display' => __('Once daily', 'omnivalt'),
      );
    }
    if ( ! isset($schedules['monthly']) ) {
      $schedules['monthly'] = array(
        'interval' => self::SCHEDULES['monthly'],
        'display' => __('Once monthly', 'omnivalt'),
      );
    }
    return $schedules;
  }

  public static function get_interval_time( $interval_key )
  {
    if ( isset(self::SCHEDULES[$interval_key]) ) {
      return self::SCHEDULES[$interval_key];
    }

    $all_intervals = apply_filters('cron_schedules', array());
    if ( isset($all_intervals[$interval_key]) ) {
      return $all_intervals[$interval_key]['interval'];
    }

    return self::SCHEDULES['monthly'];
  }

  public static function generate_locations_file()
  {
    self::log('Preparing locations update...', true, true);
    $location_params = OmnivaLt_Core::get_configs('locations');
    if ( empty($location_params['source_url']) ) {
      self::log('Empty source URL.', false);
      return;
    }

    OmnivaLt_Core::add_required_directories();
    
    $url = $location_params['source_url'];
    
    $fp = fopen(OmnivaLt_Terminals::$_terminals_dir . "locations_new.json", "w");

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

    $new_data = file_get_contents(OmnivaLt_Terminals::$_terminals_dir . "locations_new.json");
    if ( json_decode($new_data) ) {
      rename(OmnivaLt_Terminals::$_terminals_dir . "locations_new.json", OmnivaLt_Terminals::$_terminals_dir . "locations.json");
      self::log('Locations updated.', false);
    } else {
      self::log('Failed.', false);
    }
  }

  public static function send_statistic_data()
  {
    $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
    $test_mode = (OmnivaLt_Debug::is_development_mode_enabled()) ? true : false;

    $last_track_date = get_option($meta_keys['last_track_date'], current_time('Y-m-d H:i:s'));
    $date_minus_month = date('Y-m-d', strtotime('-1 month', strtotime(current_time('Y-m-d'))));
    if ( current_time('j') == 2 || $last_track_date < $date_minus_month || $test_mode ) {
      self::log('Sending statistics to Omniva...', true, true);
      $api = new OmnivaLt_Api();
      $result = $api->send_statistics();
      if ( $result['status'] ) {
        self::log('Data sent successfully.', false);
      } else {
        self::log('Failed.', false);
      }
      update_option($meta_keys['last_track_date'], current_time('Y-m-d H:i:s'));
    }
  }

  public static function log($message, $show_date = true, $next_same_line = false)
  {
    OmnivaLt_Logger::log_to(self::LOG_NAME, $message, $show_date, $next_same_line, self::LOG_KEEP_MONTHS);
  }
}
