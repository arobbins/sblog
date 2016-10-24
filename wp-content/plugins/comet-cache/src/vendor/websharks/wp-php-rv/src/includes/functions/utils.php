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
 * OS detection.
 *
 * @since 160523 OS detection.
 *
 * @return string OS identifier.
 */
function ___wp_php_rv_os()
{
    static $os; // Static cache.

    if (isset($os)) {
        return $os; // Cached already.
    } elseif (stripos(PHP_OS, 'win') === 0) {
        return $os = 'win';
    } else {
        return $os = 'nix';
    }
}

/**
 * OS long name.
 *
 * @since 160523 OS detection.
 *
 * @return string OS long name.
 */
function ___wp_php_rv_os_name($os)
{
    if ($os === 'win') {
        return 'Windows®';
    } else {
        return 'Unix-like';
    }
}

/**
 * Can call a PHP function?
 *
 * @since 160712 PHP functions.
 *
 * @return bool True if callable.
 */
function ___wp_php_rv_can_call_func($function)
{
    static $can        = array();
    static $constructs = array(
        'die',
        'echo',
        'empty',
        'exit',
        'eval',
        'include',
        'include_once',
        'isset',
        'list',
        'require',
        'require_once',
        'return',
        'print',
        'unset',
        '__halt_compiler',
    );
    static $functions_disabled; // Set below.

    if (isset($can[$function = strtolower($function)])) {
        return $can[$function]; // Already cached this.
    }
    if (!isset($functions_disabled)) {
        $functions_disabled = array(); // Initialize.

        if (($_ini_disable_functions = (string) @ini_get('disable_functions'))) {
            $_ini_disable_functions = strtolower($_ini_disable_functions);
            $functions_disabled     = array_merge($functions_disabled, preg_split('/[\s;,]+/u', $_ini_disable_functions, -1, PREG_SPLIT_NO_EMPTY));
        }
        if (($_ini_suhosin_blacklist_functions = (string) @ini_get('suhosin.executor.func.blacklist'))) {
            $_ini_suhosin_blacklist_functions = strtolower($_ini_suhosin_blacklist_functions);
            $functions_disabled               = array_merge($functions_disabled, preg_split('/[\s;,]+/u', $_ini_suhosin_blacklist_functions, -1, PREG_SPLIT_NO_EMPTY));
        }
        if (filter_var(@ini_get('suhosin.executor.disable_eval'), FILTER_VALIDATE_BOOLEAN)) {
            $functions_disabled[] = 'eval'; // The `eval()` construct is disabled also.
        }
    } // We now have a full list of all disabled functions.

    if ($functions_disabled && in_array($function, $functions_disabled, true)) {
        return $can[$function] = false; // Not possible; e.g., `eval()`.
    } elseif ((!function_exists($function) || !is_callable($function)) && !in_array($function, $constructs, true)) {
        return $can[$function] = false; // Not possible.
    }
    return $can[$function] = true;
}
