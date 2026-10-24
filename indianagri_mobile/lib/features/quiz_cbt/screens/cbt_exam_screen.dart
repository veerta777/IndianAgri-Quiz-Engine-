import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/localization/app_localizations.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../../core/widgets/state_views.dart';
import '../../results/screens/scorecard_screen.dart';

class CbtExamScreen extends StatefulWidget {
  final int quizId;

  const CbtExamScreen({super.key, required this.quizId});

  @override
  State<CbtExamScreen> createState() => _CbtExamScreenState();
}

class _CbtExamScreenState extends State<CbtExamScreen> {
  bool _isLoading = true;
  String? _errorMessage;

  int _attemptId = 0;
  bool _isImmediate = false;
  int _currentIndex = 0;
  List<dynamic> _questions = [];

  // Question State Maps (keyed by questionId)
  final Map<int, String> _userAnswers = {};
  final Map<int, bool> _markedForReview = {};
  final Map<int, Map<String, dynamic>> _immediateFeedbacks = {};
  final Map<int, bool> _lockedQuestions = {};

  // Server-authoritative Timer
  Timer? _timer;
  int _secondsRemaining = 0;
  int _totalDurationSeconds = 0;

  @override
  void initState() {
    super.initState();
    _startOrResumeExam();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _startOrResumeExam() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.post(ApiEndpoints.startQuiz(widget.quizId));
      if (res is Map<String, dynamic> && res.containsKey('attempt_id')) {
        _attemptId = (res['attempt_id'] as num).toInt();
        _isImmediate = res['is_immediate'] == true;
        _questions = (res['questions'] as List<dynamic>?) ?? [];

        // Restore answered states if resuming
        final restoredAnswers = res['answers'] as Map<String, dynamic>?;
        if (restoredAnswers != null) {
          restoredAnswers.forEach((qIdStr, opt) {
            final qId = int.tryParse(qIdStr);
            if (qId != null && opt != null) {
              _userAnswers[qId] = opt.toString();
              if (_isImmediate) {
                _lockedQuestions[qId] = true;
              }
            }
          });
        }

        // Restore immediate feedback if provided
        final restoredFeedbacks = res['immediate_feedback'] as Map<String, dynamic>?;
        if (restoredFeedbacks != null) {
          restoredFeedbacks.forEach((qIdStr, fb) {
            final qId = int.tryParse(qIdStr);
            if (qId != null && fb is Map<String, dynamic>) {
              _immediateFeedbacks[qId] = fb;
              _lockedQuestions[qId] = true;
            }
          });
        }

        // Timer initialization
        final durationMins = (res['duration_minutes'] as num?)?.toInt() ?? 0;
        final elapsedSecs = (res['time_spent_seconds'] as num?)?.toInt() ?? 0;
        _totalDurationSeconds = durationMins * 60;

        if (_totalDurationSeconds > 0) {
          _secondsRemaining = (_totalDurationSeconds - elapsedSecs).clamp(0, _totalDurationSeconds);
          _startTimer();
        }

        setState(() {
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Invalid response received from examination engine.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      if (_secondsRemaining > 0) {
        setState(() {
          _secondsRemaining--;
        });
      } else {
        _timer?.cancel();
        _autoSubmitOnTimeUp();
      }
    });
  }

  Future<void> _autoSubmitOnTimeUp() async {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Time has expired! Submitting your examination...'),
        backgroundColor: AppColors.warningAmber,
      ),
    );
    await _submitAttempt();
  }

  Future<void> _selectOption(String optionKey) async {
    if (_questions.isEmpty) return;
    final q = _questions[_currentIndex] as Map<String, dynamic>;
    final qId = (q['id'] as num).toInt();

    // Prevent altering locked answers in immediate feedback mode
    if (_lockedQuestions[qId] == true) {
      return;
    }

    setState(() {
      _userAnswers[qId] = optionKey;
    });

    // Save answer to server
    try {
      final res = await ApiClient.post(
        ApiEndpoints.saveAnswer(_attemptId),
        body: {
          'question_id': qId,
          'selected_option': optionKey,
          'is_marked_for_review': _markedForReview[qId] == true ? 1 : 0,
          'time_spent_seconds': _totalDurationSeconds - _secondsRemaining,
        },
      );

      if (res is Map<String, dynamic>) {
        if (_isImmediate && res['is_immediate'] == true) {
          setState(() {
            _lockedQuestions[qId] = true;
            _immediateFeedbacks[qId] = res;
          });
        }
      }
    } catch (e) {
      // Revert if network failed
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Save error: $e'), backgroundColor: AppColors.incorrectRed),
        );
      }
    }
  }

  void _clearSelection() {
    final q = _questions[_currentIndex] as Map<String, dynamic>;
    final qId = (q['id'] as num).toInt();

    if (_lockedQuestions[qId] == true) return;

    setState(() {
      _userAnswers.remove(qId);
    });

    ApiClient.post(
      ApiEndpoints.saveAnswer(_attemptId),
      body: {
        'question_id': qId,
        'selected_option': '',
        'is_marked_for_review': _markedForReview[qId] == true ? 1 : 0,
        'time_spent_seconds': _totalDurationSeconds - _secondsRemaining,
      },
    );
  }

  void _toggleMarkForReview() {
    final q = _questions[_currentIndex] as Map<String, dynamic>;
    final qId = (q['id'] as num).toInt();

    final newVal = !(_markedForReview[qId] ?? false);
    setState(() {
      _markedForReview[qId] = newVal;
    });

    ApiClient.post(
      ApiEndpoints.saveAnswer(_attemptId),
      body: {
        'question_id': qId,
        'selected_option': _userAnswers[qId] ?? '',
        'is_marked_for_review': newVal ? 1 : 0,
        'time_spent_seconds': _totalDurationSeconds - _secondsRemaining,
      },
    );
  }

  Future<void> _submitAttempt() async {
    _timer?.cancel();

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: CircularProgressIndicator(color: AppColors.primary),
      ),
    );

    try {
      final res = await ApiClient.post(
        ApiEndpoints.submitQuiz(_attemptId),
        body: {
          'time_spent_seconds': _totalDurationSeconds - _secondsRemaining,
        },
      );

      if (!mounted) return;
      Navigator.pop(context); // Close loading dialog

      if (res is Map<String, dynamic>) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => ScorecardScreen(attemptId: _attemptId),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Submission failed: $e'), backgroundColor: AppColors.incorrectRed),
      );
    }
  }

  void _confirmSubmit() {
    final total = _questions.length;
    final answered = _userAnswers.length;
    final unanswered = total - answered;
    final marked = _markedForReview.values.where((v) => v).length;

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Submit Examination?',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
            ),
            const SizedBox(height: 12),
            const Text(
              'Please verify your summary before final submission. Once submitted, answers cannot be modified.',
              style: TextStyle(color: AppColors.textSecondaryLight, fontSize: 13),
            ),
            const SizedBox(height: 18),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.backgroundLight,
                borderRadius: AppStyles.borderRadiusM,
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildSummaryPill('Answered', '$answered', AppColors.correctGreen),
                  _buildSummaryPill('Unanswered', '$unanswered', AppColors.incorrectRed),
                  _buildSummaryPill('Marked', '$marked', AppColors.reviewPurple),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancel'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.pop(context);
                      _submitAttempt();
                    },
                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                    child: const Text('Yes, Submit', style: TextStyle(color: Colors.white)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryPill(String label, String value, Color color) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: color)),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: LoadingView(message: 'Entering CBT examination hall...'));
    }

    if (_errorMessage != null) {
      return Scaffold(
        appBar: AppBar(title: const Text('CBT Examination')),
        body: ErrorStateView(message: _errorMessage!, onRetry: _startOrResumeExam),
      );
    }

    if (_questions.isEmpty) {
      return const Scaffold(
        body: EmptyStateView(title: 'No Questions Found in Examination'),
      );
    }

    final q = _questions[_currentIndex] as Map<String, dynamic>;
    final qId = (q['id'] as num).toInt();
    final selectedOpt = _userAnswers[qId];
    final isMarked = _markedForReview[qId] == true;
    final fb = _immediateFeedbacks[qId];
    final isLocked = _lockedQuestions[qId] == true;

    final langProvider = Provider.of<LanguageProvider>(context);
    final isHindi = langProvider.isHindi;

    // Bilingual text extraction
    final qText = (isHindi && q['question_text_hi'] != null && q['question_text_hi'].toString().isNotEmpty)
        ? q['question_text_hi'].toString()
        : q['question_text'].toString();

    // Dynamic options parsing with fallback
    final rawOptions = q['options'];
    final List<Map<String, dynamic>> optionsList = [];

    if (rawOptions is List && rawOptions.isNotEmpty) {
      for (final o in rawOptions) {
        if (o is Map<String, dynamic>) {
          optionsList.add(o);
        } else if (o is Map) {
          optionsList.add(Map<String, dynamic>.from(o));
        }
      }
    } else {
      optionsList.addAll([
        {
          'key': 'A',
          'text': q['option_a']?.toString() ?? '',
          if (q['option_a_hi'] != null) 'text_hi': q['option_a_hi'],
        },
        {
          'key': 'B',
          'text': q['option_b']?.toString() ?? '',
          if (q['option_b_hi'] != null) 'text_hi': q['option_b_hi'],
        },
        {
          'key': 'C',
          'text': q['option_c']?.toString() ?? '',
          if (q['option_c_hi'] != null) 'text_hi': q['option_c_hi'],
        },
        {
          'key': 'D',
          'text': q['option_d']?.toString() ?? '',
          if (q['option_d_hi'] != null) 'text_hi': q['option_d_hi'],
        },
      ]);
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Q ${_currentIndex + 1} of ${_questions.length}'),
        actions: [
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 8.0),
            child: LanguageToggle(),
          ),
          IconButton(
            icon: const Icon(Icons.grid_view),
            tooltip: 'Question Palette',
            onPressed: _openQuestionPalette,
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Sticky Timer Bar
            if (_totalDurationSeconds > 0) _buildTimerBar(),

            // Question & Options Content
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Question Header / Tags
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.primarySubtle,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            q['subject_name'] ?? 'General Agriculture',
                            style: const TextStyle(
                              color: AppColors.primaryDark,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const Spacer(),
                        Text(
                          '+${q['marks'] ?? 1.0} • -${q['negative_marks'] ?? 0.25}',
                          style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),

                    // Question Text
                    Text(
                      qText,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        height: 1.45,
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Options List (Dynamically rendered with sequential visual labels A/B/C/D)
                    ...List.generate(optionsList.length, (idx) {
                      final opt = optionsList[idx];
                      final displayLabel = (idx < 26) ? String.fromCharCode(65 + idx) : '${idx + 1}';
                      final optionKey = opt['key']?.toString() ?? displayLabel;
                      final optText = (isHindi && opt['text_hi'] != null && opt['text_hi'].toString().isNotEmpty)
                          ? opt['text_hi'].toString()
                          : (opt['text']?.toString() ?? '');

                      return Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: _buildOptionCard(
                          displayLabel,
                          optionKey,
                          optText,
                          selectedOpt,
                          fb,
                          isLocked,
                        ),
                      );
                    }),

                    // Immediate Feedback Card (for Practice Sets)
                    if (fb != null) ...[
                      const SizedBox(height: 18),
                      _buildImmediateFeedbackBox(fb),
                    ],
                  ],
                ),
              ),
            ),

            // Bottom Navigation & Actions Bar
            _buildBottomActionBar(isMarked, isLocked),
          ],
        ),
      ),
    );
  }

  Widget _buildTimerBar() {
    final mins = _secondsRemaining ~/ 60;
    final secs = _secondsRemaining % 60;
    final timeStr = '${mins.toString().padLeft(2, '0')}:${secs.toString().padLeft(2, '0')}';
    final progress = _totalDurationSeconds > 0 ? (_secondsRemaining / _totalDurationSeconds) : 1.0;
    final isLowTime = _secondsRemaining < 300; // Under 5 mins

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: isLowTime ? AppColors.incorrectRedBg : Colors.white,
        border: Border(bottom: BorderSide(color: AppColors.cardBorderLight)),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(
                    Icons.timer_outlined,
                    size: 18,
                    color: isLowTime ? AppColors.incorrectRed : AppColors.primary,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    'Time Remaining',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: isLowTime ? AppColors.incorrectRed : AppColors.textSecondaryLight,
                    ),
                  ),
                ],
              ),
              Text(
                timeStr,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 15,
                  color: isLowTime ? AppColors.incorrectRed : AppColors.primaryDark,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          LinearProgressIndicator(
            value: progress.clamp(0.0, 1.0),
            backgroundColor: AppColors.cardBorderLight,
            valueColor: AlwaysStoppedAnimation<Color>(
              isLowTime ? AppColors.incorrectRed : AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildOptionCard(
    String displayLabel,
    String optionKey,
    String text,
    String? selectedKey,
    Map<String, dynamic>? feedback,
    bool isLocked,
  ) {
    final isSelected = selectedKey == optionKey;
    bool isCorrectOption = false;
    bool isIncorrectSelection = false;

    if (feedback != null) {
      final correctOpt = feedback['correct_option']?.toString();
      if (correctOpt == optionKey) {
        isCorrectOption = true;
      }
      if (isSelected && feedback['is_correct'] != true) {
        isIncorrectSelection = true;
      }
    }

    Color borderColor = AppColors.cardBorderLight;
    Color bgColor = Colors.white;

    if (isCorrectOption) {
      borderColor = AppColors.correctGreen;
      bgColor = AppColors.correctGreenBg;
    } else if (isIncorrectSelection) {
      borderColor = AppColors.incorrectRed;
      bgColor = AppColors.incorrectRedBg;
    } else if (isSelected) {
      borderColor = AppColors.primary;
      bgColor = AppColors.primarySubtle;
    }

    return InkWell(
      onTap: isLocked ? null : () => _selectOption(optionKey),
      borderRadius: AppStyles.borderRadiusM,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: AppStyles.borderRadiusM,
          border: Border.all(color: borderColor, width: (isSelected || isCorrectOption || isIncorrectSelection) ? 2 : 1),
        ),
        child: Row(
          children: [
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: isCorrectOption
                    ? AppColors.correctGreen
                    : (isIncorrectSelection ? AppColors.incorrectRed : (isSelected ? AppColors.primary : Colors.transparent)),
                shape: BoxShape.circle,
                border: Border.all(
                  color: (isSelected || isCorrectOption || isIncorrectSelection)
                      ? Colors.transparent
                      : AppColors.cardBorderLight,
                ),
              ),
              child: Center(
                child: Text(
                  displayLabel,
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                    color: (isSelected || isCorrectOption || isIncorrectSelection)
                        ? Colors.white
                        : AppColors.textPrimaryLight,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                text,
                style: const TextStyle(fontSize: 14, height: 1.35),
              ),
            ),
            if (isCorrectOption) ...[
              const SizedBox(width: 6),
              const Icon(Icons.check_circle, color: AppColors.correctGreen, size: 20),
            ] else if (isIncorrectSelection) ...[
              const SizedBox(width: 6),
              const Icon(Icons.cancel, color: AppColors.incorrectRed, size: 20),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildImmediateFeedbackBox(Map<String, dynamic> fb) {
    final isCorrect = fb['is_correct'] == true;
    final message = fb['feedback_message'] ?? (isCorrect ? 'Correct! Excellent work!' : 'Keep going! Review explanation:');
    final explanation = fb['explanation'] ?? '';
    final marks = fb['marks_awarded'] ?? 0;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isCorrect ? AppColors.correctGreenBg : AppColors.incorrectRedBg,
        borderRadius: AppStyles.borderRadiusL,
        border: Border.all(color: isCorrect ? AppColors.correctGreen : AppColors.incorrectRed),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(
                isCorrect ? Icons.check_circle : Icons.info,
                color: isCorrect ? AppColors.correctGreen : AppColors.incorrectRed,
                size: 22,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  message,
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                    color: isCorrect ? const Color(0xFF065F46) : const Color(0xFF991B1B),
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: isCorrect ? AppColors.correctGreen : AppColors.incorrectRed,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${marks >= 0 ? "+" : ""}$marks Marks',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
                ),
              ),
            ],
          ),
          if (explanation.toString().isNotEmpty) ...[
            const SizedBox(height: 12),
            const Text(
              'Scientific Explanation:',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
            ),
            const SizedBox(height: 4),
            Text(
              explanation.toString(),
              style: const TextStyle(fontSize: 13, height: 1.4),
            ),
          ],
          const SizedBox(height: 14),
          ElevatedButton.icon(
            onPressed: () {
              if (_currentIndex < _questions.length - 1) {
                setState(() {
                  _currentIndex++;
                });
              } else {
                _confirmSubmit();
              }
            },
            icon: const Icon(Icons.arrow_forward, size: 16),
            label: Text(_currentIndex < _questions.length - 1 ? 'Next Question →' : 'Finish & View Scorecard'),
            style: ElevatedButton.styleFrom(
              backgroundColor: isCorrect ? AppColors.correctGreen : AppColors.primary,
              foregroundColor: Colors.white,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomActionBar(bool isMarked, bool isLocked) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: AppColors.cardBorderLight)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Previous Button
          IconButton(
            onPressed: _currentIndex > 0
                ? () {
                    setState(() {
                      _currentIndex--;
                    });
                  }
                : null,
            icon: const Icon(Icons.arrow_back),
            tooltip: 'Previous Question',
          ),

          // Mark for Review
          OutlinedButton.icon(
            onPressed: _toggleMarkForReview,
            icon: Icon(
              isMarked ? Icons.bookmark : Icons.bookmark_border,
              size: 16,
              color: isMarked ? AppColors.reviewPurple : AppColors.textSecondaryLight,
            ),
            label: Text(
              isMarked ? 'Marked' : 'Review',
              style: TextStyle(
                fontSize: 12,
                color: isMarked ? AppColors.reviewPurple : AppColors.textSecondaryLight,
              ),
            ),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              side: BorderSide(color: isMarked ? AppColors.reviewPurple : AppColors.cardBorderLight),
            ),
          ),

          // Clear Selection (hidden if answer locked in immediate mode)
          if (!isLocked) ...[
            TextButton(
              onPressed: _clearSelection,
              child: const Text('Clear', style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight)),
            ),
          ],

          // Next or Submit Button
          if (_currentIndex < _questions.length - 1)
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _currentIndex++;
                });
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              ),
              child: const Text('Next', style: TextStyle(fontSize: 13, color: Colors.white)),
            )
          else
            ElevatedButton(
              onPressed: _confirmSubmit,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.correctGreen,
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              ),
              child: const Text('Submit', style: TextStyle(fontSize: 13, color: Colors.white)),
            ),
        ],
      ),
    );
  }

  void _openQuestionPalette() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => Container(
        height: MediaQuery.of(context).size.height * 0.65,
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Question Palette',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 12),
            // Legend
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildLegendItem('Answered', AppColors.correctGreen),
                _buildLegendItem('Unanswered', AppColors.cardBorderLight),
                _buildLegendItem('Review', AppColors.reviewPurple),
              ],
            ),
            const Divider(height: 24),
            // Palette Grid
            Expanded(
              child: GridView.builder(
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 5,
                  crossAxisSpacing: 10,
                  mainAxisSpacing: 10,
                ),
                itemCount: _questions.length,
                itemBuilder: (context, index) {
                  final q = _questions[index] as Map<String, dynamic>;
                  final qId = (q['id'] as num).toInt();
                  final isAnswered = _userAnswers.containsKey(qId);
                  final isMarked = _markedForReview[qId] == true;
                  final isCurrent = index == _currentIndex;

                  Color color = Colors.white;
                  Color textColor = AppColors.textPrimaryLight;
                  Border? border = Border.all(color: AppColors.cardBorderLight);

                  if (isMarked) {
                    color = AppColors.reviewPurple;
                    textColor = Colors.white;
                    border = null;
                  } else if (isAnswered) {
                    color = AppColors.correctGreen;
                    textColor = Colors.white;
                    border = null;
                  }

                  if (isCurrent) {
                    border = Border.all(color: AppColors.primary, width: 2.5);
                  }

                  return InkWell(
                    onTap: () {
                      Navigator.pop(context);
                      setState(() {
                        _currentIndex = index;
                      });
                    },
                    borderRadius: BorderRadius.circular(8),
                    child: Container(
                      decoration: BoxDecoration(
                        color: color,
                        borderRadius: BorderRadius.circular(8),
                        border: border,
                      ),
                      child: Center(
                        child: Text(
                          '${index + 1}',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: textColor,
                            fontSize: 14,
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 12),
            AppButton(
              label: 'Submit Examination',
              onPressed: () {
                Navigator.pop(context);
                _confirmSubmit();
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLegendItem(String label, Color color) {
    return Row(
      children: [
        Container(
          width: 14,
          height: 14,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
            border: Border.all(color: Colors.black12),
          ),
        ),
        const SizedBox(width: 6),
        Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight)),
      ],
    );
  }
}
