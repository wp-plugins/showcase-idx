<?php

add_action( 'admin_menu', 'showcaseidx_create_menu_page' );
add_action( 'admin_init', 'register_mysettings' );

function showcaseidx_create_menu_page() {
    add_menu_page("Showcase IDX Admin", "Showcase IDX", "manage_options", "showcaseidx", "display_showcase_settings", null, 100);
}

function register_mysettings() {  
    register_setting( 'showcase-settings-group', 'showcaseidx_api_key' );
    register_setting( 'showcase-settings-group', 'showcaseidx_region' );
}

function matches($x) {
    if ($x == get_option('showcaseidx_region')) {
        echo " selected ";
    }
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
            <td><input type="text" name="showcaseidx_api_key" value="<?php echo get_option('showcaseidx_api_key'); ?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row">Region</th>
            <td>
                <select name="showcaseidx_region">
                    <option value=""></option>
                    <option <?php matches("1_2"); ?> value="1_2">GAMLS/FMLS</option>
                    <option <?php matches("74"); ?> value="74">Miami</option>
                    <option <?php matches("100_101_102"); ?> value="100_101_102">New Jersey</option>
                </select>
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>
</div>
<?php } ?>