<?php
/**
 * Cascade delete handler for quiz deletion.
 *
 * @package PremiaQuiz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Deletes all related entities when a quiz is removed.
 */
class PremiaQuiz_Cascade_Delete {

	/**
	 * Question repository.
	 *
	 * @var PremiaQuiz_Question_Repository
	 */
	private $question_repo;

	/**
	 * Question Option repository.
	 *
	 * @var PremiaQuiz_Question_Option_Repository
	 */
	private $option_repo;

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
	 * Result Page repository.
	 *
	 * @var PremiaQuiz_Result_Page_Repository
	 */
	private $result_page_repo;

	/**
	 * Lead Field repository.
	 *
	 * @var PremiaQuiz_Lead_Field_Repository
	 */
	private $lead_field_repo;

	/**
	 * Lead Submission repository.
	 *
	 * @var PremiaQuiz_Lead_Submission_Repository
	 */
	private $lead_submission_repo;

	/**
	 * Constructor.
	 *
	 * @param PremiaQuiz_Question_Repository        $question_repo       Question repository.
	 * @param PremiaQuiz_Question_Option_Repository $option_repo         Option repository.
	 * @param PremiaQuiz_Session_Repository         $session_repo        Session repository.
	 * @param PremiaQuiz_Answer_Repository          $answer_repo         Answer repository.
	 * @param PremiaQuiz_Result_Page_Repository     $result_page_repo    Result page repository.
	 * @param PremiaQuiz_Lead_Field_Repository      $lead_field_repo     Lead field repository.
	 * @param PremiaQuiz_Lead_Submission_Repository $lead_submission_repo Lead submission repository.
	 */
	public function __construct(
		PremiaQuiz_Question_Repository $question_repo,
		PremiaQuiz_Question_Option_Repository $option_repo,
		PremiaQuiz_Session_Repository $session_repo,
		PremiaQuiz_Answer_Repository $answer_repo,
		PremiaQuiz_Result_Page_Repository $result_page_repo,
		PremiaQuiz_Lead_Field_Repository $lead_field_repo,
		PremiaQuiz_Lead_Submission_Repository $lead_submission_repo
	) {
		$this->question_repo        = $question_repo;
		$this->option_repo          = $option_repo;
		$this->session_repo         = $session_repo;
		$this->answer_repo          = $answer_repo;
		$this->result_page_repo     = $result_page_repo;
		$this->lead_field_repo      = $lead_field_repo;
		$this->lead_submission_repo = $lead_submission_repo;
	}

	/**
	 * Delete a quiz and all related entities.
	 *
	 * @param int $quiz_id Quiz ID to delete.
	 * @return bool True on success.
	 */
	public function delete_quiz( $quiz_id ) {
		$quiz_id = absint( $quiz_id );

		if ( $quiz_id <= 0 ) {
			return false;
		}

		$this->lead_submission_repo->delete_by_quiz( $quiz_id );
		$this->lead_field_repo->delete_by_quiz( $quiz_id );

		$sessions = $this->session_repo->get_by_quiz( $quiz_id, '', 10000, 0 );
		foreach ( $sessions as $session ) {
			$this->answer_repo->delete_by_session( $session->id );
		}

		$this->session_repo->delete_by_quiz( $quiz_id );

		$questions = $this->question_repo->get_by_quiz( $quiz_id );
		foreach ( $questions as $question ) {
			$this->option_repo->delete_by_question( $question->id );
		}

		$this->question_repo->delete_by_quiz( $quiz_id );
		$this->result_page_repo->delete_by_quiz( $quiz_id );

		$quiz_repo = new PremiaQuiz_Quiz_Repository();
		return $quiz_repo->delete( $quiz_id );
	}
}
