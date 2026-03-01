<?php
/**
 * Template Name: Ankieta
 * Description: Social Media Confessions survey
 */

if (!defined('ABSPATH')) {
    exit;
}
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
<body class="ankieta-page">

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
                    Wyobraź sobie, że nikt nie widzi twojej twarzy.<br>
                    Odpowiadasz szczerze.<br>
                    8 pytań, 2 minuty, pełna anonimowość.
                </p>
                <button type="button" class="ankieta-btn ankieta-btn-primary" data-action="start">
                    Zaczynam
                </button>
            </div>
            <div class="ankieta-logo-small">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.svg" alt="Duo">
            </div>
        </section>

        <!-- EKRAN: Pytania (1-8) -->
        <?php
        $questions = array(
            'Co najbardziej męczy cię w byciu online?',
            'Opisz ostatnią rzecz, którą usunąłeś z internetu — i dlaczego.',
            'Co w twojej osobie jest najbardziej wyreżyserowane?',
            'Kiedy ostatnio udawałeś kogoś w internecie — i przed kim?',
            'Co boisz się pokazać w internecie?',
            'Co byś powiedział, gdyby nikt cię nie oceniał?',
            'Co jest największym kłamstwem na twoim Instagramie?',
            'Co ci zabrał internet?',
        );

        foreach ($questions as $index => $question) :
            $num = $index + 1;
        ?>
        <section class="ankieta-screen ankieta-question" data-screen="question" data-question="<?php echo $num; ?>">
            <div class="ankieta-question-header">
                <span class="ankieta-question-number"><?php echo $num; ?> / 8</span>
            </div>

            <div class="ankieta-content">
                <h2 class="ankieta-question-text"><?php echo esc_html($question); ?></h2>

                <div class="ankieta-textarea-wrapper">
                    <textarea
                        class="ankieta-textarea"
                        placeholder="Twoja odpowiedź..."
                        maxlength="1000"
                        rows="3"
                    ></textarea>
                    <div class="ankieta-char-count">
                        <span class="ankieta-char-current">0</span> / 1000
                    </div>
                </div>

                <div class="ankieta-rating">
                    <span class="ankieta-rating-label">Jak mocno trafia to pytanie?</span>
                    <div class="ankieta-stars" data-rating="0">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <button type="button" class="ankieta-star" data-value="<?php echo $i; ?>" aria-label="<?php echo $i; ?> gwiazdek">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="ankieta-actions">
                <button type="button" class="ankieta-btn ankieta-btn-ghost" data-action="skip">
                    Pomiń
                </button>
                <button type="button" class="ankieta-btn ankieta-btn-primary" data-action="next">
                    Dalej →
                </button>
            </div>
        </section>
        <?php endforeach; ?>

        <!-- EKRAN: Pytanie zamykające -->
        <section class="ankieta-screen ankieta-closing" data-screen="closing">
            <div class="ankieta-content">
                <h2 class="ankieta-question-text">Które z tych pytań było dla ciebie najtrudniejsze?</h2>

                <div class="ankieta-select-wrapper">
                    <select class="ankieta-select" id="hardest-question">
                        <option value="">Wybierz pytanie...</option>
                        <?php foreach ($questions as $index => $question) : ?>
                        <option value="<?php echo $index + 1; ?>"><?php echo esc_html($question); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="ankieta-actions">
                <button type="button" class="ankieta-btn ankieta-btn-primary ankieta-btn-submit" data-action="submit">
                    Wyślij
                </button>
            </div>
        </section>

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
