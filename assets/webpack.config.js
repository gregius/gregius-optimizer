const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );

const buildDir = path.resolve( __dirname, 'build' );

const assetsConfig = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		editor: path.resolve( __dirname, 'editor.js' ),
		'deactivation-modal': path.resolve( __dirname, 'deactivation-modal.js' ),
	},
	output: {
		...defaultConfig.output,
		filename: ( chunkData ) => {
			if ( chunkData.chunk.name === 'editor' ) {
				return 'editor.js';
			}
			if ( chunkData.chunk.name === 'deactivation-modal' ) {
				return 'deactivation-modal.js';
			}
			return '[name].min.[fullhash].js';
		},
		path: buildDir,
	},
	plugins: [
		...defaultConfig.plugins,
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: [ buildDir ],
			protectWebpackAssets: false,
		} ),
	],
};

module.exports = assetsConfig;
