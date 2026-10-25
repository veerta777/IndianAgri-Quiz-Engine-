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

class PracticeSetsScreen extends StatefulWidget {
  final int initialTab;
  const PracticeSetsScreen({super.key, this.initialTab = 0});

  @override
  State<PracticeSetsScreen> createState() => _PracticeSetsScreenState();
}

class _PracticeSetsScreenState extends State<PracticeSetsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  bool _isLoadingSubjects = true;
  List<dynamic> _subjects = [];
  String? _subjectsError;

  bool _isLoadingExams = true;
  List<dynamic> _exams = [];
  String? _examsError;

  bool _isLoadingBooks = true;
  List<dynamic> _books = [];
  String? _booksError;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this, initialIndex: widget.initialTab);
    _loadAllCatalogs();
  }

  void _loadAllCatalogs() {
    _fetchSubjects();
    _fetchExams();
    _fetchBooks();
  }

  Future<void> _fetchSubjects() async {
    try {
      final res = await ApiClient.get(ApiEndpoints.practiceSubjects);
      if (res is List<dynamic>) {
        setState(() {
          _subjects = res;
          _isLoadingSubjects = false;
        });
        return;
      }
    } catch (e) {
      setState(() {
        _subjectsError = e.toString();
        _isLoadingSubjects = false;
      });
    }
  }

  Future<void> _fetchExams() async {
    try {
      final res = await ApiClient.get(ApiEndpoints.examinations);
      if (res is List<dynamic>) {
        setState(() {
          _exams = res;
          _isLoadingExams = false;
        });
        return;
      }
    } catch (e) {
      setState(() {
        _examsError = e.toString();
        _isLoadingExams = false;
      });
    }
  }

  Future<void> _fetchBooks() async {
    try {
      final res = await ApiClient.get(ApiEndpoints.practiceBooks);
      if (res is List<dynamic>) {
        setState(() {
          _books = res;
          _isLoadingBooks = false;
        });
        return;
      }
    } catch (e) {
      setState(() {
        _booksError = e.toString();
        _isLoadingBooks = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Practice Question Drills', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: const [
          Padding(
            padding: EdgeInsets.only(right: 14.0),
            child: LanguageToggle(),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primary,
          unselectedLabelColor: AppColors.textSecondaryLight,
          indicatorColor: AppColors.primary,
          indicatorWeight: 3,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
          tabs: const [
            Tab(icon: Icon(Icons.menu_book, size: 20), text: 'By Subjects'),
            Tab(icon: Icon(Icons.school, size: 20), text: 'By Exams'),
            Tab(icon: Icon(Icons.library_books, size: 20), text: 'By Books'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildSubjectsTab(),
          _buildExamsTab(),
          _buildBooksTab(),
        ],
      ),
    );
  }

  // Helper to choose themed icons for subjects
  IconData _getSubjectIcon(String name) {
    final lower = name.toLowerCase();
    if (lower.contains('agronomy') || lower.contains('crop')) return Icons.grass;
    if (lower.contains('soil')) return Icons.terrain;
    if (lower.contains('genetics') || lower.contains('breeding')) return Icons.biotech;
    if (lower.contains('horticulture')) return Icons.local_florist;
    if (lower.contains('pathology')) return Icons.healing;
    if (lower.contains('entomology') || lower.contains('insect')) return Icons.pest_control;
    if (lower.contains('economic') || lower.contains('market')) return Icons.account_balance;
    if (lower.contains('extension')) return Icons.connect_without_contact;
    if (lower.contains('stat')) return Icons.bar_chart;
    if (lower.contains('animal') || lower.contains('veterinary')) return Icons.pets;
    return Icons.eco;
  }

  // 1. Subjects Tab (Primary Catalog with 110+ Sets)
  Widget _buildSubjectsTab() {
    if (_isLoadingSubjects) return const LoadingView(message: 'Loading 110+ subject practice sets...');
    if (_subjectsError != null) return ErrorStateView(message: _subjectsError!, onRetry: _fetchSubjects);
    if (_subjects.isEmpty) return const EmptyStateView(title: 'No Subjects Found');

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      itemCount: _subjects.length,
      itemBuilder: (context, index) {
        final s = _subjects[index] as Map<String, dynamic>;
        final id = (s['id'] as num?)?.toInt() ?? 0;
        final name = s['name'] ?? 'Subject';
        final description = s['description'] ?? '';
        final setsCount = (s['practice_sets_count'] as num?)?.toInt() ?? 0;
        final icon = _getSubjectIcon(name);

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          decoration: AppStyles.glassCardDecoration(context),
          child: Material(
            color: Colors.transparent,
            borderRadius: AppStyles.borderRadiusL,
            child: InkWell(
              onTap: () => _openSubjectSets(id, name),
              borderRadius: AppStyles.borderRadiusL,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        gradient: AppColors.heroEmeraldGradient,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.primary.withOpacity(0.25),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: Icon(icon, color: Colors.white, size: 26),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            name,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                          ),
                          if (description.isNotEmpty) ...[
                            const SizedBox(height: 3),
                            Text(
                              description,
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                          const SizedBox(height: 6),
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2.5),
                                decoration: BoxDecoration(
                                  color: AppColors.primarySubtle,
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  '$setsCount Practice Sets',
                                  style: const TextStyle(fontSize: 11, color: AppColors.primaryDark, fontWeight: FontWeight.bold),
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Text('• 50 MCQs / Set', style: TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textSecondaryLight),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  // 2. Exams Tab
  Widget _buildExamsTab() {
    if (_isLoadingExams) return const LoadingView(message: 'Loading competitive examinations...');
    if (_examsError != null) return ErrorStateView(message: _examsError!, onRetry: _fetchExams);
    if (_exams.isEmpty) return const EmptyStateView(title: 'No Exams Found');

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      itemCount: _exams.length,
      itemBuilder: (context, index) {
        final e = _exams[index] as Map<String, dynamic>;
        final id = (e['id'] as num?)?.toInt() ?? 0;
        final name = e['name'] ?? 'Exam';
        final code = e['code'] ?? '';
        final desc = e['description'] ?? '';

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          decoration: AppStyles.glassCardDecoration(context),
          child: Material(
            color: Colors.transparent,
            borderRadius: AppStyles.borderRadiusL,
            child: InkWell(
              onTap: () => _openExamSets(id, name),
              borderRadius: AppStyles.borderRadiusL,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      width: 50,
                      height: 50,
                      decoration: BoxDecoration(
                        color: AppColors.warningAmberBg,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.warningAmber.withOpacity(0.3)),
                      ),
                      child: const Icon(Icons.school, color: AppColors.warningAmber, size: 26),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                              ),
                              if (code.isNotEmpty)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFE0F2FE),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: Text(
                                    code,
                                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF0369A1)),
                                  ),
                                ),
                            ],
                          ),
                          if (desc.isNotEmpty) ...[
                            const SizedBox(height: 3),
                            Text(
                              desc,
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                          const SizedBox(height: 6),
                          const Text(
                            'Syllabus & Exam Mock Drills →',
                            style: TextStyle(fontSize: 11, color: AppColors.primary, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
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

  // 3. Books Tab
  Widget _buildBooksTab() {
    if (_isLoadingBooks) return const LoadingView(message: 'Loading standard books...');
    if (_booksError != null) return ErrorStateView(message: _booksError!, onRetry: _fetchBooks);
    
    // Curated standard reference books if server list is empty
    final displayBooks = _books.isNotEmpty
        ? _books
        : [
            {
              'id': 1,
              'title': 'A Competitive Book of Agriculture',
              'author': 'Nem Raj Sunda',
              'practice_sets_count': 64,
              'description': 'Standard reference covering General Agriculture, Agronomy, Soil Science, Horticulture & Entomology.',
            },
            {
              'id': 2,
              'title': 'Objective Agriculture',
              'author': 'S.R. Kantwa',
              'practice_sets_count': 35,
              'description': 'High-yield MCQs for ICAR JRF/SRF, NET, CUET and State PSC examinations.',
            },
          ];

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      itemCount: displayBooks.length,
      itemBuilder: (context, index) {
        final b = displayBooks[index] as Map<String, dynamic>;
        final id = (b['id'] as num?)?.toInt() ?? 0;
        final title = b['title'] ?? 'Textbook';
        final author = b['author'] ?? '';
        final setsCount = (b['practice_sets_count'] as num?)?.toInt() ?? 0;
        final desc = b['description'] ?? '';

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          decoration: AppStyles.glassCardDecoration(context),
          child: Material(
            color: Colors.transparent,
            borderRadius: AppStyles.borderRadiusL,
            child: InkWell(
              onTap: () => _openBookSets(id, title),
              borderRadius: AppStyles.borderRadiusL,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      width: 50,
                      height: 56,
                      decoration: BoxDecoration(
                        color: AppColors.primarySubtle,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: AppColors.primary.withOpacity(0.3)),
                      ),
                      child: const Icon(Icons.menu_book, color: AppColors.primary, size: 28),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          if (author.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Text('By $author', style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight)),
                          ],
                          if (desc.isNotEmpty) ...[
                            const SizedBox(height: 3),
                            Text(desc, style: const TextStyle(fontSize: 11, color: AppColors.textMutedLight), maxLines: 2, overflow: TextOverflow.ellipsis),
                          ],
                          const SizedBox(height: 6),
                          Text('$setsCount Chapter Practice Sets', style: const TextStyle(fontSize: 12, color: AppColors.primary, fontWeight: FontWeight.w600)),
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
      },
    );
  }

  void _openBookSets(int bookId, String title) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => GenericSetsListScreen(
          title: title,
          fetchUrl: ApiEndpoints.bookSets(bookId),
          dataKey: 'sets',
        ),
      ),
    );
  }

  void _openSubjectSets(int subjectId, String title) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => GenericSetsListScreen(
          title: title,
          fetchUrl: ApiEndpoints.subjectSets(subjectId),
          dataKey: 'sets',
        ),
      ),
    );
  }

  void _openExamSets(int examId, String title) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => GenericSetsListScreen(
          title: title,
          fetchUrl: ApiEndpoints.examSets(examId),
          dataKey: 'hierarchy',
        ),
      ),
    );
  }
}

/// Generic screen for displaying practice sets inside a book, subject, or exam.
class GenericSetsListScreen extends StatefulWidget {
  final String title;
  final String fetchUrl;
  final String dataKey;

  const GenericSetsListScreen({
    super.key,
    required this.title,
    required this.fetchUrl,
    required this.dataKey,
  });

  @override
  State<GenericSetsListScreen> createState() => _GenericSetsListScreenState();
}

class _GenericSetsListScreenState extends State<GenericSetsListScreen> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _sets = [];

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    try {
      final res = await ApiClient.get(widget.fetchUrl);
      if (res is Map<String, dynamic> && res.containsKey(widget.dataKey)) {
        final raw = res[widget.dataKey];
        if (raw is List<dynamic>) {
          setState(() {
            _sets = raw;
            _isLoading = false;
          });
          return;
        }
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
        title: Text(widget.title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return const LoadingView(message: 'Loading practice questions & sets...');
    if (_error != null) return ErrorStateView(message: _error!, onRetry: _fetch);
    if (_sets.isEmpty) return const EmptyStateView(title: 'No Sets Found in this Category');

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      itemCount: _sets.length,
      itemBuilder: (context, index) {
        final item = _sets[index] as Map<String, dynamic>;
        final quizId = (item['quiz_id'] ?? item['id'] as num?)?.toInt() ?? 0;
        final title = item['title'] ?? 'Practice Set ${index + 1}';
        final totalQ = item['total_questions'] ?? 50;
        final duration = item['duration_minutes'] ?? 0;
        final isLocked = item['is_locked'] == true;

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
                        gradient: isLocked
                            ? const LinearGradient(colors: [Color(0xFFF59E0B), Color(0xFFD97706)])
                            : AppColors.heroEmeraldGradient,
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
                          Text(
                            title,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 6),
                          Wrap(
                            spacing: 6,
                            runSpacing: 4,
                            children: [
                              _buildMetaChip(Icons.help_outline, '$totalQ MCQs / Questions', AppColors.primary),
                              _buildMetaChip(Icons.timer_outlined, duration > 0 ? '$duration Mins' : 'Practice Drill', AppColors.textSecondaryLight),
                              if (isLocked)
                                _buildMetaChip(Icons.lock, 'Subscribers Only', AppColors.accent),
                            ],
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
                    // Sample Preview Drill: Guarantees candidate can ALWAYS see and practice questions!
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
                        icon: const Icon(Icons.remove_red_eye_outlined, size: 15),
                        label: const Text('Sample Drill (10 Qs)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          side: const BorderSide(color: AppColors.primary),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    // Full Examination / Unlock CTA
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
                          isLocked ? 'Unlock All (50 Qs)' : 'Full Drill (50 Qs)',
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

  Widget _buildMetaChip(IconData icon, String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withOpacity(0.25)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 4),
          Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: color)),
        ],
      ),
    );
  }
}
