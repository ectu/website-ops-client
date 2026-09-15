<?php
if (!defined('ABSPATH')) { exit; }

function woc_update_snapshot() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $installed = get_plugins();
    $groups = [];
    foreach (['core', 'plugins', 'themes'] as $kind) {
        $cache = get_site_transient('update_' . $kind);
        $checked = is_object($cache) && isset($cache->last_checked) ? (int) $cache->last_checked : 0;
        $known = $checked > 0 && $checked <= time() + 300 && is_object($cache);
        $items = [];
        if ($kind === 'core') {
            $known = $known && isset($cache->updates) && is_array($cache->updates);
            if ($known) {
                foreach ($cache->updates as $offer) {
                    if (is_object($offer) && ($offer->response ?? '') === 'upgrade' && is_string($offer->current ?? null)) {
                        $items[] = ['name' => 'WordPress', 'installed' => get_bloginfo('version'), 'available' => $offer->current];
                        break; // Multiple locales/offers describe the same core update.
                    }
                }
            }
        } else {
            $known = $known && isset($cache->response, $cache->checked) && is_array($cache->response) && is_array($cache->checked);
            if ($known) {
                $versions = $kind === 'plugins' ? array_map(static function ($plugin) { return $plugin['Version']; }, $installed) : array_map(static function ($theme) { return $theme->get('Version'); }, wp_get_themes());
                foreach ($versions as $slug => $version) {
                    if (!isset($cache->checked[$slug]) || (string) $cache->checked[$slug] !== (string) $version) { $known = false; }
                }
                foreach ($cache->response as $slug => $offer) {
                    $offer = (array) $offer;
                    if (!is_string($offer['new_version'] ?? null)) { $known = false; continue; }
                    if ($kind === 'plugins') {
                        if (!isset($installed[$slug])) { continue; }
                        $name = $installed[$slug]['Name']; $version = $installed[$slug]['Version'];
                    } else {
                        $theme = wp_get_theme($slug);
                        if (!$theme->exists()) { continue; }
                        $name = $theme->get('Name'); $version = $theme->get('Version');
                    }
                    $items[] = ['name' => substr(sanitize_text_field($name), 0, 200), 'installed' => substr(sanitize_text_field($version), 0, 64), 'available' => substr(sanitize_text_field($offer['new_version']), 0, 64)];
                }
            }
        }
        $groups[$kind] = ['known' => $known, 'checked_at' => max(0, $checked), 'count' => count($items), 'items' => array_slice($items, 0, 100)];
    }
    return $groups;
}

// Read only the documented-in-source Pro 2.2.47/2.2.49 shapes. Never forward raw options:
// they can contain remote credentials, encryption passwords and filesystem paths.
function woc_backup_scope($types) {
    if (!is_array($types)) { return 'unknown'; }
    // Pro uses All for merged archives, which may contain only selected components.
    if (isset($types['All'])) { return 'unknown'; }
    if (isset($types['Database']) && count($types) === 1) { return 'database'; }
    return $types ? 'partial' : 'unknown';
}
function woc_backup_record($row, $storage) {
    if (!is_array($row) || ($row['backup']['result'] ?? '') !== 'success' || empty($row['backup']['files']) || !is_numeric($row['create_time'] ?? null)) { return null; }
    $time = (int) $row['create_time'];
    if ($time < 1 || $time > time() + 300) { return null; }
    return ['time' => $time, 'storage' => $storage, 'scope' => woc_backup_scope($row['backup_info']['types'] ?? []), 'time_kind' => 'started'];
}
function woc_capture_backup_event($id, $success) {
    if (!defined('WPVIVID_BACKUP_PRO_VERSION') || !class_exists('WPvivid_taskmanager')) { return; }
    $task = WPvivid_taskmanager::get_task($id);
    if (!is_array($task)) { return; }
    $stamp = (int) ($task['status']['task_end_time'] ?? time());
    if ($stamp < 1 || $stamp > time() + 300) { $stamp = time(); }
    update_option('woc_backup_attempt', ['time' => $stamp, 'status' => $success ? 'success' : 'failed'], false);
    // A backup-list entry supplies scope and storage; the event supplies completion time.
    if ($success) {
        $rows = get_option('wpvivid_backup_list', []);
        $record = is_array($rows) ? woc_backup_record($rows[$id] ?? null, 'local') : null;
        $remote = get_option('wpvivid_new_remote_list', []);
        foreach (is_array($remote) ? $remote : [] as $rows) {
            $candidate = is_array($rows) ? woc_backup_record($rows[$id] ?? null, 'remote') : null;
            if ($candidate) { $record = $candidate; break; }
        }
        if ($record) {
            $record['time'] = $stamp; $record['time_kind'] = 'completed';
            update_option('woc_backup_success', $record, false);
        }
    }
    delete_transient('woc_last_heartbeat');
}
add_action('wpvivid_handle_new_backup_succeed', static function ($id) { woc_capture_backup_event($id, true); }, 99);
add_action('wpvivid_handle_new_backup_failed', static function ($id) { woc_capture_backup_event($id, false); }, 99);

function woc_backup_snapshot() {
    $result = ['provider' => defined('WPVIVID_BACKUP_PRO_VERSION') ? 'wpvivid_pro' : 'unavailable', 'version' => defined('WPVIVID_BACKUP_PRO_VERSION') ? substr((string) WPVIVID_BACKUP_PRO_VERSION, 0, 64) : '', 'last_success' => null, 'last_attempt' => null, 'next_scheduled' => 0, 'interval' => 0];
    if ($result['provider'] === 'unavailable' || is_multisite()) { return $result; }
    $best = get_option('woc_backup_success', null);
    $local = get_option('wpvivid_backup_list', []);
    $remote = get_option('wpvivid_new_remote_list', []);
    $groups = [['rows' => $local, 'storage' => 'local']];
    foreach (is_array($remote) ? $remote : [] as $rows) { $groups[] = ['rows' => $rows, 'storage' => 'remote']; }
    foreach ($groups as $group) {
        foreach (is_array($group['rows']) ? $group['rows'] : [] as $row) {
            $candidate = woc_backup_record($row, $group['storage']);
            if ($candidate && (!$best || $candidate['time'] > $best['time'] || ($candidate['time'] === $best['time'] && $candidate['storage'] === 'remote'))) { $best = $candidate; }
        }
    }
    $result['last_success'] = is_array($best) ? $best : null;
    $result['last_attempt'] = get_option('woc_backup_attempt', null);
    $last = get_option('wpvivid_last_msg', []);
    if (is_array($last) && in_array($last['status']['str'] ?? '', ['completed', 'error'], true)) {
        $stamp = (int) ($last['status']['task_end_time'] ?? $last['status']['start_time'] ?? 0);
        if ($stamp > 0 && $stamp <= time() + 300 && $stamp > ($result['last_attempt']['time'] ?? 0)) {
            $result['last_attempt'] = ['time' => $stamp, 'status' => $last['status']['str'] === 'error' ? 'failed' : 'success'];
        }
    }
    $schedules = get_option('wpvivid_schedule_addon_setting', []);
    $active = [];
    foreach (is_array($schedules) ? $schedules : [] as $schedule) {
        if (!is_array($schedule) || ($schedule['status'] ?? '') !== 'Active' || !is_string($schedule['id'] ?? null)) { continue; }
        $next = wp_next_scheduled($schedule['id'], [$schedule['id']]);
        if ($next && (!$result['next_scheduled'] || $next < $result['next_scheduled'])) { $result['next_scheduled'] = (int) $next; }
        $recurrences = wp_get_schedules();
        $active[] = (int) ($recurrences[$schedule['type'] ?? '']['interval'] ?? 0);
    }
    // Multiple/incremental schedules cannot safely be reduced to one interval.
    if (count($active) === 1 && !get_option('wpvivid_enable_incremental_schedules', false)) { $result['interval'] = max(0, min(YEAR_IN_SECONDS, $active[0])); }
    return $result;
}
function woc_maintenance_snapshot() { return ['version' => 1, 'updates' => woc_update_snapshot(), 'backup' => woc_backup_snapshot()]; }
