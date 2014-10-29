<?php

if (!defined('_PS_VERSION_'))
    exit;

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


/* Backward compatibility */
if (_PS_VERSION_ < '1.5') {
    if (version_compare(_PS_VERSION_, '1.4.5.1', '<=')) {
        include _PS_ROOT_DIR_ . '/modules/piwikanalyticsjs/backward_compatibility/global.php';
    } else {
        require_once dirname(__FILE__) . '/backward_compatibility/global.php';
    }
}

/**
 * Description of piwikanalyticsjs
 *
 * @author Christian
 */
class piwikanalyticsjs extends Module {

    private static $_isOrder = FALSE;
    protected $_errors = "";
    protected $piwikSite = FALSE;
    protected $default_currency = array();
    protected $currencies = array();

    /**
     * setReferralCookieTimeout
     */
    const PK_RC_TIMEOUT = 262974;

    /**
     * setVisitorCookieTimeout
     */
    const PK_VC_TIMEOUT = 569777;

    /**
     * setSessionCookieTimeout
     */
    const PK_SC_TIMEOUT = 30;

    public function __construct($name = null, $context = null) {
        $this->name = 'piwikanalyticsjs';
        $this->tab = 'analytics_stats';
        $this->version = '0.6.9';
        $this->author = 'CMJ Scripter';
        $this->displayName = 'Piwik Web Analytics';

        $this->bootstrap = true;


        if (_PS_VERSION_ < '1.5' && _PS_VERSION_ > '1.3')
            parent::__construct($name);
        /* Prestashop 1.5 and up implements "$context" */
        if (_PS_VERSION_ >= '1.5')
            parent::__construct($name, ($context instanceof Context ? $context : NULL));

        //* warnings on module list page
        if ($this->id && !Configuration::get('PIWIK_TOKEN_AUTH'))
            $this->warning = (isset($this->warning) && !empty($this->warning) ? $this->warning . ',<br/> ' : '') . $this->l('is not ready to roll you need to configure the auth token');
        if ($this->id && ((int) Configuration::get('PIWIK_SITEID') <= 0))
            $this->warning = (isset($this->warning) && !empty($this->warning) ? $this->warning . ',<br/> ' : '') . $this->l('You have not yet set your Piwik Site ID');
        if ($this->id && !Configuration::get('PIWIK_HOST'))
            $this->warning = (isset($this->warning) && !empty($this->warning) ? $this->warning . ',<br/> ' : '') . $this->l('is not ready to roll you need to configure the Piwik server url');

        $this->description = $this->l('Piwik Web Analytics Javascript plugin');
        $this->confirmUninstall = $this->l('Are you sure you want to delete this plugin ?');


        /* Backward compatibility */
        if (_PS_VERSION_ < '1.5') {
            if (version_compare(_PS_VERSION_, '1.4.5.1', '<=')) {
                include _PS_ROOT_DIR_ . '/modules/piwikanalyticsjs/backward_compatibility/backward.php';
            } else {
                require dirname(__FILE__) . '/backward_compatibility/backward.php';
            }
        }
        self::$_isOrder = FALSE;
        require_once dirname(__FILE__) . '/PKHelper.php';
        $this->_errors = PKHelper::$errors = PKHelper::$error = "";
    }

    /**
     * get content to display in the admin area
     * @global string $currentIndex
     * @return string
     */
    public function getContent() {
        if (_PS_VERSION_ < '1.5')
            global $currentIndex;
        $_html = "";
        $_html .= $this->processFormsUpdate();
        $this->piwikSite = PKHelper::getPiwikSite();
        $this->displayErrors(PKHelper::$errors);
        PKHelper::$errors = PKHelper::$error = "";
        $this->__setCurrencies();

        //* warnings on module configure page
        if ($this->id && !Configuration::get('PIWIK_TOKEN_AUTH') && !Tools::getIsset('PIWIK_TOKEN_AUTH')) /* avoid the same error message twice */
            $this->_errors .= $this->displayError($this->l('Piwik auth token is empty'));
        if ($this->id && ((int) Configuration::get('PIWIK_SITEID') <= 0) && !Tools::getIsset('PIWIK_SITEID')) /* avoid the same error message twice */
            $this->_errors .= $this->displayError($this->l('Piwik site id is lower or equal to "0"'));
        if ($this->id && !Configuration::get('PIWIK_HOST'))
            $this->_errors .= $this->displayError($this->l('Piwik host cannot be empty'));

        $fields_form = array();

        $languages = Language::getLanguages(FALSE);
        foreach ($languages as $languages_key => $languages_value) {
            // is_default
            $languages[$languages_key]['is_default'] = ($languages_value['id_lang'] == (int) Configuration::get('PS_LANG_DEFAULT') ? true : false);
        }
        $helper = new HelperForm();
        if (_PS_VERSION_ >= '1.5' && _PS_VERSION_ < '1.6')
            $helper->base_folder = _PS_MODULE_DIR_ . 'piwikanalyticsjs/views/templates/helpers/form/';

        $helper->languages = $languages;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        if (_PS_VERSION_ < '1.5')
            $helper->currentIndex = $currentIndex . '&configure=' . $this->name;
        else
            $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitUpdate' . $this->name;

        $fields_form[0]['form']['legend'] = array(
            'title' => $this->displayName,
            'image' => (_PS_VERSION_ < '1.5' ? $this->_path . 'logo.gif' : $this->_path . 'logo.png')
        );

        if ($this->piwikSite !== FALSE) {
            $fields_form[0]['form']['input'][] = array(
                'type' => 'html',
                'name' => $this->l('Based on the settings you provided this is the info i get from Piwik!') . "<br>"
                . "<strong>" . $this->l('Name') . "</strong>: <i>{$this->piwikSite[0]->name}</i><br>"
                . "<strong>" . $this->l('Main Url') . "</strong>: <i>{$this->piwikSite[0]->main_url}</i><br>"
            );
        }

        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Piwik Host'),
            'name' => 'PIWIK_HOST',
            'desc' => $this->l('Example: www.example.com/piwik/ (without protocol and with / at the end!)'),
            'hint' => $this->l('The host where your piwik is installed.!'),
            'required' => true
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'switch',
            'is_bool' => true, //retro compat 1.5
            'label' => $this->l('Use proxy script'),
            'name' => 'PIWIK_USE_PROXY',
            'desc' => $this->l('Whether or not to use the proxy insted of Piwik Host'),
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
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Proxy script'),
            'name' => 'PIWIK_PROXY_SCRIPT',
            'hint' => $this->l('Example: www.example.com/pkproxy.php'),
            'desc' => sprintf($this->l('the FULL path to proxy script to use, build-in: [%s]'), self::getModuleLink($this->name, 'piwik')),
            'required' => false
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Piwik site id'),
            'name' => 'PIWIK_SITEID',
            'desc' => $this->l('Example: 10'),
            'hint' => $this->l('You can find your piwik site id by loggin to your piwik installation.'),
            'required' => true
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Piwik token auth'),
            'name' => 'PIWIK_TOKEN_AUTH',
            'desc' => $this->l('You can find your piwik token by loggin to your piwik installation. under API'),
            'required' => true
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Track visitors across subdomains'),
            'name' => 'PIWIK_COOKIE_DOMAIN',
            'desc' => $this->l('The default is the document domain; if your web site can be visited at both www.example.com and example.com, you would use: "*.example.com" OR ".example.com" without the quotes')
            . '<br />'
            . $this->l('Leave empty to exclude this from the tracking code'),
            'hint' => $this->l('So if one visitor visits x.example.com and y.example.com, they will be counted as a unique visitor. (setCookieDomain)'),
            'required' => false
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Hide known alias URLs'),
            'name' => 'PIWIK_SET_DOMAINS',
            'desc' => $this->l('In the "Outlinks" report, hide clicks to known alias URLs, Example: *.example.com')
            . '<br />'
            . $this->l('Note: to add multiple domains you must separate them with space " " one space')
            . '<br />'
            . $this->l('Note: the currently tracked website is added to this array automatically')
            . '<br />'
            . $this->l('Leave empty to exclude this from the tracking code'),
            'hint' => $this->l('So clicks on links to Alias URLs (eg. x.example.com) will not be counted as "Outlink". (setDomains)'),
            'required' => false
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'switch',
            'is_bool' => true, //retro compat 1.5
            'label' => $this->l('Enable client side DoNotTrack detection'),
            'name' => 'PIWIK_DNT',
            'desc' => $this->l('So tracking requests will not be sent if visitors do not wish to be tracked.'),
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
        );
        $image_tracking = PKHelper::getPiwikImageTrackingCode();
        $this->displayErrors(PKHelper::$errors);
        PKHelper::$errors = PKHelper::$error = "";
        $fields_form[0]['form']['input'][] = array(
            'type' => 'html',
            'name' => $this->l('Piwik image tracking code append one of them to field "Extra HTML" this will add images tracking code to all your pages') . "<br>"
            . "<strong>" . $this->l('default') . "</strong>:<br /><i>{$image_tracking['default']}</i><br>"
            . "<strong>" . $this->l('using proxy script') . "</strong>:<br /><i>{$image_tracking['proxy']}</i><br>"
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'textarea',
            'label' => $this->l('Extra HTML'),
            'name' => 'PIWIK_EXHTML',
            'desc' => $this->l('Some extra HTML code to put after the piwik tracking code, this can be any html of your choice'),
            'rows' => 10,
            'cols' => 50,
        );

        $fields_form[0]['form']['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Piwik Currency'),
            'name' => 'PIWIK_DEFAULT_CURRENCY',
            'desc' => sprintf($this->l('Based on your settings in Piwik your default currency is %s'), ($this->piwikSite !== FALSE ? $this->piwikSite[0]->currency : $this->l('unknown'))),
            'options' => array(
                'default' => $this->default_currency,
                'query' => $this->currencies,
                'id' => 'iso_code',
                'name' => 'name'
            ),
        );
        
        $fields_form[0]['form']['submit'] = array(
            'title' => $this->l('Save'),
        );



        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->displayName . ' ' . $this->l('Advanced'),
                'image' => (_PS_VERSION_ < '1.5' ? $this->_path . 'logo.gif' : $this->_path . 'logo.png')
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => $this->l('In this section you can modify certain aspects of the way this plugin sends products, searches, category view etc.. to piwik')
                ),
                array(
                    'type' => 'switch',
                    'is_bool' => true, //retro compat 1.5
                    'label' => $this->l('Use HTTPS'),
                    'name' => 'PIWIK_CRHTTPS',
                    'hint' => $this->l('ONLY enable this feature if your piwik installation is accessible via https'),
                    'desc' => $this->l('use Hypertext Transfer Protocol Secure (HTTPS) in all requests from code to piwik, this only affects how requests are sent from proxy script to piwik, your visitors will still use the protocol they visit your shop with'),
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
                    'type' => 'html',
                    'name' => $this->l('in the next few inputs you can set how the product id is passed on to piwik')
                    . '<br />'
                    . $this->l('there are three variables you can use:')
                    . '<br />'
                    . $this->l('{ID} : this variable is replaced with id the product has in prestashop')
                    . '<br />'
                    . $this->l('{REFERENCE} : this variable is replaced with the unique reference you when adding adding/updating a product, this variable is only available in prestashop 1.5 and up')
                    . '<br />'
                    . $this->l('{ATTRID} : this variable is replaced with id the product attribute')
                    . '<br />'
                    . $this->l('in cases where only the product id is available it be parsed as ID and nothing else'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Product id V1'),
                    'name' => 'PIWIK_PRODID_V1',
                    'desc' => $this->l('This template is used in case ALL three values are available ("Product ID", "Product Attribute ID" and "Product Reference")'),
                    'required' => false
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Product id V2'),
                    'name' => 'PIWIK_PRODID_V2',
                    'desc' => $this->l('This template is used in case only "Product ID" and "Product Reference" are available'),
                    'required' => false
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Product id V3'),
                    'name' => 'PIWIK_PRODID_V3',
                    'desc' => $this->l('This template is used in case only "Product ID" and "Product Attribute ID" are available'),
                    'required' => false
                ),
                array(
                    'type' => 'html',
                    'name' => "<strong>{$this->l('Piwik Cookies')}</strong>"
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik Session Cookie timeout'),
                    'name' => 'PIWIK_SESSION_TIMEOUT',
                    'required' => false,
                    'hint' => $this->l('this value must be set in minutes'),
                    'desc' => $this->l('Piwik Session Cookie timeout, the default is 30 minutes'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik Visitor Cookie timeout'),
                    'name' => 'PIWIK_COOKIE_TIMEOUT',
                    'required' => false,
                    'hint' => $this->l('this value must be set in minutes'),
                    'desc' => $this->l('Piwik Visitor Cookie timeout, the default is 13 months (569777 minutes)'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Piwik Referral Cookie timeout'),
                    'name' => 'PIWIK_RCOOKIE_TIMEOUT',
                    'required' => false,
                    'hint' => $this->l('this value must be set in minutes'),
                    'desc' => $this->l('Piwik Referral Cookie timeout, the default is 6 months (262974 minutes)'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        if ($this->piwikSite !== FALSE) {
            $tmp = PKHelper::getMyPiwikSites(TRUE);
            $this->displayErrors(PKHelper::$errors);
            PKHelper::$errors = PKHelper::$error = "";
            $pksite_default = array('value' => 0, 'label' => $this->l('Choose Piwik site'));
            $pksites = array();
            foreach ($tmp as $pksid) {
                $pksites[] = array(
                    'pkid' => $pksid->idsite,
                    'name' => "{$pksid->name} #{$pksid->idsite}",
                );
            }
            unset($tmp, $pksid);

            $pktimezone_default = array('value' => 0, 'label' => $this->l('Choose Timezone'));
            $pktimezones = array();
            $tmp = PKHelper::getTimezonesList();
            $this->displayErrors(PKHelper::$errors);
            PKHelper::$errors = PKHelper::$error = "";
            foreach ($tmp as $key => $pktz) {
                if (!isset($pktimezones[$key])) {
                    $pktimezones[$key] = array(
                        'name' => $this->l($key),
                        'query' => array(),
                    );
                }
                foreach ($pktz as $pktzK => $pktzV) {
                    $pktimezones[$key]['query'][] = array(
                        'tzId' => $pktzK,
                        'tzName' => $pktzV,
                    );
                }
            }
            unset($tmp, $pktz, $pktzV, $pktzK);
            $fields_form[2]['form'] = array(
                'legend' => array(
                    'title' => $this->displayName . ' ' . $this->l('Advanced') . ' - ' . $this->l('Edit your Piwik site'),
                    'image' => (_PS_VERSION_ < '1.5' ? $this->_path . 'logo.gif' : $this->_path . 'logo.png')
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Piwik Site'),
                        'name' => 'SPKSID',
                        'desc' => sprintf($this->l('Based on your settings in Piwik your default site is %s'), $this->piwikSite[0]->idsite),
                        'options' => array(
                            'default' => $pksite_default,
                            'query' => $pksites,
                            'id' => 'pkid',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'html',
                        'name' => $this->l('In this section you can modify your settings in piwik just so you don\'t have to login to Piwik to do this') . "<br>"
                        . "<strong>" . $this->l('Currently selected name') . "</strong>: <i>{$this->piwikSite[0]->name}</i><br>"
                        . "<input type=\"hidden\" name=\"PKAdminIdSite\" id=\"PKAdminIdSite\" value=\"{$this->piwikSite[0]->idsite}\" />"
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Piwik Site Name'),
                        'name' => 'PKAdminSiteName',
                        'desc' => $this->l('Name of this site in Piwik'),
                    ),
//                    array(
//                        'type' => 'text',
//                        'label' => $this->l('Site urls'),
//                        'name' => 'PKAdminSiteUrls',
//                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Ecommerce'),
                        'name' => 'PKAdminEcommerce',
                        'desc' => $this->l('Is this site an ecommerce site?'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Site Search'),
                        'name' => 'PKAdminSiteSearch',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Search Keyword Parameters'),
                        'name' => 'PKAdminSearchKeywordParameters',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Search Category Parameters'),
                        'name' => 'PKAdminSearchCategoryParameters',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Excluded ip addresses'),
                        'name' => 'PKAdminExcludedIps',
                        'desc' => $this->l('ip addresses excluded from tracking, separated by comma ","'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Excluded Query Parameters'),
                        'name' => 'PKAdminExcludedQueryParameters',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Timezone'),
                        'name' => 'PKAdminTimezone',
                        'desc' => sprintf($this->l('Based on your settings in Piwik your default timezone is %s'), $this->piwikSite[0]->timezone),
                        'options' => array(
                            'default' => $pktimezone_default,
                            'optiongroup' => array(
                                'label' => 'name',
                                'query' => $pktimezones,
                            ),
                            'options' => array(
                                'id' => 'tzId',
                                'name' => 'tzName',
                                'query' => 'query',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Currency'),
                        'name' => 'PKAdminCurrency',
                        'desc' => sprintf($this->l('Based on your settings in Piwik your default currency is %s'), $this->piwikSite[0]->currency),
                        'options' => array(
                            'default' => $this->default_currency,
                            'query' => $this->currencies,
                            'id' => 'iso_code',
                            'name' => 'name'
                        ),
                    ),
//                    array(
//                        'type' => 'text',
//                        'label' => $this->l('Website group'),
//                        'name' => 'PKAdminGroup',
//                    ),
//                    array(
//                        'type' => 'text',
//                        'label' => $this->l('Website start date'),
//                        'name' => 'PKAdminStartDate',
//                    ),
//                    array(
//                        'type' => 'textarea',
//                        'label' => $this->l('Excluded User Agents'),
//                        'name' => 'PKAdminExcludedUserAgents',
//                        'rows' => 10,
//                        'cols' => 50,
//                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Keep URL Fragments'),
                        'name' => 'PKAdminKeepURLFragments',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
//                    array(
//                        'type' => 'text',
//                        'label' => $this->l('Site Type'),
//                        'name' => 'PKAdminSiteType',
//                    ),
                    /* */
                    array(
                        'type' => 'html',
                        'name' => "<button onclick=\"return submitPiwikSiteAPIUpdate()\" id=\"submitUpdatePiwikAdmSite\" class=\"btn btn-default pull-left\" name=\"submitUpdatePiwikAdmSite\" value=\"1\" type=\"button\"><i class=\"process-icon-save\"></i>" . $this->l('Save') . "</button>"
                        . "<script type=\"text/javascript\">"
                        . "function submitPiwikSiteAPIUpdate(){\n"
                        . "    var idSite = $('#PKAdminIdSite').val();\n"
                        . "    var siteName = $('#PKAdminSiteName').val();\n"
                        . "    /*var urls = $('#PKAdminSiteUrls').val();*/\n"
                        . "    var ecommerce = $('input[name=PKAdminEcommerce]:checked').val();\n"
                        . "    var siteSearch = $('input[name=PKAdminSiteSearch]:checked').val();\n"
                        . "    var searchKeywordParameters = $('#PKAdminSearchKeywordParameters').val();\n"
                        . "    var searchCategoryParameters = $('#PKAdminSearchCategoryParameters').val();\n"
                        . "    var excludedIps = $('#PKAdminExcludedIps').val();\n"
                        . "    var excludedQueryParameters = $('#PKAdminExcludedQueryParameters').val();\n"
                        . "    var timezone = $('#PKAdminTimezone').val();\n"
                        . "    var currency = $('#PKAdminCurrency').val();\n"
                        . "    /*var group = $('#PKAdminGroup').val();*/\n"
                        . "    /*var startDate = $('#PKAdminStartDate').val();*/\n"
                        . "    /*var excludedUserAgents = $('#PKAdminExcludedUserAgents').val();*/\n"
                        . "    var keepURLFragments = $('#PKAdminKeepURLFragments').val();\n"
                        . "    /*var type = $('#PKAdminSiteType').val();*/\n"
                        . "    \n"
                        . "    \n"
                        . "    \n"
                        . "return false;"
                        . "}"
                        . "</script>",
                    /*
                     *  SitesManager.updateSite (
                     *      idSite,siteName = '',
                     *      urls = '', ecommerce = '',
                     *      siteSearch = '', searchKeywordParameters = '',
                     *      searchCategoryParameters = '', excludedIps = '',
                     *      excludedQueryParameters = '',  timezone = '',
                     *      currency = '', group = '', startDate = '',
                     *      excludedUserAgents = '',
                     *      keepURLFragments = '',
                     *      type = ''
                     *  )
                     */
                    ),
                ),
            );
        }
        $helper->fields_value = $this->getFormFields();
        return $this->_errors . $_html . $helper->generateForm($fields_form);
    }

    protected function getFormFields() {
        $PIWIK_PRODID_V1 = Configuration::get('PIWIK_PRODID_V1');
        $PIWIK_PRODID_V2 = Configuration::get('PIWIK_PRODID_V2');
        $PIWIK_PRODID_V3 = Configuration::get('PIWIK_PRODID_V3');
        $PIWIK_PROXY_SCRIPT = Configuration::get('PIWIK_PROXY_SCRIPT');
        $PIWIK_RCOOKIE_TIMEOUT = (int) Configuration::get('PIWIK_RCOOKIE_TIMEOUT');
        $PIWIK_COOKIE_TIMEOUT = (int) Configuration::get('PIWIK_COOKIE_TIMEOUT');
        $PIWIK_SESSION_TIMEOUT = (int) Configuration::get('PIWIK_SESSION_TIMEOUT');
        return array(
            'PIWIK_HOST' => Configuration::get('PIWIK_HOST'),
            'SPKSID' => Configuration::get('PIWIK_SITEID'),
            'PIWIK_SITEID' => Configuration::get('PIWIK_SITEID'),
            'PIWIK_TOKEN_AUTH' => Configuration::get('PIWIK_TOKEN_AUTH'),
            'PIWIK_SESSION_TIMEOUT' => ($PIWIK_SESSION_TIMEOUT != 0 ? (int) ($PIWIK_SESSION_TIMEOUT / 60) : (int) (self::PK_SC_TIMEOUT )),
            'PIWIK_COOKIE_TIMEOUT' => ($PIWIK_COOKIE_TIMEOUT != 0 ? (int) ($PIWIK_COOKIE_TIMEOUT / 60) : (int) (self::PK_VC_TIMEOUT)),
            'PIWIK_RCOOKIE_TIMEOUT' => ($PIWIK_RCOOKIE_TIMEOUT != 0 ? (int) ($PIWIK_RCOOKIE_TIMEOUT / 60) : (int) (self::PK_RC_TIMEOUT)),
            'PIWIK_USE_PROXY' => Configuration::get('PIWIK_USE_PROXY'),
            'PIWIK_EXHTML' => Configuration::get('PIWIK_EXHTML'),
            'PIWIK_CRHTTPS' => Configuration::get('PIWIK_CRHTTPS'),
            'PIWIK_DEFAULT_CURRENCY' => Configuration::get("PIWIK_DEFAULT_CURRENCY"),
            'PIWIK_PRODID_V1' => (!empty($PIWIK_PRODID_V1) ? $PIWIK_PRODID_V1 : '{ID}-{ATTRID}#{REFERENCE}'),
            'PIWIK_PRODID_V2' => (!empty($PIWIK_PRODID_V2) ? $PIWIK_PRODID_V2 : '{ID}#{REFERENCE}'),
            'PIWIK_PRODID_V3' => (!empty($PIWIK_PRODID_V3) ? $PIWIK_PRODID_V3 : '{ID}-{ATTRID}'),
            'PIWIK_COOKIE_DOMAIN' => Configuration::get('PIWIK_COOKIE_DOMAIN'),
            'PIWIK_SET_DOMAINS' => Configuration::get('PIWIK_SET_DOMAINS'),
            'PIWIK_DNT' => Configuration::get('PIWIK_DNT'),
            'PIWIK_PROXY_SCRIPT' => empty($PIWIK_PROXY_SCRIPT) ? str_replace(array("http://", "https://"), '', self::getModuleLink($this->name, 'piwik')) : $PIWIK_PROXY_SCRIPT,
            /* stuff thats isset by ajax calls to Piwik API ---(here to avoid not isset warnings..!)--- */
            'PKAdminSiteName' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->name : ''),
            'PKAdminEcommerce' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->ecommerce : ''),
            'PKAdminSiteSearch' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->sitesearch : ''),
            'PKAdminSearchKeywordParameters' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->sitesearch_keyword_parameters : ''),
            'PKAdminSearchCategoryParameters' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->sitesearch_category_parameters : ''),
            'SPKSID' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->idsite : Configuration::get('PIWIK_SITEID')),
            'PKAdminExcludedIps' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->excluded_ips : ''),
            'PKAdminExcludedQueryParameters' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->excluded_parameters : ''),
            'PKAdminTimezone' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->timezone : ''),
            'PKAdminCurrency' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->currency : ''),
            'PKAdminGroup' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->group : ''),
            'PKAdminStartDate' => '',
            'PKAdminSiteUrls' => '',
            'PKAdminExcludedUserAgents' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->excluded_user_agents : ''),
            'PKAdminKeepURLFragments' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->keep_url_fragment : 0),
            'PKAdminSiteType' => ($this->piwikSite !== FALSE ? $this->piwikSite[0]->type : 'website'),
        );
    }

    private function processFormsUpdate() {

        $_html = "";
        if (Tools::isSubmit('submitUpdate' . $this->name)) {
            if (Tools::getIsset('PIWIK_HOST')) {
                $tmp = Tools::getValue('PIWIK_HOST', '');
                if (!empty($tmp) && (filter_var($tmp, FILTER_VALIDATE_URL) || filter_var('http://' . $tmp, FILTER_VALIDATE_URL))) {
                    $tmp = str_replace(array('http://', 'https://', '//'), "", $tmp);
                    if (substr($tmp, -1) != "/") {
                        $tmp .= "/";
                    }
                    Configuration::updateValue('PIWIK_HOST', $tmp);
                } else {
                    $_html .= $this->displayError($this->l('Piwik host cannot be empty'));
                }
            }
            if (Tools::getIsset('PIWIK_SITEID')) {
                $tmp = (int) Tools::getValue('PIWIK_SITEID', 0);
                Configuration::updateValue('PIWIK_SITEID', $tmp);
                if ($tmp <= 0) {
                    $_html .= $this->displayError($this->l('Piwik site id is lower or equal to "0"'));
                }
            }
            if (Tools::getIsset('PIWIK_TOKEN_AUTH')) {
                $tmp = Tools::getValue('PIWIK_TOKEN_AUTH', '');
                Configuration::updateValue('PIWIK_TOKEN_AUTH', $tmp);
                if (empty($tmp)) {
                    $_html .= $this->displayError($this->l('Piwik auth token is empty'));
                }
            }
            /* setReferralCookieTimeout */
            if (Tools::getIsset('PIWIK_RCOOKIE_TIMEOUT')) {
                // the default is 6 months
                $tmp = (int) Tools::getValue('PIWIK_RCOOKIE_TIMEOUT', self::PK_RC_TIMEOUT);
                $tmp = (int) ($tmp * 60); //* convert to seconds
                Configuration::updateValue('PIWIK_RCOOKIE_TIMEOUT', $tmp);
            }
            /* setVisitorCookieTimeout */
            if (Tools::getIsset('PIWIK_COOKIE_TIMEOUT')) {
                // the default is 13 months
                $tmp = (int) Tools::getValue('PIWIK_COOKIE_TIMEOUT', self::PK_VC_TIMEOUT);
                $tmp = (int) ($tmp * 60); //* convert to seconds
                Configuration::updateValue('PIWIK_COOKIE_TIMEOUT', $tmp);
            }
            /* setSessionCookieTimeout */
            if (Tools::getIsset('PIWIK_SESSION_TIMEOUT')) {
                // the default is 30 minutes
                $tmp = (int) Tools::getValue('PIWIK_SESSION_TIMEOUT', self::PK_SC_TIMEOUT);
                $tmp = (int) ($tmp * 60); //* convert to seconds
                Configuration::updateValue('PIWIK_SESSION_TIMEOUT', $tmp);
            }
            if (Tools::getIsset('PIWIK_USE_PROXY'))
                Configuration::updateValue('PIWIK_USE_PROXY', Tools::getValue('PIWIK_USE_PROXY'));
            if (Tools::getIsset('PIWIK_EXHTML'))
                Configuration::updateValue('PIWIK_EXHTML', Tools::getValue('PIWIK_EXHTML'), TRUE);
            if (Tools::getIsset('PIWIK_COOKIE_DOMAIN'))
                Configuration::updateValue('PIWIK_COOKIE_DOMAIN', Tools::getValue('PIWIK_COOKIE_DOMAIN'));
            if (Tools::getIsset('PIWIK_SET_DOMAINS'))
                Configuration::updateValue('PIWIK_SET_DOMAINS', Tools::getValue('PIWIK_SET_DOMAINS'));
            if (Tools::getIsset('PIWIK_DNT'))
                Configuration::updateValue('PIWIK_DNT', Tools::getValue('PIWIK_DNT', 0));
            if (Tools::getIsset('PIWIK_PROXY_SCRIPT'))
                Configuration::updateValue('PIWIK_PROXY_SCRIPT', str_replace(array("http://", "https://", '//'), '', Tools::getValue('PIWIK_PROXY_SCRIPT')));
            if (Tools::getIsset('PIWIK_CRHTTPS'))
                Configuration::updateValue('PIWIK_CRHTTPS', Tools::getValue('PIWIK_CRHTTPS', 0));
            if (Tools::getIsset('PIWIK_PRODID_V1'))
                Configuration::updateValue('PIWIK_PRODID_V1', Tools::getValue('PIWIK_PRODID_V1', '{ID}-{ATTRID}#{REFERENCE}'));
            if (Tools::getIsset('PIWIK_PRODID_V2'))
                Configuration::updateValue('PIWIK_PRODID_V2', Tools::getValue('PIWIK_PRODID_V2', '{ID}#{REFERENCE}'));
            if (Tools::getIsset('PIWIK_PRODID_V3'))
                Configuration::updateValue('PIWIK_PRODID_V3', Tools::getValue('PIWIK_PRODID_V3', '{ID}#{ATTRID}'));
            if (Tools::getIsset('PIWIK_DEFAULT_CURRENCY'))
                Configuration::updateValue("PIWIK_DEFAULT_CURRENCY", Tools::getValue('PIWIK_DEFAULT_CURRENCY', 'EUR'));

            $_html .= $this->displayConfirmation($this->l('Configuration Updated'));
        }

        return $_html;
    }

    /* HOOKs */

    /**
     * PIWIK don't track links on the same site eg. 
     * if product is view in an iframe so we add this and makes sure that it is content only view 
     * @param mixed $param
     * @return string
     */
    public function hookdisplayRightColumnProduct($param) {
        if ((int) Configuration::get('PIWIK_SITEID') <= 0)
            return "";
        if ((int) Tools::getValue('content_only') > 0 && get_class($this->context->controller) == 'ProductController') { // we also do this in the tpl file.!
            return $this->hookFooter($param);
        }
    }

    /**
     * Search action
     * @param array $param
     */
    public function hookactionSearch($param) {
        if ((int) Configuration::get('PIWIK_SITEID') <= 0)
            return "";
        $param['total'] = intval($param['total']);
        /* if multi pages in search add page number of current if set! */
        $page = "";
        if (Tools::getIsset('p')) {
            $page = " (" . Tools::getValue('p') . ")";
        }
        // $param['expr'] is not the searched word if lets say search is Snitmøntre then the $param['expr'] will be Snitmontre
        $expr = Tools::getIsset('search_query') ? htmlentities(Tools::getValue('search_query')) : $param['expr'];
        $this->context->smarty->assign(array(
            'PIWIK_SITE_SEARCH' => "_paq.push(['trackSiteSearch',\"{$expr}{$page}\",false,{$param['total']}]);"
        ));
    }

    /**
     * only checks that the module is registered in hook "footer", 
     * this why we only appent javescript to the end of the page!
     * @param mixed $params
     */
    public function hookHeader($params) {
        if (!$this->isRegisteredInHook('footer'))
            $this->registerHook('footer');
    }

    public function hookOrderConfirmation($params) {
        if ((int) Configuration::get('PIWIK_SITEID') <= 0)
            return "";

        $order = $params['objOrder'];
        if (Validate::isLoadedObject($order)) {

            $this->__setConfigDefault();

            $this->context->smarty->assign('PIWIK_ORDER', TRUE);
            $this->context->smarty->assign('PIWIK_CART', FALSE);


            $smarty_ad = array();
            foreach ($params['objOrder']->getProductsDetail() as $value) {
                $smarty_ad[] = array(
                    'SKU' => $this->parseProductSku($value['product_id'], (isset($value['product_attribute_id']) ? $value['product_attribute_id'] : FALSE), (isset($value['product_reference']) ? $value['product_reference'] : FALSE)),
                    'NAME' => $value['product_name'],
                    'CATEGORY' => $this->get_category_names_by_product($value['product_id'], FALSE),
                    'PRICE' => $this->currencyConvertion(
                            array(
                                'price' => (isset($value['total_price_tax_incl']) ? floatval($value['total_price_tax_incl']) : (isset($value['total_price_tax_incl']) ? floatval($value['total_price_tax_incl']) : 0.00)),
                                'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : 0.00),
                            )
                    ),
                    'QUANTITY' => $value['product_quantity'],
                );
            }
            $this->context->smarty->assign('PIWIK_ORDER_PRODUCTS', $smarty_ad);
            if (isset($params['objOrder']->total_paid_tax_incl) && isset($params['objOrder']->total_paid_tax_excl))
                $tax = $params['objOrder']->total_paid_tax_incl - $params['objOrder']->total_paid_tax_excl;
            else if (isset($params['objOrder']->total_products_wt) && isset($params['objOrder']->total_products))
                $tax = $params['objOrder']->total_products_wt - $params['objOrder']->total_products;
            else
                $tax = 0.00;
            $ORDER_DETAILS = array(
                'order_id' => $params['objOrder']->id,
                'order_total' => $this->currencyConvertion(
                        array(
                            'price' => floatval(isset($params['objOrder']->total_paid_tax_incl) ? $params['objOrder']->total_paid_tax_incl : (isset($params['objOrder']->total_paid) ? $params['objOrder']->total_paid : 0.00)),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : 0.00),
                        )
                ),
                'order_sub_total' => $this->currencyConvertion(
                        array(
                            'price' => floatval($params['objOrder']->total_products_wt),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : 0.00),
                        )
                ),
                'order_tax' => $this->currencyConvertion(
                        array(
                            'price' => floatval($tax),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : 0.00),
                        )
                ),
                'order_shipping' => $this->currencyConvertion(
                        array(
                            'price' => floatval((isset($params['objOrder']->total_shipping_tax_incl) ? $params['objOrder']->total_shipping_tax_incl : (isset($params['objOrder']->total_shipping) ? $params['objOrder']->total_shipping : 0.00))),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : 0.00),
                        )
                ),
                'order_discount' => $this->currencyConvertion(
                        array(
                            'price' => (isset($params['objOrder']->total_discounts_tax_incl) ?
                                    ($params['objOrder']->total_discounts_tax_incl > 0 ?
                                            floatval($params['objOrder']->total_discounts_tax_incl) : false) : (isset($params['objOrder']->total_discounts) ?
                                            ($params['objOrder']->total_discounts > 0 ?
                                                    floatval($params['objOrder']->total_discounts) : false) : 0.00)),
                            'conversion_rate' => (isset($params['objOrder']->conversion_rate) ? $params['objOrder']->conversion_rate : 0.00),
                        )
                ),
            );
            $this->context->smarty->assign('PIWIK_ORDER_DETAILS', $ORDER_DETAILS);

            // avoid double tracking on complete order.
            self::$_isOrder = TRUE;
            return $this->display(__FILE__, 'views/templates/hook/jstracking.tpl');
        }
    }

    public function hookFooter($params) {
        if ((int) Configuration::get('PIWIK_SITEID') <= 0)
            return "";

        if (self::$_isOrder)
            return "";


        if (_PS_VERSION_ < '1.5') {
            /* get page name the LAME way :) */
            if (method_exists($this->context->smarty, 'get_template_vars')) { /* smarty_2 */
                $page_name = $this->context->smarty->get_template_vars('page_name');
            } else if (method_exists($this->context->smarty, 'getTemplateVars')) {/* smarty */
                $page_name = $this->context->smarty->getTemplateVars('page_name');
            } else
                $page_name = "";
        }
        $this->__setConfigDefault();
        $this->context->smarty->assign('PIWIK_ORDER', FALSE);

        /* cart tracking */
        if (!$this->context->cookie->PIWIKTrackCartFooter) {
            $this->context->cookie->PIWIKTrackCartFooter = time();
        }
        if (strtotime($this->context->cart->date_upd) >= $this->context->cookie->PIWIKTrackCartFooter) {
            $this->context->cookie->PIWIKTrackCartFooter = strtotime($this->context->cart->date_upd) + 2;
            $smarty_ad = array();

            $Currency = new Currency($this->context->cart->id_currency);
            foreach ($this->context->cart->getProducts() as $key => $value) {
                if (!isset($value['id_product']) || !isset($value['name']) || !isset($value['total_wt']) || !isset($value['quantity'])) {
                    continue;
                }
                $smarty_ad[] = array(
                    'SKU' => $this->parseProductSku($value['id_product'], (isset($value['id_product_attribute']) && $value['id_product_attribute'] > 0 ? $value['id_product_attribute'] : FALSE), (isset($value['reference']) ? $value['reference'] : FALSE)),
                    'NAME' => $value['name'] . (isset($value['attributes']) ? ' (' . $value['attributes'] . ')' : ''),
                    'CATEGORY' => $this->get_category_names_by_product($value['id_product'], FALSE),
                    'PRICE' => $this->currencyConvertion(
                            array(
                                'price' => $value['total_wt'],
                                'conversion_rate' => $Currency->conversion_rate,
                            )
                    ),
                    'QUANTITY' => $value['quantity'],
                );
            }
            if (count($smarty_ad) > 0) {
                $this->context->smarty->assign('PIWIK_CART', TRUE);
                $this->context->smarty->assign('PIWIK_CART_PRODUCTS', $smarty_ad);
                $this->context->smarty->assign('PIWIK_CART_TOTAL', $this->currencyConvertion(
                                array(
                                    'price' => $this->context->cart->getOrderTotal(),
                                    'conversion_rate' => $Currency->conversion_rate,
                                )
                ));
            } else {
                $this->context->smarty->assign('PIWIK_CART', FALSE);
            }
            unset($smarty_ad);
        } else {
            $this->context->smarty->assign('PIWIK_CART', FALSE);
        }

        if (_PS_VERSION_ < '1.5')
            $this->_hookFooterPS14($params, $page_name);
        else if (_PS_VERSION_ >= '1.5')
            $this->_hookFooter($params);

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
                    if (!Validate::isLoadedObject($product['product']))
                        continue;
                    if ($product['categorys'] == NULL)
                        $product['categorys'] = $this->get_category_names_by_product($product['product']->id, FALSE);
                    $smarty_ad[] = array(
                        /* (required) SKU: Product unique identifier */
                        'SKU' => $this->parseProductSku($product['product']->id, FALSE, (isset($product['product']->reference) ? $product['product']->reference : FALSE)),
                        /* (optional) Product name */
                        'NAME' => $product['product']->name,
                        /* (optional) Product category, or array of up to 5 categories */
                        'CATEGORY' => $product['categorys'], //$category->name,
                        /* (optional) Product Price as displayed on the page */
                        'PRICE' => $this->currencyConvertion(
                                array(
                                    'price' => Product::getPriceStatic($product['product']->id, true, false),
                                    'conversion_rate' => $this->context->currency->conversion_rate,
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
            $product = new Product($_GET['id_product'], false, (isset($_GET['id_lang']) && Validate::isUnsignedInt($_GET['id_lang']) ? $_GET['id_lang'] : NULL));
            if (!Validate::isLoadedObject($product))
                return;
            $product_categorys = $this->get_category_names_by_product($product->id, FALSE);
            $smarty_ad = array(
                array(
                    /* (required) SKU: Product unique identifier */
                    'SKU' => $this->parseProductSku($product->id, FALSE, (isset($product->reference) ? $product->reference : FALSE)),
                    /* (optional) Product name */
                    'NAME' => $product->name,
                    /* (optional) Product category, or array of up to 5 categories */
                    'CATEGORY' => $product_categorys,
                    /* (optional) Product Price as displayed on the page */
                    'PRICE' => $this->currencyConvertion(
                            array(
                                'price' => Product::getPriceStatic($product->id, true, false),
                                'conversion_rate' => false,
                            )
                    ),
                )
            );
            $this->context->smarty->assign(array('PIWIK_PRODUCTS' => $smarty_ad));
            unset($smarty_ad);
        }
        /* category tracking */
        if (strtolower($page_name) == "category" && isset($_GET['id_category']) && Validate::isUnsignedInt($_GET['id_category'])) {
            $category = new Category($_GET['id_category'], (isset($_GET['id_lang']) && Validate::isUnsignedInt($_GET['id_lang']) ? $_GET['id_lang'] : NULL));
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
        if ((int) Configuration::get('PIWIK_SITEID') <= 0)
            return "";
        $this->hookactionSearch($params);
    }

    /**
     * hook into admin stats page on prestashop version 1.4
     * @param array $params
     * @return string
     * @since 0.5
     */
    public function hookAdminStatsModules($params) {
        $PIWIK_HOST = Configuration::get('PIWIK_HOST');
        $PIWIK_SITEID = (int) Configuration::get('PIWIK_SITEID');
        $PIWIK_TOKEN_AUTH = Configuration::get('PIWIK_TOKEN_AUTH');
        if ((empty($PIWIK_HOST) || $PIWIK_HOST === FALSE) ||
                ($PIWIK_SITEID <= 0 || $PIWIK_SITEID === FALSE) ||
                (empty($PIWIK_TOKEN_AUTH) || $PIWIK_TOKEN_AUTH === FALSE))
            return "<h3>{$this->l("You need to set 'Piwik host url', 'Piwik token auth' and 'Piwik site id', and save them before the dashboard can be shown here")}</h3>";
        $lng = new Language($params['cookie']->id_lang);
        $html = '<script type="text/javascript">function WidgetizeiframeDashboardLoaded() {var w = $(\'#content\').width();var h = $(\'body\').height();$(\'#WidgetizeiframeDashboard\').width(\'100%\');$(\'#WidgetizeiframeDashboard\').height(h);}</script>'
                . '<fieldset class="width3">'
                . '<legend><img src="../modules/' . $this->name . '/logo.gif" /> ' . $this->displayName . '</legend>'
                . '<iframe id="WidgetizeiframeDashboard"  onload="WidgetizeiframeDashboardLoaded();" '
                . 'src="' . ((bool) Configuration::get('PIWIK_CRHTTPS') ? 'https://' : 'http://')
                . $PIWIK_HOST . 'index.php'
                . '?module=Widgetize'
                . '&action=iframe'
                . '&moduleToWidgetize=Dashboard'
                . '&actionToWidgetize=index'
                . '&idSite=' . $PIWIK_SITEID
                . '&period=day'
                . '&language=' . $lng->iso_code
                . '&token_auth=' . $PIWIK_TOKEN_AUTH
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
        if ((int) Configuration::get('PIWIK_SITEID') <= 0)
            return "";
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
        if ((int) Configuration::get('PIWIK_SITEID') <= 0)
            return "";
        // $params[product] (OBJECT)
        // $params['category'] (OBJECT)
        // $params['cookie'] (OBJECT)
        // $params['cart'] (OBJECT)
        return "";
        // this should be sufficient as long as you add some sort of content only settings
        // return $this->hookFooter($param);
    }

    /* HELPERS */

    private function parseProductSku($id, $attrid = FALSE, $ref = FALSE) {
        if (Validate::isInt($id) && (!empty($attrid) && !is_null($attrid) && $attrid !== FALSE) && (!empty($ref) && !is_null($ref) && $ref !== FALSE)) {
            $PIWIK_PRODID_V1 = Configuration::get('PIWIK_PRODID_V1');
            return str_replace(array('{ID}', '{ATTRID}', '{REFERENCE}'), array($id, $attrid, $ref), $PIWIK_PRODID_V1);
        } elseif (Validate::isInt($id) && (!empty($ref) && !is_null($ref) && $ref !== FALSE)) {
            $PIWIK_PRODID_V2 = Configuration::get('PIWIK_PRODID_V2');
            return str_replace(array('{ID}', '{REFERENCE}'), array($id, $ref), $PIWIK_PRODID_V2);
        } elseif (Validate::isInt($id) && (!empty($attrid) && !is_null($attrid) && $attrid !== FALSE)) {
            $PIWIK_PRODID_V3 = Configuration::get('PIWIK_PRODID_V3');
            return str_replace(array('{ID}', '{ATTRID}'), array($id, $attrid), $PIWIK_PRODID_V3);
        } else {
            return $id;
        }
    }

    public function displayErrors($errors) {
        if (!empty($errors)) {
            foreach ($errors as $key => $value) {
                $this->_errors .= $this->displayError($value);
            }
        }
    }

    /**
     * convert into default currentcy used in piwik
     * @param array $params
     * @return float
     * @since 0.4
     */
    private function currencyConvertion($params) {
        $pkc = Configuration::get("PIWIK_DEFAULT_CURRENCY");
        if (empty($pkc))
            return (float) $params['price'];
        if ($params['conversion_rate'] === FALSE || $params['conversion_rate'] == 0.00 || $params['conversion_rate'] == 1.00) {
            //* shop default
            return Tools::convertPrice((float) $params['price'], Currency::getCurrencyInstance((int) (Currency::getIdByIsoCode($pkc))));
        } else {
            $_shop_price = (float) ((float) $params['price'] / (float) $params['conversion_rate']);
            return Tools::convertPrice($_shop_price, Currency::getCurrencyInstance((int) (Currency::getIdByIsoCode($pkc))));
        }
        return (float) $params['price'];
    }

    /**
     * get category names by product id
     * @param integer $id product id
     * @param boolean $array get categories as PHP array (TRUE), or javacript (FAlSE)
     * @return string|array
     */
    private function get_category_names_by_product($id, $array = true) {
        $_categories = Product::getProductCategoriesFull($id, $this->context->cookie->id_lang);
        if (!is_array($_categories)) {
            if ($array)
                return array();
            else
                return "[]";
        }

        if ($array) {
            $categories = array();
            foreach ($_categories as $category) {
                $categories[] = $category['name'];
                if (count($categories) == 5)
                    break;
            }
        } else {
            $categories = '[';
            $c = 0;
            foreach ($_categories as $category) {
                $c++;
                $categories .= '"' . $category['name'] . '",';
                if ($c == 5)
                    break;
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
        if (_PS_VERSION_ < '1.5')
            return Tools::getShopDomainSsl(true, true) . _MODULE_DIR_ . $module . '/' . $controller . '.php';
        else
            return Context::getContext()->link->getModuleLink($module, $controller);
    }

    private function __setConfigDefault() {

        $this->context->smarty->assign('PIWIK_USE_PROXY', (bool) Configuration::get('PIWIK_USE_PROXY'));

        //* using proxy script?
        if ((bool) Configuration::get('PIWIK_USE_PROXY'))
            $this->context->smarty->assign('PIWIK_HOST', Configuration::get('PIWIK_PROXY_SCRIPT'));
        else
            $this->context->smarty->assign('PIWIK_HOST', Configuration::get('PIWIK_HOST'));

        $this->context->smarty->assign('PIWIK_SITEID', Configuration::get('PIWIK_SITEID'));

        $pkvct = (int) Configuration::get('PIWIK_COOKIE_TIMEOUT'); /* no iset if the same as default */
        if ($pkvct != 0 && $pkvct !== FALSE && ($pkvct != (int) (self::PK_VC_TIMEOUT * 60))) {
            $this->context->smarty->assign('PIWIK_COOKIE_TIMEOUT', $pkvct);
        }
        unset($pkvct);

        $pkrct = (int) Configuration::get('PIWIK_RCOOKIE_TIMEOUT'); /* no iset if the same as default */
        if ($pkrct != 0 && $pkrct !== FALSE && ($pkrct != (int) (self::PK_RC_TIMEOUT * 60))) {
            $this->context->smarty->assign('PIWIK_RCOOKIE_TIMEOUT', $pkrct);
        }
        unset($pkrct);

        $pksct = (int) Configuration::get('PIWIK_SESSION_TIMEOUT'); /* no iset if the same as default */
        if ($pksct != 0 && $pksct !== FALSE && ($pksct != (int) (self::PK_SC_TIMEOUT * 60))) {
            $this->context->smarty->assign('PIWIK_SESSION_TIMEOUT', $pksct);
        }
        unset($pksct);

        $this->context->smarty->assign('PIWIK_EXHTML', Configuration::get('PIWIK_EXHTML'));

        $PIWIK_COOKIE_DOMAIN = Configuration::get('PIWIK_COOKIE_DOMAIN');
        $this->context->smarty->assign('PIWIK_COOKIE_DOMAIN', (empty($PIWIK_COOKIE_DOMAIN) ? FALSE : $PIWIK_COOKIE_DOMAIN));

        $PIWIK_SET_DOMAINS = Configuration::get('PIWIK_SET_DOMAINS');
        if (!empty($PIWIK_SET_DOMAINS)) {
            $sdArr = explode(' ', Configuration::get('PIWIK_SET_DOMAINS'));
            if (count($sdArr) > 1)
                $PIWIK_SET_DOMAINS = "['" . trim(implode("','", $sdArr), ",'") . "']";
            else
                $PIWIK_SET_DOMAINS = "'{$sdArr[0]}'";
            $this->context->smarty->assign('PIWIK_SET_DOMAINS', (!empty($PIWIK_SET_DOMAINS) ? $PIWIK_SET_DOMAINS : FALSE));
            unset($sdArr);
        }else {
            $this->context->smarty->assign('PIWIK_SET_DOMAINS', FALSE);
        }
        unset($PIWIK_SET_DOMAINS);

        if ((bool) Configuration::get('PIWIK_DNT')) {
            $this->context->smarty->assign('PIWIK_DNT', "_paq.push([\"setDoNotTrack\", true]);");
        }

        if (_PS_VERSION_ < '1.5' && $this->context->cookie->isLogged()) {
            $this->context->smarty->assign('PIWIK_UUID', $this->context->cookie->id_customer);
        } else if ($this->context->customer->isLogged()) {
            $this->context->smarty->assign('PIWIK_UUID', $this->context->customer->id);
        }
    }

    private function __setCurrencies() {
        $this->default_currency = array('value' => 0, 'label' => $this->l('Choose currency'));
        if (empty($this->currencies)) {
            foreach (Currency::getCurrencies() as $key => $val) {
                $this->currencies[$key] = array(
                    'iso_code' => $val['iso_code'],
                    'name' => "{$val['name']} {$val['iso_code']}",
                );
            }
        }
    }

    private function getConfigFields($form = FALSE) {
        $fields = array(
            'PIWIK_USE_PROXY', 'PIWIK_HOST',
            'PIWIK_SITEID', 'PIWIK_TOKEN_AUTH',
            'PIWIK_COOKIE_TIMEOUT', 'PIWIK_SESSION_TIMEOUT',
            'PIWIK_DEFAULT_CURRENCY', 'PIWIK_CRHTTPS',
            'PIWIK_PRODID_V1', 'PIWIK_PRODID_V2',
            'PIWIK_PRODID_V3', 'PIWIK_COOKIE_DOMAIN',
            'PIWIK_SET_DOMAINS', 'PIWIK_DNT', 'PIWIK_EXHTML',
            'PIWIK_RCOOKIE_TIMEOUT',
        );
        $defaults = array(
            0, "", 0, "", self::PK_VC_TIMEOUT, self::PK_SC_TIMEOUT, 'EUR', 0,
            '{ID}-{ATTRID}#{REFERENCE}', '{ID}#{REFERENCE}',
            '{ID}#{ATTRID}', Tools::getShopDomain(), '', 0,
            '', self::PK_RC_TIMEOUT,
        );
        $ret = array();
        if ($form)
            foreach ($fields as $key => $value)
                $ret[$value] = Configuration::get($value);
        else
            foreach ($fields as $key => $value)
                $ret[$value] = $defaults[$key];


        return $ret;
    }

    /* INSTALL / UNINSTALL */

    /**
     * Install the module
     * @return boolean false on install error
     */
    public function install() {

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
            if ($tab->add())
                Configuration::updateValue('PIWIK_TAPID', (int) $tab->id);
        }

        /* default values */
        foreach ($this->getConfigFields(FALSE) as $key => $value) {
            Configuration::updateValue($key, $value);
        }
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
            foreach ($this->getConfigFields(FALSE) as $key => $value) {
                Configuration::deleteByName($key);
            }
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
