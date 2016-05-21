<?php
namespace WebSharks\CometCache\Traits\Plugin;

use WebSharks\CometCache\Classes;

trait WcpSettingUtils
{
    /**
     * Automatically clears all cache files for current blog under various conditions;
     *    used to check for conditions that don't have a hook that we can attach to.
     *
     * @since 150422 Rewrite.
     *
     * @attaches-to `admin_init` hook.
     */
    public function autoClearCacheOnSettingChanges()
    {
        $counter          = 0; // Initialize.
        $pagenow          = !empty($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';
        $settings_updated = !empty($_REQUEST['settings-updated']);

        if (!is_null($done = &$this->cacheKey('autoClearCacheOnSettingChanges', [$pagenow, $settings_updated]))) {
            return $counter; // Already did this.
        }
        $done = true; // Flag as having been done.

        if ($pagenow === 'options-general.php' && $settings_updated) {
            $counter += $this->autoClearCache();
        } elseif ($pagenow === 'options-reading.php' && $settings_updated) {
            $counter += $this->autoClearCache();
        } elseif ($pagenow === 'options-discussion.php' && $settings_updated) {
            $counter += $this->autoClearCache();
        } elseif ($pagenow === 'options-permalink.php' && $settings_updated) {
            $counter += $this->autoClearCache();
        }
    }
}
