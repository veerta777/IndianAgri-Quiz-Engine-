/**
 * IndianAgri Quiz Engine - Client Examination State Machine
 * Handles real-time countdown timer, question palette, auto-save heartbeat, and submission.
 *
 * @package IndianAgri_Quiz_Engine
 */

(function ($) {
	'use strict';

	var IAQQuizEngine = {
		attemptId: 0,
		quizId: 0,
		questions: [],
		currentIndex: 0,
		answers: {},
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

			// Bind Start / Resume button on Overview Card.
			$(document).on('click', '#iaq-start-quiz-btn', function (e) {
				e.preventDefault();
				var qid = $(this).data('quiz-id') || self.quizId;
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

			$(document).on('click', '#iaq-action-next', function () {
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

			// Review Solutions button on results view.
			$(document).on('click', '#iaq-res-review-btn', function () {
				self.loadSolutionsReview();
			});

			// Back to Scorecard from solutions portal.
			$(document).on('click', '#iaq-sol-back-btn, #iaq-sol-bottom-back-btn', function () {
				$('#iaq-solutions-portal').slideUp(200);
				$('#iaq-results-portal').fadeIn(300);
				$('html, body').animate({
					scrollTop: $('#iaq-results-portal').offset().top - 20
				}, 300);
			});

			// Filter Tabs on solutions portal.
			$(document).on('click', '.iaq-filter-tab', function () {
				var filter = $(this).data('filter');
				$('.iaq-filter-tab').removeClass('is-active');
				$(this).addClass('is-active');
				self.renderSolutionsList(filter);
			});

			// Print Scorecard and Print Solutions.
			$(document).on('click', '#iaq-res-print-btn, #iaq-sol-print-btn', function () {
				window.print();
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

		/**
		 * Start or Resume Quiz Attempt Session via AJAX.
		 */
		startOrResumeAttempt: function (quizId) {
			var self = this;
			var $btn = $('#iaq-start-quiz-btn');

			$btn.prop('disabled', true).text(
				(window.iaqQuizData && window.iaqQuizData.i18n && window.iaqQuizData.i18n.starting) ?
				window.iaqQuizData.i18n.starting :
				'Initializing...'
			);

			$.ajax({
				url: window.iaqQuizData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'iaq_start_quiz_attempt',
					quiz_id: quizId,
					nonce: window.iaqQuizData.nonce
				},
				dataType: 'json',
				success: function (res) {
					if (res && res.success && res.data) {
						var d = res.data;
						self.attemptId = d.attempt_id;
						self.quizId = d.quiz_id;
						self.questions = d.questions || [];
						self.remainingSeconds = d.remaining_seconds;
						self.timeSpentSeconds = 0;

						// Pre-populate answers dictionary.
						self.answers = {};
						$.each(self.questions, function (i, q) {
							self.answers[q.id] = {
								selected: q.selected || null,
								isMarked: q.is_marked ? 1 : 0,
								visited: (i === 0) ? true : (q.selected ? true : false)
							};
						});

						// Switch UI to Examination Portal.
						$('#iaq-quiz-overview-' + quizId).slideUp(200);
						$('#iaq-examination-portal').fadeIn(300);

						// Initialize Palette Grid.
						self.buildPaletteGrid();

						// Render First Question.
						self.renderQuestion(0);

						// Start Timer.
						self.initTimer();

						// Scroll into view smoothly.
						$('html, body').animate({
							scrollTop: $('#iaq-examination-portal').offset().top - 20
						}, 300);

					} else {
						var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Error starting quiz.';
						alert(errMsg);
						$btn.prop('disabled', false).text('Start Examination Now \u2192');
					}
				},
				error: function (xhr) {
					var err = 'Connection error. Please try again.';
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						err = xhr.responseJSON.data.message;
					}
					alert(err);
					$btn.prop('disabled', false).text('Start Examination Now \u2192');
				}
			});
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
			$('#iaq-question-text').html(q.question_text);

			// Render Options.
			var $opts = $('#iaq-options-list');
			$opts.empty();

			$.each(q.options, function (oi, opt) {
				var isSelected = (userAns.selected === opt.key);
				var $item = $('<div class="iaq-option-item" data-opt-key="' + opt.key + '"></div>');
				if (isSelected) {
					$item.addClass('is-selected');
				}

				var $key = $('<span class="iaq-option-key">' + opt.key + '</span>');
				var $lbl = $('<span class="iaq-option-label"></span>').html(opt.text);

				$item.append($key).append($lbl);
				$opts.append($item);
			});

			// Prev button state.
			$('#iaq-action-prev').prop('disabled', (index === 0));

			// Next button label.
			if (index === self.questions.length - 1) {
				$('#iaq-action-next').html('Save & Review &rarr;');
			} else {
				$('#iaq-action-next').html('Save & Next &rarr;');
			}

			// Update Mark button text/state.
			if (userAns.isMarked) {
				$('#iaq-action-mark').html('<span class="dashicons dashicons-flag"></span> Unmark Review');
			} else {
				$('#iaq-action-mark').html('<span class="dashicons dashicons-flag"></span> Mark for Review &amp; Next');
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
			self.autoSaveCurrent();
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
			self.answers[q.id] = curAns;

			self.autoSaveCurrent();
			self.updateQuestionPaletteStatus(self.currentIndex);

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
				url: window.iaqQuizData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'iaq_save_answer',
					attempt_id: self.attemptId,
					question_id: q.id,
					selected_option: ans.selected || '',
					is_marked: ans.isMarked,
					time_spent: self.timeSpentSeconds,
					nonce: window.iaqQuizData.nonce
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

			if (ans.selected && ans.isMarked) {
				$btn.addClass('is-ans-marked');
			} else if (ans.selected) {
				$btn.addClass('is-answered');
			} else if (ans.isMarked) {
				$btn.addClass('is-marked');
			} else if (ans.visited) {
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
				if (ans.selected && ans.isMarked) {
					countAnsMarked++;
					countAns++;
				} else if (ans.selected) {
					countAns++;
				} else if (ans.isMarked) {
					countMarked++;
				} else if (ans.visited) {
					countUnans++;
				} else {
					countNotVisited++;
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
			var marked = 0;

			$.each(self.questions, function (i, q) {
				var ans = self.answers[q.id];
				if (ans && ans.selected) {
					answered++;
				}
				if (ans && ans.isMarked) {
					marked++;
				}
			});

			var unanswered = total - answered;

			$('#iaq-modal-total-q').text(total);
			$('#iaq-modal-answered-q').text(answered);
			$('#iaq-modal-unanswered-q').text(unanswered);
			$('#iaq-modal-marked-q').text(marked);

			if (unanswered > 0) {
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

			$.ajax({
				url: window.iaqQuizData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'iaq_submit_quiz_attempt',
					attempt_id: self.attemptId,
					time_spent: self.timeSpentSeconds,
					nonce: window.iaqQuizData.nonce
				},
				dataType: 'json',
				success: function (res) {
					$('#iaq-submit-modal').hide();
					$('#iaq-timeout-modal').hide();

					if (res && res.success && res.data) {
						self.renderScorecard(res.data);
					} else {
						var msg = (res && res.data && res.data.message) ? res.data.message : 'Error submitting test.';
						alert(msg);
						self.isSubmitting = false;
						$btn.prop('disabled', false).text('Yes, Submit Final Test \u2192');
					}
				},
				error: function (xhr) {
					self.isSubmitting = false;
					$btn.prop('disabled', false).text('Yes, Submit Final Test \u2192');
					alert('Network error during submission. Please try again.');
				}
			});
		},

		/**
		 * Display scorecard upon test completion.
		 */
		renderScorecard: function (sc) {
			var self = this;
			self.lastScorecard = sc;

			$('#iaq-examination-portal').slideUp(200);
			var $res = $('#iaq-results-portal');

			$('#iaq-res-category').text(sc.exam_category || 'Examination');
			$('#iaq-res-title').text(sc.quiz_title);

			var $verdict = $('#iaq-res-verdict');
			if (sc.passed) {
				$verdict.removeClass('iaq-verdict-failed').addClass('iaq-verdict-passed').text('PASSED');
			} else {
				$verdict.removeClass('iaq-verdict-passed').addClass('iaq-verdict-failed').text('NEEDS IMPROVEMENT');
			}

			$('#iaq-res-score').text(sc.score);
			$('#iaq-res-total-marks').text(sc.total_marks);
			$('#iaq-res-percent').text(sc.percentage);
			$('#iaq-res-correct').text(sc.correct_answers);
			$('#iaq-res-total-q').text(sc.total_questions);
			$('#iaq-res-time').text(sc.time_spent_formatted || '--');

			$('#iaq-bd-correct').text(sc.correct_answers);
			$('#iaq-bd-incorrect').text(sc.incorrect_answers);
			$('#iaq-bd-unattempted').text(sc.unattempted_questions);

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
		 * Fetch and display question-by-question solutions and explanations.
		 */
		loadSolutionsReview: function () {
			var self = this;
			var $btn = $('#iaq-res-review-btn');
			$btn.prop('disabled', true);

			$.ajax({
				url: window.iaqQuizData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'iaq_get_attempt_review',
					attempt_id: self.attemptId,
					nonce: window.iaqQuizData.nonce
				},
				dataType: 'json',
				success: function (res) {
					$btn.prop('disabled', false);
					if (res && res.success && res.data) {
						self.reviewQuestions = res.data.questions || [];
						$('#iaq-sol-quiz-title').text(res.data.scorecard.quiz_title);

						// Calculate filter counts.
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
					} else {
						var msg = (res && res.data && res.data.message) ? res.data.message : 'Error loading solutions.';
						alert(msg);
					}
				},
				error: function () {
					$btn.prop('disabled', false);
					alert('Failed to retrieve question solutions.');
				}
			});
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
				if (status === 'correct') {
					$statusBadge.html('<span class="dashicons dashicons-yes"></span> Correct (+' + q.marks_awarded + ')');
				} else if (status === 'incorrect') {
					$statusBadge.html('<span class="dashicons dashicons-no"></span> Incorrect (' + q.marks_awarded + ')');
				} else {
					$statusBadge.html('<span class="dashicons dashicons-minus"></span> Not Attempted (0.00)');
				}

				$head.append($badges).append($statusBadge);
				$card.append($head);

				// Question text
				var $qText = $('<div class="iaq-sol-qtext"></div>').html(q.question_text);
				$card.append($qText);

				// 4 Options
				var $opts = $('<div class="iaq-sol-options-grid"></div>');
				var optList = [
					{ key: 'A', text: q.option_a },
					{ key: 'B', text: q.option_b },
					{ key: 'C', text: q.option_c },
					{ key: 'D', text: q.option_d }
				];

				$.each(optList, function (oi, opt) {
					var isCorrectKey = (opt.key === q.correct_option);
					var isSelectedKey = (opt.key === q.selected_option);

					var $optDiv = $('<div class="iaq-sol-option-item"></div>');
					if (isCorrectKey) {
						$optDiv.addClass('is-correct-answer');
					}
					if (isSelectedKey && !isCorrectKey) {
						$optDiv.addClass('is-user-wrong');
					}

					var $keyBadge = $('<span class="iaq-sol-opt-key">' + opt.key + '</span>');
					var $label = $('<span class="iaq-sol-opt-label"></span>').html(opt.text);
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
				if (q.explanation) {
					var $expl = $('<div class="iaq-sol-explanation-box"></div>');
					$expl.append($('<h4 class="iaq-sol-expl-title"><span class="dashicons dashicons-lightbulb"></span> Detailed Scientific Explanation &amp; Reference:</h4>'));
					$expl.append($('<div class="iaq-sol-expl-content"></div>').html(q.explanation));
					$card.append($expl);
				}

				$list.append($card);
			});
		}
	};

	var IAQDashboardEngine = {
		init: function () {
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

				if (target === 'history') {
					// Activate dashboard pane
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

					// Scroll up smoothly to top of workspace
					var $dash = $('#iaq-student-dashboard');
					if ($dash.length) {
						$('html, body').animate({
							scrollTop: $dash.offset().top
						}, 250);
					}
				}
			});

			// Initial hash navigation check
			var initialHash = window.location.hash;
			if (initialHash) {
				if (initialHash === '#iaq-custom-practice-section') {
					$('.iaq-dash-nav-trigger[data-target="custom-test"]').first().trigger('click');
				} else if (initialHash === '#iaq-bookmarks-section') {
					$('.iaq-dash-nav-trigger[data-target="bookmarks"]').first().trigger('click');
				} else if (initialHash === '#iaq-wrong-notebook-section') {
					$('.iaq-dash-nav-trigger[data-target="wrong-notebook"]').first().trigger('click');
				} else if (initialHash === '#iaq-study-streaks-section') {
					$('.iaq-dash-nav-trigger[data-target="study-streaks"]').first().trigger('click');
				} else if (initialHash === '#iaq-leaderboard-section') {
					$('.iaq-dash-nav-trigger[data-target="leaderboard"]').first().trigger('click');
				} else if (initialHash === '#iaq-history-section') {
					$('.iaq-dash-nav-trigger[data-target="history"]').first().trigger('click');
				}
			}

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

				$btn.prop('disabled', true).text('Generating Custom Test...');
				$status.css('color', '#0284c7').text('Scanning eligible questions pool...');

				var postData = $form.serializeArray();
				postData.push({ name: 'action', value: 'iaq_generate_custom_test' });
				postData.push({ name: 'nonce', value: (window.iaqQuizData ? window.iaqQuizData.nonce : '') });

				$.post(window.iaqQuizData ? window.iaqQuizData.ajaxUrl : '/wp-admin/admin-ajax.php', postData, function (res) {
					if (res && res.success && res.data && res.data.redirect_url) {
						$status.css('color', '#16a34a').text('Test generated! Launching...');
						window.location.href = res.data.redirect_url;
					} else {
						$btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> Generate & Launch Custom Test &rarr;');
						var msg = (res && res.data && res.data.message) ? res.data.message : 'Error creating custom test.';
						$status.css('color', '#dc2626').text(msg);
					}
				}).fail(function () {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> Generate & Launch Custom Test &rarr;');
					$status.css('color', '#dc2626').text('Network communication error. Please try again.');
				});
			});

			// Bookmark Remove
			$(document).on('click', '.iaq-remove-bookmark-btn', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var qid = $btn.data('question-id');
				var $card = $('#iaq-bookmark-row-' + qid);

				$.post(window.iaqQuizData ? window.iaqQuizData.ajaxUrl : '/wp-admin/admin-ajax.php', {
					action: 'iaq_toggle_bookmark',
					nonce: window.iaqQuizData ? window.iaqQuizData.nonce : '',
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
				$btn.prop('disabled', true).text('Preparing Revision Drill...');

				$.post(window.iaqQuizData ? window.iaqQuizData.ajaxUrl : '/wp-admin/admin-ajax.php', {
					action: 'iaq_create_revision_quiz',
					nonce: window.iaqQuizData ? window.iaqQuizData.nonce : ''
				}, function (res) {
					if (res && res.success && res.data && res.data.redirect_url) {
						window.location.href = res.data.redirect_url;
					} else {
						$btn.prop('disabled', false).text('Practice Bookmarked Questions');
						alert(res.data && res.data.message ? res.data.message : 'Could not generate revision quiz.');
					}
				});
			});

			// Wrong Notebook Mark Resolved
			$(document).on('click', '.iaq-resolve-wrong-btn', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var qid = $btn.data('question-id');
				var $card = $('#iaq-wrong-row-' + qid);

				$.post(window.iaqQuizData ? window.iaqQuizData.ajaxUrl : '/wp-admin/admin-ajax.php', {
					action: 'iaq_update_notebook_status',
					nonce: window.iaqQuizData ? window.iaqQuizData.nonce : '',
					question_id: qid,
					status: 'resolved'
				}, function (res) {
					if (res && res.success) {
						$card.css('opacity', '0.5');
						$btn.html('<span class="dashicons dashicons-yes"></span> Resolved!').prop('disabled', true);
					}
				});
			});

			// Wrong Notebook Remove
			$(document).on('click', '.iaq-remove-wrong-btn', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var qid = $btn.data('question-id');
				var $card = $('#iaq-wrong-row-' + qid);

				$.post(window.iaqQuizData ? window.iaqQuizData.ajaxUrl : '/wp-admin/admin-ajax.php', {
					action: 'iaq_remove_notebook_entry',
					nonce: window.iaqQuizData ? window.iaqQuizData.nonce : '',
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
				$btn.prop('disabled', true).text('Generating Retry Drill...');

				$.post(window.iaqQuizData ? window.iaqQuizData.ajaxUrl : '/wp-admin/admin-ajax.php', {
					action: 'iaq_create_retry_quiz',
					nonce: window.iaqQuizData ? window.iaqQuizData.nonce : ''
				}, function (res) {
					if (res && res.success && res.data && res.data.redirect_url) {
						window.location.href = res.data.redirect_url;
					} else {
						$btn.prop('disabled', false).text('Retry Incorrect Questions');
						alert(res.data && res.data.message ? res.data.message : 'Could not generate retry quiz.');
					}
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
				var tests = $('#iaq-goal-tests').val();
				var qs = $('#iaq-goal-qs').val();

				$.post(window.iaqQuizData ? window.iaqQuizData.ajaxUrl : '/wp-admin/admin-ajax.php', {
					action: 'iaq_update_study_goals',
					nonce: window.iaqQuizData ? window.iaqQuizData.nonce : '',
					weekly_tests: tests,
					weekly_questions: qs
				}, function (res) {
					if (res && res.success) {
						$('#iaq-goals-modal').slideUp(150);
						window.location.reload();
					}
				});
			});
		}
	};

	// On Document Ready
	$(document).ready(function () {
		IAQQuizEngine.init();
		IAQDashboardEngine.init();
	});

})(jQuery);
