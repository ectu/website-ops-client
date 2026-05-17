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

/**
 * ---------------------------------------------------
 * AUTO CONNECT
 * ---------------------------------------------------
 */

add_action('admin_init', function () {

    if (!current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['page']) || $_GET['page'] !== 'website-ops-client') {
        return;
    }

    if (
        empty($_GET['master_url']) ||
        empty($_GET['project_id']) ||
        empty($_GET['token'])
    ) {
        return;
    }

    $master_url = esc_url_raw($_GET['master_url']);
    $project_id = absint($_GET['project_id']);
    $token      = sanitize_text_field($_GET['token']);

    update_option('woc_master_url', $master_url);
    update_option('woc_project_id', $project_id);
    update_option('woc_api_token', $token);

    wp_safe_redirect(add_query_arg([
        'page'      => 'website-ops-client',
        'connected' => '1',
    ], admin_url('options-general.php')));

    exit;

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

    if (isset($_POST['woc_test_connection'])) {
        check_admin_referer('woc_test_connection');
        $connection_result = woc_get_tasks();
    }

    $heartbeat_debug = get_option('woc_last_heartbeat_debug');

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
                            type="text"
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

        <?php if ($heartbeat_debug) : ?>
            <hr>

            <h2>Heartbeat Debug</h2>

            <pre style="white-space:pre-wrap;background:#f6f7f7;border:1px solid #ccd0d4;padding:12px;"><?php echo esc_html(print_r($heartbeat_debug, true)); ?></pre>
        <?php endif; ?>

    </div>
    <?php
}