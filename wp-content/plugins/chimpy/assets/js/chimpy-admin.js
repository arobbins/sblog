/**
 * Chimpy Plugin Admin JavaScript
 */
jQuery(document).ready(function() {

    /**
     * Admin hints
     */
    jQuery('form').each(function(){
        jQuery(this).find(':input').each(function(){
            if (typeof chimpy_hints !== 'undefined' && typeof chimpy_hints[jQuery(this).prop('id')] !== 'undefined') {
                jQuery(this).parent().parent().find('th').append('<div class="chimpy_tip" title="' + chimpy_hints[jQuery(this).prop('id')] + '"><i class="fa fa-question"></div>');
            }
        });
        jQuery(this).find(':checkbox').each(function(){
            if (jQuery(this).prop('id').indexOf('_1') !== -1 || jQuery(this).prop('id').indexOf('_administrator') !== -1) {
                if (jQuery(this).prop('id').indexOf('_1') !== -1) {
                    var this_tip_key = jQuery(this).prop('id').replace('_1', '');
                }
                else {
                    var this_tip_key = jQuery(this).prop('id').replace('_administrator', '');
                }

                if (typeof chimpy_hints !== 'undefined' && typeof chimpy_hints[this_tip_key] !== 'undefined') {
                    jQuery(this).parent().parent().parent().parent().find('th').append('<div class="chimpy_tip" title="' + chimpy_hints[this_tip_key] + '"><i class="fa fa-question"></div>');
                }
            }
        });
    });
    jQuery.widget('ui.tooltip', jQuery.ui.tooltip, {
        options: {
            content: function() {
                return jQuery(this).prop('title');
            }
        }
    });
    jQuery('.chimpy_tip').tooltip();

    /**
     * Hide unused condition fields
     */
    function chimpy_hide_unused_condition_fields() {

        jQuery('#chimpy_forms_list').children().each(function() {

            var non_selected_keys = {};

            jQuery(this).find('.form_condition_key').each(function() {
                jQuery(this).children().children().each(function() {
                    if (!jQuery(this).is(':selected')) {
                        var this_key = jQuery(this).val().replace('_not', '');

                        if (typeof non_selected_keys[this_key] !== 'undefined') {
                            non_selected_keys[this_key]++;
                        }
                        else {
                            non_selected_keys[this_key] = 1;
                        }
                    }
                });
            });
            for (var prop in non_selected_keys) {
                if (non_selected_keys[prop] !== 1) {
                    jQuery(this).find('.form_condition_key').parent().parent().parent().parent().parent().find('.form_condition_value_' + prop).each(function() {
                        jQuery(this).parent().parent().hide();
                    });
                }
            }

            jQuery(this).find('.form_condition_key').change(function() {
                var new_form_condition_key = jQuery(this).children().children().filter(':selected').val();

                jQuery(this).parent().parent().parent().find('.form_condition_value').each(function() {
                    jQuery(this).val('');

                    if (!jQuery(this).hasClass('form_condition_value_' + new_form_condition_key)) {
                        jQuery(this).parent().parent().hide();
                    }
                    else {
                        jQuery(this).parent().parent().show();
                    }
                });
            });

        });
    }

    chimpy_hide_unused_condition_fields();

    /**
     * Load service status
     */
    jQuery('#chimpy_api_key').each(function() {
        jQuery(this).parent().parent().after('<tr valign="top"><th scope="row">' + chimpy_label_integration_status + '</th><td><p id="chimpy-status" class="chimpy_loading"><span class="chimpy_loading_icon"></span>' + chimpy_label_connecting_to_mailchimp + '</p></td></tr>');
    });

 


    jQuery('#chimpy-status').each(function() {
        jQuery.post(
                ajaxurl,
                {
                    'action': 'chimpy_mailchimp_status'
                },
        function(response) {

            try {
                var result = jQuery.parseJSON(response);
            }
            catch (err) {
                jQuery('#chimpy-status').html(chimpy_label_bad_ajax_response);
            }

            if (result) {
                jQuery('#chimpy-status').html(result['message']);
            }
        }
        );
    });

    /**
     * Set up accordion (form management)
     */
    jQuery('#chimpy_forms_list').accordion({
        header: '> div > h4',
        heightStyle: 'content'
    }).sortable({
        handle: 'h4',
        stop: function(event, ui) {
            regenerate_accordion_handle_titles();
        }
    });

    /**
     * Make pages, posts and post_categories fields chosen on form setup
     */
    jQuery('.form_condition_value_pages').each(function() {
        jQuery(this).chosen({
            no_results_text: chimpy_label_no_results_match_pages,
            placeholder_text_multiple: chimpy_label_select_some_pages,
            width: '400px'
        });
    });
    jQuery('.form_condition_value_posts').each(function() {
        jQuery(this).chosen({
            no_results_text: chimpy_label_no_results_match_posts,
            placeholder_text_multiple: chimpy_label_select_some_posts,
            width: '400px'
        });
    });
    jQuery('.form_condition_value_categories').each(function() {
        jQuery(this).chosen({
            no_results_text: chimpy_label_no_results_match_post_categories,
            placeholder_text_multiple: chimpy_label_select_some_post_categories,
            width: '400px'
        });
    });

    /**
     * Other "chosen" fields
     */
    jQuery('#chimpy_after_posts_allowed_forms').each(function() {
        jQuery(this).chosen({
            no_results_text: chimpy_label_no_results_match_forms,
            placeholder_text_multiple: chimpy_label_select_some_forms,
            width: '400px'
        });
    });

    /**
     * Dynamicaly change form title on the accordion handle
     */
    jQuery('.chimpy_forms_title_field').each(function() {
        jQuery(this).keyup(function() {
            jQuery(this).parent().parent().parent().parent().parent().parent().find('.chimpy_forms_title_name').html('- ' + jQuery(this).val());
        });
        jQuery(this).change(function() {
            jQuery(this).parent().parent().parent().parent().parent().parent().find('.chimpy_forms_title_name').html('- ' + jQuery(this).val());
        });
    });

    /**
     * Forms page - lists and groups
     */
    if (jQuery('#chimpy_forms_list').length) {

        // Disable submit button until lists are loaded
        jQuery('#submit').prop('disabled', true);
        jQuery('#submit').prop('title', chimpy_label_still_connecting_to_mailchimp);

        jQuery.post(
                ajaxurl,
                {
                    'action': 'chimpy_get_lists_with_multiple_groups_and_fields',
                    'data': chimpy_selected_lists
                },
        function(response) {

            try {
                var result = jQuery.parseJSON(response);
            }
            catch (err) {
                jQuery('.chimpy_forms_field_list_groups').html(chimpy_label_bad_ajax_response);
                jQuery('.chimpy_forms_field_fields').html(chimpy_label_bad_ajax_response);
            }

            if (result && typeof result['message'] === 'object') {

                /**
                 * Render lists and groups selection
                 */
                var current_field_id = 0;

                jQuery('.chimpy_forms_field_list_groups').each(function() {

                    current_field_id++;

                    var current_selected_list = (typeof chimpy_selected_lists[current_field_id] !== 'undefined' && typeof chimpy_selected_lists[current_field_id]['list'] !== 'undefined' ? chimpy_selected_lists[current_field_id]['list'] : null);

                    // List selection
                    if (typeof result['message']['lists'] === 'object') {
                        var fields = '';

                        for (var prop in result['message']['lists']) {
                            fields += '<option value="' + prop + '" ' + (current_selected_list !== null && current_selected_list === prop ? 'selected="selected"' : '') + '>' + result['message']['lists'][prop] + '</option>';
                        }

                        var field_field = '<select id="chimpy_forms_list_field_' + current_field_id + '" name="chimpy_options[forms][' + current_field_id + '][list_field]" class="chimpy-field chimpy_forms_mailing_list">' + fields + '</select>';
                        var field_html = '<table class="form-table" style="margin-bottom: 0px;"><tbody><tr valign="top"><th scope="row">' + chimpy_label_mailing_list + '</th><td>' + field_field + '</td></tr></tbody></table>';

                        jQuery(this).replaceWith(field_html);

                        // Make it chosen!
                        jQuery('#chimpy_forms_list_field_' + current_field_id).chosen({
                            no_results_text: chimpy_label_no_results_match_list,
                            placeholder_text_single: chimpy_label_select_mailing_list,
                            width: '400px'
                        }).change(function(evt, params) {
                            var current_field_id = jQuery(this).prop('id').replace('chimpy_forms_list_field_', '');
                            chimpy_update_groups_and_tags(current_field_id, params['selected']);
                        });

                        // Groups selection
                        if (typeof result['message']['groups'] === 'object') {
                            var fields = '';

                            var current_selected_groups = (typeof chimpy_selected_lists[current_field_id] !== 'undefined' && typeof chimpy_selected_lists[current_field_id]['groups'] === 'object' ? chimpy_selected_lists[current_field_id]['groups'] : null);

                            // Check if list is selected
                            if (current_selected_list !== null && typeof result['message']['groups'][current_selected_list] === 'object') {
                                for (var prop in result['message']['groups'][current_selected_list]) {
                                    fields += '<option value="' + prop + '" ' + (current_selected_groups !== null && current_selected_groups.indexOf(prop) !== -1 ? 'selected="selected"' : '') + '>' + result['message']['groups'][current_selected_list][prop] + '</option>';
                                }
                            }
                            else {
                                fields += '<option value=""></option>';
                            }

                            var field_field = '<select multiple id="chimpy_forms_groups_' + current_field_id + '" name="chimpy_options[forms][' + current_field_id + '][groups][]" class="chimpy-field chimpy_forms_groups">' + fields + '</select>';
                            var field_html = '<tr valign="top"><th scope="row">' + chimpy_label_groups + '</th><td>' + field_field + '</td></tr>';

                            jQuery('#chimpy_forms_list_field_' + current_field_id).parent().parent().after(field_html);

                            // Make it chosen!
                            jQuery('#chimpy_forms_groups_' + current_field_id).chosen({
                                no_results_text: chimpy_label_no_results_match_groups,
                                placeholder_text_multiple: chimpy_label_select_some_groups,
                                width: '400px'
                            });

                        }
                    }

                    chimpy_forms_page_hints();

                });

                /**
                 * Render merge fiels selection
                 */
                var current_field_id = 0;

                if (typeof result['message']['merge'] === 'object') {

                    jQuery('.chimpy_forms_field_fields').each(function() {

                        current_field_id++;

                        var current_selected_list = (typeof chimpy_selected_lists[current_field_id] !== 'undefined' && typeof chimpy_selected_lists[current_field_id]['list'] !== 'undefined' ? chimpy_selected_lists[current_field_id]['list'] : null);
                        var current_selected_merge = (typeof chimpy_selected_lists[current_field_id] !== 'undefined' && typeof chimpy_selected_lists[current_field_id]['merge'] !== 'undefined' ? chimpy_selected_lists[current_field_id]['merge'] : null);

                        render_forms_merge_fields_table(current_field_id, current_selected_list, current_selected_merge, result['message']['merge']);
                    });

                }

                /**
                 * Update accordion height
                 */
                jQuery('#chimpy_forms_list').accordion('refresh');

                /**
                 * Enable add set button
                 */
                jQuery('#chimpy_add_set').prop('disabled', false);
                jQuery('#chimpy_add_set').prop('title', '');

                /**
                 * Enable submit button
                 */
                jQuery('#submit').prop('disabled', false);
                jQuery('#submit').prop('title', '');

            }

        });
    }

    /**
     * Render merge fields table
     */
    function render_forms_merge_fields_table(current_field_id, current_selected_list, current_selected_merge, merge_fields) {

        if (current_selected_list !== null) {
            merge_fields = merge_fields[current_selected_list];
        }
        else {
            merge_fields = [];
        }

        // Generate options
        var field_options = '<option value></option>';

        if (typeof merge_fields === 'object') {
            for (var prop in merge_fields) {
                if (merge_fields.hasOwnProperty(prop)) {
                    field_options += '<option value="' + prop + '">' + merge_fields[prop]['name'] + ' (' + prop + ', ' + merge_fields[prop]['type'] + (merge_fields[prop]['req'] ? ', ' + 'req' : '') + ')</option>';
                }
            }
        }

        // Set up name field depending on page type
        var input_field = '<input type="text" class="chimpy_name_input" name="chimpy_options[forms][' + current_field_id + '][fields][%%%id%%%][name]" id="chimpy_forms_fields_name_' + current_field_id + '_%%%id%%%" value="%%%value%%%" />';

        // Set up list of Font Awesome icons
        var font_awesome_list = '<option value=""></option>';

        if (typeof chimpy_font_awesome_icons === 'object') {
            for (var prop in chimpy_font_awesome_icons) {
                font_awesome_list += '<option value="' + prop + '">' + chimpy_font_awesome_icons[prop] + '</option>';
            }
        }

        // Begin table
        var fields_table = '<table id="chimpy_fields_table_' + current_field_id + '" class="chimpy_fields_table"><thead><tr><th>' + chimpy_label_fields_name + '</th><th>' + chimpy_label_fields_tag + '</th><th>' + chimpy_label_fields_icon + '</th><th></th></tr></thead><tbody>';

        // Table content with preselected options
        if (typeof current_selected_merge === 'object' && current_selected_merge !== null && Object.keys(current_selected_merge).length > 0) {
            for (var prop in current_selected_merge) {
                if (current_selected_merge.hasOwnProperty(prop) && typeof merge_fields[current_selected_merge[prop]['tag']] === 'object') {
                    var this_field = input_field.replace('%%%id%%%', prop);
                    this_field = this_field.replace('%%%id%%%', prop);
                    this_field = this_field.replace('%%%value%%%', current_selected_merge[prop]['name']);
                    fields_table += '<tr class="chimpy_field_row" id="chimpy_field_' + current_field_id + '_' + prop + '"><td>' + this_field + '</td><td><select class="chimpy_tag_select" name="chimpy_options[forms][' + current_field_id + '][fields][' + prop + '][tag]" id="chimpy_field_tag_' + current_field_id + '_' + prop + '">' + field_options + '</select></td><td><select name="chimpy_options[forms][' + current_field_id + '][fields][' + prop + '][icon]" id="chimpy_field_icon_' + current_field_id + '_' + prop + '" class="chimpy_fields_icon">' + font_awesome_list + '</select></td><td><button type="button" class="chimpy_remove_field"><i class="fa fa-times"></i></button></td><input type="hidden" name="chimpy_options[forms][' + current_field_id + '][fields][' + prop + '][type]" id="chimpy_forms_fields_type_' + current_field_id + '_' + prop + '" value="' + (typeof merge_fields[current_selected_merge[prop]['tag']]['type'] !== 'undefined' ? merge_fields[current_selected_merge[prop]['tag']]['type'] : '') + '" /><input type="hidden" name="chimpy_options[forms][' + current_field_id + '][fields][' + prop + '][req]" id="chimpy_forms_fields_req_' + current_field_id + '_' + prop + '" value="' + (typeof merge_fields[current_selected_merge[prop]['tag']]['req'] !== 'undefined' ? merge_fields[current_selected_merge[prop]['tag']]['req'] : '') + '" /><input type="hidden" name="chimpy_options[forms][' + current_field_id + '][fields][' + prop + '][us_phone]" id="chimpy_forms_fields_us_phone_' + current_field_id + '_' + prop + '" value="' + (typeof merge_fields[current_selected_merge[prop]['tag']]['us_phone'] !== 'undefined' ? merge_fields[current_selected_merge[prop]['tag']]['us_phone'] : '') + '" /><input type="hidden" name="chimpy_options[forms][' + current_field_id + '][fields][' + prop + '][choices]" id="chimpy_forms_fields_choices_' + current_field_id + '_' + prop + '" value="' + (typeof merge_fields[current_selected_merge[prop]['tag']]['choices'] !== 'undefined' ? merge_fields[current_selected_merge[prop]['tag']]['choices'] : '') + '" /></tr>';
                }
            }
        }

        // Table content with no preselected options
        else {
            var this_field = input_field.replace('%%%id%%%', '1');
            this_field = this_field.replace('%%%id%%%', '1');
            this_field = this_field.replace('%%%value%%%', '');
            fields_table += '<tr class="chimpy_field_row" id="chimpy_field_' + current_field_id + '_1"><td>' + this_field + '</td><td><select class="chimpy_tag_select" name="chimpy_options[forms][' + current_field_id + '][fields][1][tag]" id="chimpy_field_tag_' + current_field_id + '_1">' + field_options + '</select></td><td><select name="chimpy_options[forms][' + current_field_id + '][fields][1][icon]" id="chimpy_field_icon_' + current_field_id + '_1" class="chimpy_fields_icon">' + font_awesome_list + '</select></td><td><button type="button" class="chimpy_remove_field"><i class="fa fa-times"></i></button></td><input type="hidden" name="chimpy_options[forms][' + current_field_id + '][fields][1][type]" id="chimpy_forms_fields_type_' + current_field_id + '_1" value="" /><input type="hidden" name="chimpy_options[forms][' + current_field_id + '][fields][1][req]" id="chimpy_forms_fields_req_' + current_field_id + '_1" value="" /><input type="hidden" name="chimpy_options[forms][' + current_field_id + '][fields][1][us_phone]" id="chimpy_forms_fields_us_phone_' + current_field_id + '_1" value="" /><input type="hidden" name="chimpy_options[forms][' + current_field_id + '][fields][1][choices]" id="chimpy_forms_fields_choices_' + current_field_id + '_1" value="" /></tr>';
        }

        // End table
        fields_table += '</tbody><tfoot><tr><td><button type="button" name="chimpy_add_field" id="chimpy_add_field" class="button button-primary" value="' + chimpy_label_add_new + '"><i class="fa fa-plus">&nbsp;&nbsp;' + chimpy_label_add_new + '</i></button></td><td></td><td></td></tr></tfoot></table></div>';

        // Output table
        jQuery('#chimpy_fields_table_' + current_field_id).replaceWith(fields_table);

        // Select preselected options
        if (typeof current_selected_merge === 'object' && current_selected_merge !== null && Object.keys(current_selected_merge).length > 0) {
            for (var prop in current_selected_merge) {
                if (current_selected_merge.hasOwnProperty(prop)) {
                    jQuery('#chimpy_field_tag_' + current_field_id + '_' + prop).find('option[value="' + current_selected_merge[prop]['tag'] + '"]').prop('selected', true);
                    jQuery('#chimpy_field_icon_' + current_field_id + '_' + prop).find('option[value="' + current_selected_merge[prop]['icon'] + '"]').prop('selected', true);
                }
            }
        }

        // Make all select fields chosen
        jQuery('#chimpy_fields_table_' + current_field_id).find('.chimpy_tag_select').each(function() {
            jQuery(this).chosen({
                no_results_text: chimpy_label_no_results_match_tags,
                placeholder_text_single: chimpy_label_select_tag,
                width: '346px'
            }).change(function(evt, params) {

                // Regenerate chosen field
                regenerate_tag_chosen(current_field_id);

                // Pass values to hidden fields
                var full_field_key = jQuery(this).prop('id').replace('chimpy_field_tag', '');
                jQuery('#chimpy_forms_fields_type' + full_field_key).val(merge_fields[params.selected]['type']);
                jQuery('#chimpy_forms_fields_req' + full_field_key).val(merge_fields[params.selected]['req']);
                jQuery('#chimpy_forms_fields_us_phone' + full_field_key).val(merge_fields[params.selected]['us_phone']);
                jQuery('#chimpy_forms_fields_choices' + full_field_key).val(merge_fields[params.selected]['choices']);
            });
        });

        // Regenerate fields (so we make selected fields disabled on other fields)
        regenerate_tag_chosen(current_field_id);

        /**
         * Handle new fields
         */
        jQuery('#chimpy_forms_list_' + current_field_id).find('#chimpy_add_field').each(function() {
            jQuery(this).click(function() {

                var $table = jQuery(this).parent().parent().parent().parent();

                // Get set id and last field id
                var table_last_tr_id = jQuery($table).find('tbody>tr:last').attr('id');
                table_last_tr_id = table_last_tr_id.replace('chimpy_field_', '');
                table_last_tr_id = table_last_tr_id.split('_');

                var current_field_id = table_last_tr_id[0];
                var current_id = table_last_tr_id[1];

                // Remove chosen from last element
                jQuery($table).find('#chimpy_field_tag_' + current_field_id + '_' + current_id).chosen('destroy');

                // Remove chosen from checkout field name
                jQuery($table).find('#chimpy_field_name_' + current_field_id + '_' + current_id).chosen('destroy');

                // Clone row and insert after the last one
                var new_fields_row = jQuery($table).find('tbody>tr:last').clone(true);
                jQuery($table).find('tbody>tr:last').after(new_fields_row);

                jQuery($table).find('tbody>tr:last').each(function() {

                    // Change ids
                    var next_id = parseInt(current_id, 10) + 1;
                    jQuery(this).attr('id', 'chimpy_field_' + current_field_id + '_' + next_id);
                    jQuery(this).find(':input').each(function() {
                        if (jQuery(this).is('input') && jQuery(this).hasClass('chimpy_name_input')) {
                            jQuery(this).attr('id', 'chimpy_forms_fields_name_' + current_field_id + '_' + next_id);
                            jQuery(this).attr('name', 'chimpy_options[forms][' + current_field_id + '][fields][' + next_id + '][name]');
                            jQuery(this).val('');
                        }
                        else if (jQuery(this).is('select') && jQuery(this).hasClass('chimpy_tag_select')) {
                            jQuery(this).attr('id', 'chimpy_field_tag_' + current_field_id + '_' + next_id);
                            jQuery(this).attr('name', 'chimpy_options[forms][' + current_field_id + '][fields][' + next_id + '][tag]');
                            jQuery(this).val('');
                        }
                        else if (jQuery(this).is('select') && jQuery(this).hasClass('chimpy_fields_icon')) {
                            jQuery(this).attr('id', 'chimpy_field_icon_' + current_field_id + '_' + next_id);
                            jQuery(this).attr('name', 'chimpy_options[forms][' + current_field_id + '][fields][' + next_id + '][icon]');
                            jQuery(this).val('');
                        }
                        else if (jQuery(this).prop('id') === 'chimpy_forms_fields_type_' + current_field_id + '_' + current_id) {
                            jQuery(this).attr('id', 'chimpy_forms_fields_type_' + current_field_id + '_' + next_id);
                            jQuery(this).attr('name', 'chimpy_options[forms][' + current_field_id + '][fields][' + next_id + '][type]');
                            jQuery(this).val('');
                        }
                        else if (jQuery(this).prop('id') === 'chimpy_forms_fields_req_' + current_field_id + '_' + current_id) {
                            jQuery(this).attr('id', 'chimpy_forms_fields_req_' + current_field_id + '_' + next_id);
                            jQuery(this).attr('name', 'chimpy_options[forms][' + current_field_id + '][fields][' + next_id + '][req]');
                            jQuery(this).val('');
                        }
                        else if (jQuery(this).prop('id') === 'chimpy_forms_fields_us_phone_' + current_field_id + '_' + current_id) {
                            jQuery(this).attr('id', 'chimpy_forms_fields_us_phone_' + current_field_id + '_' + next_id);
                            jQuery(this).attr('name', 'chimpy_options[forms][' + current_field_id + '][fields][' + next_id + '][us_phone]');
                            jQuery(this).val('');
                        }
                        else if (jQuery(this).prop('id') === 'chimpy_forms_fields_choices_' + current_field_id + '_' + current_id) {
                            jQuery(this).attr('id', 'chimpy_forms_fields_choices_' + current_field_id + '_' + next_id);
                            jQuery(this).attr('name', 'chimpy_options[forms][' + current_field_id + '][fields][' + next_id + '][choices]');
                            jQuery(this).val('');
                        }
                    });

                    // Make both tag fields chosen
                    jQuery('#chimpy_field_tag_' + current_field_id + '_' + current_id).chosen({
                        no_results_text: chimpy_label_no_results_match_tags,
                        placeholder_text_single: chimpy_label_select_tag,
                        width: '346px'
                    });
                    jQuery('#chimpy_field_tag_' + current_field_id + '_' + next_id).chosen({
                        no_results_text: chimpy_label_no_results_match_tags,
                        placeholder_text_single: chimpy_label_select_tag,
                        width: '346px'
                    });

                });

                regenerate_tag_chosen(current_field_id);

                return false;

            });
        });

        /**
         * Handle field removal
         */
        jQuery('.chimpy_remove_field').each(function() {
            jQuery(this).click(function() {
                // Do not remove the last set - reset field values instead
                if (jQuery(this).parent().parent().parent().children().length === 1) {
                    jQuery(this).parent().parent().find(':input').each(function() {
                        jQuery(this).val('');
                    });
                }
                else {
                    jQuery(this).parent().parent().remove();
                }


                jQuery('.chimpy_name_select').each(function() {
                    jQuery(this).trigger('chosen:updated');
                });

                regenerate_tag_chosen(current_field_id);
            });
        });

    }

    /**
     * Checkout - regenerate all chosen fields
     */
    function regenerate_tag_chosen(current_field_id) {
        var all_selected = {};

        // Get all selected fields
        jQuery('#chimpy_forms_list_' + current_field_id).find('.chimpy_tag_select').each(function() {
            if (jQuery(this).find(':selected').length > 0 && jQuery(this).find(':selected').val() !== '') {
                all_selected[jQuery(this).prop('id')] = jQuery(this).find(':selected').val();
            }
        });

        // Regenerate chosen fields
        jQuery('#chimpy_forms_list_' + current_field_id).find('.chimpy_tag_select').each(function() {

            if (Object.keys(all_selected).length !== 0) {

                for (var prop in all_selected) {

                    if (prop !== jQuery(this).prop('id')) {

                        // Disable
                        jQuery(this).find('option[value="' + all_selected[prop] + '"]').prop('disabled', true);
                    }

                    // Enable previously disabled values if they are available now
                    jQuery(this).find(':disabled').each(function() {

                        // Check if such disabled property exists within selected properties
                        var option_value = jQuery(this).val();
                        var exists = false;

                        for (var proper in all_selected) {
                            if (all_selected[proper] === option_value) {
                                exists = true;
                                break;
                            }
                        }

                        // Remove if it does not exist
                        if (!exists) {
                            jQuery(this).removeAttr('disabled');
                        }

                    });

                }
            }
            else {
                // Enable all properties on all fields if there's only one left
                jQuery(this).find(':disabled').each(function() {
                    jQuery(this).removeAttr('disabled');
                });
            }

            jQuery(this).trigger('chosen:updated');
        });
    }

    /**
     * Checkout - handle list change
     */
    function chimpy_update_groups_and_tags(current_field_id, list_id) {

        // Replace groups field with loading animation
        var preloader = '<p id="chimpy_forms_groups_' + current_field_id + '" class="chimpy_loading"><span class="chimpy_loading_icon"></span>' + chimpy_label_connecting_to_mailchimp + '</p>';
        jQuery('#chimpy_forms_groups_' + current_field_id).parent().html(preloader);

        // Replace fields section with loading animation
        var preloader = '<div class="chimpy-status" id="chimpy_fields_table_' + current_field_id + '"><p class="chimpy_loading"><span class="chimpy_loading_icon"></span>' + chimpy_label_connecting_to_mailchimp + '</p></div>';
        jQuery('#chimpy_fields_table_' + current_field_id).replaceWith(preloader);

        // Disable add set button until groups and fields are updated
        jQuery('#chimpy_add_set').prop('disabled', true);
        jQuery('#chimpy_add_set').prop('title', chimpy_label_still_connecting_to_mailchimp);

        // Disable submit button until groups and fields are updated
        jQuery('#submit').prop('disabled', true);
        jQuery('#submit').prop('title', chimpy_label_still_connecting_to_mailchimp);

        // Get data
        jQuery.post(
                ajaxurl,
                {
                    'action': 'chimpy_update_groups_and_tags',
                    'data': {'list': list_id}
                },
        function(response) {

            try {
                var result = jQuery.parseJSON(response);
            }
            catch (err) {
                jQuery('.chimpy_loading').html(chimpy_label_bad_ajax_response);
            }

            if (result && typeof result['message'] === 'object') {

                // Render groups field
                if (typeof result['message']['groups'] === 'object') {
                    var fields = '';

                    for (var prop in result['message']['groups']) {
                        fields += '<option value="' + prop + '">' + result['message']['groups'][prop] + '</option>';
                    }

                    // Update DOM
                    jQuery('#chimpy_forms_groups_' + current_field_id).replaceWith('<select multiple id="chimpy_forms_groups_' + current_field_id + '" name="chimpy_options[forms][' + current_field_id + '][groups][]" class="chimpy-field">' + fields + '</select>');

                    // Make it chosen!
                    jQuery('#chimpy_forms_groups_' + current_field_id).chosen({
                        no_results_text: chimpy_label_no_results_match_groups,
                        placeholder_text_multiple: chimpy_label_select_some_groups,
                        width: '400px',
                    });
                }

                // Render merge fields table
                render_forms_merge_fields_table(current_field_id, list_id, null, result['message']['merge'])

                /**
                 * Enable add set button
                 */
                jQuery('#chimpy_add_set').prop('disabled', false);
                jQuery('#chimpy_add_set').prop('title', '');

                /**
                 * Enable submit button
                 */
                jQuery('#submit').prop('disabled', false);
                jQuery('#submit').prop('title', '');

            }
        }
        );
    }

    /**
     * Checkout - add new set
     */
    jQuery('#chimpy_add_set').click(function() {

        // Get last field id
        var current_id = (jQuery('#chimpy_forms_list>div:last-child').attr('id').replace('chimpy_forms_list_', ''));

        // Remove chosen from all fields that have one
        var chosen_removed_from = [];

        jQuery('#chimpy_forms_list>div:last-child').find('select').each(function() {
            if (!jQuery(this).hasClass('form_condition_key') && !jQuery(this).hasClass('chimpy_forms_color_scheme') && !jQuery(this).hasClass('chimpy_fields_icon') && !jQuery(this).hasClass('chimpy_form_group_method')) {
                chosen_removed_from.push(jQuery(this).prop('id'));
                jQuery(this).chosen('destroy');
            }
        });

        // Clone element and insert after the last one
        jQuery('#chimpy_forms_list>div:last-child').clone(true).insertAfter('#chimpy_forms_list>div:last-child');

        // Regenerate chosen on previous fields
        for (var i = 0, len = chosen_removed_from.length; i < len; i++) {
            if (chosen_removed_from[i].search('chimpy_forms_list_field_') !== -1) {
                jQuery('#' + chosen_removed_from[i]).chosen({
                    no_results_text: chimpy_label_no_results_match_list,
                    placeholder_text_single: chimpy_label_select_mailing_list,
                    width: '400px'
                });
            }
            else if (chosen_removed_from[i].search('chimpy_forms_groups_') !== -1) {
                jQuery('#' + chosen_removed_from[i]).chosen({
                    no_results_text: chimpy_label_no_results_match_groups,
                    placeholder_text_multiple: chimpy_label_select_some_groups,
                    width: '400px'
                });
            }
            else if (chosen_removed_from[i].search('chimpy_field_tag_') !== -1) {
                jQuery('#' + chosen_removed_from[i]).chosen({
                    no_results_text: chimpy_label_no_results_match_tags,
                    placeholder_text_single: chimpy_label_select_tag,
                    width: '346px'
                });
            }
            else if (chosen_removed_from[i].search('chimpy_forms_condition_pages_') !== -1) {
                jQuery('#' + chosen_removed_from[i]).chosen({
                    no_results_text: chimpy_label_no_results_match_pages,
                    placeholder_text_multiple: chimpy_label_select_some_pages,
                    width: '400px'
                });
            }
            else if (chosen_removed_from[i].search('chimpy_forms_condition_posts_') !== -1) {
                jQuery('#' + chosen_removed_from[i]).chosen({
                    no_results_text: chimpy_label_no_results_match_posts,
                    placeholder_text_multiple: chimpy_label_select_some_posts,
                    width: '400px'
                });
            }
            else if (chosen_removed_from[i].search('chimpy_forms_condition_categories_') !== -1) {
                jQuery('#' + chosen_removed_from[i]).chosen({
                    no_results_text: chimpy_label_no_results_match_post_categories,
                    placeholder_text_multiple: chimpy_label_select_some_post_categories,
                    width: '400px'
                });
            }
        }

        /**
         * Fix new elements
         */
        jQuery('#chimpy_forms_list>div:last-child').each(function() {

            // Get next id (well.. it's current already)
            var next_id = parseInt(current_id, 10) + 1;

            // Change main div id
            jQuery(this).attr('id', 'chimpy_forms_list_' + next_id);

            // Remove name from accordion handle
            jQuery(this).find('.chimpy_forms_title_name').html('');

            // Change id and name of form title field and clear its value
            jQuery(this).find('#chimpy_forms_title_field_' + current_id).attr('id', 'chimpy_forms_title_field_' + next_id);
            jQuery('#chimpy_forms_title_field_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][title]');
            jQuery('#chimpy_forms_title_field_' + next_id).val('');

            // Change id and name of form above text field and clear its value
            jQuery(this).find('#chimpy_forms_above_field_' + current_id).attr('id', 'chimpy_forms_above_field_' + next_id);
            jQuery('#chimpy_forms_above_field_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][above]');
            jQuery('#chimpy_forms_above_field_' + next_id).val('');

            // Change id and name of form below text field and clear its value
            jQuery(this).find('#chimpy_forms_below_field_' + current_id).attr('id', 'chimpy_forms_below_field_' + next_id);
            jQuery('#chimpy_forms_below_field_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][below]');
            jQuery('#chimpy_forms_below_field_' + next_id).val('');

            // Change id and name of button label field and clear its value
            jQuery(this).find('#chimpy_forms_button_field_' + current_id).attr('id', 'chimpy_forms_button_field_' + next_id);
            jQuery('#chimpy_forms_button_field_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][button]');
            jQuery('#chimpy_forms_button_field_' + next_id).val('');

            // Change id and name of redirect URL field and clear its value
            jQuery(this).find('#chimpy_forms_redirect_url_' + current_id).attr('id', 'chimpy_forms_redirect_url_' + next_id);
            jQuery('#chimpy_forms_redirect_url_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][redirect_url]');
            jQuery('#chimpy_forms_redirect_url_' + next_id).val('');

            // Change ids and names of mailing list and groups fields
            jQuery(this).find('#chimpy_forms_list_field_' + current_id).attr('id', 'chimpy_forms_list_field_' + next_id);
            jQuery('#chimpy_forms_list_field_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][list_field]');
            jQuery(this).find('#chimpy_forms_groups_' + current_id).attr('id', 'chimpy_forms_groups_' + next_id);
            jQuery('#chimpy_forms_groups_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][groups][]');

            // Remove selected options from mailing list
            jQuery('#chimpy_forms_list_field_' + next_id).find('option:selected').prop('selected', false);

            // Remove all options from groups
            jQuery('#chimpy_forms_groups_' + next_id).html('<option value=""></option>');

            // Change id and name of the condition key field and reset selection
            jQuery(this).find('#chimpy_forms_group_method_' + current_id).attr('id', 'chimpy_forms_group_method_' + next_id);
            jQuery('#chimpy_forms_group_method_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][group_method]');
            jQuery('#chimpy_forms_group_method_' + next_id).find('option:selected').prop('selected', false);

            // Change id of fields table
            jQuery(this).find('#chimpy_fields_table_' + current_id).attr('id', 'chimpy_fields_table_' + next_id);

            // Remove all field table rows except of first one
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('tr:gt(0)').remove();

            // Change id of the first fields table row
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('tr').attr('id', 'chimpy_field_' + next_id + '_1');

            // Change id and name of first field name field and clear value
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('.chimpy_name_input').attr('id', 'chimpy_forms_fields_name_' + next_id + '_1');
            jQuery('#chimpy_forms_fields_name_' + next_id + '_1').attr('name', 'chimpy_options[forms][' + next_id + '][fields][1][name]');
            jQuery('#chimpy_forms_fields_name_' + next_id + '_1').val('');

            // Change id and name of first field tag field and remove all options
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('.chimpy_tag_select').attr('id', 'chimpy_field_tag_' + next_id + '_1');
            jQuery('#chimpy_field_tag_' + next_id + '_1').attr('name', 'chimpy_options[forms][' + next_id + '][fields][1][tag]');
            jQuery('#chimpy_field_tag_' + next_id + '_1').html('<option value=""></option>');

            // Change id and name of first field icon field and remove all options
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('.chimpy_fields_icon').attr('id', 'chimpy_field_icon_' + next_id + '_1');
            jQuery('#chimpy_field_icon_' + next_id + '_1').attr('name', 'chimpy_options[forms][' + next_id + '][fields][1][icon]');

            // Change id and name of first type field and clear value
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('#chimpy_forms_fields_type_' + current_id + '_1').attr('id', 'chimpy_forms_fields_type_' + next_id + '_1');
            jQuery('#chimpy_forms_fields_type_' + next_id + '_1').attr('name', 'chimpy_options[forms][' + next_id + '][fields][1][type]');
            jQuery('#chimpy_forms_fields_type_' + next_id + '_1').val('');

            // Change id and name of first type field and clear value
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('#chimpy_forms_fields_req_' + current_id + '_1').attr('id', 'chimpy_forms_fields_req_' + next_id + '_1');
            jQuery('#chimpy_forms_fields_req_' + next_id + '_1').attr('name', 'chimpy_options[forms][' + next_id + '][fields][1][req]');
            jQuery('#chimpy_forms_fields_req_' + next_id + '_1').val('');

            // Change id and name of first type field and clear value
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('#chimpy_forms_fields_us_phone_' + current_id + '_1').attr('id', 'chimpy_forms_fields_us_phone_' + next_id + '_1');
            jQuery('#chimpy_forms_fields_us_phone_' + next_id + '_1').attr('name', 'chimpy_options[forms][' + next_id + '][fields][1][us_phone]');
            jQuery('#chimpy_forms_fields_us_phone_' + next_id + '_1').val('');

            // Change id and name of first type field and clear value
            jQuery('#chimpy_fields_table_' + next_id + ' > tbody').find('#chimpy_forms_fields_choices_' + current_id + '_1').attr('id', 'chimpy_forms_fields_choices_' + next_id + '_1');
            jQuery('#chimpy_forms_fields_choices_' + next_id + '_1').attr('name', 'chimpy_options[forms][' + next_id + '][fields][1][choices]');
            jQuery('#chimpy_forms_fields_choices_' + next_id + '_1').val('');

            // Change id and name of the condition key field and reset selection
            jQuery(this).find('#chimpy_forms_condition_' + current_id).attr('id', 'chimpy_forms_condition_' + next_id);
            jQuery('#chimpy_forms_condition_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][condition]');
            jQuery('#chimpy_forms_condition_' + next_id).find('option:selected').prop('selected', false);

            // Change id and name of the condition pages value field and reset selections
            jQuery(this).find('#chimpy_forms_condition_pages_' + current_id).attr('id', 'chimpy_forms_condition_pages_' + next_id);
            jQuery('#chimpy_forms_condition_pages_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][condition_pages][]');
            jQuery('#chimpy_forms_condition_pages_' + next_id).find('option:selected').prop('selected', false);

            // Change id and name of the condition posts value field and reset selections
            jQuery(this).find('#chimpy_forms_condition_posts_' + current_id).attr('id', 'chimpy_forms_condition_posts_' + next_id);
            jQuery('#chimpy_forms_condition_posts_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][condition_posts][]');
            jQuery('#chimpy_forms_condition_posts_' + next_id).find('option:selected').prop('selected', false);

            // Change id and name of the condition posts value field and reset selections
            jQuery(this).find('#chimpy_forms_condition_categories_' + current_id).attr('id', 'chimpy_forms_condition_categories_' + next_id);
            jQuery('#chimpy_forms_condition_categories_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][condition_categories][]');
            jQuery('#chimpy_forms_condition_categories_' + next_id).find('option:selected').prop('selected', false);

            // Change id and name of form condition url field and clear its value
            jQuery(this).find('#chimpy_forms_condition_url_' + current_id).attr('id', 'chimpy_forms_condition_url_' + next_id);
            jQuery('#chimpy_forms_condition_url_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][condition_url]');
            jQuery('#chimpy_forms_condition_url_' + next_id).val('');

            // Change id and name of the color scheme field and reset selection
            jQuery(this).find('#chimpy_forms_color_scheme_' + current_id).attr('id', 'chimpy_forms_color_scheme_' + next_id);
            jQuery('#chimpy_forms_color_scheme_' + next_id).attr('name', 'chimpy_options[forms][' + next_id + '][color_scheme]');
            jQuery('#chimpy_forms_color_scheme_' + next_id).find('option:selected').prop('selected', false);
        });

        /**
         * Make new select fields chosen
         */
        jQuery('#chimpy_forms_list>div:last-child').find('select').each(function() {
            var current_select_id = jQuery(this).prop('id');

            if (current_select_id.search('chimpy_forms_list_field_') !== -1) {
                jQuery('#' + current_select_id).chosen({
                    no_results_text: chimpy_label_no_results_match_list,
                    placeholder_text_single: chimpy_label_select_mailing_list,
                    width: '400px'
                });
            }
            else if (current_select_id.search('chimpy_forms_groups_') !== -1) {
                jQuery('#' + current_select_id).chosen({
                    no_results_text: chimpy_label_no_results_match_groups,
                    placeholder_text_multiple: chimpy_label_select_some_groups,
                    width: '400px'
                });
            }
            else if (current_select_id.search('chimpy_field_tag_') !== -1) {
                jQuery('#' + current_select_id).chosen({
                    no_results_text: chimpy_label_no_results_match_tags,
                    placeholder_text_single: chimpy_label_select_tag,
                    width: '346px'
                });
            }
            else if (current_select_id.search('chimpy_forms_condition_pages_') !== -1) {
                jQuery('#' + current_select_id).chosen({
                    no_results_text: chimpy_label_no_results_match_pages,
                    placeholder_text_multiple: chimpy_label_select_some_pages,
                    width: '400px'
                });
            }
            else if (current_select_id.search('chimpy_forms_condition_posts_') !== -1) {
                jQuery('#' + current_select_id).chosen({
                    no_results_text: chimpy_label_no_results_match_posts,
                    placeholder_text_multiple: chimpy_label_select_some_posts,
                    width: '400px'
                });
            }
            else if (current_select_id.search('chimpy_forms_condition_categories_') !== -1) {
                jQuery('#' + current_select_id).chosen({
                    no_results_text: chimpy_label_no_results_match_post_categories,
                    placeholder_text_multiple: chimpy_label_select_some_post_categories,
                    width: '400px'
                });
            }

        });

        chimpy_hide_unused_condition_fields();
        regenerate_tag_chosen(current_id);

        /**
         * Update accordion
         */
        jQuery('#chimpy_forms_list').accordion('refresh');
        var $accordion = jQuery("#chimpy_forms_list").accordion();
        var last_accordion_element = $accordion.find('h4').length;
        $accordion.accordion('option', 'active', (last_accordion_element - 1));
        regenerate_accordion_handle_titles();

        return false;
    });

    /**
     * Checkout - remove set
     */
    jQuery('.chimpy_forms_remove').each(function() {
        jQuery(this).click(function() {

            // Remove set if it's not the last one
            if (jQuery(this).parent().parent().parent().children().length !== 1) {
                jQuery(this).parent().parent().remove();
            }

            /**
             * Update accordion
             */
            jQuery('#chimpy_forms_list').accordion('refresh');
            regenerate_accordion_handle_titles();

        });
    });

    /**
     * Regenerate accordion handle titles
     */
    function regenerate_accordion_handle_titles()
    {
        var fake_id = 1;

        jQuery('#chimpy_forms_list').children().each(function() {
            jQuery(this).find('.chimpy_forms_title').html(chimpy_label_signup_form_no + '' + fake_id);
            fake_id++;
        });
    }

    /**
     * Checkboxes and Sync list
     */
    jQuery('#chimpy_checkbox_list').each(function() {
        chimpy_load_single_list_field('checkbox');
    });
    jQuery('#chimpy_sync_list').each(function() {
        chimpy_load_single_list_field('sync');
    });

    function chimpy_load_single_list_field(context)
    {
        jQuery('#chimpy_' + context + '_list').replaceWith('<p id="chimpy_' + context + '_list" class="chimpy_loading"><span class="chimpy_loading_icon"></span>' + chimpy_label_connecting_to_mailchimp + '</p>');

        jQuery.post(
            ajaxurl,
            {
                'action': 'chimpy_get_lists'
            },
            function(response) {

                try {
                    var result = jQuery.parseJSON(response);
                }
                catch (err) {
                    jQuery('.chimpy_loading').html(chimpy_label_bad_ajax_response);
                }

                if (result && typeof result['message'] === 'object' && typeof result['message']['lists'] === 'object') {
                    var fields = '';

                    for (var prop in result['message']['lists']) {
                        fields += '<option value="' + prop + '" ' + (chimpy_selected_list !== null && chimpy_selected_list === prop ? 'selected="selected"' : '') + '>' + result['message']['lists'][prop] + '</option>';
                    }

                    jQuery('#chimpy_' + context + '_list').replaceWith('<select id="chimpy_' + context + '_list" name="chimpy_options[chimpy_' + context + '_list]" class="chimpy-field">' + fields + '</select>');
                }
            }
        );
    }

    /**
     * Set up forms page hints
     */
    function chimpy_forms_page_hints()
    {
        if (typeof chimpy_forms_hints !== 'undefined') {
            jQuery.each(chimpy_forms_hints, function(index, value) {
                jQuery('form').find('.' + index).each(function() {
                    jQuery(this).parent().parent().find('th').each(function() {
                        if (jQuery(this).find('.chimpy_tip').length === 0) {
                            jQuery(this).append('<div class="chimpy_tip" title="' + value + '"><i class="fa fa-question"></div>');
                        }
                    });
                });
            });
        }
        jQuery.widget('ui.tooltip', jQuery.ui.tooltip, {
            options: {
                content: function() {
                    return jQuery(this).prop('title');
                }
            }
        });
        jQuery('.chimpy_tip').tooltip();
    }

});
