<?php

/*
  Plugin Name: PHP Text Widget
  Plugin URI: http://www.satollo.net/plugins/php-text-widget
  Description: Extends the default WordPress text widget making it able to execute PHP code
  Version: 1.0.8
  Author: Stefano Lissa
  Author URI: http://www.satollo.net
 */


add_filter('widget_text', 'ptw_widget_text', 99);

function ptw_widget_text($text) {
    if (strpos($text, '<' . '?') !== false) {
        ob_start();
        eval('?' . '>' . $text);
        $text = ob_get_clean();
    }
    return $text;
}

$ptw_post_id = null;

add_filter('the_content', 'ptw_the_content', 99);

function ptw_the_content($content) {
    global $post, $ptw_post_id;
    if (is_single() || is_page()) {
        $ptw_post_id = $post->ID;
    }
    return $content;
}

if (is_admin()) {
    include dirname(__FILE__) . '/admin.php';
}