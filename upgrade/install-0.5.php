<?php

/*
 * Copyright (C) 2014 Christian Jensen
 *
 * This file is part of PiwikAnalyticsJS for prestashop.
 * 
 * PiwikAnalyticsJS for prestashop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * PiwikAnalyticsJS for prestashop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with PiwikAnalyticsJS for prestashop.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link http://cmjnisse.github.io/piwikanalyticsjs-prestashop
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

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
