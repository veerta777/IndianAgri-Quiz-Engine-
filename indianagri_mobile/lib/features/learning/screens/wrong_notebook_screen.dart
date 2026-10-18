import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/state_views.dart';
import '../../quiz_cbt/screens/quiz_instructions_screen.dart';

class WrongNotebookScreen extends StatefulWidget {
  const WrongNotebookScreen({super.key});

  @override
  State<WrongNotebookScreen> createState() => _WrongNotebookScreenState();
}

class _WrongNotebookScreenState extends State<WrongNotebookScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _entries = [];
  String _currentStatus = 'active'; // 'active' or 'resolved'
  bool _isCreatingRetryQuiz = false;

  @override
  void initState() {
    super.initState();
    _fetchNotebook();
  }

  Future<void> _fetchNotebook() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get('${ApiEndpoints.wrongNotebook}?status=$_currentStatus');
      if (res is Map<String, dynamic> && res.containsKey('entries')) {
        setState(() {
          _entries = (res['entries'] as List<dynamic>?) ?? [];
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load notebook.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _createRetryQuiz() async {
    setState(() {
      _isCreatingRetryQuiz = true;
    });

    try {
      final res = await ApiClient.post(ApiEndpoints.createRetryQuiz);
      if (res is Map<String, dynamic> && res.containsKey('quiz_id')) {
        final quizId = (res['quiz_id'] as num).toInt();
        if (!mounted) return;
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => QuizInstructionsScreen(quizId: quizId),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to generate retry quiz: $e'), backgroundColor: AppColors.incorrectRed),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isCreatingRetryQuiz = false;
        });
      }
    }
  }

  Future<void> _toggleStatus(int questionId, String newStatus) async {
    setState(() {
      _entries.removeWhere((e) => (e['question_id'] ?? e['id']) == questionId);
    });

    try {
      await ApiClient.post(
        ApiEndpoints.updateNotebookStatus,
        body: {
          'question_id': questionId,
          'status': newStatus,
        },
      );
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Wrong Question Notebook'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    return Column(
      children: [
        // Filter Tabs
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border(bottom: BorderSide(color: AppColors.cardBorderLight)),
          ),
          child: Row(
            children: [
              ChoiceChip(
                label: const Text('Active Mistakes'),
                selected: _currentStatus == 'active',
                onSelected: (selected) {
                  if (selected) {
                    setState(() {
                      _currentStatus = 'active';
                    });
                    _fetchNotebook();
                  }
                },
                selectedColor: AppColors.primarySubtle,
                labelStyle: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: _currentStatus == 'active' ? AppColors.primaryDark : AppColors.textSecondaryLight,
                ),
              ),
              const SizedBox(width: 8),
              ChoiceChip(
                label: const Text('Mastered / Resolved'),
                selected: _currentStatus == 'resolved',
                onSelected: (selected) {
                  if (selected) {
                    setState(() {
                      _currentStatus = 'resolved';
                    });
                    _fetchNotebook();
                  }
                },
                selectedColor: AppColors.correctGreenBg,
                labelStyle: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: _currentStatus == 'resolved' ? AppColors.correctGreen : AppColors.textSecondaryLight,
                ),
              ),
              const Spacer(),
              if (_currentStatus == 'active' && _entries.isNotEmpty)
                AppButton(
                  label: 'Launch Retry Drill',
                  icon: Icons.replay,
                  isLoading: _isCreatingRetryQuiz,
                  onPressed: _createRetryQuiz,
                ),
            ],
          ),
        ),

        // List View
        Expanded(
          child: _isLoading
              ? const LoadingView(message: 'Loading your mistake notebook...')
              : (_errorMessage != null
                  ? ErrorStateView(message: _errorMessage!, onRetry: _fetchNotebook)
                  : (_entries.isEmpty
                      ? const EmptyStateView(
                          title: 'Notebook is Clear!',
                          description: 'No active incorrect questions found in your notebook. Excellent accuracy!',
                          icon: Icons.check_circle_outline,
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _entries.length,
                          itemBuilder: (context, index) {
                            final entry = _entries[index] as Map<String, dynamic>;
                            final qId = (entry['question_id'] ?? entry['id'] as num?)?.toInt() ?? 0;
                            final qText = entry['question_text'] ?? 'Question';
                            final subject = entry['subject_name'] ?? 'Agronomy';
                            final explanation = entry['explanation']?.toString() ?? '';
                            final timesIncorrect = entry['times_incorrect'] ?? 1;

                            return Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              padding: const EdgeInsets.all(14),
                              decoration: AppStyles.cardDecoration(context),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                        decoration: BoxDecoration(
                                          color: AppColors.incorrectRedBg,
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(
                                          'Mistake ($timesIncorrect times)',
                                          style: const TextStyle(
                                            color: AppColors.incorrectRed,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 11,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        subject,
                                        style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                                      ),
                                      const Spacer(),
                                      TextButton.icon(
                                        onPressed: () {
                                          _toggleStatus(qId, _currentStatus == 'active' ? 'resolved' : 'active');
                                        },
                                        icon: Icon(
                                          _currentStatus == 'active' ? Icons.check : Icons.undo,
                                          size: 16,
                                          color: _currentStatus == 'active' ? AppColors.correctGreen : AppColors.textSecondaryLight,
                                        ),
                                        label: Text(
                                          _currentStatus == 'active' ? 'Mark Resolved' : 'Reactivate',
                                          style: TextStyle(
                                            fontSize: 11,
                                            color: _currentStatus == 'active' ? AppColors.correctGreen : AppColors.textSecondaryLight,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    qText,
                                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14, height: 1.4),
                                  ),
                                  if (explanation.isNotEmpty) ...[
                                    const SizedBox(height: 10),
                                    Container(
                                      padding: const EdgeInsets.all(10),
                                      decoration: BoxDecoration(
                                        color: AppColors.primarySubtle,
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        'Explanation: $explanation',
                                        style: const TextStyle(fontSize: 12, color: AppColors.primaryDark),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            );
                          },
                        ))),
        ),
      ],
    );
  }
}
