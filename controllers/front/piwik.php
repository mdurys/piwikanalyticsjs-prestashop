<?php

if (!defined('_PS_VERSION_'))
    exit;

class PiwikAnalyticsJSPiwikModuleFrontController extends ModuleFrontController {

    public function __construct() {

        $context = Context::getContext();


        $PIWIK_URL = ((bool) Configuration::get('PIWIK_CRHTTPS') ? 'https://' : 'http://') . Configuration::get('PIWIK_HOST');
        $TOKEN_AUTH = Configuration::get('PIWIK_TOKEN_AUTH');
        $SITE_ID = Configuration::get('PIWIK_SITEID');
        $timeout = 5;

        /*
         * ?fc=module&module=piwikanalytics&controller=piwik
         * MULTI Lanugage shop
         * ?fc=module&module=piwikanalytics&controller=piwik&id_lang=2&isolang=da
         */

        // 1) PIWIK.JS PROXY: No _GET parameter, we serve the JS file
        if (
                (count($_GET) == 3 && Tools::getIsset('module') && Tools::getIsset('controller') && Tools::getIsset('fc')) ||
                (count($_GET) == 5 && Tools::getIsset('module') && Tools::getIsset('controller') && Tools::getIsset('fc') && Tools::getIsset('id_lang') && Tools::getIsset('isolang'))
        ) {
            $modifiedSince = false;
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $modifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
                // strip any trailing data appended to header
                if (false !== ($semicolon = strpos($modifiedSince, ';'))) {
                    $modifiedSince = strtotime(substr($modifiedSince, 0, $semicolon));
                }
            }
            // Re-download the piwik.js once a day maximum
            $lastModified = time() - 86400;

            // set HTTP response headers
            header('Vary: Accept-Encoding');

            // Returns 304 if not modified since
            if (!empty($modifiedSince) && $modifiedSince < $lastModified) {
                header(sprintf("%s 304 Not Modified", $_SERVER['SERVER_PROTOCOL']));
            } else {
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                @header('Content-Type: application/javascript; charset=UTF-8');
                if ($piwikJs = file_get_contents($PIWIK_URL . 'piwik.js')) {
                    die($piwikJs);
                } else {
                    header($_SERVER['SERVER_PROTOCOL'] . '505 Internal server error');
                }
            }
            exit;
        }
        // 2) PIWIK.PHP PROXY: GET parameters found, this is a tracking request, we redirect it to Piwik
        @ini_set('magic_quotes_runtime', 0);

        $url = sprintf("%spiwik.php?cip=%s&token_auth=%s&", $PIWIK_URL, $this->getVisitIp(), $TOKEN_AUTH);

        foreach ($_GET as $key => $value) {
            $url .= $key . '=' . urlencode($value) . '&';
        }
        header("Content-Type: image/gif");
        $stream_options = array('http' => array(
                'user_agent' => @$_SERVER['HTTP_USER_AGENT'],
                'header' => sprintf("Accept-Language: %s\r\n", @str_replace(array("\n", "\t", "\r"), "", $_SERVER['HTTP_ACCEPT_LANGUAGE'])),
                'timeout' => $timeout
        ));
        $ctx = stream_context_create($stream_options);
        echo file_get_contents($url, 0, $ctx);
        exit;
    }

    private function getVisitIp() {
        $matchIp = '/^([0-9]{1,3}\.){3}[0-9]{1,3}$/';
        $ipKeys = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_CF_CONNECTING_IP',
        );
        foreach ($ipKeys as $ipKey) {
            if (isset($_SERVER[$ipKey]) && preg_match($matchIp, $_SERVER[$ipKey])) {
                return $_SERVER[$ipKey];
            }
        }
        return @$_SERVER['REMOTE_ADDR'];
    }

}
