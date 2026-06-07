const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );

const buildDir = path.resolve( __dirname, 'build' );

const assetsConfig = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		editor: path.resolve( __dirname, 'editor.js' ),
	},
	output: {
		...defaultConfig.output,
		filename: ( chunkData ) => {
			return chunkData.chunk.name === 'editor'
				? 'editor.js'
				: '[name].min.[fullhash].js';
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
