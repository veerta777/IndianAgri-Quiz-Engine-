import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/localization/app_localizations.dart';
import '../../../core/utils/citation_cleaner.dart';
import '../../../core/utils/safe_parse.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../../core/widgets/state_views.dart';
import '../../auth/screens/login_screen.dart';
import '../../subscriptions/screens/subscription_plans_screen.dart';
import '../../results/screens/scorecard_screen.dart';

class CbtExamScreen extends StatefulWidget {
  final int quizId;
  final bool isDemoMode;

  const CbtExamScreen({
    super.key,
    required this.quizId,
    this.isDemoMode = false,
  });

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
  final Set<int> _visitedQuestionIds = {};

  // Practice Mode Streaks & Tracking
  int _currentStreak = 0;
  int _highestStreak = 0;

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

    List<dynamic> fetchedQuestions = [];

    // 1. Primary: Start or resume official attempt on server
    try {
      final res = await ApiClient.post(
        ApiEndpoints.startQuiz(widget.quizId),
        body: widget.isDemoMode ? {'demo_mode': true} : {},
      );

      if (res is Map<String, dynamic> && res.containsKey('attempt_id')) {
        _attemptId = SafeParse.toInt(res['attempt_id']);
        _isImmediate = res['is_immediate'] == true || widget.isDemoMode;
        fetchedQuestions = (res['questions'] as List<dynamic>?) ?? [];

        // Restore answered states if resuming
        final restoredAnswers = res['answers'] != null && res['answers'] is Map
            ? Map<String, dynamic>.from(res['answers'] as Map)
            : null;
        if (restoredAnswers != null) {
          restoredAnswers.forEach((qIdStr, opt) {
            final qId = int.tryParse(qIdStr);
            if (qId != null && opt != null) {
              _userAnswers[qId] = opt.toString();
              _visitedQuestionIds.add(qId);
              if (_isImmediate) {
                _lockedQuestions[qId] = true;
              }
            }
          });
        }

        // Restore marked questions if resuming
        final restoredMarked = res['marked_questions'] ?? res['marked'];
        if (restoredMarked is List) {
          for (final item in restoredMarked) {
            final qId = item is num ? item.toInt() : int.tryParse(item.toString());
            if (qId != null) {
              _markedForReview[qId] = true;
              _visitedQuestionIds.add(qId);
            }
          }
        }

        // Restore immediate feedback if provided
        final restoredFeedbacks = res['immediate_feedback'] != null && res['immediate_feedback'] is Map
            ? Map<String, dynamic>.from(res['immediate_feedback'] as Map)
            : null;
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
        final durationMins = SafeParse.toInt(res['duration_minutes']);
        final elapsedSecs = SafeParse.toInt(res['time_spent_seconds']);
        _totalDurationSeconds = durationMins * 60;

        if (_totalDurationSeconds > 0) {
          _secondsRemaining = (_totalDurationSeconds - elapsedSecs).clamp(0, _totalDurationSeconds);
          _startTimer();
        }
      }
    } catch (_) {
      try {
        final demoRes = await ApiClient.post(
          ApiEndpoints.startQuiz(widget.quizId),
          body: {'demo_mode': true},
        );
        if (demoRes is Map<String, dynamic> && demoRes.containsKey('questions')) {
          _attemptId = SafeParse.toInt(demoRes['attempt_id'], 9999000 + widget.quizId);
          _isImmediate = true;
          fetchedQuestions = (demoRes['questions'] as List<dynamic>?) ?? [];
        }
      } catch (_) {}
    }

    // 2. Secondary: Direct Questions Catalog GET endpoint
    if (fetchedQuestions.isEmpty) {
      try {
        final qRes = await ApiClient.get(ApiEndpoints.quizQuestions(widget.quizId));
        if (qRes is Map<String, dynamic> && qRes.containsKey('questions')) {
          fetchedQuestions = (qRes['questions'] as List<dynamic>?) ?? [];
          _isImmediate = true;
          _attemptId = SafeParse.toInt(qRes['attempt_id'], 9999000 + widget.quizId);
        } else if (qRes is List<dynamic> && qRes.isNotEmpty) {
          fetchedQuestions = qRes;
          _isImmediate = true;
          _attemptId = 9999000 + widget.quizId;
        }
      } catch (_) {}
    }

    // 3. Tertiary: Instructions / Sample questions preview endpoint
    if (fetchedQuestions.isEmpty) {
      try {
        final instRes = await ApiClient.get(ApiEndpoints.quizInstructions(widget.quizId));
        if (instRes is Map<String, dynamic>) {
          if (instRes['questions'] is List && (instRes['questions'] as List).isNotEmpty) {
            fetchedQuestions = instRes['questions'];
          } else if (instRes['sample_questions'] is List && (instRes['sample_questions'] as List).isNotEmpty) {
            fetchedQuestions = instRes['sample_questions'];
          }
          if (fetchedQuestions.isNotEmpty) {
            _isImmediate = true;
            _attemptId = 9999000 + widget.quizId;
          }
        }
      } catch (_) {}
    }

    if (fetchedQuestions.isEmpty) {
      setState(() {
        _errorMessage = 'No questions are currently available for this quiz on the server.';
        _isLoading = false;
      });
      return;
    }

    setState(() {
      _questions = fetchedQuestions
          .where((e) => e is Map)
          .map((e) => CitationCleaner.cleanQuestionMap(Map<String, dynamic>.from(e as Map)))
          .toList();
      if (_questions.isNotEmpty) {
        final firstQId = SafeParse.toInt(_questions[0]['id'], -1);
        if (firstQId != -1) {
          _visitedQuestionIds.add(firstQId);
        }
      }
      if (_isImmediate) {
        _recomputeStreaksFromFeedbacks();
      }
      _isLoading = false;
      _errorMessage = null;
    });
  }

  void _recomputeStreaksFromFeedbacks() {
    int streak = 0;
    int maxStreak = 0;
    for (int i = 0; i < _questions.length; i++) {
      final qId = SafeParse.toInt(_questions[i]['id']);
      final fb = _immediateFeedbacks[qId];
      if (fb != null) {
        if (fb['is_correct'] == true) {
          streak++;
          if (streak > maxStreak) maxStreak = streak;
        } else {
          streak = 0;
        }
      }
    }
    _currentStreak = streak;
    _highestStreak = maxStreak;
  }

  void _updatePracticeStreak(bool isCorrect) {
    if (isCorrect) {
      _currentStreak++;
      if (_currentStreak > _highestStreak) {
        _highestStreak = _currentStreak;
      }
      String? milestoneMsg;
      bool isCelebration = false;
      if (_currentStreak == 1) {
        milestoneMsg = 'Good start! Keep it up!';
      } else if (_currentStreak == 2) {
        milestoneMsg = 'Great! 2 correct in a row!';
      } else if (_currentStreak == 3) {
        milestoneMsg = '🔥 3 in a Row! Excellent Performance!';
        isCelebration = true;
      } else if (_currentStreak == 5) {
        milestoneMsg = 'Amazing! 5 Correct Answers in a Row!';
        isCelebration = true;
      } else if (_currentStreak == 10) {
        milestoneMsg = 'Outstanding! 10 Correct Answers in a Row!';
        isCelebration = true;
      } else if (_currentStreak == 15) {
        milestoneMsg = 'Incredible! 15 Correct Answers in a Row!';
        isCelebration = true;
      } else if (_currentStreak == 20) {
        milestoneMsg = 'Unstoppable! 20 Correct Answers in a Row!';
        isCelebration = true;
      } else if (_currentStreak > 20 && _currentStreak % 5 == 0) {
        milestoneMsg = 'Mastery! $_currentStreak Correct Answers in a Row!';
        isCelebration = true;
      }

      if (milestoneMsg != null && mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                Icon(isCelebration ? Icons.local_fire_department : Icons.stars, color: Colors.amber, size: 20),
                const SizedBox(width: 8),
                Expanded(child: Text(milestoneMsg, style: const TextStyle(fontWeight: FontWeight.bold))),
              ],
            ),
            backgroundColor: const Color(0xFF117E3A),
            duration: const Duration(milliseconds: 2200),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        );
      }
    } else {
      final hadStreak = _currentStreak > 0;
      _currentStreak = 0;
      if (hadStreak && mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Row(
              children: [
                Icon(Icons.emoji_objects_outlined, color: Colors.amber, size: 20),
                SizedBox(width: 8),
                Expanded(child: Text("Don't give up! Keep practicing — you'll get the next one!", style: TextStyle(fontWeight: FontWeight.bold))),
              ],
            ),
            backgroundColor: const Color(0xFF991B1B),
            duration: const Duration(milliseconds: 2200),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        );
      }
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
    final qId = SafeParse.toInt(q['id']);

    // Prevent altering locked answers in immediate feedback mode
    if (_lockedQuestions[qId] == true) {
      return;
    }

    setState(() {
      _userAnswers[qId] = optionKey;
      _visitedQuestionIds.add(qId);
    });

    // Client-side instant evaluation in demo practice mode
    if (widget.isDemoMode || _attemptId >= 9999000) {
      final correctOpt = (q['demo_answer'] ?? q['correct_option'] ?? 'A').toString().toUpperCase().trim();
      final isCorrect = optionKey.toUpperCase().trim() == correctOpt;
      final exp = (q['demo_explanation'] ?? q['explanation'] ?? 'Review this agricultural concept thoroughly for upcoming examinations.').toString();
      final posMarks = SafeParse.toDouble(q['marks'], 1.0);
      final negMarks = SafeParse.toDouble(q['negative_marks'], 0.25);

      setState(() {
        _lockedQuestions[qId] = true;
        _immediateFeedbacks[qId] = {
          'is_immediate': true,
          'is_locked': true,
          'selected': optionKey,
          'is_correct': isCorrect,
          'correct_option': correctOpt,
          'explanation': exp,
          'feedback_message': isCorrect ? 'Correct! Outstanding work!' : 'Good try! Master the concept below:',
          'marks_awarded': isCorrect ? posMarks : -negMarks.abs(),
        };
      });
      _updatePracticeStreak(isCorrect);
      return;
    }

    // Save answer to server in official exam mode
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
          final isCorrect = res['is_correct'] == true;
          setState(() {
            _lockedQuestions[qId] = true;
            _immediateFeedbacks[qId] = res;
          });
          _updatePracticeStreak(isCorrect);
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Save error: $e'), backgroundColor: AppColors.incorrectRed),
        );
      }
    }
  }

  void _clearSelection() {
    final q = _questions[_currentIndex] as Map<String, dynamic>;
    final qId = SafeParse.toInt(q['id']);

    if (_lockedQuestions[qId] == true) return;

    setState(() {
      _userAnswers.remove(qId);
      _visitedQuestionIds.add(qId);
    });

    if (!widget.isDemoMode && _attemptId < 9999000) {
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
  }

  void _toggleMarkForReview() {
    final q = _questions[_currentIndex] as Map<String, dynamic>;
    final qId = SafeParse.toInt(q['id']);

    final newVal = !(_markedForReview[qId] ?? false);
    setState(() {
      _markedForReview[qId] = newVal;
      _visitedQuestionIds.add(qId);
    });

    if (!widget.isDemoMode && _attemptId < 9999000) {
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
  }

  void _navigateToQuestion(int newIndex) {
    if (newIndex >= 0 && newIndex < _questions.length) {
      setState(() {
        _currentIndex = newIndex;
        final nextQ = _questions[newIndex];
        if (nextQ is Map) {
          final nextQId = SafeParse.toInt(nextQ['id'], -1);
          if (nextQId != -1) {
            _visitedQuestionIds.add(nextQId);
          }
        }
      });
    }
  }

  Map<String, int> _calculateStatusCounts() {
    int countAnswered = 0;
    int countNotAnswered = 0;
    int countNotVisited = 0;
    int countMarked = 0;
    int countAnsMarked = 0;

    for (int i = 0; i < _questions.length; i++) {
      final raw = _questions[i];
      if (raw is! Map) continue;
      final q = raw as Map<String, dynamic>;
      final qId = SafeParse.toInt(q['id']);
      final hasAnswer = _userAnswers[qId] != null && _userAnswers[qId]!.trim().isNotEmpty;
      final isVisited = _visitedQuestionIds.contains(qId);
      final isMarked = _markedForReview[qId] == true;

      // Primary status totals: Strictly partition questions
      // Answered + Not Answered + Not Visited === total questions
      if (hasAnswer) {
        countAnswered++;
      } else if (isVisited) {
        countNotAnswered++;
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
    }

    return {
      'answered': countAnswered,
      'not_answered': countNotAnswered,
      'not_visited': countNotVisited,
      'marked': countMarked,
      'ans_marked': countAnsMarked,
    };
  }

  Future<void> _submitAttempt() async {
    _timer?.cancel();

    // In demo practice mode, show completion dialog directly
    if (widget.isDemoMode || _attemptId >= 9999000) {
      _showDemoCompletedDialog();
      return;
    }

    // Show loading dialog
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: Card(
          child: Padding(
            padding: EdgeInsets.all(24.0),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircularProgressIndicator(),
                SizedBox(height: 16),
                Text('Evaluating examination submission...'),
              ],
            ),
          ),
        ),
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

  void _showDemoCompletedDialog() {
    int correctCount = 0;
    int attemptedCount = _userAnswers.length;

    for (final q in _questions) {
      final qId = SafeParse.toInt(q['id']);
      final userAns = _userAnswers[qId];
      final correctOpt = (q['demo_answer'] ?? q['correct_option'] ?? 'A').toString().toUpperCase().trim();
      if (userAns != null && userAns.toUpperCase().trim() == correctOpt) {
        correctCount++;
      }
    }

    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Sample Practice Drill Completed!'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Great job completing this practice set drill! Here is your quick score:'),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.primarySubtle,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  Column(
                    children: [
                      Text('$correctCount / ${_questions.length}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppColors.primaryDark)),
                      const SizedBox(height: 2),
                      const Text('Correct Answers', style: TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
                    ],
                  ),
                  Column(
                    children: [
                      Text('$attemptedCount', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppColors.correctGreen)),
                      const SizedBox(height: 2),
                      const Text('Questions Attempted', style: TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context); // Close dialog
              Navigator.pop(context); // Exit exam screen
            },
            child: const Text('Close'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              setState(() {
                _currentIndex = 0;
                _userAnswers.clear();
                _immediateFeedbacks.clear();
                _lockedQuestions.clear();
              });
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            child: const Text('Practice Again', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _confirmSubmit() {
    final counts = _calculateStatusCounts();
    final answered = counts['answered'] ?? 0;
    final notAnswered = counts['not_answered'] ?? 0;
    final notVisited = counts['not_visited'] ?? 0;
    final totalMarked = (counts['marked'] ?? 0) + (counts['ans_marked'] ?? 0);

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
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.backgroundLight,
                borderRadius: AppStyles.borderRadiusM,
                border: Border.all(color: AppColors.cardBorderLight),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildSummaryPill('Answered', '$answered', AppColors.cbtAnswered),
                  _buildSummaryPill('Not Answered', '$notAnswered', AppColors.cbtNotAnswered),
                  _buildSummaryPill('Not Visited', '$notVisited', AppColors.cbtNotVisited),
                  _buildSummaryPill('Marked', '$totalMarked', AppColors.cbtMarked),
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
      final isAuthError = _errorMessage!.toLowerCase().contains('auth') ||
          _errorMessage!.toLowerCase().contains('token') ||
          _errorMessage!.toLowerCase().contains('log in');
      final isSubscriptionError = _errorMessage!.toLowerCase().contains('subscription') ||
          _errorMessage!.toLowerCase().contains('access_denied') ||
          _errorMessage!.toLowerCase().contains('premium');

      return Scaffold(
        appBar: AppBar(title: const Text('Examination Access')),
        body: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    color: AppColors.warningAmber.withOpacity(0.12),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.lock_outline, size: 40, color: AppColors.warningAmber),
                ),
                const SizedBox(height: 18),
                Text(
                  isAuthError
                      ? 'Student Login Required'
                      : (isSubscriptionError ? 'Active Subscription Required' : 'Unable to Open Examination'),
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 10),
                Text(
                  _errorMessage!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 13.5, color: AppColors.textSecondaryLight, height: 1.4),
                ),
                const SizedBox(height: 24),
                if (!widget.isDemoMode) ...[
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.pushReplacement(
                          context,
                          MaterialPageRoute(
                            builder: (_) => CbtExamScreen(quizId: widget.quizId, isDemoMode: true),
                          ),
                        );
                      },
                      icon: const Icon(Icons.remove_red_eye_outlined, color: Colors.white, size: 18),
                      label: const Text('Practice Sample Drill (10 Free Questions)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
                if (isAuthError) ...[
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const LoginScreen()),
                        );
                      },
                      icon: const Icon(Icons.login, color: Colors.white, size: 18),
                      label: const Text('Log In to Your Account', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryDark,
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
                if (isSubscriptionError || !isAuthError) ...[
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const SubscriptionPlansScreen()),
                        );
                      },
                      icon: const Icon(Icons.workspace_premium_outlined, color: AppColors.accent, size: 18),
                      label: const Text('View Subscription Packages', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.accent)),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 13),
                        side: const BorderSide(color: AppColors.accent, width: 1.5),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
                TextButton.icon(
                  onPressed: _startOrResumeExam,
                  icon: const Icon(Icons.refresh, size: 16),
                  label: const Text('Retry Connection'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (_questions.isEmpty) {
      return Scaffold(
        appBar: AppBar(title: const Text('CBT Examination')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.school, size: 54, color: AppColors.primary),
                const SizedBox(height: 16),
                const Text(
                  'No questions were returned for this examination session.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 14, color: AppColors.textSecondaryLight),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(
                        builder: (_) => CbtExamScreen(quizId: widget.quizId, isDemoMode: true),
                      ),
                    );
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                  child: const Text('Launch Practice Questions Drill', style: TextStyle(color: Colors.white)),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final q = _questions[_currentIndex] as Map<String, dynamic>;
    final qId = SafeParse.toInt(q['id']);
    final selectedOpt = _userAnswers[qId];
    final isMarked = _markedForReview[qId] == true;
    final fb = _immediateFeedbacks[qId];
    final isLocked = _lockedQuestions[qId] == true;

    final langProvider = Provider.of<LanguageProvider>(context);
    final isHindi = langProvider.isHindi;

    // Bilingual text extraction with bulletproof fallbacks (Never "null")
    final qText = CitationCleaner.clean((isHindi && q['question_text_hi']?.toString().isNotEmpty == true)
        ? q['question_text_hi'].toString()
        : (q['question_text'] ?? q['question'] ?? q['text'] ?? q['title'] ?? 'Question ${q['index'] ?? (_currentIndex + 1)}').toString());

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
          'text': (q['option_a'] ?? 'Option A').toString(),
          if (q['option_a_hi'] != null) 'text_hi': q['option_a_hi'],
        },
        {
          'key': 'B',
          'text': (q['option_b'] ?? 'Option B').toString(),
          if (q['option_b_hi'] != null) 'text_hi': q['option_b_hi'],
        },
        {
          'key': 'C',
          'text': (q['option_c'] ?? 'Option C').toString(),
          if (q['option_c_hi'] != null) 'text_hi': q['option_c_hi'],
        },
        {
          'key': 'D',
          'text': (q['option_d'] ?? 'Option D').toString(),
          if (q['option_d_hi'] != null) 'text_hi': q['option_d_hi'],
        },
      ]);
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Q ${_currentIndex + 1} of ${_questions.length}', style: const TextStyle(fontWeight: FontWeight.bold)),
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

            // Practice Progress & Streaks Dashboard
            if (_isImmediate) _buildPracticeDashboard(),

            // Question & Options Content
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Question Header / Subject Pill & Marks
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.primarySubtle,
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            q['subject_name']?.toString().isNotEmpty == true
                                ? q['subject_name'].toString()
                                : 'General Agriculture',
                            style: const TextStyle(
                              color: AppColors.primaryDark,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            '+${q['marks'] ?? 1.0} • -${q['negative_marks'] ?? 0.25} Marks',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.textSecondaryLight),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),

                    // Question Text in Glass Box
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: AppStyles.glassCardDecoration(context),
                      child: Text(
                        qText,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          height: 1.45,
                        ),
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Options List
                    ...List.generate(optionsList.length, (idx) {
                      final opt = optionsList[idx];
                      final displayLabel = (idx < 26) ? String.fromCharCode(65 + idx) : '${idx + 1}';
                      final optionKey = opt['key']?.toString() ?? displayLabel;
                      final optText = CitationCleaner.clean((isHindi && opt['text_hi']?.toString().isNotEmpty == true)
                          ? opt['text_hi'].toString()
                          : (opt['text'] ?? opt['option_text'] ?? opt['content'] ?? '').toString());

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

                    // Immediate Feedback Card (for Practice Sets & Demo Mode)
                    if (fb != null) ...[
                      const SizedBox(height: 16),
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
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppColors.primary.withOpacity(0.12),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Row(
          children: [
            Container(
              width: 32,
              height: 32,
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
            const SizedBox(width: 14),
            Expanded(
              child: Text(
                text,
                style: const TextStyle(fontSize: 14, height: 1.35),
              ),
            ),
            if (isCorrectOption) ...[
              const SizedBox(width: 6),
              const Icon(Icons.check_circle, color: AppColors.correctGreen, size: 22),
            ] else if (isIncorrectSelection) ...[
              const SizedBox(width: 6),
              const Icon(Icons.cancel, color: AppColors.incorrectRed, size: 22),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildPracticeDashboard() {
    int attempted = 0;
    int correct = 0;
    int incorrect = 0;
    _immediateFeedbacks.forEach((_, fb) {
      attempted++;
      if (fb['is_correct'] == true) {
        correct++;
      } else {
        incorrect++;
      }
    });

    final accuracy = attempted > 0 ? ((correct / attempted) * 100).toStringAsFixed(0) : '0';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFFF0FDF4),
        border: Border(bottom: BorderSide(color: AppColors.primary.withOpacity(0.2))),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildPracticeDashItem('Attempted', '$attempted', AppColors.primary),
          _buildPracticeDashItem('Correct', '$correct', const Color(0xFF117E3A)),
          _buildPracticeDashItem('Incorrect', '$incorrect', const Color(0xFFDC2626)),
          _buildPracticeDashItem('Streak', '🔥 $_currentStreak', const Color(0xFFD97706)),
          _buildPracticeDashItem('Best', '🏆 $_highestStreak', const Color(0xFF117E3A)),
          _buildPracticeDashItem('Accuracy', '$accuracy%', const Color(0xFF2563EB)),
        ],
      ),
    );
  }

  Widget _buildPracticeDashItem(String label, String value, Color color) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          value,
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: color),
        ),
        const SizedBox(height: 1),
        Text(
          label,
          style: const TextStyle(fontSize: 9.5, color: AppColors.textSecondaryLight, fontWeight: FontWeight.w500),
        ),
      ],
    );
  }

  Widget _buildImmediateFeedbackBox(Map<String, dynamic> fb) {
    final isCorrect = fb['is_correct'] == true;
    final message = fb['feedback_message'] ?? (isCorrect ? 'Excellent! Correct Answer!' : 'Not quite! Learn from this question.');
    final explanation = fb['explanation']?.toString() ?? '';
    final correctOpt = fb['correct_option']?.toString().toUpperCase() ?? '';
    final marks = fb['marks_awarded'] ?? 0;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFF0FDF4),
        borderRadius: AppStyles.borderRadiusL,
        border: Border(
          left: const BorderSide(color: Color(0xFF117E3A), width: 5.0),
          top: BorderSide(color: isCorrect ? const Color(0xFF86EFAC) : const Color(0xFFFECACA)),
          right: BorderSide(color: isCorrect ? const Color(0xFF86EFAC) : const Color(0xFFFECACA)),
          bottom: BorderSide(color: isCorrect ? const Color(0xFF86EFAC) : const Color(0xFFFECACA)),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(
                isCorrect ? Icons.check_circle : Icons.cancel,
                color: isCorrect ? const Color(0xFF117E3A) : const Color(0xFFDC2626),
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
                  color: isCorrect ? const Color(0xFF117E3A) : const Color(0xFFDC2626),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${marks >= 0 ? "+" : ""}$marks Marks',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
                ),
              ),
            ],
          ),
          if (correctOpt.isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFDCFCE7),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFF86EFAC)),
              ),
              child: Text(
                '✔ Correct Answer: Option $correctOpt',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF117E3A)),
              ),
            ),
          ],
          const SizedBox(height: 12),
          const Text(
            'Correct Answer – Explanation',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF117E3A)),
          ),
          const SizedBox(height: 4),
          Text(
            explanation.isNotEmpty ? explanation : 'Explanation is not available for this question.',
            style: const TextStyle(fontSize: 13, height: 1.45, color: Color(0xFF1E293B)),
          ),
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
            icon: const Icon(Icons.arrow_forward, size: 16, color: Colors.white),
            label: Text(
              _currentIndex < _questions.length - 1 ? 'Next Question →' : 'Finish Practice Drill',
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: isCorrect ? AppColors.correctGreen : AppColors.primary,
              padding: const EdgeInsets.symmetric(vertical: 10),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomActionBar(bool isMarked, bool isLocked) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: AppColors.cardBorderLight)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, -3),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Previous Button
          OutlinedButton(
            onPressed: _currentIndex > 0
                ? () => _navigateToQuestion(_currentIndex - 1)
                : null,
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            ),
            child: const Text('Previous', style: TextStyle(fontSize: 13)),
          ),

          // Mark for Review Button
          OutlinedButton.icon(
            onPressed: _toggleMarkForReview,
            icon: Icon(
              isMarked ? Icons.bookmark : Icons.bookmark_border,
              size: 16,
              color: isMarked ? AppColors.cbtMarked : AppColors.textSecondaryLight,
            ),
            label: Text(
              isMarked ? 'Marked' : 'Review',
              style: TextStyle(
                fontSize: 12,
                color: isMarked ? AppColors.cbtMarked : AppColors.textSecondaryLight,
              ),
            ),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              side: BorderSide(color: isMarked ? AppColors.cbtMarked : AppColors.cardBorderLight),
            ),
          ),

          // Clear Selection
          if (!isLocked) ...[
            TextButton(
              onPressed: _clearSelection,
              child: const Text('Clear', style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight)),
            ),
          ],

          // Next or Submit Button
          if (_currentIndex < _questions.length - 1)
            ElevatedButton(
              onPressed: () => _navigateToQuestion(_currentIndex + 1),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              ),
              child: const Text('Next', style: TextStyle(fontSize: 13, color: Colors.white, fontWeight: FontWeight.bold)),
            )
          else
            ElevatedButton(
              onPressed: _confirmSubmit,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.cbtAnswered,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              ),
              child: const Text('Submit', style: TextStyle(fontSize: 13, color: Colors.white, fontWeight: FontWeight.bold)),
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
      builder: (sheetContext) {
        final counts = _calculateStatusCounts();

        return Container(
          height: MediaQuery.of(sheetContext).size.height * 0.75,
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Question Palette',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(sheetContext),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              // Status Legend & Counters
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.backgroundLight,
                  borderRadius: AppStyles.borderRadiusM,
                  border: Border.all(color: AppColors.cardBorderLight),
                ),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        _buildLegendItem('Answered', AppColors.cbtAnswered, counts['answered'] ?? 0),
                        _buildLegendItem('Not Answered', AppColors.cbtNotAnswered, counts['not_answered'] ?? 0),
                        _buildLegendItem('Not Visited', AppColors.cbtNotVisited, counts['not_visited'] ?? 0),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _buildLegendItem('Marked Review', AppColors.cbtMarked, counts['marked'] ?? 0),
                        _buildLegendItem(
                          'Answered & Marked',
                          AppColors.cbtAnswered,
                          counts['ans_marked'] ?? 0,
                          hasPurpleBadge: true,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const Divider(height: 20),

              // Question Numbers Grid
              Expanded(
                child: GridView.builder(
                  padding: const EdgeInsets.only(bottom: 16),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 5,
                    crossAxisSpacing: 8,
                    mainAxisSpacing: 8,
                    childAspectRatio: 1.0,
                  ),
                  itemCount: _questions.length,
                  itemBuilder: (context, index) {
                    final raw = _questions[index];
                    if (raw is! Map) return const SizedBox.shrink();
                    final q = raw as Map<String, dynamic>;
                    final qId = SafeParse.toInt(q['id']);
                    final hasAnswer = _userAnswers[qId] != null && _userAnswers[qId]!.trim().isNotEmpty;
                    final isVisited = _visitedQuestionIds.contains(qId);
                    final isMarked = _markedForReview[qId] == true;
                    final isCurrent = index == _currentIndex;

                    Color bgColor;
                    Color textColor;
                    bool showPurpleBadge = false;

                    if (hasAnswer && isMarked) {
                      bgColor = AppColors.cbtAnswered;
                      textColor = Colors.white;
                      showPurpleBadge = true;
                    } else if (hasAnswer) {
                      bgColor = AppColors.cbtAnswered;
                      textColor = Colors.white;
                    } else if (isMarked) {
                      bgColor = AppColors.cbtMarked;
                      textColor = Colors.white;
                    } else if (isVisited) {
                      bgColor = AppColors.cbtNotAnswered;
                      textColor = Colors.white;
                    } else {
                      bgColor = AppColors.cbtNotVisited;
                      textColor = const Color(0xFF334155);
                    }

                    return InkWell(
                      onTap: () {
                        Navigator.pop(sheetContext);
                        _navigateToQuestion(index);
                      },
                      borderRadius: BorderRadius.circular(6),
                      child: Stack(
                        clipBehavior: Clip.none,
                        children: [
                          Container(
                            width: double.infinity,
                            height: double.infinity,
                            decoration: BoxDecoration(
                              color: bgColor,
                              borderRadius: BorderRadius.circular(6),
                              border: isCurrent
                                  ? Border.all(color: AppColors.cbtCurrentOutline, width: 3.0)
                                  : Border.all(
                                      color: (!isVisited && !hasAnswer && !isMarked)
                                          ? const Color(0xFF94A3B8)
                                          : Colors.transparent,
                                      width: 1.0,
                                    ),
                            ),
                            child: Center(
                              child: Text(
                                '${index + 1}',
                                style: TextStyle(
                                  fontWeight: isCurrent ? FontWeight.w900 : FontWeight.bold,
                                  color: textColor,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                          ),
                          if (showPurpleBadge)
                            Positioned(
                              top: 3,
                              right: 3,
                              child: Container(
                                width: 8,
                                height: 8,
                                decoration: BoxDecoration(
                                  color: AppColors.cbtMarked,
                                  shape: BoxShape.circle,
                                  border: Border.all(color: Colors.white, width: 1.5),
                                ),
                              ),
                            ),
                        ],
                      ),
                    );
                  },
                ),
              ),

              // Fixed Submit Button at bottom
              AppButton(
                label: 'Submit Examination',
                onPressed: () {
                  Navigator.pop(sheetContext);
                  _confirmSubmit();
                },
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildLegendItem(String label, Color color, int count, {bool hasPurpleBadge = false}) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Stack(
          clipBehavior: Clip.none,
          children: [
            Container(
              width: 22,
              height: 22,
              decoration: BoxDecoration(
                color: color,
                borderRadius: BorderRadius.circular(4),
                border: Border.all(
                  color: color == AppColors.cbtNotVisited ? const Color(0xFF94A3B8) : Colors.transparent,
                  width: 1.0,
                ),
              ),
              child: Center(
                child: Text(
                  '$count',
                  style: TextStyle(
                    color: color == AppColors.cbtNotVisited ? const Color(0xFF334155) : Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 11,
                  ),
                ),
              ),
            ),
            if (hasPurpleBadge)
              Positioned(
                top: -2,
                right: -2,
                child: Container(
                  width: 7,
                  height: 7,
                  decoration: BoxDecoration(
                    color: AppColors.cbtMarked,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 1.0),
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(width: 5),
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: AppColors.textSecondaryLight,
          ),
        ),
      ],
    );
  }
}
