<?php
/**
 * Retention cleanup handler.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Cleans up old quiz sessions based on per quiz retention settings.
 */
class PremiaQuiz_Retention_Cleanup {

	/**
	 * Quiz repository.
	 *
	 * @var PremiaQuiz_Quiz_Repository
	 */
	private $quiz_repo;

	/**
	 * Session repository.
	 *
	 * @var PremiaQuiz_Session_Repository
	 */
	private $session_repo;

	/**
	 * Answer repository.
	 *
	 * @var PremiaQuiz_Answer_Repository
	 */
	private $answer_repo;

	/**
	 * Lead Submission repository.
	 *
	 * @var PremiaQuiz_Lead_Submission_Repository
	 */
	private $lead_submission_repo;

	/**
	 * Constructor.
	 *
	 * @param PremiaQuiz_Quiz_Repository            $quiz_repo            Quiz repository.
	 * @param PremiaQuiz_Session_Repository         $session_repo         Session repository.
	 * @param PremiaQuiz_Answer_Repository          $answer_repo          Answer repository.
	 * @param PremiaQuiz_Lead_Submission_Repository $lead_submission_repo Lead submission repository.
	 */
	public function __construct(
		PremiaQuiz_Quiz_Repository $quiz_repo,
		PremiaQuiz_Session_Repository $session_repo,
		PremiaQuiz_Answer_Repository $answer_repo,
		PremiaQuiz_Lead_Submission_Repository $lead_submission_repo
	) {
		$this->quiz_repo            = $quiz_repo;
		$this->session_repo         = $session_repo;
		$this->answer_repo          = $answer_repo;
		$this->lead_submission_repo = $lead_submission_repo;
	}

	/**
	 * Run cleanup for all quizzes with retention configured.
	 *
	 * @return int Total sessions deleted.
	 */
	public function run_cleanup() {
		$quizzes       = $this->quiz_repo->get_all( 1000, 0 );
		$total_cleaned = 0;

		foreach ( $quizzes as $quiz ) {
			$settings  = is_string( $quiz->settings ) ? json_decode( $quiz->settings, true ) : $quiz->settings;
			$retention = isset( $quiz->retention_days ) ? (int) $quiz->retention_days : 0;
			$retention = $retention > 0 ? $retention : ( isset( $settings['retention_days'] ) ? (int) $settings['retention_days'] : 0 );

			if ( $retention <= 0 ) {
				continue;
			}

			$total_cleaned += $this->cleanup_quiz( $quiz->id, $retention );
		}

		return $total_cleaned;
	}

	/**
	 * Clean up old sessions for a specific quiz.
	 *
	 * @param int $quiz_id        Quiz ID.
	 * @param int $retention_days Number of days to keep.
	 * @return int Sessions deleted.
	 */
	public function cleanup_quiz( $quiz_id, $retention_days ) {
		$old_sessions = $this->get_old_sessions( $quiz_id, $retention_days );
		$count        = 0;

		foreach ( $old_sessions as $session ) {
			$this->answer_repo->delete_by_session( $session->id );
			$this->lead_submission_repo->delete_by_session( $session->id );
			$this->session_repo->delete( $session->id );
			++$count;
		}

		return $count;
	}

	/**
	 * Get sessions older than the retention period.
	 *
	 * @param int $quiz_id        Quiz ID.
	 * @param int $retention_days Number of days to keep.
	 * @return array Old sessions.
	 */
	private function get_old_sessions( $quiz_id, $retention_days ) {
		global $wpdb;

		$table = $wpdb->prefix . 'premiaquiz_sessions';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE quiz_id = %d AND started_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$quiz_id,
				$retention_days
			)
		);
	}
}
