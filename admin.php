<?php

/************************************************
 * Code related to the Admin area of the plugin.
 */

add_action( 'admin_menu', 'showcaseidx_create_menu_page' );
add_action( 'admin_init', 'register_mysettings' );

function showcaseidx_create_menu_page() {
    add_menu_page("Showcase IDX Admin", "Showcase IDX", "manage_options", "showcaseidx", "display_showcase_settings", null, 100);
}

function register_mysettings() {  
    register_setting( 'showcase-settings-group', 'showcaseidx_api_host');
    register_setting( 'showcase-settings-group', 'showcaseidx_api_key');
    register_setting( 'showcase-settings-group', 'showcaseidx_template');
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
    showcaseidx_install_rewrite_rules();
    showcaseidx_bust_cache();
    return $input;
}

function showcaseidx_option($value, $label, $selected) {
    $value = htmlspecialchars($value);
    $label = htmlspecialchars($label);
    $selected = ($selected == $value) ? ' selected ' : NULL;
    echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
}

function display_showcase_settings() {
    $debug = isset($_GET['showcaseidx_debug']);
    $adminPanelUrl = home_url() . '/' . showcaseidx_get_prefix() . '/#admin';

?>
<div class="wrap">
<h2>Showcase IDX Configuration</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'showcase-settings-group' ); ?>

    <table class="form-table">
<?php if ($debug): ?>
        <tr valign="top">
            <th scope="row">API Hostname</th>
            <td><input type="text" name="showcaseidx_api_host" value="<?php echo showcaseidx_get_host(); ?>" /></td>
        </tr>
<?php endif ?>
        <tr valign="top">
            <th scope="row">API Key</th>
            <td><input type="text" name="showcaseidx_api_key" value="<?php echo get_option('showcaseidx_api_key'); ?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row">URL namespace</th>
            <td>/<input type="text" name="showcaseidx_url_namespace" value="<?php echo showcaseidx_get_prefix(); ?>" />/</td>
        </tr>
<!-- LATER: I couldn't figure out how to render custom templates correctly (ie ones w/sidebars wouldn't show the sidebars
        <tr valign="top">
            <th scope="row">Page Template</th>
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
        <tr valign="top">
            <th scope="row">Edit Showcase IDX Account Setup</th>
            <td>
            Many additional options for managing the look &amp; feel of your Showcase IDX Plugin can be done from the <a href="<?php echo $adminPanelUrl; ?>" target="showcaseAdmin">Showcase IDX Admin Panel</a>.
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>
</div>
<?php } ?>
