<?php
/**
 * Quiz Answer repository.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Handles quiz answer database operations.
 */
class PremiaQuiz_Answer_Repository {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'premiaquiz_answers';
	}

	/**
	 * Get an answer by ID.
	 *
	 * @param int $id Answer ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);
	}

	/**
	 * Get all answers for a session.
	 *
	 * @param int $session_id Session ID.
	 * @return array
	 */
	public function get_by_session( $session_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE session_id = %d ORDER BY answered_at ASC",
				$session_id
			)
		);
	}

	/**
	 * Get the answer for a specific question in a session.
	 *
	 * @param int $session_id  Session ID.
	 * @param int $question_id Question ID.
	 * @return object|null
	 */
	public function get_by_session_and_question( $session_id, $question_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE session_id = %d AND question_id = %d",
				$session_id,
				$question_id
			)
		);
	}

	/**
	 * Create or update an answer (upsert by session_id + question_id).
	 *
	 * @param array $data {
	 *     Answer data.
	 *
	 *     @type int    $session_id     Session ID.
	 *     @type int    $question_id    Question ID.
	 *     @type string $answer_value   The answer value.
	 *     @type bool   $is_correct     Whether correct (nullable).
	 *     @type float  $points_awarded Points awarded.
	 * }
	 * @return int|false The answer ID on success, false on failure.
	 */
	public function save( $data ) {
		global $wpdb;

		$defaults = array(
			'session_id'     => 0,
			'question_id'    => 0,
			'answer_value'   => '',
			'is_correct'     => null,
			'points_awarded' => 0.0,
		);

		$data = wp_parse_args( $data, $defaults );

		$existing = $this->get_by_session_and_question( $data['session_id'], $data['question_id'] );

		if ( $existing ) {
			$wpdb->update(
				$this->table,
				array(
					'answer_value'   => sanitize_text_field( $data['answer_value'] ),
					'is_correct'     => null !== $data['is_correct'] ? ( $data['is_correct'] ? 1 : 0 ) : null,
					'points_awarded' => floatval( $data['points_awarded'] ),
				),
				array( 'id' => $existing->id ),
				array( '%s', '%d', '%f' ),
				array( '%d' )
			);

			return $existing->id;
		}

		$result = $wpdb->insert(
			$this->table,
			array(
				'session_id'     => absint( $data['session_id'] ),
				'question_id'    => absint( $data['question_id'] ),
				'answer_value'   => sanitize_text_field( $data['answer_value'] ),
				'is_correct'     => null !== $data['is_correct'] ? ( $data['is_correct'] ? 1 : 0 ) : null,
				'points_awarded' => floatval( $data['points_awarded'] ),
			),
			array( '%d', '%d', '%s', '%d', '%f' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Batch save answers for a session.
	 *
	 * @param array $answers Array of answer data arrays.
	 * @return array Array of answer IDs.
	 */
	public function batch_save( $answers ) {
		$ids = array();

		foreach ( $answers as $answer ) {
			$id = $this->save( $answer );
			if ( $id ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Delete an answer.
	 *
	 * @param int $id Answer ID.
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
	 * Delete all answers for a session.
	 *
	 * @param int $session_id Session ID.
	 * @return bool
	 */
	public function delete_by_session( $session_id ) {
		global $wpdb;

		return (bool) $wpdb->delete(
			$this->table,
			array( 'session_id' => absint( $session_id ) ),
			array( '%d' )
		);
	}

	/**
	 * Calculate the total score for a session.
	 *
	 * @param int $session_id Session ID.
	 * @return float
	 */
	public function calculate_score( $session_id ) {
		global $wpdb;

		$score = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(points_awarded) FROM {$this->table} WHERE session_id = %d",
				$session_id
			)
		);

		return null !== $score ? floatval( $score ) : 0.0;
	}

	/**
	 * Count answers for a session.
	 *
	 * @param int $session_id Session ID.
	 * @return int
	 */
	public function count( $session_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE session_id = %d",
				$session_id
			)
		);
	}
}
