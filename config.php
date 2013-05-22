<?php

// APP CONSTANTS
define('SHOWCASEIDX_QUERY_VAR_SEARCH',              'ShowcaseIDX');
define('SHOWCASEIDX_SEARCH_DEFAULT_URL_NAMESPACE',  'properties' );

define('SHOWCASEIDX_QUERY_VAR_SEO_TITLE',           'ShowcaseSeoTitle');
define('SHOWCASEIDX_QUERY_VAR_SEO_KEYWORDS',        'ShowcaseSeoKeywords');
define('SHOWCASEIDX_QUERY_VAR_SEO_DESCRIPTION',     'ShowcaseSeoDescription');
define('SHOWCASEIDX_QUERY_VAR_COMMUNITY',           'CommunityId');
define('SHOWCASEIDX_QUERY_VAR_LISTINGS',            'Listings'   );
define('SHOWCASEIDX_QUERY_VAR_LISTING',             'ListingId'  );

// Plugin Bootstrap / hook installation
function showcaseidx_plugin_setup() {
    // defaults for our options
    add_option('showcaseidx_api_host',                  '');
    add_option('showcaseidx_api_key',                   '');
    add_option('showcaseidx_template',                  '');
    add_option('showcaseidx_cache_version',             date('r'));
    add_option('showcaseidx_url_namespace',             SHOWCASEIDX_SEARCH_DEFAULT_URL_NAMESPACE);

    // enable widgets to run short codes
	add_filter('widget_text', 'do_shortcode', 11);

    // register all of our shortcodes
    add_shortcode('showcaseidx',                'showcaseidx_show_app');
    add_shortcode('showcaseidx_hotsheet',       'showcaseidx_show_hotsheet');
    add_shortcode('showcaseidx_widget_230',     'showcaseidx_widget_230');
    add_shortcode('showcaseidx_widget_465',     'showcaseidx_widget_465');
    add_shortcode('showcaseidx_widget_700',     'showcaseidx_widget_700');
    add_shortcode('showcaseidx_widget_930',     'showcaseidx_widget_930');

    // install routing
    add_action('init',                 'showcaseidx_install_routing');
    add_action('template_redirect',    'showcaseidx_router');

    // admin hooks
    add_action('admin_menu', 'showcaseidx_create_menu_page');
    add_action('admin_init', 'register_mysettings');
}

function showcaseidx_get_host() {
    $host = get_option('showcaseidx_api_host');
    if (!$host)
    {
        $host = 'idx.showcaseidx.com';
    }
    return $host;
}

function showcaseidx_get_prefix() {
    $prefix = get_option('showcaseidx_url_namespace');
    if (empty($prefix))
    {
        $prefix = SHOWCASEIDX_SEARCH_DEFAULT_URL_NAMESPACE;
    }
    return $prefix;
}
