import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../../core/widgets/state_views.dart';
import '../../quiz_cbt/screens/quiz_instructions_screen.dart';

class PracticeSetsScreen extends StatefulWidget {
  final int initialTab;
  const PracticeSetsScreen({super.key, this.initialTab = 0});

  @override
  State<PracticeSetsScreen> createState() => _PracticeSetsScreenState();
}

class _PracticeSetsScreenState extends State<PracticeSetsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  bool _isLoadingBooks = true;
  List<dynamic> _books = [];
  String? _booksError;

  bool _isLoadingSubjects = true;
  List<dynamic> _subjects = [];
  String? _subjectsError;

  bool _isLoadingExams = true;
  List<dynamic> _exams = [];
  String? _examsError;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this, initialIndex: widget.initialTab);
    _loadAllCatalogs();
  }

  void _loadAllCatalogs() {
    _fetchBooks();
    _fetchSubjects();
    _fetchExams();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Practice Sets'),
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
          tabs: const [
            Tab(text: 'By Books'),
            Tab(text: 'By Subjects'),
            Tab(text: 'By Exams'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildBooksTab(),
          _buildSubjectsTab(),
          _buildExamsTab(),
        ],
      ),
    );
  }

  // 1. Books Tab
  Widget _buildBooksTab() {
    if (_isLoadingBooks) return const LoadingView(message: 'Loading standard books...');
    if (_booksError != null) return ErrorStateView(message: _booksError!, onRetry: _fetchBooks);
    if (_books.isEmpty) return const EmptyStateView(title: 'No Books Found');

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _books.length,
      itemBuilder: (context, index) {
        final b = _books[index] as Map<String, dynamic>;
        final id = (b['id'] as num?)?.toInt() ?? 0;
        final title = b['title'] ?? 'Textbook';
        final author = b['author'] ?? '';
        final setsCount = b['practice_sets_count'] ?? 0;

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: AppStyles.cardDecoration(context),
          child: Row(
            children: [
              Container(
                width: 50,
                height: 60,
                decoration: BoxDecoration(
                  color: AppColors.primarySubtle,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Icon(Icons.menu_book, color: AppColors.primary, size: 28),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                    if (author.isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text('By $author', style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight)),
                    ],
                    const SizedBox(height: 6),
                    Text('$setsCount Practice Sets', style: const TextStyle(fontSize: 12, color: AppColors.primary, fontWeight: FontWeight.w600)),
                  ],
                ),
              ),
              ElevatedButton(
                onPressed: () => _openBookSets(id, title),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                ),
                child: const Text('Open', style: TextStyle(fontSize: 12, color: Colors.white)),
              ),
            ],
          ),
        );
      },
    );
  }

  // 2. Subjects Tab
  Widget _buildSubjectsTab() {
    if (_isLoadingSubjects) return const LoadingView(message: 'Loading subjects...');
    if (_subjectsError != null) return ErrorStateView(message: _subjectsError!, onRetry: _fetchSubjects);
    if (_subjects.isEmpty) return const EmptyStateView(title: 'No Subjects Found');

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _subjects.length,
      itemBuilder: (context, index) {
        final s = _subjects[index] as Map<String, dynamic>;
        final id = (s['id'] as num?)?.toInt() ?? 0;
        final name = s['name'] ?? 'Subject';
        final setsCount = s['practice_sets_count'] ?? 0;

        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: AppStyles.cardDecoration(context),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.primarySubtle,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.eco, color: AppColors.primary, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 4),
                    Text('$setsCount Practice Sets', style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight)),
                  ],
                ),
              ),
              OutlinedButton(
                onPressed: () => _openSubjectSets(id, name),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                ),
                child: const Text('View Sets', style: TextStyle(fontSize: 12)),
              ),
            ],
          ),
        );
      },
    );
  }

  // 3. Exams Tab
  Widget _buildExamsTab() {
    if (_isLoadingExams) return const LoadingView(message: 'Loading exam practice sets...');
    if (_examsError != null) return ErrorStateView(message: _examsError!, onRetry: _fetchExams);
    if (_exams.isEmpty) return const EmptyStateView(title: 'No Exams Found');

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _exams.length,
      itemBuilder: (context, index) {
        final e = _exams[index] as Map<String, dynamic>;
        final id = (e['id'] as num?)?.toInt() ?? 0;
        final name = e['name'] ?? 'Exam';
        final code = e['code'] ?? '';

        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: AppStyles.cardDecoration(context),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.primarySubtle,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.school, color: AppColors.primary, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    if (code.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(code, style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
                    ],
                  ],
                ),
              ),
              ElevatedButton(
                onPressed: () => _openExamSets(id, name),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                ),
                child: const Text('Practice', style: TextStyle(fontSize: 12, color: Colors.white)),
              ),
            ],
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
      appBar: AppBar(title: Text(widget.title)),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return const LoadingView(message: 'Loading practice sets...');
    if (_error != null) return ErrorStateView(message: _error!, onRetry: _fetch);
    if (_sets.isEmpty) return const EmptyStateView(title: 'No Sets Found in this Category');

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _sets.length,
      itemBuilder: (context, index) {
        final item = _sets[index] as Map<String, dynamic>;
        final quizId = (item['quiz_id'] ?? item['id'] as num?)?.toInt() ?? 0;
        final title = item['title'] ?? 'Practice Set ${index + 1}';
        final totalQ = item['total_questions'] ?? 0;
        final duration = item['duration_minutes'] ?? 0;

        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: AppStyles.cardDecoration(context),
          child: Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: const BoxDecoration(
                  color: AppColors.primarySubtle,
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    '${index + 1}',
                    style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.primary),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 3),
                    Text('$totalQ Questions • $duration Mins', style: const TextStyle(color: AppColors.textSecondaryLight, fontSize: 12)),
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
                  backgroundColor: AppColors.primary,
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                ),
                child: const Text('Start', style: TextStyle(fontSize: 12, color: Colors.white)),
              ),
            ],
          ),
        );
      },
    );
  }
}
