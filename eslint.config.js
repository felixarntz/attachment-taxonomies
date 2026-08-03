/**
 * WordPress dependencies
 */
const config = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...config,
	{
		files: [ 'tests/e2e/specs/**/*.js' ],
		rules: {
			'react-hooks/rules-of-hooks': 'off',
		},
	},
];
