/* global global */

import { render, screen, waitFor } from '@testing-library/react';
import QuizPanel from './quiz-panel';

// Mock the global fetch
global.fetch = jest.fn( () =>
	Promise.resolve( {
		json: () => Promise.resolve( [] ),
	} )
);

describe( 'QuizPanel', () => {
	beforeEach( () => {
		// Set up WordPress globals
		global.premiaquiz = {
			apiUrl: 'http://example.com/wp-json/premiaquiz/v1',
			nonce: 'test-nonce',
		};
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'renders without crashing', async () => {
		render( <QuizPanel /> );
		await waitFor( () => {
			expect( screen.getByText( /Select Quiz/i ) ).toBeTruthy();
		} );
	} );

	it( 'displays manage quizzes link', async () => {
		render( <QuizPanel /> );
		await waitFor( () => {
			expect( screen.getByText( /Manage Quizzes/i ) ).toBeTruthy();
		} );
	} );
} );
