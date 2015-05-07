<?php

// SHORTCODE HANDLERS
// curried shortcode generators
function showcaseidx_widget_login() { return showcaseidx_generate_app(null, "/login"); }
function showcaseidx_widget_register() { return showcaseidx_generate_app(null, "/register"); }
function showcaseidx_widget_230() { return showcaseidx_generate_widget('widgets230'); }
function showcaseidx_widget_465() { return showcaseidx_generate_widget('widgets465'); }
function showcaseidx_widget_700() { return showcaseidx_generate_widget('widgets700'); }
function showcaseidx_widget_930() { return showcaseidx_generate_widget('widgets930'); }
function showcaseidx_widget_omnibox() { return showcaseidx_generate_widget('widgetsomnibox'); }
function showcaseidx_widget_updated() { return showcaseidx_generate_widget('widgetsupdated'); }
function showcaseidx_generate_widget($type)
{
    $config = showcaseidx_generate_config();
    $cdn_host    = get_option('showcaseidx_cdn_host');

    $widgetUrl = "$cdn_host/{$type}";
    $widget = showcaseidx_cachable_fetch($widgetUrl);

    $searchHostPage = showcaseidx_base_url() . '/';
    $widget = str_replace('action_url', $searchHostPage, $widget);
    return <<<EOT
        {$config}
        {$widget}
EOT;
}

function showcaseidx_show_app() {
    $seoPlaceholder = '<noscript><a href="' . showcaseidx_base_url() . '/all">View all listings</a></noscript>';
    return showcaseidx_generate_app($seoPlaceholder);
}

function showcaseidx_show_hotsheet($scParams) {
    $shortcodeAttrs = shortcode_atts(array(
        'type' => 'custom',                 // custom, agent, office
        'name' => '',                       // name of hotsheet; only referenced for type=custom
        'hide_map' => false,
        'hide_search' => false,
    ), $scParams);
    $jsonEncoded = json_encode(array('hotsheet' => $shortcodeAttrs));

    // Get SEO listings for custom hotsheets
    if (isset($scParams['name'])) {
        echo "<noscript>";
        echo showcaseidx_post(get_option('showcaseidx_api_v2_host') . "/seo/hotsheet_listings",
            array("hotsheet_name" => $scParams['name'], "api_key" => get_option('showcaseidx_api_key')));
        echo "</noscript>";
    }
    return showcaseidx_generate_app("{$shortcodeAttrs['type']} hotsheet", NULL, $jsonEncoded);
}

/*************** HELPER FUNCTIONS FOR SHORTCODE GENERATORS **********************/
function showcaseidx_generate_app($seoPlaceholder = NULL, $defaultAppUrl = NULL, $customSearchConfig = NULL) {
    if ($customSearchConfig === NULL)
    {
        $customSearchConfig = showcaseidx_get_custom_widget_config();
    }
    $config = showcaseidx_generate_config($customSearchConfig);
    $cdn_host = get_option('showcaseidx_cdn_host');
    $defaultAppUrl = $defaultAppUrl ? showcaseidx_generate_default_app_url($defaultAppUrl) : NULL;
    $widget = apply_filters('showcase_widget_content', showcaseidx_cachable_fetch("$cdn_host/wordpress_noscript"));
    return <<<EOT
        {$config}
        {$defaultAppUrl}
        <noscript>
            {$seoPlaceholder}
        </noscript>
        {$widget}
EOT;
}

function showcaseidx_generate_config($customSearchConfig = null) {
    $api_key = get_option('showcaseidx_api_key');
    $api_root = get_option('showcaseidx_api_v2_host');
    $cdn_root = get_option('showcaseidx_cdn_host');

    if ($customSearchConfig === NULL)
    {
        $customSearchConfig = 'null';
    }

    return <<<EOT
<div></div> <!-- Hack for some plugin removing script tags -->
<script type="text/javascript">
if (!SHOWCASE_CONF) {
    var SHOWCASE_CONF = {
        WEBSITE_ROOT: "{$api_root}",
        CDN_ROOT: "{$cdn_root}",
        WEBSITE_ID: "{$api_key}"
    };
}
if ($customSearchConfig) {
    SHOWCASE_CONF.SEARCH_CONF = $customSearchConfig;
}
</script>
EOT;
}

// grabs & sanitizes the "customSearchConfig" from the form data posted by widgets (see showcaseidx_generate_config)
function showcaseidx_get_custom_widget_config()
{
    $customConfig = NULL;
    if (isset($_REQUEST['json']))
    {
        $customConfig = stripslashes($_REQUEST['json']);
    }
    return $customConfig;
}

function showcaseidx_display_templated($content)
{
    $templateName = get_option('showcaseidx_template');
    // select template....
    echo get_header($templateName);
    echo '<div id="mydx-container">';
    echo $content;
    echo '</div>';
    echo get_footer($templateName);
    exit;
}

function showcaseidx_generate_default_app_url($url)
{
    return "<script>window.location.href = '#{$url}';</script>";    // @todo there's probably a better way to do this
}
