<?php

if (!defined('ABSPATH')) {
    exit;
}

function woc_get_tasks() {

    $master_url = get_option('woc_master_url');
    $project_id = get_option('woc_project_id');
    $token      = get_option('woc_api_token');

    if (!$master_url || !$project_id || !$token) {
        return [
            'success' => false,
            'message' => 'Client noch nicht konfiguriert.',
        ];
    }

    $response = wp_remote_get(add_query_arg([
        'project_id' => $project_id,
        'token'      => $token,
    ], trailingslashit($master_url) . 'wp-json/website-ops/v1/tasks'), [
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => $response->get_error_message(),
        ];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!$body || empty($body['success'])) {
        return [
            'success' => false,
            'message' => 'Ungültige API Antwort.',
        ];
    }

    return $body;
}

function woc_create_task(
    $title,
    $message = '',
    $type = 'Bug',
    $priority = 'Normal'
) {

    $master_url = get_option('woc_master_url');
    $project_id = get_option('woc_project_id');
    $token      = get_option('woc_api_token');

    if (!$master_url || !$project_id || !$token) {
        return [
            'success' => false,
            'message' => 'Client noch nicht konfiguriert.',
        ];
    }

    $response = wp_remote_post(
        trailingslashit($master_url) . 'wp-json/website-ops/v1/tasks/create',
        [
            'timeout' => 15,
            'body'    => [
                'project_id' => $project_id,
                'token'      => $token,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'priority'   => $priority,
            ],
        ]
    );

    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => $response->get_error_message(),
        ];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!$body || empty($body['success'])) {
        return [
            'success' => false,
            'message' => $body['message'] ?? 'Ungültige API Antwort.',
        ];
    }

    return $body;
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

                if ($score >= 60) {
                    return 'recommended';
                }

                return 'critical';
            }

            return 'unknown';
        }
    }

    if (is_array($value)) {
        $critical = (int) ($value['critical'] ?? 0);
        $recommended = (int) ($value['recommended'] ?? 0);

        if ($critical > 0) {
            return 'critical';
        }

        if ($recommended > 0) {
            return 'recommended';
        }

        return 'good';
    }

    return 'unknown';
}

function woc_send_heartbeat_request() {

    $master_url = get_option('woc_master_url');

    $project_id = get_option('woc_project_id');
    $token      = get_option('woc_api_token');

    if (!$master_url || !$project_id || !$token) {
        return [
            'success' => false,
            'message' => 'Client noch nicht konfiguriert.',
        ];
    }

    $response = wp_remote_post(
        trailingslashit($master_url) . 'wp-json/website-ops/v1/heartbeat',
        [
            'timeout' => 10,
            'body' => [
            'project_id'  => $project_id,
            'token'       => $token,
            'php_version' => PHP_VERSION,
            'wp_version'  => get_bloginfo('version'),
            'site_url'    => home_url(),
            'site_icon'   => get_site_icon_url(128),
            'site_health' => woc_get_site_health_status(),
        ],
        ]
    );

    if (is_wp_error($response)) {
        $debug = [
            'time'    => current_time('mysql'),
            'success' => false,
            'error'   => $response->get_error_message(),
        ];

        update_option('woc_last_heartbeat_debug', $debug);

        return $debug;
    }

    $debug = [
        'time'          => current_time('mysql'),
        'success'       => true,
        'response_code' => wp_remote_retrieve_response_code($response),
        'body'          => wp_remote_retrieve_body($response),
    ];

    update_option('woc_last_heartbeat_debug', $debug);

    return $debug;
}