<?php

/**
 * 
 * @param piwikanalyticsjs $module
 * @return boolean
 */
function upgrade_module_0_6_5($module) {
    Configuration::updateValue('PIWIK_COOKIE_DOMAIN', '*.' . str_replace('www.', '', Tools::getShopDomain()));
    Configuration::updateValue('PIWIK_SET_DOMAINS', Tools::getShopDomain());
    Configuration::updateValue('PIWIK_DNT', 1);
    Configuration::updateValue('PIWIK_PROXY_SCRIPT', str_replace("http://", '', piwikanalyticsjs::getModuleLink($module->name, 'piwik')));

    return true;
}
