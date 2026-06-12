<?php
/**
 * Universal logger for writing messages to monthly rotating log files.
 *
 * Log files are stored in "var/logs/" and named "{name}-YYYY-MM.log".
 * Old files (older than the configured number of months) are removed
 * automatically.
 *
 * Usage:
 *   $logger = new OmnivaLt_Logger('cronjob');
 *   $logger->write('Some message');
 *
 *   // Or statically:
 *   OmnivaLt_Logger::log_to('cronjob', 'Some message');
 */
class OmnivaLt_Logger
{
  /**
   * Directory where log files are stored.
   *
   * @var string
   */
  private $logs_dir;

  /**
   * Base name of the log file (without date and extension).
   *
   * @var string
   */
  private $name;

  /**
   * Number of months to keep log files before deleting them.
   *
   * @var int
   */
  private $keep_months;

  /**
   * @param string $name Base name of the log file (e.g. "cronjob").
   * @param int $keep_months Number of months to keep log files. 0 disables cleanup.
   */
  public function __construct($name, $keep_months = 12)
  {
    $this->name = $name;
    $this->keep_months = (int) $keep_months;
    $this->logs_dir = OMNIVALT_DIR . 'var/logs/';
  }

  /**
   * Writes a message to the current month's log file.
   *
   * @param string $message Message to log.
   * @param bool $show_date Whether to prefix the message with the current date and time.
   * @param bool $next_same_line Whether the next log entry should be on the same line.
   * @return void
   */
  public function write($message, $show_date = true, $next_same_line = false)
  {
    $message = ($show_date) ? current_time('Y-m-d H:i:s') . ': ' . $message : $message;
    $message = ($next_same_line) ? $message . ' ' : $message . PHP_EOL;

    OmnivaLt_Core::add_required_directories();
    file_put_contents($this->get_file_path(), $message, FILE_APPEND);

    $this->cleanup_old_logs();
  }

  /**
   * Returns the full path to a log file for the given month.
   *
   * @param string|null $yyyy_mm Month in "Y-m" format. Defaults to the current month.
   * @return string
   */
  public function get_file_path($yyyy_mm = null)
  {
    if ( $yyyy_mm === null ) {
      $yyyy_mm = current_time('Y-m');
    }
    return $this->logs_dir . $this->name . '-' . $yyyy_mm . '.log';
  }

  /**
   * Deletes log files (for this logger's name) older than $this->keep_months.
   * Runs at most once per request per logger name.
   *
   * @return void
   */
  public function cleanup_old_logs()
  {
    static $done = array();

    if ( $this->keep_months <= 0 ) {
      return;
    }
    if ( isset($done[$this->name]) ) {
      return;
    }
    $done[$this->name] = true;

    if ( ! is_dir($this->logs_dir) ) {
      return;
    }

    $cutoff = strtotime('-' . $this->keep_months . ' months', strtotime(current_time('Y-m-01')));

    $pattern = '/' . preg_quote($this->name, '/') . '-(\d{4})-(\d{2})\.log$/';
    foreach ( glob($this->logs_dir . $this->name . '-*.log') as $file ) {
      if ( ! preg_match($pattern, $file, $matches) ) {
        continue;
      }
      $file_time = strtotime($matches[1] . '-' . $matches[2] . '-01');
      if ( $file_time !== false && $file_time < $cutoff ) {
        @unlink($file);
      }
    }
  }

  /**
   * Convenience static method to write a single message without
   * keeping a logger instance.
   *
   * @param string $name Base name of the log file.
   * @param string $message Message to log.
   * @param bool $show_date Whether to prefix the message with the current date and time.
   * @param bool $next_same_line Whether the next log entry should be on the same line.
   * @param int $keep_months Number of months to keep log files.
   * @return void
   */
  public static function log_to($name, $message, $show_date = true, $next_same_line = false, $keep_months = 12)
  {
    $logger = new self($name, $keep_months);
    $logger->write($message, $show_date, $next_same_line);
  }
}
