<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_dashboard_setup', function () {

    wp_add_dashboard_widget(
        'website_ops_tasks_widget',
        'Website Ops – Offene Aufgaben',
        'woc_render_dashboard_widget'
    );

});

function woc_render_dashboard_widget() {

    if (
        isset($_POST['woc_new_task_submit']) &&
        current_user_can('edit_posts') &&
        check_admin_referer('woc_new_task')
    ) {
        $title    = sanitize_text_field($_POST['woc_task_title'] ?? '');
            $message  = sanitize_textarea_field($_POST['woc_task_message'] ?? '');

            $type     = sanitize_text_field($_POST['woc_task_type'] ?? 'Bug');
            $priority = sanitize_text_field($_POST['woc_task_priority'] ?? 'Normal');

        if ($title) {
            $result = woc_create_task(
                $title,
                $message,
                $type,
                $priority
            );

            if (!empty($result['success'])) {
                echo '<div class="notice notice-success inline"><p>Aufgabe wurde übermittelt.</p></div>';
            } else {
                echo '<div class="notice notice-error inline"><p>' . esc_html($result['message'] ?? 'Fehler beim Übermitteln.') . '</p></div>';
            }
        }
    }

    $data = woc_get_tasks();

    if (empty($data['success'])) {
        echo '<p>' . esc_html($data['message'] ?? 'Aufgaben konnten nicht geladen werden.') . '</p>';
    } elseif (empty($data['tasks'])) {
        echo '<p>Keine offenen Aufgaben.</p>';
    } else {
        echo '<div class="woc-tasks">';

        foreach ($data['tasks'] as $task) {
            echo '<div class="woc-task">';

           echo '<div class="woc-task-title">';
echo esc_html($task['title']);

$status = strtolower(trim($task['status'] ?? ''));

$status_class = '';
$status_label = '';

switch ($status) {
    case 'eingereicht':
        $status_class = 'pending';
        $status_label = 'unbestätigt';
        break;

    case 'offen':
        $status_class = 'open';
        $status_label = 'offen';
        break;

    case 'in arbeit':
    case 'in_arbeit':
        $status_class = 'progress';
        $status_label = 'in Arbeit';
        break;

    case 'erledigt':
        $status_class = 'done';
        $status_label = 'erledigt';
        break;
}

if ($status_label) {
    echo ' <span class="woc-task-status woc-task-status-' . esc_attr($status_class) . '">';
    echo esc_html($status_label);
    echo '</span>';
}

$priority = strtolower(trim($task['priority'] ?? ''));

$priority_class = '';
$priority_label = '';

switch ($priority) {
    case 'niedrig':
        $priority_class = 'low';
        $priority_label = 'niedrig';
        break;

    case 'normal':
        $priority_class = 'normal';
        $priority_label = 'normal';
        break;

    case 'dringend':
        $priority_class = 'urgent';
        $priority_label = 'dringend';
        break;
}

if ($priority_label) {
    echo ' <span class="woc-task-priority woc-task-priority-' . esc_attr($priority_class) . '">';
    echo esc_html($priority_label);
    echo '</span>';
}

echo '</div>';

            if (!empty($task['due_date'])) {
                echo '<div class="woc-task-date">';
                echo 'Fällig: ' . esc_html(date_i18n('d.m.Y', strtotime($task['due_date'])));
                echo '</div>';
            }

            if (!empty($task['description'])) {
                echo '<div class="woc-task-description">';
                echo esc_html($task['description']);
                echo '</div>';
            }

            echo '</div>';
        }

        echo '</div>';
    }

    echo '<hr>';
    echo '<h4>Neue Aufgabe erfassen</h4>';

    echo '<form method="post">';

    wp_nonce_field('woc_new_task');

    echo '<p>';
    echo '<input type="text" name="woc_task_title" class="widefat" placeholder="Titel der Aufgabe" required>';
    echo '</p>';

    echo '<p>';

    echo '<select name="woc_task_type" class="widefat">';

    echo '<option value="Bug">Bug</option>';
    echo '<option value="Anpassung">Anpassung</option>';
    echo '<option value="Content">Content</option>';

    echo '</select>';

    echo '</p>';

    echo '<p>';

    echo '<select name="woc_task_priority" class="widefat">';

    echo '<option value="Niedrig">Niedrig</option>';
    echo '<option value="Normal" selected>Normal</option>';
    echo '<option value="Dringend">Dringend</option>';

    echo '</select>';

    echo '</p>';
    echo '<p>';
    echo '<textarea name="woc_task_message" class="widefat" rows="4" placeholder="Beschreibung / Änderungswunsch"></textarea>';
    echo '</p>';

    submit_button('Aufgabe senden', 'primary', 'woc_new_task_submit', false);

    echo '</form>';
}

add_action('admin_init', 'woc_send_heartbeat');

function woc_send_heartbeat() {

    if (!is_admin()) {
        return;
    }

    $master_url = get_option('woc_master_url');
    $project_id = get_option('woc_project_id');
    $token      = get_option('woc_api_token');

    if (!$master_url || !$project_id || !$token) {
        return;
    }

    $last_ping = get_transient('woc_last_heartbeat');

    if ($last_ping) {
        return;
    }

    wp_remote_post(
        trailingslashit($master_url) . 'wp-json/website-ops/v1/heartbeat',
        [
            'timeout' => 10,
            'body' => [
                'project_id' => $project_id,
                'token'      => $token,
                'php_version' => PHP_VERSION,
                'wp_version'  => get_bloginfo('version'),
                'site_url'    => home_url(),
                'site_icon'   => get_site_icon_url(128),
            ],
        ]
    );

    set_transient(
        'woc_last_heartbeat',
        true,
        15 * MINUTE_IN_SECONDS
    );
}