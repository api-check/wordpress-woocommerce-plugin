<?php

// Create custom plugin settings menu
add_action('admin_menu', 'apichecknl_plugin_create_menu');

function apichecknl_plugin_create_menu()
{
    // Create new top-level menu
    add_menu_page('ApiCheck WooCommerce Instellingen', 'ApiCheck', 'manage_options', __FILE__, 'apichecknl_plugin_settings_page', 'dashicons-yes', 25);

    // Call register settings function
    add_action('admin_init', 'register_apichecknl_plugin_settings');
}

function register_apichecknl_plugin_settings()
{
    // Register our settings
    register_setting('apichecknl-plugin-settings-group', 'apichecknl_api_url');
    register_setting('apichecknl-plugin-settings-group', 'apichecknl_api_key');
    register_setting('apichecknl-plugin-settings-group', 'apichecknl_enable_disabled');
    register_setting('apichecknl-plugin-settings-group', 'apichecknl_error_message');

    // Set default values
    update_option('apichecknl_error_message', 'Er is geen adres gevonden met deze gegevens. Controleer en vul je adres handmatig in.');
    update_option('apichecknl_api_url', 'https://api.apicheck.nl/lookup/v1/postalcode/');
}

function apichecknl_plugin_settings_page()
{
    ?>
    <div class="wrap" style="background: #fff; padding: 10px 20px;">

        <img height="60px" src="<?php echo(APICHECKNL_PLUGIN_URL) ?>/assets/img/logo.png" alt="ApiCheck Logo">

        <h1>ApiCheck</h1>
        <hr>

        <p>
            Met onze postcode API is het mogelijk om adressen automatisch te laten aanvullen. <br>
            De klant vult postcode en huisnummer in en krijgt vervolgens direct zijn straatnaam te zien.
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('apichecknl-plugin-settings-group'); ?>
            <?php do_settings_sections('apichecknl-plugin-settings-group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Schakel plugin in</th>
                    <td><input type="checkbox" name="apichecknl_enable_disabled"
                               value="1" <?php checked(1, get_option('apichecknl_enable_disabled'), true); ?> /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td>
                        <input class="regular-text" type="text" name="apichecknl_api_key"
                               value="<?php echo esc_attr(get_option('apichecknl_api_key')); ?>"
                               placeholder="Vul je APiCheck api-key in"/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Melding geen adres gevonden</th>
                    <td>
                        <input class="regular-text" type="text" name="apichecknl_error_message"
                               value="<?php echo esc_attr(get_option('apichecknl_error_message')); ?>"
                               placeholder="Welke melding moet er getoond worden indien er geen adres is gevonden?"/>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
<?php } ?>