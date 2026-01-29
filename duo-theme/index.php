<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Duo - Twój partner w cyfrowej transformacji">
    <title><?php bloginfo('name'); ?> - <?php bloginfo('description'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

    <!-- Wideo w tle -->
    <div class="video-background">
        <video autoplay muted loop playsinline>
            <source src="<?php echo get_template_directory_uri(); ?>/assets/video/bg-wideo.mp4" type="video/mp4">
        </video>
    </div>

    <!-- Główna zawartość -->
    <main class="main-container">

        <!-- Logo -->
        <header class="logo-container">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.svg" alt="<?php bloginfo('name'); ?>" class="logo">
        </header>

        <!-- Opis działalności -->
        <section class="description-section">
            <p>
                Duo to agencja kreatywna specjalizująca się w tworzeniu wyjątkowych doświadczeń cyfrowych.
                Łączymy strategię, design i technologię, aby budować marki, które wyróżniają się na rynku.
                Razem przekształcimy Twoją wizję w rzeczywistość.
            </p>
        </section>

        <!-- Formularz leadów -->
        <section class="lead-form-section">
            <form class="lead-form" id="duo-lead-form" method="post" action="">
                <?php wp_nonce_field('duo_lead_form', 'duo_nonce'); ?>

                <div class="form-group">
                    <input
                        type="text"
                        id="lead-name"
                        name="lead_name"
                        placeholder="Imię"
                        required
                    >
                </div>

                <div class="form-group">
                    <input
                        type="email"
                        id="lead-email"
                        name="lead_email"
                        placeholder="Email"
                        required
                    >
                </div>

                <button type="submit" class="submit-btn">Wyślij</button>

                <div class="form-message" id="form-message"></div>
            </form>
        </section>

    </main>

    <script>
    document.getElementById('duo-lead-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        formData.append('action', 'duo_save_lead');

        const messageEl = document.getElementById('form-message');
        const submitBtn = form.querySelector('.submit-btn');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Wysyłanie...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            messageEl.className = 'form-message ' + (data.success ? 'success' : 'error');
            messageEl.textContent = data.data.message;
            messageEl.style.display = 'block';

            if (data.success) {
                form.reset();
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Wyślij';
        })
        .catch(error => {
            messageEl.className = 'form-message error';
            messageEl.textContent = 'Wystąpił błąd. Spróbuj ponownie.';
            messageEl.style.display = 'block';

            submitBtn.disabled = false;
            submitBtn.textContent = 'Wyślij';
        });
    });
    </script>

    <?php wp_footer(); ?>
</body>
</html>
