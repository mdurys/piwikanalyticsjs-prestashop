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

if (!defined('_PS_VERSION_'))
    exit;

/**
 * Description of PiwikAnalytics
 *
 * @author Christian
 */
class PiwikAnalyticsController extends ModuleAdminController {

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

        $this->initTabModuleList();
        $this->addToolBarModulesListButton();
        $this->toolbar_title = $this->l('Stats', 'PiwikAnalytics');

        if (_PS_VERSION_ < '1.6')
            $this->bootstrap = false;
        else
            $this->initPageHeaderToolbar();
        if ($this->display == 'view') {

            // Some controllers use the view action without an object
            if ($this->className)
                $this->loadObject(true);


            $PIWIK_HOST = Configuration::get('PIWIK_HOST');
            $PIWIK_SITEID = (int) Configuration::get('PIWIK_SITEID');
            $PIWIK_TOKEN_AUTH = Configuration::get('PIWIK_TOKEN_AUTH');
            if ((empty($PIWIK_HOST) || $PIWIK_HOST === FALSE) ||
                    ($PIWIK_SITEID <= 0 || $PIWIK_SITEID === FALSE) ||
                    (empty($PIWIK_TOKEN_AUTH) || $PIWIK_TOKEN_AUTH === FALSE)) {

                $this->content .= "<h3 style=\"padding: 90px;\">{$this->l("You need to set 'Piwik host url', 'Piwik token auth' and 'Piwik site id', and save them before the dashboard can be shown here")}</h3>";
            } else {
                $this->content .= <<< EOF
<script type="text/javascript">
  function WidgetizeiframeDashboardLoaded() {
      var w = $('#content').width();
      var h = $('body').height();
      $('#WidgetizeiframeDashboard').width('100%');
      $('#WidgetizeiframeDashboard').height(h);
  }
</script>   
EOF;
                $lng = new LanguageCore($this->context->cookie->id_lang);
                $this->content .= ''
                        . '<iframe id="WidgetizeiframeDashboard"  onload="WidgetizeiframeDashboardLoaded();" '
                        . 'src="' . ((bool) Configuration::get('PIWIK_CRHTTPS') ? 'https://' : 'http://')
                        . $PIWIK_HOST . 'index.php'
                        . '?module=Widgetize'
                        . '&action=iframe'
                        . '&moduleToWidgetize=Dashboard'
                        . '&actionToWidgetize=index'
                        . '&idSite=' . $PIWIK_SITEID
                        . '&period=day'
                        . '&token_auth=' . $PIWIK_TOKEN_AUTH
                        . '&language=' . $lng->iso_code
                        . '&date=today" frameborder="0" marginheight="0" marginwidth="0" width="100%" height="550px"></iframe>';
            }
        }
        $this->context->smarty->assign('help_link', 'https://github.com/cmjnisse/piwikanalyticsjs-prestashop');

        $this->context->smarty->assign(array(
            'content' => $this->content,
            'show_page_header_toolbar' => $this->show_page_header_toolbar,
            'page_header_toolbar_title' => $this->page_header_toolbar_title,
            'page_header_toolbar_btn' => $this->page_header_toolbar_btn
        ));
    }

}
