<?php

// curried shortcode generators
function showcaseidx_widget_230() { return showcaseidx_generate_widget('widgets230'); }
function showcaseidx_widget_465() { return showcaseidx_generate_widget('widgets465'); }
function showcaseidx_widget_700() { return showcaseidx_generate_widget('widgets700'); }
function showcaseidx_widget_930() { return showcaseidx_generate_widget('widgets930'); }
function showcaseidx_generate_widget($type)
{
    $config = showcaseidx_generate_config();
    $host   = showcaseidx_get_host();

    $widgetUrl = "http://idx.showcaseidx.com/{$type}";
    $widget = showcaseidx_cachable_fetch($widgetUrl);

    $searchHostPage = showcaseidx_base_url();
    $widget = str_replace('action_url', $searchHostPage, $widget);

    return <<<EOT
        {$config}
        <link href="http://$host/css/widgets.css" media="screen, projection" rel="stylesheet" type="text/css" />
        <script src="http://$host/js/mydx2.js"></script>
        {$widget}
EOT;
}

function showcaseidx_show_app($seoPlaceholder = NULL, $defaultAppUrl = NULL) {
    $host = showcaseidx_get_host();
    $config = showcaseidx_generate_config();
    $defaultAppUrl = $defaultAppUrl ? showcaseidx_generate_default_app_url($defaultAppUrl) : NULL;

    return <<<EOT
        {$config}
        <link href="http://$host/css/screen.css" media="screen, projection" rel="stylesheet" type="text/css" />
        <link href='http://fonts.googleapis.com/css?family=Pontano+Sans&subset=latin' rel='stylesheet' type='text/css'>
        <link href='http://fonts.googleapis.com/css?family=Bitter:400,700&subset=latin' rel='stylesheet' type='text/css'>
        <link href='http://fonts.googleapis.com/css?family=Francois+One&subset=latin' rel='stylesheet' type='text/css'>

        {$defaultAppUrl}
        <div id="mydx-container" ng-controller="AppController" ng-app="mydx2">
            <div ng-include="'http://$host/templates/layout.html'"></div>
            <script src="http://$host/js/mydx2.js"></script>
            <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDfITsP9KWNM61m1eT_8rsov2QoK932LCY&sensor=false"></script>
            <script type="text/javascript" src="http://s7.addthis.com/js/300/addthis_widget.js#pubid=ra-50a2dde218aceee1"></script>
            <div class="mydx2-hide">
                {$seoPlaceholder}
            </div>
            <footer>
                <p><a target=_blank href="http://showcaseidx.com"><img src="http://idx.showcaseidx.com/images/poweredshowcase.png" /></a></p>
            </footer>
        </div>
EOT;
}

/*************** HELPER FUNCTIONS FOR SHORTCODE GENERATORS **********************/
function showcaseidx_generate_config() {
    $WEBSITE_ROOT = showcaseidx_get_host();
    $WEBSITE_ID = get_option('showcaseidx_api_key');
    $CONF = 'null';
    if (isset($_REQUEST['json']))
    {
        $CONF = stripslashes($_REQUEST['json']);
    }

    return <<<EOT
<script type="text/javascript">
var SHOWCASE_CONF = {
    WEBSITE_ROOT: "http://{$WEBSITE_ROOT}",
    WEBSITE_ID: "{$WEBSITE_ID}",
    SEARCH_CONF: {$CONF}
};
</script>
EOT;
}

function showcaseidx_display_templated($content)
{
    // select template....
    echo get_header($templateName);
    echo $content;
    echo get_footer($templateName);
    exit;
}

function showcaseidx_generate_default_app_url($url)
{
    return "<script>window.location.href = '#{$url}';</script>";    // @todo there's probably a better way to do this
}
