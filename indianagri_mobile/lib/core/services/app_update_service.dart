import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../models/app_update_info.dart';

/// Service responsible for querying backend update notifications,
/// comparing application versions, and displaying update modals.
class AppUpdateService {
  static const String currentAppVersion = '1.0.0';

  static AppUpdateInfo? _cachedUpdateInfo;
  static bool _hasDismissedBannerThisSession = false;

  static bool get hasDismissedBanner => _hasDismissedBannerThisSession;
  static void dismissBanner() {
    _hasDismissedBannerThisSession = true;
  }

  /// Check backend for app update info.
  static Future<AppUpdateInfo?> checkForUpdate({
    String currentVersion = currentAppVersion,
    bool forceRefresh = false,
  }) async {
    if (_cachedUpdateInfo != null && !forceRefresh) {
      return _cachedUpdateInfo;
    }

    try {
      final res = await ApiClient.get(ApiEndpoints.appVersion(currentVersion));
      if (res is Map) {
        final safeMap = Map<String, dynamic>.from(res);
        final info = AppUpdateInfo.fromJson(safeMap);
        _cachedUpdateInfo = info;
        return info;
      }
    } catch (_) {
      // Silently return cached or null on background network hiccups
    }
    return _cachedUpdateInfo;
  }

  static const String directApkDownloadUrl =
      'https://github.com/veerta777/IndianAgri-Quiz-Engine-/releases/latest/download/IndianAgriExam.apk';

  /// Launch the download / Play Store URL.
  static Future<void> launchUpdateUrl(String url, BuildContext context) async {
    try {
      var clean = url.trim();
      // If URL is empty, points to unconfigured /mobile-app/ slug, fallback to direct APK download
      if (clean.isEmpty || clean.contains('indianagriexam.com/mobile-app')) {
        clean = directApkDownloadUrl;
      }
      final uri = Uri.parse(clean);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        await launchUrl(uri);
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Could not open link: $url'),
            backgroundColor: AppColors.incorrectRed,
          ),
        );
      }
    }
  }

  /// Display the Update Information Dialog.
  static Future<void> showUpdateDialog(
    BuildContext context, {
    AppUpdateInfo? updateInfo,
    bool forceShow = false,
  }) async {
    final info = updateInfo ?? await checkForUpdate(forceRefresh: true);

    if (!context.mounted) return;

    if (info == null) {
      if (forceShow) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Unable to connect to update server. Please check your internet connection.'),
            backgroundColor: AppColors.incorrectRed,
          ),
        );
      }
      return;
    }

    // If up-to-date and user explicitly requested a check
    if (!info.isUpdateAvailable) {
      if (forceShow) {
        showDialog(
          context: context,
          builder: (ctx) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: AppStyles.borderRadiusL),
            title: Row(
              children: const [
                Icon(Icons.check_circle, color: AppColors.correctGreen, size: 28),
                SizedBox(width: 10),
                Text('Up to Date', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
              ],
            ),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'You are using the latest version of IndianAgriExam (v${info.currentVersion}).',
                  style: const TextStyle(fontSize: 14),
                ),
                const SizedBox(height: 8),
                const Text(
                  'All question banks, CBT examination sets, and study tools are fully synced.',
                  style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(ctx).pop(),
                child: const Text('OK', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.primary)),
              ),
            ],
          ),
        );
      }
      return;
    }

    // Display Update Modal
    final isForce = info.isForceUpdate;

    await showDialog(
      context: context,
      barrierDismissible: !isForce,
      builder: (ctx) {
        return PopScope(
          canPop: !isForce,
          child: Dialog(
            shape: RoundedRectangleBorder(borderRadius: AppStyles.borderRadiusL),
            clipBehavior: Clip.antiAlias,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Gradient Header
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        colors: [Color(0xFF0F766E), Color(0xFF117E3A)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                    ),
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.rocket_launch_rounded, color: Colors.white, size: 36),
                        ),
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.25),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            isForce ? 'MANDATORY UPDATE REQUIRED' : 'NEW VERSION AVAILABLE',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.8,
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          info.title,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Body Content
                  Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Version Pills
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                              decoration: BoxDecoration(
                                color: Colors.grey.shade100,
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: Colors.grey.shade300),
                              ),
                              child: Text(
                                'Current: v${info.currentVersion}',
                                style: TextStyle(fontSize: 12, color: Colors.grey.shade700, fontWeight: FontWeight.w600),
                              ),
                            ),
                            const Padding(
                              padding: EdgeInsets.symmetric(horizontal: 8.0),
                              child: Icon(Icons.arrow_forward_rounded, size: 16, color: AppColors.primary),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                              decoration: BoxDecoration(
                                color: AppColors.correctGreenBg,
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: AppColors.correctGreen.withOpacity(0.5)),
                              ),
                              child: Text(
                                'Latest: v${info.latestVersion}',
                                style: const TextStyle(
                                  fontSize: 12,
                                  color: AppColors.correctGreen,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ],
                        ),

                        if (info.message.isNotEmpty) ...[
                          const SizedBox(height: 14),
                          Text(
                            info.message,
                            style: const TextStyle(fontSize: 13, color: AppColors.textPrimaryLight, height: 1.4),
                          ),
                        ],

                        // Release Notes
                        if (info.releaseNotes.isNotEmpty) ...[
                          const SizedBox(height: 16),
                          const Text(
                            "What's New in this Update:",
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textPrimaryLight),
                          ),
                          const SizedBox(height: 8),
                          ...info.releaseNotes.map((note) => Padding(
                                padding: const EdgeInsets.only(bottom: 6),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Padding(
                                      padding: EdgeInsets.only(top: 2.0, right: 8.0),
                                      child: Icon(Icons.check_circle_rounded, size: 16, color: AppColors.primary),
                                    ),
                                    Expanded(
                                      child: Text(
                                        note,
                                        style: const TextStyle(fontSize: 12.5, color: AppColors.textPrimaryLight, height: 1.3),
                                      ),
                                    ),
                                  ],
                                ),
                              )),
                        ],

                        if (info.publishedAt.isNotEmpty) ...[
                          const SizedBox(height: 12),
                          Text(
                            'Released: ${info.publishedAt}',
                            style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight),
                          ),
                        ],

                        const SizedBox(height: 20),

                        // Action Buttons
                        Row(
                          children: [
                            if (!isForce) ...[
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: () => Navigator.of(ctx).pop(),
                                  style: OutlinedButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(vertical: 12),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                  child: const Text('Later'),
                                ),
                              ),
                              const SizedBox(width: 12),
                            ],
                            Expanded(
                              flex: isForce ? 1 : 2,
                              child: ElevatedButton.icon(
                                onPressed: () {
                                  launchUpdateUrl(info.updateUrl, context);
                                },
                                icon: const Icon(Icons.system_update_rounded, size: 18),
                                label: const Text(
                                  'Update Now',
                                  style: TextStyle(fontWeight: FontWeight.bold),
                                ),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: AppColors.primary,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 12),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  elevation: 2,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
