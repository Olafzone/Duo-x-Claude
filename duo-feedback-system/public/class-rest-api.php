<?php
/**
 * Duo Feedback System - REST API Endpoint
 */

if (!defined('ABSPATH')) {
    exit;
}

class Duo_Feedback_REST_API {

    private $db;
    private $email;

    public function __construct() {
        $this->db = new Duo_Feedback_DB();
        $this->email = new Duo_Feedback_Email();
    }

    /**
     * Initialize REST API
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        register_rest_route('duo-feedback/v1', '/submit', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_submission'),
            'permission_callback' => '__return_true',
            'args' => array(
                'project_slug' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'form_data' => array(
                    'required' => true,
                    'type' => 'object',
                ),
                'honeypot' => array(
                    'required' => false,
                    'type' => 'string',
                ),
            ),
        ));
    }

    /**
     * Handle form submission
     */
    public function handle_submission(WP_REST_Request $request) {
        $project_slug = $request->get_param('project_slug');
        $session_id = $request->get_param('session_id');
        $form_data = $request->get_param('form_data');
        $honeypot = $request->get_param('honeypot');

        // Honeypot check
        if (!empty($honeypot)) {
            // Bot detected - return fake success
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Dziekujemy za feedback!',
            ), 200);
        }

        // Get IP hash
        $ip_hash = Duo_Feedback_Public::get_ip_hash();

        // Handle project-specific or universal survey
        $project = null;
        $project_id = null;

        if (!empty($project_slug)) {
            $project = $this->db->get_project_by_slug($project_slug);
            if (!$project) {
                return new WP_Error(
                    'invalid_project',
                    'Projekt nie istnieje.',
                    array('status' => 404)
                );
            }

            if ($project->status !== 'active') {
                return new WP_Error(
                    'project_inactive',
                    'Formularz dla tego projektu jest nieaktywny.',
                    array('status' => 400)
                );
            }

            $project_id = $project->id;

            // Rate limiting (per project)
            if (!$this->db->check_rate_limit($project_id, $ip_hash)) {
                return new WP_Error(
                    'rate_limited',
                    'Prosimy poczekac przed kolejna proba.',
                    array('status' => 429)
                );
            }

            // Check duplicate session
            if ($this->db->session_exists($session_id, $project_id)) {
                return new WP_Error(
                    'duplicate_submission',
                    'Ta sesja juz zostala wyslana.',
                    array('status' => 400)
                );
            }
        } else {
            // Universal survey - rate limit by IP only
            if (!$this->db->check_rate_limit_universal($ip_hash)) {
                return new WP_Error(
                    'rate_limited',
                    'Prosimy poczekac przed kolejna proba.',
                    array('status' => 429)
                );
            }

            // Check duplicate session (universal)
            if ($this->db->session_exists_universal($session_id)) {
                return new WP_Error(
                    'duplicate_submission',
                    'Ta sesja juz zostala wyslana.',
                    array('status' => 400)
                );
            }
        }

        // Validate session ID format
        if (strlen($session_id) < 16 || strlen($session_id) > 64) {
            return new WP_Error(
                'invalid_session',
                'Nieprawidlowy identyfikator sesji.',
                array('status' => 400)
            );
        }

        // Sanitize form data
        $sanitized_data = $this->sanitize_form_data($form_data);

        // Save to database
        $submission_id = $this->db->save_feedback(array(
            'project_id' => $project_id,
            'session_id' => $session_id,
            'form_data' => $sanitized_data,
            'ip_hash' => $ip_hash,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ));

        if (!$submission_id) {
            return new WP_Error(
                'save_failed',
                'Nie udalo sie zapisac feedbacku.',
                array('status' => 500)
            );
        }

        // Send emails
        if ($project) {
            // Project-specific: send to client and admin
            $this->email->send_client_thankyou(
                $project->client_email,
                $project->client_name,
                $project->project_name
            );

            $admin_email = $project->admin_email ?: get_option('admin_email');
            $this->email->send_admin_notification(
                $admin_email,
                $sanitized_data,
                $project
            );
        } else {
            // Universal: send only to admin
            $this->email->send_admin_notification(
                get_option('admin_email'),
                $sanitized_data,
                null
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Dziekujemy za feedback!',
            'submission_id' => $submission_id,
        ), 200);
    }

    /**
     * Sanitize form data
     */
    private function sanitize_form_data($data) {
        $sanitized = array();

        // Text fields
        $text_fields = array(
            'q1_challenge',
            'q2_surprise',
            'q3_transformation',
            'q5_improvements',
            'q6_rating_reason',
            'q8_referral',
        );

        foreach ($text_fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = sanitize_textarea_field(substr($data[$field], 0, 1000));
            }
        }

        // Rating (1-10)
        if (isset($data['q6_rating'])) {
            $rating = intval($data['q6_rating']);
            $sanitized['q6_rating'] = max(1, min(10, $rating));
        }

        // Testimonial permission
        if (isset($data['q7_testimonial_permission'])) {
            $allowed = array('public', 'anonymous', 'internal');
            $sanitized['q7_testimonial_permission'] = in_array($data['q7_testimonial_permission'], $allowed)
                ? $data['q7_testimonial_permission']
                : 'internal';
        }

        // Testimonial name (when public selected)
        if (isset($data['testimonial_name'])) {
            $sanitized['testimonial_name'] = sanitize_text_field(substr($data['testimonial_name'], 0, 200));
        }

        // Checkbox arrays
        if (isset($data['q4_what_worked']) && is_array($data['q4_what_worked'])) {
            $sanitized['q4_what_worked'] = array_map('sanitize_text_field', $data['q4_what_worked']);
        }

        return $sanitized;
    }
}
