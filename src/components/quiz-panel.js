import { useState, useEffect } from '@wordpress/element';
import { Spinner, TextControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/* global premiaquiz */

const QuizPanel = () => {
	const [ quizzes, setQuizzes ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ selectedQuiz, setSelectedQuiz ] = useState( null );

	useEffect( () => {
		fetch( `${ premiaquiz.apiUrl }/quizzes`, {
			headers: {
				'X-WP-Nonce': premiaquiz.nonce,
			},
		} )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				setQuizzes( data );
				setLoading( false );
			} )
			.catch( () => {
				setLoading( false );
			} );
	}, [] );

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<div>
			<TextControl
				label={ __( 'Select Quiz', 'premiaquiz' ) }
				value={ selectedQuiz?.id || '' }
				onChange={ ( id ) => {
					const quiz = quizzes.find(
						( q ) => q.id === parseInt( id, 10 )
					);
					setSelectedQuiz( quiz || null );
				} }
			/>
			<Button
				variant="secondary"
				href="/wp-admin/admin.php?page=premiaquiz"
			>
				{ __( 'Manage Quizzes', 'premiaquiz' ) }
			</Button>
		</div>
	);
};

export default QuizPanel;
