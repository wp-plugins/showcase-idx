<?php
/*
Plugin Name: Showcase IDX
Plugin URI: http://showcaseidx.com/
Description: Interactive, map-centric real-estate property search.
Author: Kanwei Li
Version: 1.3
Author URI: http://showcaseidx.com/
*/

/*  Copyright 2012 Kanwei Li (email : kanwei@showcaseidx.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/***************************************************************************
 * URL Structure
 *
 * This plugin maps an entire URL namespace for its own purposes.
 *
 * The default value is /properties but this can be customized.
 *
 * There are 3 forms accepted by the URL namespace:
 * - /properties/                                   => SEARCH
 * - /properties/SEO…SEO/[REGION_ID]                => /properties/GA/Atlanta/12345     => "region" 12345
 * - /properties/SEO…SEO/[MLS_SERVICE_MLS_NUMBER]   => /properties/2_123456             => FMLS #123456
 *
 * NOTES:
 *   - The use of ID's in each URL ensures that we can always pull up the exact item of interest regardless of the "SEO-friendly" url info
 *   - We use the ID at the end (right) as these are slightly preferable for SEO (higher LTR visibility)
 *   - In a perfect world we wouldn't need ID's, but that's really tricky due to disintermediation issues
 *   - Fortunately not too many peopel are likely to "semantically browse" our app via URL structure
 *   - In the future, semantic URL support could be accomplished thru an API in the future => guessIdFor(/GA/Atlanta/Virginia-Highlands) => /SEO...SEO/ID12345
 *     and we could have disintermediation pages as needed.
 *
 ***************************************************************************/

global $wp_version;

require_once(ABSPATH . "wp-admin/includes/plugin.php");
require_once(dirname(__FILE__) . "/config.php");
require_once(dirname(__FILE__) . "/widgets.php");
require_once(dirname(__FILE__) . "/admin.php");

// install our plugin
add_action('plugins_loaded', 'showcaseidx_plugin_setup');
register_activation_hook(__FILE__, 'showcaseidx_notify_hq_of_activation');

function showcaseidx_seo_listing_url_regex_callback($matches)
{
    $baseUrl = showcaseidx_base_url();
    list($full, $appUrl, $title) = $matches;
    $titleUrlEncoded = urlencode($title);
    return "<a href=\"{$baseUrl}/{$titleUrlEncoded}/{$appUrl}\" title=\"{$title}\"";
}
function showcaseidx_router()
{
    global $wp_query;

    $templateName = get_option('showcaseidx_template');

    if ( array_key_exists(SHOWCASEIDX_QUERY_VAR_SEARCH, $wp_query->query_vars ) ) {
        $seoPlaceholder = '<a href="' . showcaseidx_base_url() . '/all">View all listings</a>'; //'http://idx.showcaseidx.com/sitemap/8');
        $content = showcaseidx_show_app($seoPlaceholder);
        showcaseidx_display_templated($content);
    }

    if (array_key_exists(SHOWCASEIDX_QUERY_VAR_LISTINGS, $wp_query->query_vars)) {
        $apiKey = get_option('showcaseidx_api_key');
        $content = showcaseidx_cachable_fetch("http://idx.showcaseidx.com/sitemap/{$apiKey}");
        $content = preg_replace_callback('/<a href="#\/listings\/([^"]+)" title="([^"]+)"/', 'showcaseidx_seo_listing_url_regex_callback', $content);
        showcaseidx_display_templated($content);
    }

    if (array_key_exists(SHOWCASEIDX_QUERY_VAR_LISTING, $wp_query->query_vars)) {
        $ListingId = trim($_REQUEST[SHOWCASEIDX_QUERY_VAR_LISTING], ' /');
        $defaultAppUrl = "/listings/{$ListingId}";

        $seoPlaceholder = showcaseidx_cachable_fetch("http://idx.showcaseidx.com/seo_listing/{$ListingId}");
        $content = showcaseidx_show_app($seoPlaceholder, $defaultAppUrl);
        showcaseidx_display_templated($content);
    }

    // temporarily disabled pending community-by-id data refector
    if (0 && array_key_exists(SHOWCASEIDX_QUERY_VAR_COMMUNITIES, $wp_query->query_vars)) {
        $CommunityId = trim($_REQUEST[SHOWCASEIDX_QUERY_VAR_COMMUNITIES], ' /');
        $defaultAppUrl = "/browse/{$CommunityId}";

        $seoPlaceholder = showcaseidx_cachable_fetch("http://idx.showcaseidx.com/seo_community/{$CommunityId}");
        $content = showcaseidx_show_app($seoPlaceholder, $defaultAppUrl);
        showcaseidx_display_templated($content);
    }
}

function showcaseidx_install_routing() {
    global $wp_rewrite;

    // in order to get any custom rewrite rules inserted into .htaccess, have to set use_verbose_rules=true for some reason
    // seems like a hideous bug but hey what can I do
    $wp_rewrite->use_verbose_rules = true;

    // map LISTINGS page (seo list for all listings)
    add_rewrite_rule(
        showcaseidx_get_prefix() . '/all/?$',
        'index.php?' . SHOWCASEIDX_QUERY_VAR_LISTINGS,
        'top'
    );
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_LISTINGS . '%', '([^&]+)');
    
    // map LISTING pages
    add_rewrite_rule(
        showcaseidx_get_prefix() . '/?.*/([0-9]+_[0-9]+)/?$',
        'index.php?' . SHOWCASEIDX_QUERY_VAR_LISTING . '=$1',
        'top'
    );
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_LISTING . '%', '([^&]+)');
    
    // map COMMUNITY pages
    add_rewrite_rule(
        showcaseidx_get_prefix() . '/?.*/([0-9]+)/?$',
        'index.php?' . SHOWCASEIDX_QUERY_VAR_COMMUNITIES . '=$1',
        'top'
    );
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_COMMUNITIES . '%', '([^&]+)');
    
    // map our widget/form response handler
    add_rewrite_rule(
        showcaseidx_get_prefix() . '/?(.*)$',
        'index.php?' . SHOWCASEIDX_QUERY_VAR_SEARCH . '&$1',
        'top'
    );
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_SEARCH . '%', '([^&]+)');

//    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_BROWSE_BY_REGION . '%', '([^&]+)');
//    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_BROWSE_BY_REGION_ID . '%', '([^&]+)');
//    add_permastruct('browse-properties', SHOWCASEIDX_BROWSE_DEFAULT_URL_NAMESPACE . '/%' . SHOWCASEIDX_QUERY_VAR_BROWSE_BY_REGION . '%/%' . SHOWCASEIDX_QUERY_VAR_BROWSE_BY_REGION_ID . '%', array('with_front' => false, 'feed' => false, 'paged' => false));
}

function showcaseidx_bust_cache()
{
    update_option('showcaseidx_cache_version', date('r'));
}

function showcaseidx_install_rewrite_rules()
{
    showcaseidx_install_routing();
    flush_rewrite_rules();
}

function showcaseidx_base_url()
{
    // should detect if mod_rewrite works and if NOT do something like this...
    // return 'index.php?' . SHOWCASEIDX_QUERY_VAR_SEARCH;
    return get_settings('home') . '/' . showcaseidx_get_prefix();
}

function showcaseidx_cachable_fetch($seoContentURL)
{
    // cachably fetch content, with cache-busting (happens every time the admin is SAVED)
    $transient_id = 'showcaseidx-'.md5($seoContentURL . get_option('showcaseidx_cache_version'));   // max 45 chars!!! -- this is 44 *always*
    if (($seoContent = get_transient($transient_id)) === false) {
        $seoContent = "View all listings";

        // this code runs when there is no valid transient set
        $resp = wp_remote_get($seoContentURL);
        if ($resp instanceof WP_Error or wp_remote_retrieve_response_code($resp) != 200)
        {
            $seoContent = 'SEO PROXY ERROR, ' . print_r($resp, true);
        }
        else
        {
            $seoContent = wp_remote_retrieve_body($resp);
            if ($seoContent)
            {
                $ok = set_transient($transient_id, $seoContent, 1 * DAY_IN_SECONDS);
                if (!$ok)
                {
                    // do something....
                }
            }
        }
    }

    //$seoContent = substr($seoContent, 0, 1000); // for testing
    return $seoContent;
}

function showcaseidx_notify_hq_of_activation()
{
    $blogInfo = array();
    foreach (array('admin_email', 'url', 'version') as $key) {
        $blogInfo[$key] = get_bloginfo($key);
    }

    $blogInfo['__cc_email'] = 'scott@showcasere.com';
    $blogInfo['__subject'] = 'Wordpress Activation!';

    $queryString = '';
    foreach ($blogInfo as $k => $v) {
        if ($queryString) $queryString .= "&";
        $v = urlencode($v);
        $queryString .= "{$k}={$v}";
    }
    $pingUrl = "http://showcasere.com/4/formmail.php?{$queryString}";

    wp_remote_get($pingUrl);
}
