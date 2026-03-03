/**
 * Duo Survey - Social Media Confessions v2
 * 3 etapy, wybór pytania z 3 opcji
 */

(function() {
    'use strict';

    // =========================================================================
    // CONFIG & STATE
    // =========================================================================

    const STAGES = {
        1: [
            'Co najbardziej męczy cię w byciu online?',
            'Opisz ostatnią rzecz, którą usunąłeś z internetu — i dlaczego.',
            'Co w twojej osobie jest najbardziej wyreżyserowane?',
        ],
        2: [
            'Kiedy ostatnio udawałeś kogoś w internecie — i przed kim?',
            'Co boisz się pokazać w internecie?',
            'Co byś powiedział, gdyby nikt cię nie oceniał?',
        ],
        3: [
            'Co jest największym kłamstwem na twoim Instagramie?',
            'Co ci zabrał internet?',
            'Czym najbardziej różni się twoje życie offline od tego, co pokazujesz online?',
        ],
    };

    const state = {
        sessionId: null,
        currentScreen: 'intro',
        currentStage: 0,
        stageData: {
            1: { selectedIndex: null, selectedQuestion: null, rejectedQuestions: [], answer: '' },
            2: { selectedIndex: null, selectedQuestion: null, rejectedQuestions: [], answer: '' },
            3: { selectedIndex: null, selectedQuestion: null, rejectedQuestions: [], answer: '' },
        },
    };

    // =========================================================================
    // UTILITIES
    // =========================================================================

    function generateSessionId() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function getSessionId() {
        let sessionId = sessionStorage.getItem('duo_survey_session_v2');
        if (!sessionId) {
            sessionId = generateSessionId();
            sessionStorage.setItem('duo_survey_session_v2', sessionId);
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

    async function saveStageResponse(stageNumber) {
        const data = state.stageData[stageNumber];
        if (!data || !data.selectedQuestion || !data.answer) {
            return { success: false };
        }

        const formData = new FormData();
        formData.append('action', 'duo_survey_save');
        formData.append('survey_nonce', duoSurvey.nonce);
        formData.append('session_id', state.sessionId);
        formData.append('stage_number', stageNumber);
        formData.append('selected_question_text', data.selectedQuestion);
        formData.append('rejected_question_1_text', data.rejectedQuestions[0] || '');
        formData.append('rejected_question_2_text', data.rejectedQuestions[1] || '');
        formData.append('answer_text', data.answer);
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

    // =========================================================================
    // UI HELPERS
    // =========================================================================

    function showScreen(screenType, stageNumber) {
        const allScreens = document.querySelectorAll('.ankieta-screen');
        allScreens.forEach(screen => {
            screen.classList.remove('active');
        });

        let targetScreen;
        if (screenType === 'stage') {
            targetScreen = document.querySelector(`.ankieta-stage[data-stage="${stageNumber}"]`);
        } else {
            targetScreen = document.querySelector(`.ankieta-${screenType}`);
        }

        if (targetScreen) {
            setTimeout(() => {
                targetScreen.classList.add('active');
            }, 50);
        }

        state.currentScreen = screenType;
        state.currentStage = stageNumber || 0;

        updateProgress();
    }

    function updateProgress() {
        const progressFill = document.querySelector('.ankieta-progress-fill');
        const progressText = document.querySelector('.ankieta-progress-text');
        const progressContainer = document.querySelector('.ankieta-progress');

        let percent = 0;

        if (state.currentScreen === 'intro') {
            percent = 0;
            progressContainer.classList.remove('visible');
        } else if (state.currentScreen === 'stage') {
            // 0% -> 33% -> 66% -> 100%
            percent = Math.round((state.currentStage / 3) * 100);
            progressContainer.classList.add('visible');
        } else if (state.currentScreen === 'thankyou') {
            percent = 100;
            progressContainer.classList.add('visible');
            // Ukryj po chwili
            setTimeout(() => {
                progressContainer.classList.remove('visible');
            }, 1000);
        }

        progressFill.style.width = percent + '%';
        progressText.textContent = percent + '%';
    }

    function resetStageUI(stageNumber) {
        const screen = document.querySelector(`.ankieta-stage[data-stage="${stageNumber}"]`);
        if (!screen) return;

        const selectDiv = screen.querySelector('.ankieta-stage-select');
        const answerDiv = screen.querySelector('.ankieta-stage-answer');
        const boxes = screen.querySelectorAll('.ankieta-question-box');
        const textarea = screen.querySelector('.ankieta-textarea');
        const charCount = screen.querySelector('.ankieta-char-current');
        const nextBtn = screen.querySelector('.ankieta-btn-next');

        // Reset to selection state
        selectDiv.style.display = 'block';
        answerDiv.style.display = 'none';

        boxes.forEach(box => {
            box.classList.remove('selected', 'rejected');
        });

        if (textarea) textarea.value = '';
        if (charCount) charCount.textContent = '0';
        if (nextBtn) nextBtn.disabled = true;

        // Reset state data
        state.stageData[stageNumber] = {
            selectedIndex: null,
            selectedQuestion: null,
            rejectedQuestions: [],
            answer: '',
        };
    }

    function selectQuestion(stageNumber, questionIndex) {
        const screen = document.querySelector(`.ankieta-stage[data-stage="${stageNumber}"]`);
        if (!screen) return;

        const boxes = screen.querySelectorAll('.ankieta-question-box');
        const selectDiv = screen.querySelector('.ankieta-stage-select');
        const answerDiv = screen.querySelector('.ankieta-stage-answer');
        const selectedQuestionEl = screen.querySelector('.ankieta-selected-question');
        const textarea = screen.querySelector('.ankieta-textarea');

        const questions = STAGES[stageNumber];
        const selectedQuestion = questions[questionIndex];
        const rejectedQuestions = questions.filter((_, i) => i !== questionIndex);

        // Update state
        state.stageData[stageNumber] = {
            selectedIndex: questionIndex,
            selectedQuestion: selectedQuestion,
            rejectedQuestions: rejectedQuestions,
            answer: '',
        };

        // Visual feedback - highlight selected, fade others
        boxes.forEach((box, i) => {
            if (i === questionIndex) {
                box.classList.add('selected');
                box.classList.remove('rejected');
            } else {
                box.classList.add('rejected');
                box.classList.remove('selected');
            }
        });

        // Transition to answer state after short delay
        setTimeout(() => {
            selectDiv.style.display = 'none';
            answerDiv.style.display = 'block';
            selectedQuestionEl.textContent = selectedQuestion;

            // Focus textarea
            setTimeout(() => {
                if (textarea) textarea.focus();
            }, 100);
        }, 300);
    }

    function handleTextareaInput(textarea, stageNumber) {
        const wrapper = textarea.closest('.ankieta-textarea-wrapper');
        const charCount = wrapper.querySelector('.ankieta-char-current');
        const screen = textarea.closest('.ankieta-stage');
        const nextBtn = screen.querySelector('.ankieta-btn-next');

        if (charCount) {
            charCount.textContent = textarea.value.length;
        }

        // Update state
        state.stageData[stageNumber].answer = textarea.value.trim();

        // Enable/disable next button
        if (nextBtn) {
            nextBtn.disabled = textarea.value.trim().length === 0;
        }

        // Auto-resize
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
    }

    // =========================================================================
    // EVENT HANDLERS
    // =========================================================================

    function handleStart() {
        showScreen('stage', 1);
    }

    async function handleNext() {
        const stageNumber = state.currentStage;
        const data = state.stageData[stageNumber];

        if (!data.selectedQuestion || !data.answer) {
            return;
        }

        // Save response
        await saveStageResponse(stageNumber);

        // Move to next stage or thank you
        if (stageNumber < 3) {
            showScreen('stage', stageNumber + 1);
        } else {
            showScreen('thankyou');
        }
    }

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    function init() {
        // Get or create session ID
        state.sessionId = getSessionId();

        // Button clicks - Start and Next
        document.addEventListener('click', function(e) {
            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;

            if (action === 'start') {
                handleStart();
            } else if (action === 'next') {
                handleNext();
            }
        });

        // Question box clicks
        document.addEventListener('click', function(e) {
            const box = e.target.closest('.ankieta-question-box');
            if (!box) return;

            const screen = box.closest('.ankieta-stage');
            if (!screen) return;

            const stageNumber = parseInt(screen.dataset.stage);
            const questionIndex = parseInt(box.dataset.questionIndex);

            // Only allow selection if not already selected
            if (state.stageData[stageNumber].selectedIndex === null) {
                selectQuestion(stageNumber, questionIndex);
            }
        });

        // Textarea input
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('ankieta-textarea')) {
                const screen = e.target.closest('.ankieta-stage');
                if (screen) {
                    const stageNumber = parseInt(screen.dataset.stage);
                    handleTextareaInput(e.target, stageNumber);
                }
            }
        });

        // Keyboard - Enter to proceed (when not in textarea)
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
                } else if (activeScreen.classList.contains('ankieta-stage')) {
                    const nextBtn = activeScreen.querySelector('.ankieta-btn-next');
                    if (nextBtn && !nextBtn.disabled) {
                        e.preventDefault();
                        handleNext();
                    }
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
