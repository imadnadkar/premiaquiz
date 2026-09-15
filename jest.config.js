module.exports = {
	preset: '@wordpress/jest-preset-default',
	setupFilesAfterSetup: [],
	testPathIgnorePatterns: [
		'/node_modules/',
		'/build/',
		'/vendor/',
	],
	collectCoverageFrom: [
		'src/**/*.{js,jsx}',
		'!src/**/*.test.{js,jsx}',
		'!src/**/*.stories.{js,jsx}',
	],
	coverageThreshold: {
		global: {
			branches: 0,
			functions: 0,
			lines: 0,
			statements: 0,
		},
	},
};
