<?php

/*
 * Copyright (C) 21-05-2014 Christian Jensen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Description of piwikanalyticsjs
 *
 * @author Christian
 */
class piwikanalyticsjs extends Module {

    public function __construct($name = null, \Context $context = null) {
        $this->name = 'piwikanalyticsjs';
        $this->tab = 'analytics_stats';
        $this->version = '0.2';
        $this->author = 'CMJ Scripter';
        $this->displayName = 'Piwik Web Analytics';

        $this->bootstrap = true;

        parent::__construct($name, $context);

        if (!Configuration::get('PIWIK_TOKEN_AUTH'))
            $this->warning = (isset($this->warning) ? $this->warning . '<br/>' : '') . $this->l('PIWIK is not ready to roll you need to configure the auth token');
        if ($this->id && !Configuration::get('PIWIK_SITEID'))
            $this->warning = (isset($this->warning) ? $this->warning . '<br/>' : '') . $this->l('You have not yet set your Piwik Analytics ID');
        if (!Configuration::get('PIWIK_HOST'))
            $this->warning = (isset($this->warning) ? $this->warning . '<br/>' : '') . $this->l('PIWIK is not ready to roll you need to configure the piwik server url');

        $this->description = $this->l('Piwik Web Analytics Javascript plugin');
        $this->confirmUninstall = $this->l('Are you sure you want to delete this plugin ?');
    }

    public function uninstall() {
        try {
            $tab = Tab::getInstanceFromClassName('PiwikAnalytics');
            $tab->delete();
        } catch (Exception $ex) {
            
        }
        return Configuration::deleteByName('PIWIK_TAPID') && 
                Configuration::deleteByName('PIWIK_USE_PROXY') &&
                Configuration::deleteByName('PIWIK_HOST') &&
                Configuration::deleteByName('PIWIK_TOKEN_AUTH') &&
                parent::uninstall();
    }

    public function install() {
        $tab = new Tab();
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Piwik Analytics';
        }
        $tab->module = 'piwikanalyticsjs';
        $tab->active = TRUE;
        $tab->class_name = 'PiwikAnalytics';
        $AdminParentStats = Tab::getInstanceFromClassName('AdminParentStats');
        $tab->id_parent = $AdminParentStats->id;
        if ($tabid = $tab->add()) {
            Configuration::updateValue('PIWIK_TAPID', $tabid);
        }

        Configuration::updateValue('PIWIK_USE_PROXY', 1);
        return (parent::install() &&
                $this->registerHook('footer') &&
                $this->registerHook('actionSearch') &&
                $this->registerHook('displayRightColumnProduct') &&
                $this->registerHook('orderConfirmation'));
    }

    public function getContent() {
        $_html = "";
        if (Tools::isSubmit('submitUpdate' . $this->name)) {
            if (Tools::getIsset('PIWIK_HOST'))
                Configuration::updateValue('PIWIK_HOST', Tools::getValue('PIWIK_HOST', ''));
            if (Tools::getIsset('PIWIK_SITEID'))
                Configuration::updateValue('PIWIK_SITEID', (int) Tools::getValue('PIWIK_SITEID', 0));
            if (Tools::getIsset('PIWIK_TOKEN_AUTH'))
                Configuration::updateValue('PIWIK_TOKEN_AUTH', Tools::getValue('PIWIK_TOKEN_AUTH'));
            if (Tools::getIsset('PIWIK_COOKIE_TIMEOUT'))
                Configuration::updateValue('PIWIK_COOKIE_TIMEOUT', Tools::getValue('PIWIK_COOKIE_TIMEOUT'));
            if (Tools::getIsset('PIWIK_SESSION_TIMEOUT'))
                Configuration::updateValue('PIWIK_SESSION_TIMEOUT', Tools::getValue('PIWIK_SESSION_TIMEOUT'));
            if (Tools::getIsset('PIWIK_USE_PROXY'))
                Configuration::updateValue('PIWIK_USE_PROXY', Tools::getValue('PIWIK_USE_PROXY'));

            $_html .= $this->displayConfirmation($this->l('Configuration Updated'));
        }
        $fields_form = array();

        $languages = Language::getLanguages(FALSE);
        foreach ($languages as $languages_key => $languages_value) {
            // is_default
            $languages[$languages_key]['is_default'] = ($languages_value['id_lang'] == (int) Configuration::get('PS_LANG_DEFAULT') ? true : false);
        }
        $helper = new HelperForm();
        $helper->languages = $languages;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitUpdate' . $this->name;

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->displayName,
                'image' => $this->_path . 'logo.png'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik Host'),
                    'name' => 'PIWIK_HOST',
                    'desc' => $this->l('Example: www.example.com/piwik/ (without protocol and with / at the end!)'),
                    'hint' => $this->l('The host where your piwik is installed.!'),
                    'required' => true
                ),
                array(
                    'type' => 'switch',
                    'is_bool' => true, //retro compat 1.5
                    'label' => $this->l('Use proxy script'),
                    'name' => 'PIWIK_USE_PROXY',
                    'desc' => sprintf($this->l('Whether or not to use the proxy (%s) insted of Piwik Host'), Context::getContext()->link->getModuleLink('piwikanalyticsjs', 'piwik')),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik site id'),
                    'name' => 'PIWIK_SITEID',
                    'desc' => $this->l('Example: 10'),
                    'hint' => $this->l('You can find your piwik site id by loggin to your piwik installation.'),
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik token auth'),
                    'name' => 'PIWIK_TOKEN_AUTH',
                    'desc' => $this->l('You can find your piwik token by loggin to your piwik installation. under API'),
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik Session Cookie timeout'),
                    'name' => 'PIWIK_SESSION_TIMEOUT',
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik Cookie timeout'),
                    'name' => 'PIWIK_COOKIE_TIMEOUT',
                    'required' => true
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );
        $helper->fields_value['PIWIK_HOST'] = Configuration::get('PIWIK_HOST');
        $helper->fields_value['PIWIK_SITEID'] = Configuration::get('PIWIK_SITEID');
        $helper->fields_value['PIWIK_TOKEN_AUTH'] = Configuration::get('PIWIK_TOKEN_AUTH');
        $helper->fields_value['PIWIK_SESSION_TIMEOUT'] = Configuration::get('PIWIK_SESSION_TIMEOUT');
        $helper->fields_value['PIWIK_COOKIE_TIMEOUT'] = Configuration::get('PIWIK_COOKIE_TIMEOUT');
        $helper->fields_value['PIWIK_USE_PROXY'] = Configuration::get('PIWIK_USE_PROXY');

        return $_html . $helper->generateForm($fields_form);
    }

    /* HOOKs */

    /**
     * PIWIK don't track links on the same site eg. 
     * if product is view in an iframe so we add this and make sure that it is content only view 
     * @param type $param
     * @return type
     */
    public function hookdisplayRightColumnProduct($param) {
        if ((int) Tools::getValue('content_only') > 0 && get_class($this->context->controller) == 'ProductController') {
            return $this->hookFooter($param);
        }
    }

    public function hookactionSearch($param) {
        $param['total'] = intval($param['total']);
        /* if multi side søgning tilføj side nummer */
        $page = "";
        if (Tools::getIsset('p')) {
            $page = " (" . Tools::getValue('p') . ")";
        }
        // $param['expr'] is not the searched word if lets say search is Snitmøntre then the $param['expr'] will be Snitmontre
        $expr = Tools::getIsset('search_query') ? htmlentities(Tools::getValue('search_query')) : $param['expr'];
        $this->context->smarty->assign(array(
            'PIWIK_SITE_SEARCH' => "_paq.push(['trackSiteSearch',\"{$expr}{$page}\",'false',{$param['total']}]);"
        ));
    }

    public function hookHeader($params) {
        if (!$this->isRegisteredInHook('footer'))
            $this->registerHook('footer');
    }

    public function hookOrderConfirmation($params) {

        $order = $params['objOrder'];
        if (Validate::isLoadedObject($order)) {

            $this->context->smarty->assign('PIWIK_USE_PROXY', (bool) Configuration::get('PIWIK_USE_PROXY'));

            //* tjek om vi bruger modul proxy script eller ej.
            if ((bool) Configuration::get('PIWIK_USE_PROXY'))
                $this->context->smarty->assign('PIWIK_HOST', Context::getContext()->link->getModuleLink('piwikanalyticsjs', 'piwik'));
            else
                $this->context->smarty->assign('PIWIK_HOST', Configuration::get('PIWIK_HOST'));

            $this->context->smarty->assign('PIWIK_SITEID', Configuration::get('PIWIK_SITEID'));

            $this->context->smarty->assign('PIWIK_ORDER', TRUE);

            $this->context->smarty->assign('PIWIK_CART', FALSE);

            $this->context->smarty->assign('PIWIK_COOKIE_DOMAIN', '*.' . str_replace('www.', '', Tools::getShopDomain()));

            $this->context->smarty->assign('PIWIK_DOMAINS', Tools::getShopDomain());

            $this->context->smarty->assign('PIWIK_COOKIE_TIMEOUT', Configuration::get('PIWIK_COOKIE_TIMEOUT') ? Configuration::get('PIWIK_COOKIE_TIMEOUT') : 1209600);

            $this->context->smarty->assign('PIWIK_SESSION_TIMEOUT', Configuration::get('PIWIK_SESSION_TIMEOUT') ? Configuration::get('PIWIK_COOKIE_TIMEOUT') : 1209600);

            $smarty_ad = array();
            foreach ($params['objOrder']->getProductsDetail() as $value) {
                $smarty_ad[] = array(
                    'SKU' => $value['product_id'],
                    'NAME' => $value['product_name'],
                    'CATEGORY' => $this->get_category_names_by_product($value['product_id'], FALSE),
                    'PRICE' => Tools::ps_round(floatval($value['total_price_tax_incl']), 2),
                    'QUANTITY' => $value['product_quantity'],
                );
            }
            $this->context->smarty->assign('PIWIK_ORDER_PRODUCTS', $smarty_ad);
            $tax = $params['objOrder']->total_paid_tax_incl - $params['objOrder']->total_paid_tax_excl;
            $ORDER_DETAILS = array(
                'order_id' => $params['objOrder']->id,
                'order_total' => Tools::ps_round(floatval($params['objOrder']->total_paid_tax_incl), 2),
                'order_sub_total' => Tools::ps_round(floatval($params['objOrder']->total_products_wt), 2),
                'order_tax' => Tools::ps_round(floatval($tax), 2),
                'order_shipping' => Tools::ps_round(floatval($params['objOrder']->total_shipping_tax_incl), 2),
                'order_discount' => ($params['objOrder']->total_discounts_tax_incl > 0 ? Tools::ps_round(floatval($params['objOrder']->total_discounts_tax_incl), 2) : 'false'),
            );
            $this->context->smarty->assign('PIWIK_ORDER_DETAILS', $ORDER_DETAILS);
            return $this->display(__FILE__, 'views/templates/hook/jstracking.tpl');
        }
    }

    public function hookFooter($params) {

        $this->context->smarty->assign('PIWIK_USE_PROXY', (bool) Configuration::get('PIWIK_USE_PROXY'));

        //* tjek om vi bruger modul proxy script eller ej.
        if ((bool) Configuration::get('PIWIK_USE_PROXY'))
            $this->context->smarty->assign('PIWIK_HOST', Context::getContext()->link->getModuleLink('piwikanalyticsjs', 'piwik'));
        else
            $this->context->smarty->assign('PIWIK_HOST', Configuration::get('PIWIK_HOST'));

        $this->context->smarty->assign('PIWIK_SITEID', Configuration::get('PIWIK_SITEID'));

        $this->context->smarty->assign('PIWIK_ORDER', FALSE);

        $this->context->smarty->assign('PIWIK_COOKIE_DOMAIN', '*.' . str_replace('www.', '', Tools::getShopDomain()));

        $this->context->smarty->assign('PIWIK_DOMAINS', Tools::getShopDomain());

        $this->context->smarty->assign('PIWIK_COOKIE_TIMEOUT', Configuration::get('PIWIK_COOKIE_TIMEOUT') ? Configuration::get('PIWIK_COOKIE_TIMEOUT') : 1209600);

        $this->context->smarty->assign('PIWIK_SESSION_TIMEOUT', Configuration::get('PIWIK_SESSION_TIMEOUT') ? Configuration::get('PIWIK_COOKIE_TIMEOUT') : 1209600);

        /* Tilføj kurv tracking */
        if (!$this->context->cookie->PIWIKTrackCartFooter) {
            $this->context->cookie->PIWIKTrackCartFooter = time();
        }
        if (strtotime($this->context->cart->date_upd) >= $this->context->cookie->PIWIKTrackCartFooter) {
            $this->context->cookie->PIWIKTrackCartFooter = strtotime($this->context->cart->date_upd) + 2;

            foreach ($this->context->cart->getProducts() as $key => $value) {
                if (!isset($value['id_product']) || !isset($value['name']) || !isset($value['total_wt']) || !isset($value['quantity'])) {
                    continue;
                }
                $smarty_ad[] = array(
                    'SKU' => $value['id_product'],
                    'NAME' => $value['name'] . (isset($value['attributes']) ? ' (' . $value['attributes'] . ')' : ''),
                    'CATEGORY' => $this->get_category_names_by_product($value['id_product'], FALSE),
                    'PRICE' => Tools::ps_round($value['total_wt'], 2),
                    'QUANTITY' => $value['quantity'],
                );
            }
            if (count($smarty_ad) > 0) {
                $this->context->smarty->assign('PIWIK_CART', TRUE);
                $this->context->smarty->assign('PIWIK_CART_PRODUCTS', $smarty_ad);
                $this->context->smarty->assign('PIWIK_CART_TOTAL', $this->context->cart->getOrderTotal());
            } else {
                $this->context->smarty->assign('PIWIK_CART', FALSE);
            }
            unset($smarty_ad);
        } else {
            $this->context->smarty->assign('PIWIK_CART', FALSE);
        }
        /* Tilføj product tracking */
        if (get_class($this->context->controller) == 'ProductController') {
            $products = array(array('product' => $this->context->controller->getProduct(), 'categorys' => NULL));
            if (isset($products) && isset($products[0]['product'])) {
                $smarty_ad = array();
                foreach ($products as $product) {
                    if (!Validate::isLoadedObject($product['product']))
                        continue;
                    if ($product['categorys'] == NULL)
                        $product['categorys'] = $this->get_category_names_by_product($product['product']->id, FALSE);
                    $smarty_ad[] = array(
                        /* (required) SKU: Product unique identifier */
                        'SKU' => $product['product']->id . "#" . $product['product']->reference,
                        /* (optional) Product name */
                        'NAME' => $product['product']->name,
                        /* (optional) Product category, or array of up to 5 categories */
                        'CATEGORY' => $product['categorys'], //$category->name,
                        /* (optional) Product Price as displayed on the page */
                        'PRICE' => Tools::ps_round(Product::getPriceStatic($product['product']->id, true), 2),
                    );
                }
                $this->context->smarty->assign(array('PIWIK_PRODUCTS' => $smarty_ad));
                unset($smarty_ad);
            }
        }
        /* Tilføj kategori tracking */
        if (get_class($this->context->controller) == 'CategoryController') {
            $category = $this->context->controller->getCategory();
            if (Validate::isLoadedObject($category)) {
                $this->context->smarty->assign(array(
                    'piwik_category' => array('NAME' => $category->name),
                ));
            }
        }

        return $this->display(__FILE__, 'views/templates/hook/jstracking.tpl');
    }

    private function get_category_names_by_product($id, $array = true) {
        $_categories = Product::getProductCategoriesFull($id, $this->context->cookie->id_lang);
        if ($array) {
            $categories = array();
            foreach ($_categories as $category) {
                $categories[] = $category['name'];
            }
        } else {
            $categories = '[';
            foreach ($_categories as $category) {
                $categories .= '"' . $category['name'] . '",';
            }
            $categories = rtrim($categories, ',');
            $categories .= ']';
        }
        return $categories;
    }

}
