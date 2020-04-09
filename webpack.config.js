process.env.WP_DEVTOOL = false;

const path = require( 'path' );
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

module.exports = {
	...defaultConfig,
	entry: {
		"clipboard": "clipboard",
		"health-check": [ path.resolve( process.cwd(), 'src/javascript', 'health-check.js' ), path.resolve( process.cwd(), 'src/sass', 'health-check.scss' ) ],
		"troubleshooting-mode": path.resolve( process.cwd(), 'src/javascript', 'troubleshooting-mode.js' ),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'health-check/assets/javascript/' ),
	},
	module: {
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.s[ac]ss$/i,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					'sass-loader',
				],
			}
		]
	},
	plugins: [
		new CopyPlugin([
			{
				from: path.resolve( process.cwd(), 'src/php' ),
				to: path.resolve( process.cwd(), 'health-check' )
			},
			{
				from: path.resolve( process.cwd(), 'docs' ),
				to: path.resolve( process.cwd(), 'health-check' )
			}
		]),
		new MiniCssExtractPlugin({
			filename: '../css/[name].css',
		}),
	],
	externals: {
		react: 'React',
		'react-dom': 'ReactDOM',
		'__' : '@wordpress/i18n'
	}
};
