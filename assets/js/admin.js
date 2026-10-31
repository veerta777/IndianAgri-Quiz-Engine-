/**
 * IndianAgri Quiz Engine - Admin Interactions & AJAX Handlers
 */
(function ($) {
	'use strict';

	$(document).ready(function () {

		/* =========================================================================
		 * 1. Check All Checkboxes & Bulk Action Guard
		 * ========================================================================= */
		$('#iaq-check-all').on('change', function () {
			var isChecked = $(this).is(':checked');
			$('.iaq-row-checkbox').prop('checked', isChecked);
		});

		$('.iaq-row-checkbox').on('change', function () {
			var total = $('.iaq-row-checkbox').length;
			var checked = $('.iaq-row-checkbox:checked').length;
			$('#iaq-check-all').prop('checked', total === checked);
		});

		$('#iaq-bulk-form').on('submit', function (e) {
			var action = $('#iaq-bulk-action').val();
			var checkedCount = $('.iaq-row-checkbox:checked').length;

			if (!action) {
				alert('Please select a bulk action from the dropdown.');
				e.preventDefault();
				return false;
			}

			if (checkedCount === 0) {
				alert('Please select at least one question to perform this action.');
				e.preventDefault();
				return false;
			}

			if (action === 'delete') {
				if (!confirm('Are you sure you want to delete ' + checkedCount + ' selected question(s)? This action cannot be undone.')) {
					e.preventDefault();
					return false;
				}
			}
		});

		/* =========================================================================
		 * 2. Individual Delete Confirmation
		 * ========================================================================= */
		$(document).on('click', '.iaq-confirm-delete', function (e) {
			var message = (typeof iaqAdminData !== 'undefined' && iaqAdminData.i18n && iaqAdminData.i18n.confirmDelete)
				? iaqAdminData.i18n.confirmDelete
				: 'Are you sure you want to delete this item? This action cannot be undone.';

			if (!confirm(message)) {
				e.preventDefault();
				return false;
			}
		});

		/* =========================================================================
		 * 3. Dynamic Subject -> Topic Dropdown Filtering via AJAX
		 * ========================================================================= */
		function fetchTopics(subjectId, targetSelect, defaultLabel, callback) {
			if (!subjectId || subjectId === '0') {
				targetSelect.html('<option value="0">' + defaultLabel + '</option>');
				if (typeof callback === 'function') callback();
				return;
			}

			targetSelect.prop('disabled', true);

			$.ajax({
				url: iaqAdminData.ajaxUrl,
				type: 'GET',
				dataType: 'json',
				data: {
					action: 'iaq_get_topics_by_subject',
					nonce: iaqAdminData.nonce,
					subject_id: subjectId
				},
				success: function (response) {
					targetSelect.empty();
					targetSelect.append('<option value="0">' + defaultLabel + '</option>');

					if (response.success && response.data && response.data.topics) {
						$.each(response.data.topics, function (index, topic) {
							targetSelect.append('<option value="' + topic.id + '">' + topic.name + '</option>');
						});
					}
					targetSelect.prop('disabled', false);
					if (typeof callback === 'function') callback();
				},
				error: function () {
					targetSelect.prop('disabled', false);
					if (typeof callback === 'function') callback();
				}
			});
		}

		// In Question Bank Filter Bar:
		$('#iaq-filter-subject').on('change', function () {
			var subjectId = $(this).val();
			var target = $('#iaq-filter-topic');
			var defaultLabel = (typeof iaqAdminData !== 'undefined' && iaqAdminData.i18n) ? iaqAdminData.i18n.allTopics : 'All Topics';
			fetchTopics(subjectId, target, defaultLabel);
		});

		// In Question Form:
		$('#iaq-form-subject').on('change', function () {
			var subjectId = $(this).val();
			var target = $('#iaq-form-topic');
			var defaultLabel = (typeof iaqAdminData !== 'undefined' && iaqAdminData.i18n) ? iaqAdminData.i18n.selectTopic : '— Select Topic —';
			fetchTopics(subjectId, target, defaultLabel);
		});

		/* =========================================================================
		 * 4. MCQ Options Radio Highlighting
		 * ========================================================================= */
		$('input[name="correct_option"]').on('change', function () {
			$('.iaq-option-row').removeClass('is-correct-row');
			$(this).closest('.iaq-option-row').addClass('is-correct-row');
		});

		/* =========================================================================
		 * 5. Question Preview Modal
		 * ========================================================================= */
		$(document).on('click', '.iaq-btn-preview', function () {
			var qJson = $(this).attr('data-question');
			if (!qJson) return;

			try {
				var q = JSON.parse(qJson);
				var html = '';

				html += '<div style="margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap;">';
				if (q.subject_name) {
					html += '<span class="iaq-badge" style="background:#f1f5f9;color:#334155;">' + q.subject_name + '</span>';
				}
				if (q.topic_name) {
					html += '<span class="iaq-badge" style="background:#f1f5f9;color:#64748b;">' + q.topic_name + '</span>';
				}
				html += '<span class="iaq-badge iaq-badge-exam">' + q.exam_category + '</span>';
				html += '<span class="iaq-badge" style="background:#dcfce7;color:#15803d;">Marks: +' + q.marks + ' / -' + q.negative_marks + '</span>';
				html += '</div>';

				html += '<div style="font-size:16px;font-weight:600;line-height:1.5;color:#0f172a;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">';
				html += q.question_text;
				html += '</div>';

				html += '<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px;">';

				var opts = [
					{ letter: 'A', text: q.option_a },
					{ letter: 'B', text: q.option_b },
					{ letter: 'C', text: q.option_c },
					{ letter: 'D', text: q.option_d }
				];

				$.each(opts, function (i, item) {
					var isCorrect = (q.correct_option === item.letter);
					var bg = isCorrect ? '#f0fdf4' : '#f8fafc';
					var border = isCorrect ? '#86efac' : '#e2e8f0';
					var color = isCorrect ? '#15803d' : '#334155';
					var badge = isCorrect ? '<span style="margin-left:auto;font-size:11px;font-weight:700;color:#15803d;background:#dcfce7;padding:2px 8px;border-radius:10px;">✓ Correct Answer</span>' : '';

					html += '<div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid ' + border + ';background:' + bg + ';border-radius:8px;">';
					html += '<span style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:700;font-size:12px;' + (isCorrect ? 'background:#117E3A;color:#fff;' : 'background:#e2e8f0;color:#475569;') + '">' + item.letter + '</span>';
					html += '<span style="font-size:14px;color:' + color + ';">' + item.text + '</span>';
					html += badge;
					html += '</div>';
				});

				html += '</div>';

				if (q.explanation && $.trim(q.explanation).length > 0) {
					html += '<div style="background:#f8fafc;border-left:4px solid #117E3A;padding:12px 16px;border-radius:4px;margin-top:14px;">';
					html += '<div style="font-weight:700;font-size:13px;color:#117E3A;margin-bottom:4px;">Explanation / Rationale:</div>';
					html += '<div style="font-size:13px;color:#475569;line-height:1.5;">' + q.explanation + '</div>';
					html += '</div>';
				}

				$('#iaq-preview-content').html(html);
				$('#iaq-preview-modal').fadeIn(150);
			} catch (err) {
				console.error('Preview error:', err);
			}
		});

		$('#iaq-close-preview, #iaq-close-preview-btn').on('click', function () {
			$('#iaq-preview-modal').fadeOut(150);
		});

		$('#iaq-preview-modal').on('click', function (e) {
			if ($(e.target).is('#iaq-preview-modal')) {
				$('#iaq-preview-modal').fadeOut(150);
			}
		});

		$(document).on('keyup', function (e) {
			if (e.key === 'Escape' && $('#iaq-preview-modal').is(':visible')) {
				$('#iaq-preview-modal').fadeOut(150);
			}
		});

		/* =========================================================================
		 * 6. File Upload Dropzone Preview
		 * ========================================================================= */
		$('#iaq-csv-file-input').on('change', function () {
			if (this.files && this.files.length > 0) {
				var fileName = this.files[0].name;
				$('#iaq-file-name-display').text('Selected file: ' + fileName);
				$('#iaq-file-preview').show();
			} else {
				$('#iaq-file-preview').hide();
			}
		});

		/* =========================================================================
		 * 7. Quiz Builder Enhancements & Shortcode Copy
		 * ========================================================================= */
		$('#iaq-check-all-quizzes').on('change', function () {
			var isChecked = $(this).is(':checked');
			$('.iaq-quiz-checkbox').prop('checked', isChecked);
		});

		$('#iaq-check-all-add').on('change', function () {
			var isChecked = $(this).is(':checked');
			$('.iaq-add-checkbox:not(:disabled)').prop('checked', isChecked);
		});

		$('#quiz-access').on('change', function () {
			if ($(this).val() === 'paid') {
				$('#iaq-wc-product-wrap').slideDown(150);
			} else {
				$('#iaq-wc-product-wrap').slideUp(150);
			}
		});

		$(document).on('click', '.iaq-copy-shortcode', function () {
			var text = $(this).text().trim();
			var self = $(this);
			navigator.clipboard.writeText(text).then(function () {
				var orig = self.text();
				self.text('Copied!');
				setTimeout(function () {
					self.text(orig);
				}, 1500);
			});
		});

		/* =========================================================================
		 * 8. Taxonomy (Subjects & Topics) Interactive In-Place Editing
		 * ========================================================================= */
		// Edit Subject
		$(document).on('click', '.iaq-edit-subject-btn', function (e) {
			e.preventDefault();
			var id = $(this).data('id') || $(this).attr('data-id');
			var name = $(this).data('name') || $(this).attr('data-name') || '';
			var slug = $(this).data('slug') || $(this).attr('data-slug') || '';
			var desc = $(this).data('desc') || $(this).attr('data-desc') || '';

			$('#subj-id').val(id);
			$('#subj-name').val(name);
			$('#subj-slug').val(slug);
			$('#subj-desc').val(desc);

			$('#iaq-subject-form-card .iaq-form-title-text').text('Edit Subject: ' + name);
			$('#iaq-subject-submit-btn .iaq-btn-text').text('Update Subject');
			$('#iaq-subject-cancel-btn').fadeIn(150);

			var $card = $('#iaq-subject-form-card');
			if ($card.length) {
				$card.removeClass('iaq-form-highlight');
				setTimeout(function () {
					$card.addClass('iaq-form-highlight');
				}, 20);
				$('html, body').animate({
					scrollTop: $card.offset().top - 30
				}, 300);
			}
			$('#subj-name').focus();
		});

		// Cancel Subject Edit
		$(document).on('click', '#iaq-subject-cancel-btn', function (e) {
			e.preventDefault();
			$('#subj-id').val('');
			$('#subj-name').val('');
			$('#subj-slug').val('');
			$('#subj-desc').val('');

			$('#iaq-subject-form-card .iaq-form-title-text').text('Add New Subject');
			$('#iaq-subject-submit-btn .iaq-btn-text').text('Create Subject');
			$('#iaq-subject-cancel-btn').hide();
			$('#iaq-subject-form-card').removeClass('iaq-form-highlight');
		});

		// Edit Topic
		$(document).on('click', '.iaq-edit-topic-btn', function (e) {
			e.preventDefault();
			var id = $(this).data('id') || $(this).attr('data-id');
			var subjectId = $(this).data('subject-id') || $(this).attr('data-subject-id') || '';
			var name = $(this).data('name') || $(this).attr('data-name') || '';
			var slug = $(this).data('slug') || $(this).attr('data-slug') || '';

			$('#topic-id').val(id);
			if (subjectId) {
				$('#topic-subject').val(subjectId);
			}
			$('#topic-name').val(name);
			$('#topic-slug').val(slug);

			$('#iaq-topic-form-card .iaq-form-title-text').text('Edit Topic: ' + name);
			$('#iaq-topic-submit-btn .iaq-btn-text').text('Update Topic');
			$('#iaq-topic-cancel-btn').fadeIn(150);

			var $card = $('#iaq-topic-form-card');
			if ($card.length) {
				$card.removeClass('iaq-form-highlight');
				setTimeout(function () {
					$card.addClass('iaq-form-highlight');
				}, 20);
				$('html, body').animate({
					scrollTop: $card.offset().top - 30
				}, 300);
			}
			$('#topic-name').focus();
		});

		// Cancel Topic Edit
		$(document).on('click', '#iaq-topic-cancel-btn', function (e) {
			e.preventDefault();
			$('#topic-id').val('');
			$('#topic-subject').val('');
			$('#topic-name').val('');
			$('#topic-slug').val('');

			$('#iaq-topic-form-card .iaq-form-title-text').text('Add New Topic');
			$('#iaq-topic-submit-btn .iaq-btn-text').text('Create Topic');
			$('#iaq-topic-cancel-btn').hide();
			$('#iaq-topic-form-card').removeClass('iaq-form-highlight');
		});

		// Check if URL has edit params on initial load, auto-scroll and highlight
		var urlParams = new URLSearchParams(window.location.search);
		if (urlParams.has('edit_subject') && $('#iaq-subject-form-card').length) {
			$('#iaq-subject-form-card').addClass('iaq-form-highlight');
			$('html, body').animate({
				scrollTop: $('#iaq-subject-form-card').offset().top - 30
			}, 300);
		} else if (urlParams.has('edit_topic') && $('#iaq-topic-form-card').length) {
			$('#iaq-topic-form-card').addClass('iaq-form-highlight');
			$('html, body').animate({
				scrollTop: $('#iaq-topic-form-card').offset().top - 30
			}, 300);
		}

		/* =========================================================================
		 * 9. Subject Practice Sets Selector & Inline Bulk Upload Handlers
		 * ========================================================================= */
		$('#iaq-ps-subject-picker').on('change', function () {
			var subjId = $(this).val();
			if (subjId && parseInt(subjId, 10) > 0) {
				var baseUrl = window.location.pathname;
				var url = baseUrl + '?page=iaq-subjects-topics&subject_id=' + subjId + '#practice-sets';
				window.location.href = url;
			} else {
				window.location.href = window.location.pathname + '?page=iaq-subjects-topics';
			}
		});

		$(document).on('click', '.iaq-toggle-bulk-upload-btn', function (e) {
			e.preventDefault();
			var targetId = $(this).attr('data-target');
			if (targetId) {
				$('#' + targetId).slideToggle(200);
			}
		});

		// Import / Export: Filter Quizzes by Subject and Topic
		$('#import-filter-subject').on('change', function () {
			var selectedSubjId = $(this).val();
			var $topicSelect = $('#import-filter-topic');
			var $quizSelect = $('#assign-quiz-id');

			// Update topics dropdown
			fetchTopics(selectedSubjId, $topicSelect, '— All Topics —');

			// Filter quizzes
			$quizSelect.find('option').each(function () {
				var $opt = $(this);
				var qSubjId = $opt.attr('data-subject-id');

				if ($opt.val() === '0') {
					$opt.show();
					return;
				}

				if (!selectedSubjId || selectedSubjId === '0' || qSubjId === selectedSubjId) {
					$opt.show();
				} else {
					$opt.hide();
				}
			});

			$quizSelect.val('0');
		});

		$('#import-filter-topic').on('change', function () {
			var selectedTopicId = $(this).val();
			var selectedSubjId = $('#import-filter-subject').val();
			var $quizSelect = $('#assign-quiz-id');

			$quizSelect.find('option').each(function () {
				var $opt = $(this);
				var qSubjId = $opt.attr('data-subject-id');
				var qTopicId = $opt.attr('data-topic-id');

				if ($opt.val() === '0') {
					$opt.show();
					return;
				}

				var matchSubj = (!selectedSubjId || selectedSubjId === '0' || qSubjId === selectedSubjId);
				var matchTopic = (!selectedTopicId || selectedTopicId === '0' || qTopicId === selectedTopicId);

				if (matchSubj && matchTopic) {
					$opt.show();
				} else {
					$opt.hide();
				}
			});

			$quizSelect.val('0');
		});
	});
})(jQuery);
