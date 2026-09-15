/**
 * Premia Quiz Frontend
 *
 * Lightweight vanilla JS quiz interface for website visitors.
 *
 * @package PremiaQuiz
 */

( function () {
	'use strict';

	/**
	 * Quiz class for handling quiz display and submission.
	 */
	class PremiaQuiz {
		/**
		 * Initialize the quiz.
		 *
		 * @param {HTMLElement} container The quiz container element.
		 */
		constructor( container ) {
			this.container = container;
			this.quizId = container.dataset.quizId;
			this.currentQuestion = 0;
			this.answers = {};
			this.questions = [];

			this.init();
		}

		/**
		 * Initialize quiz functionality.
		 *
		 * @return {void}
		 */
		init() {
			this.loadQuiz();
			this.bindEvents();
		}

		/**
		 * Load quiz data from API.
		 *
		 * @return {Promise<void>}
		 */
		async loadQuiz() {
			try {
				const response = await fetch(
					`${ premiaquiz.apiUrl }/quizzes/${ this.quizId }`,
					{
						headers: {
							'X-WP-Nonce': premiaquiz.nonce,
						},
					}
				);

				if ( ! response.ok ) {
					throw new Error( 'Failed to load quiz' );
				}

				const data = await response.json();
				this.questions = data.questions || [];
				this.renderQuiz();
			} catch ( error ) {
				this.container.innerHTML = `
					<div class="premiaquiz-error">
						<p>Failed to load quiz. Please try again later.</p>
					</div>
				`;
			}
		}

		/**
		 * Render the quiz.
		 *
		 * @return {void}
		 */
		renderQuiz() {
			if ( this.questions.length === 0 ) {
				this.container.innerHTML = `
					<div class="premiaquiz-empty">
						<p>No questions available.</p>
					</div>
				`;
				return;
			}

			this.container.innerHTML = `
				<div class="premiaquiz-quiz">
					<div class="premiaquiz-progress">
						<span class="premiaquiz-current">1</span> / <span class="premiaquiz-total">${ this.questions.length }</span>
					</div>
					<div class="premiaquiz-question"></div>
					<div class="premiaquiz-answers"></div>
					<div class="premiaquiz-navigation">
						<button class="premiaquiz-prev" disabled>Previous</button>
						<button class="premiaquiz-next">Next</button>
						<button class="premiaquiz-submit" style="display:none;">Submit</button>
					</div>
				</div>
			`;

			this.renderQuestion();
		}

		/**
		 * Render current question.
		 *
		 * @return {void}
		 */
		renderQuestion() {
			const question = this.questions[ this.currentQuestion ];
			const questionEl = this.container.querySelector(
				'.premiaquiz-question'
			);
			const answersEl = this.container.querySelector(
				'.premiaquiz-answers'
			);
			const currentEl = this.container.querySelector(
				'.premiaquiz-current'
			);

			currentEl.textContent = this.currentQuestion + 1;
			questionEl.innerHTML = `<h3>${ question.title }</h3>`;

			// Render answers based on question type.
			switch ( question.type ) {
				case 'multiple_choice':
					this.renderMultipleChoice( answersEl, question );
					break;
				case 'true_false':
					this.renderTrueFalse( answersEl, question );
					break;
				default:
					this.renderTextAnswer( answersEl, question );
			}

			this.updateNavigation();
		}

		/**
		 * Render multiple choice answers.
		 *
		 * @param {HTMLElement} container The answers container.
		 * @param {Object}      question  The question object.
		 * @return {void}
		 */
		renderMultipleChoice( container, question ) {
			const answers = question.answers || [];
			container.innerHTML = answers
				.map(
					( answer ) => `
					<label class="premiaquiz-answer">
						<input type="radio" name="question_${ question.id }" value="${ answer.id }"
							${
								this.answers[ question.id ] === answer.id
									? 'checked'
									: ''
							} />
						<span>${ answer.content }</span>
					</label>
				`
				)
				.join( '' );
		}

		/**
		 * Render true/false answers.
		 *
		 * @param {HTMLElement} container The answers container.
		 * @param {Object}      question  The question object.
		 * @return {void}
		 */
		renderTrueFalse( container, question ) {
			container.innerHTML = `
				<label class="premiaquiz-answer">
					<input type="radio" name="question_${ question.id }" value="true"
						${ this.answers[ question.id ] === 'true' ? 'checked' : ''} />
					<span>True</span>
				</label>
				<label class="premiaquiz-answer">
					<input type="radio" name="question_${ question.id }" value="false"
						${ this.answers[ question.id ] === 'false' ? 'checked' : ''} />
					<span>False</span>
				</label>
			`;
		}

		/**
		 * Render text input answer.
		 *
		 * @param {HTMLElement} container The answers container.
		 * @param {Object}      question  The question object.
		 * @return {void}
		 */
		renderTextAnswer( container, question ) {
			container.innerHTML = `
				<textarea class="premiaquiz-text-answer"
					placeholder="Enter your answer..."
					rows="4">${ this.answers[ question.id ] || '' }</textarea>
			`;
		}

		/**
		 * Update navigation buttons.
		 *
		 * @return {void}
		 */
		updateNavigation() {
			const prevBtn = this.container.querySelector( '.premiaquiz-prev' );
			const nextBtn = this.container.querySelector( '.premiaquiz-next' );
			const submitBtn = this.container.querySelector(
				'.premiaquiz-submit'
			);

			prevBtn.disabled = this.currentQuestion === 0;

			const isLast =
				this.currentQuestion === this.questions.length - 1;
			nextBtn.style.display = isLast ? 'none' : '';
			submitBtn.style.display = isLast ? '' : 'none';
		}

		/**
		 * Bind event listeners.
		 *
		 * @return {void}
		 */
		bindEvents() {
			this.container.addEventListener( 'click', ( e ) => {
				if ( e.target.classList.contains( 'premiaquiz-prev' ) ) {
					this.prevQuestion();
				} else if (
					e.target.classList.contains( 'premiaquiz-next' )
				) {
					this.nextQuestion();
				} else if (
					e.target.classList.contains( 'premiaquiz-submit' )
				) {
					this.submitQuiz();
				}
			} );

			this.container.addEventListener( 'change', ( e ) => {
				if (
					e.target.name &&
					e.target.name.startsWith( 'question_' )
				) {
					const questionId = e.target.name.replace( 'question_', '' );
					this.answers[ questionId ] = e.target.value;
				}
			} );

			this.container.addEventListener( 'input', ( e ) => {
				if (
					e.target.classList.contains( 'premiaquiz-text-answer' )
				) {
					const question =
						this.questions[ this.currentQuestion ];
					this.answers[ question.id ] = e.target.value;
				}
			} );
		}

		/**
		 * Go to previous question.
		 *
		 * @return {void}
		 */
		prevQuestion() {
			if ( this.currentQuestion > 0 ) {
				this.currentQuestion--;
				this.renderQuestion();
			}
		}

		/**
		 * Go to next question.
		 *
		 * @return {void}
		 */
		nextQuestion() {
			if ( this.currentQuestion < this.questions.length - 1 ) {
				this.currentQuestion++;
				this.renderQuestion();
			}
		}

		/**
		 * Submit quiz answers.
		 *
		 * @return {Promise<void>}
		 */
		async submitQuiz() {
			const submitBtn = this.container.querySelector(
				'.premiaquiz-submit'
			);
			submitBtn.disabled = true;
			submitBtn.textContent = 'Submitting...';

			try {
				const response = await fetch(
					`${ premiaquiz.apiUrl }/quizzes/${ this.quizId }/submit`,
					{
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': premiaquiz.nonce,
						},
						body: JSON.stringify( { answers: this.answers } ),
					}
				);

				if ( ! response.ok ) {
					throw new Error( 'Failed to submit quiz' );
				}

				const result = await response.json();
				this.showResults( result );
			} catch ( error ) {
				submitBtn.disabled = false;
				submitBtn.textContent = 'Submit';
				alert( 'Failed to submit quiz. Please try again.' );
			}
		}

		/**
		 * Show quiz results.
		 *
		 * @param {Object} result The quiz result.
		 * @return {void}
		 */
		showResults( result ) {
			const percentage =
				result.max_score > 0
					? Math.round(
							( result.score / result.max_score ) * 100
					  )
					: 0;

			this.container.innerHTML = `
				<div class="premiaquiz-results">
					<h3>Quiz Complete!</h3>
					<div class="premiaquiz-score">
						<span class="premiaquiz-percentage">${ percentage }%</span>
						<span class="premiaquiz-score-text">${ result.score } / ${ result.max_score }</span>
					</div>
					<button class="premiaquiz-retry">Try Again</button>
				</div>
			`;

			const retryBtn = this.container.querySelector(
				'.premiaquiz-retry'
			);
			retryBtn.addEventListener( 'click', () => {
				this.currentQuestion = 0;
				this.answers = {};
				this.renderQuiz();
			} );
		}
	}

	// Initialize all quiz containers on the page.
	document.addEventListener( 'DOMContentLoaded', function () {
		const containers = document.querySelectorAll( '.premiaquiz-container' );
		containers.forEach( function ( container ) {
			new PremiaQuiz( container );
		} );
	} );
} )();
