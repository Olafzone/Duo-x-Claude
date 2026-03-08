<?php
/**
 * Duo Feedback System - Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Duo_Feedback_Admin {

    private $db;

    public function __construct() {
        $this->db = new Duo_Feedback_DB();
    }

    /**
     * Initialize admin hooks
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Duo Feedback',
            'Duo Feedback',
            'manage_options',
            'duo-feedback',
            array($this, 'render_submissions_page'),
            'dashicons-feedback',
            30
        );

        add_submenu_page(
            'duo-feedback',
            'Odpowiedzi',
            'Odpowiedzi',
            'manage_options',
            'duo-feedback',
            array($this, 'render_submissions_page')
        );

        add_submenu_page(
            'duo-feedback',
            'Projekty',
            'Projekty',
            'manage_options',
            'duo-feedback-projects',
            array($this, 'render_projects_page')
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'duo-feedback') === false) {
            return;
        }

        wp_enqueue_style(
            'duo-feedback-admin',
            DUO_FEEDBACK_URL . 'admin/css/admin.css',
            array(),
            DUO_FEEDBACK_VERSION
        );
    }

    /**
     * Handle admin actions
     */
    public function handle_actions() {
        // Export CSV
        if (isset($_GET['duo_feedback_export']) && $_GET['duo_feedback_export'] === '1') {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'duo_feedback_export')) {
                wp_die('Security check failed');
            }
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }
            $this->export_csv();
        }

        // Add project
        if (isset($_POST['duo_feedback_add_project'])) {
            if (!wp_verify_nonce($_POST['_wpnonce'], 'duo_feedback_add_project')) {
                wp_die('Security check failed');
            }
            $this->handle_add_project();
        }

        // Delete project
        if (isset($_GET['duo_feedback_delete_project'])) {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'duo_feedback_delete_project')) {
                wp_die('Security check failed');
            }
            $project_id = intval($_GET['duo_feedback_delete_project']);
            $this->db->delete_project($project_id);
            wp_redirect(admin_url('admin.php?page=duo-feedback-projects&deleted=1'));
            exit;
        }
    }

    /**
     * Handle add project form
     */
    private function handle_add_project() {
        $data = array(
            'project_name' => sanitize_text_field($_POST['project_name']),
            'project_slug' => sanitize_title($_POST['project_slug']),
            'client_name' => sanitize_text_field($_POST['client_name']),
            'client_email' => sanitize_email($_POST['client_email']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'project_type' => sanitize_text_field($_POST['project_type']),
        );

        if (empty($data['project_name']) || empty($data['project_slug']) || empty($data['client_email'])) {
            wp_redirect(admin_url('admin.php?page=duo-feedback-projects&error=missing_fields'));
            exit;
        }

        $result = $this->db->insert_project($data);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=duo-feedback-projects&added=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=duo-feedback-projects&error=insert_failed'));
        }
        exit;
    }

    /**
     * Render submissions page
     */
    public function render_submissions_page() {
        // Check for single submission view
        if (isset($_GET['submission_id'])) {
            $this->render_single_submission(intval($_GET['submission_id']));
            return;
        }

        $feedbacks = $this->db->get_feedbacks(array('limit' => 100));
        $export_url = wp_nonce_url(
            admin_url('admin.php?page=duo-feedback&duo_feedback_export=1'),
            'duo_feedback_export'
        );
        ?>
        <div class="wrap">
            <h1>
                Duo Feedback - Odpowiedzi
                <a href="<?php echo esc_url($export_url); ?>" class="page-title-action">Eksportuj do CSV</a>
            </h1>

            <?php if (empty($feedbacks)): ?>
                <p>Brak odpowiedzi.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Projekt</th>
                            <th>Klient</th>
                            <th>Ocena</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $feedback): ?>
                        <tr>
                            <td><?php echo esc_html($feedback->id); ?></td>
                            <td><?php echo esc_html($feedback->project_name); ?></td>
                            <td><?php echo esc_html($feedback->client_name); ?></td>
                            <td>
                                <?php if ($feedback->overall_rating): ?>
                                    <strong><?php echo esc_html($feedback->overall_rating); ?></strong>/10
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-<?php echo esc_attr($feedback->status); ?>">
                                    <?php echo esc_html($feedback->status); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($feedback->created_at))); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=duo-feedback&submission_id=' . $feedback->id); ?>">
                                    Zobacz
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render single submission
     */
    private function render_single_submission($id) {
        $feedback = $this->db->get_feedback($id);

        if (!$feedback) {
            echo '<div class="wrap"><h1>Nie znaleziono</h1></div>';
            return;
        }

        $form_data = json_decode($feedback->form_data, true);
        ?>
        <div class="wrap">
            <h1>
                <a href="<?php echo admin_url('admin.php?page=duo-feedback'); ?>">&larr;</a>
                Feedback #<?php echo esc_html($id); ?>
            </h1>

            <div class="duo-feedback-detail">
                <table class="form-table">
                    <tr>
                        <th>Projekt</th>
                        <td><?php echo esc_html($feedback->project_name); ?></td>
                    </tr>
                    <tr>
                        <th>Klient</th>
                        <td><?php echo esc_html($feedback->client_name); ?> (<?php echo esc_html($feedback->client_email); ?>)</td>
                    </tr>
                    <tr>
                        <th>Data</th>
                        <td><?php echo esc_html($feedback->created_at); ?></td>
                    </tr>
                    <tr>
                        <th>Ocena</th>
                        <td><strong><?php echo esc_html($feedback->overall_rating ?: '-'); ?></strong>/10</td>
                    </tr>
                    <tr>
                        <th>Testimonial</th>
                        <td><?php echo esc_html($feedback->testimonial_permission); ?></td>
                    </tr>
                </table>

                <h2>Odpowiedzi</h2>

                <?php if (isset($form_data['q1_challenge'])): ?>
                <div class="response-item">
                    <h4>Co bylo glownym wyzwaniem przed startem wspolpracy?</h4>
                    <p><?php echo nl2br(esc_html($form_data['q1_challenge'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (isset($form_data['q2_surprise'])): ?>
                <div class="response-item">
                    <h4>Co najbardziej Cie zaskoczylo w procesie?</h4>
                    <p><?php echo nl2br(esc_html($form_data['q2_surprise'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (isset($form_data['q3_transformation'])): ?>
                <div class="response-item">
                    <h4>Jak zmienilo sie Twoje podejscie do komunikacji/marki po projekcie?</h4>
                    <p><?php echo nl2br(esc_html($form_data['q3_transformation'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (isset($form_data['q4_what_worked']) && is_array($form_data['q4_what_worked'])): ?>
                <div class="response-item">
                    <h4>Co dzialalo mega dobrze?</h4>
                    <ul>
                        <?php foreach ($form_data['q4_what_worked'] as $item): ?>
                        <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($form_data['q5_improvements'])): ?>
                <div class="response-item">
                    <h4>Co moglismy zrobic lepiej?</h4>
                    <p><?php echo nl2br(esc_html($form_data['q5_improvements'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($form_data['q6_rating_reason'])): ?>
                <div class="response-item">
                    <h4>Dlaczego ta ocena?</h4>
                    <p><?php echo nl2br(esc_html($form_data['q6_rating_reason'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($form_data['q8_referral'])): ?>
                <div class="response-item" style="background: #fff8e1; padding: 15px; border-radius: 4px;">
                    <h4>Potencjalny referral</h4>
                    <p><?php echo nl2br(esc_html($form_data['q8_referral'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (isset($form_data['q9_stay_in_touch']) && is_array($form_data['q9_stay_in_touch'])): ?>
                <div class="response-item">
                    <h4>Chce zostac w kontakcie</h4>
                    <ul>
                        <?php foreach ($form_data['q9_stay_in_touch'] as $item): ?>
                        <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render projects page
     */
    public function render_projects_page() {
        $projects = $this->db->get_projects();
        ?>
        <div class="wrap">
            <h1>Duo Feedback - Projekty</h1>

            <?php if (isset($_GET['added'])): ?>
                <div class="notice notice-success"><p>Projekt dodany!</p></div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-success"><p>Projekt usuniety!</p></div>
            <?php endif; ?>

            <h2>Dodaj nowy projekt</h2>
            <form method="post" action="">
                <?php wp_nonce_field('duo_feedback_add_project'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="project_name">Nazwa projektu</label></th>
                        <td><input type="text" name="project_name" id="project_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="project_slug">Slug (URL)</label></th>
                        <td>
                            <input type="text" name="project_slug" id="project_slug" class="regular-text" required>
                            <p class="description">URL: <?php echo home_url('/feedback/'); ?><strong id="slug-preview">slug</strong>/</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="client_name">Imie klienta</label></th>
                        <td><input type="text" name="client_name" id="client_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="client_email">Email klienta</label></th>
                        <td><input type="email" name="client_email" id="client_email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="admin_email">Email admina (opcjonalnie)</label></th>
                        <td>
                            <input type="email" name="admin_email" id="admin_email" class="regular-text">
                            <p class="description">Domyslnie: <?php echo get_option('admin_email'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="project_type">Typ projektu</label></th>
                        <td>
                            <select name="project_type" id="project_type">
                                <option value="visual-identity">Visual Identity</option>
                                <option value="event-branding">Event Branding</option>
                                <option value="communication">Communication Campaign</option>
                                <option value="other">Inny</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="duo_feedback_add_project" class="button button-primary">
                        Dodaj projekt
                    </button>
                </p>
            </form>

            <h2>Istniejace projekty</h2>
            <?php if (empty($projects)): ?>
                <p>Brak projektow.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Slug</th>
                            <th>Klient</th>
                            <th>Odpowiedzi</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo esc_html($project->project_name); ?></td>
                            <td>
                                <a href="<?php echo home_url('/feedback/' . $project->project_slug . '/'); ?>" target="_blank">
                                    /feedback/<?php echo esc_html($project->project_slug); ?>/
                                </a>
                            </td>
                            <td><?php echo esc_html($project->client_name); ?></td>
                            <td><?php echo $this->db->count_feedbacks($project->id); ?></td>
                            <td><?php echo esc_html($project->status); ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=duo-feedback-projects&duo_feedback_delete_project=' . $project->id), 'duo_feedback_delete_project'); ?>"
                                   onclick="return confirm('Na pewno usunac?');"
                                   style="color: #a00;">
                                    Usun
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <script>
        document.getElementById('project_name').addEventListener('input', function(e) {
            var slug = e.target.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('project_slug').value = slug;
            document.getElementById('slug-preview').textContent = slug || 'slug';
        });
        </script>
        <?php
    }

    /**
     * Export CSV
     */
    private function export_csv() {
        $feedbacks = $this->db->get_feedbacks(array('limit' => 1000));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=duo-feedback-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        fputcsv($output, array(
            'ID',
            'Projekt',
            'Klient',
            'Email',
            'Data',
            'Ocena',
            'Wyzwanie (Q1)',
            'Zaskoczenie (Q2)',
            'Transformacja (Q3)',
            'Co dzialalo (Q4)',
            'Ulepszenia (Q5)',
            'Dlaczego ocena (Q6)',
            'Testimonial',
            'Referral (Q8)',
            'Kontakt (Q9)',
        ));

        // Rows
        foreach ($feedbacks as $feedback) {
            $form_data = json_decode($feedback->form_data, true);

            fputcsv($output, array(
                $feedback->id,
                $feedback->project_name,
                $feedback->client_name,
                $feedback->client_email,
                $feedback->created_at,
                $feedback->overall_rating,
                $form_data['q1_challenge'] ?? '',
                $form_data['q2_surprise'] ?? '',
                $form_data['q3_transformation'] ?? '',
                isset($form_data['q4_what_worked']) ? implode(', ', $form_data['q4_what_worked']) : '',
                $form_data['q5_improvements'] ?? '',
                $form_data['q6_rating_reason'] ?? '',
                $feedback->testimonial_permission,
                $form_data['q8_referral'] ?? '',
                isset($form_data['q9_stay_in_touch']) ? implode(', ', $form_data['q9_stay_in_touch']) : '',
            ));
        }

        fclose($output);
        exit;
    }
}
