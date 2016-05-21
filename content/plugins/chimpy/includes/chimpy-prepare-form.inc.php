<?php
/**
 * Returns generated form to be displayed
 * 
 * @param array $form
 * @param array $opt
 * @param string $context
 * @param mixed $widget_args
 * @param bool $is_popup
 * @return string
 */
if (!function_exists('chimpy_prepare_form')) {
    function chimpy_prepare_form($form, $opt, $context, $widget_args = null, $is_popup = false)
    {
        $chimpy = Chimpy::get_instance();

        $global_form_id = $chimpy->get_next_rendered_form_id();

        // Extract form id and form settings
        reset($form);
        $form_key = key($form);
        $form = array_pop($form);

        // Title
        $title = ($context == 'widget') ? apply_filters('widget_title', $form['title']) : $form['title'];

        // Color scheme class and css override class
        $custom_classes = ($form['color_scheme'] != 'cyan' ? 'sky-form-' . $form['color_scheme'] . ' ' : '') . 'chimpy_custom_css';

        // Should be opened as popup?
        $custom_classes = $is_popup ? $custom_classes . ' sky-form-modal ' : $custom_classes;

        /***********************************************************************
         * MAILCHIMP GROUP SETUP
         */
        $groupings = array();
        $required_groups = array();

        if (isset($form['groups']) && is_array($form['groups']) && !empty($form['groups']) && isset($form['group_method']) && in_array($form['group_method'], array('multi', 'single', 'single_req', 'select', 'select_req'))) {
            foreach ($form['groups'] as $group) {
                $group_parts = preg_split('/%%%/', $group);

                if (!isset($groupings[$group_parts[0]])) {
                    $groupings[$group_parts[0]] = array(
                        'title' => $group_parts[1],
                    );
                }

                $groupings[$group_parts[0]]['children'][] = $group_parts[2];

                if (in_array($form['group_method'], array('single_req', 'select_req'))) {
                    $required_groups[] = $group_parts[0];
                }
            }
        }

        /***********************************************************************
         * VALIDATION RULES & MESSAGES
         */
        $validation_rules = array();
        $validation_messages = array();

        foreach ($form['fields'] as $field) {

            $field_rules = array(
                'required'  => (isset($field['req']) && $field['req'] ? true : false),
                'maxlength' => 200,
            );

            $field_messages = array();

            if (isset($field['req']) && $field['req']) {
                $field_messages['required'] = $opt['chimpy_label_empty_field'];
            }

            // Add type-specific validation rules
            switch ($field['type']) {

                // Email
                case 'email':
                    $field_rules['email'] = true;
                    $field_messages['email'] = $opt['chimpy_label_invalid_format'];
                    break;

                // Text
                case 'text':
                    
                    break;

                // Number
                case 'number':
                    $field_rules['number'] = true;
                    $field_messages['number'] = $opt['chimpy_label_not_number'];
                    break;

                // Radio
                case 'radio':
                    $field_rules['digits'] = true;
                    $field_rules['minlength'] = 1;
                    $field_rules['maxlength'] = 3;
                    break;

                // Dropdown
                case 'dropdown':
                    $field_rules['digits'] = true;
                    $field_rules['minlength'] = 1;
                    $field_rules['maxlength'] = 3;
                    break;

                // Date
                case 'date':
                    $field_rules['pattern'] = Chimpy::get_date_pattern('date', $opt['chimpy_date_format'], 'pattern');
                    $field_messages['pattern'] = $opt['chimpy_label_invalid_format'];
                    break;

                // Birthday
                case 'birthday':
                    $field_rules['pattern'] = Chimpy::get_date_pattern('birthday', $opt['chimpy_birthday_format'], 'pattern');
                    $field_messages['pattern'] = $opt['chimpy_label_invalid_format'];
                    break;

                // ZIP
                case 'zip':
                    $field_rules['digits'] = true;
                    $field_rules['minlength'] = 4;
                    $field_rules['maxlength'] = 5;
                    $field_messages['digits'] = $opt['chimpy_label_invalid_format'];
                    $field_messages['minlength'] = $opt['chimpy_label_invalid_format'];
                    $field_messages['maxlength'] = $opt['chimpy_label_invalid_format'];
                    break;

                // Phone
                case 'phone':

                    // Check if it's US format
                    if (isset($field['us_phone']) && $field['us_phone']) {
                        $field_rules['phoneUS'] = true;
                        $field_messages['phoneUS'] = $opt['chimpy_label_invalid_format'];
                    }

                    break;

                // URL
                case 'url':
                    $field_rules['url'] = true;
                    break;

                default:
                    break;
            }

            $validation_rules['chimpy_' . $context . '_subscribe[custom][' . $field['tag'] . ']'] = $field_rules;
            $validation_messages['chimpy_' . $context . '_subscribe[custom][' . $field['tag'] . ']'] = $field_messages;
        }

        // Also set up required groupings
        if (!empty($required_groups)) {
            foreach ($required_groups as $required_group) {
                $validation_rules['chimpy_' . $context . '_subscribe[groups][' . $required_group . ']']['required'] = true;
                $validation_messages['chimpy_' . $context . '_subscribe[groups][' . $required_group . ']']['required'] = $opt['chimpy_label_empty_field'];
            }
        }

        /***********************************************************************
         * INPUT MASKS
         */
        $masks = array();

        foreach ($form['fields'] as $field) {
            if ($field['type'] == 'date') {
                $masks[] = array(
                    'selector'      => 'chimpy_' . $context . '_field_' . $field['tag'],
                    'template'      => Chimpy::get_date_pattern('date', $opt['chimpy_date_format'], 'mask'),
                    'placeholder'   => Chimpy::get_date_pattern('date', $opt['chimpy_date_format'], 'placeholder'),
                );
            }
            else if ($field['type'] == 'birthday') {
                $masks[] = array(
                    'selector'      => 'chimpy_' . $context . '_field_' . $field['tag'],
                    'template'      => Chimpy::get_date_pattern('birthday', $opt['chimpy_birthday_format'], 'mask'),
                    'placeholder'   => Chimpy::get_date_pattern('birthday', $opt['chimpy_birthday_format'], 'placeholder'),
                );
            }
            else if ($field['type'] == 'phone' && isset($field['us_phone']) && $field['us_phone']) {
                $masks[] = array(
                    'selector'      => 'chimpy_' . $context . '_field_' . $field['tag'],
                    'template'      => '999-999-9999',
                    'placeholder'   => 'X',
                );
            }
        }

        /***********************************************************************
         * START BUILDING FORM
         */
        $html = '';

        // Content lock box
        if ($context == 'lock') {
            $html .= '<div class="chimpy_lock_box">'
                   . '<div class="chimpy_lock_title">' . $opt['chimpy_lock_title'] . '</div>'
                   . '<div class="chimpy_lock_message">' . $opt['chimpy_lock_message'] . '</div>';
        }

        // Ajax URL
        $html .= '<script>'
               . 'var chimpy_ajaxurl = "' . admin_url('admin-ajax.php') . '";'
               . 'var chimpy_max_form_width = ' . (isset($opt['chimpy_width_limit']) && !empty($opt['chimpy_width_limit']) ? (int)$opt['chimpy_width_limit'] : 400) . ';'
               . '</script>';

        // Override CSS
        $html .= '<style>' . $opt['chimpy_css_override'] . '</style>';

        // Container
        $html .= '<div class="chimpy-reset chimpy_' . $context . '_content" style="' . (in_array($context, array('after_posts', 'shortcode', 'lock')) && $opt['chimpy_width_limit'] > 0 ? 'max-width:' . $opt['chimpy_width_limit'] . 'px;' : '') . ($context == 'lock' ? 'display:table;margin:0 auto;' : '') . '">';

        // Before widget (if it's widget)
        if (isset($widget_args['before_widget'])) {
            $html .= $widget_args['before_widget'];
        }

        // Start form
        $html .= '<form id="chimpy_' . $context . '_' . $global_form_id . '" class="chimpy_signup_form sky-form ' . $custom_classes . '">';

        // Form ID
        $html .= '<input type="hidden" name="chimpy_' . $context . '_subscribe[form]" value="' . $form_key . '">';

        // Context
        $html .= '<input type="hidden" id="chimpy_form_context" name="chimpy_' . $context . '_subscribe[context]" value="' . $context . '">';

        // Title
        if ($is_popup) {
            $html .= '<header>' . $title . '</header><i id="chimpy_popup_close" class="icon-append fa-times" style="padding:10px 10px 0 0;cursor:pointer;"></i>';
        }
        else if (!empty($title)) {
            $html .= '<header>' . $title . '</header>';
        }

        // White background (fix for popup)
        $html .= '<div class="chimpy_status_underlay">';

        // Start fieldset
        $html .= '<fieldset>';

        // Text above form
        if (isset($form['above']) && $form['above'] != '') {
            $html .= '<div class="description">' . $form['above'] . '</div>';
        }

        // Fields
        foreach ($form['fields'] as $field) {
            $html .= '<section>';

            // Radio
            if ($field['type'] == 'radio') {

                $html .= '<label class="label">' . $field['name'] . '</label>';

                foreach ($field['choices'] as $choice_key => $choice) {

                    $html .= '<label class="radio">';

                    $html .= '<input type="radio" '
                           . 'id="chimpy_' . $context . '_field_' . $field['tag'] . '_' . $choice_key . '" '
                           . 'name="chimpy_' . $context . '_subscribe[custom][' . $field['tag'] . ']" '
                           . 'value="' . $choice_key . '" >';

                    $html .= '<i></i>' . $choice . '</label>';

                }

            }

            // Dropdown
            else if ($field['type'] == 'dropdown') {

                $html .= '<label class="label">' . $field['name'] . '</label>';

                $html .= '<label class="select">';

                $html .= '<select '
                       . 'id="chimpy_' . $context . '_field_' . $field['tag'] . '" '
                       . 'name="chimpy_' . $context . '_subscribe[custom][' . $field['tag'] . ']" '
                       . '>';

                // Populate with options
                foreach ($field['choices'] as $choice_key => $choice) {
                    $html .= '<option value="' . $choice_key . '">' . $choice . '</option>';
                }

                $html .= '</select><i></i></label>';

            }

            // Any other field (basic text input)
            else {

                if (!$opt['chimpy_labels_inline']) {
                    $html .= '<label class="label">' . $field['name'] . '</label>';
                }

                $html .= '<label class="input">';

                if (isset($field['icon']) && $field['icon']) {
                    $html .= '<i class="icon-append ' . $field['icon'] . '"></i>';
                }

                $html .= '<input type="text" '
                       . 'id="chimpy_' . $context . '_field_' . $field['tag'] . '" '
                       . 'name="chimpy_' . $context . '_subscribe[custom][' . $field['tag'] . ']" '
                       . ($opt['chimpy_labels_inline'] ? 'placeholder="' . $field['name'] . '"' : '')
                       . '></input>';

                $html .= '</label>';

            }

            $html .= '</section>';
        }

        // Groups
        if (!empty($groupings)) {

            // Hide interest groups initially?
            if (isset($opt['chimpy_groups_hidden']) && $opt['chimpy_groups_hidden']) {
                $html .= '<div class="chimpy_interest_groups_hidden" style="display: none;">';
            }

            foreach ($groupings as $grouping_key => $grouping) {

                // Select field begin
                if (in_array($form['group_method'], array('select', 'select_req'))) {
                    $html .= '<section><label class="select">';
                    $html .= '<select id="chimpy_' . $context . '_field_' . $grouping_key . '" '
                           . 'name="chimpy_' . $context . '_subscribe[groups][' . $grouping_key . ']" >'
                           . '<option value="" disabled selected>' . $grouping['title'] . '</option>';
                }
                else {
                    $html .= '<label class="label">' . $grouping['title'] . '</label>';
                }

                foreach ($grouping['children'] as $choice_key => $choice) {

                    // Display checkbox group
                    if ($form['group_method'] == 'multi') {

                            $html .= '<label class="checkbox">';

                            $html .= '<input type="checkbox" '
                                   . 'id="chimpy_' . $context . '_field_' . $grouping_key . '_' . $choice_key . '" '
                                   . 'name="chimpy_' . $context . '_subscribe[groups][' . $grouping_key . '][]" '
                                   . 'value="' . $grouping_key . '%%%' . $grouping['title'] . '%%%' . $choice . '" >';

                            $html .= '<i></i>' . $choice . '</label>';

                    }

                    // Display select field options
                    else if (in_array($form['group_method'], array('select', 'select_req'))) {
                        $html .= '<option value="' . $grouping_key . '%%%' . $grouping['title'] . '%%%' . $choice . '">' . $choice . '</option>';
                    }

                    // Display radio set
                    else {

                            $html .= '<label class="radio">';

                            $html .= '<input type="radio" '
                                   . 'id="chimpy_' . $context . '_field_' . $grouping_key . '_' . $choice_key . '" '
                                   . 'name="chimpy_' . $context . '_subscribe[groups][' . $grouping_key . ']" '
                                   . 'value="' . $grouping_key . '%%%' . $grouping['title'] . '%%%' . $choice . '" >';

                            $html .= '<i></i>' . $choice . '</label>';

                    }

                }

                // Select field end
                if (in_array($form['group_method'], array('select', 'select_req'))) {
                    $html .= '</select><i></i></label></section>';
                }

            }

            // Hide interest groups initially?
            if (isset($opt['chimpy_groups_hidden']) && $opt['chimpy_groups_hidden']) {
                $html .= '</div>';
            }

        }

        // Text below form
        if (isset($form['below']) && $form['below'] != '') {
            $html .= '<div class="description">' . $form['below'] . '</div>';
        }

        // End fieldset
        $html .= '</fieldset>';

        // Processing placeholder
        $html .= '<div id="chimpy_signup_' . $context . '_processing" class="chimpy_signup_processing" style="display: none;"></div>';

        // Something went wrong...
        $html .= '<div id="chimpy_signup_' . $context . '_error" class="chimpy_signup_error" style="display: none;"><div></div></div>';

        // Success
        $html .= '<div id="chimpy_signup_' . $context . '_success" class="chimpy_signup_success" style="display: none;"><div></div></div>';

        $html .= '</div>';

        // Start footer
        $html .= '<footer>';

        // Dismiss link
        if ($is_popup && $opt['chimpy_popup_allow_dismissing']) {
            $html .= '<div id="chimpy_dismiss" class="dismiss">' . $opt['chimpy_label_dismiss_popup'] . '</div>';
        }

        // Submit button
        $html .= '<button type="button" id="chimpy_' . $context . '_submit" class="button">' . $form['button'] . '</button>';

        // End footer
        $html .= '</footer>';

        // End form
        $html .= '</form>';

        // Form validation rules
        $html .= '<script type="text/javascript">'
               . 'jQuery(function() {'
               . 'jQuery("#chimpy_' . $context . '_' . $global_form_id . '").validate({'
               . 'rules: ' . json_encode($validation_rules) . ','
               . 'messages: ' . json_encode($validation_messages) . ','
               . 'errorPlacement: function(error, element) { error.insertAfter(element.parent()); }'
               . '});';

        if (isset($masks) && !empty($masks)) {
            foreach ($masks as $mask) {
                $html .= 'jQuery("#' . $mask['selector'] . '").mask("' . $mask['template'] . '", {placeholder:"' . $mask['placeholder'] . '"});';
            }
        }

        $html .= '});'
               . '</script>';

        // After widget (if it's widget)
        if (isset($widget_args['after_widget'])) {
            $html .= $widget_args['after_widget'];
        }

        // End container
        $html .= '</div>';

        // Content lock box
        if ($context == 'lock') {
            $html .= '</div>';
        }

        if (!$is_popup) {
            return $html;
        }
        else {
            return array(
                'html'      => $html,
                'id'   => $global_form_id
            );
        }
    }
}

?>