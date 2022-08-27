process.env.WP_DEVTOOL = false;

const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		"health-check": [
			path.resolve( process.cwd(), 'src/javascript', 'health-check.js' ),
			path.resolve( process.cwd(), 'src/styles', 'health-check.scss' )
		],
		"health-check-tools": [ path.resolve( process.cwd(), 'src/javascript', 'tools.js' ) ],
		"troubleshooting-mode": path.resolve( process.cwd(), 'src/javascript', 'troubleshooting-mode.js' ),
	}
};
