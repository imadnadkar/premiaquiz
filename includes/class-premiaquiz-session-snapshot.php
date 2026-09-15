<?php
/**
 * Session snapshot mechanism.
 *
 * @package PremiaQuiz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates and manages quiz structure snapshots for active sessions.
 */
class PremiaQuiz_Session_Snapshot {

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
	 * Constructor.
	 *
	 * @param PremiaQuiz_Question_Repository        $question_repo Question repository.
	 * @param PremiaQuiz_Question_Option_Repository $option_repo   Option repository.
	 */
	public function __construct(
		PremiaQuiz_Question_Repository $question_repo,
		PremiaQuiz_Question_Option_Repository $option_repo
	) {
		$this->question_repo = $question_repo;
		$this->option_repo   = $option_repo;
	}

	/**
	 * Create a snapshot of the quiz structure at the current moment.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return array The snapshot data.
	 */
	public function create_snapshot( $quiz_id ) {
		$questions = $this->question_repo->get_by_quiz( $quiz_id );
		$snapshot  = array();

		foreach ( $questions as $question ) {
			$options = $this->option_repo->get_by_question( $question->id );

			$snapshot[] = array(
				'id'            => $question->id,
				'question_type' => $question->question_type,
				'text'          => $question->text,
				'position'      => $question->position,
				'is_required'   => (bool) $question->is_required,
				'settings'      => $question->settings,
				'options'       => array_map(
					function ( $option ) {
						return array(
							'id'         => $option->id,
							'text'       => $option->text,
							'position'   => $option->position,
							'is_correct' => (bool) $option->is_correct,
							'weight'     => $option->weight,
							'image_url'  => $option->image_url,
						);
					},
					$options
				),
			);
		}

		return $snapshot;
	}

	/**
	 * Get questions from a snapshot.
	 *
	 * @param array $snapshot The snapshot data.
	 * @return array Questions in the same format as the repository returns.
	 */
	public function get_questions_from_snapshot( $snapshot ) {
		return array_map(
			function ( $item ) {
				return (object) array(
					'id'            => $item['id'],
					'question_type' => $item['question_type'],
					'text'          => $item['text'],
					'position'      => $item['position'],
					'is_required'   => $item['is_required'],
					'settings'      => $item['settings'],
				);
			},
			$snapshot
		);
	}

	/**
	 * Get options for a question from a snapshot.
	 *
	 * @param array $snapshot    The snapshot data.
	 * @param int   $question_id Question ID to find.
	 * @return array Options for the question.
	 */
	public function get_options_from_snapshot( $snapshot, $question_id ) {
		foreach ( $snapshot as $item ) {
			if ( $item['id'] === $question_id ) {
				return array_map(
					function ( $opt ) {
						return (object) $opt;
					},
					$item['options']
				);
			}
		}

		return array();
	}
}
