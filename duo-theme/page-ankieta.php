<?php
/**
 * Template Name: Ankieta
 * Description: Social Media Confessions survey - 3 etapy
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pule pytań dla każdego etapu
$stages = array(
    1 => array(
        'title' => 'rozgrzewka',
        'questions' => array(
            'Co najbardziej męczy cię w byciu online?',
            'Opisz ostatnią rzecz, którą usunąłeś z internetu — i dlaczego.',
            'Co w twojej osobie jest najbardziej wyreżyserowane?',
        ),
    ),
    2 => array(
        'title' => 'głębiej',
        'questions' => array(
            'Kiedy ostatnio udawałeś kogoś w internecie — i przed kim?',
            'Co boisz się pokazać w internecie?',
            'Co byś powiedział, gdyby nikt cię nie oceniał?',
        ),
    ),
    3 => array(
        'title' => 'uderzenie',
        'questions' => array(
            'Co jest największym kłamstwem na twoim Instagramie?',
            'Co ci zabrał internet?',
            'Czym najbardziej różni się twoje życie offline od tego, co pokazujesz online?',
        ),
    ),
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Social Media Confessions - anonimowa ankieta o stosunku do social mediów">
    <title>Social Media Confessions - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('ankieta-page'); ?>>
<?php wp_body_open(); ?>

    <!-- Tło -->
    <div class="ankieta-background">
        <div class="ankieta-overlay"></div>
    </div>

    <!-- Główny kontener -->
    <main class="ankieta-container">

        <!-- Honeypot -->
        <input type="text" name="website" class="ankieta-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

        <!-- EKRAN: Intro -->
        <section class="ankieta-screen ankieta-intro active" data-screen="intro">
            <div class="ankieta-content">
                <h1 class="ankieta-title">Social Media Confessions</h1>
                <p class="ankieta-subtitle">
                    Wybierz jedno pytanie. Odpowiedz szczerze.<br>
                    Nikt nie zobaczy twojej twarzy.<br>
                    3 pytania, 2 minuty, pełna anonimowość.
                </p>
                <button type="button" class="ankieta-btn ankieta-btn-primary" data-action="start">
                    Zaczynam
                </button>
            </div>
            <div class="ankieta-logo-small">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.svg" alt="Duo">
            </div>
        </section>

        <!-- EKRANY: Etapy 1-3 -->
        <?php foreach ($stages as $stage_num => $stage) : ?>
        <section class="ankieta-screen ankieta-stage" data-screen="stage" data-stage="<?php echo $stage_num; ?>">
            <div class="ankieta-stage-header">
                <span class="ankieta-stage-number"><?php echo $stage_num; ?> / 3</span>
            </div>

            <div class="ankieta-content ankieta-stage-content">
                <!-- Stan 1: Wybór pytania -->
                <div class="ankieta-stage-select">
                    <p class="ankieta-stage-instruction">Wybierz pytanie, na które chcesz odpowiedzieć:</p>
                    <div class="ankieta-boxes">
                        <?php foreach ($stage['questions'] as $q_index => $question) : ?>
                        <button type="button" class="ankieta-question-box" data-question-index="<?php echo $q_index; ?>">
                            <span class="ankieta-box-text"><?php echo esc_html($question); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Stan 2: Odpowiedź (ukryty na start) -->
                <div class="ankieta-stage-answer" style="display: none;">
                    <h2 class="ankieta-selected-question"></h2>
                    <div class="ankieta-textarea-wrapper">
                        <textarea
                            class="ankieta-textarea"
                            placeholder="Twoja odpowiedź..."
                            maxlength="1000"
                            rows="4"
                        ></textarea>
                        <div class="ankieta-char-count">
                            <span class="ankieta-char-current">0</span> / 1000
                        </div>
                    </div>
                </div>
            </div>

            <div class="ankieta-actions">
                <button type="button" class="ankieta-btn ankieta-btn-primary ankieta-btn-next" data-action="next" disabled>
                    Dalej →
                </button>
            </div>
        </section>
        <?php endforeach; ?>

        <!-- EKRAN: Podziękowanie -->
        <section class="ankieta-screen ankieta-thankyou" data-screen="thankyou">
            <div class="ankieta-content">
                <h2 class="ankieta-title">Dzięki za szczerość.</h2>
                <p class="ankieta-thankyou-text">
                    Twoje odpowiedzi są w pełni anonimowe. Wykorzystamy je w projekcie kreatywnym,
                    który bada stosunek ludzi do social mediów — jak się w nich kreujemy,
                    co ukrywamy i co nas w tym wszystkim najbardziej męczy.
                </p>

                <div class="ankieta-divider"></div>

                <p class="ankieta-cta-text">Chcesz dowiedzieć się więcej o tym, co robimy?</p>

                <div class="ankieta-duo-info">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.svg" alt="Duo" class="ankieta-duo-logo">
                    <p class="ankieta-duo-desc">
                        Duo. — tworzymy wizerunki artystyczne dla twórców i kampanie dla marek,
                        które stawiają na autentyczność.
                    </p>
                </div>

                <div class="ankieta-links">
                    <a href="<?php echo home_url(); ?>" class="ankieta-link">Strona główna</a>
                    <a href="https://instagram.com/duo" target="_blank" rel="noopener noreferrer" class="ankieta-link">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="ankieta-link-icon">
                            <rect x="2" y="2" width="20" height="20" rx="5" stroke="currentColor" stroke-width="1.5"/>
                            <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5"/>
                            <circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>
                        </svg>
                        Instagram
                    </a>
                </div>
            </div>
        </section>

        <!-- Progress bar -->
        <div class="ankieta-progress">
            <div class="ankieta-progress-bar">
                <div class="ankieta-progress-fill" style="width: 0%"></div>
            </div>
            <span class="ankieta-progress-text">0%</span>
        </div>

    </main>

    <?php wp_footer(); ?>
</body>
</html>
