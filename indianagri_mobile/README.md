# IndianAgriExam Mobile Application (Flutter)

Production-oriented mobile application for [IndianAgriExam.com](https://indianagriexam.com), built with Flutter and Dart, seamlessly integrated with the custom WordPress **IndianAgri Quiz Engine** plugin.

---

## 1. Branding & Design System

- **Application Name**: IndianAgriExam
- **Primary Color**: `#117E3A` (IndianAgri Forest Green)
- **Secondary / Accent**: `#059669` (Emerald Correct), `#DC2626` (Ruby Mistake), `#7C3AED` (Review Purple)
- **Typography**: Poppins (Google Fonts) with Noto Sans Devanagari fallback for Hindi
- **Bilingual Support**: Dynamic runtime toggle between **English** and **हिन्दी** across all navigation, questions, options, scorecards, and scientific explanations.
- **Design Philosophy**: Academic, mobile-first, distraction-free Computer-Based Test (CBT) environment.

---

## 2. Architecture & Folder Structure

```
indianagri_mobile/
├── lib/
│   ├── main.dart                               # App entry point, MultiProvider & MaterialApp
│   ├── core/
│   │   ├── api/
│   │   │   ├── api_client.dart                 # HTTP client with Bearer Token interceptor & timeouts
│   │   │   └── api_endpoints.dart              # Environment-configurable REST routes
│   │   ├── constants/
│   │   │   ├── app_colors.dart                 # Color palette tokens (#117E3A, status colors)
│   │   │   └── app_styles.dart                 # Border radii, elevations, card decorations
│   │   ├── localization/
│   │   │   └── app_localizations.dart          # English/Hindi dictionary & LanguageProvider
│   │   ├── storage/
│   │   │   └── secure_storage.dart             # Encrypted token storage & session manager
│   │   ├── theme/
│   │   │   └── app_theme.dart                  # Material 3 light & dark themes
│   │   └── widgets/
│   │       ├── app_button.dart                 # Standardized button variants (primary, outline, loading)
│   │       ├── state_views.dart                # Loading, empty, and retry error states
│   │       └── language_toggle.dart            # In-app language switcher widget
│   └── features/
│       ├── auth/                               # AuthProvider, Splash, Login, Register, Forgot Password
│       ├── dashboard/                          # Home KPIs, in-progress resume banner, mastery cards
│       ├── examinations/                       # Active examinations catalogue
│       ├── practice_sets/                      # 3-way browsing: By Books, By Subjects, By Exams
│       ├── mock_tests/                         # 10 Main Categories & dynamic test series hierarchy
│       ├── quiz_cbt/                           # Instructions, Timed CBT Hall, Question Palette, Immediate Feedback
│       ├── results/                            # Scorecards, accuracy, percentile, solutions review
│       ├── learning/                           # Bookmarks, Wrong Question Notebook, Custom Practice, Streaks, Leaderboard
│       ├── subscriptions/                      # Membership plans & Razorpay/WooCommerce checkout
│       └── profile/                            # Student profile, subscription validity, app settings
├── test/
│   └── model_test.dart                         # Unit tests for API endpoints and bilingual localization
├── android/                                    # Android manifest, permissions, deep linking
├── ios/                                        # iOS Runner Info.plist, configuration
└── pubspec.yaml
```

---

## 3. WordPress REST API Endpoints (`/wp-json/iaq/v1/`)

All mobile requests are authenticated via `Authorization: Bearer <token>` against the WordPress backend.

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/iaq/v1/auth/login` | Student login (returns 30-day mobile Bearer token) | No |
| `POST` | `/iaq/v1/auth/register` | Student self-registration | No |
| `POST` | `/iaq/v1/auth/forgot-password` | Password reset email trigger | No |
| `GET`  | `/iaq/v1/auth/me` | Fetch active student profile & entitlements | **Yes** |
| `POST` | `/iaq/v1/auth/logout` | Revoke active mobile token | **Yes** |
| `GET`  | `/iaq/v1/dashboard/summary` | Dashboard KPIs, streaks, active attempt | **Yes** |
| `GET`  | `/iaq/v1/examinations` | List active competitive examinations | No |
| `GET`  | `/iaq/v1/practice-sets/books` | Books catalogue with practice set counts | No |
| `GET`  | `/iaq/v1/practice-sets/subjects` | Subjects catalogue | No |
| `GET`  | `/iaq/v1/practice-sets/exams/{id}` | Exam-subject practice hierarchy | No |
| `GET`  | `/iaq/v1/mock-tests/categories` | 10 Main Mock Categories | No |
| `GET`  | `/iaq/v1/mock-tests/hierarchy` | Category test series & papers (Mock Test 1..N) | No |
| `GET`  | `/iaq/v1/quiz/{id}/instructions` | Examination syllabus, marking, duration & paywall | No |
| `POST` | `/iaq/v1/quiz/{id}/start` | Start/Resume attempt (returns timer, stripped keys for mocks) | **Yes** |
| `POST` | `/iaq/v1/quiz/attempt/{id}/save-answer` | Auto-save answer (returns immediate feedback for Practice) | **Yes** |
| `POST` | `/iaq/v1/quiz/attempt/{id}/submit` | Final submission & server-side evaluation | **Yes** |
| `GET`  | `/iaq/v1/quiz/attempt/{id}/scorecard` | Scorecard, percentile, accuracy, time spent | **Yes** |
| `GET`  | `/iaq/v1/quiz/attempt/{id}/solutions` | Full solutions & scientific explanations | **Yes** |
| `GET`  | `/iaq/v1/learning/bookmarks` | List student's starred questions | **Yes** |
| `POST` | `/iaq/v1/learning/bookmarks/toggle` | Star/unstar question with optional note | **Yes** |
| `POST` | `/iaq/v1/learning/bookmarks/create-quiz` | Assemble revision drill quiz | **Yes** |
| `GET`  | `/iaq/v1/learning/wrong-notebook` | Mistake notebook (active vs. resolved) | **Yes** |
| `POST` | `/iaq/v1/learning/wrong-notebook/status` | Mark mistake as resolved | **Yes** |
| `POST` | `/iaq/v1/learning/wrong-notebook/create-quiz` | Assemble retry drill quiz | **Yes** |
| `GET`  | `/iaq/v1/learning/custom-practice/options` | Question pool availability counts | **Yes** |
| `POST` | `/iaq/v1/learning/custom-practice/create` | Assemble custom filtered quiz | **Yes** |
| `GET`  | `/iaq/v1/learning/study-streaks` | 14-day activity calendar & streak stats | **Yes** |
| `POST` | `/iaq/v1/learning/goals` | Update weekly study goals | **Yes** |
| `GET`  | `/iaq/v1/learning/leaderboard` | National student merit rankings | No |
| `GET`  | `/iaq/v1/subscriptions/plans` | Active membership plans & pricing | No |
| `POST` | `/iaq/v1/subscriptions/checkout-url` | Generate verified WooCommerce/Razorpay checkout URL | **Yes** |

---

## 4. Setup & Running Instructions

### Prerequisites
1. **Flutter SDK** (v3.0.0 or higher) installed on your system.
2. **Android Studio** or **VS Code** with Flutter and Dart extensions.
3. For Android testing: Android device or emulator with API Level 21+.
4. For iOS testing: macOS with Xcode 14+.

### Installation Steps

1. Navigate to the mobile app directory:
   ```bash
   cd indianagri_mobile
   ```

2. Fetch Flutter dependencies:
   ```bash
   flutter pub get
   ```

3. Run the app in development:
   ```bash
   # Run on connected Android device / emulator
   flutter run
   ```

4. Building the Android APK:
   ```bash
   # Build a release APK
   flutter build apk --release

   # The generated APK will be at:
   # build/app/outputs/flutter-apk/app-release.apk
   ```

5. Building for iOS:
   ```bash
   cd ios
   pod install
   cd ..
   flutter build ios --no-codesign
   ```

---

## 5. Environment Configuration

To point the app to a local staging environment (e.g. running on port 8000), update `lib/core/api/api_endpoints.dart`:

```dart
// For local emulator testing:
static const String baseUrl = 'http://10.0.2.2:8000/wp-json/iaq/v1';

// For physical device testing over Wi-Fi:
static const String baseUrl = 'http://192.168.1.X:8000/wp-json/iaq/v1';

// For production:
static const String baseUrl = 'https://indianagriexam.com/wp-json/iaq/v1';
```
