<?php
/**
 * Standalone Local Development & Interactive Preview Environment.
 * Runs on PHP Built-in Server with persistent JSON storage.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

define( 'IAQ_VERSION', '1.1.0' );
define( 'IAQ_DB_VERSION', '1.0.0' );
define( 'IAQ_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'IAQ_PLUGIN_URL', '/' );
define( 'IAQ_PLUGIN_BASENAME', 'indianagri-quiz-engine/indianagri-quiz-engine.php' );

$data_file = __DIR__ . '/data.json';

/**
 * File-backed Persistent Mock WPDB.
 */
class Local_Persistent_WPDB {
	public $prefix = 'wp_';
	public $insert_id = 0;
	private $file;
	public $data = array();

	public function __construct( $file ) {
		$this->file = $file;
		$this->load();
	}

	public function load() {
		if ( file_exists( $this->file ) ) {
			$raw = file_get_contents( $this->file );
			$this->data = json_decode( $raw, true );
		}
		if ( ! is_array( $this->data ) || empty( $this->data ) ) {
			$this->data = array(
				'options' => array(),
				'tables'  => array(
					'wp_iaq_subjects'           => array(),
					'wp_iaq_topics'             => array(),
					'wp_iaq_examinations'       => array(),
					'wp_iaq_questions'          => array(),
					'wp_iaq_quizzes'            => array(),
					'wp_iaq_quiz_questions'     => array(),
					'wp_iaq_quiz_attempts'      => array(),
					'wp_iaq_attempt_answers'    => array(),
					'wp_iaq_subscription_plans' => array(),
					'wp_iaq_user_subscriptions' => array(),
				),
				'last_id' => 0,
			);
		}
		if ( ! isset( $this->data['tables']['wp_iaq_examinations'] ) ) {
			$this->data['tables']['wp_iaq_examinations'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_subscription_plans'] ) ) {
			$this->data['tables']['wp_iaq_subscription_plans'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_user_subscriptions'] ) ) {
			$this->data['tables']['wp_iaq_user_subscriptions'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_bookmarks'] ) ) {
			$this->data['tables']['wp_iaq_bookmarks'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_wrong_notebook'] ) ) {
			$this->data['tables']['wp_iaq_wrong_notebook'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_study_activity'] ) ) {
			$this->data['tables']['wp_iaq_study_activity'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_books'] ) ) {
			$this->data['tables']['wp_iaq_books'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_book_quizzes'] ) ) {
			$this->data['tables']['wp_iaq_book_quizzes'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_exam_subjects'] ) ) {
			$this->data['tables']['wp_iaq_exam_subjects'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_exam_subject_quizzes'] ) ) {
			$this->data['tables']['wp_iaq_exam_subject_quizzes'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_mock_categories'] ) ) {
			$this->data['tables']['wp_iaq_mock_categories'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_mock_series'] ) ) {
			$this->data['tables']['wp_iaq_mock_series'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_mock_vacancies'] ) ) {
			$this->data['tables']['wp_iaq_mock_vacancies'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_mock_states_universities'] ) ) {
			$this->data['tables']['wp_iaq_mock_states_universities'] = array();
		}
		if ( ! isset( $this->data['tables']['wp_iaq_mock_test_relations'] ) ) {
			$this->data['tables']['wp_iaq_mock_test_relations'] = array();
		}
		$this->insert_id = isset( $this->data['last_id'] ) ? $this->data['last_id'] : 0;
	}

	public function save() {
		$this->data['last_id'] = $this->insert_id;
		file_put_contents( $this->file, json_encode( $this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	}

	public function get_charset_collate() {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
	}

	public function prepare( $query, ...$args ) {
		if ( isset( $args[0] ) && is_array( $args[0] ) && count( $args ) === 1 ) {
			$args = $args[0];
		}
		foreach ( $args as $arg ) {
			$val = is_numeric( $arg ) ? $arg : "'" . addslashes( (string) $arg ) . "'";
			$pos = strpos( $query, '%d' );
			if ( false === $pos ) $pos = strpos( $query, '%s' );
			if ( false === $pos ) $pos = strpos( $query, '%f' );
			if ( false !== $pos ) {
				$query = substr_replace( $query, $val, $pos, 2 );
			}
		}
		return $query;
	}

	public function insert( $table, $row_data, $format = null ) {
		$this->insert_id++;
		$record = array_merge( array( 'id' => $this->insert_id ), $row_data );
		$this->data['tables'][ $table ][ $this->insert_id ] = $record;
		$this->save();
		return 1;
	}

	public function update( $table, $row_data, $where, $format = null, $where_format = null ) {
		$count = 0;
		if ( isset( $where['id'] ) ) {
			$id = $where['id'];
			if ( isset( $this->data['tables'][ $table ][ $id ] ) ) {
				foreach ( $row_data as $k => $v ) {
					$this->data['tables'][ $table ][ $id ][ $k ] = $v;
				}
				$count++;
			}
		} else {
			foreach ( $this->data['tables'][ $table ] as $id => $row ) {
				$match = true;
				foreach ( $where as $wk => $wv ) {
					if ( ! isset( $row[ $wk ] ) || $row[ $wk ] != $wv ) {
						$match = false;
						break;
					}
				}
				if ( $match ) {
					foreach ( $row_data as $k => $v ) {
						$this->data['tables'][ $table ][ $id ][ $k ] = $v;
					}
					$count++;
				}
			}
		}
		$this->save();
		return $count;
	}

	public function delete( $table, $where, $where_format = null ) {
		$deleted = 0;
		foreach ( $this->data['tables'][ $table ] as $id => $row ) {
			$match = true;
			foreach ( $where as $wk => $wv ) {
				if ( ! isset( $row[ $wk ] ) || $row[ $wk ] != $wv ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				unset( $this->data['tables'][ $table ][ $id ] );
				$deleted++;
			}
		}
		$this->save();
		return $deleted;
	}

	public function get_results( $query ) {
		$results = array();

		// Quizzes query
		if ( strpos( $query, 'wp_iaq_quizzes' ) !== false && strpos( $query, 'SELECT q.*' ) !== false && strpos( $query, 'mock_test_relations' ) === false && strpos( $query, 'INNER JOIN' ) === false ) {
			foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
				$subj = ( $qz['subject_id'] && isset( $this->data['tables']['wp_iaq_subjects'][ $qz['subject_id'] ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $qz['subject_id'] ]['name'] : '';
				$top  = ( $qz['topic_id'] && isset( $this->data['tables']['wp_iaq_topics'][ $qz['topic_id'] ] ) ) ? $this->data['tables']['wp_iaq_topics'][ $qz['topic_id'] ]['name'] : '';

				$obj = (object) $qz;
				$obj->subject_name = $subj;
				$obj->topic_name   = $top;

				$q_count = 0;
				foreach ( $this->data['tables']['wp_iaq_quiz_questions'] as $qq ) {
					if ( $qq['quiz_id'] == $qz['id'] ) $q_count++;
				}
				$obj->assigned_questions_count = $q_count;
				$results[] = $obj;
			}
			return array_reverse( $results );
		}

		// Quiz Questions query
		if ( strpos( $query, 'wp_iaq_quiz_questions' ) !== false && strpos( $query, 'qq.quiz_id' ) !== false ) {
			if ( preg_match( "/qq\.quiz_id = (\d+)/", $query, $m ) ) {
				$quiz_id = (int) $m[1];
				foreach ( $this->data['tables']['wp_iaq_quiz_questions'] as $qq ) {
					if ( $qq['quiz_id'] == $quiz_id && isset( $this->data['tables']['wp_iaq_questions'][ $qq['question_id'] ] ) ) {
						$q = (object) $this->data['tables']['wp_iaq_questions'][ $qq['question_id'] ];
						$q->link_id               = $qq['id'];
						$q->question_order        = $qq['question_order'];
						$q->custom_marks          = isset( $qq['custom_marks'] ) ? $qq['custom_marks'] : null;
						$q->custom_negative_marks = isset( $qq['custom_negative_marks'] ) ? $qq['custom_negative_marks'] : null;
						$q->subject_name          = isset( $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ] ) ? $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ]['name'] : '';
						$q->topic_name            = ( $q->topic_id && isset( $this->data['tables']['wp_iaq_topics'][ $q->topic_id ] ) ) ? $this->data['tables']['wp_iaq_topics'][ $q->topic_id ]['name'] : '';
						$results[] = $q;
					}
				}
			}
			return $results;
		}

		// Questions query
		if ( strpos( $query, 'wp_iaq_questions' ) !== false && strpos( $query, 'SELECT q.*' ) !== false ) {
			foreach ( $this->data['tables']['wp_iaq_questions'] as $q ) {
				$subj = isset( $this->data['tables']['wp_iaq_subjects'][ $q['subject_id'] ] ) ? $this->data['tables']['wp_iaq_subjects'][ $q['subject_id'] ]['name'] : '';
				$top  = ( $q['topic_id'] && isset( $this->data['tables']['wp_iaq_topics'][ $q['topic_id'] ] ) ) ? $this->data['tables']['wp_iaq_topics'][ $q['topic_id'] ]['name'] : '';

				$obj = (object) $q;
				$obj->subject_name = $subj;
				$obj->topic_name   = $top;
				$results[] = $obj;
			}
			return array_reverse( $results );
		}

		// Subjects query
		if ( strpos( $query, 'wp_iaq_subjects' ) !== false && strpos( $query, 'SELECT s.*' ) !== false ) {
			$subjects = array();
			foreach ( $this->data['tables']['wp_iaq_subjects'] as $s ) {
				$obj = (object) $s;
				$q_count = 0;
				foreach ( $this->data['tables']['wp_iaq_questions'] as $q ) {
					if ( $q['subject_id'] == $s['id'] ) $q_count++;
				}
				$t_count = 0;
				foreach ( $this->data['tables']['wp_iaq_topics'] as $t ) {
					if ( $t['subject_id'] == $s['id'] ) $t_count++;
				}
				$ps_count = 0;
				foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
					$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
					$qt = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : 'practice';
					if ( isset( $qz['subject_id'] ) && (int) $qz['subject_id'] === (int) $s['id'] && 'practice' === $qt && 'published' === $st ) {
						$ps_count++;
					}
				}
				$obj->question_count      = $q_count;
				$obj->topic_count         = $t_count;
				$obj->topics_count        = $t_count;
				$obj->practice_sets_count = $ps_count;
				$subjects[] = $obj;
			}
			return $subjects;
		}

		// Topics query
		if ( strpos( $query, 'wp_iaq_topics' ) !== false && strpos( $query, 'SELECT t.*' ) !== false ) {
			$filter_subj = 0;
			if ( preg_match( "/WHERE t\.subject_id = (\d+)/", $query, $m ) ) {
				$filter_subj = (int) $m[1];
			}
			foreach ( $this->data['tables']['wp_iaq_topics'] as $t ) {
				if ( $filter_subj > 0 && $t['subject_id'] != $filter_subj ) {
					continue;
				}
				$obj = (object) $t;
				$q_count = 0;
				foreach ( $this->data['tables']['wp_iaq_questions'] as $q ) {
					if ( isset( $q['topic_id'] ) && $q['topic_id'] == $t['id'] ) $q_count++;
				}
				$obj->question_count = $q_count;
				$results[] = $obj;
			}
			return $results;
		}

		// Attempt answers query
		if ( strpos( $query, 'wp_iaq_attempt_answers' ) !== false ) {
			if ( preg_match( "/WHERE (?:ans\.)?attempt_id = (\d+)/", $query, $m ) ) {
				$att_id = (int) $m[1];
				foreach ( $this->data['tables']['wp_iaq_attempt_answers'] as $row ) {
					if ( $row['attempt_id'] == $att_id ) {
						$obj = (object) $row;
						if ( isset( $this->data['tables']['wp_iaq_questions'][ $row['question_id'] ] ) ) {
							$q = (object) $this->data['tables']['wp_iaq_questions'][ $row['question_id'] ];
							$obj->question_text  = $q->question_text;
							$obj->option_a       = $q->option_a;
							$obj->option_b       = $q->option_b;
							$obj->option_c       = $q->option_c;
							$obj->option_d       = $q->option_d;
							$obj->correct_option = $q->correct_option;
							$obj->explanation    = $q->explanation;
							$obj->subject_id     = isset( $q->subject_id ) ? (int) $q->subject_id : 0;
							$obj->topic_id       = isset( $q->topic_id ) ? (int) $q->topic_id : 0;
							$obj->subject_name   = ( isset( $q->subject_id ) && isset( $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ]['name'] : 'General Agriculture';
							$obj->topic_name     = ( isset( $q->topic_id ) && isset( $this->data['tables']['wp_iaq_topics'][ $q->topic_id ] ) ) ? $this->data['tables']['wp_iaq_topics'][ $q->topic_id ]['name'] : '';
						}
						$results[] = $obj;
					}
				}
				return $results;
			}
		}

		// User overall subject mastery query
		if ( strpos( $query, "a.status = 'completed'" ) !== false && strpos( $query, 'wp_iaq_attempt_answers' ) !== false && preg_match( "/WHERE a\.user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			$results = array();
			$completed_att_ids = array();
			foreach ( $this->data['tables']['wp_iaq_quiz_attempts'] as $att ) {
				if ( $att['user_id'] == $uid && 'completed' === $att['status'] ) {
					$completed_att_ids[] = $att['id'];
				}
			}
			foreach ( $this->data['tables']['wp_iaq_attempt_answers'] as $ans ) {
				if ( in_array( $ans['attempt_id'], $completed_att_ids ) ) {
					$row = new stdClass();
					$row->selected_option = $ans['selected_option'];
					$row->is_correct = isset( $ans['is_correct'] ) ? $ans['is_correct'] : null;
					$row->marks_awarded = isset( $ans['marks_awarded'] ) ? $ans['marks_awarded'] : 0;
					if ( isset( $this->data['tables']['wp_iaq_questions'][ $ans['question_id'] ] ) ) {
						$q = $this->data['tables']['wp_iaq_questions'][ $ans['question_id'] ];
						$row->subject_id = isset( $q['subject_id'] ) ? $q['subject_id'] : 0;
						$row->subject_name = ( isset( $q['subject_id'] ) && isset( $this->data['tables']['wp_iaq_subjects'][ $q['subject_id'] ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $q['subject_id'] ]['name'] : 'General Agriculture';
					} else {
						$row->subject_id = 0;
						$row->subject_name = 'General Agriculture';
					}
					$results[] = $row;
				}
			}
			return $results;
		}

		// User attempt history
		if ( strpos( $query, 'SELECT a.*' ) !== false && strpos( $query, 'FROM wp_iaq_quiz_attempts a' ) !== false && preg_match( "/WHERE a\.user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			$results = array();
			foreach ( array_reverse( $this->data['tables']['wp_iaq_quiz_attempts'] ) as $att ) {
				if ( $att['user_id'] == $uid ) {
					$clone = (object) $att;
					if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $att['quiz_id'] ] ) ) {
						$qz = $this->data['tables']['wp_iaq_quizzes'][ $att['quiz_id'] ];
						$clone->quiz_title = $qz['title'];
						$clone->quiz_type = $qz['quiz_type'];
						$clone->exam_category = $qz['exam_category'];
					}
					$results[] = $clone;
				}
			}
			return $results;
		}

		// Quiz counts query
		if ( strpos( $query, 'wp_iaq_quizzes' ) !== false && strpos( $query, 'COUNT(id) as count_val' ) !== false ) {
			$counts = array( 'practice' => 0, 'mock' => 0, 'previous_year' => 0 );
			foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
				$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
				$qt = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : 'practice';
				if ( 'published' === $st && isset( $counts[ $qt ] ) ) {
					$counts[ $qt ]++;
				}
			}
			foreach ( $counts as $t => $c ) {
				$results[] = (object) array( 'quiz_type' => $t, 'count_val' => $c );
			}
			return $results;
		}

		// Continue practicing attempts
		if ( ( strpos( $query, 'attempt_status' ) !== false || strpos( $query, 'scored_out_of' ) !== false ) && strpos( $query, 'JOIN wp_iaq_quizzes q' ) !== false ) {
			if ( preg_match( "/WHERE a\.user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$req_status = null;
				if ( strpos( $query, "a.status = 'in_progress'" ) !== false ) $req_status = 'in_progress';
				elseif ( strpos( $query, "a.status = 'completed'" ) !== false ) $req_status = 'completed';

				foreach ( array_reverse( $this->data['tables']['wp_iaq_quiz_attempts'] ) as $att ) {
					if ( $att['user_id'] == $uid && ( null === $req_status || $att['status'] === $req_status ) ) {
						if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $att['quiz_id'] ] ) ) {
							$qz = $this->data['tables']['wp_iaq_quizzes'][ $att['quiz_id'] ];
							$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
							if ( 'published' === $st ) {
								$row = (object) array(
									'attempt_id'       => $att['id'],
									'quiz_id'          => $att['quiz_id'],
									'start_time'       => $att['start_time'],
									'attempt_status'   => $att['status'],
									'score'            => isset( $att['score'] ) ? $att['score'] : 0,
									'scored_out_of'    => isset( $att['total_marks'] ) ? $att['total_marks'] : 0,
									'percentage'       => isset( $att['percentage'] ) ? $att['percentage'] : 0,
									'passed'           => isset( $att['passed'] ) ? $att['passed'] : 0,
									'title'            => $qz['title'],
									'quiz_type'        => $qz['quiz_type'],
									'exam_category'    => $qz['exam_category'],
									'total_questions'  => $qz['total_questions'],
									'total_marks'      => $qz['total_marks'],
									'duration_minutes' => $qz['duration_minutes'],
									'access_type'      => $qz['access_type'],
								);
								$results[] = $row;
							}
						}
					}
				}
				return $results;
			}
		}

		// Published quizzes query for continue practicing / recommendations
		if ( strpos( $query, 'FROM wp_iaq_quizzes q' ) !== false && strpos( $query, "q.status = 'published'" ) !== false && strpos( $query, 'SELECT e.*' ) === false && strpos( $query, 'SELECT s.*' ) === false ) {
			foreach ( array_reverse( $this->data['tables']['wp_iaq_quizzes'] ) as $qz ) {
				$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
				if ( 'published' === $st ) {
					$results[] = (object) array(
						'quiz_id'          => $qz['id'],
						'title'            => $qz['title'],
						'quiz_type'        => $qz['quiz_type'],
						'exam_category'    => $qz['exam_category'],
						'total_questions'  => $qz['total_questions'],
						'total_marks'      => $qz['total_marks'],
						'duration_minutes' => $qz['duration_minutes'],
						'access_type'      => $qz['access_type'],
					);
				}
			}
			return $results;
		}

		// Assigned questions with custom marks
		if ( strpos( $query, 'FROM wp_iaq_quiz_questions qq' ) !== false && preg_match( "/WHERE qq\.quiz_id = (\d+)/", $query, $m ) ) {
			$quiz_id = (int) $m[1];
			foreach ( $this->data['tables']['wp_iaq_quiz_questions'] as $qq ) {
				if ( $qq['quiz_id'] == $quiz_id && isset( $this->data['tables']['wp_iaq_questions'][ $qq['question_id'] ] ) ) {
					$q = (object) $this->data['tables']['wp_iaq_questions'][ $qq['question_id'] ];
					$q->custom_marks = isset( $qq['custom_marks'] ) ? $qq['custom_marks'] : null;
					$q->custom_negative_marks = isset( $qq['custom_negative_marks'] ) ? $qq['custom_negative_marks'] : null;
					$results[] = $q;
				}
			}
			return $results;
		}

		// Examinations query
		if ( strpos( $query, 'FROM wp_iaq_examinations' ) !== false || strpos( $query, 'SELECT e.*' ) !== false ) {
			$status_match = null;
			if ( preg_match( "/(?:WHERE|e\.)status = '([^']+)'/", $query, $m ) ) {
				$status_match = $m[1];
			}
			$exams_list = array();
			foreach ( $this->data['tables']['wp_iaq_examinations'] as $ex ) {
				if ( $status_match && isset( $ex['status'] ) && $ex['status'] !== $status_match ) {
					continue;
				}
				$clone = (object) $ex;
				$subj_cnt = 0;
				foreach ( $this->data['tables']['wp_iaq_exam_subjects'] as $es ) {
					if ( (int) $es['exam_id'] === (int) $clone->id ) {
						$subj_cnt++;
					}
				}
				$clone->assigned_subjects_count = $subj_cnt;

				$ps_count = 0;
				foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
					$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
					$qt = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : 'practice';
					if ( 'practice' === $qt && 'published' === $st ) {
						$matched = false;
						foreach ( $this->data['tables']['wp_iaq_exam_subject_quizzes'] as $esq ) {
							if ( (int) $esq['exam_id'] === (int) $clone->id && (int) $esq['quiz_id'] === (int) $qz['id'] ) {
								$matched = true;
								break;
							}
						}
						if ( ! $matched && ! empty( $qz['exam_category'] ) ) {
							if ( $qz['exam_category'] === $clone->code || $qz['exam_category'] === $clone->name ) {
								$matched = true;
							}
						}
						if ( $matched ) {
							$ps_count++;
						}
					}
				}
				$clone->practice_sets_count = $ps_count;
				$exams_list[] = $clone;
			}
			usort( $exams_list, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $exams_list;
		}

		// Subscription Plans query
		if ( strpos( $query, 'FROM wp_iaq_subscription_plans' ) !== false ) {
			$status_match = null;
			if ( preg_match( "/WHERE status = '([^']+)'/", $query, $m ) ) {
				$status_match = $m[1];
			}
			foreach ( $this->data['tables']['wp_iaq_subscription_plans'] as $pl ) {
				if ( $status_match && isset( $pl['status'] ) && $pl['status'] !== $status_match ) {
					continue;
				}
				$results[] = (object) $pl;
			}
			return $results;
		}

		// User Subscriptions query
		if ( strpos( $query, 'FROM wp_iaq_user_subscriptions' ) !== false ) {
			$uid_match = null;
			if ( preg_match( "/user_id = (\d+)/", $query, $m ) ) {
				$uid_match = (int) $m[1];
			}
			$status_match = null;
			if ( preg_match( "/status = '([^']+)'/", $query, $m ) ) {
				$status_match = $m[1];
			}
			if ( 2 === $uid_match ) {
				$results[] = (object) array(
					'id'                  => 99,
					'user_id'             => 2,
					'plan_id'             => 75,
					'plan_name'           => 'Six-Month All Access Pass',
					'plan_code'           => 'six-month-all-access',
					'access_scope'        => 'all_access',
					'included_categories' => 'All Agricultural Exams Included',
					'start_date'          => date( 'Y-m-d H:i:s', strtotime( '-15 days' ) ),
					'expiry_date'         => date( 'Y-m-d H:i:s', strtotime( '+165 days' ) ),
					'status'              => 'active',
				);
				return $results;
			}
			foreach ( $this->data['tables']['wp_iaq_user_subscriptions'] as $us ) {
				if ( $uid_match && (int) $us['user_id'] !== $uid_match ) {
					continue;
				}
				if ( $status_match && $us['status'] !== $status_match ) {
					continue;
				}
				$results[] = (object) $us;
			}
			return array_reverse( $results );
		}

		// Bookmarks query
		if ( strpos( $query, 'wp_iaq_bookmarks' ) !== false && strpos( $query, 'JOIN wp_iaq_questions q' ) !== false ) {
			if ( preg_match( "/b\.user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				foreach ( array_reverse( $this->data['tables']['wp_iaq_bookmarks'] ) as $bm ) {
					if ( (int) $bm['user_id'] === $uid && isset( $this->data['tables']['wp_iaq_questions'][ $bm['question_id'] ] ) ) {
						$q = (object) $this->data['tables']['wp_iaq_questions'][ $bm['question_id'] ];
						$results[] = (object) array(
							'bookmark_id'    => $bm['id'],
							'user_id'        => $bm['user_id'],
							'question_id'    => $bm['question_id'],
							'notes'          => isset( $bm['notes'] ) ? $bm['notes'] : '',
							'bookmarked_at'  => $bm['created_at'],
							'question_text'  => $q->question_text,
							'option_a'       => $q->option_a,
							'option_b'       => $q->option_b,
							'option_c'       => $q->option_c,
							'option_d'       => $q->option_d,
							'correct_option' => $q->correct_option,
							'explanation'    => $q->explanation,
							'marks'          => $q->marks,
							'negative_marks' => $q->negative_marks,
							'difficulty'     => $q->difficulty,
							'exam_category'  => $q->exam_category,
							'subject_id'     => $q->subject_id,
							'topic_id'       => $q->topic_id,
							'subject_name'   => isset( $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ] ) ? $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ]['name'] : 'General Agriculture',
							'topic_name'     => isset( $this->data['tables']['wp_iaq_topics'][ $q->topic_id ] ) ? $this->data['tables']['wp_iaq_topics'][ $q->topic_id ]['name'] : '',
						);
					}
				}
				return $results;
			}
		}

		// Wrong Notebook query
		if ( strpos( $query, 'wp_iaq_wrong_notebook' ) !== false && strpos( $query, 'JOIN wp_iaq_questions q' ) !== false ) {
			if ( preg_match( "/n\.user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				foreach ( array_reverse( $this->data['tables']['wp_iaq_wrong_notebook'] ) as $wn ) {
					if ( (int) $wn['user_id'] === $uid && isset( $this->data['tables']['wp_iaq_questions'][ $wn['question_id'] ] ) ) {
						$q = (object) $this->data['tables']['wp_iaq_questions'][ $wn['question_id'] ];
						$results[] = (object) array(
							'notebook_id'         => $wn['id'],
							'user_id'             => $wn['user_id'],
							'question_id'         => $wn['question_id'],
							'last_attempt_id'     => $wn['last_attempt_id'],
							'times_incorrect'     => $wn['times_incorrect'],
							'notebook_status'     => $wn['status'],
							'student_notes'       => isset( $wn['notes'] ) ? $wn['notes'] : '',
							'last_failed_at'      => $wn['updated_at'],
							'question_text'       => $q->question_text,
							'option_a'            => $q->option_a,
							'option_b'            => $q->option_b,
							'option_c'            => $q->option_c,
							'option_d'            => $q->option_d,
							'correct_option'      => $q->correct_option,
							'explanation'         => $q->explanation,
							'marks'               => $q->marks,
							'negative_marks'      => $q->negative_marks,
							'difficulty'          => $q->difficulty,
							'exam_category'       => $q->exam_category,
							'subject_id'          => $q->subject_id,
							'topic_id'            => $q->topic_id,
							'subject_name'        => isset( $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ] ) ? $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ]['name'] : 'General Agriculture',
							'topic_name'          => isset( $this->data['tables']['wp_iaq_topics'][ $q->topic_id ] ) ? $this->data['tables']['wp_iaq_topics'][ $q->topic_id ]['name'] : '',
							'last_selected_option'=> ( 'B' === $q->correct_option ) ? 'C' : 'A',
						);
					}
				}
				return $results;
			}
		}

		// Study activity calendar query
		if ( strpos( $query, 'wp_iaq_study_activity' ) !== false && strpos( $query, 'GROUP BY activity_date' ) !== false ) {
			if ( preg_match( "/user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$by_date = array();
				foreach ( $this->data['tables']['wp_iaq_study_activity'] as $sa ) {
					if ( (int) $sa['user_id'] === $uid ) {
						$d = $sa['activity_date'];
						if ( ! isset( $by_date[ $d ] ) ) {
							$by_date[ $d ] = (object) array( 'activity_date' => $d, 'total_qs' => 0, 'total_events' => 0 );
						}
						$by_date[ $d ]->total_qs += (int) $sa['questions_solved'];
						$by_date[ $d ]->total_events++;
					}
				}
				return array_values( $by_date );
			}
		}

		// Leaderboard ranking query
		if ( strpos( $query, 'MAX(a.score) as max_score' ) !== false && strpos( $query, 'GROUP BY a.user_id' ) !== false ) {
			$by_user = array();
			foreach ( $this->data['tables']['wp_iaq_quiz_attempts'] as $att ) {
				if ( 'completed' === $att['status'] ) {
					$uid   = (int) $att['user_id'];
					$score = (float) ( isset( $att['score'] ) ? $att['score'] : 0 );
					$pct   = (float) ( isset( $att['percentage'] ) ? $att['percentage'] : 0 );
					$time  = (int) ( isset( $att['time_spent_seconds'] ) ? $att['time_spent_seconds'] : 300 );
					if ( ! isset( $by_user[ $uid ] ) || $score > $by_user[ $uid ]->max_score ) {
						$qz = isset( $this->data['tables']['wp_iaq_quizzes'][ $att['quiz_id'] ] ) ? (object) $this->data['tables']['wp_iaq_quizzes'][ $att['quiz_id'] ] : null;
						$by_user[ $uid ] = (object) array(
							'user_id'            => $uid,
							'max_score'          => $score,
							'max_percentage'     => $pct,
							'min_time'           => $time,
							'latest_attempt_time'=> $att['created_at'],
							'total_attempts'     => 1,
							'quiz_title'         => $qz ? $qz->title : 'Mock Test',
							'exam_category'      => $qz ? $qz->exam_category : 'ICAR',
						);
					} else {
						$by_user[ $uid ]->total_attempts++;
					}
				}
			}
			uasort( $by_user, function( $a, $b ) {
				return $b->max_score <=> $a->max_score;
			} );
			return array_values( $by_user );
		}

		// Books query
		if ( strpos( $query, 'FROM wp_iaq_books' ) !== false || ( strpos( $query, 'wp_iaq_books' ) !== false && strpos( $query, 'SELECT b.*' ) !== false ) ) {
			$status_match = null;
			if ( preg_match( "/b\.status = '([^']+)'/", $query, $sm ) ) {
				$status_match = $sm[1];
			}
			$not_archived = ( strpos( $query, "b.status != 'archived'" ) !== false );
			$search_term  = null;
			if ( preg_match( "/b\.title LIKE '%([^%]+)%'/i", $query, $lkm ) ) {
				$search_term = strtolower( $lkm[1] );
			}

			$books = array();
			foreach ( $this->data['tables']['wp_iaq_books'] as $bk ) {
				if ( $status_match && isset( $bk['status'] ) && $bk['status'] !== $status_match ) {
					continue;
				}
				if ( $not_archived && isset( $bk['status'] ) && 'archived' === $bk['status'] ) {
					continue;
				}
				if ( $search_term ) {
					$t_match = false;
					if ( ! empty( $bk['title'] ) && false !== stripos( $bk['title'], $search_term ) ) $t_match = true;
					if ( ! empty( $bk['author'] ) && false !== stripos( $bk['author'], $search_term ) ) $t_match = true;
					if ( ! empty( $bk['description'] ) && false !== stripos( $bk['description'], $search_term ) ) $t_match = true;
					if ( ! $t_match ) continue;
				}
				$clone = (object) $bk;
				$clone->subject_name = ( ! empty( $clone->subject_id ) && isset( $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ]['name'] : '';
				$clone->examination_name = ( ! empty( $clone->examination_id ) && isset( $this->data['tables']['wp_iaq_examinations'][ $clone->examination_id ] ) ) ? $this->data['tables']['wp_iaq_examinations'][ $clone->examination_id ]['name'] : '';
				$cnt = 0;
				foreach ( $this->data['tables']['wp_iaq_book_quizzes'] as $bq ) {
					if ( (int) $bq['book_id'] === (int) $clone->id ) {
						if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ] ) ) {
							$st = isset( $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ]['status'] ) ? $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ]['status'] : 'published';
							if ( 'published' === $st ) $cnt++;
						}
					}
				}
				$clone->practice_sets_count = $cnt;
				$books[] = $clone;
			}
			usort( $books, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $books;
		}

		// Book quizzes query
		if ( strpos( $query, 'wp_iaq_book_quizzes' ) !== false && strpos( $query, 'bq.book_id' ) !== false ) {
			if ( preg_match( "/bq\.book_id = (\d+)/", $query, $bm ) ) {
				$book_id = (int) $bm[1];
				$status_match = null;
				if ( preg_match( "/q\.status = '([^']+)'/", $query, $sm ) ) {
					$status_match = $sm[1];
				}
				$not_archived = ( strpos( $query, "q.status != 'archived'" ) !== false );

				$list = array();
				foreach ( $this->data['tables']['wp_iaq_book_quizzes'] as $bq ) {
					if ( (int) $bq['book_id'] === $book_id ) {
						$qid = (int) $bq['quiz_id'];
						if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $qid ] ) ) {
							$qz = (object) $this->data['tables']['wp_iaq_quizzes'][ $qid ];
							$st = isset( $qz->status ) ? $qz->status : 'published';
							if ( $status_match && $st !== $status_match ) continue;
							if ( $not_archived && 'archived' === $st ) continue;
							$qz->book_sort_order    = (int) $bq['sort_order'];
							$qz->book_quiz_rel_id   = (int) $bq['id'];
							$qz->subject_name       = ( ! empty( $qz->subject_id ) && isset( $this->data['tables']['wp_iaq_subjects'][ $qz->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $qz->subject_id ]['name'] : '';
							$list[] = $qz;
						}
					}
				}
				usort( $list, function( $a, $b ) {
					if ( $a->book_sort_order !== $b->book_sort_order ) return $a->book_sort_order <=> $b->book_sort_order;
					return (int) $a->id <=> (int) $b->id;
				} );
				return $list;
			}
		}

		// Subject practice quizzes query
		if ( strpos( $query, "q.quiz_type = 'practice'" ) !== false && preg_match( "/q\.subject_id = (\d+)/", $query, $sm ) ) {
			$sid = (int) $sm[1];
			$status_match = null;
			if ( preg_match( "/q\.status = '([^']+)'/", $query, $st_m ) ) {
				$status_match = $st_m[1];
			}
			$not_archived = ( strpos( $query, "q.status != 'archived'" ) !== false );
			$list = array();
			foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
				$t = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : 'practice';
				if ( isset( $qz['subject_id'] ) && (int) $qz['subject_id'] === $sid && 'practice' === $t ) {
					$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
					if ( $status_match && $st !== $status_match ) continue;
					if ( $not_archived && 'archived' === $st ) continue;
					$clone = (object) $qz;
					$clone->subject_name = ( ! empty( $clone->subject_id ) && isset( $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ]['name'] : '';
					$clone->topic_name   = ( ! empty( $clone->topic_id ) && isset( $this->data['tables']['wp_iaq_topics'][ $clone->topic_id ] ) ) ? $this->data['tables']['wp_iaq_topics'][ $clone->topic_id ]['name'] : '';
					$list[] = $clone;
				}
			}
			usort( $list, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		// Exam subjects query
		if ( strpos( $query, 'wp_iaq_exam_subjects' ) !== false && strpos( $query, 'es.exam_id =' ) !== false ) {
			if ( preg_match( "/es\.exam_id = (\d+)/", $query, $em ) ) {
				$exam_id = (int) $em[1];
				$list = array();
				foreach ( $this->data['tables']['wp_iaq_exam_subjects'] as $es ) {
					if ( (int) $es['exam_id'] === $exam_id ) {
						$sid = (int) $es['subject_id'];
						if ( isset( $this->data['tables']['wp_iaq_subjects'][ $sid ] ) ) {
							$subj = (object) $this->data['tables']['wp_iaq_subjects'][ $sid ];
							$subj->exam_subject_order  = (int) $es['display_order'];
							$subj->exam_subject_rel_id = (int) $es['id'];

							$ps_count = 0;
							foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
								$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
								$qt = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : 'practice';
								if ( isset( $qz['subject_id'] ) && (int) $qz['subject_id'] === $sid && 'practice' === $qt && 'published' === $st ) {
									$matched = false;
									foreach ( $this->data['tables']['wp_iaq_exam_subject_quizzes'] as $esq ) {
										if ( (int) $esq['exam_id'] === $exam_id && (int) $esq['subject_id'] === $sid && (int) $esq['quiz_id'] === (int) $qz['id'] ) {
											$matched = true;
											break;
										}
									}
									if ( ! $matched && isset( $this->data['tables']['wp_iaq_examinations'][ $exam_id ] ) ) {
										$ex_obj = $this->data['tables']['wp_iaq_examinations'][ $exam_id ];
										if ( ! empty( $qz['exam_category'] ) && ( $qz['exam_category'] === $ex_obj['code'] || $qz['exam_category'] === $ex_obj['name'] ) ) {
											$matched = true;
										}
									}
									if ( $matched ) $ps_count++;
								}
							}
							$subj->practice_sets_count = $ps_count;
							$list[] = $subj;
						}
					}
				}
				usort( $list, function( $a, $b ) {
					if ( $a->exam_subject_order !== $b->exam_subject_order ) return $a->exam_subject_order <=> $b->exam_subject_order;
					return strcmp( $a->name, $b->name );
				} );
				return $list;
			}
		}

		// Exam Subject Quizzes query
		if ( ( strpos( $query, 'wp_iaq_exam_subject_quizzes' ) !== false || strpos( $query, 'exam_sort_order' ) !== false ) && preg_match( "/(?:esq\.exam_id = |exam_id = )(\d+)/", $query, $em ) && preg_match( "/(?:esq\.subject_id = |subject_id = )(\d+)/", $query, $sm ) ) {
			$exam_id = (int) $em[1];
			$subject_id = (int) $sm[1];
			$status_match = null;
			if ( preg_match( "/q\.status = '([^']+)'/", $query, $st_m ) ) {
				$status_match = $st_m[1];
			}
			$not_archived = ( strpos( $query, "q.status != 'archived'" ) !== false );

			$list = array();
			foreach ( $this->data['tables']['wp_iaq_exam_subject_quizzes'] as $esq ) {
				if ( (int) $esq['exam_id'] === $exam_id && (int) $esq['subject_id'] === $subject_id ) {
					$qid = (int) $esq['quiz_id'];
					if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $qid ] ) ) {
						$qz = (object) $this->data['tables']['wp_iaq_quizzes'][ $qid ];
						$st = isset( $qz->status ) ? $qz->status : 'published';
						if ( $status_match && $st !== $status_match ) continue;
						if ( $not_archived && 'archived' === $st ) continue;
						$qz->exam_sort_order  = (int) $esq['sort_order'];
						$qz->exam_quiz_rel_id = (int) $esq['id'];
						$qz->subject_name     = ( ! empty( $qz->subject_id ) && isset( $this->data['tables']['wp_iaq_subjects'][ $qz->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $qz->subject_id ]['name'] : '';
						$list[] = $qz;
					}
				}
			}
			if ( empty( $list ) && isset( $this->data['tables']['wp_iaq_examinations'][ $exam_id ] ) ) {
				$ex_obj = $this->data['tables']['wp_iaq_examinations'][ $exam_id ];
				foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
					$qt = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : 'practice';
					if ( isset( $qz['subject_id'] ) && (int) $qz['subject_id'] === $subject_id && 'practice' === $qt ) {
						if ( ! empty( $qz['exam_category'] ) && ( $qz['exam_category'] === $ex_obj['code'] || $qz['exam_category'] === $ex_obj['name'] ) ) {
							$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
							if ( $status_match && $st !== $status_match ) continue;
							if ( $not_archived && 'archived' === $st ) continue;
							$clone = (object) $qz;
							$clone->exam_sort_order  = isset( $clone->display_order ) ? (int) $clone->display_order : 0;
							$clone->exam_quiz_rel_id = 0;
							$clone->subject_name     = ( ! empty( $clone->subject_id ) && isset( $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ]['name'] : '';
							$list[] = $clone;
						}
					}
				}
			}
			usort( $list, function( $a, $b ) {
				if ( $a->exam_sort_order !== $b->exam_sort_order ) return $a->exam_sort_order <=> $b->exam_sort_order;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		// Mock Categories query
		if ( strpos( $query, 'wp_iaq_mock_categories' ) !== false && strpos( $query, 'mock_test_relations' ) === false ) {
			$status_match = null;
			if ( preg_match( "/status = '([^']+)'/", $query, $sm ) ) {
				$status_match = $sm[1];
			}
			$list = array();
			foreach ( $this->data['tables']['wp_iaq_mock_categories'] as $cat ) {
				if ( $status_match && isset( $cat['status'] ) && $cat['status'] !== $status_match ) continue;
				$list[] = (object) $cat;
			}
			usort( $list, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		// Mock States & Universities query
		if ( strpos( $query, 'wp_iaq_mock_states_universities' ) !== false ) {
			$type_match = null;
			if ( preg_match( "/(?:entity_type|type) = '([^']+)'/", $query, $tm ) ) {
				$type_match = $tm[1];
			}
			$status_match = null;
			if ( preg_match( "/status = '([^']+)'/", $query, $sm ) ) {
				$status_match = $sm[1];
			}
			$list = array();
			foreach ( $this->data['tables']['wp_iaq_mock_states_universities'] as $su ) {
				$su_type = isset( $su['entity_type'] ) ? $su['entity_type'] : ( isset( $su['type'] ) ? $su['type'] : 'state' );
				if ( $type_match && $su_type !== $type_match ) continue;
				if ( $status_match && isset( $su['status'] ) && $su['status'] !== $status_match ) continue;
				$list[] = (object) $su;
			}
			usort( $list, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return strcmp( $a->name, $b->name );
			} );
			return $list;
		}

		// Mock Vacancies query
		if ( strpos( $query, 'wp_iaq_mock_vacancies' ) !== false ) {
			$exam_match = null;
			if ( preg_match( "/(?:v\.)?(?:examination_id|exam_id) = (\d+)/", $query, $em ) ) {
				$exam_match = (int) $em[1];
			}
			$status_match = null;
			if ( preg_match( "/status = '([^']+)'/", $query, $sm ) ) {
				$status_match = $sm[1];
			}
			$list = array();
			foreach ( $this->data['tables']['wp_iaq_mock_vacancies'] as $vac ) {
				$vac_eid = ! empty( $vac['examination_id'] ) ? (int) $vac['examination_id'] : ( ! empty( $vac['exam_id'] ) ? (int) $vac['exam_id'] : 0 );
				if ( $exam_match && $vac_eid !== $exam_match ) continue;
				if ( $status_match && isset( $vac['status'] ) && $vac['status'] !== $status_match ) continue;
				$clone = (object) $vac;
				$clone->exam_name = ( $vac_eid && isset( $this->data['tables']['wp_iaq_examinations'][ $vac_eid ] ) ) ? $this->data['tables']['wp_iaq_examinations'][ $vac_eid ]['name'] : '';
				$list[] = $clone;
			}
			usort( $list, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		// Mock Series query
		if ( strpos( $query, 'wp_iaq_mock_series' ) !== false ) {
			$cat_match = preg_match( "/category_id = (\d+)/", $query, $cm ) ? (int) $cm[1] : 0;
			$exam_match = preg_match( "/exam_id = (\d+)/", $query, $em ) ? (int) $em[1] : 0;
			$sub_match = preg_match( "/subject_id = (\d+)/", $query, $sm ) ? (int) $sm[1] : 0;
			$status_match = preg_match( "/status = '([^']+)'/", $query, $stm ) ? $stm[1] : null;

			$list = array();
			foreach ( $this->data['tables']['wp_iaq_mock_series'] as $ser ) {
				if ( $cat_match && ! empty( $ser['category_id'] ) && (int) $ser['category_id'] !== $cat_match ) continue;
				if ( $exam_match && ! empty( $ser['exam_id'] ) && (int) $ser['exam_id'] !== $exam_match ) continue;
				if ( $sub_match && ! empty( $ser['subject_id'] ) && (int) $ser['subject_id'] !== $sub_match ) continue;
				if ( $status_match && isset( $ser['status'] ) && $ser['status'] !== $status_match ) continue;

				$clone = (object) $ser;
				$cid = ! empty( $ser['category_id'] ) ? (int) $ser['category_id'] : 0;
				$eid = ! empty( $ser['exam_id'] ) ? (int) $ser['exam_id'] : 0;
				$sid = ! empty( $ser['subject_id'] ) ? (int) $ser['subject_id'] : 0;
				$clone->category_name = ( $cid && isset( $this->data['tables']['wp_iaq_mock_categories'][ $cid ] ) ) ? $this->data['tables']['wp_iaq_mock_categories'][ $cid ]['name'] : '';
				$clone->exam_name = ( $eid && isset( $this->data['tables']['wp_iaq_examinations'][ $eid ] ) ) ? $this->data['tables']['wp_iaq_examinations'][ $eid ]['name'] : '';
				$clone->subject_name = ( $sid && isset( $this->data['tables']['wp_iaq_subjects'][ $sid ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $sid ]['name'] : '';
				$list[] = $clone;
			}
			usort( $list, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		// Mock Test Relations joined context query
		if ( strpos( $query, 'wp_iaq_mock_test_relations' ) !== false && strpos( $query, 'INNER JOIN' ) !== false ) {
			$cat_id   = preg_match( "/r\.category_id = (\d+)/", $query, $cm ) ? (int) $cm[1] : 0;
			$exam_id  = preg_match( "/r\.exam_id = (\d+)/", $query, $em ) ? (int) $em[1] : 0;
			$sub_id   = preg_match( "/r\.subject_id = (\d+)/", $query, $sm ) ? (int) $sm[1] : 0;
			$vac_id   = preg_match( "/r\.vacancy_id = (\d+)/", $query, $vm ) ? (int) $vm[1] : 0;
			$state_id = preg_match( "/r\.state_id = (\d+)/", $query, $stm ) ? (int) $stm[1] : 0;
			$ser_id   = preg_match( "/r\.series_id = (\d+)/", $query, $serm ) ? (int) $serm[1] : 0;

			$list = array();
			foreach ( $this->data['tables']['wp_iaq_mock_test_relations'] as $rel ) {
				if ( $cat_id && ! empty( $rel['category_id'] ) && (int) $rel['category_id'] !== $cat_id ) continue;
				if ( $exam_id && ! empty( $rel['exam_id'] ) && (int) $rel['exam_id'] !== $exam_id ) continue;
				if ( $sub_id && ! empty( $rel['subject_id'] ) && (int) $rel['subject_id'] !== $sub_id ) continue;
				if ( $vac_id && ! empty( $rel['vacancy_id'] ) && (int) $rel['vacancy_id'] !== $vac_id ) continue;
				if ( $state_id && ! empty( $rel['state_id'] ) && (int) $rel['state_id'] !== $state_id ) continue;
				if ( $ser_id && ! empty( $rel['series_id'] ) && (int) $rel['series_id'] !== $ser_id ) continue;

				$qid = (int) $rel['quiz_id'];
				if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $qid ] ) ) {
					$qz = (object) $this->data['tables']['wp_iaq_quizzes'][ $qid ];
					$st = isset( $qz->status ) ? $qz->status : 'published';
					$qt = isset( $qz->quiz_type ) ? $qz->quiz_type : 'mock';
					if ( 'published' !== $st || 'mock' !== $qt ) continue;
					$qz->mock_rel_id     = (int) $rel['id'];
					$qz->mock_sort_order = isset( $rel['sort_order'] ) ? (int) $rel['sort_order'] : 0;
					$qz->mock_series_id  = isset( $rel['series_id'] ) ? (int) $rel['series_id'] : 0;
					$list[] = $qz;
				}
			}
			usort( $list, function( $a, $b ) {
				if ( $a->mock_sort_order !== $b->mock_sort_order ) return $a->mock_sort_order <=> $b->mock_sort_order;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		// Direct mock quizzes query fallback
		if ( ( strpos( $query, "q.quiz_type = 'mock'" ) !== false || strpos( $query, "quiz_type = 'mock'" ) !== false ) && strpos( $query, 'mock_test_relations' ) === false ) {
			$sub_id = preg_match( "/q\.subject_id = (\d+)/", $query, $sm ) ? (int) $sm[1] : 0;
			$exam_cat = preg_match( "/q\.exam_category = '([^']+)'/", $query, $ecm ) ? $ecm[1] : null;

			$list = array();
			foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
				$qt = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : 'mock';
				$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
				if ( 'mock' !== $qt || 'published' !== $st ) continue;
				if ( $sub_id && isset( $qz['subject_id'] ) && (int) $qz['subject_id'] !== $sub_id ) continue;
				if ( $exam_cat && isset( $qz['exam_category'] ) && $qz['exam_category'] !== $exam_cat ) continue;

				$clone = (object) $qz;
				$clone->mock_rel_id     = 0;
				$clone->mock_sort_order = isset( $clone->display_order ) ? (int) $clone->display_order : 0;
				$clone->mock_series_id  = 0;
				$list[] = $clone;
			}
			usort( $list, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		return $results;
	}

	public function get_row( $query ) {
		// Match single book
		if ( strpos( $query, 'wp_iaq_books' ) !== false ) {
			if ( preg_match( "/WHERE (?:b\.)?id = (\d+)/", $query, $m ) ) {
				$bid = (int) $m[1];
				if ( isset( $this->data['tables']['wp_iaq_books'][ $bid ] ) ) {
					$bk = (object) $this->data['tables']['wp_iaq_books'][ $bid ];
					$bk->subject_name = ( ! empty( $bk->subject_id ) && isset( $this->data['tables']['wp_iaq_subjects'][ $bk->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $bk->subject_id ]['name'] : '';
					$bk->examination_name = ( ! empty( $bk->examination_id ) && isset( $this->data['tables']['wp_iaq_examinations'][ $bk->examination_id ] ) ) ? $this->data['tables']['wp_iaq_examinations'][ $bk->examination_id ]['name'] : '';
					$cnt = 0;
					foreach ( $this->data['tables']['wp_iaq_book_quizzes'] as $bq ) {
						if ( (int) $bq['book_id'] === $bk->id ) {
							if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ] ) ) {
								$st = isset( $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ]['status'] ) ? $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ]['status'] : 'published';
								if ( 'published' === $st ) $cnt++;
							}
						}
					}
					$bk->practice_sets_count = $cnt;
					return $bk;
				}
			}
			if ( preg_match( "/WHERE (?:b\.)?slug = '([^']+)'/", $query, $m ) ) {
				$slug = $m[1];
				foreach ( $this->data['tables']['wp_iaq_books'] as $bk_raw ) {
					if ( isset( $bk_raw['slug'] ) && $bk_raw['slug'] === $slug ) {
						$clone = (object) $bk_raw;
						$clone->subject_name = ( ! empty( $clone->subject_id ) && isset( $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ]['name'] : '';
						$clone->examination_name = ( ! empty( $clone->examination_id ) && isset( $this->data['tables']['wp_iaq_examinations'][ $clone->examination_id ] ) ) ? $this->data['tables']['wp_iaq_examinations'][ $clone->examination_id ]['name'] : '';
						$cnt = 0;
						foreach ( $this->data['tables']['wp_iaq_book_quizzes'] as $bq ) {
							if ( (int) $bq['book_id'] === $clone->id ) {
								if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ] ) ) {
									$st = isset( $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ]['status'] ) ? $this->data['tables']['wp_iaq_quizzes'][ $bq['quiz_id'] ]['status'] : 'published';
									if ( 'published' === $st ) $cnt++;
								}
							}
						}
						$clone->practice_sets_count = $cnt;
						return $clone;
					}
				}
			}
		}

		// Latest attempt for practice sets enrichment
		if ( strpos( $query, 'wp_iaq_quiz_attempts' ) !== false && preg_match( "/WHERE quiz_id = (\d+) AND user_id = (\d+)/", $query, $m ) && strpos( $query, 'ORDER BY id DESC' ) !== false ) {
			$quiz_id = (int) $m[1];
			$user_id = (int) $m[2];
			foreach ( array_reverse( $this->data['tables']['wp_iaq_quiz_attempts'] ) as $att ) {
				if ( (int) $att['quiz_id'] === $quiz_id && (int) $att['user_id'] === $user_id ) {
					return (object) $att;
				}
			}
			return null;
		}

		// Cumulative dashboard stats
		if ( strpos( $query, 'COUNT(id) AS total_attempts' ) !== false && preg_match( "/WHERE user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			$total = 0;
			$completed = 0;
			$passed = 0;
			$sum_pct = 0.0;
			$sum_score = 0.0;
			$best_pct = 0.0;
			$tot_q = 0;
			$tot_cor = 0;
			foreach ( $this->data['tables']['wp_iaq_quiz_attempts'] as $att ) {
				if ( $att['user_id'] == $uid ) {
					$total++;
					if ( 'completed' === $att['status'] ) {
						$completed++;
						if ( ! empty( $att['passed'] ) ) $passed++;
						$sum_pct += (float) $att['percentage'];
						$sum_score += (float) $att['score'];
						if ( (float) $att['percentage'] > $best_pct ) $best_pct = (float) $att['percentage'];
						$tot_q += (int) $att['total_questions'];
						$tot_cor += (int) $att['correct_answers'];
					}
				}
			}
			$res = new stdClass();
			$res->total_attempts = $total;
			$res->completed_attempts = $completed;
			$res->in_progress_attempts = $total - $completed;
			$res->passed_attempts = $passed;
			$res->avg_percentage = $completed > 0 ? ( $sum_pct / $completed ) : null;
			$res->avg_score = $completed > 0 ? ( $sum_score / $completed ) : null;
			$res->best_percentage = $best_pct;
			$res->total_questions_solved = $tot_q;
			$res->total_questions_attempted = $tot_cor;
			$res->total_correct_solved = $tot_cor;
			return $res;
		}

		// Active user attempt
		if ( strpos( $query, "a.status = 'in_progress'" ) !== false && preg_match( "/WHERE a\.user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			foreach ( array_reverse( $this->data['tables']['wp_iaq_quiz_attempts'] ) as $att ) {
				if ( $att['user_id'] == $uid && 'in_progress' === $att['status'] ) {
					$clone = (object) $att;
					if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $att['quiz_id'] ] ) ) {
						$qz = $this->data['tables']['wp_iaq_quizzes'][ $att['quiz_id'] ];
						$clone->quiz_title = $qz['title'];
						$clone->duration_minutes = $qz['duration_minutes'];
						$clone->exam_category = $qz['exam_category'];
					}
					return $clone;
				}
			}
			return null;
		}

		// Active in_progress or single attempt
		if ( strpos( $query, 'wp_iaq_quiz_attempts' ) !== false ) {
			if ( preg_match( "/WHERE quiz_id = (\d+) AND user_id = (\d+) AND status = 'in_progress'/", $query, $m ) ) {
				$quiz_id = (int) $m[1];
				$user_id = (int) $m[2];
				$matched = null;
				foreach ( $this->data['tables']['wp_iaq_quiz_attempts'] as $att ) {
					if ( $att['quiz_id'] == $quiz_id && $att['user_id'] == $user_id && $att['status'] === 'in_progress' ) {
						$matched = (object) $att;
					}
				}
				return $matched;
			}
			if ( preg_match( "/WHERE (?:a\.)?id = (\d+)/", $query, $m ) ) {
				$id = (int) $m[1];
				if ( isset( $this->data['tables']['wp_iaq_quiz_attempts'][ $id ] ) ) {
					$att = (object) $this->data['tables']['wp_iaq_quiz_attempts'][ $id ];
					if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $att->quiz_id ] ) ) {
						$qz = $this->data['tables']['wp_iaq_quizzes'][ $att->quiz_id ];
						$att->duration_minutes  = isset( $qz['duration_minutes'] ) ? $qz['duration_minutes'] : 0;
						$att->q_correct         = isset( $qz['correct_marks'] ) ? $qz['correct_marks'] : 1.0;
						$att->q_negative        = isset( $qz['negative_marks'] ) ? $qz['negative_marks'] : 0.25;
						$att->pass_percentage   = isset( $qz['pass_percentage'] ) ? $qz['pass_percentage'] : 40.0;
						$att->show_answers_mode = isset( $qz['show_answers_mode'] ) ? $qz['show_answers_mode'] : 'after_submission';
						$att->quiz_title        = isset( $qz['title'] ) ? $qz['title'] : '';
						$att->quiz_type         = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : 'practice';
						$att->exam_category     = isset( $qz['exam_category'] ) ? $qz['exam_category'] : 'General';
					}
					return $att;
				}
			}
		}

		if ( strpos( $query, 'wp_iaq_quizzes' ) !== false && preg_match( "/(WHERE q\.id = |WHERE id = )(\d+)/", $query, $m ) ) {
			$id = (int) $m[2];
			if ( isset( $this->data['tables']['wp_iaq_quizzes'][ $id ] ) ) {
				$qz = (object) $this->data['tables']['wp_iaq_quizzes'][ $id ];
				$qz->subject_name = ( $qz->subject_id && isset( $this->data['tables']['wp_iaq_subjects'][ $qz->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $qz->subject_id ]['name'] : '';
				$qz->topic_name   = ( $qz->topic_id && isset( $this->data['tables']['wp_iaq_topics'][ $qz->topic_id ] ) ) ? $this->data['tables']['wp_iaq_topics'][ $qz->topic_id ]['name'] : '';
				return $qz;
			}
		}

		if ( strpos( $query, 'wp_iaq_quizzes' ) !== false && preg_match( "/WHERE subject_id = (\d+)/", $query, $m ) ) {
			$sid = (int) $m[1];
			foreach ( $this->data['tables']['wp_iaq_quizzes'] as $qz ) {
				$st = isset( $qz['status'] ) ? $qz['status'] : 'published';
				if ( isset( $qz['subject_id'] ) && (int) $qz['subject_id'] === $sid && 'published' === $st ) {
					$clone = (object) $qz;
					$clone->subject_name = ( $clone->subject_id && isset( $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ] ) ) ? $this->data['tables']['wp_iaq_subjects'][ $clone->subject_id ]['name'] : '';
					$clone->topic_name   = ( $clone->topic_id && isset( $this->data['tables']['wp_iaq_topics'][ $clone->topic_id ] ) ) ? $this->data['tables']['wp_iaq_topics'][ $clone->topic_id ]['name'] : '';
					return $clone;
				}
			}
		}

		if ( preg_match( "/WHERE q\.id = (\d+)/", $query, $m ) ) {
			$id = (int) $m[1];
			if ( isset( $this->data['tables']['wp_iaq_questions'][ $id ] ) ) {
				$q = (object) $this->data['tables']['wp_iaq_questions'][ $id ];
				$q->subject_name = isset( $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ] ) ? $this->data['tables']['wp_iaq_subjects'][ $q->subject_id ]['name'] : '';
				$q->topic_name   = ( $q->topic_id && isset( $this->data['tables']['wp_iaq_topics'][ $q->topic_id ] ) ) ? $this->data['tables']['wp_iaq_topics'][ $q->topic_id ]['name'] : '';
				return $q;
			}
		}

		if ( preg_match( "/WHERE id = (\d+)/", $query, $m ) ) {
			$id = (int) $m[1];
			if ( strpos( $query, 'wp_iaq_subjects' ) !== false ) {
				return isset( $this->data['tables']['wp_iaq_subjects'][ $id ] ) ? (object) $this->data['tables']['wp_iaq_subjects'][ $id ] : null;
			}
			if ( strpos( $query, 'wp_iaq_topics' ) !== false ) {
				return isset( $this->data['tables']['wp_iaq_topics'][ $id ] ) ? (object) $this->data['tables']['wp_iaq_topics'][ $id ] : null;
			}
			if ( strpos( $query, 'wp_iaq_examinations' ) !== false ) {
				return isset( $this->data['tables']['wp_iaq_examinations'][ $id ] ) ? (object) $this->data['tables']['wp_iaq_examinations'][ $id ] : null;
			}
			if ( strpos( $query, 'wp_iaq_subscription_plans' ) !== false ) {
				return isset( $this->data['tables']['wp_iaq_subscription_plans'][ $id ] ) ? (object) $this->data['tables']['wp_iaq_subscription_plans'][ $id ] : null;
			}
			if ( strpos( $query, 'wp_iaq_user_subscriptions' ) !== false ) {
				return isset( $this->data['tables']['wp_iaq_user_subscriptions'][ $id ] ) ? (object) $this->data['tables']['wp_iaq_user_subscriptions'][ $id ] : null;
			}
		}

		if ( strpos( $query, 'wp_iaq_examinations' ) !== false && preg_match( "/WHERE (?:code = )'([^']+)'/", $query, $m ) ) {
			$cd = $m[1];
			foreach ( $this->data['tables']['wp_iaq_examinations'] as $ex ) {
				if ( isset( $ex['code'] ) && $ex['code'] === $cd ) return (object) $ex;
			}
		}

		if ( strpos( $query, 'wp_iaq_subscription_plans' ) !== false ) {
			if ( preg_match( "/WHERE (?:code = )'([^']+)'/", $query, $m ) ) {
				$cd = $m[1];
				foreach ( $this->data['tables']['wp_iaq_subscription_plans'] as $pl ) {
					if ( isset( $pl['code'] ) && $pl['code'] === $cd ) return (object) $pl;
				}
			}
			if ( preg_match( "/WHERE (?:wc_product_id = )(\d+)/", $query, $m ) ) {
				$pid = (int) $m[1];
				foreach ( $this->data['tables']['wp_iaq_subscription_plans'] as $pl ) {
					if ( isset( $pl['wc_product_id'] ) && (int) $pl['wc_product_id'] === $pid ) return (object) $pl;
				}
			}
		}

		// Single Category
		if ( strpos( $query, 'wp_iaq_mock_categories' ) !== false ) {
			if ( preg_match( "/WHERE (?:id = )(\d+)/", $query, $m ) ) {
				$cid = (int) $m[1];
				return isset( $this->data['tables']['wp_iaq_mock_categories'][ $cid ] ) ? (object) $this->data['tables']['wp_iaq_mock_categories'][ $cid ] : null;
			}
			if ( preg_match( "/WHERE (?:slug = )'([^']+)'/", $query, $m ) ) {
				$sl = $m[1];
				foreach ( $this->data['tables']['wp_iaq_mock_categories'] as $cat ) {
					if ( isset( $cat['slug'] ) && $cat['slug'] === $sl ) return (object) $cat;
				}
			}
		}

		// Single State or University
		if ( strpos( $query, 'wp_iaq_mock_states_universities' ) !== false ) {
			if ( preg_match( "/WHERE (?:id = )(\d+)/", $query, $m ) ) {
				$suid = (int) $m[1];
				return isset( $this->data['tables']['wp_iaq_mock_states_universities'][ $suid ] ) ? (object) $this->data['tables']['wp_iaq_mock_states_universities'][ $suid ] : null;
			}
		}

		// Single Vacancy
		if ( strpos( $query, 'wp_iaq_mock_vacancies' ) !== false ) {
			if ( preg_match( "/WHERE (?:id = )(\d+)/", $query, $m ) ) {
				$vid = (int) $m[1];
				return isset( $this->data['tables']['wp_iaq_mock_vacancies'][ $vid ] ) ? (object) $this->data['tables']['wp_iaq_mock_vacancies'][ $vid ] : null;
			}
		}

		// Single Series
		if ( strpos( $query, 'wp_iaq_mock_series' ) !== false ) {
			if ( preg_match( "/WHERE (?:id = )(\d+)/", $query, $m ) ) {
				$seid = (int) $m[1];
				return isset( $this->data['tables']['wp_iaq_mock_series'][ $seid ] ) ? (object) $this->data['tables']['wp_iaq_mock_series'][ $seid ] : null;
			}
		}

		return null;
	}

	public function get_var( $query ) {
		if ( strpos( $query, 'COUNT(*)' ) !== false ) {
			if ( strpos( $query, 'wp_iaq_quizzes' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_quizzes'] );
			}
			if ( strpos( $query, 'wp_iaq_questions' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_questions'] );
			}
			if ( strpos( $query, 'wp_iaq_subjects' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_subjects'] );
			}
			if ( strpos( $query, 'wp_iaq_topics' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_topics'] );
			}
			if ( strpos( $query, 'wp_iaq_examinations' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_examinations'] );
			}
			if ( strpos( $query, 'wp_iaq_subscription_plans' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_subscription_plans'] );
			}
			if ( strpos( $query, 'wp_iaq_user_subscriptions' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_user_subscriptions'] );
			}
			if ( strpos( $query, 'wp_iaq_mock_categories' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_mock_categories'] );
			}
			if ( strpos( $query, 'wp_iaq_mock_states_universities' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_mock_states_universities'] );
			}
			if ( strpos( $query, 'wp_iaq_mock_vacancies' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_mock_vacancies'] );
			}
			if ( strpos( $query, 'wp_iaq_mock_series' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_mock_series'] );
			}
			if ( strpos( $query, 'wp_iaq_mock_test_relations' ) !== false ) {
				return count( $this->data['tables']['wp_iaq_mock_test_relations'] );
			}
		}

		// Attempts MAX attempt number
		if ( strpos( $query, 'wp_iaq_quiz_attempts' ) !== false ) {
			if ( strpos( $query, 'MAX(attempt_number)' ) !== false && preg_match( "/WHERE quiz_id = (\d+) AND user_id = (\d+)/", $query, $m ) ) {
				$quiz_id = (int) $m[1];
				$user_id = (int) $m[2];
				$max_num = 0;
				foreach ( $this->data['tables']['wp_iaq_quiz_attempts'] as $att ) {
					if ( $att['quiz_id'] == $quiz_id && $att['user_id'] == $user_id && $att['attempt_number'] > $max_num ) {
						$max_num = (int) $att['attempt_number'];
					}
				}
				return $max_num;
			}
			if ( strpos( $query, 'COUNT(id)' ) !== false && preg_match( "/WHERE quiz_id = (\d+) AND user_id = (\d+) AND status = 'completed'/", $query, $m ) ) {
				$quiz_id = (int) $m[1];
				$user_id = (int) $m[2];
				$cnt = 0;
				foreach ( $this->data['tables']['wp_iaq_quiz_attempts'] as $att ) {
					if ( $att['quiz_id'] == $quiz_id && $att['user_id'] == $user_id && $att['status'] === 'completed' ) {
						$cnt++;
					}
				}
				return $cnt;
			}
		}

		// Attempt Answers get ID and Counts
		if ( strpos( $query, 'wp_iaq_attempt_answers' ) !== false ) {
			if ( preg_match( "/WHERE attempt_id = (\d+) AND question_id = (\d+)/", $query, $m ) ) {
				$att_id = (int) $m[1];
				$qid    = (int) $m[2];
				foreach ( $this->data['tables']['wp_iaq_attempt_answers'] as $ans ) {
					if ( $ans['attempt_id'] == $att_id && $ans['question_id'] == $qid ) {
						return $ans['id'];
					}
				}
				return null;
			}
			if ( strpos( $query, 'selected_option IS NOT NULL' ) !== false && preg_match( "/WHERE attempt_id = (\d+)/", $query, $m ) ) {
				$att_id = (int) $m[1];
				$cnt = 0;
				foreach ( $this->data['tables']['wp_iaq_attempt_answers'] as $ans ) {
					if ( $ans['attempt_id'] == $att_id && ! empty( $ans['selected_option'] ) ) {
						$cnt++;
					}
				}
				return $cnt;
			}
			if ( strpos( $query, 'is_marked_for_review = 1' ) !== false && preg_match( "/WHERE attempt_id = (\d+)/", $query, $m ) ) {
				$att_id = (int) $m[1];
				$cnt = 0;
				foreach ( $this->data['tables']['wp_iaq_attempt_answers'] as $ans ) {
					if ( $ans['attempt_id'] == $att_id && ! empty( $ans['is_marked_for_review'] ) ) {
						$cnt++;
					}
				}
				return $cnt;
			}
		}

		if ( strpos( $query, 'MAX(question_order)' ) !== false ) {
			$max = 0;
			foreach ( $this->data['tables']['wp_iaq_quiz_questions'] as $qq ) {
				if ( $qq['question_order'] > $max ) $max = $qq['question_order'];
			}
			return $max;
		}

		if ( strpos( $query, 'wp_iaq_quiz_questions' ) !== false && preg_match( "/quiz_id = (\d+) AND question_id = (\d+)/", $query, $m ) ) {
			$quiz_id = (int) $m[1];
			$qid     = (int) $m[2];
			foreach ( $this->data['tables']['wp_iaq_quiz_questions'] as $qq ) {
				if ( $qq['quiz_id'] == $quiz_id && $qq['question_id'] == $qid ) {
					return $qq['id'];
				}
			}
			return null;
		}
		// Books get_var
		if ( strpos( $query, 'wp_iaq_books' ) !== false ) {
			if ( preg_match( "/slug = '([^']+)'/", $query, $sm ) ) {
				$slug = $sm[1];
				$ex_id = 0;
				if ( preg_match( "/id != (\d+)/", $query, $id_m ) ) {
					$ex_id = (int) $id_m[1];
				}
				foreach ( $this->data['tables']['wp_iaq_books'] as $bk ) {
					if ( isset( $bk['slug'] ) && $bk['slug'] === $slug && (int) $bk['id'] !== $ex_id ) {
						return $bk['id'];
					}
				}
				return null;
			}
		}

		// Book quizzes get_var
		if ( strpos( $query, 'wp_iaq_book_quizzes' ) !== false ) {
			if ( preg_match( "/MAX\(sort_order\) FROM [^\s]+ WHERE book_id = (\d+)/", $query, $m ) ) {
				$bid = (int) $m[1];
				$max = 0;
				foreach ( $this->data['tables']['wp_iaq_book_quizzes'] as $bq ) {
					if ( (int) $bq['book_id'] === $bid && (int) $bq['sort_order'] > $max ) {
						$max = (int) $bq['sort_order'];
					}
				}
				return $max;
			}
			if ( preg_match( "/WHERE book_id = (\d+) AND quiz_id = (\d+)/", $query, $m ) ) {
				$bid = (int) $m[1];
				$qid = (int) $m[2];
				foreach ( $this->data['tables']['wp_iaq_book_quizzes'] as $bq ) {
					if ( (int) $bq['book_id'] === $bid && (int) $bq['quiz_id'] === $qid ) {
						return $bq['id'];
					}
				}
				return null;
			}
		}

		// Exam subjects get_var
		if ( strpos( $query, 'wp_iaq_exam_subjects' ) !== false ) {
			if ( preg_match( "/MAX\(display_order\) FROM [^\s]+ WHERE exam_id = (\d+)/", $query, $m ) ) {
				$eid = (int) $m[1];
				$max = 0;
				foreach ( $this->data['tables']['wp_iaq_exam_subjects'] as $es ) {
					if ( (int) $es['exam_id'] === $eid && (int) $es['display_order'] > $max ) {
						$max = (int) $es['display_order'];
					}
				}
				return $max;
			}
			if ( preg_match( "/WHERE exam_id = (\d+) AND subject_id = (\d+)/", $query, $m ) ) {
				$eid = (int) $m[1];
				$sid = (int) $m[2];
				foreach ( $this->data['tables']['wp_iaq_exam_subjects'] as $es ) {
					if ( (int) $es['exam_id'] === $eid && (int) $es['subject_id'] === $sid ) {
						return $es['id'];
					}
				}
				return null;
			}
		}

		// Exam subject quizzes get_var
		if ( strpos( $query, 'wp_iaq_exam_subject_quizzes' ) !== false ) {
			if ( preg_match( "/MAX\(sort_order\) FROM [^\s]+ WHERE exam_id = (\d+)/", $query, $m ) ) {
				$eid = (int) $m[1];
				$sid = (int) $m[2];
				$max = 0;
				foreach ( $this->data['tables']['wp_iaq_exam_subject_quizzes'] as $esq ) {
					if ( (int) $esq['exam_id'] === $eid && (int) $esq['subject_id'] === $sid && (int) $esq['sort_order'] > $max ) {
						$max = (int) $esq['sort_order'];
					}
				}
				return $max;
			}
			if ( preg_match( "/WHERE exam_id = (\d+) AND subject_id = (\d+) AND quiz_id = (\d+)/", $query, $m ) ) {
				$eid = (int) $m[1];
				$sid = (int) $m[2];
				$qid = (int) $m[3];
				foreach ( $this->data['tables']['wp_iaq_exam_subject_quizzes'] as $esq ) {
					if ( (int) $esq['exam_id'] === $eid && (int) $esq['subject_id'] === $sid && (int) $esq['quiz_id'] === $qid ) {
						return $esq['id'];
					}
				}
				return null;
			}
		}

		return null;
	}

	public function get_col( $query ) {
		$col = array();
		if ( strpos( $query, 'SELECT id FROM wp_iaq_questions' ) !== false ) {
			foreach ( $this->data['tables']['wp_iaq_questions'] as $q ) {
				$col[] = $q['id'];
			}
		}
		return $col;
	}

	public function query( $query ) {
		return 1;
	}
}

global $wpdb;
$wpdb = new Local_Persistent_WPDB( $data_file );

// Mock WordPress functions.
function get_option( $option, $default = false ) {
	global $wpdb;
	return isset( $wpdb->data['options'][ $option ] ) ? $wpdb->data['options'][ $option ] : $default;
}

function update_option( $option, $value ) {
	global $wpdb;
	$wpdb->data['options'][ $option ] = $value;
	$wpdb->save();
	return true;
}

function delete_option( $option ) {
	global $wpdb;
	unset( $wpdb->data['options'][ $option ] );
	$wpdb->save();
	return true;
}

function delete_transient( $t ) { return true; }
function current_user_can( $c ) { return true; }

function get_user_meta( $user_id, $key = '', $single = false ) {
	global $wpdb;
	if ( ! isset( $wpdb->data['user_meta'] ) ) {
		$wpdb->data['user_meta'] = array();
	}
	if ( empty( $key ) ) {
		return isset( $wpdb->data['user_meta'][ $user_id ] ) ? $wpdb->data['user_meta'][ $user_id ] : array();
	}
	if ( isset( $wpdb->data['user_meta'][ $user_id ][ $key ] ) ) {
		return $single ? $wpdb->data['user_meta'][ $user_id ][ $key ] : array( $wpdb->data['user_meta'][ $user_id ][ $key ] );
	}
	return $single ? '' : array();
}

function update_user_meta( $user_id, $key, $value ) {
	global $wpdb;
	if ( ! isset( $wpdb->data['user_meta'] ) ) {
		$wpdb->data['user_meta'] = array();
	}
	if ( ! isset( $wpdb->data['user_meta'][ $user_id ] ) ) {
		$wpdb->data['user_meta'][ $user_id ] = array();
	}
	$wpdb->data['user_meta'][ $user_id ][ $key ] = $value;
	$wpdb->save();
	return true;
}

function delete_user_meta( $user_id, $key ) {
	global $wpdb;
	if ( isset( $wpdb->data['user_meta'][ $user_id ][ $key ] ) ) {
		unset( $wpdb->data['user_meta'][ $user_id ][ $key ] );
		$wpdb->save();
	}
	return true;
}

function is_user_logged_in() {
	if ( isset( $_GET['student_view'] ) && 'guest' === $_GET['student_view'] ) {
		return false;
	}
	return true;
}

function get_current_user_id() {
	if ( isset( $_GET['student_view'] ) ) {
		if ( 'guest' === $_GET['student_view'] ) return 0;
		if ( 'new_student' === $_GET['student_view'] ) return 999;
		if ( 'subscribed' === $_GET['student_view'] ) return 2;
	}
	return 1;
}

function wp_get_current_user() {
	$uid = get_current_user_id();
	if ( $uid === 0 ) {
		return (object) array( 'ID' => 0, 'display_name' => '', 'user_login' => '', 'user_email' => '' );
	}
	if ( $uid === 999 ) {
		return (object) array( 'ID' => 999, 'display_name' => 'Aarav Sharma (New Student)', 'user_login' => 'aarav', 'user_email' => 'aarav@indianagriexam.com' );
	}
	if ( $uid === 2 ) {
		return (object) array( 'ID' => 2, 'display_name' => 'Priya Patel (Premium)', 'user_login' => 'priya', 'user_email' => 'priya@indianagriexam.com' );
	}
	return (object) array(
		'ID'           => 1,
		'display_name' => 'Virendra Pratap',
		'user_login'   => 'virendra',
		'user_email'   => 'student@indianagriexam.com',
	);
}

function get_userdata( $user_id ) {
	$user_id = (int) $user_id;
	if ( 1 === $user_id ) {
		return (object) array(
			'ID'           => 1,
			'display_name' => 'Virendra Pratap',
			'user_login'   => 'virendra',
			'user_email'   => 'student@indianagriexam.com',
			'first_name'   => 'Virendra',
			'last_name'    => 'Pratap',
		);
	}
	if ( 2 === $user_id ) {
		return (object) array(
			'ID'           => 2,
			'display_name' => 'Priya Patel',
			'user_login'   => 'priya',
			'user_email'   => 'priya@indianagriexam.com',
			'first_name'   => 'Priya',
			'last_name'    => 'Patel',
		);
	}
	return (object) array(
		'ID'           => $user_id,
		'display_name' => 'Student #' . $user_id,
		'user_login'   => 'student' . $user_id,
		'user_email'   => 'student' . $user_id . '@indianagriexam.com',
		'first_name'   => 'Student',
		'last_name'    => '#' . $user_id,
	);
}
function get_user_by( $field, $value ) {
	return get_userdata( 1 );
}
function current_time( $t ) { return ( 'timestamp' === $t ) ? time() : gmdate( 'Y-m-d H:i:s' ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_title( $t ) {
	$t = strtolower( trim( (string) $t ) );
	$t = preg_replace( '/[^a-z0-9_\-\s]/', '', $t );
	return preg_replace( '/[\s_]+/', '-', $t );
}
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_hex_color( $c ) { return preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $c ) ? $c : ''; }
function sanitize_file_name( $n ) { return preg_replace( '/[^a-zA-Z0-9_\.\-]/', '', (string) $n ); }
function wp_kses_post( $s ) { return (string) $s; }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $u ) { return (string) $u; }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $t, $d = 'default' ) { return esc_html( $t ); }
function esc_html_e( $t, $d = 'default' ) { echo esc_html( $t ); }
function esc_attr__( $t, $d = 'default' ) { return esc_attr( $t ); }
function esc_attr_e( $t, $d = 'default' ) { echo esc_attr( $t ); }
function __( $t, $d = 'default' ) { return $t; }
function _e( $t, $d = 'default' ) { echo $t; }
function sanitize_html_class( $c, $f = '' ) {
	$s = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c );
	return '' === $s ? $f : $s;
}
function absint( $i ) { return abs( intval( $i ) ); }
function wp_parse_args( $a, $d = array() ) { return array_merge( $d, (array) $a ); }
function wp_json_encode( $d ) { return json_encode( $d, JSON_UNESCAPED_UNICODE ); }
function number_format_i18n( $n ) { return number_format( (float) $n ); }
function date_i18n( $f, $t = false ) { return date( $f, false === $t ? time() : $t ); }
function apply_filters( $tag, $v ) { return $v; }
function add_action( $t, $c, $p = 10, $a = 1 ) { return true; }
function do_action( $t, ...$a ) { return true; }
function add_shortcode( $t, $c ) { return true; }
function shortcode_atts( $pairs, $atts, $s = '' ) {
	$out = array();
	foreach ( $pairs as $name => $default ) {
		$out[ $name ] = array_key_exists( $name, (array) $atts ) ? $atts[ $name ] : $default;
	}
	return $out;
}
function wp_register_style() { return true; }
function wp_enqueue_style() { return true; }
function wp_enqueue_script() { return true; }
function wp_localize_script() { return true; }
function is_admin() { return true; }
function wpautop( $pee ) { return '<p>' . nl2br( (string) $pee ) . '</p>'; }
function wp_trim_words( $text, $num_words = 55, $more = null ) {
	if ( null === $more ) {
		$more = '&hellip;';
	}
	$text = wp_strip_all_tags( (string) $text );
	$words = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
	if ( count( $words ) > $num_words ) {
		array_pop( $words );
		return implode( ' ', $words ) . $more;
	}
	return implode( ' ', $words );
}
function wp_list_pluck( $list, $field ) {
	$res = array();
	foreach ( $list as $item ) {
		if ( is_object( $item ) && isset( $item->$field ) ) $res[] = $item->$field;
		elseif ( is_array( $item ) && isset( $item[ $field ] ) ) $res[] = $item[ $field ];
	}
	return $res;
}
function get_permalink( $post = 0 ) {
	return '/?page=iaq-preview-student-quiz';
}
function wp_login_url( $r = '' ) { return '#login'; }
function wp_create_nonce( $a = '' ) { return md5( $a ); }
function check_admin_referer( $a = '' ) { return true; }
function check_ajax_referer( $a = '', $q = '' ) { return true; }
function wp_verify_nonce( $n, $a = '' ) { return true; }
function wp_nonce_field( $a = 'nonce' ) { echo '<input type="hidden" name="_wpnonce" value="' . md5( $a ) . '">'; }
function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
	return add_query_arg( $name, md5( $action ), $actionurl );
}
function admin_url( $path = '' ) {
	return '/' . ( $path ? '?' . ltrim( $path, '?' ) : '' );
}
function home_url( $path = '' ) {
	return '/' . ltrim( $path, '/' );
}
function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ) ) {
		$params = $args[0];
		$url    = isset( $args[1] ) ? $args[1] : '';
	} else {
		$params = array( $args[0] => $args[1] );
		$url    = isset( $args[2] ) ? $args[2] : '';
	}
	$parts = explode( '?', $url, 2 );
	$base  = $parts[0];
	$existing = array();
	if ( isset( $parts[1] ) ) {
		parse_str( $parts[1], $existing );
	}
	$merged = array_merge( $existing, $params );
	return $base . '?' . http_build_query( $merged );
}
function wp_safe_redirect( $location ) {
	header( 'Location: ' . $location );
	exit;
}
function wp_send_json_success( $data = null ) {
	header( 'Content-Type: application/json; charset=utf-8' );
	echo json_encode( array( 'success' => true, 'data' => $data ) );
	exit;
}
function wp_send_json_error( $data = null ) {
	header( 'Content-Type: application/json; charset=utf-8' );
	echo json_encode( array( 'success' => false, 'data' => $data ) );
	exit;
}
function selected( $selected, $current = true, $echo = true ) {
	$result = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
	if ( $echo ) echo $result;
	return $result;
}
function checked( $checked, $current = true, $echo = true ) {
	$result = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
	if ( $echo ) echo $result;
	return $result;
}
function disabled( $disabled, $current = true, $echo = true ) {
	$result = ( (string) $disabled === (string) $current ) ? ' disabled="disabled"' : '';
	if ( $echo ) echo $result;
	return $result;
}
function paginate_links( $args = '' ) {
	$current = isset( $args['current'] ) ? $args['current'] : 1;
	$total   = isset( $args['total'] ) ? $args['total'] : 1;
	$html    = '';
	for ( $i = 1; $i <= $total; $i++ ) {
		if ( $i == $current ) {
			$html .= '<span class="current">' . $i . '</span>';
		} else {
			$html .= '<a href="' . esc_url( add_query_arg( 'paged', $i ) ) . '">' . $i . '</a>';
		}
	}
	return $html;
}
function wp_editor( $content, $editor_id, $settings = array() ) {
	$textarea_name = isset( $settings['textarea_name'] ) ? $settings['textarea_name'] : $editor_id;
	$rows          = isset( $settings['textarea_rows'] ) ? $settings['textarea_rows'] : 5;
	echo '<textarea name="' . esc_attr( $textarea_name ) . '" id="' . esc_attr( $editor_id ) . '" rows="' . esc_attr( $rows ) . '" class="iaq-textarea iaq-full-width" style="font-family:inherit;font-size:14px;">' . esc_textarea( $content ) . '</textarea>';
}
function dbDelta( $sql ) { return array(); }

class WP_Error {
	private $errors = array();
	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( ! empty( $code ) ) $this->errors[ $code ][] = $message;
	}
	public function get_error_message( $code = '' ) {
		if ( empty( $code ) ) $code = key( $this->errors );
		return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
	}
}
function is_wp_error( $t ) { return ( $t instanceof WP_Error ); }

// Load Plugin Classes.
require_once IAQ_PLUGIN_DIR . 'includes/class-database.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-activator.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-question-bank.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-examinations.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-access-control.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-quiz-builder.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-practice-sets.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-mock-tests.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-quiz-attempt.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-bookmarks.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-wrong-notebook.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-custom-practice.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-leaderboard.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-study-streaks.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-import-export.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-frontend.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-admin.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-plugin.php';

IAQ_Access_Control::init();

// Initial Seed if tables empty.
if ( empty( $wpdb->data['tables']['wp_iaq_subjects'] ) || empty( $wpdb->data['tables']['wp_iaq_examinations'] ) || empty( $wpdb->data['tables']['wp_iaq_subscription_plans'] ) || empty( $wpdb->data['tables']['wp_iaq_books'] ) || empty( $wpdb->data['tables']['wp_iaq_mock_categories'] ) ) {
	IAQ_Database::seed_default_data();

	// Seed Sample Questions (English and Hindi).
	$s_agronomy = 1;
	$q1 = IAQ_Question_Bank::save_question(
		array(
			'subject_id'     => $s_agronomy,
			'exam_category'  => 'ICAR AIEEA (UG / PG)',
			'difficulty'     => 'easy',
			'question_text'  => 'Which of the following is known as the "King of Cereals"?',
			'option_a'       => 'Rice (Oryza sativa)',
			'option_b'       => 'Wheat (Triticum aestivum)',
			'option_c'       => 'Maize (Zea mays)',
			'option_d'       => 'Barley (Hordeum vulgare)',
			'correct_option' => 'B',
			'marks'          => 1.00,
			'negative_marks' => 0.25,
			'explanation'    => 'Wheat is universally regarded as the King of Cereals due to its vast acreage and dietary calorie contribution.',
			'status'         => 'published',
		)
	);

	$q2 = IAQ_Question_Bank::save_question(
		array(
			'subject_id'     => $s_agronomy,
			'exam_category'  => 'IBPS AFO (Mains)',
			'difficulty'     => 'medium',
			'question_text'  => 'धान (Rice) में "खैरा रोग" (Khaira Disease) किसकी कमी के कारण होता है?',
			'option_a'       => 'नाइट्रोजन (N)',
			'option_b'       => 'फास्फोरस (P)',
			'option_c'       => 'जस्ता (Zinc - Zn)',
			'option_d'       => 'लोहा (Iron - Fe)',
			'correct_option' => 'C',
			'marks'          => 1.00,
			'negative_marks' => 0.25,
			'explanation'    => 'धान में खैरा रोग जिंक (Zn) की कमी के कारण होता है। इसकी खोज डॉ. वाई. एल. नेने ने पंतनगर में की थी।',
			'status'         => 'published',
		)
	);

	$q3 = IAQ_Question_Bank::save_question(
		array(
			'subject_id'     => $s_agronomy,
			'exam_category'  => 'ICAR JRF / SRF / NET',
			'difficulty'     => 'medium',
			'question_text'  => 'What is the optimum seed rate for Hybrid Rice per hectare in India?',
			'option_a'       => '12 - 15 kg/ha',
			'option_b'       => '25 - 30 kg/ha',
			'option_c'       => '40 - 50 kg/ha',
			'option_d'       => '75 - 100 kg/ha',
			'correct_option' => 'A',
			'marks'          => 1.00,
			'negative_marks' => 0.25,
			'explanation'    => 'The recommended seed rate for hybrid rice production under transplanted conditions is 12 to 15 kg per hectare.',
			'status'         => 'published',
		)
	);

	$q4 = IAQ_Question_Bank::save_question(
		array(
			'subject_id'     => 2, // Soil Science
			'exam_category'  => 'State Agriculture PSC',
			'difficulty'     => 'hard',
			'question_text'  => 'काली मिट्टी (Black Soil) में किस क्ले खनिज (Clay Mineral) की प्रधानता होती है?',
			'option_a'       => 'काओलिनाइट (Kaolinite)',
			'option_b'       => 'मोंटमोरिलोनाइट (Montmorillonite)',
			'option_c'       => 'इलाइट (Illite)',
			'option_d'       => 'क्लोराइट (Chlorite)',
			'correct_option' => 'B',
			'marks'          => 1.00,
			'negative_marks' => 0.33,
			'explanation'    => 'काली मिट्टी (Vertisol) में मोंटमोरिलोनाइट खनिज की अधिकता होती है, जिससे इसमें फूलने और सिकुड़ने का गुण पाया जाता है।',
			'status'         => 'published',
		)
	);

	// Seed Sample Quizzes.
	$quiz1 = IAQ_Quiz_Builder::save_quiz(
		array(
			'title'             => 'ICAR JRF Agronomy Mock Test 01 (General Agriculture & Field Crops)',
			'slug'              => 'icar-jrf-agronomy-mock-01',
			'quiz_type'         => 'mock',
			'exam_category'     => 'ICAR JRF / SRF / NET',
			'subject_id'        => 1,
			'duration_minutes'  => 60,
			'correct_marks'     => 1.00,
			'negative_marks'    => 0.25,
			'pass_percentage'   => 40.00,
			'shuffle_questions' => 1,
			'shuffle_options'   => 1,
			'max_attempts'      => 3,
			'access_type'       => 'free',
			'instructions'      => "1. Total 4 questions in this demonstration mock test.\n2. Each correct answer carries +1 mark, and 0.25 mark will be deducted for each incorrect answer.\n3. Complete the exam within 60 minutes.",
			'status'            => 'published',
		)
	);

	IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz1, array( (int) $q1, (int) $q2, (int) $q3, (int) $q4 ) );

	if ( class_exists( 'IAQ_Mock_Tests' ) ) {
		IAQ_Mock_Tests::attach_quiz_to_mock_context(
			$quiz1,
			array(
				'category_id' => 1,
				'exam_id'     => 1,
				'subject_id'  => 1,
				'sort_order'  => 1,
			)
		);
		IAQ_Mock_Tests::attach_quiz_to_mock_context(
			$quiz1,
			array(
				'category_id' => 8,
				'subject_id'  => 1,
				'sort_order'  => 1,
			)
		);
	}

	$quiz2 = IAQ_Quiz_Builder::save_quiz(
		array(
			'title'             => 'Soil Science & Nutrient Management Practice Set 01',
			'slug'              => 'soil-science-practice-01',
			'quiz_type'         => 'practice',
			'exam_category'     => 'State Agriculture PSC',
			'subject_id'        => 2,
			'duration_minutes'  => 0, // untimed
			'correct_marks'     => 2.00,
			'negative_marks'    => 0.00,
			'pass_percentage'   => 50.00,
			'access_type'       => 'free',
			'instructions'      => 'Self-paced practice set for state agricultural competitive examinations.',
			'status'            => 'published',
		)
	);
	IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz2, array( (int) $q4 ) );
}

// Ensure Mock Categories and Relations are populated in sandbox even if pre-existing data.json
if ( empty( $wpdb->data['tables']['wp_iaq_mock_categories'] ) ) {
	IAQ_Database::seed_default_data();
}
if ( empty( $wpdb->data['tables']['wp_iaq_mock_test_relations'] ) ) {
	foreach ( $wpdb->data['tables']['wp_iaq_quizzes'] as $qz ) {
		$qt = isset( $qz['quiz_type'] ) ? $qz['quiz_type'] : '';
		if ( 'mock' === $qt ) {
			IAQ_Mock_Tests::attach_quiz_to_mock_context(
				(int) $qz['id'],
				array(
					'category_id' => 1,
					'exam_id'     => 1,
					'subject_id'  => 1,
					'sort_order'  => 1,
				)
			);
			IAQ_Mock_Tests::attach_quiz_to_mock_context(
				(int) $qz['id'],
				array(
					'category_id' => 8,
					'subject_id'  => 1,
					'sort_order'  => 1,
				)
			);
			break;
		}
	}
}

// Instantiate Admin, Frontend, and Attempt controllers.
$admin_controller    = new IAQ_Admin();
$frontend_controller = new IAQ_Frontend();
$attempt_controller  = new IAQ_Quiz_Attempt();

// Handle AJAX actions.
$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';

if ( 'iaq_get_topics_by_subject' === $action ) {
	$subject_id = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;
	$topics     = IAQ_Question_Bank::get_topics( $subject_id );
	wp_send_json_success( array( 'topics' => $topics ) );
}

if ( 'iaq_start_quiz_attempt' === $action ) {
	$attempt_controller->ajax_start_or_resume_attempt();
}

if ( 'iaq_save_answer' === $action ) {
	$attempt_controller->ajax_save_answer();
}

if ( 'iaq_submit_quiz_attempt' === $action ) {
	$attempt_controller->ajax_submit_quiz_attempt();
}

if ( 'iaq_get_attempt_review' === $action ) {
	$attempt_controller->ajax_get_attempt_review();
}

if ( 'iaq_toggle_bookmark' === $action ) {
	$frontend_controller->ajax_toggle_bookmark();
}

if ( 'iaq_create_revision_quiz' === $action ) {
	$frontend_controller->ajax_create_revision_quiz();
}

if ( 'iaq_update_notebook_status' === $action ) {
	$frontend_controller->ajax_update_notebook_status();
}

if ( 'iaq_remove_notebook_entry' === $action ) {
	$frontend_controller->ajax_remove_notebook_entry();
}

if ( 'iaq_create_retry_quiz' === $action ) {
	$frontend_controller->ajax_create_retry_quiz();
}

if ( 'iaq_generate_custom_test' === $action ) {
	$frontend_controller->ajax_generate_custom_test();
}

if ( 'iaq_update_study_goals' === $action ) {
	$frontend_controller->ajax_update_study_goals();
}

if ( 'iaq_get_leaderboard' === $action ) {
	$frontend_controller->ajax_get_leaderboard();
}

// Process POST/GET Form Submissions.
$admin_controller->handle_form_submissions();

// Determine Page to Render.
$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'iaq-question-bank';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>IndianAgri Quiz Engine — Local Development Sandbox</title>
	<!-- Dashicons & Google Fonts -->
	<link rel="stylesheet" href="https://s.w.org/wp-includes/css/dashicons.min.css">
	<link rel="stylesheet" href="/assets/css/admin.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="/assets/css/frontend.css?v=<?php echo time(); ?>">
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script>
		window.iaqAdminData = {
			ajaxUrl: '/?action=iaq_get_topics_by_subject',
			nonce: '<?php echo wp_create_nonce( 'iaq_admin_ajax_nonce' ); ?>',
			i18n: {
				confirmDelete: 'Are you sure you want to delete this item? This action cannot be undone.',
				selectTopic: '— Select Topic —',
				allTopics: 'All Topics',
				loading: 'Loading...'
			}
		};
		window.iaqQuizData = {
			ajaxUrl: '/',
			nonce: '<?php echo wp_create_nonce( 'iaq_quiz_nonce' ); ?>',
			i18n: {
				starting: 'Initializing examination session...',
				saving: 'Saving...',
				saved: 'Synchronized',
				submitting: 'Submitting examination...',
				confirmSubmit: 'Are you sure you want to submit your examination?',
				leaveConfirm: 'You have an examination in progress. Leaving this page may cause progress loss.'
			}
		};
	</script>
	<script src="/assets/js/admin.js?v=<?php echo time(); ?>"></script>
	<script src="/assets/js/quiz-engine.js?v=<?php echo time(); ?>"></script>
	<style>
		body {
			background: #f1f5f9;
			margin: 0;
			padding: 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}
		.iaq-sandbox-bar {
			background: #0f172a;
			color: #ffffff;
			padding: 10px 24px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 12px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.15);
		}
		.iaq-sandbox-brand {
			display: flex;
			align-items: center;
			gap: 10px;
			font-size: 15px;
			font-weight: 700;
		}
		.iaq-sandbox-brand-dot {
			width: 10px;
			height: 10px;
			border-radius: 50%;
			background: #22c55e;
			box-shadow: 0 0 8px #22c55e;
		}
		.iaq-sandbox-nav {
			display: flex;
			align-items: center;
			gap: 6px;
			flex-wrap: wrap;
		}
		.iaq-nav-pill {
			color: #cbd5e1;
			text-decoration: none;
			font-size: 12px;
			font-weight: 600;
			padding: 5px 10px;
			border-radius: 6px;
			background: #1e293b;
			transition: all 0.15s ease;
		}
		.iaq-nav-pill:hover,
		.iaq-nav-pill.is-active {
			background: #117E3A;
			color: #ffffff;
		}
		.iaq-nav-pill.is-frontend {
			background: #0369a1;
		}
		.iaq-nav-pill.is-frontend:hover,
		.iaq-nav-pill.is-frontend.is-active {
			background: #0284c7;
		}
		.iaq-sandbox-container {
			max-width: 1300px;
			margin: 24px auto;
			padding: 0 20px;
		}
		.iaq-sandbox-container.is-fullwidth {
			max-width: 100%;
			margin: 0;
			padding: 0;
		}
		@media (max-width: 900px) {
			.iaq-sandbox-bar {
				padding: 8px 12px;
			}
			.iaq-sandbox-nav {
				overflow-x: auto;
				white-space: nowrap;
				flex-wrap: nowrap;
				width: 100%;
				padding-bottom: 4px;
				-webkit-overflow-scrolling: touch;
			}
		}
	</style>
</head>
<body>

	<!-- Top Navigation Bar -->
	<header class="iaq-sandbox-bar">
		<div class="iaq-sandbox-brand">
			<div class="iaq-sandbox-brand-dot"></div>
			<span>IndianAgriExam.com</span>
			<span style="color:#94a3b8;font-size:12px;font-weight:400;">| Quiz Engine Sandbox (Port 8000)</span>
		</div>
		<nav class="iaq-sandbox-nav">
			<a href="/?page=iaq-dashboard" class="iaq-nav-pill <?php echo 'iaq-dashboard' === $current_page ? 'is-active' : ''; ?>">
				<span class="dashicons dashicons-dashboard" style="font-size:14px;vertical-align:middle;"></span> Dashboard
			</a>
			<a href="/?page=iaq-examinations" class="iaq-nav-pill <?php echo 'iaq-examinations' === $current_page ? 'is-active' : ''; ?>">
				Examinations
			</a>
			<a href="/?page=iaq-books" class="iaq-nav-pill <?php echo 'iaq-books' === $current_page ? 'is-active' : ''; ?>">
				Books
			</a>
			<a href="/?page=iaq-subjects-topics" class="iaq-nav-pill <?php echo 'iaq-subjects-topics' === $current_page ? 'is-active' : ''; ?>">
				Taxonomy
			</a>
			<a href="/?page=iaq-question-bank" class="iaq-nav-pill <?php echo 'iaq-question-bank' === $current_page ? 'is-active' : ''; ?>">
				Question Bank
			</a>
			<a href="/?page=iaq-add-question" class="iaq-nav-pill <?php echo 'iaq-add-question' === $current_page ? 'is-active' : ''; ?>">
				+ Add MCQ
			</a>
			<a href="/?page=iaq-quizzes" class="iaq-nav-pill <?php echo 'iaq-quizzes' === $current_page ? 'is-active' : ''; ?>">
				Quizzes & Mocks
			</a>
			<a href="/?page=iaq-mock-tests" class="iaq-nav-pill <?php echo 'iaq-mock-tests' === $current_page ? 'is-active' : ''; ?>" style="border-left: 2px solid #22c55e;">
				<span class="dashicons dashicons-networking" style="font-size:13px;vertical-align:middle;"></span> Mock Hierarchy
			</a>
			<a href="/?page=iaq-add-quiz" class="iaq-nav-pill <?php echo 'iaq-add-quiz' === $current_page ? 'is-active' : ''; ?>">
				+ Create Quiz
			</a>
			<a href="/?page=iaq-subscription-plans" class="iaq-nav-pill <?php echo 'iaq-subscription-plans' === $current_page ? 'is-active' : ''; ?>">
				Plans (₹)
			</a>
			<a href="/?page=iaq-user-subscriptions" class="iaq-nav-pill <?php echo 'iaq-user-subscriptions' === $current_page ? 'is-active' : ''; ?>">
				Subscribers
			</a>
			<a href="/?page=iaq-attempts" class="iaq-nav-pill <?php echo 'iaq-attempts' === $current_page ? 'is-active' : ''; ?>">
				Attempts & Results
			</a>
			<a href="/?page=iaq-import-export" class="iaq-nav-pill <?php echo 'iaq-import-export' === $current_page ? 'is-active' : ''; ?>">
				Import/Export
			</a>
			<a href="/?page=iaq-settings" class="iaq-nav-pill <?php echo 'iaq-settings' === $current_page ? 'is-active' : ''; ?>">
				Settings
			</a>
			<a href="/?page=iaq-preview-mock-tests" class="iaq-nav-pill is-frontend <?php echo 'iaq-preview-mock-tests' === $current_page ? 'is-active' : ''; ?>" style="background:#117E3A;color:#fff;">
				[iaq_mock_tests]
			</a>
			<a href="/?page=iaq-preview-practice-sets" class="iaq-nav-pill is-frontend <?php echo 'iaq-preview-practice-sets' === $current_page ? 'is-active' : ''; ?>">
				[iaq_practice_sets]
			</a>
			<a href="/?page=iaq-preview-student-list" class="iaq-nav-pill is-frontend <?php echo 'iaq-preview-student-list' === $current_page ? 'is-active' : ''; ?>">
				[iaq_quiz_list]
			</a>
			<a href="/?page=iaq-preview-student-quiz" class="iaq-nav-pill is-frontend <?php echo 'iaq-preview-student-quiz' === $current_page ? 'is-active' : ''; ?>">
				[iaq_quiz]
			</a>
			<a href="/?page=iaq-preview-student-results" class="iaq-nav-pill is-frontend <?php echo 'iaq-preview-student-results' === $current_page ? 'is-active' : ''; ?>">
				[iaq_quiz_results]
			</a>
			<a href="/?page=iaq-preview-student-dashboard" class="iaq-nav-pill is-frontend <?php echo 'iaq-preview-student-dashboard' === $current_page ? 'is-active' : ''; ?>">
				[iaq_student_dashboard]
			</a>
			<a href="/?page=iaq-preview-subscription-plans" class="iaq-nav-pill is-frontend <?php echo 'iaq-preview-subscription-plans' === $current_page ? 'is-active' : ''; ?>">
				[iaq_subscription_plans]
			</a>
		</nav>
	</header>

	<!-- Main Workspace -->
	<main class="iaq-sandbox-container <?php echo 'iaq-preview-student-dashboard' === $current_page ? 'is-fullwidth' : ''; ?>">
		<?php
		// Render Notices if any.
		$admin_controller->render_notices();

		switch ( $current_page ) {
			case 'iaq-dashboard':
				$admin_controller->render_dashboard_page();
				break;
			case 'iaq-examinations':
				$admin_controller->render_examinations_page();
				break;
			case 'iaq-books':
				$admin_controller->render_books_page();
				break;
			case 'iaq-subscription-plans':
				$admin_controller->render_subscription_plans_page();
				break;
			case 'iaq-user-subscriptions':
				$admin_controller->render_user_subscriptions_page();
				break;
			case 'iaq-attempts':
				$admin_controller->render_attempts_page();
				break;
			case 'iaq-question-bank':
				$admin_controller->render_question_bank_page();
				break;
			case 'iaq-add-question':
				$admin_controller->render_add_question_page();
				break;
			case 'iaq-quizzes':
				$admin_controller->render_quizzes_page();
				break;
			case 'iaq-add-quiz':
				$admin_controller->render_add_quiz_page();
				break;
			case 'iaq-subjects-topics':
				$admin_controller->render_subjects_topics_page();
				break;
			case 'iaq-import-export':
				$admin_controller->render_import_export_page();
				break;
			case 'iaq-settings':
				$admin_controller->render_settings_page();
				break;
			case 'iaq-mock-tests':
				$admin_controller->render_mock_tests_page();
				break;
			case 'iaq-preview-mock-tests':
				?>
				<div style="background:#ffffff;padding:24px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.05);margin-bottom:20px;">
					<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:14px;margin-bottom:18px;">
						<div>
							<span class="iaq-pill iaq-pill-category">Elementor / Frontend Preview</span>
							<h2 style="margin:6px 0 0 0;font-size:20px;color:#0f172a;">Shortcode: <code>[iaq_mock_tests]</code></h2>
						</div>
					</div>
					<?php echo $frontend_controller->render_mock_tests_shortcode( array() ); ?>
				</div>
				<?php
				break;
			case 'iaq-preview-practice-sets':
				?>
				<div style="background:#ffffff;padding:24px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.05);margin-bottom:20px;">
					<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:14px;margin-bottom:18px;">
						<div>
							<span class="iaq-pill iaq-pill-category">Elementor / Frontend Preview</span>
							<h2 style="margin:6px 0 0 0;font-size:20px;color:#0f172a;">Shortcode: <code>[iaq_practice_sets]</code></h2>
						</div>
					</div>
					<?php echo $frontend_controller->render_practice_sets_shortcode( array() ); ?>
				</div>
				<?php
				break;
			case 'iaq-preview-student-list':
				?>
				<div style="background:#ffffff;padding:24px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.05);margin-bottom:20px;">
					<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:14px;margin-bottom:18px;">
						<div>
							<span class="iaq-pill iaq-pill-category">Elementor / Frontend Preview</span>
							<h2 style="margin:6px 0 0 0;font-size:20px;color:#0f172a;">Shortcode: <code>[iaq_quiz_list]</code></h2>
						</div>
					</div>
					<?php echo $frontend_controller->render_quiz_list_shortcode( array() ); ?>
				</div>
				<?php
				break;
			case 'iaq-preview-student-quiz':
				$all_quizzes      = IAQ_Quiz_Builder::get_quizzes( array( 'per_page' => 1 ) );
				$default_first_id = ! empty( $all_quizzes ) ? (int) $all_quizzes[0]->id : 1;
				$preview_id       = isset( $_GET['iaq_quiz_id'] ) ? absint( $_GET['iaq_quiz_id'] ) : $default_first_id;
				?>
				<div style="background:#ffffff;padding:24px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.05);margin-bottom:20px;">
					<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:14px;margin-bottom:18px;">
						<div>
							<span class="iaq-pill iaq-pill-category">Elementor / Frontend Preview</span>
							<h2 style="margin:6px 0 0 0;font-size:20px;color:#0f172a;">Shortcode: <code>[iaq_quiz id="<?php echo esc_attr( $preview_id ); ?>"]</code></h2>
						</div>
					</div>
					<?php echo $frontend_controller->render_quiz_shortcode( array( 'id' => $preview_id ) ); ?>
				</div>
				<?php
				break;
			case 'iaq-preview-student-results':
				$attempt_id = isset( $_GET['attempt_id'] ) ? absint( $_GET['attempt_id'] ) : 0;
				?>
				<div style="background:#ffffff;padding:24px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.05);margin-bottom:20px;">
					<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:14px;margin-bottom:18px;">
						<div>
							<span class="iaq-pill iaq-pill-category">Elementor / Frontend Preview</span>
							<h2 style="margin:6px 0 0 0;font-size:20px;color:#0f172a;">Shortcode: <code>[iaq_quiz_results<?php echo $attempt_id ? ' id="' . esc_attr( $attempt_id ) . '"' : ''; ?>]</code></h2>
						</div>
					</div>
					<?php echo $frontend_controller->render_results_shortcode( array( 'id' => $attempt_id ) ); ?>
				</div>
				<?php
				break;
			case 'iaq-preview-student-dashboard':
				$cur_view = isset( $_GET['student_view'] ) ? sanitize_key( $_GET['student_view'] ) : 'default';
				?>
				<div style="background:#0f172a;color:#cbd5e1;padding:8px 24px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #1e293b;flex-wrap:wrap;gap:8px;">
					<div style="display:flex;align-items:center;gap:8px;">
						<span class="iaq-pill iaq-pill-category" style="margin:0;font-size:11px;">Sandbox Environment</span>
						<span style="font-size:12px;color:#94a3b8;">Shortcode: <code>[iaq_student_dashboard]</code></span>
					</div>
					<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
						<span style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Test Persona:</span>
						<a href="/?page=iaq-preview-student-dashboard" style="font-size:11px;font-weight:700;text-decoration:none;padding:4px 8px;border-radius:5px;background:<?php echo 'default' === $cur_view ? '#117E3A;color:#fff;' : '#1e293b;color:#94a3b8;'; ?>">
							Student w/ Attempts
						</a>
						<a href="/?page=iaq-preview-student-dashboard&student_view=subscribed" style="font-size:11px;font-weight:700;text-decoration:none;padding:4px 8px;border-radius:5px;background:<?php echo 'subscribed' === $cur_view ? '#117E3A;color:#fff;' : '#1e293b;color:#94a3b8;'; ?>">
							Subscribed Student (Premium)
						</a>
						<a href="/?page=iaq-preview-student-dashboard&student_view=new_student" style="font-size:11px;font-weight:700;text-decoration:none;padding:4px 8px;border-radius:5px;background:<?php echo 'new_student' === $cur_view ? '#117E3A;color:#fff;' : '#1e293b;color:#94a3b8;'; ?>">
							New Student (0 Attempts)
						</a>
						<a href="/?page=iaq-preview-student-dashboard&student_view=guest" style="font-size:11px;font-weight:700;text-decoration:none;padding:4px 8px;border-radius:5px;background:<?php echo 'guest' === $cur_view ? '#117E3A;color:#fff;' : '#1e293b;color:#94a3b8;'; ?>">
							Logged-Out Guest
						</a>
					</div>
				</div>
				<?php echo $frontend_controller->render_student_dashboard( array() ); ?>
				<?php
				break;
			case 'iaq-preview-subscription-plans':
				?>
				<div style="background:#ffffff;padding:32px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.05);margin-bottom:20px;">
					<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:14px;margin-bottom:24px;">
						<div>
							<span class="iaq-pill iaq-pill-category">Elementor / Frontend Preview</span>
							<h2 style="margin:6px 0 0 0;font-size:20px;color:#0f172a;">Shortcode: <code>[iaq_subscription_plans]</code></h2>
						</div>
					</div>
					<?php echo $frontend_controller->render_subscription_plans_shortcode( array() ); ?>
				</div>
				<?php
				break;
			default:
				$admin_controller->render_dashboard_page();
				break;
		}
		?>
	</main>

</body>
</html>
