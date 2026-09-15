<?php
/**
 * Database migration for all plugin tables.
 *
 * @package PremiaQuiz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades the 8 custom tables via dbDelta().
 */
class PremiaQuiz_Migration {

	/**
	 * Current schema version.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option key for stored DB version.
	 *
	 * @var string
	 */
	const DB_VERSION_OPTION = 'premiaquiz_db_version';

	/**
	 * Run the migration (create or upgrade tables).
	 *
	 * @return void
	 */
	public function run() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$this->create_tables( $charset_collate );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Check if an upgrade is needed.
	 *
	 * @return bool
	 */
	public function needs_upgrade() {
		$stored = get_option( self::DB_VERSION_OPTION, '0.0.0' );
		return version_compare( $stored, self::DB_VERSION, '<' );
	}

	/**
	 * Get the list of table names.
	 *
	 * @return array<string, string>
	 */
	public function get_table_names() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'premiaquiz_';

		return array(
			'quizzes'          => $prefix . 'quizzes',
			'questions'        => $prefix . 'questions',
			'question_options' => $prefix . 'question_options',
			'sessions'         => $prefix . 'sessions',
			'answers'          => $prefix . 'answers',
			'result_pages'     => $prefix . 'result_pages',
			'lead_field_defs'  => $prefix . 'lead_field_defs',
			'lead_submissions' => $prefix . 'lead_submissions',
		);
	}

	/**
	 * Create all 8 tables.
	 *
	 * @param string $charset_collate The charset collation string.
	 * @return void
	 */
	private function create_tables( $charset_collate ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'premiaquiz_';

		$quizzes          = $prefix . 'quizzes';
		$questions        = $prefix . 'questions';
		$question_options = $prefix . 'question_options';
		$sessions         = $prefix . 'sessions';
		$answers          = $prefix . 'answers';
		$result_pages     = $prefix . 'result_pages';
		$lead_field_defs  = $prefix . 'lead_field_defs';
		$lead_submissions = $prefix . 'lead_submissions';

		$sql = "CREATE TABLE {$quizzes} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description text,
			status varchar(20) NOT NULL DEFAULT 'draft',
			quiz_type varchar(20) NOT NULL DEFAULT 'assessment',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			retention_days int(11) unsigned DEFAULT NULL,
			settings longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY quiz_type (quiz_type),
			KEY created_by (created_by)
		) {$charset_collate};

		CREATE TABLE {$questions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) unsigned NOT NULL,
			question_type varchar(50) NOT NULL DEFAULT 'multiple_choice',
			text text NOT NULL,
			position int(11) NOT NULL DEFAULT 0,
			is_required tinyint(1) NOT NULL DEFAULT 1,
			settings longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY quiz_id (quiz_id),
			KEY position (position),
			KEY question_type (question_type)
		) {$charset_collate};

		CREATE TABLE {$question_options} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			question_id bigint(20) unsigned NOT NULL,
			text text NOT NULL,
			position int(11) NOT NULL DEFAULT 0,
			is_correct tinyint(1) NOT NULL DEFAULT 0,
			weight decimal(5,2) NOT NULL DEFAULT 0.00,
			image_url varchar(500) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY question_id (question_id),
			KEY position (position)
		) {$charset_collate};

		CREATE TABLE {$sessions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			session_key varchar(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'in_progress',
			result_page_id bigint(20) unsigned DEFAULT NULL,
			score decimal(10,2) NOT NULL DEFAULT 0.00,
			percentage decimal(5,2) NOT NULL DEFAULT 0.00,
			started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at datetime DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(500) DEFAULT NULL,
			quiz_snapshot longtext,
			PRIMARY KEY  (id),
			UNIQUE KEY session_quiz (session_key, quiz_id),
			KEY quiz_id (quiz_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY started_at (started_at)
		) {$charset_collate};

		CREATE TABLE {$answers} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id bigint(20) unsigned NOT NULL,
			question_id bigint(20) unsigned NOT NULL,
			answer_value text,
			is_correct tinyint(1) DEFAULT NULL,
			points_awarded decimal(10,2) NOT NULL DEFAULT 0.00,
			answered_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY session_question (session_id, question_id),
			KEY session_id (session_id),
			KEY question_id (question_id)
		) {$charset_collate};

		CREATE TABLE {$result_pages} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL,
			content text,
			image_url varchar(500) DEFAULT NULL,
			min_score decimal(10,2) DEFAULT NULL,
			max_score decimal(10,2) DEFAULT NULL,
			min_percentage decimal(5,2) DEFAULT NULL,
			max_percentage decimal(5,2) DEFAULT NULL,
			outcome_pattern longtext,
			position int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY quiz_id (quiz_id),
			KEY position (position)
		) {$charset_collate};

		CREATE TABLE {$lead_field_defs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) unsigned NOT NULL,
			field_name varchar(100) NOT NULL,
			field_label varchar(255) NOT NULL,
			field_type varchar(20) NOT NULL DEFAULT 'text',
			is_required tinyint(1) NOT NULL DEFAULT 0,
			position int(11) NOT NULL DEFAULT 0,
			settings longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY quiz_field_name (quiz_id, field_name),
			KEY quiz_id (quiz_id),
			KEY position (position)
		) {$charset_collate};

		CREATE TABLE {$lead_submissions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) unsigned NOT NULL,
			session_id bigint(20) unsigned NOT NULL,
			field_id bigint(20) unsigned NOT NULL,
			field_value text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY quiz_id (quiz_id),
			KEY session_id (session_id),
			KEY field_id (field_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
