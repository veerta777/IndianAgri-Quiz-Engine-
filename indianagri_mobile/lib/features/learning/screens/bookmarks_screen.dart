import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/state_views.dart';
import '../../quiz_cbt/screens/quiz_instructions_screen.dart';

class BookmarksScreen extends StatefulWidget {
  const BookmarksScreen({super.key});

  @override
  State<BookmarksScreen> createState() => _BookmarksScreenState();
}

class _BookmarksScreenState extends State<BookmarksScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _bookmarks = [];
  bool _isCreatingRevisionQuiz = false;

  @override
  void initState() {
    super.initState();
    _fetchBookmarks();
  }

  Future<void> _fetchBookmarks() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.bookmarks);
      if (res is Map<String, dynamic> && res.containsKey('bookmarks')) {
        setState(() {
          _bookmarks = (res['bookmarks'] as List<dynamic>?) ?? [];
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load bookmarks.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _createRevisionQuiz() async {
    setState(() {
      _isCreatingRevisionQuiz = true;
    });

    try {
      final res = await ApiClient.post(ApiEndpoints.createRevisionQuiz);
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
          SnackBar(content: Text('Failed to generate revision quiz: $e'), backgroundColor: AppColors.incorrectRed),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isCreatingRevisionQuiz = false;
        });
      }
    }
  }

  Future<void> _removeBookmark(int questionId) async {
    setState(() {
      _bookmarks.removeWhere((b) => b['question_id'] == questionId);
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
        title: const Text('Saved Bookmarks'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingView(message: 'Loading your starred questions...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(message: _errorMessage!, onRetry: _fetchBookmarks);
    }

    if (_bookmarks.isEmpty) {
      return const EmptyStateView(
        title: 'No Bookmarked Questions',
        description: 'Star questions during practice or review to build custom revision drill quizzes.',
        icon: Icons.bookmark_border,
      );
    }

    return Column(
      children: [
        // Top Action Bar
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border(bottom: BorderSide(color: AppColors.cardBorderLight)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '${_bookmarks.length} Questions Saved',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
              AppButton(
                label: 'Launch Revision Drill',
                icon: Icons.bolt,
                isLoading: _isCreatingRevisionQuiz,
                onPressed: _createRevisionQuiz,
              ),
            ],
          ),
        ),

        // Bookmarks List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: _bookmarks.length,
            itemBuilder: (context, index) {
              final b = _bookmarks[index] as Map<String, dynamic>;
              final qId = (b['question_id'] ?? b['id'] as num?)?.toInt() ?? 0;
              final qText = b['question_text'] ?? 'Question';
              final subject = b['subject_name'] ?? 'General Agriculture';
              final notes = b['notes']?.toString() ?? '';

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
                            color: AppColors.primarySubtle,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            subject,
                            style: const TextStyle(
                              color: AppColors.primaryDark,
                              fontWeight: FontWeight.bold,
                              fontSize: 11,
                            ),
                          ),
                        ),
                        const Spacer(),
                        IconButton(
                          icon: const Icon(Icons.bookmark_remove, size: 20, color: AppColors.incorrectRed),
                          tooltip: 'Remove Bookmark',
                          onPressed: () => _removeBookmark(qId),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      qText,
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14, height: 1.4),
                    ),
                    if (notes.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: AppColors.warningAmberBg,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          'Note: $notes',
                          style: const TextStyle(fontSize: 12, color: AppColors.warningAmber),
                        ),
                      ),
                    ],
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
