<?php

require 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\BadResponseException;

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
  'lib/extras.php'                 // Custom functions
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



































//
// Fetching Mailchimp API Key
//
function mailchimp_auth_creds() {
  return array('arobbins', '275cafe2706cafae258728fc0f11132d-us11');
}


//
// Fetching Mailchimp List ID
//
function mailinglist_get_list_id() {

  echo '5c6bd183d4';
  die();

}

add_action('wp_ajax_mailinglist_get_list_id', 'mailinglist_get_list_id');
add_action('wp_ajax_nopriv_mailinglist_get_list_id', 'mailinglist_get_list_id');




//
// Fetching Mailchimp List
//
function mailinglist_signup() {

  $email = $_POST['email'];
  $nonce = $_POST['nonce'];

  if(wp_verify_nonce($nonce, 'mailinglist_signup')) {

    $resp = [];

    try {
      $client = new GuzzleHttp\Client(['base_uri' => 'https://us11.api.mailchimp.com/3.0/']);

      $response = $client->request('POST', 'lists/5c6bd183d4/members', [
        'auth' => [
          'arobbins', '255970dd153fe7b1d83bec1478cbaa74-us11'
        ],
        'json' => [
          'email_address' => $email,
          "status" => "pending",
        ]
      ]);

      $statusCode = $response->getStatusCode();

      $resp['code'] = $statusCode;
      $resp['message'] = json_decode($response->getBody());

      echo json_encode($resp);
      die();

    } catch (GuzzleHttp\Exception\ClientException $e) {

      $response = $e->getResponse();
      $statusCode = $response->getStatusCode();
      $message = $e->getMessage();

      // print_r( json_encode($e->getMessage()) );
      // echo $e->getMessage();

      $resp['code'] = $statusCode;
      $resp['message'] = json_decode($response->getBody());

      echo json_encode($resp);
      die();

    }

  } else {
    echo 'Invalid Nonce';
    die();

  }

}

add_action('wp_ajax_mailinglist_signup', 'mailinglist_signup');
add_action('wp_ajax_nopriv_mailinglist_signup', 'mailinglist_signup');
