<?php
/**
 * Lead Field Definition repository.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Handles lead field definition database operations.
 */
class PremiaQuiz_Lead_Field_Repository {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Valid field types.
	 *
	 * @var array
	 */
	const VALID_FIELD_TYPES = array( 'text', 'email', 'phone', 'number', 'textarea' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'premiaquiz_lead_field_defs';
	}

	/**
	 * Get a lead field definition by ID.
	 *
	 * @param int $id Lead field ID.
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
	 * Get all lead field definitions for a quiz, ordered by position.
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
	 * Create a new lead field definition.
	 *
	 * @param array $data {
	 *     Lead field data.
	 *
	 *     @type int    $quiz_id     Quiz ID.
	 *     @type string $field_name  Machine name.
	 *     @type string $field_label Human readable label.
	 *     @type string $field_type  Field type.
	 *     @type bool   $is_required Whether required.
	 *     @type int    $position    Sort position.
	 *     @type array  $settings    Field settings.
	 * }
	 * @return int|false The lead field ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'quiz_id'     => 0,
			'field_name'  => '',
			'field_label' => '',
			'field_type'  => 'text',
			'is_required' => false,
			'position'    => 0,
			'settings'    => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->table,
			array(
				'quiz_id'     => absint( $data['quiz_id'] ),
				'field_name'  => sanitize_key( $data['field_name'] ),
				'field_label' => sanitize_text_field( $data['field_label'] ),
				'field_type'  => sanitize_text_field( $data['field_type'] ),
				'is_required' => $data['is_required'] ? 1 : 0,
				'position'    => absint( $data['position'] ),
				'settings'    => wp_json_encode( $data['settings'] ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a lead field definition.
	 *
	 * @param int   $id   Lead field ID.
	 * @param array $data Fields to update.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$allowed = array(
			'field_name',
			'field_label',
			'field_type',
			'is_required',
			'position',
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
			} elseif ( 'is_required' === $field ) {
				$prepared[ $field ] = $data[ $field ] ? 1 : 0;
				$formats[]          = '%d';
			} elseif ( 'field_name' === $field ) {
				$prepared[ $field ] = sanitize_key( $data[ $field ] );
				$formats[]          = '%s';
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
	 * Delete a lead field definition.
	 *
	 * @param int $id Lead field ID.
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
	 * Delete all lead field definitions for a quiz.
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
