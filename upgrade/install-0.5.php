<?php

/**
 * 
 * @param piwikanalyticsjs $module
 * @return boolean
 */
function upgrade_module_0_5($module) {
    if (_PS_VERSION_ < '1.5' && _PS_VERSION_ > '1.3') {
        $PiwikAnalytics = Tab::getIdFromClassName('PiwikAnalytics');
        if (is_int($PiwikAnalytics) && $PiwikAnalytics > 0) {
            $tab = new Tab($PiwikAnalytics);
            $tab->delete();
        }
        return $this->registerHook('header') &&
                $this->registerHook('footer') &&
                $this->registerHook('search') &&
                $this->registerHook('extraRight') &&
                $this->registerHook('productfooter') &&
                $this->registerHook('orderConfirmation') &&
                $this->registerHook('AdminStatsModules');
    }
    return true;
}
