import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/state_views.dart';
import '../../quiz_cbt/screens/quiz_instructions_screen.dart';

class CustomPracticeScreen extends StatefulWidget {
  const CustomPracticeScreen({super.key});

  @override
  State<CustomPracticeScreen> createState() => _CustomPracticeScreenState();
}

class _CustomPracticeScreenState extends State<CustomPracticeScreen> {
  bool _isLoading = true;
  String? _errorMessage;

  List<dynamic> _subjects = [];
  List<dynamic> _examinations = [];

  int? _selectedExamId;
  final Set<int> _selectedSubjectIds = {};
  String _selectedDifficulty = 'all';
  int _questionCount = 20;
  bool _isGenerating = false;

  @override
  void initState() {
    super.initState();
    _fetchOptions();
  }

  Future<void> _fetchOptions() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.customPracticeOptions);
      if (res is Map<String, dynamic>) {
        setState(() {
          _subjects = (res['subjects'] as List<dynamic>?) ?? [];
          _examinations = (res['examinations'] as List<dynamic>?) ?? [];
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load custom practice options.';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _generateTest() async {
    if (_selectedSubjectIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select at least one agricultural subject.'),
          backgroundColor: AppColors.warningAmber,
        ),
      );
      return;
    }

    setState(() {
      _isGenerating = true;
    });

    try {
      final res = await ApiClient.post(
        ApiEndpoints.createCustomPractice,
        body: {
          'examination_id': _selectedExamId,
          'subject_ids': _selectedSubjectIds.toList(),
          'difficulty': _selectedDifficulty,
          'question_count': _questionCount,
        },
      );

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
          SnackBar(content: Text('Failed to generate custom test: $e'), backgroundColor: AppColors.incorrectRed),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isGenerating = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Custom Practice Test'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingView(message: 'Loading question bank configuration...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(message: _errorMessage!, onRetry: _fetchOptions);
    }

    return SafeArea(
      child: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text(
                    'Configure Your Practice Session',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Select subjects, difficulty level, and question pool size to assemble a custom CBT test.',
                    style: TextStyle(fontSize: 13, color: AppColors.textSecondaryLight),
                  ),
                  const SizedBox(height: 20),

                  // Target Examination Selector
                  if (_examinations.isNotEmpty) ...[
                    const Text('Target Examination (Optional)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<int>(
                      value: _selectedExamId,
                      decoration: const InputDecoration(
                        hintText: 'All Examinations',
                        contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      ),
                      items: [
                        const DropdownMenuItem<int>(
                          value: null,
                          child: Text('All Examinations (General Pool)'),
                        ),
                        ..._examinations.map((e) {
                          final id = (e['id'] as num).toInt();
                          final name = e['name']?.toString() ?? 'Exam';
                          return DropdownMenuItem<int>(
                            value: id,
                            child: Text(name),
                          );
                        }),
                      ],
                      onChanged: (val) {
                        setState(() {
                          _selectedExamId = val;
                        });
                      },
                    ),
                    const SizedBox(height: 20),
                  ],

                  // Subjects Multi-selector
                  const Text('Select Subjects *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _subjects.map((sub) {
                      final sId = (sub['id'] as num).toInt();
                      final sName = sub['name']?.toString() ?? 'Subject';
                      final isSelected = _selectedSubjectIds.contains(sId);

                      return FilterChip(
                        label: Text(sName),
                        selected: isSelected,
                        onSelected: (selected) {
                          setState(() {
                            if (selected) {
                              _selectedSubjectIds.add(sId);
                            } else {
                              _selectedSubjectIds.remove(sId);
                            }
                          });
                        },
                        selectedColor: AppColors.primarySubtle,
                        checkmarkColor: AppColors.primary,
                        labelStyle: TextStyle(
                          color: isSelected ? AppColors.primaryDark : AppColors.textPrimaryLight,
                          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                          fontSize: 12,
                        ),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 20),

                  // Difficulty Level
                  const Text('Difficulty Level', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      _buildDifficultyOption('all', 'Any Level'),
                      const SizedBox(width: 8),
                      _buildDifficultyOption('easy', 'Easy'),
                      const SizedBox(width: 8),
                      _buildDifficultyOption('medium', 'Medium'),
                      const SizedBox(width: 8),
                      _buildDifficultyOption('hard', 'Hard'),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // Question Count
                  const Text('Number of Questions', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 8),
                  Row(
                    children: [10, 20, 30, 50].map((cnt) {
                      final isSelected = _questionCount == cnt;
                      return Padding(
                        padding: const EdgeInsets.only(right: 10.0),
                        child: ChoiceChip(
                          label: Text('$cnt Questions'),
                          selected: isSelected,
                          onSelected: (selected) {
                            if (selected) {
                              setState(() {
                                _questionCount = cnt;
                              });
                            }
                          },
                          selectedColor: AppColors.primarySubtle,
                          labelStyle: TextStyle(
                            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                            color: isSelected ? AppColors.primaryDark : AppColors.textSecondaryLight,
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                ],
              ),
            ),
          ),

          // Submit Action
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: AppColors.cardBorderLight)),
            ),
            child: AppButton(
              label: 'Generate & Start Practice Test',
              icon: Icons.flash_on,
              isLoading: _isGenerating,
              onPressed: _generateTest,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDifficultyOption(String value, String label) {
    final isSelected = _selectedDifficulty == value;
    return Expanded(
      child: ChoiceChip(
        label: Center(child: Text(label, style: TextStyle(fontSize: 11, fontWeight: isSelected ? FontWeight.bold : FontWeight.normal))),
        selected: isSelected,
        onSelected: (selected) {
          if (selected) {
            setState(() {
              _selectedDifficulty = value;
            });
          }
        },
        selectedColor: AppColors.primarySubtle,
      ),
    );
  }
}
