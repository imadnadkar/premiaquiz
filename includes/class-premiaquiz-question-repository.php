<?php
/**
 * Question repository for CRUD and ordering.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Handles question database operations.
 */
class PremiaQuiz_Question_Repository {

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
		$this->table = $wpdb->prefix . 'premiaquiz_questions';
	}

	/**
	 * Get a question by ID.
	 *
	 * @param int $id Question ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);

		if ( $row && is_string( $row->settings ) ) {
			$row->settings = json_decode( $row->settings, true );
		}

		return $row;
	}

	/**
	 * Get all questions for a quiz, ordered by position.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return array
	 */
	public function get_by_quiz( $quiz_id ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE quiz_id = %d ORDER BY position ASC",
				$quiz_id
			)
		);

		foreach ( $rows as &$row ) {
			if ( is_string( $row->settings ) ) {
				$row->settings = json_decode( $row->settings, true );
			}
		}

		return $rows;
	}

	/**
	 * Get the next position for a quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return int
	 */
	public function get_next_position( $quiz_id ) {
		global $wpdb;

		$max = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(position) FROM {$this->table} WHERE quiz_id = %d",
				$quiz_id
			)
		);

		return null !== $max ? (int) $max + 1 : 0;
	}

	/**
	 * Create a new question.
	 *
	 * @param array $data {
	 *     Question data.
	 *
	 *     @type int    $quiz_id       Quiz ID.
	 *     @type string $question_type Question type enum.
	 *     @type string $text          Question text.
	 *     @type int    $position      Sort position.
	 *     @type bool   $is_required   Whether required.
	 *     @type array  $settings      Settings (stored as JSON).
	 * }
	 * @return int|false The question ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'quiz_id'       => 0,
			'question_type' => 'multiple_choice',
			'text'          => '',
			'position'      => 0,
			'is_required'   => true,
			'settings'      => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( 0 === $data['position'] && 0 !== $data['quiz_id'] ) {
			$data['position'] = $this->get_next_position( $data['quiz_id'] );
		}

		$result = $wpdb->insert(
			$this->table,
			array(
				'quiz_id'       => absint( $data['quiz_id'] ),
				'question_type' => sanitize_text_field( $data['question_type'] ),
				'text'          => wp_kses_post( $data['text'] ),
				'position'      => absint( $data['position'] ),
				'is_required'   => $data['is_required'] ? 1 : 0,
				'settings'      => wp_json_encode( $data['settings'] ),
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a question.
	 *
	 * @param int   $id   Question ID.
	 * @param array $data Fields to update.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$allowed = array(
			'question_type',
			'text',
			'position',
			'is_required',
			'settings',
		);

		$prepared = array();
		$formats  = array();

		foreach ( $allowed as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}

			if ( 'settings' === $field ) {
				$prepared[ $field ] = wp_json_encode( $data[ $field ] );
				$formats[]          = '%s';
			} elseif ( 'text' === $field ) {
				$prepared[ $field ] = wp_kses_post( $data[ $field ] );
				$formats[]          = '%s';
			} elseif ( 'is_required' === $field ) {
				$prepared[ $field ] = $data[ $field ] ? 1 : 0;
				$formats[]          = '%d';
			} else {
				$prepared[ $field ] = sanitize_text_field( $data[ $field ] );
				$formats[]          = '%s';
			}
		}

		if ( empty( $prepared ) ) {
			return false;
		}

		return (bool) $wpdb->update(
			$this->table,
			$prepared,
			array( 'id' => absint( $id ) ),
			$formats,
			array( '%d' )
		);
	}

	/**
	 * Delete a question.
	 *
	 * @param int $id Question ID.
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
	 * Delete all questions for a quiz.
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
	 * Count questions for a quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return int
	 */
	public function count( $quiz_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE quiz_id = %d",
				$quiz_id
			)
		);
	}

	/**
	 * Reorder questions for a quiz.
	 *
	 * @param int   $quiz_id     Quiz ID.
	 * @param array $ordered_ids Array of question IDs in desired order.
	 * @return bool
	 */
	public function reorder( $quiz_id, $ordered_ids ) {
		global $wpdb;

		$position = 0;

		foreach ( $ordered_ids as $question_id ) {
			$wpdb->update(
				$this->table,
				array( 'position' => $position ),
				array(
					'id'      => absint( $question_id ),
					'quiz_id' => absint( $quiz_id ),
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
			++$position;
		}

		return true;
	}
}
