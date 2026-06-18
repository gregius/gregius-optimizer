#!/usr/bin/env node

const fs = require( 'fs' );
const path = require( 'path' );
const archiver = require( 'archiver' );

const ROOT_DIR = path.resolve( __dirname, '..' );
const PLUGIN_SLUG = 'gregius-optimizer';
const OUTPUT_FILE = path.join( ROOT_DIR, 'build', 'zip', `${ PLUGIN_SLUG }.zip` );

const EXCLUDE_PATTERNS = [
	'.git',
	'.github',
	'node_modules',
	'assets/node_modules',
	'assets/src',
	'assets/editor.js',
	'package.json',
	'package-lock.json',
	'assets/package.json',
	'assets/package-lock.json',
	'webpack.config.js',
	'assets/webpack.config.js',
	'.gitignore',
	'assets/.gitignore',
	'bin',
	'build',
	'docs',
	'*.md',
	'*.log',
	'*.bak',
	'*.tmp',
	'*.zip',
];

function shouldExclude( filePath ) {
	const normalizedPath = filePath.replace( /\\/g, '/' );
	const pathParts = normalizedPath.split( '/' );
	const fileName = path.basename( normalizedPath );

	for ( const pattern of EXCLUDE_PATTERNS ) {
		if ( pattern.includes( '*' ) ) {
			if ( pattern.startsWith( '*.' ) ) {
				const extension = pattern.substring( 1 );
				if ( fileName.endsWith( extension ) ) {
					return true;
				}
			} else {
				const regex = new RegExp( '^' + pattern.replace( /\*/g, '.*' ).replace( /\//g, '\\/' ) );
				if ( regex.test( fileName ) || regex.test( normalizedPath ) ) {
					return true;
				}
			}
			continue;
		}
		if ( normalizedPath === pattern ) {
			return true;
		}
		if ( normalizedPath.startsWith( pattern + '/' ) ) {
			return true;
		}
		if ( pathParts[ 0 ] === pattern && ! pattern.includes( '/' ) ) {
			return true;
		}
	}
	return false;
}

async function createZip() {
	console.log( `Building ${ PLUGIN_SLUG } plugin zip...\n` );

	const buildDir = path.join( ROOT_DIR, 'build', 'zip' );
	if ( ! fs.existsSync( buildDir ) ) {
		fs.mkdirSync( buildDir, { recursive: true } );
	}

	if ( fs.existsSync( OUTPUT_FILE ) ) {
		fs.unlinkSync( OUTPUT_FILE );
	}

	const output = fs.createWriteStream( OUTPUT_FILE );
	const archive = archiver( 'zip', { zlib: { level: 9 } } );

	let filesAdded = 0;
	let filesSkipped = 0;

	output.on( 'close', () => {
		const sizeInMB = ( archive.pointer() / 1024 / 1024 ).toFixed( 2 );
		console.log( `\nPlugin zip created successfully!` );
		console.log( `   File: ${ OUTPUT_FILE }` );
		console.log( `   Size: ${ sizeInMB } MB` );
		console.log( `   Files included: ${ filesAdded }` );
		console.log( `   Files excluded: ${ filesSkipped }` );
	} );

	archive.on( 'warning', ( err ) => {
		if ( err.code === 'ENOENT' ) {
			console.warn( 'Warning:', err.message );
		} else {
			throw err;
		}
	} );

	archive.on( 'error', ( err ) => {
		throw err;
	} );

	archive.pipe( output );

	function addDirectory( dirPath, basePath = ROOT_DIR ) {
		const items = fs.readdirSync( dirPath );

		for ( const item of items ) {
			const fullPath = path.join( dirPath, item );
			const relativePath = path.relative( basePath, fullPath );
			const zipPath = path.join( PLUGIN_SLUG, relativePath );

			if ( shouldExclude( relativePath ) ) {
				filesSkipped++;
				continue;
			}

			const stats = fs.statSync( fullPath );

			if ( stats.isDirectory() ) {
				addDirectory( fullPath, basePath );
			} else 			if ( stats.isFile() ) {
				if ( fullPath === OUTPUT_FILE ) {
					filesSkipped++;
					continue;
				}
				archive.file( fullPath, { name: zipPath } );
				filesAdded++;
			}
		}
	}

	addDirectory( ROOT_DIR );
	await archive.finalize();
}

createZip().catch( ( err ) => {
	console.error( `Error creating zip:`, err.message );
	process.exit( 1 );
} );
