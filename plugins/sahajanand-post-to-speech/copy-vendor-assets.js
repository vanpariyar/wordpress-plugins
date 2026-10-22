/* eslint-disable no-console */
const fs = require( 'fs' );
const path = require( 'path' );

function copyFile( src, dest ) {
	if ( ! fs.existsSync( src ) ) {
		console.warn( `Source file not found: ${ src }` );
		return;
	}
	fs.mkdirSync( path.dirname( dest ), { recursive: true } );
	fs.copyFileSync( src, dest );
	console.log( `Copied ${ src } to ${ dest }` );
}

const pluginRoot = __dirname;

// 1. verify KittenTTS Web SDK
const kittenFile = path.join(
	pluginRoot,
	'assets',
	'vendor',
	'kittentts',
	'kittentts-web.js'
);
if ( fs.existsSync( kittenFile ) ) {
	console.log( `Verified KittenTTS Web SDK at ${ kittenFile }` );
} else {
	console.warn( `KittenTTS Web SDK missing at ${ kittenFile }` );
}

// 2. copy onnxruntime-web assets (if installed)
const onnxSrcDir = path.join(
	pluginRoot,
	'node_modules',
	'onnxruntime-web',
	'dist'
);
const onnxDestDir = path.join(
	pluginRoot,
	'assets',
	'vendor',
	'onnxruntime-web'
);

const onnxFiles = [
	'ort-wasm-simd-threaded.mjs',
	'ort-wasm-simd-threaded.wasm',
];

onnxFiles.forEach( ( file ) => {
	copyFile( path.join( onnxSrcDir, file ), path.join( onnxDestDir, file ) );
} );
