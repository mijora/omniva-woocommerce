<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Updater
{
  private static $using_last_known_update = false;

  public static function get_latest_update()
  {
    self::$using_last_known_update = false;
    $update_params = self::get_update_params();

    if ( empty($update_params['check_url']) ) {
      return false;
    }

    static $force_check_processed = false;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- force-check is a read-only WordPress core flag; the capability check authorizes cache refresh.
    if ( ! $force_check_processed && is_admin() && current_user_can('update_plugins') && isset($_GET['force-check']) ) {
      delete_site_transient('omnivalt_latest_update');
      $force_check_processed = true;
    }

    $cached_update = get_site_transient('omnivalt_latest_update');
    if ( is_array($cached_update) ) {
      if ( ! empty($cached_update['error']) ) {
        return ! empty($cached_update['retryable']) ? self::get_last_known_update() : false;
      }
      if ( ! empty($cached_update['version']) && array_key_exists('tag', $cached_update) && array_key_exists('changelog', $cached_update) ) {
			$cached_package = isset($cached_update['package']) && is_string($cached_update['package']) ? $cached_update['package'] : '';
			if ( array_key_exists('package', $cached_update) && '' !== trim($cached_package) && false === strpos($cached_package, '/releases/latest/download/') ) {
				return $cached_update;
			}

			delete_site_transient('omnivalt_latest_update');
      }
    }

    $response = wp_remote_get($update_params['check_url'], array(
      'timeout' => 5,
      'headers' => array(
        'Accept' => 'application/vnd.github+json',
        'User-Agent' => 'Omniva-WooCommerce/' . OMNIVALT_VERSION,
      ),
    ));

    if ( is_wp_error($response) ) {
      set_site_transient('omnivalt_latest_update', array('error' => true, 'retryable' => true), HOUR_IN_SECONDS);
      return self::get_last_known_update();
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    if ( 200 !== $status ) {
      $retryable = 0 === $status || 403 === $status || 408 === $status || 429 === $status || $status >= 500;
      set_site_transient('omnivalt_latest_update', array('error' => true, 'retryable' => $retryable), HOUR_IN_SECONDS);
      if ( ! $retryable ) {
        delete_site_transient('omnivalt_last_known_update');
      }
      return $retryable ? self::get_last_known_update() : false;
    }

    $response_data = json_decode(wp_remote_retrieve_body($response));
    if ( ! is_object($response_data) || empty($response_data->tag_name) ) {
      set_site_transient('omnivalt_latest_update', array('error' => true, 'retryable' => true), HOUR_IN_SECONDS);
      return self::get_last_known_update();
    }

    $release_tag = (string) $response_data->tag_name;
    $release_notes = ( isset($response_data->body) && is_string($response_data->body) ) ? $response_data->body : '';

    $asset_name = isset($update_params['asset_name']) && is_string($update_params['asset_name']) ? trim($update_params['asset_name']) : '';
    $assets = isset($response_data->assets) ? $response_data->assets : array();
    $package_url = self::resolve_package_url($assets, $asset_name);
    if ( '' === $package_url ) {
      set_site_transient('omnivalt_latest_update', array('error' => true), HOUR_IN_SECONDS);
      delete_site_transient('omnivalt_last_known_update');
      return false;
    }

		$release_version = preg_replace('/^v/i', '', $release_tag);
		if ( ! is_string($release_version) ) {
			$release_version = $release_tag;
		}

		$latest_update = array(
      'tag' => $release_tag,
		'version' => $release_version,
      'url' => ( ! empty($response_data->html_url) ) ? esc_url_raw($response_data->html_url) : '#',
      'package' => $package_url,
      'last_updated' => ( ! empty($response_data->published_at) ) ? sanitize_text_field($response_data->published_at) : '',
      'changelog' => self::format_release_notes($release_notes),
    );

    set_site_transient('omnivalt_latest_update', $latest_update, 12 * HOUR_IN_SECONDS);
    set_site_transient('omnivalt_last_known_update', array('checked_at' => time(), 'update' => $latest_update), 48 * HOUR_IN_SECONDS);

    return $latest_update;
  }

  /**
   * Use only a recent, previously verified release when GitHub is temporarily unavailable.
   * A missing installable asset is not a temporary transport failure.
   */
  private static function get_last_known_update()
  {
    $cached = get_site_transient('omnivalt_last_known_update');
    $params = self::get_update_params();
    $asset_name = isset($params['asset_name']) && is_string($params['asset_name']) ? trim($params['asset_name']) : '';

    if ( ! is_array($cached) || ! isset($cached['checked_at'], $cached['update']) || ! is_int($cached['checked_at']) || $cached['checked_at'] > time() || time() - $cached['checked_at'] >= 48 * HOUR_IN_SECONDS ) {
      return false;
    }

    $update = $cached['update'];
    if ( ! is_array($update) || ! isset($update['tag'], $update['version'], $update['url'], $update['package'], $update['changelog']) || '' === $asset_name ) {
      return false;
    }

    if ( ! is_string($update['tag']) || ! is_string($update['version']) || '' === trim($update['version']) || ! is_string($update['url']) || ! is_string($update['package']) || '' === trim($update['package']) || ! is_string($update['changelog']) ) {
      return false;
    }

    $package_path = wp_parse_url($update['package'], PHP_URL_PATH);
    if ( 'https' !== wp_parse_url($update['package'], PHP_URL_SCHEME) || 'github.com' !== wp_parse_url($update['package'], PHP_URL_HOST) || ! is_string($package_path) || $asset_name !== basename($package_path) ) {
      return false;
    }

    self::$using_last_known_update = true;
    return $update;
  }

  public static function check_update( $current_version = '' )
  {
    $update_info = self::get_latest_update();

    if ( empty($update_info['version']) ) {
      return false;
    }

    if ( empty($current_version) ) {
      $current_version = defined('OMNIVALT_VERSION') ? OMNIVALT_VERSION : '';
    }

    if ( empty($current_version) && function_exists('get_file_data') ) {
      $plugin_data = get_file_data(OmnivaLt_Core::$main_file_path, array('Version' => 'Version'), '');
      $current_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '';
    }

    return ( version_compare($current_version, $update_info['version'], '<') ) ? $update_info : false;
  }

  public static function update_plugins( $transient )
  {
    $plugin_basename = self::get_plugin_basename();

		if ( '' === $plugin_basename || ! is_object($transient) ) {
      return $transient;
    }

    $current_version = '';
    if ( isset($transient->checked) && is_array($transient->checked) && isset($transient->checked[$plugin_basename]) && is_scalar($transient->checked[$plugin_basename]) ) {
      $current_version = (string) $transient->checked[$plugin_basename];
    }
    if ( '' === $current_version && defined('OMNIVALT_VERSION') ) {
      $current_version = OMNIVALT_VERSION;
    }

		$response = ( isset($transient->response) && is_array($transient->response) ) ? $transient->response : array();
		$no_update = ( isset($transient->no_update) && is_array($transient->no_update) ) ? $transient->no_update : array();
		$latest_update = self::get_latest_update();
		if ( empty($latest_update) || ! isset($latest_update['version']) || ! is_string($latest_update['version']) || '' === trim($latest_update['version']) ) {
			unset($response[$plugin_basename]);
			$no_update[$plugin_basename] = self::get_no_update_plugin_data($plugin_basename, $current_version);
			$transient_data = get_object_vars($transient);
			$transient_data['response'] = $response;
			$transient_data['no_update'] = $no_update;
			return (object) $transient_data;
		}

		$has_update = version_compare($latest_update['version'], $current_version, '>');
		$has_package = isset($latest_update['package']) && is_string($latest_update['package']) && '' !== trim($latest_update['package']);
		$has_custom_changes = ! empty(self::get_custom_changes());

		if ( $has_update && ! $has_package && ! $has_custom_changes ) {
			unset($response[$plugin_basename]);
			$no_update[$plugin_basename] = self::get_no_update_plugin_data($plugin_basename, $current_version);
			$transient_data = get_object_vars($transient);
			$transient_data['response'] = $response;
			$transient_data['no_update'] = $no_update;
			return (object) $transient_data;
		}

		if ( $has_update ) {
			$update_info = self::enrich_update_info_with_github_metadata($latest_update);
			$plugin_update = array(
				'id' => $plugin_basename,
				'slug' => dirname($plugin_basename),
				'plugin' => $plugin_basename,
				'new_version' => $update_info['version'],
				'url' => $update_info['url'],
				'package' => $update_info['package'],
			);

			foreach ( array('requires', 'tested', 'requires_php') as $metadata_key ) {
				if ( ! empty($update_info[$metadata_key]) ) {
					$plugin_update[$metadata_key] = $update_info[$metadata_key];
				}
			}

			if ( $has_custom_changes ) {
				$plugin_update['package'] = '';
			}

      unset($no_update[$plugin_basename]);
      $response[$plugin_basename] = (object) $plugin_update;
    } else {
      unset($response[$plugin_basename]);
      $no_update[$plugin_basename] = self::get_no_update_plugin_data($plugin_basename, $current_version, $latest_update);
    }

    $transient_data = get_object_vars($transient);
    $transient_data['response'] = $response;
    $transient_data['no_update'] = $no_update;

    return (object) $transient_data;
  }

  private static function get_no_update_plugin_data( $plugin_basename, $current_version, $latest_update = array() )
  {
    $update_url = is_array($latest_update) && isset($latest_update['url']) && is_string($latest_update['url']) ? $latest_update['url'] : '';

    return (object) array(
      'id' => $plugin_basename,
      'slug' => dirname($plugin_basename),
      'plugin' => $plugin_basename,
      'new_version' => $current_version,
      'url' => $update_url,
      'package' => '',
    );
  }

  public static function disable_auto_update( $update, $item )
  {
		if ( empty(self::get_custom_changes()) || ( ! is_object($item) && ! is_array($item) ) ) {
      return $update;
    }

    $plugin_basename = self::get_plugin_basename();
		$item_plugin = is_object($item) && isset($item->plugin) && is_string($item->plugin) ? $item->plugin : '';
		$item_id = is_object($item) && isset($item->id) && is_string($item->id) ? $item->id : '';
		if ( is_array($item) ) {
			$item_plugin = isset($item['plugin']) && is_string($item['plugin']) ? $item['plugin'] : '';
			$item_id     = isset($item['id']) && is_string($item['id']) ? $item['id'] : '';
		}

    if ( $plugin_basename !== $item_plugin && $plugin_basename !== $item_id ) {
      return $update;
    }

    return false;
  }

  public static function clear_update_cache( $upgrader, $hook_extra )
  {
    if ( ! is_array($hook_extra) || empty($hook_extra['type']) || 'plugin' !== $hook_extra['type'] ) {
      return;
    }

    $plugin_basename = self::get_plugin_basename();
    $plugins = array();
    if ( isset($hook_extra['plugin']) && is_string($hook_extra['plugin']) ) {
      $plugins[] = $hook_extra['plugin'];
    }
    if ( isset($hook_extra['plugins']) && is_array($hook_extra['plugins']) ) {
      $plugins = array_merge($plugins, $hook_extra['plugins']);
    }

    if ( in_array($plugin_basename, $plugins, true) ) {
      delete_site_transient('omnivalt_latest_update');
      delete_site_transient('omnivalt_last_known_update');
    }
  }

  public static function plugin_information( $result, $action, $args )
  {
    $plugin_basename = self::get_plugin_basename();
    $plugin_slug = dirname($plugin_basename);

    if ( '' === $plugin_basename || 'plugin_information' !== $action || ! is_object($args) || empty($args->slug) || $plugin_slug !== $args->slug ) {
      return $result;
    }

    $update_info = self::get_latest_update();
    if ( ! $update_info ) {
      return $result;
    }

    $update_info = self::enrich_update_info_with_github_metadata($update_info);
    $requirements_section = '';

    if ( ! empty($update_info['wc_requires']) ) {
      $requirements_section .= '<p><strong>' . esc_html__('WC requires at least:', 'omnivalt') . '</strong> ' . esc_html($update_info['wc_requires']) . '</p>';
    }
    if ( ! empty($update_info['wc_tested']) ) {
      $requirements_section .= '<p><strong>' . esc_html__('WC tested up to:', 'omnivalt') . '</strong> ' . esc_html($update_info['wc_tested']) . '</p>';

      $wc_tested_version = is_string($update_info['wc_tested']) ? trim($update_info['wc_tested']) : '';
      $wc_current_version = defined('WC_VERSION') ? WC_VERSION : '';
      if ( '' !== $wc_tested_version && '' !== $wc_current_version && version_compare($wc_current_version, $wc_tested_version, '>') ) {
        $requirements_section .= '<div class="notice notice-warning notice-alt"><p><strong>' . esc_html__('Warning:', 'omnivalt') . '</strong> ' . esc_html__('This plugin has not been tested with your current version of WooCommerce.', 'omnivalt') . '</p></div>';
      }
    }

    $sections = array(
      'description' => __('Official Omniva shipping plugin for WooCommerce', 'omnivalt'),
      'changelog' => ! empty($update_info['changelog']) ? $update_info['changelog'] : __('See the release notes on GitHub.', 'omnivalt'),
    );
    if ( '' !== $requirements_section ) {
      $sections['requirements'] = $requirements_section;
    }

    $plugin_information = array(
      'name' => 'Omniva shipping',
      'slug' => $plugin_slug,
      'version' => $update_info['version'],
      'author' => 'Omniva',
      'homepage' => 'https://www.omniva.lt/en/business/integrations-for-e-shops',
      'download_link' => $update_info['package'],
      'last_updated' => $update_info['last_updated'],
      'sections' => $sections,
    );

    foreach ( array('requires', 'tested', 'requires_php') as $metadata_key ) {
      if ( ! empty($update_info[$metadata_key]) ) {
        $plugin_information[$metadata_key] = $update_info[$metadata_key];
      }
    }

    return (object) $plugin_information;
  }

  public static function update_message( $plugin_data, $response )
  {
    if ( ! is_array($plugin_data) || ! is_object($response) ) {
      return;
    }

    $update_params = self::get_update_params();
    if ( ! empty($update_params['download_url']) ) {
      echo '<br/><br/>' . sprintf(
        esc_html__('Or you can manually download the plugin %s.', 'omnivalt'),
        '<a href="' . esc_url($update_params['download_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('here', 'omnivalt') . '</a>'
      );
    }

    $custom_changes = self::get_custom_changes();
    if ( ! empty($custom_changes) ) {
      echo '<br/><br/><strong style="color:red;">' . esc_html__('We do not recommend update the plugin, because your plugin have changes that is not included in the update', 'omnivalt') . ':</strong>';
      foreach ( $custom_changes as $change ) {
        if ( is_scalar($change) && '' !== (string) $change ) {
          echo '<br/>&middot; ' . esc_html($change);
        }
      }
    }
  }

  private static function enrich_update_info_with_github_metadata( $update_info )
  {
    if ( ! is_array($update_info) || isset($update_info['requires'], $update_info['tested'], $update_info['requires_php'], $update_info['wc_requires'], $update_info['wc_tested']) ) {
      return $update_info;
    }

    $update_params = self::get_update_params();
    $github_metadata = self::get_github_plugin_metadata(
      isset($update_info['tag']) ? $update_info['tag'] : '',
      isset($update_params['check_url']) ? $update_params['check_url'] : ''
    );
    $update_info = array_merge($update_info, $github_metadata);
    // Enriching a fallback must not turn an API error into a fresh 12-hour release check.
    if ( ! self::$using_last_known_update ) {
      set_site_transient('omnivalt_latest_update', $update_info, 12 * HOUR_IN_SECONDS);
    }

    $last_known = get_site_transient('omnivalt_last_known_update');
    if ( is_array($last_known) && isset($last_known['checked_at'], $last_known['update']) && is_int($last_known['checked_at']) && $last_known['checked_at'] <= time() && is_array($last_known['update']) && isset($last_known['update']['tag'], $last_known['update']['package']) && $last_known['update']['tag'] === $update_info['tag'] && $last_known['update']['package'] === $update_info['package'] ) {
      $remaining = 48 * HOUR_IN_SECONDS - ( time() - $last_known['checked_at'] );
      if ( $remaining > 0 ) {
        $last_known['update'] = $update_info;
        set_site_transient('omnivalt_last_known_update', $last_known, $remaining);
      }
    }

    return $update_info;
  }

  private static function get_github_plugin_metadata( $tag_name, $check_url )
  {
    $metadata = array(
      'requires' => '',
      'tested' => '',
      'requires_php' => '',
      'wc_requires' => '',
      'wc_tested' => '',
    );

    if ( ! is_string($tag_name) || '' === $tag_name || ! is_string($check_url) || '' === $check_url ) {
      return $metadata;
    }

    $check_url_parts = wp_parse_url($check_url);
    if ( ! is_array($check_url_parts) || empty($check_url_parts['host']) || 'api.github.com' !== strtolower($check_url_parts['host']) || empty($check_url_parts['path']) ) {
      return $metadata;
    }

    if ( ! preg_match('#^/repos/([^/]+/[^/]+)/releases/latest$#', $check_url_parts['path'], $repository_match) ) {
      return $metadata;
    }

    $metadata_url = 'https://raw.githubusercontent.com/' . $repository_match[1] . '/' . rawurlencode($tag_name) . '/omniva-woocommerce/' . basename(self::get_plugin_basename());
    $response = wp_remote_get($metadata_url, array(
      'timeout' => 5,
      'headers' => array(
        'Accept' => 'text/plain',
        'User-Agent' => 'Omniva-WooCommerce/' . OMNIVALT_VERSION,
      ),
    ));

    if ( is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response) ) {
      return $metadata;
    }

    $plugin_source = wp_remote_retrieve_body($response);
    if ( ! is_string($plugin_source) || '' === $plugin_source ) {
      return $metadata;
    }

    $plugin_source = substr($plugin_source, 0, 8192);
    $header_names = array(
      'requires' => 'Requires at least',
      'tested' => 'Tested up to',
      'requires_php' => 'Requires PHP',
      'wc_requires' => 'WC requires at least',
      'wc_tested' => 'WC tested up to',
    );

    foreach ( $header_names as $metadata_key => $header_name ) {
      $header_pattern = '/^[ \t]*(?:\*[ \t]*)?' . preg_quote($header_name, '/') . '[ \t]*:[ \t]*(.+?)[ \t]*$/mi';
      if ( preg_match($header_pattern, $plugin_source, $header_match) ) {
        $metadata[$metadata_key] = sanitize_text_field($header_match[1]);
      }
    }

    return $metadata;
  }

  private static function get_plugin_basename()
  {
    if ( ! defined('OMNIVALT_BASENAME') ) {
      return '';
    }

    $plugin_basename = constant('OMNIVALT_BASENAME');
    return is_string($plugin_basename) ? $plugin_basename : '';
  }

  /**
   * Resolve the configured installable release asset from the GitHub response.
   *
   * @param mixed  $assets       Release assets returned by GitHub.
   * @param string $expected_name Expected installable asset filename.
   * @return string
   */
  private static function resolve_package_url( $assets, $expected_name )
  {
    if ( ! is_array($assets) || ! is_string($expected_name) || '' === trim($expected_name) ) {
      return '';
    }

    foreach ( $assets as $asset ) {
      $asset_name = '';
      $asset_url  = '';
      if ( is_object($asset) ) {
        $asset_name = isset($asset->name) && is_string($asset->name) ? $asset->name : '';
        $asset_url  = isset($asset->browser_download_url) && is_string($asset->browser_download_url) ? $asset->browser_download_url : '';
      } elseif ( is_array($asset) ) {
        $asset_name = isset($asset['name']) && is_string($asset['name']) ? $asset['name'] : '';
        $asset_url  = isset($asset['browser_download_url']) && is_string($asset['browser_download_url']) ? $asset['browser_download_url'] : '';
      }

      if ( $expected_name !== $asset_name || '' === $asset_url ) {
        continue;
      }

      $package_url = esc_url_raw($asset_url);
      if ( is_string($package_url) && '' !== trim($package_url) ) {
        return $package_url;
      }
    }

    return '';
  }

  private static function get_update_params()
  {
    // The configuration is immutable during a request; avoid rebuilding all
    // shipping methods and API settings for each updater hook invocation.
    static $update_params = null;

    if ( null === $update_params ) {
      $configured_params = OmnivaLt_Core::get_configs('update');
      $update_params = is_array($configured_params) ? $configured_params : array();
    }

    return $update_params;
  }

  private static function get_custom_changes()
  {
    $constant_name = 'OMNIVALT_CUSTOM_CHANGES';
		return defined($constant_name) ? constant($constant_name) : array();
	}

	/**
	 * Convert GitHub Markdown release notes into safe HTML for the WordPress
	 * plugin information modal.
	 *
	 * @param mixed $release_notes Release notes returned by GitHub.
	 * @return string
	 */
	private static function format_release_notes( $release_notes ) {
		if ( ! is_string($release_notes) || '' === trim($release_notes) ) {
			return '';
		}

		$lines = preg_split('/\r\n|\r|\n/', wp_kses_post($release_notes));
		if ( ! is_array($lines) ) {
			return '';
		}

		$html      = '';
		$paragraph = array();
		$in_list   = false;

		foreach ( $lines as $line ) {
			$line = trim((string) $line);

			if ( preg_match('/^#{1,6}\s+(.+)$/', $line, $matches) ) {
				if ( ! empty($paragraph) ) {
					$html      .= wpautop(implode("\n", $paragraph));
					$paragraph = array();
				}
				if ( $in_list ) {
					$html    .= '</ul>';
					$in_list = false;
				}
				$html .= '<h3>' . wp_kses_post($matches[1]) . '</h3>';
				continue;
			}

			if ( preg_match('/^[-*+]\s+(.+)$/', $line, $matches) ) {
				if ( ! empty($paragraph) ) {
					$html      .= wpautop(implode("\n", $paragraph));
					$paragraph = array();
				}
				if ( ! $in_list ) {
					$html    .= '<ul>';
					$in_list = true;
				}

				$html .= '<li>' . wp_kses_post($matches[1]) . '</li>';
				continue;
			}

			if ( '' === $line ) {
				if ( ! empty($paragraph) ) {
					$html      .= wpautop(implode("\n", $paragraph));
					$paragraph = array();
				}
				if ( $in_list ) {
					$html    .= '</ul>';
					$in_list = false;
				}
				continue;
			}

			if ( $in_list ) {
				$html    .= '</ul>';
				$in_list = false;
			}

			$paragraph[] = $line;
		}

		if ( ! empty($paragraph) ) {
			$html .= wpautop(implode("\n", $paragraph));
		}
		if ( $in_list ) {
			$html .= '</ul>';
		}

		return wp_kses_post($html);
	}
}
