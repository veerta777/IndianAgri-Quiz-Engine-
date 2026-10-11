<?php
/**
 * Test Suite: Examination Management (Admin CRUD & Operations)
 *
 * @package IndianAgri_Quiz_Engine
 */

class Test_Examinations_Admin {

	public static function run() {
		echo "\nRunning Test_Examinations_Admin...\n";

		self::test_examination_creation_and_validation();
		self::test_duplicate_code_prevention();
		self::test_examination_update();
		self::test_examination_duplication();
		self::test_examination_status_toggle();
		self::test_examination_list_retrieval();

		echo "Test_Examinations_Admin: ALL PASS\n";
	}

	private static function test_examination_creation_and_validation() {
		// 1. Validation test: empty name or code
		$invalid_res = IAQ_Examinations::save_examination(
			array(
				'name' => '',
				'code' => '',
			)
		);
		if ( ! is_wp_error( $invalid_res ) ) {
			throw new Exception( 'Expected validation failure for empty examination name/code.' );
		}

		// 2. Valid creation
		$exam_id = IAQ_Examinations::save_examination(
			array(
				'name'         => 'CUET PG Agricultural Economics',
				'code'         => 'cuet-pg-agri-econ',
				'description'  => 'Central University Entrance Test PG for Agricultural Economics aspirants.',
				'instructions' => '100 Questions, 400 Marks, 120 Minutes duration.',
				'sort_order'   => 10,
				'status'       => 'active',
			)
		);

		if ( is_wp_error( $exam_id ) || ! $exam_id ) {
			throw new Exception( 'Failed to create new examination category.' );
		}

		$created = IAQ_Examinations::get_examination( $exam_id );
		if ( ! $created || 'CUET PG Agricultural Economics' !== $created->name ) {
			throw new Exception( 'Retrieved examination record does not match created attributes.' );
		}

		echo "  [PASS] Examination creation and parameter validation verified.\n";
	}

	private static function test_duplicate_code_prevention() {
		// Attempt to create another exam with 'cuet-pg-agri-econ'
		$dup_res = IAQ_Examinations::save_examination(
			array(
				'name' => 'CUET PG Duplicate Test',
				'code' => 'cuet-pg-agri-econ',
			)
		);

		if ( ! is_wp_error( $dup_res ) ) {
			throw new Exception( 'Expected duplicate code rejection, but examination was saved.' );
		}

		echo "  [PASS] Unique examination code constraint verified.\n";
	}

	private static function test_examination_update() {
		$exam = IAQ_Examinations::get_examination_by_code( 'cuet-pg-agri-econ' );
		if ( ! $exam ) {
			throw new Exception( 'Could not find seeded test examination.' );
		}

		$updated_res = IAQ_Examinations::save_examination(
			array(
				'name'        => 'CUET PG Agricultural Economics (Updated)',
				'code'        => 'cuet-pg-agri-econ',
				'description' => 'Updated exam description for syllabus 2026.',
				'sort_order'  => 15,
			),
			$exam->id
		);

		if ( is_wp_error( $updated_res ) ) {
			throw new Exception( 'Failed to update examination record.' );
		}

		$updated_exam = IAQ_Examinations::get_examination( $exam->id );
		if ( 'CUET PG Agricultural Economics (Updated)' !== $updated_exam->name || 15 !== (int) $updated_exam->display_order ) {
			throw new Exception( 'Updated examination properties were not persisted.' );
		}

		echo "  [PASS] Examination editing and property persistence verified.\n";
	}

	private static function test_examination_duplication() {
		$exam = IAQ_Examinations::get_examination_by_code( 'cuet-pg-agri-econ' );
		$clone_id = IAQ_Examinations::duplicate_examination( $exam->id );

		if ( is_wp_error( $clone_id ) || ! $clone_id ) {
			throw new Exception( 'Failed to duplicate examination category.' );
		}

		$cloned = IAQ_Examinations::get_examination( $clone_id );
		if ( ! $cloned || strpos( $cloned->name, '(Copy)' ) === false ) {
			throw new Exception( 'Cloned examination name does not include copy suffix.' );
		}
		if ( 'inactive' !== $cloned->status ) {
			throw new Exception( 'Cloned examination must default to inactive for admin review.' );
		}

		echo "  [PASS] Examination category cloning and safe deactivation verified.\n";
	}

	private static function test_examination_status_toggle() {
		$exam = IAQ_Examinations::get_examination_by_code( 'cuet-pg-agri-econ' );
		$new_status = IAQ_Examinations::toggle_status( $exam->id );

		if ( 'inactive' !== $new_status ) {
			throw new Exception( 'Expected status toggle from active to inactive.' );
		}

		$new_status_again = IAQ_Examinations::toggle_status( $exam->id );
		if ( 'active' !== $new_status_again ) {
			throw new Exception( 'Expected status toggle back to active.' );
		}

		echo "  [PASS] Examination status toggling (active/inactive) verified.\n";
	}

	private static function test_examination_list_retrieval() {
		$exams = IAQ_Examinations::get_examinations( array( 'status' => 'active' ) );
		if ( empty( $exams ) || ! is_array( $exams ) ) {
			throw new Exception( 'Failed to retrieve active examinations list.' );
		}

		echo "  [PASS] Examination filtering and dynamic list retrieval verified.\n";
	}
}
