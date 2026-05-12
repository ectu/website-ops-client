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