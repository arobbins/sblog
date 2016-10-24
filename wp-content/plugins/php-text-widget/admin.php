<?php

add_action('admin_head', 'ptw_admin_head');
function ptw_admin_head()
{
    if (isset($_GET['page']) && strpos($_GET['page'], 'php-text-widget/') === 0) {
        echo '<link type="text/css" rel="stylesheet" href="' . plugins_url('admin.css', __FILE__) . '">';
    }
}

add_action('admin_menu', 'ptw_admin_menu');

function ptw_admin_menu() {
    add_options_page('PHP Text Widget', 'PHP Text Widget', 'manage_options', 'php-text-widget/options.php');
}