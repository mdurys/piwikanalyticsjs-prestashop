<script type="text/javascript">
        {if $PIWIK_USE_PROXY eq true}
            {literal}var u= "{/literal}{$PIWIK_HOST}{literal}";{/literal}
        {else}
            {literal}var u= "//{/literal}{$PIWIK_HOST}{literal}" : "//{/literal}{$PIWIK_HOST}{literal}");{/literal}
        {/if}{literal}
        var _paq = _paq || [];
        
        _paq.push(["setSiteId", {/literal}{$PIWIK_SITEID}{literal}]);{/literal}
        {if $PIWIK_USE_PROXY eq true}
            {literal}_paq.push(["setTrackerUrl",u]);{/literal}
        {else}
            {literal}_paq.push(["setTrackerUrl", u+'piwik.php']);{/literal}
        {/if}
        {literal}
        _paq.push(["setCookieDomain", "{/literal}{$PIWIK_COOKIE_DOMAIN}{literal}"]);
        _paq.push(['setDomains', "{/literal}{$PIWIK_DOMAINS}{literal}"]);
        _paq.push(['setVisitorCookieTimeout', '{/literal}{$PIWIK_COOKIE_TIMEOUT}{literal}']);
        _paq.push(['setSessionCookieTimeout', '{/literal}{$PIWIK_SESSION_TIMEOUT}{literal}']);
        _paq.push(['enableLinkTracking']);
        {/literal}
    {if isset($PIWIK_PRODUCTS) && is_array($PIWIK_PRODUCTS)}
        {foreach from=$PIWIK_PRODUCTS item=piwikproduct}
            {literal}
                    _paq.push(['setEcommerceView', '{/literal}{$piwikproduct.SKU}{literal}', '{/literal}{$piwikproduct.NAME|escape:'htmlall':'UTF-8'}{literal}', {/literal}{$piwikproduct.CATEGORY}{literal}, '{/literal}{$piwikproduct.PRICE|floatval}{literal}']);
            {/literal}
        {/foreach}
    {/if}
    {if isset($piwik_category) && is_array($piwik_category)}{literal}
            _paq.push(['setEcommerceView', false, false, '{/literal}{$piwik_category.NAME|escape:'htmlall':'UTF-8'}{literal}']);{/literal}
    {/if}
    {if $PIWIK_CART eq true}
        {if is_array($PIWIK_CART_PRODUCTS)}
            {foreach from=$PIWIK_CART_PRODUCTS item=_product}
                _paq.push(['addEcommerceItem', '{$_product.SKU}', '{$_product.NAME}', {$_product.CATEGORY}, '{$_product.PRICE}', '{$_product.QUANTITY}']);
            {/foreach}
        {/if}
        {if isset($PIWIK_CART_TOTAL)}
            _paq.push(['trackEcommerceCartUpdate', {$PIWIK_CART_TOTAL|floatval}]);
        {/if}
    {/if}
    {if $PIWIK_ORDER eq true}
        {if is_array($PIWIK_ORDER_PRODUCTS)}
            {foreach from=$PIWIK_ORDER_PRODUCTS item=_product}
                _paq.push(['addEcommerceItem', '{$_product.SKU}', '{$_product.NAME}', {$_product.CATEGORY}, '{$_product.PRICE}', '{$_product.QUANTITY}']);
            {/foreach}
        {/if}
        _paq.push(['trackEcommerceOrder',"{$PIWIK_ORDER_DETAILS.order_id}", '{$PIWIK_ORDER_DETAILS.order_total}', '{$PIWIK_ORDER_DETAILS.order_sub_total}', '{$PIWIK_ORDER_DETAILS.order_tax}', '{$PIWIK_ORDER_DETAILS.order_shipping}', '{$PIWIK_ORDER_DETAILS.order_discount}']);
    {/if}
    {if isset($PIWIK_SITE_SEARCH)}
        {$PIWIK_SITE_SEARCH}
    {else}
        {literal}_paq.push(['trackPageView']);{/literal}
    {/if}
    {literal}
        (function() {var d = document, g = d.createElement("script"), s = d.getElementsByTagName("script")[0];g.type = "text/javascript";g.defer = true;g.async = true;g.src = {/literal}{if $PIWIK_USE_PROXY eq true}{literal}u{/literal}{else}{literal}u+'piwik.js'{/literal}{/if}{literal};s.parentNode.insertBefore(g, s);})();
    {/literal}
</script>
{* let's help get those harvesters and other, comment spammers see http://projecthoneypot.org/ *}
<a href="//cmjscripter.net/independence.php" style="display:none;">Most sold products</a>