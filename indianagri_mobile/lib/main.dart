import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';
import 'core/localization/app_localizations.dart';
import 'core/theme/app_theme.dart';
import 'core/constants/app_colors.dart';
import 'core/api/api_endpoints.dart';
import 'core/storage/secure_storage.dart';
import 'features/auth/auth_provider.dart';
import 'features/auth/screens/splash_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Prevent any unhandled widget build exception from ever rendering a blank grey screen (#C0C0C0)
  ErrorWidget.builder = (FlutterErrorDetails details) {
    return Material(
      color: Colors.transparent,
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.refresh_rounded, size: 44, color: AppColors.primary),
              const SizedBox(height: 14),
              const Text(
                'Content is loading...',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 8),
              const Text(
                'Please pull down to refresh or check your connection.',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.textSecondaryLight, fontSize: 13),
              ),
            ],
          ),
        ),
      ),
    );
  };

  try {
    final savedBaseUrl = await SecureStorage.getBaseUrl();
    if (savedBaseUrl != null && savedBaseUrl.trim().isNotEmpty) {
      ApiEndpoints.setBaseUrl(savedBaseUrl);
    }
  } catch (e) {
    debugPrint('Storage initialization fallback: $e');
  }

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => LanguageProvider()),
      ],
      child: const IndianAgriExamApp(),
    ),
  );
}

class IndianAgriExamApp extends StatelessWidget {
  const IndianAgriExamApp({super.key});

  @override
  Widget build(BuildContext context) {
    final langProvider = Provider.of<LanguageProvider>(context);

    return MaterialApp(
      title: 'IndianAgriExam',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: ThemeMode.light,
      locale: langProvider.locale,
      supportedLocales: const [
        Locale('en', 'US'),
        Locale('hi', 'IN'),
      ],
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: const SplashScreen(),
    );
  }
}
