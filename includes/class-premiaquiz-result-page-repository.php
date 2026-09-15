<?php
/**
 * Result Page repository.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Handles result page database operations and range matching.
 */
class PremiaQuiz_Result_Page_Repository {

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
		$this->table = $wpdb->prefix . 'premiaquiz_result_pages';
	}

	/**
	 * Get a result page by ID.
	 *
	 * @param int $id Result page ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);

		if ( $row && is_string( $row->outcome_pattern ) ) {
			$row->outcome_pattern = json_decode( $row->outcome_pattern, true );
		}

		return $row;
	}

	/**
	 * Get all result pages for a quiz, ordered by position.
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
			if ( is_string( $row->outcome_pattern ) ) {
				$row->outcome_pattern = json_decode( $row->outcome_pattern, true );
			}
		}

		return $rows;
	}

	/**
	 * Match a score to a result page for an assessment quiz.
	 *
	 * @param int   $quiz_id    Quiz ID.
	 * @param float $score      Score value.
	 * @param float $percentage Percentage value.
	 * @return object|null Matched result page or null.
	 */
	public function match_by_score( $quiz_id, $score, $percentage ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE quiz_id = %d AND (min_score IS NULL OR min_score <= %f) AND (max_score IS NULL OR max_score >= %f) AND (min_percentage IS NULL OR min_percentage <= %f) AND (max_percentage IS NULL OR max_percentage >= %f) ORDER BY position ASC LIMIT 1",
				$quiz_id,
				$score,
				$score,
				$percentage,
				$percentage
			)
		);

		if ( $row && is_string( $row->outcome_pattern ) ) {
			$row->outcome_pattern = json_decode( $row->outcome_pattern, true );
		}

		return $row;
	}

	/**
	 * Match an outcome pattern to a result page for a personality quiz.
	 *
	 * @param int   $quiz_id Quiz ID.
	 * @param array $pattern The answer pattern array.
	 * @return object|null Matched result page or null.
	 */
	public function match_by_pattern( $quiz_id, $pattern ) {
		$rows = $this->get_by_quiz( $quiz_id );

		foreach ( $rows as $row ) {
			if ( ! is_array( $row->outcome_pattern ) ) {
				continue;
			}

			if ( $this->pattern_matches( $row->outcome_pattern, $pattern ) ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * Check if a result page pattern matches the given answer pattern.
	 *
	 * @param array $defined_pattern Pattern from the result page.
	 * @param array $answer_pattern  Pattern from the user's answers.
	 * @return bool
	 */
	private function pattern_matches( $defined_pattern, $answer_pattern ) {
		foreach ( $defined_pattern as $key => $value ) {
			if ( ! array_key_exists( $key, $answer_pattern ) ) {
				return false;
			}

			if ( is_array( $value ) ) {
				if ( ! is_array( $answer_pattern[ $key ] ) || $value !== $answer_pattern[ $key ] ) {
					return false;
				}
			} elseif ( $answer_pattern[ $key ] !== $value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Create a new result page.
	 *
	 * @param array $data {
	 *     Result page data.
	 *
	 *     @type int    $quiz_id         Quiz ID.
	 *     @type string $title           Result page title.
	 *     @type string $content         Result page content.
	 *     @type string $image_url       Optional image URL.
	 *     @type float  $min_score       Minimum score (assessment).
	 *     @type float  $max_score       Maximum score (assessment).
	 *     @type float  $min_percentage  Minimum percentage (assessment).
	 *     @type float  $max_percentage  Maximum percentage (assessment).
	 *     @type array  $outcome_pattern Outcome pattern (personality).
	 *     @type int    $position        Sort position.
	 * }
	 * @return int|false The result page ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'quiz_id'         => 0,
			'title'           => '',
			'content'         => '',
			'image_url'       => null,
			'min_score'       => null,
			'max_score'       => null,
			'min_percentage'  => null,
			'max_percentage'  => null,
			'outcome_pattern' => null,
			'position'        => 0,
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->table,
			array(
				'quiz_id'         => absint( $data['quiz_id'] ),
				'title'           => sanitize_text_field( $data['title'] ),
				'content'         => wp_kses_post( $data['content'] ),
				'image_url'       => $data['image_url'] ? esc_url_raw( $data['image_url'] ) : null,
				'min_score'       => null !== $data['min_score'] ? floatval( $data['min_score'] ) : null,
				'max_score'       => null !== $data['max_score'] ? floatval( $data['max_score'] ) : null,
				'min_percentage'  => null !== $data['min_percentage'] ? floatval( $data['min_percentage'] ) : null,
				'max_percentage'  => null !== $data['max_percentage'] ? floatval( $data['max_percentage'] ) : null,
				'outcome_pattern' => null !== $data['outcome_pattern'] ? wp_json_encode( $data['outcome_pattern'] ) : null,
				'position'        => absint( $data['position'] ),
			),
			array( '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a result page.
	 *
	 * @param int   $id   Result page ID.
	 * @param array $data Fields to update.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$allowed = array(
			'title',
			'content',
			'image_url',
			'min_score',
			'max_score',
			'min_percentage',
			'max_percentage',
			'outcome_pattern',
			'position',
		);

		$prepared = array();
		$formats  = array();

		foreach ( $allowed as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}

			if ( 'outcome_pattern' === $field ) {
				$prepared[ $field ] = null !== $data[ $field ] ? wp_json_encode( $data[ $field ] ) : null;
				$formats[]          = '%s';
			} elseif ( 'title' === $field ) {
				$prepared[ $field ] = sanitize_text_field( $data[ $field ] );
				$formats[]          = '%s';
			} elseif ( 'content' === $field ) {
				$prepared[ $field ] = wp_kses_post( $data[ $field ] );
				$formats[]          = '%s';
			} elseif ( 'image_url' === $field ) {
				$prepared[ $field ] = $data[ $field ] ? esc_url_raw( $data[ $field ] ) : null;
				$formats[]          = '%s';
			} elseif ( in_array( $field, array( 'min_score', 'max_score', 'min_percentage', 'max_percentage' ), true ) ) {
				$prepared[ $field ] = null !== $data[ $field ] ? floatval( $data[ $field ] ) : null;
				$formats[]          = '%f';
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
	 * Delete a result page.
	 *
	 * @param int $id Result page ID.
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
	 * Delete all result pages for a quiz.
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
}
