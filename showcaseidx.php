<?php
/*
Plugin Name: Showcase IDX
Plugin URI: http://showcaseidx.com/
Description: Interactive, map-centric real-estate property search.
Author: Kanwei Li
Version: 1.2.2
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

global $wp_version;

require_once(ABSPATH . "wp-admin/includes/plugin.php");
add_action( 'plugins_loaded', 'showcase_plugin_setup' );

function showcase_plugin_setup() {
    add_option("showcaseidx_api_key", "");
    add_shortcode("showcaseidx", "showcase_shortcode");

    require_once("idx_options.php");
}

function showcase_shortcode() {
    $host = "idx.showcaseidx.com";
    $api_key = get_option("showcaseidx_api_key", "");
    $data_prefix = get_option("showcaseidx_region", "");
    return <<<EOT
        <link href="http://$host/css/screen.css" media="screen, projection" rel="stylesheet" type="text/css" />
        <link href='http://fonts.googleapis.com/css?family=Pontano+Sans&subset=latin' rel='stylesheet' type='text/css'>
        <link href='http://fonts.googleapis.com/css?family=Bitter:400,700&subset=latin' rel='stylesheet' type='text/css'>
        <link href='http://fonts.googleapis.com/css?family=Francois+One&subset=latin' rel='stylesheet' type='text/css'>
        <script type="text/javascript">var SHOWCASE_CONF = { WEBSITE_ID: "$api_key", WEBSITE_ROOT: "http://$host", DATA_PREFIX: "$data_prefix" };</script>

        <div id="mydx-container" ng-controller="AppController" ng-app="mydx2">
            <div ng-include="'http://$host/templates/layout.html'"></div>
            <script src="http://$host/js/mydx2.js"></script>
            <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDfITsP9KWNM61m1eT_8rsov2QoK932LCY&sensor=false"></script>
            <script type="text/javascript" src="http://s7.addthis.com/js/300/addthis_widget.js#pubid=ra-50a2dde218aceee1"></script>
        </div>
EOT;
}

?>