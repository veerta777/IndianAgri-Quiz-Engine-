<?php
/**
 * Test bootstrap and WordPress mock environment.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'IAQ_TESTING' ) ) {
	define( 'IAQ_TESTING', true );
}

if ( ! defined( 'IAQ_VERSION' ) ) {
	define( 'IAQ_VERSION', '1.0.0' );
}

if ( ! defined( 'IAQ_DB_VERSION' ) ) {
	define( 'IAQ_DB_VERSION', '1.0.0' );
}

if ( ! defined( 'IAQ_PLUGIN_DIR' ) ) {
	define( 'IAQ_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'IAQ_PLUGIN_URL' ) ) {
	define( 'IAQ_PLUGIN_URL', 'http://example.com/wp-content/plugins/indianagri-quiz-engine/' );
}

if ( ! defined( 'IAQ_PLUGIN_BASENAME' ) ) {
	define( 'IAQ_PLUGIN_BASENAME', 'indianagri-quiz-engine/indianagri-quiz-engine.php' );
}

// Global Options storage mock.
global $mock_wp_options, $mock_wp_roles, $current_user_id;
$mock_wp_options = array();
$mock_wp_roles   = array(
	'administrator' => new class {
		public $caps = array();
		public function add_cap( $cap ) {
			$this->caps[ $cap ] = true;
		}
	},
);
$current_user_id = 1;

// Mock WordPress functions.
function get_option( $option, $default = false ) {
	global $mock_wp_options;
	return isset( $mock_wp_options[ $option ] ) ? $mock_wp_options[ $option ] : $default;
}

function update_option( $option, $value ) {
	global $mock_wp_options;
	$mock_wp_options[ $option ] = $value;
	return true;
}

function delete_option( $option ) {
	global $mock_wp_options;
	unset( $mock_wp_options[ $option ] );
	return true;
}

function delete_transient( $transient ) {
	return true;
}

function get_role( $role ) {
	global $mock_wp_roles;
	return isset( $mock_wp_roles[ $role ] ) ? $mock_wp_roles[ $role ] : null;
}

function get_current_user_id() {
	global $current_user_id;
	return $current_user_id;
}

function wp_get_current_user() {
	global $current_user_id;
	$user = new stdClass();
	$user->ID = $current_user_id;
	$user->display_name = 'Virendra Pratap';
	$user->user_login = 'virendra';
	$user->user_email = 'student@indianagriexam.com';
	return $user;
}

function get_userdata( $user_id ) {
	$user = new stdClass();
	$user->ID = (int) $user_id;
	$user->display_name = 'Student #' . $user_id;
	$user->user_login = 'student' . $user_id;
	$user->user_email = 'student' . $user_id . '@indianagriexam.com';
	return $user;
}

global $mock_post_meta;
$mock_post_meta = array();

function get_post_meta( $post_id, $key = '', $single = false ) {
	global $mock_post_meta;
	if ( empty( $key ) ) {
		return isset( $mock_post_meta[ $post_id ] ) ? $mock_post_meta[ $post_id ] : array();
	}
	if ( isset( $mock_post_meta[ $post_id ][ $key ] ) ) {
		return $single ? $mock_post_meta[ $post_id ][ $key ] : array( $mock_post_meta[ $post_id ][ $key ] );
	}
	return $single ? '' : array();
}

function update_post_meta( $post_id, $key, $value ) {
	global $mock_post_meta;
	if ( ! isset( $mock_post_meta[ $post_id ] ) ) {
		$mock_post_meta[ $post_id ] = array();
	}
	$mock_post_meta[ $post_id ][ $key ] = $value;
	return true;
}

global $mock_user_meta;
$mock_user_meta = array();

function get_user_meta( $user_id, $key = '', $single = false ) {
	global $mock_user_meta;
	if ( empty( $key ) ) {
		return isset( $mock_user_meta[ $user_id ] ) ? $mock_user_meta[ $user_id ] : array();
	}
	if ( isset( $mock_user_meta[ $user_id ][ $key ] ) ) {
		return $single ? $mock_user_meta[ $user_id ][ $key ] : array( $mock_user_meta[ $user_id ][ $key ] );
	}
	return $single ? '' : array();
}

function update_user_meta( $user_id, $key, $value ) {
	global $mock_user_meta;
	if ( ! isset( $mock_user_meta[ $user_id ] ) ) {
		$mock_user_meta[ $user_id ] = array();
	}
	$mock_user_meta[ $user_id ][ $key ] = $value;
	return true;
}

function delete_user_meta( $user_id, $key ) {
	global $mock_user_meta;
	if ( isset( $mock_user_meta[ $user_id ][ $key ] ) ) {
		unset( $mock_user_meta[ $user_id ][ $key ] );
	}
	return true;
}

function current_user_can( $capability ) {
	return true;
}

function user_can( $user, $capability ) {
	if ( empty( $user ) ) {
		return false;
	}
	$user_id = is_object( $user ) ? $user->ID : (int) $user;
	return 1 === $user_id;
}

function current_time( $type, $gmt = 0 ) {
	if ( 'timestamp' === $type ) {
		return time();
	} elseif ( 'mysql' === $type ) {
		return gmdate( 'Y-m-d H:i:s' );
	}
	return gmdate( $type );
}
// WooCommerce Mocks
global $mock_wc_orders;
$mock_wc_orders = array();

class Mock_WC_Order {
	public $id;
	public $user_id;
	public $status = 'completed';
	public $items = array();
	public $meta = array();
	public $notes = array();

	public function __construct( $id, $user_id = 1 ) {
		$this->id      = $id;
		$this->user_id = $user_id;
	}

	public function get_id() { return $this->id; }
	public function get_user_id() { return $this->user_id; }
	public function get_status() { return $this->status; }
	public function get_items() { return $this->items; }
	public function get_meta( $key ) { return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : ''; }
	public function update_meta_data( $key, $val ) { $this->meta[ $key ] = $val; }
	public function add_order_note( $note ) { $this->notes[] = $note; }
	public function save() { return true; }
}

class Mock_WC_Order_Item {
	public $product_id;
	public function __construct( $product_id ) { $this->product_id = $product_id; }
	public function get_product_id() { return $this->product_id; }
}

if ( ! function_exists( 'wc_get_order' ) ) {
	function wc_get_order( $order_id ) {
		global $mock_wc_orders;
		return isset( $mock_wc_orders[ $order_id ] ) ? $mock_wc_orders[ $order_id ] : null;
	}
}

function sanitize_text_field( $str ) {
	return trim( strip_tags( (string) $str ) );
}

function sanitize_textarea_field( $str ) {
	return trim( strip_tags( (string) $str ) );
}

function sanitize_title( $title ) {
	$title = strtolower( trim( (string) $title ) );
	$title = preg_replace( '/[^a-z0-9_\-\s]/', '', $title );
	return preg_replace( '/[\s_]+/', '-', $title );
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function sanitize_hex_color( $color ) {
	if ( preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color ) ) {
		return $color;
	}
	return '';
}

function sanitize_file_name( $name ) {
	return preg_replace( '/[^a-zA-Z0-9_\.\-]/', '', (string) $name );
}

function wp_kses_post( $str ) {
	return (string) $str; // In mock, preserve HTML & Unicode
}

function wp_strip_all_tags( $str ) {
	return strip_tags( (string) $str );
}

function wp_unslash( $val ) {
	return is_string( $val ) ? stripslashes( $val ) : $val;
}

function esc_html( $str ) {
	return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $str ) {
	return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return (string) $url;
}

function esc_url_raw( $url ) {
	return (string) $url;
}

function wp_rand( $min = 0, $max = 0 ) {
	return mt_rand( (int) $min, (int) $max );
}

function wp_trim_words( $text, $num_words = 55, $more = null ) {
	if ( null === $more ) $more = '&hellip;';
	$words = preg_split( '/\s+/', trim( (string) $text ) );
	if ( count( $words ) <= $num_words ) return $text;
	return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
}

function remove_query_arg( $key, $url = '' ) {
	$parts = explode( '?', (string) $url );
	$base  = $parts[0];
	$query = array();
	if ( isset( $parts[1] ) ) {
		parse_str( $parts[1], $query );
	}
	if ( is_array( $key ) ) {
		foreach ( $key as $k ) unset( $query[ $k ] );
	} else {
		unset( $query[ $key ] );
	}
	return empty( $query ) ? $base : $base . '?' . http_build_query( $query );
}

function esc_textarea( $str ) {
	return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' );
}

function sanitize_html_class( $class, $fallback = '' ) {
	$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
	return '' === $sanitized ? $fallback : $sanitized;
}

function absint( $maybeint ) {
	return abs( intval( $maybeint ) );
}

function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) {
		$r = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$r =& $args;
	} else {
		parse_str( (string) $args, $r );
	}
	return array_merge( $defaults, $r );
}

function wp_json_encode( $data ) {
	return json_encode( $data, JSON_UNESCAPED_UNICODE );
}

function number_format_i18n( $number ) {
	return number_format( (float) $number );
}

function date_i18n( $format, $timestamp_with_offset = false, $gmt = false ) {
	if ( false === $timestamp_with_offset ) {
		$timestamp_with_offset = time();
	}
	return date( $format, $timestamp_with_offset );
}

function apply_filters( $tag, $value ) {
	return $value;
}

function __( $text, $domain = 'default' ) {
	return $text;
}

function _e( $text, $domain = 'default' ) {
	echo $text;
}

function wp_list_pluck( $list, $field ) {
	$plucked = array();
	foreach ( $list as $item ) {
		if ( is_object( $item ) && isset( $item->$field ) ) {
			$plucked[] = $item->$field;
		} elseif ( is_array( $item ) && isset( $item[ $field ] ) ) {
			$plucked[] = $item[ $field ];
		}
	}
	return $plucked;
}

function wpautop( $pee ) {
	return '<p>' . nl2br( (string) $pee ) . '</p>';
}

global $mock_shortcodes;
$mock_shortcodes = array();

function add_shortcode( $tag, $callback ) {
	global $mock_shortcodes;
	$mock_shortcodes[ $tag ] = $callback;
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$out = array();
	foreach ( $pairs as $name => $default ) {
		if ( array_key_exists( $name, (array) $atts ) ) {
			$out[ $name ] = $atts[ $name ];
		} else {
			$out[ $name ] = $default;
		}
	}
	return $out;
}

function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	return true;
}

function wp_enqueue_style( $handle ) {
	return true;
}

function wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
	return true;
}

function wp_enqueue_script( $handle ) {
	return true;
}

function wp_localize_script( $handle, $object_name, $l10n ) {
	return true;
}

function admin_url( $path = '', $scheme = 'admin' ) {
	return 'http://example.com/wp-admin/' . $path;
}

function get_permalink( $post = 0 ) {
	return 'http://example.com/quiz-portal/';
}

function home_url( $path = '' ) {
	return 'http://example.com/' . ltrim( $path, '/' );
}

function is_user_logged_in() {
	global $mock_is_logged_in;
	if ( isset( $mock_is_logged_in ) ) {
		return (bool) $mock_is_logged_in;
	}
	return true;
}

function wp_login_url( $redirect = '' ) {
	return 'http://example.com/wp-login.php?redirect_to=' . urlencode( $redirect );
}

function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ) ) {
		$params = $args[0];
		$url    = isset( $args[1] ) ? $args[1] : '';
	} else {
		$params = array( $args[0] => $args[1] );
		$url    = isset( $args[2] ) ? $args[2] : '';
	}
	$query = http_build_query( $params );
	if ( strpos( $url, '?' ) !== false ) {
		return $url . '&' . $query;
	}
	return $url . '?' . $query;
}

function esc_html__( $text, $domain = 'default' ) {
	return esc_html( $text );
}

function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( $text );
}

function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( $text );
}

function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( $text );
}

function is_admin() {
	return true;
}

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	return true;
}

function do_action( $tag, ...$args ) {
	return true;
}

function dbDelta( $sql ) {
	global $mock_dbdelta_calls;
	$mock_dbdelta_calls[] = $sql;
	return array();
}

/**
 * WP_Error Mock.
 */
class WP_Error {
	private $errors = array();

	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( ! empty( $code ) ) {
			$this->errors[ $code ][] = $message;
		}
	}

	public function get_error_code() {
		$codes = array_keys( $this->errors );
		return empty( $codes ) ? '' : $codes[0];
	}

	public function get_error_message( $code = '' ) {
		if ( empty( $code ) ) {
			$code = key( $this->errors );
		}
		return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
	}
}

function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}

/**
 * Mock WordPress Database ($wpdb).
 */
class Mock_WPDB {
	public $prefix = 'wp_';
	public $insert_id = 0;
	public $queries = array();
	public $tables = array();

	public function __construct() {
		$this->reset();
	}

	public function reset() {
		$this->insert_id = 0;
		$this->queries   = array();
		$this->tables    = array(
			$this->prefix . 'iaq_subjects'           => array(),
			$this->prefix . 'iaq_topics'             => array(),
			$this->prefix . 'iaq_examinations'       => array(),
			$this->prefix . 'iaq_questions'          => array(),
			$this->prefix . 'iaq_quizzes'            => array(),
			$this->prefix . 'iaq_quiz_questions'     => array(),
			$this->prefix . 'iaq_quiz_attempts'      => array(),
			$this->prefix . 'iaq_attempt_answers'    => array(),
			$this->prefix . 'iaq_subscription_plans' => array(),
			$this->prefix . 'iaq_user_subscriptions' => array(),
			$this->prefix . 'iaq_bookmarks'            => array(),
			$this->prefix . 'iaq_wrong_notebook'       => array(),
			$this->prefix . 'iaq_study_activity'       => array(),
			$this->prefix . 'iaq_books'                => array(),
			$this->prefix . 'iaq_book_quizzes'         => array(),
			$this->prefix . 'iaq_exam_subjects'        => array(),
			$this->prefix . 'iaq_exam_subject_quizzes' => array(),
			$this->prefix . 'iaq_mock_categories'          => array(),
			$this->prefix . 'iaq_mock_series'              => array(),
			$this->prefix . 'iaq_mock_vacancies'           => array(),
			$this->prefix . 'iaq_mock_states_universities' => array(),
			$this->prefix . 'iaq_mock_test_relations'      => array(),
		);
	}

	public function get_charset_collate() {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
	}

	public function prepare( $query, ...$args ) {
		if ( isset( $args[0] ) && is_array( $args[0] ) && count( $args ) === 1 ) {
			$args = $args[0];
		}

		$this->queries[] = array( 'query' => $query, 'args' => $args );

		// Positional substitution for mock execution.
		foreach ( $args as $arg ) {
			$positions = array();
			$pos_d = strpos( $query, '%d' );
			if ( false !== $pos_d ) {
				$positions[ $pos_d ] = '%d';
			}
			$pos_s = strpos( $query, '%s' );
			if ( false !== $pos_s ) {
				$positions[ $pos_s ] = '%s';
			}
			$pos_f = strpos( $query, '%f' );
			if ( false !== $pos_f ) {
				$positions[ $pos_f ] = '%f';
			}

			if ( ! empty( $positions ) ) {
				ksort( $positions );
				reset( $positions );
				$first_pos = key( $positions );
				$type      = current( $positions );

				if ( '%d' === $type ) {
					$val = (int) $arg;
				} elseif ( '%f' === $type ) {
					$val = (float) $arg;
				} else {
					$val = "'" . addslashes( (string) $arg ) . "'";
				}

				$query = substr_replace( $query, (string) $val, $first_pos, 2 );
			}
		}

		return $query;
	}

	public function insert( $table, $data, $format = null ) {
		$this->insert_id++;
		$record = array_merge( array( 'id' => $this->insert_id ), $data );
		$this->tables[ $table ][ $this->insert_id ] = (object) $record;
		return 1;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$count = 0;
		if ( isset( $where['id'] ) ) {
			$id = $where['id'];
			if ( isset( $this->tables[ $table ][ $id ] ) ) {
				foreach ( $data as $k => $v ) {
					$this->tables[ $table ][ $id ]->$k = $v;
				}
				$count++;
			}
		} else {
			foreach ( $this->tables[ $table ] as $id => $row ) {
				$match = true;
				foreach ( $where as $wk => $wv ) {
					if ( ! isset( $row->$wk ) || $row->$wk != $wv ) {
						$match = false;
						break;
					}
				}
				if ( $match ) {
					foreach ( $data as $k => $v ) {
						$this->tables[ $table ][ $id ]->$k = $v;
					}
					$count++;
				}
			}
		}
		return $count;
	}

	public function delete( $table, $where, $where_format = null ) {
		$deleted = 0;
		foreach ( $this->tables[ $table ] as $id => $row ) {
			$match = true;
			foreach ( $where as $wk => $wv ) {
				if ( ! isset( $row->$wk ) || $row->$wk != $wv ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				unset( $this->tables[ $table ][ $id ] );
				$deleted++;
			}
		}
		return $deleted;
	}

	public function get_results( $query ) {
		$this->queries[] = $query;
		$results = array();

		// Handle questions query in mock
		if ( strpos( $query, $this->prefix . 'iaq_questions' ) !== false && strpos( $query, 'SELECT q.*' ) !== false ) {
			foreach ( $this->tables[ $this->prefix . 'iaq_questions' ] as $q ) {
				$subj = isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ] ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ]->name : '';
				$top  = ( $q->topic_id && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ]->name : '';
				
				$clone = clone $q;
				$clone->subject_name = $subj;
				$clone->topic_name   = $top;
				$results[] = $clone;
			}
			return array_reverse( $results );
		}

		// Handle subjects query
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_subjects' ) !== false ) {
			foreach ( $this->tables[ $this->prefix . 'iaq_subjects' ] as $s ) {
				$clone = clone $s;
				$q_count = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_questions' ] as $q ) {
					if ( $q->subject_id == $s->id ) $q_count++;
				}
				$t_count = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_topics' ] as $t ) {
					if ( $t->subject_id == $s->id ) $t_count++;
				}
				$ps_count = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
					if ( ! empty( $qz->subject_id ) && (int) $qz->subject_id === (int) $s->id && isset( $qz->quiz_type ) && 'practice' === $qz->quiz_type && isset( $qz->status ) && 'published' === $qz->status ) {
						$ps_count++;
					}
				}
				$clone->question_count      = $q_count;
				$clone->topic_count         = $t_count;
				$clone->topics_count        = $t_count;
				$clone->practice_sets_count = $ps_count;
				$results[] = $clone;
			}
			return $results;
		}

		// Handle topics query
		if ( strpos( $query, $this->prefix . 'iaq_topics' ) !== false && strpos( $query, 'SELECT t.*' ) !== false ) {
			foreach ( $this->tables[ $this->prefix . 'iaq_topics' ] as $t ) {
				$clone = clone $t;
				$q_count = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_questions' ] as $q ) {
					if ( isset( $q->topic_id ) && $q->topic_id == $t->id ) $q_count++;
				}
				$clone->question_count = $q_count;
				$results[] = $clone;
			}
			return $results;
		}

		// Handle quiz counts query
		if ( strpos( $query, $this->prefix . 'iaq_quizzes' ) !== false && strpos( $query, 'COUNT(id) as count_val' ) !== false ) {
			$counts = array( 'practice' => 0, 'mock' => 0, 'previous_year' => 0 );
			foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
				if ( 'published' === $qz->status && isset( $counts[ $qz->quiz_type ] ) ) {
					$counts[ $qz->quiz_type ]++;
				}
			}
			foreach ( $counts as $t => $c ) {
				$results[] = (object) array( 'quiz_type' => $t, 'count_val' => $c );
			}
			return $results;
		}

		// Handle continue practicing attempts
		if ( ( strpos( $query, 'attempt_status' ) !== false || strpos( $query, 'scored_out_of' ) !== false ) && strpos( $query, 'JOIN ' . $this->prefix . 'iaq_quizzes q' ) !== false ) {
			if ( preg_match( "/WHERE a\.user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$req_status = null;
				if ( strpos( $query, "a.status = 'in_progress'" ) !== false ) $req_status = 'in_progress';
				elseif ( strpos( $query, "a.status = 'completed'" ) !== false ) $req_status = 'completed';

				foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] ) as $att ) {
					if ( $att->user_id == $uid && ( null === $req_status || $att->status === $req_status ) ) {
						if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ] ) ) {
							$qz = $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ];
							if ( 'published' === $qz->status ) {
								$row = (object) array(
									'attempt_id'       => $att->id,
									'quiz_id'          => $att->quiz_id,
									'start_time'       => $att->start_time,
									'attempt_status'   => $att->status,
									'score'            => $att->score,
									'scored_out_of'    => $att->total_marks,
									'percentage'       => $att->percentage,
									'passed'           => $att->passed,
									'title'            => $qz->title,
									'quiz_type'        => $qz->quiz_type,
									'exam_category'    => $qz->exam_category,
									'total_questions'  => $qz->total_questions,
									'total_marks'      => $qz->total_marks,
									'duration_minutes' => $qz->duration_minutes,
									'access_type'      => $qz->access_type,
								);
								$results[] = $row;
							}
						}
					}
				}
				return $results;
			}
		}

		// Handle published quizzes recommendations query
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_quizzes q' ) !== false && ( strpos( $query, 'q.id as quiz_id' ) !== false || strpos( $query, 'q.id AS quiz_id' ) !== false ) ) {
			foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_quizzes' ] ) as $qz ) {
				if ( 'published' === $qz->status ) {
					$results[] = (object) array(
						'quiz_id'          => $qz->id,
						'title'            => $qz->title,
						'quiz_type'        => $qz->quiz_type,
						'exam_category'    => $qz->exam_category,
						'total_questions'  => $qz->total_questions,
						'total_marks'      => $qz->total_marks,
						'duration_minutes' => $qz->duration_minutes,
						'access_type'      => $qz->access_type,
					);
				}
			}
			return $results;
		}

		// Handle quizzes query
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_quizzes' ) !== false && strpos( $query, 'SELECT q.*' ) !== false && strpos( $query, 'mock_test_relations' ) === false && strpos( $query, 'INNER JOIN' ) === false ) {
			foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
				$subj = ( ! empty( $qz->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $qz->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $qz->subject_id ]->name : '';
				$top  = ( ! empty( $qz->topic_id ) && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $qz->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $qz->topic_id ]->name : '';

				$clone = clone $qz;
				$clone->subject_name = $subj;
				$clone->topic_name   = $top;

				$q_count = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_quiz_questions' ] as $qq ) {
					if ( $qq->quiz_id == $qz->id ) $q_count++;
				}
				$clone->assigned_questions_count = $q_count;
				$results[] = $clone;
			}
			return array_reverse( $results );
		}

		// Handle quiz_questions query
		if ( strpos( $query, $this->prefix . 'iaq_quiz_questions' ) !== false && strpos( $query, 'qq.quiz_id' ) !== false ) {
			if ( preg_match( "/qq\.quiz_id = (\d+)/", $query, $m ) ) {
				$quiz_id = (int) $m[1];
				foreach ( $this->tables[ $this->prefix . 'iaq_quiz_questions' ] as $qq ) {
					if ( $qq->quiz_id == $quiz_id && isset( $this->tables[ $this->prefix . 'iaq_questions' ][ $qq->question_id ] ) ) {
						$q = clone $this->tables[ $this->prefix . 'iaq_questions' ][ $qq->question_id ];
						$q->link_id                = $qq->id;
						$q->question_order         = $qq->question_order;
						$q->custom_marks           = isset( $qq->custom_marks ) ? $qq->custom_marks : null;
						$q->custom_negative_marks  = isset( $qq->custom_negative_marks ) ? $qq->custom_negative_marks : null;
						$q->subject_name           = isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ] ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ]->name : '';
						$q->topic_name             = ( $q->topic_id && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ]->name : '';
						$results[] = $q;
					}
				}
			}
			return $results;
		}

		// Handle attempt_answers query
		if ( strpos( $query, $this->prefix . 'iaq_attempt_answers' ) !== false ) {
			if ( preg_match( "/WHERE (?:ans\.)?attempt_id = (\d+)/", $query, $m ) ) {
				$att_id = (int) $m[1];
				$only_wrong       = ( strpos( $query, 'is_correct = 0' ) !== false );
				$require_selected = ( strpos( $query, 'selected_option IS NOT NULL' ) !== false );
				foreach ( $this->tables[ $this->prefix . 'iaq_attempt_answers' ] as $ans ) {
					if ( $ans->attempt_id == $att_id ) {
						if ( $only_wrong && ( ! isset( $ans->is_correct ) || 0 !== (int) $ans->is_correct ) ) {
							continue;
						}
						if ( $require_selected && empty( $ans->selected_option ) ) {
							continue;
						}
						$clone = clone $ans;
						if ( isset( $this->tables[ $this->prefix . 'iaq_questions' ][ $ans->question_id ] ) ) {
							$q = $this->tables[ $this->prefix . 'iaq_questions' ][ $ans->question_id ];
							$clone->question_text  = $q->question_text;
							$clone->option_a       = $q->option_a;
							$clone->option_b       = $q->option_b;
							$clone->option_c       = $q->option_c;
							$clone->option_d       = $q->option_d;
							$clone->correct_option = $q->correct_option;
							$clone->explanation    = $q->explanation;
							$clone->subject_id     = isset( $q->subject_id ) ? (int) $q->subject_id : 0;
							$clone->topic_id       = isset( $q->topic_id ) ? (int) $q->topic_id : 0;
							$clone->subject_name   = ( isset( $q->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ]->name : 'General Agriculture';
							$clone->topic_name     = ( isset( $q->topic_id ) && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ]->name : '';
						}
						$results[] = $clone;
					}
				}
				return $results;
			}
		}

		// User overall subject mastery query
		if ( strpos( $query, "a.status = 'completed'" ) !== false && strpos( $query, 'iaq_attempt_answers' ) !== false && preg_match( "/WHERE a\.user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			$results = array();
			$completed_att_ids = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] as $att ) {
				if ( $att->user_id == $uid && 'completed' === $att->status ) {
					$completed_att_ids[] = $att->id;
				}
			}
			foreach ( $this->tables[ $this->prefix . 'iaq_attempt_answers' ] as $ans ) {
				if ( in_array( $ans->attempt_id, $completed_att_ids ) ) {
					$row = new stdClass();
					$row->selected_option = $ans->selected_option;
					$row->is_correct = $ans->is_correct;
					$row->marks_awarded = $ans->marks_awarded;
					if ( isset( $this->tables[ $this->prefix . 'iaq_questions' ][ $ans->question_id ] ) ) {
						$q = $this->tables[ $this->prefix . 'iaq_questions' ][ $ans->question_id ];
						$row->subject_id = $q->subject_id;
						$row->subject_name = isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ] ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ]->name : 'General Agriculture';
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
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_quiz_attempts a' ) !== false && preg_match( "/WHERE a\.user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			$results = array();
			foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] ) as $att ) {
				if ( $att->user_id == $uid ) {
					$clone = clone $att;
					if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ] ) ) {
						$qz = $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ];
						$clone->quiz_title = $qz->title;
						$clone->quiz_type = $qz->quiz_type;
						$clone->exam_category = $qz->exam_category;
					}
					$results[] = $clone;
				}
			}
			return $results;
		}

		// Handle assigned questions with custom marks
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_quiz_questions qq' ) !== false && preg_match( "/WHERE qq\.quiz_id = (\d+)/", $query, $m ) ) {
			$quiz_id = (int) $m[1];
			foreach ( $this->tables[ $this->prefix . 'iaq_quiz_questions' ] as $qq ) {
				if ( $qq->quiz_id == $quiz_id && isset( $this->tables[ $this->prefix . 'iaq_questions' ][ $qq->question_id ] ) ) {
					$q = clone $this->tables[ $this->prefix . 'iaq_questions' ][ $qq->question_id ];
					$q->custom_marks          = isset( $qq->custom_marks ) ? $qq->custom_marks : null;
					$q->custom_negative_marks = isset( $qq->custom_negative_marks ) ? $qq->custom_negative_marks : null;
					$results[] = $q;
				}
			}
			return $results;
		}
		// Handle questions pool query for custom practice
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_questions' ) !== false && strpos( $query, 'marks' ) !== false && strpos( $query, 'status' ) !== false ) {
			$subj_match  = preg_match( "/subject_id = (\d+)/", $query, $sm ) ? (int) $sm[1] : 0;
			$topic_match = preg_match( "/topic_id = (\d+)/", $query, $tm ) ? (int) $tm[1] : 0;
			$diff_match  = preg_match( "/difficulty = '([^']+)'/", $query, $dm ) ? $dm[1] : null;
			$cat_match   = preg_match( "/exam_category = '([^']+)'/", $query, $cm ) ? $cm[1] : null;
			$limit       = preg_match( "/LIMIT (\d+)/", $query, $lm ) ? (int) $lm[1] : 100;
			foreach ( $this->tables[ $this->prefix . 'iaq_questions' ] as $q ) {
				if ( isset( $q->status ) && 'published' !== $q->status ) continue;
				if ( $subj_match && (int) $q->subject_id !== $subj_match ) continue;
				if ( $topic_match && (int) $q->topic_id !== $topic_match ) continue;
				if ( $diff_match && $q->difficulty !== $diff_match ) continue;
				if ( $cat_match && $q->exam_category !== $cat_match ) continue;
				$results[] = (object) array(
					'id'             => $q->id,
					'marks'          => isset( $q->marks ) ? $q->marks : 1.0,
					'negative_marks' => isset( $q->negative_marks ) ? $q->negative_marks : 0.25,
				);
				if ( count( $results ) >= $limit ) {
					break;
				}
			}
			return $results;
		}

		// Handle examinations query
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_examinations' ) !== false ) {
			$status_match = null;
			if ( preg_match( "/(?:WHERE\s+status|e\.status)\s*=\s*'([^']+)'/", $query, $m ) ) {
				$status_match = $m[1];
			}
			$cat_filter = preg_match( "/(?:category_id|e\.category_id)\s*=\s*(\d+)/", $query, $cm ) ? (int) $cm[1] : 0;
			$parent_filter = preg_match( "/(?:parent_id|e\.parent_id)\s*=\s*(\d+)/", $query, $pm ) ? (int) $pm[1] : null;

			foreach ( $this->tables[ $this->prefix . 'iaq_examinations' ] as $ex ) {
				if ( $status_match && isset( $ex->status ) && $ex->status !== $status_match ) {
					continue;
				}
				if ( $cat_filter > 0 && isset( $ex->category_id ) && (int) $ex->category_id !== $cat_filter ) {
					continue;
				}
				if ( null !== $parent_filter && isset( $ex->parent_id ) && (int) $ex->parent_id !== $parent_filter ) {
					continue;
				}
				$clone = clone $ex;
				$sub_cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_exam_subjects' ] as $es ) {
					if ( (int) $es->exam_id === (int) $ex->id ) $sub_cnt++;
				}
				$clone->assigned_subjects_count = $sub_cnt;

				$ps_cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
					if ( 'published' === $qz->status && 'practice' === $qz->quiz_type ) {
						$matched_exam = false;
						if ( ! empty( $qz->exam_category ) && ( $qz->exam_category === $ex->code || $qz->exam_category === $ex->name ) ) {
							$matched_exam = true;
						} else {
							foreach ( $this->tables[ $this->prefix . 'iaq_exam_subject_quizzes' ] as $esq ) {
								if ( (int) $esq->exam_id === (int) $ex->id && (int) $esq->quiz_id === (int) $qz->id ) {
									$matched_exam = true;
									break;
								}
							}
						}
						if ( $matched_exam ) $ps_cnt++;
					}
				}
				$clone->practice_sets_count = $ps_cnt;
				$results[] = $clone;
			}
			return $results;
		}

		// Handle subscription plans query
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_subscription_plans' ) !== false ) {
			$status_match = null;
			if ( preg_match( "/WHERE status = '([^']+)'/", $query, $m ) ) {
				$status_match = $m[1];
			}
			foreach ( $this->tables[ $this->prefix . 'iaq_subscription_plans' ] as $pl ) {
				if ( $status_match && isset( $pl->status ) && $pl->status !== $status_match ) {
					continue;
				}
				$results[] = clone $pl;
			}
			return $results;
		}

		// Handle user subscriptions query
		if ( strpos( $query, 'FROM ' . $this->prefix . 'iaq_user_subscriptions' ) !== false ) {
			$uid_match = null;
			if ( preg_match( "/user_id = (\d+)/", $query, $m ) ) {
				$uid_match = (int) $m[1];
			}
			$status_match = null;
			if ( preg_match( "/status = '([^']+)'/", $query, $m ) ) {
				$status_match = $m[1];
			}
			foreach ( $this->tables[ $this->prefix . 'iaq_user_subscriptions' ] as $us ) {
				if ( $uid_match && (int) $us->user_id !== $uid_match ) {
					continue;
				}
				if ( $status_match && $us->status !== $status_match ) {
					continue;
				}
				$results[] = clone $us;
			}
			return array_reverse( $results );
		}

		// Bookmarks query
		if ( strpos( $query, $this->prefix . 'iaq_bookmarks' ) !== false && strpos( $query, 'JOIN ' . $this->prefix . 'iaq_questions q' ) !== false ) {
			if ( preg_match( "/b\.user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_bookmarks' ] ) as $bm ) {
					if ( (int) $bm->user_id === $uid && isset( $this->tables[ $this->prefix . 'iaq_questions' ][ $bm->question_id ] ) ) {
						$q = clone $this->tables[ $this->prefix . 'iaq_questions' ][ $bm->question_id ];
						$results[] = (object) array(
							'bookmark_id'    => $bm->id,
							'user_id'        => $bm->user_id,
							'question_id'    => $bm->question_id,
							'notes'          => isset( $bm->notes ) ? $bm->notes : '',
							'bookmarked_at'  => $bm->created_at,
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
							'subject_name'   => isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ] ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ]->name : 'General Agriculture',
							'topic_name'     => isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ] ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ]->name : '',
						);
					}
				}
				return $results;
			}
		}

		// Wrong Notebook query
		if ( strpos( $query, $this->prefix . 'iaq_wrong_notebook' ) !== false && strpos( $query, 'JOIN ' . $this->prefix . 'iaq_questions q' ) !== false ) {
			if ( preg_match( "/n\.user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_wrong_notebook' ] ) as $wn ) {
					if ( (int) $wn->user_id === $uid && isset( $this->tables[ $this->prefix . 'iaq_questions' ][ $wn->question_id ] ) ) {
						$q = clone $this->tables[ $this->prefix . 'iaq_questions' ][ $wn->question_id ];
						$last_selected = null;
						if ( ! empty( $wn->last_attempt_id ) ) {
							foreach ( $this->tables[ $this->prefix . 'iaq_attempt_answers' ] as $ans ) {
								if ( (int) $ans->attempt_id === (int) $wn->last_attempt_id && (int) $ans->question_id === (int) $wn->question_id ) {
									$last_selected = $ans->selected_option;
									break;
								}
							}
						}
						if ( null === $last_selected ) {
							$last_selected = ( 'B' === $q->correct_option ) ? 'C' : 'A';
						}
						$results[] = (object) array(
							'notebook_id'         => $wn->id,
							'user_id'             => $wn->user_id,
							'question_id'         => $wn->question_id,
							'last_attempt_id'     => $wn->last_attempt_id,
							'times_incorrect'     => $wn->times_incorrect,
							'status'              => $wn->status,
							'notebook_status'     => $wn->status,
							'student_notes'       => isset( $wn->notes ) ? $wn->notes : '',
							'last_failed_at'      => $wn->updated_at,
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
							'subject_name'        => isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ] ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ]->name : 'General Agriculture',
							'topic_name'          => isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ] ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ]->name : '',
							'last_selected_option'=> $last_selected,
						);
					}
				}
				return $results;
			}
		}

		// Study activity query
		if ( strpos( $query, $this->prefix . 'iaq_study_activity' ) !== false && strpos( $query, 'GROUP BY activity_date' ) !== false ) {
			if ( preg_match( "/user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$by_date = array();
				foreach ( $this->tables[ $this->prefix . 'iaq_study_activity' ] as $sa ) {
					if ( (int) $sa->user_id === $uid ) {
						$d = $sa->activity_date;
						if ( ! isset( $by_date[ $d ] ) ) {
							$by_date[ $d ] = (object) array( 'activity_date' => $d, 'total_qs' => 0, 'total_events' => 0 );
						}
						$by_date[ $d ]->total_qs += (int) $sa->questions_solved;
						$by_date[ $d ]->total_events++;
					}
				}
				return array_values( $by_date );
			}
		}

		// Leaderboard query
		if ( strpos( $query, 'MAX(a.score) as max_score' ) !== false && strpos( $query, 'GROUP BY a.user_id' ) !== false ) {
			$by_user = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] as $att ) {
				if ( 'completed' === $att->status ) {
					$uid   = (int) $att->user_id;
					$score = (float) ( isset( $att->score ) ? $att->score : 0 );
					$pct   = (float) ( isset( $att->percentage ) ? $att->percentage : 0 );
					$time  = (int) ( isset( $att->time_spent_seconds ) ? $att->time_spent_seconds : 300 );
					if ( ! isset( $by_user[ $uid ] ) || $score > $by_user[ $uid ]->max_score ) {
						$qz = isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ] ) ? $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ] : null;
						$by_user[ $uid ] = (object) array(
							'user_id'            => $uid,
							'max_score'          => $score,
							'max_percentage'     => $pct,
							'min_time'           => $time,
							'latest_attempt_time'=> $att->start_time,
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
				if ( $b->max_score != $a->max_score ) {
					return $b->max_score <=> $a->max_score;
				}
				if ( $b->max_percentage != $a->max_percentage ) {
					return $b->max_percentage <=> $a->max_percentage;
				}
				return $a->min_time <=> $b->min_time;
			} );
			return array_values( $by_user );
		}

		// Books query
		if ( strpos( $query, $this->prefix . 'iaq_books' ) !== false && strpos( $query, 'SELECT b.*' ) !== false ) {
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
			foreach ( $this->tables[ $this->prefix . 'iaq_books' ] as $bk ) {
				if ( $status_match && isset( $bk->status ) && $bk->status !== $status_match ) {
					continue;
				}
				if ( $not_archived && isset( $bk->status ) && 'archived' === $bk->status ) {
					continue;
				}
				if ( $search_term ) {
					$t_match = false;
					if ( ! empty( $bk->title ) && false !== stripos( $bk->title, $search_term ) ) $t_match = true;
					if ( ! empty( $bk->author ) && false !== stripos( $bk->author, $search_term ) ) $t_match = true;
					if ( ! empty( $bk->description ) && false !== stripos( $bk->description, $search_term ) ) $t_match = true;
					if ( ! $t_match ) continue;
				}
				$clone = clone $bk;
				$clone->subject_name = ( ! empty( $clone->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ]->name : '';
				$clone->examination_name = ( ! empty( $clone->examination_id ) && isset( $this->tables[ $this->prefix . 'iaq_examinations' ][ $clone->examination_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_examinations' ][ $clone->examination_id ]->name : '';
				$cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_book_quizzes' ] as $bq ) {
					if ( (int) $bq->book_id === (int) $clone->id ) {
						if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $bq->quiz_id ] ) && 'published' === $this->tables[ $this->prefix . 'iaq_quizzes' ][ $bq->quiz_id ]->status ) {
							$cnt++;
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
		if ( strpos( $query, $this->prefix . 'iaq_book_quizzes' ) !== false && strpos( $query, 'bq.book_id' ) !== false ) {
			if ( preg_match( "/bq\.book_id = (\d+)/", $query, $bm ) ) {
				$book_id = (int) $bm[1];
				$status_match = null;
				if ( preg_match( "/q\.status = '([^']+)'/", $query, $sm ) ) {
					$status_match = $sm[1];
				}
				$not_archived = ( strpos( $query, "q.status != 'archived'" ) !== false );

				$list = array();
				foreach ( $this->tables[ $this->prefix . 'iaq_book_quizzes' ] as $bq ) {
					if ( (int) $bq->book_id === $book_id ) {
						$qid = (int) $bq->quiz_id;
						if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $qid ] ) ) {
							$qz = clone $this->tables[ $this->prefix . 'iaq_quizzes' ][ $qid ];
							if ( $status_match && isset( $qz->status ) && $qz->status !== $status_match ) continue;
							if ( $not_archived && isset( $qz->status ) && 'archived' === $qz->status ) continue;
							$qz->book_sort_order    = (int) $bq->sort_order;
							$qz->book_quiz_rel_id   = (int) $bq->id;
							$qz->subject_name       = ( ! empty( $qz->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $qz->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $qz->subject_id ]->name : '';
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
			foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
				if ( (int) $qz->subject_id === $sid && 'practice' === $qz->quiz_type ) {
					if ( $status_match && isset( $qz->status ) && $qz->status !== $status_match ) continue;
					if ( $not_archived && isset( $qz->status ) && 'archived' === $qz->status ) continue;
					$clone = clone $qz;
					$clone->subject_name = ( ! empty( $clone->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ]->name : '';
					$clone->topic_name   = ( ! empty( $clone->topic_id ) && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $clone->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $clone->topic_id ]->name : '';
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
		if ( strpos( $query, $this->prefix . 'iaq_exam_subjects' ) !== false && strpos( $query, 'es.exam_id =' ) !== false ) {
			if ( preg_match( "/es\.exam_id = (\d+)/", $query, $em ) ) {
				$exam_id = (int) $em[1];
				$list = array();
				foreach ( $this->tables[ $this->prefix . 'iaq_exam_subjects' ] as $es ) {
					if ( (int) $es->exam_id === $exam_id ) {
						$sid = (int) $es->subject_id;
						if ( isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $sid ] ) ) {
							$subj = clone $this->tables[ $this->prefix . 'iaq_subjects' ][ $sid ];
							$subj->exam_subject_order  = (int) $es->display_order;
							$subj->exam_subject_rel_id = (int) $es->id;

							// Practice sets count for this exam & subject
							$ps_count = 0;
							foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
								if ( ! empty( $qz->subject_id ) && (int) $qz->subject_id === $sid && isset( $qz->quiz_type ) && 'practice' === $qz->quiz_type && isset( $qz->status ) && 'published' === $qz->status ) {
									$matched = false;
									foreach ( $this->tables[ $this->prefix . 'iaq_exam_subject_quizzes' ] as $esq ) {
										if ( (int) $esq->exam_id === $exam_id && (int) $esq->subject_id === $sid && (int) $esq->quiz_id === (int) $qz->id ) {
											$matched = true;
											break;
										}
									}
									if ( ! $matched && isset( $this->tables[ $this->prefix . 'iaq_examinations' ][ $exam_id ] ) ) {
										$ex_obj = $this->tables[ $this->prefix . 'iaq_examinations' ][ $exam_id ];
										if ( ! empty( $qz->exam_category ) && ( $qz->exam_category === $ex_obj->code || $qz->exam_category === $ex_obj->name ) ) {
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
		if ( ( strpos( $query, $this->prefix . 'iaq_exam_subject_quizzes' ) !== false || strpos( $query, 'exam_sort_order' ) !== false ) && preg_match( "/(?:esq\.exam_id = |exam_id = )(\d+)/", $query, $em ) && preg_match( "/(?:esq\.subject_id = |subject_id = )(\d+)/", $query, $sm ) ) {
			$exam_id = (int) $em[1];
			$subject_id = (int) $sm[1];
			$status_match = null;
			if ( preg_match( "/q\.status = '([^']+)'/", $query, $st_m ) ) {
				$status_match = $st_m[1];
			}
			$not_archived = ( strpos( $query, "q.status != 'archived'" ) !== false );

			$list = array();
			// Explicit mappings
			foreach ( $this->tables[ $this->prefix . 'iaq_exam_subject_quizzes' ] as $esq ) {
				if ( (int) $esq->exam_id === $exam_id && (int) $esq->subject_id === $subject_id ) {
					$qid = (int) $esq->quiz_id;
					if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $qid ] ) ) {
						$qz = clone $this->tables[ $this->prefix . 'iaq_quizzes' ][ $qid ];
						if ( $status_match && isset( $qz->status ) && $qz->status !== $status_match ) continue;
						if ( $not_archived && isset( $qz->status ) && 'archived' === $qz->status ) continue;
						$qz->exam_sort_order  = (int) $esq->sort_order;
						$qz->exam_quiz_rel_id = (int) $esq->id;
						$qz->subject_name     = ( ! empty( $qz->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $qz->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $qz->subject_id ]->name : '';
						$list[] = $qz;
					}
				}
			}
			// Fallback by exam_category & subject_id if list empty
			if ( empty( $list ) && isset( $this->tables[ $this->prefix . 'iaq_examinations' ][ $exam_id ] ) ) {
				$ex_obj = $this->tables[ $this->prefix . 'iaq_examinations' ][ $exam_id ];
				foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
					if ( ! empty( $qz->subject_id ) && (int) $qz->subject_id === $subject_id && isset( $qz->quiz_type ) && 'practice' === $qz->quiz_type ) {
						if ( ! empty( $qz->exam_category ) && ( $qz->exam_category === $ex_obj->code || $qz->exam_category === $ex_obj->name ) ) {
							if ( $status_match && isset( $qz->status ) && $qz->status !== $status_match ) continue;
							if ( $not_archived && isset( $qz->status ) && 'archived' === $qz->status ) continue;
							$clone = clone $qz;
							$clone->exam_sort_order  = isset( $clone->display_order ) ? (int) $clone->display_order : 0;
							$clone->exam_quiz_rel_id = 0;
							$clone->subject_name     = ( ! empty( $clone->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ]->name : '';
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

		// Mock categories query
		if ( strpos( $query, $this->prefix . 'iaq_mock_categories' ) !== false ) {
			$status_match = null;
			if ( preg_match( "/status = '([^']+)'/", $query, $sm ) ) {
				$status_match = $sm[1];
			}
			$list = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_mock_categories' ] as $cat ) {
				if ( $status_match && isset( $cat->status ) && $cat->status !== $status_match ) continue;
				$list[] = clone $cat;
			}
			usort( $list, function( $a, $b ) {
				$oa = isset( $a->display_order ) ? (int) $a->display_order : 0;
				$ob = isset( $b->display_order ) ? (int) $b->display_order : 0;
				if ( $oa !== $ob ) return $oa <=> $ob;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		// Mock States / Universities query
		if ( strpos( $query, $this->prefix . 'iaq_mock_states_universities' ) !== false ) {
			$type_match = null;
			if ( preg_match( "/type = '([^']+)'/", $query, $tm ) ) {
				$type_match = $tm[1];
			}
			$status_match = null;
			if ( preg_match( "/status = '([^']+)'/", $query, $sm ) ) {
				$status_match = $sm[1];
			}
			$list = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_mock_states_universities' ] as $su ) {
				if ( $type_match && isset( $su->type ) && $su->type !== $type_match ) continue;
				if ( $status_match && isset( $su->status ) && $su->status !== $status_match ) continue;
				$list[] = clone $su;
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
		if ( strpos( $query, $this->prefix . 'iaq_mock_vacancies' ) !== false ) {
			$exam_match = null;
			if ( preg_match( "/(?:v\.)?(?:examination_id|exam_id) = (\d+)/", $query, $em ) ) {
				$exam_match = (int) $em[1];
			}
			$list = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_mock_vacancies' ] as $vac ) {
				$vac_eid = ! empty( $vac->examination_id ) ? (int) $vac->examination_id : ( ! empty( $vac->exam_id ) ? (int) $vac->exam_id : 0 );
				if ( $exam_match && $vac_eid !== $exam_match ) continue;
				$clone = clone $vac;
				$clone->exam_name = ( $vac_eid && isset( $this->tables[ $this->prefix . 'iaq_examinations' ][ $vac_eid ] ) ) ? $this->tables[ $this->prefix . 'iaq_examinations' ][ $vac_eid ]->name : '';
				$list[] = $clone;
			}
			return $list;
		}

		// Mock Series query
		if ( strpos( $query, $this->prefix . 'iaq_mock_series' ) !== false ) {
			$list = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_mock_series' ] as $ser ) {
				$clone = clone $ser;
				$clone->category_name = ( ! empty( $ser->category_id ) && isset( $this->tables[ $this->prefix . 'iaq_mock_categories' ][ $ser->category_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_mock_categories' ][ $ser->category_id ]->name : '';
				$clone->exam_name = ( ! empty( $ser->examination_id ) && isset( $this->tables[ $this->prefix . 'iaq_examinations' ][ $ser->examination_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_examinations' ][ $ser->examination_id ]->name : '';
				$clone->subject_name = ( ! empty( $ser->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $ser->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $ser->subject_id ]->name : '';
				$list[] = $clone;
			}
			return $list;
		}

		// Mock test relations / joined context query
		if ( strpos( $query, $this->prefix . 'iaq_mock_test_relations' ) !== false && strpos( $query, 'INNER JOIN' ) !== false ) {
			$cat_id   = preg_match( "/r\.category_id = (\d+)/", $query, $cm ) ? (int) $cm[1] : 0;
			$exam_id  = preg_match( "/r\.exam_id = (\d+)/", $query, $em ) ? (int) $em[1] : 0;
			$sub_id   = preg_match( "/r\.subject_id = (\d+)/", $query, $sm ) ? (int) $sm[1] : 0;
			$vac_id   = preg_match( "/r\.vacancy_id = (\d+)/", $query, $vm ) ? (int) $vm[1] : 0;
			$state_id = preg_match( "/r\.state_id = (\d+)/", $query, $stm ) ? (int) $stm[1] : 0;
			$ser_id   = preg_match( "/r\.series_id = (\d+)/", $query, $serm ) ? (int) $serm[1] : 0;

			$list = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_mock_test_relations' ] as $rel ) {
				if ( $cat_id && ! empty( $rel->category_id ) && (int) $rel->category_id !== $cat_id ) continue;
				if ( $exam_id && ! empty( $rel->exam_id ) && (int) $rel->exam_id !== $exam_id ) continue;
				if ( $sub_id && ! empty( $rel->subject_id ) && (int) $rel->subject_id !== $sub_id ) continue;
				if ( $vac_id && ! empty( $rel->vacancy_id ) && (int) $rel->vacancy_id !== $vac_id ) continue;
				if ( $state_id && ! empty( $rel->state_id ) && (int) $rel->state_id !== $state_id ) continue;
				if ( $ser_id && ! empty( $rel->series_id ) && (int) $rel->series_id !== $ser_id ) continue;

				$qid = (int) $rel->quiz_id;
				if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $qid ] ) ) {
					$qz = clone $this->tables[ $this->prefix . 'iaq_quizzes' ][ $qid ];
					if ( 'published' !== $qz->status || 'mock' !== $qz->quiz_type ) continue;
					$qz->mock_rel_id     = (int) $rel->id;
					$qz->mock_sort_order = (int) $rel->sort_order;
					$qz->mock_series_id  = (int) $rel->series_id;
					$list[] = $qz;
				}
			}
			usort( $list, function( $a, $b ) {
				if ( $a->mock_sort_order !== $b->mock_sort_order ) return $a->mock_sort_order <=> $b->mock_sort_order;
				return (int) $a->id <=> (int) $b->id;
			} );
			return $list;
		}

		// Direct mock quizzes query (quiz_type = 'mock')
		if ( strpos( $query, "q.quiz_type = 'mock'" ) !== false || strpos( $query, "quiz_type = 'mock'" ) !== false ) {
			$sub_id = preg_match( "/q\.subject_id = (\d+)/", $query, $sm ) ? (int) $sm[1] : 0;
			$exam_cat = preg_match( "/q\.exam_category = '([^']+)'/", $query, $ecm ) ? $ecm[1] : null;

			$list = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
				if ( 'mock' !== $qz->quiz_type ) continue;
				if ( isset( $qz->status ) && 'published' !== $qz->status ) continue;
				if ( $sub_id && isset( $qz->subject_id ) && (int) $qz->subject_id !== $sub_id ) continue;
				if ( $exam_cat && isset( $qz->exam_category ) && $qz->exam_category !== $exam_cat ) continue;

				$clone = clone $qz;
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
		$this->queries[] = $query;

		// Match single book
		if ( strpos( $query, $this->prefix . 'iaq_books' ) !== false ) {
			if ( preg_match( "/WHERE (?:b\.)?id = (\d+)/", $query, $m ) ) {
				$bid = (int) $m[1];
				if ( isset( $this->tables[ $this->prefix . 'iaq_books' ][ $bid ] ) ) {
					$bk = clone $this->tables[ $this->prefix . 'iaq_books' ][ $bid ];
					$bk->subject_name = ( ! empty( $bk->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $bk->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $bk->subject_id ]->name : '';
					$bk->examination_name = ( ! empty( $bk->examination_id ) && isset( $this->tables[ $this->prefix . 'iaq_examinations' ][ $bk->examination_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_examinations' ][ $bk->examination_id ]->name : '';
					$cnt = 0;
					foreach ( $this->tables[ $this->prefix . 'iaq_book_quizzes' ] as $bq ) {
						if ( (int) $bq->book_id === $bk->id ) {
							if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $bq->quiz_id ] ) && 'published' === $this->tables[ $this->prefix . 'iaq_quizzes' ][ $bq->quiz_id ]->status ) {
								$cnt++;
							}
						}
					}
					$bk->practice_sets_count = $cnt;
					return $bk;
				}
			}
			if ( preg_match( "/WHERE (?:b\.)?slug = '([^']+)'/", $query, $m ) ) {
				$slug = $m[1];
				foreach ( $this->tables[ $this->prefix . 'iaq_books' ] as $bk ) {
					if ( isset( $bk->slug ) && $bk->slug === $slug ) {
						$clone = clone $bk;
						$clone->subject_name = ( ! empty( $clone->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ]->name : '';
						$clone->examination_name = ( ! empty( $clone->examination_id ) && isset( $this->tables[ $this->prefix . 'iaq_examinations' ][ $clone->examination_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_examinations' ][ $clone->examination_id ]->name : '';
						$cnt = 0;
						foreach ( $this->tables[ $this->prefix . 'iaq_book_quizzes' ] as $bq ) {
							if ( (int) $bq->book_id === $clone->id ) {
								if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $bq->quiz_id ] ) && 'published' === $this->tables[ $this->prefix . 'iaq_quizzes' ][ $bq->quiz_id ]->status ) {
									$cnt++;
								}
							}
						}
						$clone->practice_sets_count = $cnt;
						return $clone;
					}
				}
			}
		}

		// Match single mock category
		if ( strpos( $query, $this->prefix . 'iaq_mock_categories' ) !== false ) {
			if ( preg_match( "/WHERE id = (\d+)/", $query, $m ) ) {
				$cid = (int) $m[1];
				return isset( $this->tables[ $this->prefix . 'iaq_mock_categories' ][ $cid ] ) ? clone $this->tables[ $this->prefix . 'iaq_mock_categories' ][ $cid ] : null;
			}
			if ( preg_match( "/WHERE slug = '([^']+)'/", $query, $m ) ) {
				$slug = $m[1];
				foreach ( $this->tables[ $this->prefix . 'iaq_mock_categories' ] as $cat ) {
					if ( isset( $cat->slug ) && $cat->slug === $slug ) return clone $cat;
				}
			}
		}

		// Match single mock state/univ
		if ( strpos( $query, $this->prefix . 'iaq_mock_states_universities' ) !== false ) {
			if ( preg_match( "/WHERE id = (\d+)/", $query, $m ) ) {
				$sid = (int) $m[1];
				return isset( $this->tables[ $this->prefix . 'iaq_mock_states_universities' ][ $sid ] ) ? clone $this->tables[ $this->prefix . 'iaq_mock_states_universities' ][ $sid ] : null;
			}
		}

		// Match single mock vacancy
		if ( strpos( $query, $this->prefix . 'iaq_mock_vacancies' ) !== false ) {
			if ( preg_match( "/WHERE id = (\d+)/", $query, $m ) ) {
				$vid = (int) $m[1];
				return isset( $this->tables[ $this->prefix . 'iaq_mock_vacancies' ][ $vid ] ) ? clone $this->tables[ $this->prefix . 'iaq_mock_vacancies' ][ $vid ] : null;
			}
		}

		// Match single mock series
		if ( strpos( $query, $this->prefix . 'iaq_mock_series' ) !== false ) {
			if ( preg_match( "/WHERE id = (\d+)/", $query, $m ) ) {
				$sid = (int) $m[1];
				return isset( $this->tables[ $this->prefix . 'iaq_mock_series' ][ $sid ] ) ? clone $this->tables[ $this->prefix . 'iaq_mock_series' ][ $sid ] : null;
			}
		}

		// Latest quiz attempt for practice set enrichment
		if ( strpos( $query, $this->prefix . 'iaq_quiz_attempts' ) !== false && preg_match( "/WHERE quiz_id = (\d+) AND user_id = (\d+)/", $query, $m ) && strpos( $query, 'ORDER BY id DESC' ) !== false ) {
			$quiz_id = (int) $m[1];
			$user_id = (int) $m[2];
			foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] ) as $att ) {
				if ( (int) $att->quiz_id === $quiz_id && (int) $att->user_id === $user_id ) {
					return clone $att;
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
			foreach ( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] as $att ) {
				if ( $att->user_id == $uid ) {
					$total++;
					if ( 'completed' === $att->status ) {
						$completed++;
						if ( ! empty( $att->passed ) ) $passed++;
						$sum_pct += (float) $att->percentage;
						$sum_score += (float) $att->score;
						if ( (float) $att->percentage > $best_pct ) $best_pct = (float) $att->percentage;
						$tot_q += (int) $att->total_questions;
						$tot_cor += (int) $att->correct_answers;
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
			foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] ) as $att ) {
				if ( $att->user_id == $uid && 'in_progress' === $att->status ) {
					$clone = clone $att;
					if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ] ) ) {
						$qz = $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ];
						$clone->quiz_title = $qz->title;
						$clone->duration_minutes = $qz->duration_minutes;
						$clone->exam_category = $qz->exam_category;
					}
					return $clone;
				}
			}
			return null;
		}

		// Match active in_progress or single attempt
		if ( strpos( $query, $this->prefix . 'iaq_quiz_attempts' ) !== false ) {
			if ( preg_match( "/WHERE quiz_id = (\d+) AND user_id = (\d+) AND status = 'in_progress'/", $query, $m ) ) {
				$quiz_id = (int) $m[1];
				$user_id = (int) $m[2];
				$matched = null;
				foreach ( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] as $att ) {
					if ( $att->quiz_id == $quiz_id && $att->user_id == $user_id && $att->status === 'in_progress' ) {
						$matched = clone $att;
					}
				}
				return $matched;
			}
			if ( preg_match( "/WHERE (?:a\.)?id = (\d+)/", $query, $m ) ) {
				$id = (int) $m[1];
				if ( isset( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ][ $id ] ) ) {
					$att = clone $this->tables[ $this->prefix . 'iaq_quiz_attempts' ][ $id ];
					if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ] ) ) {
						$qz = $this->tables[ $this->prefix . 'iaq_quizzes' ][ $att->quiz_id ];
						$att->duration_minutes  = isset( $qz->duration_minutes ) ? $qz->duration_minutes : 0;
						$att->q_correct         = isset( $qz->correct_marks ) ? $qz->correct_marks : 1.0;
						$att->q_negative        = isset( $qz->negative_marks ) ? $qz->negative_marks : 0.25;
						$att->pass_percentage   = isset( $qz->pass_percentage ) ? $qz->pass_percentage : 40.0;
						$att->show_answers_mode = isset( $qz->show_answers_mode ) ? $qz->show_answers_mode : 'after_submission';
						$att->quiz_title        = isset( $qz->title ) ? $qz->title : '';
						$att->quiz_type         = isset( $qz->quiz_type ) ? $qz->quiz_type : 'practice';
						$att->exam_category     = isset( $qz->exam_category ) ? $qz->exam_category : 'General';
					}
					return $att;
				}
			}
		}

		// Match single quiz
		if ( strpos( $query, $this->prefix . 'iaq_quizzes' ) !== false && preg_match( "/(WHERE q\.id = |WHERE id = )(\d+)/", $query, $m ) ) {
			$id = (int) $m[2];
			if ( isset( $this->tables[ $this->prefix . 'iaq_quizzes' ][ $id ] ) ) {
				$qz = clone $this->tables[ $this->prefix . 'iaq_quizzes' ][ $id ];
				$qz->subject_name = ( ! empty( $qz->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $qz->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $qz->subject_id ]->name : '';
				$qz->topic_name   = ( ! empty( $qz->topic_id ) && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $qz->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $qz->topic_id ]->name : '';
				return $qz;
			}
		}

		if ( strpos( $query, $this->prefix . 'iaq_quizzes' ) !== false && preg_match( "/WHERE (?:q\.)?title = '([^']+)'/", $query, $m ) ) {
			$title = $m[1];
			foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
				if ( isset( $qz->title ) && $qz->title === $title ) {
					$clone = clone $qz;
					$clone->subject_name = ( ! empty( $clone->subject_id ) && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ]->name : '';
					$clone->topic_name   = ( ! empty( $clone->topic_id ) && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $clone->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $clone->topic_id ]->name : '';
					return $clone;
				}
			}
		}

		if ( strpos( $query, $this->prefix . 'iaq_quizzes' ) !== false && preg_match( "/WHERE subject_id = (\d+)/", $query, $m ) ) {
			$sid = (int) $m[1];
			foreach ( $this->tables[ $this->prefix . 'iaq_quizzes' ] as $qz ) {
				if ( isset( $qz->subject_id ) && $qz->subject_id == $sid && 'published' === $qz->status ) {
					$clone = clone $qz;
					$clone->subject_name = ( $clone->subject_id && isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $clone->subject_id ]->name : '';
					$clone->topic_name   = ( $clone->topic_id && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $clone->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $clone->topic_id ]->name : '';
					return $clone;
				}
			}
		}

		// Match single question
		if ( preg_match( "/WHERE q\.id = (\d+)/", $query, $m ) ) {
			$id = (int) $m[1];
			if ( isset( $this->tables[ $this->prefix . 'iaq_questions' ][ $id ] ) ) {
				$q = clone $this->tables[ $this->prefix . 'iaq_questions' ][ $id ];
				$q->subject_name = isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ] ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $q->subject_id ]->name : '';
				$q->topic_name   = ( $q->topic_id && isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ] ) ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $q->topic_id ]->name : '';
				return $q;
			}
		}

		// Match single subject or topic
		if ( preg_match( "/WHERE id = (\d+)/", $query, $m ) ) {
			$id = (int) $m[1];
			if ( strpos( $query, $this->prefix . 'iaq_subjects' ) !== false ) {
				return isset( $this->tables[ $this->prefix . 'iaq_subjects' ][ $id ] ) ? $this->tables[ $this->prefix . 'iaq_subjects' ][ $id ] : null;
			}
			if ( strpos( $query, $this->prefix . 'iaq_topics' ) !== false ) {
				return isset( $this->tables[ $this->prefix . 'iaq_topics' ][ $id ] ) ? $this->tables[ $this->prefix . 'iaq_topics' ][ $id ] : null;
			}
			if ( strpos( $query, $this->prefix . 'iaq_examinations' ) !== false ) {
				return isset( $this->tables[ $this->prefix . 'iaq_examinations' ][ $id ] ) ? clone $this->tables[ $this->prefix . 'iaq_examinations' ][ $id ] : null;
			}
			if ( strpos( $query, $this->prefix . 'iaq_subscription_plans' ) !== false ) {
				return isset( $this->tables[ $this->prefix . 'iaq_subscription_plans' ][ $id ] ) ? clone $this->tables[ $this->prefix . 'iaq_subscription_plans' ][ $id ] : null;
			}
			if ( strpos( $query, $this->prefix . 'iaq_user_subscriptions' ) !== false ) {
				return isset( $this->tables[ $this->prefix . 'iaq_user_subscriptions' ][ $id ] ) ? clone $this->tables[ $this->prefix . 'iaq_user_subscriptions' ][ $id ] : null;
			}
		}

		if ( strpos( $query, $this->prefix . 'iaq_examinations' ) !== false && preg_match( "/WHERE (?:code = )'([^']+)'/", $query, $m ) ) {
			$cd = $m[1];
			foreach ( $this->tables[ $this->prefix . 'iaq_examinations' ] as $ex ) {
				if ( isset( $ex->code ) && $ex->code === $cd ) return clone $ex;
			}
		}

		if ( strpos( $query, $this->prefix . 'iaq_subscription_plans' ) !== false ) {
			if ( preg_match( "/WHERE (?:code = |slug = )'([^']+)'/", $query, $m ) ) {
				$cd = $m[1];
				foreach ( $this->tables[ $this->prefix . 'iaq_subscription_plans' ] as $pl ) {
					if ( ( isset( $pl->code ) && $pl->code === $cd ) || ( isset( $pl->slug ) && $pl->slug === $cd ) ) return clone $pl;
				}
			}
			if ( preg_match( "/WHERE (?:wc_product_id = )(\d+)/", $query, $m ) ) {
				$pid = (int) $m[1];
				foreach ( $this->tables[ $this->prefix . 'iaq_subscription_plans' ] as $pl ) {
					if ( isset( $pl->wc_product_id ) && (int) $pl->wc_product_id === $pid ) return clone $pl;
				}
			}
		}

		if ( strpos( $query, $this->prefix . 'iaq_user_subscriptions' ) !== false ) {
			if ( preg_match( "/WHERE wc_order_id = (\d+)/", $query, $m ) ) {
				$oid = (int) $m[1];
				foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_user_subscriptions' ] ) as $us ) {
					if ( isset( $us->wc_order_id ) && (int) $us->wc_order_id === $oid ) {
						return clone $us;
					}
				}
			}
			if ( preg_match( "/WHERE user_id = (\d+) AND plan_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$pid = (int) $m[2];
				foreach ( array_reverse( $this->tables[ $this->prefix . 'iaq_user_subscriptions' ] ) as $us ) {
					if ( (int) $us->user_id === $uid && (int) $us->plan_id === $pid && 'active' === $us->status ) {
						return clone $us;
					}
				}
			}
		}

		return null;
	}

	public function get_var( $query ) {
		$this->queries[] = $query;

		// COUNT queries
		if ( strpos( $query, 'COUNT(*)' ) !== false ) {
			if ( strpos( $query, $this->prefix . 'iaq_quizzes' ) !== false ) {
				return count( $this->tables[ $this->prefix . 'iaq_quizzes' ] );
			}
			if ( strpos( $query, $this->prefix . 'iaq_questions' ) !== false ) {
				return count( $this->tables[ $this->prefix . 'iaq_questions' ] );
			}
			if ( strpos( $query, $this->prefix . 'iaq_subjects' ) !== false ) {
				return count( $this->tables[ $this->prefix . 'iaq_subjects' ] );
			}
			if ( strpos( $query, $this->prefix . 'iaq_topics' ) !== false ) {
				return count( $this->tables[ $this->prefix . 'iaq_topics' ] );
			}
			if ( strpos( $query, $this->prefix . 'iaq_examinations' ) !== false ) {
				return count( $this->tables[ $this->prefix . 'iaq_examinations' ] );
			}
			if ( strpos( $query, $this->prefix . 'iaq_subscription_plans' ) !== false ) {
				return count( $this->tables[ $this->prefix . 'iaq_subscription_plans' ] );
			}
			if ( strpos( $query, $this->prefix . 'iaq_user_subscriptions' ) !== false ) {
				return count( $this->tables[ $this->prefix . 'iaq_user_subscriptions' ] );
			}
			if ( strpos( $query, $this->prefix . 'iaq_books' ) !== false ) {
				return count( $this->tables[ $this->prefix . 'iaq_books' ] );
			}
		}

		// Books check
		if ( strpos( $query, $this->prefix . 'iaq_books' ) !== false ) {
			if ( preg_match( "/slug = '([^']+)'/", $query, $sm ) ) {
				$slug = $sm[1];
				$ex_id = 0;
				if ( preg_match( "/id != (\d+)/", $query, $id_m ) ) {
					$ex_id = (int) $id_m[1];
				}
				foreach ( $this->tables[ $this->prefix . 'iaq_books' ] as $bk ) {
					if ( isset( $bk->slug ) && $bk->slug === $slug && (int) $bk->id !== $ex_id ) {
						return $bk->id;
					}
				}
				return null;
			}
		}

		// Book quizzes get_var
		if ( strpos( $query, $this->prefix . 'iaq_book_quizzes' ) !== false ) {
			if ( preg_match( "/MAX\(sort_order\) FROM [^\s]+ WHERE book_id = (\d+)/", $query, $m ) ) {
				$bid = (int) $m[1];
				$max = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_book_quizzes' ] as $bq ) {
					if ( (int) $bq->book_id === $bid && (int) $bq->sort_order > $max ) {
						$max = (int) $bq->sort_order;
					}
				}
				return $max;
			}
			if ( preg_match( "/WHERE book_id = (\d+) AND quiz_id = (\d+)/", $query, $m ) ) {
				$bid = (int) $m[1];
				$qid = (int) $m[2];
				foreach ( $this->tables[ $this->prefix . 'iaq_book_quizzes' ] as $bq ) {
					if ( (int) $bq->book_id === $bid && (int) $bq->quiz_id === $qid ) {
						return $bq->id;
					}
				}
				return null;
			}
		}

		// Exam subjects get_var
		if ( strpos( $query, $this->prefix . 'iaq_exam_subjects' ) !== false ) {
			if ( preg_match( "/MAX\(display_order\) FROM [^\s]+ WHERE exam_id = (\d+)/", $query, $m ) ) {
				$eid = (int) $m[1];
				$max = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_exam_subjects' ] as $es ) {
					if ( (int) $es->exam_id === $eid && (int) $es->display_order > $max ) {
						$max = (int) $es->display_order;
					}
				}
				return $max;
			}
			if ( preg_match( "/WHERE exam_id = (\d+) AND subject_id = (\d+)/", $query, $m ) ) {
				$eid = (int) $m[1];
				$sid = (int) $m[2];
				foreach ( $this->tables[ $this->prefix . 'iaq_exam_subjects' ] as $es ) {
					if ( (int) $es->exam_id === $eid && (int) $es->subject_id === $sid ) {
						return $es->id;
					}
				}
				return null;
			}
		}

		// Exam subject quizzes get_var
		if ( strpos( $query, $this->prefix . 'iaq_exam_subject_quizzes' ) !== false ) {
			if ( preg_match( "/MAX\(sort_order\) FROM [^\s]+ WHERE exam_id = (\d+) AND subject_id = (\d+)/", $query, $m ) ) {
				$eid = (int) $m[1];
				$sid = (int) $m[2];
				$max = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_exam_subject_quizzes' ] as $esq ) {
					if ( (int) $esq->exam_id === $eid && (int) $esq->subject_id === $sid && (int) $esq->sort_order > $max ) {
						$max = (int) $esq->sort_order;
					}
				}
				return $max;
			}
			if ( preg_match( "/WHERE exam_id = (\d+) AND subject_id = (\d+) AND quiz_id = (\d+)/", $query, $m ) ) {
				$eid = (int) $m[1];
				$sid = (int) $m[2];
				$qid = (int) $m[3];
				foreach ( $this->tables[ $this->prefix . 'iaq_exam_subject_quizzes' ] as $esq ) {
					if ( (int) $esq->exam_id === $eid && (int) $esq->subject_id === $sid && (int) $esq->quiz_id === $qid ) {
						return $esq->id;
					}
				}
				return null;
			}
		}

		// Attempts MAX attempt number
		if ( strpos( $query, $this->prefix . 'iaq_quiz_attempts' ) !== false ) {
			if ( strpos( $query, 'MAX(attempt_number)' ) !== false && preg_match( "/WHERE quiz_id = (\d+) AND user_id = (\d+)/", $query, $m ) ) {
				$quiz_id = (int) $m[1];
				$user_id = (int) $m[2];
				$max_num = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] as $att ) {
					if ( $att->quiz_id == $quiz_id && $att->user_id == $user_id && $att->attempt_number > $max_num ) {
						$max_num = (int) $att->attempt_number;
					}
				}
				return $max_num;
			}
			if ( strpos( $query, 'COUNT(id)' ) !== false && preg_match( "/WHERE quiz_id = (\d+) AND user_id = (\d+) AND status = 'completed'/", $query, $m ) ) {
				$quiz_id = (int) $m[1];
				$user_id = (int) $m[2];
				$cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_quiz_attempts' ] as $att ) {
					if ( $att->quiz_id == $quiz_id && $att->user_id == $user_id && $att->status === 'completed' ) {
						$cnt++;
					}
				}
				return $cnt;
			}
		}

		// Attempt Answers get ID and counts
		if ( strpos( $query, $this->prefix . 'iaq_attempt_answers' ) !== false ) {
			if ( preg_match( "/WHERE attempt_id = (\d+) AND question_id = (\d+)/", $query, $m ) ) {
				$att_id = (int) $m[1];
				$qid    = (int) $m[2];
				foreach ( $this->tables[ $this->prefix . 'iaq_attempt_answers' ] as $ans ) {
					if ( $ans->attempt_id == $att_id && $ans->question_id == $qid ) {
						return $ans->id;
					}
				}
				return null;
			}
			if ( strpos( $query, 'selected_option IS NOT NULL' ) !== false && preg_match( "/WHERE attempt_id = (\d+)/", $query, $m ) ) {
				$att_id = (int) $m[1];
				$cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_attempt_answers' ] as $ans ) {
					if ( $ans->attempt_id == $att_id && ! empty( $ans->selected_option ) ) {
						$cnt++;
					}
				}
				return $cnt;
			}
			if ( strpos( $query, 'is_marked_for_review = 1' ) !== false && preg_match( "/WHERE attempt_id = (\d+)/", $query, $m ) ) {
				$att_id = (int) $m[1];
				$cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_attempt_answers' ] as $ans ) {
					if ( $ans->attempt_id == $att_id && ! empty( $ans->is_marked_for_review ) ) {
						$cnt++;
					}
				}
				return $cnt;
			}
		}

		if ( strpos( $query, 'MAX(question_order)' ) !== false ) {
			$max = 0;
			foreach ( $this->tables[ $this->prefix . 'iaq_quiz_questions' ] as $qq ) {
				if ( $qq->question_order > $max ) $max = $qq->question_order;
			}
			return $max;
		}

		// Exists check for quiz_questions
		if ( strpos( $query, $this->prefix . 'iaq_quiz_questions' ) !== false && preg_match( "/quiz_id = (\d+) AND question_id = (\d+)/", $query, $m ) ) {
			$quiz_id = (int) $m[1];
			$qid     = (int) $m[2];
			foreach ( $this->tables[ $this->prefix . 'iaq_quiz_questions' ] as $qq ) {
				if ( $qq->quiz_id == $quiz_id && $qq->question_id == $qid ) {
					return $qq->id;
				}
			}
			return null;
		}

		// Exists check for examinations code or slug
		if ( strpos( $query, $this->prefix . 'iaq_examinations' ) !== false ) {
			if ( preg_match( "/code = '([^']+)' AND id != (\d+)/", $query, $m ) ) {
				$code  = $m[1];
				$ex_id = (int) $m[2];
				foreach ( $this->tables[ $this->prefix . 'iaq_examinations' ] as $ex ) {
					if ( isset( $ex->code ) && $ex->code === $code && (int) $ex->id !== $ex_id ) {
						return $ex->id;
					}
				}
				return null;
			}
			if ( preg_match( "/slug = '([^']+)' AND id != (\d+)/", $query, $m ) ) {
				$slug  = $m[1];
				$ex_id = (int) $m[2];
				foreach ( $this->tables[ $this->prefix . 'iaq_examinations' ] as $ex ) {
					if ( isset( $ex->slug ) && $ex->slug === $slug && (int) $ex->id !== $ex_id ) {
						return $ex->id;
					}
				}
				return null;
			}
		}

		// Exists check for subscription plans code or slug
		if ( strpos( $query, $this->prefix . 'iaq_subscription_plans' ) !== false ) {
			if ( preg_match( "/(?:slug|code) = '([^']+)'/", $query, $m ) ) {
				$code  = $m[1];
				$ex_id = 0;
				if ( preg_match( "/id != (\d+)/", $query, $id_m ) ) {
					$ex_id = (int) $id_m[1];
				}
				foreach ( $this->tables[ $this->prefix . 'iaq_subscription_plans' ] as $pl ) {
					if ( ( ( isset( $pl->code ) && $pl->code === $code ) || ( isset( $pl->slug ) && $pl->slug === $code ) ) && (int) $pl->id !== $ex_id ) {
						return $pl->id;
					}
				}
				return null;
			}
		}

		// Exists check for questions table
		if ( strpos( $query, $this->prefix . 'iaq_questions' ) !== false ) {
			if ( preg_match( "/SELECT id FROM " . preg_quote( $this->prefix . 'iaq_questions', '/' ) . " WHERE id = (\d+)/", $query, $m ) ) {
				$qid = (int) $m[1];
				return isset( $this->tables[ $this->prefix . 'iaq_questions' ][ $qid ] ) ? $qid : null;
			}
			if ( strpos( $query, 'COUNT(id)' ) !== false ) {
				$cnt = 0;
				$subj_match = preg_match( "/subject_id = (\d+)/", $query, $sm ) ? (int) $sm[1] : 0;
				$diff_match = preg_match( "/difficulty = '([^']+)'/", $query, $dm ) ? $dm[1] : null;
				$cat_match  = preg_match( "/exam_category = '([^']+)'/", $query, $cm ) ? $cm[1] : null;
				foreach ( $this->tables[ $this->prefix . 'iaq_questions' ] as $q ) {
					if ( isset( $q->status ) && 'published' !== $q->status ) continue;
					if ( $subj_match && (int) $q->subject_id !== $subj_match ) continue;
					if ( $diff_match && $q->difficulty !== $diff_match ) continue;
					if ( $cat_match && $q->exam_category !== $cat_match ) continue;
					$cnt++;
				}
				return $cnt;
			}
		}

		// Bookmarks get_var
		if ( strpos( $query, $this->prefix . 'iaq_bookmarks' ) !== false ) {
			if ( preg_match( "/WHERE (?:b\.)?user_id = (\d+) AND (?:b\.)?question_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$qid = (int) $m[2];
				foreach ( $this->tables[ $this->prefix . 'iaq_bookmarks' ] as $bm ) {
					if ( (int) $bm->user_id === $uid && (int) $bm->question_id === $qid ) {
						return $bm->id;
					}
				}
				return null;
			}
			if ( preg_match( "/COUNT\((?:b\.)?id\)/", $query ) && preg_match( "/WHERE (?:b\.)?user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_bookmarks' ] as $bm ) {
					if ( (int) $bm->user_id === $uid ) $cnt++;
				}
				return $cnt;
			}
		}

		// Wrong Notebook get_var
		if ( strpos( $query, $this->prefix . 'iaq_wrong_notebook' ) !== false ) {
			if ( preg_match( "/WHERE (?:n\.)?user_id = (\d+) AND (?:n\.)?question_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$qid = (int) $m[2];
				foreach ( $this->tables[ $this->prefix . 'iaq_wrong_notebook' ] as $wn ) {
					if ( (int) $wn->user_id === $uid && (int) $wn->question_id === $qid ) {
						return $wn->id;
					}
				}
				return null;
			}
			if ( preg_match( "/COUNT\((?:n\.)?id\)/", $query ) && preg_match( "/WHERE (?:n\.)?user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$status = null;
				if ( preg_match( "/(?:n\.)?status = '([^']+)'/", $query, $sm ) ) {
					$status = $sm[1];
				}
				$cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_wrong_notebook' ] as $wn ) {
					if ( (int) $wn->user_id === $uid ) {
						if ( null === $status || ( isset( $wn->status ) && $wn->status === $status ) ) {
							$cnt++;
						}
					}
				}
				return $cnt;
			}
		}

		// Study Activity get_var
		if ( strpos( $query, $this->prefix . 'iaq_study_activity' ) !== false ) {
			if ( preg_match( "/WHERE user_id = (\d+) AND activity_date = '([^']+)'/", $query, $m ) ) {
				$uid  = (int) $m[1];
				$date = $m[2];
				foreach ( $this->tables[ $this->prefix . 'iaq_study_activity' ] as $sa ) {
					if ( (int) $sa->user_id === $uid && $sa->activity_date === $date ) {
						return $sa->id;
					}
				}
				return null;
			}
			if ( strpos( $query, 'COUNT(DISTINCT activity_date)' ) !== false && preg_match( "/WHERE user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$dates = array();
				foreach ( $this->tables[ $this->prefix . 'iaq_study_activity' ] as $sa ) {
					if ( (int) $sa->user_id === $uid ) $dates[ $sa->activity_date ] = true;
				}
				return count( $dates );
			}
			if ( strpos( $query, 'SUM(questions_solved)' ) !== false && preg_match( "/WHERE user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$sum = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_study_activity' ] as $sa ) {
					if ( (int) $sa->user_id === $uid ) $sum += (int) $sa->questions_solved;
				}
				return $sum;
			}
			if ( strpos( $query, 'COUNT(id)' ) !== false && preg_match( "/WHERE user_id = (\d+)/", $query, $m ) ) {
				$uid = (int) $m[1];
				$cnt = 0;
				foreach ( $this->tables[ $this->prefix . 'iaq_study_activity' ] as $sa ) {
					if ( (int) $sa->user_id === $uid ) $cnt++;
				}
				return $cnt;
			}
		}

		return null;
	}

	public function get_col( $query ) {
		$this->queries[] = $query;
		$col = array();
		if ( strpos( $query, 'SELECT id FROM ' . $this->prefix . 'iaq_questions' ) !== false ) {
			foreach ( $this->tables[ $this->prefix . 'iaq_questions' ] as $q ) {
				$col[] = $q->id;
			}
			return $col;
		}

		if ( strpos( $query, 'DISTINCT activity_date FROM ' . $this->prefix . 'iaq_study_activity' ) !== false && preg_match( "/WHERE user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			$dates = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_study_activity' ] as $sa ) {
				if ( (int) $sa->user_id === $uid ) {
					$dates[ $sa->activity_date ] = true;
				}
			}
			$keys = array_keys( $dates );
			rsort( $keys );
			return $keys;
		}

		if ( strpos( $query, 'question_id FROM ' . $this->prefix . 'iaq_bookmarks' ) !== false && preg_match( "/WHERE user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			$ids = array();
			foreach ( $this->tables[ $this->prefix . 'iaq_bookmarks' ] as $bm ) {
				if ( (int) $bm->user_id === $uid ) {
					$ids[] = $bm->question_id;
				}
			}
			return $ids;
		}

		if ( strpos( $query, 'question_id FROM ' . $this->prefix . 'iaq_wrong_notebook' ) !== false && preg_match( "/WHERE user_id = (\d+)/", $query, $m ) ) {
			$uid = (int) $m[1];
			$ids = array();
			$status = null;
			if ( preg_match( "/status = '([^']+)'/", $query, $sm ) ) {
				$status = $sm[1];
			}
			foreach ( $this->tables[ $this->prefix . 'iaq_wrong_notebook' ] as $wn ) {
				if ( (int) $wn->user_id === $uid ) {
					if ( null === $status || ( isset( $wn->status ) && $wn->status === $status ) ) {
						$ids[] = $wn->question_id;
					}
				}
			}
			return $ids;
		}

		return $col;
	}

	public function query( $query ) {
		$this->queries[] = $query;
		if ( strpos( $query, 'UPDATE ' . $this->prefix . 'iaq_study_activity' ) !== false ) {
			if ( preg_match( "/WHERE id = (\d+)/", $query, $m ) ) {
				$id = (int) $m[1];
				if ( isset( $this->tables[ $this->prefix . 'iaq_study_activity' ][ $id ] ) ) {
					if ( preg_match( "/questions_solved = GREATEST\(questions_solved,\s*(\d+)\)/", $query, $qm ) ) {
						$qs = (int) $qm[1];
						$current_qs = (int) $this->tables[ $this->prefix . 'iaq_study_activity' ][ $id ]->questions_solved;
						$this->tables[ $this->prefix . 'iaq_study_activity' ][ $id ]->questions_solved = max( $current_qs, $qs );
					}
					if ( preg_match( "/time_spent_seconds = GREATEST\(time_spent_seconds,\s*(\d+)\)/", $query, $tm ) ) {
						$ts = (int) $tm[1];
						$current_ts = (int) $this->tables[ $this->prefix . 'iaq_study_activity' ][ $id ]->time_spent_seconds;
						$this->tables[ $this->prefix . 'iaq_study_activity' ][ $id ]->time_spent_seconds = max( $current_ts, $ts );
					}
				}
			}
		}
		return 1;
	}
}

global $wpdb;
$wpdb = new Mock_WPDB();

// Require plugin core files.
require_once dirname( __DIR__ ) . '/includes/class-database.php';
require_once dirname( __DIR__ ) . '/includes/class-activator.php';
require_once dirname( __DIR__ ) . '/includes/class-deactivator.php';
require_once dirname( __DIR__ ) . '/includes/class-question-bank.php';
require_once dirname( __DIR__ ) . '/includes/class-examinations.php';
require_once dirname( __DIR__ ) . '/includes/class-access-control.php';
require_once dirname( __DIR__ ) . '/includes/class-quiz-builder.php';
require_once dirname( __DIR__ ) . '/includes/class-practice-sets.php';
require_once dirname( __DIR__ ) . '/includes/class-mock-tests.php';
require_once dirname( __DIR__ ) . '/includes/class-quiz-attempt.php';
require_once dirname( __DIR__ ) . '/includes/class-bookmarks.php';
require_once dirname( __DIR__ ) . '/includes/class-wrong-notebook.php';
require_once dirname( __DIR__ ) . '/includes/class-custom-practice.php';
require_once dirname( __DIR__ ) . '/includes/class-leaderboard.php';
require_once dirname( __DIR__ ) . '/includes/class-study-streaks.php';
require_once dirname( __DIR__ ) . '/includes/class-import-export.php';
require_once dirname( __DIR__ ) . '/includes/class-frontend.php';
require_once dirname( __DIR__ ) . '/includes/class-admin.php';
require_once dirname( __DIR__ ) . '/includes/class-plugin.php';
