<?php
/**
 * Quiz Session repository.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Handles quiz session database operations and state transitions.
 */
class PremiaQuiz_Session_Repository {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Valid session statuses.
	 *
	 * @var array
	 */
	const VALID_STATUSES = array( 'in_progress', 'completed', 'abandoned' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'premiaquiz_sessions';
	}

	/**
	 * Get a session by ID.
	 *
	 * @param int $id Session ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);

		if ( $row && is_string( $row->quiz_snapshot ) ) {
			$row->quiz_snapshot = json_decode( $row->quiz_snapshot, true );
		}

		return $row;
	}

	/**
	 * Get a session by session key and quiz ID.
	 *
	 * @param string $session_key Session key.
	 * @param int    $quiz_id     Quiz ID.
	 * @return object|null
	 */
	public function get_by_key( $session_key, $quiz_id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE session_key = %s AND quiz_id = %d",
				$session_key,
				$quiz_id
			)
		);

		if ( $row && is_string( $row->quiz_snapshot ) ) {
			$row->quiz_snapshot = json_decode( $row->quiz_snapshot, true );
		}

		return $row;
	}

	/**
	 * Get the active session for a user on a quiz.
	 *
	 * @param int    $user_id     WordPress user ID (0 for anonymous).
	 * @param int    $quiz_id     Quiz ID.
	 * @param string $session_key Session key for anonymous users.
	 * @return object|null
	 */
	public function get_active( $user_id, $quiz_id, $session_key = '' ) {
		global $wpdb;

		if ( $user_id > 0 ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table} WHERE user_id = %d AND quiz_id = %d AND status = 'in_progress'",
					$user_id,
					$quiz_id
				)
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table} WHERE session_key = %s AND quiz_id = %d AND status = 'in_progress'",
					$session_key,
					$quiz_id
				)
			);
		}

		if ( $row && is_string( $row->quiz_snapshot ) ) {
			$row->quiz_snapshot = json_decode( $row->quiz_snapshot, true );
		}

		return $row;
	}

	/**
	 * Get sessions for a quiz.
	 *
	 * @param int    $quiz_id Quiz ID.
	 * @param string $status  Optional status filter.
	 * @param int    $limit   Number of results.
	 * @param int    $offset  Offset.
	 * @return array
	 */
	public function get_by_quiz( $quiz_id, $status = '', $limit = 20, $offset = 0 ) {
		global $wpdb;

		if ( $status ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table} WHERE quiz_id = %d AND status = %s ORDER BY started_at DESC LIMIT %d OFFSET %d",
					$quiz_id,
					$status,
					$limit,
					$offset
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE quiz_id = %d ORDER BY started_at DESC LIMIT %d OFFSET %d",
				$quiz_id,
				$limit,
				$offset
			)
		);
	}

	/**
	 * Create a new session.
	 *
	 * @param array $data {
	 *     Session data.
	 *
	 *     @type int    $quiz_id       Quiz ID.
	 *     @type int    $user_id       WordPress user ID (0 for anonymous).
	 *     @type string $session_key   Unique session key.
	 *     @type string $ip_address    User IP address.
	 *     @type string $user_agent    User agent string.
	 *     @type array  $quiz_snapshot Quiz structure snapshot.
	 * }
	 * @return int|false The session ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'quiz_id'       => 0,
			'user_id'       => 0,
			'session_key'   => '',
			'ip_address'    => '',
			'user_agent'    => '',
			'quiz_snapshot' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['session_key'] ) ) {
			$data['session_key'] = wp_generate_password( 32, false );
		}

		$result = $wpdb->insert(
			$this->table,
			array(
				'quiz_id'       => absint( $data['quiz_id'] ),
				'user_id'       => absint( $data['user_id'] ),
				'session_key'   => sanitize_text_field( $data['session_key'] ),
				'status'        => 'in_progress',
				'ip_address'    => sanitize_text_field( $data['ip_address'] ),
				'user_agent'    => sanitize_text_field( $data['user_agent'] ),
				'quiz_snapshot' => wp_json_encode( $data['quiz_snapshot'] ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Transition session to completed status.
	 *
	 * @param int   $id             Session ID.
	 * @param float $score          Final score.
	 * @param float $percentage     Final percentage.
	 * @param int   $result_page_id Matched result page ID.
	 * @return bool
	 */
	public function complete( $id, $score, $percentage, $result_page_id = 0 ) {
		global $wpdb;

		$current = $this->get( $id );

		if ( ! $current || 'in_progress' !== $current->status ) {
			return false;
		}

		return (bool) $wpdb->update(
			$this->table,
			array(
				'status'         => 'completed',
				'score'          => floatval( $score ),
				'percentage'     => floatval( $percentage ),
				'result_page_id' => absint( $result_page_id ),
				'completed_at'   => current_time( 'mysql' ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%f', '%f', '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Transition session to abandoned status.
	 *
	 * @param int $id Session ID.
	 * @return bool
	 */
	public function abandon( $id ) {
		global $wpdb;

		$current = $this->get( $id );

		if ( ! $current || 'in_progress' !== $current->status ) {
			return false;
		}

		return (bool) $wpdb->update(
			$this->table,
			array( 'status' => 'abandoned' ),
			array( 'id' => absint( $id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete a session.
	 *
	 * @param int $id Session ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		return (bool) $wpdb->delete(
			$this->table,
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);
	}

	/**
	 * Delete all sessions for a quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return bool
	 */
	public function delete_by_quiz( $quiz_id ) {
		global $wpdb;

		return (bool) $wpdb->delete(
			$this->table,
			array( 'quiz_id' => absint( $quiz_id ) ),
			array( '%d' )
		);
	}

	/**
	 * Count sessions by status for a quiz.
	 *
	 * @param int    $quiz_id Quiz ID.
	 * @param string $status  Status to count.
	 * @return int
	 */
	public function count( $quiz_id, $status = '' ) {
		global $wpdb;

		if ( $status ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE quiz_id = %d AND status = %s",
					$quiz_id,
					$status
				)
			);
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE quiz_id = %d",
				$quiz_id
			)
		);
	}

	/**
	 * Delete sessions older than retention days for a quiz.
	 *
	 * @param int $quiz_id        Quiz ID.
	 * @param int $retention_days Number of days to keep.
	 * @return int Number of deleted sessions.
	 */
	public function cleanup_old( $quiz_id, $retention_days ) {
		global $wpdb;

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE quiz_id = %d AND started_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$quiz_id,
				$retention_days
			)
		);
	}
}
