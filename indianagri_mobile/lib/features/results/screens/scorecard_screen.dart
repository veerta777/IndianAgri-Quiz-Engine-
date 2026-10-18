import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/state_views.dart';
import 'solutions_review_screen.dart';
import '../../dashboard/screens/dashboard_screen.dart';

class ScorecardScreen extends StatefulWidget {
  final int attemptId;

  const ScorecardScreen({super.key, required this.attemptId});

  @override
  State<ScorecardScreen> createState() => _ScorecardScreenState();
}

class _ScorecardScreenState extends State<ScorecardScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _scorecard;
  List<dynamic> _breakdown = [];

  @override
  void initState() {
    super.initState();
    _fetchScorecard();
  }

  Future<void> _fetchScorecard() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.scorecard(widget.attemptId));
      if (res is Map<String, dynamic>) {
        setState(() {
          _scorecard = res['scorecard'] as Map<String, dynamic>?;
          _breakdown = (res['breakdown'] as List<dynamic>?) ?? [];
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load examination results.';
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
        title: const Text('Examination Scorecard'),
        leading: IconButton(
          icon: const Icon(Icons.home),
          onPressed: () {
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const DashboardScreen()),
              (route) => false,
            );
          },
        ),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingView(message: 'Calculating official score & percentile...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(
        message: _errorMessage!,
        onRetry: _fetchScorecard,
      );
    }

    final s = _scorecard!;
    final title = s['quiz_title'] ?? 'Examination';
    final score = (s['score'] as num?)?.toDouble() ?? 0.0;
    final totalMarks = (s['total_marks'] as num?)?.toDouble() ?? 0.0;
    final percentage = (s['percentage'] as num?)?.toDouble() ?? 0.0;
    final isPassed = s['passed'] == true;
    final correct = s['correct_answers'] ?? 0;
    final incorrect = s['incorrect_answers'] ?? 0;
    final unattempted = s['unattempted_questions'] ?? 0;
    final negativeMarks = (s['negative_deductions'] as num?)?.toDouble() ?? 0.0;
    final timeSpent = s['time_spent_formatted'] ?? '00m 00s';
    final percentile = s['percentile'];

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Status Banner
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: isPassed ? AppColors.correctGreenBg : AppColors.incorrectRedBg,
                borderRadius: AppStyles.borderRadiusL,
                border: Border.all(color: isPassed ? AppColors.correctGreen : AppColors.incorrectRed),
              ),
              child: Column(
                children: [
                  Icon(
                    isPassed ? Icons.emoji_events : Icons.refresh,
                    size: 48,
                    color: isPassed ? AppColors.correctGreen : AppColors.incorrectRed,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    isPassed ? 'Congratulations! Passed' : 'Needs Practice & Revision',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                      color: isPassed ? const Color(0xFF065F46) : const Color(0xFF991B1B),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    title,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    '${score.toStringAsFixed(2)} / ${totalMarks.toStringAsFixed(2)}',
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                      color: isPassed ? AppColors.correctGreen : AppColors.incorrectRed,
                    ),
                  ),
                  Text(
                    'Score (${percentage.toStringAsFixed(1)}%)',
                    style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Performance Metrics Grid
            GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              childAspectRatio: 1.8,
              children: [
                _buildMetricTile('Correct', '$correct', AppColors.correctGreen, Icons.check_circle_outline),
                _buildMetricTile('Incorrect', '$incorrect', AppColors.incorrectRed, Icons.cancel_outlined),
                _buildMetricTile('Unattempted', '$unattempted', AppColors.textSecondaryLight, Icons.remove_circle_outline),
                _buildMetricTile('Negative Marks', '-${negativeMarks.toStringAsFixed(2)}', AppColors.incorrectRed, Icons.trending_down),
                _buildMetricTile('Time Spent', timeSpent, AppColors.primary, Icons.timer_outlined),
                _buildMetricTile(
                  'Percentile',
                  percentile != null ? '${(percentile as num).toStringAsFixed(1)}th' : 'N/A',
                  const Color(0xFF8B5CF6),
                  Icons.leaderboard_outlined,
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Subject Breakdown
            if (_breakdown.isNotEmpty) ...[
              const Text(
                'Subject Mastery Breakdown',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 10),
              ..._breakdown.map((b) => _buildBreakdownCard(b)),
              const SizedBox(height: 20),
            ],

            // Actions
            AppButton(
              label: 'View Detailed Solutions & Notes',
              icon: Icons.assignment_turned_in,
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => SolutionsReviewScreen(attemptId: widget.attemptId),
                  ),
                );
              },
            ),
            const SizedBox(height: 10),
            OutlinedButton(
              onPressed: () {
                Navigator.pushAndRemoveUntil(
                  context,
                  MaterialPageRoute(builder: (_) => const DashboardScreen()),
                  (route) => false,
                );
              },
              child: const Text('Back to Student Dashboard'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMetricTile(String label, String value, Color color, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: AppStyles.cardDecoration(context),
      child: Row(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: color)),
                const SizedBox(height: 2),
                Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBreakdownCard(dynamic item) {
    final name = item['subject_name'] ?? 'Subject';
    final accuracy = (item['accuracy'] as num?)?.toDouble() ?? 0.0;
    final total = item['total_questions'] ?? 0;
    final correct = item['correct_count'] ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: AppStyles.cardDecoration(context),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              Text('$correct / $total Correct (${accuracy.toStringAsFixed(1)}%)',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.primary)),
            ],
          ),
          const SizedBox(height: 6),
          LinearProgressIndicator(
            value: (accuracy / 100.0).clamp(0.0, 1.0),
            backgroundColor: AppColors.cardBorderLight,
            valueColor: AlwaysStoppedAnimation<Color>(
              accuracy >= 70 ? AppColors.correctGreen : (accuracy >= 45 ? AppColors.warningAmber : AppColors.incorrectRed),
            ),
          ),
        ],
      ),
    );
  }
}
