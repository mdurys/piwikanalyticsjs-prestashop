<?php

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
            $this->content .= ''
                    . '<iframe id="WidgetizeiframeDashboard"  onload="WidgetizeiframeDashboardLoaded();" src="http://'
                    . Configuration::get('PIWIK_HOST') . 'index.php'
                    . '?module=Widgetize'
                    . '&action=iframe'
                    . '&moduleToWidgetize=Dashboard'
                    . '&actionToWidgetize=index'
                    . '&idSite=' . (int) Configuration::get('PIWIK_SITEID')
                    . '&period=day'
                    . '&token_auth=' . Configuration::get('PIWIK_TOKEN_AUTH')
                    . '&date=today" frameborder="0" marginheight="0" marginwidth="0" width="100%" height="550px"></iframe>';
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
