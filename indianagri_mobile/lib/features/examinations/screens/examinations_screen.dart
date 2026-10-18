import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../../core/widgets/state_views.dart';
import '../../practice_sets/screens/practice_sets_screen.dart';

class ExaminationsScreen extends StatefulWidget {
  const ExaminationsScreen({super.key});

  @override
  State<ExaminationsScreen> createState() => _ExaminationsScreenState();
}

class _ExaminationsScreenState extends State<ExaminationsScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _examinations = [];

  @override
  void initState() {
    super.initState();
    _fetchExaminations();
  }

  Future<void> _fetchExaminations() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.examinations);
      if (res is List<dynamic>) {
        setState(() {
          _examinations = res;
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load examinations.';
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
        title: const Text('Examinations Catalogue'),
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
      return const LoadingView(message: 'Loading examination catalogues...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(
        message: _errorMessage!,
        onRetry: _fetchExaminations,
      );
    }

    if (_examinations.isEmpty) {
      return const EmptyStateView(
        title: 'No Active Examinations',
        description: 'Examinations will appear here once published by the academic board.',
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _examinations.length,
      itemBuilder: (context, index) {
        final exam = _examinations[index] as Map<String, dynamic>;
        final id = (exam['id'] as num?)?.toInt() ?? 0;
        final name = exam['name'] ?? 'Agricultural Examination';
        final code = exam['code'] ?? '';
        final description = exam['description'] ?? '';

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: AppStyles.cardDecoration(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  if (code.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.primarySubtle,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        code,
                        style: const TextStyle(
                          color: AppColors.primaryDark,
                          fontWeight: FontWeight.bold,
                          fontSize: 11,
                        ),
                      ),
                    ),
                  const Spacer(),
                  const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textMutedLight),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                name,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              if (description.isNotEmpty) ...[
                const SizedBox(height: 6),
                Text(
                  description,
                  style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const SizedBox(height: 14),
              Row(
                children: [
                  OutlinedButton.icon(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => const PracticeSetsScreen(initialTab: 2),
                        ),
                      );
                    },
                    icon: const Icon(Icons.menu_book, size: 16),
                    label: const Text('View Practice Sets', style: TextStyle(fontSize: 12)),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
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
