<?php

/**
 * 
 * @param piwikanalyticsjs $module
 * @return boolean
 */
function upgrade_module_0_6_6($module) {
    Configuration::updateValue('PIWIK_CRHTTPS', 0);
    Configuration::updateValue('PIWIK_PRODID_V1', '{ID}-{ATTRID}#{REFERENCE}');
    Configuration::updateValue('PIWIK_PRODID_V2', '{ID}#{REFERENCE}');
    Configuration::updateValue('PIWIK_PRODID_V3', '{ID}#{ATTRID}');

    return true;
}
