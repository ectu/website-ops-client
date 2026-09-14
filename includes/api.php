<?php
if (!defined('ABSPATH')) { exit; }

/** One transport for all calls; never put credentials in URLs or follow redirects with them. */
function woc_api_request($route, $method = 'GET', $payload = []) {
    $master = get_option('woc_master_url');
    $project = get_option('woc_project_id');
    $token = get_option('woc_api_token');
    if (!$master || !$project || !$token) {
        return ['success' => false, 'message' => 'Client noch nicht konfiguriert.', 'response_code' => 0];
    }
    $parts = wp_parse_url($master);
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        return ['success' => false, 'message' => 'Bitte die HTTPS-Adresse des Masters ohne Zugangsdaten oder URL-Parameter eintragen.', 'response_code' => 0];
    }
    $url = trailingslashit($master) . 'wp-json/website-ops/v1/' . $route;
    $payload['project_id'] = absint($project);
    $args = ['method' => $method, 'timeout' => 15, 'redirection' => 0, 'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']];
    if ($method === 'GET') { $url = add_query_arg($payload, $url); }
    else { $args['body'] = $payload; }
    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) {
        return ['success' => false, 'message' => substr(str_replace($token, '[entfernt]', sanitize_text_field($response->get_error_message())), 0, 500), 'response_code' => 0];
    }
    $status = (int) wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($status >= 200 && $status < 300 && is_array($data) && ($data['success'] ?? false) === true) {
        $data['response_code'] = $status;
        return $data;
    }
    $message = is_array($data) && is_string($data['message'] ?? null) ? $data['message'] : 'Keine gültige Bestätigung vom Master erhalten.';
    if ($status >= 300 && $status < 400) { $message = 'Der Master leitet die Anfrage um. Bitte die endgültige HTTPS-Adresse (inklusive korrektem www-Präfix) eintragen.'; }
    $message = substr(str_replace($token, '[entfernt]', sanitize_text_field($message)), 0, 500);
    return ['success' => false, 'message' => 'HTTP ' . $status . ': ' . $message, 'response_code' => $status];
}
function woc_get_tasks() { return woc_api_request('tasks'); }
function woc_create_task($title, $message = '', $type = 'Bug', $priority = 'Normal') {
    return woc_api_request('tasks/create', 'POST', ['title' => $title, 'message' => $message, 'type' => $type, 'priority' => $priority]);
}

function woc_get_site_health_status() {

    $value = get_transient('health-check-site-status-result');

    if (!$value) {
        $value = get_site_transient('health-check-site-status-result');
    }

    if (!$value) {
        $value = get_option('health-check-site-status-result');
    }

    if (!$value) {
        return 'unknown';
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $value = $decoded;
        } else {
            $plain = strtolower(trim($value));

            if (in_array($plain, ['good', 'recommended', 'critical'], true)) {
                return $plain;
            }

            if (is_numeric($plain)) {
                $score = (int) $plain;

                if ($score >= 80) {
                    return 'good';
                }

                if ($score >= 40) {
                    return 'recommended';
                }

                return 'critical';
            }

            return 'unknown';
        }
    }

    if (is_array($value)) {
        if (isset($value['status']) && in_array($value['status'], ['good', 'recommended', 'critical'], true)) {
            return $value['status'];
        }

        if (isset($value['score'])) {
            $score = (int) $value['score'];

            if ($score >= 80) {
                return 'good';
            }

            if ($score >= 40) {
                return 'recommended';
            }

            return 'critical';
        }

        $critical = (int) ($value['critical'] ?? 0);
        $recommended = (int) ($value['recommended'] ?? 0);

        if ($critical > 0 || $recommended > 0) {
            return 'recommended';
        }

        return 'good';
    }

    return 'unknown';
}

function woc_send_heartbeat_request() {
    $result = woc_api_request('heartbeat', 'POST', [
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
        'site_url' => home_url(),
        'site_icon' => get_site_icon_url(128),
        'site_health' => woc_get_site_health_status(),
    ]);
    $success = !empty($result['success']);
    $summary = [
        'time' => current_time('mysql'),
        'success' => $success,
        'response_code' => (int) ($result['response_code'] ?? 0),
        'message' => $success ? 'Heartbeat vom Master bestätigt.' : ($result['message'] ?? 'Heartbeat fehlgeschlagen.'),
    ];
    update_option('woc_last_heartbeat_debug', $summary, false);
    if ($success) {
        set_transient('woc_last_heartbeat', true, 15 * MINUTE_IN_SECONDS);
        delete_transient('woc_heartbeat_retry');
        update_option('woc_last_heartbeat_success', current_time('mysql'), false);
    } else {
        delete_transient('woc_last_heartbeat');
        set_transient('woc_heartbeat_retry', true, MINUTE_IN_SECONDS);
    }
    return $summary;
}
