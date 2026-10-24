/// REST API Endpoints configuration for IndianAgriExam.
class ApiEndpoints {
  // Configurable base URL (can point to production or local preview sandbox)
  static const String baseUrl = 'https://indianagriexam.com/wp-json/iaq/v1';
  static const String localSandboxUrl = 'http://10.0.2.2:8000/wp-json/iaq/v1'; // Android emulator localhost

  // Active base endpoint (defaults to production)
  static String activeBaseUrl = baseUrl;

  static void setBaseUrl(String url) {
    var clean = url.trim();
    while (clean.endsWith('/')) {
      clean = clean.substring(0, clean.length - 1);
    }
    if (clean.isNotEmpty) {
      activeBaseUrl = clean;
    }
  }

  // 1. Auth
  static String get login => '$activeBaseUrl/auth/login';
  static String get register => '$activeBaseUrl/auth/register';
  static String get forgotPassword => '$activeBaseUrl/auth/forgot-password';
  static String get me => '$activeBaseUrl/auth/me';
  static String get logout => '$activeBaseUrl/auth/logout';

  // 2. Dashboard
  static String get dashboardSummary => '$activeBaseUrl/dashboard/summary';

  // 3. Examinations
  static String get examinations => '$activeBaseUrl/examinations';
  static String examinationDetails(int id) => '$activeBaseUrl/examinations/$id';

  // 4. Practice Sets
  static String get practiceBooks => '$activeBaseUrl/practice-sets/books';
  static String bookSets(int id) => '$activeBaseUrl/practice-sets/books/$id';
  static String get practiceSubjects => '$activeBaseUrl/practice-sets/subjects';
  static String subjectSets(int id) => '$activeBaseUrl/practice-sets/subjects/$id';
  static String examSets(int id) => '$activeBaseUrl/practice-sets/exams/$id';

  // 5. Mock Tests
  static String get mockCategories => '$activeBaseUrl/mock-tests/categories';
  static String get mockHierarchy => '$activeBaseUrl/mock-tests/hierarchy';
  static String mockSeries(int id) => '$activeBaseUrl/mock-tests/series/$id';

  // 6. Quiz CBT Attempt Engine
  static String quizInstructions(int id) => '$activeBaseUrl/quiz/$id/instructions';
  static String startQuiz(int id) => '$activeBaseUrl/quiz/$id/start';
  static String saveAnswer(int attemptId) => '$activeBaseUrl/quiz/attempt/$attemptId/save-answer';
  static String submitQuiz(int attemptId) => '$activeBaseUrl/quiz/attempt/$attemptId/submit';
  static String scorecard(int attemptId) => '$activeBaseUrl/quiz/attempt/$attemptId/scorecard';
  static String solutions(int attemptId) => '$activeBaseUrl/quiz/attempt/$attemptId/solutions';

  // 7. Student Learning Tools
  static String get bookmarks => '$activeBaseUrl/learning/bookmarks';
  static String get toggleBookmark => '$activeBaseUrl/learning/bookmarks/toggle';
  static String get createRevisionQuiz => '$activeBaseUrl/learning/bookmarks/create-quiz';
  static String get wrongNotebook => '$activeBaseUrl/learning/wrong-notebook';
  static String get updateNotebookStatus => '$activeBaseUrl/learning/wrong-notebook/status';
  static String get removeNotebookEntry => '$activeBaseUrl/learning/wrong-notebook/remove';
  static String get createRetryQuiz => '$activeBaseUrl/learning/wrong-notebook/create-quiz';
  static String get customPracticeOptions => '$activeBaseUrl/learning/custom-practice/options';
  static String get createCustomPractice => '$activeBaseUrl/learning/custom-practice/create';
  static String get studyStreaks => '$activeBaseUrl/learning/study-streaks';
  static String get updateGoals => '$activeBaseUrl/learning/goals';
  static String get leaderboard => '$activeBaseUrl/learning/leaderboard';

  // 8. Subscriptions
  static String get subscriptionPlans => '$activeBaseUrl/subscriptions/plans';
  static String get mySubscriptions => '$activeBaseUrl/subscriptions/my-subscriptions';
  static String get checkoutUrl => '$activeBaseUrl/subscriptions/checkout-url';

  // 9. Mobile App Version & Updates
  static String appVersion([String? currentVer]) {
    if (currentVer != null && currentVer.isNotEmpty) {
      return '$activeBaseUrl/app/version?current_version=$currentVer';
    }
    return '$activeBaseUrl/app/version';
  }
}
