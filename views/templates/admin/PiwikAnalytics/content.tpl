<div class="col col1">
    <div id="widgetIframe">
        <iframe 
            width="100%" 
            height="240" 
            src="{$PIWIK_HOST}?module=Widgetize&action=iframe&columns[]=nb_visits&widget=1&moduleToWidgetize=VisitsSummary&actionToWidgetize=getEvolutionGraph&idSite={$PIWIK_SITEID}&period=day&date=today&disableLink=1&widget=1&token_auth={$PIWIK_TOKEN_AUTH}&language={$LANGUAGE}" 
            scrolling="no" 
            frameborder="0"
            marginheight="0"
            marginwidth="0"></iframe>
    </div>
    <div id="widgetIframe">
        <iframe
            width="100%"
            height="2000"
            src="{$PIWIK_HOST}?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Live&actionToWidgetize=widget&idSite={$PIWIK_SITEID}&period=day&date=today&disableLink=1&widget=1&token_auth={$PIWIK_TOKEN_AUTH}&language={$LANGUAGE}"
            scrolling="no"
            frameborder="0"
            marginheight="0"
            marginwidth="0"></iframe>
    </div>
</div>
<div class="col col2">
    <div id="widgetIframe">
        <iframe 
            width="100%" 
            height="240" 
            src="{$PIWIK_HOST}?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Live&actionToWidgetize=getSimpleLastVisitCount&idSite={$PIWIK_SITEID}&period=day&date=today&disableLink=1&widget=1&token_auth={$PIWIK_TOKEN_AUTH}&language={$LANGUAGE}"
            scrolling="no"
            frameborder="0"
            marginheight="0"
            marginwidth="0"></iframe>
    </div>
    <div id="widgetIframe">
        <iframe
            width="100%"
            height="900"
            src="{$PIWIK_HOST}?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=VisitsSummary&actionToWidgetize=index&idSite={$PIWIK_SITEID}&period=day&date=today&disableLink=1&widget=1&token_auth={$PIWIK_TOKEN_AUTH}&language={$LANGUAGE}"
            scrolling="no"
            frameborder="0"
            marginheight="0"
            marginwidth="0"></iframe>
    </div>
</div>
<div class="col col3">
    <div id="widgetIframe">
        <iframe
            width="100%"
            height="240" 
            src="{$PIWIK_HOST}?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Referrers&actionToWidgetize=getWebsites&idSite={$PIWIK_SITEID}&period=day&date=today&disableLink=1&widget=1&token_auth={$PIWIK_TOKEN_AUTH}&language={$LANGUAGE}"
            scrolling="no"
            frameborder="0"
            marginheight="0"
            marginwidth="0"></iframe>
    </div>
    <div id="widgetIframe">
        <iframe
            width="100%"
            height="900"
            src="{$PIWIK_HOST}?module=Widgetize&action=iframe&idGoal=ecommerceOrder&widget=1&moduleToWidgetize=Goals&actionToWidgetize=widgetGoalReport&idSite={$PIWIK_SITEID}&period=day&date=today&disableLink=1&widget=1&token_auth={$PIWIK_TOKEN_AUTH}&language={$LANGUAGE}"
            scrolling="no"
            frameborder="0"
            marginheight="0"
            marginwidth="0"></iframe>
    </div>
</div>