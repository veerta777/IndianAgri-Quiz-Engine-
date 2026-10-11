<?php
/**
 * Database installer, schema manager, and migration handler.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Database
 */
class IAQ_Database {

	/**
	 * Get table names.
	 *
	 * @param string $table Short table key.
	 * @return string Full table name with prefix.
	 */
	public static function get_table_name( $table ) {
		global $wpdb;

		$tables = array(
			'subjects'           => $wpdb->prefix . 'iaq_subjects',
			'topics'             => $wpdb->prefix . 'iaq_topics',
			'examinations'       => $wpdb->prefix . 'iaq_examinations',
			'questions'          => $wpdb->prefix . 'iaq_questions',
			'quizzes'            => $wpdb->prefix . 'iaq_quizzes',
			'quiz_questions'     => $wpdb->prefix . 'iaq_quiz_questions',
			'quiz_attempts'      => $wpdb->prefix . 'iaq_quiz_attempts',
			'attempt_answers'    => $wpdb->prefix . 'iaq_attempt_answers',
			'subscription_plans' => $wpdb->prefix . 'iaq_subscription_plans',
			'user_subscriptions' => $wpdb->prefix . 'iaq_user_subscriptions',
			'bookmarks'            => $wpdb->prefix . 'iaq_bookmarks',
			'wrong_notebook'       => $wpdb->prefix . 'iaq_wrong_notebook',
			'study_activity'       => $wpdb->prefix . 'iaq_study_activity',
			'books'                => $wpdb->prefix . 'iaq_books',
			'book_quizzes'         => $wpdb->prefix . 'iaq_book_quizzes',
			'exam_subjects'        => $wpdb->prefix . 'iaq_exam_subjects',
			'exam_subject_quizzes' => $wpdb->prefix . 'iaq_exam_subject_quizzes',
			'mock_categories'          => $wpdb->prefix . 'iaq_mock_categories',
			'mock_series'              => $wpdb->prefix . 'iaq_mock_series',
			'mock_vacancies'           => $wpdb->prefix . 'iaq_mock_vacancies',
			'mock_states_universities' => $wpdb->prefix . 'iaq_mock_states_universities',
			'mock_test_relations'      => $wpdb->prefix . 'iaq_mock_test_relations',
		);

		return isset( $tables[ $table ] ) ? $tables[ $table ] : '';
	}

	/**
	 * Create or upgrade all custom plugin database tables using dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_subjects           = self::get_table_name( 'subjects' );
		$table_topics             = self::get_table_name( 'topics' );
		$table_examinations       = self::get_table_name( 'examinations' );
		$table_questions          = self::get_table_name( 'questions' );
		$table_quizzes            = self::get_table_name( 'quizzes' );
		$table_quiz_questions     = self::get_table_name( 'quiz_questions' );
		$table_quiz_attempts      = self::get_table_name( 'quiz_attempts' );
		$table_attempt_answers    = self::get_table_name( 'attempt_answers' );
		$table_subscription_plans = self::get_table_name( 'subscription_plans' );
		$table_user_subscriptions = self::get_table_name( 'user_subscriptions' );
		$table_bookmarks          = self::get_table_name( 'bookmarks' );
		$table_wrong_notebook     = self::get_table_name( 'wrong_notebook' );
		$table_study_activity     = self::get_table_name( 'study_activity' );
		$table_books              = self::get_table_name( 'books' );
		$table_book_quizzes       = self::get_table_name( 'book_quizzes' );
		$table_exam_subjects      = self::get_table_name( 'exam_subjects' );
		$table_exam_subject_quizzes = self::get_table_name( 'exam_subject_quizzes' );
		$table_mock_categories      = self::get_table_name( 'mock_categories' );
		$table_mock_series          = self::get_table_name( 'mock_series' );
		$table_mock_vacancies       = self::get_table_name( 'mock_vacancies' );
		$table_mock_states_univs    = self::get_table_name( 'mock_states_universities' );
		$table_mock_test_relations  = self::get_table_name( 'mock_test_relations' );

		// dbDelta requires specific syntax (2 spaces after PRIMARY KEY, lowercase types, uppercase keywords).
		$sql = "
CREATE TABLE {$table_subjects} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(191) NOT NULL,
  slug varchar(191) NOT NULL,
  description text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_slug (slug)
) {$charset_collate};

CREATE TABLE {$table_topics} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  subject_id bigint(20) unsigned NOT NULL,
  name varchar(191) NOT NULL,
  slug varchar(191) NOT NULL,
  description text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_subject_id (subject_id),
  KEY idx_slug (slug)
) {$charset_collate};

CREATE TABLE {$table_examinations} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(191) NOT NULL,
  slug varchar(191) NOT NULL,
  code varchar(50) DEFAULT NULL,
  description text DEFAULT NULL,
  instructions longtext DEFAULT NULL,
  category_id bigint(20) unsigned DEFAULT NULL,
  parent_id bigint(20) unsigned DEFAULT NULL,
  state_id bigint(20) unsigned DEFAULT NULL,
  university_id bigint(20) unsigned DEFAULT NULL,
  display_order int(11) NOT NULL DEFAULT 0,
  status enum('active','inactive','archived') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_category (category_id),
  KEY idx_parent (parent_id),
  KEY idx_status (status)
) {$charset_collate};

CREATE TABLE {$table_questions} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  subject_id bigint(20) unsigned NOT NULL,
  topic_id bigint(20) unsigned DEFAULT NULL,
  exam_category varchar(100) NOT NULL DEFAULT 'General',
  difficulty enum('easy','medium','hard') NOT NULL DEFAULT 'medium',
  question_text longtext NOT NULL,
  option_a longtext NOT NULL,
  option_b longtext NOT NULL,
  option_c longtext NOT NULL,
  option_d longtext NOT NULL,
  correct_option char(1) NOT NULL,
  marks decimal(5,2) NOT NULL DEFAULT 1.00,
  negative_marks decimal(5,2) NOT NULL DEFAULT 0.00,
  explanation longtext DEFAULT NULL,
  status enum('draft','published','archived') NOT NULL DEFAULT 'published',
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_subject (subject_id),
  KEY idx_topic (topic_id),
  KEY idx_exam_category (exam_category),
  KEY idx_difficulty (difficulty),
  KEY idx_status (status)
) {$charset_collate};

CREATE TABLE {$table_quizzes} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  slug varchar(191) NOT NULL,
  description longtext DEFAULT NULL,
  quiz_type enum('practice','mock','previous_year') NOT NULL DEFAULT 'practice',
  subject_id bigint(20) unsigned DEFAULT NULL,
  topic_id bigint(20) unsigned DEFAULT NULL,
  exam_category varchar(100) NOT NULL DEFAULT 'General',
  total_questions int(11) NOT NULL DEFAULT 0,
  total_marks decimal(7,2) NOT NULL DEFAULT 0.00,
  duration_minutes int(11) NOT NULL DEFAULT 0,
  correct_marks decimal(5,2) NOT NULL DEFAULT 1.00,
  negative_marks decimal(5,2) NOT NULL DEFAULT 0.25,
  pass_percentage decimal(5,2) NOT NULL DEFAULT 40.00,
  shuffle_questions tinyint(1) NOT NULL DEFAULT 0,
  shuffle_options tinyint(1) NOT NULL DEFAULT 0,
  max_attempts int(11) NOT NULL DEFAULT 0,
  access_type enum('free','paid') NOT NULL DEFAULT 'free',
  wc_product_id bigint(20) unsigned DEFAULT NULL,
  instructions longtext DEFAULT NULL,
  show_answers_mode enum('immediate','after_submission','never') NOT NULL DEFAULT 'after_submission',
  pyq_year int(4) DEFAULT NULL,
  pyq_session varchar(50) DEFAULT NULL,
  pdf_attachment_url varchar(255) DEFAULT NULL,
  start_date datetime DEFAULT NULL,
  end_date datetime DEFAULT NULL,
  is_custom tinyint(1) NOT NULL DEFAULT 0,
  display_order int(11) NOT NULL DEFAULT 0,
  status enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_quiz_type (quiz_type),
  KEY idx_access_type (access_type),
  KEY idx_wc_product (wc_product_id),
  KEY idx_status (status)
) {$charset_collate};

CREATE TABLE {$table_quiz_questions} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  quiz_id bigint(20) unsigned NOT NULL,
  question_id bigint(20) unsigned NOT NULL,
  question_order int(11) NOT NULL DEFAULT 0,
  custom_marks decimal(5,2) DEFAULT NULL,
  custom_negative_marks decimal(5,2) DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_quiz_question (quiz_id,question_id),
  KEY idx_quiz_id (quiz_id),
  KEY idx_question_id (question_id)
) {$charset_collate};

CREATE TABLE {$table_quiz_attempts} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  quiz_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  attempt_number int(11) NOT NULL DEFAULT 1,
  status enum('in_progress','completed','abandoned','timed_out') NOT NULL DEFAULT 'in_progress',
  start_time datetime NOT NULL,
  end_time datetime DEFAULT NULL,
  time_spent_seconds int(11) NOT NULL DEFAULT 0,
  total_questions int(11) NOT NULL DEFAULT 0,
  attempted_questions int(11) NOT NULL DEFAULT 0,
  correct_answers int(11) NOT NULL DEFAULT 0,
  incorrect_answers int(11) NOT NULL DEFAULT 0,
  unattempted_questions int(11) NOT NULL DEFAULT 0,
  score decimal(7,2) NOT NULL DEFAULT 0.00,
  total_marks decimal(7,2) NOT NULL DEFAULT 0.00,
  percentage decimal(5,2) NOT NULL DEFAULT 0.00,
  passed tinyint(1) NOT NULL DEFAULT 0,
  ip_address varchar(45) DEFAULT NULL,
  user_agent varchar(255) DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_quiz_user (quiz_id,user_id),
  KEY idx_status (status),
  KEY idx_user_id (user_id)
) {$charset_collate};

CREATE TABLE {$table_attempt_answers} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  attempt_id bigint(20) unsigned NOT NULL,
  question_id bigint(20) unsigned NOT NULL,
  selected_option char(1) DEFAULT NULL,
  is_correct tinyint(1) DEFAULT NULL,
  marks_awarded decimal(5,2) NOT NULL DEFAULT 0.00,
  is_marked_for_review tinyint(1) NOT NULL DEFAULT 0,
  answered_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_attempt_question (attempt_id,question_id),
  KEY idx_attempt_id (attempt_id),
  KEY idx_question_id (question_id)
) {$charset_collate};

CREATE TABLE {$table_subscription_plans} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(191) NOT NULL,
  slug varchar(191) NOT NULL,
  description text DEFAULT NULL,
  price decimal(7,2) NOT NULL DEFAULT 0.00,
  currency varchar(10) NOT NULL DEFAULT 'INR',
  duration_value int(11) NOT NULL DEFAULT 1,
  duration_unit enum('days','months','years') NOT NULL DEFAULT 'months',
  access_type enum('all_access','exam_specific') NOT NULL DEFAULT 'all_access',
  included_exam_ids text DEFAULT NULL,
  wc_product_id bigint(20) unsigned DEFAULT NULL,
  is_featured tinyint(1) NOT NULL DEFAULT 0,
  display_order int(11) NOT NULL DEFAULT 0,
  features text DEFAULT NULL,
  status enum('active','inactive','archived') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_access_type (access_type),
  KEY idx_wc_product (wc_product_id),
  KEY idx_status (status)
) {$charset_collate};

CREATE TABLE {$table_user_subscriptions} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  plan_id bigint(20) unsigned NOT NULL,
  plan_name_snapshot varchar(191) NOT NULL,
  price_snapshot decimal(7,2) NOT NULL DEFAULT 0.00,
  currency_snapshot varchar(10) NOT NULL DEFAULT 'INR',
  access_type_snapshot enum('all_access','exam_specific') NOT NULL DEFAULT 'all_access',
  included_exam_ids_snapshot text DEFAULT NULL,
  start_date datetime NOT NULL,
  expiry_date datetime NOT NULL,
  wc_order_id bigint(20) unsigned DEFAULT NULL,
  payment_status varchar(50) NOT NULL DEFAULT 'completed',
  status enum('active','expired','cancelled','refunded') NOT NULL DEFAULT 'active',
  notes text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_user_id (user_id),
  KEY idx_plan_id (plan_id),
  KEY idx_wc_order (wc_order_id),
  KEY idx_status (status),
  KEY idx_expiry (expiry_date)
) {$charset_collate};

CREATE TABLE {$table_bookmarks} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  question_id bigint(20) unsigned NOT NULL,
  notes text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_user_question (user_id,question_id),
  KEY idx_user_id (user_id),
  KEY idx_question_id (question_id)
) {$charset_collate};

CREATE TABLE {$table_wrong_notebook} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  question_id bigint(20) unsigned NOT NULL,
  last_attempt_id bigint(20) unsigned NOT NULL DEFAULT 0,
  times_incorrect int(11) NOT NULL DEFAULT 1,
  status enum('active','resolved','dismissed') NOT NULL DEFAULT 'active',
  notes text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_user_question (user_id,question_id),
  KEY idx_user_status (user_id,status),
  KEY idx_question_id (question_id)
) {$charset_collate};

CREATE TABLE {$table_study_activity} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  activity_date date NOT NULL,
  activity_type varchar(50) NOT NULL DEFAULT 'quiz_completed',
  reference_id bigint(20) unsigned NOT NULL DEFAULT 0,
  questions_solved int(11) NOT NULL DEFAULT 0,
  time_spent_seconds int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_user_date_ref (user_id,activity_date,reference_id),
  KEY idx_user_date (user_id,activity_date)
) {$charset_collate};

CREATE TABLE {$table_books} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  slug varchar(191) NOT NULL,
  author varchar(191) DEFAULT NULL,
  description text DEFAULT NULL,
  cover_image_url varchar(255) DEFAULT NULL,
  subject_id bigint(20) unsigned DEFAULT NULL,
  examination_id bigint(20) unsigned DEFAULT NULL,
  access_type enum('free','paid') NOT NULL DEFAULT 'free',
  display_order int(11) NOT NULL DEFAULT 0,
  status enum('draft','published','archived') NOT NULL DEFAULT 'published',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_status (status),
  KEY idx_subject (subject_id),
  KEY idx_exam (examination_id)
) {$charset_collate};

CREATE TABLE {$table_book_quizzes} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  book_id bigint(20) unsigned NOT NULL,
  quiz_id bigint(20) unsigned NOT NULL,
  sort_order int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_book_quiz (book_id,quiz_id),
  KEY idx_book_id (book_id),
  KEY idx_quiz_id (quiz_id),
  KEY idx_sort_order (sort_order)
) {$charset_collate};

CREATE TABLE {$table_exam_subjects} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  exam_id bigint(20) unsigned NOT NULL,
  subject_id bigint(20) unsigned NOT NULL,
  display_order int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_exam_subject (exam_id,subject_id),
  KEY idx_exam_id (exam_id),
  KEY idx_subject_id (subject_id),
  KEY idx_display_order (display_order)
) {$charset_collate};

CREATE TABLE {$table_exam_subject_quizzes} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  exam_id bigint(20) unsigned NOT NULL,
  subject_id bigint(20) unsigned NOT NULL,
  quiz_id bigint(20) unsigned NOT NULL,
  sort_order int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_exam_subj_quiz (exam_id,subject_id,quiz_id),
  KEY idx_exam_subj (exam_id,subject_id),
  KEY idx_quiz_id (quiz_id),
  KEY idx_sort_order (sort_order)
) {$charset_collate};

CREATE TABLE {$table_mock_categories} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(191) NOT NULL,
  slug varchar(191) NOT NULL,
  code varchar(50) DEFAULT NULL,
  description text DEFAULT NULL,
  icon varchar(100) DEFAULT 'dashicons-awards',
  badge_text varchar(50) DEFAULT NULL,
  display_order int(11) NOT NULL DEFAULT 0,
  status enum('active','inactive') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_display_order (display_order),
  KEY idx_status (status)
) {$charset_collate};

CREATE TABLE {$table_mock_series} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  category_id bigint(20) unsigned NOT NULL,
  exam_id bigint(20) unsigned DEFAULT NULL,
  subject_id bigint(20) unsigned DEFAULT NULL,
  title varchar(255) NOT NULL,
  slug varchar(191) NOT NULL,
  description text DEFAULT NULL,
  total_tests int(11) NOT NULL DEFAULT 0,
  access_type enum('free','paid') NOT NULL DEFAULT 'free',
  display_order int(11) NOT NULL DEFAULT 0,
  status enum('draft','published','archived') NOT NULL DEFAULT 'published',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_category (category_id),
  KEY idx_exam (exam_id),
  KEY idx_subject (subject_id)
) {$charset_collate};

CREATE TABLE {$table_mock_vacancies} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  exam_id bigint(20) unsigned NOT NULL,
  title varchar(191) NOT NULL,
  slug varchar(191) NOT NULL,
  description text DEFAULT NULL,
  posts_count int(11) NOT NULL DEFAULT 0,
  display_order int(11) NOT NULL DEFAULT 0,
  status enum('active','inactive') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_exam_id (exam_id),
  KEY idx_status (status)
) {$charset_collate};

CREATE TABLE {$table_mock_states_univs} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  entity_type enum('state','university','type') NOT NULL DEFAULT 'state',
  name varchar(191) NOT NULL,
  slug varchar(191) NOT NULL,
  code varchar(50) DEFAULT NULL,
  description text DEFAULT NULL,
  display_order int(11) NOT NULL DEFAULT 0,
  status enum('active','inactive') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY uq_type_slug (entity_type,slug),
  KEY idx_entity_type (entity_type),
  KEY idx_status (status)
) {$charset_collate};

CREATE TABLE {$table_mock_test_relations} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  quiz_id bigint(20) unsigned NOT NULL,
  category_id bigint(20) unsigned NOT NULL,
  exam_id bigint(20) unsigned DEFAULT NULL,
  sub_exam_id bigint(20) unsigned DEFAULT NULL,
  subject_id bigint(20) unsigned DEFAULT NULL,
  vacancy_id bigint(20) unsigned DEFAULT NULL,
  state_id bigint(20) unsigned DEFAULT NULL,
  university_id bigint(20) unsigned DEFAULT NULL,
  series_id bigint(20) unsigned DEFAULT NULL,
  sort_order int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_quiz_id (quiz_id),
  KEY idx_category_id (category_id),
  KEY idx_exam_id (exam_id),
  KEY idx_sub_exam_id (sub_exam_id),
  KEY idx_subject_id (subject_id),
  KEY idx_vacancy_id (vacancy_id),
  KEY idx_state_id (state_id),
  KEY idx_series_id (series_id),
  KEY idx_sort_order (sort_order)
) {$charset_collate};
";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $sql );

		update_option( 'iaq_db_version', IAQ_DB_VERSION );
	}

	/**
	 * Seed initial default subjects, topics, examinations, and subscription plans.
	 */
	public static function seed_default_data() {
		global $wpdb;

		$table_subjects     = self::get_table_name( 'subjects' );
		$table_topics       = self::get_table_name( 'topics' );
		$table_examinations = self::get_table_name( 'examinations' );
		$table_plans        = self::get_table_name( 'subscription_plans' );

		// 1. Seed Subjects and Topics if subjects table is empty.
		$existing_subjects = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_subjects}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( 0 === $existing_subjects ) {
			$seed_data = array(
				'Agronomy' => array(
					'description' => 'Principles of Agronomy, Meteorology, Weed Management, Water Management, and Cropping Systems.',
					'topics'      => array( 'Crop Production & Agro-techniques', 'Weed Science & Management', 'Water Management & Irrigation', 'Dryland Agriculture', 'Farming Systems & Sustainable Agriculture' ),
				),
				'Soil Science & Agricultural Chemistry' => array(
					'description' => 'Soil Genesis, Physical and Chemical Properties, Fertility, and Nutrient Management.',
					'topics'      => array( 'Soil Physics & Mineralogy', 'Soil Fertility & Plant Nutrition', 'Problematic Soils & Reclamation', 'Manures, Fertilizers & Biofertilizers' ),
				),
				'Genetics & Plant Breeding' => array(
					'description' => 'Mendelian Genetics, Plant Breeding Methods, Cytogenetics, and Biotechnology.',
					'topics'      => array( 'Principles of Genetics', 'Breeding Methods of Self & Cross-pollinated Crops', 'Heterosis & Polyploidy', 'Seed Technology & IPR' ),
				),
				'Horticulture' => array(
					'description' => 'Pomology, Olericulture, Floriculture, and Post-Harvest Technology.',
					'topics'      => array( 'Fruit Science (Pomology)', 'Vegetable Science (Olericulture)', 'Floriculture & Landscaping', 'Post-Harvest Management & Processing' ),
				),
				'Plant Pathology' => array(
					'description' => 'Fungal, Bacterial, and Viral diseases of crop plants and integrated disease management.',
					'topics'      => array( 'Mycology & Plant Diseases', 'Bacteriology & Virology', 'Diseases of Field & Horticultural Crops', 'Fungicides & Integrated Disease Management' ),
				),
				'Agricultural Entomology' => array(
					'description' => 'Insect Morphology, Taxonomy, Pests of Crops and Integrated Pest Management (IPM).',
					'topics'      => array( 'Insect Taxonomy & Anatomy', 'Field Crop Pests & Storage Pests', 'Biological Control & IPM', 'Toxicology of Insecticides' ),
				),
				'Agricultural Economics' => array(
					'description' => 'Farm Management, Agricultural Marketing, Farm Finance, and Production Economics.',
					'topics'      => array( 'Principles of Economics & Farm Management', 'Agricultural Finance & Co-operation', 'Agricultural Marketing & Price Analysis', 'Agri-Business Management' ),
				),
				'Agricultural Extension Education' => array(
					'description' => 'Extension Program Planning, Rural Sociology, Communication, and ICT in Agriculture.',
					'topics'      => array( 'Extension Methodologies & Teaching', 'Rural Development Programs in India', 'Communication & Transfer of Technology' ),
				),
				'General Agriculture & Current Affairs' => array(
					'description' => 'Latest agricultural statistics, schemes, ICAR institutes, MSP, and national data.',
					'topics'      => array( 'Government Schemes & Policies', 'Agricultural Statistics & Data', 'ICAR & Allied Research Institutes', 'Recent Developments & Agritech' ),
				),
			);

			foreach ( $seed_data as $subj_name => $subj_info ) {
				$subj_slug = sanitize_title( $subj_name );
				$wpdb->insert(
					$table_subjects,
					array(
						'name'        => $subj_name,
						'slug'        => $subj_slug,
						'description' => $subj_info['description'],
						'created_at'  => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s' )
				);

				$subject_id = (int) $wpdb->insert_id;
				if ( $subject_id && ! empty( $subj_info['topics'] ) ) {
					foreach ( $subj_info['topics'] as $topic_name ) {
						$wpdb->insert(
							$table_topics,
							array(
								'subject_id'  => $subject_id,
								'name'        => $topic_name,
								'slug'        => sanitize_title( $topic_name ),
								'description' => '',
								'created_at'  => current_time( 'mysql' ),
							),
							array( '%d', '%s', '%s', '%s', '%s' )
						);
					}
				}
			}
		}

		// 2. Seed Default Examinations if examinations table is empty.
		$existing_exams = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_examinations}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( 0 === $existing_exams ) {
			$default_exams = array(
				array( 'name' => 'ICAR AIEEA (UG / PG)', 'code' => 'ICAR-AIEEA', 'desc' => 'All India Entrance Examination for Admission in Agriculture' ),
				array( 'name' => 'ICAR JRF / SRF / NET', 'code' => 'ICAR-JRF', 'desc' => 'Junior & Senior Research Fellowship and National Eligibility Test' ),
				array( 'name' => 'ASRB NET Agronomy', 'code' => 'ASRB-AGRO', 'desc' => 'Agricultural Scientists Recruitment Board NET - Agronomy' ),
				array( 'name' => 'ASRB NET Agricultural Extension', 'code' => 'ASRB-EXT', 'desc' => 'Agricultural Scientists Recruitment Board NET - Agricultural Extension' ),
				array( 'name' => 'UPCATET PG', 'code' => 'UPCATET-PG', 'desc' => 'Uttar Pradesh Combined Agriculture and Technology Entrance Test' ),
				array( 'name' => 'CUET PG Agriculture', 'code' => 'CUET-PG', 'desc' => 'Common University Entrance Test PG - Agricultural Sciences' ),
				array( 'name' => 'State Agriculture PSC (ADO / ADA / AEO)', 'code' => 'STATE-PSC', 'desc' => 'State Public Service Commission Agricultural Officer Examinations' ),
				array( 'name' => 'IBPS AFO (Agricultural Field Officer)', 'code' => 'IBPS-AFO', 'desc' => 'Institute of Banking Personnel Selection - Specialist Officer Agriculture' ),
				array( 'name' => 'General Agriculture Mock Examination', 'code' => 'GEN-AGRI', 'desc' => 'Comprehensive practice mock tests for all competitive agricultural examinations' ),
			);

			$order = 1;
			foreach ( $default_exams as $ex ) {
				$wpdb->insert(
					$table_examinations,
					array(
						'name'          => $ex['name'],
						'slug'          => sanitize_title( $ex['name'] ),
						'code'          => $ex['code'],
						'description'   => $ex['desc'],
						'instructions'  => 'Attempt all questions in accordance with examination guidelines. Negative marking applies where specified.',
						'display_order' => $order++,
						'status'        => 'active',
						'created_at'    => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
				);
			}
		}

		// 3. Seed Default Subscription Plans if not already existing.
		$default_plans = array(
			array(
				'name'           => 'Monthly All Access',
				'slug'           => 'monthly-all-access',
				'description'    => 'Full 30-day access to all premium mock examinations, practice sets, and detailed scientific solutions.',
				'price'          => 29.00,
				'duration_value' => 1,
				'duration_unit'  => 'months',
				'access_type'    => 'all_access',
				'is_featured'    => 0,
				'display_order'  => 1,
				'features'       => "Unlimited Mock Test Attempts\nComplete Question Explanations in Hindi & English\nSubject & Topic Mastery Diagnostics\nPrintable Performance Scorecards",
			),
			array(
				'name'           => 'Quarterly All Access',
				'slug'           => 'quarterly-all-access',
				'description'    => '3 calendar months of unlimited test series preparation across all agricultural disciplines.',
				'price'          => 79.00,
				'duration_value' => 3,
				'duration_unit'  => 'months',
				'access_type'    => 'all_access',
				'is_featured'    => 0,
				'display_order'  => 2,
				'features'       => "3 Months Unlimited Access\nFull Question Bank Explanations\nSubject Mastery Analytics\nPrevious Year Question Papers (PYQs)",
			),
			array(
				'name'           => 'Six-Month All Access',
				'slug'           => 'six-month-all-access',
				'description'    => 'Comprehensive 6-month competitive exam subscription. Best for semester-round preparation.',
				'price'          => 149.00,
				'duration_value' => 6,
				'duration_unit'  => 'months',
				'access_type'    => 'all_access',
				'is_featured'    => 1,
				'display_order'  => 3,
				'features'       => "Most Popular for Semester & Entrance Prep\nAll Competitive Examinations Included\nUnlimited Examination Attempts\nPriority Doubt Clarification & Solutions",
			),
			array(
				'name'           => 'Yearly All Access',
				'slug'           => 'yearly-all-access',
				'description'    => 'Full 365-day access to every mock examination, past papers, and upcoming tests for 1 calendar year.',
				'price'          => 249.00,
				'duration_value' => 1,
				'duration_unit'  => 'years',
				'access_type'    => 'all_access',
				'is_featured'    => 0,
				'display_order'  => 4,
				'features'       => "Complete 1-Year Comprehensive Access\nICAR JRF, ASRB NET, UPCATET, CUET Included\nAll New Test Series Added Throughout the Year\nOffline Printable Solutions & Analysis",
			),
		);

		foreach ( $default_plans as $pl ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table_plans} WHERE slug = %s", $pl['slug'] )
			);
			if ( ! $exists ) {
				$wpdb->insert(
					$table_plans,
					array(
						'name'           => $pl['name'],
						'slug'           => $pl['slug'],
						'description'    => $pl['description'],
						'price'          => $pl['price'],
						'currency'       => 'INR',
						'duration_value' => $pl['duration_value'],
						'duration_unit'  => $pl['duration_unit'],
						'access_type'    => $pl['access_type'],
						'is_featured'    => $pl['is_featured'],
						'display_order'  => $pl['display_order'],
						'features'       => $pl['features'],
						'status'         => 'active',
						'created_at'     => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
				);
			}
		}

		// 5. Seed Default Agricultural Books and Exam-Subject links if empty.
		$table_books         = self::get_table_name( 'books' );
		$table_exam_subjects = self::get_table_name( 'exam_subjects' );
		$existing_books      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_books}" );
		if ( 0 === $existing_books ) {
			$default_books = array(
				array(
					'title'           => 'A Competitive Book of Agriculture',
					'slug'            => 'competitive-book-of-agriculture',
					'author'          => 'Nem Raj Sundha',
					'description'     => 'Comprehensive handbook covering General Agriculture, Agronomy, Soil Science, Genetics and allied subjects for ICAR, JRF, and State PSCs.',
					'access_type'     => 'free',
					'display_order'   => 1,
					'status'          => 'published',
				),
				array(
					'title'           => 'Fundamentals of Agriculture (Vol 1 & 2)',
					'slug'            => 'fundamentals-of-agriculture',
					'author'          => 'Dr. Arun Katyayan',
					'description'     => 'In-depth conceptual study reference for Agronomy, Soil Science, Agricultural Extension, and Crop Physiology.',
					'access_type'     => 'free',
					'display_order'   => 2,
					'status'          => 'published',
				),
				array(
					'title'           => 'Soil Science at a Glance',
					'slug'            => 'soil-science-at-a-glance',
					'author'          => 'Dr. D.K. Das',
					'description'     => 'Specialized objective review handbook for Soil Physics, Soil Chemistry, Fertility and Nutrient Management.',
					'access_type'     => 'paid',
					'display_order'   => 3,
					'status'          => 'published',
				),
			);

			foreach ( $default_books as $b ) {
				$wpdb->insert(
					$table_books,
					array(
						'title'         => $b['title'],
						'slug'          => $b['slug'],
						'author'        => $b['author'],
						'description'   => $b['description'],
						'access_type'   => $b['access_type'],
						'display_order' => $b['display_order'],
						'status'        => $b['status'],
						'created_at'    => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
				);
			}
		}

		// 6. Seed Default Exam-Subject relationships if empty.
		$existing_es = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_exam_subjects}" );
		if ( 0 === $existing_es ) {
			$exams_list    = $wpdb->get_results( "SELECT id, name FROM {$table_examinations}" );
			$subjects_list = $wpdb->get_results( "SELECT id, name FROM {$table_subjects}" );

			if ( ! empty( $exams_list ) && ! empty( $subjects_list ) ) {
				$sub_map = array();
				foreach ( $subjects_list as $s ) {
					$sub_map[ $s->name ] = $s->id;
				}

				foreach ( $exams_list as $ex ) {
					$order = 1;
					foreach ( $subjects_list as $s ) {
						// Assign primary agricultural subjects to top examinations.
						$wpdb->insert(
							$table_exam_subjects,
							array(
								'exam_id'       => $ex->id,
								'subject_id'    => $s->id,
								'display_order' => $order++,
								'created_at'    => current_time( 'mysql' ),
							),
							array( '%d', '%d', '%d', '%s' )
						);
					}
				}
			}
		}

		// 7. Seed Default Mock Categories (The 10 Main Categories).
		$table_mock_cats = self::get_table_name( 'mock_categories' );
		$existing_mc     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_mock_cats}" );
		if ( 0 === $existing_mc ) {
			$default_mc = array(
				array(
					'name'          => '1. ICAR Exams',
					'slug'          => 'icar-exams',
					'code'          => 'ICAR',
					'description'   => 'ICAR AIEEA PG / JRF, ICAR Ph.D. / SRF, and ASRB (ARS & NET) Agricultural Mock Tests.',
					'icon'          => 'dashicons-awards',
					'badge_text'    => 'National Entrance',
					'display_order' => 1,
				),
				array(
					'name'          => '2. CUET',
					'slug'          => 'cuet',
					'code'          => 'CUET',
					'description'   => 'Common University Entrance Test Mock Tests for CUET UG and CUET PG Agricultural Sciences.',
					'icon'          => 'dashicons-welcome-learn-more',
					'badge_text'    => 'UG & PG Entrance',
					'display_order' => 2,
				),
				array(
					'name'          => '3. UP Agriculture Exams',
					'slug'          => 'up-agriculture-exams',
					'code'          => 'UP-AGRI',
					'description'   => 'UPPCS, UPSSSC (AGTA, Sugarcane Officer), UPCATET, and other Uttar Pradesh state agricultural exams.',
					'icon'          => 'dashicons-location-alt',
					'badge_text'    => 'UP State Recruitment',
					'display_order' => 3,
				),
				array(
					'name'          => '4. Teaching Exams',
					'slug'          => 'teaching-exams',
					'code'          => 'TEACHING',
					'description'   => 'TGT, PGT, Assistant Professor, and College Lecturer Competitive Agricultural Mock Tests.',
					'icon'          => 'dashicons-welcome-write-blog',
					'badge_text'    => 'TGT / PGT / Faculty',
					'display_order' => 4,
				),
				array(
					'name'          => '5. Banking & AFO',
					'slug'          => 'banking-afo',
					'code'          => 'BANKING',
					'description'   => 'IBPS AFO (Agricultural Field Officer), NABARD Grade A, and Public Sector Bank Agricultural Specialist Officer exams.',
					'icon'          => 'dashicons-chart-line',
					'badge_text'    => 'IBPS / NABARD',
					'display_order' => 5,
				),
				array(
					'name'          => '6. State Agriculture Exams',
					'slug'          => 'state-agriculture-exams',
					'code'          => 'STATE-EXAMS',
					'description'   => 'State PSC and Agricultural Department recruitments: Rajasthan, MP, Bihar, UP, Maharashtra & more.',
					'icon'          => 'dashicons-admin-site-alt3',
					'badge_text'    => 'State PSC & ADO',
					'display_order' => 6,
				),
				array(
					'name'          => '7. University Entrance',
					'slug'          => 'university-entrance',
					'code'          => 'UNIV-ENTRANCE',
					'description'   => 'State Agricultural Universities (SAUs), PG and Ph.D. Entrance Examination Mock Tests.',
					'icon'          => 'dashicons-building',
					'badge_text'    => 'SAUs / PG / Ph.D',
					'display_order' => 7,
				),
				array(
					'name'          => '8. Subject-Wise Mock Tests',
					'slug'          => 'subject-wise-mocks',
					'code'          => 'SUBJECTS',
					'description'   => 'In-depth Subject-Wise Full Length Tests: Agronomy, Soil Science, Horticulture, Pathology, Entomology & more.',
					'icon'          => 'dashicons-category',
					'badge_text'    => '11+ Core Disciplines',
					'display_order' => 8,
				),
				array(
					'name'          => '9. General Agriculture',
					'slug'          => 'general-agriculture',
					'code'          => 'GEN-AGRI',
					'description'   => 'General Agriculture, Agricultural GK, National Agricultural Current Affairs & Mixed Mock Drills.',
					'icon'          => 'dashicons-book-alt',
					'badge_text'    => 'General Drills',
					'display_order' => 9,
				),
				array(
					'name'          => '10. Other Government Exams',
					'slug'          => 'other-govt-exams',
					'code'          => 'OTHER-GOVT',
					'description'   => 'FCI, CCI, National Seeds Corporation (NSC), IFFCO AGT, KRIBHCO and Central Agri PSUs.',
					'icon'          => 'dashicons-groups',
					'badge_text'    => 'PSU & Boards',
					'display_order' => 10,
				),
			);

			foreach ( $default_mc as $cat ) {
				$wpdb->insert(
					$table_mock_cats,
					array(
						'name'          => $cat['name'],
						'slug'          => $cat['slug'],
						'code'          => $cat['code'],
						'description'   => $cat['description'],
						'icon'          => $cat['icon'],
						'badge_text'    => $cat['badge_text'],
						'display_order' => $cat['display_order'],
						'status'        => 'active',
						'created_at'    => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
				);
			}
		}

		// 8. Seed Default States & Universities.
		$table_states_univs = self::get_table_name( 'mock_states_universities' );
		$existing_su        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_states_univs}" );
		if ( 0 === $existing_su ) {
			$default_su = array(
				array( 'type' => 'state', 'name' => 'Rajasthan', 'slug' => 'rajasthan', 'code' => 'RJ', 'order' => 1 ),
				array( 'type' => 'state', 'name' => 'Madhya Pradesh', 'slug' => 'madhya-pradesh', 'code' => 'MP', 'order' => 2 ),
				array( 'type' => 'state', 'name' => 'Bihar', 'slug' => 'bihar', 'code' => 'BR', 'order' => 3 ),
				array( 'type' => 'state', 'name' => 'Uttar Pradesh', 'slug' => 'uttar-pradesh', 'code' => 'UP', 'order' => 4 ),
				array( 'type' => 'state', 'name' => 'Maharashtra', 'slug' => 'maharashtra', 'code' => 'MH', 'order' => 5 ),
				array( 'type' => 'state', 'name' => 'Other States', 'slug' => 'other-states', 'code' => 'OTH', 'order' => 6 ),
				array( 'type' => 'university', 'name' => 'State Agricultural Universities', 'slug' => 'state-agri-universities', 'code' => 'SAU', 'order' => 1 ),
				array( 'type' => 'university', 'name' => 'PG Entrance', 'slug' => 'pg-entrance', 'code' => 'PGE', 'order' => 2 ),
				array( 'type' => 'university', 'name' => 'Ph.D. Entrance', 'slug' => 'phd-entrance', 'code' => 'PHDE', 'order' => 3 ),
				array( 'type' => 'university', 'name' => 'Other University Examinations', 'slug' => 'other-university-exams', 'code' => 'OUE', 'order' => 4 ),
			);
			foreach ( $default_su as $su ) {
				$wpdb->insert(
					$table_states_univs,
					array(
						'entity_type'   => $su['type'],
						'name'          => $su['name'],
						'slug'          => $su['slug'],
						'code'          => $su['code'],
						'display_order' => $su['order'],
						'status'        => 'active',
						'created_at'    => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
				);
			}
		}

		// 9. Seed Default Vacancies (UPSSSC & Posts).
		$table_vacancies = self::get_table_name( 'mock_vacancies' );
		$existing_vac    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_vacancies}" );
		if ( 0 === $existing_vac ) {
			$default_vacancies = array(
				array( 'exam_id' => 1, 'title' => 'Technical Assistant (AGTA)', 'slug' => 'technical-assistant', 'order' => 1 ),
				array( 'exam_id' => 1, 'title' => 'Sugarcane Officer (Ganna Paryavekshak)', 'slug' => 'sugarcane-officer', 'order' => 2 ),
				array( 'exam_id' => 1, 'title' => 'Other Vacancies', 'slug' => 'other-vacancies', 'order' => 3 ),
			);
			foreach ( $default_vacancies as $vac ) {
				$wpdb->insert(
					$table_vacancies,
					array(
						'exam_id'       => $vac['exam_id'],
						'title'         => $vac['title'],
						'slug'          => $vac['slug'],
						'display_order' => $vac['order'],
						'status'        => 'active',
						'created_at'    => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%d', '%s', '%s' )
				);
			}
		}

		// 10. Seed Default Mock Test Series.
		$table_mock_series = self::get_table_name( 'mock_series' );
		$existing_ser      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_mock_series}" );
		if ( 0 === $existing_ser ) {
			$default_series = array(
				array(
					'category_id'   => 1,
					'title'         => 'ICAR AIEEA PG 2026 Full Length Mock Series',
					'slug'          => 'icar-aieea-pg-2026-full-length',
					'description'   => 'Comprehensive 120-question full syllabus mock tests as per latest NTA / ICAR examination blueprint.',
					'total_tests'   => 5,
					'access_type'   => 'free',
					'display_order' => 1,
				),
				array(
					'category_id'   => 8,
					'title'         => 'Agronomy Master Drill Series',
					'slug'          => 'agronomy-master-drill-series',
					'description'   => 'Rigorous topic-by-topic and comprehensive agronomy competitive tests.',
					'total_tests'   => 10,
					'access_type'   => 'paid',
					'display_order' => 2,
				),
			);
			foreach ( $default_series as $ser ) {
				$wpdb->insert(
					$table_mock_series,
					array(
						'category_id'   => $ser['category_id'],
						'title'         => $ser['title'],
						'slug'          => $ser['slug'],
						'description'   => $ser['description'],
						'total_tests'   => $ser['total_tests'],
						'access_type'   => $ser['access_type'],
						'display_order' => $ser['display_order'],
						'status'        => 'published',
						'created_at'    => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
				);
			}
		}
	}
}
