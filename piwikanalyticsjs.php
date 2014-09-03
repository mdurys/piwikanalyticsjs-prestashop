<?php

if (!defined('_PS_VERSION_')) exit;

/*
 * Copyright (C) 2014 Christian Jensen
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

/* Backward compatibility */
if (_PS_VERSION_ < '1.5') require_once dirname(__FILE__) . '/backward_compatibility/global.php';

/**
 * Description of piwikanalyticsjs
 *
 * @author Christian
 */
class piwikanalyticsjs extends Module {

    public function __construct($name = null, $context = null) {
        $this->name = 'piwikanalyticsjs';
        $this->tab = 'analytics_stats';
        $this->version = '0.6.1';
        $this->author = 'CMJ Scripter';
        $this->displayName = 'Piwik Web Analytics';

        $this->bootstrap = true;


        if (_PS_VERSION_ < '1.5' && _PS_VERSION_ > '1.3') parent::__construct($name);
        /* Prestashop 1.5 and up implements "$context" */
        if (_PS_VERSION_ >= '1.5') parent::__construct($name, ($context instanceof Context ? $context : NULL));


        if ($this->id && !Configuration::get('PIWIK_TOKEN_AUTH')) $this->warning = (isset($this->warning) && !empty($this->warning) ? $this->warning . ',<br/> ' : '') . $this->l('PIWIK is not ready to roll you need to configure the auth token');
        if ($this->id && !Configuration::get('PIWIK_SITEID')) $this->warning = (isset($this->warning) && !empty($this->warning) ? $this->warning . ',<br/> ' : '') . $this->l('You have not yet set your Piwik Site ID');
        if ($this->id && !Configuration::get('PIWIK_HOST')) $this->warning = (isset($this->warning) && !empty($this->warning) ? $this->warning . ',<br/> ' : '') . $this->l('PIWIK is not ready to roll you need to configure the Piwik server url');

        $this->description = $this->l('Piwik Web Analytics Javascript plugin');
        $this->confirmUninstall = $this->l('Are you sure you want to delete this plugin ?');


        /* Backward compatibility */
        if (_PS_VERSION_ < '1.5') require dirname(__FILE__) . '/backward_compatibility/backward.php';
    }

    /**
     * get content to display in the admin area
     * @global string $currentIndex
     * @return string
     */
    public function getContent() {
        if (_PS_VERSION_ < '1.5') global $currentIndex;
        $_html = "";
        Configuration::get('PIWIK_HOST');
        Configuration::get('PIWIK_TOKEN_AUTH');
        if (Tools::isSubmit('submitUpdate' . $this->name)) {
            if (Tools::getIsset('PIWIK_HOST')) Configuration::updateValue('PIWIK_HOST', Tools::getValue('PIWIK_HOST', ''));
            if (Tools::getIsset('PIWIK_SITEID')) Configuration::updateValue('PIWIK_SITEID', (int) Tools::getValue('PIWIK_SITEID', 0));
            if (Tools::getIsset('PIWIK_TOKEN_AUTH')) Configuration::updateValue('PIWIK_TOKEN_AUTH', Tools::getValue('PIWIK_TOKEN_AUTH'));
            if (Tools::getIsset('PIWIK_COOKIE_TIMEOUT')) Configuration::updateValue('PIWIK_COOKIE_TIMEOUT', Tools::getValue('PIWIK_COOKIE_TIMEOUT'));
            if (Tools::getIsset('PIWIK_SESSION_TIMEOUT')) Configuration::updateValue('PIWIK_SESSION_TIMEOUT', Tools::getValue('PIWIK_SESSION_TIMEOUT'));
            if (Tools::getIsset('PIWIK_USE_PROXY')) Configuration::updateValue('PIWIK_USE_PROXY', Tools::getValue('PIWIK_USE_PROXY'));

            $_html .= $this->displayConfirmation($this->l('Configuration Updated'));
        }
        $fields_form = array();

        $languages = Language::getLanguages(FALSE);
        foreach ($languages as $languages_key => $languages_value) {
            // is_default
            $languages[$languages_key]['is_default'] = ($languages_value['id_lang'] == (int) Configuration::get('PS_LANG_DEFAULT') ? true : false);
        }
        $helper = new HelperForm();
        if (_PS_VERSION_ >= '1.5' && _PS_VERSION_ < '1.6') $helper->base_folder = _PS_MODULE_DIR_ . 'piwikanalyticsjs/views/templates/helpers/form/';

        $helper->languages = $languages;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        if (_PS_VERSION_ < '1.5') $helper->currentIndex = $currentIndex . '&configure=' . $this->name;
        else $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitUpdate' . $this->name;

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->displayName,
                'image' => (_PS_VERSION_ < '1.5' ? $this->_path . 'logo.gif' : $this->_path . 'logo.png')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik Host'),
                    'name' => 'PIWIK_HOST',
                    'desc' => $this->l('Example: www.example.com/piwik/ (without protocol and / at the end!)'),
                    'hint' => $this->l('The host where your piwik is installed.!'),
                    'required' => true
                ),
                array(
                    'type' => 'switch',
                    'is_bool' => true, //retro compat 1.5
                    'label' => $this->l('Use proxy script'),
                    'name' => 'PIWIK_USE_PROXY',
                    'desc' => sprintf($this->l('Whether or not to use the proxy (%s) insted of Piwik Host'), self::getModuleLink($this->name, 'piwik')),
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
     * if product is view in an iframe so we add this and makes sure that it is content only view 
     * @param mixed $param
     * @return string
     */
    public function hookdisplayRightColumnProduct($param) {
        if ((int) Tools::getValue('content_only') > 0 && get_class($this->context->controller) == 'ProductController') { // we also do this in the tpl file.!
            return $this->hookFooter($param);
        }
    }

    /**
     * Search action
     * @param array $param
     */
    public function hookactionSearch($param) {
        $param['total'] = intval($param['total']);
        /* if multi pages in search add page number of current if set! */
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

    /**
     * only checks that the module is registered in hook "footer", 
     * this why we only appen javescript to the end of the page!
     * @param mixed $params
     */
    public function hookHeader($params) {
        if (!$this->isRegisteredInHook('footer')) $this->registerHook('footer');
    }

    public function hookOrderConfirmation($params) {

        $order = $params['objOrder'];
        if (Validate::isLoadedObject($order)) {

            $this->context->smarty->assign('PIWIK_USE_PROXY', (bool) Configuration::get('PIWIK_USE_PROXY'));

            //* using proxy script?
            if ((bool) Configuration::get('PIWIK_USE_PROXY')) $this->context->smarty->assign('PIWIK_HOST', $this->getModuleLink('piwikanalyticsjs', 'piwik'));
            else $this->context->smarty->assign('PIWIK_HOST', Configuration::get('PIWIK_HOST'));

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
                    'SKU' => $value['product_id'] . (isset($value['product_reference']) ? '#' . $value['product_reference'] : (isset($value['product_attribute_id']) ? '#' . $value['product_attribute_id'] : '')),
                    'NAME' => $value['product_name'],
                    'CATEGORY' => $this->get_category_names_by_product($value['product_id'], FALSE),
                    'PRICE' => $this->currencyConvertion(
                            array(
                                'price' => Tools::ps_round(( isset($value['total_price_tax_incl']) ? floatval($value['total_price_tax_incl']) : (isset($value['total_price_tax_incl']) ? floatval($value['total_price_tax_incl']) : 0.00)), 2),
                                'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : false),
                            )
                    ),
                    'QUANTITY' => $value['product_quantity'],
                );
            }
            $this->context->smarty->assign('PIWIK_ORDER_PRODUCTS', $smarty_ad);
            if (isset($params['objOrder']->total_paid_tax_incl) && isset($params['objOrder']->total_paid_tax_excl)) $tax = $params['objOrder']->total_paid_tax_incl - $params['objOrder']->total_paid_tax_excl;
            else if (isset($params['objOrder']->total_products_wt) && isset($params['objOrder']->total_products)) $tax = $params['objOrder']->total_products_wt - $params['objOrder']->total_products;
            else $tax = 0.00;
            $ORDER_DETAILS = array(
                'order_id' => $params['objOrder']->id,
                'order_total' => $this->currencyConvertion(
                        array(
                            'price' => Tools::ps_round(floatval(isset($params['objOrder']->total_paid_tax_incl) ? $params['objOrder']->total_paid_tax_incl : (isset($params['objOrder']->total_paid) ? $params['objOrder']->total_paid : 0.00)), 2),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : false),
                        )
                ),
                'order_sub_total' => $this->currencyConvertion(
                        array(
                            'price' => Tools::ps_round(floatval($params['objOrder']->total_products_wt), 2),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : false),
                        )
                ),
                'order_tax' => $this->currencyConvertion(
                        array(
                            'price' => Tools::ps_round(floatval($tax), 2),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : false),
                        )
                ),
                'order_shipping' => $this->currencyConvertion(
                        array(
                            'price' => Tools::ps_round(floatval((isset($params['objOrder']->total_shipping_tax_incl) ? $params['objOrder']->total_shipping_tax_incl : (isset($params['objOrder']->total_shipping) ? $params['objOrder']->total_shipping : 0.00))), 2),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : false),
                        )
                ),
                'order_discount' => $this->currencyConvertion(
                        array(
                            'price' => (isset($params['objOrder']->total_discounts_tax_incl) ?
                                    ($params['objOrder']->total_discounts_tax_incl > 0 ?
                                            Tools::ps_round(floatval($params['objOrder']->total_discounts_tax_incl), 2) : false) : (isset($params['objOrder']->total_discounts) ?
                                            ($params['objOrder']->total_discounts > 0 ?
                                                    Tools::ps_round(floatval($params['objOrder']->total_discounts), 2) : false) : 0.00)),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : false),
                        )
                ),
            );
            $this->context->smarty->assign('PIWIK_ORDER_DETAILS', $ORDER_DETAILS);
            return $this->display(__FILE__, 'views/templates/hook/jstracking.tpl');
        }
    }

    public function hookFooter($params) {

        if (_PS_VERSION_ < '1.5') {
            /* get page name the LAME way :) */
            if (method_exists($this->context->smarty, 'get_template_vars')) { /* smarty_2 */
                $page_name = $this->context->smarty->get_template_vars('page_name');
            } else if (method_exists($this->context->smarty, 'getTemplateVars')) {/* smarty */
                $page_name = $this->context->smarty->getTemplateVars('page_name');
            } else $page_name = "";
        }

        $this->context->smarty->assign('PIWIK_USE_PROXY', (bool) Configuration::get('PIWIK_USE_PROXY'));

        //* using proxy script?
        if ((bool) Configuration::get('PIWIK_USE_PROXY')) $this->context->smarty->assign('PIWIK_HOST', $this->getModuleLink('piwikanalyticsjs', 'piwik'));
        else $this->context->smarty->assign('PIWIK_HOST', Configuration::get('PIWIK_HOST'));

        $this->context->smarty->assign('PIWIK_SITEID', Configuration::get('PIWIK_SITEID'));

        $this->context->smarty->assign('PIWIK_ORDER', FALSE);

        $this->context->smarty->assign('PIWIK_COOKIE_DOMAIN', '*.' . str_replace('www.', '', Tools::getShopDomain()));

        $this->context->smarty->assign('PIWIK_DOMAINS', Tools::getShopDomain());

        $this->context->smarty->assign('PIWIK_COOKIE_TIMEOUT', Configuration::get('PIWIK_COOKIE_TIMEOUT') ? Configuration::get('PIWIK_COOKIE_TIMEOUT') : 1209600);

        $this->context->smarty->assign('PIWIK_SESSION_TIMEOUT', Configuration::get('PIWIK_SESSION_TIMEOUT') ? Configuration::get('PIWIK_COOKIE_TIMEOUT') : 1209600);

        /* cart tracking */
        if (!$this->context->cookie->PIWIKTrackCartFooter) {
            $this->context->cookie->PIWIKTrackCartFooter = time();
        }
        if (strtotime($this->context->cart->date_upd) >= $this->context->cookie->PIWIKTrackCartFooter) {
            $this->context->cookie->PIWIKTrackCartFooter = strtotime($this->context->cart->date_upd) + 2;
            $smarty_ad = array();
            foreach ($this->context->cart->getProducts() as $key => $value) {
                if (!isset($value['id_product']) || !isset($value['name']) || !isset($value['total_wt']) || !isset($value['quantity'])) {
                    continue;
                }
                $smarty_ad[] = array(
                    'SKU' => $value['id_product'],
                    'NAME' => $value['name'] . (isset($value['attributes']) ? ' (' . $value['attributes'] . ')' : ''),
                    'CATEGORY' => $this->get_category_names_by_product($value['id_product'], FALSE),
                    'PRICE' => $this->currencyConvertion(
                            array(
                                'price' => Tools::ps_round($value['total_wt'], 2),
                            )
                    ),
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

        if (_PS_VERSION_ < '1.5') $this->_hookFooterPS14($params, $page_name);
        else if (_PS_VERSION_ >= '1.5') $this->_hookFooter($params);

        return $this->display(__FILE__, 'views/templates/hook/jstracking.tpl');
    }

    /**
     * add Prestashop !LATEST! specific settings
     * @param mixed $params
     * @since 0.4
     */
    private function _hookFooter($params) {

        /* product tracking */
        if (get_class($this->context->controller) == 'ProductController') {
            $products = array(array('product' => $this->context->controller->getProduct(), 'categorys' => NULL));
            if (isset($products) && isset($products[0]['product'])) {
                $smarty_ad = array();
                foreach ($products as $product) {
                    if (!Validate::isLoadedObject($product['product'])) continue;
                    if ($product['categorys'] == NULL) $product['categorys'] = $this->get_category_names_by_product($product['product']->id, FALSE);
                    $smarty_ad[] = array(
                        /* (required) SKU: Product unique identifier */
                        'SKU' => $product['product']->id . (!empty($product['product']->reference) ? "#" . $product['product']->reference : ""),
                        /* (optional) Product name */
                        'NAME' => $product['product']->name,
                        /* (optional) Product category, or array of up to 5 categories */
                        'CATEGORY' => $product['categorys'], //$category->name,
                        /* (optional) Product Price as displayed on the page */
                        'PRICE' => $this->currencyConvertion(
                                array(
                                    'price' => Tools::ps_round(Product::getPriceStatic($product['product']->id, true), 2),
                                )
                        ),
                    );
                }
                $this->context->smarty->assign(array('PIWIK_PRODUCTS' => $smarty_ad));
                unset($smarty_ad);
            }
        }

        /* category tracking */
        if (get_class($this->context->controller) == 'CategoryController') {
            $category = $this->context->controller->getCategory();
            if (Validate::isLoadedObject($category)) {
                $this->context->smarty->assign(array(
                    'piwik_category' => array('NAME' => $category->name),
                ));
            }
        }
    }

    /* Prestashop 1.4 only HOOKs */

    /**
     * add Prestashop 1.4 specific settings
     * @param mixed $params
     * @since 0.4
     */
    private function _hookFooterPS14($params, $page_name) {
        if (empty($page_name)) {
            /* we can't do any thing use full  */
            return;
        }

        if (strtolower($page_name) == "product" && isset($_GET['id_product']) && Validate::isUnsignedInt($_GET['id_product'])) {
            $product = new ProductCore($_GET['id_product'], false, (isset($_GET['id_lang']) && Validate::isUnsignedInt($_GET['id_lang']) == 2 ? $_GET['id_lang'] : NULL));
            if (!Validate::isLoadedObject($product)) return;
            $product_categorys = $this->get_category_names_by_product($product->id, FALSE);
            $smarty_ad = array(
                array(
                    /* (required) SKU: Product unique identifier */
                    'SKU' => $product->id . (!empty($product->reference) ? "#" . $product->reference : ""),
                    /* (optional) Product name */
                    'NAME' => $product->name,
                    /* (optional) Product category, or array of up to 5 categories */
                    'CATEGORY' => $product_categorys,
                    /* (optional) Product Price as displayed on the page */
                    'PRICE' => $this->currencyConvertion(
                            array(
                                'price' => Tools::ps_round(Product::getPriceStatic($product->id, true), 2),
                            )
                    ),
                )
            );
            $this->context->smarty->assign(array('PIWIK_PRODUCTS' => $smarty_ad));
            unset($smarty_ad);
        }
        /* category tracking */
        if (strtolower($page_name) == "category" && isset($_GET['id_category']) && Validate::isUnsignedInt($_GET['id_category'])) {
            $category = new Category($_GET['id_category'], (isset($_GET['id_lang']) && Validate::isUnsignedInt($_GET['id_lang']) == 2 ? $_GET['id_lang'] : NULL));
            $this->context->smarty->assign(array(
                'piwik_category' => array('NAME' => $category->name),
            ));
        }
    }

    /**
     * search action
     * @param array $params
     * @since 0.4
     */
    public function hookSearch($params) {
        $this->hookactionSearch($params);
    }

    /**
     * hook into admin stats page on prestashop version 1.4
     * @param array $params
     * @return string
     * @since 0.5
     */
    public function hookAdminStatsModules($params) {
        $lng = new LanguageCore($params['cookie']->id_lang);
        $html = '<script type="text/javascript">function WidgetizeiframeDashboardLoaded() {var w = $(\'#content\').width();var h = $(\'body\').height();$(\'#WidgetizeiframeDashboard\').width(\'100%\');$(\'#WidgetizeiframeDashboard\').height(h);}</script>'
                . '<fieldset class="width3">'
                . '<legend><img src="../modules/' . $this->name . '/logo.gif" /> ' . $this->displayName . '</legend>'
                . '<iframe id="WidgetizeiframeDashboard"  onload="WidgetizeiframeDashboardLoaded();" src="http://'
                . Configuration::get('PIWIK_HOST') . 'index.php'
                . '?module=Widgetize'
                . '&action=iframe'
                . '&moduleToWidgetize=Dashboard'
                . '&actionToWidgetize=index'
                . '&idSite=' . (int) Configuration::get('PIWIK_SITEID')
                . '&period=day'
                . '&language=' . $lng->iso_code
                . '&token_auth=' . Configuration::get('PIWIK_TOKEN_AUTH')
                . '&date=today" frameborder="0" marginheight="0" marginwidth="0" width="100%" height="550px"></iframe>'
                . '</fieldset>';
        return $html;
    }

    /*
     * 
     * your may add code here if you have some sort af advanched theme that uses iframes for products view
     * if you got iframes for displaying products pages the product will not be tracked by piwik unless you added some code for it.!
     * 
     * hookExtraRight
     * hookProductfooter
     */

    /**
     * Extra Right hook on products page!
     * @param mixed $params
     * @return string
     * @since 0.4
     */
    public function hookExtraRight($params) {
        // $params['cookie'] (OBJECT)
        // $params['cart'] (OBJECT)
        return "";
        // this should be sufficient as long as you add some sort of content only settings
        // return $this->hookFooter($param);
    }

    /**
     * Footer hook on products page!
     * @param mixed $params
     * @return string
     * @since 0.4
     */
    public function hookProductfooter($params) {
        // $params[product] (OBJECT)
        // $params['category'] (OBJECT)
        // $params['cookie'] (OBJECT)
        // $params['cart'] (OBJECT)
        return "";
        // this should be sufficient as long as you add some sort of content only settings
        // return $this->hookFooter($param);
    }

    /* HELPERS */

    /**
     * convert into default currentcy used in piwik
     * @param array $params
     * @return float
     * @since 0.4
     */
    private function currencyConvertion($params) {
        return (float) $params['price'];
    }

    /**
     * get category names by product id
     * @param integer $id product id
     * @param boolean $array get categories as PHP array (TRUE), or javacript (FAlSE)
     * @return string|array
     */
    private function get_category_names_by_product($id, $array = true) {
        $_categories = ProductCore::getProductCategoriesFull($id, $this->context->cookie->id_lang);
        if (!is_array($_categories)) {
            if ($array) return array();
            else return "[]";
        }

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

    /**
     * get module link
     * @param string $module
     * @param string $controller
     * @return string
     * @since 0.4
     */
    public static function getModuleLink($module, $controller = 'default') {
        if (_PS_VERSION_ < '1.5') return Tools::getShopDomainSsl(true, true) . _MODULE_DIR_ . $module . '/' . $controller . '.php';
        else return Context::getContext()->link->getModuleLink($module, $controller);
    }

    /* INSTALL / UNINSTALL */

    /**
     * Install the module
     * @return boolean false on install error
     */
    public function install() {

        /*
         * check ps version
         * add piwik iframe dashboard as submenu of stats main menu in prestashop
         * --- if unable to get id of prestashop admin class "AdminParentStats" set piwik class as "-1 == main menu item"
         */
        if (_PS_VERSION_ < '1.5' && _PS_VERSION_ > '1.3') {
            /* use tab in default stats page
              $AdminParentStats = Tab::getIdFromClassName('AdminStats');
              $tab->id_parent = (is_int($AdminParentStats) ? $AdminParentStats : -1);
             */
        } else if (_PS_VERSION_ >= '1.5') {
            /* create complete new page tab */
            $tab = new TabCore();
            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[(int) $lang['id_lang']] = 'Piwik Analytics';
            }
            $tab->module = 'piwikanalyticsjs';
            $tab->active = TRUE;
            $tab->class_name = 'PiwikAnalytics';
            $AdminParentStats = Tab::getInstanceFromClassName('AdminParentStats');
            $tab->id_parent = ($AdminParentStats instanceof Tab ? $AdminParentStats->id : -1);
            if ($tab->add()) Configuration::updateValue('PIWIK_TAPID', (int) $tab->id);
        }

        /* default values */
        Configuration::updateValue('PIWIK_USE_PROXY', 1);
        Configuration::updateValue('PIWIK_HOST', "");
        Configuration::updateValue('PIWIK_SITEID', 0);
        Configuration::updateValue('PIWIK_TOKEN_AUTH', "");
        Configuration::updateValue('PIWIK_COOKIE_TIMEOUT', 1209600);
        Configuration::updateValue('PIWIK_SESSION_TIMEOUT', 1209600);

        if (_PS_VERSION_ < '1.5' && _PS_VERSION_ > '1.3') {
            return (parent::install() && $this->registerHook('header') && $this->registerHook('footer') && $this->registerHook('search') && $this->registerHook('extraRight') && $this->registerHook('productfooter') && $this->registerHook('orderConfirmation') && $this->registerHook('AdminStatsModules'));
        } else if (_PS_VERSION_ >= '1.5') {
            return (parent::install() && $this->registerHook('header') && $this->registerHook('footer') && $this->registerHook('actionSearch') && $this->registerHook('displayRightColumnProduct') && $this->registerHook('orderConfirmation'));
        }
    }

    /**
     * Uninstall the module
     * @return boolean false on uninstall error
     */
    public function uninstall() {
        if (parent::uninstall()) {
            Configuration::deleteByName('PIWIK_HOST');
            Configuration::deleteByName('PIWIK_SITEID');
            Configuration::deleteByName('PIWIK_TOKEN_AUTH');
            Configuration::deleteByName('PIWIK_COOKIE_TIMEOUT');
            Configuration::deleteByName('PIWIK_SESSION_TIMEOUT');
            Configuration::deleteByName('PIWIK_USE_PROXY');
            try {
                if (_PS_VERSION_ < '1.5' && _PS_VERSION_ > '1.3') {
                    
                } else if (_PS_VERSION_ >= '1.5') {
                    $tab = Tab::getInstanceFromClassName('PiwikAnalytics');
                    $tab->delete();
                }
            } catch (Exception $ex) {
                
            }
            Configuration::deleteByName('PIWIK_TAPID');
            return true;
        }
        return false;
    }

}
