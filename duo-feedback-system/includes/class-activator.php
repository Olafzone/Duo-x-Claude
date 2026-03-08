<?php
/**
 * Duo Feedback System - Activator
 * Handles plugin activation: creates database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class Duo_Feedback_Activator {

    /**
     * Run activation tasks
     */
    public static function activate() {
        self::create_tables();
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table: Projects
        $table_projects = $wpdb->prefix . 'duo_feedback_projects';
        $sql_projects = "CREATE TABLE $table_projects (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_slug VARCHAR(255) NOT NULL,
            project_name VARCHAR(255) NOT NULL,
            client_name VARCHAR(255) NOT NULL,
            client_email VARCHAR(255) NOT NULL,
            admin_email VARCHAR(255) DEFAULT NULL,
            project_type VARCHAR(100) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_slug (project_slug),
            KEY idx_status (status),
            KEY idx_created (created_at)
        ) $charset_collate;";

        // Table: Feedback responses (project_id NULL = universal survey)
        $table_feedback = $wpdb->prefix . 'duo_feedback';
        $sql_feedback = "CREATE TABLE $table_feedback (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id BIGINT UNSIGNED DEFAULT NULL,
            session_id VARCHAR(64) NOT NULL,
            form_data LONGTEXT NOT NULL,
            overall_rating TINYINT UNSIGNED DEFAULT NULL,
            testimonial_permission VARCHAR(50) DEFAULT 'internal',
            ip_hash VARCHAR(64) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'new',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_project (project_id),
            KEY idx_session (session_id),
            KEY idx_status (status),
            KEY idx_rating (overall_rating),
            KEY idx_created (created_at),
            KEY idx_ip_hash (ip_hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_projects);
        dbDelta($sql_feedback);

        // Store version
        update_option('duo_feedback_db_version', DUO_FEEDBACK_VERSION);
    }

    /**
     * Add rewrite rules
     */
    private static function add_rewrite_rules() {
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
}
