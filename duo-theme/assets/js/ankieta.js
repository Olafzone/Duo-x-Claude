/**
 * Duo Survey - Social Media Confessions
 * SPA Logic
 */

(function() {
    'use strict';

    // =========================================================================
    // CONFIG & STATE
    // =========================================================================

    const QUESTIONS = [
        'Co najbardziej męczy cię w byciu online?',
        'Opisz ostatnią rzecz, którą usunąłeś z internetu — i dlaczego.',
        'Co w twojej osobie jest najbardziej wyreżyserowane?',
        'Kiedy ostatnio udawałeś kogoś w internecie — i przed kim?',
        'Co boisz się pokazać w internecie?',
        'Co byś powiedział, gdyby nikt cię nie oceniał?',
        'Co jest największym kłamstwem na twoim Instagramie?',
        'Co ci zabrał internet?',
    ];

    const state = {
        sessionId: null,
        currentScreen: 'intro',
        currentQuestion: 0,
        questionStartTime: null,
        answers: {},
    };

    // =========================================================================
    // UTILITIES
    // =========================================================================

    function generateSessionId() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        // Fallback for older browsers
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function getSessionId() {
        let sessionId = sessionStorage.getItem('duo_survey_session');
        if (!sessionId) {
            sessionId = generateSessionId();
            sessionStorage.setItem('duo_survey_session', sessionId);
        }
        return sessionId;
    }

    function getHoneypotValue() {
        const honeypot = document.querySelector('.ankieta-honeypot');
        return honeypot ? honeypot.value : '';
    }

    // =========================================================================
    // AJAX
    // =========================================================================

    async function saveResponse(questionNumber, answerText, starRating, skipped, timeSpentMs) {
        const formData = new FormData();
        formData.append('action', 'duo_survey_save');
        formData.append('survey_nonce', duoSurvey.nonce);
        formData.append('session_id', state.sessionId);
        formData.append('question_number', questionNumber);
        formData.append('question_text', QUESTIONS[questionNumber - 1]);
        formData.append('answer_text', answerText || '');
        formData.append('star_rating', starRating || '');
        formData.append('skipped', skipped ? '1' : '0');
        formData.append('time_spent_ms', timeSpentMs);
        formData.append('website', getHoneypotValue());

        try {
            const response = await fetch(duoSurvey.ajaxUrl, {
                method: 'POST',
                body: formData,
            });
            return await response.json();
        } catch (error) {
            console.error('Save error:', error);
            return { success: false };
        }
    }

    async function completesurvey(hardestQuestion) {
        const formData = new FormData();
        formData.append('action', 'duo_survey_complete');
        formData.append('survey_nonce', duoSurvey.nonce);
        formData.append('session_id', state.sessionId);
        formData.append('hardest_question', hardestQuestion);
        formData.append('website', getHoneypotValue());

        try {
            const response = await fetch(duoSurvey.ajaxUrl, {
                method: 'POST',
                body: formData,
            });
            return await response.json();
        } catch (error) {
            console.error('Complete error:', error);
            return { success: false };
        }
    }

    // =========================================================================
    // UI HELPERS
    // =========================================================================

    function showScreen(screenType, questionNumber) {
        const allScreens = document.querySelectorAll('.ankieta-screen');
        allScreens.forEach(screen => {
            screen.classList.remove('active');
            screen.classList.remove('fade-out');
        });

        let targetScreen;
        if (screenType === 'question') {
            targetScreen = document.querySelector(`.ankieta-question[data-question="${questionNumber}"]`);
        } else {
            targetScreen = document.querySelector(`.ankieta-${screenType}`);
        }

        if (targetScreen) {
            // Small delay for transition
            setTimeout(() => {
                targetScreen.classList.add('active');
            }, 50);
        }

        state.currentScreen = screenType;
        state.currentQuestion = questionNumber || 0;

        // Update progress
        updateProgress();

        // Start timer for question
        if (screenType === 'question') {
            state.questionStartTime = Date.now();
        }

        // Focus textarea if question screen
        if (screenType === 'question' && targetScreen) {
            const textarea = targetScreen.querySelector('.ankieta-textarea');
            if (textarea) {
                setTimeout(() => textarea.focus(), 300);
            }
        }
    }

    function updateProgress() {
        const progressFill = document.querySelector('.ankieta-progress-fill');
        const progressText = document.querySelector('.ankieta-progress-text');
        const progressContainer = document.querySelector('.ankieta-progress');

        let percent = 0;

        if (state.currentScreen === 'intro') {
            percent = 0;
            progressContainer.classList.remove('visible');
        } else if (state.currentScreen === 'question') {
            percent = Math.round((state.currentQuestion / 9) * 100);
            progressContainer.classList.add('visible');
        } else if (state.currentScreen === 'closing') {
            percent = 100;
            progressContainer.classList.add('visible');
        } else if (state.currentScreen === 'thankyou') {
            progressContainer.classList.remove('visible');
        }

        progressFill.style.width = percent + '%';
        progressText.textContent = percent + '%';
    }

    function getCurrentQuestionData() {
        const screen = document.querySelector(`.ankieta-question[data-question="${state.currentQuestion}"]`);
        if (!screen) return null;

        const textarea = screen.querySelector('.ankieta-textarea');
        const starsContainer = screen.querySelector('.ankieta-stars');

        return {
            answerText: textarea ? textarea.value.trim() : '',
            starRating: starsContainer ? parseInt(starsContainer.dataset.rating) || 0 : 0,
            timeSpentMs: state.questionStartTime ? Date.now() - state.questionStartTime : 0,
        };
    }

    function resetQuestionUI(questionNumber) {
        const screen = document.querySelector(`.ankieta-question[data-question="${questionNumber}"]`);
        if (!screen) return;

        const textarea = screen.querySelector('.ankieta-textarea');
        const starsContainer = screen.querySelector('.ankieta-stars');
        const charCount = screen.querySelector('.ankieta-char-current');

        if (textarea) textarea.value = '';
        if (charCount) charCount.textContent = '0';
        if (starsContainer) {
            starsContainer.dataset.rating = '0';
            starsContainer.querySelectorAll('.ankieta-star').forEach(star => {
                star.classList.remove('active');
            });
        }
    }

    // =========================================================================
    // EVENT HANDLERS
    // =========================================================================

    async function handleStart() {
        showScreen('question', 1);
    }

    async function handleNext() {
        const data = getCurrentQuestionData();
        if (!data) return;

        // Save response
        await saveResponse(
            state.currentQuestion,
            data.answerText,
            data.starRating || null,
            false,
            data.timeSpentMs
        );

        // Store answer locally
        state.answers[state.currentQuestion] = data;

        // Move to next
        if (state.currentQuestion < 8) {
            showScreen('question', state.currentQuestion + 1);
        } else {
            showScreen('closing');
        }
    }

    async function handleSkip() {
        const data = getCurrentQuestionData();

        // Save as skipped
        await saveResponse(
            state.currentQuestion,
            '',
            null,
            true,
            data ? data.timeSpentMs : 0
        );

        // Move to next
        if (state.currentQuestion < 8) {
            showScreen('question', state.currentQuestion + 1);
        } else {
            showScreen('closing');
        }
    }

    async function handleSubmit() {
        const select = document.getElementById('hardest-question');
        const hardestQuestion = select ? parseInt(select.value) : 0;

        if (!hardestQuestion) {
            select.focus();
            select.classList.add('error');
            setTimeout(() => select.classList.remove('error'), 1000);
            return;
        }

        // Complete survey
        await completesurvey(hardestQuestion);

        // Show thank you
        showScreen('thankyou');
    }

    function handleStarClick(star, starsContainer) {
        const value = parseInt(star.dataset.value);
        starsContainer.dataset.rating = value;

        // Update visual state
        starsContainer.querySelectorAll('.ankieta-star').forEach(s => {
            const starValue = parseInt(s.dataset.value);
            if (starValue <= value) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    }

    function handleTextareaInput(textarea) {
        const wrapper = textarea.closest('.ankieta-textarea-wrapper');
        const charCount = wrapper.querySelector('.ankieta-char-current');
        if (charCount) {
            charCount.textContent = textarea.value.length;
        }

        // Auto-resize
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
    }

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    function init() {
        // Get or create session ID
        state.sessionId = getSessionId();

        // Button clicks
        document.addEventListener('click', function(e) {
            const action = e.target.closest('[data-action]');
            if (!action) return;

            const actionType = action.dataset.action;

            switch (actionType) {
                case 'start':
                    handleStart();
                    break;
                case 'next':
                    handleNext();
                    break;
                case 'skip':
                    handleSkip();
                    break;
                case 'submit':
                    handleSubmit();
                    break;
            }
        });

        // Star rating clicks
        document.addEventListener('click', function(e) {
            const star = e.target.closest('.ankieta-star');
            if (!star) return;

            const starsContainer = star.closest('.ankieta-stars');
            if (starsContainer) {
                handleStarClick(star, starsContainer);
            }
        });

        // Textarea input
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('ankieta-textarea')) {
                handleTextareaInput(e.target);
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                const activeScreen = document.querySelector('.ankieta-screen.active');
                if (!activeScreen) return;

                // Don't submit if in textarea
                if (e.target.classList.contains('ankieta-textarea')) {
                    return;
                }

                if (activeScreen.classList.contains('ankieta-intro')) {
                    e.preventDefault();
                    handleStart();
                } else if (activeScreen.classList.contains('ankieta-question')) {
                    e.preventDefault();
                    handleNext();
                } else if (activeScreen.classList.contains('ankieta-closing')) {
                    e.preventDefault();
                    handleSubmit();
                }
            }
        });

        // Show intro
        showScreen('intro');
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
