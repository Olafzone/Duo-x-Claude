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
