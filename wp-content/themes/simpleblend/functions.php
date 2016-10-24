<?php
/**
 * Sage includes
 *
 * The $sage_includes array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 *
 * Please note that missing files will produce a fatal error.
 *
 * @link https://github.com/roots/sage/pull/1042
 */
$sage_includes = [
  'lib/utils.php',                 // Utility functions
  'lib/init.php',                  // Initial theme setup and constants
  'lib/wrapper.php',               // Theme wrapper class
  'lib/conditional-tag-check.php', // ConditionalTagCheck class
  'lib/config.php',                // Configuration
  'lib/assets.php',                // Scripts and stylesheets
  'lib/titles.php',                // Page titles
  'lib/extras.php',                // Custom functions
];

foreach ($sage_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'sage'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}
unset($file, $filepath);

//
// Modifying the view more link for posts
//
function et_excerpt_more($more) {
  $more = '<i class="fa fa-long-arrow-right hvr-wobble-horizontal"></i>';
  return ' <a href="'. get_permalink($post->ID) . '" class="article-more">' . $more . '</a>';
}
//add_filter('excerpt_more', 'et_excerpt_more');

// disable WordPress sanitization to allow more than just $allowedtags from /wp-includes/kses.php
remove_filter('pre_user_description', 'wp_filter_kses');

// add sanitization for WordPress posts
add_filter( 'pre_user_description', 'wp_filter_post_kses');

//
// Adding lazy load class to all images in content area of posts
//
function lazy_imgs($html, $id, $caption, $title, $align, $url, $size, $alt) {

  $imgNew = '<img data-original="' . $url . '" ';
  $html = str_replace('<img ', $imgNew, $html);
  return $html;
}
add_filter('image_send_to_editor', 'lazy_imgs', 10, 8);

//
// Adding lazy load class to all images in content area of posts
//
function img_responsive($content) {
  return str_replace('<img class="', '<img class="is-lazy ', $content);
}
add_filter('the_content', 'img_responsive');

//
// Adding class to all iframe videos
//
function custom_youtube_oembed( $code ) {
  if( stripos( $code, 'youtube.com' ) !== FALSE && stripos( $code, 'iframe' ) !== FALSE )
      $code = str_replace( '<iframe', '<iframe class="content-video" type="text/html" ', $code );

  return $code;
}
add_filter( 'embed_oembed_html', 'custom_youtube_oembed' );


function posts_by_year() {

  $years = array();

  // Grabbing all posts from WP
  $posts = get_posts(array(
    'numberposts' => -1,
    'orderby' => 'post_date',
    'order' => 'DESC',
    'post_type' => 'post',
    'post_status' => 'publish'
  ));

  // loop through posts, populating our data structure
  foreach($posts as $post) {

    $year = date('Y', strtotime($post->post_date));
    $month = date('F', strtotime($post->post_date));

    $years[$year][$month][] = $post;

  }
  krsort($years);

  return $years;

}






/*

Callback function to insert 'styleselect' into the $buttons array

*/
function deftly_mce_buttons_2($buttons) {
  array_unshift( $buttons, 'styleselect' );
  return $buttons;
}
add_filter('mce_buttons_2', 'deftly_mce_buttons_2');


/*

Callback function to filter the MCE settings

*/
function deftly_mce_before_init_insert_formats($init_array) {

  $style_formats = array(

    array(
      'title' => 'Narrow image',
      'selector' => 'p',
      'classes' => 'narrow-img'
    )

  );

  // Insert the array, JSON ENCODED, into 'style_formats'
  $init_array['style_formats'] = json_encode( $style_formats );

  return $init_array;

}
add_filter( 'tiny_mce_before_init', 'deftly_mce_before_init_insert_formats' );