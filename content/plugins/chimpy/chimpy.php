<?php

/**
 * Plugin Name: Chimpy
 * Plugin URI: http://www.rightpress.net/chimpy
 * Description: MailChimp WordPress Plugin
 * Version: 2.1.3
 * Author: RightPress
 * Author URI: http://www.rightpress.net
 * Requires at least: 3.5
 * Tested up to: 3.7
 * 
 * Text Domain: chimpy
 * Domain Path: /languages
 * 
 * @package Chimpy
 * @category Core
 * @author RightPress
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('CHIMPY_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('CHIMPY_PLUGIN_URL', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)));
define('CHIMPY_VERSION', '2.1.3');

if (!class_exists('Chimpy')) {

    /**
     * Main plugin class
     * 
     * @package Chimpy
     * @author RightPress
     */
    class Chimpy
    {
        private static $instance = false;
        private $last_rendered_form = 0;
        private $popup_page_capping_in_effect = false;

        /**
         * Singleton control
         */
        public static function get_instance()
        {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Class constructor
         * 
         * @access public
         * @return void
         */
        public function __construct()
        {
            $this->mailchimp = null;

            // Load translation
            load_plugin_textdomain('chimpy', false, dirname(plugin_basename(__FILE__)) . '/languages/');

            // Load classes/includes
            require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-plugin-structure.inc.php';
            require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-form.inc.php';
            require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-widget.class.php';

            // Load configuration and current settings
            $this->get_config();
            $this->opt = $this->get_options();

            /**
             * For admin only
             */
            if (is_admin()) {

                if (!(defined('DOING_AJAX') && DOING_AJAX)) {
                    // General plugin setup
                    add_action('admin_menu', array($this, 'add_admin_menu'));
                    add_action('admin_init', array($this, 'admin_construct'));
                    add_filter('plugin_action_links', array($this, 'plugin_settings_link'), 10, 2);

                    // Load scripts/styles conditionally
                    if (preg_match('/page=chimpy/i', $_SERVER['QUERY_STRING']) && !preg_match('/page=chimpy_lite/i', $_SERVER['QUERY_STRING'])) {
                        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts_and_styles'));
                    }
                }

                // Ajax handlers
                add_action('wp_ajax_chimpy_mailchimp_status', array($this, 'ajax_mailchimp_status'));
                add_action('wp_ajax_chimpy_get_lists', array($this, 'ajax_get_lists'));
                add_action('wp_ajax_chimpy_get_lists_with_multiple_groups_and_fields', array($this, 'ajax_get_lists_groups_fields'));
                add_action('wp_ajax_chimpy_update_groups_and_tags', array($this, 'ajax_groups_and_tags_in_array'));
            }

            /**
             * For frontend only
             */
            else {

                if (!(defined('DOING_AJAX') && DOING_AJAX)) {

                    // Hooks
                    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts_and_styles'));
                    add_action('wp_footer', array($this, 'display_popup'));
                    add_filter('the_content', array($this, 'form_after_content'));
                    add_filter('the_content', array($this, 'content_lock'), 99);

                    // Add checkboxes
                    add_action('register_form', array($this, 'checkbox_render_registration'), 99);
                    add_action('comment_form', array($this,'checkbox_render_comment'), 99);

                    // Subscribe after checkbox checked
                    add_action('user_register', array($this, 'checkbox_subscribe_registration'), 99, 1);
                    add_action('comment_post', array($this, 'checkbox_subscribe_comments'), 99, 2);

                    // Track page hits for popup frequency capping
                    if ($this->opt['chimpy_popup_page_limit'] > 1) {
                        $this->track_page_hits();
                    }
                }
            }

            /**
             * For all
             */
            add_action('widgets_init', create_function('', 'return register_widget("Chimpy_Widget");'));
            add_shortcode('chimpy_form', array($this, 'subscription_shortcode'));
            register_uninstall_hook(__FILE__, array('Chimpy', 'uninstall'));

            /**
             * User sync
             */
            add_action('user_register', array($this, 'sync_create'));
            add_action('profile_update', array($this, 'sync_update'), 10, 2);
            add_action('delete_user', array($this, 'sync_delete'));

            /**
             * Ajax handlers
             */
            add_action('wp_ajax_chimpy_subscribe', array($this, 'ajax_subscribe'));
            add_action('wp_ajax_nopriv_chimpy_subscribe', array($this, 'ajax_subscribe'));

        }

        /**
         * Loads (and sets) configuration values from structure file and database
         * 
         * @access public
         * @return void
         */
        public function get_config()
        {
            // Settings tree
            $this->settings = chimpy_plugin_settings();

            // Load some data from config
            $this->hints = $this->options('hint');
            $this->validation = $this->options('validation', true);
            $this->titles = $this->options('title');
            $this->options = $this->options('values');
            $this->section_info = $this->get_section_info();
            $this->default_tabs = $this->get_default_tabs();
        }

        /**
         * Get settings options: default, hint, validation, values
         * 
         * @access public
         * @param string $name
         * @param bool $split_by_page
         * @return array
         */
        public function options($name, $split_by_subpage = false)
        {
            $results = array();

            // Iterate over settings array and extract values
            foreach ($this->settings as $page => $page_value) {
                $page_options = array();

                foreach ($page_value['children'] as $subpage => $subpage_value) {
                    foreach ($subpage_value['children'] as $section => $section_value) {
                        foreach ($section_value['children'] as $field => $field_value) {
                            if (isset($field_value[$name])) {
                                $page_options['chimpy_' . $field] = $field_value[$name];
                            }
                        }
                    }

                    $results[preg_replace('/_/', '-', $subpage)] = $page_options;
                    $page_options = array();
                }
            }

            $final_results = array();

            // Do we need to split results by page?
            if (!$split_by_subpage) {
                foreach ($results as $value) {
                    $final_results = array_merge($final_results, $value);
                }
            }
            else {
                $final_results = $results;
            }

            return $final_results;
        }

        /**
         * Get default tab for each page
         * 
         * @access public
         * @return array
         */
        public function get_default_tabs()
        {
            $tabs = array();

            // Iterate over settings array and extract values
            foreach ($this->settings as $page => $page_value) {
                reset($page_value['children']);
                $tabs[$page] = key($page_value['children']);
            }

            return $tabs;
        }

        /**
         * Get array of section info strings
         * 
         * @access public
         * @return array
         */
        public function get_section_info()
        {
            $results = array();

            // Iterate over settings array and extract values
            foreach ($this->settings as $page_value) {
                foreach ($page_value['children'] as $subpage => $subpage_value) {
                    foreach ($subpage_value['children'] as $section => $section_value) {
                        if (isset($section_value['info'])) {
                            $results[$section] = $section_value['info'];
                        }
                    }
                }
            }

            return $results;
        }

        /*
         * Get plugin options set by user
         * 
         * @access public
         * @return array
         */
        public function get_options()
        {
            $default_options = array_merge(
                $this->options('default'),
                array(
                    'chimpy_checkout_fields' => array(),
                    'chimpy_widget_fields' => array(),
                    'chimpy_shortcode_fields' => array(),
                )
            );

            return array_merge(
                       $default_options,
                       get_option('chimpy_options', $this->options('default'))
                   );
        }

        /*
         * Update options
         * 
         * @access public
         * @return bool
         */
        public function update_options($args = array())
        {
            return update_option('chimpy_options', array_merge($this->get_options(), $args));
        }

        /**
         * Add link to admin page
         * 
         * @access public
         * @return void
         */
        public function add_admin_menu()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            add_options_page(
                $this->settings['chimpy']['page_title'],
                $this->settings['chimpy']['title'],
                $this->settings['chimpy']['capability'],
                $this->settings['chimpy']['slug'],
                array($this, 'set_up_admin_page')
            );
        }

        /*
         * Set up admin page
         * 
         * @access public
         * @return void
         */
        public function set_up_admin_page()
        {
            // Check for general warnings
            if (!$this->curl_enabled()) {
                add_settings_error(
                    'error_type',
                    'general',
                    sprintf(__('Warning: PHP cURL extension is not enabled on this server. cURL is required for this plugin to function correctly. You can read more about cURL <a href="%s">here</a>.', 'chimpy'), 'http://php.net/manual/en/book.curl.php')
                );
            }

            // Print notices
            settings_errors('chimpy');

            // Print page tabs
            $this->render_tabs();

            // Print page content
            $this->render_page();
        }

        /**
         * Admin interface constructor
         * 
         * @access public
         * @return void
         */
        public function admin_construct()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            // Iterate subpages
            foreach ($this->settings['chimpy']['children'] as $subpage => $subpage_value) {

                register_setting(
                    'chimpy_opt_group_' . $subpage,            // Option group
                    'chimpy_options',                          // Option name
                    array($this, 'options_validate')                  // Sanitize
                );

                // Iterate sections
                foreach ($subpage_value['children'] as $section => $section_value) {

                    add_settings_section(
                        $section,
                        $section_value['title'],
                        array($this, 'render_section_info'),
                        'chimpy-admin-' . str_replace('_', '-', $subpage)
                    );

                    // Iterate fields
                    foreach ($section_value['children'] as $field => $field_value) {

                        add_settings_field(
                            'chimpy_' . $field,                                     // ID
                            $field_value['title'],                                      // Title 
                            array($this, 'render_options_' . $field_value['type']),     // Callback
                            'chimpy-admin-' . str_replace('_', '-', $subpage), // Page
                            $section,                                                   // Section
                            array(                                                      // Arguments
                                'name' => 'chimpy_' . $field,
                                'options' => $this->get_options(),
                            )
                        );

                    }
                }
            }
        }

        /**
         * Render admin page navigation tabs
         * 
         * @access public
         * @param string $current_tab
         * @return void
         */
        public function render_tabs()
        {
            // Get current page and current tab
            $current_page = preg_replace('/settings_page_/', '', $this->get_current_page_slug());
            $current_tab = $this->get_current_tab();

            // Output admin page tab navigation
            echo '<div class="chimpy-container">';
            echo '<div id="icon-chimpy" class="icon32 icon32-chimpy"><br></div>';
            echo '<h2 class="nav-tab-wrapper">';
            foreach ($this->settings as $page => $page_value) {
                if ($page != $current_page) {
                    continue;
                }

                foreach ($page_value['children'] as $subpage => $subpage_value) {
                    $class = ($subpage == $current_tab) ? ' nav-tab-active' : '';
                    echo '<a class="nav-tab'.$class.'" href="?page='.preg_replace('/_/', '-', $page).'&tab='.$subpage.'">'.((isset($subpage_value['icon']) && !empty($subpage_value['icon'])) ? $subpage_value['icon'] . ($subpage_value['title'] != '' ? '&nbsp;' : '') : '').$subpage_value['title'].'</a>';
                }
            }
            echo '</h2>';
            echo '</div>';
        }

        /**
         * Get current tab (fallback to default)
         * 
         * @access public
         * @param bool $is_dash
         * @return string
         */
        public function get_current_tab($is_dash = false)
        {
            $tab = (isset($_GET['tab']) && $this->page_has_tab($_GET['tab'])) ? preg_replace('/-/', '_', $_GET['tab']) : $this->get_default_tab();

            return (!$is_dash) ? $tab : preg_replace('/_/', '-', $tab);
        }

        /**
         * Get default tab
         * 
         * @access public
         * @return string
         */
        public function get_default_tab()
        {
            $current_page_slug = $this->get_current_page_slug();
            return $this->default_tabs[$current_page_slug];
        }

        /**
         * Get current page slug
         * 
         * @access public
         * @return string
         */
        public function get_current_page_slug()
        {
            $current_screen = get_current_screen();
            $current_page = $current_screen->base;
            $current_page_slug = preg_replace('/settings_page_/', '', $current_page);
            return preg_replace('/-/', '_', $current_page_slug);
        }

        /**
         * Check if current page has requested tab
         * 
         * @access public
         * @param string $tab
         * @return bool
         */
        public function page_has_tab($tab)
        {
            $current_page_slug = $this->get_current_page_slug();

            if (isset($this->settings[$current_page_slug]['children'][$tab]))
                return true;

            return false;
        }

        /**
         * Render settings page
         * 
         * @access public
         * @param string $page
         * @return void
         */
        public function render_page(){

            $current_tab = $this->get_current_tab(true);

            ?>
                <div class="wrap chimpy">
                    <div class="chimpy-container">
                        <div class="chimpy-left">
                            <form method="post" action="options.php" enctype="multipart/form-data">
                                <input type="hidden" name="current_tab" value="<?php echo $current_tab; ?>" />

                                <?php
                                    settings_fields('chimpy_opt_group_'.preg_replace('/-/', '_', $current_tab));
                                    do_settings_sections('chimpy-admin-' . $current_tab);

                                    if ($current_tab == 'forms') {
                                        echo '<div style="width:100%;text-align:center;border-top:1px solid #dfdfdf;padding-top:18px;"><a href="' . admin_url('/options-general.php?page=chimpy&tab=help') . '">' . __('How do I display my forms?', 'chimpy') . '</a></div>';
                                    }
                                    else if ($current_tab == 'help') {
                                        echo '<script>jQuery(document).ready(function() { jQuery(\'.submit\').remove(); });</script>';
                                    }
                                    else {
                                        echo '<div></div>';
                                    }

                                    submit_button();
                                ?>

                            </form>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            <?php

                /**
                 * Pass data on selected lists, groups and merge tags
                 */
                if (isset($this->opt['forms']) && is_array($this->opt['forms']) && !empty($this->opt['forms'])) {

                    $chimpy_selected_lists = array();

                    foreach ($this->opt['forms'] as $form_key => $form) {
                        $chimpy_selected_lists[$form_key] = array(
                            'list'      => $form['list'],
                            'groups'    => $form['groups'],
                            'merge'     => $form['fields']
                        );
                    }
                }
                else {
                    $chimpy_selected_lists = array();
                }

                /**
                 * Pass Forms tab field hints
                 */
                $forms_page_hints = array(
                    'chimpy_forms_title_field'              => __('<p>Title of the form - will be displayed in a signup form header.</p>', 'chimpy'),
                    'chimpy_forms_above_field'              => __('<p>Message to display above form fields.</p>', 'chimpy'),
                    'chimpy_forms_below_field'              => __('<p>Message to display below form fields.</p>', 'chimpy'),
                    'chimpy_forms_button_field'             => __('<p>Form submit button label.</p>', 'chimpy'),
                    'chimpy_forms_color_scheme'             => __('<p>Select one of the predefined color schemes. You can further customize the look & feel of your forms by adding custom CSS rules to Settings > Override CSS.</p>', 'chimpy'),
                    'chimpy_forms_redirect_url'             => __('<p>Optionaly provide an URL where subscribers should be redirected to after successful signup.</p> <p>Leave this field empty to simply display a thank you message.</p>', 'chimpy'),
                    'chimpy_forms_mailing_list'             => __('<p>Select one of your MailChimp mailing lists for users to be subscribed to.</p>', 'chimpy'),
                    'chimpy_forms_groups'                   => __('<p>Select interest groups that you want to add automatically or allow users to choose in the form.</p> <p>If no interest groups are available, either mailing list has not been selected yet in the field above or you have no interest groups created for this list.</p>', 'chimpy'),
                    'chimpy_form_group_method'              => __('<p>Select how you would like interest groups to work with this form - you can either add all selected interest groups to subscribers profile by default or allow your visitors to manually select some.</p>', 'chimpy'),
                    'form_condition_key'                    => __('<p>Controls on which parts of the website this form will appear (or not appear).</p>', 'chimpy'),
                    'form_condition_value_pages'            => __('<p>List of pages to check current page against.</p>', 'chimpy'),
                    'form_condition_value_posts'            => __('<p>List of posts to check current post against.</p>', 'chimpy'),
                    'form_condition_value_post_categories'  => __('<p>List of post categories to check current post against.</p>', 'chimpy'),
                    'form_condition_value_url'              => __('<p>URL fragment to search in the URL of the page.</p>', 'chimpy'),
                );

            // Pass variables to JavaScript
            ?>
                <script>
                    var chimpy_hints = <?php echo json_encode($this->hints); ?>;
                    var chimpy_forms_hints = <?php echo json_encode($forms_page_hints); ?>;
                    var chimpy_home_url = '<?php echo site_url(); ?>';
                    var chimpy_label_still_connecting_to_mailchimp = '<?php _e('Still connecting to MailChimp...', 'chimpy'); ?>';
                    var chimpy_label_mailing_list = '<?php _e('Mailing list', 'chimpy'); ?>';
                    var chimpy_label_no_results_match_list = '<?php _e('There are no lists named', 'chimpy'); ?>';
                    var chimpy_label_select_mailing_list = '<?php _e('Select a mailing list', 'chimpy'); ?>';
                    var chimpy_label_no_results_match_groups = '<?php _e('Selected list does not have groups named', 'chimpy'); ?>';
                    var chimpy_label_select_some_groups = '<?php _e('Select some groups (optional)', 'chimpy'); ?>';
                    var chimpy_label_groups = '<?php _e('Interest groups', 'chimpy'); ?>';
                    var chimpy_label_fields_name = '<?php _e('Field Label', 'chimpy'); ?>';
                    var chimpy_label_fields_tag = '<?php _e('MailChimp Tag', 'chimpy'); ?>';
                    var chimpy_label_fields_icon = '<?php _e('Icon', 'chimpy'); ?>';
                    var chimpy_label_add_new = '<?php _e('Add Field', 'chimpy'); ?>';
                    var chimpy_label_no_results_match_tags = '<?php _e('Selected list does not have tags named', 'chimpy'); ?>';
                    var chimpy_label_select_tag = '<?php _e('Select a tag', 'chimpy'); ?>';
                    var chimpy_label_connecting_to_mailchimp = '<?php _e('Connecting to MailChimp...', 'chimpy'); ?>';
                    var chimpy_label_no_results_match_pages = '<?php _e('No pages named', 'chimpy'); ?>';
                    var chimpy_label_select_some_pages = '<?php _e('Select some pages', 'chimpy'); ?>';
                    var chimpy_label_no_results_match_posts = '<?php _e('No posts named', 'chimpy'); ?>';
                    var chimpy_label_select_some_posts = '<?php _e('Select some posts', 'chimpy'); ?>';
                    var chimpy_label_no_results_match_post_categories = '<?php _e('No post categories named', 'chimpy'); ?>';
                    var chimpy_label_select_some_post_categories = '<?php _e('Select some post categories', 'chimpy'); ?>';
                    var chimpy_label_no_results_match_forms = '<?php _e('No forms named', 'chimpy'); ?>';
                    var chimpy_label_select_some_forms = '<?php _e('Select some forms (optional)', 'chimpy'); ?>';
                    var chimpy_label_signup_form_no = '<?php _e('Signup Form #', 'chimpy'); ?>';
                    var chimpy_label_email = '<?php _e('Email', 'chimpy'); ?>';
                    var chimpy_label_button = '<?php _e('Submit', 'chimpy'); ?>';
                    var chimpy_font_awesome_icons = <?php echo json_encode($this->get_font_awesome_icons()); ?>;
                    var chimpy_label_bad_ajax_response = '<?php printf(__('%s Response received from your server is <a href="%s" target="_blank">malformed</a>.', 'chimpy'), '<i class="fa fa-times" style="font-size: 1.5em; color: red;"></i>&nbsp;&nbsp;&nbsp;', 'http://support.rightpress.net/hc/en-us/articles/201670123'); ?>';
                    var chimpy_label_integration_status = '<?php _e('Integration status', 'chimpy'); ?>';

                    <?php if ($current_tab == 'forms'): ?>
                        var chimpy_selected_lists = <?php echo json_encode($chimpy_selected_lists); ?>;
                    <?php endif; ?>

                    <?php if ($current_tab == 'checkboxes'): ?>
                        var chimpy_selected_list = '<?php echo (isset($this->opt['chimpy_checkbox_list']) ? $this->opt['chimpy_checkbox_list'] : ''); ?>';
                    <?php endif; ?>

                    <?php if ($current_tab == 'sync'): ?>
                        var chimpy_selected_list = '<?php echo (isset($this->opt['chimpy_sync_list']) ? $this->opt['chimpy_sync_list'] : ''); ?>';
                    <?php endif; ?>

                 </script>
            <?php
        }

        /**
         * Render section info
         * 
         * @access public
         * @param array $section
         * @return void
         */
        public function render_section_info($section)
        {
            if (isset($this->section_info[$section['id']])) {
                echo $this->section_info[$section['id']];
            }

            if ($section['id'] == 'forms') {

                if (!$this->opt['chimpy_api_key']) {
                    ?>
                    <div class="chimpy-forms">
                        <p><?php printf(__('You must <a href="%s">enter</a> your MailChimp API key to use this feature.', 'chimpy'), admin_url('/options-general.php?page=chimpy&tab=settings')); ?></p>
                    </div>
                    <?php
                }
                else {

                    /**
                     * Load list of all pages
                     */
                    $pages = array('' => '');

                    $pages_raw = get_posts(array(
                        'posts_per_page'    => -1,
                        'post_type'         => 'page',
                        'post_status'       => 'publish'
                    ));

                    foreach ($pages_raw as $post_key => $post) {
                        $post_name = $post->post_title;

                        if ($post->post_parent) {
                            $parent_id = $post->post_parent;
                            $has_parent = true;

                            // Count iterations to make sure while does not get insane and crash the page
                            $post_iterations = array();

                            while ($has_parent) {

                                // Track iteration count
                                if (!isset($post_iterations[$parent_id])) {
                                    $post_iterations[$parent_id] = 1;
                                }
                                else {
                                    $post_iterations[$parent_id]++;

                                    if ($post_iterations[$parent_id] > 100) {
                                        break;
                                    }
                                }

                                foreach ($pages_raw as $parent_post_key => $parent_post) {
                                    if ($parent_post->ID == $parent_id) {
                                        $post_name = $parent_post->post_title . ' &rarr; ' . $post_name;

                                        if ($parent_post->post_parent) {
                                            $parent_id = $parent_post->post_parent;
                                        }
                                        else {
                                            $has_parent = false;
                                        }

                                        break;
                                    }
                                }
                            }
                        }

                        $pages[$post->ID] = $post_name;
                    }

                    /**
                     * Load list of all posts
                     */
                    $posts = array('' => '');

                    $posts_raw = get_posts(array(
                        'posts_per_page'    => -1,
                        'post_type'         => 'post',
                        'post_status'       => 'publish'
                    ));

                    foreach ($posts_raw as $post_key => $post) {
                        $post_name = $post->post_title;
                        $posts[$post->ID] = $post_name;
                    }

                    /**
                     * Load list of all post categories
                     */
                    $post_categories = array('' => '');

                    $post_categories_raw = get_categories(array(
                        'type'          => 'post',
                        'hide_empty'    => 0,
                    ));

                    foreach ($post_categories_raw as $post_cat_key => $post_cat) {
                        $category_name = $post_cat->name;

                        if ($post_cat->parent) {
                            $parent_id = $post_cat->parent;
                            $has_parent = true;

                            while ($has_parent) {
                                foreach ($post_categories_raw as $parent_post_cat_key => $parent_post_cat) {
                                    if ($parent_post_cat->term_id == $parent_id) {
                                        $category_name = $parent_post_cat->name . ' &rarr; ' . $category_name;

                                        if ($parent_post_cat->parent) {
                                            $parent_id = $parent_post_cat->parent;
                                        }
                                        else {
                                            $has_parent = false;
                                        }

                                        break;
                                    }
                                }
                            }
                        }

                        $post_categories[$post_cat->term_id] = $category_name;
                    }

                    /**
                     * Available assignment to groups methods
                     */
                    $group_methods = array(
                        array(
                            'title'     => __('Automatically', 'chimpy'),
                            'children'  => array(
                                'auto'  => __('All groups selected above', 'chimpy'),
                            ),
                        ),
                        array(
                            'title'     => __('Allow users to select (optional)', 'chimpy'),
                            'children'  => array(
                                'multi'         => __('Checkbox group for each grouping', 'chimpy'),
                                'single'        => __('Radio button group for each grouping', 'chimpy'),
                                'select'        => __('Select field (dropdown) for each grouping', 'chimpy'),
                            ),
                        ),
                        array(
                            'title'     => __('Require users to select (required)', 'chimpy'),
                            'children'  => array(
                                'single_req'    => __('Radio button group for each grouping', 'chimpy'),
                                'select_req'    => __('Select field (dropdown) for each grouping', 'chimpy'),
                            ),
                        )
                    );

                    /**
                     * Available conditions
                     */
                    $condition_options = array(
                        array(
                            'title'     => __('No condition', 'chimpy'),
                            'children'  => array(
                                'always'        => __('Always display this form', 'chimpy'),
                                'disable'       => __('Disable this form', 'chimpy'),
                            ),
                        ),
                        array(
                            'title'     => __('Conditions', 'chimpy'),
                            'children'  => array(
                                'front'         => __('Front page only', 'chimpy'),
                                'pages'         => __('Specific pages', 'chimpy'),
                                'posts'         => __('Specific posts', 'chimpy'),
                                'categories'    => __('Specific post categories', 'chimpy'),
                                'url'           => __('URL contains', 'chimpy'),
                            ),
                        ),
                        array(
                            'title'     => __('Inversed Conditions', 'chimpy'),
                            'children'  => array(
                                'pages_not'         => __('Pages not', 'chimpy'),
                                'posts_not'         => __('Posts not', 'chimpy'),
                                'categories_not'    => __('Post categories not', 'chimpy'),
                                'url_not'           => __('URL does not contain', 'chimpy'),
                            ),
                        ),
                    );

                    /**
                     * Available color schemes
                     */
                    $color_schemes = array(
                        'cyan'      => __('Cyan', 'chimpy'),
                        'red'       => __('Red', 'chimpy'),
                        'orange'    => __('Orange', 'chimpy'),
                        'green'     => __('Green', 'chimpy'),
                        'purple'    => __('Purple', 'chimpy'),
                        'pink'      => __('Pink', 'chimpy'),
                        'yellow'    => __('Yellow', 'chimpy'),
                        'blue'      => __('Blue', 'chimpy'),
                        'black'     => __('Black', 'chimpy'),
                    );

                    /**
                     * Load saved forms
                     */
                    if (isset($this->opt['forms']) && is_array($this->opt['forms']) && !empty($this->opt['forms'])) {

                        // Real forms
                        $saved_forms = $this->opt['forms'];

                        // Pass selected properties to Javascript
                        $chimpy_selected_lists = array();

                        foreach ($saved_forms as $form_key => $form) {
                            $chimpy_selected_lists[$form_key] = array(
                                'list'      => $form['list'],
                                'groups'    => $form['groups'],
                                'merge'     => $form['fields']
                            );
                        }
                    }
                    else {

                        // Mockup
                        $saved_forms[1] = array(
                            'title'     => '',
                            'above'     => '',
                            'below'     => '',
                            'list'      => '',
                            'groups'    => array(),
                            'fields'    => array(),
                            'condition' => array(
                                'key'   =>  'always',
                                'value' =>  '',
                            ),
                            'color_scheme'  => 'cyan',
                        );

                        // Pass selected properties to Javascript
                        $chimpy_selected_lists = array();
                    }

                    ?>

                    <div class="chimpy-forms">
                        <div id="chimpy_forms_list">

                        <?php foreach ($saved_forms as $form_key => $form): ?>

                            <div id="chimpy_forms_list_<?php echo $form_key; ?>">
                                <h4 class="chimpy_forms_handle"><span class="chimpy_forms_title" id="chimpy_forms_title_<?php echo $form_key; ?>"><?php _e('Signup Form #', 'chimpy'); ?><?php echo $form_key; ?></span>&nbsp;<span class="chimpy_forms_title_name"><?php echo (!empty($form['title'])) ? '- ' . $form['title'] : ''; ?></span><span class="chimpy_forms_remove" id="chimpy_forms_remove_<?php echo $form_key; ?>" title="<?php _e('Remove', 'chimpy'); ?>"><i class="fa fa-times"></i></span></h4>
                                <div style="clear:both;">

                                    <div class="chimpy_forms_section"><?php _e('Main Settings', 'chimpy'); ?></div>
                                    <table class="form-table"><tbody>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Form title', 'chimpy'); ?></th>
                                            <td><input type="text" id="chimpy_forms_title_field_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][title]" value="<?php echo $form['title']; ?>" class="chimpy-field chimpy_forms_title_field"></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Text above form', 'chimpy'); ?></th>
                                            <td><input type="text" id="chimpy_forms_above_field_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][above]" value="<?php echo $form['above']; ?>" class="chimpy-field chimpy_forms_above_field"></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Text below form', 'chimpy'); ?></th>
                                            <td><input type="text" id="chimpy_forms_below_field_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][below]" value="<?php echo $form['below']; ?>" class="chimpy-field chimpy_forms_below_field"></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Submit button label', 'chimpy'); ?></th>
                                            <td><input type="text" id="chimpy_forms_button_field_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][button]" value="<?php echo $form['button']; ?>" class="chimpy-field chimpy_forms_button_field"></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Color scheme', 'chimpy'); ?></th>
                                            <td><select id="chimpy_forms_color_scheme_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][color_scheme]" class="chimpy-field chimpy_forms_color_scheme">

                                                <?php
                                                    foreach ($color_schemes as $scheme_value => $scheme_title) {
                                                        $is_selected = ((isset($form['color_scheme']) && $form['color_scheme'] == $scheme_value) ? 'selected="selected"' : '');
                                                        echo '<option value="' . $scheme_value . '" ' . $is_selected . '>' . $scheme_title . '</option>';
                                                    }
                                                ?>

                                            </select></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Success redirect URL', 'chimpy'); ?></th>
                                            <td><input type="text" id="chimpy_forms_redirect_url_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][redirect_url]" value="<?php echo $form['redirect_url']; ?>" class="chimpy-field chimpy_forms_redirect_url"></td>
                                        </tr>
                                    </tbody></table>

                                    <div class="chimpy_forms_section">List & Groups</div>
                                    <p id="chimpy_forms_list_<?php echo $form_key; ?>" class="chimpy_loading_list chimpy_forms_field_list_groups">
                                        <span class="chimpy_loading_icon"></span>
                                        <?php _e('Connecting to MailChimp...', 'chimpy'); ?>
                                    </p>
                                    <table class="form-table" style="margin-top: 0px;"><tbody>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Add to groups', 'chimpy'); ?></th>
                                            <td><select id="chimpy_forms_group_method_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][group_method]" class="chimpy-field chimpy_form_group_method">

                                                <?php
                                                    foreach ($group_methods as $group_group) {
                                                        echo '<optgroup label="' . $group_group['title'] . '">';

                                                        foreach ($group_group['children'] as $group_method_value => $group_method_title) {
                                                            $is_selected = (isset($form['group_method']) && $form['group_method'] == $group_method_value) ? 'selected="selected"' : '';
                                                            echo '<option value="' . $group_method_value . '" ' . $is_selected . '>' . $group_method_title . '</option>';
                                                        }

                                                        echo '</optgroup>';
                                                    }
                                                ?>

                                            </select></td>
                                        </tr>
                                    </tbody></table>

                                    <div class="chimpy_forms_section">Form Fields</div>
                                    <p id="chimpy_fields_table_<?php echo $form_key; ?>" class="chimpy_loading_list chimpy_forms_field_fields">
                                        <span class="chimpy_loading_icon"></span>
                                        <?php _e('Connecting to MailChimp...', 'chimpy'); ?>
                                    </p>

                                    <div class="chimpy_forms_section">Conditions</div>
                                    <table class="form-table"><tbody>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Display condition', 'chimpy'); ?></th>
                                            <td><select id="chimpy_forms_condition_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][condition]" class="chimpy-field form_condition_key">

                                                <?php
                                                foreach ($condition_options as $cond_cat) {
                                                    echo '<optgroup label="' . $cond_cat['title'] . '">';

                                                    foreach ($cond_cat['children'] as $cond_value => $cond_title) {
                                                        $is_selected = (is_array($form['condition']) && isset($form['condition']['key']) && $form['condition']['key'] == $cond_value) ? 'selected="selected"' : '';
                                                        echo '<option value="' . $cond_value . '" ' . $is_selected . '>' . $cond_title . '</option>';
                                                    }

                                                    echo '</optgroup>';
                                                }
                                                ?>

                                            </select></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Pages', 'chimpy'); ?></th>
                                            <td><select multiple id="chimpy_forms_condition_pages_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][condition_pages][]" class="chimpy-field form_condition_value form_condition_value_pages form_condition_value_pages_not">

                                                <?php
                                                    foreach ($pages as $key => $name) {
                                                        $is_selected = (is_array($form['condition']) && isset($form['condition']['key']) && in_array($form['condition']['key'], array('pages', 'pages_not')) && isset($form['condition']['value']) && is_array($form['condition']['value']) && in_array($key, $form['condition']['value'])) ? 'selected="selected"' : '';
                                                        echo '<option value="' . $key . '" ' . $is_selected . '>' . $name . '</option>';
                                                    }
                                                ?>

                                            </select></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Posts', 'chimpy'); ?></th>
                                            <td><select multiple id="chimpy_forms_condition_posts_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][condition_posts][]" class="chimpy-field form_condition_value form_condition_value_posts form_condition_value_posts_not">

                                                <?php
                                                    foreach ($posts as $key => $name) {
                                                        $is_selected = (is_array($form['condition']) && isset($form['condition']['key']) && in_array($form['condition']['key'], array('posts', 'posts_not')) && isset($form['condition']['value']) && is_array($form['condition']['value']) && in_array($key, $form['condition']['value'])) ? 'selected="selected"' : '';
                                                        echo '<option value="' . $key . '" ' . $is_selected . '>' . $name . '</option>';
                                                    }
                                                ?>

                                            </select></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Post categories', 'chimpy'); ?></th>
                                            <td><select multiple id="chimpy_forms_condition_categories_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][condition_categories][]" class="chimpy-field form_condition_value form_condition_value_categories form_condition_value_categories_not">

                                                <?php
                                                    foreach ($post_categories as $key => $name) {
                                                        $is_selected = (is_array($form['condition']) && isset($form['condition']['key']) && in_array($form['condition']['key'], array('categories', 'categories_not')) && isset($form['condition']['value']) && is_array($form['condition']['value']) && in_array($key, $form['condition']['value'])) ? 'selected="selected"' : '';
                                                        echo '<option value="' . $key . '" ' . $is_selected . '>' . $name . '</option>';
                                                    }
                                                ?>

                                            </select></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php _e('URL fragment', 'chimpy'); ?></th>
                                            <td><input type="text" id="chimpy_forms_condition_url_<?php echo $form_key; ?>" name="chimpy_options[forms][<?php echo $form_key; ?>][condition_url]" value="<?php echo ((isset($form['condition']['key']) && in_array($form['condition']['key'], array('url', 'url_not')) && isset($form['condition']['value'])) ? $form['condition']['value'] : ''); ?>" class="chimpy-field form_condition_value form_condition_value_url form_condition_value_url_not"></td>
                                        </tr>
                                    </tbody></table>

                                </div>
                                <div style="clear: both;"></div>
                            </div>

                        <?php endforeach; ?>

                        </div>
                        <div>
                            <button type="button" name="chimpy_add_set" id="chimpy_add_set" disabled="disabled" class="button button-primary" value="<?php _e('Add Form', 'chimpy'); ?>" title="<?php _e('Still connecting to MailChimp...', 'chimpy'); ?>"><i class="fa fa-plus">&nbsp;&nbsp;<?php _e('Add Form', 'chimpy'); ?></i></button>
                            <div style="clear: both;"></div>
                        </div>
                    </div>

                    <?php

                }
            }
            else if ($section['id'] == 'help_display') {
                echo '<div class="chimpy-section-info"><p style="line-height: 160%;">'
                   . __('You can create multiple newsletter signup forms on the "Forms" page. Each can be displayed in one of the following ways:', 'chimpy')
                   . '</p><p style="line-height: 160%;">'
                   . '<strong style="color:#222;">' . __('Widget', 'chimpy') . '</strong><br />'
                   . sprintf(__('Display any form on a sidebar of your choice by using a standard WordPress Widget. Go to the <a href="%s">Widgets page</a> and use the one named MailChimp Signup (Chimpy). You can add as many instances of this widget as you like. You can optionally specify a comma-separated list of form IDs to limit which forms can be displayed on that particular spot.', 'chimpy'), admin_url('/widgets.php'))
                   . '</p><p style="line-height: 160%;">'
                   . '<strong style="color:#222;">' . __('Shortcode', 'chimpy') . ' <code>[chimpy_form]</code></strong><br />'
                   . __('Insert any a form into individual posts or pages by placing a shortcode anywhere in the content. To limit which forms can be displayed here, add a parameter <code>forms</code> with a comma-separated list of form IDs, e.g. <code>[chimpy_form forms="1,3,4"]</code>', 'chimpy')
                   . '</p><p style="line-height: 160%;">'
                   . '<strong style="color:#222;">' . __('Function', 'chimpy') . ' <code>chimpy_form()</code></strong><br />'
                   . __('To display a form in nonstandard parts of your website, take advantage of the PHP function <code>chimpy_form()</code>. To limit which forms can be displayed at that particular spot, pass a list of allowed form IDs in an array, e.g. <code>chimpy_form(array(\'1\', \'3\', \'4\'))</code>', 'chimpy')
                   . '</p><p style="line-height: 160%;">'
                   . '<strong style="color:#222;">' . __('Popup', 'chimpy') . '</strong><br />'
                   . sprintf(__('Increase the signup rate by displaying one of your signup forms in a <a href="%s">popup</a>. If you wish popup to be opened on click and not automatically on page load, simply assign ID <code>chimpy_popup_open</code> to the element that you want to bind it to.', 'chimpy'), admin_url('/options-general.php?page=chimpy&tab=popup'))
                   . '</p><p style="line-height: 160%;">'
                   . '<strong style="color:#222;">' . __('Under Post Content', 'chimpy') . '</strong><br />'
                   . sprintf(__('You can easily configure this plugin to display signup forms under each <a href="%s">post</a> that you publish. You can exclude (or include only particular posts) by setting appropriate form displaying conditions.', 'chimpy'), admin_url('/options-general.php?page=chimpy&tab=below'))
                   . '</p><p style="line-height: 160%;">'
                   . '<strong style="color:#222;">' . __('As Content Lock', 'chimpy') . '</strong><br />'
                   . sprintf(__('If you have valuable content that your visitors are after, you may wish to <a href="%s">lock</a> some of it so only visitors who subscribe to your mailing list can access it.', 'chimpy'), admin_url('/options-general.php?page=chimpy&tab=lock'))
                   . '</p></div>';
            }
            else if ($section['id'] == 'help_contact') {
                echo '<div class="chimpy-section-info"><p>'
                   . sprintf(__('If you\'ve got any questions, feel free to visit our <a href="%s">support center</a> or submit a <a href="%s">support ticket</a>.', 'chimpy'), 'http://support.rightpress.net/hc/en-us/categories/200094438-Chimpy', 'http://support.rightpress.net/hc/en-us/requests/new')
                   . '</p><p>'
                   . '</p></div>';
            }

        }

        /*
         * Render a text field
         * 
         * @access public
         * @param array $args
         * @return void
         */
        public function render_options_text($args = array())
        {
            printf(
                '<input type="text" id="%s" name="chimpy_options[%s]" value="%s" class="chimpy-field" />',
                $args['name'],
                $args['name'],
                $args['options'][$args['name']]
            );
        }

        /*
         * Render a text area
         * 
         * @access public
         * @param array $args
         * @return void
         */
        public function render_options_textarea($args = array())
        {
            printf(
                '<textarea id="%s" name="chimpy_options[%s]" class="chimpy-textarea">%s</textarea>',
                $args['name'],
                $args['name'],
                $args['options'][$args['name']]
            );
        }

        /*
         * Render a checkbox
         * 
         * @access public
         * @param array $args
         * @return void
         */
        public function render_options_checkbox($args = array())
        {
            printf(
                '<input type="checkbox" id="%s" name="chimpy_options[%s]" value="1" %s />',
                $args['name'],
                $args['name'],
                checked($args['options'][$args['name']], true, false)
            );
        }

        /*
         * Render a set of checkboxes
         * 
         * @access public
         * @param array $args
         * @return void
         */
        public function render_options_checkbox_set($args = array())
        {
            echo '<ul style="margin-top:7px;">';

            foreach ($this->options[$args['name']] as $key => $name) {

                echo '<li>';

                $is_checked = false;

                if (isset($args['options'][$args['name']]) && is_array($args['options'][$args['name']])) {
                    $is_checked = in_array($key, $args['options'][$args['name']]) ? true : false;
                }

                printf(
                    '<input type="checkbox" id="%s_%s" name="chimpy_options[%s][]" value="%s" %s />',
                    $args['name'],
                    $key,
                    $args['name'],
                    $key,
                    checked($is_checked, true, false)
                );

                echo $name . '</li>';
            }

            echo '</ul>';
        }

        /*
         * Render a dropdown
         * 
         * @access public
         * @param array $args
         * @return void
         */
        public function render_options_dropdown($args = array())
        {
            printf(
                '<select id="%s" name="chimpy_options[%s]" class="chimpy-field">',
                $args['name'],
                $args['name']
            );

            foreach ($this->options[$args['name']] as $key => $name) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    $key,
                    selected($key, $args['options'][$args['name']], false),
                    $name
                );
            }

            echo '</select>';
        }

        /*
         * Render a multi-select dropdown
         * 
         * @access public
         * @param array $args
         * @return void
         */
        public function render_options_dropdown_multi($args = array())
        {
            printf(
                '<select multiple id="%s" name="chimpy_options[%s][]" class="chimpy-field">',
                $args['name'],
                $args['name']
            );

            foreach ($this->options[$args['name']] as $key => $name) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    $key,
                    (in_array($key, $args['options'][$args['name']]) ? 'selected="selected"' : ''),
                    $name
                );
            }

            echo '</select>';
        }

        /*
         * Render a password field
         * 
         * @access public
         * @param array $args
         * @return void
         */
        public function render_options_password($args = array())
        {
            printf(
                '<input type="password" id="%s" name="chimpy_options[%s]" value="%s" class="chimpy-field" />',
                $args['name'],
                $args['name'],
                $args['options'][$args['name']]
            );
        }

        /**
         * Validate admin form input
         * 
         * @access public
         * @param array $input
         * @return array
         */
        public function options_validate($input)
        {
            $current_tab = isset($_POST['current_tab']) ? $_POST['current_tab'] : 'settings';
            $output = $original = $this->get_options();

            $revert = array();
            $errors = array();

            // Validate forms
            if ($current_tab == 'forms') {
                if (isset($input['forms']) && !empty($input['forms'])) {

                    $new_forms = array();
                    $form_number = 0;

                    foreach ($input['forms'] as $form) {

                        $form_number++;

                        $new_forms[$form_number] = array();

                        // Title
                        $new_forms[$form_number]['title'] = (isset($form['title']) && !empty($form['title'])) ? $form['title']: '';

                        // Above
                        $new_forms[$form_number]['above'] = (isset($form['above']) && !empty($form['above'])) ? $form['above']: '';

                        // Below
                        $new_forms[$form_number]['below'] = (isset($form['below']) && !empty($form['below'])) ? $form['below']: '';

                        // Button
                        $new_forms[$form_number]['button'] = (isset($form['button']) && !empty($form['button'])) ? $form['button']: '';

                        // Redirect URL
                        $new_forms[$form_number]['redirect_url'] = (isset($form['redirect_url']) && !empty($form['redirect_url'])) ? $form['redirect_url']: '';

                        // List
                        $new_forms[$form_number]['list'] = (isset($form['list_field']) && !empty($form['list_field'])) ? $form['list_field']: '';

                        // Groups
                        $new_forms[$form_number]['groups'] = array();

                        if (isset($form['groups']) && is_array($form['groups'])) {
                            foreach ($form['groups'] as $group) {
                                $new_forms[$form_number]['groups'][] = htmlspecialchars($group, ENT_QUOTES);
                            }
                        }

                        // Group method
                        if (isset($form['group_method']) && in_array($form['group_method'], array('auto', 'single', 'single_req', 'multi', 'select', 'select_req'))) {
                            $new_forms[$form_number]['group_method'] = $form['group_method'];
                        }
                        else {
                            $new_forms[$form_number]['group_method'] = 'auto';
                        }

                        // Fields
                        $new_forms[$form_number]['fields'] = array();

                        if (isset($form['fields']) && is_array($form['fields'])) {

                            $field_number = 0;

                            foreach ($form['fields'] as $field) {

                                if (!is_array($field) || !isset($field['name']) || !isset($field['tag']) || empty($field['tag'])) {
                                    continue;
                                }

                                $field_number++;

                                $new_forms[$form_number]['fields'][$field_number] = array(
                                    'name'      => $field['name'],
                                    'tag'       => $field['tag'],
                                    'icon'      => $field['icon'],
                                    'type'      => $field['type'],
                                    'req'       => ($field['req'] == 'true' ? true : false),
                                    'us_phone'  => ($field['us_phone'] == 'true' ? true : false),
                                );

                                if (isset($field['choices']) && !empty($field['choices'])) {
                                    $new_forms[$form_number]['fields'][$field_number]['choices'] = preg_split('/%%%/', $field['choices']);
                                }
                                else {
                                    $new_forms[$form_number]['fields'][$field_number]['choices'] = array();
                                }
                            }
                        }

                        // Condition
                        $new_forms[$form_number]['condition'] = array();
                        $new_forms[$form_number]['condition']['key'] = (isset($form['condition']) && !empty($form['condition'])) ? $form['condition']: 'always';

                        // Condition value
                        if (in_array($new_forms[$form_number]['condition']['key'], array('pages', 'pages_not'))) {
                            if (isset($form['condition_pages']) && is_array($form['condition_pages']) && !empty($form['condition_pages'])) {
                                foreach ($form['condition_pages'] as $condition_item) {
                                    if (empty($condition_item)) {
                                        continue;
                                    }

                                    $new_forms[$form_number]['condition']['value'][] = $condition_item;
                                }
                            }
                            else {
                                $new_forms[$form_number]['condition']['key'] = 'always';
                                $new_forms[$form_number]['condition']['value'] = '';
                            }
                        }
                        else if (in_array($new_forms[$form_number]['condition']['key'], array('posts', 'posts_not'))) {
                            if (isset($form['condition_posts']) && is_array($form['condition_posts']) && !empty($form['condition_posts'])) {
                                foreach ($form['condition_posts'] as $condition_item) {
                                    if (empty($condition_item)) {
                                        continue;
                                    }

                                    $new_forms[$form_number]['condition']['value'][] = $condition_item;
                                }
                            }
                            else {
                                $new_forms[$form_number]['condition']['key'] = 'always';
                                $new_forms[$form_number]['condition']['value'] = '';
                            }
                        }
                        else if (in_array($new_forms[$form_number]['condition']['key'], array('categories', 'categories_not'))) {
                            if (isset($form['condition_categories']) && is_array($form['condition_categories']) && !empty($form['condition_categories'])) {
                                foreach ($form['condition_categories'] as $condition_item) {
                                    if (empty($condition_item)) {
                                        continue;
                                    }

                                    $new_forms[$form_number]['condition']['value'][] = $condition_item;
                                }
                            }
                            else {
                                $new_forms[$form_number]['condition']['key'] = 'always';
                                $new_forms[$form_number]['condition']['value'] = '';
                            }
                        }
                        else if (in_array($new_forms[$form_number]['condition']['key'], array('url', 'url_not'))) {
                            if (isset($form['condition_url']) && !empty($form['condition_url'])) {
                                $new_forms[$form_number]['condition']['value'] = $form['condition_url'];
                            }
                            else {
                                $new_forms[$form_number]['condition']['key'] = 'always';
                                $new_forms[$form_number]['condition']['value'] = '';
                            }
                        }
                        else {
                            $new_forms[$form_number]['condition']['value'] = '';
                        }

                        // Color scheme
                        $new_forms[$form_number]['color_scheme'] = (isset($form['color_scheme']) && !empty($form['color_scheme'])) ? $form['color_scheme']: 'cyan';

                    }
                }

                $output['forms'] = $new_forms;
            }

            // Validate other content
            else {

                // Iterate over fields and validate/sanitize input
                foreach ($this->validation[$current_tab] as $field => $rule) {

                    $allow_empty = true;

                    // Conditional validation
                    if (is_array($rule['empty']) && !empty($rule['empty'])) {
                        if (isset($input['chimpy_' . $rule['empty'][0]]) && ($input['chimpy_' . $rule['empty'][0]] != '0')) {
                            $allow_empty = false;
                        }
                    }
                    else if ($rule['empty'] == false) {
                        $allow_empty = false;
                    }

                    // Different routines for different field types
                    switch($rule['rule']) {

                        // Validate numbers
                        case 'number':
                            if (is_numeric($input[$field]) || ($input[$field] == '' && $allow_empty)) {
                                $output[$field] = $input[$field];
                            }
                            else {
                                if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                    $revert[$rule['empty'][0]] = '0';
                                }
                                array_push($errors, array('setting' => $field, 'code' => 'number'));
                            }
                            break;

                        // Validate boolean values (actually 1 and 0)
                        case 'bool':
                            $input[$field] = $input[$field] == '' ? '0' : $input[$field];
                            if (in_array($input[$field], array('0', '1')) || ($input[$field] == '' && $allow_empty)) {
                                $output[$field] = $input[$field];
                            }
                            else {
                                if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                    $revert[$rule['empty'][0]] = '0';
                                }
                                array_push($errors, array('setting' => $field, 'code' => 'bool'));
                            }
                            break;

                        // Validate predefined options
                        case 'option':

                            // Check if this call is for mailing lists
                            if ($field == 'chimpy_list_checkout') {
                                //$this->options[$field] = $this->get_lists();
                                if (is_array($rule['empty']) && !empty($rule['empty']) && $input['chimpy_'.$rule['empty'][0]] != '1' && (empty($input[$field]) || $input[$field] == '0')) {
                                    if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                        $revert[$rule['empty'][0]] = '1';
                                    }
                                    array_push($errors, array('setting' => $field, 'code' => 'option'));
                                }
                                else {
                                    $output[$field] = ($input[$field] == null ? '0' : $input[$field]);
                                }

                                break;
                            }
                            else if (in_array($field, array('chimpy_list_widget', 'chimpy_list_shortcode'))) {
                                //$this->options[$field] = $this->get_lists();
                                if (is_array($rule['empty']) && !empty($rule['empty']) && $input['chimpy_'.$rule['empty'][0]] != '0' && (empty($input[$field]) || $input[$field] == '0')) {
                                    if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                        $revert[$rule['empty'][0]] = '0';
                                    }
                                    array_push($errors, array('setting' => $field, 'code' => 'option'));
                                }
                                else {
                                    $output[$field] = ($input[$field] == null ? '0' : $input[$field]);
                                }

                                break;
                            }

                            if (isset($this->options[$field][$input[$field]]) || ($input[$field] == '' && $allow_empty)) {
                                $output[$field] = ($input[$field] == null ? '0' : $input[$field]);
                            }
                            else {
                                if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                    $revert[$rule['empty'][0]] = '0';
                                }
                                array_push($errors, array('setting' => $field, 'code' => 'option'));
                            }
                            break;

                        // Multiple selections
                        case 'multiple_any':
                            if (empty($input[$field]) && !$allow_empty) {
                                if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                    $revert[$rule['empty'][0]] = '0';
                                }
                                array_push($errors, array('setting' => $field, 'code' => 'multiple_any'));
                            }
                            else {
                                if (is_array($input[$field]) && !empty($input[$field])) {
                                    $temporary_output = array();

                                    foreach ($input[$field] as $field_val) {
                                        $temporary_output[] = htmlspecialchars($field_val, ENT_QUOTES);
                                    }

                                    $output[$field] = $temporary_output;
                                }
                                else {
                                    $output[$field] = array();
                                }
                            }
                            break;

                        // Validate emails
                        case 'email':
                            if (filter_var(trim($input[$field]), FILTER_VALIDATE_EMAIL) || ($input[$field] == '' && $allow_empty)) {
                                $output[$field] = esc_attr(trim($input[$field]));
                            }
                            else {
                                if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                    $revert[$rule['empty'][0]] = '0';
                                }
                                array_push($errors, array('setting' => $field, 'code' => 'email'));
                            }
                            break;

                        // Validate URLs
                        case 'url':
                            // FILTER_VALIDATE_URL for filter_var() does not work as expected
                            if (($input[$field] == '' && !$allow_empty)) {
                                if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                    $revert[$rule['empty'][0]] = '0';
                                }
                                array_push($errors, array('setting' => $field, 'code' => 'url'));
                            }
                            else {
                                $output[$field] = esc_attr(trim($input[$field]));
                            }
                            break;

                        // Custom validation function
                        case 'function':
                            $function_name = 'validate_' . $field;
                            $validation_results = $this->$function_name($input[$field]);

                            // Check if parent is disabled - do not validate then and reset to ''
                            if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                if (empty($input['chimpy_'.$rule['empty'][0]])) {
                                    $output[$field] = '';
                                    break;
                                }
                            }

                            if (($input[$field] == '' && $allow_empty) || $validation_results === true) {
                                $output[$field] = $input[$field];
                            }
                            else {
                                if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                    $revert[$rule['empty'][0]] = '0';
                                }
                                array_push($errors, array('setting' => $field, 'code' => 'option', 'custom' => $validation_results));
                            }
                            break;

                        // Default validation rule (text fields etc)
                        default:
                            if (($input[$field] == '' && !$allow_empty)) {
                                if (is_array($rule['empty']) && !empty($rule['empty'])) {
                                    $revert[$rule['empty'][0]] = '0';
                                }
                                array_push($errors, array('setting' => $field, 'code' => 'string'));
                            }
                            else {
                                $output[$field] = esc_attr(trim($input[$field]));
                            }
                            break;
                    }
                }

                // Revert parent fields if needed
                if (!empty($revert)) {
                    foreach ($revert as $key => $value) {
                        $output['chimpy_'.$key] = $value;
                    }
                }

            }

            // Display settings updated message
            add_settings_error(
                'chimpy_settings_updated',
                'chimpy_settings_updated',
                __('Your settings have been saved.', 'chimpy'),
                'updated'
            );

            // Define error messages
            $messages = array(
                'number' => __('must be numeric', 'chimpy'),
                'bool' => __('must be either 0 or 1', 'chimpy'),
                'option' => __('is not allowed', 'chimpy'),
                'email' => __('is not a valid email address', 'chimpy'),
                'url' => __('is not a valid URL', 'chimpy'),
                'string' => __('is not a valid text string', 'chimpy'),
            );

            // Display errors
            foreach ($errors as $error) {

                $message = (!isset($error['custom']) ? $messages[$error['code']] : $error['custom']) . '. ' . __('Reverted to a previous state.', 'chimpy');

                add_settings_error(
                    'chimpy_settings_updated',
                    //$error['setting'],
                    $error['code'],
                    __('Value of', 'chimpy') . ' "' . $this->titles[$error['setting']] . '" ' . $message
                );
            }

            return $output;
        }

        /**
         * Custom validation for service provider API key
         * 
         * @access public
         * @param string $key
         * @return mixed
         */
        public function validate_chimpy_api_key($key)
        {
            if (empty($key)) {
                return 'is empty';
            }

            $test_results = $this->test_mailchimp($key);

            if ($test_results === true) {
                return true;
            }
            else {
                return __(' is not valid or something went wrong. More details: ', 'chimpy') . $test_results;
            }
        }

        /**
         * Custom validation for allowed forms after posts
         * 
         * @access public
         * @param string $value
         * @return mixed
         */
        public function validate_chimpy_after_posts_allowed_forms($value)
        {
            if (empty($value)) {
                return true;
            }

            if (preg_match('/^([0-9]+,?)+$/', $value)) {
                return true;
            }
            else {
                return __(' is not in a valid format', 'chimpy');
            }
        }

        /**
         * Load scripts required for admin
         * 
         * @access public
         * @return void
         */
        public function enqueue_admin_scripts_and_styles()
        {
            // Custom jQuery UI script and its styles
            //wp_register_script('chimpy-jquery-ui', CHIMPY_PLUGIN_URL . '/assets/jquery-ui/js/jquery-ui-1.10.3.custom.min.js', array('jquery'), '1.10.3');
            wp_register_style('chimpy-jquery-ui-styles', CHIMPY_PLUGIN_URL . '/assets/jquery-ui/css/jquery-ui-1.10.3.custom.min.css', array(), CHIMPY_VERSION);

            // Chosen scripts and styles (advanced form fields)
            wp_register_script('jquery-chimpy-chosen', CHIMPY_PLUGIN_URL . '/assets/js/chosen.jquery.js', array('jquery'), '1.0.0');
            wp_register_style('jquery-chimpy-chosen-css', CHIMPY_PLUGIN_URL . '/assets/css/chosen.min.css', array(), CHIMPY_VERSION);

            // Font awesome (icons)
            wp_register_style('chimpy-font-awesome', CHIMPY_PLUGIN_URL . '/assets/css/font-awesome/css/font-awesome.min.css', array(), '4.0.3');

            // Our own scripts and styles
            wp_register_script('chimpy-admin-scripts', CHIMPY_PLUGIN_URL . '/assets/js/chimpy-admin.js', array('jquery'), CHIMPY_VERSION);
            wp_register_style('chimpy-admin-styles', CHIMPY_PLUGIN_URL . '/assets/css/style-admin.css', array(), CHIMPY_VERSION);

            // Scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-accordion');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script('jquery-chimpy-chosen');
            wp_enqueue_script('chimpy-admin-scripts');

            // Styles
            wp_enqueue_style('chimpy-jquery-ui-styles');
            wp_enqueue_style('jquery-chimpy-chosen-css');
            wp_enqueue_style('chimpy-font-awesome');
            wp_enqueue_style('chimpy-admin-styles');
        }

        /**
         * Load scripts required for frontend
         * 
         * @access public
         * @return void
         */
        public function enqueue_frontend_scripts_and_styles()
        {
            if (!$this->opt['chimpy_api_key'] || !isset($this->opt['forms']) || empty($this->opt['forms'])) {
                return;
            }

            // jQuery cookie if not yet enqueued
            if (!wp_script_is('jquery-cookie', 'enqueued')) {
                wp_register_script('jquery-cookie', CHIMPY_PLUGIN_URL . '/assets/js/jquery.cookie.js', array('jquery'), '1.4');
                wp_enqueue_script('jquery-cookie');
            }

            // Chimpy frontend scripts
            wp_register_script('chimpy-frontend', CHIMPY_PLUGIN_URL . '/assets/js/chimpy-frontend.js', array('jquery'), CHIMPY_VERSION);
            wp_enqueue_script('chimpy-frontend');

            // Chimpy frontent styles
            wp_register_style('chimpy', CHIMPY_PLUGIN_URL . '/assets/css/style-frontend.css', array(), CHIMPY_VERSION);
            wp_enqueue_style('chimpy');

            // Font awesome (icons)
            wp_register_style('chimpy-font-awesome', CHIMPY_PLUGIN_URL . '/assets/css/font-awesome/css/font-awesome.min.css', array(), '4.0.3');
            wp_enqueue_style('chimpy-font-awesome');

            // Sky Forms scripts
            wp_register_script('chimpy-sky-forms', CHIMPY_PLUGIN_URL . '/assets/forms/js/jquery.form.min.js', array('jquery'), '20130711');
            wp_enqueue_script('chimpy-sky-forms');

            // Sky Forms scripts - validate
            wp_register_script('chimpy-sky-forms-validate', CHIMPY_PLUGIN_URL . '/assets/forms/js/jquery.validate.min.js', array('jquery'), '1.11.0');
            wp_enqueue_script('chimpy-sky-forms-validate');

            // Sky Forms scripts - masked input
            wp_register_script('chimpy-sky-forms-maskedinput', CHIMPY_PLUGIN_URL . '/assets/forms/js/jquery.maskedinput.min.js', array('jquery'), '1.3.1');
            wp_enqueue_script('chimpy-sky-forms-maskedinput');

            // Sky Forms main styles
            wp_register_style('chimpy-sky-forms-style', CHIMPY_PLUGIN_URL . '/assets/forms/css/sky-forms.css', array(), CHIMPY_VERSION);
            wp_enqueue_style('chimpy-sky-forms-style');

            // Sky Forms color schemes
            wp_register_style('chimpy-sky-forms-color-schemes', CHIMPY_PLUGIN_URL . '/assets/forms/css/sky-forms-color-schemes.css', array(), CHIMPY_VERSION);
            wp_enqueue_style('chimpy-sky-forms-color-schemes');

            // Check browser version
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') !== false) {
                $ie = 6;
            }
            else if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.') !== false) {
                $ie = 7;
            }
            else if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.') !== false) {
                $ie = 8;
            }
            else if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.') !== false) {
                $ie = 9;
            }
            else {
                $ie = false;
            }

            // For IE < 9
            if ($ie && $ie < 9) {

                // Additional scripts
                wp_register_script('chimpy-sky-forms-ie8', CHIMPY_PLUGIN_URL . '/assets/forms/js/sky-forms-ie8.js', array('jquery'), CHIMPY_VERSION);
                wp_enqueue_script('chimpy-sky-forms-ie8');

                // Additional styles
                wp_register_style('chimpy-sky-forms-style-ie8', CHIMPY_PLUGIN_URL . '/assets/forms/css/sky-forms-ie8.css', array(), CHIMPY_VERSION);
                wp_enqueue_style('chimpy-sky-forms-style-ie8');
            }

            // For IE < 10
            if ($ie && $ie < 10) {

                // Placeholder
                wp_register_script('chimpy-sky-forms-placeholder', CHIMPY_PLUGIN_URL . '/assets/forms/js/jquery.placeholder.min.js', array('jquery'), CHIMPY_VERSION);
                wp_enqueue_script('chimpy-sky-forms-placeholder');

                // HTM5 Shim
                wp_register_script('chimpy-sky-forms-html5shim', CHIMPY_PLUGIN_URL . '/assets/forms/js/html5.js', array('jquery'), CHIMPY_VERSION);
                wp_enqueue_script('chimpy-sky-forms-html5shim');
            }

        }

        /**
         * Add settings link on plugins page
         * (Note to myself: won't display if plugin is included as a symlink due to __FILE__)
         * 
         * @access public
         * @return void
         */
        public function plugin_settings_link($links, $file)
        {
            if ($file == plugin_basename(__FILE__)){
                $settings_link = '<a href="http://support.rightpress.net/" target="_blank">'.__('Support', 'woo_pdf').'</a>';
                array_unshift($links, $settings_link);
                $settings_link = '<a href="options-general.php?page=chimpy">'.__('Settings', 'chimpy').'</a>';
                array_unshift($links, $settings_link);
            }
            return $links;
        }

        /**
         * Handle plugin uninstall
         * 
         * @access public
         * @return void
         */
        public function uninstall()
        {
            if (defined('WP_UNINSTALL_PLUGIN')) {
                delete_option('chimpy_options');
            }
        }

        /**
         * Get lists from MailChimp
         * 
         * @access public
         * @return boid
         */
        public function ajax_get_lists()
        {
            // Get lists
            $lists = $this->get_lists();
            echo json_encode(array('message' => array('lists' => $lists)));
            die();
        }

        /**
         * Get all lists plus groups and fields for selected lists in array
         * 
         * @access public
         * @return void
         */
        public function ajax_get_lists_groups_fields()
        {
            if (isset($_POST['data'])) {
                $data = $_POST['data'];
            }
            else {
                $data = array();
            }

            // Get lists
            $lists = $this->get_lists();

            // Check if we have something pre-selected
            if (!empty($data)) {

                // Get merge vars
                $merge = $this->get_merge_vars($lists);

                $lists_to_get = array();

                // Get groups
                foreach ($data as $form_key => $form) {
                    $lists_to_get[] = $form['list'];
                }

                $groups = $this->get_groups($lists_to_get);
            }
            else {

                $merge = array();
                $groups = array();

                foreach ($lists as $list_key => $list_value) {

                    if ($list_key == '') {
                        continue;
                    }

                    // Blank merge vars
                    $merge[$list_key] = array('' => array());

                    // Blank groups
                    $groups[$list_key] = array('' => '');
                }
            }

            echo json_encode(array('message' => array('lists' => $lists, 'groups' => $groups, 'merge' => $merge)));
            die();
        }

        /**
         * Return all lists from MailChimp to be used in select fields
         * 
         * @access public
         * @return array
         */
        public function get_lists()
        {
            $this->load_mailchimp();

            try {
                if (!$this->mailchimp) {
                    throw new Exception(__('Unable to load lists', 'chimpy'));
                }

                $lists = $this->mailchimp->lists_get_list();

                if ($lists['total'] < 1) {
                    throw new Exception(__('No lists found', 'chimpy'));
                }

                $results = array('' => '');

                foreach ($lists['data'] as $list) {
                    $results[$list['id']] = $list['name'];
                }

                return $results;
            }
            catch (Exception $e) {
                return array('' => '');
            }
        }

        /**
         * Return all merge vars for all available lists
         * 
         * @access public
         * @param array $lists
         * @return array
         */
        public function get_merge_vars($lists)
        {
            $this->load_mailchimp();

            // Unset blank list
            unset($lists['']);

            // Pre-populate results array with list ids as keys
            $results = array();

            foreach (array_keys($lists) as $list) {
                $results[$list] = array();
            }

            try {

                if (!$this->mailchimp) {
                    throw new Exception(__('Unable to load merge vars', 'chimpy'));
                }

                $merge_vars = $this->mailchimp->lists_merge_vars(array_keys($lists));

                if (!$merge_vars || empty($merge_vars) || !isset($merge_vars['data'])) {
                    throw new Exception(__('No merge vars found', 'chimpy'));
                }

                foreach ($merge_vars['data'] as $merge_var) {
                    foreach ($merge_var['merge_vars'] as $var) {

                        // Skip address field (may be added later)
                        if ($var['field_type'] == 'address') {
                            continue;
                        }

                        $results[$merge_var['id']][$var['tag']] = array(
                            'name'  => $var['name'],
                            'req'   => ($var['req'] ? true : false),
                            'type'  => $var['field_type'],           // enum: email, text, number, radio, dropdown, date, birthday, zip, phone, url
                        );

                        // Add choices (for radio, dropdown)
                        $results[$merge_var['id']][$var['tag']]['choices'] = (isset($var['choices']) && !empty($var['choices'])) ? join('%%%', $var['choices']) : '';

                        // US phone format?
                        $results[$merge_var['id']][$var['tag']]['us_phone'] = isset($var['phoneformat']) && $var['phoneformat'] == 'US' ? true : false;
                    }
                }

                return $results;
            }
            catch (Exception $e) {
                return $results;
            }
        }

        /**
         * Return all groupings/groups from MailChimp to be used in select fields
         * 
         * @access public
         * @param mixed $list_id
         * @return array
         */
        public function get_groups($list_id)
        {
            $this->load_mailchimp();

            try {

                if (!$this->mailchimp) {
                    throw new Exception(__('Unable to load groups', 'chimpy'));
                }

                // Single list?
                if (in_array(gettype($list_id), array('integer', 'string'))) {
                    $groupings = $this->mailchimp->lists_interest_groupings($list_id);

                    if (!$groupings || empty($groupings)) {
                        throw new Exception(__('No groups found', 'chimpy'));
                    }

                    $results = array('' => '');

                    foreach ($groupings as $grouping) {
                        foreach ($grouping['groups'] as $group) {
                            $results[$grouping['id'] . '%%%' . htmlspecialchars($grouping['name'], ENT_QUOTES) . '%%%' . htmlspecialchars($group['name'], ENT_QUOTES)] = htmlspecialchars($grouping['name'], ENT_QUOTES) . ': ' . htmlspecialchars($group['name'], ENT_QUOTES);
                        }
                    }
                }

                // Multiple lists...
                else {

                    $results = array();

                    foreach ($list_id as $list_id_value) {

                        $results[$list_id_value] = array('' => '');

                        try {
                            $groupings = $this->mailchimp->lists_interest_groupings($list_id_value);
                        }
                        catch(Exception $e) {
                            continue;
                        }

                        if (!$groupings || empty($groupings)) {
                            continue;
                        }

                        foreach ($groupings as $grouping) {
                            foreach ($grouping['groups'] as $group) {
                                $results[$list_id_value][$grouping['id'] . '%%%' . htmlspecialchars($grouping['name'], ENT_QUOTES) . '%%%' . htmlspecialchars($group['name'], ENT_QUOTES)] = htmlspecialchars($grouping['name'], ENT_QUOTES) . ': ' . htmlspecialchars($group['name'], ENT_QUOTES);
                            }
                        }
                    }
                }

                return $results;
            }
            catch (Exception $e) {
                return array();
            }
        }

        /**
         * Ajax - Return MailChimp groups and tags as array for multiselect field
         */
        public function ajax_groups_and_tags_in_array()
        {
            // Check if we have received required data
            if (isset($_POST['data']) && isset($_POST['data']['list'])) {
                $groups = $this->get_groups($_POST['data']['list']);
                $merge_vars = $this->get_merge_vars(array($_POST['data']['list'] => ''));
            }
            else {
                $groups = array('' => '');
                $merge_vars = array('' => '');
            }

            echo json_encode(array('message' => array('groups' => $groups, 'merge' => $merge_vars)));
            die();
        }

        /**
         * Test MailChimp key and connection
         * 
         * @access public
         * @return bool
         */
        public function test_mailchimp($key = null)
        {
            // Try to get key from options if not set
            if ($key == null) {
                $key = $this->opt['chimpy_api_key'];
            }

            // Check if api key is set now
            if (empty($key)) {
                return __('No API key provided', 'chimpy');
            }

            // Check if curl extension is loaded
            if (!function_exists('curl_exec')) {
                return __('PHP Curl extension not loaded on your server', 'chimpy');
            }

            // Load MailChimp Wrapper
            if (!class_exists('Chimpy_Mailchimp')) {
                require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-mailchimp.class.php';
            }

            // Try to initialize MailChimp
            $this->mailchimp = new Chimpy_Mailchimp($key);

            if (!$this->mailchimp) {
                return __('Unable to initialize MailChimp class', 'chimpy');
            }

            // Ping
            try {
                $results = $this->mailchimp->helper_ping();

                if ($results['msg'] == 'Everything\'s Chimpy!') {
                    return true;
                }

                throw new Exception($results['msg']);  
            }
            catch (Exception $e) {
                return $e->getMessage();
            }

            return __('Something went wrong...', 'chimpy');
        }

        /**
         * Get MailChimp account details
         * 
         * @access public
         * @return mixed
         */
        public function get_mailchimp_account_info()
        {
            if ($this->load_mailchimp()) {
                try {
                    $results = $this->mailchimp->helper_account_details();
                    return $results;
                }
                catch (Exception $e) {
                    return false;
                }
            }

            return false;
        }

        /**
         * Load MailChimp object
         * 
         * @access public
         * @return mixed
         */
        public function load_mailchimp()
        {
            if ($this->mailchimp) {
                return true;
            }

            // Load MailChimp Wrapper
            if (!class_exists('Chimpy_Mailchimp')) {
                require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-mailchimp.class.php';
            }

            try {
                $this->mailchimp = new Chimpy_Mailchimp($this->opt['chimpy_api_key']);
                return true;
            }
            catch (Exception $e) {
                return false;
            }
        }

        /**
         * Ajax - Render MailChimp status
         * 
         * @access public
         * @return void
         */
        public function ajax_mailchimp_status()
        {
            if (!$this->opt['chimpy_api_key']) {
                $message = '<h4 style="margin:0px;"><i class="fa fa-times" style="font-size: 1.5em; color: red;"></i>&nbsp;&nbsp;&nbsp;' . __('API key not set', 'chimpy') . '</h4>';
            }
            else if ($account_info = $this->get_mailchimp_account_info()) {
                $message =  '<h4 style="margin:0px;"><i class="fa fa-check" style="font-size: 1.5em; color: green;"></i>&nbsp;&nbsp;&nbsp;' . __('Connected to account', 'chimpy') . ' ' . $account_info['username'] . '</h4>';
            }
            else {
                $message = '<h4 style="margin:0px;"><i class="fa fa-times" style="font-size: 1.5em; color: red;"></i>&nbsp;&nbsp;&nbsp;' . __('Connection to MailChimp failed.', 'chimpy') . '</h4>';
            }

            echo json_encode(array('message' => $message));
            die();
        }

        /**
         * Check if curl is enabled
         * 
         * @access public
         * @return void
         */
        public function curl_enabled()
        {
            if (function_exists('curl_version')) {
                return true;
            }

            return false;
        }

        /**
         * Select form based on conditions and request data
         * 
         * @access public
         * @param array $forms
         * @return mixed
         */
        public static function select_form_by_conditions($forms, $allowed_forms = array())
        {
            $selected_form = false;

            // Iterate over forms and return the first form that matches conditions
            foreach ($forms as $form_key => $form) {

                // Check if form is enabled and has mailing list set
                if ($form['condition'] == 'disable' || empty($form['list'])) {
                    continue;
                }

                // Check if form is allowed by user
                if (is_array($allowed_forms) && !empty($allowed_forms)) {
                    if (!in_array((int)$form_key, $allowed_forms)) {
                        continue;
                    }
                }

                // Switch all possible scenarios
                switch ($form['condition']['key']) {

                    /**
                     * ALWAYS
                     */
                    case 'always':
                        $selected_form[$form_key] = $form;
                        break;

                    /**
                     * FRONT PAGE
                     */
                    case 'front':
                        if (is_front_page()) {
                            $selected_form[$form_key] = $form;
                        }
                        break;

                    /**
                     * PAGES
                     */
                    case 'pages':
                        global $post;

                        // Check if we have any pages selected
                        if (!$post || !isset($post->ID) || !array($form['condition']['value']) || empty($form['condition']['value'])) {
                            break;
                        }

                        // Check if current post is within selected posts
                        if (in_array($post->ID, $form['condition']['value'])) {
                            $selected_form[$form_key] = $form;
                        }

                        break;

                    /**
                     * PAGES NOT
                     */
                    case 'pages_not':
                        global $post;

                        // Check if we have any pages selected
                        if (!$post || !isset($post->ID) || !array($form['condition']['value']) || empty($form['condition']['value'])) {
                            $selected_form[$form_key] = $form;
                            break;
                        }

                        // Check if current post is NOT within selected posts
                        if (!in_array($post->ID, $form['condition']['value'])) {
                            $selected_form[$form_key] = $form;
                        }

                        break;

                    /**
                     * POSTS
                     */
                    case 'posts':
                        global $post;

                        // Check if we have any pages selected
                        if (!$post || !isset($post->ID) || !array($form['condition']['value']) || empty($form['condition']['value'])) {
                            break;
                        }

                        // Check if current post is within selected posts
                        if (in_array($post->ID, $form['condition']['value'])) {
                            $selected_form[$form_key] = $form;
                        }

                        break;

                    /**
                     * POSTS NOT
                     */
                    case 'posts_not':
                        global $post;

                        // Check if we have any pages selected
                        if (!$post || !isset($post->ID) || !array($form['condition']['value']) || empty($form['condition']['value'])) {
                            $selected_form[$form_key] = $form;
                            break;
                        }

                        // Check if current post is within selected posts
                        if (!in_array($post->ID, $form['condition']['value'])) {
                            $selected_form[$form_key] = $form;
                        }

                        break;

                    /**
                     * CATEGORIES
                     */
                    case 'categories':
                        global $post;

                        // Check if we have any categories selected
                        if (!$post || !isset($post->ID) || !array($form['condition']['value']) || empty($form['condition']['value']) || is_front_page()) {
                            break;
                        }

                        // Get all categories with children
                        $category_with_children_ids = self::get_categories_with_children($form['condition']['value']);

                        if (!is_array($category_with_children_ids) || empty($category_with_children_ids)) {
                            break;
                        }

                        // Get all categories that this post is associated with
                        $post_category_ids = wp_get_post_categories($post->ID);

                        if (!is_array($post_category_ids) || empty($post_category_ids)) {
                            break;
                        }

                        // Check if there's at least one category match
                        foreach ($category_with_children_ids as $single_cat_id) {
                            if (in_array($single_cat_id, $post_category_ids)) {
                                $selected_form[$form_key] = $form;
                                break;
                            }
                        }

                        break;

                    /**
                     * CATEGORIES NOT
                     */
                    case 'categories_not':
                        global $post;

                        // Check if we have any categories selected
                        if (!$post || !isset($post->ID) || !array($form['condition']['value']) || empty($form['condition']['value'])) {
                            $selected_form[$form_key] = $form;
                            break;
                        }

                        // Get all categories with children
                        $category_with_children_ids = self::get_categories_with_children($form['condition']['value']);

                        if (!is_array($category_with_children_ids) || empty($category_with_children_ids)) {
                            $selected_form[$form_key] = $form;
                            break;
                        }

                        // Get all categories that this post is associated with
                        $post_category_ids = wp_get_post_categories($post->ID);

                        if (!is_array($post_category_ids) || empty($post_category_ids)) {
                            $selected_form[$form_key] = $form;
                            break;
                        }

                        // Make sure there are NO matches
                        $found = false;

                        foreach ($category_with_children_ids as $single_cat_id) {
                            if (in_array($single_cat_id, $post_category_ids)) {
                                $found = true;
                            }
                        }

                        if (!$found) {
                            $selected_form[$form_key] = $form;
                        }

                        break;

                    /**
                     * URL
                     */
                    case 'url':
                        $request_url = ($_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI];

                        if (preg_match('/' . preg_quote($form['condition']['value']) . '/i', $request_url)) {
                            $selected_form[$form_key] = $form;
                        }

                        break;

                    /**
                     * URL NOT
                     */
                    case 'url_not':
                        $request_url = ($_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI];

                        if (!preg_match('/' . preg_quote($form['condition']['value']) . '/i', $request_url)) {
                            $selected_form[$form_key] = $form;
                        }

                        break;

                    /**
                     * DEFAULT
                     */
                    default:
                        break;
                }

                // Check if we have selected this form
                if ($selected_form) {
                    break;
                }
            }

            return $selected_form;
        }

        /**
         * Get array of category ids with all child categories
         * 
         * @access public
         * @param array $category_ids
         * @return array
         */
        public static function get_categories_with_children($category_ids)
        {
            $categories_with_children = array();

            // Get all child categories
            foreach ($category_ids as $category_id) {
                $categories_with_children[] = $category_id;

                $current_category = get_category($category_id);

                if (!$current_category) {
                    continue;
                }

                $child_categories = get_categories(array(
                    'type'          => 'post',
                    'child_of'      => $category_id,
                    'hide_empty'    => 0,
                ));

                if (!is_array($child_categories) || empty($child_categories)) {
                    continue;
                }

                foreach ($child_categories as $child_category) {
                    $categories_with_children[] = $child_category->term_id;
                }

            }

            return $categories_with_children;
        }

        /**
         * Display form after content
         * 
         * @access public
         * @param $content
         * @return string
         */
        public function form_after_content($content)
        {
            if (is_archive() || is_search() || is_home() || is_front_page() || is_feed()) {
                return $content;
            }

            // Check if integration is enabled and at least one form configured
            if (!$this->opt['chimpy_api_key'] || !isset($this->opt['forms']) || empty($this->opt['forms'])) {
                return $content;
            }

            // Check if form below content is enabled
            if (!(is_single() && in_array('1', $this->opt['chimpy_after_posts_post_types'])) && !(is_page() && in_array('2', $this->opt['chimpy_after_posts_post_types']))) {
                return $content;
            }

            // Select form that match the conditions best
            $form = self::select_form_by_conditions($this->opt['forms'], $this->opt['chimpy_after_posts_allowed_forms']);

            if (!$form) {
                return $content;
            }

            require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-prepare-form.inc.php';

            $form_html = chimpy_prepare_form($form, $this->opt, 'after_posts', $args);

            return $content . $form_html;
        }

        /**
         * Form shortcode handler
         * 
         * @access public
         * @param mixed $attributes
         * @return void
         */
        public function subscription_shortcode($attributes)
        {
            // Make sure this is not a feed
            if (is_home() || is_archive() || is_search() || is_feed()) {
                return '';
            }

            // Check if integration is enabled and at least one form configured
            if (!$this->opt['chimpy_api_key'] || !isset($this->opt['forms']) || empty($this->opt['forms'])) {
                return '';
            }

            // Extract attributes
            extract(shortcode_atts(array(
                'forms' => ''
            ), $attributes));

            if ($forms != '') {
                $forms = preg_split('/,/', $forms);
                $normalized_allowed_forms = array();

                foreach ($forms as $allowed_form) {
                    if (is_numeric($allowed_form)) {
                        $normalized_allowed_forms[] = (int)$allowed_form;
                    }
                }

                $allowed_forms = $normalized_allowed_forms;
            }
            else {
                $allowed_forms = array();
            }

            // Select form that match the conditions best
            $form = self::select_form_by_conditions($this->opt['forms'], $allowed_forms);

            if (!$form) {
                return '';
            }

            require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-prepare-form.inc.php';

            $form_html = chimpy_prepare_form($form, $this->opt, 'shortcode');

            return $form_html;
        }

        /**
         * Subscribe user to mailing list
         * 
         * @access public
         * @param string $list_id
         * @param string $email
         * @param array $groups
         * @param array $custom_fields
         * @param bool $is_backend
         * @return mixed
         */
        public function subscribe($list_id, $email, $groups, $custom_fields, $is_backend = false)
        {
            // Load MailChimp
            if (!$this->load_mailchimp()) {
                return false;
            }

            $groupings = array();

            // Any groups to be set?
            if (!empty($groups)) {

                // First make an acceptable structure
                $groups_parent_children = array();

                foreach ($groups as $group) {
                    $parts = preg_split('/%%%/', htmlspecialchars_decode($group, ENT_QUOTES));

                    if (count($parts) == 3) {
                        $groups_parent_children[$parts[0]][] = $parts[2];
                    }
                }

                // Now populate groupings array
                foreach ($groups_parent_children as $parent => $child) {
                    $groupings[] = array(
                        'id' => $parent,
                        'groups' => $child
                    );
                }
            }

            // All merge vars
            $merge_vars = array(
                'groupings' => $groupings,
            );

            foreach ($custom_fields as $key => $value) {
                $merge_vars[$key] = $value;
            }

            // Opt-in IP and time
            if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
                $merge_vars['optin_ip'] = $this->get_visitor_ip();
                $merge_vars['optin_time'] = date('Y-m-d H:i:s');
            }

            // Subscribe
            try {
                $results = $this->mailchimp->lists_subscribe(
                    $list_id,
                    array('email' => $email),
                    $merge_vars,
                    'html',
                    $this->opt['chimpy_double_optin'],
                    $this->opt['chimpy_update_existing'],
                    $this->opt['chimpy_replace_groups'],
                    $this->opt['chimpy_send_welcome']
                );

                if ($is_backend) {
                    return $results;
                }
                else {
                    return true;
                }
            }
            catch (Exception $e) {
                if (preg_match('/.+is already subscribed to list.+/', $e->getMessage()) && !$this->opt['chimpy_update_existing'] && !$is_backend) {
                    return $this->opt['chimpy_label_already_subscribed'];
                }

                return false;
            }
        }

        /**
         * Update subscription (subscriber details)
         * 
         * @access public
         * @param string $sync_key
         * @param string $list_id
         * @param array $groups
         * @param array $custom_fields
         * @return mixed
         */
        public function update_subscription($sync_key, $list_id, $groups, $custom_fields)
        {
            // Load MailChimp
            if (!$this->load_mailchimp()) {
                return false;
            }

            $groupings = array();

            // Any groups to be set?
            if (!empty($groups)) {

                // First make an acceptable structure
                $groups_parent_children = array();

                foreach ($groups as $group) {
                    $parts = preg_split('/%%%/', htmlspecialchars_decode($group, ENT_QUOTES));

                    if (count($parts) == 3) {
                        $groups_parent_children[$parts[0]][] = $parts[2];
                    }
                }

                // Now populate groupings array
                foreach ($groups_parent_children as $parent => $child) {
                    $groupings[] = array(
                        'id' => $parent,
                        'groups' => $child
                    );
                }
            }

            // All merge vars
            $merge_vars = array(
                'groupings' => $groupings,
            );

            foreach ($custom_fields as $key => $value) {
                $merge_vars[$key] = $value;
            }

            // Update subscription
            try {
                $results = $this->mailchimp->lists_subscribe(
                    $list_id,
                    array('leid' => $sync_key),
                    $merge_vars,
                    'html',
                    false,
                    true,
                    $this->opt['chimpy_replace_groups'],
                    false
                );

                return true;
            }
            catch (Exception $e) {
                return false;
            }
        }

        /**
         * Unsubscribe user
         * 
         * @access public
         * @param string $list_id
         * @param string $key
         * @param bool $is_key_email
         * @return mixed
         */
        public function unsubscribe($list_id, $key, $is_key_email)
        {
            // Load MailChimp
            if (!$this->load_mailchimp()) {
                return false;
            }

            $handle = array();

            if ($is_key_email) {
                $handle['email'] = $key;
            }
            else {
                $handle['leid'] = $key;
            }

            // Unsubscribe
            try {
                $results = $this->mailchimp->lists_unsubscribe(
                    $list_id,
                    $handle,
                    false,
                    false,
                    false
                );

                return true;
            }
            catch (Exception $e) {
                return false;
            }
        }

        /**
         * Ajax - process subscription
         * 
         * @access public
         * @return void
         */
        public function ajax_subscribe()
        {
            // Check if integration is enabled
            if (!$this->opt['chimpy_api_key'] || !isset($this->opt['forms']) || !is_array($this->opt['forms']) || empty($this->opt['forms'])) {
                $this->respond_with_error();
            }

            // Check if data has been received
            if (!isset($_POST['data'])) {
                $this->respond_with_error();
            }

            $data = array();
            parse_str($_POST['data'], $data);

            // Get context
            if (isset($data['chimpy_widget_subscribe'])) {
                $context = 'widget';
            }
            else if (isset($data['chimpy_after_posts_subscribe'])) {
                $context = 'after_posts';
            }
            else if (isset($data['chimpy_popup_subscribe'])) {
                $context = 'popup';
            }
            else if (isset($data['chimpy_shortcode_subscribe'])) {
                $context = 'shortcode';
            }
            else if (isset($data['chimpy_lock_subscribe'])) {
                $context = 'lock';
            }
            else {
                $this->respond_with_error();
            }

            $data = $data['chimpy_' . $context . '_subscribe'];

            // Load form
            if (isset($data['form']) && isset($this->opt['forms'][$data['form']])) {
                $form = $this->opt['forms'][$data['form']];
            }
            else {
                $this->respond_with_error();
            }

            // Check if form is enabled
            if ($form['condition'] == 'disable' || !isset($form['list']) || empty($form['list'])) {
                $this->respond_with_error();
            }

            // Parse custom fields
            $custom_fields = array();
            $email = null;

            if (isset($form['fields']) && is_array($form['fields']) && !empty($form['fields'])) {
                if (!isset($data['custom']) || !is_array($data['custom']) || empty($data['custom'])) {
                    $this->respond_with_error();
                }

                foreach ($form['fields'] as $field) {

                    // Value missing?
                    if (!isset($data['custom'][$field['tag']]) || $data['custom'][$field['tag']] == '') {
                        if ($field['req']) {
                            $this->respond_with_error();
                        }
                        else {
                            continue;
                        }
                    }

                    // If it's email address, extract it
                    if ($field['type'] == 'email') {
                        $email = $data['custom'][$field['tag']];
                        continue;
                    }

                    // If it's dropdown or radio - extract actual value
                    else if (in_array($field['type'], array('dropdown', 'radio'))) {
                        if (isset($field['choices'][$data['custom'][$field['tag']]])) {
                            $custom_fields[$field['tag']] = $field['choices'][$data['custom'][$field['tag']]];
                        }
                        else {
                            if ($field['reg']) {
                                $this->respond_with_error();
                            }
                            else {
                                continue;
                            }
                        }
                    }

                    // If it's date - change to acceptable format
                    else if ($field['type'] == 'date') {

                        // Get date format
                        $date_format = self::get_date_pattern('date', $this->opt['chimpy_date_format'], 'date_format');

                        // Parse to array with date details (using strptime to support PHP 5.2)
                        $date_parsed = strptime($data['custom'][$field['tag']], $date_format);

                        if (!$date_parsed) {
                            if ($field['reg']) {
                                $this->respond_with_error();
                            }
                            else {
                                continue;
                            }
                        }

                        $custom_fields[$field['tag']] = ($date_parsed['tm_year'] + 1900) . '-' . ($date_parsed['tm_mon'] + 1 < 10 ? '0' : '') . ($date_parsed['tm_mon'] + 1) . '-' . (($date_parsed['tm_mday'] < 10 ? '0' : '') . $date_parsed['tm_mday']);
                    }

                    // If it's birthday - change to acceptable format
                    else if ($field['type'] == 'birthday') {

                        if (strlen($data['custom'][$field['tag']]) != 5) {
                            if ($field['reg']) {
                                $this->respond_with_error();
                            }
                            else {
                                continue;
                            }
                        }

                        // Get birthday format
                        $birthday_format = self::get_date_pattern('birthday', $this->opt['chimpy_birthday_format'], 'date_format');

                        $month_first = in_array($birthday_format, array('%m/%d', '%m-%d', '%m.%d')) ? true : false;
                        $birthday_separator = in_array($birthday_format, array('%m/%d', '%d/%m')) ? '/' : (in_array($birthday_format, array('%m-%d', '%d-%m')) ? '-' : '.');

                        // Split to month and day
                        $birthday_parts = preg_split('/\\' . $birthday_separator . '/', $data['custom'][$field['tag']]);

                        if (count($birthday_parts) != 2 || strlen($birthday_parts[0]) != 2 || strlen($birthday_parts[1]) != 2) {
                            if ($field['reg']) {
                                $this->respond_with_error();
                            }
                            else {
                                continue;
                            }
                        }

                        // Glue them together to MM/DD
                        $custom_fields[$field['tag']] = ($month_first ? $birthday_parts[0] : $birthday_parts[1]) . '/' . ($month_first ? $birthday_parts[1] : $birthday_parts[0]);
                    }

                    // Regular field..
                    else {
                        $custom_fields[$field['tag']] = $data['custom'][$field['tag']];
                    }
                }
            }

            // Do we have email address?
            if (!$email) {
                $this->respond_with_error();
            }

            // Process groups
            $subscribe_groups = array();

            if (isset($form['group_method']) && in_array($form['group_method'], array('multi', 'single', 'single_req', 'select', 'select_req'))) {
                if (isset($data['groups']) && is_array($data['groups']) && !empty($data['groups'])) {
                    if ($form['group_method'] == 'multi') {
                        foreach ($data['groups'] as $grouping) {
                            foreach ($grouping as $group) {
                                $subscribe_groups[] = $group;
                            }
                        }
                    }
                    else {
                        foreach ($data['groups'] as $group) {
                            $subscribe_groups[] = $group;
                        }
                    }
                }
            }
            else {
                $subscribe_groups = $form['groups'];
            }

            // Subscribe user
            $subscribe_result = $this->subscribe($form['list'], $email, $subscribe_groups, $custom_fields);

            if (is_bool($subscribe_result)) {
                if ($subscribe_result) {

                    $response = array(
                        'error' => 0,
                        'message' => $this->opt['chimpy_label_success']
                    );

                    // Do we need to redirect user to some other page?
                    if (isset($form['redirect_url']) && $form['redirect_url']) {
                        $response['redirect_url'] = $form['redirect_url'];
                    }

                    // Send Ajax response
                    echo json_encode($response);
                    die();
                }
                else {
                    $this->respond_with_error();
                }
            }
            else {
                echo json_encode(array('error' => 1, 'message' => $subscribe_result));
                die();
            }
        }

        /**
         * Respond to signup ajax request with standard error
         * 
         * @access public
         * @return void
         */
        public function respond_with_error()
        {
            echo json_encode(array('error' => 1, 'message' => $this->opt['chimpy_label_error']));
            die();
        }

        /**
         * Return list of Font Awesome icon names and unicode codes
         * 
         * @access public
         * @return array
         */
        public function get_font_awesome_icons()
        {
            return array(
                'fa-glass' => '&#xf000;',
                'fa-music' => '&#xf001;',
                'fa-search' => '&#xf002;',
                'fa-envelope-o' => '&#xf003;',
                'fa-heart' => '&#xf004;',
                'fa-star' => '&#xf005;',
                'fa-star-o' => '&#xf006;',
                'fa-user' => '&#xf007;',
                'fa-film' => '&#xf008;',
                'fa-th-large' => '&#xf009;',
                'fa-th' => '&#xf00a;',
                'fa-th-list' => '&#xf00b;',
                'fa-check' => '&#xf00c;',
                'fa-times' => '&#xf00d;',
                'fa-search-plus' => '&#xf00e;',
                'fa-search-minus' => '&#xf010;',
                'fa-power-off' => '&#xf011;',
                'fa-signal' => '&#xf012;',
                'fa-cog' => '&#xf013;',
                'fa-trash-o' => '&#xf014;',
                'fa-home' => '&#xf015;',
                'fa-file-o' => '&#xf016;',
                'fa-clock-o' => '&#xf017;',
                'fa-road' => '&#xf018;',
                'fa-download' => '&#xf019;',
                'fa-arrow-circle-o-down' => '&#xf01a;',
                'fa-arrow-circle-o-up' => '&#xf01b;',
                'fa-inbox' => '&#xf01c;',
                'fa-play-circle-o' => '&#xf01d;',
                'fa-repeat' => '&#xf01e;',
                'fa-refresh' => '&#xf021;',
                'fa-list-alt' => '&#xf022;',
                'fa-lock' => '&#xf023;',
                'fa-flag' => '&#xf024;',
                'fa-headphones' => '&#xf025;',
                'fa-volume-off' => '&#xf026;',
                'fa-volume-down' => '&#xf027;',
                'fa-volume-up' => '&#xf028;',
                'fa-qrcode' => '&#xf029;',
                'fa-barcode' => '&#xf02a;',
                'fa-tag' => '&#xf02b;',
                'fa-tags' => '&#xf02c;',
                'fa-book' => '&#xf02d;',
                'fa-bookmark' => '&#xf02e;',
                'fa-print' => '&#xf02f;',
                'fa-camera' => '&#xf030;',
                'fa-font' => '&#xf031;',
                'fa-bold' => '&#xf032;',
                'fa-italic' => '&#xf033;',
                'fa-text-height' => '&#xf034;',
                'fa-text-width' => '&#xf035;',
                'fa-align-left' => '&#xf036;',
                'fa-align-center' => '&#xf037;',
                'fa-align-right' => '&#xf038;',
                'fa-align-justify' => '&#xf039;',
                'fa-list' => '&#xf03a;',
                'fa-outdent' => '&#xf03b;',
                'fa-indent' => '&#xf03c;',
                'fa-video-camera' => '&#xf03d;',
                'fa-picture-o' => '&#xf03e;',
                'fa-pencil' => '&#xf040;',
                'fa-map-marker' => '&#xf041;',
                'fa-adjust' => '&#xf042;',
                'fa-tint' => '&#xf043;',
                'fa-pencil-square-o' => '&#xf044;',
                'fa-share-square-o' => '&#xf045;',
                'fa-check-square-o' => '&#xf046;',
                'fa-arrows' => '&#xf047;',
                'fa-step-backward' => '&#xf048;',
                'fa-fast-backward' => '&#xf049;',
                'fa-backward' => '&#xf04a;',
                'fa-play' => '&#xf04b;',
                'fa-pause' => '&#xf04c;',
                'fa-stop' => '&#xf04d;',
                'fa-forward' => '&#xf04e;',
                'fa-fast-forward' => '&#xf050;',
                'fa-step-forward' => '&#xf051;',
                'fa-eject' => '&#xf052;',
                'fa-chevron-left' => '&#xf053;',
                'fa-chevron-right' => '&#xf054;',
                'fa-plus-circle' => '&#xf055;',
                'fa-minus-circle' => '&#xf056;',
                'fa-times-circle' => '&#xf057;',
                'fa-check-circle' => '&#xf058;',
                'fa-question-circle' => '&#xf059;',
                'fa-info-circle' => '&#xf05a;',
                'fa-crosshairs' => '&#xf05b;',
                'fa-times-circle-o' => '&#xf05c;',
                'fa-check-circle-o' => '&#xf05d;',
                'fa-ban' => '&#xf05e;',
                'fa-arrow-left' => '&#xf060;',
                'fa-arrow-right' => '&#xf061;',
                'fa-arrow-up' => '&#xf062;',
                'fa-arrow-down' => '&#xf063;',
                'fa-share' => '&#xf064;',
                'fa-expand' => '&#xf065;',
                'fa-compress' => '&#xf066;',
                'fa-plus' => '&#xf067;',
                'fa-minus' => '&#xf068;',
                'fa-asterisk' => '&#xf069;',
                'fa-exclamation-circle' => '&#xf06a;',
                'fa-gift' => '&#xf06b;',
                'fa-leaf' => '&#xf06c;',
                'fa-fire' => '&#xf06d;',
                'fa-eye' => '&#xf06e;',
                'fa-eye-slash' => '&#xf070;',
                'fa-exclamation-triangle' => '&#xf071;',
                'fa-plane' => '&#xf072;',
                'fa-calendar' => '&#xf073;',
                'fa-random' => '&#xf074;',
                'fa-comment' => '&#xf075;',
                'fa-magnet' => '&#xf076;',
                'fa-chevron-up' => '&#xf077;',
                'fa-chevron-down' => '&#xf078;',
                'fa-retweet' => '&#xf079;',
                'fa-shopping-cart' => '&#xf07a;',
                'fa-folder' => '&#xf07b;',
                'fa-folder-open' => '&#xf07c;',
                'fa-arrows-v' => '&#xf07d;',
                'fa-arrows-h' => '&#xf07e;',
                'fa-bar-chart-o' => '&#xf080;',
                'fa-twitter-square' => '&#xf081;',
                'fa-facebook-square' => '&#xf082;',
                'fa-camera-retro' => '&#xf083;',
                'fa-key' => '&#xf084;',
                'fa-cogs' => '&#xf085;',
                'fa-comments' => '&#xf086;',
                'fa-thumbs-o-up' => '&#xf087;',
                'fa-thumbs-o-down' => '&#xf088;',
                'fa-star-half' => '&#xf089;',
                'fa-heart-o' => '&#xf08a;',
                'fa-sign-out' => '&#xf08b;',
                'fa-linkedin-square' => '&#xf08c;',
                'fa-thumb-tack' => '&#xf08d;',
                'fa-external-link' => '&#xf08e;',
                'fa-sign-in' => '&#xf090;',
                'fa-trophy' => '&#xf091;',
                'fa-github-square' => '&#xf092;',
                'fa-upload' => '&#xf093;',
                'fa-lemon-o' => '&#xf094;',
                'fa-phone' => '&#xf095;',
                'fa-square-o' => '&#xf096;',
                'fa-bookmark-o' => '&#xf097;',
                'fa-phone-square' => '&#xf098;',
                'fa-twitter' => '&#xf099;',
                'fa-facebook' => '&#xf09a;',
                'fa-github' => '&#xf09b;',
                'fa-unlock' => '&#xf09c;',
                'fa-credit-card' => '&#xf09d;',
                'fa-rss' => '&#xf09e;',
                'fa-hdd-o' => '&#xf0a0;',
                'fa-bullhorn' => '&#xf0a1;',
                'fa-bell' => '&#xf0f3;',
                'fa-certificate' => '&#xf0a3;',
                'fa-hand-o-right' => '&#xf0a4;',
                'fa-hand-o-left' => '&#xf0a5;',
                'fa-hand-o-up' => '&#xf0a6;',
                'fa-hand-o-down' => '&#xf0a7;',
                'fa-arrow-circle-left' => '&#xf0a8;',
                'fa-arrow-circle-right' => '&#xf0a9;',
                'fa-arrow-circle-up' => '&#xf0aa;',
                'fa-arrow-circle-down' => '&#xf0ab;',
                'fa-globe' => '&#xf0ac;',
                'fa-wrench' => '&#xf0ad;',
                'fa-tasks' => '&#xf0ae;',
                'fa-filter' => '&#xf0b0;',
                'fa-briefcase' => '&#xf0b1;',
                'fa-arrows-alt' => '&#xf0b2;',
                'fa-users' => '&#xf0c0;',
                'fa-link' => '&#xf0c1;',
                'fa-cloud' => '&#xf0c2;',
                'fa-flask' => '&#xf0c3;',
                'fa-scissors' => '&#xf0c4;',
                'fa-files-o' => '&#xf0c5;',
                'fa-paperclip' => '&#xf0c6;',
                'fa-floppy-o' => '&#xf0c7;',
                'fa-square' => '&#xf0c8;',
                'fa-bars' => '&#xf0c9;',
                'fa-list-ul' => '&#xf0ca;',
                'fa-list-ol' => '&#xf0cb;',
                'fa-strikethrough' => '&#xf0cc;',
                'fa-underline' => '&#xf0cd;',
                'fa-table' => '&#xf0ce;',
                'fa-magic' => '&#xf0d0;',
                'fa-truck' => '&#xf0d1;',
                'fa-pinterest' => '&#xf0d2;',
                'fa-pinterest-square' => '&#xf0d3;',
                'fa-google-plus-square' => '&#xf0d4;',
                'fa-google-plus' => '&#xf0d5;',
                'fa-money' => '&#xf0d6;',
                'fa-caret-down' => '&#xf0d7;',
                'fa-caret-up' => '&#xf0d8;',
                'fa-caret-left' => '&#xf0d9;',
                'fa-caret-right' => '&#xf0da;',
                'fa-columns' => '&#xf0db;',
                'fa-sort' => '&#xf0dc;',
                'fa-sort-asc' => '&#xf0dd;',
                'fa-sort-desc' => '&#xf0de;',
                'fa-envelope' => '&#xf0e0;',
                'fa-linkedin' => '&#xf0e1;',
                'fa-undo' => '&#xf0e2;',
                'fa-gavel' => '&#xf0e3;',
                'fa-tachometer' => '&#xf0e4;',
                'fa-comment-o' => '&#xf0e5;',
                'fa-comments-o' => '&#xf0e6;',
                'fa-bolt' => '&#xf0e7;',
                'fa-sitemap' => '&#xf0e8;',
                'fa-umbrella' => '&#xf0e9;',
                'fa-clipboard' => '&#xf0ea;',
                'fa-lightbulb-o' => '&#xf0eb;',
                'fa-exchange' => '&#xf0ec;',
                'fa-cloud-download' => '&#xf0ed;',
                'fa-cloud-upload' => '&#xf0ee;',
                'fa-user-md' => '&#xf0f0;',
                'fa-stethoscope' => '&#xf0f1;',
                'fa-suitcase' => '&#xf0f2;',
                'fa-bell-o' => '&#xf0a2;',
                'fa-coffee' => '&#xf0f4;',
                'fa-cutlery' => '&#xf0f5;',
                'fa-file-text-o' => '&#xf0f6;',
                'fa-building-o' => '&#xf0f7;',
                'fa-hospital-o' => '&#xf0f8;',
                'fa-ambulance' => '&#xf0f9;',
                'fa-medkit' => '&#xf0fa;',
                'fa-fighter-jet' => '&#xf0fb;',
                'fa-beer' => '&#xf0fc;',
                'fa-h-square' => '&#xf0fd;',
                'fa-plus-square' => '&#xf0fe;',
                'fa-angle-double-left' => '&#xf100;',
                'fa-angle-double-right' => '&#xf101;',
                'fa-angle-double-up' => '&#xf102;',
                'fa-angle-double-down' => '&#xf103;',
                'fa-angle-left' => '&#xf104;',
                'fa-angle-right' => '&#xf105;',
                'fa-angle-up' => '&#xf106;',
                'fa-angle-down' => '&#xf107;',
                'fa-desktop' => '&#xf108;',
                'fa-laptop' => '&#xf109;',
                'fa-tablet' => '&#xf10a;',
                'fa-mobile' => '&#xf10b;',
                'fa-circle-o' => '&#xf10c;',
                'fa-quote-left' => '&#xf10d;',
                'fa-quote-right' => '&#xf10e;',
                'fa-spinner' => '&#xf110;',
                'fa-circle' => '&#xf111;',
                'fa-reply' => '&#xf112;',
                'fa-github-alt' => '&#xf113;',
                'fa-folder-o' => '&#xf114;',
                'fa-folder-open-o' => '&#xf115;',
                'fa-smile-o' => '&#xf118;',
                'fa-frown-o' => '&#xf119;',
                'fa-meh-o' => '&#xf11a;',
                'fa-gamepad' => '&#xf11b;',
                'fa-keyboard-o' => '&#xf11c;',
                'fa-flag-o' => '&#xf11d;',
                'fa-flag-checkered' => '&#xf11e;',
                'fa-terminal' => '&#xf120;',
                'fa-code' => '&#xf121;',
                'fa-reply-all' => '&#xf122;',
                'fa-mail-reply-all' => '&#xf122;',
                'fa-star-half-o' => '&#xf123;',
                'fa-location-arrow' => '&#xf124;',
                'fa-crop' => '&#xf125;',
                'fa-code-fork' => '&#xf126;',
                'fa-chain-broken' => '&#xf127;',
                'fa-question' => '&#xf128;',
                'fa-info' => '&#xf129;',
                'fa-exclamation' => '&#xf12a;',
                'fa-superscript' => '&#xf12b;',
                'fa-subscript' => '&#xf12c;',
                'fa-eraser' => '&#xf12d;',
                'fa-puzzle-piece' => '&#xf12e;',
                'fa-microphone' => '&#xf130;',
                'fa-microphone-slash' => '&#xf131;',
                'fa-shield' => '&#xf132;',
                'fa-calendar-o' => '&#xf133;',
                'fa-fire-extinguisher' => '&#xf134;',
                'fa-rocket' => '&#xf135;',
                'fa-maxcdn' => '&#xf136;',
                'fa-chevron-circle-left' => '&#xf137;',
                'fa-chevron-circle-right' => '&#xf138;',
                'fa-chevron-circle-up' => '&#xf139;',
                'fa-chevron-circle-down' => '&#xf13a;',
                'fa-html5' => '&#xf13b;',
                'fa-css3' => '&#xf13c;',
                'fa-anchor' => '&#xf13d;',
                'fa-unlock-alt' => '&#xf13e;',
                'fa-bullseye' => '&#xf140;',
                'fa-ellipsis-h' => '&#xf141;',
                'fa-ellipsis-v' => '&#xf142;',
                'fa-rss-square' => '&#xf143;',
                'fa-play-circle' => '&#xf144;',
                'fa-ticket' => '&#xf145;',
                'fa-minus-square' => '&#xf146;',
                'fa-minus-square-o' => '&#xf147;',
                'fa-level-up' => '&#xf148;',
                'fa-level-down' => '&#xf149;',
                'fa-check-square' => '&#xf14a;',
                'fa-pencil-square' => '&#xf14b;',
                'fa-external-link-square' => '&#xf14c;',
                'fa-share-square' => '&#xf14d;',
                'fa-compass' => '&#xf14e;',
                'fa-caret-square-o-down' => '&#xf150;',
                'fa-caret-square-o-up' => '&#xf151;',
                'fa-caret-square-o-right' => '&#xf152;',
                'fa-eur' => '&#xf153;',
                'fa-gbp' => '&#xf154;',
                'fa-usd' => '&#xf155;',
                'fa-inr' => '&#xf156;',
                'fa-jpy' => '&#xf157;',
                'fa-rub' => '&#xf158;',
                'fa-krw' => '&#xf159;',
                'fa-btc' => '&#xf15a;',
                'fa-file' => '&#xf15b;',
                'fa-file-text' => '&#xf15c;',
                'fa-sort-alpha-asc' => '&#xf15d;',
                'fa-sort-alpha-desc' => '&#xf15e;',
                'fa-sort-amount-asc' => '&#xf160;',
                'fa-sort-amount-desc' => '&#xf161;',
                'fa-sort-numeric-asc' => '&#xf162;',
                'fa-sort-numeric-desc' => '&#xf163;',
                'fa-thumbs-up' => '&#xf164;',
                'fa-thumbs-down' => '&#xf165;',
                'fa-youtube-square' => '&#xf166;',
                'fa-youtube' => '&#xf167;',
                'fa-xing' => '&#xf168;',
                'fa-xing-square' => '&#xf169;',
                'fa-youtube-play' => '&#xf16a;',
                'fa-dropbox' => '&#xf16b;',
                'fa-stack-overflow' => '&#xf16c;',
                'fa-instagram' => '&#xf16d;',
                'fa-flickr' => '&#xf16e;',
                'fa-adn' => '&#xf170;',
                'fa-bitbucket' => '&#xf171;',
                'fa-bitbucket-square' => '&#xf172;',
                'fa-tumblr' => '&#xf173;',
                'fa-tumblr-square' => '&#xf174;',
                'fa-long-arrow-down' => '&#xf175;',
                'fa-long-arrow-up' => '&#xf176;',
                'fa-long-arrow-left' => '&#xf177;',
                'fa-long-arrow-right' => '&#xf178;',
                'fa-apple' => '&#xf179;',
                'fa-windows' => '&#xf17a;',
                'fa-android' => '&#xf17b;',
                'fa-linux' => '&#xf17c;',
                'fa-dribbble' => '&#xf17d;',
                'fa-skype' => '&#xf17e;',
                'fa-foursquare' => '&#xf180;',
                'fa-trello' => '&#xf181;',
                'fa-female' => '&#xf182;',
                'fa-male' => '&#xf183;',
                'fa-gittip' => '&#xf184;',
                'fa-sun-o' => '&#xf185;',
                'fa-moon-o' => '&#xf186;',
                'fa-archive' => '&#xf187;',
                'fa-bug' => '&#xf188;',
                'fa-vk' => '&#xf189;',
                'fa-weibo' => '&#xf18a;',
                'fa-renren' => '&#xf18b;',
                'fa-pagelines' => '&#xf18c;',
                'fa-stack-exchange' => '&#xf18d;',
                'fa-arrow-circle-o-right' => '&#xf18e;',
                'fa-arrow-circle-o-left' => '&#xf190;',
                'fa-caret-square-o-left' => '&#xf191;',
                'fa-dot-circle-o' => '&#xf192;',
                'fa-wheelchair' => '&#xf193;',
                'fa-vimeo-square' => '&#xf194;',
                'fa-try' => '&#xf195;',
                'fa-plus-square-o' => '&#xf196;',
            );
        }

        /**
         * Return corresponding date/birthday pattern
         * 
         * @access public
         * @param string $type
         * @param string $key
         * @return array
         */
        public static function get_date_pattern($type, $key, $what)
        {
            $patterns = array(
                'date' => array(
                    '0' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])\/([0]?[1-9]|1[012])\/[0-9]{4}',
                        'mask'          => '99/99/9999',
                        'placeholder'   => __('dd/mm/yyyy', 'chimpy'),
                        'date_format'   => '%d/%m/%Y',
                    ),
                    '1' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])-([0]?[1-9]|1[012])-[0-9]{4}',
                        'mask'          => '99-99-9999',
                        'placeholder'   => __('dd-mm-yyyy', 'chimpy'),
                        'date_format'   => '%d-%m-%Y',
                    ),
                    '2' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])\.([0]?[1-9]|1[012])\.[0-9]{4}',
                        'mask'          => '99.99.9999',
                        'placeholder'   => __('dd.mm.yyyy', 'chimpy'),
                        'date_format'   => '%d.%m.%Y',
                    ),
                    '3' => array(
                        'pattern'       => '([0]?[1-9]|1[012])\/([0]?[1-9]|1[0-9]|2[0-9]|3[01])\/[0-9]{4}',
                        'mask'          => '99/99/9999',
                        'placeholder'   => __('mm/dd/yyyy', 'chimpy'),
                        'date_format'   => '%m/%d/%Y',
                    ),
                    '4' => array(
                        'pattern'       => '([0]?[1-9]|1[012])-([0]?[1-9]|1[0-9]|2[0-9]|3[01])-[0-9]{4}',
                        'mask'          => '99-99-9999',
                        'placeholder'   => __('mm-dd-yyyy', 'chimpy'),
                        'date_format'   => '%m-%d-%Y',
                    ),
                    '5' => array(
                        'pattern'       => '([0]?[1-9]|1[012])\.([0]?[1-9]|1[0-9]|2[0-9]|3[01])\.[0-9]{4}',
                        'mask'          => '99.99.9999',
                        'placeholder'   => __('mm.dd.yyyy', 'chimpy'),
                        'date_format'   => '%m.%d.%Y',
                    ),
                    '6' => array(
                        'pattern'       => '[0-9]{4}\/([0]?[1-9]|1[012])\/([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '9999/99/99',
                        'placeholder'   => __('yyyy/mm/dd', 'chimpy'),
                        'date_format'   => '%Y/%m/%d',
                    ),
                    '7' => array(
                        'pattern'       => '[0-9]{4}-([0]?[1-9]|1[012])-([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '9999-99-99',
                        'placeholder'   => __('yyyy-mm-dd', 'chimpy'),
                        'date_format'   => '%Y-%m-%d',
                    ),
                    '8' => array(
                        'pattern'       => '[0-9]{4}\.([0]?[1-9]|1[012])\.([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '9999.99.99',
                        'placeholder'   => __('yyyy.mm.dd', 'chimpy'),
                        'date_format'   => '%Y.%m.%d',
                    ),
                    '9' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])\/([0]?[1-9]|1[012])\/[0-9]{2}',
                        'mask'          => '99/99/99',
                        'placeholder'   => __('dd/mm/yy', 'chimpy'),
                        'date_format'   => '%d/%m/%y',
                    ),
                    '10' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])-([0]?[1-9]|1[012])-[0-9]{2}',
                        'mask'          => '99-99-99',
                        'placeholder'   => __('dd-mm-yy', 'chimpy'),
                        'date_format'   => '%d-%m-%y',
                    ),
                    '11' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])\.([0]?[1-9]|1[012])\.[0-9]{2}',
                        'mask'          => '99.99.99',
                        'placeholder'   => __('dd.mm.yy', 'chimpy'),
                        'date_format'   => '%d.%m.%y',
                    ),
                    '12' => array(
                        'pattern'       => '([0]?[1-9]|1[012])\/([0]?[1-9]|1[0-9]|2[0-9]|3[01])\/[0-9]{2}',
                        'mask'          => '99/99/99',
                        'placeholder'   => __('mm/dd/yy', 'chimpy'),
                        'date_format'   => '%m/%d/%y',
                    ),
                    '13' => array(
                        'pattern'       => '([0]?[1-9]|1[012])-([0]?[1-9]|1[0-9]|2[0-9]|3[01])-[0-9]{2}',
                        'mask'          => '99-99-99',
                        'placeholder'   => __('mm-dd-yy', 'chimpy'),
                        'date_format'   => '%m-%d-%y',
                    ),
                    '14' => array(
                        'pattern'       => '([0]?[1-9]|1[012])\.([0]?[1-9]|1[0-9]|2[0-9]|3[01])\.[0-9]{2}',
                        'mask'          => '99.99.99',
                        'placeholder'   => __('mm.dd.yy', 'chimpy'),
                        'date_format'   => '%m.%d.%y',
                    ),
                    '15' => array(
                        'pattern'       => '[0-9]{2}\/([0]?[1-9]|1[012])\/([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '99/99/99',
                        'placeholder'   => __('yy/mm/dd', 'chimpy'),
                        'date_format'   => '%y/%m/%d',
                    ),
                    '16' => array(
                        'pattern'       => '[0-9]{2}-([0]?[1-9]|1[012])-([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '99-99-99',
                        'placeholder'   => __('yy-mm-dd', 'chimpy'),
                        'date_format'   => '%y-%m-%d',
                    ),
                    '17' => array(
                        'pattern'       => '[0-9]{2}\.([0]?[1-9]|1[012])\.([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '99.99.99',
                        'placeholder'   => __('yy.mm.dd', 'chimpy'),
                        'date_format'   => '%y.%m.%d',
                    ),
                ),
                'birthday' => array(
                    '0' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])\/([0]?[1-9]|1[012])',
                        'mask'          => '99/99',
                        'placeholder'   => __('dd/mm', 'chimpy'),
                        'date_format'   => '%d/%m',
                    ),
                    '1' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])-([0]?[1-9]|1[012])',
                        'mask'          => '99/99',
                        'placeholder'   => __('dd-mm', 'chimpy'),
                        'date_format'   => '%d-%m',
                    ),
                    '2' => array(
                        'pattern'       => '([0]?[1-9]|1[0-9]|2[0-9]|3[01])\.([0]?[1-9]|1[012])',
                        'mask'          => '99/99',
                        'placeholder'   => __('dd.mm', 'chimpy'),
                        'date_format'   => '%d.%m',
                    ),
                    '3' => array(
                        'pattern'       => '([0]?[1-9]|1[012])\/([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '99/99',
                        'placeholder'   => __('mm/dd', 'chimpy'),
                        'date_format'   => '%m/%d',
                    ),
                    '4' => array(
                        'pattern'       => '([0]?[1-9]|1[012])-([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '99-99',
                        'placeholder'   => __('mm-dd', 'chimpy'),
                        'date_format'   => '%m-%d',
                    ),
                    '5' => array(
                        'pattern'       => '([0]?[1-9]|1[012])\.([0]?[1-9]|1[0-9]|2[0-9]|3[01])',
                        'mask'          => '99.99',
                        'placeholder'   => __('mm.dd', 'chimpy'),
                        'date_format'   => '%m.%d',
                    ),
                )
            );

            return $patterns[$type][$key][$what];
        }

        /**
         * Get list of all user roles in this installation
         * 
         * @access public
         * @return array
         */
        public static function get_all_user_roles()
        {
            global $wp_roles;

            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }

            return $wp_roles->get_names();
        }

        /**
         * Get next form id (to control multiple forms on the same page)
         * 
         * @access public
         * @return int
         */
        public function get_next_rendered_form_id()
        {
            $this->last_rendered_form++;

            return $this->last_rendered_form;
        }

        /**
         * Return list of forms for dropdown
         * 
         * @access public
         * @return array
         */
        public static function get_list_of_forms($first_empty = true)
        {
            $results = array();

            if ($first_empty) {
                $results[''] = '';
            }

            $opt = get_option('chimpy_options', $results);

            // Check if integration is enabled
            if (!$opt || !is_array($opt) || empty($opt) || !isset($opt['chimpy_api_key']) || !$opt['chimpy_api_key']) {
                return $results;
            }

            // Check if at least one form is defined
            if (!isset($opt['forms']) || empty($opt['forms'])) {
                return $results;
            }

            foreach ($opt['forms'] as $form_key => $form) {
                $results[$form_key] = '#' . $form_key . ' - ' . $form['title'];
            }

            return $results;
        }

        /**
         * Display signup popup (if needed)
         * 
         * @access public
         * @return void
         */
        public function display_popup()
        {
            // Check if page frequency capping is not in effect
            if ($this->popup_page_capping_in_effect) {
                return;
            }

            // Check if user has not opt out of popup (i.e. dismissed it)
            if (isset($_COOKIE['chimpy_d'])) {
                return;
            }

            // Check if popup is enabled and has a form selected
            if (!$this->opt['chimpy_popup_enabled'] || !$this->opt['chimpy_popup_form']) {
                return;
            }

            // Check if integration is enabled and at least one form configured
            if (!$this->opt['chimpy_api_key'] || !isset($this->opt['forms']) || empty($this->opt['forms'])) {
                return;
            }

            // Check if selected form exists within configured forms
            if (!isset($this->opt['forms'][$this->opt['chimpy_popup_form']])) {
                return;
            }

            // Check if form can be displayed on this page
            $is_allowed_page = false;

            if (is_front_page() && in_array(1, $this->opt['chimpy_popup_display_on'])) {
                $is_allowed_page = true;
            }

            if (!$is_allowed_page && is_page() && !is_front_page() && in_array(2, $this->opt['chimpy_popup_display_on'])) {
                $is_allowed_page = true;
            }

            if (!$is_allowed_page && is_single() && in_array(3, $this->opt['chimpy_popup_display_on'])) {
                $is_allowed_page = true;
            }

            if (!$is_allowed_page && !is_front_page() && !is_page() && !is_single() && in_array(4, $this->opt['chimpy_popup_display_on'])) {
                $is_allowed_page = true;
            }

            if (!$is_allowed_page) {
                return;
            }

            // Check time frequency capping (cookie "chimpy_t")
            if (isset($_COOKIE['chimpy_t']) && $this->opt['chimpy_popup_time_limit']) {
                return;
            }

            // Check if form can be displayed
            $form = self::select_form_by_conditions($this->opt['forms'], array((int)$this->opt['chimpy_popup_form']));

            if (!$form) {
                return;
            }

            require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-prepare-form.inc.php';

            $prepared_form = chimpy_prepare_form($form, $this->opt, 'popup', null, true);
            $form_html = $prepared_form['html'];
            $prepared_form_id = $prepared_form['id'];

            ?>
                 <script>
                    jQuery(function()
                    {
                        if (jQuery('#chimpy_popup_open').length > 0) {
                            jQuery('#chimpy_popup_open').click(function() {
                                chimpy_open_popup();
                            });
                        }
                        else {
                            setTimeout(function() {
                                chimpy_open_popup();
                            }, <?php echo ($this->opt['chimpy_popup_delay'] != '' ? ($this->opt['chimpy_popup_delay'] * 1000) : 1); ?>);
                        }

                        function chimpy_open_popup()
                        {
                            if (!jQuery('#sky-form-modal-overlay').length) {
                                jQuery('body').append('<div id="sky-form-modal-overlay" class="sky-form-modal-overlay"></div>');
                            }

                            form = jQuery('#chimpy_popup_<?php echo $prepared_form_id; ?>');
                            jQuery('#sky-form-modal-overlay').fadeIn();
                            //form.fadeIn();
                            form.css('top', '50%').css('left', '50%').css('margin-top', -form.outerHeight()/2).css('margin-left', -form.outerWidth()/2).fadeIn();

                            jQuery('#sky-form-modal-overlay').on('click', function() {
                                chimpy_close_popup();
                            });

                            jQuery('#chimpy_popup_close').click(function() {
                                chimpy_close_popup();
                            });

                            jQuery('#chimpy_dismiss').click(function() {
                                chimpy_write_cookie('chimpy_d', '1', (5 * 365 * 24 * 60));
                                chimpy_close_popup();
                            });

                            <?php if ($this->opt['chimpy_popup_time_limit']): ?>
                                var minutes = <?php echo $this->opt['chimpy_popup_time_limit']; ?>;
                                chimpy_write_cookie('chimpy_t', '1', minutes);
                            <?php endif; ?>

                            <?php if ($this->opt['chimpy_popup_page_limit'] > 1): ?>
                                chimpy_write_cookie('chimpy_p', '1', (30 * 24 * 60));
                            <?php endif; ?>

                        }

                        function chimpy_close_popup()
                        {
                            jQuery('#sky-form-modal-overlay').fadeOut();
                            jQuery('.sky-form-modal').fadeOut();
                        }

                        function chimpy_write_cookie(key, value, minutes)
                        {
                            var date = new Date();
                            date.setTime(date.getTime() + (minutes * 60 * 1000));
                            jQuery.cookie(key, value, { expires: date, path: '/' });
                        }
                    });
                 </script>
            <?php

            echo $form_html;
        }

        /**
         * Track page hits (for popup frequency capping)
         * 
         * @access public
         * @return void
         */
        public function track_page_hits()
        {
            if (isset($_COOKIE['chimpy_p'])) {
                if ($_COOKIE['chimpy_p'] < $this->opt['chimpy_popup_page_limit']) {
                    setcookie('chimpy_p', ($_COOKIE['chimpy_p'] + 1), (time()+60*60*24*30), '/');
                    $this->popup_page_capping_in_effect = true;
                }
            }
        }

        /**
         * Maybe lock content
         * 
         * @access public
         * @return void
         */
        public function content_lock($content)
        {
            // Do not ever lock these pages
            if (is_archive() || is_search() || is_home() || is_front_page() || is_feed()) {
                return $content;
            }

            // Check if integration is enabled and at least one form configured
            if (!$this->opt['chimpy_api_key'] || !isset($this->opt['forms']) || empty($this->opt['forms'])) {
                return $content;
            }

            // Check if locking is enabled and form selected
            if (!$this->opt['chimpy_lock_enabled'] || !$this->opt['chimpy_lock_form']) {
                return $content;
            }

            // Check if user has already subscribed
            if (isset($_COOKIE['chimpy_s'])) {
                return $content;
            }

            // Select form that match the conditions best
            $form = self::select_form_by_conditions($this->opt['forms'], array($this->opt['chimpy_lock_form']));

            if (!$form) {
                return $content;
            }

            require_once CHIMPY_PLUGIN_PATH . '/includes/chimpy-prepare-form.inc.php';

            $form_html = chimpy_prepare_form($form, $this->opt, 'lock');

            return $form_html;
        }

        /**
         * Maybe render checkbox below registration form
         * 
         * @access public
         * @return void
         */
        public function checkbox_render_registration()
        {
            if ($this->opt['chimpy_api_key'] && in_array('1', $this->opt['chimpy_checkbox_add_to']) && $this->opt['chimpy_checkbox_list']) {
                $this->render_checkbox('registration');
            }
        }

        /**
         * Maybe render checkbox below comments form
         * 
         * @access public
         * @return void
         */
        public function checkbox_render_comment()
        {
            if ($this->opt['chimpy_api_key'] && in_array('2', $this->opt['chimpy_checkbox_add_to']) && $this->opt['chimpy_checkbox_list']) {
                $this->render_checkbox('comment');
            }
        }

        /**
         * Render checkbox
         * 
         * @access public
         * @param string $context
         * @return void
         */
        public function render_checkbox($context)
        {
            echo '<p style="margin: 2px 6px 16px 0px;"><label for="chimpy_checkbox_signup"><input type="checkbox" id="chimpy_checkbox_signup" name="chimpy_checkbox_signup" value="1" ' . ($this->opt['chimpy_checkbox_state'] == '1' ? 'checked="checked"' : '') . ' /> ' . $this->opt['chimpy_checkbox_label'] . '</label></p>';
        }

        /**
         * Subscribe from registration checkbox
         * 
         * @access public
         * @param string $user_id
         * @return void
         */
        public function checkbox_subscribe_registration($user_id)
        {
            // Check configuration
            if (!$this->opt['chimpy_api_key'] || !in_array('1', $this->opt['chimpy_checkbox_add_to']) || !$this->opt['chimpy_checkbox_list']) {
                return;
            }

            // Check if checkbox has been checked
            if (!isset($_POST['chimpy_checkbox_signup']) || $_POST['chimpy_checkbox_signup'] != '1') {
                return;
            }

            // Get user
            $user = get_userdata($user_id);

            if (!$user) {
                return;
            }

            // Email
            $email = $user->user_email;

            // Get merge fields
            $merge = array();

            // First name
            if (isset($user->user_firstname) && !empty($user->user_firstname)) {
                $merge['FNAME'] = $user->user_firstname;
            }

            // Last name
            if (isset($user->user_lastname) && !empty($user->user_lastname)) {
                $merge['LNAME'] = $user->user_firstname;
            }

            // Role
            $merge['ROLE'] = array_shift(array_values($user->roles));

            // User name
            $merge['USERNAME'] = $user->user_login;

            $this->subscribe($this->opt['chimpy_checkbox_list'], $email, array(), $merge);
        }

        /**
         * Subscribe from comments checkbox
         * 
         * @access public
         * @param string $comment_id
         * @param string $approved
         * @return void
         */
        public function checkbox_subscribe_comments($comment_id, $approved)
        {
            // Check configuration
            if (!$this->opt['chimpy_api_key'] || !in_array('2', $this->opt['chimpy_checkbox_add_to']) || !$this->opt['chimpy_checkbox_list']) {
                
            }

            // Check if checkbox has been checked
            if (!isset($_POST['chimpy_checkbox_signup']) || $_POST['chimpy_checkbox_signup'] != '1') {
                return;
            }

            // Check if comment is not spam
            if ($approved === 'spam') {
                return;
            }

            // Get visitor email
            $comment = get_comment($comment_id);
            $email = $comment->comment_author_email;

            $this->subscribe($this->opt['chimpy_checkbox_list'], $email, array(), array());
        }

        /**
         * Sync - user created
         * 
         * @access public
         * @param string $user_id
         * @return void
         */
        public function sync_create($user_id)
        {
            // Check configuration
            if (!$this->opt['chimpy_api_key'] || !$this->opt['chimpy_sync_list']) {
                return;
            }

            // Get user
            $user = get_userdata($user_id);

            if (!$user) {
                return;
            }

            // Check if user with this role can be synced
            $proceed = false;

            foreach ($user->roles as $role) {
                if (in_array($role, $this->opt['chimpy_sync_roles'])) {
                    $proceed = true;
                }
            }

            if (!$proceed) {
                return;
            }

            // Email
            $email = $user->user_email;

            // Get merge fields
            $merge = array();

            // First name
            if (isset($user->user_firstname) && !empty($user->user_firstname)) {
                $merge['FNAME'] = $user->user_firstname;
            }

            // Last name
            if (isset($user->user_lastname) && !empty($user->user_lastname)) {
                $merge['LNAME'] = $user->user_lastname;
            }

            // Role
            $merge['ROLE'] = array_shift(array_values($user->roles));

            // User name
            $merge['USERNAME'] = $user->user_login;

            // Subscribe user
            if ($response = $this->subscribe($this->opt['chimpy_sync_list'], $email, array(), $merge, true)) {
                update_user_meta($user_id, 'chimpy_sync_key', $response['leid']);
            }
        }

        /**
         * Sync - user updated
         * 
         * @access public
         * @param string $user_id
         * @param object $user_old
         * @return void
         */
        public function sync_update($user_id, $user_old)
        {
            // Check configuration
            if (!$this->opt['chimpy_api_key'] || !$this->opt['chimpy_sync_list']) {
                return;
            }

            // Get user
            $user = get_userdata($user_id);

            if (!$user) {
                return;
            }

            // Check if user with this role can be synced
            $proceed = false;

            foreach ($user->roles as $role) {
                if (in_array($role, $this->opt['chimpy_sync_roles'])) {
                    $proceed = true;
                }
            }

            if (!$proceed) {
                return;
            }

            // Check if user has been synced previously
            $sync_key = get_user_meta($user_id, 'chimpy_sync_key', true);

            if ($sync_key == '') {
                $this->sync_create($user_id);
                return;
            }

            // Get new values and check if anything changed
            $changes = false;
            $merge = array();

            // Email
            $email = $user->user_email;
            $email_old = $user_old->user_email;

            if ($email != $email_old) {
                $merge['new-email'] = $email;
                $changes = true;
            }

            // First name
            $first_name = (isset($user->user_firstname) && !empty($user->user_firstname)) ? $user->user_firstname : '';
            $first_name_old = (isset($user_old->user_firstname) && !empty($user_old->user_firstname)) ? $user_old->user_firstname : '';

            if ($first_name != $first_name_old) {
                $merge['FNAME'] = $first_name;
                $changes = true;
            }

            // Last name
            $last_name = (isset($user->user_lastname) && !empty($user->user_lastname)) ? $user->user_lastname : '';
            $last_name_old = (isset($user_old->user_lastname) && !empty($user_old->user_lastname)) ? $user_old->user_lastname : '';

            if ($last_name != $last_name_old) {
                $merge['LNAME'] = $last_name;
                $changes = true;
            }

            // Role
            $role = array_shift(array_values($user->roles));
            $role_old = array_shift(array_values($user_old->roles));

            if ($role != $role_old) {
                $merge['ROLE'] = $role;
                $changes = true;
            }

            if ($changes) {
                $this->update_subscription($sync_key, $this->opt['chimpy_sync_list'], array(), $merge);
            }
        }

        /**
         * Sync - user deleted
         * 
         * @access public
         * @param string $user_id
         * @return void
         */
        public function sync_delete($user_id)
        {
            // Check configuration
            if (!$this->opt['chimpy_api_key'] || !$this->opt['chimpy_sync_list']) {
                return;
            }

            // Get user
            $user = get_userdata($user_id);

            if (!$user) {
                return;
            }

            // Check if user with this role can be synced
            $proceed = false;

            foreach ($user->roles as $role) {
                if (in_array($role, $this->opt['chimpy_sync_roles'])) {
                    $proceed = true;
                }
            }

            if (!$proceed) {
                return;
            }

            // Get sync key
            $sync_key = get_user_meta($user_id, 'chimpy_sync_key', true);

            if ($sync_key != '') {
                $this->unsubscribe($this->opt['chimpy_sync_list'], $sync_key, false);
            }
        }

        /**
         * Get visitor IP address
         * 
         * @access public
         * @return string
         */
        public function get_visitor_ip()
        {
            if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
                    $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    return trim($ip[0]);
                }
                else {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            }
            else {
                return $_SERVER['REMOTE_ADDR'];
            }
        }

    }

}

$GLOBALS['Chimpy'] = Chimpy::get_instance();

?>
