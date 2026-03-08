<?php
/**
 * Duo Feedback System - Public Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class Duo_Feedback_Public {

    private $db;

    public function __construct() {
        $this->db = new Duo_Feedback_DB();
    }

    /**
     * Initialize hooks
     */
    public function init() {
        add_action('template_redirect', array($this, 'template_redirect'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Handle template redirect for feedback URLs
     */
    public function template_redirect() {
        $project_slug = get_query_var('duo_feedback_project');
        $is_generic = get_query_var('duo_feedback_generic');

        if (!$project_slug && !$is_generic) {
            return;
        }

        // If specific project
        if ($project_slug) {
            $project = $this->db->get_project_by_slug($project_slug);

            if (!$project || $project->status !== 'active') {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return;
            }

            // Set global for template
            $GLOBALS['duo_feedback_project'] = $project;
        }

        // Load template
        include DUO_FEEDBACK_PATH . 'public/templates/feedback-form.php';
        exit;
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        $project_slug = get_query_var('duo_feedback_project');
        $is_generic = get_query_var('duo_feedback_generic');

        if (!$project_slug && !$is_generic) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'duo-feedback-form',
            DUO_FEEDBACK_URL . 'assets/css/feedback-form.css',
            array(),
            DUO_FEEDBACK_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'duo-feedback-form',
            DUO_FEEDBACK_URL . 'assets/js/feedback-form.js',
            array(),
            DUO_FEEDBACK_VERSION,
            true
        );

        // Localize script
        wp_localize_script('duo-feedback-form', 'duoFeedback', array(
            'restUrl' => rest_url('duo-feedback/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'projectSlug' => $project_slug ?: '',
        ));
    }

    /**
     * Get IP hash for rate limiting
     */
    public static function get_ip_hash() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (empty($ip)) {
            return '';
        }
        return hash('sha256', $ip . wp_salt('auth'));
    }
}
