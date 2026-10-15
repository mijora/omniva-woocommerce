<?php
class OmnivaLt_Admin_Page_Assets
{
  /**
   * Add a fallback that reveals a server-rendered admin page when its enhancement script fails.
   *
   * @param string $script_handle Enqueued script handle.
   * @param string $root_id       Root element ID.
   * @param string $ready_class   Class changed when the page is ready.
   * @param string $class_action  Whether the fallback adds or removes the class.
   * @return void
   */
  public static function add_readiness_fallback( $script_handle, $root_id, $ready_class, $class_action = 'remove' )
  {
    if ( ! function_exists('wp_add_inline_script') || ! in_array($class_action, array('add', 'remove'), true) ) {
      return;
    }

    $root_id_json = wp_json_encode($root_id);
    $ready_class_json = wp_json_encode($ready_class);

    if ( false === $root_id_json || false === $ready_class_json ) {
      return;
    }

    $script = '(function() {' . "\n"
      . '  var root = document.getElementById(' . $root_id_json . ');' . "\n"
      . '  if (!root) {' . "\n"
      . '    return;' . "\n"
      . '  }' . "\n\n"
      . '  function reveal() {' . "\n"
      . '    root.classList.' . $class_action . '(' . $ready_class_json . ');' . "\n"
      . "    window.removeEventListener('load', reveal);\n"
      . "    window.removeEventListener('error', reveal, true);\n"
      . '  }' . "\n\n"
      . "  if (document.readyState === 'complete') {\n"
      . '    reveal();' . "\n"
      . '    return;' . "\n"
      . '  }' . "\n\n"
      . "  window.addEventListener('load', reveal);\n"
      . "  window.addEventListener('error', reveal, true);\n"
      . '})();';

    wp_add_inline_script($script_handle, $script, 'after');
  }
}
