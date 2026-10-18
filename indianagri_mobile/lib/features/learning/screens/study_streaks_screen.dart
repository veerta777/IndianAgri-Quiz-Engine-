import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/state_views.dart';

class StudyStreaksScreen extends StatefulWidget {
  const StudyStreaksScreen({super.key});

  @override
  State<StudyStreaksScreen> createState() => _StudyStreaksScreenState();
}

class _StudyStreaksScreenState extends State<StudyStreaksScreen> {
  bool _isLoading = true;
  String? _errorMessage;

  int _currentStreak = 0;
  int _bestStreak = 0;
  List<dynamic> _calendar = [];
  Map<String, dynamic>? _goals;

  final _testsGoalController = TextEditingController();
  final _qsGoalController = TextEditingController();
  bool _isSavingGoals = false;

  @override
  void initState() {
    super.initState();
    _fetchStreaks();
  }

  @override
  void dispose() {
    _testsGoalController.dispose();
    _qsGoalController.dispose();
    super.dispose();
  }

  Future<void> _fetchStreaks() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.studyStreaks);
      if (res is Map<String, dynamic>) {
        setState(() {
          _currentStreak = (res['current_streak'] as num?)?.toInt() ?? 0;
          _bestStreak = (res['best_streak'] as num?)?.toInt() ?? 0;
          _calendar = (res['calendar'] as List<dynamic>?) ?? [];
          _goals = res['goals'] as Map<String, dynamic>?;

          if (_goals != null) {
            _testsGoalController.text = (_goals!['weekly_tests_target'] ?? 5).toString();
            _qsGoalController.text = (_goals!['weekly_questions_target'] ?? 50).toString();
          }

          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load study streaks.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _saveGoals() async {
    final tests = int.tryParse(_testsGoalController.text) ?? 5;
    final qs = int.tryParse(_qsGoalController.text) ?? 50;

    setState(() {
      _isSavingGoals = true;
    });

    try {
      await ApiClient.post(
        ApiEndpoints.updateGoals,
        body: {
          'weekly_tests': tests,
          'weekly_questions': qs,
        },
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Weekly study targets updated successfully!'), backgroundColor: AppColors.correctGreen),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to update targets: $e'), backgroundColor: AppColors.incorrectRed),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSavingGoals = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Study Streaks & Goals')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingView(message: 'Loading attendance calendar...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(message: _errorMessage!, onRetry: _fetchStreaks);
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Streak Badges Header
          Row(
            children: [
              Expanded(
                child: _buildStreakCard(
                  title: 'Current Streak',
                  value: '$_currentStreak Days',
                  icon: Icons.local_fire_department,
                  color: const Color(0xFFEA580C),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStreakCard(
                  title: 'All-Time Best',
                  value: '$_bestStreak Days',
                  icon: Icons.military_tech,
                  color: AppColors.warningAmber,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // 14-Day Activity Calendar
          const Text('14-Day Activity Calendar', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: AppStyles.cardDecoration(context),
            child: _calendar.isEmpty
                ? const Text('Activity records will populate as you solve questions.', style: TextStyle(fontSize: 13, color: AppColors.textSecondaryLight))
                : Wrap(
                    spacing: 8,
                    runSpacing: 10,
                    children: _calendar.map((day) {
                      final dateStr = day['date']?.toString() ?? '';
                      final hasActivity = day['has_activity'] == true;
                      final qs = day['questions_solved'] ?? 0;

                      return Tooltip(
                        message: '$dateStr: $qs questions solved',
                        child: Container(
                          width: 42,
                          height: 52,
                          decoration: BoxDecoration(
                            color: hasActivity ? AppColors.correctGreen : AppColors.cardBorderLight.withOpacity(0.4),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                hasActivity ? Icons.check : Icons.remove,
                                size: 16,
                                color: hasActivity ? Colors.white : AppColors.textMutedLight,
                              ),
                              const SizedBox(height: 2),
                              Text(
                                dateStr.split('-').last,
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: hasActivity ? Colors.white : AppColors.textSecondaryLight,
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }).toList(),
                  ),
          ),
          const SizedBox(height: 24),

          // Weekly Targets Configuration
          const Text('Weekly Study Target Settings', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: AppStyles.cardDecoration(context),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextField(
                  controller: _testsGoalController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Target Tests per Week',
                    prefixIcon: Icon(Icons.checklist),
                  ),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _qsGoalController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Target Solved Questions per Week',
                    prefixIcon: Icon(Icons.help_outline),
                  ),
                ),
                const SizedBox(height: 16),
                AppButton(
                  label: 'Update Study Targets',
                  isLoading: _isSavingGoals,
                  onPressed: _saveGoals,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStreakCard({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: AppStyles.cardDecoration(context),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(title, style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight)),
              Icon(icon, color: color, size: 24),
            ],
          ),
          const SizedBox(height: 8),
          Text(value, style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: color)),
        ],
      ),
    );
  }
}
