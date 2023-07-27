<?php
class OmnivaLt_Debug
{
    public static $_debug_dir = OMNIVALT_DIR . 'var/debug/';
    public static $_log_dir = OMNIVALT_DIR . 'var/logs/';

    public static function check_debug_enabled()
    {
        $settings = get_option(OmnivaLt_Core::get_configs('plugin')['settings_key']);
        if ( isset($settings['debug_mode']) && $settings['debug_mode'] === 'yes' ) {
            return true;
        }

        return false;
    }

    public static function debug_request( $request )
    {
        if ( ! self::check_debug_enabled() ) {
            return '';
        }

        OmnivaLt_Core::add_required_directories();

        $file_name = 'request_' . current_time('Ymd_His_'.substr((string)microtime(), 2, 4)) . '.log';
        $file = fopen(self::$_debug_dir . $file_name, 'w');
        fwrite($file, print_r($request,true));
        fclose($file);

        return $request;
    }

    public static function debug_response( $response )
    {
        if ( ! self::check_debug_enabled() ) {
            return '';
        }

        OmnivaLt_Core::add_required_directories();

        $file_name = 'response_' . current_time('Ymd_His_'.substr((string)microtime(), 2, 4)) . '.log';
        $file = fopen(self::$_debug_dir . $file_name, 'w');
        fwrite($file, print_r($response,true));
        fclose($file);

        return $response;
    }

    public static function log( $type, $msg, $show_backtrace = false )
    {
        $available_types = array('error', 'notice', 'order', 'cart', 'product', 'custom');
        $message = '';
        
        if ( ! in_array($type, $available_types) ) {
            $message = 'Got wrong log type in ';
            $type = 'log_error';
            $show_backtrace = true;
            $msg = '';
        }

        if ( $show_backtrace ) {
            $backtrace = debug_backtrace(1, 2);
            $message .= $backtrace[0]['file'] . '::' . $backtrace[0]['line'] . ' - ' . $backtrace[1]['function'] . '()';
            if ( $msg !== '' ) {
                $message .= "\n";
            }
        }

        if ( is_object($msg) || is_array($msg) ) {
            $msg = print_r($msg, true);
        }

        self::save_log_msg($type, $message . $msg);
    }

    public static function log_error( $error_msg )
    {
        self::save_log_msg('error', $error_msg);
    }

    public static function get_all_files( $get_section = '' )
    {
        $debug_params = OmnivaLt_Core::get_configs('debug');
        self::delete_old_files($debug_params['delete_after']);
        $files = array_diff(scandir(self::$_debug_dir), array('.', '..'));
        $request_files = array();
        $response_files = array();

        foreach ( $files as $file ) {
            preg_match_all('/\d+/', $file, $matches);
            $file_data = array(
                'name' => $file,
                'day' => (isset($matches[0][0])) ? $matches[0][0] : '',
                'time' => (isset($matches[0][1])) ? $matches[0][1] : '',
            );
            if ( strpos($file, 'request') !== false ) {
                $request_files[] = $file_data;
            }
            if ( strpos($file, 'response') !== false ) {
                $response_files[] = $file_data;
            }
        }

        usort($request_files, function ($a, $b) {
            if ( $b['day'] === $a['day'] ) {
                return $b['time'] <=> $a['time'];
            }
            return $b['day'] <=> $a['day'];
        });
        usort($response_files, function ($a, $b) {
            if ( $b['day'] === $a['day'] ) {
                return $b['time'] <=> $a['time'];
            }
            return $b['day'] <=> $a['day'];
        });

        $output = array(
            'request' => $request_files,
            'response' => $response_files,
        );

        if ( ! empty($get_section) && isset($output[$get_section]) ) {
            return $output[$get_section];
        }

        return $output;
    }

    private static function delete_old_files( $older_than )
    {
        $files = array_diff(scandir(self::$_debug_dir), array('.', '..'));
        foreach ( $files as $file ) {
            if ( strpos($file, 'request') !== false || strpos($file, 'response') !== false ) {
                preg_match_all('/\d+/', $file, $matches);
                $file_data = array(
                    'name' => $file,
                    'day' => (isset($matches[0][0])) ? $matches[0][0] : '',
                    'time' => (isset($matches[0][1])) ? $matches[0][1] : '',
                );
                if ( empty($file_data['day']) || strtotime($file_data['day']) < strtotime('-' . $older_than . ' days') ) {
                    unlink(self::$_debug_dir . $file);
                }
            }
        }
    }

    private static function save_log_msg($type, $message)
    {
        $file_name = $type . '.log';
        error_log(self::build_log_text($message), 3, self::$_log_dir . $file_name);
    }

    private static function build_log_text( $message )
    {
        $log_pref = '[' . current_time("Y-m-d H:i:s") . ']: ';
        return $log_pref . $message . PHP_EOL;
    }
}
