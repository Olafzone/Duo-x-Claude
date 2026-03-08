<?php
/**
 * Duo Feedback Form Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$project = isset($GLOBALS['duo_feedback_project']) ? $GLOBALS['duo_feedback_project'] : null;
$client_name = $project ? $project->client_name : '';
$project_name = $project ? $project->project_name : '';
$project_slug = $project ? $project->project_slug : '';

// Questions configuration
$questions = array(
    array(
        'id' => 'q1_challenge',
        'type' => 'textarea',
        'question' => 'Co bylo glownym wyzwaniem przed startem wspolpracy?',
        'placeholder' => 'Np. Mialem muzyke, ale zero wizualnej spojnosci...',
        'required' => true,
    ),
    array(
        'id' => 'q2_surprise',
        'type' => 'textarea',
        'question' => 'Co najbardziej Cie zaskoczylo w procesie?',
        'placeholder' => 'Np. Nie spodziwalem sie ze research phase bedzie tak gleboka...',
        'required' => true,
    ),
    array(
        'id' => 'q3_transformation',
        'type' => 'textarea',
        'question' => 'Co zmienilo sie w Twoim podejsciu po zakonczeniu projektu?',
        'placeholder' => '',
        'required' => true,
    ),
    array(
        'id' => 'q4_what_worked',
        'type' => 'checkbox',
        'question' => 'Co dzialalo mega dobrze?',
        'options' => array(
            'komunikacja' => 'Komunikacja (szybkosc, clarity, dostepnosc)',
            'research' => 'Research phase (zrozumienie kontekstu, insighty)',
            'kreatywnosc' => 'Kreatywnosc (rozwiazania, pomysly)',
            'proces' => 'Proces (timeline, milestones, przejrzystosc)',
            'deliverables' => 'Deliverables (jakosc, attention to detail)',
        ),
        'required' => true,
    ),
    array(
        'id' => 'q5_improvements',
        'type' => 'textarea',
        'question' => 'Co moglismy zrobic lepiej?',
        'helper' => 'Brutal honesty welcome - chcemy rosnac.',
        'placeholder' => '',
        'required' => false,
    ),
    array(
        'id' => 'q6_rating',
        'type' => 'scale',
        'question' => 'Jak ocenilbys wspolprace ogolnie?',
        'min' => 1,
        'max' => 10,
        'required' => true,
    ),
    array(
        'id' => 'q6_rating_reason',
        'type' => 'textarea',
        'question' => 'Dlaczego ta ocena?',
        'placeholder' => '',
        'required' => false,
    ),
    array(
        'id' => 'q7_testimonial_permission',
        'type' => 'radio',
        'question' => 'Czy mozemy uzyc Twoich odpowiedzi jako referencje?',
        'options' => array(
            'public' => 'Tak, mozecie uzyc moich odpowiedzi publicznie (imie + projekt)',
            'anonymous' => 'Tak, ale anonimowo (bez imienia)',
            'internal' => 'Tylko wewnetrznie (feedback dla was, nie do publicznego uzycia)',
        ),
        'required' => true,
        'show_name_field' => true,
    ),
    array(
        'id' => 'q8_referral',
        'type' => 'textarea',
        'question' => 'Czy znasz kogos kto moglby potrzebowac podobnego wsparcia?',
        'helper' => 'Jesli znasz muzyka/artyste/organizacje ktorzy walcza z visual identity - chetnie ich poznamy. Zadnej presji :)',
        'placeholder' => '',
        'required' => false,
    ),
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Feedback form - <?php echo esc_attr($project_name); ?>">
    <title>Feedback - <?php echo esc_html($project_name ?: 'Duo.'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="duo-feedback-page">

    <!-- Background -->
    <div class="duo-feedback-background">
        <div class="duo-feedback-overlay"></div>
    </div>

    <!-- Main container -->
    <main class="duo-feedback-container" data-project-slug="<?php echo esc_attr($project_slug); ?>">

        <!-- Honeypot -->
        <input type="text" name="website" class="duo-feedback-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

        <!-- SCREEN: Intro -->
        <section class="duo-feedback-screen active" data-screen="intro">
            <div class="duo-feedback-content">
                <h1 class="duo-feedback-title">
                    <?php if ($client_name): ?>
                        Czesc, <?php echo esc_html($client_name); ?>!
                    <?php else: ?>
                        Czesc!
                    <?php endif; ?>
                </h1>
                <?php if ($project): ?>
                <p class="duo-feedback-subtitle">
                    Projekt <?php echo esc_html($project_name); ?> wlasnie wyladowal w swiecie.
                    Mamy nadzieje, ze daje Ci kompas do dalszych decyzji wizualnych.
                </p>
                <?php else: ?>
                <p class="duo-feedback-subtitle">
                    Dziekujemy za wspolprace. Mamy nadzieje, ze projekt daje Ci kompas do dalszych decyzji wizualnych.
                </p>
                <?php endif; ?>
                <p class="duo-feedback-subtitle">
                    Mamy dla Ciebie krotki formularz z pytaniami o wspolprace.<br>
                    Zajmie ~5 minut. Twoje odpowiedzi pomagaja nam rosnac.
                </p>
                <div class="duo-feedback-actions">
                    <button type="button" class="duo-feedback-btn duo-feedback-btn-primary" data-action="start">
                        Zaczynam
                    </button>
                </div>
            </div>
            <div class="duo-feedback-logo-small">
                <span>Duo.</span>
            </div>
        </section>

        <!-- SCREENS: Questions -->
        <?php foreach ($questions as $index => $q): ?>
        <section class="duo-feedback-screen" data-screen="question" data-question-id="<?php echo esc_attr($q['id']); ?>" data-question-index="<?php echo $index; ?>" data-required="<?php echo $q['required'] ? 'true' : 'false'; ?>">
            <div class="duo-feedback-question-header">
                <span class="duo-feedback-question-number"><?php echo ($index + 1); ?> / <?php echo count($questions); ?></span>
            </div>

            <div class="duo-feedback-content">
                <?php if ($q['type'] === 'textarea'): ?>
                    <h2 class="duo-feedback-question-text"><?php echo esc_html($q['question']); ?></h2>
                    <?php if (!empty($q['helper'])): ?>
                        <p class="duo-feedback-helper"><?php echo esc_html($q['helper']); ?></p>
                    <?php endif; ?>
                    <div class="duo-feedback-textarea-wrapper">
                        <textarea
                            class="duo-feedback-textarea"
                            placeholder="<?php echo esc_attr($q['placeholder'] ?? ''); ?>"
                            maxlength="1000"
                            rows="4"
                            data-field="<?php echo esc_attr($q['id']); ?>"
                        ></textarea>
                        <div class="duo-feedback-char-count">
                            <span class="current">0</span> / 1000
                        </div>
                    </div>

                <?php elseif ($q['type'] === 'checkbox'): ?>
                    <h2 class="duo-feedback-question-text"><?php echo esc_html($q['question']); ?></h2>
                    <div class="duo-feedback-options">
                        <?php foreach ($q['options'] as $value => $label): ?>
                        <label class="duo-feedback-option">
                            <input type="checkbox" name="<?php echo esc_attr($q['id']); ?>[]" value="<?php echo esc_attr($value); ?>">
                            <span class="duo-feedback-option-box"></span>
                            <span class="duo-feedback-option-label"><?php echo esc_html($label); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($q['type'] === 'radio'): ?>
                    <h2 class="duo-feedback-question-text"><?php echo esc_html($q['question']); ?></h2>
                    <div class="duo-feedback-options">
                        <?php foreach ($q['options'] as $value => $label): ?>
                        <label class="duo-feedback-option">
                            <input type="radio" name="<?php echo esc_attr($q['id']); ?>" value="<?php echo esc_attr($value); ?>">
                            <span class="duo-feedback-option-box"></span>
                            <span class="duo-feedback-option-label"><?php echo esc_html($label); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($q['show_name_field'])): ?>
                    <div class="duo-feedback-testimonial-name" style="display: none; margin-top: 20px;">
                        <input type="text" class="duo-feedback-name-input" placeholder="Twoje imie i nazwisko" data-field="testimonial_name">
                    </div>
                    <?php endif; ?>

                <?php elseif ($q['type'] === 'scale'): ?>
                    <h2 class="duo-feedback-question-text"><?php echo esc_html($q['question']); ?></h2>
                    <div class="duo-feedback-scale">
                        <?php for ($i = $q['min']; $i <= $q['max']; $i++): ?>
                        <button type="button" class="duo-feedback-scale-btn" data-value="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </button>
                        <?php endfor; ?>
                    </div>
                    <div class="duo-feedback-scale-labels">
                        <span>Slabo</span>
                        <span>Swietnie</span>
                    </div>

                <?php elseif ($q['type'] === 'combined'): ?>
                    <h2 class="duo-feedback-question-text"><?php echo esc_html($q['question']); ?></h2>
                    <?php foreach ($q['fields'] as $field): ?>
                        <div class="duo-feedback-combined-field">
                            <label class="duo-feedback-field-label"><?php echo esc_html($field['label']); ?></label>
                            <?php if (!empty($field['helper'])): ?>
                                <p class="duo-feedback-helper"><?php echo esc_html($field['helper']); ?></p>
                            <?php endif; ?>

                            <?php if ($field['type'] === 'textarea'): ?>
                                <div class="duo-feedback-textarea-wrapper">
                                    <textarea
                                        class="duo-feedback-textarea"
                                        maxlength="500"
                                        rows="3"
                                        data-field="<?php echo esc_attr($field['id']); ?>"
                                    ></textarea>
                                </div>
                            <?php elseif ($field['type'] === 'checkbox'): ?>
                                <div class="duo-feedback-options">
                                    <?php foreach ($field['options'] as $value => $label): ?>
                                    <label class="duo-feedback-option">
                                        <input type="checkbox" name="<?php echo esc_attr($field['id']); ?>[]" value="<?php echo esc_attr($value); ?>">
                                        <span class="duo-feedback-option-box"></span>
                                        <span class="duo-feedback-option-label"><?php echo esc_html($label); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="duo-feedback-actions">
                <?php if ($index > 0): ?>
                <button type="button" class="duo-feedback-btn duo-feedback-btn-back" data-action="back">
                    &larr; Wstecz
                </button>
                <?php endif; ?>
                <button type="button" class="duo-feedback-btn duo-feedback-btn-primary" data-action="next" <?php echo $q['required'] ? 'disabled' : ''; ?>>
                    <?php echo ($index === count($questions) - 1) ? 'Wyslij' : 'Dalej &rarr;'; ?>
                </button>
            </div>
        </section>
        <?php endforeach; ?>

        <!-- SCREEN: Thank You -->
        <section class="duo-feedback-screen" data-screen="thankyou">
            <div class="duo-feedback-content">
                <h1 class="duo-feedback-title">Dzieki za szczerosc.</h1>
                <p class="duo-feedback-thankyou-text">
                    Twoje odpowiedzi sa w pelni anonimowe. Wykorzystamy je w projekcie kreatywnym,
                    ktory bada stosunek ludzi do tworzenia marek - jak sie w nich kreujemy,
                    co ukrywamy i co nas w tym wszystkim najbardziej meczy.
                </p>

                <div class="duo-feedback-divider"></div>

                <p class="duo-feedback-cta-text">Chcesz dowiedziec sie wiecej o tym, co robimy?</p>

                <div class="duo-feedback-duo-info">
                    <span class="duo-feedback-duo-logo">Duo.</span>
                    <p class="duo-feedback-duo-desc">
                        Duo. - tworzymy wizerunki artystyczne dla tworcow i kampanie dla marek,
                        ktore stawiaja na autentycznosc.
                    </p>
                </div>

                <div class="duo-feedback-links">
                    <a href="<?php echo esc_url(home_url()); ?>" class="duo-feedback-link">Strona glowna</a>
                    <a href="https://instagram.com/duo" target="_blank" rel="noopener noreferrer" class="duo-feedback-link">
                        Instagram
                    </a>
                </div>
            </div>
        </section>

        <!-- Progress bar -->
        <div class="duo-feedback-progress">
            <div class="duo-feedback-progress-bar">
                <div class="duo-feedback-progress-fill" style="width: 0%"></div>
            </div>
            <span class="duo-feedback-progress-text">0%</span>
        </div>

    </main>

    <?php wp_footer(); ?>
</body>
</html>
