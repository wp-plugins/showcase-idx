<?php

add_action( 'admin_menu', 'showcaseidx_create_menu_page' );
add_action( 'admin_init', 'register_mysettings' );

function showcaseidx_create_menu_page() {
    add_menu_page("Showcase IDX Admin", "Showcase IDX", "manage_options", "showcaseidx", "display_showcase_settings", null, 100);
}

function register_mysettings() {  
    register_setting( 'showcase-settings-group', 'api_key' );
    register_setting( 'showcase-settings-group', 'website_id' );
}

function display_showcase_settings() {
?>
<div class="wrap">
<h2>Showcase IDX Configuration</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'showcase-settings-group' ); ?>

    <table class="form-table">
        <tr valign="top">
        <th scope="row">API Key</th>
        <td><input type="text" name="api_key" value="<?php echo get_option('api_key'); ?>" /></td>
        </tr>        
    </table>
    <?php submit_button(); ?>
</form>
</div>
<?php } ?>