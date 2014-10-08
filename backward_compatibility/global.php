<?php

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
