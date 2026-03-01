<?php
/**
 * Duo Theme - Functions and definitions
 *
 * @package Duo_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ładowanie stylów motywu
 */
function duo_enqueue_styles() {
    wp_enqueue_style(
        'duo-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'duo_enqueue_styles');

/**
 * Rejestracja Custom Post Type dla leadów
 */
function duo_register_leads_cpt() {
    $labels = array(
        'name'               => 'Leady',
        'singular_name'      => 'Lead',
        'menu_name'          => 'Leady',
        'all_items'          => 'Wszystkie leady',
        'view_item'          => 'Zobacz lead',
        'search_items'       => 'Szukaj leadów',
        'not_found'          => 'Nie znaleziono leadów',
        'not_found_in_trash' => 'Brak leadów w koszu'
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-groups',
        'supports'            => array('title'),
        'capability_type'     => 'post',
        'has_archive'         => false,
        'exclude_from_search' => true,
    );

    register_post_type('duo_lead', $args);
}
add_action('init', 'duo_register_leads_cpt');

/**
 * Dodanie meta boxów dla leadów
 */
function duo_add_lead_meta_boxes() {
    add_meta_box(
        'duo_lead_details',
        'Szczegóły leada',
        'duo_lead_meta_box_callback',
        'duo_lead',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'duo_add_lead_meta_boxes');

/**
 * Callback dla meta boxa leadów
 */
function duo_lead_meta_box_callback($post) {
    $email = get_post_meta($post->ID, '_duo_lead_email', true);
    $date = get_post_meta($post->ID, '_duo_lead_date', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label>Email</label></th>
            <td><strong><?php echo esc_html($email); ?></strong></td>
        </tr>
        <tr>
            <th><label>Data zapisania</label></th>
            <td><?php echo esc_html($date); ?></td>
        </tr>
    </table>
    <?php
}

/**
 * Dodanie kolumn do listy leadów w adminie
 */
function duo_lead_columns($columns) {
    $columns = array(
        'cb'         => '<input type="checkbox" />',
        'title'      => 'Imię',
        'email'      => 'Email',
        'date'       => 'Data'
    );
    return $columns;
}
add_filter('manage_duo_lead_posts_columns', 'duo_lead_columns');

/**
 * Wypełnienie kolumn leadów
 */
function duo_lead_column_content($column, $post_id) {
    switch ($column) {
        case 'email':
            echo esc_html(get_post_meta($post_id, '_duo_lead_email', true));
            break;
    }
}
add_action('manage_duo_lead_posts_custom_column', 'duo_lead_column_content', 10, 2);

/**
 * Obsługa AJAX dla formularza leadów
 */
function duo_save_lead() {
    // Weryfikacja nonce
    if (!isset($_POST['duo_nonce']) || !wp_verify_nonce($_POST['duo_nonce'], 'duo_lead_form')) {
        wp_send_json_error(array('message' => 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.'));
    }

    // Walidacja danych
    $name = isset($_POST['lead_name']) ? sanitize_text_field($_POST['lead_name']) : '';
    $email = isset($_POST['lead_email']) ? sanitize_email($_POST['lead_email']) : '';

    if (empty($name)) {
        wp_send_json_error(array('message' => 'Proszę podać imię.'));
    }

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(array('message' => 'Proszę podać prawidłowy adres email.'));
    }

    // Sprawdzenie czy email już istnieje
    $existing = get_posts(array(
        'post_type'  => 'duo_lead',
        'meta_key'   => '_duo_lead_email',
        'meta_value' => $email,
        'posts_per_page' => 1
    ));

    if (!empty($existing)) {
        wp_send_json_error(array('message' => 'Ten adres email jest już zapisany.'));
    }

    // Zapisanie leada
    $post_id = wp_insert_post(array(
        'post_type'   => 'duo_lead',
        'post_title'  => $name,
        'post_status' => 'publish'
    ));

    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, '_duo_lead_email', $email);
        update_post_meta($post_id, '_duo_lead_date', current_time('mysql'));

        // Opcjonalnie: wysłanie powiadomienia email do admina
        duo_send_admin_notification($name, $email);

        wp_send_json_success(array('message' => 'Dziękujemy! Wkrótce się odezwiemy.'));
    } else {
        wp_send_json_error(array('message' => 'Wystąpił błąd. Spróbuj ponownie później.'));
    }
}
add_action('wp_ajax_duo_save_lead', 'duo_save_lead');
add_action('wp_ajax_nopriv_duo_save_lead', 'duo_save_lead');

/**
 * Wysłanie powiadomienia email do admina
 */
function duo_send_admin_notification($name, $email) {
    $admin_email = get_option('admin_email');
    $subject = 'Nowy lead na stronie Duo';
    $message = sprintf(
        "Nowy lead został zapisany na stronie:\n\nImię: %s\nEmail: %s\nData: %s",
        $name,
        $email,
        current_time('d.m.Y H:i')
    );

    wp_mail($admin_email, $subject, $message);
}

/**
 * Eksport leadów do CSV
 */
function duo_export_leads_csv() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['duo_export_leads']) || $_GET['duo_export_leads'] !== '1') {
        return;
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'duo_export_leads')) {
        wp_die('Błąd bezpieczeństwa');
    }

    $leads = get_posts(array(
        'post_type'      => 'duo_lead',
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ));

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=duo-leady-' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM dla UTF-8

    fputcsv($output, array('Imię', 'Email', 'Data'));

    foreach ($leads as $lead) {
        fputcsv($output, array(
            $lead->post_title,
            get_post_meta($lead->ID, '_duo_lead_email', true),
            get_post_meta($lead->ID, '_duo_lead_date', true)
        ));
    }

    fclose($output);
    exit;
}
add_action('admin_init', 'duo_export_leads_csv');

/**
 * Dodanie przycisku eksportu na stronie leadów
 */
function duo_add_export_button() {
    global $typenow;
    if ($typenow === 'duo_lead') {
        $export_url = wp_nonce_url(
            admin_url('edit.php?post_type=duo_lead&duo_export_leads=1'),
            'duo_export_leads'
        );
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.wrap h1').after('<a href="<?php echo esc_url($export_url); ?>" class="page-title-action">Eksportuj do CSV</a>');
        });
        </script>
        <?php
    }
}
add_action('admin_head', 'duo_add_export_button');

/**
 * Theme setup
 */
function duo_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'duo_theme_setup');

// ============================================================================
// ANKIETA - Social Media Confessions
// ============================================================================

/**
 * Tworzenie tabel dla ankiety przy aktywacji motywu
 */
function duo_survey_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_responses = $wpdb->prefix . 'duo_survey_responses';
    $table_hardest = $wpdb->prefix . 'duo_survey_hardest';

    $sql_responses = "CREATE TABLE $table_responses (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64) NOT NULL,
        question_number TINYINT UNSIGNED NOT NULL,
        question_text TEXT NOT NULL,
        answer_text TEXT,
        star_rating TINYINT UNSIGNED DEFAULT NULL,
        skipped TINYINT(1) DEFAULT 0,
        time_spent_ms INT UNSIGNED DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_hash VARCHAR(64) DEFAULT NULL,
        INDEX idx_session (session_id),
        INDEX idx_created (created_at),
        INDEX idx_ip_hash (ip_hash)
    ) $charset_collate;";

    $sql_hardest = "CREATE TABLE $table_hardest (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64) NOT NULL UNIQUE,
        hardest_question_number TINYINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_responses);
    dbDelta($sql_hardest);
}
add_action('after_switch_theme', 'duo_survey_create_tables');

/**
 * Pomocnicza funkcja - hash IP z solą
 */
function duo_get_ip_hash() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (empty($ip)) return '';
    return hash('sha256', $ip . wp_salt('auth'));
}

/**
 * Sprawdzenie rate limit - max 1 ankieta na IP na godzinę
 */
function duo_survey_check_rate_limit($ip_hash) {
    global $wpdb;
    $table = $wpdb->prefix . 'duo_survey_hardest';

    // Sprawdź czy ten IP hash ma ukończoną ankietę w ostatniej godzinie
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT h.session_id) FROM $table h
         INNER JOIN {$wpdb->prefix}duo_survey_responses r ON h.session_id = r.session_id
         WHERE r.ip_hash = %s AND h.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        $ip_hash
    ));

    return intval($count) === 0;
}

/**
 * AJAX: Zapis pojedynczej odpowiedzi
 */
function duo_survey_save_response() {
    // Weryfikacja nonce
    if (!isset($_POST['survey_nonce']) || !wp_verify_nonce($_POST['survey_nonce'], 'duo_survey')) {
        wp_send_json_error(array('message' => 'Błąd bezpieczeństwa.'));
    }

    // Honeypot check
    if (!empty($_POST['website'])) {
        // Bot detected - cichy success
        wp_send_json_success(array('message' => 'OK'));
    }

    // Pobierz i waliduj dane
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    $question_number = isset($_POST['question_number']) ? intval($_POST['question_number']) : 0;
    $question_text = isset($_POST['question_text']) ? sanitize_textarea_field($_POST['question_text']) : '';
    $answer_text = isset($_POST['answer_text']) ? sanitize_textarea_field($_POST['answer_text']) : '';
    $star_rating = isset($_POST['star_rating']) ? intval($_POST['star_rating']) : null;
    $skipped = isset($_POST['skipped']) && $_POST['skipped'] === '1' ? 1 : 0;
    $time_spent_ms = isset($_POST['time_spent_ms']) ? intval($_POST['time_spent_ms']) : 0;

    // Walidacja podstawowa
    if (empty($session_id) || strlen($session_id) < 16 || strlen($session_id) > 64) {
        wp_send_json_error(array('message' => 'Nieprawidłowa sesja.'));
    }

    if ($question_number < 1 || $question_number > 8) {
        wp_send_json_error(array('message' => 'Nieprawidłowe pytanie.'));
    }

    // Minimum time check (3 sekundy, chyba że skip)
    if (!$skipped && $time_spent_ms < 3000 && !empty($answer_text)) {
        // Zbyt szybko - potencjalny bot, ale zapisz z flagą
        // Możemy później filtrować te odpowiedzi
    }

    // Sanityzacja star_rating
    if ($star_rating !== null && ($star_rating < 1 || $star_rating > 5)) {
        $star_rating = null;
    }

    // Ogranicz długość odpowiedzi
    $answer_text = mb_substr($answer_text, 0, 1000);

    $ip_hash = duo_get_ip_hash();

    global $wpdb;
    $table = $wpdb->prefix . 'duo_survey_responses';

    // Sprawdź czy odpowiedź na to pytanie już istnieje dla tej sesji
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE session_id = %s AND question_number = %d",
        $session_id,
        $question_number
    ));

    if ($existing) {
        // Update istniejącej odpowiedzi
        $result = $wpdb->update(
            $table,
            array(
                'answer_text' => $answer_text,
                'star_rating' => $star_rating,
                'skipped' => $skipped,
                'time_spent_ms' => $time_spent_ms,
            ),
            array('id' => $existing),
            array('%s', '%d', '%d', '%d'),
            array('%d')
        );
    } else {
        // Insert nowej odpowiedzi
        $result = $wpdb->insert(
            $table,
            array(
                'session_id' => $session_id,
                'question_number' => $question_number,
                'question_text' => $question_text,
                'answer_text' => $answer_text,
                'star_rating' => $star_rating,
                'skipped' => $skipped,
                'time_spent_ms' => $time_spent_ms,
                'ip_hash' => $ip_hash,
            ),
            array('%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s')
        );
    }

    if ($result !== false) {
        wp_send_json_success(array('message' => 'OK'));
    } else {
        wp_send_json_error(array('message' => 'Błąd zapisu.'));
    }
}
add_action('wp_ajax_duo_survey_save', 'duo_survey_save_response');
add_action('wp_ajax_nopriv_duo_survey_save', 'duo_survey_save_response');

/**
 * AJAX: Zapis najtrudniejszego pytania (zakończenie ankiety)
 */
function duo_survey_complete() {
    // Weryfikacja nonce
    if (!isset($_POST['survey_nonce']) || !wp_verify_nonce($_POST['survey_nonce'], 'duo_survey')) {
        wp_send_json_error(array('message' => 'Błąd bezpieczeństwa.'));
    }

    // Honeypot check
    if (!empty($_POST['website'])) {
        wp_send_json_success(array('message' => 'OK'));
    }

    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    $hardest_question = isset($_POST['hardest_question']) ? intval($_POST['hardest_question']) : 0;

    if (empty($session_id) || strlen($session_id) < 16) {
        wp_send_json_error(array('message' => 'Nieprawidłowa sesja.'));
    }

    if ($hardest_question < 1 || $hardest_question > 8) {
        wp_send_json_error(array('message' => 'Nieprawidłowy wybór.'));
    }

    // Rate limit check
    $ip_hash = duo_get_ip_hash();
    if (!duo_survey_check_rate_limit($ip_hash)) {
        // Nie informuj użytkownika - po prostu nie zapisuj ponownie
        wp_send_json_success(array('message' => 'OK'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'duo_survey_hardest';

    // Sprawdź czy ta sesja już ma wpis
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE session_id = %s",
        $session_id
    ));

    if (!$existing) {
        $wpdb->insert(
            $table,
            array(
                'session_id' => $session_id,
                'hardest_question_number' => $hardest_question,
            ),
            array('%s', '%d')
        );
    }

    wp_send_json_success(array('message' => 'OK'));
}
add_action('wp_ajax_duo_survey_complete', 'duo_survey_complete');
add_action('wp_ajax_nopriv_duo_survey_complete', 'duo_survey_complete');

/**
 * Ładowanie skryptów dla strony ankiety
 */
function duo_survey_enqueue_scripts() {
    if (is_page_template('page-ankieta.php')) {
        wp_enqueue_script(
            'duo-ankieta',
            get_template_directory_uri() . '/assets/js/ankieta.js',
            array(),
            '1.0.0',
            true
        );

        wp_localize_script('duo-ankieta', 'duoSurvey', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('duo_survey'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'duo_survey_enqueue_scripts');

