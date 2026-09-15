<?php
/**
 * Lead Submission repository.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Handles lead submission database operations.
 */
class PremiaQuiz_Lead_Submission_Repository {

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
		$this->table = $wpdb->prefix . 'premiaquiz_lead_submissions';
	}

	/**
	 * Get a lead submission by ID.
	 *
	 * @param int $id Lead submission ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);
	}

	/**
	 * Get all lead submissions for a session.
	 *
	 * @param int $session_id Session ID.
	 * @return array
	 */
	public function get_by_session( $session_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE session_id = %d ORDER BY created_at ASC",
				$session_id
			)
		);
	}

	/**
	 * Get all lead submissions for a quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @param int $limit   Number of results.
	 * @param int $offset  Offset.
	 * @return array
	 */
	public function get_by_quiz( $quiz_id, $limit = 20, $offset = 0 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE quiz_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$quiz_id,
				$limit,
				$offset
			)
		);
	}

	/**
	 * Create a new lead submission.
	 *
	 * @param array $data {
	 *     Lead submission data.
	 *
	 *     @type int    $quiz_id     Quiz ID.
	 *     @type int    $session_id  Session ID.
	 *     @type int    $field_id    Lead field definition ID.
	 *     @type string $field_value Submitted value.
	 * }
	 * @return int|false The lead submission ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'quiz_id'     => 0,
			'session_id'  => 0,
			'field_id'    => 0,
			'field_value' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->table,
			array(
				'quiz_id'     => absint( $data['quiz_id'] ),
				'session_id'  => absint( $data['session_id'] ),
				'field_id'    => absint( $data['field_id'] ),
				'field_value' => sanitize_text_field( $data['field_value'] ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Batch create lead submissions for a session.
	 *
	 * @param array $submissions Array of submission data arrays.
	 * @return array Array of lead submission IDs.
	 */
	public function batch_create( $submissions ) {
		$ids = array();

		foreach ( $submissions as $submission ) {
			$id = $this->create( $submission );
			if ( $id ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Delete a lead submission.
	 *
	 * @param int $id Lead submission ID.
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
	 * Delete all lead submissions for a session.
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
	 * Delete all lead submissions for a quiz.
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
	 * Count lead submissions for a quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return int
	 */
	public function count( $quiz_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM {$this->table} WHERE quiz_id = %d",
				$quiz_id
			)
		);
	}
}
