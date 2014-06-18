<?php
/**
 * 
 * @param piwikanalyticsjs $module
 * @return boolean
 */
function upgrade_module_0_2($module) {
    if ($tab = Tab::getInstanceFromClassName('PiwikAnalyticsResource')) {
        $tab->delete();
    }
    return true;
}
