<?php
/**
 * Plugin Name: Duo Feedback System
 * Plugin URI: https://duo.studio
 * Description: System zbierania feedbacku od klientow po zakonczeniu projektu
 * Version: 1.0.0
 * Author: Duo Team
 * Author URI: https://duo.studio
 * Text Domain: duo-feedback
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('DUO_FEEDBACK_VERSION', '1.0.0');
define('DUO_FEEDBACK_PATH', plugin_dir_path(__FILE__));
define('DUO_FEEDBACK_URL', plugin_dir_url(__FILE__));
define('DUO_FEEDBACK_BASENAME', plugin_basename(__FILE__));

/**
 * Activation hook - create tables and flush rewrite rules
 */
function duo_feedback_activate() {
    require_once DUO_FEEDBACK_PATH . 'includes/class-activator.php';
    Duo_Feedback_Activator::activate();
}
register_activation_hook(__FILE__, 'duo_feedback_activate');

/**
 * Deactivation hook
 */
function duo_feedback_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'duo_feedback_deactivate');

/**
 * Initialize plugin
 */
function duo_feedback_init() {
    // Load text domain
    load_plugin_textdomain('duo-feedback', false, dirname(DUO_FEEDBACK_BASENAME) . '/languages');

    // Include required files
    require_once DUO_FEEDBACK_PATH . 'includes/class-db.php';
    require_once DUO_FEEDBACK_PATH . 'includes/class-email.php';
    require_once DUO_FEEDBACK_PATH . 'public/class-public.php';
    require_once DUO_FEEDBACK_PATH . 'public/class-rest-api.php';

    // Initialize public
    $public = new Duo_Feedback_Public();
    $public->init();

    // Initialize REST API
    $rest_api = new Duo_Feedback_REST_API();
    $rest_api->init();

    // Admin only
    if (is_admin()) {
        require_once DUO_FEEDBACK_PATH . 'admin/class-admin.php';
        $admin = new Duo_Feedback_Admin();
        $admin->init();
    }
}
add_action('plugins_loaded', 'duo_feedback_init');

/**
 * Add rewrite rules on init
 */
function duo_feedback_add_rewrite_rules() {
    add_rewrite_rule(
        '^feedback/([^/]+)/?$',
        'index.php?duo_feedback_project=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^feedback/?$',
        'index.php?duo_feedback_generic=1',
        'top'
    );
}
add_action('init', 'duo_feedback_add_rewrite_rules');

/**
 * Register query vars
 */
function duo_feedback_query_vars($vars) {
    $vars[] = 'duo_feedback_project';
    $vars[] = 'duo_feedback_generic';
    return $vars;
}
add_filter('query_vars', 'duo_feedback_query_vars');
