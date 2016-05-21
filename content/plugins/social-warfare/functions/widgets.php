<?php 

class sw_popular_posts_widget extends WP_Widget {

	// constructor
	function sw_popular_posts_widget() {
		parent::__construct(false, $name = __('Popular Posts by Social Warfare', 'wp_widget_plugin') );
	}
	
	// widget form creation
	function form($instance) {
	
	// Check values
	if( $instance ) {
		 $title = esc_attr($instance['title']);
		 $count = esc_attr($instance['count']);
		 $timeframe = esc_textarea($instance['timeframe']);
		 $network = esc_textarea($instance['network']);
		 $showCount = esc_textarea($instance['showCount']);
		 $countLabel = esc_textarea($instance['countLabel']);
		 $style = esc_textarea($instance['style']);
	} else {
		 $title = 'Popular Posts';
		 $count = '10';
		 $timeframe = '180';
		 $network = 'totes';
		 $showCount = 'true';
		 $countLabel = 'Total Shares';
		 $style = 'Default';
	}
	
	$options = sw_get_user_options();	
	$availableNetworks = $options['newOrderOfIcons'];

	
	?>
	
	<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'wp_widget_plugin'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
	</p>
	
	<p>
	<label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('How many posts would you like to display?', 'wp_widget_plugin'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="number" value="<?php echo $count; ?>" min="0" />
	</p>
	
	<p style="display:none;">
	<label for="<?php echo $this->get_field_id('timeframe'); ?>"><?php _e('What is maximum age of a post (in days) that you would like to include?', 'wp_widget_plugin'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('timeframe'); ?>" name="<?php echo $this->get_field_name('timeframe'); ?>" value="<?php echo $timeframe; ?>" type="number" min="0">
	</p>
    
    <p>
	<label for="<?php echo $this->get_field_id('network'); ?>"><?php _e('Which network would you like to base your posts popularity on?', 'wp_widget_plugin'); ?></label>
	<select class="widefat" id="<?php echo $this->get_field_id('network'); ?>" name="<?php echo $this->get_field_name('network'); ?>">
    	<option value="totes" <?php if($network=='totes') echo 'selected'; ?>>All Networks</option>
        
        <?php
		foreach($availableNetworks as $key => $value):
			if($options[$key]) {
				if($network == $key.'_shares'):
        			echo '<option value="'.$key.'_shares" selected>'.$value.'</option>';
				else:
					echo '<option value="'.$key.'_shares">'.$value.'</option>';
				endif;
			};
		endforeach;

		?>

    </select>
	</p>
    
     <p>
	<label for="<?php echo $this->get_field_id('showCount'); ?>"><?php _e('Would you like to show the count?', 'wp_widget_plugin'); ?></label>
	<select class="widefat" id="<?php echo $this->get_field_id('showCount'); ?>" name="<?php echo $this->get_field_name('showCount'); ?>">
    	<option value="true" <?php if($showCount=='true') echo 'selected'; ?>>Yes</option>
        <option value="false" <?php if($showCount=='false') echo 'selected'; ?>>No</option>
    </select>
	</p>
    
    	<p>
	<label for="<?php echo $this->get_field_id('countLabel'); ?>"><?php _e('Count Number Label', 'wp_widget_plugin'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('countLabel'); ?>" name="<?php echo $this->get_field_name('countLabel'); ?>" type="text" value="<?php echo $countLabel; ?>" />
	</p>
         <p>
	<label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Which visual style would you like to use?', 'wp_widget_plugin'); ?></label>
	<select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
    	
        <!-- Begin the options for the visual Selector -->
       	<option value="style1" <?php if($style=='style1') echo 'selected'; ?>>First Style</option>
        <option value="style2" <?php if($style=='style2') echo 'selected'; ?>>Second Style</option>
    	<!-- End the options for the visual Selector -->
        
    </select>
	</p>
    
    
	<?php
	}
	
	// update widget
	function update($new_instance, $old_instance) {
		  $instance = $old_instance;
		  // Fields
		  $instance['title'] = strip_tags($new_instance['title']);
		  $instance['count'] = strip_tags($new_instance['count']);
		  $instance['timeframe'] = strip_tags($new_instance['timeframe']);
		  $instance['network'] = strip_tags($new_instance['network']);
		  $instance['showCount'] = strip_tags($new_instance['showCount']);
		  $instance['countLabel'] = strip_tags($new_instance['countLabel']);
		  $instance['style'] = strip_tags($new_instance['style']);
		 return $instance;
	}
	
	// display widget
	function widget($args, $instance) {
	   extract( $args );
	   // these are the widget options
	   $title = apply_filters('widget_title', $instance['title']);
	   $count = $instance['count'];
	   $timeframe = $instance['timeframe'].' days ago';
	   $network = $instance['network'];
	   $showCount = $instance['showCount'];
	   $countLabel = $instance['countLabel'];
	   $style	= $instance['style'];
	   
	   echo $before_widget;
	   // Display the widget
	   echo '<div class="widget-text wp_widget_plugin_box sw_pop_'.$style.'">';
	
	   // Check if title is set
	   if ( $title ) {
		  echo $before_title . $title . $after_title;
	   }
	
	   // Check if text is set
	  $args = array(
			"posts_per_page" => $count,
			"post_type" => "post",
			"meta_key" => '_'.$network,
			"orderby" => "meta_value_num",
			"order" => "DESC"
		);
		$q = new WP_Query( $args );
		if( $q->have_posts() ) :
			$i = 1;
			echo '<ul">';
			while( $q->have_posts() ):
				$q->the_post();
				if($showCount == 'true'):
					$postID = get_the_ID();
					$shares = get_post_meta($postID,'_'.$network,true);
					echo '<li><a class="swPopularity" href="'.get_the_permalink().'"><b>'.get_the_title().'</b> - '.kilomega($shares).' '.$countLabel.'</a></li>';
				else:
					echo '<li><a class="swPopularity" href="'.get_the_permalink().'"><b>'.get_the_title().'</b></a></li>';
				endif;
			endwhile;
			echo '</ul>';
		endif;
		wp_reset_postdata();
	   echo '</div>';
	   echo $after_widget;
	}
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("sw_popular_posts_widget");'));
