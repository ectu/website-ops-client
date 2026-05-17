<?php
/**
 * Plugin Name: Website Ops Client
 * Description: Verbindet WordPress-Websites mit dem Website Ops Master Dashboard. Kunden können Aufgaben erfassen, Status verfolgen und Änderungen zentral verwalten.
 * Version: 1.0.6
 * Author: Symforma
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WOC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WOC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WOC_PLUGIN_PATH . 'includes/api.php';
require_once WOC_PLUGIN_PATH . 'includes/settings.php';
require_once WOC_PLUGIN_PATH . 'includes/dashboard-widget.php';

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style(
        'woc-admin',
        WOC_PLUGIN_URL . 'assets/admin.css',
        [],
        '1.0.0'
    );
});

require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/ectu/website-ops-client/',
    __FILE__,
    'website-ops-client'
);

$updateChecker->setBranch('main');