import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/localization/app_localizations.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../../core/widgets/state_views.dart';

class SolutionsReviewScreen extends StatefulWidget {
  final int attemptId;

  const SolutionsReviewScreen({super.key, required this.attemptId});

  @override
  State<SolutionsReviewScreen> createState() => _SolutionsReviewScreenState();
}

class _SolutionsReviewScreenState extends State<SolutionsReviewScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _questions = [];
  final Set<int> _bookmarkedQuestions = {};

  @override
  void initState() {
    super.initState();
    _fetchSolutions();
  }

  Future<void> _fetchSolutions() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.solutions(widget.attemptId));
      if (res is List<dynamic>) {
        setState(() {
          _questions = res;
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load solutions review.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _toggleBookmark(int questionId) async {
    final isBookmarked = _bookmarkedQuestions.contains(questionId);
    setState(() {
      if (isBookmarked) {
        _bookmarkedQuestions.remove(questionId);
      } else {
        _bookmarkedQuestions.add(questionId);
      }
    });

    try {
      await ApiClient.post(
        ApiEndpoints.toggleBookmark,
        body: {'question_id': questionId},
      );
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Solutions & Review'),
        actions: const [
          Padding(
            padding: EdgeInsets.only(right: 14.0),
            child: LanguageToggle(),
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingView(message: 'Loading verified solutions and explanations...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(
        message: _errorMessage!,
        onRetry: _fetchSolutions,
      );
    }

    if (_questions.isEmpty) {
      return const EmptyStateView(
        title: 'Solutions Not Available',
        description: 'Solutions for this examination are not permitted for immediate review or attempt is incomplete.',
      );
    }

    final isHindi = Provider.of<LanguageProvider>(context).isHindi;

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _questions.length,
      itemBuilder: (context, index) {
        final q = _questions[index] as Map<String, dynamic>;
        final qId = (q['id'] as num?)?.toInt() ?? 0;
        final selectedOpt = q['selected_option']?.toString();
        final correctOpt = q['correct_option']?.toString() ?? '';
        final isCorrect = q['is_correct'] == 1;
        final isUnattempted = selectedOpt == null || selectedOpt.isEmpty;
        final marks = (q['marks_awarded'] as num?)?.toDouble() ?? 0.0;
        final isBookmarked = _bookmarkedQuestions.contains(qId);

        final qText = (isHindi && q['question_text_hi'] != null && q['question_text_hi'].toString().isNotEmpty)
            ? q['question_text_hi'].toString()
            : q['question_text'].toString();

        final explanation = (isHindi && q['explanation_hi'] != null && q['explanation_hi'].toString().isNotEmpty)
            ? q['explanation_hi'].toString()
            : q['explanation'].toString();

        return Container(
          margin: const EdgeInsets.only(bottom: 16),
          padding: const EdgeInsets.all(16),
          decoration: AppStyles.cardDecoration(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Question Header Bar
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: isCorrect
                          ? AppColors.correctGreenBg
                          : (isUnattempted ? AppColors.warningAmberBg : AppColors.incorrectRedBg),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      isCorrect ? 'Correct (+${marks.toStringAsFixed(1)})' : (isUnattempted ? 'Unattempted (0.0)' : 'Incorrect (${marks.toStringAsFixed(1)})'),
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 11,
                        color: isCorrect ? AppColors.correctGreen : (isUnattempted ? AppColors.warningAmber : AppColors.incorrectRed),
                      ),
                    ),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: Icon(
                      isBookmarked ? Icons.bookmark : Icons.bookmark_border,
                      size: 20,
                      color: isBookmarked ? AppColors.warningAmber : AppColors.textSecondaryLight,
                    ),
                    onPressed: () => _toggleBookmark(qId),
                  ),
                ],
              ),
              const SizedBox(height: 10),

              // Question Text
              Text(
                'Q${index + 1}. $qText',
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15, height: 1.4),
              ),
              const SizedBox(height: 14),

              // Options Review
              _buildReviewOption('A', q['option_a']?.toString() ?? '', selectedOpt, correctOpt),
              const SizedBox(height: 6),
              _buildReviewOption('B', q['option_b']?.toString() ?? '', selectedOpt, correctOpt),
              const SizedBox(height: 6),
              _buildReviewOption('C', q['option_c']?.toString() ?? '', selectedOpt, correctOpt),
              const SizedBox(height: 6),
              _buildReviewOption('D', q['option_d']?.toString() ?? '', selectedOpt, correctOpt),

              // Scientific Explanation
              if (explanation.isNotEmpty) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppColors.primarySubtle,
                    borderRadius: AppStyles.borderRadiusM,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Scientific Explanation:',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.primaryDark),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        explanation,
                        style: const TextStyle(fontSize: 13, height: 1.4, color: AppColors.primaryDark),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  Widget _buildReviewOption(String key, String text, String? selectedKey, String correctKey) {
    final isSelected = selectedKey == key;
    final isCorrect = correctKey == key;

    Color bgColor = Colors.transparent;
    Color borderColor = AppColors.cardBorderLight;
    IconData? icon;
    Color iconColor = Colors.transparent;

    if (isCorrect) {
      bgColor = AppColors.correctGreenBg;
      borderColor = AppColors.correctGreen;
      icon = Icons.check_circle;
      iconColor = AppColors.correctGreen;
    } else if (isSelected && !isCorrect) {
      bgColor = AppColors.incorrectRedBg;
      borderColor = AppColors.incorrectRed;
      icon = Icons.cancel;
      iconColor = AppColors.incorrectRed;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: AppStyles.borderRadiusM,
        border: Border.all(color: borderColor, width: (isCorrect || isSelected) ? 1.5 : 1),
      ),
      child: Row(
        children: [
          Container(
            width: 24,
            height: 24,
            decoration: BoxDecoration(
              color: isCorrect ? AppColors.correctGreen : (isSelected ? AppColors.incorrectRed : AppColors.cardBorderLight),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                key,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                  color: (isCorrect || isSelected) ? Colors.white : AppColors.textPrimaryLight,
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(child: Text(text, style: const TextStyle(fontSize: 13))),
          if (icon != null) ...[
            const SizedBox(width: 6),
            Icon(icon, color: iconColor, size: 18),
          ],
        ],
      ),
    );
  }
}
