<?php
/**
 * PHP vX.x Handlers.
 *
 * @since 160503 Reorganizing structure.
 *
 * @copyright WebSharks, Inc. <http://websharks-inc.com>
 * @license GNU General Public License, version 3
 */

/**
 * Any compatibility issue?
 *
 * @since 141004 First documented version.
 *
 * @return bool True if no issue.
 */
function wp_php_rv() // See below.
{
    return ___wp_php_rv_issue() ? false : true;
}

/**
 * Any compatibility issue?
 *
 * @since 141004 First documented version.
 *
 * @return array Issue; else empty array.
 */
function ___wp_php_rv_issue()
{
    global $wp_php_rv;
    global $___wp_php_rv;
    global $wp_version;

    if (isset($wp_php_rv)) {
        ___wp_php_rv_initialize();
    }
    $required_os             = $___wp_php_rv['os'];
    $php_min_version         = $___wp_php_rv['min'];
    $php_max_version         = $___wp_php_rv['max'];
    $php_minimum_bits        = $___wp_php_rv['bits'];
    $php_required_functions  = $___wp_php_rv['functions'];
    $php_required_extensions = $___wp_php_rv['extensions'];
    $wp_min_version          = $___wp_php_rv['wp']['min'];
    $wp_max_version          = $___wp_php_rv['wp']['max'];

    if ($required_os && ___wp_php_rv_os() !== $required_os) {
        return array('reason' => 'os-incompatible');
    } elseif ($php_min_version && version_compare(PHP_VERSION, $php_min_version, '<')) {
        return array('reason' => 'php-needs-upgrade');
    } elseif ($php_max_version && version_compare(PHP_VERSION, $php_max_version, '>')) {
        return array('reason' => 'php-needs-downgrade');
    } elseif ($php_minimum_bits && $php_minimum_bits / 8 > PHP_INT_SIZE) {
        return array('reason' => 'php-missing-bits');
    }
    if ($php_required_functions) { // Requires PHP functions?
        $php_missing_functions = array(); // Initialize.

        foreach ($php_required_functions as $_required_function) {
            if (!___wp_php_rv_can_call_func($_required_function)) {
                $php_missing_functions[] = $_required_function;
            } // See also: <http://jas.xyz/29Bdz3N>
        } // unset($_required_function); // Housekeeping.

        if ($php_missing_functions) { // Missing PHP functions?
            return array(
                'php_missing_functions' => $php_missing_functions,
                'reason'                => 'php-missing-functions',
            );
        }
    }
    if ($php_required_extensions) { // Requires PHP extensions?
        $php_missing_extensions = array(); // Initialize.

        foreach ($php_required_extensions as $_required_extension) {
            if (!extension_loaded($_required_extension)) {
                $php_missing_extensions[] = $_required_extension;
            } // See also: <http://jas.xyz/1TtzgX5>
        } // unset($_required_extension); // Housekeeping.

        if ($php_missing_extensions) { // Missing PHP extensions?
            return array(
                'php_missing_extensions' => $php_missing_extensions,
                'reason'                 => 'php-missing-extensions',
            );
        }
    }
    if ($wp_min_version && version_compare($wp_version, $wp_min_version, '<')) {
        return array('reason' => 'wp-needs-upgrade');
    } elseif ($wp_max_version && version_compare($wp_version, $wp_max_version, '>')) {
        return array('reason' => 'wp-needs-downgrade');
    }
    return array(); // No problem.
}

/**
 * Initializes each instance; unsets `$wp_php_rv`.
 *
 * @since 141004 First documented version.
 *
 * @note `$wp_php_rv` is for the API, we use a different variable internally.
 *    The internal global is defined here: `$___wp_php_rv`.
 */
function ___wp_php_rv_initialize()
{
    global $wp_php_rv;
    global $___wp_php_rv;

    $___wp_php_rv = array(
        'os'         => '',
        'min'        => '',
        'max'        => '',
        'bits'       => 0,
        'functions'  => array(),
        'extensions' => array(),
        'wp'         => array(
            'min' => '',
            'max' => '',
        ),
    );
    if (!empty($wp_php_rv)) {
        if (is_string($wp_php_rv)) {
            $___wp_php_rv['min'] = $wp_php_rv;
        } elseif (is_array($wp_php_rv)) {
            if (!empty($wp_php_rv['os'])) {
                $___wp_php_rv['os'] = (string) $wp_php_rv['os'];
            }
            if (!empty($wp_php_rv['min'])) {
                $___wp_php_rv['min'] = (string) $wp_php_rv['min'];
            } elseif (!empty($wp_php_rv['rv'])) {
                $___wp_php_rv['min'] = (string) $wp_php_rv['rv'];
            }
            if (!empty($wp_php_rv['max'])) {
                $___wp_php_rv['max'] = (string) $wp_php_rv['max'];
            }
            if (!empty($wp_php_rv['bits'])) {
                $___wp_php_rv['bits'] = (int) $wp_php_rv['bits'];
            }
            if (!empty($wp_php_rv['functions'])) {
                $___wp_php_rv['functions'] = (array) $wp_php_rv['functions'];
            }
            if (!empty($wp_php_rv['extensions'])) {
                $___wp_php_rv['extensions'] = (array) $wp_php_rv['extensions'];
            } elseif (!empty($wp_php_rv['re'])) {
                $___wp_php_rv['extensions'] = (array) $wp_php_rv['re'];
            }
            if (!empty($wp_php_rv['wp']['min'])) {
                $___wp_php_rv['wp']['min'] = (string) $wp_php_rv['wp']['min'];
            }
            if (!empty($wp_php_rv['wp']['max'])) {
                $___wp_php_rv['wp']['max'] = (string) $wp_php_rv['wp']['max'];
            }
        }
    } // End of API conversion to internal global settings.
    $wp_php_rv = null; // Unset to avoid theme/plugin conflicts.
}
