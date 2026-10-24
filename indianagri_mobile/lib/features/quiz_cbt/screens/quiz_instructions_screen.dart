import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/state_views.dart';
import '../../subscriptions/screens/subscription_plans_screen.dart';
import 'cbt_exam_screen.dart';

class QuizInstructionsScreen extends StatefulWidget {
  final int quizId;

  const QuizInstructionsScreen({super.key, required this.quizId});

  @override
  State<QuizInstructionsScreen> createState() => _QuizInstructionsScreenState();
}

class _QuizInstructionsScreenState extends State<QuizInstructionsScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _quiz;
  Map<String, dynamic>? _entitlement;
  bool _hasAgreed = false;

  @override
  void initState() {
    super.initState();
    _fetchInstructions();
  }

  Future<void> _fetchInstructions() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.quizInstructions(widget.quizId));
      if (res is Map<String, dynamic>) {
        setState(() {
          _quiz = res['quiz'] as Map<String, dynamic>?;
          _entitlement = res['entitlement'] as Map<String, dynamic>?;
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load examination details.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Examination Instructions'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingView(message: 'Verifying candidate entitlement...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(
        message: _errorMessage!,
        onRetry: _fetchInstructions,
      );
    }

    final q = _quiz!;
    final title = q['title'] ?? 'Examination';
    final totalQ = q['total_questions'] ?? 0;
    final totalMarks = (q['total_marks'] as num?)?.toDouble() ?? 0.0;
    final duration = q['duration_minutes'] ?? 0;
    final correctMarks = (q['correct_marks'] as num?)?.toDouble() ?? 1.0;
    final negMarks = (q['negative_marks'] as num?)?.toDouble() ?? 0.25;
    final instructions = q['instructions'] ?? 'Read each question carefully before submitting your answer.';
    final isAllowed = _entitlement?['allowed'] == true;
    final reason = _entitlement?['reason'] ?? '';

    return SafeArea(
      child: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Title & Meta
                  Text(
                    title,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
                  ),
                  const SizedBox(height: 16),

                  // Key Details Card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: AppStyles.cardDecoration(context),
                    child: Column(
                      children: [
                        _buildDetailRow('Total Questions', '$totalQ Questions'),
                        const Divider(height: 16),
                        _buildDetailRow('Maximum Marks', '$totalMarks Marks'),
                        const Divider(height: 16),
                        _buildDetailRow('Duration', '$duration Minutes'),
                        const Divider(height: 16),
                        _buildDetailRow('Marking Scheme', '+$correctMarks for Correct • -$negMarks for Incorrect'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Paywall / Access check notice
                  if (!isAllowed) ...[
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppColors.incorrectRedBg,
                        borderRadius: AppStyles.borderRadiusM,
                        border: Border.all(color: AppColors.incorrectRed.withOpacity(0.4)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Icon(Icons.lock, color: AppColors.incorrectRed, size: 22),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  reason.isNotEmpty ? reason : 'Active subscription required to access this examination.',
                                  style: const TextStyle(color: AppColors.incorrectRed, fontSize: 13, height: 1.4),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton.icon(
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => const SubscriptionPlansScreen(),
                                  ),
                                );
                              },
                              icon: const Icon(Icons.arrow_forward, size: 14, color: AppColors.accent),
                              label: const Text(
                                'View Subscription Plans',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.accent),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],

                  // Instructions Box
                  const Text(
                    'Instructions for Candidates',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: AppStyles.cardDecoration(context),
                    child: Text(
                      instructions,
                      style: const TextStyle(fontSize: 13, height: 1.5),
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Agreement Checkbox
                  if (isAllowed) ...[
                    CheckboxListTile(
                      value: _hasAgreed,
                      onChanged: (val) {
                        setState(() {
                          _hasAgreed = val ?? false;
                        });
                      },
                      contentPadding: EdgeInsets.zero,
                      controlAffinity: ListTileControlAffinity.leading,
                      title: const Text(
                        'I have read and understood all the examination instructions and agree to adhere to the code of conduct.',
                        style: TextStyle(fontSize: 13),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),

          // Action CTA
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: AppColors.cardBorderLight)),
            ),
            child: isAllowed
                ? AppButton(
                    label: 'Enter Examination Hall',
                    icon: Icons.play_arrow,
                    onPressed: _hasAgreed
                        ? () {
                            Navigator.pushReplacement(
                              context,
                              MaterialPageRoute(
                                builder: (_) => CbtExamScreen(quizId: widget.quizId),
                              ),
                            );
                          }
                        : null,
                  )
                : AppButton(
                    label: 'Subscribe to Unlock Examination',
                    icon: Icons.lock_open,
                    customColor: AppColors.accent,
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => const SubscriptionPlansScreen(),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight)),
        Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
      ],
    );
  }
}
