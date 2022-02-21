<?php

// Create custom plugin settings menu
add_action('admin_menu', 'ac_plugin_create_menu');

function ac_plugin_create_menu()
{
    // Create new top-level menu
    add_menu_page('ApiCheck WooCommerce Instellingen', 'ApiCheck', 'manage_options', __FILE__, 'ac_plugin_settings_page', 'dashicons-yes', 25);

    // Call register settings function
    add_action('admin_init', 'register_ac_plugin_settings');
}

function register_ac_plugin_settings()
{
    // Register our settings
    register_setting('ac-plugin-settings-group', 'ac_api_url');
    register_setting('ac-plugin-settings-group', 'ac_api_key');
    register_setting('ac-plugin-settings-group', 'ac_enable_disabled');
    register_setting('ac-plugin-settings-group', 'ac_error_message');

    // Set default values
    update_option('ac_error_message', 'Er is geen adres gevonden. Vul je adres handmatig in.');
    update_option('ac_api_url', 'https://api.apicheck.nl/lookup/v1/postalcode/');
}

function ac_plugin_settings_page()
{
?>
    <div class="wrap" style="background: #fff; padding: 10px 20px;">

        <img height="60px" src="/wp-content/plugins/apicheck-woocommerce-postcode-checker/assets/img/logo.png" alt="ApiCheck Logo">

        <h1>ApiCheck WooCommerce</h1>
        <hr>

        <form method="post" action="options.php">
            <?php settings_fields('ac-plugin-settings-group'); ?>
            <?php do_settings_sections('ac-plugin-settings-group'); ?>
            <table class="form-table">

                <tr valign="top">
                    <th scope="row">Schakel plugin in </th>
                    <td><input type="checkbox" name="ac_enable_disabled" value="1" <?php checked(1, get_option('ac_enable_disabled'), true); ?> /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td><input type="text" name="ac_api_key" value="<?php echo esc_attr(get_option('ac_api_key')); ?>" style="width:100%;" placeholder="Vul je APiCheck api-key in" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Melding geen adres gevonden</th>
                    <td><input type="text" name="ac_error_message" value="<?php echo esc_attr(get_option('ac_error_message')); ?>" style="width:100%;" placeholder="Welke melding moet er getoont worden indien er geen adres is gevonden?" /></td>
                </tr>

            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php } ?>
