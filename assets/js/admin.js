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
	});
})(jQuery);
