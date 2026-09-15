<?php
if (!defined('ABSPATH')) { exit; }

function woc_email_policy_defaults() {
    return ['plugin' => true, 'theme' => true, 'core' => true];
}
// Only accept a complete, versioned policy. A malformed response cannot alter it.
function woc_store_email_policy($policy) {
    if (!is_array($policy) || ($policy['version'] ?? null) !== 1 || !is_array($policy['suppress_success'] ?? null)) { return false; }
    foreach (woc_email_policy_defaults() as $key => $unused) {
        if (!isset($policy['suppress_success'][$key]) || !is_bool($policy['suppress_success'][$key])) { return false; }
    }
    $clean = array_intersect_key($policy['suppress_success'], woc_email_policy_defaults());
    update_option('woc_email_policy', $clean, false);
    update_option('woc_email_policy_synced', current_time('mysql'), false);
    return true;
}
function woc_email_policy() {
    $saved = get_option('woc_email_policy', []);
    $policy = woc_email_policy_defaults();
    if (is_array($saved)) {
        foreach ($policy as $key => $default) {
            if (isset($saved[$key]) && is_bool($saved[$key])) { $policy[$key] = $saved[$key]; }
        }
    }
    return $policy;
}
function woc_filter_update_email($enabled, $results, $kind) {
    // Network-wide update emails require a network-wide configuration, not a subsite policy.
    if (is_multisite() || !$enabled || !woc_email_policy()[$kind] || !is_array($results) || !$results) { return $enabled; }
    foreach ($results as $result) {
        if (!is_object($result) || !isset($result->result) || $result->result !== true) { return $enabled; }
    }
    return false;
}
add_filter('auto_plugin_update_send_email', static function ($enabled, $results = []) {
    return woc_filter_update_email($enabled, $results, 'plugin');
}, 10, 2);
add_filter('auto_theme_update_send_email', static function ($enabled, $results = []) {
    return woc_filter_update_email($enabled, $results, 'theme');
}, 10, 2);
add_filter('auto_core_update_send_email', static function ($send, $type) {
    return !is_multisite() && $type === 'success' && woc_email_policy()['core'] ? false : $send;
}, 10, 2);

// Scheduled independently of admin visits. No HTTP request is made from a mail filter.
add_action('init', static function () {
    if (get_option('woc_master_url') && get_option('woc_project_id') && get_option('woc_api_token') && !wp_next_scheduled('woc_email_policy_sync')) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', 'woc_email_policy_sync');
    }
});
add_action('woc_email_policy_sync', static function () {
    if (get_option('woc_master_url') && get_option('woc_project_id') && get_option('woc_api_token')) { woc_send_heartbeat_request(); }
});
function woc_email_policy_deactivate() { wp_clear_scheduled_hook('woc_email_policy_sync'); }

function woc_render_email_policy() {
    echo '<h2>WordPress-E-Mail-Benachrichtigungen</h2>';
    if (is_multisite()) {
        echo '<p>Multisite: Die E-Mail-Filter sind deaktiviert. Netzwerkweite Update-Mails bleiben unverändert.</p>';
        return;
    }
    $synced = get_option('woc_email_policy_synced');
    echo '<p>' . esc_html($synced ? 'Zentrale Regel zuletzt übernommen: ' . $synced : 'Standardregel aktiv; noch keine zentrale Regel empfangen.') . '</p><ul>';
    foreach (['plugin' => 'Plugin', 'theme' => 'Theme', 'core' => 'WordPress-Core'] as $key => $label) {
        echo '<li>' . esc_html($label . '-Updates erfolgreich: ' . (woc_email_policy()[$key] ? 'E-Mail unterdrücken' : 'WordPress-Versand zulassen')) . '</li>';
    }
    echo '</ul><p>Fehlgeschlagene Updates, Wiederherstellungsmodus und Konto-E-Mails werden von Website Ops nicht unterdrückt. Einstellungen im Master unter Website Ops → Einstellungen bzw. beim Projekt ändern. Übernahme mit dem nächsten Heartbeat oder stündlich über WP-Cron (abhängig von Website-Aufrufen). „Heartbeat jetzt senden“ übernimmt Änderungen sofort.</p><p>Bei Verbindungsfehlern bleibt die letzte gültige Regel aktiv. Vor dem ersten Abgleich werden reine Erfolgsmeldungen unterdrückt. E-Mails anderer Plugins und deren eigene Versandfilter bleiben unverändert.</p>';
}
