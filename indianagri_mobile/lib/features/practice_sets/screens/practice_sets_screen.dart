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

class _PracticeSetsScreenState extends State<PracticeSetsScreen> with SingleTickerProviderStateMixin, AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

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
  String _bookSearchQuery = '';
  final Set<int> _favoriteBookIds = {};
  final TextEditingController _bookSearchController = TextEditingController();

  @override
  void dispose() {
    _bookSearchController.dispose();
    super.dispose();
  }

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
    super.build(context);
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

    final isDark = Theme.of(context).brightness == Brightness.dark;

    // Curated standard reference books matching live database IDs & counts
    final allBooks = _books.isNotEmpty
        ? _books
        : [
            {
              'id': 1,
              'title': 'A Competitive Book of Agriculture',
              'author': 'Nem Raj Sundha',
              'practice_sets_count': 69,
              'subjects_text': '10+ Subjects',
              'questions_text': '5,000+ Questions',
              'image_asset': 'assets/images/book_general_agri.jpg',
              'description': 'Comprehensive handbook covering General Agriculture, Agronomy, Soil Science, Genetics and allied subjects for ICAR, JRF, and State PSCs.',
            },
            {
              'id': 4,
              'title': 'Instant Horticulture',
              'author': 'S N Gupta',
              'practice_sets_count': 1,
              'subjects_text': 'Horticulture',
              'questions_text': '1,000+ Questions',
              'image_asset': 'assets/images/book_horticulture.jpg',
              'description': 'Chapter-wise practice sets on fruits, vegetables, floriculture, landscaping and allied areas for competitive examinations.',
            },
            {
              'id': 5,
              'title': 'Objective Agriculture',
              'author': 'S R Kantwa',
              'practice_sets_count': 67,
              'subjects_text': 'Mixed Subjects',
              'questions_text': '3,000+ Questions',
              'image_asset': 'assets/images/pathway_subjects.jpg',
              'description': 'Topic-wise objective questions covering major agricultural subjects for ICAR, ASRB NET, CUET PG and IBPS AFO.',
            },
            {
              'id': 2,
              'title': 'Fundamentals of Agriculture (Vol 1 & 2)',
              'author': 'Dr. Arun Katyayan',
              'practice_sets_count': 12,
              'subjects_text': 'Agronomy & Allied',
              'questions_text': '2,500+ Questions',
              'image_asset': 'assets/images/pathway_books.jpg',
              'description': 'Conceptual core textbook covering Agronomy, Soil Science, Agricultural Extension, and Crop Physiology.',
            },
            {
              'id': 3,
              'title': 'Soil Science at a Glance',
              'author': 'Dr. D.K. Das',
              'practice_sets_count': 8,
              'subjects_text': 'Soil Science',
              'questions_text': '1,200+ Questions',
              'image_asset': 'assets/images/pathway_exams.jpg',
              'description': 'Specialized review handbook for Soil Physics, Soil Chemistry, Fertility, and Nutrient Management.',
            },
          ];

    // Filter books based on search query
    final displayBooks = allBooks.where((b) {
      if (_bookSearchQuery.trim().isEmpty) return true;
      final q = _bookSearchQuery.toLowerCase().trim();
      final title = (b['title'] ?? '').toString().toLowerCase();
      final author = (b['author'] ?? '').toString().toLowerCase();
      return title.contains(q) || author.contains(q);
    }).toList();

    return ListView(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      children: [
        // 1. Top Hero Card matching screenshot
        Container(
          margin: const EdgeInsets.only(bottom: 16),
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: isDark
                  ? [const Color(0xFF132A1C), const Color(0xFF0F1E16)]
                  : [const Color(0xFFEAF8EE), const Color(0xFFF3FAF4)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: isDark ? const Color(0xFF234C31) : const Color(0xFFBCE3C8),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Breadcrumb
              Row(
                children: [
                  const Icon(Icons.home_outlined, size: 14, color: AppColors.primary),
                  const SizedBox(width: 4),
                  Text(
                    'Practice Sets > Practice by Book',
                    style: TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                      color: isDark ? const Color(0xFF68D391) : const Color(0xFF1B6B38),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              // Title & Banner Image
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        RichText(
                          text: TextSpan(
                            style: TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w800,
                              color: isDark ? Colors.white : const Color(0xFF0F2F19),
                              fontFamily: 'Inter',
                              height: 1.25,
                            ),
                            children: const [
                              TextSpan(text: 'Practice by Book '),
                              TextSpan(
                                text: '— Standard Reference Literature',
                                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14.5),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Solve chapter-wise practice tests based on authoritative agricultural textbooks and competitive handbooks.',
                          style: TextStyle(
                            fontSize: 11.5,
                            color: isDark ? Colors.grey[400] : const Color(0xFF4A5568),
                            height: 1.35,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.asset(
                      'assets/images/pathway_books.jpg',
                      width: 68,
                      height: 68,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        width: 68,
                        height: 68,
                        color: AppColors.primarySubtle,
                        child: const Icon(Icons.menu_book, color: AppColors.primary, size: 32),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              // 4 Feature Pills in a horizontal scrollable row
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                physics: const BouncingScrollPhysics(),
                child: Row(
                  children: [
                    _buildHeroFeaturePill(Icons.menu_book_outlined, 'Authoritative Books', isDark),
                    const SizedBox(width: 8),
                    _buildHeroFeaturePill(Icons.description_outlined, 'Chapter-wise Practice Sets', isDark),
                    const SizedBox(width: 8),
                    _buildHeroFeaturePill(Icons.layers_outlined, 'Exam-oriented MCQs', isDark),
                    const SizedBox(width: 8),
                    _buildHeroFeaturePill(Icons.track_changes_outlined, 'Build Strong Concepts', isDark),
                  ],
                ),
              ),
            ],
          ),
        ),

        // 2. Filter & Search Bar
        Container(
          margin: const EdgeInsets.only(bottom: 18),
          child: Row(
            children: [
              Expanded(
                child: Container(
                  height: 46,
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF1E293B) : Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(
                      color: isDark ? const Color(0xFF334155) : const Color(0xFFCBD5E1),
                    ),
                  ),
                  child: TextField(
                    controller: _bookSearchController,
                    onChanged: (val) {
                      setState(() {
                        _bookSearchQuery = val;
                      });
                    },
                    decoration: InputDecoration(
                      hintText: 'Search by book title or author name...',
                      hintStyle: TextStyle(
                        fontSize: 12,
                        color: isDark ? Colors.grey[500] : const Color(0xFF94A3B8),
                      ),
                      prefixIcon: const Icon(Icons.search, size: 18, color: AppColors.primary),
                      suffixIcon: _bookSearchQuery.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 16),
                              onPressed: () {
                                _bookSearchController.clear();
                                setState(() {
                                  _bookSearchQuery = '';
                                });
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Container(
                height: 46,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F5A29),
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF0F5A29).withOpacity(0.3),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ],
                ),
                child: const Center(
                  child: Text(
                    'Search',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),

        // 3. Book Cards List
        if (displayBooks.isEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 40),
              child: Column(
                children: [
                  Icon(Icons.search_off, size: 48, color: Colors.grey[400]),
                  const SizedBox(height: 12),
                  Text(
                    'No standard books match "$_bookSearchQuery"',
                    style: TextStyle(fontSize: 14, color: isDark ? Colors.grey[400] : Colors.grey[600]),
                  ),
                ],
              ),
            ),
          )
        else
          ...displayBooks.map((b) => _buildBookCard(b as Map<String, dynamic>, isDark)),
      ],
    );
  }

  Widget _buildHeroFeaturePill(IconData icon, String label, bool isDark) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1C3A27) : const Color(0xFFD8F3DF),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: isDark ? const Color(0xFF285E3B) : const Color(0xFFB4E6C1),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 13, color: const Color(0xFF117E3A)),
          const SizedBox(width: 5),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: isDark ? Colors.white70 : const Color(0xFF14532D),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBookCard(Map<String, dynamic> b, bool isDark) {
    final id = (b['id'] as num?)?.toInt() ?? 0;
    final title = (b['title'] ?? 'Textbook').toString();
    final author = (b['author'] ?? '').toString();
    final setsCount = (b['practice_sets_count'] as num?)?.toInt() ?? 0;
    final desc = (b['description'] ?? '').toString();

    final subjectsText = (b['subjects_text'] ?? (title.contains('Horticulture') ? 'Horticulture' : (title.contains('Objective') ? 'Mixed Subjects' : '10+ Subjects'))).toString();
    final questionsText = (b['questions_text'] ?? (title.contains('Horticulture') ? '1,000+ Questions' : (title.contains('Objective') ? '3,000+ Questions' : '5,000+ Questions'))).toString();

    String imageAsset = 'assets/images/pathway_books.jpg';
    if (b['image_asset'] != null) {
      imageAsset = b['image_asset'];
    } else if (title.toLowerCase().contains('nem raj') || title.toLowerCase().contains('competitive book')) {
      imageAsset = 'assets/images/book_general_agri.jpg';
    } else if (title.toLowerCase().contains('horticulture')) {
      imageAsset = 'assets/images/book_horticulture.jpg';
    } else if (title.toLowerCase().contains('objective')) {
      imageAsset = 'assets/images/pathway_subjects.jpg';
    }

    final isFav = _favoriteBookIds.contains(id);

    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(
          color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.3 : 0.06),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. Cover with curved mask & badges
          Stack(
            children: [
              ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
                child: SizedBox(
                  height: 165,
                  width: double.infinity,
                  child: Image.asset(
                    imageAsset,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      color: const Color(0xFFE8F5E9),
                      child: const Center(
                        child: Icon(Icons.book, size: 54, color: AppColors.primary),
                      ),
                    ),
                  ),
                ),
              ),
              Positioned.fill(
                child: ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
                  child: Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Colors.black.withOpacity(0.12),
                          Colors.transparent,
                          Colors.black.withOpacity(0.35),
                        ],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                      ),
                    ),
                  ),
                ),
              ),
              // Top-left: PREMIUM ACCESS Badge
              Positioned(
                top: 12,
                left: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFBF1D3),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFF6D682), width: 1.2),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.12),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: const [
                      Text('👑', style: TextStyle(fontSize: 10)),
                      SizedBox(width: 4),
                      Text(
                        'PREMIUM ACCESS',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.5,
                          color: Color(0xFF8D5300),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // Top-right: Favorite Heart Button
              Positioned(
                top: 10,
                right: 12,
                child: InkWell(
                  onTap: () {
                    setState(() {
                      if (isFav) {
                        _favoriteBookIds.remove(id);
                      } else {
                        _favoriteBookIds.add(id);
                      }
                    });
                  },
                  borderRadius: BorderRadius.circular(20),
                  child: Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.92),
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.15),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Icon(
                      isFav ? Icons.favorite : Icons.favorite_border,
                      size: 19,
                      color: isFav ? const Color(0xFFE53E3E) : const Color(0xFF2D3748),
                    ),
                  ),
                ),
              ),
            ],
          ),

          // 2. Card Content Body
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Title
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w800,
                    color: isDark ? Colors.white : const Color(0xFF1A202C),
                    letterSpacing: -0.2,
                  ),
                ),
                if (author.isNotEmpty) ...[
                  const SizedBox(height: 5),
                  Row(
                    children: [
                      Icon(
                        Icons.person_outline,
                        size: 15,
                        color: isDark ? Colors.grey[400] : const Color(0xFF718096),
                      ),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          'By Reference: $author',
                          style: TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w500,
                            color: isDark ? Colors.grey[400] : const Color(0xFF4A5568),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ],
                if (desc.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    desc,
                    style: TextStyle(
                      fontSize: 12,
                      height: 1.4,
                      color: isDark ? Colors.grey[300] : const Color(0xFF718096),
                    ),
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
                const SizedBox(height: 14),

                // 3. Meta stats row
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAF9),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: isDark ? const Color(0xFF334155) : const Color(0xFFEEF2F6),
                    ),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Row(
                          children: [
                            const Icon(Icons.description_outlined, size: 14, color: AppColors.primary),
                            const SizedBox(width: 4),
                            Expanded(
                              child: Text(
                                '$setsCount Sets',
                                style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.primary,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Container(width: 1, height: 16, color: Colors.grey[300]),
                      Expanded(
                        child: Padding(
                          padding: const EdgeInsets.only(left: 6),
                          child: Row(
                            children: [
                              const Icon(Icons.layers_outlined, size: 14, color: AppColors.primary),
                              const SizedBox(width: 4),
                              Expanded(
                                child: Text(
                                  subjectsText,
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: isDark ? Colors.grey[300] : const Color(0xFF2D3748),
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      Container(width: 1, height: 16, color: Colors.grey[300]),
                      Expanded(
                        child: Padding(
                          padding: const EdgeInsets.only(left: 6),
                          child: Row(
                            children: [
                              const Icon(Icons.bar_chart_outlined, size: 14, color: AppColors.primary),
                              const SizedBox(width: 4),
                              Expanded(
                                child: Text(
                                  questionsText,
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: isDark ? Colors.grey[300] : const Color(0xFF2D3748),
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),

                // 4. Action button: Explore Practice Sets ->
                InkWell(
                  onTap: () => _openBookSets(id, title),
                  borderRadius: BorderRadius.circular(30),
                  child: Container(
                    height: 48,
                    padding: const EdgeInsets.symmetric(horizontal: 18),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF0F5A29), Color(0xFF147A38)],
                        begin: Alignment.centerLeft,
                        end: Alignment.centerRight,
                      ),
                      borderRadius: BorderRadius.circular(30),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF0F5A29).withOpacity(0.28),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Text(
                          'Explore Practice Sets',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 0.2,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          width: 26,
                          height: 26,
                          decoration: const BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.arrow_forward,
                            size: 15,
                            color: Color(0xFF0F5A29),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
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
      final res = await ApiClient.get(widget.fetchUrl, forceRefresh: true);
      List<dynamic> extractedSets = [];

      if (res is List<dynamic>) {
        extractedSets = res;
      } else if (res is Map<String, dynamic>) {
        if (res[widget.dataKey] is List<dynamic>) {
          extractedSets = res[widget.dataKey];
        } else if (res['sets'] is List<dynamic>) {
          extractedSets = res['sets'];
        } else if (res['quizzes'] is List<dynamic>) {
          extractedSets = res['quizzes'];
        } else if (res['practice_sets'] is List<dynamic>) {
          extractedSets = res['practice_sets'];
        } else if (res['hierarchy'] is List<dynamic>) {
          extractedSets = res['hierarchy'];
        }
      }

      setState(() {
        _sets = extractedSets;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  void _openQuiz(BuildContext context, int quizId, bool isLocked, String title, int setNumber) {
    // Open instructions screen so candidate can review syllabus, rules, and launch CBT or sample preview
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => QuizInstructionsScreen(quizId: quizId),
      ),
    );
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
        final setNumber = (item['practice_set_number'] as num?)?.toInt() ?? (index + 1);
        final rawTitle = item['title']?.toString() ?? 'Practice Set $setNumber';
        final totalQ = item['total_questions'] ?? 50;
        final duration = item['duration_minutes'] ?? 0;
        final isLocked = item['is_locked'] == true;
        final subjectName = item['subject_name']?.toString() ?? '';

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          decoration: AppStyles.glassCardDecoration(context),
          child: Material(
            color: Colors.transparent,
            borderRadius: AppStyles.borderRadiusL,
            child: InkWell(
              onTap: () => _openQuiz(context, quizId, isLocked, rawTitle, setNumber),
              borderRadius: AppStyles.borderRadiusL,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Distinct Practice Set Number Badge
                        Container(
                          width: 48,
                          height: 52,
                          decoration: BoxDecoration(
                            gradient: isLocked
                                ? const LinearGradient(colors: [Color(0xFFF59E0B), Color(0xFFD97706)])
                                : AppColors.heroEmeraldGradient,
                            borderRadius: BorderRadius.circular(10),
                            boxShadow: [
                              BoxShadow(
                                color: (isLocked ? const Color(0xFFD97706) : AppColors.primary).withOpacity(0.25),
                                blurRadius: 6,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Text(
                                'SET',
                                style: TextStyle(fontWeight: FontWeight.w800, fontSize: 9.5, color: Colors.white70, letterSpacing: 0.8),
                              ),
                              Text(
                                '$setNumber',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Colors.white),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Explicit Practice Set Number Heading
                              Text(
                                'Practice Set $setNumber: $rawTitle',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, height: 1.25),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              if (subjectName.isNotEmpty) ...[
                                const SizedBox(height: 4),
                                Text(
                                  subjectName,
                                  style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight, fontWeight: FontWeight.w500),
                                ),
                              ],
                              const SizedBox(height: 6),
                              Wrap(
                                spacing: 6,
                                runSpacing: 4,
                                children: [
                                  _buildMetaChip(Icons.help_outline, '$totalQ Questions', AppColors.primary),
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
                        // Button 1: Sample Preview Drill (Always opens directly so questions are accessible)
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
                              padding: const EdgeInsets.symmetric(vertical: 9),
                              side: const BorderSide(color: AppColors.primary),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        // Button 2: Full Exam Instructions & CBT Hall
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: () => _openQuiz(context, quizId, isLocked, rawTitle, setNumber),
                            icon: Icon(isLocked ? Icons.lock_open : Icons.play_arrow, size: 16),
                            label: Text(
                              isLocked ? 'Unlock (50 Qs)' : 'Full Drill (50 Qs)',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: isLocked ? AppColors.accent : AppColors.primary,
                              padding: const EdgeInsets.symmetric(vertical: 9),
                            ),
                          ),
                        ),
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
