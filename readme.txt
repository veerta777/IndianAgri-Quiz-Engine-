=== IndianAgri Quiz Engine ===
Contributors: indianagriexam
Tags: quiz, mcq, agriculture, mock exam, test engine, elementor, woocommerce
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A secure, high-performance MCQ Question Bank, Quiz Builder, and Mock Test Engine tailored for agricultural competitive examinations.

== Description ==

IndianAgri Quiz Engine is engineered specifically for agricultural competitive examination portals such as IndianAgriExam.com. It provides an enterprise-grade question bank, subject and topic taxonomy management, custom quiz creation, bilingual support (English and Hindi Unicode), and seamless CSV import/export.

### Key Features
* **Dedicated Custom Database Tables:** Bypasses post meta overhead for rapid querying of tens of thousands of MCQs.
* **Agricultural Taxonomy:** Native hierarchical categorization for Subjects (e.g., Agronomy, Soil Science, Horticulture) and Topics.
* **Rich MCQ Parameters:** Question text, 4 options (A, B, C, D), correct option, marking scheme, negative marking, and detailed answer explanations.
* **Quiz Builder Engine:** Build Practice Sets, Mock Tests, and Previous Year Papers with custom time limits, passing percentage, and shuffle rules.
* **Dynamic Question Linker:** Manual question picker with live filters and automated auto-pick tool by topic/difficulty.
* **Full Bilingual Support:** Native UTF-8 Unicode support for Hindi (Devanagari) and English questions.
* **Elementor & WordPress Shortcodes:** Seamlessly embed quizzes using `[iaq_quiz id="123"]` and `[iaq_quiz_list]`.
* **CSV Bulk Import & Export:** Streamlined bulk upload with downloadable sample template and error reporting.
* **Brand Styling:** Elegant UI designed around the brand primary color `#117E3A`.

== Installation ==

1. Upload the `indianagri-quiz-engine.zip` file via **Plugins -> Add Plugin -> Upload Plugin** in your WordPress dashboard.
2. Activate the plugin through the **Plugins** menu.
3. Access **Agri Quiz Engine** in your admin navigation menu.

== Shortcodes ==

* `[iaq_quiz id="123"]`: Embeds the examination card and instructions for a specific quiz.
* `[iaq_quiz_list type="mock" category="ICAR AIEEA"]`: Displays a responsive grid of available examinations.
* `[iaq_practice_sets]`: Responsive portal for Practice Sets categorized by Books, Subjects, and Exams.
* `[iaq_mock_tests]`: Hierarchical Mock Test portal with adaptive category browsing (Central, State, Banking, etc.).
* `[iaq_student_dashboard]`: Student portal showing examination history, active tests, and active subscriptions.
* `[iaq_quiz_results]`: Displays quiz scorecards, multi-subject analytics, and detailed answer keys.
* `[iaq_subscription_plans]`: Displays responsive subscription pricing cards linked to WooCommerce checkout.

== Frequently Asked Questions ==

= Does it support Hindi questions? =
Yes! All database tables use `utf8mb4` character collation, fully supporting Devanagari script and complex Unicode characters.

= Does this interfere with Tutor LMS or WooCommerce? =
No. The plugin uses dedicated custom database tables and independent shortcodes, ensuring zero interference with existing LMS courses while integrating seamlessly with WooCommerce order webhooks.

== Changelog ==

= 1.0.0 =
* Production Release: Complete end-to-end Quiz Engine, 10 custom tables, Question Bank, Subject/Topic taxonomy, Quiz Builder (Practice/Mock/PYQ), CBT attempt engine with server-side anti-cheat stripping, subject mastery scorecards, student dashboard, examination manager, subscription plans, WooCommerce monetization & entitlement provisioning, and comprehensive admin dashboard.
