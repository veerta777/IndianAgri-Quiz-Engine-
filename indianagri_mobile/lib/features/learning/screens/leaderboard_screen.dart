import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/state_views.dart';

class LeaderboardScreen extends StatefulWidget {
  const LeaderboardScreen({super.key});

  @override
  State<LeaderboardScreen> createState() => _LeaderboardScreenState();
}

class _LeaderboardScreenState extends State<LeaderboardScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _rankings = [];
  Map<String, dynamic>? _userRank;

  @override
  void initState() {
    super.initState();
    _fetchLeaderboard();
  }

  Future<void> _fetchLeaderboard() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiClient.get(ApiEndpoints.leaderboard);
      if (res is Map<String, dynamic>) {
        setState(() {
          _rankings = (res['rankings'] as List<dynamic>?) ?? [];
          _userRank = res['current_user_rank'] as Map<String, dynamic>?;
          _isLoading = false;
        });
        return;
      }
      setState(() {
        _errorMessage = 'Could not load leaderboard.';
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
      appBar: AppBar(title: const Text('National Leaderboard')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingView(message: 'Calculating national merit rankings...');
    }

    if (_errorMessage != null) {
      return ErrorStateView(message: _errorMessage!, onRetry: _fetchLeaderboard);
    }

    if (_rankings.isEmpty) {
      return const EmptyStateView(
        title: 'Leaderboard Empty',
        description: 'Complete examinations to rank on the national student merit list.',
        icon: Icons.emoji_events_outlined,
      );
    }

    return Column(
      children: [
        // User Rank Banner if available
        if (_userRank != null) ...[
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: AppColors.primarySubtle,
              border: Border(bottom: BorderSide(color: AppColors.primary.withOpacity(0.3))),
            ),
            child: Row(
              children: [
                const Icon(Icons.star, color: AppColors.primary, size: 24),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Your Current Rank: #${_userRank!['rank'] ?? "N/A"}',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.primaryDark),
                      ),
                      Text(
                        'Total Score: ${_userRank!['total_score'] ?? 0} pts • Accuracy: ${_userRank!['accuracy'] ?? 0}%',
                        style: const TextStyle(fontSize: 12, color: AppColors.primaryDark),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],

        // Rankings List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: _rankings.length,
            itemBuilder: (context, index) {
              final rank = _rankings[index] as Map<String, dynamic>;
              final rankNum = rank['rank'] ?? (index + 1);
              final name = rank['display_name'] ?? 'Scholar';
              final score = (rank['total_score'] as num?)?.toDouble() ?? 0.0;
              final accuracy = (rank['accuracy'] as num?)?.toDouble() ?? 0.0;
              final isTop3 = index < 3;

              Color rankBadgeColor = AppColors.primarySubtle;
              Color rankTextColor = AppColors.primaryDark;
              if (index == 0) {
                rankBadgeColor = const Color(0xFFFEF3C7);
                rankTextColor = const Color(0xFFB45309); // Gold
              } else if (index == 1) {
                rankBadgeColor = const Color(0xFFF1F5F9);
                rankTextColor = const Color(0xFF475569); // Silver
              } else if (index == 2) {
                rankBadgeColor = const Color(0xFFFFEDD5);
                rankTextColor = const Color(0xFFC2410C); // Bronze
              }

              return Container(
                margin: const EdgeInsets.only(bottom: 10),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                decoration: AppStyles.cardDecoration(context),
                child: Row(
                  children: [
                    Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: rankBadgeColor,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          '$rankNum',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: rankTextColor),
                        ),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          const SizedBox(height: 2),
                          Text('${accuracy.toStringAsFixed(1)}% Accuracy', style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
                        ],
                      ),
                    ),
                    Text(
                      '${score.toStringAsFixed(1)} pts',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.primary),
                    ),
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
