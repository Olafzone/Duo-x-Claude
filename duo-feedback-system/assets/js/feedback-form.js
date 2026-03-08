/**
 * Duo Feedback Form - Multi-screen form logic
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        STORAGE_KEY: 'duo_feedback_progress',
        TOTAL_QUESTIONS: 8,
    };

    // State
    const state = {
        sessionId: null,
        currentScreen: 'intro',
        currentQuestionIndex: -1,
        formData: {},
        isSubmitting: false,
    };

    // DOM Elements
    let container;
    let progressBar;
    let progressText;
    let progressContainer;

    /**
     * Initialize
     */
    function init() {
        container = document.querySelector('.duo-feedback-container');
        progressBar = document.querySelector('.duo-feedback-progress-fill');
        progressText = document.querySelector('.duo-feedback-progress-text');
        progressContainer = document.querySelector('.duo-feedback-progress');

        if (!container) return;

        // Generate or restore session ID
        state.sessionId = getOrCreateSessionId();

        // Restore progress
        restoreProgress();

        // Setup event listeners
        setupEventListeners();

        // Show initial screen
        if (state.currentScreen === 'intro') {
            showScreen('intro');
        }
    }

    /**
     * Generate or restore session ID
     */
    function getOrCreateSessionId() {
        const storageKey = 'duo_feedback_session';
        let sessionId = sessionStorage.getItem(storageKey);

        if (!sessionId) {
            sessionId = generateUUID();
            sessionStorage.setItem(storageKey, sessionId);
        }

        return sessionId;
    }

    /**
     * Generate UUID
     */
    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Button clicks
        container.addEventListener('click', function(e) {
            const action = e.target.dataset.action;
            if (!action) return;

            switch (action) {
                case 'start':
                    handleStart();
                    break;
                case 'next':
                    handleNext();
                    break;
                case 'back':
                    handleBack();
                    break;
            }
        });

        // Textarea input
        container.addEventListener('input', function(e) {
            if (e.target.classList.contains('duo-feedback-textarea')) {
                handleTextareaInput(e.target);
            }
        });

        // Checkbox/radio change
        container.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox' || e.target.type === 'radio') {
                handleOptionChange(e.target);
            }
        });

        // Scale button click
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('duo-feedback-scale-btn')) {
                handleScaleClick(e.target);
            }
        });

        // Auto-save on beforeunload
        window.addEventListener('beforeunload', saveProgress);
    }

    /**
     * Show screen
     */
    function showScreen(screenType, questionIndex = -1) {
        const allScreens = container.querySelectorAll('.duo-feedback-screen');
        allScreens.forEach(screen => screen.classList.remove('active'));

        let targetScreen;

        if (screenType === 'intro') {
            targetScreen = container.querySelector('[data-screen="intro"]');
            state.currentScreen = 'intro';
            state.currentQuestionIndex = -1;
            progressContainer.classList.remove('visible');
        } else if (screenType === 'question') {
            targetScreen = container.querySelector(`[data-question-index="${questionIndex}"]`);
            state.currentScreen = 'question';
            state.currentQuestionIndex = questionIndex;
            progressContainer.classList.add('visible');
            updateProgress();
        } else if (screenType === 'thankyou') {
            targetScreen = container.querySelector('[data-screen="thankyou"]');
            state.currentScreen = 'thankyou';
            progressContainer.classList.remove('visible');
        }

        if (targetScreen) {
            setTimeout(() => {
                targetScreen.classList.add('active');
                // Focus first input
                const firstInput = targetScreen.querySelector('textarea, input:not([type="hidden"])');
                if (firstInput && screenType === 'question') {
                    setTimeout(() => firstInput.focus(), 100);
                }
            }, 50);
        }

        saveProgress();
    }

    /**
     * Update progress bar
     */
    function updateProgress() {
        const progress = ((state.currentQuestionIndex + 1) / CONFIG.TOTAL_QUESTIONS) * 100;
        progressBar.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '%';
    }

    /**
     * Handle start button
     */
    function handleStart() {
        showScreen('question', 0);
    }

    /**
     * Handle next button
     */
    function handleNext() {
        if (state.isSubmitting) return;

        const currentScreen = container.querySelector('.duo-feedback-screen.active');
        const questionId = currentScreen.dataset.questionId;
        const isRequired = currentScreen.dataset.required === 'true';

        // Save current answer
        saveCurrentAnswer(currentScreen);

        // Validate if required
        if (isRequired && !validateQuestion(currentScreen)) {
            return;
        }

        // Check if last question
        if (state.currentQuestionIndex >= CONFIG.TOTAL_QUESTIONS - 1) {
            submitForm();
        } else {
            showScreen('question', state.currentQuestionIndex + 1);
        }
    }

    /**
     * Handle back button
     */
    function handleBack() {
        if (state.currentQuestionIndex > 0) {
            showScreen('question', state.currentQuestionIndex - 1);
        }
    }

    /**
     * Save current answer
     */
    function saveCurrentAnswer(screen) {
        const questionId = screen.dataset.questionId;

        // Textarea
        const textarea = screen.querySelector('.duo-feedback-textarea[data-field]');
        if (textarea) {
            state.formData[textarea.dataset.field] = textarea.value.trim();
        }

        // Checkboxes
        const checkboxes = screen.querySelectorAll('input[type="checkbox"]:checked');
        if (checkboxes.length > 0) {
            const name = checkboxes[0].name.replace('[]', '');
            state.formData[name] = Array.from(checkboxes).map(cb => cb.value);
        }

        // Radio
        const radio = screen.querySelector('input[type="radio"]:checked');
        if (radio) {
            state.formData[radio.name] = radio.value;

            // Save testimonial name if public option selected
            if (radio.name === 'q7_testimonial_permission' && radio.value === 'public') {
                const nameInput = screen.querySelector('.duo-feedback-name-input');
                if (nameInput) {
                    state.formData['testimonial_name'] = nameInput.value.trim();
                }
            }
        }

        // Scale
        const scaleBtn = screen.querySelector('.duo-feedback-scale-btn.selected');
        if (scaleBtn && questionId) {
            state.formData[questionId] = parseInt(scaleBtn.dataset.value);
        }

    }

    /**
     * Validate question
     */
    function validateQuestion(screen) {
        const questionId = screen.dataset.questionId;

        // Check textarea
        const textarea = screen.querySelector('.duo-feedback-textarea[data-field]');
        if (textarea && !textarea.value.trim()) {
            return false;
        }

        // Check checkboxes
        const checkboxGroup = screen.querySelector('.duo-feedback-options input[type="checkbox"]');
        if (checkboxGroup) {
            const checked = screen.querySelectorAll('input[type="checkbox"]:checked');
            if (checked.length === 0) return false;
        }

        // Check radio
        const radioGroup = screen.querySelector('.duo-feedback-options input[type="radio"]');
        if (radioGroup) {
            const checked = screen.querySelector('input[type="radio"]:checked');
            if (!checked) return false;
        }

        // Check scale
        const scaleContainer = screen.querySelector('.duo-feedback-scale');
        if (scaleContainer) {
            const selected = screen.querySelector('.duo-feedback-scale-btn.selected');
            if (!selected) return false;
        }

        return true;
    }

    /**
     * Handle textarea input
     */
    function handleTextareaInput(textarea) {
        // Update char count
        const wrapper = textarea.closest('.duo-feedback-textarea-wrapper');
        const counter = wrapper?.querySelector('.duo-feedback-char-count .current');
        if (counter) {
            counter.textContent = textarea.value.length;
        }

        // Update next button state
        updateNextButtonState();
    }

    /**
     * Handle option change (checkbox/radio)
     */
    function handleOptionChange(input) {
        updateNextButtonState();

        // Toggle testimonial name field
        if (input.name === 'q7_testimonial_permission') {
            const nameField = container.querySelector('.duo-feedback-testimonial-name');
            if (nameField) {
                nameField.style.display = input.value === 'public' ? 'block' : 'none';
            }
        }
    }

    /**
     * Handle scale click
     */
    function handleScaleClick(button) {
        const container = button.closest('.duo-feedback-scale');
        container.querySelectorAll('.duo-feedback-scale-btn').forEach(btn => {
            btn.classList.remove('selected');
        });
        button.classList.add('selected');
        updateNextButtonState();
    }

    /**
     * Update next button state
     */
    function updateNextButtonState() {
        const currentScreen = container.querySelector('.duo-feedback-screen.active');
        if (!currentScreen) return;

        const nextBtn = currentScreen.querySelector('[data-action="next"]');
        if (!nextBtn) return;

        const isRequired = currentScreen.dataset.required === 'true';

        if (isRequired) {
            nextBtn.disabled = !validateQuestion(currentScreen);
        } else {
            nextBtn.disabled = false;
        }
    }

    /**
     * Submit form
     */
    async function submitForm() {
        if (state.isSubmitting) return;
        state.isSubmitting = true;

        const currentScreen = container.querySelector('.duo-feedback-screen.active');
        saveCurrentAnswer(currentScreen);

        const nextBtn = currentScreen.querySelector('[data-action="next"]');
        if (nextBtn) {
            nextBtn.disabled = true;
            nextBtn.textContent = 'Wysylanie...';
        }

        try {
            const projectSlug = container.dataset.projectSlug || duoFeedback.projectSlug;

            const response = await fetch(duoFeedback.restUrl + 'submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': duoFeedback.nonce,
                },
                body: JSON.stringify({
                    project_slug: projectSlug,
                    session_id: state.sessionId,
                    form_data: state.formData,
                    honeypot: document.querySelector('.duo-feedback-honeypot')?.value || '',
                }),
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Clear saved progress
                localStorage.removeItem(CONFIG.STORAGE_KEY);
                sessionStorage.removeItem('duo_feedback_session');

                // Show thank you
                showScreen('thankyou');
            } else {
                throw new Error(data.message || 'Submission failed');
            }
        } catch (error) {
            console.error('Submission error:', error);
            alert('Cos poszlo nie tak. Sprobuj ponownie lub skontaktuj sie z nami.');

            if (nextBtn) {
                nextBtn.disabled = false;
                nextBtn.textContent = 'Wyslij';
            }
        }

        state.isSubmitting = false;
    }

    /**
     * Save progress to localStorage
     */
    function saveProgress() {
        const data = {
            sessionId: state.sessionId,
            currentQuestionIndex: state.currentQuestionIndex,
            currentScreen: state.currentScreen,
            formData: state.formData,
            timestamp: Date.now(),
        };

        try {
            localStorage.setItem(CONFIG.STORAGE_KEY, JSON.stringify(data));
        } catch (e) {
            console.error('Failed to save progress:', e);
        }
    }

    /**
     * Restore progress from localStorage
     */
    function restoreProgress() {
        try {
            const saved = localStorage.getItem(CONFIG.STORAGE_KEY);
            if (!saved) return;

            const data = JSON.parse(saved);

            // Check if within 24 hours
            if (Date.now() - data.timestamp > 24 * 60 * 60 * 1000) {
                localStorage.removeItem(CONFIG.STORAGE_KEY);
                return;
            }

            // Restore state
            state.sessionId = data.sessionId || state.sessionId;
            state.formData = data.formData || {};
            state.currentQuestionIndex = data.currentQuestionIndex;
            state.currentScreen = data.currentScreen;

            // Restore form values
            restoreFormValues();

            // Show correct screen
            if (state.currentScreen === 'question' && state.currentQuestionIndex >= 0) {
                showScreen('question', state.currentQuestionIndex);
            }
        } catch (e) {
            console.error('Failed to restore progress:', e);
        }
    }

    /**
     * Restore form values from state
     */
    function restoreFormValues() {
        Object.keys(state.formData).forEach(key => {
            const value = state.formData[key];

            // Textarea
            const textarea = container.querySelector(`[data-field="${key}"]`);
            if (textarea) {
                textarea.value = value;
            }

            // Checkboxes
            if (Array.isArray(value)) {
                value.forEach(v => {
                    const checkbox = container.querySelector(`input[name="${key}[]"][value="${v}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }

            // Radio
            const radio = container.querySelector(`input[name="${key}"][value="${value}"]`);
            if (radio) radio.checked = true;

            // Scale
            if (key.startsWith('q6_rating') && typeof value === 'number') {
                const btn = container.querySelector(`.duo-feedback-scale-btn[data-value="${value}"]`);
                if (btn) btn.classList.add('selected');
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
