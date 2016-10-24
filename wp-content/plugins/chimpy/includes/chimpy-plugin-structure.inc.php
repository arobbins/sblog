<?php

/*
 * Returns configuration for this plugin
 * 
 * @return array
 */
if (!function_exists('chimpy_plugin_settings')) {
    function chimpy_plugin_settings()
    {
        $settings = array(
            'chimpy' => array(
                'title' => __('MailChimp', 'chimpy'),
                'page_title' => __('MailChimp', 'chimpy'),
                'capability' => 'manage_options',
                'slug' => 'chimpy',
                'children' => array(
                    'settings' => array(
                        'title' => __('Settings', 'chimpy'),
                        'icon' => '<i class="fa fa-cogs" style="font-size: 0.8em;"></i>',
                        'children' => array(
                            'integration' => array(
                                'title' => __('Integration', 'chimpy'),
                                'children' => array(
                                    'api_key' => array(
                                        'title' => __('MailChimp API key', 'chimpy'),
                                        'type' => 'text',
                                        'default' => '',
                                        'validation' => array(
                                            'rule' => 'function',
                                            'empty' => true,
                                        ),
                                        'hint' => __('<p>API key is required for this plugin to communicate with MailChimp servers.</p> <p>To get an API key, login to your MailChimp account and navigate to Account Settings > Extras > API keys.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                            'general_settings' => array(
                                'title' => __('General Settings', 'chimpy'),
                                'children' => array(
                                    'double_optin' => array(
                                        'title' => __('Require double opt-in', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 1,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>Controls whether a double opt-in confirmation message is sent before user is actually subscribed.</p>', 'chimpy'),
                                    ),
                                    'send_welcome' => array(
                                        'title' => __('Send welcome email', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 1,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>If double opt-in is disabled and this setting is enabled, MailChimp will send your lists Welcome Email on user subscription. This has no effect if double opt-in is enabled.</p>', 'chimpy'),
                                    ),
                                    'replace_groups' => array(
                                        'title' => __('Replace interest groups on update', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>Setting is used by MailChimp to determine whether interest groups are added to a set of existing interest groups of particular user or they are completely replaced with new interest groups. This is applicable when user is already subscribed to the list and profile is being updated.</p>', 'chimpy'),
                                    ),
                                    'update_existing' => array(
                                        'title' => __('Update existing subscribers', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 1,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>Control whether existing subscribers are updated when they fill out the signup form again or error is displayed. This has no effect for Sync functionality.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                            'settings_styling' => array(
                                'title' => __('Form Styling', 'chimpy'),
                                'children' => array(
                                    'labels_inline' => array(
                                        'title' => __('Display field labels inline', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 1,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>Controls whether signup form field labels are displayed inside fields as value placeholders (inline) or above fields.</p>', 'chimpy'),
                                    ),
                                    'groups_hidden' => array(
                                        'title' => __('Hide interest groups initially', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>Interest groups can take significant amount of space. If this option is enabled, interest group fields will be hidden until user starts filling in the form (clicks anywhere on the form).</p>', 'chimpy'),
                                    ),
                                    'width_limit' => array(
                                        'title' => __('Max form width in pixels', 'chimpy'),
                                        'type' => 'text',
                                        'default' => '',
                                        'validation' => array(
                                            'rule' => 'number',
                                            'empty' => true,
                                        ),
                                        'hint' => __('<p>If your website features a wide layout, you may wish to set the max width for the form to look better. This has no effect for forms displayed as popup.</p>', 'chimpy'),
                                    ),
                                    'css_override' => array(
                                        'title' => __('Override CSS', 'chimpy'),
                                        'type' => 'textarea',
                                        'default' => '.chimpy_custom_css {}',
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                        'hint' => __('<p>You can further customize the appearance of your signup forms by adding custom CSS to this field. To make changes to the style, simply use CSS class chimpy_custom_css as a basis.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'forms' => array(
                        'title' => __('Forms', 'chimpy'),
                        'icon' => '<i class="fa fa-edit" style="font-size: 0.8em;"></i>',
                        'children' => array(
                            'forms' => array(
                                'title' => __('Manage Signup Forms', 'chimpy'),
                                'children' => array(
                                ),
                            ),
                        ),
                    ),
                    'popup' => array(
                        'title' => __('Popup', 'chimpy'),
                        'icon' => '<i class="fa fa-comment" style="font-size: 0.8em;"></i>',
                        'children' => array(
                            'popup_general_settings' => array(
                                'title' => __('Display Popup', 'chimpy'),
                                'children' => array(
                                    'popup_enabled' => array(
                                        'title' => __('Enable popup', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>Displays popup with mailing list signup form as configured. You can use form conditions for more granual control.</p>', 'chimpy'),
                                    ),
                                    'popup_form' => array(
                                        'title' => __('Form to display', 'chimpy'),
                                        'type' => 'dropdown',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'option',
                                            'empty' => true
                                        ),
                                        'values' => Chimpy::get_list_of_forms(),
                                        'hint' => __('<p>Select one of the forms created under tab Forms to display as a popup.</p>', 'chimpy'),
                                    ),
                                    'popup_delay' => array(
                                        'title' => __('Open delay in seconds', 'chimpy'),
                                        'type' => 'text',
                                        'default' => '5',
                                        'validation' => array(
                                            'rule' => 'number',
                                            'empty' => true,
                                        ),
                                        'hint' => __('<p>If set, form will be opened after a specified number of seconds counting from the complete page load.</p>', 'chimpy'),
                                    ),
                                    'popup_display_on' => array(
                                        'title' => __('Display in', 'chimpy'),
                                        'type' => 'checkbox_set',
                                        'default' => array('1'),
                                        'validation' => array(
                                            'rule' => 'multiple_any',
                                            'empty' => true
                                        ),
                                        'values' => array(
                                            '1' => __('Home page', 'chimpy'),
                                            '2' => __('Pages', 'chimpy'),
                                            '3' => __('Posts', 'chimpy'),
                                            '4' => __('Everywhere else', 'chimpy'),
                                        ),
                                        'hint' => __('<p>Page types where you want the popup to be fired.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                            'popup_frequency' => array(
                                'title' => __('Frequency Capping', 'chimpy'),
                                'children' => array(
                                    'popup_page_limit' => array(
                                        'title' => __('Page frequency', 'chimpy'),
                                        'type' => 'text',
                                        'default' => '1',
                                        'validation' => array(
                                            'rule' => 'number',
                                            'empty' => true,
                                        ),
                                        'hint' => __('<p>Popup will be displayed once in every X pages opened. Leaving blank or setting value to 1 will have the same effect.</p>', 'chimpy'),
                                    ),
                                    'popup_time_limit' => array(
                                        'title' => __('Time frequency in minutes', 'chimpy'),
                                        'type' => 'text',
                                        'default' => '5',
                                        'validation' => array(
                                            'rule' => 'number',
                                            'empty' => true,
                                        ),
                                        'hint' => __('<p>Popup will be displayed once in every X minutes.</p>', 'chimpy'),
                                    ),
                                    'popup_allow_dismissing' => array(
                                        'title' => __('Allow dismissing', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 1,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>If enabled, users will be provided with an option (a link to click) to hide the popup forever without filling in the form.</p>', 'chimpy'),
                                    ),
                                    'label_dismiss_popup' => array(
                                        'title' => __('Dismiss link text', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('Never display this again', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                        'hint' => __('<p>If popup dismissing is enabled, this will be the text of the dismiss link.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                         ),
                    ),
                    'below' => array(
                        'title' => __('Posts', 'chimpy'),
                        'icon' => '<i class="fa fa-file-text" style="font-size: 0.8em;"></i>',
                        'children' => array(
                            'below_settings' => array(
                                'title' => __('Display Below Every Post', 'chimpy'),
                                'children' => array(
                                    'after_posts_post_types' => array(
                                        'title' => __('Display signup form below', 'chimpy'),
                                        'type' => 'checkbox_set',
                                        'default' => array(),
                                        'validation' => array(
                                            'rule' => 'multiple_any',
                                            'empty' => true
                                        ),
                                        'values' => array(
                                            '1' => __('Posts', 'chimpy'),
                                            '2' => __('Pages', 'chimpy'),
                                        ),
                                        'hint' => __('<p>Page types where you want the form to be displayed below the main content. You can use form conditions for more granular control.</p>', 'chimpy'),
                                    ),
                                    'after_posts_allowed_forms' => array(
                                        'title' => __('Allow only these forms', 'chimpy'),
                                        'type' => 'dropdown_multi',
                                        'default' => array(),
                                        'validation' => array(
                                            'rule' => 'multiple_any',
                                            'empty' => true
                                        ),
                                        'values' => Chimpy::get_list_of_forms(false),
                                        'hint' => __('<p>If you leave this field empty, all forms will have a chance to be displayed. The final form will be selected using form conditions as configured under tab Forms.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'lock' => array(
                        'title' => __('Locking', 'chimpy'),
                        'icon' => '<i class="fa fa-lock" style="font-size: 0.8em;"></i>',
                        'children' => array(
                            'lock_general_settings' => array(
                                'title' => __('Subscribe To Unlock', 'chimpy'),
                                'children' => array(
                                    'lock_enabled' => array(
                                        'title' => __('Enable content locking', 'chimpy'),
                                        'type' => 'checkbox',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'bool',
                                            'empty' => false
                                        ),
                                        'hint' => __('<p>If enabled, posts or pages that match form display conditions will be locked. Only subscribers will be able to access that content. This functionality uses cookies for tracking. If subscriber clears browser cookies, content will be locked again.</p> <p>Use form conditions to control which pages will be locked.</p>', 'chimpy'),
                                    ),
                                    'lock_form' => array(
                                        'title' => __('Form to display', 'chimpy'),
                                        'type' => 'dropdown',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'option',
                                            'empty' => true
                                        ),
                                        'values' => Chimpy::get_list_of_forms(),
                                        'hint' => __('<p>Select one of the forms created under tab Forms to display instead of locked content.</p>', 'chimpy'),
                                    ),
                                    'lock_title' => array(
                                        'title' => __('Title', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('Subscribe To Unlock', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                        'hint' => __('<p>Title to display above signup form.</p>', 'chimpy'),
                                    ),
                                    'lock_message' => array(
                                        'title' => __('Message', 'chimpy'),
                                        'type' => 'textarea',
                                        'default' => __('Subscribe now to become a premium member and gain access to premium content.', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                        'hint' => __('<p>Message to display above signup form.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'checkboxes' => array(
                        'title' => __('Checkbox', 'chimpy'),
                        'icon' => '<i class="fa fa-check-square-o" style="font-size: 0.8em;"></i>',
                        'children' => array(
                            'checkboxes_settings' => array(
                                'title' => __('Display Checkbox Below Forms', 'chimpy'),
                                'children' => array(
                                    'checkbox_add_to' => array(
                                        'title' => __('Add signup checkbox to', 'chimpy'),
                                        'type' => 'checkbox_set',
                                        'default' => array(),
                                        'validation' => array(
                                            'rule' => 'multiple_any',
                                            'empty' => true
                                        ),
                                        'values' => array(
                                            '1' => __('Registration Form', 'chimpy'),
                                            '2' => __('Comments Form', 'chimpy'),
                                        ),
                                        'hint' => __('<p>Select WordPress forms where you would like mailing list opt-in checkbox to appear.</p>', 'chimpy'),
                                    ),
                                    'checkbox_label' => array(
                                        'title' => __('Checkbox label', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('Subscribe to our newsletter', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                        'hint' => __('<p>Label to display next to the checkbox.</p>', 'chimpy'),
                                    ),
                                    'checkbox_state' => array(
                                        'title' => __('Default state', 'chimpy'),
                                        'type' => 'dropdown',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'option',
                                            'empty' => false
                                        ),
                                        'values' => array(
                                            '0' => __('Not Checked', 'chimpy'),
                                            '1' => __('Checked', 'chimpy'),
                                        ),
                                        'hint' => __('<p>Default checkbox state.</p>', 'chimpy'),
                                    ),
                                    'checkbox_list' => array(
                                        'title' => __('Mailing list', 'chimpy'),
                                        'type' => 'text',
                                        'default' => '',
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                        'hint' => __('<p>Select one of your MailChimp mailing lists to subscribe visitors to.</p> <p>To save additional fields, create merge tags FNAME, LNAME, ROLE and USERNAME under your list settings in MailChimp.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'sync' => array(
                        'title' => __('Sync', 'chimpy'),
                        'icon' => '<i class="fa fa-refresh" style="font-size: 0.8em;"></i>',
                        'children' => array(
                            'sync_settings' => array(
                                'title' => __('Synchronize User Data', 'chimpy'),
                                'children' => array(
                                    'sync_roles' => array(
                                        'title' => __('User roles to sync', 'chimpy'),
                                        'type' => 'checkbox_set',
                                        'default' => array(),
                                        'validation' => array(
                                            'rule' => 'multiple_any',
                                            'empty' => true
                                        ),
                                        'values' => Chimpy::get_all_user_roles(),
                                        'hint' => __('<p>Select user roles that you would like to synchronize with MailChimp.</p> <p>This feature sends data to MailChimp when one of the following actions occur - user is created, user is updated and user is deleted.</p>', 'chimpy'),
                                    ),
                                    'sync_list' => array(
                                        'title' => __('Mailing list', 'chimpy'),
                                        'type' => 'text',
                                        'default' => '',
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                        'hint' => __('<p>Select one of your MailChimp mailing lists to sync user data with.</p> <p>To save additional fields, create merge tags FNAME, LNAME, ROLE and USERNAME under your list settings in MailChimp.</p>', 'chimpy'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'localization' => array(
                        'title' => __('Localization', 'chimpy'),
                        'icon' => '<i class="fa fa-font" style="font-size: 0.8em;"></i>',
                        'children' => array(
                            'localization' => array(
                                'title' => __('Date Format', 'chimpy'),
                                'children' => array(
                                    'date_format' => array(
                                        'title' => __('Date field format', 'chimpy'),
                                        'type' => 'dropdown',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'option',
                                            'empty' => false
                                        ),
                                        'values' => array(
                                            '0' => __('dd/mm/yyyy', 'chimpy'),
                                            '1' => __('dd-mm-yyyy', 'chimpy'),
                                            '2' => __('dd.mm.yyyy', 'chimpy'),
                                            '3' => __('mm/dd/yyyy', 'chimpy'),
                                            '4' => __('mm-dd-yyyy', 'chimpy'),
                                            '5' => __('mm.dd.yyyy', 'chimpy'),
                                            '6' => __('yyyy/mm/dd', 'chimpy'),
                                            '7' => __('yyyy-mm-dd', 'chimpy'),
                                            '8' => __('yyyy.mm.dd', 'chimpy'),
                                            '9' => __('dd/mm/yy', 'chimpy'),
                                            '10' => __('dd-mm-yy', 'chimpy'),
                                            '11' => __('dd.mm.yy', 'chimpy'),
                                            '12' => __('mm/dd/yy', 'chimpy'),
                                            '13' => __('mm-dd-yy', 'chimpy'),
                                            '14' => __('mm.dd.yy', 'chimpy'),
                                            '15' => __('yy/mm/dd', 'chimpy'),
                                            '16' => __('yy-mm-dd', 'chimpy'),
                                            '17' => __('yy.mm.dd', 'chimpy'),
                                        ),
                                    ),
                                    'birthday_format' => array(
                                        'title' => __('Birthday field format', 'chimpy'),
                                        'type' => 'dropdown',
                                        'default' => 0,
                                        'validation' => array(
                                            'rule' => 'option',
                                            'empty' => false
                                        ),
                                        'values' => array(
                                            '0' => __('dd/mm', 'chimpy'),
                                            '1' => __('dd-mm', 'chimpy'),
                                            '2' => __('dd.mm', 'chimpy'),
                                            '3' => __('mm/dd', 'chimpy'),
                                            '4' => __('mm-dd', 'chimpy'),
                                            '5' => __('mm.dd', 'chimpy'),
                                        ),
                                    ),
                                ),
                            ),
                            'labels_settings' => array(
                                'title' => __('Labels', 'chimpy'),
                                'children' => array(
                                    'label_success' => array(
                                        'title' => __('Subscribed successfully', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('Thank you for signing up!', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                    ),
                                    'label_empty_field' => array(
                                        'title' => __('(Error) Required field empty', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('Please enter a value', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                    ),
                                    'label_invalid_format' => array(
                                        'title' => __('(Error) Invalid format', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('Invalid format', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                    ),
                                    'label_not_number' => array(
                                        'title' => __('(Error) Value not a number', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('Please enter a valid number', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                    ),
                                    'label_already_subscribed' => array(
                                        'title' => __('(Error) Already subscribed', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('You are already subscribed to this list', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                    ),
                                    'label_error' => array(
                                        'title' => __('(Error) Unknown error', 'chimpy'),
                                        'type' => 'text',
                                        'default' => __('Unknown error. Please try again later.', 'chimpy'),
                                        'validation' => array(
                                            'rule' => 'string',
                                            'empty' => true
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'help' => array(
                        'title' => '',
                        'icon' => '<i class="fa fa-question" style="font-size: 1em;"></i>',
                        'children' => array(
                            'help_display' => array(
                                'title' => __('Displaying Forms', 'chimpy'),
                                'children' => array(
                                ),
                            ),
                            /*'help_targeting' => array(
                                'title' => __('Conditions & Targeting', 'chimpy'),
                                'children' => array(
                                ),
                            ),*/
                            'help_contact' => array(
                                'title' => __('Get In Touch', 'chimpy'),
                                'children' => array(
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );

        return $settings;
    }
}

?>