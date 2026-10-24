import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_styles.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/language_toggle.dart';
import '../../auth/auth_provider.dart';
import '../../auth/screens/login_screen.dart';
import '../../subscriptions/screens/subscription_plans_screen.dart';
import '../../../core/services/app_update_service.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final user = auth.user;
    final subscriptions = auth.subscriptions;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Student Profile'),
        actions: const [
          Padding(
            padding: EdgeInsets.only(right: 14.0),
            child: LanguageToggle(),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Student Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: AppStyles.cardDecoration(context),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 32,
                    backgroundColor: AppColors.primarySubtle,
                    child: const Icon(Icons.person, size: 40, color: AppColors.primary),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user?['display_name'] ?? 'Candidate',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          user?['email'] ?? '',
                          style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '@${user?['username'] ?? ""}',
                          style: const TextStyle(fontSize: 12, color: AppColors.primaryDark),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Active Subscriptions
            const Text(
              'Active Subscriptions & Entitlements',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            const SizedBox(height: 10),
            if (subscriptions.isEmpty) ...[
              Container(
                padding: const EdgeInsets.all(16),
                decoration: AppStyles.cardDecoration(context),
                child: Column(
                  children: [
                    const Icon(Icons.lock_outline, size: 36, color: AppColors.warningAmber),
                    const SizedBox(height: 8),
                    const Text('No Active Subscriptions', style: TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    const Text(
                      'Subscribe to unlock premium mock test series and targeted revision banks.',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                    ),
                    const SizedBox(height: 14),
                    AppButton(
                      label: 'Explore Membership Plans',
                      icon: Icons.star_outline,
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const SubscriptionPlansScreen()),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ] else ...[
              ...subscriptions.map((sub) {
                final planName = sub['plan_name'] ?? 'Agricultural Pass';
                final expiry = sub['expiry_date'] ?? 'N/A';
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppColors.correctGreenBg,
                    borderRadius: AppStyles.borderRadiusM,
                    border: Border.all(color: AppColors.correctGreen.withOpacity(0.5)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.verified, color: AppColors.correctGreen, size: 22),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(planName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                            const SizedBox(height: 2),
                            Text('Valid until: $expiry', style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight)),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }),
              const SizedBox(height: 10),
              OutlinedButton.icon(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const SubscriptionPlansScreen()),
                  );
                },
                icon: const Icon(Icons.upgrade, size: 18),
                label: const Text('Upgrade or Renew Plan'),
              ),
            ],
            const SizedBox(height: 24),

            // App Settings & Preferences
            const Text(
              'App Settings',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            const SizedBox(height: 10),
            Container(
              decoration: AppStyles.cardDecoration(context),
              child: Column(
                children: [
                  ListTile(
                    leading: const Icon(Icons.translate, color: AppColors.primary),
                    title: const Text('Language (English / हिन्दी)'),
                    trailing: const LanguageToggle(),
                  ),
                  const Divider(height: 1),
                  ListTile(
                    leading: const Icon(Icons.system_update_alt, color: AppColors.primary),
                    title: const Text('App Updates & Release Notes'),
                    subtitle: const Text('v1.0.0 • Tap to check for latest updates', style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight)),
                    trailing: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: AppColors.primarySubtle,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Text('Check', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.primary)),
                    ),
                    onTap: () {
                      AppUpdateService.showUpdateDialog(context, forceShow: true);
                    },
                  ),
                  const Divider(height: 1),
                  ListTile(
                    leading: const Icon(Icons.security, color: AppColors.textSecondaryLight),
                    title: const Text('Privacy & Academic Integrity'),
                    trailing: const Icon(Icons.chevron_right, size: 18),
                    onTap: () {},
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Logout Button
            OutlinedButton.icon(
              onPressed: () async {
                await auth.logout();
                if (context.mounted) {
                  Navigator.pushAndRemoveUntil(
                    context,
                    MaterialPageRoute(builder: (_) => const LoginScreen()),
                    (route) => false,
                  );
                }
              },
              icon: const Icon(Icons.logout, color: AppColors.incorrectRed, size: 18),
              label: const Text('Log Out', style: TextStyle(color: AppColors.incorrectRed)),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: AppColors.incorrectRed),
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
