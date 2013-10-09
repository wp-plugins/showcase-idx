<?php
/*
Plugin Name: Showcase IDX
Plugin URI: http://showcaseidx.com/
Description: Interactive, map-centric real-estate property search.
Author: Kanwei Li
Version: 2.1.2
Author URI: http://showcaseidx.com/
*/

/*  Copyright 2013 Kanwei Li (email : kanwei@showcaseidx.com)

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
register_activation_hook(__FILE__, 'showcaseidx_activation_hook');

register_activation_hook(__FILE__, 'showcaseidx_cachebust_activation');
add_action('showcaseidx_cachebust', 'showcaseidx_bust_cache');
register_deactivation_hook(__FILE__, 'showcaseidx_cachebust_deactivation'); 

function showcaseidx_cachebust_activation() {
    wp_schedule_event(time(), 'hourly', 'showcaseidx_cachebust');
}

function showcaseidx_cachebust_deactivation() {
    wp_clear_scheduled_hook('showcaseidx_cachebust');
}

function showcaseidx_add_scripts() {
    wp_enqueue_script("showcaseidx_js", "http://cdn.showcaseidx.com/js/mydx2.js", array(), null, true);
    wp_enqueue_style("showcaseidx_css", "http://cdn.showcaseidx.com/css/screen.css");
}
add_action( 'wp_enqueue_scripts', 'showcaseidx_add_scripts' );

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

    if ( array_key_exists(SHOWCASEIDX_QUERY_VAR_SEARCH, $wp_query->query_vars ) ) {
        showcaseidx_seoify('Property Search', 'Search the MLS for real estate, both for sale and for rent, in your area.', 'real estate property search, mls search');

        $seoPlaceholder = '<a href="' . showcaseidx_base_url() . '/all">View all listings</a>'; //'http://idx.showcaseidx.com/sitemap/8');
        $content = showcaseidx_generate_app($seoPlaceholder);
        showcaseidx_display_templated($content);
    }

    if (array_key_exists(SHOWCASEIDX_QUERY_VAR_LISTINGS, $wp_query->query_vars)) {
        showcaseidx_seoify('Real Estate For Sale & For Rent', 'All listings For Sale & For Rent in the MLS.', 'real estate for sale, real estate for rent');

        // pages go 0..n
        $currentPageNum = (int) $wp_query->get(SHOWCASEIDX_QUERY_VAR_LISTINGS_PAGENUM);
        $apiKey = get_option('showcaseidx_api_key');

        // get root "all" page
        $proxyContentBaseUrl = "http://idx.showcaseidx.com/seo/{$apiKey}/"; // trailing / required
        $sitemap = showcaseidx_cachable_fetch($proxyContentBaseUrl);

        $pageMatches = array();
        $count = preg_match_all('/<a href="([^"]+)"/', $sitemap, $pageMatches);
        if ($count === 0)
        {
            wp_redirect( showcaseidx_base_url(), 302 );
            exit();
        }
        $lastPageNum = $count;

        $currentPageName = $pageMatches[1][$currentPageNum];
        $proxyContentPageUrl = "{$proxyContentBaseUrl}{$currentPageName}";
        $currentPageContent = showcaseidx_cachable_fetch($proxyContentPageUrl);

        // page content
        $content = preg_replace_callback('/<a href="#\/listings\/([^"]+)" title="([^"]+)"/', 'showcaseidx_seo_listing_url_regex_callback', $currentPageContent);

        // pagination
        $seoBaseUrl = showcaseidx_base_url() . '/all/';
        if ($currentPageNum != 0)
        {
            $prevPageNum = $currentPageNum-1;
            $content .= '<link rel="prev" href="' . $seoBaseUrl . $prevPageNum . '" />';
            $content .= '<a href="' . $seoBaseUrl . $prevPageNum . '">prev</a>';
            $content .= ' ';
        }
        if ($currentPageNum < $lastPageNum)
        {
            $nextPageNum = $currentPageNum+1;
            $content .= '<link rel="next" href="' . $seoBaseUrl . $nextPageNum . '" />';
            $content .= '<a href="' . $seoBaseUrl . $nextPageNum . '">next</a>';
        }

        showcaseidx_display_templated("<h1>Real Estate For Sale &amp; For Rent</h1>{$content}");
    }

    if (array_key_exists(SHOWCASEIDX_QUERY_VAR_LISTING, $wp_query->query_vars)) {
        $seo = $wp_query->get(SHOWCASEIDX_QUERY_VAR_SEO_TITLE);
        showcaseidx_seoify($seo, "Real estate information on {$seo}. See pictures, current price, sale and rental status, and more.", "{$seo}, {$seo} for sale, {$seo} for rent");

        $ListingId = trim($_REQUEST[SHOWCASEIDX_QUERY_VAR_LISTING], ' /');
        $defaultAppUrl = "/listings/{$ListingId}";

        $seoPlaceholder = showcaseidx_cachable_fetch("http://idx.showcaseidx.com/seo_listing/{$ListingId}");
        $content = showcaseidx_generate_app($seoPlaceholder, $defaultAppUrl);
        showcaseidx_display_templated($content);
    }

    // temporarily disabled pending community-by-id data refector
    if (0 && array_key_exists(SHOWCASEIDX_QUERY_VAR_COMMUNITY, $wp_query->query_vars)) {
        $CommunityId = trim($_REQUEST[SHOWCASEIDX_QUERY_VAR_COMMUNITY], ' /');
        $defaultAppUrl = "/browse/{$CommunityId}";

        $seoPlaceholder = showcaseidx_cachable_fetch("http://idx.showcaseidx.com/seo_community/{$CommunityId}");
        $content = showcaseidx_generate_app($seoPlaceholder, $defaultAppUrl);
        showcaseidx_display_templated($content);
    }
}

function showcaseidx_install_routing() {
    global $wp_rewrite;

    // in order to get any custom rewrite rules inserted into .htaccess, have to set use_verbose_rules=true for some reason
    // seems like a hideous bug but hey what can I do
    $wp_rewrite->use_verbose_rules = true;

    // shared stuff
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_SEO_TITLE . '%', '([^&]+)');

    // map LISTINGS page (seo list for all listings)
    add_rewrite_rule(
        showcaseidx_get_prefix() . '/all/?([0-9]+)?.*$',
        'index.php?' . SHOWCASEIDX_QUERY_VAR_LISTINGS . '&' . SHOWCASEIDX_QUERY_VAR_LISTINGS_PAGENUM . '=$1',
        'top'
    );
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_LISTINGS . '%', '([^&]+)');
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_LISTINGS_PAGENUM . '%', '([^&]+)');
    
    // map LISTING pages
    add_rewrite_rule(
        showcaseidx_get_prefix() . '/(.*)/([0-9]+_[0-9]+)/?$',
        'index.php?' . SHOWCASEIDX_QUERY_VAR_LISTING . '=$2&' . SHOWCASEIDX_QUERY_VAR_SEO_TITLE . '=$1',
        'top'
    );
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_LISTING . '%', '([^&]+)');
    
    // map COMMUNITY pages
    add_rewrite_rule(
        showcaseidx_get_prefix() . '/?.*/([0-9]+)/?$',
        'index.php?' . SHOWCASEIDX_QUERY_VAR_COMMUNITY . '=$1',
        'top'
    );
    add_rewrite_tag('%' . SHOWCASEIDX_QUERY_VAR_COMMUNITY . '%', '([^&]+)');
    
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

function showcaseidx_wp_title($title, $sep)
{
    global $wp_query;

    $localTitle = $wp_query->get(SHOWCASEIDX_QUERY_VAR_SEO_TITLE);
    $localTitle = trim($localTitle);
    $localTitle = htmlentities($localTitle);
    if (empty($localTitle))
    {
        return $title;
    }
    else
    {
        return "{$localTitle} {$sep} {$title}";
    }
}

function showcaseidx_seoify($title, $description = NULL, $keywords = NULL)
{
    global $wp_query;

    add_filter('wp_title', 'showcaseidx_wp_title', 10, 2);
    $wp_query->set(SHOWCASEIDX_QUERY_VAR_SEO_TITLE, $title);

    add_action('wp_head', 'showcaseidx_wp_head');
    $wp_query->set(SHOWCASEIDX_QUERY_VAR_SEO_DESCRIPTION, $description);
    $wp_query->set(SHOWCASEIDX_QUERY_VAR_SEO_KEYWORDS, $keywords);
}

function showcaseidx_wp_head()
{
    global $wp_query;

    $seoDescription = htmlentities(trim($wp_query->get(SHOWCASEIDX_QUERY_VAR_SEO_DESCRIPTION)));
    if (!empty($seoDescription))
    {
        echo "<meta name=\"description\" value=\"{$seoDescription}\" />\n";
    }

    $seoKeywords = htmlentities(trim($wp_query->get(SHOWCASEIDX_QUERY_VAR_SEO_KEYWORDS)));
    if (!empty($seoKeywords))
    {
        echo "<meta name=\"keywords\" value=\"{$seoKeywords}\" />\n";
    }
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
    return get_option('home') . '/' . showcaseidx_get_prefix();
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

function showcaseidx_activation_hook()
{
    showcaseidx_refresh_setup_expensive();
    showcaseidx_notify_hq_of_activation();
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
