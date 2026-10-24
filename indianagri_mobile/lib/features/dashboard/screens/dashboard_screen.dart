import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../../core/widgets/state_views.dart';
import '../../auth/auth_provider.dart';
import '../../examinations/screens/examinations_screen.dart';
import '../../practice_sets/screens/practice_sets_screen.dart';
import '../../mock_tests/screens/mock_tests_screen.dart';
import '../../learning/screens/bookmarks_screen.dart';
import '../../learning/screens/wrong_notebook_screen.dart';
import '../../learning/screens/custom_practice_screen.dart';
import '../../learning/screens/study_streaks_screen.dart';
import '../../learning/screens/leaderboard_screen.dart';
import '../../profile/screens/profile_screen.dart';
import '../../quiz_cbt/screens/cbt_exam_screen.dart';
import '../../quiz_cbt/screens/quiz_instructions_screen.dart';
import '../../../core/models/app_update_info.dart';
import '../../../core/services/app_update_service.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _currentNavIndex = 0;
  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _summary;
  AppUpdateInfo? _appUpdateInfo;

  @override
  void initState() {
    super.initState();
    _fetchDashboardSummary();
    _checkForAppUpdates();
  }

  Future<void> _checkForAppUpdates() async {
    final info = await AppUpdateService.checkForUpdate();
    if (mounted && info != null) {
      setState(() {
        _appUpdateInfo = info;
      });
      // If mandatory update, immediately show update dialog
      if (info.isForceUpdate) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) {
            AppUpdateService.showUpdateDialog(context, updateInfo: info);
          }
        });
      }
    }
  }

  Future<void> _fetchDashboardSummary() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.dashboardSummary);
      if (res is Map<String, dynamic>) {
        setState(() {
          _summary = res;
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load dashboard data.';
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
    final auth = Provider.of<AuthProvider>(context);
    final user = auth.user;

    final pages = [
      _buildHomeView(user),
      const PracticeSetsScreen(),
      const MockTestsScreen(),
      _buildLearningView(),
      const ProfileScreen(),
    ];

    return Scaffold(
      body: pages[_currentNavIndex],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentNavIndex,
        onDestinationSelected: (index) {
          setState(() {
            _currentNavIndex = index;
          });
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.menu_book_outlined),
            selectedIcon: Icon(Icons.menu_book),
            label: 'Practice',
          ),
          NavigationDestination(
            icon: Icon(Icons.assignment_outlined),
            selectedIcon: Icon(Icons.assignment),
            label: 'Mocks',
          ),
          NavigationDestination(
            icon: Icon(Icons.psychology_outlined),
            selectedIcon: Icon(Icons.psychology),
            label: 'Learning',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }

  Widget _buildHomeView(Map<String, dynamic>? user) {
    if (_isLoading) {
      return const Scaffold(body: LoadingView(message: 'Loading student dashboard...'));
    }

    if (_errorMessage != null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Dashboard')),
        body: ErrorStateView(
          message: _errorMessage!,
          onRetry: _fetchDashboardSummary,
        ),
      );
    }

    final stats = _summary?['stats'] as Map<String, dynamic>?;
    final streaks = _summary?['streaks'] as Map<String, dynamic>?;
    final activeAttempt = _summary?['active_attempt'] as Map<String, dynamic>?;
    final continueQuizzes = (_summary?['continue_quizzes'] as List<dynamic>?) ?? [];
    final subjectMastery = (_summary?['subject_mastery'] as List<dynamic>?) ?? [];

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: AppColors.primarySubtle,
              child: const Icon(Icons.person, color: AppColors.primary, size: 22),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  user?['display_name'] ?? 'Student',
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                ),
                const Text(
                  'IndianAgriExam Scholar',
                  style: TextStyle(fontSize: 11, color: AppColors.textSecondaryLight),
                ),
              ],
            ),
          ],
        ),
        actions: const [
          Padding(
            padding: EdgeInsets.only(right: 14.0),
            child: LanguageToggle(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchDashboardSummary,
        color: AppColors.primary,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // App Update Banner (if available and not dismissed)
              if (_appUpdateInfo != null && _appUpdateInfo!.isUpdateAvailable && !AppUpdateService.hasDismissedBanner) ...[
                _buildUpdateBanner(_appUpdateInfo!),
                const SizedBox(height: 16),
              ],

              // Active Attempt Alert (Resume Test)
              if (activeAttempt != null) ...[
                _buildActiveAttemptCard(activeAttempt),
                const SizedBox(height: 16),
              ],

              // KPI Cards Grid
              _buildStatsGrid(stats, streaks),
              const SizedBox(height: 20),

              // Quick Action Tiles
              const Text(
                'Explore Preparation Hub',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              _buildQuickActions(),
              const SizedBox(height: 24),

              // Continue Practicing
              if (continueQuizzes.isNotEmpty) ...[
                const Text(
                  'Continue Practicing',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 12),
                ...continueQuizzes.map((q) => _buildContinueQuizCard(q)),
                const SizedBox(height: 24),
              ],

              // Subject Mastery
              if (subjectMastery.isNotEmpty) ...[
                const Text(
                  'Subject Mastery Overview',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 12),
                _buildMasteryList(subjectMastery),
                const SizedBox(height: 24),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildUpdateBanner(AppUpdateInfo info) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F766E), Color(0xFF117E3A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: AppStyles.borderRadiusM,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF117E3A).withOpacity(0.25),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.2),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.rocket_launch_rounded, color: Colors.white, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Update v${info.latestVersion} Available',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13.5),
                ),
                const SizedBox(height: 2),
                Text(
                  info.title.isNotEmpty ? info.title : "Tap to see what's new and get the latest features.",
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: Colors.white.withOpacity(0.9), fontSize: 11.5),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          ElevatedButton(
            onPressed: () {
              AppUpdateService.showUpdateDialog(context, updateInfo: info);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.white,
              foregroundColor: const Color(0xFF0F766E),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Update', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
          ),
          if (!info.isForceUpdate) ...[
            const SizedBox(width: 4),
            IconButton(
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
              icon: const Icon(Icons.close, color: Colors.white70, size: 18),
              onPressed: () {
                setState(() {
                  AppUpdateService.dismissBanner();
                });
              },
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildActiveAttemptCard(Map<String, dynamic> attempt) {
    final quizTitle = attempt['quiz_title'] ?? 'Examination in Progress';
    final quizId = attempt['quiz_id'];
    final attemptId = attempt['id'];

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.primarySubtle,
        borderRadius: AppStyles.borderRadiusL,
        border: Border.all(color: AppColors.primary.withOpacity(0.5)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.timer, color: Colors.white, size: 16),
              ),
              const SizedBox(width: 8),
              const Text(
                'UNSUBMITTED TEST IN PROGRESS',
                style: TextStyle(
                  color: AppColors.primaryDark,
                  fontWeight: FontWeight.bold,
                  fontSize: 11,
                  letterSpacing: 0.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            quizTitle,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
          ),
          const SizedBox(height: 12),
          AppButton(
            label: 'Resume Test Now',
            icon: Icons.play_arrow,
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => CbtExamScreen(quizId: (quizId as num).toInt()),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildStatsGrid(Map<String, dynamic>? stats, Map<String, dynamic>? streaks) {
    final testsAttempted = stats?['attempts_count'] ?? 0;
    final questionsSolved = stats?['questions_solved'] ?? 0;
    final accuracy = (stats?['avg_accuracy'] as num?)?.toDouble() ?? 0.0;
    final streak = streaks?['current'] ?? 0;

    return GridView.count(
      crossAxisCount: 2,
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      childAspectRatio: 1.45,
      children: [
        _buildStatCard(
          title: 'Tests Attempted',
          value: '$testsAttempted',
          icon: Icons.checklist_rtl,
          color: AppColors.primary,
        ),
        _buildStatCard(
          title: 'Accuracy',
          value: '${accuracy.toStringAsFixed(1)}%',
          icon: Icons.track_changes,
          color: AppColors.correctGreen,
        ),
        _buildStatCard(
          title: 'Questions Solved',
          value: '$questionsSolved',
          icon: Icons.psychology,
          color: AppColors.warningAmber,
        ),
        _buildStatCard(
          title: 'Study Streak',
          value: '$streak Days',
          icon: Icons.local_fire_department,
          color: const Color(0xFFEA580C),
        ),
      ],
    );
  }

  Widget _buildStatCard({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: AppStyles.cardDecoration(context),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                title,
                style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
              ),
              Icon(icon, color: color, size: 20),
            ],
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions() {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildActionTile(
                title: 'Examinations',
                subtitle: 'ICAR JRF, SRF & CUET',
                icon: Icons.account_balance,
                color: AppColors.primary,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const ExaminationsScreen()),
                  );
                },
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildActionTile(
                title: 'Custom Test',
                subtitle: 'Pick Subjects & Topics',
                icon: Icons.tune,
                color: const Color(0xFF2563EB),
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const CustomPracticeScreen()),
                  );
                },
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildActionTile(
                title: 'Wrong Notebook',
                subtitle: 'Retry Past Mistakes',
                icon: Icons.menu_book,
                color: AppColors.incorrectRed,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const WrongNotebookScreen()),
                  );
                },
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildActionTile(
                title: 'Bookmarks',
                subtitle: 'Starred Drill Quizzes',
                icon: Icons.bookmark,
                color: AppColors.warningAmber,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const BookmarksScreen()),
                  );
                },
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildActionTile({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: AppStyles.borderRadiusL,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: AppStyles.cardDecoration(context),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: color.withOpacity(0.12),
                borderRadius: AppStyles.borderRadiusM,
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(height: 10),
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            const SizedBox(height: 2),
            Text(
              subtitle,
              style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContinueQuizCard(dynamic quiz) {
    final title = quiz['title'] ?? 'Practice Set';
    final qId = (quiz['id'] as num?)?.toInt() ?? 0;
    final totalQ = quiz['total_questions'] ?? 0;
    final duration = quiz['duration_minutes'] ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: AppStyles.cardDecoration(context),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                ),
                const SizedBox(height: 4),
                Text(
                  '$totalQ Questions • $duration Mins',
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
                  builder: (_) => QuizInstructionsScreen(quizId: qId),
                ),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            ),
            child: const Text('Start', style: TextStyle(fontSize: 13, color: Colors.white)),
          ),
        ],
      ),
    );
  }

  Widget _buildMasteryList(List<dynamic> list) {
    return Column(
      children: list.take(4).map((sub) {
        final name = sub['subject_name'] ?? 'General Agriculture';
        final accuracy = (sub['accuracy'] as num?)?.toDouble() ?? 0.0;
        final solved = sub['questions_solved'] ?? 0;

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
                  Text(name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  Text(
                    '${accuracy.toStringAsFixed(1)}%',
                    style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.primary, fontSize: 13),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              LinearProgressIndicator(
                value: (accuracy / 100.0).clamp(0.0, 1.0),
                backgroundColor: AppColors.cardBorderLight,
                valueColor: AlwaysStoppedAnimation<Color>(
                  accuracy >= 70 ? AppColors.correctGreen : (accuracy >= 45 ? AppColors.warningAmber : AppColors.incorrectRed),
                ),
                borderRadius: BorderRadius.circular(4),
              ),
              const SizedBox(height: 4),
              Text(
                '$solved Questions Solved',
                style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildLearningView() {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Learning & Tools'),
        actions: const [
          Padding(
            padding: EdgeInsets.only(right: 14.0),
            child: LanguageToggle(),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildToolCard(
            title: 'Wrong Question Notebook',
            description: 'Inspect mistakes from past attempts and launch instant retry drills.',
            icon: Icons.menu_book,
            color: AppColors.incorrectRed,
            onTap: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const WrongNotebookScreen()));
            },
          ),
          const SizedBox(height: 12),
          _buildToolCard(
            title: 'Bookmarks & Revision Drill',
            description: 'Review starred agricultural questions and generate targeted revision tests.',
            icon: Icons.bookmark,
            color: AppColors.warningAmber,
            onTap: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const BookmarksScreen()));
            },
          ),
          const SizedBox(height: 12),
          _buildToolCard(
            title: 'Custom Practice Test',
            description: 'Build customized practice exams tailored to specific subjects, topics, and difficulties.',
            icon: Icons.tune,
            color: const Color(0xFF2563EB),
            onTap: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomPracticeScreen()));
            },
          ),
          const SizedBox(height: 12),
          _buildToolCard(
            title: 'Study Streaks & Goals',
            description: 'Monitor daily attendance, track 14-day study streaks, and update weekly question targets.',
            icon: Icons.local_fire_department,
            color: const Color(0xFFEA580C),
            onTap: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const StudyStreaksScreen()));
            },
          ),
          const SizedBox(height: 12),
          _buildToolCard(
            title: 'Leaderboard & Rankings',
            description: 'Compare scores and percentiles against peer agricultural scholars nationwide.',
            icon: Icons.emoji_events,
            color: const Color(0xFF8B5CF6),
            onTap: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const LeaderboardScreen()));
            },
          ),
        ],
      ),
    );
  }

  Widget _buildToolCard({
    required String title,
    required String description,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: AppStyles.borderRadiusL,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: AppStyles.cardDecoration(context),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.12),
                borderRadius: AppStyles.borderRadiusL,
              ),
              child: Icon(icon, color: color, size: 28),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                  const SizedBox(height: 4),
                  Text(
                    description,
                    style: const TextStyle(color: AppColors.textSecondaryLight, fontSize: 12),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: AppColors.textMutedLight),
          ],
        ),
      ),
    );
  }
}
