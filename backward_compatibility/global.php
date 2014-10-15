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

if (!defined('_PS_VERSION_')) exit;

if (_PS_VERSION_ < '1.5') {
    /* to simplify the module i added the needed helper-form classes from prestashop 1.5 */
    if (!in_array('HelperCore', get_declared_classes())) require_once dirname(__FILE__) . '/helper/Helper.php';
    if (!in_array('HelperFormCore', get_declared_classes())) require_once dirname(__FILE__) . '/helper/HelperForm.php';
}
if (!in_array('HelperFormCore', get_declared_classes())) {

    class HelperFormCore {

        /**
         * get module link
         * @param string $module
         * @param string $controller
         * @return string
         */
        public static function getModuleLink($module, $controller = 'default', array $params = array(), $ssl = null, $id_lang = null, $id_shop = null) {
            $query = http_build_query($params, '', '&');
            if (_PS_VERSION_ < '1.5') return ($ssl ? Tools::getShopDomainSsl(true, true) : Tools::getShopDomain(true, true)) . _MODULE_DIR_ . $module . '/' . $controller . '.php' . ($query ? '?' . $query : '');
            else return Context::getContext()->link->getModuleLink(getModuleLink($module, $controller, $params, $ssl, $id_lang, $id_shop));
        }

    }

}
