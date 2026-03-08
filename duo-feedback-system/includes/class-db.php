<?php
/**
 * Duo Feedback System - Database Operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class Duo_Feedback_DB {

    private $table_projects;
    private $table_feedback;

    public function __construct() {
        global $wpdb;
        $this->table_projects = $wpdb->prefix . 'duo_feedback_projects';
        $this->table_feedback = $wpdb->prefix . 'duo_feedback';
    }

    /**
     * Get project by slug
     */
    public function get_project_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_projects} WHERE project_slug = %s",
                $slug
            )
        );
    }

    /**
     * Get project by ID
     */
    public function get_project($id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_projects} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Get all projects
     */
    public function get_projects($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $where = "1=1";
        if ($args['status']) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'created_at DESC';
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_projects} WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d",
                $args['limit'],
                $args['offset']
            )
        );
    }

    /**
     * Insert project
     */
    public function insert_project($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_projects,
            array(
                'project_slug' => sanitize_title($data['project_slug']),
                'project_name' => sanitize_text_field($data['project_name']),
                'client_name' => sanitize_text_field($data['client_name']),
                'client_email' => sanitize_email($data['client_email']),
                'admin_email' => isset($data['admin_email']) ? sanitize_email($data['admin_email']) : null,
                'project_type' => isset($data['project_type']) ? sanitize_text_field($data['project_type']) : null,
                'status' => 'active',
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update project
     */
    public function update_project($id, $data) {
        global $wpdb;

        $update_data = array();
        $format = array();

        if (isset($data['project_name'])) {
            $update_data['project_name'] = sanitize_text_field($data['project_name']);
            $format[] = '%s';
        }
        if (isset($data['client_name'])) {
            $update_data['client_name'] = sanitize_text_field($data['client_name']);
            $format[] = '%s';
        }
        if (isset($data['client_email'])) {
            $update_data['client_email'] = sanitize_email($data['client_email']);
            $format[] = '%s';
        }
        if (isset($data['admin_email'])) {
            $update_data['admin_email'] = sanitize_email($data['admin_email']);
            $format[] = '%s';
        }
        if (isset($data['project_type'])) {
            $update_data['project_type'] = sanitize_text_field($data['project_type']);
            $format[] = '%s';
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }

        return $wpdb->update($this->table_projects, $update_data, array('id' => $id), $format, array('%d'));
    }

    /**
     * Delete project
     */
    public function delete_project($id) {
        global $wpdb;
        return $wpdb->delete($this->table_projects, array('id' => $id), array('%d'));
    }

    /**
     * Save feedback submission
     */
    public function save_feedback($data) {
        global $wpdb;

        $insert_data = array(
            'session_id' => sanitize_text_field($data['session_id']),
            'form_data' => wp_json_encode($data['form_data']),
            'overall_rating' => isset($data['form_data']['q6_rating']) ? intval($data['form_data']['q6_rating']) : null,
            'testimonial_permission' => isset($data['form_data']['q7_testimonial_permission']) ? sanitize_text_field($data['form_data']['q7_testimonial_permission']) : 'internal',
            'ip_hash' => $data['ip_hash'],
            'user_agent' => isset($data['user_agent']) ? substr(sanitize_text_field($data['user_agent']), 0, 500) : null,
            'status' => 'new',
        );
        $format = array('%s', '%s', '%d', '%s', '%s', '%s', '%s');

        // Add project_id if provided (NULL for universal survey)
        if (!empty($data['project_id'])) {
            $insert_data['project_id'] = intval($data['project_id']);
            $format[] = '%d';
        }

        $result = $wpdb->insert($this->table_feedback, $insert_data, $format);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get feedback by ID
     */
    public function get_feedback($id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT f.*, p.project_name, p.project_slug, p.client_name, p.client_email
                 FROM {$this->table_feedback} f
                 LEFT JOIN {$this->table_projects} p ON f.project_id = p.id
                 WHERE f.id = %d",
                $id
            )
        );
    }

    /**
     * Get feedbacks with filters
     */
    public function get_feedbacks($args = array()) {
        global $wpdb;

        $defaults = array(
            'project_id' => null,
            'status' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $where = "1=1";
        if ($args['project_id']) {
            $where .= $wpdb->prepare(" AND f.project_id = %d", $args['project_id']);
        }
        if ($args['status']) {
            $where .= $wpdb->prepare(" AND f.status = %s", $args['status']);
        }

        $orderby = sanitize_sql_orderby('f.' . $args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'f.created_at DESC';
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT f.*, p.project_name, p.client_name
                 FROM {$this->table_feedback} f
                 LEFT JOIN {$this->table_projects} p ON f.project_id = p.id
                 WHERE $where
                 ORDER BY $orderby
                 LIMIT %d OFFSET %d",
                $args['limit'],
                $args['offset']
            )
        );
    }

    /**
     * Update feedback status
     */
    public function update_feedback_status($id, $status) {
        global $wpdb;
        return $wpdb->update(
            $this->table_feedback,
            array('status' => sanitize_text_field($status)),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Count feedbacks for project
     */
    public function count_feedbacks($project_id = null) {
        global $wpdb;

        if ($project_id) {
            return $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_feedback} WHERE project_id = %d",
                    $project_id
                )
            );
        }

        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_feedback}");
    }

    /**
     * Check rate limit (max 1 submission per hour per IP per project)
     */
    public function check_rate_limit($project_id, $ip_hash) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_feedback}
                 WHERE project_id = %d AND ip_hash = %s
                 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $project_id,
                $ip_hash
            )
        );

        return intval($count) === 0;
    }

    /**
     * Check if session already submitted
     */
    public function session_exists($session_id, $project_id) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_feedback}
                 WHERE session_id = %s AND project_id = %d",
                $session_id,
                $project_id
            )
        );

        return intval($count) > 0;
    }

    /**
     * Check rate limit for universal survey (no project)
     */
    public function check_rate_limit_universal($ip_hash) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_feedback}
                 WHERE project_id IS NULL AND ip_hash = %s
                 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $ip_hash
            )
        );

        return intval($count) === 0;
    }

    /**
     * Check if session exists for universal survey
     */
    public function session_exists_universal($session_id) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_feedback}
                 WHERE session_id = %s AND project_id IS NULL",
                $session_id
            )
        );

        return intval($count) > 0;
    }
}
