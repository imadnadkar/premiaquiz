<?php
/**
 * Plugin Name: Premia Quiz & Survey AI
 * Plugin URI: https://premiaquizai.com
 * Description: Create quizzes and surveys with AI-powered question generation and grading.
 * Version: 0.1.0
 * Author: Premia
 * Author URI: https://premiaquizai.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: premiaquiz
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package PremiaQuiz
 */

defined( 'ABSPATH' ) || exit;

define( 'PREMIAQUIZ_VERSION', '0.1.0' );
define( 'PREMIAQUIZ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PREMIAQUIZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PREMIAQUIZ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum PHP version check.
 */
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action( 'admin_notices', 'premiaquiz_php_version_notice' );
	deactivate_plugins( plugin_basename( __FILE__ ) );
	return;
}

/**
 * Admin notice for PHP version mismatch.
 *
 * @return void
 */
function premiaquiz_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Premia Quiz requires PHP 8.0 or higher.', 'premiaquiz' ); ?></p>
	</div>
	<?php
}

/**
 * Include required files.
 */
require_once PREMIAQUIZ_PLUGIN_DIR . 'includes/class-premiaquiz.php';

/**
 * Initialize the plugin.
 *
 * @return PremiaQuiz
 */
function premiaquiz() {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new PremiaQuiz();
	}

	return $instance;
}

/**
 * Bootstrap the plugin after all plugins are loaded.
 */
add_action( 'plugins_loaded', 'premiaquiz_init' );

/**
 * Initialize plugin after plugins are loaded.
 *
 * @return void
 */
function premiaquiz_init() {
	premiaquiz();
}

/**
 * Activation hook.
 */
register_activation_hook( __FILE__, 'premiaquiz_activate' );

/**
 * Run on plugin activation.
 *
 * @return void
 */
function premiaquiz_activate() {
	premiaquiz()->activate();
}

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, 'premiaquiz_deactivate' );

/**
 * Run on plugin deactivation.
 *
 * @return void
 */
function premiaquiz_deactivate() {
	premiaquiz()->deactivate();
}
