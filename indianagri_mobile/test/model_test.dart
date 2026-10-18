import 'package:flutter_test/flutter_test.dart';
import 'package:indianagri_mobile/core/api/api_endpoints.dart';
import 'package:indianagri_mobile/core/localization/app_localizations.dart';
import 'package:flutter/material.dart';

void main() {
  group('IndianAgriExam Core Model & Endpoint Tests', () {
    test('API Endpoints construct correctly under active base URL', () {
      expect(ApiEndpoints.login, contains('/auth/login'));
      expect(ApiEndpoints.dashboardSummary, contains('/dashboard/summary'));
      expect(ApiEndpoints.startQuiz(101), contains('/quiz/101/start'));
      expect(ApiEndpoints.saveAnswer(55), contains('/quiz/attempt/55/save-answer'));
      expect(ApiEndpoints.scorecard(55), contains('/quiz/attempt/55/scorecard'));
      expect(ApiEndpoints.solutions(55), contains('/quiz/attempt/55/solutions'));
    });

    test('Bilingual dictionary translates English and Hindi terms accurately', () {
      final en = AppLocalizations(const Locale('en'));
      final hi = AppLocalizations(const Locale('hi'));

      expect(en.translate('appName'), equals('IndianAgriExam'));
      expect(hi.translate('appName'), equals('इंडियन एग्री एग्जाम'));
      expect(en.translate('score'), equals('Net Score'));
      expect(hi.translate('score'), equals('कुल अंक'));
      expect(en.translate('correctMessage'), contains('Correct!'));
      expect(hi.translate('correctMessage'), contains('बिल्कुल सही!'));
    });

    test('Language provider toggles locale between en and hi', () {
      final provider = LanguageProvider();
      expect(provider.isHindi, isFalse);

      provider.toggleLanguage();
      expect(provider.isHindi, isTrue);
      expect(provider.locale.languageCode, equals('hi'));

      provider.toggleLanguage();
      expect(provider.isHindi, isFalse);
    });
  });
}
