<?php

/************************************************
 * Code related to the Admin area of the plugin.
 */

add_action( 'admin_menu', 'showcaseidx_create_menu_page' );
add_action( 'admin_init', 'register_mysettings' );

function showcaseidx_create_menu_page() {
    add_menu_page("Showcase IDX Admin", "Showcase IDX", "manage_options", "showcaseidx", "display_showcase_settings", null, '100.1337');
}

function register_mysettings() {  
    register_setting( 'showcase-settings-group', 'showcaseidx_api_host');
    register_setting( 'showcase-settings-group', 'showcaseidx_api_key');
    register_setting( 'showcase-settings-group', 'showcaseidx_template');
    register_setting( 'showcase-settings-group', 'showcaseidx_setup_step');
    register_setting( 'showcase-settings-group', 'showcaseidx_url_namespace', 'showcaseidx_sanitize_url_namespace');

    // this is a fake, unusued setting, which makes it easy to only flush out our rewrite rules (expensive) when our plugin's admin panel is saved
    register_setting( 'showcase-settings-group', 'showcaseidx_fake', 'showcaseidx_once_per_admin_save_hack_via_sanitizer');
}

function showcaseidx_sanitize_url_namespace($input)
{
    $input = trim($input);
    $input = trim($input, '/');
    $input = preg_replace('/[^A-z0-9-_]/', '', $input);
    return $input;
}

function showcaseidx_once_per_admin_save_hack_via_sanitizer($input)
{
    showcaseidx_refresh_setup_expensive();
    return $input;
}

function showcaseidx_refresh_setup_expensive()
{
    showcaseidx_install_rewrite_rules();
    showcaseidx_bust_cache();
}

function showcaseidx_option($value, $label, $selected) {
    $value = htmlspecialchars($value);
    $label = htmlspecialchars($label);
    $selected = ($selected == $value) ? ' selected ' : NULL;
    echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
}

function display_showcase_settings() {
    if (isset($_GET["showcaseidx_remove_key"]) && !isset($_GET["settings-updated"])) {
        update_option('showcaseidx_api_key', '');
        update_option('showcaseidx_setup_step', '');
    }
    if (isset($_GET["showcaseidx_change_namespace"]) && !isset($_GET["settings-updated"])) {
        update_option('showcaseidx_setup_step', 'api_key');
    }
    $adminPanelUrl = home_url() . '/' . showcaseidx_get_prefix() . '/#/admin';
    $propertySearchBaseUrl = home_url() . '/' . showcaseidx_get_prefix();
    $current_key = get_option('showcaseidx_api_key');
    $current_namespace = get_option('showcaseidx_url_namespace');
    $status = "Offline";
    $activated = false;
    if ($current_key) {
        $response_code = wp_remote_retrieve_response_code(wp_remote_get("http://idx.showcaseidx.com/wp_status?key=$current_key&namespace=$current_namespace"));
        if($response_code == 200) {
            $status = "Online";
            $activated = true;
        }
    }

?>

<style type="text/css">
    .showcase-admin {
        font-size: 16px;
        line-height: 1.5em;
    }
    .showcase-link {
        float: left;
        margin-right: 50px;
    }
    .showcase-status {
        clear: both;
        width: 100%;
        padding: 10px;
        text-transform: uppercase;
        color: white;
        text-align: center;
        font-size: 18px;
        font-weight: bold;
    }
    .showcase-Offline {
        background-color: #B3000B;
    }
    .showcase-Online {
        background-color: #6AC228;
    }
    .showcase-input {
        width: 30%;
        height: 50px;
        font-size: 18px;
    }
    .showcase-url {
        font-size: 18px;
    }
    .showcase-admin .button-primary {
        height: 50px;
        width: 150px;
        font-size: 18px;        
    }
    .helptext {
        margin-top: -10px;
        color: #777;
    }
</style>
<div class="showcase-admin">
<h1>Showcase IDX Configuration</h1>

<?php if($activated) { ?>
<ul>
    <li class="showcase-link"><a href="http://cdn.showcaseidx.com/ShowcaseIDXGettingStarted.pdf" target="_blank">Getting Started Guide</a></li>
    <li class="showcase-link"><a href="<?php echo $adminPanelUrl ?>/support" target="_blank">Help and Support</a></li>
    <li class="showcase-link"><a href="<?php echo $adminPanelUrl ?>" target="_blank">LeadMagic Dashboard</a></li>
</ul>

<?php } ?>

<div class="showcase-status showcase-<?php echo $status ?>"><?php echo $status ?></div>

<form method="post" action="options.php">
    <?php settings_fields( 'showcase-settings-group' ); ?>

<?php if(!$activated) { ?>

<div style="text-align: center;">

    <?php if($current_key && !$activated) { ?>

    <h2>That could've gone better...</h2>

    <p>You tried this API key: <b><?php echo $current_key; ?></b></p>
    <p>Unfortunately, it didn't work... let's try again</p>

    <?php } ?>

<h2>Enter your API Key:</h2>
<input class="showcase-input" type="text" name="showcaseidx_api_key" />
<input type="hidden" name="showcaseidx_setup_step" value="api_key" />
<input type="hidden" name="showcaseidx_url_namespace" value="<?php echo SHOWCASEIDX_SEARCH_DEFAULT_URL_NAMESPACE ?>" />

<center><?php submit_button("Get Started", "primary"); ?></center>

<h3>Don't have an API Key? <a href="http://showcaseidx.com/plans-pricing/" target="_blank">Sign up for Showcase IDX</a></h3>
</div>

<?php } else if (get_option("showcaseidx_setup_step") == "api_key") { ?>

<div style="text-align: center;">

<h2>Nicely done! You're online!</h2>

<h3>One more completely optional question for you...</h3>
<p>You can change what we call a namespace... it lets you customize your SEO URLs.<br>Most agents put in the market where they work, or you can leave it be. <br>
<b>Important: You can change this, but it will change ALL your SEO links, so it's not a good idea once you're up and running.</b></p>

<b class="showcase-url"><?php echo get_site_url(); ?>/</b>
<input class="showcase-input" type="text" name="showcaseidx_url_namespace" value="<?php echo $current_namespace; ?>" />
<br><p class="showcase-examples"><i>examples: miami-homes, atlanta-condo-search, south-beach-rentals</i></p>


<input type="hidden" name="showcaseidx_api_key" value="<?php echo $current_key; ?>" />
</p>
<input type="hidden" name="showcaseidx_setup_step" value="namespace" />
<center><?php submit_button("Finish", "primary"); ?></center>

</div>

<?php } else { ?>

    <center>
        <p>API Key: <b><?php echo get_option('showcaseidx_api_key'); ?></b> <a href="admin.php?page=showcaseidx&showcaseidx_remove_key=true">(Remove)</a></p>
        <p>URL namespace: <b><?php echo get_site_url(); ?>/<?php echo showcaseidx_get_prefix(); ?></b> <a href="admin.php?page=showcaseidx&showcaseidx_change_namespace=true">(Change)</a></p>
    </center>

<!-- LATER: I couldn't figure out how to render custom templates correctly (ie ones w/sidebars wouldn't show the sidebars
        <tr valign="top">
            <th>Page Template</th>
            <td>
                <select name="showcaseidx_template">
                    <?php
                       $templates = get_page_templates();
                       foreach ( $templates as $template_name => $template_filename ) {
                           showcaseidx_option($template_filename, $template_name, get_option('showcaseidx_template'));
                       }
                    ?>
                </select>
            </td>
        </tr>
-->
    </table>

<?php } ?>

</form>

<?php if($activated) { ?>
    <p><center>Your IDX search is available at <a href="<?php echo $propertySearchBaseUrl; ?>" target="_blank"><?php echo $propertySearchBaseUrl; ?></a><br> and any pages where you embed the plugin using the shortcode.</center></p>
    
    <div style="background: #EEEEEE; padding: 10px;">
        <h2>Quick Start Guide</h2>
        <p class="helptext">For full instructions, download our <a href="http://cdn.showcaseidx.com/ShowcaseIDXGettingStarted.pdf" target="_blank">Getting Started Guide</a></p>
        <ol>
            <li>Insert the Full Search Page shortcode <b>[showcaseidx]</b> on the page you want the search to appear on.</li>
            <li>We recommend that under Page Attributes you make the page Full Width. It works great in columns automatically adjusting to width, but it looks better in full width pages. That's usually around 930px-1000px wide.)</li>
            <li>Update or Save the page.</li>
            <li>The IDX Search will appear on the page.</li>
        </ol>
        
        <p>If you have any issues installing the plugin, please refer to our full <a href="http://cdn.showcaseidx.com/ShowcaseIDXGettingStarted.pdf" target="_blank">Getting Started Guide</a>. If you're really having trouble or something isn't working... let us know through our <a href="<?php echo $adminPanelUrl ?>/support" target="_blank">Support System</a>.</p>
    </div>

    <h2>Shortcodes</h2>
    <p class="helptext">Use these shortcodes to embed our plugin and widgets on your content pages and sidebars.</p>
    <table>
        <tr><td colspan="2"><b>Default Search Page</b></td></tr>
        <tr valign="top">
            <td style="width: 250px;">Full Search Widget</td>
            <td>[showcaseidx]</td>
        </tr>
        <tr><td colspan="2"><br><b>Search Form Widgets (form only, no results)</b></td></tr>
        <tr valign="top">
            <td>Sidebar / 230 pixels wide</td>
            <td>[showcaseidx_widget_230]</td>
        </tr>
        <tr valign="top">
            <td>Blog Post / 465 pixels wide</td>
            <td>[showcaseidx_widget_465]</td>
        </tr>
        <tr valign="top">
            <td>Header / 700 pixels wide</td>
            <td>[showcaseidx_widget_700]</td>
        </tr>
        <tr valign="top">
            <td>Header / 930 pixels wide</td>
            <td>[showcaseidx_widget_930]</td>
        </tr>
    </table>
    <br><hr>
    <h2>Hotsheets</h2>
    <p class="helptext">Hotsheets are custom searches that you can save an embed into your website. You get two automatic hotsheets that automatically display your personal or office listings. For more information on how to use hotsheets, check out the <a href="http://cdn.showcaseidx.com/ShowcaseIDXGettingStarted.pdf" target="_blank">Getting Started Guide</a>.</p>
    <table>
        <tr valign="top">
            <td style="width: 250px;">Agent Listings Hotsheet</td>
            <td style="width: 250px;">List View Results</td>
            <td>[showcaseidx_hotsheet type="agent"]</td>
        </tr>
        <tr valign="top">
            <td>Office Listings Hotsheet</td>
            <td>List View Results</td>
            <td>[showcaseidx_hotsheet type="office"]</td>
        </tr>
        <tr valign="top">
            <td>Custom Hotsheet</td>
            <td colspan="2">See <a target="_blank" href="<?php echo $adminPanelUrl; ?>/hotsheets">Hotsheets Admin Page</a> for exact shortcodes</td>
        </tr>
    </table>
<?php } else { ?>

<div style="background: #EEEEEE; padding: 10px;">
    <h2>Quick Start Guide</h2>
    <p class="helptext">For full instructions, download our <a href="http://cdn.showcaseidx.com/ShowcaseIDXGettingStarted.pdf" target="_blank">Getting Started Guide</a></p>

    <ol>
        <li>Sign up for an account with a 7-day Free Trial at <a href="http://showcaseidx.com" target="_blank">showcaseidx.com.</a></li>

        <li>Activate the plugin with your Personal API Key, it comes in the Getting Started email once your MLS paperwork has been approved.</li>

        <li>Insert the Full Search Widget <b>[showcaseidx]</b> shortcode on the page you want the search to appear on.

        <li>Update or Save the page.</li>

        <li>The IDX Search will appear on that page.</li>
    </ol>
</div>

<?php } ?>

</div>
<?php } ?>
