<?php
/**
 * Duo Feedback System - Email Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class Duo_Feedback_Email {

    /**
     * Send thank you email to client
     */
    public function send_client_thankyou($client_email, $client_name, $project_name) {
        $subject = 'Dziekujemy za feedback!';

        ob_start();
        include DUO_FEEDBACK_PATH . 'templates/email/client-thankyou.php';
        $message = ob_get_clean();

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Duo. <hello@duo.studio>',
        );

        return wp_mail($client_email, $subject, $message, $headers);
    }

    /**
     * Send notification to admin
     */
    public function send_admin_notification($admin_email, $feedback_data, $project) {
        $subject = sprintf('Nowy feedback: %s', $project->project_name);

        ob_start();
        include DUO_FEEDBACK_PATH . 'templates/email/admin-notification.php';
        $message = ob_get_clean();

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Duo Feedback <hello@duo.studio>',
        );

        return wp_mail($admin_email, $subject, $message, $headers);
    }
}
