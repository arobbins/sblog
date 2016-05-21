<?php

/**
 * Chimpy Plugin Widget
 * 
 * @class Chimpy_Widget
 * @package Chimpy
 * @author RightPress
 */
if (!class_exists('Chimpy_Widget')) {
    class Chimpy_Widget extends WP_Widget
    {
        /**
         * Widget constructor (registering widget with WP)
         * 
         * @access public
         * @return void
         */
        public function __construct() {
            parent::__construct(
                'chimpy_form',
                __('MailChimp Signup (Chimpy)', 'chimpy'),
                array(
                    'description' => __('Widget displays a signup form, if enabled under MailChimp settings.', 'chimpy'),
                )
            );

            $this->opt = $this->plugin_settings();
        }

        /**
         * Load plugin settings
         * 
         * @access public
         * @return array
         */
        public function plugin_settings()
        {
            $this->settings = chimpy_plugin_settings();

            $results = array();

            // Iterate over settings array and extract values
            foreach ($this->settings as $page => $page_value) {
                foreach ($page_value['children'] as $subpage => $subpage_value) {
                    foreach ($subpage_value['children'] as $section => $section_value) {
                        foreach ($section_value['children'] as $field => $field_value) {
                            if (isset($field_value['default'])) {
                                $results['chimpy_' . $field] = $field_value['default'];
                            }
                        }
                    }
                }
            }

            return array_merge(
                $results,
                get_option('chimpy_options', $results)
            );
        }

        /**
         * Frontend display of widget
         * 
         * @access public
         * @param array $args
         * @param array $instance
         * @return void
         */
        public function widget($args, $instance)
        {
            // Check if integration is enabled
            if (!$this->opt['chimpy_api_key'] || !isset($this->opt['forms']) || empty($this->opt['forms'])) {
                return;
            }

            // Get allowed forms
            $allowed_forms = isset($instance['allowed_forms']) && is_array($instance['allowed_forms']) && !empty($instance['allowed_forms']) ? $instance['allowed_forms'] : array();

            // Select form that match the conditions best
            $form = Chimpy::select_form_by_conditions($this->opt['forms'], $allowed_forms);

            if (!$form) {
                return;
            }

            require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-prepare-form.inc.php';

            $form_html = chimpy_prepare_form($form, $this->opt, 'widget', $args);

            echo $form_html;
        }

        /**
         * Backend configuration form
         * 
         * @access public
         * @param array $instance
         * @return void
         */
        public function form($instance)
        {
            // Get allowed forms
            $allowed_forms = isset($instance['allowed_forms']) && is_array($instance['allowed_forms']) && !empty($instance['allowed_forms']) ? join(',', $instance['allowed_forms']) : '';

            ?>

            <p>
                <?php printf(__('This widget renders a MailChimp signup form.<br />You can edit signup forms <a href="%s">here</a>.', 'chimpy'), site_url('/wp-admin/admin.php?page=chimpy')); ?>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('allowed_forms'); ?>"><?php _e('Allow only these forms (comma-separated IDs):', 'chimpy'); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id('allowed_forms'); ?>" name="<?php echo $this->get_field_name('allowed_forms'); ?>" type="text" value="<?php echo esc_attr($allowed_forms); ?>" />
            </p>

            <?php
        }

        /**
         * Sanitize form values
         * 
         * @access public
         * @param array $new_instance
         * @param array $old_instance
         * @return void
         */
        public function update($new_instance, $old_instance)
        {
            $instance = array();

            // Get allowed forms
            $instance['allowed_forms'] = (!empty($new_instance['allowed_forms'])) ? strip_tags($new_instance['allowed_forms']) : '';

            if ($instance['allowed_forms'] != '' && preg_match('/^([0-9]+,?)+$/', $instance['allowed_forms'])) {
                $instance['allowed_forms'] = preg_split('/,/', $instance['allowed_forms'], -1, PREG_SPLIT_NO_EMPTY);
                $normalized_allowed_forms = array();

                foreach ($instance['allowed_forms'] as $allowed_form) {
                        $normalized_allowed_forms[] = (int)$allowed_form;
                }

                $instance['allowed_forms'] = $normalized_allowed_forms;
            }
            else {
                $instance['allowed_forms'] = array();
            }

            return $instance;
        }

    }
}

?>
