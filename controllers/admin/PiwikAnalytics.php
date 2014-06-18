<?php

if (!defined('_PS_VERSION_'))
    exit;

/**
 * Description of PiwikAnalytics
 *
 * @author Christian
 */
class PiwikAnalyticsController extends ModuleAdminController {

    private $tabs = array();
    private $tabs_content = array();

    public function init() {
        parent::init();

        $this->bootstrap = true;
        $this->action = 'view';
        $this->display = 'view';
        $this->show_page_header_toolbar = true;
        $this->tpl_folder = _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/PiwikAnalytics/';
    }

    public function initContent() {
        if ($this->ajax)
            return;
        $this->toolbar_title = $this->l('Stats', 'PiwikAnalytics');

        if (_PS_VERSION_ < '1.6')
            $this->bootstrap = false;
        else
            $this->initPageHeaderToolbar();
        if ($this->display == 'view') {

            // Some controllers use the view action without an object
            if ($this->className)
                $this->loadObject(true);


            $this->addCSS($this->module->getPathUri() . 'css/admin.css');
            $this->content .= $this->displayContent();
        }
        $this->context->smarty->assign('help_link', 'http://cmjscripter.net/public/2014/01/24/piwik-traking-prestashop/?_ps=' . Tools::getShopDomainSsl());

        $this->context->smarty->assign(array(
            'content' => $this->content,
            'show_page_header_toolbar' => $this->show_page_header_toolbar,
            'page_header_toolbar_title' => $this->page_header_toolbar_title,
            'page_header_toolbar_btn' => $this->page_header_toolbar_btn
        ));
    }

    public function displayContent() {
        $this->context->smarty->assign(array(
            'base_link' => $this->context->link->getAdminLink('PiwikAnalytics'),
            'PIWIK_HOST' => '//' . Configuration::get('PIWIK_HOST') . 'index.php',
            'PIWIK_TOKEN_AUTH' => Configuration::get('PIWIK_TOKEN_AUTH'),
            'PIWIK_SITEID' => (int) Configuration::get('PIWIK_SITEID'),
            'LANGUAGE' => $this->context->language->iso_code,
        ));
        $tpl = $this->createTemplate('content.tpl');
        return $tpl->fetch();
    }

    public function createTemplate($tpl_name) {
        if (file_exists(_PS_THEME_DIR_ . 'modules/' . $this->module->name . '/views/templates/admin/PiwikAnalytics/' . $tpl_name))
            return $this->context->smarty->createTemplate(_PS_THEME_DIR_ . 'modules/' . $this->module->name . '/views/templates/admin/PiwikAnalytics/' . $tpl_name, $this->context->smarty);
        else
            return $this->context->smarty->createTemplate(_PS_MODULE_DIR_ . '/' . $this->module->name . '/views/templates/admin/PiwikAnalytics/' . $tpl_name, $this->context->smarty);
    }

}
