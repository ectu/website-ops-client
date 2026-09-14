<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ---------------------------------------------------
 * SETTINGS PAGE
 * ---------------------------------------------------
 */

add_action('admin_menu', function () {

    add_options_page(
        'Website Ops Client',
        'Website Ops Client',
        'manage_options',
        'website-ops-client',
        'woc_render_settings_page'
    );

});

/**
 * ---------------------------------------------------
 * REGISTER SETTINGS
 * ---------------------------------------------------
 */

add_action('admin_init', function () {

    register_setting('woc_settings', 'woc_master_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);

    register_setting('woc_settings', 'woc_project_id', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 0,
    ]);

    register_setting('woc_settings', 'woc_api_token', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);

});

// Reset the interval when the connection target or credentials change.
function woc_reset_heartbeat_state() {
    delete_transient('woc_last_heartbeat');
    delete_transient('woc_heartbeat_retry');
    delete_option('woc_last_heartbeat_debug');
    delete_option('woc_last_heartbeat_success');
}
foreach (['woc_master_url', 'woc_project_id', 'woc_api_token'] as $woc_option) {
    add_action('update_option_' . $woc_option, 'woc_reset_heartbeat_state', 10, 0);
    add_action('add_option_' . $woc_option, 'woc_reset_heartbeat_state', 10, 0);
}
unset($woc_option);
// An upgrade does not run activation hooks; also reset an old cached success once.
add_action('admin_init', static function () {
    if (current_user_can('manage_options') && get_option('woc_heartbeat_fix_version') !== '1.0.7') {
        woc_reset_heartbeat_state();
        update_option('woc_heartbeat_fix_version', '1.0.7', false);
    }
});

/**
 * ---------------------------------------------------
 * SETTINGS PAGE RENDER
 * ---------------------------------------------------
 */

function woc_render_settings_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    $connection_result = null;
    $heartbeat_result = null;

    if (isset($_POST['woc_test_connection'])) {
        check_admin_referer('woc_test_connection');
        $connection_result = woc_get_tasks();
        if (!empty($connection_result['success'])) { $heartbeat_result = woc_send_heartbeat_request(); }
    }

    if (isset($_POST['woc_send_heartbeat_now'])) {
        check_admin_referer('woc_send_heartbeat_now');
        $heartbeat_result = woc_send_heartbeat_request();
    }
    $heartbeat_summary = $heartbeat_result ?: get_option('woc_last_heartbeat_debug');
    ?>
    <div class="wrap">

        <h1>Website Ops Client</h1>

        <?php if (isset($_GET['connected'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Verbindung erfolgreich übernommen.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">

            <?php settings_fields('woc_settings'); ?>

            <table class="form-table">

                <tr>
                    <th scope="row">
                        <label for="woc_master_url">Master URL</label>
                    </th>
                    <td>
                        <input
                            type="url"
                            id="woc_master_url"
                            name="woc_master_url"
                            value="<?php echo esc_attr(get_option('woc_master_url')); ?>"
                            class="regular-text"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="woc_project_id">Projekt-ID</label>
                    </th>
                    <td>
                        <input
                            type="number"
                            id="woc_project_id"
                            name="woc_project_id"
                            value="<?php echo esc_attr(get_option('woc_project_id')); ?>"
                            class="small-text"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="woc_api_token">API Token</label>
                    </th>
                    <td>
                        <input
                            type="password"
                            autocomplete="new-password"
                            id="woc_api_token"
                            name="woc_api_token"
                            value="<?php echo esc_attr(get_option('woc_api_token')); ?>"
                            class="regular-text"
                        >
                    </td>
                </tr>

            </table>

            <?php submit_button('Einstellungen speichern'); ?>

        </form>

        <hr>

        <h2>Verbindung testen</h2>

        <form method="post">
            <?php wp_nonce_field('woc_test_connection'); ?>

            <?php submit_button(
                'Verbindung testen',
                'secondary',
                'woc_test_connection'
            ); ?>
        </form>

        <?php if ($connection_result !== null) : ?>
            <div class="notice <?php echo !empty($connection_result['success']) ? 'notice-success' : 'notice-error'; ?> inline">
                <p>
                    <?php if (!empty($connection_result['success'])) : ?>
                        Verbindung erfolgreich.
                        Gefundene Aufgaben:
                        <?php echo esc_html($connection_result['count'] ?? 0); ?>
                    <?php else : ?>
                        Verbindung fehlgeschlagen:
                        <?php echo esc_html($connection_result['message'] ?? 'Unbekannter Fehler'); ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <hr>
        <h2>Heartbeat</h2>
        <p>Übermittelt PHP-Version, Favicon und Website-Zustand an den Master. Automatisch bei Administrator-Aufrufen, nach bestätigtem Erfolg höchstens alle 15 Minuten.</p>
        <form method="post">
            <?php wp_nonce_field('woc_send_heartbeat_now'); ?>
            <?php submit_button('Heartbeat jetzt senden', 'secondary', 'woc_send_heartbeat_now'); ?>
        </form>
        <?php if (is_array($heartbeat_summary)) : ?>
            <div class="notice <?php echo !empty($heartbeat_summary['success']) ? 'notice-success' : 'notice-error'; ?> inline">
                <p><?php echo esc_html(($heartbeat_summary['time'] ?? '') . ' — ' . ($heartbeat_summary['message'] ?? 'Noch keine geprüfte Antwort.')); ?></p>
            </div>
        <?php else : ?>
            <p>Noch kein Heartbeat-Versuch mit diesen Einstellungen.</p>
        <?php endif; ?>
        <?php if (get_option('woc_last_heartbeat_success')) : ?>
            <p>Zuletzt vom Master bestätigt: <?php echo esc_html(get_option('woc_last_heartbeat_success')); ?></p>
        <?php endif; ?>
    </div>
    <?php
}