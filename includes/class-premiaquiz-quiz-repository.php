<?php
/**
 * Quiz repository for CRUD operations.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Handles quiz database operations.
 */
class PremiaQuiz_Quiz_Repository {

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
		$this->table = $wpdb->prefix . 'premiaquiz_quizzes';
	}

	/**
	 * Get a quiz by ID.
	 *
	 * @param int $id Quiz ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);
	}

	/**
	 * Get quizzes by status.
	 *
	 * @param string $status Quiz status.
	 * @param int    $limit  Number of results.
	 * @param int    $offset Offset.
	 * @return array
	 */
	public function get_by_status( $status = 'published', $limit = 20, $offset = 0 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$status,
				$limit,
				$offset
			)
		);
	}

	/**
	 * Get all quizzes.
	 *
	 * @param int $limit  Number of results.
	 * @param int $offset Offset.
	 * @return array
	 */
	public function get_all( $limit = 20, $offset = 0 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);
	}

	/**
	 * Create a new quiz.
	 *
	 * @param array $data {
	 *     Quiz data.
	 *
	 *     @type string $title          Quiz title.
	 *     @type string $description    Quiz description.
	 *     @type string $status         Quiz status (draft/published/archived).
	 *     @type string $quiz_type      Quiz type (assessment/personality).
	 *     @type int    $created_by     User ID of creator.
	 *     @type int    $retention_days Data retention days.
	 *     @type array  $settings       Settings array (stored as JSON).
	 * }
	 * @return int|false The quiz ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'title'          => '',
			'description'    => '',
			'status'         => 'draft',
			'quiz_type'      => 'assessment',
			'created_by'     => 0,
			'retention_days' => null,
			'settings'       => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->table,
			array(
				'title'          => sanitize_text_field( $data['title'] ),
				'description'    => wp_kses_post( $data['description'] ),
				'status'         => sanitize_text_field( $data['status'] ),
				'quiz_type'      => sanitize_text_field( $data['quiz_type'] ),
				'created_by'     => absint( $data['created_by'] ),
				'retention_days' => null !== $data['retention_days'] ? absint( $data['retention_days'] ) : null,
				'settings'       => wp_json_encode( $data['settings'] ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a quiz.
	 *
	 * @param int   $id   Quiz ID.
	 * @param array $data Fields to update.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$allowed = array(
			'title',
			'description',
			'status',
			'quiz_type',
			'retention_days',
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
			} elseif ( 'title' === $field || 'description' === $field ) {
				$prepared[ $field ] = 'title' === $field ? sanitize_text_field( $data[ $field ] ) : wp_kses_post( $data[ $field ] );
				$formats[]          = '%s';
			} elseif ( 'retention_days' === $field ) {
				$prepared[ $field ] = null !== $data[ $field ] ? absint( $data[ $field ] ) : null;
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
	 * Delete a quiz.
	 *
	 * @param int $id Quiz ID.
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
	 * Count quizzes by status.
	 *
	 * @param string $status Quiz status.
	 * @return int
	 */
	public function count( $status = '' ) {
		global $wpdb;

		if ( $status ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE status = %s",
					$status
				)
			);
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}
}
