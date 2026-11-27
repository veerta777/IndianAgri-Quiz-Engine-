/**
 * IndianAgri Quiz Engine - Client Examination State Machine
 * Handles real-time countdown timer, question palette, auto-save heartbeat, and submission.
 *
 * @package IndianAgri_Quiz_Engine
 */

(function ($) {
	'use strict';

	var IAQ_ICONS = {
		check: '<svg class="iaq-icon iaq-icon-check" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>',
		cross: '<svg class="iaq-icon iaq-icon-cross" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm2.707-10.707a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293z" clip-rule="evenodd" /></svg>',
		lightbulb: '<svg class="iaq-icon iaq-icon-lightbulb" viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true" style="color:#f59e0b;vertical-align:-2px;margin-right:6px;"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14H8a4 4 0 01-.813-7.917A4 4 0 0112 14z" /></svg>',
		flag: '<svg class="iaq-icon iaq-icon-flag" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true" style="vertical-align:-2px;margin-right:4px;"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd" /></svg>',
		minus: '<svg class="iaq-icon iaq-icon-minus" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>',
		spinner: '<svg class="iaq-icon iaq-icon-spin" viewBox="0 0 24 24" fill="none" width="14" height="14" aria-hidden="true" style="animation: iaqSpin 1s linear infinite;vertical-align:-2px;margin-right:6px;"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"/><path fill="currentColor" d="M12 2a10 10 0 0110 10h-3a7 7 0 00-7-7V2z"/></svg>'
	};

	function cleanContentText(txt) {
		if (!txt) return '';
		txt = String(txt);
		// Strip [cite: 3], [cite: 1, 2], [cite:3], [cite 3], [cite: ...]
		txt = txt.replace(/\s*\[cite:?\s*[^\]]+\]/gi, '');
		// Strip (cite: 3), (cite 3), (cite: ...)
		txt = txt.replace(/\s*\(cite:?\s*[^\)]+\)/gi, '');
		// Strip bare cite 3, cite: 3, cite 1
		txt = txt.replace(/\s*\bcite:?\s*\d+\b/gi, '');
		// Strip ChatGPT/Copilot citations like 【3†source】 or 【12†source】
		txt = txt.replace(/\s*【[^】]+】/g, '');
		// Strip [citation: ...], [ref: ...], [source: ...]
		txt = txt.replace(/\s*\[(citation|ref|source):?\s*[^\]]+\]/gi, '');
		// Strip standalone footnote numbers in brackets e.g. [3] or [1] at end/after word
		txt = txt.replace(/\s*\[\d{1,2}\](?!\s*[\+\-\*\/=])/g, '');
		// Strip HTML <cite> tags
		txt = txt.replace(/<\/?cite[^>]*>/gi, '');
		return txt.trim();
	}

	function cleanExplanationText(txt) {
		if (!txt) return '';
		txt = cleanContentText(txt);
		if (txt.indexOf('<p>') === -1 && txt.indexOf('<br') === -1 && txt.indexOf('<div') === -1) {
			txt = txt.replace(/\n\n+/g, '</p><p>').replace(/\n/g, '<br>');
			txt = '<p>' + txt + '</p>';
		}
		return txt;
	}

	var IAQQuizEngine = {
		attemptId: 0,
		quizId: 0,
		questions: [],
		currentIndex: 0,
		answers: {},
		isImmediate: false,
		showAnswersMode: 'after_submission',
		feedbackData: {},
		remainingSeconds: null,
		timerInterval: null,
		timeSpentSeconds: 0,
		isSubmitting: false,

		init: function () {
			var self = this;

			// Check if examination portal is present on the page.
			var $portal = $('#iaq-examination-portal');
			if (!$portal.length) {
				return;
			}

			self.quizId = parseInt($portal.data('quiz-id'), 10);
			self.quizType = $portal.data('quiz-type') || '';
			var portalImmediate = $portal.data('is-immediate');
			var showAnswersMode = $portal.data('show-answers-mode');
			var urlParams = new URLSearchParams(window.location.search);
			var isPracticeUrl = (urlParams.get('iaq_mode') === 'practice') || (urlParams.get('iaq_tab') === 'practice-sets') || (window.location.href.indexOf('practice') !== -1);
			if (portalImmediate === 1 || portalImmediate === '1' || showAnswersMode === 'immediate' || self.quizType === 'practice' || (self.quizType !== 'mock' && self.quizType !== 'previous_year') || isPracticeUrl) {
				self.isImmediate = true;
			}

			// Bind Start / Resume button on Overview Card.
			$(document).on('click', '#iaq-start-quiz-btn, .iaq-btn-start', function (e) {
				e.preventDefault();
				var $btn = $(this);
				if ($btn.prop('disabled')) {
					return;
				}
				var qid = $btn.data('quiz-id') || self.quizId;
				self.startOrResumeAttempt(qid);
			});

			// Check if auto-launch requested via URL (?iaq_take=1).
			var urlParams = new URLSearchParams(window.location.search);
			if (urlParams.get('iaq_take') === '1' || urlParams.get('iaq_action') === 'attempt') {
				self.startOrResumeAttempt(self.quizId);
			}

			// Bind Question Action Buttons.
			$(document).on('click', '#iaq-action-prev', function () {
				self.prevQuestion();
			});

			$(document).on('click', '#iaq-action-next, .iaq-feedback-next-btn', function () {
				self.saveAndNext();
			});

			$(document).on('click', '#iaq-action-clear', function () {
				self.clearSelection();
			});

			$(document).on('click', '#iaq-action-mark', function () {
				self.markForReviewAndNext();
			});

			// Bind Option Selection.
			$(document).on('click', '.iaq-option-item', function () {
				var optKey = $(this).data('opt-key');
				self.selectOption(optKey);
			});

			// Bind Palette Question Jump.
			$(document).on('click', '.iaq-palette-btn', function () {
				var targetIdx = parseInt($(this).data('q-index'), 10);
				self.jumpToQuestion(targetIdx);
			});

			// Bind Final Submit Modal Open.
			$(document).on('click', '#iaq-btn-open-submit', function () {
				self.openSubmitModal();
			});

			// Bind Modal Cancel / Return Buttons.
			$(document).on('click', '#iaq-modal-cancel-btn, #iaq-modal-return-btn', function () {
				$('#iaq-submit-modal').fadeOut(150);
			});

			// Bind Modal Final Submission.
			$(document).on('click', '#iaq-modal-confirm-submit', function () {
				self.submitAttempt();
			});

			// Retake button on results view.
			$(document).on('click', '#iaq-res-retake-btn', function () {
				window.location.reload();
			});

			// Re-attempt Mistakes Only button on results view.
			$(document).on('click', '#iaq-res-retake-mistakes-btn', function () {
				self.startMistakesDrill();
			});

			// Review Solutions button on results view.
			$(document).on('click', '#iaq-res-review-btn', function () {
				self.loadSolutionsReview();
			});

			// Print Scorecard / Solutions & Download PDF
			$(document).on('click', '#iaq-res-print-btn, #iaq-sol-print-btn, #iaq-sol-bottom-print-btn, .iaq-btn-print', function (e) {
				e.preventDefault();
				window.print();
			});

			// Back to Scorecard from solutions portal.
			$(document).on('click', '#iaq-sol-back-btn, #iaq-sol-bottom-back-btn', function () {
				$('#iaq-solutions-portal').slideUp(200);
				$('#iaq-results-portal').fadeIn(300);
				$('html, body').animate({
					scrollTop: $('#iaq-results-portal').offset().top - 20
				}, 300);
			});

			// Filter Tabs on solutions portal & standalone scorecard.
			$(document).on('click', '.iaq-filter-tab, .iaq-filter-trigger', function () {
				var filter = $(this).data('filter');
				if (!filter) return;

				$('.iaq-filter-tab').removeClass('is-active');
				$('.iaq-filter-tab[data-filter="' + filter + '"]').addClass('is-active');

				var $staticCards = $('.iaq-solutions-section .iaq-solution-card');
				if ($staticCards.length > 0) {
					if (filter === 'all') {
						$staticCards.fadeIn(150);
					} else {
						$staticCards.hide();
						$staticCards.filter('.is-status-' + filter).fadeIn(150);
					}
					// If triggered from top breakdown tiles, scroll down smoothly to solutions
					if ($(this).hasClass('iaq-filter-trigger')) {
						var $solSec = $('.iaq-solutions-section');
						if ($solSec.length > 0) {
							$('html, body').animate({
								scrollTop: $solSec.offset().top - 20
							}, 300);
						}
					}
				} else if (self && typeof self.renderSolutionsList === 'function') {
					self.renderSolutionsList(filter);
				}
			});

			// Return to Dashboard from Active Examination
			$(document).on('click', '#iaq-exam-back-dashboard-btn', function (e) {
				e.preventDefault();
				if (self.attemptId && !self.isSubmitting) {
					var warnMsg = (window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.leaveConfirm) ?
						window.iaqQuizData.i18n.leaveConfirm :
						'You have an examination in progress. Are you sure you want to return to the dashboard? Your answers are autosaved, and you can resume this attempt anytime before the deadline.';
					if (!window.confirm(warnMsg)) {
						return;
					}
				}
				// Stop client timer
				if (self.timerInterval) {
					clearInterval(self.timerInterval);
					self.timerInterval = null;
				}
				// Return to dashboard overview tab
				$('#iaq-examination-portal').hide();
				$('.iaq-dashboard-view-pane').removeClass('is-active');
				$('#iaq-pane-dashboard').addClass('is-active');
				$('.iaq-side-nav-item').removeClass('is-active');
				$('.iaq-side-nav-item[data-view="dashboard"]').addClass('is-active');
				$('.iaq-topbar-nav-links .iaq-top-link').removeClass('is-active');
				$('.iaq-topbar-nav-links .iaq-top-link[data-target="dashboard"]').addClass('is-active');
				if (window.history && window.history.pushState) {
					try {
						var returnUrl = new URL(window.location.href);
						returnUrl.searchParams.delete('iaq_take');
						returnUrl.searchParams.delete('iaq_action');
						returnUrl.searchParams.delete('iaq_quiz_id');
						returnUrl.searchParams.set('iaq_tab', 'dashboard');
						returnUrl.hash = 'dashboard';
						window.history.pushState({ iaq_tab: 'dashboard' }, '', returnUrl.toString());
					} catch (err) {}
				}
				$('html, body').animate({ scrollTop: 0 }, 200);
			});

			// Warn candidate before tab close/refresh during active exam.
			window.addEventListener('beforeunload', function (e) {
				if (self.attemptId && !self.isSubmitting) {
					var msg = (window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.leaveConfirm) ?
						window.iaqQuizData.i18n.leaveConfirm :
						'You have an examination in progress.';
					e.preventDefault();
					e.returnValue = msg;
					return msg;
				}
			});
		},

		getAjaxUrl: function () {
			var url = (window.iaqQuizData && window.iaqQuizData.ajaxUrl) ? window.iaqQuizData.ajaxUrl : '/wp-admin/admin-ajax.php';
			if (window.location.protocol === 'https:' && url.indexOf('http:') === 0) {
				url = url.replace(/^http:/, 'https:');
			}
			return url;
		},

		getNonce: function () {
			return (window.iaqQuizData && window.iaqQuizData.nonce) ? window.iaqQuizData.nonce : '';
		},

		/**
		 * Launch Examination Portal instantly with question payload.
		 */
		launchPortalSession: function (d, quizId) {
			var self = this;
			self.attemptId = d.attempt_id || (9999000 + parseInt(quizId, 10));
			self.quizId = d.quiz_id || parseInt(quizId, 10);
			self.questions = d.questions || [];
			self.remainingSeconds = (d.remaining_seconds != null) ? d.remaining_seconds : ((d.duration_minutes || 0) * 60 || null);
			self.timeSpentSeconds = d.time_spent_seconds || 0;
			self.quizType = d.quiz_type || self.quizType || '';
			var urlParams = new URLSearchParams(window.location.search);
			var isPracticeRequested = (urlParams.get('iaq_mode') === 'practice') || (urlParams.get('iaq_tab') === 'practice-sets') || (window.location.href.indexOf('practice') !== -1);
			var hasImmediateData = !!d.is_immediate || (d.show_answers_mode === 'immediate') || (d.quiz_type === 'practice') || (self.quizType === 'practice');
			var isExplicitMockOrPyq = (self.quizType === 'mock' || self.quizType === 'previous_year');
			self.isImmediate = isPracticeRequested || hasImmediateData || (self.isImmediate) || (!isExplicitMockOrPyq);
			self.showAnswersMode = self.isImmediate ? 'immediate' : (d.show_answers_mode || 'after_submission');
			self.feedbackData = {};

			// Pre-populate answers dictionary and restore immediate feedback state
			self.answers = {};
			self.currentStreak = 0;
			self.highestStreak = 0;
			self.correctCount = 0;
			self.incorrectCount = 0;

			$.each(self.questions, function (i, q) {
				if (q.question_text) q.question_text = cleanContentText(q.question_text);
				if (q.question_text_hi) q.question_text_hi = cleanContentText(q.question_text_hi);
				if (q.explanation) q.explanation = cleanExplanationText(q.explanation);
				if (q.options && Array.isArray(q.options)) {
					$.each(q.options, function (_, opt) {
						if (opt.text) opt.text = cleanContentText(opt.text);
						if (opt.text_hi) opt.text_hi = cleanContentText(opt.text_hi);
					});
				}
				var hasAns = (q.selected != null && String(q.selected).trim() !== '');
				var isM = (q.is_marked ? 1 : 0);
				var isV = (i === 0) ? true : (q.visited ? true : (hasAns ? true : (isM ? true : false)));
				self.answers[q.id] = {
					selected: hasAns ? q.selected : null,
					isMarked: isM,
					visited: isV
				};
				if (q.immediate_feedback) {
					self.feedbackData[q.id] = q.immediate_feedback;
				}
			});

			// Reconstruct streaks and counts if resuming practice session
			if (self.isImmediate) {
				var runningStreak = 0;
				$.each(self.questions, function (i, q) {
					var fb = self.feedbackData[q.id];
					if (fb) {
						if (fb.is_correct) {
							self.correctCount++;
							runningStreak++;
							if (runningStreak > self.highestStreak) {
								self.highestStreak = runningStreak;
							}
						} else {
							self.incorrectCount++;
							runningStreak = 0;
						}
					}
				});
				self.currentStreak = runningStreak;
				$('#iaq-practice-dashboard').css('display', 'flex').show();
				$('#iaq-submit-btn-label').text('Finish Practice');
				$('.iaq-legend-marked, .iaq-legend-ans-marked').hide();
				self.updatePracticeDashboard();
			} else {
				$('#iaq-practice-dashboard').hide();
				$('#iaq-submit-btn-label').text('Submit Examination');
				$('.iaq-legend-marked, .iaq-legend-ans-marked').show();
			}

			// Switch UI to Examination Portal immediately.
			$('#iaq-quiz-overview-' + quizId + ', .iaq-exam-card').slideUp(150);
			$('#iaq-examination-portal').toggleClass('is-practice-mode', self.isImmediate).fadeIn(200);

			// Initialize Palette Grid.
			self.buildPaletteGrid();

			// Render First Question.
			self.renderQuestion(0);

			// Start Timer.
			self.initTimer();

			// Scroll into view smoothly.
			if ($('#iaq-examination-portal').length && $('#iaq-examination-portal').offset()) {
				$('html, body').animate({
					scrollTop: $('#iaq-examination-portal').offset().top - 20
				}, 200);
			}
		},

		/**
		 * Start or Resume Quiz Attempt Session via AJAX with resilient multi-tier instant fallback.
		 */
		startOrResumeAttempt: function (quizId) {
			var self = this;
			var $btn = $('#iaq-start-quiz-btn, .iaq-btn-start');

			$btn.prop('disabled', true).text(
				(window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.starting) ?
				window.iaqQuizData.i18n.starting :
				'Initializing...'
			);

			var hasStarted = false;
			function startInstantly(payload) {
				if (hasStarted) return;
				hasStarted = true;
				self.launchPortalSession(payload, quizId);
			}

			// Instant Preload Check: If questions are preloaded in DOM, start immediately (0ms wait!)
			var $preEl = $('#iaq-preloaded-quiz-' + quizId);
			if ($preEl.length) {
				try {
					var pData = JSON.parse($preEl.text());
					if (pData && pData.questions && pData.questions.length > 0) {
						startInstantly(pData);
					}
				} catch (e) {}
			}

			// Tier 1: Primary WordPress AJAX attempt
			var primaryUrl = self.getAjaxUrl();
			$.ajax({
				url: primaryUrl,
				type: 'POST',
				data: {
					action: 'iaq_start_quiz_attempt',
					quiz_id: quizId,
					nonce: self.getNonce()
				},
				dataType: 'json',
				timeout: 4500,
				xhrFields: { withCredentials: true },
				success: function (res) {
					if (res && res.success && res.data) {
						if (!hasStarted && res.data.questions && res.data.questions.length > 0) {
							startInstantly(res.data);
						} else if (hasStarted && res.data.attempt_id) {
							self.attemptId = res.data.attempt_id;
						}
					} else if (!hasStarted) {
						tryRestStart(quizId);
					}
				},
				error: function () {
					if (!hasStarted) {
						tryRestStart(quizId);
					}
				}
			});

			// Tier 2: Instant REST API Fallback
			function tryRestStart(qid) {
				var restUrl = (window.iaqQuizData && window.iaqQuizData.restStartUrl) ?
					window.iaqQuizData.restStartUrl.replace('{id}', qid) :
					'/wp-json/iaq/v1/quiz/' + qid + '/start';

				$.ajax({
					url: restUrl,
					type: 'POST',
					data: JSON.stringify({ demo_mode: true }),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					timeout: 4500,
					xhrFields: { withCredentials: true },
					success: function (d) {
						if (d && d.questions && d.questions.length > 0) {
							startInstantly(d);
						} else {
							tryCatalogQuestions(qid);
						}
					},
					error: function () {
						tryCatalogQuestions(qid);
					}
				});
			}

			// Tier 3: Direct Questions Catalog Fallback
			function tryCatalogQuestions(qid) {
				var catUrl = (window.iaqQuizData && window.iaqQuizData.restQuestionsUrl) ?
					window.iaqQuizData.restQuestionsUrl.replace('{id}', qid) :
					'/wp-json/iaq/v1/quiz/' + qid + '/questions';

				$.ajax({
					url: catUrl,
					type: 'GET',
					dataType: 'json',
					timeout: 4500,
					xhrFields: { withCredentials: true },
					success: function (qRes) {
						var qs = (qRes && qRes.questions) ? qRes.questions : (Array.isArray(qRes) ? qRes : []);
						if (qs && qs.length > 0) {
							startInstantly({
								attempt_id: 9999000 + parseInt(qid, 10),
								quiz_id: parseInt(qid, 10),
								questions: qs,
								remaining_seconds: 7200,
								is_immediate: true,
								show_answers_mode: 'immediate'
							});
						} else {
							tryInstructionsQuestions(qid);
						}
					},
					error: function () {
						tryInstructionsQuestions(qid);
					}
				});
			}

			// Tier 4: Try Quiz Instructions Preview
			function tryInstructionsQuestions(qid) {
				var instUrl = '/wp-json/iaq/v1/quiz/' + qid + '/instructions';

				$.ajax({
					url: instUrl,
					type: 'GET',
					dataType: 'json',
					timeout: 4500,
					xhrFields: { withCredentials: true },
					success: function (iRes) {
						var qs = (iRes && iRes.questions) ? iRes.questions : ((iRes && iRes.sample_questions) ? iRes.sample_questions : []);
						if (qs && qs.length > 0) {
							startInstantly({
								attempt_id: 9999000 + parseInt(qid, 10),
								quiz_id: parseInt(qid, 10),
								questions: qs,
								remaining_seconds: 7200,
								is_immediate: true,
								show_answers_mode: 'immediate'
							});
						} else {
							abortStart('No questions are currently available for this examination. Please contact administrator.');
						}
					},
					error: function () {
						abortStart('Connection error. Please check your internet connection and try again.');
					}
				});
			}

			function abortStart(msg) {
				if (hasStarted) return;
				alert(msg);
				$btn.prop('disabled', false).text('Start Examination Now \u2192');
			}
		},

		/**
		 * Build the question palette number buttons.
		 */
		buildPaletteGrid: function () {
			var self = this;
			var $grid = $('#iaq-palette-grid');
			$grid.empty();

			$.each(self.questions, function (i, q) {
				var btnNum = i + 1;
				var $btn = $('<button type="button" class="iaq-palette-btn is-not-visited" id="iaq-pal-btn-' + i + '"></button>');
				$btn.text(btnNum).attr('data-q-index', i);
				$grid.append($btn);
			});

			self.updateAllPaletteStatuses();
		},

		/**
		 * Render immediate feedback explanation box and badges.
		 */
		renderImmediateFeedbackBox: function (q, fb) {
			var self = this;
			var $fbBox = $('#iaq-immediate-feedback-box');
			if (!$fbBox.length) return;

			var isCorrect = !!fb.is_correct;
			var bannerCls = isCorrect ? 'is-correct' : 'is-incorrect';
			var iconHtml = isCorrect
				? '<span class="iaq-feedback-icon" style="color:#117E3A;">' + IAQ_ICONS.check + '</span>'
				: '<span class="iaq-feedback-icon" style="color:#dc2626;">' + IAQ_ICONS.cross + '</span>';
			var scorePillHtml = isCorrect
				? '<span class="iaq-feedback-score-pill">+' + (fb.marks_awarded !== undefined ? fb.marks_awarded : q.marks) + ' Marks</span>'
				: '<span class="iaq-feedback-score-pill">' + (fb.marks_awarded !== undefined ? (fb.marks_awarded > 0 ? '+' + fb.marks_awarded : fb.marks_awarded) : '0.00') + ' Marks</span>';

			var congratulatoryMsgs = [
				'Excellent! Correct Answer!',
				'Well done! Keep learning!',
				'Brilliant! You got it right!',
				'Great job! Keep it up!'
			];
			var supportiveMsgs = [
				"Not quite! Don't give up.",
				"Keep learning. You'll get the next one!",
				"Good try! Learn from this question."
			];

			var qIdx = self.currentIndex || 0;
			var feedbackMsg;
			if (fb.feedback_message) {
				feedbackMsg = fb.feedback_message;
			} else if (isCorrect) {
				feedbackMsg = congratulatoryMsgs[qIdx % congratulatoryMsgs.length];
			} else {
				feedbackMsg = supportiveMsgs[qIdx % supportiveMsgs.length];
			}

			// Clearly identify correct answer option key and text
			var correctOptObj = null;
			var targetKey = fb.correct_option || q.correct_option;
			if (q.options && q.options.length) {
				$.each(q.options, function (oi, opt) {
					if (opt.key === targetKey) {
						correctOptObj = opt;
					}
				});
			}
			var correctOptText = correctOptObj ? ('Option ' + targetKey + ': ' + correctOptObj.text) : ('Option ' + targetKey);

			var html = '<div class="iaq-feedback-banner ' + bannerCls + '">';
			html += '<div class="iaq-feedback-msg-group">';
			html += iconHtml;
			html += '<h4 class="iaq-feedback-title">' + feedbackMsg + '</h4>';
			html += '</div>';
			html += scorePillHtml;
			html += '</div>';

			// Clearly identify correct answer banner
			html += '<div class="iaq-feedback-correct-answer-banner">';
			html += '<span style="color:#117E3A;font-size:16px;">✔</span> ';
			html += '<span><strong>Correct Answer:</strong> ' + correctOptText + '</span>';
			html += '</div>';

			// Heading: "Correct Answer – Explanation"
			var expl = cleanExplanationText(fb.explanation || q.explanation);
			if (!expl || $.trim(expl).length === 0) {
				expl = 'Explanation is not available for this question.';
			}

			html += '<div class="iaq-feedback-explanation">';
			html += '<h5 class="iaq-feedback-expl-heading">' + IAQ_ICONS.lightbulb + ' Correct Answer – Explanation</h5>';
			html += '<div class="iaq-feedback-expl-text">' + expl + '</div>';
			html += '</div>';

			var nextBtnText = (self.currentIndex === self.questions.length - 1)
				? 'Finish Practice Set & View Scorecard &rarr;'
				: 'Next Question &rarr;';

			html += '<div class="iaq-feedback-action-row">';
			html += '<button type="button" class="iaq-feedback-next-btn">' + nextBtnText + '</button>';
			html += '</div>';

			$fbBox.html(html).slideDown(200);

			if ($fbBox.offset()) {
				$('html, body').animate({
					scrollTop: $fbBox.offset().top - 120
				}, 250);
			}
		},

		/**
		 * Update Practice Progress Dashboard (Attempted, Correct, Incorrect, Streaks, Accuracy).
		 */
		updatePracticeDashboard: function () {
			var self = this;
			if (!self.isImmediate) return;

			var attempted = self.correctCount + self.incorrectCount;
			var total = self.questions.length;
			var accuracy = attempted > 0 ? ((self.correctCount / attempted) * 100).toFixed(1) + '%' : '0.0%';

			$('#iaq-dash-attempted').text(attempted + ' / ' + total);
			$('#iaq-dash-correct').text(self.correctCount);
			$('#iaq-dash-incorrect').text(self.incorrectCount);
			var $streak = $('#iaq-dash-streak');
			$streak.html((self.currentStreak > 0 ? '🔥 ' : '') + self.currentStreak);
			$streak.addClass('is-streak-pulse');
			setTimeout(function () {
				$streak.removeClass('is-streak-pulse');
			}, 400);

			$('#iaq-dash-highest-streak').html((self.highestStreak > 0 ? '🏆 ' : '') + self.highestStreak);
			$('#iaq-dash-accuracy').text(accuracy);
		},

		/**
		 * Display celebratory streak toast message.
		 */
		showCelebrationToast: function (message, isMega) {
			var $container = $('#iaq-global-celebration-container');
			if (!$container.length) {
				$container = $('<div class="iaq-celebration-container" id="iaq-global-celebration-container" style="position:fixed;top:28px;left:50%;transform:translateX(-50%);z-index:999999;pointer-events:none;display:flex;flex-direction:column;align-items:center;gap:10px;"></div>').appendTo('body');
			}
			var megaCls = isMega ? ' is-mega-streak' : '';
			var $toast = $('<div class="iaq-streak-toast' + megaCls + '">' + message + '</div>');
			$container.append($toast);
			setTimeout(function () {
				$toast.fadeOut(300, function () { $(this).remove(); });
			}, 2800);
		},

		/**
		 * Trigger lightweight, non-intrusive celebratory confetti particles.
		 */
		triggerConfetti: function () {
			var colors = ['#117E3A', '#16A34A', '#F59E0B', '#8B5CF6', '#EC4899', '#0284C7', '#EAB308'];
			for (var i = 0; i < 28; i++) {
				var $particle = $('<div class="iaq-confetti-particle"></div>');
				var leftOffset = Math.random() * window.innerWidth;
				var color = colors[Math.floor(Math.random() * colors.length)];
				var delay = Math.random() * 0.4;
				var size = 6 + Math.random() * 6;

				$particle.css({
					left: leftOffset + 'px',
					top: '10px',
					width: size + 'px',
					height: size + 'px',
					backgroundColor: color,
					borderRadius: Math.random() > 0.5 ? '50%' : '2px',
					animationDelay: delay + 's'
				});

				$('body').append($particle);
				(function ($p) {
					setTimeout(function () {
						$p.remove();
					}, 2200);
				})($particle);
			}
		},

		/**
		 * Render the active question in workspace.
		 */
		renderQuestion: function (index) {
			var self = this;
			if (index < 0 || index >= self.questions.length) {
				return;
			}

			self.currentIndex = index;
			var q = self.questions[index];
			var userAns = self.answers[q.id] || { selected: null, isMarked: 0 };
			userAns.visited = true;
			self.answers[q.id] = userAns;

			// Update Question Numbers & Marks.
			$('#iaq-current-q-index').text(index + 1);
			$('#iaq-total-q-count').text(self.questions.length);
			$('#iaq-q-pos-marks').text(q.marks);
			$('#iaq-q-neg-marks').text(q.negative_marks);

			// Render Question Text.
			$('#iaq-question-text').html(cleanContentText(q.question_text));

			// Reset Immediate Feedback Box.
			var $fbBox = $('#iaq-immediate-feedback-box');
			$fbBox.empty().hide();

			// Check if immediate feedback is active and this question has already been answered.
			var fb = (self.isImmediate && self.feedbackData[q.id]) ? self.feedbackData[q.id] : null;

			// Render Options.
			var $opts = $('#iaq-options-list');
			$opts.empty();

			$.each(q.options, function (oi, opt) {
				var isSelected = (userAns.selected === opt.key);
				var $item = $('<div class="iaq-option-item" data-opt-key="' + opt.key + '"></div>');
				if (isSelected) {
					$item.addClass('is-selected');
				}

				var visualLabel = ['A', 'B', 'C', 'D'][oi] || opt.key;
				var $key = $('<span class="iaq-option-key">' + visualLabel + '</span>');
				var $lbl = $('<span class="iaq-option-label"></span>').html(cleanContentText(opt.text));

				$item.append($key).append($lbl);

				if (fb) {
					$item.addClass('is-disabled');
					if (opt.key === fb.correct_option) {
						$item.addClass('is-immediate-correct');
						$item.append('<span class="iaq-option-status-tag is-correct-tag">' + IAQ_ICONS.check + ' <span>Correct Answer</span></span>');
					} else if (opt.key === fb.selected && !fb.is_correct) {
						$item.addClass('is-immediate-incorrect');
						$item.append('<span class="iaq-option-status-tag is-incorrect-tag">' + IAQ_ICONS.cross + ' <span>Your Choice</span></span>');
					}
				}

				$opts.append($item);
			});

			if (self.isImmediate) {
				$('#iaq-action-mark').hide();
				if (fb) {
					self.renderImmediateFeedbackBox(q, fb);
					$('#iaq-action-clear').hide();
				} else {
					$('#iaq-action-clear').show();
				}
			} else {
				$('#iaq-action-mark').show();
				$('#iaq-action-clear').show();
			}

			// Prev button state.
			$('#iaq-action-prev').prop('disabled', (index === 0));

			// Next button label.
			if (index === self.questions.length - 1) {
				if (self.isImmediate) {
					$('#iaq-action-next').html('Submit Practice Set &rarr;');
				} else {
					$('#iaq-action-next').html('Save & Review &rarr;');
				}
			} else {
				if (self.isImmediate) {
					$('#iaq-action-next').html('Next Question &rarr;');
				} else {
					$('#iaq-action-next').html('Save & Next &rarr;');
				}
			}

			// Update Mark button text/state.
			if (userAns.isMarked) {
				$('#iaq-action-mark').html(IAQ_ICONS.flag + ' Unmark Review');
			} else {
				$('#iaq-action-mark').html(IAQ_ICONS.flag + ' Mark for Review &amp; Next');
			}

			// Update Active Highlight on Palette.
			$('.iaq-palette-btn').removeClass('is-active-q');
			$('#iaq-pal-btn-' + index).addClass('is-active-q');

			// Recompute Status.
			self.updateQuestionPaletteStatus(index);
			self.updateSummaryCounters();
		},

		/**
		 * Candidate clicks an option choice.
		 */
		selectOption: function (optKey) {
			var self = this;
			var q = self.questions[self.currentIndex];
			if (!q) return;

			if (self.isImmediate) {
				// Anti-cheat: prevent changing response repeatedly if already answered/locked
				if (self.feedbackData[q.id]) {
					return;
				}

				// Immediately disable options to prevent rapid multi-clicks
				$('.iaq-option-item').addClass('is-disabled');
				$('.iaq-option-item[data-opt-key="' + optKey + '"]').addClass('is-selected');

				var curAns = self.answers[q.id] || { selected: null, isMarked: 0, visited: true };
				curAns.selected = optKey;
				curAns.visited = true;
				self.answers[q.id] = curAns;

				var $statusTag = $('#iaq-live-save-status');
				$statusTag.addClass('is-saving').find('.iaq-save-text').text(
					(window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.saving) ?
					window.iaqQuizData.i18n.saving :
					'Saving...'
				);

				function applyImmediateFeedback(fb) {
					if (self.feedbackData[q.id] && self.feedbackData[q.id].is_applied) {
						return;
					}
					fb.is_applied = true;
					self.feedbackData[q.id] = fb;
					curAns.selected = fb.selected;
					self.answers[q.id] = curAns;

					if (fb.correct_option) q.correct_option = fb.correct_option;
					if (fb.explanation) q.explanation = fb.explanation;

					var isCor = !!fb.is_correct;
					if (isCor) {
						self.correctCount++;
						self.currentStreak++;
						if (self.currentStreak > self.highestStreak) {
							self.highestStreak = self.currentStreak;
						}

						// Streak milestones celebration (intelligent non-repetitive notifications)
						if (self.currentStreak === 1) {
							self.showCelebrationToast('Good start! 1 Correct Answer!', false);
						} else if (self.currentStreak === 2) {
							self.showCelebrationToast('Great! 2 correct in a row!', false);
						} else if (self.currentStreak === 3) {
							self.showCelebrationToast('🔥 3 in a Row! Excellent Performance!', true);
							self.triggerConfetti();
						} else if (self.currentStreak === 4) {
							self.showCelebrationToast('Superb! 4 in a Row!', false);
						} else if (self.currentStreak === 5) {
							self.showCelebrationToast('Amazing! 5 Correct Answers in a Row!', true);
							self.triggerConfetti();
						} else if (self.currentStreak === 10) {
							self.showCelebrationToast('Outstanding! 10 Correct Answers in a Row!', true);
							self.triggerConfetti();
						} else if (self.currentStreak === 15) {
							self.showCelebrationToast('Incredible! 15 Correct Answers in a Row!', true);
							self.triggerConfetti();
						} else if (self.currentStreak === 20) {
							self.showCelebrationToast('Unstoppable! 20 Correct Answers in a Row!', true);
							self.triggerConfetti();
						} else if (self.currentStreak > 20 && self.currentStreak % 5 === 0) {
							self.showCelebrationToast('Mastery! ' + self.currentStreak + ' Correct Answers in a Row!', true);
							self.triggerConfetti();
						}
					} else {
						self.incorrectCount++;
						if (self.currentStreak > 0) {
							self.showCelebrationToast("Don't give up! Keep practicing — you'll get the next one!", false);
						}
						self.currentStreak = 0;
					}

					self.updatePracticeDashboard();

					// Highlight options with accessible tags and distinct colors
					$('.iaq-option-item').each(function () {
						var k = $(this).data('opt-key');
						if (k === fb.correct_option) {
							$(this).addClass('is-immediate-correct');
							if (!$(this).find('.iaq-option-status-tag').length) {
								$(this).append('<span class="iaq-option-status-tag is-correct-tag">' + IAQ_ICONS.check + ' <span>Correct Answer</span></span>');
							}
						} else if (k === fb.selected && !fb.is_correct) {
							$(this).addClass('is-immediate-incorrect');
							if (!$(this).find('.iaq-option-status-tag').length) {
								$(this).append('<span class="iaq-option-status-tag is-incorrect-tag">' + IAQ_ICONS.cross + ' <span>Your Choice</span></span>');
							}
						}
					});

					// Render feedback box with explanation
					self.renderImmediateFeedbackBox(q, fb);
					$('#iaq-action-clear').hide();
					$('#iaq-action-mark').hide();

					// Update palette status and summary counters
					self.updateQuestionPaletteStatus(self.currentIndex);
					self.updateSummaryCounters();
				}

				// Check if question has preloaded correct_option (instant 0ms client-side evaluation)
				var hasClientKey = (q.correct_option && String(q.correct_option).trim() !== '');
				if (hasClientKey) {
					var isCor = (String(q.correct_option).trim().toUpperCase() === String(optKey).trim().toUpperCase());
					var clientFb = {
						question_id: q.id,
						selected: optKey,
						is_correct: isCor ? 1 : 0,
						correct_option: String(q.correct_option).trim().toUpperCase(),
						explanation: q.explanation || ''
					};
					applyImmediateFeedback(clientFb);
				}

				$.ajax({
					url: self.getAjaxUrl(),
					type: 'POST',
					xhrFields: { withCredentials: true },
					data: {
						action: 'iaq_save_answer',
						attempt_id: self.attemptId,
						question_id: q.id,
						selected_option: optKey,
						is_marked: curAns.isMarked,
						time_spent: self.timeSpentSeconds,
						nonce: self.getNonce()
					},
					dataType: 'json',
					success: function (res) {
						$statusTag.removeClass('is-saving').find('.iaq-save-text').text(
							(window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.saved) ?
							window.iaqQuizData.i18n.saved :
							'Synchronized'
						);

						if (res && res.success && res.data) {
							if (res.data.correct_option) q.correct_option = res.data.correct_option;
							if (res.data.explanation) q.explanation = res.data.explanation;
							if (!hasClientKey) {
								applyImmediateFeedback(res.data);
							}
						} else if (!hasClientKey) {
							tryRestFallback(optKey);
						}
					},
					error: function () {
						if (!hasClientKey) {
							tryRestFallback(optKey);
						} else {
							$statusTag.removeClass('is-saving').find('.iaq-save-text').text('Saved Locally');
						}
					}
				});

				function tryRestFallback(chosenOpt) {
					var restUrl = '/wp-json/iaq/v1/quiz/' + self.quizId + '/questions';
					$.ajax({
						url: restUrl,
						type: 'GET',
						dataType: 'json',
						timeout: 3500,
						xhrFields: { withCredentials: true },
						success: function (qList) {
							var qs = (qList && qList.questions) ? qList.questions : (Array.isArray(qList) ? qList : []);
							var found = null;
							$.each(qs, function (_, item) {
								if (parseInt(item.id, 10) === parseInt(q.id, 10)) {
									found = item;
								}
							});
							if (found && (found.correct_option || found.demo_answer)) {
								var cOpt = (found.correct_option || found.demo_answer || '').toUpperCase().trim();
								var cExp = found.explanation || found.demo_explanation || '';
								var isC = (chosenOpt.toUpperCase().trim() === cOpt) ? 1 : 0;
								applyImmediateFeedback({
									question_id: q.id,
									selected: chosenOpt,
									is_correct: isC,
									correct_option: cOpt,
									explanation: cExp
								});
								$statusTag.removeClass('is-saving').find('.iaq-save-text').text('Synchronized');
							} else {
								$statusTag.removeClass('is-saving').find('.iaq-save-text').text('Offline');
							}
						},
						error: function () {
							$statusTag.removeClass('is-saving').find('.iaq-save-text').text('Offline');
						}
					});
				}
				return;
				return;
			}

			// Standard Mode (Mock Tests / After Submission Mode)
			var curAns = self.answers[q.id] || { selected: null, isMarked: 0, visited: true };

			// Toggle or set selection.
			curAns.selected = optKey;
			curAns.visited = true;
			self.answers[q.id] = curAns;

			// Update visual option list.
			$('.iaq-option-item').removeClass('is-selected');
			$('.iaq-option-item[data-opt-key="' + optKey + '"]').addClass('is-selected');

			// Trigger Auto-Save.
			self.autoSaveCurrent();

			// Update palette status.
			self.updateQuestionPaletteStatus(self.currentIndex);
			self.updateSummaryCounters();
		},

		/**
		 * Candidate clears option choice.
		 */
		clearSelection: function () {
			var self = this;
			var q = self.questions[self.currentIndex];
			if (!q) return;

			// In immediate feedback mode, locked answers cannot be cleared.
			if (self.isImmediate && self.feedbackData[q.id]) {
				return;
			}

			var curAns = self.answers[q.id] || { selected: null, isMarked: 0, visited: true };
			curAns.selected = null;
			self.answers[q.id] = curAns;

			$('.iaq-option-item').removeClass('is-selected');

			self.autoSaveCurrent();
			self.updateQuestionPaletteStatus(self.currentIndex);
			self.updateSummaryCounters();
		},

		/**
		 * Save & advance to next question.
		 */
		saveAndNext: function () {
			var self = this;
			if (!self.isImmediate) {
				self.autoSaveCurrent();
			}
			if (self.currentIndex < self.questions.length - 1) {
				self.renderQuestion(self.currentIndex + 1);
			} else {
				self.openSubmitModal();
			}
		},

		/**
		 * Mark current question for review and advance.
		 */
		markForReviewAndNext: function () {
			var self = this;
			var q = self.questions[self.currentIndex];
			if (!q) return;

			var curAns = self.answers[q.id] || { selected: null, isMarked: 0, visited: true };
			curAns.isMarked = curAns.isMarked ? 0 : 1;
			curAns.visited = true;
			self.answers[q.id] = curAns;

			self.autoSaveCurrent();
			self.updateQuestionPaletteStatus(self.currentIndex);
			self.updateSummaryCounters();

			if (self.currentIndex < self.questions.length - 1) {
				self.renderQuestion(self.currentIndex + 1);
			} else {
				self.renderQuestion(self.currentIndex);
			}
		},

		/**
		 * Previous question.
		 */
		prevQuestion: function () {
			var self = this;
			if (self.currentIndex > 0) {
				self.renderQuestion(self.currentIndex - 1);
			}
		},

		/**
		 * Direct jump to a specific question via palette button.
		 */
		jumpToQuestion: function (targetIndex) {
			var self = this;
			self.autoSaveCurrent();
			self.renderQuestion(targetIndex);
		},

		/**
		 * Send answer selection live via AJAX heartbeat.
		 */
		autoSaveCurrent: function () {
			var self = this;
			if (!self.attemptId) return;

			var q = self.questions[self.currentIndex];
			if (!q) return;

			var ans = self.answers[q.id] || { selected: null, isMarked: 0 };
			var $statusTag = $('#iaq-live-save-status');

			$statusTag.addClass('is-saving').find('.iaq-save-text').text(
				(window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.saving) ?
				window.iaqQuizData.i18n.saving :
				'Saving...'
			);

			$.ajax({
				url: self.getAjaxUrl(),
				type: 'POST',
				xhrFields: { withCredentials: true },
				data: {
					action: 'iaq_save_answer',
					attempt_id: self.attemptId,
					question_id: q.id,
					selected_option: ans.selected || '',
					is_marked: ans.isMarked,
					time_spent: self.timeSpentSeconds,
					nonce: self.getNonce()
				},
				dataType: 'json',
				success: function (res) {
					$statusTag.removeClass('is-saving').find('.iaq-save-text').text(
						(window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.saved) ?
						window.iaqQuizData.i18n.saved :
						'Synchronized'
					);
				},
				error: function () {
					$statusTag.removeClass('is-saving').find('.iaq-save-text').text('Offline');
				}
			});
		},

		/**
		 * Update palette button status color for a given question index.
		 */
		updateQuestionPaletteStatus: function (index) {
			var self = this;
			var q = self.questions[index];
			if (!q) return;

			var ans = self.answers[q.id] || { selected: null, isMarked: 0, visited: false };
			var $btn = $('#iaq-pal-btn-' + index);

			$btn.removeClass('is-answered is-unanswered is-not-visited is-marked is-ans-marked');

			var hasAnswer = !!(ans.selected && String(ans.selected).trim() !== '');
			var isVisited = !!ans.visited;
			var isMarked = !!(ans.isMarked && ans.isMarked !== 0);

			if (hasAnswer && isMarked) {
				$btn.addClass('is-ans-marked');
			} else if (hasAnswer) {
				$btn.addClass('is-answered');
			} else if (isMarked) {
				$btn.addClass('is-marked');
			} else if (isVisited) {
				$btn.addClass('is-unanswered');
			} else {
				$btn.addClass('is-not-visited');
			}
		},

		/**
		 * Update palette colors for all questions.
		 */
		updateAllPaletteStatuses: function () {
			var self = this;
			$.each(self.questions, function (i) {
				self.updateQuestionPaletteStatus(i);
			});
			self.updateSummaryCounters();
		},

		/**
		 * Calculate and refresh live summary status counts on the palette.
		 * Dynamically partitions all questions so Answered + Not Answered + Not Visited === total questions.
		 */
		updateSummaryCounters: function () {
			var self = this;
			var countAns = 0;
			var countUnans = 0;
			var countNotVisited = 0;
			var countMarked = 0;
			var countAnsMarked = 0;

			$.each(self.questions, function (i, q) {
				var ans = self.answers[q.id] || { selected: null, isMarked: 0, visited: false };
				var hasAnswer = !!(ans.selected && String(ans.selected).trim() !== '');
				var isVisited = !!ans.visited;
				var isMarked = !!(ans.isMarked && ans.isMarked !== 0);

				// Primary status totals: Strictly partition questions
				// Answered + Not Answered + Not Visited === total questions
				if (hasAnswer) {
					countAns++;
				} else if (isVisited) {
					countUnans++;
				} else {
					countNotVisited++;
				}

				// Review categories
				if (isMarked) {
					if (hasAnswer) {
						countAnsMarked++;
					} else {
						countMarked++;
					}
				}
			});

			$('#iaq-count-answered').text(countAns);
			$('#iaq-count-unanswered').text(countUnans);
			$('#iaq-count-not-visited').text(countNotVisited);
			$('#iaq-count-marked').text(countMarked);
			$('#iaq-count-ans-marked').text(countAnsMarked);
		},

		/**
		 * Timer countdown ticker.
		 */
		initTimer: function () {
			var self = this;
			if (null === self.remainingSeconds) {
				// Untimed quiz
				$('#iaq-timer-digits').text('Untimed');
				return;
			}

			if (self.timerInterval) {
				clearInterval(self.timerInterval);
			}

			self.updateTimerDisplay();

			self.timerInterval = setInterval(function () {
				self.remainingSeconds--;
				self.timeSpentSeconds++;

				self.updateTimerDisplay();

				// Expiry check
				if (self.remainingSeconds <= 0) {
					clearInterval(self.timerInterval);
					self.remainingSeconds = 0;
					self.updateTimerDisplay();
					self.handleTimeExpired();
				}
			}, 1000);
		},

		/**
		 * Update the digital timer string and visual warning pulses.
		 */
		updateTimerDisplay: function () {
			var self = this;
			if (null === self.remainingSeconds) return;

			var s = Math.max(0, self.remainingSeconds);
			var hours = Math.floor(s / 3600);
			var mins = Math.floor((s % 3600) / 60);
			var secs = s % 60;

			var str = '';
			if (hours > 0) {
				str = (hours < 10 ? '0' : '') + hours + ':' +
					  (mins < 10 ? '0' : '') + mins + ':' +
					  (secs < 10 ? '0' : '') + secs;
			} else {
				str = (mins < 10 ? '0' : '') + mins + ':' +
					  (secs < 10 ? '0' : '') + secs;
			}

			$('#iaq-timer-digits').text(str);

			var $clock = $('#iaq-timer-container');
			if (s <= 60) {
				$clock.removeClass('is-warning').addClass('is-critical');
			} else if (s <= 300) {
				$clock.addClass('is-warning').removeClass('is-critical');
			} else {
				$clock.removeClass('is-warning is-critical');
			}
		},

		/**
		 * Time expiry handler: auto submits test.
		 */
		handleTimeExpired: function () {
			var self = this;
			if (self.isSubmitting) return;

			$('#iaq-submit-modal').hide();
			$('#iaq-timeout-modal').fadeIn(200);

			setTimeout(function () {
				self.submitAttempt('timed_out');
			}, 1500);
		},

		/**
		 * Open the final submission confirmation modal with live tally.
		 */
		openSubmitModal: function () {
			var self = this;
			var total = self.questions.length;
			var answered = 0;
			var notAnswered = 0;
			var notVisited = 0;
			var marked = 0;
			var ansMarked = 0;

			$.each(self.questions, function (i, q) {
				var ans = self.answers[q.id] || { selected: null, isMarked: 0, visited: false };
				var hasAnswer = !!(ans.selected && String(ans.selected).trim() !== '');
				var isVisited = !!ans.visited;
				var isMarked = !!(ans.isMarked && ans.isMarked !== 0);

				if (hasAnswer) {
					answered++;
				} else if (isVisited) {
					notAnswered++;
				} else {
					notVisited++;
				}

				if (isMarked) {
					if (hasAnswer) {
						ansMarked++;
					} else {
						marked++;
					}
				}
			});

			$('#iaq-modal-total-q').text(total);
			$('#iaq-modal-answered-q').text(answered);
			$('#iaq-modal-unanswered-q').text(notAnswered);
			$('#iaq-modal-not-visited-q').text(notVisited);
			$('#iaq-modal-marked-q').text(marked);
			$('#iaq-modal-ans-marked-q').text(ansMarked);

			var unattempted = notAnswered + notVisited;
			if (unattempted > 0) {
				$('#iaq-modal-warning').show();
			} else {
				$('#iaq-modal-warning').hide();
			}

			$('#iaq-submit-modal').fadeIn(200);
		},

		/**
		 * Final submission of examination to server.
		 */
		submitAttempt: function (forcedStatus) {
			var self = this;
			if (self.isSubmitting) return;
			self.isSubmitting = true;

			if (self.timerInterval) {
				clearInterval(self.timerInterval);
			}

			var $btn = $('#iaq-modal-confirm-submit');
			$btn.prop('disabled', true).text(
				(window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.submitting) ?
				window.iaqQuizData.i18n.submitting :
				'Submitting...'
			);

			// Calculate local stats defensively
			var totalQ = self.questions ? self.questions.length : 0;
			var correctCount = self.correctCount || 0;
			var incorrectCount = self.incorrectCount || 0;
			var attempted = correctCount + incorrectCount;
			var unattempted = Math.max(0, totalQ - attempted);
			var acc = attempted > 0 ? ((correctCount / attempted) * 100).toFixed(1) : 0;

			$.ajax({
				url: self.getAjaxUrl(),
				type: 'POST',
				xhrFields: { withCredentials: true },
				data: {
					action: 'iaq_submit_quiz_attempt',
					attempt_id: self.attemptId,
					time_spent: self.timeSpentSeconds,
					answers: JSON.stringify(self.answers),
					correct_count: correctCount,
					incorrect_count: incorrectCount,
					unattempted: unattempted,
					nonce: self.getNonce()
				},
				dataType: 'json',
				success: function (res) {
					$('#iaq-submit-modal').hide();
					$('#iaq-timeout-modal').hide();

					if (res && res.success && res.data) {
						if (res.data.questions && res.data.questions.length > 0) {
							self.reviewQuestions = res.data.questions;
						}
						// If server returned valid scorecard metrics or if practice/instant mode
						self.renderScorecard(res.data);
					} else {
						// Fallback to local scorecard
						self.renderScorecard({
							score: correctCount,
							total_marks: totalQ,
							percentage: acc,
							attempted: attempted,
							total_questions: totalQ,
							correct_answers: correctCount,
							incorrect_answers: incorrectCount,
							unattempted_questions: unattempted,
							time_spent_seconds: self.timeSpentSeconds,
							passed: (acc >= 40)
						});
					}
				},
				error: function (xhr) {
					$('#iaq-submit-modal').hide();
					$('#iaq-timeout-modal').hide();
					// Resilient fallback for immediate / practice or network hiccup: ALWAYS render scorecard accurately
					self.renderScorecard({
						score: correctCount,
						total_marks: totalQ,
						percentage: acc,
						attempted: attempted,
						total_questions: totalQ,
						correct_answers: correctCount,
						incorrect_answers: incorrectCount,
						unattempted_questions: unattempted,
						time_spent_seconds: self.timeSpentSeconds,
						passed: (acc >= 40)
					});
				}
			});
		},

		/**
		 * Display scorecard upon test completion.
		 */
		renderScorecard: function (sc) {
			var self = this;
			if (!sc) sc = {};
			self.lastScorecard = sc;

			$('#iaq-examination-portal').slideUp(200);
			var $res = $('#iaq-results-portal');

			$('#iaq-res-category').text(sc.exam_category || (window.iaqQuizData && window.iaqQuizData.category) || 'Examination');
			$('#iaq-res-title').text(sc.quiz_title || (window.iaqQuizData && window.iaqQuizData.title) || '');

			var totalQ = (sc.total_questions != null) ? parseInt(sc.total_questions, 10) : (self.questions ? self.questions.length : 0);
			var correctCount = (sc.correct_answers != null) ? parseInt(sc.correct_answers, 10) :
				((sc.correct_count != null) ? parseInt(sc.correct_count, 10) :
				((sc.correct != null) ? parseInt(sc.correct, 10) : (self.correctCount || 0)));
			var incorrectCount = (sc.incorrect_answers != null) ? parseInt(sc.incorrect_answers, 10) :
				((sc.incorrect_count != null) ? parseInt(sc.incorrect_count, 10) :
				((sc.incorrect != null) ? parseInt(sc.incorrect, 10) : (self.incorrectCount || 0)));
			var unattempted = (sc.unattempted_questions != null) ? parseInt(sc.unattempted_questions, 10) :
				((sc.unattempted != null) ? parseInt(sc.unattempted, 10) :
				Math.max(0, totalQ - (correctCount + incorrectCount)));

			var score = (sc.score != null) ? sc.score : correctCount;
			var totalMarks = (sc.total_marks != null) ? sc.total_marks : totalQ;
			var percent = (sc.percentage != null) ? sc.percentage : (totalMarks > 0 ? ((score / totalMarks) * 100).toFixed(1) : 0);

			var timeFormatted = sc.time_spent_formatted;
			if (!timeFormatted || timeFormatted === '--') {
				var secs = (sc.time_spent_seconds != null) ? sc.time_spent_seconds : (self.timeSpentSeconds || 0);
				if (secs > 0) {
					var m = Math.floor(secs / 60);
					var s = secs % 60;
					timeFormatted = (m < 10 ? '0' : '') + m + 'm ' + (s < 10 ? '0' : '') + s + 's';
				} else {
					timeFormatted = '--';
				}
			}

			var isPassed = (sc.passed != null) ? !!sc.passed : (percent >= (sc.pass_percentage || 40));
			var $verdict = $('#iaq-res-verdict');
			if (isPassed) {
				$verdict.removeClass('iaq-verdict-failed').addClass('iaq-verdict-passed').text('PASSED');
			} else {
				$verdict.removeClass('iaq-verdict-passed').addClass('iaq-verdict-failed').text('NEEDS IMPROVEMENT');
			}

			$('#iaq-res-score').text(score);
			$('#iaq-res-total-marks').text(totalMarks);
			$('#iaq-res-percent').text(percent);
			$('#iaq-res-correct').text(correctCount);
			$('#iaq-res-total-q').text(totalQ);
			$('#iaq-res-time').text(timeFormatted);

			$('#iaq-bd-correct').text(correctCount);
			$('#iaq-bd-incorrect').text(incorrectCount);
			$('#iaq-bd-unattempted').text(unattempted);

			var mistakes = (parseInt(incorrectCount, 10) || 0) + (parseInt(unattempted, 10) || 0);
			if (mistakes > 0) {
				$('#iaq-res-mistakes-count').text(mistakes);
				$('#iaq-res-retake-mistakes-btn').show();
			} else {
				$('#iaq-res-retake-mistakes-btn').hide();
			}

			// Generate WhatsApp & Telegram viral share URLs
			var shareQuizTitle = sc.quiz_title || (window.iaqQuizData && window.iaqQuizData.title) || 'Agricultural Examination';
			var shareTargetUrl = window.location.href.split('#')[0];
			var shareMsg = '🌾 IndianAgriExam CBT Mock Test 🎯\n' +
				'📝 Exam: ' + shareQuizTitle + '\n' +
				'📊 Score: ' + score + ' / ' + totalMarks + ' (' + percent + '%)\n' +
				'⭐ Accuracy: ' + correctCount + ' / ' + totalQ + ' Correct\n';
			if (sc.percentile != null) {
				shareMsg += '🏆 Percentile: ' + sc.percentile + 'th %ile\n';
			}
			shareMsg += '\nCan you beat my score? Test your preparation here:\n🔗 ' + shareTargetUrl;

			$('#iaq-res-share-whatsapp').attr('href', 'https://api.whatsapp.com/send?text=' + encodeURIComponent(shareMsg));
			$('#iaq-res-share-telegram').attr('href', 'https://t.me/share/url?url=' + encodeURIComponent(shareTargetUrl) + '&text=' + encodeURIComponent(shareMsg));

			if (sc.questions && sc.questions.length > 0) {
				self.reviewQuestions = sc.questions;
			}

			// Render Subject-Wise Mastery Analytics.
			var $subTable = $('#iaq-subject-tbody');
			$subTable.empty();

			if (sc.subject_breakdown && sc.subject_breakdown.length > 0) {
				$('#iaq-subject-analytics-section').show();
				$.each(sc.subject_breakdown, function (idx, sb) {
					var masteryClass = 'is-mastery-weak';
					if (sb.mastery_level === 'strong') {
						masteryClass = 'is-mastery-strong';
					} else if (sb.mastery_level === 'moderate') {
						masteryClass = 'is-mastery-moderate';
					}

					var $tr = $('<tr></tr>');
					$tr.append($('<td><strong>' + sb.subject_name + '</strong></td>'));
					$tr.append($('<td>' + sb.total_questions + '</td>'));
					$tr.append($('<td>' + sb.attempted + '</td>'));
					$tr.append($('<td><span class="iaq-score-pos">' + sb.correct + '</span></td>'));
					$tr.append($('<td><strong>' + sb.score + '</strong></td>'));
					$tr.append($('<td>' + sb.accuracy_percent + '%</td>'));
					$tr.append($('<td><span class="iaq-mastery-badge ' + masteryClass + '">' + sb.mastery_label + '</span></td>'));
					$subTable.append($tr);
				});
			} else {
				$('#iaq-subject-analytics-section').hide();
			}

			$res.fadeIn(300);

			$('html, body').animate({
				scrollTop: $res.offset().top - 20
			}, 300);
		},

		/**
		 * Build question-by-question review items locally from in-memory test state.
		 */
		buildLocalReviewQuestions: function () {
			var self = this;
			var review = [];
			if (!self.questions || 0 === self.questions.length) {
				return review;
			}

			$.each(self.questions, function (i, q) {
				var ans = self.answers[q.id] || {};
				var fb = self.feedbackData[q.id] || {};
				var selOpt = ans.selected || fb.selected || q.selected_option || q.selected || null;
				if (selOpt) selOpt = String(selOpt).trim().toUpperCase();

				var corOpt = q.correct_option || fb.correct_option || '';
				if (corOpt) corOpt = String(corOpt).trim().toUpperCase();

				var isCor = null;
				if (selOpt && corOpt) {
					isCor = (selOpt === corOpt) ? 1 : 0;
				} else if (fb.is_correct != null) {
					isCor = fb.is_correct ? 1 : 0;
				}

				var posMarks = q.marks ? parseFloat(q.marks) : 1.0;
				var negMarks = q.negative_marks ? parseFloat(q.negative_marks) : 0.25;
				var marksAwarded = 0.00;
				if (isCor === 1) {
					marksAwarded = posMarks;
				} else if (isCor === 0) {
					marksAwarded = -1 * Math.abs(negMarks);
				}

				var opts = [];
				if (q.options && Array.isArray(q.options) && q.options.length > 0) {
					opts = q.options;
				} else {
					opts = [
						{ key: 'A', text: q.option_a || '', text_hi: q.option_a_hi || '' },
						{ key: 'B', text: q.option_b || '', text_hi: q.option_b_hi || '' },
						{ key: 'C', text: q.option_c || '', text_hi: q.option_c_hi || '' },
						{ key: 'D', text: q.option_d || '', text_hi: q.option_d_hi || '' }
					];
				}

				review.push({
					index: i + 1,
					id: q.id,
					question_text: q.question_text || '',
					question_text_hi: q.question_text_hi || '',
					subject_name: q.subject_name || '',
					topic_name: q.topic_name || '',
					marks_awarded: marksAwarded >= 0 ? '+' + marksAwarded.toFixed(2) : marksAwarded.toFixed(2),
					selected_option: selOpt,
					correct_option: corOpt,
					explanation: q.explanation || fb.explanation || '',
					is_correct: isCor,
					options: opts,
					option_a: q.option_a || (opts[0] ? opts[0].text : ''),
					option_b: q.option_b || (opts[1] ? opts[1].text : ''),
					option_c: q.option_c || (opts[2] ? opts[2].text : ''),
					option_d: q.option_d || (opts[3] ? opts[3].text : '')
				});
			});

			return review;
		},

		/**
		 * Display detailed question solutions portal.
		 */
		displaySolutionsPortal: function () {
			var self = this;
			if (!self.reviewQuestions || 0 === self.reviewQuestions.length) {
				self.reviewQuestions = self.buildLocalReviewQuestions();
			}

			var quizTitle = (self.lastScorecard && self.lastScorecard.quiz_title) ?
				self.lastScorecard.quiz_title :
				((window.iaqQuizData && window.iaqQuizData.title) ? window.iaqQuizData.title : '');
			$('#iaq-sol-quiz-title').text(quizTitle);

			var total = self.reviewQuestions.length;
			var correct = 0;
			var incorrect = 0;
			var unattempted = 0;

			$.each(self.reviewQuestions, function (i, rq) {
				if (rq.is_correct === 1) {
					correct++;
				} else if (rq.is_correct === 0) {
					incorrect++;
				} else {
					unattempted++;
				}
			});

			$('#iaq-tab-count-all').text(total);
			$('#iaq-tab-count-correct').text(correct);
			$('#iaq-tab-count-incorrect').text(incorrect);
			$('#iaq-tab-count-unattempted').text(unattempted);

			$('.iaq-filter-tab').removeClass('is-active');
			$('.iaq-filter-tab[data-filter="all"]').addClass('is-active');

			self.renderSolutionsList('all');

			$('#iaq-results-portal').slideUp(200);
			$('#iaq-solutions-portal').fadeIn(300);

			$('html, body').animate({
				scrollTop: $('#iaq-solutions-portal').offset().top - 20
			}, 300);
		},

		/**
		 * Fetch and display question-by-question solutions and explanations.
		 */
		loadSolutionsReview: function () {
			var self = this;
			var $btn = $('#iaq-res-review-btn');

			// If we already have review questions, display immediately without network latency
			if (self.reviewQuestions && self.reviewQuestions.length > 0) {
				self.displaySolutionsPortal();
				return;
			}

			// If valid database attempt (< 9999000), fetch official server review
			if (self.attemptId && self.attemptId < 9999000) {
				$btn.prop('disabled', true);
				$.ajax({
					url: self.getAjaxUrl(),
					type: 'POST',
					xhrFields: { withCredentials: true },
					data: {
						action: 'iaq_get_attempt_review',
						attempt_id: self.attemptId,
						nonce: self.getNonce()
					},
					dataType: 'json',
					success: function (res) {
						$btn.prop('disabled', false);
						if (res && res.success && res.data && res.data.questions && res.data.questions.length > 0) {
							self.reviewQuestions = res.data.questions;
							if (res.data.scorecard && res.data.scorecard.quiz_title) {
								if (!self.lastScorecard) self.lastScorecard = {};
								self.lastScorecard.quiz_title = res.data.scorecard.quiz_title;
							}
							self.displaySolutionsPortal();
						} else {
							// Resilient fallback to local evaluation
							self.reviewQuestions = self.buildLocalReviewQuestions();
							self.displaySolutionsPortal();
						}
					},
					error: function () {
						$btn.prop('disabled', false);
						// Resilient fallback to local evaluation
						self.reviewQuestions = self.buildLocalReviewQuestions();
						self.displaySolutionsPortal();
					}
				});
				return;
			}

			// In-memory or immediate practice set: build locally and show immediately
			self.reviewQuestions = self.buildLocalReviewQuestions();
			self.displaySolutionsPortal();
		},

		/**
		 * Render the filtered solutions list.
		 */
		renderSolutionsList: function (filter) {
			var self = this;
			var $list = $('#iaq-solutions-list');
			$list.empty();

			if (!self.reviewQuestions || 0 === self.reviewQuestions.length) {
				$list.html('<div class="iaq-notice-box iaq-notice-info">No review questions available for this examination.</div>');
				return;
			}

			$.each(self.reviewQuestions, function (i, q) {
				var status = 'unattempted';
				if (q.is_correct === 1) {
					status = 'correct';
				} else if (q.is_correct === 0) {
					status = 'incorrect';
				}

				if (filter !== 'all' && filter !== status) {
					return;
				}

				var $card = $('<div class="iaq-solution-card is-status-' + status + '"></div>');

				// Header
				var $head = $('<div class="iaq-sol-card-header"></div>');
				var $badges = $('<div class="iaq-sol-badges"></div>');
				$badges.append($('<span class="iaq-sol-qnum">Question ' + q.index + '</span>'));
				if (q.subject_name) {
					$badges.append($('<span class="iaq-pill iaq-pill-category">' + q.subject_name + '</span>'));
				}

				var $statusBadge = $('<span class="iaq-sol-status-badge is-' + status + '"></span>');
				var marksTxt = q.marks_awarded != null ? q.marks_awarded : '0.00';
				if (typeof marksTxt === 'number') {
					marksTxt = (marksTxt >= 0 ? '+' : '') + marksTxt.toFixed(2);
				}
				if (status === 'correct') {
					$statusBadge.html(IAQ_ICONS.check + ' Correct (' + marksTxt + ')');
				} else if (status === 'incorrect') {
					$statusBadge.html(IAQ_ICONS.cross + ' Incorrect (' + marksTxt + ')');
				} else {
					$statusBadge.html(IAQ_ICONS.minus + ' Not Attempted (0.00)');
				}

				$head.append($badges).append($statusBadge);
				$card.append($head);

				// Question text (English and Hindi)
				var qHtml = cleanContentText(q.question_text);
				if (q.question_text_hi && q.question_text_hi !== q.question_text) {
					qHtml += '<div class="iaq-qtext-hi" style="margin-top:6px;color:#334155;font-weight:500;">' + cleanContentText(q.question_text_hi) + '</div>';
				}
				var $qText = $('<div class="iaq-sol-qtext"></div>').html(qHtml);
				$card.append($qText);

				// 4 Options Grid
				var $opts = $('<div class="iaq-sol-options-grid"></div>');
				var optList = [];
				if (q.options && Array.isArray(q.options) && q.options.length > 0) {
					optList = q.options;
				} else {
					optList = [
						{ key: 'A', text: cleanContentText(q.option_a), text_hi: cleanContentText(q.option_a_hi) },
						{ key: 'B', text: cleanContentText(q.option_b), text_hi: cleanContentText(q.option_b_hi) },
						{ key: 'C', text: cleanContentText(q.option_c), text_hi: cleanContentText(q.option_c_hi) },
						{ key: 'D', text: cleanContentText(q.option_d), text_hi: cleanContentText(q.option_d_hi) }
					];
				}

				var corKey = q.correct_option ? String(q.correct_option).trim().toUpperCase() : '';
				var selKey = q.selected_option ? String(q.selected_option).trim().toUpperCase() : '';

				$.each(optList, function (oi, opt) {
					var optKey = String(opt.key).trim().toUpperCase();
					var isCorrectKey = (optKey === corKey);
					var isSelectedKey = (optKey === selKey);

					var $optDiv = $('<div class="iaq-sol-option-item"></div>');
					if (isCorrectKey) {
						$optDiv.addClass('is-correct-answer');
					}
					if (isSelectedKey && !isCorrectKey) {
						$optDiv.addClass('is-user-wrong');
					}

					var $keyBadge = $('<span class="iaq-sol-opt-key">' + optKey + '</span>');
					var optTxt = cleanContentText(opt.text);
					if (opt.text_hi && opt.text_hi !== opt.text) {
						optTxt += ' <span class="iaq-opt-hi" style="color:#64748b;font-weight:normal;">(' + cleanContentText(opt.text_hi) + ')</span>';
					}
					var $label = $('<span class="iaq-sol-opt-label"></span>').html(optTxt);
					var $tag = $('<span class="iaq-sol-opt-tag"></span>');

					if (isCorrectKey && isSelectedKey) {
						$tag.text('✓ Your Correct Choice');
					} else if (isCorrectKey) {
						$tag.text('✓ Correct Answer');
					} else if (isSelectedKey) {
						$tag.text('✗ Your Choice');
					}

					$optDiv.append($keyBadge).append($label);
					if ($tag.text()) {
						$optDiv.append($tag);
					}
					$opts.append($optDiv);
				});
				$card.append($opts);

				// Detailed Explanation Box
				var solExpl = cleanExplanationText(q.explanation);
				if (solExpl && $.trim(solExpl).length > 0) {
					var $expl = $('<div class="iaq-sol-explanation-box"></div>');
					$expl.append($('<h4 class="iaq-sol-expl-title">' + IAQ_ICONS.lightbulb + ' Detailed Scientific Explanation &amp; Reference:</h4>'));
					$expl.append($('<div class="iaq-sol-expl-content"></div>').html(solExpl));
					$card.append($expl);
				}

				$list.append($card);
			});
		},

		/**
		 * Start a fast in-memory mistakes drill containing only incorrect and unattempted questions.
		 */
		startMistakesDrill: function () {
			var self = this;
			if (!self.reviewQuestions || 0 === self.reviewQuestions.length) {
				self.reviewQuestions = self.buildLocalReviewQuestions();
			}

			var mistakeMap = {};
			var mistakeCount = 0;
			$.each(self.reviewQuestions, function (i, rq) {
				if (rq.is_correct !== 1) {
					mistakeMap[rq.id] = true;
					mistakeCount++;
				}
			});

			if (0 === mistakeCount) {
				alert('No incorrect or unattempted questions to re-attempt! Perfect score.');
				return;
			}

			// Filter existing questions to only mistake questions
			var filteredQuestions = [];
			var pIdx = 1;
			if (self.questions && self.questions.length > 0) {
				$.each(self.questions, function (i, q) {
					if (mistakeMap[q.id]) {
						var cloneQ = $.extend({}, q);
						cloneQ.index = pIdx++;
						cloneQ.selected = null;
						cloneQ.is_marked = 0;
						filteredQuestions.push(cloneQ);
					}
				});
			}

			// Fallback reconstruction from review questions
			if (0 === filteredQuestions.length && self.reviewQuestions && self.reviewQuestions.length > 0) {
				var pIdx2 = 1;
				$.each(self.reviewQuestions, function (i, rq) {
					if (mistakeMap[rq.id]) {
						filteredQuestions.push({
							index: pIdx2++,
							id: rq.id,
							question_text: rq.question_text || '',
							question_text_hi: rq.question_text_hi || '',
							subject_name: rq.subject_name || '',
							topic_name: rq.topic_name || '',
							marks: 1.0,
							negative_marks: 0.25,
							option_a: rq.option_a || '',
							option_b: rq.option_b || '',
							option_c: rq.option_c || '',
							option_d: rq.option_d || '',
							options: rq.options || [],
							selected: null,
							is_marked: 0
						});
					}
				});
			}

			if (0 === filteredQuestions.length) {
				window.location.reload();
				return;
			}

			// Reinitialize examination state with mistake questions
			self.questions = filteredQuestions;
			self.totalQuestions = filteredQuestions.length;
			self.answers = {};
			self.feedbackData = {};
			self.currentQIndex = 0;
			self.isSubmitting = false;
			self.reviewQuestions = [];
			self.attemptId = 9999000 + (self.quizId || 1);

			// Update question palette and header badges
			$('#iaq-total-questions').text(self.totalQuestions);
			var subjectLabel = 'Targeted Mistakes Practice (' + self.totalQuestions + ' Questions)';
			$('#iaq-active-subject-name').text(subjectLabel);

			// Hide results/solutions, show examination CBT workspace
			$('#iaq-results-portal').hide();
			$('#iaq-solutions-portal').hide();
			$('#iaq-examination-portal').fadeIn(300);

			self.renderCurrentQuestion();
			self.renderPalette();
			self.updateStats();

			$('html, body').animate({
				scrollTop: $('#iaq-examination-portal').offset().top - 20
			}, 300);
		}
	};

	var IAQDashboardEngine = {
		init: function () {
			var self = this;
			self.initFullScreenPortal();

			// Tab switching (Legacy support)
			$(document).on('click', '.iaq-tab-btn', function (e) {
				e.preventDefault();
				var targetTab = $(this).data('tab');
				$('.iaq-tab-btn').removeClass('is-active');
				$(this).addClass('is-active');

				$('.iaq-tab-pane').removeClass('is-active');
				$('#iaq-pane-' + targetTab).addClass('is-active');
			});

			// Dashboard Navigation Trigger (Sidebar & in-page link tabs)
			$(document).on('click', '.iaq-dash-nav-trigger', function (e) {
				e.preventDefault();
				var target = $(this).data('target');
				if (!target) {
					return;
				}

				// Warn candidate if an examination is actively running
				if (IAQQuizEngine.attemptId && !IAQQuizEngine.isSubmitting) {
					var warnMsg = (window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.leaveConfirm) ?
						window.iaqQuizData.i18n.leaveConfirm :
						'You have an examination in progress. Are you sure you want to navigate away? Your answers are autosaved, and your timer continues running.';
					if (!window.confirm(warnMsg)) {
						return;
					}
					$('#iaq-examination-portal').hide();
				}

				if (target === 'history' && !$('#iaq-pane-history').length) {
					// Fallback to dashboard history section if pane not present
					$('.iaq-dashboard-view-pane').removeClass('is-active');
					$('#iaq-pane-dashboard').addClass('is-active');
					$('.iaq-side-nav-item').removeClass('is-active');
					$('.iaq-side-nav-item[data-view="history"]').addClass('is-active');
					if ($('#iaq-history-section').length) {
						$('html, body').animate({
							scrollTop: $('#iaq-history-section').offset().top - 40
						}, 350);
					}
					return;
				}

				var $targetPane = $('#iaq-pane-' + target);
				if ($targetPane.length) {
					$('.iaq-dashboard-view-pane').removeClass('is-active');
					$targetPane.addClass('is-active');

					// Update active sidebar item
					$('.iaq-side-nav-item').removeClass('is-active');
					$('.iaq-side-nav-item[data-view="' + target + '"]').addClass('is-active');

					// Update active topbar navigation link
					$('.iaq-topbar-nav-links .iaq-top-link').removeClass('is-active');
					$('.iaq-topbar-nav-links .iaq-top-link[data-target="' + target + '"]').addClass('is-active');

					// Update browser location history for back/forward navigation
					if (window.history && window.history.pushState) {
						try {
							var tabUrl = new URL(window.location.href);
							tabUrl.searchParams.set('iaq_tab', target);
							tabUrl.hash = target;
							window.history.pushState({ iaq_tab: target }, '', tabUrl.toString());
						} catch (e) {}
					}

					// Scroll up smoothly to top of workspace
					var $dash = $('#iaq-student-dashboard');
					if ($dash.length) {
						$('html, body').animate({
							scrollTop: $dash.offset().top
						}, 250);
					} else {
						$('html, body').animate({
							scrollTop: 0
						}, 250);
					}
				}
			});

			// Browser Back / Forward native button handling (popstate)
			window.addEventListener('popstate', function () {
				try {
					if (IAQQuizEngine.attemptId && !IAQQuizEngine.isSubmitting) {
						var warnMsg = (window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.leaveConfirm) ?
							window.iaqQuizData.i18n.leaveConfirm :
							'You have an examination in progress. Are you sure you want to navigate away?';
						if (!window.confirm(warnMsg)) {
							return;
						}
						$('#iaq-examination-portal').hide();
					}

					var params = new URLSearchParams(window.location.search);
					var tab = params.get('iaq_tab');
					if (!tab && window.location.hash) {
						tab = window.location.hash.replace('#iaq-pane-', '').replace('#iaq-', '').replace('#', '');
					}
					if (!tab) {
						tab = 'dashboard';
					}
					if (tab === 'history') {
						$('.iaq-dash-nav-trigger[data-target="history"]').first().trigger('click');
					} else {
						var $pane = $('#iaq-pane-' + tab);
						if ($pane.length) {
							$('.iaq-dashboard-view-pane').removeClass('is-active');
							$pane.addClass('is-active');
							$('.iaq-side-nav-item').removeClass('is-active');
							$('.iaq-side-nav-item[data-view="' + tab + '"]').addClass('is-active');
							$('.iaq-topbar-nav-links .iaq-top-link').removeClass('is-active');
							$('.iaq-topbar-nav-links .iaq-top-link[data-target="' + tab + '"]').addClass('is-active');
						}
					}
				} catch (e) {}
			});

			// Initial URL params & hash navigation check
			try {
				var urlParams = new URLSearchParams(window.location.search);
				var requestedTab = urlParams.get('iaq_tab');
				if (requestedTab) {
					var $reqTrigger = $('.iaq-dash-nav-trigger[data-target="' + requestedTab + '"]').first();
					if ($reqTrigger.length) {
						$reqTrigger.trigger('click');
					}
				} else {
					var initialHash = window.location.hash;
					if (initialHash) {
						if (initialHash === '#iaq-custom-practice-section' || initialHash === '#custom-test') {
							$('.iaq-dash-nav-trigger[data-target="custom-test"]').first().trigger('click');
						} else if (initialHash === '#iaq-pane-practice-sets' || initialHash === '#practice-sets') {
							$('.iaq-dash-nav-trigger[data-target="practice-sets"]').first().trigger('click');
						} else if (initialHash === '#iaq-pane-mock-tests' || initialHash === '#mock-tests') {
							$('.iaq-dash-nav-trigger[data-target="mock-tests"]').first().trigger('click');
						} else if (initialHash === '#iaq-pane-pyq' || initialHash === '#pyq') {
							$('.iaq-dash-nav-trigger[data-target="pyq"]').first().trigger('click');
						} else if (initialHash === '#iaq-pane-examinations' || initialHash === '#examinations') {
							$('.iaq-dash-nav-trigger[data-target="examinations"]').first().trigger('click');
						} else if (initialHash === '#iaq-pane-subscriptions' || initialHash === '#subscriptions') {
							$('.iaq-dash-nav-trigger[data-target="subscriptions"]').first().trigger('click');
						} else if (initialHash === '#iaq-pane-profile' || initialHash === '#profile') {
							$('.iaq-dash-nav-trigger[data-target="profile"]').first().trigger('click');
						} else if (initialHash === '#iaq-bookmarks-section' || initialHash === '#bookmarks') {
							$('.iaq-dash-nav-trigger[data-target="bookmarks"]').first().trigger('click');
						} else if (initialHash === '#iaq-wrong-notebook-section' || initialHash === '#wrong-notebook') {
							$('.iaq-dash-nav-trigger[data-target="wrong-notebook"]').first().trigger('click');
						} else if (initialHash === '#iaq-study-streaks-section' || initialHash === '#study-streaks') {
							$('.iaq-dash-nav-trigger[data-target="study-streaks"]').first().trigger('click');
						} else if (initialHash === '#iaq-leaderboard-section' || initialHash === '#leaderboard') {
							$('.iaq-dash-nav-trigger[data-target="leaderboard"]').first().trigger('click');
						} else if (initialHash === '#iaq-history-section' || initialHash === '#history') {
							$('.iaq-dash-nav-trigger[data-target="history"]').first().trigger('click');
						}
					}
				}
			} catch (e) {}

			// Mobile drawer navigation toggle
			$(document).on('click', '#iaq-dash-menu-toggle', function (e) {
				e.preventDefault();
				$('.iaq-dash-sidebar').addClass('is-open');
				$('#iaq-dash-backdrop').addClass('is-open');
				$('body').addClass('iaq-noscroll-mobile');
			});

			$(document).on('click', '#iaq-sidebar-close-btn, #iaq-dash-backdrop, .iaq-dash-sidebar .iaq-dash-nav-trigger', function () {
				$('.iaq-dash-sidebar').removeClass('is-open');
				$('#iaq-dash-backdrop').removeClass('is-open');
				$('body').removeClass('iaq-noscroll-mobile');
			});

			// Smooth scroll trigger
			$(document).on('click', '.iaq-scroll-trigger', function (e) {
				e.preventDefault();
				$('.iaq-tab-btn[data-tab="custom-test"]').trigger('click');
				if ($('#iaq-custom-practice-section').length) {
					$('html, body').animate({
						scrollTop: $('#iaq-custom-practice-section').offset().top - 80
					}, 400);
				}
			});

			// Custom Test Form Submission
			$(document).on('submit', '#iaq-custom-test-form', function (e) {
				e.preventDefault();
				var $form = $(this);
				var $btn = $form.find('#iaq-btn-build-test');
				var $status = $('#iaq-ct-status-msg');
				var originalHtml = $btn.html();

				$btn.prop('disabled', true).html('<span class="iaq-spin" style="margin-right:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg></span>' + (window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.starting ? window.iaqQuizData.i18n.starting : 'Generating Custom Test...'));
				$status.css('color', '#0284c7').text('Scanning eligible questions pool...');

				var postData = $form.serializeArray();
				postData.push({ name: 'action', value: 'iaq_generate_custom_test' });
				postData.push({ name: 'nonce', value: (window.iaqQuizData ? window.iaqQuizData.nonce : '') });
				postData.push({ name: 'current_url', value: window.location.href });

				$.post(self.getAjaxUrl(), postData, function (res) {
					if (res && res.success && res.data && res.data.redirect_url) {
						$status.css('color', '#16a34a').text('Test generated! Launching...');
						window.location.href = res.data.redirect_url;
					} else {
						$btn.prop('disabled', false).html(originalHtml);
						var msg = (res && res.data && res.data.message) ? res.data.message : 'Error creating custom test.';
						$status.css('color', '#dc2626').text(msg);
					}
				}).fail(function () {
					$btn.prop('disabled', false).html(originalHtml);
					$status.css('color', '#dc2626').text('Network communication error. Please try again.');
				});
			});

			// Bookmark Remove
			$(document).on('click', '.iaq-remove-bookmark-btn', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var qid = $btn.data('question-id');
				var $card = $('#iaq-bookmark-row-' + qid);

				$.post(self.getAjaxUrl(), {
					action: 'iaq_toggle_bookmark',
					nonce: self.getNonce(),
					question_id: qid
				}, function (res) {
					if (res && res.success) {
						$card.fadeOut(200, function () {
							$card.remove();
							var count = $('#iaq-bookmarks-container .iaq-learning-item-card').length;
							$('#iaq-badge-bookmarks').text(count);
						});
					}
				});
			});

			// Bookmark Revision Quiz Start
			$(document).on('click', '#iaq-btn-start-revision-quiz', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var originalHtml = $btn.html();
				$btn.prop('disabled', true).html(IAQ_ICONS.spinner + ' Preparing Revision Drill...');

				$.post(self.getAjaxUrl(), {
					action: 'iaq_create_revision_quiz',
					nonce: self.getNonce(),
					current_url: window.location.href
				}, function (res) {
					if (res && res.success && res.data && res.data.redirect_url) {
						window.location.href = res.data.redirect_url;
					} else {
						$btn.prop('disabled', false).html(originalHtml);
						alert(res.data && res.data.message ? res.data.message : 'Could not generate revision quiz.');
					}
				}).fail(function () {
					$btn.prop('disabled', false).html(originalHtml);
					alert('Network communication error. Please try again.');
				});
			});

			// Wrong Notebook Mark Resolved
			$(document).on('click', '.iaq-resolve-wrong-btn', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var qid = $btn.data('question-id');
				var $card = $('#iaq-wrong-row-' + qid);

				$.post(self.getAjaxUrl(), {
					action: 'iaq_update_notebook_status',
					nonce: self.getNonce(),
					question_id: qid,
					status: 'resolved'
				}, function (res) {
					if (res && res.success) {
						$card.css('opacity', '0.5');
						$btn.html(IAQ_ICONS.check + ' Resolved!').prop('disabled', true);
					}
				});
			});

			// Wrong Notebook Remove
			$(document).on('click', '.iaq-remove-wrong-btn', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var qid = $btn.data('question-id');
				var $card = $('#iaq-wrong-row-' + qid);

				$.post(self.getAjaxUrl(), {
					action: 'iaq_remove_notebook_entry',
					nonce: self.getNonce(),
					question_id: qid
				}, function (res) {
					if (res && res.success) {
						$card.fadeOut(200, function () {
							$card.remove();
							var count = $('#iaq-wrong-container .iaq-learning-item-card').length;
							$('#iaq-badge-wrong').text(count);
						});
					}
				});
			});

			// Retry Wrong Quiz
			$(document).on('click', '#iaq-btn-retry-wrong-quiz', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var originalHtml = $btn.html();
				$btn.prop('disabled', true).html(IAQ_ICONS.spinner + ' Generating Retry Drill...');

				$.post(self.getAjaxUrl(), {
					action: 'iaq_create_retry_quiz',
					nonce: self.getNonce(),
					current_url: window.location.href
				}, function (res) {
					if (res && res.success && res.data && res.data.redirect_url) {
						window.location.href = res.data.redirect_url;
					} else {
						$btn.prop('disabled', false).html(originalHtml);
						alert(res.data && res.data.message ? res.data.message : 'Could not generate retry quiz.');
					}
				}).fail(function () {
					$btn.prop('disabled', false).html(originalHtml);
					alert('Network communication error. Please try again.');
				});
			});

			// Edit Goals Toggle
			$(document).on('click', '#iaq-btn-edit-goals', function () {
				$('#iaq-goals-modal').slideToggle(200);
			});

			$(document).on('click', '#iaq-btn-cancel-goals', function () {
				$('#iaq-goals-modal').slideUp(150);
			});

			// Goals Form Submission
			$(document).on('submit', '#iaq-goals-form', function (e) {
				e.preventDefault();
				var $form = $(this);
				var $btn = $form.find('button[type="submit"]');
				var $status = $('#iaq-goals-status-msg');
				var originalHtml = $btn.html();
				var tests = $('#iaq-goal-tests').val();
				var qs = $('#iaq-goal-qs').val();

				$btn.prop('disabled', true).html('<span class="iaq-spin" style="margin-right:6px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg></span> Saving...');

				$.post(self.getAjaxUrl(), {
					action: 'iaq_update_study_goals',
					nonce: self.getNonce(),
					weekly_tests: tests,
					weekly_questions: qs
				}, function (res) {
					if (res && res.success) {
						$status.css('color', '#16a34a').text('Goals updated successfully!');
						setTimeout(function () {
							window.location.reload();
						}, 600);
					} else {
						$btn.prop('disabled', false).html(originalHtml);
						$status.css('color', '#dc2626').text('Failed to update goals.');
					}
				}).fail(function () {
					$btn.prop('disabled', false).html(originalHtml);
					$status.css('color', '#dc2626').text('Network error. Please try again.');
				});
			});
		},

		/**
		 * Expand Elementor and theme parent containers so dashboard occupies full screen width.
		 */
		initFullScreenPortal: function () {
			var $portal = $('#iaq-student-portal, .iaq-student-portal');
			if (!$portal.length) {
				return;
			}

			// Add active class to body for horizontal overflow & scrollbar safety
			$('body').addClass('iaq-portal-active');

			// Expand immediate ancestor Elementor & theme wrapper containers
			$portal
				.parents('.elementor-section, .elementor-container, .elementor-column, .elementor-widget, .elementor-widget-container, .entry-content, .post-content, .site-content, .content-area')
				.addClass('iaq-elementor-fullscreen-parent');
		}
	};

	// On Document Ready
	$(document).ready(function () {
		IAQQuizEngine.init();
		IAQDashboardEngine.init();

		// Accordion folder toggle for Practice Sets, Mock Tests, and PYQs
		$(document).on('click', '.iaq-folder-header', function (e) {
			$(this).closest('.iaq-folder-card').toggleClass('is-collapsed');
		});
	});

})(jQuery);
