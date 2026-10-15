<?php
class OmnivaLt_Admin_Navigation
{
  const MENU_SLUG = 'omniva-shipping';
  // WooCommerce uses position 55, so keep Omniva immediately above it.
  const MENU_POSITION = 54;
  // The non-visible Illustrator slice rectangle is omitted to prevent a white square in the admin menu.
  const MENU_ICON = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1MTUgNTE2Ij4KICA8ZGVmcz4KICAgIDxzdHlsZT4KICAgICAgLmNscy0xIHsKICAgICAgICBmaWxsOiBub25lOwogICAgICB9CgogICAgICAuY2xzLTEsIC5jbHMtMiB7CiAgICAgICAgc3Ryb2tlLXdpZHRoOiAwcHg7CiAgICAgIH0KCiAgICAgIC5jbHMtMiB7CiAgICAgICAgZmlsbDogI2ZmZjsKICAgICAgfQogICAgPC9zdHlsZT4KICA8L2RlZnM+CiAgPHBhdGggY2xhc3M9ImNscy0yIiBkPSJtODguNTMsODkuMjJjLTExNy43NSwxMTcuNzUtMTE3Ljc1LDMwOC42NSwwLDQyNi40di04OC4zMWMxMTcuNzUsMTE3Ljc1LDMwOC42NSwxMTcuNzUsNDI2LjQsMEg4OC41M1Y4OS4yMlpNNDI2LjYyLjkxdjQyNi40YzExNy43NS0xMTcuNzUsMTE3Ljc1LTMwOC42NSwwLTQyNi40aDBaTS4yMiw4OS4yMmg0MjYuNEMzMDguODctMjguNTMsMTE3Ljk3LTI4LjUzLjIyLDg5LjIyWiIvPgogIAo8L3N2Zz4=';
  const SETTINGS_PAGE_SLUG = 'omnivalt-settings';
  const MANIFEST_PAGE_SLUG = 'omniva-manifest';

  private static $menu_registered = false;

  public static function get_pages()
  {
    return array(
      self::SETTINGS_PAGE_SLUG => array(
        'title' => __('Omniva settings', 'omnivalt'),
        'menu_title' => __('Settings', 'omnivalt'),
        'capability' => 'manage_woocommerce',
        'url' => add_query_arg(
          array('page' => self::SETTINGS_PAGE_SLUG),
          admin_url('admin.php')
        ),
      ),
      self::MANIFEST_PAGE_SLUG => array(
        'title' => __('Omniva delivery', 'omnivalt'),
        'menu_title' => __('Shipments', 'omnivalt'),
        'capability' => 'manage_woocommerce',
        'url' => add_query_arg(
          array('page' => self::MANIFEST_PAGE_SLUG),
          admin_url('admin.php')
        ),
      ),
    );
  }

  public static function get_page( $slug )
  {
    $pages = self::get_pages();

    return isset($pages[$slug]) ? $pages[$slug] : false;
  }

  public static function register_menu_pages()
  {
    if ( self::$menu_registered ) {
      return;
    }

    $manifest_page = self::get_page(self::MANIFEST_PAGE_SLUG);
    $settings_page = self::get_page(self::SETTINGS_PAGE_SLUG);
    if ( ! $manifest_page || ! $settings_page ) {
      return;
    }

    add_menu_page(
      __('Omniva delivery', 'omnivalt'),
      __('Omniva delivery', 'omnivalt'),
      $manifest_page['capability'],
      self::MENU_SLUG,
      array('OmnivaLt_Manifest', 'manifest_page'),
      self::MENU_ICON,
      self::MENU_POSITION
    );

    add_submenu_page(
      self::MENU_SLUG,
      $manifest_page['title'],
      $manifest_page['menu_title'],
      $manifest_page['capability'],
      self::MANIFEST_PAGE_SLUG,
      array('OmnivaLt_Manifest', 'manifest_page')
    );

    add_submenu_page(
      self::MENU_SLUG,
      $settings_page['title'],
      $settings_page['menu_title'],
      $settings_page['capability'],
      self::SETTINGS_PAGE_SLUG,
      array('OmnivaLt_Settings_Page', 'render_page')
    );

    // add_menu_page() creates a duplicate submenu entry for the parent page.
    remove_submenu_page(self::MENU_SLUG, self::MENU_SLUG);
    self::$menu_registered = true;
  }

}
