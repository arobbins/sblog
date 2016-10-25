<?php

namespace Roots\Sage\Assets;

/**
 * Scripts and stylesheets
 *
 * Enqueue stylesheets in the following order:
 * 1. /theme/dist/styles/main.css
 *
 * Enqueue scripts in the following order:
 * 1. /theme/dist/scripts/modernizr.js
 * 2. /theme/dist/scripts/main.js
 */

class JsonManifest {
  private $manifest;

  public function __construct($manifest_path) {
    if (file_exists($manifest_path)) {
      $this->manifest = json_decode(file_get_contents($manifest_path), true);
    } else {
      $this->manifest = [];
    }
  }

  public function get() {
    return $this->manifest;
  }

  public function getPath($key = '', $default = null) {
    $collection = $this->manifest;
    if (is_null($key)) {
      return $collection;
    }
    if (isset($collection[$key])) {
      return $collection[$key];
    }
    foreach (explode('.', $key) as $segment) {
      if (!isset($collection[$segment])) {
        return $default;
      } else {
        $collection = $collection[$segment];
      }
    }
    return $collection;
  }
}

function asset_path($filename) {
  $dist_path = get_template_directory_uri();
  $directory = dirname($filename) . '/';
  $file = basename($filename);
  static $manifest;

  if (empty($manifest)) {
    $manifest_path = get_template_directory() . DIST_DIR . 'assets.json';
    $manifest = new JsonManifest($manifest_path);
  }

  if (array_key_exists($file, $manifest->get())) {
    return $dist_path . $directory . $manifest->get()[$file];
  } else {
    return $dist_path . $directory . $file;
  }
}

function assets() {

  if (is_single() && comments_open() && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
  }

  if(!is_admin()) {

    // Fonts
    wp_enqueue_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', false, null);

    wp_enqueue_style('font-secondary-3', 'https://fonts.googleapis.com/css?family=Bitter:400,700|Open+Sans:400,400i,700,700i', false, null);

    // Vendor
    wp_enqueue_style('hover-css', asset_path('/assets/css/vendor/hover-min.css'), false, null);
    wp_enqueue_style('animate-css', "https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.4.0/animate.min.css", false, null);
    wp_enqueue_style('tooltip-theme-arrows', asset_path('/assets/css/vendor/tooltip-theme-arrows.css'), false, null);
    wp_enqueue_style('tooltip-theme-twipsy', asset_path('/assets/css/vendor/tooltip-theme-twipsy.css'), false, null);
    wp_enqueue_style('prism-css', asset_path('/assets/css/vendor/prism.min.css'), false, null);
    wp_enqueue_script('prism-js', asset_path('/assets/js/vendor/prism.min.js'), ['jquery'], null, true);
    wp_enqueue_script('modernizer', asset_path('/assets/js/vendor/modernizr.min.js'), ['jquery'], null, true);
    wp_enqueue_script('lazyload', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.lazyload/1.9.1/jquery.lazyload.min.js', ['jquery'], null, true);
    wp_enqueue_script('fitvid', 'https://cdnjs.cloudflare.com/ajax/libs/fitvids/1.1.0/jquery.fitvids.min.js', ['jquery'], null, true);

    wp_enqueue_script('jquery-validation', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.15.0/jquery.validate.min.js', ['jquery'], null, true);

    // App
    wp_enqueue_style('app-css', asset_path('/assets/css/app.min.css'), false, null);
    wp_enqueue_script('app-js', asset_path('/assets/js/app.min.js'), ['jquery'], null, true);

  }

}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\assets', 100);
