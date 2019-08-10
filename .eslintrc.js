module.exports = {
	root: true,
	"extends": [
		"plugin:@wordpress/eslint-plugin/esnext"
	],
	overrides: [
		{
			files: [ 'assets/javascript/**/*.js' ],
			globals: {
				jQuery: true,
				document: true,
				window: true,
				wp: true,
			}
		}
	]
};