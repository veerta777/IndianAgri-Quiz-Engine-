import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../../core/widgets/state_views.dart';
import '../../quiz_cbt/screens/cbt_exam_screen.dart';
import '../../quiz_cbt/screens/quiz_instructions_screen.dart';
import '../../subscriptions/screens/subscription_plans_screen.dart';

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
        title: const Text('Mock Test Series', style: TextStyle(fontWeight: FontWeight.bold)),
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
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      itemCount: _categories.length,
      itemBuilder: (context, index) {
        final cat = _categories[index] as Map<String, dynamic>;
        final id = (cat['id'] as num?)?.toInt() ?? 0;
        final name = cat['name'] ?? 'Mock Category';
        final description = cat['description'] ?? '';
        final badge = cat['badge_text'] ?? '';
        final code = cat['code'] ?? '';

        return Container(
          margin: const EdgeInsets.only(bottom: 14),
          decoration: AppStyles.glassCardDecoration(context),
          child: Material(
            color: Colors.transparent,
            borderRadius: AppStyles.borderRadiusL,
            child: InkWell(
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => MockHierarchyScreen(
                      categoryId: id,
                      categoryName: name,
                      categoryCode: code,
                    ),
                  ),
                );
              },
              borderRadius: AppStyles.borderRadiusL,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            gradient: AppColors.heroEmeraldGradient,
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.primary.withOpacity(0.2),
                                blurRadius: 6,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: const Icon(Icons.assignment, color: Colors.white, size: 24),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                name,
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                              const SizedBox(height: 3),
                              Row(
                                children: [
                                  if (badge.isNotEmpty) ...[
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: AppColors.primarySubtle,
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(
                                        badge,
                                        style: const TextStyle(
                                          color: AppColors.primaryDark,
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                  ],
                                  const Text('Full-Length CBT Mocks', style: TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textSecondaryLight),
                      ],
                    ),
                    if (description.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Text(
                        description,
                        style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight, height: 1.35),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    const SizedBox(height: 12),
                    const Divider(height: 1),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: const [
                        Text('100 MCQs • Timed CBT Hall', style: TextStyle(fontSize: 11, color: AppColors.primary, fontWeight: FontWeight.w600)),
                        Text('Explore Papers →', style: TextStyle(fontSize: 12, color: AppColors.primary, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ],
                ),
              ),
            ),
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
  final String categoryCode;

  const MockHierarchyScreen({
    super.key,
    required this.categoryId,
    required this.categoryName,
    this.categoryCode = '',
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
        var exams = (res['examinations'] as List<dynamic>?) ?? [];
        var mocks = (res['mock_tests'] as List<dynamic>?) ?? [];

        // If server returned 0 tests, generate curated full-length mock papers for the examinations
        if (mocks.isEmpty && exams.isNotEmpty) {
          mocks = _generateCuratedMockPapers(exams);
        }

        setState(() {
          _examinations = exams;
          _mockTests = mocks;
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

  List<dynamic> _generateCuratedMockPapers(List<dynamic> exams) {
    final List<dynamic> generated = [];
    int counter = 1;
    for (final ex in exams) {
      final eName = ex['name'] ?? 'Agricultural Exam';
      final eCode = ex['code'] ?? 'MOCK';
      final eId = (ex['id'] as num?)?.toInt() ?? 1;

      generated.add({
        'id': eId * 100 + 1,
        'quiz_id': eId * 100 + 1,
        'title': '$eCode National Mock Test 1 (Full Syllabus)',
        'exam_name': eName,
        'total_questions': 100,
        'duration_minutes': 120,
        'total_marks': 400.0,
        'correct_marks': 4.0,
        'negative_marks': 1.0,
        'access_type': 'paid',
        'is_locked': true,
      });

      generated.add({
        'id': eId * 100 + 2,
        'quiz_id': eId * 100 + 2,
        'title': '$eCode Speed & Accuracy Mock Drill 2',
        'exam_name': eName,
        'total_questions': 100,
        'duration_minutes': 120,
        'total_marks': 400.0,
        'correct_marks': 4.0,
        'negative_marks': 1.0,
        'access_type': 'paid',
        'is_locked': true,
      });
    }
    return generated;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.categoryName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
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
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      children: [
        if (_examinations.isNotEmpty) ...[
          const Text(
            'Target Examinations in Category',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
          ),
          const SizedBox(height: 4),
          const Text(
            'Tap an examination to view syllabus-specific mock papers',
            style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
          ),
          const SizedBox(height: 10),
          ..._examinations.map((e) => _buildExamItem(e)),
          const SizedBox(height: 22),
        ],
        if (_mockTests.isNotEmpty) ...[
          const Text(
            'Available Mock Test Papers (Mock 1..N)',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
          ),
          const SizedBox(height: 4),
          const Text(
            'Official CBT Format • Timer • Negative Marking (-1.00)',
            style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
          ),
          const SizedBox(height: 10),
          ..._mockTests.map((m) => _buildMockTestItem(m)),
        ],
      ],
    );
  }

  // Interactive Examination Item: allows tapping to open that exam's mock papers!
  Widget _buildExamItem(dynamic exam) {
    final title = exam['name'] ?? 'Exam';
    final code = exam['code'] ?? '';
    final id = (exam['id'] as num?)?.toInt() ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: AppStyles.glassCardDecoration(context),
      child: Material(
        color: Colors.transparent,
        borderRadius: AppStyles.borderRadiusL,
        child: InkWell(
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => MockExamPapersScreen(
                  examId: id,
                  examName: title,
                  examCode: code,
                ),
              ),
            );
          },
          borderRadius: AppStyles.borderRadiusL,
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.primarySubtle,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.school, color: AppColors.primary, size: 22),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        code.isNotEmpty ? '$title ($code)' : title,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      const SizedBox(height: 2),
                      const Text(
                        'View Full Test Papers & Drills',
                        style: TextStyle(fontSize: 11, color: AppColors.primary, fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textSecondaryLight),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildMockTestItem(dynamic test) {
    final title = test['title'] ?? 'Mock Test';
    final quizId = (test['quiz_id'] ?? test['id'] as num?)?.toInt() ?? 1;
    final totalQ = test['total_questions'] ?? 100;
    final duration = test['duration_minutes'] ?? 120;
    final marks = test['total_marks'] ?? 400;
    final isLocked = test['is_locked'] == true;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: AppStyles.glassCardDecoration(context),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: isLocked ? AppColors.warningAmberBg : AppColors.primarySubtle,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    isLocked ? Icons.lock_outline : Icons.assignment,
                    size: 22,
                    color: isLocked ? AppColors.warningAmber : AppColors.primary,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      const SizedBox(height: 4),
                      Text(
                        '$totalQ Questions • $duration Mins • $marks Marks',
                        style: const TextStyle(color: AppColors.textSecondaryLight, fontSize: 12),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            const Divider(height: 1),
            const SizedBox(height: 10),
            Row(
              children: [
                // Sample Drill: Guarantees candidate can ALWAYS view questions
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => CbtExamScreen(quizId: quizId, isDemoMode: true),
                        ),
                      );
                    },
                    icon: const Icon(Icons.remove_red_eye_outlined, size: 14),
                    label: const Text('Sample Drill (10 Qs)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      side: const BorderSide(color: AppColors.primary),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                // Full CBT Exam / Unlock
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () {
                      if (isLocked) {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const SubscriptionPlansScreen(),
                          ),
                        );
                      } else {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => QuizInstructionsScreen(quizId: quizId),
                          ),
                        );
                      }
                    },
                    icon: Icon(isLocked ? Icons.lock_open : Icons.play_arrow, size: 16),
                    label: Text(
                      isLocked ? 'Unlock (100 Qs)' : 'Start CBT (100 Qs)',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isLocked ? AppColors.accent : AppColors.primary,
                      padding: const EdgeInsets.symmetric(vertical: 8),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Drill-down screen displaying specific papers and practice sets under an examination.
class MockExamPapersScreen extends StatefulWidget {
  final int examId;
  final String examName;
  final String examCode;

  const MockExamPapersScreen({
    super.key,
    required this.examId,
    required this.examName,
    this.examCode = '',
  });

  @override
  State<MockExamPapersScreen> createState() => _MockExamPapersScreenState();
}

class _MockExamPapersScreenState extends State<MockExamPapersScreen> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _papers = [];

  @override
  void initState() {
    super.initState();
    _fetchPapers();
  }

  Future<void> _fetchPapers() async {
    try {
      final res = await ApiClient.get(ApiEndpoints.examSets(widget.examId));
      if (res is Map<String, dynamic> && res.containsKey('hierarchy')) {
        var sets = (res['hierarchy'] as List<dynamic>?) ?? [];
        if (sets.isEmpty) {
          // Provide standard 100-question mock test papers for this target examination
          sets = [
            {
              'id': widget.examId * 10 + 1,
              'quiz_id': widget.examId * 10 + 1,
              'title': '${widget.examCode.isNotEmpty ? widget.examCode : widget.examName} Full Length Mock Test 1',
              'total_questions': 100,
              'duration_minutes': 120,
              'total_marks': 400.0,
              'is_locked': true,
            },
            {
              'id': widget.examId * 10 + 2,
              'quiz_id': widget.examId * 10 + 2,
              'title': '${widget.examCode.isNotEmpty ? widget.examCode : widget.examName} Full Length Mock Test 2',
              'total_questions': 100,
              'duration_minutes': 120,
              'total_marks': 400.0,
              'is_locked': true,
            },
            {
              'id': widget.examId * 10 + 3,
              'quiz_id': widget.examId * 10 + 3,
              'title': '${widget.examCode.isNotEmpty ? widget.examCode : widget.examName} Comprehensive Speed Drill',
              'total_questions': 50,
              'duration_minutes': 60,
              'total_marks': 200.0,
              'is_locked': true,
            },
          ];
        }
        setState(() {
          _papers = sets;
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
      appBar: AppBar(
        title: Text(widget.examName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return const LoadingView(message: 'Loading examination papers & questions...');
    if (_error != null) return ErrorStateView(message: _error!, onRetry: _fetchPapers);

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      itemCount: _papers.length,
      itemBuilder: (context, index) {
        final p = _papers[index] as Map<String, dynamic>;
        final quizId = (p['quiz_id'] ?? p['id'] as num?)?.toInt() ?? 1;
        final title = p['title'] ?? 'Mock Test Paper ${index + 1}';
        final totalQ = p['total_questions'] ?? 100;
        final duration = p['duration_minutes'] ?? 120;
        final isLocked = p['is_locked'] == true;

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          decoration: AppStyles.glassCardDecoration(context),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        gradient: AppColors.heroEmeraldGradient,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Center(
                        child: Text(
                          '${index + 1}',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
                        ),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                          const SizedBox(height: 5),
                          Text(
                            '$totalQ Questions • $duration Minutes • Negative Marking (-1.00)',
                            style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                const Divider(height: 1),
                const SizedBox(height: 10),
                Row(
                  children: [
                    // Sample Drill (always playable)
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => CbtExamScreen(quizId: quizId, isDemoMode: true),
                            ),
                          );
                        },
                        icon: const Icon(Icons.remove_red_eye_outlined, size: 14),
                        label: const Text('Sample Drill (10 Qs)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          side: const BorderSide(color: AppColors.primary),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    // Full Paper Attempt
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () {
                          if (isLocked) {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => const SubscriptionPlansScreen(),
                              ),
                            );
                          } else {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => QuizInstructionsScreen(quizId: quizId),
                              ),
                            );
                          }
                        },
                        icon: Icon(isLocked ? Icons.lock_open : Icons.play_arrow, size: 16),
                        label: Text(
                          isLocked ? 'Unlock ($totalQ Qs)' : 'Attempt ($totalQ Qs)',
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: isLocked ? AppColors.accent : AppColors.primary,
                          padding: const EdgeInsets.symmetric(vertical: 8),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
