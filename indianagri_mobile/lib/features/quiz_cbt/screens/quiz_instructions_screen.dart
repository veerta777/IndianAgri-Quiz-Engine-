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
  List<dynamic> _sampleQuestions = [];
  bool _hasAgreed = false;
  bool _showQuestionsPreview = false;

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
        var sampleQs = (res['sample_questions'] as List<dynamic>?) ?? [];
        if (sampleQs.isEmpty && res['questions'] is List) {
          sampleQs = res['questions'];
        }
        if (sampleQs.isEmpty) {
          try {
            final qRes = await ApiClient.get(ApiEndpoints.quizQuestions(widget.quizId));
            if (qRes is Map<String, dynamic> && qRes['questions'] is List) {
              sampleQs = qRes['questions'];
            }
          } catch (_) {}
        }

        setState(() {
          _quiz = res['quiz'] as Map<String, dynamic>?;
          _entitlement = res['entitlement'] as Map<String, dynamic>?;
          _sampleQuestions = sampleQs;
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
        title: const Text('Examination Instructions', style: TextStyle(fontWeight: FontWeight.bold)),
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
    final totalQ = q['total_questions'] ?? 50;
    final totalMarks = (q['total_marks'] as num?)?.toDouble() ?? (totalQ * 1.0);
    final duration = q['duration_minutes'] ?? 0;
    final correctMarks = (q['correct_marks'] as num?)?.toDouble() ?? 1.0;
    final negMarks = (q['negative_marks'] as num?)?.toDouble() ?? 0.25;
    final instructions = q['instructions']?.toString().isNotEmpty == true
        ? q['instructions']
        : 'Attempt all questions with precision. In CBT mode, navigate using the bottom bar or Question Palette. Timer will automatically submit your attempt when it runs out.';
    final isAllowed = _entitlement?['allowed'] == true;
    final reason = _entitlement?['reason'] ?? 'An active subscription is required to unlock the full examination.';

    return SafeArea(
      child: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Title Header Card
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: AppStyles.heroGradientDecoration(),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: const Text(
                            'ONLINE CBT EXAMINATION',
                            style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          title,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 20, color: Colors.white),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          '$totalQ Questions • ${duration > 0 ? "$duration Minutes" : "Practice Drill"} • Max Marks: $totalMarks',
                          style: TextStyle(color: Colors.white.withOpacity(0.9), fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Key Details Card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: AppStyles.glassCardDecoration(context),
                    child: Column(
                      children: [
                        _buildDetailRow('Total Questions', '$totalQ Multiple Choice Questions (MCQs)'),
                        const Divider(height: 16),
                        _buildDetailRow('Maximum Marks', '$totalMarks Marks'),
                        const Divider(height: 16),
                        _buildDetailRow('Examination Duration', duration > 0 ? '$duration Minutes' : 'Untimed Practice Drill'),
                        const Divider(height: 16),
                        _buildDetailRow('Marking Scheme', '+$correctMarks for Correct • -$negMarks for Incorrect'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Sample Questions Preview Accordion (Guarantees candidate can ALWAYS preview questions!)
                  if (_sampleQuestions.isNotEmpty) ...[
                    Container(
                      decoration: AppStyles.glassCardDecoration(context),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          InkWell(
                            onTap: () {
                              setState(() {
                                _showQuestionsPreview = !_showQuestionsPreview;
                              });
                            },
                            borderRadius: AppStyles.borderRadiusL,
                            child: Padding(
                              padding: const EdgeInsets.all(14),
                              child: Row(
                                children: [
                                  const Icon(Icons.quiz, color: AppColors.primary, size: 20),
                                  const SizedBox(width: 10),
                                  const Expanded(
                                    child: Text(
                                      'Preview Syllabus & Sample Questions',
                                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                    ),
                                  ),
                                  Icon(
                                    _showQuestionsPreview ? Icons.keyboard_arrow_up : Icons.keyboard_arrow_down,
                                    color: AppColors.textSecondaryLight,
                                  ),
                                ],
                              ),
                            ),
                          ),
                          if (_showQuestionsPreview) ...[
                            const Divider(height: 1),
                            Padding(
                              padding: const EdgeInsets.all(14),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  ..._sampleQuestions.map((sq) {
                                    final sIndex = sq['index'] ?? 1;
                                    final sText = sq['question_text'] ?? '';
                                    final sSub = sq['subject_name'] ?? '';

                                    return Padding(
                                      padding: const EdgeInsets.only(bottom: 12),
                                      child: Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Container(
                                            width: 22,
                                            height: 22,
                                            decoration: const BoxDecoration(
                                              color: AppColors.primarySubtle,
                                              shape: BoxShape.circle,
                                            ),
                                            child: Center(
                                              child: Text(
                                                '$sIndex',
                                                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 10),
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text(sText, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500)),
                                                if (sSub.toString().isNotEmpty) ...[
                                                  const SizedBox(height: 2),
                                                  Text(sSub, style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
                                                ],
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    );
                                  }),
                                ],
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Paywall / Access check notice if not allowed
                  if (!isAllowed) ...[
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.warningAmberBg,
                        borderRadius: AppStyles.borderRadiusL,
                        border: Border.all(color: AppColors.warningAmber.withOpacity(0.4)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: const [
                              Icon(Icons.lock, color: AppColors.warningAmber, size: 20),
                              SizedBox(width: 8),
                              Text(
                                'Full Examination Locked',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.warningAmber),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(
                            reason,
                            style: const TextStyle(fontSize: 13, height: 1.4),
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              // 1-Click Free Sample Drill Button
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () {
                                    Navigator.pushReplacement(
                                      context,
                                      MaterialPageRoute(
                                        builder: (_) => CbtExamScreen(quizId: widget.quizId, isDemoMode: true),
                                      ),
                                    );
                                  },
                                  icon: const Icon(Icons.play_circle_outline, size: 16),
                                  label: const Text('Try 10 Free Qs', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                                  style: OutlinedButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(vertical: 8),
                                    side: const BorderSide(color: AppColors.primary),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              // Subscribe Plans Button
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () {
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (_) => const SubscriptionPlansScreen(),
                                      ),
                                    );
                                  },
                                  icon: const Icon(Icons.star, size: 16),
                                  label: const Text('Unlock All (Subscribe)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.accent,
                                    padding: const EdgeInsets.symmetric(vertical: 8),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Instructions Box
                  const Text(
                    'Code of Conduct & Instructions',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: AppStyles.glassCardDecoration(context),
                    child: Text(
                      instructions,
                      style: const TextStyle(fontSize: 13, height: 1.5),
                    ),
                  ),
                  const SizedBox(height: 16),

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

          // Bottom Action Bar
          Container(
            padding: const EdgeInsets.all(16),
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
            child: isAllowed
                ? AppButton(
                    label: 'Enter CBT Examination Hall',
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
                : OutlinedButton.icon(
                    onPressed: () {
                      Navigator.pushReplacement(
                        context,
                        MaterialPageRoute(
                          builder: (_) => CbtExamScreen(quizId: widget.quizId, isDemoMode: true),
                        ),
                      );
                    },
                    icon: const Icon(Icons.science_outlined),
                    label: const Text('Launch Free Practice Drill (Sample Questions)', style: TextStyle(fontWeight: FontWeight.bold)),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      side: const BorderSide(color: AppColors.primary, width: 1.8),
                    ),
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
        Text(label, style: const TextStyle(color: AppColors.textSecondaryLight, fontSize: 13)),
        const SizedBox(width: 12),
        Flexible(
          child: Text(
            value,
            textAlign: TextAlign.right,
            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
          ),
        ),
      ],
    );
  }
}
