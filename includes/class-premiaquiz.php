<?php
/**
 * Main plugin class.
 *
 * @package PremiaQuiz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin bootstrap and lifecycle management.
 */
class PremiaQuiz {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Database version.
	 *
	 * @var string
	 */
	private $db_version = '1.0.0';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->version = PREMIAQUIZ_VERSION;

		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Load required files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// Future: load admin, frontend, API, and database classes here.
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register the admin menu page.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'Premia Quiz', 'premiaquiz' ),
			__( 'Premia Quiz', 'premiaquiz' ),
			'manage_options',
			'premiaquiz',
			array( $this, 'render_admin_page' ),
			'dashicons-welcome-learn-more',
			30
		);

		add_submenu_page(
			'premiaquiz',
			__( 'Quizzes', 'premiaquiz' ),
			__( 'Quizzes', 'premiaquiz' ),
			'manage_options',
			'premiaquiz',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Premia Quiz', 'premiaquiz' ); ?></h1>
			<div id="premiaquiz-admin"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// Only load on our admin pages (toplevel_page_premiaquiz or premiaquiz_page_*).
		if ( strpos( $hook_suffix, 'premiaquiz' ) === false ) {
			return;
		}

		$asset_file = PREMIAQUIZ_PLUGIN_DIR . 'build/index.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = include $asset_file;
		} else {
			$asset = array(
				'dependencies' => array(),
				'version'      => $this->version,
			);
		}

		wp_enqueue_script(
			'premiaquiz-admin',
			PREMIAQUIZ_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_localize_script(
			'premiaquiz-admin',
			'premiaquiz',
			array(
				'apiUrl' => rest_url( 'premiaquiz/v1' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			'premiaquiz-admin',
			PREMIAQUIZ_PLUGIN_URL . 'build/index.css',
			array(),
			$this->version
		);

		wp_enqueue_style(
			'premiaquiz-admin-custom',
			PREMIAQUIZ_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Enqueue frontend quiz scripts.
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		wp_enqueue_script(
			'premiaquiz-frontend',
			PREMIAQUIZ_PLUGIN_URL . 'frontend/quiz.js',
			array(),
			$this->version,
			true
		);

		wp_localize_script(
			'premiaquiz-frontend',
			'premiaquiz',
			array(
				'apiUrl' => rest_url( 'premiaquiz/v1' ),
				'nonce'  => wp_create_nonce( 'premiaquiz_nonce' ),
			)
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Future: register REST API routes here.
	}

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		$this->create_tables();
		update_option( 'premiaquiz_db_version', $this->db_version );
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Nothing to clean up on deactivation.
	}

	/**
	 * Create custom database tables.
	 *
	 * @return void
	 */
	private function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$quizzes_table   = $wpdb->prefix . 'premiaquiz_quizzes';
		$questions_table = $wpdb->prefix . 'premiaquiz_questions';
		$answers_table   = $wpdb->prefix . 'premiaquiz_answers';
		$results_table   = $wpdb->prefix . 'premiaquiz_results';

		$sql = "CREATE TABLE {$quizzes_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description text,
			status varchar(20) NOT NULL DEFAULT 'draft',
			settings longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;

		CREATE TABLE {$questions_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL,
			type varchar(50) NOT NULL DEFAULT 'multiple_choice',
			content longtext NOT NULL,
			settings longtext,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY quiz_id (quiz_id),
			KEY sort_order (sort_order)
		) $charset_collate;

		CREATE TABLE {$answers_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			question_id bigint(20) unsigned NOT NULL,
			content longtext NOT NULL,
			is_correct tinyint(1) NOT NULL DEFAULT 0,
			settings longtext,
			sort_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY question_id (question_id),
			KEY sort_order (sort_order)
		) $charset_collate;

		CREATE TABLE {$results_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) unsigned NOT NULL,
			submission_id bigint(20) unsigned NOT NULL,
			score decimal(5,2) NOT NULL DEFAULT 0,
			max_score decimal(5,2) NOT NULL DEFAULT 0,
			data longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY quiz_id (quiz_id),
			KEY submission_id (submission_id)
		) $charset_collate;";

		dbDelta( $sql );
	}
}
