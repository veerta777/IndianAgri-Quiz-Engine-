import 'package:flutter/material.dart';

/// In-app localization dictionary supporting English and Hindi (Devanagari).
class AppLocalizations {
  final Locale locale;

  AppLocalizations(this.locale);

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations) ??
        AppLocalizations(const Locale('en'));
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  static final Map<String, Map<String, String>> _localizedValues = {
    'en': {
      'appName': 'IndianAgriExam',
      'tagline': 'India\'s #1 Agricultural Examination Preparation Portal',
      'login': 'Log In',
      'register': 'Sign Up',
      'logout': 'Log Out',
      'username': 'Username or Email',
      'password': 'Password',
      'forgotPassword': 'Forgot Password?',
      'resetPassword': 'Reset Password',
      'dashboard': 'Home',
      'practiceSets': 'Practice Sets',
      'mockTests': 'Mock Tests',
      'examinations': 'Examinations',
      'learning': 'My Learning',
      'profile': 'Profile',
      'subscriptions': 'Plans & Pricing',
      'startTest': 'Start Examination',
      'resumeTest': 'Resume Test',
      'nextQuestion': 'Next Question',
      'previousQuestion': 'Previous',
      'clearSelection': 'Clear Choice',
      'markForReview': 'Mark for Review',
      'submitTest': 'Submit Examination',
      'timeRemaining': 'Time Left',
      'questionPalette': 'Question Palette',
      'correctMessage': 'Correct! Excellent work!',
      'incorrectMessage': 'Not quite right this time. Keep going! Review the correct concept below:',
      'scientificExplanation': 'Scientific Explanation',
      'scorecard': 'Scorecard & Performance',
      'viewSolutions': 'View Detailed Solutions',
      'accuracy': 'Accuracy',
      'score': 'Net Score',
      'percentile': 'Cohort Percentile',
      'bookmarks': 'Bookmarks',
      'wrongNotebook': 'Wrong Question Notebook',
      'customPractice': 'Custom Practice Test',
      'studyStreaks': 'Study Streaks',
      'leaderboard': 'Leaderboard',
      'retryQuiz': 'Generate Retry Quiz',
      'revisionQuiz': 'Generate Revision Quiz',
      'retry': 'Retry',
      'loading': 'Loading...',
      'noData': 'No data available at this time.',
      'errorNetwork': 'Network connection issue. Please verify your internet connectivity.',
      'language': 'Language / भाषा',
    },
    'hi': {
      'appName': 'इंडियन एग्री एग्जाम',
      'tagline': 'कृषि प्रतियोगी परीक्षाओं की तैयारी का सर्वश्रेष्ठ मंच',
      'login': 'लॉग इन करें',
      'register': 'रजिस्टर करें',
      'logout': 'लॉग आउट',
      'username': 'यूज़रनेम या ईमेल',
      'password': 'पासवर्ड',
      'forgotPassword': 'पासवर्ड भूल गए?',
      'resetPassword': 'पासवर्ड रीसेट करें',
      'dashboard': 'होम',
      'practiceSets': 'प्रैक्टिस सेट्स',
      'mockTests': 'मॉक टेस्ट्स',
      'examinations': 'परीक्षाएं',
      'learning': 'मेरी पढ़ाई',
      'profile': 'प्रोफ़ाइल',
      'subscriptions': 'सदस्यता प्लान्स',
      'startTest': 'परीक्षा शुरू करें',
      'resumeTest': 'पुनः जारी रखें',
      'nextQuestion': 'अगला प्रश्न',
      'previousQuestion': 'पिछला प्रश्न',
      'clearSelection': 'चयन हटाएं',
      'markForReview': 'रिव्यू के लिए मार्क करें',
      'submitTest': 'परीक्षा सबमिट करें',
      'timeRemaining': 'शेष समय',
      'questionPalette': 'प्रश्न पैलेट',
      'correctMessage': 'बिल्कुल सही! शानदार प्रयास!',
      'incorrectMessage': 'यह उत्तर सही नहीं है। निराश न हों! सही अवधारणा नीचे देखें:',
      'scientificExplanation': 'वैज्ञानिक व्याख्या एवं संदर्भ',
      'scorecard': 'स्कोरकार्ड एवं विश्लेषण',
      'viewSolutions': 'विस्तृत हल देखें',
      'accuracy': 'सटीकता',
      'score': 'कुल अंक',
      'percentile': 'पर्सेंटाइल',
      'bookmarks': 'बुकमार्क प्रश्न',
      'wrongNotebook': 'गलत प्रश्नों की नोटबुक',
      'customPractice': 'कस्टम प्रैक्टिस टेस्ट',
      'studyStreaks': 'अध्ययन स्ट्रीक',
      'leaderboard': 'लीडरबोर्ड',
      'retryQuiz': 'रीट्राई क्विज बनाएं',
      'revisionQuiz': 'रिवीजन क्विज बनाएं',
      'retry': 'पुनः प्रयास करें',
      'loading': 'लोड हो रहा है...',
      'noData': 'वर्तमान में कोई डेटा उपलब्ध नहीं है।',
      'errorNetwork': 'नेटवर्क कनेक्शन में समस्या है। कृपया इंटरनेट जांचें।',
      'language': 'भाषा / Language',
    },
  };

  String translate(String key) {
    final langCode = locale.languageCode;
    return _localizedValues[langCode]?[key] ?? _localizedValues['en']?[key] ?? key;
  }
}

class _AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) => ['en', 'hi'].contains(locale.languageCode);

  @override
  Future<AppLocalizations> load(Locale locale) async {
    return AppLocalizations(locale);
  }

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

/// Provider to handle dynamic in-app language switching between English and Hindi.
class LanguageProvider extends ChangeNotifier {
  Locale _locale = const Locale('en');

  Locale get locale => _locale;
  bool get isHindi => _locale.languageCode == 'hi';

  void toggleLanguage() {
    _locale = _locale.languageCode == 'en' ? const Locale('hi') : const Locale('en');
    notifyListeners();
  }

  void setLanguage(String code) {
    _locale = Locale(code);
    notifyListeners();
  }
}
