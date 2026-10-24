/// Model representing mobile app update information from the IndianAgri backend.
class AppUpdateInfo {
  final String currentVersion;
  final String latestVersion;
  final String minSupportedVersion;
  final bool isUpdateAvailable;
  final bool isForceUpdate;
  final String title;
  final String message;
  final List<String> releaseNotes;
  final String updateUrl;
  final String publishedAt;

  const AppUpdateInfo({
    required this.currentVersion,
    required this.latestVersion,
    required this.minSupportedVersion,
    required this.isUpdateAvailable,
    required this.isForceUpdate,
    required this.title,
    required this.message,
    required this.releaseNotes,
    required this.updateUrl,
    required this.publishedAt,
  });

  factory AppUpdateInfo.fromJson(Map<String, dynamic> json) {
    final rawNotes = json['release_notes'];
    List<String> notes = [];
    if (rawNotes is List) {
      notes = rawNotes.map((e) => e.toString().trim()).where((e) => e.isNotEmpty).toList();
    } else if (rawNotes is String) {
      notes = rawNotes.split('\n').map((e) => e.replaceAll(RegExp(r'^[•\-\*]\s*'), '').trim()).where((e) => e.isNotEmpty).toList();
    }

    return AppUpdateInfo(
      currentVersion: json['current_version']?.toString() ?? '1.0.0',
      latestVersion: json['latest_version']?.toString() ?? '1.1.0',
      minSupportedVersion: json['min_supported_version']?.toString() ?? '1.0.0',
      isUpdateAvailable: json['is_update_available'] == true || json['is_update_available'] == 1,
      isForceUpdate: json['is_force_update'] == true || json['is_force_update'] == 1,
      title: json['title']?.toString() ?? 'New Update Available',
      message: json['message']?.toString() ?? '',
      releaseNotes: notes,
      updateUrl: json['update_url']?.toString() ?? 'https://indianagriexam.com/mobile-app/',
      publishedAt: json['published_at']?.toString() ?? '',
    );
  }
}
