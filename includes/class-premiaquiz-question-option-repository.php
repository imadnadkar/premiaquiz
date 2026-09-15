<?php
/**
 * Question Option repository for CRUD operations.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Handles question option database operations.
 */
class PremiaQuiz_Question_Option_Repository {

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
		$this->table = $wpdb->prefix . 'premiaquiz_question_options';
	}

	/**
	 * Get an option by ID.
	 *
	 * @param int $id Option ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);
	}

	/**
	 * Get all options for a question, ordered by position.
	 *
	 * @param int $question_id Question ID.
	 * @return array
	 */
	public function get_by_question( $question_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE question_id = %d ORDER BY position ASC",
				$question_id
			)
		);
	}

	/**
	 * Get the next position for a question.
	 *
	 * @param int $question_id Question ID.
	 * @return int
	 */
	public function get_next_position( $question_id ) {
		global $wpdb;

		$max = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(position) FROM {$this->table} WHERE question_id = %d",
				$question_id
			)
		);

		return null !== $max ? (int) $max + 1 : 0;
	}

	/**
	 * Create a new option.
	 *
	 * @param array $data {
	 *     Option data.
	 *
	 *     @type int    $question_id Question ID.
	 *     @type string $text        Option text.
	 *     @type int    $position    Sort position.
	 *     @type bool   $is_correct  Whether correct.
	 *     @type float  $weight      Weight value.
	 *     @type string $image_url   Optional image URL.
	 * }
	 * @return int|false The option ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'question_id' => 0,
			'text'        => '',
			'position'    => 0,
			'is_correct'  => false,
			'weight'      => 0.0,
			'image_url'   => null,
		);

		$data = wp_parse_args( $data, $defaults );

		if ( 0 === $data['position'] && 0 !== $data['question_id'] ) {
			$data['position'] = $this->get_next_position( $data['question_id'] );
		}

		$result = $wpdb->insert(
			$this->table,
			array(
				'question_id' => absint( $data['question_id'] ),
				'text'        => wp_kses_post( $data['text'] ),
				'position'    => absint( $data['position'] ),
				'is_correct'  => $data['is_correct'] ? 1 : 0,
				'weight'      => floatval( $data['weight'] ),
				'image_url'   => $data['image_url'] ? esc_url_raw( $data['image_url'] ) : null,
			),
			array( '%d', '%s', '%d', '%d', '%f', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an option.
	 *
	 * @param int   $id   Option ID.
	 * @param array $data Fields to update.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$allowed = array(
			'text',
			'position',
			'is_correct',
			'weight',
			'image_url',
		);

		$prepared = array();
		$formats  = array();

		foreach ( $allowed as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}

			if ( 'text' === $field ) {
				$prepared[ $field ] = wp_kses_post( $data[ $field ] );
				$formats[]          = '%s';
			} elseif ( 'is_correct' === $field ) {
				$prepared[ $field ] = $data[ $field ] ? 1 : 0;
				$formats[]          = '%d';
			} elseif ( 'weight' === $field ) {
				$prepared[ $field ] = floatval( $data[ $field ] );
				$formats[]          = '%f';
			} elseif ( 'image_url' === $field ) {
				$prepared[ $field ] = $data[ $field ] ? esc_url_raw( $data[ $field ] ) : null;
				$formats[]          = '%s';
			} else {
				$prepared[ $field ] = absint( $data[ $field ] );
				$formats[]          = '%d';
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
	 * Delete an option.
	 *
	 * @param int $id Option ID.
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
	 * Delete all options for a question.
	 *
	 * @param int $question_id Question ID.
	 * @return bool
	 */
	public function delete_by_question( $question_id ) {
		global $wpdb;

		return (bool) $wpdb->delete(
			$this->table,
			array( 'question_id' => absint( $question_id ) ),
			array( '%d' )
		);
	}

	/**
	 * Reorder options for a question.
	 *
	 * @param int   $question_id Question ID.
	 * @param array $ordered_ids Array of option IDs in desired order.
	 * @return bool
	 */
	public function reorder( $question_id, $ordered_ids ) {
		global $wpdb;

		$position = 0;

		foreach ( $ordered_ids as $option_id ) {
			$wpdb->update(
				$this->table,
				array( 'position' => $position ),
				array(
					'id'          => absint( $option_id ),
					'question_id' => absint( $question_id ),
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
			++$position;
		}

		return true;
	}
}
