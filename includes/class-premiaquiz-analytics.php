<?php
/**
 * Analytics query helpers.
 *
 * @package PremiaQuiz
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

/**
 * Provides aggregate stats and per question breakdowns for quizzes.
 */
class PremiaQuiz_Analytics {

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
	 * Question repository.
	 *
	 * @var PremiaQuiz_Question_Repository
	 */
	private $question_repo;

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
	 * @param PremiaQuiz_Question_Repository        $question_repo        Question repository.
	 * @param PremiaQuiz_Lead_Submission_Repository $lead_submission_repo Lead submission repository.
	 */
	public function __construct(
		PremiaQuiz_Quiz_Repository $quiz_repo,
		PremiaQuiz_Session_Repository $session_repo,
		PremiaQuiz_Answer_Repository $answer_repo,
		PremiaQuiz_Question_Repository $question_repo,
		PremiaQuiz_Lead_Submission_Repository $lead_submission_repo
	) {
		$this->quiz_repo            = $quiz_repo;
		$this->session_repo         = $session_repo;
		$this->answer_repo          = $answer_repo;
		$this->question_repo        = $question_repo;
		$this->lead_submission_repo = $lead_submission_repo;
	}

	/**
	 * Get aggregate stats for a quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return array {
	 *     Aggregate statistics.
	 *
	 *     @type int   $total_sessions  Total sessions started.
	 *     @type int   $completed       Sessions completed.
	 *     @type int   $abandoned       Sessions abandoned.
	 *     @type int   $in_progress     Sessions in progress.
	 *     @type float $avg_score       Average score.
	 *     @type float $avg_percentage  Average percentage.
	 *     @type float $completion_rate Completion rate (0 to 1).
	 *     @type int   $total_leads     Unique lead submissions.
	 * }
	 */
	public function get_quiz_stats( $quiz_id ) {
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'premiaquiz_sessions';

		$total_sessions = $this->session_repo->count( $quiz_id );
		$completed      = $this->session_repo->count( $quiz_id, 'completed' );
		$abandoned      = $this->session_repo->count( $quiz_id, 'abandoned' );
		$in_progress    = $this->session_repo->count( $quiz_id, 'in_progress' );

		$avg = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT AVG(score) as avg_score, AVG(percentage) as avg_percentage FROM {$sessions_table} WHERE quiz_id = %d AND status = %s",
				$quiz_id,
				'completed'
			)
		);

		$leads = $this->lead_submission_repo->count( $quiz_id );

		return array(
			'total_sessions'  => $total_sessions,
			'completed'       => $completed,
			'abandoned'       => $abandoned,
			'in_progress'     => $in_progress,
			'avg_score'       => null !== $avg->avg_score ? floatval( $avg->avg_score ) : 0.0,
			'avg_percentage'  => null !== $avg->avg_percentage ? floatval( $avg->avg_percentage ) : 0.0,
			'completion_rate' => $total_sessions > 0 ? $completed / $total_sessions : 0.0,
			'total_leads'     => $leads,
		);
	}

	/**
	 * Get per question breakdown for a quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return array Array of question stats.
	 */
	public function get_question_breakdown( $quiz_id ) {
		global $wpdb;

		$answers_table  = $wpdb->prefix . 'premiaquiz_answers';
		$sessions_table = $wpdb->prefix . 'premiaquiz_sessions';

		$questions = $this->question_repo->get_by_quiz( $quiz_id );
		$breakdown = array();

		foreach ( $questions as $question ) {
			$stats = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(a.id) as total_answers, SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_count, AVG(a.points_awarded) as avg_points FROM {$answers_table} a INNER JOIN {$sessions_table} s ON a.session_id = s.id WHERE a.question_id = %d AND s.quiz_id = %d AND s.status = %s",
					$question->id,
					$quiz_id,
					'completed'
				)
			);

			$total_answers = null !== $stats->total_answers ? (int) $stats->total_answers : 0;
			$correct_count = null !== $stats->correct_count ? (int) $stats->correct_count : 0;

			$breakdown[] = array(
				'question_id'   => $question->id,
				'question_text' => $question->text,
				'question_type' => $question->question_type,
				'total_answers' => $total_answers,
				'correct_count' => $correct_count,
				'accuracy'      => $total_answers > 0 ? $correct_count / $total_answers : 0.0,
				'avg_points'    => null !== $stats->avg_points ? floatval( $stats->avg_points ) : 0.0,
			);
		}

		return $breakdown;
	}

	/**
	 * Get score distribution for a quiz.
	 *
	 * @param int $quiz_id      Quiz ID.
	 * @param int $bucket_count Number of buckets (default 10).
	 * @return array Array of score buckets.
	 */
	public function get_score_distribution( $quiz_id, $bucket_count = 10 ) {
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'premiaquiz_sessions';

		$percentages = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT percentage FROM {$sessions_table} WHERE quiz_id = %d AND status = %s ORDER BY percentage ASC",
				$quiz_id,
				'completed'
			)
		);

		if ( empty( $percentages ) ) {
			return array();
		}

		$bucket_size  = 100 / $bucket_count;
		$distribution = array();

		for ( $i = 0; $i < $bucket_count; $i++ ) {
			$min   = $i * $bucket_size;
			$max   = ( $i + 1 ) * $bucket_size;
			$count = 0;

			foreach ( $percentages as $p ) {
				if ( $p >= $min && ( ( $bucket_count - 1 ) === $i ? $p <= $max : $p < $max ) ) {
					++$count;
				}
			}

			$distribution[] = array(
				'min'   => round( $min, 1 ),
				'max'   => round( $max, 1 ),
				'count' => $count,
			);
		}

		return $distribution;
	}

	/**
	 * Get recent activity for a quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @param int $limit   Number of recent sessions.
	 * @return array Recent sessions.
	 */
	public function get_recent_activity( $quiz_id, $limit = 10 ) {
		return $this->session_repo->get_by_quiz( $quiz_id, '', $limit, 0 );
	}
}
