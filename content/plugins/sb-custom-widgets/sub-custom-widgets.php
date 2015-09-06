<?php

/*
  Plugin Name: Simpleblend Custom Widgets
  Version: 1.0
  Author: Andrew Robbins - https://simpleblend.net
  Description: Custom Widgets for Simpleblend
*/

class Donations extends WP_Widget {

  function Donations() {
    // Instantiate the parent object
    parent::__construct(false, 'Donations Widget');
  }

  function widget($args, $instance) {
    // Widget output
    extract($args);
    $title = apply_filters('widget_title', $instance['title']);

    require('widgets/donations/donations.php');
  }

  function update($new_instance, $old_instance) {
    // Save widget options
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);

    return $instance;
  }

  function form($instance) {
    // Output admin widget options form
    $title = esc_attr($instance['title']);

    require('widgets/donations/donations-fields.php');
  }
}

function wp_donations() {
  register_widget('Donations');
}

add_action('widgets_init', 'wp_donations');

?>