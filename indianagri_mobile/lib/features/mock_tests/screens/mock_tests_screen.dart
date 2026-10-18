import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../../core/widgets/state_views.dart';
import '../../quiz_cbt/screens/quiz_instructions_screen.dart';

class MockTestsScreen extends StatefulWidget {
  const MockTestsScreen({super.key});

  @override
  State<MockTestsScreen> createState() => _MockTestsScreenState();
}

class _MockTestsScreenState extends State<MockTestsScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _categories = [];

  @override
  void initState() {
    super.initState();
    _fetchCategories();
  }

  Future<void> _fetchCategories() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.mockCategories);
      if (res is List<dynamic>) {
        setState(() {
          _categories = res;
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load mock test categories.';
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
        title: const Text('Mock Test Series'),
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
      return const LoadingView(message: 'Loading 10 main examination categories...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(
        message: _errorMessage!,
        onRetry: _fetchCategories,
      );
    }

    if (_categories.isEmpty) {
      return const EmptyStateView(title: 'No Mock Categories Found');
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _categories.length,
      itemBuilder: (context, index) {
        final cat = _categories[index] as Map<String, dynamic>;
        final id = (cat['id'] as num?)?.toInt() ?? 0;
        final name = cat['name'] ?? 'Mock Category';
        final description = cat['description'] ?? '';
        final badge = cat['badge_text'] ?? '';

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: AppStyles.cardDecoration(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.primarySubtle,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.assignment, color: AppColors.primary, size: 24),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          name,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                        ),
                        if (badge.isNotEmpty) ...[
                          const SizedBox(height: 3),
                          Text(
                            badge,
                            style: const TextStyle(
                              color: AppColors.primary,
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
              if (description.isNotEmpty) ...[
                const SizedBox(height: 10),
                Text(
                  description,
                  style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const SizedBox(height: 14),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  ElevatedButton(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => MockHierarchyScreen(
                            categoryId: id,
                            categoryName: name,
                          ),
                        ),
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    ),
                    child: const Text('Explore Test Papers', style: TextStyle(fontSize: 12, color: Colors.white)),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}

/// Drilldown screen into category examinations, test series, and papers.
class MockHierarchyScreen extends StatefulWidget {
  final int categoryId;
  final String categoryName;

  const MockHierarchyScreen({
    super.key,
    required this.categoryId,
    required this.categoryName,
  });

  @override
  State<MockHierarchyScreen> createState() => _MockHierarchyScreenState();
}

class _MockHierarchyScreenState extends State<MockHierarchyScreen> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _examinations = [];
  List<dynamic> _mockTests = [];

  @override
  void initState() {
    super.initState();
    _fetchHierarchy();
  }

  Future<void> _fetchHierarchy() async {
    try {
      final res = await ApiClient.get('${ApiEndpoints.mockHierarchy}?category_id=${widget.categoryId}');
      if (res is Map<String, dynamic>) {
        setState(() {
          _examinations = (res['examinations'] as List<dynamic>?) ?? [];
          _mockTests = (res['mock_tests'] as List<dynamic>?) ?? [];
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.categoryName)),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return const LoadingView(message: 'Loading test series & examinations...');
    if (_error != null) return ErrorStateView(message: _error!, onRetry: _fetchHierarchy);

    if (_examinations.isEmpty && _mockTests.isEmpty) {
      return const EmptyStateView(
        title: 'No Mock Papers Available',
        description: 'Mock test papers for this category are being prepared by the faculty.',
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_examinations.isNotEmpty) ...[
          const Text(
            'Target Examinations in Category',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
          ),
          const SizedBox(height: 10),
          ..._examinations.map((e) => _buildExamItem(e)),
          const SizedBox(height: 20),
        ],
        if (_mockTests.isNotEmpty) ...[
          const Text(
            'Available Mock Test Papers (Mock Test 1..N)',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
          ),
          const SizedBox(height: 10),
          ..._mockTests.map((m) => _buildMockTestItem(m)),
        ],
      ],
    );
  }

  Widget _buildExamItem(dynamic exam) {
    final title = exam['name'] ?? 'Exam';
    final code = exam['code'] ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: AppStyles.cardDecoration(context),
      child: Row(
        children: [
          const Icon(Icons.school, color: AppColors.primary, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              code.isNotEmpty ? '$title ($code)' : title,
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMockTestItem(dynamic test) {
    final title = test['title'] ?? 'Mock Test';
    final quizId = (test['quiz_id'] ?? test['id'] as num?)?.toInt() ?? 0;
    final totalQ = test['total_questions'] ?? 0;
    final duration = test['duration_minutes'] ?? 0;
    final marks = test['total_marks'] ?? 0;
    final isPaid = test['access_type'] == 'paid';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: AppStyles.cardDecoration(context),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: isPaid ? AppColors.warningAmberBg : AppColors.primarySubtle,
              shape: BoxShape.circle,
            ),
            child: Icon(
              isPaid ? Icons.lock_outline : Icons.assignment,
              size: 20,
              color: isPaid ? AppColors.warningAmber : AppColors.primary,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                const SizedBox(height: 3),
                Text(
                  '$totalQ Questions • $duration Mins • $marks Marks',
                  style: const TextStyle(color: AppColors.textSecondaryLight, fontSize: 12),
                ),
              ],
            ),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => QuizInstructionsScreen(quizId: quizId),
                ),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: isPaid ? AppColors.warningAmber : AppColors.primary,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            ),
            child: Text(
              isPaid ? 'Unlock' : 'Attempt',
              style: const TextStyle(fontSize: 12, color: Colors.white),
            ),
          ),
        ],
      ),
    );
  }
}
